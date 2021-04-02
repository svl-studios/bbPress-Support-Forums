<?php

defined( 'ABSPATH' ) || exit;

/*
 * bbps - envato verify functions
 */
add_filter( 'bbp_current_user_can_publish_topics', 'bbps_envato_can_publish_topics_replies' );
add_filter( 'bbp_current_user_can_publish_replies', 'bbps_envato_can_publish_topics_replies' );

/**
 * Can publish topic replies.
 *
 * @param $retval
 *
 * @return bool|mixed
 */
function bbps_envato_can_publish_topics_replies( $retval ) {

	// check if user has valid purchase code.
	if ( get_option( '_bbps_envato_username', '' ) && get_option( '_bbps_envato_api_key', '' ) && is_user_logged_in() ) {
		$user_id = get_current_user_id();
		if ( $user_id ) {
			$envato_codes = get_user_meta( $user_id, 'envato_codes', true );
			if ( ! $envato_codes ) {
				if ( current_user_can( 'bbp_moderator' ) ) {
					return true;
				}

				return false;
			}
		}
	}

	return $retval;
}

add_action( 'personal_options_update', 'bbps_envato_bbp_user_edit_additional_save' );

/**
 * Can edit additional saves.
 */
function bbps_envato_bbp_user_edit_additional_save() {
	if ( wp_verify_nonce( sanitize_key( wp_unslash( $_POST['_wpnonce'] ?? '' ) ), 'envato_purchase_nonce' ) ) {
		if ( get_option( '_bbps_envato_username', '' ) && get_option( '_bbps_envato_api_key', '' ) && isset( $_POST['user_purchase_code'] ) && strlen( sanitize_text_field( wp_unslash( $_POST['user_purchase_code'] ) ) ) > 5 ) {
			$user_id      = bbp_get_user_id();
			$envato_codes = get_user_meta( $user_id, 'envato_codes', true );

			if ( ! $envato_codes ) {
				$envato_codes = array();
			}

			$purchase_code = strtolower( trim( sanitize_text_field( wp_unslash( $_POST['user_purchase_code'] ) ) ) );
			$api_result    = verify_purchase( $purchase_code );

			if ( is_array( $api_result ) ) {
				$envato_codes[ $purchase_code ] = $api_result;
				update_user_meta( $user_id, 'envato_codes', $envato_codes );
			}
		}
	}
}

/**
 * Verify purchase.
 *
 * @param string $purchase_code Purchase code.
 *
 * @return array|false|int
 */
function verify_purchase( string $purchase_code ) {
	$purchase_code = strtolower( $purchase_code );

	$api_url  = 'https://api.envato.com/v3/market/author/sale?code=' . $purchase_code;
	$response = wp_remote_get(
		$api_url,
		array(
			'headers' => array(
				'Authorization' => 'Bearer ' . get_option( '_bbps_envato_api_key', '' ),
				'User-Agent'    => 'SVL Studios Support Forum Verify',
			),
		)
	);

	if ( ! is_wp_error( $response ) ) {
		$api_result = json_decode( $response['body'], true );
		if ( $api_result && isset( $api_result['item'] ) && is_array( $api_result['item'] ) && isset( $api_result['item']['id'] ) ) {
			return $api_result;
		} else {
			return false; // invalid code.
		}
	} else {
		return 0; // error.
	}
}

/**
 * This is for SupportHub to operate correctly, passing the envato_codes
 * as a meta key along with user requests.
 */
add_filter( 'xmlrpc_prepare_user', '_bbps_xmlrpc_prepare_user', 10, 3 );

/**
 * Prepare user.
 *
 * @param array $_user  Meta user.
 * @param mixed $user   Unsed.
 * @param mixed $fields Unused.
 *
 * @return array
 */
function _bbps_xmlrpc_prepare_user( array $_user, $user, $fields ): array {
	$envato_codes = get_user_meta( $_user['user_id'], 'envato_codes', true );

	if ( is_array( $envato_codes ) ) {
		$_user['envato_codes'] = $envato_codes;
	}

	$_user['support_hub'] = array(
		'reply_options' => array(
			array(
				'title' => 'Mark Thread as Resolved',
				'field' => array(
					'type'    => 'check',
					'value'   => 'resolved',
					'name'    => 'thread_resolved',
					'checked' => true,
				),
			),
		),
	);

	return $_user;
}

add_action( 'wp_insert_post', '_bbps_wp_insert_post', 10, 3 );

/** Insert post.
 *
 * @param mixed   $post_ID Post ID.
 * @param WP_Post $post    Post object.
 * @param mixed   $update  Unsed.
 */
function _bbps_wp_insert_post( $post_ID, WP_Post $post, $update ) {
	if ( 'publish' === $post->post_status && 'reply' === $post->post_type ) {
		$check    = get_post_meta( $post_ID, 'support_hub', true );
		$forum_id = bbp_get_topic_forum_id( $post->post_parent );

		if ( $check ) {
			$data = json_decode( $check, true );

			if ( ! is_array( $data ) ) {
				$data = array();
			}

			if ( ! isset( $data['done'] ) || ! $data['done'] ) {
				$data['done'] = true;
				update_post_meta( $post_ID, 'support_hub', wp_json_encode( $data ) ); // Change the flag so we don't double process during this hook.

				// Run the usual bbpress hooks to update thread counts, send email replies and other stuff.
				do_action( 'bbp_new_reply', $post_ID, $post->post_parent, $forum_id, 0, $post->post_author, false, 0 );
				do_action( 'bbp_new_reply_post_extras', $post_ID );

				// TODO: only set thread status to resolved if ticked in extra data above.
				if ( isset( $data['thread_resolved'] ) && 'resolved' === $data['thread_resolved'] ) {
					update_post_meta( $post->post_parent, '_bbps_topic_status', 2 ); // Update the thread status to resolved.
				}
			}
		}
	}
}

add_action( 'bbp_user_edit_additional', 'bbps_envato_bbp_user_edit_additional' );
add_action( 'bbp_user_display_additional', 'bbps_envato_bbp_user_display_additional' );

/**
 * Display additional user info.
 */
function bbps_envato_bbp_user_display_additional() {
	bbps_envato_bbp_user_edit_additional( false );
}

/**
 * Edit additional info.
 *
 * @param bool $edit Can edit.
 */
function bbps_envato_bbp_user_edit_additional( $edit = true ) {
	if ( get_option( '_bbps_envato_username', '' ) && get_option( '_bbps_envato_api_key', '' ) ) {
		$envato_codes = get_user_meta( bbp_get_user_id(), 'envato_codes', true );

		?>
		<fieldset class="bbp-form">
			<legend><?php esc_html_e( 'Verified Purchases', 'bbpress' ); ?></legend>
			<?php

			if ( is_array( $envato_codes ) ) {

				?>
				<ul>
					<?php
					foreach ( $envato_codes as $license_code => $license_data ) {
						?>
						<li>
							Purchase <?php echo esc_html( $license_data['item']['name'] ); ?>
							on <?php echo esc_html( $license_data['sold_at'] ); ?> with
							username <?php echo esc_html( $license_data['buyer'] ); ?> (<?php echo esc_html( $license_data['licence'] ); ?>).
						</li>
						<?php

					}

					?>
				</ul>
			<?php } ?>
			<?php if ( $edit ) { ?>
				<?php // TODO:  embed this image. ?>
				<p>Submit additional purchase codes here (<a href="//dtbaker.net/admin/includes/plugin_envato/images/envato-license-code.gif" target="_blank">click
						here</a> for help locating your item purchase code):</p>

				<div class="form-group">
					<input type="text" class="form-control" name="user_purchase_code" id="register_widget_purchase_code" placeholder="Please paste your purchase code here">
				</div>
				<button type="submit" class="btn btn-default">Submit Purchase Code</button>
			<?php } ?>
		</fieldset>
		<?php

	}
}

add_action( 'bbp_template_before_pagination_loop', 'bbps_envato_notify_purchase_code' );

/**
 * Notify purcahase code.
 */
function bbps_envato_notify_purchase_code() {
	if ( get_option( '_bbps_envato_username', '' ) && get_option( '_bbps_envato_api_key', '' ) && is_user_logged_in() ) {
		$user_id = get_current_user_id();

		if ( $user_id ) {
			$envato_codes = get_user_meta( $user_id, 'envato_codes', true );

			if ( ! $envato_codes ) {
				$envato_codes = array();
			}

			if ( wp_verify_nonce( sanitize_key( wp_unslash( $_POST['_wpnonce'] ?? '' ) ), 'envato_purchase_nonce' ) ) {
				if ( isset( $_POST['user_purchase_code'] ) && strlen( sanitize_text_field( wp_unslash( $_POST['user_purchase_code'] ) ) ) > 5 ) {
					$purchase_code = strtolower( trim( sanitize_text_field( wp_unslash( $_POST['user_purchase_code'] ) ) ) );
					$api_result    = verify_purchase( $purchase_code );

					if ( is_array( $api_result ) ) {
						$envato_codes[ $purchase_code ] = $api_result;
						update_user_meta( $user_id, 'envato_codes', $envato_codes );

						?>
						<div class="alert alert-info" role="alert">
							Thank you <strong><?php echo esc_html( $api_result['buyer'] ); ?></strong>! <br/>
							You have verified your purchase of <em><?php echo esc_html( $api_result['item']['name'] ); ?></em> from
							<em><?php echo esc_html( $api_result['sold_at'] ); ?></em>. <br/>
							You can verify additional purchases from your <a href="<?php bbp_user_profile_edit_url( $user_id ); ?>">profile page</a>.
						</div>
						<?php
					} elseif ( 0 === $api_result ) {
						?>
						<div class="alert alert-error" role="alert">Sorry a temporary error occurred while processing your license code request.</div>
						<?php
					} else {
						?>
						<div class="alert alert-error" role="alert">Sorry this license code is not valid. Please send through an email support request for
							assistance.
						</div>
						<?php
					}
				}
			}

			if ( ! $envato_codes ) {
				?>
				<div class="alert alert-info" role="alert">
					<div style="padding-bottom: 10px">
						<strong>Notice:</strong> To continue posting on the Support Forum please enter your Item Purchase Code below. This helps us verify
						buyers and provide a better level of service. Please <a href="//dtbaker.net/admin/includes/plugin_envato/images/envato-license-code.gif"
																				target="_blank">click here</a> for help finding your purchase code.
					</div>
					<form name="envato_purchase_code" id="envato_purchase_code" action="" method="post">
						<?php wp_nonce_field( 'envato_purchase_nonce' ); ?>
						<div class="form-group">
							<input type="text" class="form-control" name="user_purchase_code" id="register_widget_purchase_code" placeholder="Please paste your purchase code here">
						</div>
						<button type="submit" class="btn btn-default">Submit Purchase Code</button>
					</form>
				</div>
				<?php
			}
		}
	}
}

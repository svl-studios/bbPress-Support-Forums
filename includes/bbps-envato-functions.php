<?php

defined( 'ABSPATH' ) || exit;

/*
bbps - envato verify functions
*/

add_filter( 'bbp_current_user_can_publish_topics', 'bbps_envato_can_publish_topics_replies' );
add_filter( 'bbp_current_user_can_publish_replies', 'bbps_envato_can_publish_topics_replies' );

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
function bbps_envato_bbp_user_edit_additional_save() {
	if ( get_option( '_bbps_envato_username', '' ) && get_option( '_bbps_envato_api_key', '' ) && isset( $_REQUEST['user_purchase_code'] ) && strlen( $_REQUEST['user_purchase_code'] ) > 5 ) {
		$user_id      = bbp_get_user_id();
		$envato_codes = get_user_meta( $user_id, 'envato_codes', true );
		if ( ! $envato_codes ) {
			$envato_codes = array();
		}
		$purchase_code = strtolower( trim( $_POST['user_purchase_code'] ) );
		$api_result    = verify_purchase( $purchase_code );
		// echo "Verify purchase code $purchase_code with api result is: ";print_r($api_result);exit;
		if ( is_array( $api_result ) ) {
			$envato_codes[ $purchase_code ] = $api_result;
			update_user_meta( $user_id, 'envato_codes', $envato_codes );
		}
	}
}

function verify_purchase( $purchase_code ) {
	$purchase_code = strtolower( $purchase_code );
	// $api_url = 'http://marketplace.envato.com/api/edge/' . get_option('_bbps_envato_username','') . '/' . get_option('_bbps_envato_api_key',''). '/verify-purchase:'.$purchase_code.'.json';
	// $response  = wp_remote_get($api_url);
	$api_url  = 'https://api.envato.com/v1/market/private/user/verify-purchase:' . $purchase_code . '.json';
	$response = wp_remote_get(
		$api_url,
		array(
			'user-agent' => 'dtbaker forums verify',
			'headers'    => array(
				'Authorization' => 'Bearer ' . get_option( '_bbps_envato_api_key', '' ),
			),
		)
	);
	if ( ! is_wp_error( $response ) ) {
		$api_result = @json_decode( $response['body'], true );
		if ( $api_result && isset( $api_result['verify-purchase'] ) && is_array( $api_result['verify-purchase'] ) && isset( $api_result['verify-purchase']['item_id'] ) ) {
			return $api_result['verify-purchase'];
		} else {
			return false; // invalid code
		}
	} else {
		return 0; // error
	}
}

// this is for SupportHub to operate correctly, passing the envato_codes as a meta key along with user requests.
// apply_filters( 'xmlrpc_prepare_user', $_user, $user, $fields )
add_filter( 'xmlrpc_prepare_user', '_bbps_xmlrpc_prepare_user', 10, 3 );
function _bbps_xmlrpc_prepare_user( $_user, $user, $fields ) {
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

// do_action( 'wp_insert_post', $post_ID, $post, $update );
add_action( 'wp_insert_post', '_bbps_wp_insert_post', 10, 3 );
function _bbps_wp_insert_post( $post_ID, $post, $update ) {
	if ( $post->post_status == 'publish' && $post->post_type == 'reply' ) {
		$check    = get_post_meta( $post_ID, 'support_hub', true );
		$forum_id = bbp_get_topic_forum_id( $post->post_parent );
		// mail('dtbaker@gmail.com','WP Insert Post Done 2',var_export($post,true).var_export($check,true)."\n" . $forum_id);
		if ( $check ) {
			$data = @json_decode( $check, true );
			if ( ! is_array( $data ) ) {
				$data = array();
			}
			if ( ! isset( $data['done'] ) || ! $data['done'] ) {
				$data['done'] = true;
				update_post_meta( $post_ID, 'support_hub', json_encode( $data ) ); // change the flag so we don't double process during this hook
				// run the usual bbpress hooks to update thread counts, send email replies and other stuff
				do_action( 'bbp_new_reply', $post_ID, $post->post_parent, $forum_id, 0, $post->post_author, false, 0 );
				do_action( 'bbp_new_reply_post_extras', $post_ID );
				// todo: only set thread status to resolved if ticked in extra data above.
				if ( isset( $data['thread_resolved'] ) && $data['thread_resolved'] == 'resolved' ) {
					update_post_meta( $post->post_parent, '_bbps_topic_status', 2 ); // update the thread status to resolved
				}
			}
		}
	}
}

add_action( 'bbp_user_edit_additional', 'bbps_envato_bbp_user_edit_additional' );
add_action( 'bbp_user_display_additional', 'bbps_envato_bbp_user_display_additional' );
function bbps_envato_bbp_user_display_additional() {
	bbps_envato_bbp_user_edit_additional( false );
}

function bbps_envato_bbp_user_edit_additional( $edit = true ) {
	if ( get_option( '_bbps_envato_username', '' ) && get_option( '_bbps_envato_api_key', '' ) ) {
		$envato_codes = get_user_meta( bbp_get_user_id(), 'envato_codes', true );
		?>
		<fieldset class="bbp-form">
			<legend><?php _e( 'Verified Purchases', 'bbpress' ); ?></legend>

			<?php
			if ( is_array( $envato_codes ) ) {
				// print_r($envato_codes);
				?>
				<ul>
					<?php
					foreach ( $envato_codes as $license_code => $license_data ) {
						?>
						<li>
							Purchase <?php echo $license_data['item_name']; ?>
							on <?php echo $license_data['created_at']; ?> with
							username <?php echo $license_data['buyer']; ?> (<?php echo $license_data['licence']; ?>).
						</li>
						<?php
					}
					?>
				</ul>
			<?php } ?>

			<?php if ( true ) { ?>
				<p>Submit additional purchase codes here (<a
							href="//dtbaker.net/admin/includes/plugin_envato/images/envato-license-code.gif" target="_blank">click
						here</a> for help locating your item purchase code):</p>

				<div class="form-group">
					<input type="text" class="form-control" name="user_purchase_code" id="register_widget_purchase_code"
						   placeholder="Please paste your purchase code here">
				</div>
				<button type="submit" class="btn btn-default">Submit Purchase Code</button>
			<?php } ?>

		</fieldset>
		<?php
	}
}

add_action( 'bbp_template_before_pagination_loop', 'bbps_envato_notify_purchase_code' );
function bbps_envato_notify_purchase_code() {
	if ( get_option( '_bbps_envato_username', '' ) && get_option( '_bbps_envato_api_key', '' ) && is_user_logged_in() ) {
		$user_id = get_current_user_id();
		if ( $user_id ) {
			$envato_codes = get_user_meta( $user_id, 'envato_codes', true );
			if ( ! $envato_codes ) {
				$envato_codes = array();
			}
			if ( isset( $_POST['user_purchase_code'] ) && strlen( $_POST['user_purchase_code'] ) > 5 ) {
				$purchase_code = strtolower( trim( $_POST['user_purchase_code'] ) );
				$api_result    = verify_purchase( $purchase_code );
				if ( is_array( $api_result ) ) {
					$envato_codes[ $purchase_code ] = $api_result;
					update_user_meta( $user_id, 'envato_codes', $envato_codes );
					?>
					<div class="alert alert-info" role="alert">
						Thank you <strong><?php echo $api_result['buyer']; ?></strong>! <br/>
						You have verified your purchase of <em><?php echo $api_result['item_name']; ?></em> from
						<em><?php echo $api_result['created_at']; ?></em>. <br/>
						You can verify additional purchases from your <a href="<?php bbp_user_profile_edit_url( $user_id ); ?>">profile page</a>.
					</div>
					<?php
				} elseif ( $api_result === 0 ) {
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

			if ( ! $envato_codes ) {
				?>
				<div class="alert alert-info" role="alert">
					<div style="padding-bottom: 10px">
						<strong>Notice:</strong> To continue posting on the Support Forum please enter your Item Purchase Code below. This helps us verify
						buyers and provide a better level of service. Please <a href="//dtbaker.net/admin/includes/plugin_envato/images/envato-license-code.gif"
																				target="_blank">click here</a> for help finding your purchase code.
					</div>
					<form name="envato_purchase_code" id="envato_purchase_code" action="" method="post">
						<div class="form-group">
							<input type="text" class="form-control" name="user_purchase_code" id="register_widget_purchase_code"
								   placeholder="Please paste your purchase code here">
						</div>
						<button type="submit" class="btn btn-default">Submit Purchase Code</button>
					</form>

				</div>
				<?php
			}
		}
	}
}

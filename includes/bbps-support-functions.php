<?php

defined( 'ABSPATH' ) || exit;

/**
 * Get update capabilities.
 */
function bbps_get_update_capabilities() {
	$the_current_user = wp_get_current_user();
	$user_id          = $the_current_user->ID;

	$topic_author_id = bbp_get_topic_author_id();
	$permissions     = get_option( '_bbps_status_permissions' );
	$can_edit        = '';

	// check the users permission this is easy.
	if ( true === (bool) $permissions['admin'] && current_user_can( 'administrator' ) || true === (bool) $permissions['mod'] && current_user_can( 'bbp_moderator' ) ) {
		$can_edit = true;
	}

	// now check the current user against the topic creator are they they same person and can they change the status?.
	if ( $user_id === $topic_author_id && true === (bool) $permissions['user'] ) {
		$can_edit = true;
	}

	return $can_edit;
}

/*
 @TODO ASAP */
/* split this function up as its getting way to big now with all these extra features */

add_action( 'bbp_template_before_single_topic', 'bbps_add_support_forum_features' );

/**
 * Add support forum features.
 */
function bbps_add_support_forum_features() {

	// only display all this stuff if the support forum option has been selected.
	if ( bbps_is_support_forum( bbp_get_forum_id() ) ) {
		$can_edit = bbps_get_update_capabilities();

		if ( $can_edit ) {
			$topic_id = bbp_get_topic_id();
			$status   = bbps_get_topic_status( $topic_id );
			$forum_id = bbp_get_forum_id();
			$user_id  = get_current_user_id();

			?>
			<div class="row">
				<div class="col-md-6">
					<div id="bbps_support_forum_options" class="well">
						<?php

						// get out the option to tell us who is allowed to view and update the drop down list.
						if ( true === $can_edit ) {
							bbps_generate_status_options( $topic_id, $status );
						} else {
							?>
							This topic is:
							<?php
							echo esc_html( $status );
						}
						?>
					</div>
					<?php

					// has the user enabled the move topic feature?
					if ( ( true === (bool) get_option( '_bbps_enable_topic_move' ) ) && ( current_user_can( 'administrator' ) || current_user_can( 'bbp_moderator' ) ) ) {

						?>
						<div id="bbps_support_forum_move" class="span6 well">
							<form id="bbps-topic-move" name="bbps_support_topic_move" action="" method="post">
								<label for="bbp_forum_id">Move topic to: </label><?php bbp_dropdown(); ?>
								<input type="submit" value="Move" name="bbps_topic_move_submit"/>
								<input type="hidden" value="bbps_move_topic" name="bbps_action"/>
								<input type="hidden" value="<?php echo esc_attr( $topic_id ); ?>" name="bbps_topic_id"/>
								<input type="hidden" value="<?php echo esc_attr( $forum_id ); ?>" name="bbp_old_forum_id"/>
							</form>
						</div>
					<?php } ?>
				</div>
			</div> <!-- row -->
			<?php
		}
	}
}

add_action( 'bbp_theme_before_reply_form_notices', 'bbps_bbp_theme_before_reply_form_submit_button' );

/**
 * Before form reply button.
 */
function bbps_bbp_theme_before_reply_form_submit_button() {

	// only display all this stuff if the support forum option has been selected.
	if ( bbps_is_support_forum( bbp_get_forum_id() ) ) {
		$can_edit = bbps_get_update_capabilities();

		if ( $can_edit ) {
			$topic_id = bbp_get_topic_id();
			$status   = bbps_get_topic_status( $topic_id );
			$forum_id = bbp_get_forum_id();
			$user_id  = get_current_user_id();

			?>
			<div class="row">
				<div class="col-md-6">
					<div id="bbps_support_forum_options" class="well">
						<?php
						// get out the option to tell us who is allowed to view and update the drop down list.
						if ( true === $can_edit ) {
							bbps_generate_status_options( $topic_id, $status, false );
						} else {
							?>
							This topic is:
							<?php
							echo esc_html( $status );
						}
						?>
					</div>
				</div>
				<div class="col-md-6">
					<div id="bbps_support_forum_options_mod" class="well">
						<?php

						// get out the option to tell us who is allowed to view and update the drop down list.
						if ( true === $can_edit ) {

							?>
							<label for="bbps_minutes_spent">Time spent on this thread: </label>
							<br/>
							<input type="button" name="gogotimer" onclick="jQuery(this).hide(); return bbps_ticktock();" value="Start Timer">
							<input type="text" name="bbps_minutes_spent" id="bbps_minutes_spent" value="0.00" size="10"> minutes
							<script type="text/javascript">
								var bbps_ticktock_seconds = 0, bbps_ticktock_run = true, bbps_ticktock_doing = false;
								jQuery( function() {
									jQuery( '#bbps_minutes_spent' ).change( function() {
										if ( !bbps_ticktock_doing ) bbps_ticktock_run = false;
									} );
								} );

								function bbps_ticktock() {
									// runs every seconds.
									bbps_ticktock_doing = true;
									bbps_ticktock_seconds++;
									var minutes = Math.floor( bbps_ticktock_seconds / 60 );
									var seconds = bbps_ticktock_seconds - (minutes * 60);
									jQuery( '#bbps_minutes_spent' ).val( Math.floor( (minutes + (seconds / 60)) * 100 ) / 100 );
									if ( bbps_ticktock_run ) {
										setTimeout( bbps_ticktock, 1000 );
									}
									bbps_ticktock_doing = false;
									return false;
								}
							</script>
						<?php
						// get a history of items for this thread.
						$thread_time = get_post_meta( $topic_id, '_bbps_topic_minutes', true );

						if ( ! is_array( $thread_time ) ) {
							$thread_time = array();
						}

						if ( count( $thread_time ) ) {
						?>
							<ul>
								<?php
								foreach ( $thread_time as $thread_tim ) {
									$user_info = get_userdata( $thread_tim['user_id'] );
									?>
									<li>
										User <?php echo esc_html( $user_info->user_login ); ?> spent <?php echo esc_html( $thread_tim['time'] ); ?> minutes
										on <?php echo esc_html( gmdate( 'Y-m-d', $thread_tim['recorded'] ) ); ?>
									</li>
								<?php } ?>
							</ul>
							<?php
						}
						}
						?>
					</div>
				</div>
			</div> <!-- row -->
			<?php
		}
	}
}

/**
 * Get topic status.
 *
 * @param mixed $topic_id Topic ID.
 *
 * @return string
 */
function bbps_get_topic_status( $topic_id ): string {
	$default = get_option( '_bbps_default_status' );
	$status  = get_post_meta( $topic_id, '_bbps_topic_status', true );

	// todo: not hard code these if we let the users add their own status.
	if ( $status ) {
		$switch = $status;
	} else {
		$switch = $default;
	}

	switch ( $switch ) {
		case 1:
			return 'not resolved';
		case 2:
			return 'resolved';
		case 3:
			return 'not a support question';
	}
}

/**
 * Generates a drop down list with the support forum topic status only for admin and moderators tho.
 *
 * @param mixed  $topic_id Topic ID.
 * @param string $status   Status.
 * @param bool   $button   Button.
 */
function bbps_generate_status_options( $topic_id, string $status, $button = true ) {
	$dropdown_options = get_option( '_bbps_used_status' );
	$status           = get_post_meta( $topic_id, '_bbps_topic_status', true );
	$default          = get_option( '_bbps_default_status' );

	// only use the default value as selected if the topic doesnt ahve a status set.
	if ( $status ) {
		$value = $status;
	} else {
		$value = $default;
	}

	if ( $button ) {

		?>
		<form id="bbps-topic-status" name="bbps_support" action="" method="post">
	<?php } ?>
	<label for="bbps_support_options">Change topic status: </label>
	<select name="bbps_support_option" id="bbps_support_options">
		<?php
		// we only want to display the options the user has selected. the long term goal is to let users add their own forum statuses.
		if ( true === (bool) $dropdown_options['res'] ) {

			?>
			<option value="1" <?php selected( $value, 1 ); ?> >not resolved</option>
			<?php

		}
		if ( true === (bool) $dropdown_options['notres'] ) {

			?>
			<option value="2" <?php selected( $value, 2 ); ?> >resolved</option>
			<?php

		}
		if ( true === (bool) $dropdown_options['notsup'] ) {

			?>
			<option value="3" <?php selected( $value, 3 ); ?> >not a support question</option> <?php } ?>
	</select>
	<?php if ( $button ) { ?>
		<input type="submit" value="Update" name="bbps_support_submit"/>
	<?php } ?>
	<input type="hidden" value="bbps_update_status" name="bbps_action"/>
	<input type="hidden" value="<?php echo esc_attr( $topic_id ); ?>" name="bbps_topic_id"/>
	<?php if ( $button ) { ?>
		</form>
		<?php
	}
}

/**
 * Update status.
 */
function bbps_update_status() {
	$can_edit = bbps_get_update_capabilities();

	if ( ! $can_edit ) {
		return;
	}

	$topic_id = $_POST['bbps_topic_id'];
	$status   = $_POST['bbps_support_option'];

	// check if the topic already has resolved meta - if it does then delete it before readding
	// we do this so that any topic updates will have a new meta id for sorting recently resolved etc.
	$has_status = get_post_meta( $topic_id, '_bbps_topic_status', true );
	$is_urgent  = get_post_meta( $topic_id, '_bbps_urgent_topic', true );
	$is_claimed = get_post_meta( $topic_id, '_bbps_topic_claimed', true );

	if ( $has_status ) {
		delete_post_meta( $topic_id, '_bbps_topic_status' );
	}

	// if the status is going to resolved we need to check for claimed and urgent meta and delete this to
	// 2 == resolved status :).
	if ( 2 === (int) $status ) {
		if ( $is_urgent ) {
			delete_post_meta( $topic_id, '_bbps_urgent_topic' );
		}
		if ( $is_claimed ) {
			delete_post_meta( $topic_id, '_bbps_topic_claimed' );
		}
	}

	update_post_meta( $topic_id, '_bbps_topic_status', $status );
	if ( isset( $_POST['bbps_minutes_spent'] ) ) {
		$thread_time = get_post_meta( $topic_id, '_bbps_topic_minutes', true );
		if ( ! is_array( $thread_time ) ) {
			$thread_time = array();
		}
		$id = get_current_user_id();
		if ( $id ) {
			$thread_time[] = array(
					'user_id'  => $id,
					'recorded' => time(),
					'time'     => $_POST['bbps_minutes_spent'],
			);
		}

		update_post_meta( $topic_id, '_bbps_topic_minutes', $thread_time );
	}
}

/**
 * Move topic.
 */
function bbps_move_topic() {
	global $wpdb;

	$topic_id     = $_POST['bbps_topic_id'];
	$new_forum_id = $_POST['bbp_forum_id'];
	$old_forum_id = $_POST['bbp_old_forum_id'];

	// move the topics we will need to run a recount to after this is done.
	if ( '' !== $topic_id && '' !== $new_forum_id ) {

		// phpcs:ignore
		$wpdb->update(
				'wp_posts',
				array(
						'post_parent' => $new_forum_id,
				),
				array(
						'ID' => $topic_id,
				)
		);

		update_post_meta( $topic_id, '_bbp_forum_id', $new_forum_id );

		// update all the forum meta and counts for the old forum and the new forum.
		bbp_update_forum( array( 'forum_id' => $new_forum_id ) );
		bbp_update_forum( array( 'forum_id' => $old_forum_id ) );
	}
}

/**
 * Checks the status of the option and generates and displays
 * a link based on if the topic is already marked as urgent.
 *
 * @param array $args     Args.
 * @param array $defaults Defaults.
 *
 * @return array|object
 */
function bbps_filter_get_topic_admin_links( array $args, $defaults = array() ) {

	// bail if option not set or user permission not up to scratch or if the forum has not been set as a support forum.
	if ( ( true === (bool) get_option( '_bbps_status_permissions_urgent' ) ) && ( current_user_can( 'administrator' ) || current_user_can( 'bbp_moderator' ) ) && ( bbps_is_support_forum( bbp_get_forum_id() ) ) ) {
		$topic_id = bbp_get_topic_id();

		// 1 = urgent topic 0 or nothing is topic not urgent so we give the admin / mods the chance to make it urgent.
		if ( true !== (bool) get_post_meta( $topic_id, '_bbps_urgent_topic', true ) ) {
			$urgent_uri = add_query_arg(
					array(
							'action'   => 'bbps_make_topic_urgent',
							'topic_id' => $topic_id,
					)
			);

			$args['links']['urgent'] = '<a href="' . $urgent_uri . '">Urgent</a>';
		}
	}

	return wp_parse_args( $args, $defaults );
}

add_filter( 'bbp_before_get_topic_admin_links_parse_args', 'bbps_filter_get_topic_admin_links' );

// check if the url generated above has been clicked and generated.
if ( ( isset( $_GET['action'] ) && isset( $_GET['topic_id'] ) && 'bbps_make_topic_urgent' === $_GET['action'] ) ) {
	bbps_urgent_topic();
}

if ( ( isset( $_GET['action'] ) && isset( $_GET['topic_id'] ) && 'bbps_make_topic_not_urgent' === $_GET['action'] ) ) {
	bbps_not_urgent_topic();
}

/**
 * Urgent topic.
 */
function bbps_urgent_topic() {
	$topic_id = $_GET['topic_id'];
	update_post_meta( $topic_id, '_bbps_urgent_topic', 1 );
}

/**
 * Not urgent topic.
 */
function bbps_not_urgent_topic() {
	$topic_id = $_GET['topic_id'];
	delete_post_meta( $topic_id, '_bbps_urgent_topic' );
}

/**
 * Display a message to all admin on the single topic view so they know a topic is
 * urgent also give them a link to check it as not urgent.
 */
function display_urgent_message() {

	// only display to the correct people.
	if ( ( true === (bool) get_option( '_bbps_status_permissions_urgent' ) ) && ( current_user_can( 'administrator' ) || current_user_can( 'bbp_moderator' ) ) && ( bbps_is_support_forum( bbp_get_forum_id() ) ) ) {
		$topic_id = bbp_get_topic_id();

		// topic is urgent so make a link.
		if ( true === (bool) get_post_meta( $topic_id, '_bbps_urgent_topic', true ) ) {
			$urgent_uri = add_query_arg(
					array(
							'action'   => 'bbps_make_topic_not_urgent',
							'topic_id' => $topic_id,
					)
			);

			echo "<div class='bbps-support-forums-message'> This topic is currently marked as urgent change the status to " . '<a href="' . esc_url( $urgent_uri ) . '">Not Urgent?</a></div>';
		}
	}
}

add_action( 'bbp_template_before_single_topic', 'display_urgent_message' );

/**
 * Topic Claim code starts here.
 */
function bbps_claim_topic_link() {

	// bail if option not set or user permission not up to scratch or if the forum has not been set as a support forum.
	if ( ( true === (bool) get_option( '_bbps_claim_topic' ) ) && ( current_user_can( 'administrator' ) || current_user_can( 'bbp_moderator' ) ) && ( bbps_is_support_forum( bbp_get_forum_id() ) ) ) {
		$topic_id = bbp_get_topic_id();

		$the_current_user = wp_get_current_user();

		$user_id = $the_current_user->ID;

		// anything greater than one will be claimed as it saves the claimed user id and will set this back to 0 if a topic is unclaimed.
		if ( get_post_meta( $topic_id, '_bbps_topic_claimed', true ) < 1 ) {
			$urgent_uri = add_query_arg(
					array(
							'action'   => 'bbps_claim_topic',
							'topic_id' => $topic_id,
							'user_id'  => $user_id,
					)
			);

			echo '<span class="bbp-admin-links bbps-links"><a href="' . esc_url( $urgent_uri ) . '">Claim </a> | </span>';
		}
	}
}

add_action( 'bbp_theme_after_reply_admin_links', 'bbps_claim_topic_link' );

// check for the link to be clicked.
if ( ( isset( $_GET['action'] ) && isset( $_GET['topic_id'] ) && isset( $_GET['user_id'] ) && 'bbps_claim_topic' === $_GET['action'] ) ) {
	bbps_claim_topic();
}

if ( ( isset( $_GET['action'] ) && isset( $_GET['topic_id'] ) && isset( $_GET['user_id'] ) && 'bbps_unclaim_topic' === $_GET['action'] ) ) {
	bbps_unclaim_topic();
}

/**
 * Claim topic.
 */
function bbps_claim_topic() {
	$user_id  = $_GET['user_id'];
	$topic_id = $_GET['topic_id'];

	// subscribe the user to the topic - this is a bbpress function.
	bbp_add_user_subscription( $user_id, $topic_id );

	// record who has claimed the topic in postmeta for use within this plugin.
	update_post_meta( $topic_id, '_bbps_topic_claimed', $user_id );
}

/**
 *
 */
function bbps_unclaim_topic() {
	$user_id  = $_GET['user_id'];
	$topic_id = $_GET['topic_id'];

	// subscribe the user to the topic - this is a bbpress function.
	bbp_remove_user_subscription( $user_id, $topic_id );

	// reupdate the postmeta with an id of 0 this is unclaimed now.
	delete_post_meta( $topic_id, '_bbps_topic_claimed' );
}

/**
 * Display claimed message.
 */
function bbps_display_claimed_message() {
	$topic_author_id = bbp_get_topic_author_id();
	$the_user        = wp_get_current_user();
	$user_id         = $the_user->ID;

	// we want to display the claimed topic message to the topic owner to.
	if ( ( true === (bool) get_option( '_bbps_claim_topic' ) ) && ( current_user_can( 'administrator' ) || current_user_can( 'bbp_moderator' ) || $topic_author_id === $user_id ) && ( bbps_is_support_forum( bbp_get_forum_id() ) ) ) {
		$topic_id        = bbp_get_topic_id();
		$claimed_user_id = get_post_meta( $topic_id, '_bbps_topic_claimed', true );

		if ( $claimed_user_id > 0 ) {
			$user_info         = get_userdata( $claimed_user_id );
			$claimed_user_name = $user_info->user_login;
		}

		if ( $claimed_user_id > 0 && $claimed_user_id !== $user_id ) {
			echo "<div class='bbps-support-forums-message'>This topic is currently claimed by " . esc_html( $claimed_user_name ) . ', they will be working on it now. </div>';
		}

		// the person who claimed it can unclaim it this will also unsubscribe them when they do.
		if ( $claimed_user_id === $user_id ) {
			$urgent_uri = add_query_arg(
					array(
							'action'   => 'bbps_unclaim_topic',
							'topic_id' => $topic_id,
							'user_id'  => $user_id,
					)
			);

			echo '<div class="bbps-support-forums-message"> You currently own this topic would you like to <a href="' . esc_url( $urgent_uri ) . '">Unclame</a> it?</div>';
		}
	}
}

add_action( 'bbp_template_before_single_topic', 'bbps_display_claimed_message' );

/**
 * Assign to another user.
 */
function bbps_assign_topic_form() {
	if ( ( true === (bool) get_option( '_bbps_topic_assign' ) ) && ( current_user_can( 'administrator' ) || current_user_can( 'bbp_moderator' ) ) ) {
		$topic_id        = bbp_get_topic_id();
		$topic_assigned  = get_post_meta( $topic_id, 'bbps_topic_assigned', true );
		$the_user        = wp_get_current_user();
		$current_user_id = $the_user->ID;

		?>
		<div id="bbps_support_forum_options" class="well">
			<?php

			$user_login = $current_user->user_login;
			if ( ! empty( $topic_assigned ) ) {
				if ( $topic_assigned === $current_user_id ) {

					?>
					<div class='bbps-support-forums-message'> This topic is assigned to you!</div>
					<?php

				} else {
					$user_info          = get_userdata( $topic_assigned );
					$assigned_user_name = $user_info->user_login;

					?>
					<div class='bbps-support-forums-message'> This topic is already assigned to: <?php echo esc_html( $assigned_user_name ); ?></div>
					<?php

				}
			}

			?>
			<div id="bbps_support_topic_assign">
				<form id="bbps-topic-assign" name="bbps_support_topic_assign" action="" method="post">
					<?php bbps_user_assign_dropdown(); ?>
					<input type="submit" value="Assign" name="bbps_support_topic_assign"/>
					<input type="hidden" value="bbps_assign_topic" name="bbps_action"/>
					<input type="hidden" value="<?php echo esc_attr( $topic_id ); ?>" name="bbps_topic_id"/>
				</form>
			</div>
		</div>
		<?php

	}
}

add_action( 'bbp_template_before_single_topic', 'bbps_assign_topic_form' );

/**
 * User assign dropdown.
 */
function bbps_user_assign_dropdown() {
	$args    = array();
	$args[0] = 'ID';
	$args[1] = 'user_login';
	$args[2] = 'user_email';

	$wp_user_search = new WP_User_Query(
			array(
					'role'   => 'administrator',
					'fields' => $args,
			)
	);

	$admins = $wp_user_search->get_results();

	$wp_user_search = new WP_User_Query(
			array(
					'role'   => 'bbp_moderator',
					'fields' => $args,
			)
	);

	$moderators      = $wp_user_search->get_results();
	$all_users       = array_merge( $moderators, $admins );
	$topic_id        = bbp_get_topic_id();
	$claimed_user_id = get_post_meta( $topic_id, 'bbps_topic_assigned', true );

	if ( ! empty( $all_users ) ) {
		if ( $claimed_user_id > 0 ) {
			$text = 'Reassign topic to: ';
		} else {
			$text = 'Assign topic to: ';
		}

		echo esc_html( $text );

		?>
		<select name="bbps_assign_list" id="bbps_support_options">
			<option value="">Unassigned</option>
			<?php
			foreach ( $all_users as $user ) {
				?>
				<option value="<?php echo esc_attr( $user->ID ); ?>"> <?php echo esc_html( $user->user_login ); ?></option>
				<?php
			}
			?>
		</select>
		<?php

	}
}

/**
 * Assign topic.
 */
function bbps_assign_topic() {
	$user_id  = $_POST['bbps_assign_list'];
	$topic_id = $_POST['bbps_topic_id'];

	if ( $user_id > 0 ) {
		$userinfo   = get_userdata( $user_id );
		$user_email = $userinfo->user_email;
		$post_link  = get_permalink( $topic_id );

		// add the user as a subscriber to the topic and send them an email to let them know they have been assigned to a topic.
		bbp_add_user_subscription( $user_id, $topic_id );

		// update the post meta with the assigned users id.
		$assigned = (bool) update_post_meta( $topic_id, 'bbps_topic_assigned', $user_id );

		$message = <<< EMAILMSG
		You have been assigned to the following topic, by another forum moderator or the site administrator. Please take a look at it when you get a chance.
		$post_link
EMAILMSG;
		if ( true === $assigned ) {
			wp_mail( $user_email, 'A forum topic has been assigned to you', $message );
		}
	}
}

// I believe this Problem is because your Plugin is loading at the wrong time, and can be fixed by wrapping your plugin in a wrapper class.
// need to find a hook or think of the best way to do this
add_action( 'init', 'dtbaker_bbps_activation_done' );

/**
 * Activation done.
 */
function dtbaker_bbps_activation_done() {
	if ( ! empty( $_POST['bbps_support_topic_assign'] ) ) {
		bbps_assign_topic( $_POST );
	}

	if ( ! empty( $_POST['bbps_action'] ) && 'bbps_update_status' === $_POST['bbps_action'] ) {
		bbps_update_status();
	}

	if ( ! empty( $_POST['bbps_topic_move_submit'] ) ) {
		bbps_move_topic();
	}
}

/**
 * Adds a class and status to the front of the topic title.
 *
 * @param string $title    Title.
 * @param mixed  $topic_id Topic ID.
 */
function bbps_modify_title( string $title, $topic_id = 0 ) {
	$topic_id        = bbp_get_topic_id( $topic_id );
	$title           = '';
	$topic_author_id = bbp_get_topic_author_id();
	$the_user        = wp_get_current_user();
	$user_id         = $the_user->ID;

	$claimed_user_id = get_post_meta( $topic_id, '_bbps_topic_claimed', true );
	if ( $claimed_user_id > 0 ) {
		$user_info         = get_userdata( $claimed_user_id );
		$claimed_user_name = $user_info->user_login;
	}

	// 2 is the resolved status ID.
	if ( 2 === (int) get_post_meta( $topic_id, '_bbps_topic_status', true ) ) {
		echo '<span class="label label-success">Resolved</span>';
	}

	// we only want to display the urgent topic status to admin and moderators.
	if ( true === (bool) get_post_meta( $topic_id, '_bbps_urgent_topic', true ) && ( current_user_can( 'administrator' ) || current_user_can( 'bbp_moderator' ) ) ) {
		echo '<span class="label label-warning">Urgent</span>';
	}

	// claimed topics also only get shown to admin and moderators and the person who owns the topic.
	if ( get_post_meta( $topic_id, '_bbps_topic_claimed', true ) > 0 && ( current_user_can( 'administrator' ) || current_user_can( 'bbp_moderator' ) || $topic_author_id === $user_id ) ) {

		// if this option == 1 we display the users name not [claimed].
		if ( true === (bool) get_option( '_bbps_claim_topic_display' ) ) {
			echo '<span class="label label-info">[' . esc_html( $claimed_user_name ) . ']</span>';
		} else {
			echo '<span class="label label-info">Claimed</span>';
		}
	}
}

add_action( 'bbp_theme_before_topic_title', 'bbps_modify_title' );

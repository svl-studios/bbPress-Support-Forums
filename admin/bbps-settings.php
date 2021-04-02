<?php

defined( 'ABSPATH' ) || exit;

// The GetShopped Section.

/**
 * Sections callback.
 */
function bbps_admin_setting_callback_getshopped_section() {
	?>
	<p><?php esc_html_e( 'User ranking allows you to differentiate and reward your forum users with Custom Titles based on the number of topics and replies they have contributed to.', 'bbps-forum' ); ?></p>
	<?php
}

/**
 * Status callback.
 */
function bbps_admin_setting_callback_status_section() {
	?>
	<p><?php esc_html_e( 'Enable and configure the settings for topic statuses these will be displayed on each topic', 'bbps-forum' ); ?></p>
	<?php
}

/**
 * SUpport forum section callback.
 */
function bbps_admin_setting_callback_support_forum_section() {
	?>
	<p><?php esc_html_e( 'Enable and configure the settings for support forums, these options will be displayed on each topic within your support forums', 'bbps-forum' ); ?></p>
	<?php

}

/*
* WEBMAN EDITED CODE STARTS HERE
* www.webmandesign.eu
*/

/**
 * Reply count callback.
 *
 * @param array $args Args.
 */
function bbps_admin_setting_callback_reply_count( array $args ) {
	$i       = ( isset( $args[0] ) ) ? ( absint( $args[0] ) ) : ( 1 );
	$options = get_option( '_bbps_reply_count' );

    if ( isset( $options[ $i ]['title'] ) ) {
        $title = trim( $options[ $i ]['title'] );
    }

    if ( isset( $options[ $i ]['start'] ) ) {
        $start = trim( $options[ $i ]['start'] );
    }

    if ( isset( $options[ $i ]['end'] ) ) {
        $end = trim( $options[ $i ]['end'] );
    }

	?>
	Rank Title
	<input name="_bbps_reply_count[<?php echo intval( $i ); ?>][title]" type="text" id="_bbps_reply_count_title_<?php echo intval( $i ); ?>" value="<?php echo $title; ?>"/>
	is granted when a user has at least
	<input name="_bbps_reply_count[<?php echo intval( $i ); ?>][start]" type="text" id="bbps_reply_count_start_<?php echo intval( $i ); ?>" value="<?php echo $start; ?>" class="small-text"/>
	posts but not more than
	<input name="_bbps_reply_count[<?php echo intval( $i ); ?>][end]" type="text" id="bbps_reply_count_end_<?php echo intval( $i ); ?>" value="<?php echo $end; ?>" class="small-text"/>
	posts
	<?php
}

/*
 * WEBMAN EDITED CODE ENDS HERE
 * www.webmandesign.eu
 */

/**
 * Post count callback.
 */
function bbps_admin_setting_callback_post_count() {
	?>
	<input id="_bbps_enable_post_count" name="_bbps_enable_post_count" type="checkbox" <?php checked( bbps_is_post_count_enabled(), 1 ); ?> value="1"/>
	<label for="_bbps_enable_post_count"><?php esc_html_e( 'Show the users post count below their gravatar?', 'bbpress' ); ?></label>
	<?php
}

/**
 * User rank callback.
 */
function bbps_admin_setting_callback_user_rank() {
	?>
	<input id="bbps_enable_user_rank" name="_bbps_enable_user_rank" type="checkbox" <?php checked( bbps_is_user_rank_enabled(), 1 ); ?> value="1"/>
	<label for="bbps_enable_user_rank"><?php esc_html_e( 'Display the users rank title below their gravatar?', 'bbpress' ); ?></label>
	<?php
}

/**
 * Default status callback.
 */
function bbps_admin_setting_callback_default_status() {
	$option = get_option( '_bbps_default_status' );
	?>
	<select name="_bbps_default_status" id="bbps_default_status">
		<option value="1" <?php selected( $option, 1 ); ?> >not resolved</option>
		<option value="2" <?php selected( $option, 2 ); ?> >resolved</option>
		<option value="3" <?php selected( $option, 3 ); ?> >not a support question</option>
	</select>
	<label for="bbps_default_status"><?php esc_html_e( 'This is the default status that will get displayed on all topics', 'bbpress' ); ?></label>
	<?php
}

/**
 * Resolved status callback.
 */
function bbps_admin_setting_callback_displayed_status_res() {
	?>
	<input id="bbps_used_status" name="_bbps_used_status[res]" <?php checked( bbps_is_resolved_enabled(), 1 ); ?> type="checkbox" value="1"/>
	<label for="bbps_used_status"><?php esc_html_e( 'Resolved', 'bbpress' ); ?></label>
	<?php
}

/**
 * Unresolved status callback.
 */
function bbps_admin_setting_callback_displayed_status_notres() {
	?>
	<input id="bbps_used_status" name="_bbps_used_status[notres]" <?php checked( bbps_is_not_resolved_enabled(), 1 ); ?> type="checkbox" value="1"/>
	<label for="bbps_used_status"><?php esc_html_e( 'Not Resolved', 'bbpress' ); ?></label>
	<?php
}

/**
 * No support status callback.
 */
function bbps_admin_setting_callback_displayed_status_notsup() {
	?>
	<input id="bbps_used_status" name="_bbps_used_status[notsup]" <?php checked( bbps_is_not_support_enabled(), 1 ); ?> type="checkbox" value="1"/>
	<label for="bbps_used_status"><?php esc_html_e( 'Not a support question', 'bbpress' ); ?></label>
	<?php
}

/**
 * Admin permission callback.
 */
function bbps_admin_setting_callback_permission_admin() {
	?>
	<input id="bbps_status_permissions" name="_bbps_status_permissions[admin]" <?php checked( bbps_is_admin_enabled(), 1 ); ?> type="checkbox" value="1"/>
	<label for="bbps_status_permissions"><?php esc_html_e( 'Allow the admin to update the topic status (recommended).', 'bbpress' ); ?></label>
	<?php
}

/**
 * User permissions callback.
 */
function bbps_admin_setting_callback_permission_user() {
	?>
	<input id="bbps_status_permissions" name="_bbps_status_permissions[user]" <?php checked( bbps_is_user_enabled(), 1 ); ?> type="checkbox" value="1"/>
	<label for="bbps_status_permissions"><?php esc_html_e( 'Allow the person who created the topic to update the status.', 'bbpress' ); ?></label>
	<?php
}

/**
 * Moderator permissions callback.
 */
function bbps_admin_setting_callback_permission_moderator() {
	?>
	<input id="bbps_status_permissions" name="_bbps_status_permissions[mod]" <?php checked( bbps_is_moderator_enabled(), 1 ); ?> type="checkbox" value="1"/>
	<label for="bbps_status_permissions"><?php esc_html_e( 'Allow the forum moderators to update the post status.', 'bbpress' ); ?></label>
	<?php
}

/**
 * Move topic callback.
 */
function bbps_admin_setting_callback_move_topic() {
	?>
	<input id="bbps_enable_topic_move" name="_bbps_enable_topic_move" <?php checked( bbps_is_topic_move_enabled(), 1 ); ?> type="checkbox" value="1"/>
	<label for="bbps_enable_topic_move"><?php esc_html_e( 'Allow the forum moderators and admin to move topics to other forums.', 'bbpress' ); ?></label>
	<?php
}

/**
 * Urgent post callback.
 */
function bbps_admin_setting_callback_urgent() {
	?>
	<input id="bbps_status_permissions_urgent" name="_bbps_status_permissions_urgent" <?php checked( bbps_is_topic_urgent_enabled(), 1 ); ?> type="checkbox" value="1"/>
	<label for="bbps_status_permissions_urgent"><?php esc_html_e( 'Allow the forum moderators and admin to mark a topic as Urgent, this will mark the topic title with [urgent].', 'bbpress' ); ?></label>
	<?php
}

/**
 * Claim topic callback.
 */
function bbps_admin_setting_callback_claim_topic() {
	?>
	<input id="bbps_claim_topic" name="_bbps_claim_topic" <?php checked( bbps_is_topic_claim_enabled(), 1 ); ?> type="checkbox" value="1"/>
	<label for="bbps_claim_topic"><?php esc_html_e( 'Allow the forum moderators and admin to claim a topic, this will mark the topic title with [claimed] but will only show to forum moderators and admin users', 'bbpress' ); ?></label>
	<?php
}

/**
 * Topic display callback.
 */
function bbps_admin_setting_callback_claim_topic_display() {
	?>
	<input id="bbps_claim_topic_display" name="_bbps_claim_topic_display" <?php checked( bbps_is_topic_claim_display_enabled(), 1 ); ?> type="checkbox" value="1"/>
	<label for="bbps_claim_topic_display"><?php esc_html_e( 'By selecting this option if a topic is claimed the claimed persons username will be displayed next to the topic title instead of the words [claimed], leaving this unchecked will default to [claimed]', 'bbpress' ); ?></label>
	<?php

}

/**
 * Assign topic callback.
 */
function bbps_admin_setting_callback_assign_topic() {
	?>
	<input id="bbps_topic_assign" name="_bbps_topic_assign" <?php checked( bbps_is_topic_assign_enabled(), 1 ); ?> type="checkbox" value="1"/>
	<label for="bbps_topic_assign"><?php esc_html_e( 'Allow administrators and forum moderators to assign topics to other administrators and forum moderators', 'bbpress' ); ?></label>
	<?php
}

/**
 * Subject notification callback.
 */
function bbps_admin_setting_callback_notifiation_subject() {
	$subject = get_option( '_bbps_notification_subject' );

	if ( empty( $subject ) ) {
		$subject = __( 'Your registration at %BLOGNAME%' );
	}

	?>
	<input type="text" name="_bbps_notification_subject" value='<?php echo esc_attr( $subject ); ?>' class='wide'/>
	<br/>
	<i><?php esc_html_e( '<code>%USERNAME%</code> will be replaced with a username.' ); ?></i><br/>
	<i><?php esc_html_e( "<code>%PASSWORD%</code> will be replaced with the user's password." ); ?></i><br/>
	<i><?php esc_html_e( '<code>%BLOGNAME%</code> will be replaced with the name of your blog.' ); ?></i>
	<i><?php esc_html_e( '<code>%BLOGURL%</code> will be replaced with the url of your blog.' ); ?></i>
	<?php
}

/**
 * Message notification callback.
 */
function bbps_admin_setting_callback_notifiation_message() {

	$message = get_option( '_bbps_notification_message' );
	if ( empty( $message ) ) {

		// phpcs:ignore
		$message = esc_html__( 'Thanks for signing up to our blog.

You can login with the following credentials by visiting %BLOGURL%

Username : %USERNAME%
Password : %PASSWORD%

We look forward to your next visit!

The team at %BLOGNAME%'
		);
	}
	?>
	<textarea name="_bbps_notification_message" class='wide' style="width:100%; height:250px;"><?php echo esc_textarea( $message ); ?></textarea>
	<br/>
	<i><?php esc_html_e( '<code>%BLOGNAME%</code> will be replaced with the name of your blog.' ); ?></i>
	<i><?php esc_html_e( '<code>%BLOGURL%</code> will be replaced with the url of your blog.' ); ?></i>
	<?php
}

/**
 * Staff hours callback.
 */
function bbps_admin_setting_callback_staff_hours() {

	// get a list of staff members and work out how many hours they have logged on tickets.
	$all_users      = get_users();
	$specific_users = array();
	foreach ( $all_users as $user ) {
		if ( $user->has_cap( 'administrator' ) || $user->has_cap( 'bbp_moderator' ) ) {
			$specific_users[] = $user;
		}
	}

	?>
	<ul>
		<?php foreach ( $specific_users as $specific_user ) { ?>
			<li>
				<a href="/forums/users/<?php echo esc_html( $specific_user->data->user_login ); ?>" target="_blank"><?php echo esc_html( $specific_user->data->user_login ); ?></a>
			</li>
		<?php } ?>
	</ul>
	<?php
}


/**
 * Envato API ID.
 */
function bbps_admin_setting_callback_envato_api_id() {
	?>
	<input id="bbps_envato_username" name="_bbps_envato_username" type="text" value="<?php echo esc_attr( get_option( '_bbps_envato_username' ) ); ?>"/>
	<label for="bbps_envato_username"><?php esc_html_e( 'Envato API username for purchase verification', 'bbpress' ); ?></label>
	<br/>
	<input id="bbps_envato_api_key" name="_bbps_envato_api_key" type="text" value="<?php echo esc_attr( get_option( '_bbps_envato_api_key' ) ); ?>"/>
	<label for="bbps_envato_api_key"><?php esc_html_e( 'Envato API key for purchase verification', 'bbpress' ); ?></label>
	<?php
}

/**
 * Recaptcha callback.
 */
function bbps_admin_setting_callback_recaptcha() {
	?>
	<input id="bbps_recaptcha_client" name="_bbps_recaptcha_client" type="text" value="<?php echo esc_attr( get_option( '_bbps_recaptcha_client', '' ) ); ?>"/>
	<label for="bbps_recaptcha_client"><?php esc_html_e( 'Registration Recaptcha Public Key', 'bbpress' ); ?></label>
	<br/>
	<input id="bbps_recaptcha_secret" name="_bbps_recaptcha_secret" type="text" value="<?php echo esc_attr( get_option( '_bbps_recaptcha_secret', '' ) ); ?>"/>
	<label for="bbps_recaptcha_secret"><?php esc_html_e( 'Registration Recaptcha Secret Key', 'bbpress' ); ?></label>
	<?php
}

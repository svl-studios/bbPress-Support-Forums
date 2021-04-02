<?php

defined( 'ABSPATH' ) || exit;

/**
 * Update the user post count meta everytime the user creates a new post
 */
function bbps_increament_post_count() {
	$post_type = get_post_type();

	// bail unless we are creating topics or replies.
	if ( 'topic' === $post_type || 'reply' === $post_type ) {

		$the_current_user = wp_get_current_user();
		$user_id          = $the_current_user->ID;
		$user_rank        = get_user_meta( $user_id, '_bbps_rank_info' );

		// if this is their first post.
		if ( empty( $user_rank[0] ) ) {
			bbps_create_user_ranking_meta( $user_id );
		}

		bbps_check_ranking( $user_id );
	}
}

add_action( 'save_post', 'bbps_increament_post_count' );

/**
 * Check ranking.
 *
 * @param mixed $user_id User ID.
 */
function bbps_check_ranking( $user_id ) {
	$user_rank = get_user_meta( $user_id, '_bbps_rank_info' );

	$post_count   = $user_rank[0]['post_count'];
	$current_rank = $user_rank[0]['current_ranking'];
	$rankings     = get_option( '_bbps_reply_count', 0 );

	++$post_count;

	foreach ( (array) $rankings as $rank ) {

		// if post count == the end value then this title no longer applies so remove it
		// we subtract one here to allow for the between number eg between 1 - 4 we still
		// want to dispaly the title if the post count is 4.
		if ( $post_count - 1 === (int) $rank['end'] ) {
			$current_rank = '';
		}

		if ( $post_count === (int) $rank['start'] ) {
			$current_rank = $rank['title'];
		}
	}

	$meta = array(
		'post_count'      => $post_count,
		'current_ranking' => $current_rank,
	);

	update_user_meta( $user_id, '_bbps_rank_info', $meta );
}

/**
 * Called by bbps_increament_post_count function, this will create the usermeta if this is their first post.
 *
 * @param mixed $user_id User ID.
 */
function bbps_create_user_ranking_meta( $user_id ) {
	$rankings = get_option( '_bbps_reply_count' );

	$meta = array(
		'post_count'      => '0',
		'current_ranking' => '',
	);

	update_user_meta( $user_id, '_bbps_rank_info', $meta );
}

/**
 * Called by the bbp_theme_after_reply_author_details hook in bbpress 2.0.
 */
function bbps_display_user_title() {
	if ( true === (bool) get_option( '_bbps_enable_user_rank' ) ) {
		$user_id   = bbp_get_reply_author_id();
		$user_rank = get_user_meta( $user_id, '_bbps_rank_info' );

		if ( ! empty( $user_rank[0]['current_ranking'] ) ) {
			echo '<div id ="bbps-user-title">' . esc_html( $user_rank[0]['current_ranking'] ) . '</div>';
		}
	}

}

/**
 * Called by the bbp_theme_after_reply_author_details hook in bbpress 2.0.
 */
function bbps_display_user_post_count() {
	if ( true === (bool) get_option( '_bbps_enable_post_count' ) ) {
		$user_id   = bbp_get_reply_author_id();
		$user_rank = get_user_meta( $user_id, '_bbps_rank_info' );

		if ( ! empty( $user_rank[0]['post_count'] ) ) {
			echo '<div id ="bbps-post-count"> Post count: ' . intval( $user_rank[0]['post_count'] ) . '</div>';
		}
	}
}

/**
 * Called by the bbp_theme_after_reply_author_details hook in bbpress 2.0,
 * will display a trusted tag below the site administrators and bp-moderators gravitar.
 */
function bbps_display_trusted_tag() {
	$user_id = bbp_get_reply_author_id();
	$user    = get_userdata( $user_id );

	if ( true === (bool) get_option( '_bbps_enable_trusted_tag' ) && ( ( true === ! empty( $user->wp_capabilities['administrator'] ) ) || ( true === ! empty( $user->wp_capabilities['bbp_moderator'] ) ) ) ) {
		echo '<div id ="trusted"><em>Trusted</em></div>';
	}
}

add_action( 'bbp_theme_after_reply_author_details', 'bbps_display_user_title' );
add_action( 'bbp_theme_after_reply_author_details', 'bbps_display_user_post_count' );
add_action( 'bbp_theme_after_reply_author_details', 'bbps_display_trusted_tag' );

<?php
/*
This file contains handy little functions
that get used more than once throughout this
plugin.
*/

defined( 'ABSPATH' ) || exit;

/**
 * Checks if the current forum is a premium one
 *
 * @param mixed $forum_id Forum ID.
 *
 * @return bool
 */
function bbps_is_premium_forum( $forum_id ): bool {
	$premium_forum = (bool) get_post_meta( $forum_id, '_bbps_is_premium', true );

	if ( true === $premium_forum ) {
		return true;
	} else {
		return false;
	}
}

/**
 * Is support forum.
 *
 * @param mixed $forum_id Forum ID.
 *
 * @return bool
 */
function bbps_is_support_forum( $forum_id ): bool {
	$support_forum = (bool) get_post_meta( $forum_id, '_bbps_is_support', true );

	if ( true === $support_forum ) {
		return true;
	} else {
		return false;
	}
}

/**
 * Is voting forum.
 *
 * @param mixed $forum_id Forum ID.
 *
 * @return bool
 */
function bbps_is_voting_forum( $forum_id ): bool {
	$voting_forum = (bool) get_post_meta( $forum_id, '_bbps_is_voting', true );

	if ( true === $voting_forum ) {
		return true;
	} else {
		return false;
	}
}

/**
 * Must be used without the topic loop checks if the topic is part of the prem forum.
 *
 * @param mixed $id Forum ID.
 *
 * @return bool
 */
function bbps_is_topic_premium2( $id ): bool {
	$is_premium = get_post_meta( $id, '_bbps_is_premium' );

	if ( true === (bool) $is_premium[0] ) {
		return true;
	} else {
		return false;
	}
}

/**
 * Is topic premium.
 *
 * @return bool
 */
function bbps_is_topic_premium(): bool {
	$is_premium = get_post_meta( bbp_get_topic_forum_id(), '_bbps_is_premium' );

	if ( true === (bool) $is_premium[0] ) {
		return true;
	} else {
		return false;
	}
}

/**
 * Is reply premium.
 *
 * @return bool
 */
function bbps_is_reply_premium(): bool {
	$is_premium = get_post_meta( bbp_get_reply_forum_id(), '_bbps_is_premium' );

	if ( true === (bool) $is_premium[0] ) {
		return true;
	} else {
		return false;
	}
}

/**
 * Get all premium topic IDs.
 *
 * @return array
 */
function bbps_get_all_premium_topic_ids(): array {
	global $wpdb;

	// phpcs:disable
	$forum_query    = 'SELECT `post_id` FROM ' . $wpdb->postmeta . " WHERE `meta_key` = '_bbps_is_premium'";
	$premium_forums = $wpdb->get_col( $forum_query );

	$exclude        = implode( ',', $premium_forums );
	$topics_query   = 'SELECT `id` FROM ' . $wpdb->posts . ' WHERE `post_parent` IN (' . $exclude . ')';

	return $wpdb->get_col( $topics_query );
	// phpcs:enable
}

/**
 * Display a support forum drop down list of only forums that have been marked as premium.
 */
function bbps_support_forum_ddl() {
	global $wpdb;

	$sql               = 'SELECT `post_id` FROM ' . $wpdb->postmeta . " WHERE `meta_key` = '_bbps_is_premium' AND `meta_value` = '1'";
	$premium_forum_ids = $wpdb->get_col( $sql ); // phpcs:ignore

	$select = '<select id="bbp_forum_id" name="bbp_forum_id">';
	foreach ( $premium_forum_ids as $id ) {
		$select .= '<option value="' . esc_attr( $id ) . '">' . esc_html( get_the_title( $id ) ) . '</option>';
	}

	$select .= '</select>';

	echo $select; // phpcs:ignore WordPress.Security.EscapeOutput
}

/**
 * Get resolved status.
 *
 * @param mixed $topic_id Topic ID.
 *
 * @return bool
 */
function bbps_topic_resolved( $topic_id ): bool {
	if ( 2 === (int) get_post_meta( $topic_id, '_bbps_topic_status', true ) ) {
		return true;
	} else {
		return false;
	}
}

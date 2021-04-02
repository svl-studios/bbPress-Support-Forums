<?php

defined( 'ABSPATH' ) || exit;

/**
 * Accepted vote count.
 */
const _BBPS_FEATURE_ACCEPTED_VOTE_COUNT = 10000;

/**
 * Funding vote count.
 */
const _BBPS_FEATURE_FUNDING_VOTE_COUNT = 20000;

/**
 * Funcdoing paid vote count.
 */
const _BBPS_FEATURE_FUNDING_PAID_VOTE_COUNT = 30000;

add_action( 'bbp_template_before_topics_loop', 'dtbaker_vote_bbp_template_before_topics_loop' );

/**
 * Before topics loop.
 */
function dtbaker_vote_bbp_template_before_topics_loop() {

	// a tab to display resolved or unresilved voted items within this forum.
	$forum_id = bbp_get_forum_id();
	if ( bbps_is_voting_forum( $forum_id ) ) {
		?>
		<a href="<?php echo esc_url( add_query_arg( array( 'show_resolved' => 0 ), bbp_get_forum_permalink( $forum_id ) ) ); ?>">Pending Feature Requests</a> |
		<a href="<?php echo esc_url( add_query_arg( array( 'show_resolved' => 1 ), bbp_get_forum_permalink( $forum_id ) ) ); ?>">Resolved Requests</a>
		<?php
	}
}

add_filter( 'bbp_topic_pagination', 'dtbaker_vote_bbp_topic_pagination', 10, 1 );

/**
 * Topic pagination.
 *
 * @param array $options Options.
 *
 * @return array
 */
function dtbaker_vote_bbp_topic_pagination( array $options ): array {
	if ( bbps_is_voting_forum( bbp_get_forum_id() ) ) {
		if ( isset( $_REQUEST['show_resolved'] ) && $_REQUEST['show_resolved'] ) {
			$options['add_args'] = array( 'show_resolved' => 1 );
		}
	}

	return $options;
}

/**
 * Voting is admin.
 *
 * @return bool|string
 */
function bbps_voting_is_admin() {
	$the_current_user = wp_get_current_user();
	$user_id          = $the_current_user->ID;
	$topic_author_id  = bbp_get_topic_author_id();
	$permissions      = get_option( '_bbps_status_permissions' );
	$can_edit         = '';

	// check the users permission this is easy.
	if ( true === (bool) $permissions['admin'] && current_user_can( 'administrator' ) || true === (bool) $permissions['mod'] && current_user_can( 'bbp_moderator' ) ) {
		$can_edit = true;
	}

	return $can_edit;
}


add_action( 'bbp_template_before_single_topic', 'bbps_add_voting_forum_features' );

/**
 * Add voting features.
 */
function bbps_add_voting_forum_features() {

	// only display all this stuff if the support forum option has been selected.
	if ( bbps_is_voting_forum( bbp_get_forum_id() ) ) {
		$topic_id = bbp_get_topic_id();
		$forum_id = bbp_get_forum_id();
		$user_id  = get_current_user_id();

		if ( ( isset( $_GET['action'] ) && isset( $_GET['topic_id'] ) && 'bbps_vote_for_topic' === $_GET['action'] ) ) {
			bbps_vote_topic();
		}

		if ( ( isset( $_GET['action'] ) && isset( $_GET['topic_id'] ) && 'bbps_unvote_for_topic' === $_GET['action'] ) ) {
			bbps_unvote_topic();
		}

		$votes = bbps_get_topic_votes( $topic_id );

		?>
		<div class="row">
			<div id="bbps_voting_forum_options" class="col-md-6">
				<div class="well">
					Votes:
					<?php
					echo count( $votes );
					if ( is_user_logged_in() ) {
						if ( in_array( $user_id, $votes, true ) ) {
							$vote_uri = add_query_arg(
								array(
									'action'   => 'bbps_unvote_for_topic',
									'topic_id' => $topic_id,
								)
							);

							?>
							Vote Successful. Thanks! (<a href="<?php echo esc_url( $vote_uri ); ?>">undo vote</a>)
							<?php
						} else {
							$vote_uri = add_query_arg(
								array(
									'action'   => 'bbps_vote_for_topic',
									'topic_id' => $topic_id,
								)
							);
							?>
							<a href="<?php echo esc_url( $vote_uri ); ?>" class="btn btn-primary">Vote for this!</a>
							<?php
						}
					} else {
						echo '(please login to vote)';
					}

					?>
				</div>
			</div>
			<?php
			if ( bbps_voting_is_admin() ) {
				if ( isset( $_POST['bbps_topic_feature_accepted'] ) ) {
					update_post_meta( $topic_id, '_bbps_topic_feature_accepted', $_POST['bbps_topic_feature_accepted'] );
					bbps_update_vote_count( $topic_id );
				}

				if ( isset( $_POST['bbps_topic_feature_funding_paid'] ) ) {
					update_post_meta( $topic_id, '_bbps_topic_feature_funding_paid', $_POST['bbps_topic_feature_funding_paid'] );
					bbps_update_vote_count( $topic_id );
				}

				if ( isset( $_POST['bbps_topic_feature_funding'] ) ) {
					update_post_meta( $topic_id, '_bbps_topic_feature_funding', $_POST['bbps_topic_feature_funding'] );
					bbps_update_vote_count( $topic_id );
				}

				$feature_accepted = get_post_meta( $topic_id, '_bbps_topic_feature_accepted', true );

				?>
				<div id="bbps_voting_forum_options" class="col-md-6">
					<div class="well">
						<form id="bbps-topic-vote-feature" name="bbps_support_feature" action="" method="post">
							<input type="hidden" value="bbps_feature_accepted" name="bbps_action"/>
							<div>
								<label for="bbps_topic_feature_accepted">Feature Accepted? </label>
								<select name="bbps_topic_feature_accepted" id="bbps_topic_feature_accepted">
									<option value="0">no</option>
									<option value="1" <?php echo $feature_accepted ? ' selected' : ''; ?>>yes accepted</option>
								</select>
								<input type="submit" value="Update" name="bbps_support_feature_accepted_btn"/>
							</div>
							<div>
								<label for="bbps_support_feature_accepted">Funding Level: </label>
								<input type="text" name="bbps_topic_feature_funding_paid" value="<?php echo esc_attr( get_post_meta( $topic_id, '_bbps_topic_feature_funding_paid', true ) ); ?>" size="5">
								paid of
								<input type="text" name="bbps_topic_feature_funding" value="<?php echo esc_attr( get_post_meta( $topic_id, '_bbps_topic_feature_funding', true ) ); ?>" size="5">
								total
								<input type="submit" value="Update" name="bbps_support_feature_funding_btn"/>
							</div>
						</form>
					</div>
				</div>
				<?php
			} else {
				$feature_accepted   = get_post_meta( $topic_id, '_bbps_topic_feature_accepted', true );
				$funding_level      = get_post_meta( $topic_id, '_bbps_topic_feature_funding', true );
				$funding_level_paid = get_post_meta( $topic_id, '_bbps_topic_feature_funding_paid', true );

				?>
				<div id="bbps_voting_forum_options" class="col-md-6">
					<div class="well">
						<div>
							<?php if ( $feature_accepted ) { ?>
								This feature request has been <strong>accepted</strong>! It will be worked on asap. Updates will appear below.
							<?php } else { ?>
								This feature request has not yet been accepted (we are rather busy). Keep voting (or funding) and be sure to subscribe for updates!
							<?php } ?>
						</div>
						<hr>
						<div>
							<?php if ( $funding_level < 0 ) { ?>
								Sorry this feature has been marked as "not possible" so funding has been disabled.
							<?php } else { ?>
								<?php if ( $funding_level > 0 ) { ?>
									A funding goal of $<?php echo number_format( $funding_level, 2 ); ?> has been set for this feature request. <br/>
									If you would like to contribute please click below: <br/>
								<?php } else { ?>
									No funding level has been set for this feature request yet. <br/>
									You can still make a contribution below: <br/>
								<?php } ?>

								<form class="form-inline" role="form" method="post">
									<input type="hidden" name="make_contribution_title" value="<?php echo esc_attr( bbp_get_topic_title( $topic_id ) ); ?>">
									<input type="hidden" name="make_contribution" value="<?php echo esc_attr( bbp_get_topic_permalink( $topic_id ) ); ?>">
									<div class="form-group col-xs-5">
										<div class="input-group">
											<div class="input-group-addon">$</div>
											<input class="form-control" type="number" name="amount" placeholder="Enter amount" value="10">
										</div>
									</div>
									<button type="submit" class="btn btn-primary">Make Contribution</button>
								</form>
								<?php if ( $funding_level_paid > 0 ) { ?>
									This feature request has raised $<?php echo number_format( $funding_level_paid, 2 ); ?> so far! Thank you!<br/>
								<?php } ?>
							<?php } ?>

						</div>
					</div>
				</div>
				<?php
			}
			?>
		</div> <!-- row -->
		<?php
	}
}

add_action( 'bbp_after_setup_actions', 'dtbaker_bbps_check_vote_contribution' );

/**
 * Check vote contribution.
 */
function dtbaker_bbps_check_vote_contribution() {
	if ( ! empty( $_POST['make_contribution'] ) ) {
		$url = 'https://www.paypal.com/cgi-bin/webscr?cmd=_xclick&amount=' . trim( rawurlencode( $_POST['amount'] ) ) . '&business=dtbaker@gmail.com&item_name=Contribution+For+Feature&on1=Feature&os1=' . rawurlencode( $_POST['make_contribution_title'] ) . '&on2=URL&os2=' . rawurlencode( $_POST['make_contribution'] ) . '&currency_code=USD&cpp_header_image=' . rawurlencode( 'https://dtbaker.net/files/ucm-paypal2.jpg' );
		header( "Location: $url" );
		exit;
	}
}


/**
 * Get topic votes.
 *
 * @param mixed $topic_id Topic ID.
 *
 * @return array|false|string[]
 */
function bbps_get_topic_votes( $topic_id ) {
	$votes = trim( get_post_meta( $topic_id, '_bbps_topic_user_votes', true ) );
	if ( strlen( $votes ) ) {
		$votes = explode( ',', $votes );
	} else {
		$votes = array();
	}

	// To do not hard code these if we let the users add their own status.
	return $votes;
}

/**
 * Adds a class and status to the front of the topic title.
 *
 * @param string $title    Title.
 * @param mixed  $topic_id Topic ID.
 */
function bbps_modify_vote_title( string $title, $topic_id = 0 ) {
	$topic_id = bbp_get_topic_id( $topic_id );
	$forum_id = bbp_get_forum_id();

	if ( bbps_is_voting_forum( $forum_id ) ) {
		$votes = bbps_get_topic_votes( $topic_id );

		if ( isset( $GLOBALS['bbps_feature_request_params']['type'] ) && 'popular' === $GLOBALS['bbps_feature_request_params']['type'] ) {

			// hack to get ids of displayed popular posts.
			if ( ! isset( $GLOBALS['bbps_popular_ids'] ) ) {
				$GLOBALS['bbps_popular_ids'] = array();
			}

			$GLOBALS['bbps_popular_ids'][] = $topic_id;
		}

		if ( count( $votes ) ) {
			echo ' <span class="badge badge-info">Votes: ' . count( $votes ) . '</span> ';
		}

		if ( get_post_meta( $topic_id, '_bbps_topic_feature_accepted', true ) ) {

			// accepted feature, move it to the top.
			echo ' <span class="label label-info">Accepted Feature!</span> ';
		}

		if ( get_post_meta( $topic_id, '_bbps_topic_feature_funding', true ) ) {

			// funding feature, move it to the very top.
			$paid  = '$' . number_format( get_post_meta( $topic_id, '_bbps_topic_feature_funding_paid', true ), 2 );
			$limit = '$' . number_format( get_post_meta( $topic_id, '_bbps_topic_feature_funding', true ), 2 );
			echo ' <span class="label label-success">Funded ' . esc_html( $paid ) . ' of ' . esc_html( $limit ) . '</span> ';
		} elseif ( get_post_meta( $topic_id, '_bbps_topic_feature_funding_paid', true ) ) {

			// funding feature, move it to the very top.
			$paid = '$' . number_format( get_post_meta( $topic_id, '_bbps_topic_feature_funding_paid', true ), 2 );
			echo ' <span class="label label-success">Funded ' . esc_html( $paid ) . '</span> ';
		}
	}
}

add_action( 'bbp_theme_before_topic_title', 'bbps_modify_vote_title' );

/**
 * Vote topic.
 */
function bbps_vote_topic() {
	if ( is_user_logged_in() ) {
		$user_id = get_current_user_id();

		if ( $user_id ) {
			$topic_id = bbp_get_topic_id();
			$forum_id = bbp_get_forum_id();

			if ( bbps_is_voting_forum( $forum_id ) ) {
				$votes = bbps_get_topic_votes( $topic_id );
				if ( ! in_array( $user_id, $votes, true ) ) {
					$votes[] = $user_id;
					update_post_meta( $topic_id, '_bbps_topic_user_votes', implode( ',', $votes ) );
					bbps_update_vote_count( $topic_id );
				}
			}
		}
	}
}

/**
 * Update vote count.
 *
 * @param mixed $topic_id Topic ID.
 */
function bbps_update_vote_count( $topic_id ) {
	$votes      = bbps_get_topic_votes( $topic_id );
	$vote_count = count( $votes );

	if ( get_post_meta( $topic_id, '_bbps_topic_feature_accepted', true ) ) {

		// accepted feature, move it to the top.
		$vote_count = count( $votes ) + _BBPS_FEATURE_ACCEPTED_VOTE_COUNT;
	}

	if ( get_post_meta( $topic_id, '_bbps_topic_feature_funding', true ) ) {

		// funding feature, move it to the very top.
		$vote_count = count( $votes ) + _BBPS_FEATURE_FUNDING_VOTE_COUNT;
	}

	if ( get_post_meta( $topic_id, '_bbps_topic_feature_funding_paid', true ) ) {

		// funding feature, move it to the very top.
		$vote_count = count( $votes ) + _BBPS_FEATURE_FUNDING_PAID_VOTE_COUNT + get_post_meta( $topic_id, '_bbps_topic_feature_funding_paid', true );
	}

	update_post_meta( $topic_id, '_bbps_topic_user_votes_count', $vote_count );
}

/**
 * Unvote topic.
 */
function bbps_unvote_topic() {
	if ( is_user_logged_in() ) {
		$user_id = get_current_user_id();

		if ( $user_id ) {
			$topic_id = bbp_get_topic_id();
			$forum_id = bbp_get_forum_id();

			if ( bbps_is_voting_forum( $forum_id ) ) {
				$votes = bbps_get_topic_votes( $topic_id );
				$key   = array_search( $user_id, $votes, true );

				if ( false !== $key ) {
					unset( $votes[ $key ] );
					update_post_meta( $topic_id, '_bbps_topic_user_votes', implode( ',', $votes ) );
					update_post_meta( $topic_id, '_bbps_topic_user_votes_count', count( $votes ) );
				}
			}
		}
	}
}

/**
 * Vote custom order.
 *
 * @param array $clauses Clauses.
 *
 * @return mixed
 */
function dtbaker_filter_topics_vote_custom_order( array $clauses ) {
	global $wp_query;

	if ( preg_match( '#([a-zA-Z_0-9]*postmeta)\.meta_key = \'_bbps_topic_user_votes_count\'#', $clauses['where'], $matches ) ) {

		// change the inner join to a left outer join,
		// and change the where so it is applied to the join, not the results of the query
		// ON (all_5_posts.ID = all_5_postmeta.post_id).
		$clauses['where'] = preg_replace( '#\n#', ' ', $clauses['where'] );
		$join_matches     = preg_split( "#\n#", $clauses['join'] );
		$clauses['join']  = '';

		foreach ( $join_matches as $join_match_id => $join_match ) {
			if ( strpos( $join_match, $matches[1] . '.post_id' ) !== false ) {
				$join_matches[ $join_match_id ]  = str_replace( 'INNER JOIN', 'LEFT OUTER JOIN', $join_matches[ $join_match_id ] );
				$clauses['where']                = str_replace( $matches[0], '1', $clauses['where'] );
				$join_matches[ $join_match_id ] .= ' AND ' . $matches[0] . ' ';
			}

			$clauses['join'] .= $join_matches[ $join_match_id ] . ' ';
		}

		$clauses['where'] = str_replace( '1 OR ', '', $clauses['where'] );

	}

	return $clauses;
}

add_filter( 'get_meta_sql', 'dtbaker_filter_topics_vote_custom_order', 10, 1 );

/**
 * Topics parse args.
 *
 * @param array $args Args.
 *
 * @return mixed
 */
function bbps_filter_bbp_after_has_topics_parse_args( array $args ) {
	$forum_id = bbp_get_forum_id();

	if ( $forum_id && bbps_is_voting_forum( $forum_id ) ) {
		$args['meta_query'] = array();

		if ( isset( $_REQUEST['show_resolved'] ) && $_REQUEST['show_resolved'] ) {
			$args['meta_query'][] = array(
				'key'     => '_bbps_topic_status',
				'value'   => 2,
				'compare' => '=',
			);
		} else {
			$args['orderby']    = 'meta_value_num';
			$args['meta_key']   = '_bbps_topic_user_votes_count';
			$args['order']      = 'DESC';
			$args['meta_query'] = array(
				'relation' => 'OR',
				array(
					'key'     => '_bbps_topic_status',
					'compare' => 'NOT EXISTS',
					'value'   => '2',
				),
				array(
					'key'     => '_bbps_topic_status',
					'value'   => 2,
					'compare' => '!=',
				),
			);
		}
	}

	return $args;
}

add_filter( 'bbp_after_has_topics_parse_args', 'bbps_filter_bbp_after_has_topics_parse_args', 10, 1 );


/* shortcode added by dtbaker */
add_shortcode( 'bbps-feature-requests', 'dtbaker_bbps_feature_requests' );

/**
 * Feature requests.
 *
 * @param mixed $params Params.
 *
 * @return string
 */
function dtbaker_bbps_feature_requests( $params ): string {
	$result = '';

	remove_action( 'bbp_template_before_topics_loop', 'dtbaker_vote_bbp_template_before_topics_loop' );
	remove_filter( 'bbp_after_has_topics_parse_args', 'bbps_filter_bbp_after_has_topics_parse_args', 10, 1 );
	remove_filter( 'get_meta_sql', 'dtbaker_filter_topics_vote_custom_order', 10, 1 );

	// filter the bbPress query args that are run when [bbp-topic-index] is executed.
	$GLOBALS['bbps_feature_request_params'] = $params;
	add_filter( 'bbp_after_has_topics_parse_args', 'dtbaker_bbps_feature_requests_parse_args', 3, 1 );

	// adjust the generated 'where' SQL to perform a table comparison.
	add_filter( 'get_meta_sql', 'dtbaker_filter_topics_vote_custom_order', 3, 6 );
	add_filter( 'bbp_topic_pagination', 'dtbaker_bbps_feature_requests_bbp_topic_pagination', 3, 1 );
	add_filter( 'bbp_get_forum_pagination_count', 'dtbaker_bbps_feature_requests_bbp_get_topic_pagination_count', 3, 1 );
	add_filter( 'bbp_is_single_forum', 'dtbaker_bbps_feature_requests_bbp_is_single_forum', 3, 1 );

	// run the built in bbpress shortcode which does everything nicely.
	$result .= do_shortcode( '[bbp-topic-index]' );

	// undo our nasty hacks from above.
	remove_filter( 'bbp_after_has_topics_parse_args', 'dtbaker_bbps_feature_requests_parse_args', 3, 1 );
	remove_filter( 'get_meta_sql', 'dtbaker_filter_topics_vote_custom_order', 3, 6 );
	remove_filter( 'bbp_topic_pagination', 'dtbaker_bbps_feature_requests_bbp_topic_pagination', 3, 1 );
	remove_filter( 'bbp_get_forum_pagination_count', 'dtbaker_bbps_feature_requests_bbp_get_topic_pagination_count', 3, 1 );
	remove_filter( 'bbp_is_single_forum', 'dtbaker_bbps_feature_requests_bbp_is_single_forum', 3, 1 );

	// (hopefully) output the list of unread posts to logged in users.
	return $result;
}

/**
 * Is single forum.
 *
 * @param string $str Variable.
 *
 * @return bool
 */
function dtbaker_bbps_feature_requests_bbp_is_single_forum( $str ): bool {
	return true;
}

/**
 * @param string $str Variable.
 *
 * @return string
 */
function dtbaker_bbps_feature_requests_bbp_get_topic_pagination_count( $str ): string {
	return ' &nbsp; ';
}

/**
 * Request pagination.
 *
 * @param array $args Args.
 *
 * @return mixed
 */
function dtbaker_bbps_feature_requests_bbp_topic_pagination( $args ) {
	$args['total'] = 1;

	return $args;
}

/**
 * Meta query.
 *
 * @param array    $clauses  Clauses.
 * @param WP_Query $wp_query Query.
 *
 * @return array
 */
function dtbaker_bbps_my_meta_query( array $clauses, WP_Query $wp_query ): array {
	global $wpdb;
	if ( 123 === $wp_query->get( 'dtbaker_bbps_custom_where' ) ) {
		$clauses['join']  .= "
      LEFT JOIN {$wpdb->postmeta} m_status1 ON ({$wpdb->posts}.ID = m_status1.post_id AND m_status1.meta_key = '_bbps_topic_status')
      LEFT JOIN {$wpdb->postmeta} m_status2 ON ({$wpdb->posts}.ID = m_status2.post_id AND m_status2.meta_key = '_bbps_topic_feature_accepted')
      LEFT JOIN {$wpdb->postmeta} m_status3 ON ({$wpdb->posts}.ID = m_status3.post_id AND m_status3.meta_key = '_bbps_topic_feature_funding_paid')

    ";
		$clauses['where'] .= "\n AND ( m_status1.post_id IS NULL OR CAST(m_status1.meta_value AS CHAR) = '1') "; // OR (m_status2.meta_key = '_bbps_topic_status' AND CAST(m_status2.meta_value AS CHAR) != '2') ) )";.
		$clauses['where'] .= "\n AND ( m_status2.post_id IS NULL OR CAST(m_status2.meta_value AS CHAR) != '1') ";
		$clauses['where'] .= "\n AND ( m_status3.post_id IS NULL OR CAST(m_status3.meta_value AS CHAR) = '0') ";
		if ( isset( $GLOBALS['bbps_popular_ids'] ) && count( $GLOBALS['bbps_popular_ids'] ) ) {
			$clauses['where'] .= "\n AND ( {$wpdb->posts}.ID NOT IN ( " . implode( ',', $GLOBALS['bbps_popular_ids'] ) . ' ) ) ';
		}
	}

	return $clauses;
}

add_filter( 'posts_clauses', 'dtbaker_bbps_my_meta_query', 10, 2 );

/**
 * Filter the bbPress query args that are run when [bbp-topic-index] is executed.
 *
 * @param array $args Args.
 *
 * @return array
 */
function dtbaker_bbps_feature_requests_parse_args( array $args ): array {
	if ( isset( $GLOBALS['bbps_feature_request_params'] ) && isset( $GLOBALS['bbps_feature_request_params']['post_parent'] ) ) {
		$args['post_parent']   = $GLOBALS['bbps_feature_request_params']['post_parent'];
		$bbp                   = bbpress();
		$bbp->current_forum_id = $args['post_parent'];
	}

	if ( isset( $GLOBALS['bbps_feature_request_params']['limit'] ) ) {
		$args['posts_per_page'] = $GLOBALS['bbps_feature_request_params']['limit'];
	}

	if ( isset( $GLOBALS['bbps_feature_request_params']['type'] ) && 'resolved' === $GLOBALS['bbps_feature_request_params']['type'] ) {
		$args['meta_query']   = array();
		$args['meta_query'][] = array(
			'key'     => '_bbps_topic_status',
			'value'   => 2,
			'compare' => '=',
		);
	} elseif ( isset( $GLOBALS['bbps_feature_request_params']['type'] ) && 'new' === $GLOBALS['bbps_feature_request_params']['type'] ) {
		$args['dtbaker_bbps_custom_where'] = 123;
	} else {
		$args['meta_query'] = array();

		// copied from bbps_filter_bbp_after_has_topics_parse_args abpo
		$args['orderby']    = 'meta_value_num';
		$args['meta_key']   = '_bbps_topic_user_votes_count';
		$args['order']      = 'DESC';
		$args['meta_query'] = array(
			'relation' => 'OR',
			array(
				'key'     => '_bbps_topic_status',
				'compare' => 'NOT EXISTS',
				'value'   => '2',
			),
			array(
				'key'     => '_bbps_topic_status',
				'value'   => 2,
				'compare' => '!=',
			),
		);
	}

	return $args;
}

<?php

defined( 'ABSPATH' ) || exit;

/**
 * Class bbps_claimed_topics_widget
 */
class Bbps_Claimed_Topics_Widget extends WP_Widget {

	/**
	 * Constructor: bbps_claimed_topics_widget constructor.
	 */
	public function __construct() {
		$widget_ops = array(
			'classname'   => 'bbps_claimed_topics_widget',
			'description' => 'Dsiplay a list of the users claimed topics',
		);

		$control_ops = array(
			'width'   => 250,
			'height'  => 200,
			'id_base' => 'bbps_claimed_topics_widget',
		);

		parent::__construct( 'bbps_claimed_topics_widget', 'Claimed Topics', $widget_ops, $control_ops );
	}

	/**
	 * Form render.
	 *
	 * @param array $instance Instance.
	 *
	 * @return void
	 */
	public function form( $instance ) {
		$defaults = array(
			'title'                    => 'My Claimed Topics',
			'number_of_claimed_topics' => '10',
		);

		$instance = wp_parse_args( (array) $instance, $defaults );

		$title         = $instance['title'];
		$number_topics = $instance['number_of_claimed_topics'];

		?>
			<p>Title: <input class="widefat" name="<?php echo esc_attr( $this->get_field_name( 'title' ) ); ?>" type="text" value="<?php echo esc_attr( $title ); ?>" /> </p>
			<p>Topic to show<input class="widefat" name="<?php echo esc_attr( $this->get_field_name( 'number_of_claimed_topics' ) ); ?>" type="text" value="<?php echo esc_attr( $number_topics ); ?>" /></p>
			<p>How many resolved topics would you like to display?</p>					
		<?php

	}

	/**
	 * Update.
	 *
	 * @param array $new_instance New instance.
	 * @param array $old_instance Old instance.
	 *
	 * @return array
	 */
	public function update( $new_instance, $old_instance ): array {
		$instance                             = $old_instance;
		$instance['title']                    = $new_instance['title'];
		$instance['number_of_claimed_topics'] = $new_instance['number_of_claimed_topics'];

		return $instance;
	}

	/**
	 * Widget render.
	 *
	 * @param array $args Args.
	 * @param array $instance Instance.
	 */
	public function widget( $args, $instance ) {

		// phpcs:ignore WordPress.PHP.DontExtract
		extract( $args );

		$number_claimed_topics = $instance['number_of_claimed_topics'];

		// phpcs:ignore WordPress.Security.EscapeOutput
		echo $before_widget;

		$title = apply_filters( 'widget_title', $instance['title'] );

		if ( ! empty( $title ) ) {

			// phpcs:ignore WordPress.Security.EscapeOutput
			echo $before_title . esc_html( $title ) . $after_title;
		}

		get_users_claimed_topics( $number_claimed_topics );

		// phpcs:ignore WordPress.Security.EscapeOutput
		echo $after_widget . ' ';
	}
}

/**
 * End of claimed topics class.
 *
 * @param mixed $number_claimed_topics Count.
 */
function get_users_claimed_topics( $number_claimed_topics ) {
	global $wpdb;

	$the_user = wp_get_current_user();
	$user_id  = $the_user->ID;

	$claimed_query  = 'SELECT `meta_id`, `post_id` FROM ' . $wpdb->postmeta . " WHERE `meta_key` = '_bbps_topic_claimed' AND `meta_value` = " . $user_id . ' ORDER BY meta_id DESC LIMIT ' . $number_claimed_topics;
	$claimed_topics = $wpdb->get_results( $claimed_query ); // phpcs:ignore
	$permalink      = '';

	echo '<ul>';

	foreach ( (array) $claimed_topics as $claimed_topic ) {
		echo '<li><a href="' . esc_url( get_permalink( $claimed_topic->post_id ) ) . '"> ' . esc_html( get_the_title( $claimed_topic->post_id ) ) . '</a></li>';
	}

	echo '</ul>';
}

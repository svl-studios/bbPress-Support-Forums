<?php

defined( 'ABSPATH' ) || exit;

/**
 * Class Bbps_Support_Recently_Resolved_Widget
 */
class Bbps_Support_Recently_Resolved_Widget extends WP_Widget {

	/**
	 * Bbps_Support_Recently_Resolved_Widget constructor.
	 */
	public function __construct() {
		$widget_ops = array(
			'classname'   => 'bbps_support_recently_resolved_widget',
			'description' => 'Dsiplay a list of recently resolved topics in your forum',
		);

		$control_ops = array(
			'width'   => 250,
			'height'  => 200,
			'id_base' => 'bbps_support_recently_resolved_widget',
		);

		parent::__construct( 'bbps_support_recently_resolved_widget', 'Recently Resolved', $widget_ops, $control_ops );
	}

	/**
	 * Render form.
	 *
	 * @param array $instance Instance.
	 *
	 * @return void
	 */
	public function form( $instance ) {
		$defaults = array(
			'title'            => 'Recently Resolved',
			'number_of_topics' => '10',
		);

		$instance = wp_parse_args( (array) $instance, $defaults );

		$title         = $instance['title'];
		$number_topics = $instance['number_of_topics'];

		?>
			<p>Title: <input class="widefat" name="<?php echo esc_attr( $this->get_field_name( 'title' ) ); ?>" type="text" value="<?php echo esc_attr( $title ); ?>" /> </p>
			<p>Topic to show<input class="widefat" name="<?php echo esc_attr( $this->get_field_name( 'number_of_topics' ) ); ?>" type="text" value="<?php echo esc_attr( $number_topics ); ?>" /></p>
			<p>How many resolved topics would you like to display?</p>					
		<?php

	}

	/**
	 * Update settings.
	 *
	 * @param array $new_instance New instance.
	 * @param array $old_instance Old instance.
	 *
	 * @return array
	 */
	public function update( $new_instance, $old_instance ): array {
		$instance                     = $old_instance;
		$instance['title']            = $new_instance['title'];
		$instance['number_of_topics'] = $new_instance['number_of_topics'];

		return $instance;
	}

	/**
	 * Render widget.
	 *
	 * @param array $args Args.
	 * @param array $instance Instance.
	 */
	public function widget( $args, $instance ) {

		// phpcs:ignore WordPress.PHP.DontExtract
		extract( $args );

		$number_topics = $instance['number_of_topics'];

		// phpcs:ignore WordPress.Security.EscapeOutput
		echo $before_widget;

		$title = apply_filters( 'widget_title', $instance['title'] );

		if ( ! empty( $title ) ) {

			// phpcs:ignore WordPress.Security.EscapeOutput
			echo $before_title . esc_html( $title ) . $after_title;
		}

		get_resolved_topic_list( $number_topics );

		// phpcs:ignore WordPress.Security.EscapeOutput
		echo $after_widget . ' ';
	}
}

/**
 * Get resolved topic list.
 *
 * @param mixed $number_topics Number of topics.
 */
function get_resolved_topic_list( $number_topics ) {
	global $wpdb;

	$resolved_query = 'SELECT `meta_id`, `post_id` FROM ' . $wpdb->postmeta . " WHERE `meta_key` = '_bbps_topic_status' AND `meta_value` = 2 ORDER BY meta_id DESC LIMIT " . $number_topics;

	$resolved_topics = $wpdb->get_results( $resolved_query ); // phpcs:ignore
	$permalink       = '';

	echo '<ul>';

	foreach ( (array) $resolved_topics as $resolved_topic ) {
		echo '<li><a href="' . esc_url( get_permalink( $resolved_topic->post_id ) ) . '"> ' . esc_html( get_the_title( $resolved_topic->post_id ) ) . '</a></li>';
	}

	echo '</ul>';
}

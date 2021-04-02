<?php

defined( 'ABSPATH' ) || exit;

/**
 * Class Bbps_Support_Resolved_Count_Widget
 */
class Bbps_Support_Resolved_Count_Widget extends WP_Widget {

	/**
	 * Bbps_Support_Resolved_Count_Widget constructor.
	 */
	public function __construct() {
		$widget_ops = array(
			'classname'   => 'bbps_support_resolved_count_widget',
			'description' => 'Dsiplay a count of resolved topics in your forum',
		);

		$control_ops = array(
			'width'   => 250,
			'height'  => 200,
			'id_base' => 'bbps_support_resolved_count_widget',
		);

		parent::__construct( 'bbps_support_resolved_count_widget', 'Resolved Topic Count', $widget_ops, $control_ops );
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
			'title'                => 'Resolved Topics',
			'show_total'           => '',
			'show_resolved'        => '',
			'text_before_total'    => '',
			'text_after_total'     => '',
			'text_before_resolved' => '',
			'text_after_resolved'  => '',
		);

		$instance = wp_parse_args( (array) $instance, $defaults );

		$title                = $instance['title'];
		$show_total           = $instance['show_total'];
		$show_resolved        = $instance['show_resolved'];
		$text_before_total    = $instance['text_before_total'];
		$text_after_total     = $instance['text_after_total'];
		$text_before_resolved = $instance['text_before_resolved'];
		$text_after_resolved  = $instance['text_after_resolved'];

		if ( function_exists( 'bbps_add_support_forum_features' ) ) {
			?>
			<p>Title: <input class="widefat" name="<?php echo esc_attr( $this->get_field_name( 'title' ) ); ?>" type="text" value="<?php echo esc_attr( $title ); ?>"/></p>

			<p> Display Total Topic Count: <input class="checkbox" type="checkbox" <?php checked( $instance['show_total'], 'on' ); ?> name="<?php echo esc_attr( $this->get_field_name( 'show_total' ) ); ?>"/></p>
			<p> This will display the total number of topics in your forums </p>

			<p> Display Resolved Topic Count: <input class="checkbox" type="checkbox" <?php checked( $instance['show_resolved'], 'on' ); ?> name="<?php echo esc_attr( $this->get_field_name( 'show_resolved' ) ); ?>"/></p>
			<p> This will display the total number of resolved topics in your forums </p>

			<p>Text Before Total: <input class="widefat" name="<?php echo esc_attr( $this->get_field_name( 'text_before_total' ) ); ?>" type="text" value="<?php echo esc_attr( $text_before_total ); ?>"/> The text you would like to display before the total topics count eg: Our Forums have </p>

			<p>Text After Total: <input class="widefat" name="<?php echo esc_attr( $this->get_field_name( 'text_after_total' ) ); ?>" type="text" value="<?php echo esc_attr( $text_after_total ); ?>"/> The text you would like to display after the total topics count eg: topics in total </p>
			<p>Text Before Resolved Total: <input class="widefat" name="<?php echo esc_attr( $this->get_field_name( 'text_before_resolved' ) ); ?>" type="text" value="<?php echo esc_attr( $text_before_resolved ); ?>"/> The text you would like to display before the resolved topic count eg: We have </p>

			<p>Text After Resolved Total: <input class="widefat" name="<?php echo esc_attr( $this->get_field_name( 'text_after_resolved' ) ); ?>" type="text" value="<?php echo esc_attr( $text_after_resolved ); ?>"/> The text you would like to display after the resolved topic count eg: topics that are resolved </p>
			<?php

		} else {
			echo '<p> You need the GetShopped Support plugin to use this widget </p>';
		}
	}

	/**
	 * Save settings.
	 *
	 * @param array $new_instance New instance.
	 * @param array $old_instance Old instance.
	 *
	 * @return array
	 */
	public function update( $new_instance, $old_instance ): array {
		$instance                         = $old_instance;
		$instance['title']                = $new_instance['title'];
		$instance['show_total']           = $new_instance['show_total'];
		$instance['show_resolved']        = $new_instance['show_resolved'];
		$instance['text_before_total']    = $new_instance['text_before_total'];
		$instance['text_after_total']     = $new_instance['text_after_total'];
		$instance['text_before_resolved'] = $new_instance['text_before_resolved'];
		$instance['text_after_resolved']  = $new_instance['text_after_resolved'];

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

		// phpcs:ignore WordPress.Security.EscapeOutput
		echo $before_widget;

		$title = apply_filters( 'widget_title', $instance['title'] );

		$text_before_total    = empty( $instance['text_before_total'] ) ? '' : $instance['text_before_total'];
		$text_after_total     = empty( $instance['text_after_total'] ) ? '' : $instance['text_after_total'];
		$text_before_resolved = empty( $instance['text_before_resolved'] ) ? '' : $instance['text_before_resolved'];
		$text_after_resolved  = empty( $instance['text_after_resolved'] ) ? '' : $instance['text_after_resolved'];
		$total_resolved       = bbps_get_resolved_count();
		$total_topics         = bbps_get_topic_count();

		if ( ! empty( $title ) ) {

			// phpcs:ignore WordPress.Security.EscapeOutput
			echo $before_title . esc_html( $title ) . $after_title;
		}

		echo wp_kses_post( $text_before_total ) . ' ';

		if ( 'on' === $instance['show_total'] ) {
			echo esc_html( $total_topics ) . ' ';
		}

		echo wp_kses_post( $text_after_total ) . '<br />';
		echo wp_kses_post( $text_before_resolved ) . ' ';

		if ( 'on' === $instance['show_resolved'] ) {
			echo esc_html( $total_resolved ) . ' ';
		}

		echo wp_kses_post( $text_after_resolved ) . ' ';

		// phpcs:ignore WordPress.Security.EscapeOutput
		echo $after_widget . ' ';
	}
}

/**
 * Does a simple mysql query and counts all the resolved topics - status id 2.
 *
 * @return int|void
 */
function bbps_get_resolved_count() {
	global $wpdb;
	$resolved_query = 'SELECT `meta_id` FROM ' . $wpdb->postmeta . " WHERE `meta_key` = '_bbps_topic_status' AND `meta_value` = 2";
	$resolved_count = $wpdb->get_col( $resolved_query ); // phpcs:ignore

	return count( $resolved_count );
}

/**
 * Does a simple mysql query and counts all the topics.
 *
 * @return int|void
 */
function bbps_get_topic_count() {
	global $wpdb;
	$topic_query = 'SELECT `ID` FROM ' . $wpdb->posts . " WHERE `post_type` = 'topic' AND `post_status` = 'publish'";

	$topic_count = $wpdb->get_col( $topic_query ); // phpcs:ignore

	return count( $topic_count );
}

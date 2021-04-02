<?php

defined( 'ABSPATH' ) || exit;

/**
 * Class Bbps_Support_Urgent_Topics_Widget
 */
class Bbps_Support_Urgent_Topics_Widget extends WP_Widget {

	/**
	 * Bbps_Support_Urgent_Topics_Widget constructor.
	 */
	public function __construct() {
		$widget_ops = array(
			'classname'   => 'bbps_support_urgent_topics_widget',
			'description' => 'Dsiplay a list of urgent topics in your forum',
		);

		$control_ops = array(
			'width'   => 250,
			'height'  => 200,
			'id_base' => 'bbps_support_urgent_topics_widget',
		);

		parent::__construct( 'bbps_support_urgent_topics_widget', 'Urgent Topics', $widget_ops, $control_ops );
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
			'title'                  => 'Urgent Topics',
			'show_urgent_list_admin' => '',
			'show_urgent_list_mod'   => '',
			'show_urgent_list_user'  => '',
		);

		$instance = wp_parse_args( (array) $instance, $defaults );

		$title      = $instance['title'];
		$show_admin = $instance['show_urgent_list_admin'];
		$show_mod   = $instance['show_urgent_list_mod'];
		$show_user  = $instance['show_urgent_list_user'];

		?>
		<p>Title: <input class="widefat" name="<?php echo esc_attr( $this->get_field_name( 'title' ) ); ?>" type="text" value="<?php echo esc_attr( $title ); ?>"/></p>
		<p>Select the user types who will be able to see the list of urgent topics</p>
		<p> Administrators <input class="checkbox" type="checkbox" <?php checked( $instance['show_urgent_list_admin'], 'on' ); ?> name="<?php echo esc_attr( $this->get_field_name( 'show_urgent_list_admin' ) ); ?>"/></p>
		<p> Moderators <input class="checkbox" type="checkbox" <?php checked( $instance['show_urgent_list_mod'], 'on' ); ?> name="<?php echo esc_attr( $this->get_field_name( 'show_urgent_list_mod' ) ); ?>"/></p>
		<p> Site Users <input class="checkbox" type="checkbox" <?php checked( $instance['show_urgent_list_user'], 'on' ); ?> name="<?php echo esc_attr( $this->get_field_name( 'show_urgent_list_user' ) ); ?>"/></p>
		<?php

	}

	/**
	 * Savev settings.
	 *
	 * @param array $new_instance New instance.
	 * @param array $old_instance Old instance.
	 *
	 * @return array
	 */
	public function update( $new_instance, $old_instance ): array {
		$instance                           = $old_instance;
		$instance['title']                  = $new_instance['title'];
		$instance['show_urgent_list_admin'] = $new_instance['show_urgent_list_admin'];
		$instance['show_urgent_list_mod']   = $new_instance['show_urgent_list_mod'];
		$instance['show_urgent_list_user']  = $new_instance['show_urgent_list_user'];

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

		if ( ( 'on' === $instance['show_urgent_list_admin'] && current_user_can( 'administrator' ) ) || ( 'on' === $instance['show_urgent_list_mod'] && current_user_can( 'bbp_moderator' ) ) || ( 'on' === $instance['show_urgent_list_user'] && is_user_logged_in() ) ) {

			// phpcs:ignore WordPress.Security.EscapeOutput
			echo $before_widget;

			$title = apply_filters( 'widget_title', $instance['title'] );

			if ( ! empty( $title ) ) {

				// phpcs:ignore WordPress.Security.EscapeOutput
				echo $before_title . esc_html( $title ) . $after_title;
			}

			get_urgent_topic_list();

			// phpcs:ignore WordPress.Security.EscapeOutput
			echo $after_widget . ' ';
		}
	}
}

/**
 * Urgent topics we want the oldest at the top.
 */
function get_urgent_topic_list() {
	global $wpdb;

	$urgent_query  = 'SELECT `post_id` FROM ' . $wpdb->postmeta . " WHERE `meta_key` = '_bbps_urgent_topic' AND `meta_value` = 1";
	$urgent_topics = $wpdb->get_col( $urgent_query ); // phpcs:ignore
	$permalink     = '';
	$urgent_topic  = '';

	echo '<ul>';

	foreach ( $urgent_topics as $urgent_topic ) {
		echo '<li><a href="' . esc_url( get_permalink( $urgent_topic ) ) . '"> ' . esc_html( get_the_title( $urgent_topic ) ) . '</a></li>';
	}

	echo '</ul>';
}

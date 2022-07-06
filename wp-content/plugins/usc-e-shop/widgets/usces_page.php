<?php

/**
 * Welcart_page Class
 */
class Welcart_page extends WP_Widget {
	/** constructor */
	function __construct() {
		parent::__construct(false, $name = 'Welcart '.__('Page', 'usces'));
	}

	/** @see WP_Widget::widget */
	function widget($args, $instance) {
		extract( $args );
		$title = ( !isset($instance['title']) || WCUtils::is_blank($instance['title'])) ? 'Welcart '.__('Page', 'usces') : $instance['title'];
		$icon = ( !isset($instance['icon']) || WCUtils::is_blank($instance['icon'])) ? 1 : (int)$instance['icon'];
		$page = ( !isset($instance['page']) || WCUtils::is_blank($instance['page'])) ? '' : $instance['page'];
		$img_path = file_exists(get_stylesheet_directory().'/images/page.png') ? get_stylesheet_directory_uri().'/images/page.png' : USCES_FRONT_PLUGIN_URL . '/images/page.png';
		if($icon == 1) $before_title .= '<img src="' . $img_path . '" alt="' . $title . '" />';
		?>
			<?php echo $before_widget; ?>
				<?php echo $before_title
					. esc_html($title)
					. $after_title; ?>

		<ul class="ucart_widget_body">
		<?php wp_list_pages(apply_filters('usces_filter_wc_widget_page_arg', ('title_li=&include=' . $page), $page)) ; ?>
		</ul>

			<?php echo $after_widget; ?>
		<?php
	}

	/** @see WP_Widget::update */
	function update($new_instance, $old_instance) {
		return $new_instance;
	}

	/** @see WP_Widget::form */
	function form($instance) {
		$title = ( !isset($instance['title']) || WCUtils::is_blank($instance['title'])) ? 'Welcart '.__('Page', 'usces') : esc_attr($instance['title']);
		$icon = ( !isset($instance['icon']) || WCUtils::is_blank($instance['icon'])) ? 1 : (int)$instance['icon'];
		$page = ( !isset($instance['page']) || WCUtils::is_blank($instance['page'])) ? '' : $instance['page'];
		?>
			<p><label for="<?php echo $this->get_field_id('title'); ?>"><?php _e('Title:', 'usces'); ?> <input class="widefat" id="<?php echo $this->get_field_id('title'); ?>" name="<?php echo $this->get_field_name('title'); ?>" type="text" value="<?php echo $title; ?>" /></label></p>
			<p><label for="<?php echo $this->get_field_id('icon'); ?>"><?php _e('display of icon', 'usces'); ?>: <select class="widefat" id="<?php echo $this->get_field_id('icon'); ?>" name="<?php echo $this->get_field_name('icon'); ?>"><option value="1"<?php if($icon == 1){echo ' selected="selected"';} ?>><?php _e('Indication', 'usces'); ?></option><option value="2"<?php if($icon == 2){echo ' selected="selected"';} ?>><?php _e('Non-indication', 'usces'); ?></option></select></label></p>
			<p><label for="<?php echo $this->get_field_id('page'); ?>"><?php _e('Page ID(comma separate)', 'usces'); ?>: <input class="widefat" id="<?php echo $this->get_field_id('page'); ?>" name="<?php echo $this->get_field_name('page'); ?>" type="text" value="<?php echo $page; ?>" /></label></p>
		<?php 
	}

}
?>
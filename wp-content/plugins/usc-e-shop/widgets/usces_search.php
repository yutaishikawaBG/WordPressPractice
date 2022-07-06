<?php

/**
 * Welcart_search Class
 */
class Welcart_search extends WP_Widget {
	/** constructor */
	function __construct() {
		parent::__construct(false, $name = 'Welcart '.__('keyword search', 'usces'));
	}

	/** @see WP_Widget::widget */
	function widget($args, $instance) {
		global $usces;
		extract( $args );
		$title = ( !isset($instance['title']) || WCUtils::is_blank($instance['title'])) ? 'Welcart '.__('keyword search', 'usces') : $instance['title'];
		$icon = ( !isset($instance['icon']) || WCUtils::is_blank($instance['icon'])) ? 1 : (int)$instance['icon'];
		$img_path = file_exists(get_stylesheet_directory().'/images/search.png') ? get_stylesheet_directory_uri().'/images/search.png' : USCES_FRONT_PLUGIN_URL . '/images/search.png';
		if($icon == 1) $before_title .= '<img src="' . $img_path . '" alt="' . $title . '" />';
		?>
			<?php echo $before_widget; ?>
				<?php echo $before_title
					. esc_html($title)
					. $after_title; ?>

		<ul class="ucart_search_body ucart_widget_body"><li>
		<form method="get" id="searchform" action="<?php echo home_url(); ?>" >
		<input type="text" value="" name="s" id="s" class="searchtext" /><input type="submit" id="searchsubmit" value="<?php _e('Search', 'usces'); ?>" />
		<?php
		$search_form = '<div><a href="' . (USCES_CART_URL . $usces->delim) . 'usces_page=search_item">' . __("'AND' search by categories", 'usces') . '&gt;</a></div>';
		echo apply_filters( 'usces_filter_search_widget_form', $search_form );
		?>
		</form>
		</li></ul>

			<?php echo $after_widget; ?>
		<?php
	}

	/** @see WP_Widget::update */
	function update($new_instance, $old_instance) {
		return $new_instance;
	}

	/** @see WP_Widget::form */
	function form($instance) {
		$title = ( !isset($instance['title']) || WCUtils::is_blank($instance['title'])) ? 'Welcart '.__('keyword search', 'usces') : esc_attr($instance['title']);
		$icon = ( !isset($instance['icon']) || WCUtils::is_blank($instance['icon'])) ? 1 : (int)$instance['icon'];
		?>
			<p><label for="<?php echo $this->get_field_id('title'); ?>"><?php _e('Title:', 'usces'); ?> <input class="widefat" id="<?php echo $this->get_field_id('title'); ?>" name="<?php echo $this->get_field_name('title'); ?>" type="text" value="<?php echo $title; ?>" /></label></p>
			<p><label for="<?php echo $this->get_field_id('icon'); ?>"><?php _e('display of icon', 'usces'); ?>: <select class="widefat" id="<?php echo $this->get_field_id('icon'); ?>" name="<?php echo $this->get_field_name('icon'); ?>"><option value="1"<?php if($icon == 1){echo ' selected="selected"';} ?>><?php _e('Indication', 'usces'); ?></option><option value="2"<?php if($icon == 2){echo ' selected="selected"';} ?>><?php _e('Non-indication', 'usces'); ?></option></select></label></p>
		<?php 
	}

}
?>
<?php

/**
 * Welcart_login Class
 */
class Welcart_login extends WP_Widget {
	/** constructor */
	function __construct() {
		parent::__construct(false, $name = 'Welcart '.__('Log-in', 'usces'));
	}

	/** @see WP_Widget::widget */
	function widget($args, $instance) {
		global $usces;
		
		extract( $args );
		$title = ( !isset($instance['title']) || WCUtils::is_blank($instance['title'])) ? 'Welcart '.__('Log-in', 'usces') : $instance['title'];
		$icon = ( !isset($instance['icon']) || WCUtils::is_blank($instance['icon'])) ? 1 : (int)$instance['icon'];
		$img_path = file_exists(get_stylesheet_directory().'/images/login.png') ? get_stylesheet_directory_uri().'/images/login.png' : USCES_FRONT_PLUGIN_URL . '/images/login.png';
		if($icon == 1) $before_title .= '<img src="' . $img_path . '" alt="' . $title . '" />';
		?>
			<?php echo $before_widget; ?>
				<?php echo $before_title
					. esc_html($title)
					. $after_title; ?>

		<ul class="ucart_login_body ucart_widget_body"><li>

		<?php ob_start(); ?>

		<div class="loginbox">
		<?php if ( ! usces_is_login() ) { ?>
		<form name="loginwidget" id="loginformw" action="<?php echo USCES_MEMBER_URL; ?>" method="post">
		<p>
		<label><?php _e('e-mail adress', 'usces'); ?><br />
		<input type="text" name="loginmail" id="loginmailw" class="loginmail" value="<?php usces_remembername(); ?>" size="20" /></label><br />
		<label><?php _e('password', 'usces'); ?><br />
		<input type="password" name="loginpass" id="loginpassw" class="loginpass" size="20" autocomplete="off" /></label><br />
		<label><input name="rememberme" type="checkbox" id="remembermew" value="forever" /> <?php _e('Remember Me', 'usces'); ?></label></p>
		<p class="submit">
		<input type="submit" name="member_login" id="member_loginw" value="<?php _e('Log-in', 'usces'); ?>" />
		</p>
		<?php
			echo apply_filters('usces_filter_login_inform', NULL);
			$noncekey = 'post_member' . $usces->get_uscesid(false);
			wp_nonce_field( $noncekey, 'wel_nonce' );
		?>
		</form>
		<a href="<?php echo USCES_LOSTMEMBERPASSWORD_URL; ?>" title="<?php _e('Pssword Lost and Found', 'usces'); ?>"><?php _e('Lost your password?', 'usces'); ?></a><br />
		<a href="<?php echo USCES_NEWMEMBER_URL; ?>" title="<?php _e('New enrollment for membership.', 'usces'); ?>"><?php _e('New enrollment for membership.', 'usces'); ?></a>
		<?php }else{ ?>
		<div><?php echo sprintf(_x('%s', 'honorific', 'usces'), usces_the_member_name('return')); ?></div>
		<?php echo usces_loginout(); ?><br />
		<a href="<?php echo USCES_MEMBER_URL; ?>" class="login_widget_mem_info_a"><?php _e('Membership information','usces') ?></a>
		<?php } ?>
		</div>

		<?php
		$loginbox = ob_get_contents();
		ob_end_clean();
		echo apply_filters('usces_filter_login_widget', $loginbox, $args, $instance);
		?>

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
		$title = (!isset($instance['title']) || WCUtils::is_blank($instance['title']) ) ? 'Welcart '.__('Log-in', 'usces') : esc_attr($instance['title']);
		$icon = (!isset($instance['icon']) || WCUtils::is_blank($instance['icon']) ) ? 1 : (int)$instance['icon'];
		?>
			<p><label for="<?php echo $this->get_field_id('title'); ?>"><?php _e('Title:', 'usces'); ?> <input class="widefat" id="<?php echo $this->get_field_id('title'); ?>" name="<?php echo $this->get_field_name('title'); ?>" type="text" value="<?php echo $title; ?>" /></label></p>
			<p><label for="<?php echo $this->get_field_id('icon'); ?>"><?php _e('display of icon', 'usces'); ?>: <select class="widefat" id="<?php echo $this->get_field_id('icon'); ?>" name="<?php echo $this->get_field_name('icon'); ?>"><option value="1"<?php if($icon == 1){echo ' selected="selected"';} ?>><?php _e('Indication', 'usces'); ?></option><option value="2"<?php if($icon == 2){echo ' selected="selected"';} ?>><?php _e('Non-indication', 'usces'); ?></option></select></label></p>
		<?php 
	}

}
?>
<?php

$member_compmode = $this->page;
$html = '<div id="memberpages">

<div class="post">';

$html .= '<div class="header_explanation">';
$header = '';
$html .= apply_filters('usces_filter_memberverified_page_header', $header);
$html .= '</div>';


$html .= '<h2>' . __('Member registration complete', 'usces') . '</h2>';
$html .= '<p>' . __('Member registration is complete. Click "Continue shopping" to continue shopping.', 'usces') . '</p>';


$html .= '<div class="footer_explanation">';
$footer = '';
$html .= apply_filters('usces_filter_memberverified_page_footer', $footer);
$html .= '</div>';

$html .= '<div><a class="welcart-btn orange" href="' . USCES_LOGIN_URL . '&redirect_to=' . urlencode(USCES_CART_URL . '?usces_page=delivery') . '" />'.__('Continue shopping', 'usces').'</a></div>&nbsp;&nbsp;';
$html .= '<div class="send"><a href="' . home_url() . '" class="back_to_top_button">' . __('Back to the top page.', 'usces') . '</a></div>'."\n";

	
$html .= '</div>

	</div>';

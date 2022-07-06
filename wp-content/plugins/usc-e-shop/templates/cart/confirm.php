<?php
global $usces_entries, $usces_carts, $usces_members;
usces_get_members();
usces_get_entries();
usces_get_carts();
$html = '<div id="info-confirm">

	<div class="usccart_navi">
	<ol class="ucart">
	<li class="ucart usccart">' . __('1.Cart','usces') . '</li>
	<li class="ucart usccustomer">' . __('2.Customer Info','usces') . '</li>
	<li class="ucart uscdelivery">' . __('3.Deli. & Pay.','usces') . '</li>
	<li class="ucart uscconfirm usccart_confirm">' . __('4.Confirm','usces') . '</li>
	</ol>
	</div>';

$html .= '<div class="header_explanation">';
$header = '';
$html .= apply_filters('usces_filter_confirm_page_header', $header);
$html .= '</div>';
$html .= '<div class="error_message">' . $this->error_message . '</div>';

$confirm_table_head = '<div id="cart">
<div class="currency_code">' . __('Currency','usces') . ' : ' . __(usces_crcode( 'return' ), 'usces') . '</div>
<table cellspacing="0" id="cart_table">
		<thead>
		<tr>
			<th scope="row" class="num">' . __('No.','usces') . '</th>
			<th class="thumbnail">&nbsp;&nbsp;</th>
			<th class="productname">' . __('Items','usces') . '</th>
			<th class="unitprice">' . __('Unit price','usces') . '</th>
			<th class="quantity">'.__('Quantity', 'usces').'</th>
			<th class="subtotal">'.__('Amount', 'usces').'</th>
			<th class="action"></th>
		</tr>
		</thead>
		<tbody>';
$html .= apply_filters( 'usces_filter_confirm_table_head', $confirm_table_head );

$member = $this->get_member();

$html .= usces_get_confirm_rows('return');

$confirm_table_footer = '</tbody>
	<tfoot>
	<tr class="total_items_price">
		<th class="num">&nbsp;</th>
		<th class="thumbnail">&nbsp;</th>
		<th colspan="3" class="aright totallabel">'.__('total items', 'usces').'</th>
		<th class="aright totalend">' . usces_crform($usces_entries['order']['total_items_price'], true, false, 'return') . '</th>
		<th class="action">&nbsp;</th>
	</tr>';
if( !empty($usces_entries['order']['discount']) ) {
	$confirm_table_footer .= '<tr class="discount">
		<td class="num">&nbsp;</td>
		<td class="thumbnail">&nbsp;</td>
		<td colspan="3" class="aright totallabel">'.apply_filters('usces_confirm_discount_label', __('Campaign discount', 'usces')).'</td>
		<td class="aright totalend" style="color:#FF0000">' . usces_crform($usces_entries['order']['discount'], true, false, 'return') . '</td>
		<td class="action">&nbsp;</td>
	</tr>';
}
if( usces_is_tax_display() && 'products' == $this->options['tax_target'] ) {
	$confirm_table_footer .= '<tr class="tax">
		<td class="num">&nbsp;</td>
		<td class="thumbnail">&nbsp;</td>
		<td colspan="3" class="aright totallabel">'.usces_tax_label(array(), 'return').'</td>
		<td class="aright totalend">' . usces_tax($usces_entries, 'return') . '</td>
		<td class="action">&nbsp;</td>
	</tr>';
}
if( $this->options['membersystem_state'] == 'activate' && $this->options['membersystem_point'] == 'activate' && !empty($usces_entries['order']['usedpoint']) && $this->options['point_coverage'] == 0 ) {
	$confirm_table_footer .= '<tr class="usedpoint">
		<td class="num">&nbsp;</td>
		<td class="thumbnail">&nbsp;</td>
		<td colspan="3" class="aright totallabel">'.__('Used points', 'usces').'</td>
		<td class="aright totalend" style="color:#FF0000">' . number_format($usces_entries['order']['usedpoint']) . '</td>
		<td class="action">&nbsp;</td>
	</tr>';
}
$confirm_table_footer .= '<tr class="shipping_charge">
	<td class="num">&nbsp;</td>
	<td class="thumbnail">&nbsp;</td>
	<td colspan="3" class="aright totallabel">'.__('Shipping', 'usces').'</td>
	<td class="aright totalend">' . usces_crform($usces_entries['order']['shipping_charge'], true, false, 'return') . '</td>
	<td class="action">&nbsp;</td>
	</tr>';
if( !empty($usces_entries['order']['cod_fee']) ) {
	$confirm_table_footer .= '<tr class="cod_fee">
		<td class="num">&nbsp;</td>
		<td class="thumbnail">&nbsp;</td>
		<td colspan="3" class="aright totallabel">'.apply_filters('usces_filter_cod_label', __('COD fee', 'usces')).'</td>
		<td class="aright totalend">' . usces_crform($usces_entries['order']['cod_fee'], true, false, 'return') . '</td>
		<td class="action">&nbsp;</td>
	</tr>';
}
if( usces_is_tax_display() && 'all' == $this->options['tax_target'] ) {
	$confirm_table_footer .= '<tr class="tax">
		<td class="num">&nbsp;</td>
		<td class="thumbnail">&nbsp;</td>
		<td colspan="3" class="aright totallabel">'.usces_tax_label(array(), 'return').'</td>
		<td class="aright totalend">' . usces_tax($usces_entries, 'return') . '</td>
		<td class="action">&nbsp;</td>
	</tr>';
}
if( $this->options['membersystem_state'] == 'activate' && $this->options['membersystem_point'] == 'activate' && !empty($usces_entries['order']['usedpoint']) && $this->options['point_coverage'] == 1 ) {
	$confirm_table_footer .= '<tr class="usedpoint">
		<td class="num">&nbsp;</td>
		<td class="thumbnail">&nbsp;</td>
		<td colspan="3" class="aright totallabel">'.__('Used points', 'usces').'</td>
		<td class="aright totalend" style="color:#FF0000">' . number_format($usces_entries['order']['usedpoint']) . '</td>
		<td class="action">&nbsp;</td>
	</tr>';
}
$confirm_table_footer .= '<tr class="total_full_price">
	<th class="num">&nbsp;</th>
	<th class="thumbnail">&nbsp;</th>
	<th colspan="3" class="aright totallabel">'.__('Total Amount', 'usces').'</th>
	<th class="aright totalend">' . usces_crform($usces_entries['order']['total_full_price'], true, false, 'return') . '</th>
	<th class="action">&nbsp;</th>
	</tr>
	</tfoot>
	</table>';
$html .= apply_filters( 'usces_filter_confirm_table_footer', $confirm_table_footer );
$html .= apply_filters( 'usces_filter_confirm_table_after', '' );

if( $this->options['membersystem_state'] == 'activate' &&  $this->options['membersystem_point'] == 'activate' &&  $this->is_member_logged_in() ) {
	$confirm_point_table = '<form action="' . USCES_CART_URL . '" method="post" onKeyDown="if (event.keyCode == 13) {return false;}">
		<div class="error_message">' . $this->error_message . '</div>
		<table cellspacing="0" id="point_table">
		<tr>
		<td>'.__('The current point', 'usces').'</td>
		<td><span class="point">' . $member['point'] . '</span>pt</td>
		</tr>
		<tr>
		<td>'.__('Points you are using here', 'usces').'</td>
		<td><input name="offer[usedpoint]" class="used_point" type="text" value="' . esc_attr($usces_entries['order']['usedpoint']) . '" />pt</td>
		</tr>
		<tr>
		<td colspan="2"><input name="use_point" type="submit" class="use_point_button" value="'.__('Use the points', 'usces').'" /></td>
		</tr>
	</table>';
	$confirm_point_table = apply_filters( 'usces_filter_confirm_point_table', $confirm_point_table );
	$html = apply_filters('usces_filter_confirm_point_inform', $html . $confirm_point_table );

	$noncekey = 'use_point' . $this->get_uscesid(false);
	$html .= wp_nonce_field( $noncekey, 'wc_nonce', true, false );
	$html .= '</form>';
}
$html .= apply_filters('usces_filter_confirm_after_form', NULL);
$html .= '</div>';
$customer_info_table = '
	<table id="confirm_table">
	<tr class="ttl">
	<td colspan="2"><h3>'.__('Customer Information', 'usces').'</h3></td>
	</tr>
	<tr>
	<th>'.__('e-mail adress', 'usces').'</th>
	<td>' . esc_html($usces_entries['customer']['mailaddress1']) . '</td>
	</tr>';

$customer_info_table .= uesces_addressform( 'confirm', $usces_entries );

$customer_info_table .= '<tr>';
$customer_info_table .= '<td class="ttl" colspan="2"><h3>'.__('Others', 'usces').'</h3></td>
	</tr>';
$shipping_info = '<tr>
	<th>'.__('shipping option', 'usces').'</th><td>' . esc_html(usces_delivery_method_name( $usces_entries['order']['delivery_method'], 'return' )) . '</td>
	</tr>
	<tr>
	<th>'.__('Delivery date', 'usces').'</th><td>' . esc_html($usces_entries['order']['delivery_date']) . '</td>
	</tr>
	<tr class="bdc">
	<th>'.__('Delivery Time', 'usces').'</th><td>' . esc_html($usces_entries['order']['delivery_time']) . '</td>
	</tr>';
$customer_info_table .= apply_filters('usces_filter_confirm_shipping_info', $shipping_info);

$customer_info_table .= '<tr>
	<th>'.__('payment method', 'usces').'</th><td>' . esc_html($usces_entries['order']['payment_name'] . usces_payment_detail($usces_entries)) . '</td>
	</tr>';
$customer_info_table .= usces_custom_field_info($usces_entries, 'order', '', 'return');
$customer_info_table .= '<tr>
	<th>'.__('Notes', 'usces').'</th><td>' . nl2br(esc_html($usces_entries['order']['note'])) . '</td>
	</tr>';
$customer_info_table .= '</table>';
$html .= apply_filters( 'usces_filter_confirm_customer_info_table', $customer_info_table );
$html .= apply_filters( 'usces_filter_confirm_page_notes', '' );

require( USCES_PLUGIN_DIR . "/includes/purchase_button.php");


$html .= '<div class="footer_explanation">';
$footer = '';
$html .= apply_filters('usces_filter_confirm_page_footer', $footer);
$html .= '</div>';

$html .= '</div>';
?>

<?php
usces_the_item();

$html = '<div id="itempage">';
$html .= '<form action="' . USCES_CART_URL . '" method="post">';

$html .= '<div class="itemimg">';
$html .= '<a href="' . usces_the_itemImageURL(0, 'return') . '"';
$html .= apply_filters( 'usces_itemimg_anchor_rel', '' );
$html .= '>';
$itemImage = usces_the_itemImage(0, 200, 250, $post, 'return');
$html .= apply_filters('usces_filter_the_itemImage', $itemImage, $post);
$html .= '</a>';
$html .= '</div>';

if(usces_sku_num() === 1) { //1SKU
	usces_have_skus();

	$html .= '<h3>' . esc_html(usces_the_itemName( 'return' )) . '&nbsp; (' . esc_html(usces_the_itemCode( 'return' )) . ') </h3>';
	$html .= '<div class="exp clearfix">';
	$html .= '<div class="field">';
	if( usces_the_itemCprice('return') > 0 ){
		$usces_listprice = __('List price', 'usces') . usces_guid_tax('return');
		$html .= '<div class="field_name">' . apply_filters('usces_filter_listprice_label', $usces_listprice, __('List price', 'usces'), usces_guid_tax('return')) . '</div>';
		$html .= '<div class="field_cprice">' . usces_the_itemCpriceCr('return') . '</div>';
	}
	$usces_sellingprice = __('selling price', 'usces') . usces_guid_tax('return');
	$html .= '<div class="field_name">' . apply_filters('usces_filter_sellingprice_label', $usces_sellingprice, __('selling price', 'usces'), usces_guid_tax('return')) . '</div>';
	$html .= '<div class="field_price">' . usces_the_itemPriceCr('return') . '</div>';
	$html .= usces_crform_the_itemPriceCr_taxincluded( true, '', '', '', true, false, true, 'return' );
	$html .= '</div>';
	$singlestock = '<div class="field">' . __('stock status', 'usces') . ' : ' . esc_html(usces_get_itemZaiko( 'name' )) . '</div>';
	$html .= apply_filters('single_item_stock_field', $singlestock);
	$item_custom = usces_get_item_custom( $post->ID, 'list', 'return' );
	if($item_custom){
		$html .= '<div class="field">';
		$html .= $item_custom;
		$html .= '</div>';
	}

	$html .= $content;
	$html .= '</div><!-- end of exp -->';
	$html .= usces_the_itemGpExp('return');
	$html .= '<div class="skuform" align="right">';
	if (usces_is_options()) {
		$html .= '<table class="item_option"><caption>' . apply_filters('usces_filter_single_item_options_caption', __('Please appoint an option.', 'usces'), $post) . '</caption>';
		while (usces_have_options()) {
			$opttr = "<tr><th>" . esc_html(usces_getItemOptName()) . '</th><td>' . usces_the_itemOption(usces_getItemOptName(),'','return') . '</td></tr>';
			$html .= apply_filters('usces_filter_singleitem_option', $opttr, usces_getItemOptName(), NULL);
		}
		$html .= "</table>";
	}
	if( !usces_have_zaiko() ){
		$html .= '<div class="zaiko_status">' . apply_filters('usces_filters_single_sku_zaiko_message', esc_html(usces_get_itemZaiko( 'name' ))) . '</div>';
	}else{
		$html .= '<div style="margin-top:10px">'.__('Quantity', 'usces').usces_the_itemQuant('return') . esc_html(usces_the_itemSkuUnit('return')) . usces_the_itemSkuButton(__('Add to Shopping Cart', 'usces'), 0, 'return') . '</div>';
		$html .= '<div class="error_message">' . usces_singleitem_error_message($post->ID, usces_the_itemSku('return'), 'return') . '</div>';
	}

	$html .= '</div><!-- end of skuform -->';
	$html .= apply_filters('single_item_single_sku_after_field', NULL);

} elseif(usces_sku_num() > 1) { //some SKU
	usces_have_skus();
	$html .= '<h3>' . usces_the_itemName( 'return' ) . '&nbsp; (' . usces_the_itemCode( 'return' ) . ') </h3>';
	$html .= '<div class="exp clearfix">';
	$html .= $content;
	$item_custom = usces_get_item_custom( $post->ID, 'list', 'return' );
	if($item_custom){
		$html .= '<div class="field">';
		$html .= $item_custom;
		$html .= '</div>';
	}
	$html .= '</div>';

	$html .= '<div class="skuform">';
	$html .= '<table class="skumulti">';
	$html .= '<thead>';
	$html .= '<tr>';
	$html .= '<th rowspan="2" class="thborder">'.__('order number', 'usces').'</th>';
	$html .= '<th colspan="2">'.__('Title', 'usces').'</th>';
	if( usces_the_itemCprice('return') > 0 ){
		$usces_bothprice = '('.__('List price', 'usces').')'.__('selling price', 'usces') . usces_guid_tax('return');
		$html .= '<th colspan="2">'.apply_filters('usces_filter_bothprice_label', $usces_bothprice, __('List price', 'usces'), __('selling price', 'usces'), usces_guid_tax('return')) . '</th>';
	}else{
		$usces_sellingprice = __('selling price', 'usces') . usces_guid_tax('return');
		$html .= '<th colspan="2">'.apply_filters('usces_filter_sellingprice_label', $usces_sellingprice, __('selling price', 'usces'), usces_guid_tax('return')) . '</th>';
	}
	$html .= '</tr>';
	$html .= '<tr>';
	$html .= '<th class="thborder">'.__('stock status', 'usces').'</th>';
	$html .= '<th class="thborder">'.__('Quantity', 'usces').'</th>';
	$html .= '<th class="thborder">'.__('unit', 'usces').'</th>';
	$html .= '<th class="thborder">&nbsp;</th>';
	$html .= '</tr>';
	$html .= '</thead>';
	$html .= '<tbody>';
	do {
		$html .= '<tr>';
		$html .= '<td rowspan="2">' . esc_html(usces_the_itemSku('return')) . '</td>';
		$html .= '<td colspan="2" class="skudisp subborder">' . apply_filters('usces_filter_singleitem_skudisp', esc_html(usces_the_itemSkuDisp('return')));
		if (usces_is_options()) {
			$html .= '<table class="item_option"><caption>' . apply_filters('usces_filter_single_item_options_caption', __('Please appoint an option.', 'usces'), $post) . '</caption>';
			while (usces_have_options()) {
				$opttr = '<tr><th>' . esc_html(usces_getItemOptName()) . '</th><td>' . usces_the_itemOption(usces_getItemOptName(),'','return') . '</td></tr>';
				$html .= apply_filters('usces_filter_singleitem_option', $opttr, usces_getItemOptName(), NULL);
			}
			$html .= '</table>';
		}
		$html .= '</td>';
		$html .= '<td colspan="2" class="subborder price">';
		if( usces_the_itemCprice('return') > 0 ){
			$html .= '<span class="cprice">(' . usces_the_itemCpriceCr('return') . ')</span>';
		}
		$html .= '<span class="price">' . usces_the_itemPriceCr('return') . '</span>';
		$html .= usces_crform_the_itemPriceCr_taxincluded( true, '', '', '', true, false, true, 'return' );
		$html .= '<br />' . usces_the_itemGpExp('return') . '</td>';
		$html .= '</tr>';
		$html .= '<tr>';
		$html .= '<td class="zaiko">' . usces_get_itemZaiko( 'name' ) . '</td>';
		$html .= '<td class="quant">' . usces_the_itemQuant('return') . '</td>';
		$html .= '<td class="unit">' . usces_the_itemSkuUnit('return') . '</td>';
		if( !usces_have_zaiko() ){
			$html .= '<td class="button">' . apply_filters('usces_filters_single_sku_zaiko_message', esc_html(usces_get_itemZaiko( 'name' ))) . '</td>';
		}else{
			$html .= '<td class="button">' . usces_the_itemSkuButton(__('Add to Shopping Cart', 'usces'), 0, 'return') . '</td>';
		}
		$html .= '</tr>';
		$html .= '<tr><td colspan="5" class="error_message">' . usces_singleitem_error_message($post->ID, usces_the_itemSku('return'), 'return') . '</td></tr>';

	} while (usces_have_skus());
	$html .= '</tbody>';
	$html .= '</table>';
	$html .= '</div><!-- end of skuform -->';
	$html .= apply_filters('single_item_multi_sku_after_field', NULL);
}

$html .= '<div class="itemsubimg">';
$imageid = usces_get_itemSubImageNums();
foreach ( $imageid as $id ) {
	$html .= '<a href="' . usces_the_itemImageURL($id, 'return') . '"';
	$html .= apply_filters('usces_itemimg_anchor_rel', '');
	$html .= '>';
	$itemImage = usces_the_itemImage($id, 137, 200, $post, 'return');
	$html .= apply_filters('usces_filter_the_SubImage', $itemImage, $post, $id);
	$html .= '</a>';
}
$html .= '</div><!-- end of itemsubimg -->';

if (usces_get_assistance_id_list($post->ID)) {
	$org_opst = $post;
	$html .= '<div class="assistance_item">';
	$assistanceposts = get_posts('include='.usces_get_assistance_id_list($post->ID));
	if ($assistanceposts) {
		$assistance_item_title = '<h3>' . usces_the_itemCode( 'return' ) . __('An article concerned', 'usces').'</h3>';
		$html .= apply_filters('usces_assistance_item_title', $assistance_item_title);
		$html .= '<ul class="clearfix">';
		foreach ($assistanceposts as $post) {
			setup_postdata($post);
			usces_the_item();
			$html .= '<li><div class="listbox clearfix">';
			$html .= '<div class="slit"><a href="' . get_permalink($post->ID) . '" rel="bookmark" title="' . esc_attr($post->post_title) . '">' . usces_the_itemImage(0, 100, 100, $post, 'return') . '</a></div>';
			$html .= '<div class="detail">';
			$html .= '<h4>' . usces_the_itemName('return') . '</h4>';
			$html .= $post->post_excerpt;
			$html .= '<p>';
			if (usces_is_skus()) {
				$html .= usces_crform( usces_the_firstPrice('return'), true, false, 'return' );
			}
			$html .= '<br />';
			$html .= '&raquo; <a href="' . get_permalink($post->ID) . '" rel="bookmark" title="' . esc_attr($post->post_title) . '">'.__('see the details', 'usces').'</a></p>';
			$html .= '</div>';
			$html .= '</div>';
			$html .= '</li>';
		}
		$html .= '</ul>';
	}

	$html .= '</div><!-- end of assistance_item -->';
	$post = $org_opst;
	setup_postdata($post);
}

$html = apply_filters('usces_filter_single_item_inform', $html);
$html .= '</form>';
$html .= apply_filters('usces_filter_single_item_outform', NULL);

$html .= '</div><!-- end of itemspage -->';
$html = apply_filters('usces_filter_single_item', $html, $post, $content );

?>

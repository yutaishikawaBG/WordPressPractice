<?php
function usces_guid_tax( $out = '' ) {
	global $usces;

	if( $out == 'return' ) {
		return $usces->getGuidTax();
	} else {
		echo $usces->getGuidTax();
	}
}

function usces_tax_label( $data = array(), $out = '' ) {
	global $usces;

	if( empty( $data ) || !array_key_exists( 'order_condition', $data ) ) {
		$condition = $usces->get_condition();
		$tax_mode = $usces->options['tax_mode'];
	} else {
		$condition = maybe_unserialize( $data['order_condition'] );
		$tax_mode = ( isset( $condition['tax_mode'] ) ) ? $condition['tax_mode'] : $usces->options['tax_mode'];
	}
	if( 'exclude' == $tax_mode ) {
		$label = __( 'consumption tax', 'usces' );
	} else {
		if( isset( $condition['tax_mode'] ) && !empty( $data['ID'] ) ) {
			$materials = array(
				'total_items_price' => $data['order_item_total_price'],
				'discount' => $data['order_discount'],
				'shipping_charge' => $data['order_shipping_charge'],
				'cod_fee' => $data['order_cod_fee'],
				'use_point' => $data['order_usedpoint'],
				'carts' => usces_get_ordercartdata( $data['ID'] ),
				'condition' => $condition,
				'order_id' => $data['ID'],
			);
			$label = __( 'Internal tax', 'usces' ) . '(' . usces_crform( usces_internal_tax( $materials, 'return' ), true, false, 'return' ) . ')';
		} else {
			$label = __( 'Internal tax', 'usces' );
		}
	}
	$label = apply_filters( 'usces_filter_tax_label', $label );

	if( $out == 'return' ) {
		return $label;
	} else {
		echo $label;
	}
}

/**
 * @param array $data when the 'order' key in $data exist, entry data, unless order data.
 */
function usces_tax( $data, $out = '' ) {
	global $usces;

	if( !usces_is_tax_display() ) {
		$tax_str = '';
	} else {
		if( 'exclude' == $usces->options['tax_mode'] ){
			if( array_key_exists( 'order', $data ) && array_key_exists( 'tax', $data['order'] ) ) {// from Entry
				$tax = $data['order']['tax'];
			} elseif( array_key_exists( 'order_tax', $data ) ) {//from Order Data
				$tax = $data['order_tax'];
			} elseif( array_key_exists( 'tax', $data ) ) {
				$tax = $data['tax'];
			} else {
				$tax = 0;
			}
			$tax_str = usces_crform( $tax, true, false, 'return' );
		} else {
			if( array_key_exists( 'order', $data ) ) {// from Entry
				$materials = array(
					'total_items_price' => $data['order']['total_items_price'],
					'discount' => ( isset( $data['order']['discount'] ) ) ? $data['order']['discount'] : 0,
					'shipping_charge' => ( isset( $data['order']['shipping_charge'] ) ) ? $data['order']['shipping_charge'] : 0,
					'cod_fee' => ( isset( $data['order']['cod_fee'] ) ) ? $data['order']['cod_fee'] : 0,
					'use_point' => ( isset( $data['order']['use_point'] ) ) ? $data['order']['use_point'] : 0,
				);
				$tax_str = '(' . usces_crform( usces_internal_tax( $materials, 'return' ), true, false, 'return' ) . ')';
			} elseif( array_key_exists( 'order_tax', $data ) ) {//from Order Data
				$materials = array(
					'total_items_price' => $data['order_item_total_price'],
					'discount' => $data['order_discount'],
					'shipping_charge' => $data['order_shipping_charge'],
					'cod_fee' => $data['order_cod_fee'],
					'use_point' => $data['order_usedpoint'],
					'carts' => usces_get_ordercartdata( $data['ID'] ),
					'condition' => unserialize( $data['order_condition'] ),
				);
				$tax_str = '(' . usces_crform( usces_internal_tax( $materials, 'return' ), true, false, 'return' ) . ')';
			} elseif( array_key_exists( 'tax', $data ) ) {
				$item_total_price = $usces->get_total_price( $data['cart'] );
				$materials = array(
					'total_items_price' => $item_total_price,
					'discount' => ( isset( $data['discount'] ) ) ? $data['discount'] : 0,
					'shipping_charge' => ( isset( $data['shipping_charge'] ) ) ? $data['shipping_charge'] : 0,
					'cod_fee' => ( isset( $data['cod_fee'] ) ) ? $data['cod_fee'] : 0,
					'use_point' => ( isset( $data['usedpoint'] ) ) ? $data['usedpoint'] : 0,
					'carts' => $data['cart'],
				);
				$tax_str = '(' . usces_crform( usces_internal_tax( $materials, 'return' ), true, false, 'return' ) . ')';
			} else {
				$materials = array();
				$tax_str = '(' . usces_crform( usces_internal_tax( $materials, 'return' ), true, false, 'return' ) . ')';
			}
		}
		$tax_str = apply_filters( 'usces_filter_tax', $tax_str );
	}

	if( $out == 'return' ) {
		return $tax_str;
	} else {
		echo $tax_str;
	}
}

function usces_order_history_tax( $data, $tax_mode ) {
	global $usces;

	if ( 'exclude' == $tax_mode ) {
		$tax_str = usces_crform( $data['tax'], true, false, 'return' );
	} else {
		$materials = array(
			'total_items_price' => $data['total_items_price'],
			'discount' => $data['discount'],
			'shipping_charge' => $data['shipping_charge'],
			'cod_fee' => $data['cod_fee'],
			'use_point' => $data['usedpoint'],
			'carts' => $data['cart'],
			'condition' => $data['condition'],
		);
		$tax_str = '(' . usces_crform( usces_internal_tax( $materials, 'return' ), true, false, 'return' ) . ')';
	}
	$tax_str = apply_filters( 'usces_filter_order_history_tax', $tax_str, $data, $tax_mode );
	return $tax_str;
}

function usces_internal_tax( $materials, $out = '' ) {
	global $usces;

	if( !usces_is_tax_display() ) {
		$tax = 0;
	} else {
		if( !empty( $materials['condition'] ) ) {
			$condition = $materials['condition'];
		} else {
			$condition = $usces->get_condition();
		}
		$reduced_taxrate = ( isset( $condition['applicable_taxrate'] ) && 'reduced' == $condition['applicable_taxrate'] ) ? true : false;
		if( $reduced_taxrate ) {
			$usces_tax = Welcart_Tax::get_instance();
			$usces_tax->get_order_tax( $materials );
			$tax = apply_filters( 'usces_filter_internal_tax', $usces_tax->tax, $materials );
		} else {
			if( 1 == usces_point_coverage() ) {
				if( 'products' == $condition['tax_target'] ) {
					$total = $materials['total_items_price'] + $materials['discount'];
				} else {
					$total = $materials['total_items_price'] + $materials['discount'] + $materials['shipping_charge'] + $materials['cod_fee'];
				}
			} else {
				if( 'products' == $condition['tax_target'] ) {
					$total = $materials['total_items_price'] + $materials['discount'];
				} else {
					$use_point = ( empty( $materials['use_point'] ) ) ? 0 : $materials['use_point'];
					$total = $materials['total_items_price'] + $materials['discount'] - $use_point + $materials['shipping_charge'] + $materials['cod_fee'];
				}
			}
			$total = apply_filters( 'usces_filter_internal_tax_total', $total, $materials );
			$tax_rate = (float)$condition['tax_rate'];
			$tax = $total * $tax_rate / 100;
			$tax = $total - $total / ( 1 + ( $tax_rate / 100 ) );
			$tax = usces_tax_rounding_off( $tax, $condition['tax_method'] );
			$tax = apply_filters( 'usces_filter_internal_tax', $tax, $materials );
		}
	}

	if( $out == 'return' ) {
		return $tax;
	} else {
		echo $tax;
	}
}

function usces_currency_symbol( $out = '' ) {
	global $usces;

	if( $out == 'return' ){
		return $usces->getCurrencySymbol();
	}else{
		echo esc_html($usces->getCurrencySymbol());
	}
}

function usces_is_error_message() {
	global $usces;
	if ( $usces->error_message != '' )
		return true;
	else
		return false;
}

function usces_is_item( $post_id = null ) {
	if( null === $post_id ){
		global $post;
		$post_id = $post->ID;
	}
	$product = wel_get_product( $post_id );
	if ( isset( $product['_pst'] ) && 'item' === $product['_pst']->post_mime_type ) {
		return true;
	} else {
		return false;
	}
}

function usces_the_itemCode( $out = '' ) {
	global $post;

	$product = wel_get_product( $post->ID );
	$str     = $product['itemCode'];
	if ( $out === 'return' ) {
		return $str;
	} else {
		echo esc_html( $str );
	}
}

function usces_the_itemName( $out = '', $post = null ) {
	if ( $post == null ){
		global $post;
	}

	$product = wel_get_product( $post );
	$str     = $product['itemName'];
		
	if ( $out === 'return' ) {
		return $str;
	} else {
		echo esc_html( $str );
	}
}

function usces_the_point_rate( $out = '' ){
	global $post;

	$product = wel_get_product( $post );
	$str     = $product['itemPointrate'];
	$rate    = (int) $str;
	
	if ( $out === 'return' ) {
		return $rate;
	} else {
		echo esc_html( $rate );
	}
}

function usces_the_shipment_aim( $out = '' ){
	global $post;

	$product = wel_get_product( $post );
	$str     = $product['itemShipping'];
	$no      = (int) $str;
	if ( 0 === $no ) {
		return '';
	}
	$rules = get_option( 'usces_shipping_rule' );
	
	if ( $out === 'return' ) {
		return $rules[ $no ];
	} else {
		echo esc_html( $rules[ $no ] );
	}
}

function usces_the_item(){
	global $usces, $post;
	
	$usces->itemskus = wel_get_skus( $post );
	$usces->current_itemsku = -1;
	$usces->itemopts = wel_get_opts( $post );
	$usces->current_itemopt = -1;
	
	return;
}

function usces_get_itemMeta( $metakey, $post_id, $out = '' ) {

	$product      = wel_get_product( $post_id );
	$reserved_key = ltrim( $metakey, '_' );

	if ( array_key_exists( $reserved_key, $product ) ) {
		$meta = $product[ $reserved_key ];
	} else {
		$meta = $product[ $metakey ];
	}
	if ( is_array( $meta ) ) {
		$value = $meta[0];
	} else {
		$value = $meta;
	}

	if ( $out === 'return' ) {
		return $value;
	} else {
		echo esc_html( $value );
	}
}

function usces_sku_num() {
	global $usces;
	$sku_num = ( !empty( $usces->itemskus ) && is_array( $usces->itemskus ) ) ? count( $usces->itemskus ) : 0;
	return $sku_num;
}

function usces_is_skus() {
	global $usces;
	
	if( !empty( $usces->itemskus ) && is_array( $usces->itemskus ) && 0 < count( $usces->itemskus ) ){
		$usces->current_itemsku = -1;
		reset($usces->itemskus);
		$usces->itemsku = array();
		return true;
	}else{
		return false;
	}
}

function usces_reset_skus() {
	global $usces;

	$usces->current_itemsku = -1;
	reset($usces->itemskus);
}

function usces_have_skus() {
	global $usces;

	if( null === $usces->current_itemsku ) {
	    $usces->current_itemsku = -1;
    }

	if ( $usces->current_itemsku + 1 < usces_sku_num() ) {
		$usces->current_itemsku++;
		$usces->itemsku = $usces->itemskus[ $usces->current_itemsku ];

		return true;
	} else {
		return false;
	}
}

function usces_the_itemSku($out = '') {
	global $usces;

	if($out == 'return'){
		return $usces->itemsku['code'];
	}else{
		echo esc_attr($usces->itemsku['code']);
	}
}

function usces_the_itemPrice($out = '') {
	global $usces;
	if($out == 'return'){
		return $usces->itemsku['price'];
	}else{
		echo number_format($usces->itemsku['price']);
	}
}

function usces_the_itemCprice($out = '') {
	global $usces;
	if($out == 'return'){
		return $usces->itemsku['cprice'];
	}else{
		echo number_format($usces->itemsku['cprice']);
	}
}

function usces_the_itemPriceCr($out = '') {
	global $usces;
	$res = $usces->get_currency($usces->itemsku['price'], true, false );
	$res = apply_filters( 'usces_filter_the_item_price_cr', $res, $usces->itemsku['price'], $out );
	if($out == 'return'){
		return $res;
	}else{
		echo esc_html($res);
	}
}

function usces_the_itemCpriceCr($out = '') {
	global $usces;
	$res = $usces->get_currency($usces->itemsku['cprice'], true, false );
	$res = apply_filters( 'usces_filter_the_item_cprice_cr', $res, $usces->itemsku['cprice'], $out );

	if($out == 'return'){
		return $res;
	}else{
		echo esc_html($res);
	}
}

function usces_crcode( $out = '' ) {
	global $usces;
	$res = esc_html($usces->get_currency_code());
	
	if($out == 'return'){
		return $res;
	}else{
		echo __($res, 'usces');
	}
}

function usces_crsymbol( $out = '', $js = NULL ) {
	global $usces;
	$res = $usces->getCurrencySymbol();
	if( 'js' === $js && '&yen;' == $res ){
		$res = mb_convert_encoding($res, 'UTF-8', 'HTML-ENTITIES');
	}
	
	if($out == 'return'){
		return $res;
	}else{
		echo esc_html($res);
	}
}

function usces_the_itemZaiko( $out = '' ) {
	global $usces, $post;
	$itemOrderAcceptable = $usces->getItemOrderAcceptable( $post->ID );
	$num = (int)$usces->itemsku['stock'];
	$stocknum = $usces->itemsku['stocknum'];
	
	if( $itemOrderAcceptable != 1 || WCUtils::is_blank($stocknum) ) {
		if( 1 < $num || ( 0 === (int)$usces->itemsku['stocknum'] && !WCUtils::is_blank($usces->itemsku['stocknum']) ) ){
			$res = $usces->zaiko_status[$num];
		}elseif( 1 >= $num && ( 0 === (int)$usces->itemsku['stocknum'] && !WCUtils::is_blank($usces->itemsku['stocknum']) ) ){
			$res = $usces->zaiko_status[2];
		}else{
			$res = $usces->zaiko_status[$num];
		}
	} else {
		if( 1 < $num ){
			$res = $usces->zaiko_status[$num];
		}elseif( 1 >= $num && 0 >= (int)$stocknum ){
			$res = ( !empty($usces->options['order_acceptable_label']) ) ? $usces->options['order_acceptable_label'] : __('Order acceptable', 'usces');
		}else{
			$res = $usces->zaiko_status[$num];
		}
	}
	
	if( $out == 'return' ){
		return $res;
	}else{
		echo esc_html($res);
	}
}

function usces_the_itemZaikoStatus( $out = '' ) {
	global $usces;
	
	if( $out == 'return' ){
		return usces_get_itemZaiko( 'name' );
	}else{
		echo esc_html(usces_get_itemZaiko( 'name' ));
	}
}

function usces_get_itemZaiko( $field = 'name', $post_id=NULL, $sku=NULL ) {
	global $usces;

	if( $post_id == NULL ) {
		global $post;
		$post_id = $post->ID;
	}

	if( empty($sku) && ! empty( $usces->itemsku ) ) {
		$num = (int)$usces->itemsku['stock'];
		$stocknum = $usces->itemsku['stocknum'];
	}else{
		$skus = wel_get_skus( $post_id, 'code' );
		$num = (int)$skus[$sku]['stock'];
		$stocknum = $skus[$sku]['stocknum'];
	}
	
	if( 'id' == $field ){
		$res = $num;
	}else{
		$itemOrderAcceptable = $usces->getItemOrderAcceptable( $post_id );
		if( $itemOrderAcceptable != 1 || WCUtils::is_blank($stocknum) ) {
			$res = $usces->zaiko_status[$num];
		} else {
			if( 2 > $num && 0 >= (int)$stocknum ) {
				$res = ( !empty($usces->options['order_acceptable_label']) ) ? $usces->options['order_acceptable_label'] : __('Order acceptable', 'usces');
			} else {
				$res = $usces->zaiko_status[$num];
			}
		}
	}
	return $res;
}

function usces_the_itemZaikoNum( $out = '' ) {
	global $usces;
	$num = $usces->itemsku['stocknum'];
	
	if( $out == 'return' ){
		return $num;
	}else{
		echo number_format($num);
	}
}

function usces_the_itemSkuDisp( $out = '' ) {
	global $usces;
	
	if( $out == 'return' ){
		return $usces->itemsku['name'];
	}else{
		echo esc_html($usces->itemsku['name']);
	}
}

function usces_the_itemSkuUnit( $out = '' ) {
	global $usces;
	
	if( $out == 'return' ){
		return $usces->itemsku['unit'];
	}else{
		echo esc_html($usces->itemsku['unit']);
	}
}

function usces_the_firstSku( $out = '' ) {
	global $post, $usces;
	$post_id = $post->ID;

	$skus = wel_get_skus( $post_id );

	if ( $out === 'return' ) {
		return $skus[0]['code'];
	} else {
		echo esc_html( $skus[0]['code'] );
	}
}

function usces_the_firstPrice( $out = '', $post = null ) {
	global $usces;

	if ( $post === null ){
		global $post;
	}
	$post_id = $post->ID;
	$skus    = wel_get_skus( $post_id );

	$price = apply_filters( 'usces_filter_the_first_price', $skus[0]['price'], $post_id, $skus, $out );
	if ( $out === 'return' ) {
		return $price;
	} else {
		echo number_format( $price );
	}
}

function usces_the_firstCprice( $out = '', $post = null ) {
	global $usces;

	if ( $post === null ){
		global $post;
	}
	$post_id = $post->ID;
	$skus    = wel_get_skus( $post_id );
	
	if ( $out === 'return' ) {
		return $skus[0]['cprice'];
	} else {
		echo number_format( $skus[0]['cprice'] );
	}
}

function usces_the_firstPriceCr( $out = '', $post = null ) {
	global $usces;

	if ( $post === null ){
		global $post;
	}
	$post_id = $post->ID;
	$skus    = wel_get_skus( $post_id );
	$res     = $usces->get_currency( $skus[0]['price'], true, false );

	$price = apply_filters( 'usces_filter_the_first_price_cr', $res, $skus[0]['price'], $post_id, $skus, $out );

	if ( $out === 'return' ) {
		return $price;
	} else {
		echo esc_html( $price );
	}
}

function usces_the_firstCpriceCr( $out = '', $post = null ) {
	global $usces;

	if ( $post === null ){
		global $post;
	}
	$post_id = $post->ID;
	$skus    = wel_get_skus( $post_id );
	$res     = $usces->get_currency( $skus[0]['cprice'], true, false );

	$cprice = apply_filters( 'usces_filter_the_first_cprice_cr', $res, $skus[0]['cprice'], $post_id, $skus, $out );

	if ( $out === 'return' ) {
		return $cprice;
	} else {
		echo esc_html( $cprice );
	}
}

function usces_the_firstZaiko( $out = '', $post = null ) {
	global $usces;

	if ( $post === null ){
		global $post;
	}
	$post_id = $post->ID;
	$skus    = wel_get_skus( $post_id );

	if ( $out === 'return' ) {
		return $skus[0]['stock'];
	} else {
		echo esc_html( $skus[0]['stock'] );
	}
}

function usces_the_lastSku( $out = '', $post = null ) {
	global $usces;

	if ( $post === null ){
		global $post;
	}
	$post_id = $post->ID;
	$skus    = wel_get_skus( $post_id );
	$sku     = end( $skus );

	if ( $out === 'return' ) {
		return $sku['code'];
	} else {
		echo esc_html( $sku['code'] );
	}
}

function usces_the_lastPrice( $out = '', $post = null ) {
	global $usces;

	if ( $post === null ){
		global $post;
	}
	$post_id = $post->ID;
	$skus    = wel_get_skus( $post_id );
	$sku     = end( $skus );

	if ( $out === 'return' ) {
		return $sku['price'];
	} else {
		echo number_format( $sku['price'] );
	}
}

function usces_the_lastZaiko( $out = '', $post = null ) {
	global $usces;

	if ( $post === null ){
		global $post;
	}
	$post_id = $post->ID;
	$skus    = wel_get_skus( $post_id );
	$sku     = end( $skus );

	if ( $out === 'return' ) {
		return $sku['stock'];
	} else {
		echo esc_html( $sku['stock'] );
	}
}

function usces_have_zaiko(){
	global $post, $usces;
	return $usces->is_item_zaiko( $post->ID, $usces->itemsku['code'] );
}

function usces_have_zaiko_anyone( $post_id = null ){
	global $post, $usces;

	if ( null === $post_id ) {
		$post_id = $post->ID;
	}

	$itemOrderAcceptable = $usces->getItemOrderAcceptable( $post_id );

	$skus   = wel_get_skus( $post_id );
	$status = false;

	foreach ( $skus as $sku ) {
		if ( $usces->is_item_zaiko( $post_id, $sku['code'] ) ) {
			$status = true;
			break;
		}
	}
	return apply_filters( 'usces_have_zaiko_anyone', $status, $post_id, $skus );
}

function usces_have_fewstock( $post_id = null ){
	global $post, $usces;

	if ( null === $post_id ) {
		$post_id = $post->ID;
	}

	$skus = wel_get_skus( $post_id );
	$res  = false;
	foreach ( $skus as $sku ) {
		if ( 1 === (int) $sku['stock'] ) {
			$res = true;
			break;
		}
	}
	return $res;
}

function usces_is_gptekiyo( $post_id, $sku, $quant ){
	global $usces;
	return $usces->is_gptekiyo( $post_id, $sku, $quant );
}

function usces_the_itemGpExp( $out = '' ) {
	global $post, $usces;
	$post_id = $post->ID;
	$sku = $usces->itemsku['code'];
	$GpN1 = $usces->getItemGpNum1($post_id);
	$GpN2 = $usces->getItemGpNum2($post_id);
	$GpN3 = $usces->getItemGpNum3($post_id);
	$GpD1 = $usces->getItemGpDis1($post_id);
	$GpD2 = $usces->getItemGpDis2($post_id);
	$GpD3 = $usces->getItemGpDis3($post_id);
	$unit = $usces->getItemSkuUnit($post_id, $sku);
	$price = $usces->getItemPrice($post_id, $sku);
	if ( ( isset( $usces->options['tax_display'] ) && 'deactivate' == $usces->options['tax_display'] ) || ( isset( $usces->options['tax_mode'] ) && 'include' == $usces->options['tax_mode'] ) ) {
		$tax_rate = 0;
	} else {
		$usces_tax = Welcart_Tax::get_instance();
		$tax_rate = $usces_tax->get_sku_tax_rate( $post_id, $sku );
	}

	if( ($usces->itemsku['gp'] == 0) || empty($GpN1) || empty($GpD1) ){
		return;
	}
	$html = "<dl class='itemGpExp'>\n<dt>" . apply_filters( 'usces_filter_itemGpExp_title', __('Business package discount','usces')) . "</dt>\n<dd>\n<ul>\n";
	if(!empty($GpN1) && !empty($GpD1)) {
		if(empty($GpN2) || empty($GpD2)) {
			$price1 = round( $price * ( 100 - $GpD1 ) / 100 );
			$html .= "<li>";
			$html .= sprintf( __('<span class=%5$s>%1$s</span>%2$s par 1%3$s for more than %4$s%3$s', 'usces'),
						$usces->get_currency( $price1, true, false ), 
						$usces->getGuidTax(),
						esc_html($unit),
						$GpN1, 
						"'price'"
					);
			if ( 0 < $tax_rate ) {
				$html .= usces_crform_the_itemGpExp_taxincluded( $price1, $tax_rate );
			}
			$html .= "</li>\n";
		} else {

			$price1 = round( $price * ( 100 - $GpD1 ) / 100 );
			$html .= "<li>";
			$html .= sprintf( __('<span class=%6$s>%1$s</span>%2$s par 1%3$s for %4$s-%5$s%3$s', 'usces'),
						$usces->get_currency( $price1, true, false ), 
						$usces->getGuidTax(),
						esc_html($unit),
						$GpN1, 
						$GpN2-1, 
						"'price'"
					);
			if ( 0 < $tax_rate ) {
				$html .= usces_crform_the_itemGpExp_taxincluded( $price1, $tax_rate );
			}
			$html .= "</li>\n";
			if(empty($GpN3) || empty($GpD3)) {
				$price2 = round( $price * ( 100 - $GpD2 ) / 100 );
				$html .= "<li>";
				$html .= sprintf( __('<span class=%5$s>%1$s</span>%2$s par 1%3$s for more than %4$s%3$s', 'usces'),
							$usces->get_currency( $price2, true, false ), 
							$usces->getGuidTax(),
							esc_html($unit),
							$GpN2, 
							"'price'"
						);
				if ( 0 < $tax_rate ) {
					$html .= usces_crform_the_itemGpExp_taxincluded( $price2, $tax_rate );
				}
				$html .= "</li>\n";
			} else {
				$price2 = round( $price * ( 100 - $GpD2 ) / 100 );
				$html .= "<li>";
				$html .= sprintf( __('<span class=%6$s>%1$s</span>%2$s par 1%3$s for %4$s-%5$s%3$s', 'usces'),
							$usces->get_currency( $price2, true, false ), 
							$usces->getGuidTax(),
							esc_html($unit),
							$GpN2, 
							$GpN3-1, 
							"'price'"
						);
				if ( 0 < $tax_rate ) {
					$html .= usces_crform_the_itemGpExp_taxincluded( $price2, $tax_rate );
				}
				$html .= "</li>\n";
				$price3 = round( $price * ( 100 - $GpD3 ) / 100 );
				$html .= "<li>";
				$html .= sprintf( __('<span class=%5$s>%1$s</span>%2$s par 1%3$s for more than %4$s%3$s', 'usces'),
							$usces->get_currency( $price3, true, false ), 
							$usces->getGuidTax(),
							esc_html($unit),
							$GpN3, 
							"'price'"
						);
				if ( 0 < $tax_rate ) {
					$html .= usces_crform_the_itemGpExp_taxincluded( $price3, $tax_rate );
				}
				$html .= "</li>\n";
			}
		}
	}
	$html .= "</ul></dd></dl>";
	
	$html = apply_filters('usces_filter_itemGpExp', $html);
		
	if( $out == 'return' ){
		return $html;
	}else{
		echo $html;
	}
}

function usces_crform_the_itemGpExp_taxincluded( $price, $tax_rate, $label_pre = true, $label = '', $start_tag = '', $end_tag = '', $symbol_pre = true, $symbol_post = false, $seperator_flag = true ) {
	global $usces;

	if ( ( isset( $usces->options['tax_display'] ) && 'deactivate' == $usces->options['tax_display'] ) || ( isset( $usces->options['tax_mode'] ) && 'include' == $usces->options['tax_mode'] ) ) {
		$res = '';
	} else {
		$tax = (float) sprintf( '%.3f', (float) $price * (float) $tax_rate / 100 );
		$tax = usces_tax_rounding_off( $tax );
		$price_gpexp = esc_html( $usces->get_currency( $price + $tax, $symbol_pre, $symbol_post, $seperator_flag ) );
		if ( empty( $label ) ) {
			$label_tag = '<em class="tax tax_inc_label">' . __( 'tax-included', 'usces' ) . '</em>';
		} else {
			$label_tag = '<em class="tax tax_inc_label">' . $label . '</em>';
		}
		if ( empty( $start_tag ) ) {
			$start_tag = '<span class="tax_inc_block">(';
		}
		if ( $label_pre ) {
			$start_tag = $start_tag . $label_tag;
		}
		if ( empty( $end_tag ) ) {
			$end_tag = ')</span>';
		}
		if ( true !== $label_pre ) {
			$end_tag = $label_tag . $end_tag;
		}
		$res = apply_filters( 'usces_filter_crform_the_itemGpExp_taxincluded', $start_tag . $price_gpexp . $end_tag, $price, $tax_rate, $label_pre, $label, $symbol_pre, $symbol_post, $seperator_flag );
	}
	return $res;
}

function usces_the_itemQuant( $out = '' ) {
	global $usces, $post;
	$post_id = $post->ID;
	$sku = urlencode($usces->itemsku['code']);
	$value = isset( $_SESSION['usces_singleitem']['quant'][$post_id][$sku] ) ? $_SESSION['usces_singleitem']['quant'][$post_id][$sku] : 1;
	$quant = "<input name=\"quant[{$post_id}][" . $sku . "]\" type=\"text\" id=\"quant[{$post_id}][" . $sku . "]\" class=\"skuquantity\" value=\"" . esc_attr($value) . "\" onKeyDown=\"if (event.keyCode == 13) {return false;}\" />";
	$html = apply_filters('usces_filter_the_itemQuant', $quant, $post);
		
	if( $out == 'return' ){
		return $html;
	}else{
		echo $html;
	}
}

function usces_the_itemSkuButton($value, $type=0, $out = '') {
	global $usces, $post;
	$post_id = (int)$post->ID;
	$zaikonum = esc_attr($usces->itemsku['stocknum']);
	$zaiko_status = esc_attr($usces->itemsku['stock']);
	$gptekiyo = esc_attr($usces->itemsku['gp']);
	$skuPrice = esc_attr($usces->getItemPrice($post_id, $usces->itemsku['code']));
	$value = esc_attr(apply_filters( 'usces_filter_incart_button_label', $value));
	$sku = esc_attr(urlencode($usces->itemsku['code']));
	
	if($type == 1)
		$type = 'button';
	else
		$type = 'submit';
		
	$html = "<input name=\"zaikonum[{$post_id}][{$sku}]\" type=\"hidden\" id=\"zaikonum[{$post_id}][{$sku}]\" value=\"{$zaikonum}\" />\n";
	$html .= "<input name=\"zaiko[{$post_id}][{$sku}]\" type=\"hidden\" id=\"zaiko[{$post_id}][{$sku}]\" value=\"{$zaiko_status}\" />\n";
	$html .= "<input name=\"gptekiyo[{$post_id}][{$sku}]\" type=\"hidden\" id=\"gptekiyo[{$post_id}][{$sku}]\" value=\"{$gptekiyo}\" />\n";
	$html .= "<input name=\"skuPrice[{$post_id}][{$sku}]\" type=\"hidden\" id=\"skuPrice[{$post_id}][{$sku}]\" value=\"{$skuPrice}\" />\n";
	if( $usces->use_js ){
		$html .= "<input name=\"inCart[{$post_id}][{$sku}]\" type=\"{$type}\" id=\"inCart[{$post_id}][{$sku}]\" class=\"skubutton\" value=\"{$value}\" onclick=\"return uscesCart.intoCart('{$post_id}','{$sku}')\" />";
	}else{
		$html .= "<a name=\"cart_button\"></a><input name=\"inCart[{$post_id}][{$sku}]\" type=\"{$type}\" id=\"inCart[{$post_id}][{$sku}]\" class=\"skubutton\" value=\"{$value}\" />";
	}
	$html .= "<input name=\"usces_referer\" type=\"hidden\" value=\"" . esc_url($_SERVER['REQUEST_URI']) . "\" />\n";
	$html = apply_filters( 'usces_filter_item_sku_button', $html, $value, $type );
	
	if( $out == 'return' ){
		return $html;
	}else{
		echo $html;
	}
}
				
function usces_direct_intoCart( $post_id, $sku, $force = false, $value = null, $options = null, $out = '' ) {
	global $usces;
	if ( empty( $value ) ) {
		$value = __('Add To Cart', 'usces');
	}
	$skus = wel_get_skus( $post_id, 'code' );

	$zaikonum = $skus[$sku]['stocknum'];
	$zaiko    = $skus[$sku]['stock'];
	$gptekiyo = $skus[$sku]['gp'];
	$skuPrice = $skus[$sku]['price'];
	$enc_sku  = urlencode( $sku );

	$usces->itemopts        = wel_get_opts( $post_id, 'sort' );
	$usces->current_itemopt = -1;

	$usces->itemsku = $skus[ $sku ];

	$html = "<form action=\"" . USCES_CART_URL . "\" method=\"post\" name=\"" . $post_id."-". $enc_sku . "\">\n";
	$html .= "<input name=\"zaikonum[{$post_id}][{$enc_sku}]\" type=\"hidden\" id=\"zaikonum[{$post_id}][{$enc_sku}]\" value=\"{$zaikonum}\" />\n";
	$html .= "<input name=\"zaiko[{$post_id}][{$enc_sku}]\" type=\"hidden\" id=\"zaiko[{$post_id}][{$enc_sku}]\" value=\"{$zaiko}\" />\n";
	$html .= "<input name=\"gptekiyo[{$post_id}][{$enc_sku}]\" type=\"hidden\" id=\"gptekiyo[{$post_id}][{$enc_sku}]\" value=\"{$gptekiyo}\" />\n";
	$html .= "<input name=\"skuPrice[{$post_id}][{$enc_sku}]\" type=\"hidden\" id=\"skuPrice[{$post_id}][{$enc_sku}]\" value=\"{$skuPrice}\" />\n";

	if ( true === $options && usces_is_options() ) {
		while ( usces_have_options() ) {
			$html .= '<div class="itemopt_row">' . usces_the_itemOption( usces_getItemOptName(), '#default#', 'return') . "</div>\n";
		}
	}

	$html .= "<a name=\"cart_button\"></a><input name=\"inCart[{$post_id}][{$enc_sku}]\" type=\"submit\" id=\"inCart[{$post_id}][{$enc_sku}]\" class=\"skubutton\" value=\"{$value}\" " . apply_filters('usces_filter_direct_intocart_button', NULL, $post_id, $sku, $force, $options) . " />";
	$html .= "<input name=\"usces_referer\" type=\"hidden\" value=\"" . esc_url($_SERVER['REQUEST_URI']) . "\" />\n";
	if ( $force ) {
		$html .= "<input name=\"usces_force\" type=\"hidden\" value=\"incart\" />\n";
	}
	$html = apply_filters( 'usces_filter_single_item_inform', $html );

	$html .= "</form>";
	$html .= '<div class="direct_error_message">' . usces_singleitem_error_message( $post_id, $sku, 'return' ) . '</div>'."\n";

	if ( $out === 'return' ) {
		return $html;
	} else {
		echo $html;
	}
}


function usces_the_itemImage( $number = 0, $width = 60, $height = 60, $post = '', $out = '', $media = 'item' ) {
	global $usces;

	if ( $post == '' ) {
		global $post;
	}
	$post_id = $post->ID;

	$ptitle = $number;
	if ( $ptitle && 0 === (int) $number ) {

		$picposts = query_posts( array( 'post_type' => 'attachment', 'name' => $ptitle ) );
		$pictid   = empty( $picposts ) ? 0 : $picposts[0]->ID;
		$html     = wp_get_attachment_image( $pictid, array( $width, $height ), false );
		if ( 'item' === $media ) {
			$alt   = 'alt="' . esc_attr( $code[0] ) . '"';
			$alt   = apply_filters( 'usces_filter_img_alt', $alt, $post_id, $pictid, $width, $height );

			$html = preg_replace( '/alt=\"[^\"]*\"/', $alt, $html );

			$title = 'title="' . esc_attr( $name[0] ).'"';
			$title = apply_filters( 'usces_filter_img_title', $title, $post_id, $pictid, $width, $height );

			$html = preg_replace( '/title=\"[^\"]+\"/', $title, $html );
			$html = apply_filters( 'usces_filter_main_img', $html, $post_id, $pictid, $width, $height );
		}

	}else{

		$product = wel_get_product( $post_id );
		$code =  $product['itemCode'];
		if ( ! $code ) {
			return false;
		}
		$name = $product['itemName'];

		if ( 0 === $number ) {

			$pictid = (int) $usces->get_mainpictid( $code );
			$html   = wp_get_attachment_image( $pictid, array( $width, $height ), true );/* '<img src="#" height="60" width="60" alt="" />'; */
			if ( 'item' === $media ) {
				$alt = 'alt="' . esc_attr( $code ) . '"';
				$alt = apply_filters( 'usces_filter_img_alt', $alt, $post_id, $pictid, $width, $height );

				$html = preg_replace( '/alt=\"[^\"]*\"/', $alt, $html );

				$title = 'title="' . esc_attr( $name ) . '"';
				$title = apply_filters( 'usces_filter_img_title', $title, $post_id, $pictid, $width, $height );

				$html = preg_replace( '/title=\"[^\"]+\"/', $title, $html );
				$html = apply_filters( 'usces_filter_main_img', $html, $post_id, $pictid, $width, $height );
			}

		}else{

			$pictids = $usces->get_pictids( $code );
			$ind     = $number - 1;
			$pictid = ( isset( $pictids[ $ind ] ) && (int) $pictids[ $ind ] ) ? $pictids[ $ind ] : 0;
			$html   = wp_get_attachment_image( $pictid, array( $width, $height ), false );/* '<img src="#" height="60" width="60" alt="" />'; */
			if ( 'item' === $media ) {
				$alt = 'alt="' . esc_attr( $code ) . '"';
				$alt = apply_filters( 'usces_filter_img_alt', $alt, $post_id, $pictid, $width, $height );

				$html = preg_replace( '/alt=\"[^\"]*\"/', $alt, $html );

				$title = 'title="' . esc_attr( $name ) . '"';
				$title = apply_filters( 'usces_filter_img_title', $title, $post_id, $pictid, $width, $height );

				$html = preg_replace( '/title=\"[^\"]+\"/', $title, $html );
				$html = apply_filters( 'usces_filter_sub_img', $html, $post_id, $pictid, $width, $height );
			}
		}
	}

	if ( $out === 'return' ) {
		return $html;
	} else {
		echo $html;
	}
}

function usces_the_itemImageURL( $number = 0, $out = '', $post = '' ) {
	global $usces;

	$ptitle = $number;
	if ( $ptitle && is_string( $number ) ) {

		$picposts = query_posts( array( 'post_type' => 'attachment', 'name' => $ptitle ) );
		if ( ! $picposts ) {
			return '';
		}
		$pictid = empty( $picposts ) ? 0 : $picposts[0]->ID;
		$pictid = $picposts[0]->ID;
		$html   = wp_get_attachment_url( $pictid );

	} else {

		if ( $post == '' ) {
			global $post;
		}
		$post_id = $post->ID;
		$product = wel_get_product( $post_id );

		$code =  $product['itemCode'];
		if ( ! $code ) {
			return false;
		}
		$name = $product['itemName'];
		if ( 0 == $number ) {
			$pictid = (int) $usces->get_mainpictid( $code );
			$html   = wp_get_attachment_url( $pictid );
		} else {
			$pictids = $usces->get_pictids( $code );
			$ind     = $number - 1;
			$pictid  = ( isset( $pictids[ $ind ] ) && (int) $pictids[ $ind ] ) ? $pictids[ $ind ] : 0;
			$html    = wp_get_attachment_url( $pictid );
		}
	}
	
	if ( $out === 'return' ) {
		return $html;
	} else {
		echo $html;
	}
}

function usces_the_itemImageCaption( $number = 0, $post = '', $out = '' ) {
	global $usces;

	$ptitle = $number;
	if ( $ptitle && 0 === (int) $number ) {

		$picposts = query_posts( array( 'post_type' => 'attachment', 'name' => $ptitle ) );
		$excerpt  = empty( $picposts ) ? '' : $picposts[0]->post_excerpt;

	} else {

		if ( $post == '' ) {
			global $post;
		}
		$post_id = $post->ID;
		$product = wel_get_product( $post_id );

		$code =  $product['itemCode'];
		if ( ! $code ) {
			return false;
		}
		$name = $product['itemName'];
	
		if ( 0 == $number ) {
			$pictid    = $usces->get_mainpictid( $code );
			$attach_ob = get_post( $pictid );
		} else {
			$pictids   = $usces->get_pictids( $code );
			$ind       = $number - 1;
			$attach_ob = get_post( $pictids[ $ind ] );
		}
		$excerpt = $attach_ob->post_excerpt;
	}

	if ( $out === 'return' ) {
		return $excerpt;
	} else {
		echo esc_html( $excerpt );
	}
}

function usces_the_itemImageDescription( $number = 0, $post = '', $out = '' ) {
	global $usces;

	$ptitle = $number;
	if ( $ptitle && 0 === (int) $number ) {

		$picposts = query_posts( array( 'post_type' => 'attachment', 'name' => $ptitle ) );
		$excerpt  = empty( $picposts ) ? '' : $picposts[0]->post_content;
	
	} else {

		if ( $post == '' ) {
			global $post;
		}
		$post_id = $post->ID;
		$product = wel_get_product( $post_id );

		$code =  $product['itemCode'];
		if ( ! $code ) {
			return false;
		}
		$name = $product['itemName'];
		
		if ( 0 == $number ) {
			$pictid    = $usces->get_mainpictid( $code );
			$attach_ob = get_post( $pictid );
		} else {
			$pictids   = $usces->get_pictids( $code );
			$ind       = $number - 1;
			$attach_ob = get_post( $pictids[ $ind ] );
		}
		$excerpt = $attach_ob->post_content;
	}

	if ( $out === 'return' ) {
		return $excerpt;
	} else {
		echo esc_html( $excerpt );
	}
}

function usces_get_itemSubImageNums() {
	global $post, $usces;

	$post_id = $post->ID;
	$res     = array();
	
	$product = wel_get_product( $post_id );
	$code    =  $product['itemCode'];
	if ( ! $code ) {
		return false;
	}
	$name = $product['itemName'];

	$pictids       = $usces->get_pictids( $code );
	$pictids_count = ( $pictids && is_array( $pictids ) ) ? count( $pictids ) : 0;
	for ( $i = 1; $i <= $pictids_count; $i++ ) {
		$res[] = $i;
	}
	return  $res;
}

function usces_is_options() {
	global $usces;
	
	if( !empty( $usces->itemopts ) && is_array( $usces->itemopts ) && 0 < count( $usces->itemopts ) ){
		reset($usces->itemopts);
		$usces->itemopt = array();
		$usces->current_itemopt = -1;
		return true;
	}else{
		return false;
	}
}

function usces_have_options() {
	global $usces;

	if ( null === $usces->current_itemopt ) {
		$usces->current_itemopt = - 1;
	}

	if ( !empty( $usces->itemopts ) && is_array( $usces->itemopts ) && ( $usces->current_itemopt + 1 < count( $usces->itemopts ) ) ) {
		$usces->current_itemopt++;
		$usces->itemopt = ( isset( $usces->itemopts[ $usces->current_itemopt ] ) ) ? $usces->itemopts[ $usces->current_itemopt ] : array();

		return true;
	} else {
		return false;
	}
}

function usces_getItemOptName() {
	global $usces;
	return $usces->itemopt['name'];
}

function usces_the_itemOptName($out = '') {
	global $usces;

	if($out == 'return'){
		return $usces->itemopt['name'];
	}else{
		echo esc_html($usces->itemopt['name']);
	}
}

function usces_the_itemOption( $name, $label = '#default#', $out = '' ) {
	global $post, $usces;
	$post_id = $post->ID;
	
	if ( $label === '#default#' ) {
		$label = $name;
	}

	$opts = wel_get_opts( $post_id, 'name' );
	if ( ! $opts ) {
		return false;
	}

	$opt          = $opts[ $name ];
	$opt['value'] = usces_change_line_break( $opt['value'] );
	$means        = (int) $opt['means'];
	$essential    = (int) $opt['essential'];

	$sku     = esc_attr( urlencode( $usces->itemsku['code'] ) );
	$optcode = esc_attr( urlencode( $name ) );
	$name    = esc_attr( $name );
	$label   = esc_attr( $label );

	$session_value = isset( $_SESSION['usces_singleitem']['itemOption'][ $post_id ][ $sku ][ $optcode ] ) ? $_SESSION['usces_singleitem']['itemOption'][ $post_id ][ $sku ][ $optcode ] : null;

	$html = '';
	$html .= "\n<label for='itemOption[{$post_id}][{$sku}][{$optcode}]' class='iopt_label'>{$label}</label>\n";

	switch ( $means ) {
		case 0:// Single-select.
		case 1:// Multi-select.
			$selects        = explode( "\n", $opt['value'] );
			$multiple       = ( $means === 0 ) ? '' : ' multiple';
			$multiple_array = ($means == 0) ? '' : '[]';

			$html .= "\n<select name='itemOption[{$post_id}][{$sku}][{$optcode}]{$multiple_array}' id='itemOption[{$post_id}][{$sku}][{$optcode}]' class='iopt_select'{$multiple} onKeyDown=\"if (event.keyCode == 13) {return false;}\">\n";
			if ( $essential == 1 ) {
				if ( 0 == $means && ( '#NONE#' == $session_value || NULL == $session_value ) ) {
					$selected = ' selected="selected"';
				} else {
					$selected = '';
				}
				$html .= "\t<option value='#NONE#'{$selected}>" . __('Choose','usces') . "</option>\n";
			}
			$i=0;
			foreach ( (array) $selects as $v ) {
				$v = trim( $v );
				if( ( $i == 0 && $essential == 0 && null == $session_value) || esc_attr( $v ) == $session_value ) { 
					$selected = ' selected="selected"';
				} else {
					$selected = '';
				}
				$html .= "\t<option value='" . esc_attr( $v ) . "'{$selected}>" . esc_attr( $v ) . "</option>\n";
				$i++;
			}
			$html .= "</select>\n";
			break;
		case 2:// Text.
			$html .= "\n<input name='itemOption[{$post_id}][{$sku}][{$optcode}]' type='text' id='itemOption[{$post_id}][{$sku}][{$optcode}]' class='iopt_text' onKeyDown=\"if (event.keyCode == 13) {return false;}\" value=\"" . esc_attr($session_value) . "\" />\n";
			break;
		case 3:// Radio-button.
			$selects = explode( "\n", $opt['value'] );
			$i = 0;
			foreach ( (array) $selects as $v ) {
				$v = trim( $v );
				if ( $v == $session_value ) {
					$checked = ' checked="checked"';
				} else {
					$checked = '';
				}
				$html .= "\t<label for='itemOption[{$post_id}][{$sku}][{$optcode}]{$i}' class='iopt_radio_label'><input name='itemOption[{$post_id}][{$sku}][{$optcode}]' id='itemOption[{$post_id}][{$sku}][{$optcode}]{$i}' class='iopt_radio' type='radio' value='" . urlencode($v) . "'{$checked}>" . esc_html($v) . "</label>\n";
				$i++;
			}
			break;
		case 4:// Check-box.
			$selects = explode( "\n", $opt['value'] );
			$i=0;
			foreach ( (array) $selects as $v ) {
				$v = trim( $v );
				if ( $v == $session_value ) {
					$checked = ' checked="checked"';
				} else {
					$checked = '';
				}
				$html .= "\t<label for='itemOption[{$post_id}][{$sku}][{$optcode}]{$i}' class='iopt_checkbox_label'><input name='itemOption[{$post_id}][{$sku}][{$optcode}][]' id='itemOption[{$post_id}][{$sku}][{$optcode}]{$i}' class='iopt_checkbox' type='checkbox' value='" . urlencode($v) . "'{$checked}>" . esc_html($v) . "</label><br />\n";
				$i++;
			}
			break;
		case 5:// Text-area.
			$html .= "\n<textarea name='itemOption[{$post_id}][{$sku}][{$optcode}]' id='itemOption[{$post_id}][{$sku}][{$optcode}]' class='iopt_textarea'>" . esc_attr($session_value) . "</textarea>\n";
			break;
	}

	$html = apply_filters( 'usces_filter_the_itemOption', $html, $opts, $name, $label, $post_id, $usces->itemsku['code'] );

	if ( $out === 'return' ) {
		return $html;
	} else {
		echo $html;
	}
}

function usces_the_cart() {
	global $usces;
	
	$usces->display_cart();
	
}

function usces_is_cart_page() {
	global $usces;
	if( $usces->is_cart_page($_SERVER['REQUEST_URI']) ) {
		if( 'cart' == $usces->page ) return true;
		if( 'customer' != $usces->page and 'delivery' != $usces->page and 'confirm' != $usces->page and 'ordercompletion' != $usces->page and 'error' != $usces->page and 'search_item' != $usces->page ) return true;
	}
	return false;
}

function usces_is_cart() {
	global $usces;
	
	if($usces->cart->num_row() > 0)
		if( apply_filters('usces_is_cart_check', true) ) {
			return true;
		}else{
			return false;
		}
	else
		return false;
		
}

function usces_is_category( $str ) {
	global $post;

	$cat = get_the_category();
	$slugs = array();
	foreach($cat as $value){
		$slugs[] = $value->slug;
	}
	
	$str = utf8_uri_encode($str);
	
	if( in_array( $str, $slugs) )
		return true;
	else
		return false;
}

function usces_the_pref( $flag, $out = '' ){
	global $usces;
	
	$usces_members = $usces->get_member();
	$usces_entries = $usces->cart->get_entry();
	$name = esc_attr($flag) . '[pref]';
	$pref = $usces_entries[$flag]['pref'];
	if( 'member' == $flag)
		$pref = $usces_members['pref'];
	
	$html = "<select name='" . esc_attr($name) . "' id='pref' class='pref'>\n";
	$prefs = get_usces_states(usces_get_local_addressform());
	foreach($prefs as $value) {
		$selected = ($pref == $value) ? ' selected="selected"' : '';
		$html .= "\t<option value='" . esc_attr($value) . "'{$selected}>" . esc_html($value) . "</option>\n";
	}
	$html .= "</select>\n";
	
	if( $out == 'return' ){
		return $html;
	}else{
		echo $html;
	}
}

function usces_the_company_name(){
	global $usces;
	echo esc_html($usces->options['company_name']);
}

function usces_the_zip_code(){
	global $usces;
	echo esc_html($usces->options['zip_code']);
}

function usces_the_address1(){
	global $usces;
	echo esc_html($usces->options['address1']);
}

function usces_the_address2(){
	global $usces;
	echo esc_html($usces->options['address2']);
}

function usces_the_tel_number(){
	global $usces;
	echo esc_html($usces->options['tel_number']);
}

function usces_the_fax_number(){
	global $usces;
	echo esc_html($usces->options['fax_number']);
}

function usces_the_inquiry_mail(){
	global $usces;
	echo esc_html($usces->options['inquiry_mail']);
}

function usces_the_postage_privilege(){
	global $usces;
	echo esc_html($usces->options['postage_privilege']);
}

function usces_the_start_point(){
	global $usces;
	echo esc_html($usces->options['start_point']);
}

function usces_point_rate( $post_id = null, $out = '' ) {
	global $usces;

	if (  $post_id == null ) {
		$rate = $usces->options['point_rate'];
	} else {
		$product = wel_get_product( $post_id );
		$str     = $product['itemPointrate'];
		$rate    = (int) $str;
	}
	if ( $out === 'return' ) {
		return $rate;
	} else {
		echo $rate;
	}
}

function usces_point_rate_discount( $post_id = null, $out = '' ) {
	global $post, $usces;

	if ( $post_id == null ) {
		$post_id = $post->ID;
	}

	$product = wel_get_product( $post_id );
	$str     = $product['itemPointrate'];
	$rate    = (int) $str;
	if ( $usces->options['campaign_privilege'] == 'point' ) {
		if ( in_category( (int) $usces->options['campaign_category'], $post_id ) ) {
			$rate *= $usces->options['privilege_point'];
		}
	}
	if ( $out === 'return' ) {
		return $rate;
	} else {
		echo $rate;
	}
}

function usces_get_point( $post_id, $sku_code ) {
	global $usces;

	if ( $post_id == null ) {
		$rate = $usces->options['point_rate'];
	} else {
		$product = wel_get_product( $post_id );
		$str     = $product['itemPointrate'];
		$rate    = (int) $str;
	}

	$skus = wel_get_skus( $post_id, 'code' );
	$point = ceil( $skus[ $sku_code ]['price'] * $rate / 100 );

	return $point;
}

function usces_the_payment_method( $value = '', $out = '' ){
	global $usces;
	$payments = usces_get_system_option( 'usces_payment_method', 'sort' );
	$payments = apply_filters('usces_fiter_the_payment_method', $payments, $value);
	
	if( defined('WCEX_DLSELLER_VERSION') and version_compare( WCEX_DLSELLER_VERSION, '2.2-beta', '<=' ) ) {
		$cart = $usces->cart->get_cart();
		$have_continue_charge = usces_have_continue_charge( $cart );
		$continue_payment_method = apply_filters( 'usces_filter_the_continue_payment_method', array( 'acting_remise_card', 'acting_paypal_ec' ) );
	}

	$list = '';
	$payment_ct = ( $payments && is_array( $payments ) ) ? count( $payments ) : 0;
	foreach ((array)$payments as $id => $payment) {
		if( defined('WCEX_DLSELLER_VERSION') and version_compare( WCEX_DLSELLER_VERSION, '2.2-beta', '<=' ) ) {
			if( $have_continue_charge ) {
				if( !in_array( $payment['settlement'], $continue_payment_method ) ) {
					$payment_ct--;
					continue;
				}
				if( isset($usces->options['acting_settings']['remise']['continuation']) && 'on' !== $usces->options['acting_settings']['remise']['continuation'] && 'acting_remise_card' == $payment['settlement']) {
					$payment_ct--;
					continue;
				} elseif( isset($usces->options['acting_settings']['paypal']['continuation']) && 'on' !== $usces->options['acting_settings']['paypal']['continuation'] && 'acting_paypal_ec' == $payment['settlement']) {
					$payment_ct--;
					continue;
				}
			}
		}
		if( $payment['name'] != '' and $payment['use'] != 'deactivate' ) {
			$module = trim($payment['module']);
			if( !WCUtils::is_blank($value) ){
				$checked = ($payment['name'] == $value) ? ' checked' : '';
			}else if( 1 == $payment_ct ){
				$checked = ' checked';
			}else{
				$checked = '';
            }
            $paymentRow = '';
			$checked = apply_filters( 'usces_fiter_the_payment_method_checked', $checked, $payment, $value );
			$explanation = apply_filters( 'usces_fiter_the_payment_method_explanation', $payment['explanation'], $payment, $value );
			if( (empty($module) || !file_exists($usces->options['settlement_path'] . $module)) && $payment['settlement'] == 'acting' ) {
				$checked = '';
				$paymentRow .= '<dt class="payment_'.$id.'"><label for="payment_name_'.$id.'"><input name="offer[payment_name]" id="payment_name_'.$id.'" type="radio" value="'.esc_attr($payment['name']).'"'.$checked.' disabled onKeyDown="if (event.keyCode == 13) {return false;}" />'.esc_attr($payment['name'])."</label> <b>(".__('cannot use this payment method now.','usces').")</b></dt>";
			}else{
				$paymentRow .= '<dt class="payment_'.$id.'"><label for="payment_name_'.$id.'"><input name="offer[payment_name]" id="payment_name_'.$id.'" type="radio" value="'.esc_attr($payment['name']).'"'.$checked.' onKeyDown="if (event.keyCode == 13) {return false;}" />'.esc_attr($payment['name'])."</label></dt>";
			}
			if( !empty( $explanation ) ) {
				$paymentRow .= '<dd class="payment_'.$id.'">'.$explanation.'</dd>';
            }
            $paymentRow = apply_filters('usces_filter_the_payment_method_row', $paymentRow, $id, $payment, $checked, $module, $value, $explanation);
            if (!empty($paymentRow)) {
                $list .= $paymentRow;
            }
		}
	}

	if( !empty( $list ) ) {
		$html = '<dl>'.$list.'</dl>';
	} else {
		$html = '<div>'.__('Not yet ready for the payment method. Please refer to a manager.', 'usces').'</div>';
	}

	$html = apply_filters( 'usces_filter_the_payment_method_choices', $html, $payments );
	if( $out == 'return' ){
		return $html;
	}else{
		echo $html;
	}
}

function usces_get_payments_by_name( $name ){
	global $usces;
	$init = array(
		'id'          => null,
		'name'        => null,
		'explanation' => null,
		'settlement'  => null,
		'module'      => null,
		'sort'        => null,
		'use'         => null
	);
	$payments = usces_get_system_option( 'usces_payment_method', 'name' );
	if( empty($payments) ) return $init;
	
	if( isset($payments[$name]) ) {
		return $payments[$name];
	}

	return $init;
}

function usces_the_delivery_method( $value = '', $out = '' ){
	global $usces;
	$deli_id = apply_filters('usces_filter_get_available_delivery_method', $usces->get_available_delivery_method());
	if( empty($deli_id) ){
		$html = '<p>' . __('No valid shipping methods.', 'usces') . '</p>';
	}else{
		$cdeliid = ( is_array( $deli_id ) ) ? count( $deli_id ) : 0;
		$html = '<select name="offer[delivery_method]"  id="delivery_method_select" class="delivery_time" onKeyDown="if (event.keyCode == 13) {return false;}">'."\n";
		foreach ($deli_id as $id) {
			$index = $usces->get_delivery_method_index($id);
			if( 0 <= $index ) {
				$selected = ($id == $value || 1 === $cdeliid) ? ' selected="selected"' : '';
				$html .= "\t<option value='{$id}'{$selected}>" . esc_html($usces->options['delivery_method'][$index]['name']) . "</option>\n";
			}
		}
	
		$html .= "</select>\n";
	}
	
	$html = apply_filters('usces_filter_the_delivery_method', $html, $deli_id );
	
	if( $out == 'return' ){
		return $html;
	}else{
		echo $html;
	}
}

function usces_the_delivery_date( $value = '', $out = '' ){
	global $usces;

	$html = "<select name='offer[delivery_date]' id='delivery_date_select' class='delivery_date'>\n";
	$html .= "</select>\n";

	$html = apply_filters('the_delivery_date', $html );

	if( $out == 'return' ){
		return $html;
	}else{
		echo $html;
	}
}

function usces_the_delivery_time( $value = '', $out = '' ){
	global $usces;

	$html = "<div id='delivery_time_limit_message'></div>\n";
	$html .= "<select name='offer[delivery_time]' id='delivery_time_select' class='delivery_time'>\n";

	$html .= "</select>\n";
	
	$html = apply_filters('the_delivery_time', $html );

	if( $out == 'return' ){
		return $html;
	}else{
		echo $html;
	}
}

function usces_the_campaign_schedule($flag, $kind){
	global $usces;
	$startdate = $usces->options['campaign_schedule']['start']['year'] . __('year','usces') . $usces->options['campaign_schedule']['start']['month'] . __('month','usces') . $usces->options['campaign_schedule']['start']['day'] . __('day','usces');
	$starttime = $usces->options['campaign_schedule']['start']['hour'] . __('hour','usces') . $usces->options['campaign_schedule']['start']['min'] . __('min','usces');
	$enddate = $usces->options['campaign_schedule']['end']['year'] . __('year','usces') . $usces->options['campaign_schedule']['end']['month'] . __('month','usces') . $usces->options['campaign_schedule']['end']['day'] . __('day','usces');
	$endtime = $usces->options['campaign_schedule']['end']['hour'] . __('hour','usces') . $usces->options['campaign_schedule']['end']['min'] . __('min','usces');
	if( 'start' == $flag ) {
		if( 'date' == $kind ) {
			echo esc_html($startdate);
		}elseif( 'datetime' == $kind ) {
			echo esc_html($startdate . ' ' . $starttime);
		}
	} elseif ( 'end' == $flag ) {
		if( 'date' == $kind ) {
			echo esc_html($enddate);
		}elseif( 'datetime' == $kind ) {
			echo esc_html($enddate . ' ' . $endtime);
		}
	}
}


function usces_the_confirm() {
	global $usces;
	
	$usces->display_cart_confirm();
}

function usces_inquiry_condition() {
	global $error_message, $reserve, $inq_name, $inq_mailaddress, $inq_contents;
	require(USCES_PLUGIN_DIR.'/includes/inquiry_condition.php');
}

function usces_the_inquiry_form() {
	global $usces;
	$error_message = '';
	if( isset($_POST['inq_name']) && !WCUtils::is_blank($_POST['inq_name']) ) {
		$inq_name = trim( wp_unslash( $_POST['inq_name'] ) );
	}else{
		$inq_name = '';
		if($usces->page == 'deficiency')
			$error_message .= __('Please input your name.', 'usces') . "<br />";
	}
	if( isset($_POST['inq_mailaddress']) && is_email(trim(wp_unslash( $_POST['inq_mailaddress'] ))) ) {
		$inq_mailaddress = trim(wp_unslash( $_POST['inq_mailaddress'] ));
	}elseif( isset($_POST['inq_mailaddress']) && !is_email(trim(wp_unslash( $_POST['inq_mailaddress'] ))) ) {
		$inq_mailaddress = trim(wp_unslash( $_POST['inq_mailaddress'] ));
		if($usces->page == 'deficiency')
			$error_message .= __('E-mail address is not correct', 'usces') . "<br />";
	}else{
		$inq_mailaddress = '';
		if($usces->page == 'deficiency')
			$error_message .= __('Please input your e-mail address.', 'usces') . "<br />";
	}
	if( isset($_POST['inq_contents']) && !WCUtils::is_blank($_POST['inq_contents']) ) {
		$inq_contents = trim(wp_unslash( $_POST['inq_contents'] ));
	}else{
		$inq_contents = '';
		if($usces->page == 'deficiency')
			$error_message .= __('Please input contents.', 'usces');
	}
	

	if($usces->page == 'inquiry_comp') :
		$inq_message = apply_filters( 'usces_filter_inquiry_message_completion', __('I send a reply email to a visitor. I ask in a few minutes to be able to have you refer in there being the fear that e-mail address is different again when the email from this shop does not arrive.', 'usces') );
?>
	<div class="inquiry_comp"><?php _e('sending completed','usces') ?></div>
	<div class="compbox"><?php echo $inq_message; ?></div>
<?php
	elseif($usces->page == 'inquiry_error') :
?>
	<div class="inquiry_comp"><?php _e('Failure in sending','usces') ?></div>
<?php 
	else :
?>
<?php if( !empty($error_message) ): ?>
<div class="error_message"><?php echo $error_message; ?></div>
<?php endif; ?>
<form name="inquiry_form" action="<?php //echo USCES_CART_URL; ?>" method="post">
<input type="hidden" name="kakuninyou" />
<table border="0" cellpadding="0" cellspacing="0" class="inquiry_table">
<tr>
<th scope="row"><?php _e('Full name','usces') ?></th>
<td><input name="inq_name" type="text" class="inquiry_name" value="<?php echo esc_attr($inq_name); ?>" /></td>
</tr>
<tr>
<th scope="row"><?php _e('e-mail adress','usces') ?></th>
<td><input name="inq_mailaddress" type="text" class="inquiry_mailaddress" value="<?php echo esc_attr($inq_mailaddress); ?>" /></td>
</tr>
<tr>
<th scope="row"><?php _e('contents','usces') ?></th>
<td><textarea name="inq_contents" class="inquiry_contents"><?php echo esc_attr($inq_contents); ?></textarea></td>
</tr>
</table>
<div class="send"><input name="inquiry_button" type="submit" value="<?php _e('Admit to send it with this information.','usces') ?>" /></div>
</form>
<?php
	endif;
}

function usces_get_cat_id( $slug ) {
	$cat = get_category_by_slug( $slug ); 
	return $cat->term_id; 
}

function usces_the_calendar() {
	global $usces;
	include (USCES_PLUGIN_DIR . '/includes/widget_calendar.php'); 
}

function usces_loginout( $out = '') {
	global $usces;
	if ( !$usces->is_member_logged_in() )
		$res = '<a href="' . apply_filters('usces_filter_login_uri', USCES_LOGIN_URL) . '" class="usces_login_a">' . apply_filters('usces_filter_loginlink_label', __('Log-in','usces')) . '</a>';
	else
		$res = '<a href="' . apply_filters('usces_filter_logout_uri', USCES_LOGOUT_URL) . '" class="usces_logout_a">' . apply_filters('usces_filter_logoutlink_label', __('Log out','usces')) . '</a>';

	if( $out == 'return' ){
		return $res;
	}else{
		echo $res;
	}
}

function usces_is_login() {
	global $usces;
	
	if( false === $usces->is_member_logged_in() )
		$res = false;
	else
		$res = true;
		
	return $res;
}

function usces_the_member_name( $out = '') {
	global $usces;
	$usces->get_current_member();
	$res = esc_html($usces->current_member['name']);
	if( $out == 'return' ){
		return $res;
	}else{
		echo $res;
	}
	
}

function usces_the_member_point( $out = '' ) {
	global $usces;
	
	if( !$usces->is_member_logged_in() ) return;
	
	$member = $usces->get_member();
	if( $out == 'return' ){
		return $member['point'];
	}else{
		echo number_format($member['point']);
	}
}

function usces_the_member_status( $out = '' ) {
	global $usces;
	if( !$usces->is_member_logged_in() ) return;
	
	$usces->get_current_member();
	$member = $usces->get_member_info($usces->current_member['id']);
	$status_name = $usces->member_status[$member['mem_status']];

	if( $out == 'return' ){
		return $status_name;
	}else{
		echo esc_html($status_name);
	}
}

function usces_get_assistance_id_list($post_id) {
	global $usces;
	$names = $usces->get_tag_names($post_id);
	$list = '';
	foreach ( $names as $itemname )
		$list .= $usces->get_ID_byItemName($itemname, 'publish') . ',';
	
	$list = trim($list, ',');

	return $list;
}
function usces_get_assistance_ids($post_id) {
	global $usces;
	$names = $usces->get_tag_names($post_id);
	$ids = array();
	foreach ( $names as $itemname )
		$ids[] = $usces->get_ID_byItemName($itemname, 'publish');

	return $ids;
}
function usces_remembername( $out = '' ){
	global $usces;
	$value = $usces->get_cookie();
	
	if( $out == 'return' ){
//		if(isset($value['name']))
//			return $value['name'];
//		else
			return '';
	}else{
//		if(isset($value['name']))
//			echo esc_html($value['name']);
//		else
			echo '';
	}
}
function usces_rememberpass( $out = '' ){
	global $usces;
	$value = $usces->get_cookie();
	
	if( $out == 'return' ){
//		if(isset($value['pass']))
//			return $value['pass'];
//		else
			return '';
	}else{
//		if(isset($value['pass']))
//			echo esc_html($value['pass']);
//		else
			echo '';
	}
}
function usces_remembercheck( $out = '' ){
	global $usces;
	$value = $usces->get_cookie();
	
	if( $out == 'return' ){
//		if(isset($value['name']) && $value['name'] != '')
//			return ' checked="checked"';
//		else
			return '';
	}else{
//		if(isset($value['name']) && $value['name'] != '')
//			echo ' checked="checked"';
//		else
			echo '';
	}
}
function usces_shippingchargeTR( $index='' ) {
	global $usces;
	if($index == ""){
		$index = 0;
	}
	$list = '';
	if( !isset($usces->options['shipping_charge'][$index]) ) return;
	$shipping_charge = $usces->options['shipping_charge'][$index];
	$entry = $usces->cart->get_entry();
	$country = (isset($entry['delivery']['country']) && !empty($entry['delivery']['country'])) ? $entry['delivery']['country'] : $entry['customer']['country'];
	foreach ($shipping_charge[$country] as $pref => $value) {
		$list .= "<tr><th>" . esc_html($pref) . "</th>\n";
		$list .= "<td class='rightnum'>" . number_format($value) . "</td>\n";
		$list .= "</tr>\n";
	}
	echo $list;
}
function usces_sc_shipping_charge() {
	global $usces;
	echo esc_html($usces->sc_shipping_charge());
}
function usces_sc_postage_privilege() {
	global $usces;
	echo esc_html($usces->sc_postage_privilege());
}
function usces_sc_payment_title() {
	global $usces;
	echo $usces->sc_payment_title();
}



function usces_posts_random_offset( $posts ){
	$ids = array();
	foreach( (array)$posts as $post ){
		$ids[] = $post->ID;
	}
	$ct = count($ids);
	$index = rand(0, ($ct-1));
	return $index;
}

function usces_get_category_link_by_slug( $slug ){
	$category = get_category_by_slug($slug); 
	echo get_category_link( $category->term_id );
}

function usces_get_page_ID_by_pname( $post_name, $return = 'echo' ){
	$page = get_page_by_path( $post_name );
	if($return == 'return')
		return $page->ID;
	else
		echo $page->ID;
}

function usces_list_bestseller( $num, $days = '' ){
	global $usces;
	$ids = $usces->get_bestseller_ids( $days );
	$htm = "";
	for ( $i = 0; $i < $num; $i++ ) {
		if ( isset( $ids[ $i ] ) ) {
			$post_id = (int) $ids[ $i ];
			$product = wel_get_product( $post_id );
			$post = $product['_pst'];

			if( false === $product ) {
				continue;
			}
				
			$disp_text = apply_filters( 'usces_widget_bestseller_auto_text', esc_html( $post->post_title ), $post_id );
			$list      = "<li><a href='" . get_permalink( $post_id ) . "'>" . $disp_text . "</a></li>\n";
			$htm .= apply_filters( 'usces_filter_bestseller', $list, $post_id, $i );
		}
	}
	wp_reset_postdata();
	echo $htm;
}

function usces_list_post( $slug, $rownum, $widget_id=NULL ){
	global $usces, $post;
	usces_remove_filter();
	
	$li = '';
	$infolist = new wp_query( array('category_name'=>$slug, 'post_status'=>'publish', 'posts_per_page'=>$rownum, 'order'=>'DESC', 'orderby'=>'date') );
	if( NULL != $widget_id && $infolist->have_posts() ){
		remove_filter( 'excerpt_length', 'welcart_excerpt_length' );
		remove_filter( 'excerpt_mblength', 'welcart_excerpt_mblength' );
		remove_filter( 'excerpt_more', 'welcart_auto_excerpt_more' );
		if( function_exists('welcart_widget_post_excerpt_length_'.$widget_id) )
			add_filter( 'excerpt_length', 'welcart_widget_post_excerpt_length_'.$widget_id );
		if( function_exists('welcart_widget_post_excerpt_mblength_'.$widget_id) )
			add_filter( 'excerpt_mblength', 'welcart_widget_post_excerpt_mblength_'.$widget_id );
	}
	$list_index = 0;
	while ($infolist->have_posts()) {
		$infolist->the_post();
		$list = '<li class="post_list'. apply_filters('usces_filter_post_list_class', NULL, $list_index, $infolist->post_count ) . '">'."\n";
		$list .= "<div class='title'><a href='" . get_permalink($post->ID) . "'>" . get_the_title() . "</a></div>\n";
		$list .= "<p>" . get_the_excerpt() . "</p>\n";
		$list .= "</li>\n";
		$li .= apply_filters( 'usces_filter_widget_post', $list, $post, $slug, $list_index);
		$list_index++;
	}
	wp_reset_query();
	usces_reset_filter();
	if( NULL != $widget_id && $infolist->have_posts() ){
		add_filter( 'excerpt_length', 'welcart_excerpt_length' );
		add_filter( 'excerpt_mblength', 'welcart_excerpt_mblength' );
		add_filter( 'excerpt_more', 'welcart_auto_excerpt_more' );
	}
	echo $li;
}

function usces_categories_checkbox($output=''){
	global $usces;
	$retcats = apply_filters('usces_search_retcats', usces_search_categories());
	$parent_id = apply_filters('usces_search_categories_checkbox_parent', USCES_ITEM_CAT_PARENT_ID);
	$htm = usces_get_categories_checkbox($parent_id);
	$htm = apply_filters('usces_filter_categories_checkbox', $htm, $parent_id);
	
	if($output == '' || $output == 'echo')
		echo $htm;
	else
		return $htm;
}

function usces_get_categories_checkbox($parent_id){
	global $usces;
	$htm = '';
	$retcats = usces_search_categories();
	$parent_cat = get_category($parent_id);
	$categories =  get_categories('parent='.$parent_id . "&hide_empty=0&orderby=" . $usces->options['fukugo_category_orderby'] . "&order=" . $usces->options['fukugo_category_order']); 
	$htm .= "<fieldset class='catfield-" . $parent_cat->term_id . "'><legend>" . $parent_cat->cat_name . "</legend><ul>\n";
	foreach ($categories as $cat) {
		$children =  get_categories('parent='.$cat->term_id . "&hide_empty=0");
		if( 0 === count($children) ){
			$checked = in_array($cat->term_id, $retcats) ? " checked='checked'" : "";
			$htm .= "<li><input name='category[".$cat->term_id."]' type='checkbox' id='category[".$cat->term_id."]' value='".$cat->term_id."'".$checked." /><label for='category[".$cat->term_id."]' class='catlabel-" . $cat->term_id . "'>".esc_html($cat->cat_name)."</label></li>\n";
		}
	}
	$htm .= "</ul>\n";
	foreach ($categories as $cat) {
		$children =  get_categories('parent='.$cat->term_id . "&hide_empty=0");
		if( 0 < count($children) ){
			$htm .= usces_get_categories_checkbox($cat->term_id);
		}
	}
	$htm .= "</fieldset>\n";

	return $htm;
}

function usces_search_categories(){
	$cats = array();
	if(isset($_REQUEST['category']))
		$cats = wp_unslash( $_REQUEST['category'] );
	else
		$cats[] = USCES_ITEM_CAT_PARENT_ID;
	sort($cats);
	return $cats;
}

function usces_delivery_method_name( $id, $out = '' ){
	global $usces;
	
	$id =$usces->get_delivery_method_index($id);
	if($id > -1){
		$name = $usces->options['delivery_method'][$id]['name'];
	}else{		
		$name = __('No preference','usces');
	}
	
	if($out == 'return'){
		return $name;
	}else{
		echo esc_html($name);
	}
}

function usces_is_membersystem_state(){
	global $usces;

	if($usces->options['membersystem_state'] == 'activate') {
		return true;
	}else{
		return false;
	}
}

function usces_is_membersystem_point(){
	global $usces;

	if($usces->options['membersystem_point'] == 'activate') {
		return true;
	}else{
		return false;
	}
}

function usces_copyright(){
	global $usces;

	echo esc_html($usces->options['copyright']);
}

function usces_totalprice_in_cart(){
	global $usces;

	echo number_format($usces->get_total_price());
}

function usces_totalquantity_in_cart(){
	global $usces;

	echo number_format($usces->get_total_quantity());
}

function usces_get_page_mode(){
	global $usces;

	return $usces->page;
}

function usces_is_cat_of_item( $cat_id ){
	global $usces;
	$ids = $usces->get_item_cat_ids();
	$ids[] = USCES_ITEM_CAT_PARENT_ID;
	if(in_array($cat_id, $ids)){
		return true;
	}else{
		return false;
	}
}

function usces_get_item_custom( $post_id, $type = 'list', $out = '' ){
	global $usces;

	$cfields = wel_get_extra_data( $post_id );

	switch ( $type ) {
		case 'list':
			$list = '';
			$html = '<ul class="item_custom_field">' . "\n";
			foreach ( $cfields as $key => $values ) {
				if ( 'wccs_' === substr( $key, 0, 5 ) ) {
					if ( is_array( $values ) ) {
						foreach ( $values as $value ) {
							$list .= '<li>' . esc_html( substr( $key, 5 ) ) . ' : ' . nl2br( esc_html( $value ) ) . '</li>'."\n";
						}
					} else {
						$list .= '<li>' . esc_html( substr( $key, 5 ) ) . ' : ' . nl2br( esc_html( $values ) ) . '</li>'."\n";
					}
				}
			}
			if ( empty( $list ) ) {
				$html = '';
			} else {
				$html .= $list . '</ul>' . "\n";
			}
			break;

		case 'table':
			$list = '';
			$html = '<table class="item_custom_field">' . "\n";
			foreach ( $cfields as $key => $values ) {
				if ( 'wccs_' === substr( $key, 0, 5 ) ) {
					if ( is_array( $values ) ) {
						foreach ( $values as $value ) {
							$list .= '<tr><th>' . esc_html( substr( $key, 5 ) ) . '</th><td>' . nl2br( esc_html( $value ) ) . '</td></tr>' . "\n";
						}
					} else {
						$list .= '<tr><th>' . esc_html( substr( $key, 5 ) ) . '</th><td>' . nl2br( esc_html( $values ) ) . '</td></tr>' . "\n";
					}
				}
			}
			if ( empty( $list ) ) {
				$html = '';
			} else {
				$html .= $list . '</table>' . "\n";
			}
			break;

		case 'notag':
			$list = '';
			foreach ( $cfields as $key => $values ) {
				if ( 'wccs_' === substr( $key, 0, 5 ) ) {
					if ( is_array( $values ) ) {
						foreach ( $values as $value ) {
							$list .= esc_html( substr( $key, 5 ) ) . ' : ' . nl2br( esc_html( $value ) ) . "\r\n";
						}
					} else {
						$list .= esc_html( substr( $key, 5 ) ) . ' : ' . nl2br( esc_html( $values ) ) . "\r\n";
					}
				}
			}
			if ( empty( $list ) ) {
				$html = '';
			} else {
				$html = $list;
			}
			break;

		case 'mail_html':
			$list = '';
			foreach ( $cfields as $key => $values ) {
				if ( 'wccs_' == substr( $key, 0, 5 ) ) {
					if ( is_array( $values ) ) {
						foreach ( $values as $value ) {
							$list .= '<tr>
							<td style="padding: 0 0 10px; text-align: left; width: 100px; font-weight: normal; vertical-align: text-top;">' . esc_html( substr( $key, 5 ) ) . '</td>
							<td style="padding: 0 0 10px 50px; width: calc( 100% - 100px );">' . nl2br( esc_html( $value ) ) . '</td>
							</tr>';
						}
					} else {
						$list .= '<tr>
						<td style="padding: 0 0 10px; text-align: left; width: 100px; font-weight: normal; vertical-align: text-top;">' . esc_html( substr( $key, 5 ) ) . '</td>
						<td style="padding: 0 0 10px 50px; width: calc( 100% - 100px );">' . nl2br( esc_html( $values ) ) . '</td>
						</tr>';
				}
				}
			}
			if ( empty( $list ) ) {
				$html = '';
			} else {
				$html  = '<table style="font-size: 14px; width: 100%; border-collapse: collapse;">';
				$html .= '<tbody><tr><td style="background-color: #f9f9f9; padding: 30px;">';
				$html .= '<table style="width: 100%;"><tbody>';
				$html .= $list;
				$html .= '</tbody></table></td></tr></tbody></table>';
			}
			break;
	}
	$html = apply_filters( 'usces_filter_item_custom', $html, $post_id, $type );

	if ( 'return' === $out ) {
		return $html;
	} else {
		echo $html;
	}
}

function usces_settle_info_field( $order_id, $type='nl', $out='echo' ){
	global $usces;
	$str = '';
	$fields = $usces->get_settle_info_field( $order_id );
	$acting = isset($fields['acting']) ? $fields['acting'] : '';
	$keys = array(
		'acting','order_no','tracking_no','status','error_message','money',
		'pay_cvs', 'pay_no1', 'pay_no2', 'pay_limit', 'error_code',
		'settlement_id','RECDATE','JOB_ID','S_TORIHIKI_NO','TOTAL','CENDATE',
		'gid', 'rst', 'ap', 'ec', 'god', 'ta', 'cv', 'no', 'cu', 'mf', 'nk', 'nkd', 'bank', 'exp', 'txn_id', 
		'order_number',
		'res_tracking_id', 'res_payment_date', 'res_payinfo_key',
		'settltment_status', 'settltment_errmsg', 
		'stran', 'mbtran', 'bktrans', 'tranid', 'TransactionId', 
		'mStatus', 'vResultCode', 'orderId', 'cvsType', 'receiptNo', 'receiptDate', 'rcvAmount', 
		'trading_id', 'payment_type', 'seq_payment_id', 'sendpoint', 'option', 
		'LINK_KEY' 
	);
	$keys = apply_filters( 'usces_filter_settle_info_field_keys', $keys, $fields );
	foreach($fields as $key => $value){
		if( !in_array($key, $keys) ) {
			continue;
		}

		if( 'jpayment_conv' == $acting ) {
			if( 'rst' == $key ) {
				if( '1' == $value ) {
					$value = 'OK';
				} elseif( '2' == $value ) {
					$value = 'NG';
				}
			} elseif( 'ap' == $key ) {
				if( 'CPL_PRE' == $value ) {
					$value = 'コンビニペーパーレス決済識別コード';
				} elseif( 'CPL' == $value ) {
					$value = '入金確定';
				} elseif( 'CVS_CAN' == $value ) {
					$value = '入金取消';
				}
			} elseif( 'cv' == $key ) {
				$value = esc_html(usces_get_conv_name($value));
			} else {
				continue;
			}

		} elseif( 'jpayment_bank' == $acting ) {
			if( 'rst' == $key ) {
				if( '1' == $value ) {
					$value = 'OK';
				} elseif( '2' == $value ) {
					$value = 'NG';
				}
			} elseif( 'ap' == $key ) {
				if( 'BANK' == $value ) {
					$value = '受付完了';
				} elseif( 'BAN_SAL' == $value ) {
					$value = '入金完了';
				}
			} elseif( 'mf' == $key ) {
				if( '1' == $value ) {
					$value = 'マッチ';
				} elseif( '2' == $value ) {
					$value = '過少';
				} elseif( '3' == $value ) {
					$value = '過剰';
				}
			} elseif( 'nkd' == $key ) {
				$value = substr( $value, 0, 4 ).'年'.substr( $value, 4, 2 ).'月'.substr( $value, 6, 2 ).'日';
			} elseif( 'exp' == $key ) {
				$value = substr( $value, 0, 4 ).'年'.substr( $value, 4, 2 ).'月'.substr( $value, 6, 2 ).'日';
			} else {
				continue;
			}

		} elseif( 'veritrans_conv' == $acting ) {
			if( 'cvsType' == $key ) {
				switch( $value ) {
				case 'sej':
					$value = 'セブン－イレブン';
					break;
				case 'econ-lw':
					$value = 'ローソン';
					break;
				case 'econ-fm':
					$value = 'ファミリーマート';
					break;
				case 'econ-mini':
					$value = 'ミニストップ';
					break;
				case 'econ-other':
					$value = 'セイコーマート';
					break;
				case 'econ-sn':
					$value = 'サンクス';
					break;
				case 'econ-ck':
					$value = 'サークルK';
					break;
				}
			}
		}
		$value = apply_filters( 'usces_filter_settle_info_field_value', $value, $key, $acting );
		switch($type){
			case 'nl':
				$str .= $key . ' : ' . $value . "<br />\n";
				break;
				
			case 'tr':
				$str .= '<tr><td class="label">' . $key . '</td><td>' . $value . "</td></tr>\n";
				break;
				
			case 'li':
				$str .= '<li>' . $key . ' : ' . $value . "</li>\n";
				break;
		}
	}
	if( 'return' == $out){
		return $str;
	}else{
		echo $str;
	}
}

function usces_custom_field_input( $data, $custom_field, $position, $out = '' ) {

	$html = '';
	switch($custom_field) {
	case 'order':
		$label = 'custom_order';
		$field = 'usces_custom_order_field';
		break;
	case 'customer':
		$label = 'custom_customer';
		$field = 'usces_custom_customer_field';
		break;
	case 'delivery':
		$label = 'custom_delivery';
		$field = 'usces_custom_delivery_field';
		break;
	case 'member':
		$label = 'custom_member';
		$field = 'usces_custom_member_field';
		break;
	default:
		return;
	}

	$meta = usces_has_custom_field_meta($custom_field);

	if(!empty($meta) and is_array($meta)) {
		foreach($meta as $key => $entry) {
			if($custom_field == 'order' or $entry['position'] == $position) {
				$name = $entry['name'];
				$means = $entry['means'];
				$essential = $entry['essential'];
				$value = '';
				if(is_array($entry['value'])) {
					foreach($entry['value'] as $k => $v) {
						$value .= $v."\n";
					}
				}
				$value = usces_change_line_break( $value );

				$e = ($essential == 1) ? '<em>' . __('*', 'usces') . '</em>' : '';
				$html .= '
					<tr class="customkey_' . $key . '">
					<th scope="row">'.$e.esc_html($name).apply_filters('usces_filter_custom_field_input_label', NULL, $key, $entry).'</th>';
				$html .= apply_filters( 'usces_filter_custom_field_input_td', '<td colspan="2">', $key, $entry);
				switch($means) {
					case 0://シングルセレクト
					case 1://マルチセレクト
						$selects = explode("\n", $value);
						$multiple = ($means == 0) ? '' : ' multiple';
						$multiple_array = ($means == 0) ? '' : '[]';
						$html .= '<select name="'.$label.'['.esc_attr($key).']'.$multiple_array.'" class="iopt_select"'.$multiple.'>';
						if($essential == 1) 
							$html .= '
								<option value="#NONE#">'.__('Choose','usces').'</option>';
						foreach((array)$selects as $v) {
							$selected = (isset($data[$label][$key]) && $data[$label][$key] == $v) ? ' selected' : '';
							$html .= '
								<option value="'.esc_attr($v).'"'.$selected.'>'.esc_html($v).'</option>';
						}
						$html .= '
							</select>';
						break;
					case 2://テキスト
						$text = isset($data[$label][$key]) ? $data[$label][$key] : '';
						$html .= '<input type="text" name="'.$label.'['.esc_attr($key).']" class="iopt_text" value="'.esc_attr($text).'" />';
						break;
					case 3://ラジオボタン
						$selects = explode("\n", $value);
						foreach((array)$selects as $v) {
							$checked = ( isset($data[$label][$key]) && $data[$label][$key] == $v) ? ' checked' : '';
							$html .= '
							<label for="'.$label.'['.esc_attr($key).']['.esc_attr($v).']" class="iopt_label"><input type="radio" name="'.$label.'['.esc_attr($key).']" id="'.$label.'['.esc_attr($key).']['.esc_attr($v).']" value="'.esc_attr($v).'"'.$checked.'>'.esc_html($v).'</label>';
						}
						break;
					case 4://チェックボックス
						$selects = explode("\n", $value);
						foreach($selects as $v) {
							if( isset($data[$label][$key]) && is_array($data[$label][$key]) ) {
								$checked = (isset($data[$label][$key]) && array_key_exists($v, $data[$label][$key])) ? ' checked' : '';
							} else {
								$checked = (isset($data[$label][$key]) && $data[$label][$key] == $v) ? ' checked' : '';
							}
							$html .= '
							<label for="'.$label.'['.esc_attr($key).']['.esc_attr($v).']" class="iopt_label"><input type="checkbox" name="'.$label.'['.esc_attr($key).']['.esc_attr($v).']" id="'.$label.'['.esc_attr($key).']['.esc_attr($v).']" value="'.esc_attr($v).'"'.$checked.'>'.esc_html($v).'</label>';
						}
						break;
					case 5://Text-area
						$text = ( isset($data[$label][$key]) ) ? $data[$label][$key] : '';
						$html .= '<textarea name="'.$label.'['.esc_attr($key).']" class="iopt_textarea">'.esc_attr($text).'</textarea>';
						break;
				}
				$html .= apply_filters('usces_filter_custom_field_input_value', NULL, $key, $entry).'</td>';
				$html .= '
					</tr>';
			}
		}
	}
	
	$html = apply_filters('usces_filter_custom_field_input', $html, $data, $custom_field, $position);

	if($out == 'return') {
		return stripslashes($html);
	} else {
		echo stripslashes($html);
	}
}

function usces_custom_field_info( $data, $custom_field, $position, $out = '' ) {

	$html = '';
	switch($custom_field) {
	case 'order':
		$label = 'custom_order';
		$field = 'usces_custom_order_field';
		break;
	case 'customer':
		$label = 'custom_customer';
		$field = 'usces_custom_customer_field';
		break;
	case 'delivery':
		$label = 'custom_delivery';
		$field = 'usces_custom_delivery_field';
		break;
	case 'member':
		$label = 'custom_member';
		$field = 'usces_custom_member_field';
		break;
	default:
		return;
	}

	$meta = usces_has_custom_field_meta($custom_field);

	if(!empty($meta) and is_array($meta)) {
		foreach($meta as $key => $entry) {
			if($custom_field == 'order' or $entry['position'] == $position) {
				$name = $entry['name'];
				$means = $entry['means'];

				$html .= '<tr>
					<th>'.esc_html($name).'</th>
					<td>';
				if(!empty($data[$label][$key])) {
					switch($means) {
					case 0://シングルセレクト
					case 2://テキスト
					case 3://ラジオボタン
					case 5://テキストエリア
						$html .= esc_html($data[$label][$key]);
						break;
					case 1://マルチセレクト
					case 4://チェックボックス
						if(is_array($data[$label][$key])) {
							$c = '';
							foreach($data[$label][$key] as $v) {
								$html .= $c.esc_html($v);
								$c = ', ';
							}
						} else {
							if(!empty($data[$label][$key])) $html .= esc_html($data[$label][$key]);
						}
						break;
					}
				}
				$html .= '
					</td>
					</tr>';
			}
		}
	}

	$html = apply_filters('usces_filter_custom_field_info', $html, $data, $custom_field, $position);

	if($out == 'return') {
		return stripslashes($html);
	} else {
		echo stripslashes($html);
	}
}

function usces_admin_custom_field_input( $meta, $custom_field, $position, $out = '' ) {

	$html = '';
	switch($custom_field) {
	case 'order':
		$label = 'custom_order';
		$class = '';
		break;
	case 'customer':
		$label = 'custom_customer';
		$class = ' class="col2"';
		break;
	case 'delivery':
		$label = 'custom_delivery';
		$class = ' class="col3"';
		break;
	case 'member':
		$label = 'custom_member';
		$class = '';
		break;
	case 'admin_member':
		$label = 'admin_custom_member';
		$class = '';
		break;
	default:
		return;
	}


	if(!empty($meta) and is_array($meta)) {
		foreach($meta as $key => $entry) {
			if($custom_field == 'order' or $entry['position'] == $position) {
				$name = $entry['name'];
				$means = $entry['means'];
				$essential = $entry['essential'];
				$value = '';
				if(is_array($entry['value'])) {
					foreach($entry['value'] as $k => $v) {
						$value .= $v."\n";
					}
				}
				$value = usces_change_line_break( $value );
				$value = apply_filters( 'usces_filter_admin_custom_field_input_value', $value, $key, $entry, $custom_field );
				$data = ( isset($entry['data']) ) ? $entry['data'] : NULL;

				$html .= '
					<tr>
					<td class="label">'.esc_html($name).'</td>';
				switch($means) {
				case 0://シングルセレクト
				case 1://マルチセレクト
					$selects = explode("\n", $value);
					$multiple = ($means == 0) ? '' : ' multiple';
					$multiple_array = ($means == 0) ? '' : '[]';
					$html .= '
						<td'.$class.'>
						<select name="'.$label.'['.esc_attr($key).']'.$multiple_array.'" id="'.$label.'['.esc_attr($key).']" class="iopt_select"'.$multiple.'>';
					if($essential == 1) 
						$html .= '
							<option value="#NONE#">'.__('Choose','usces').'</option>';
					foreach($selects as $v) {
						$selected = ($data == $v) ? ' selected' : '';
						$html .= '
							<option value="'.esc_attr($v).'"'.$selected.'>'.esc_html($v).'</option>';
					}
					$html .= '
						</select></td>';
					break;
				case 2://テキスト
					$html .= '
						<td'.$class.'><input type="text" name="'.$label.'['.esc_attr($key).']" id="'.$label.'['.esc_attr($key).']" size="30" value="'.esc_attr($data).'" /></td>';
					break;
				case 3://ラジオボタン
					$selects = explode("\n", $value);
					$html .= '
						<td'.$class.'>';
					foreach($selects as $v) {
						$checked = ($data == $v) ? ' checked' : '';
						$html .= '
						<input type="radio" name="'.$label.'['.esc_attr($key).']" value="'.esc_attr($v).'"'.$checked.'><label for="'.$label.'['.esc_attr($key).']['.esc_attr($v).']" class="iopt_label">'.esc_html($v).'</label>';
					}
					$html .= '
						</td>';
					break;
				case 4://チェックボックス
					$selects = explode("\n", $value);
					$html .= '
						<td'.$class.'>';
					foreach($selects as $v) {
						if(is_array($data)) {
							$checked = (array_key_exists($v, $data)) ? ' checked' : '';
						} else {
							$checked = ($data == $v) ? ' checked' : '';
						}
						$html .= '
						<input type="checkbox" name="'.$label.'['.esc_attr($key).']['.esc_attr($v).']" value="'.esc_attr($v).'"'.$checked.'><label for="'.$label.'['.esc_attr($key).']['.esc_attr($v).']" class="iopt_label">'.esc_html($v).'</label>';
					}
					$html .= '
						</td>';
					break;
				case 5://テキストエリア
					$html .= '
						<td'.$class.'><textarea name="'.$label.'['.esc_attr($key).']" id="'.$label.'['.esc_attr($key).']" >'.esc_attr($data).'</textarea></td>';
					break;
				}
				$html .= '
					</tr>';
			}
			
		}
	}
	$html = apply_filters( 'usces_filter_admin_custom_field_input', $html,  $meta, $custom_field, $position, $out );

	if($out == 'return') {
		return stripslashes($html);
	} else {
		echo stripslashes($html);
	}
}

function has_custom_customer_field_essential() {

	$mes = '';
	$essential = array();

	$csmb_meta = usces_has_custom_field_meta('member');
	if(!empty($csmb_meta) and is_array($csmb_meta)) {
		foreach($csmb_meta as $key => $entry) {
			if($entry['essential'] == 1) {
				$essential[$key] = $key;
			}
		}
	}
	if(!empty($essential)) {
		$cscs_meta = usces_has_custom_field_meta('customer');
		if(!empty($cscs_meta) and is_array($cscs_meta)) {
			foreach($cscs_meta as $key => $entry) {
				if($entry['essential'] == 1) {
					if(!array_key_exists($key, $essential)) {
						if($entry['means'] == 2) {//Text
							$mes .= sprintf(__("Input the %s", 'usces'), esc_html($entry['name']))."<br />";
						} else {
							$mes .= sprintf(__("Chose the %s", 'usces'), esc_html($entry['name']))."<br />";
						}
					}
				}
			}
		}
	}
	return $mes;
}

function usces_order_discount( $out = '' ){
	global $usces;
	$res = abs($usces->get_order_discount());
	
	if($out == 'return') {
		return $res;
	} else {
		echo number_format($res);
	}
}

function usces_item_discount( $out = '', $post_id = '', $sku = '' ){
	global $usces, $post;
	
	if( '' == $post_id )
		$post_id = $post->ID;
	if( '' == $sku )
		$sku = $usces->itemsku['code'];
		
	$res = $usces->getItemDiscount($post_id, $sku);
	
	if($out == 'return') {
		return $res;
	} else {
		echo number_format($res);
	}
}

function usces_singleitem_error_message($post_id, $skukey, $out = ''){
	if( !isset($_SESSION['usces_singleitem']['error_message'][$post_id][$skukey]) )
		$ret = '';
	else
		$ret = $_SESSION['usces_singleitem']['error_message'][$post_id][$skukey];
		
	if($out == 'return') {
		return $ret;
	} else {
		echo $ret;
	}
}

function usces_crform( $float, $symbol_pre = true, $symbol_post = true, $out = '', $seperator_flag = true ) {
	global $usces;
	$price = esc_html($usces->get_currency($float, $symbol_pre, $symbol_post, $seperator_flag ));
	$res = apply_filters('usces_filter_crform', $price, $float);
	
	if($out == 'return'){
		return $res;
	}else{
		echo $res;
	}
}

function usces_memberinfo( $key, $out = '' ){
	global $usces, $wpdb;
	$info = $usces->get_member();

	if( empty($key) ) return $info;
	
	switch ($key){
		case 'registered':
			$res = mysql2date(__('Mj, Y', 'usces'), $info['registered']);
			break;
		case 'point':
			$member_table = usces_get_tablename( 'usces_member' );
			$query = $wpdb->prepare("SELECT mem_point FROM $member_table WHERE ID = %d", $info['ID']);
			$res = $wpdb->get_var( $query );
			break;
		default:
			$res = isset($info[$key]) ? $info[$key] : '';
	}
	
	if($out == 'return'){
		return $res;
	}else{
		echo esc_html($res);
	}
}

function usces_member_history( $out = '' ){
	global $usces;
	
	$usces_members = $usces->get_member();
	$history = $usces->get_member_history($usces_members['ID']);
	$usces_member_history = apply_filters( 'usces_filter_get_member_history', $history, $usces_members['ID'] );

	$usces_member_history_count = ( $usces_member_history && is_array( $usces_member_history ) ) ? count( $usces_member_history ) : 0;

	$html = '<div class="history-area">';
	if ( 0 == $usces_member_history_count ) {
		$html .= '<table id="history_head"><tr>
		<td>' . __('There is no purchase history for this moment.', 'usces') . '</td>
		</tr></table>';
	} else {
		$management_status = apply_filters( 'usces_filter_management_status', get_option( 'usces_management_status' ) );
		foreach ( $usces_member_history as $umhs ) {
			$condition = $umhs['condition'];
			$tax_display = ( isset( $condition['tax_display'] ) ) ? $condition['tax_display'] : usces_get_tax_display();
			$tax_mode = ( isset( $condition['tax_mode'] ) ) ? $condition['tax_mode'] : usces_get_tax_mode();
			$tax_target = ( isset( $condition['tax_target'] ) ) ? $condition['tax_target'] : usces_get_tax_target();
			$tax_label = ( 'exclude' == $tax_mode ) ? __( 'consumption tax', 'usces' ) : __( 'Internal tax', 'usces' );
			$member_system_point = ( isset( $condition['membersystem_point'] ) && 'activate' == $condition['membersystem_point'] ) ? true : usces_is_membersystem_point();
			$point_coverage = ( isset( $condition['point_coverage'] ) ) ? $condition['point_coverage'] : usces_point_coverage();
			$cart = $umhs['cart'];
			$value = $umhs['order_status'];

			$p_status = '';
			if ( $usces->is_status( 'duringorder', $value ) ) {
				$p_status = isset( $management_status['duringorder'] ) ? esc_html( $management_status['duringorder'] ) : '';
			} elseif ( $usces->is_status( 'cancel', $value ) ) {
				$p_status = isset( $management_status['cancel'] ) ? esc_html( $management_status['cancel'] ) : '';
			} elseif ( $usces->is_status( 'completion', $value ) ) {
				$p_status = isset( $management_status['completion'] ) ? esc_html( $management_status['completion'] ) : '';
			} else {
				$p_status = esc_html( __( 'new order', 'usces' ) );
			}
			$p_status = apply_filters( 'usces_filter_orderlist_process_status', $p_status, $value, $management_status, $umhs['ID'] );

			$r_status = '';
			if ( $usces->is_status( 'noreceipt', $value ) ) {
				$r_status = isset( $management_status['noreceipt'] ) ? esc_html( $management_status['noreceipt'] ) : '';
			} elseif ( $usces->is_status( 'pending', $value ) ) {
				$r_status = isset( $management_status['pending'] ) ? esc_html( $management_status['pending'] ) : '';
			} elseif ( $usces->is_status( 'receipted', $value ) ) {
				$r_status = isset( $management_status['receipted'] ) ? esc_html( $management_status['receipted'] ) : '';
			}
			$r_status = apply_filters( 'usces_filter_orderlist_receipt_status', $r_status, $value, $management_status, $umhs['ID'] );

			$shipping = usces_have_shipped( $cart );
			$delivery_company = '';
			$tracking_number = '';
			$delivery_company_url = '';
			if ( $shipping ) {
				$tracking_number = $usces->get_order_meta_value( apply_filters( 'usces_filter_tracking_meta_key', 'tracking_number' ), $umhs['ID'] );
				$delivery_company = $usces->get_order_meta_value( 'delivery_company', $umhs['ID'] );
				$delivery_company_url = usces_get_delivery_company_url( $delivery_company, $tracking_number );
			}

			$history_member_head = '<table id="history_head"><thead>
				<tr class="order_head_label">
				<th class="historyrow order_number">' . __('Order number', 'usces') . '</th>
				<th class="historyrow purchase_date">' . __('Purchase date', 'usces') . '</th>';
			if ( ! empty( $p_status ) ) {
				$history_member_head .= '<th class="historyrow processing_status">' . __('Processing status', 'usces') . '</th>';
			}
			if ( ! empty( $r_status ) ) {
				$history_member_head .= '<th class="historyrow transfer_statement">' . __('transfer statement', 'usces') . '</th>';
			}
			$history_member_head .= '<th class="historyrow purchase_price">' . __('Purchase price', 'usces') . '</th>
				<th class="historyrow discount">' . apply_filters( 'usces_member_discount_label', __('Discount', 'usces'), $umhs['ID'] ) . '</th>';
			if ( $tax_display && 'products' == $tax_target ) {
				$history_member_head .= '<th class="historyrow tax">' . $tax_label . '</th>';
			}
			if ( $member_system_point && 0 == $point_coverage ) {
				$history_member_head .= '<th class="historyrow used_point">' . __('Used points', 'usces') . '</th>';
			}
			$history_member_head .= '<th class="historyrow shipping">' . __('Shipping', 'usces') . '</th>
				<th class="historyrow cod">' . apply_filters( 'usces_filter_member_history_cod_label', __('C.O.D', 'usces'), $umhs['ID'] ) . '</th>';
			if ( $tax_display && 'all' == $tax_target ) {
				$history_member_head .= '<th class="historyrow tax">' . $tax_label . '</th>';
			}
			if ( $member_system_point && 1 == $point_coverage ) {
				$history_member_head .= '<th class="historyrow used_point">' . __('Used points', 'usces') . '</th>';
			}
			if ( $member_system_point ) {
				$history_member_head .= '<th class="historyrow get_point">' . __('Acquired points', 'usces') . '</th>';
			}
			if( ! empty( $tracking_number ) ) {
				$history_member_head .= '<th class="historyrow">' . __('Tracking number', 'usces') . '</th>';
			}
			$total_price = $umhs['total_items_price']-$umhs['usedpoint']+$umhs['discount']+$umhs['shipping_charge']+$umhs['cod_fee']+$umhs['tax'];
			if( $total_price < 0 ) $total_price = 0;
			$history_member_head .= '</tr></thead>
				<tbody>
				<tr class="order_head_value">
				<td class="order_number">' . usces_get_deco_order_id($umhs['ID']) . '</td>
				<td class="date purchase_date">' . $umhs['date'] . '</td>';
			if ( ! empty( $p_status ) ) {
				$history_member_head .= '<td class="rightnum">' . $p_status . '</td>';
			}
			if ( ! empty( $r_status ) ) {
				$history_member_head .= '<td class="rightnum">' . $r_status . '</td>';
			}
			$history_member_head .= '<td class="rightnum purchase_price">' . usces_crform( $total_price, true, false, 'return' ) . '</td>
				<td class="rightnum discount">' . usces_crform($umhs['discount'], true, false, 'return') . '</td>';
			if ( $tax_display && 'products' == $tax_target ) {
				$history_member_head .= '<td class="rightnum tax">' . usces_order_history_tax( $umhs, $tax_mode ) . '</td>';
			}
			if ( $member_system_point && 0 == $point_coverage ) {
				$history_member_head .= '<td class="rightnum used_point">' . number_format($umhs['usedpoint']) . '</td>';
			}
			$history_member_head .= '<td class="rightnum shipping">' . usces_crform($umhs['shipping_charge'], true, false, 'return') . '</td>
				<td class="rightnum cod">' . usces_crform($umhs['cod_fee'], true, false, 'return') . '</td>';
			if ( $tax_display && 'all' == $tax_target ) {
				$history_member_head .= '<td class="rightnum tax">' . usces_order_history_tax( $umhs, $tax_mode ) . '</td>';
			}
			if ( $member_system_point && 1 == $point_coverage ) {
				$history_member_head .= '<td class="rightnum used_point">' . number_format($umhs['usedpoint']) . '</td>';
			}
			if ( $member_system_point ) {
				$history_member_head .= '<td class="rightnum get_point">' . number_format($umhs['getpoint']) . '</td>';
			}
			if( ! empty( $tracking_number ) ) {
				if( ! empty( $delivery_company_url ) ) {
					$history_member_head .= '<td><a href="' . esc_url( $delivery_company_url ) . '">' . esc_attr( $tracking_number ) . '</a></td>';
				} else {
					$history_member_head .= '<td>' . esc_attr( $tracking_number ) . '</td>';
				}
			}
			$history_member_head .= '</tr>';
			$html .= apply_filters( 'usces_filter_history_member_head', $history_member_head, $umhs );
			$html .= apply_filters('usces_filter_member_history_header', NULL, $umhs);
			$html .= '</tbody></table>
					<table id="retail_table_' . $umhs['ID'] . '" class="retail">';
			$history_cart_head = '<thead><tr>
					<th scope="row" class="cartrownum">No.</th>
					<th class="thumbnail">&nbsp;</th>
					<th class="productname">' . __('Items', 'usces') . '</th>
					<th class="price">' . __('Unit price', 'usces') . '</th>
					<th class="quantity">' . __('Quantity', 'usces') . '</th>
					<th class="subtotal">' . __('Amount', 'usces') . '</th>
					</tr></thead><tbody>';
			$html .= apply_filters('usces_filter_history_cart_head', $history_cart_head, $umhs);
			$cart_count = ( $cart && is_array( $cart ) ) ? count( $cart ) : 0;
			for($i=0; $i<$cart_count; $i++) { 
				$cart_row = $cart[$i];
				$ordercart_id = $cart_row['cart_id'];
				$post_id = $cart_row['post_id'];
				$sku = $cart_row['sku'];
				$sku_code = urldecode($cart_row['sku']);
				$quantity = $cart_row['quantity'];
				$options = ( !empty( $cart_row['options'] ) ) ? $cart_row['options'] : array();
				$itemCode = $cart_row['item_code'];
				$itemName = $cart_row['item_name'];
				$cartItemName = $usces->getCartItemName_byOrder($cart_row);
				$skuPrice = $cart_row['price'];
				$pictid = (int)$usces->get_mainpictid($itemCode);
				$optstr =  '';
				if( is_array($options) && count($options) > 0 ){
					foreach($options as $key => $value){
						if( !empty($key) ) {
							$key = urldecode($key);
							$value = maybe_unserialize($value);
							if(is_array($value)) {
								$c = '';
								$optstr .= esc_html($key) . ' : ';
								foreach($value as $v) {
									$optstr .= $c.nl2br(esc_html(rawurldecode($v)));
									$c = ', ';
								}
								$optstr .= "<br />\n";
							} else {
								$optstr .= esc_html($key) . ' : ' . nl2br(esc_html(rawurldecode($value))) . "<br />\n";
							}
						}
					}
					$optstr = apply_filters( 'usces_filter_option_history', $optstr, $options);
				}
				$optstr = apply_filters( 'usces_filter_option_info_history', $optstr, $umhs, $cart_row, $i );
				$args = compact('cart', 'i', 'cart_row', 'post_id', 'sku' );

				$cart_item_name = '<a href="' . get_permalink($post_id) . '">' . apply_filters('usces_filter_cart_item_name', esc_html($cartItemName), $args ) . '<br />' . $optstr . '</a>' . apply_filters('usces_filter_history_item_name', NULL, $umhs, $cart_row, $i);
				$cart_item_name = apply_filters( 'usces_filter_history_cart_item_name', $cart_item_name, $cartItemName, $optstr, $cart_row, $i, $umhs );

				$history_cart_row = '<tr>
					<td class="cartrownum">' . ($i + 1) . '</td>
					<td class="thumbnail">';
				$cart_thumbnail = '<a href="' . get_permalink($post_id) . '">' . wp_get_attachment_image( $pictid, array(60, 60), true ) . '</a>';
				$history_cart_row .= apply_filters('usces_filter_cart_thumbnail', $cart_thumbnail, $post_id, $pictid, $i, $cart_row);
				$history_cart_row .= '</td>
					<td class="aleft productname">' . $cart_item_name . '</td>
					<td class="rightnum price">' . usces_crform($skuPrice, true, false, 'return') . '</td>
					<td class="rightnum quantity">' . number_format($cart_row['quantity']) . '</td>
					<td class="rightnum subtotal">' . usces_crform($skuPrice * $cart_row['quantity'], true, false, 'return') . '</td>
					</tr>';
				$materials = compact( 'cart_thumbnail', 'post_id', 'pictid', 'cartItemName', 'optstr' );
				$html .= apply_filters( 'usces_filter_history_cart_row', $history_cart_row, $umhs, $cart_row, $i, $materials );
			}
			$html .= '</tbody></table>';
			$html .= apply_filters( 'usces_filter_member_history_row', '', $umhs, $cart );
		}
	}
	$html .= '</div>';
	$html = apply_filters( 'usces_filter_member_history', $html, $usces_member_history );

	if($out == 'return'){
		return $html;
	}else{
		echo $html;
	}
}

function usces_newmember_button($member_regmode){
	$html = '<input name="member_regmode" type="hidden" value="' . $member_regmode . '" />';
	$newmemberbutton = '<input name="regmember" type="submit" value="' . __('transmit a message', 'usces') . '" />';
	$html .= apply_filters('usces_filter_newmember_button', $newmemberbutton);
	echo $html;
}

function usces_login_button(){
	$loginbutton = '<input type="submit" name="member_login" id="member_login" class="member_login_button" value="' . __('Log-in', 'usces') . '" />';
	$html = apply_filters('usces_filter_login_button', $loginbutton);
	echo $html;
}

function usces_assistance_item($post_id, $title ){
	if (usces_get_assistance_id_list($post_id)) :
		global $post;
		$r = new WP_Query( array('post__in'=>usces_get_assistance_ids($post_id), 'ignore_sticky_posts'=>1) );
		if($r->have_posts()) :
		add_filter( 'excerpt_length', 'welcart_assistance_excerpt_length' );
		add_filter( 'excerpt_mblength', 'welcart_assistance_excerpt_mblength' );
		$width = apply_filters( 'usces_filter_assistance_item_width', 100 );
		$height = apply_filters( 'usces_filter_assistance_item_height', 100 );
?>
	<div class="assistance_item">
		<h3><?php echo $title; ?></h3>
		<ul class="clearfix">
<?php
		while ($r->have_posts()) :
			$r->the_post();
			usces_remove_filter();
			usces_the_item();
			ob_start();
?>
			<li>
			<div class="listbox clearfix">
				<div class="slit">
					<a href="<?php the_permalink(); ?>" rel="bookmark" title="<?php echo wp_filter_nohtml_kses(get_the_title()); ?>"><?php usces_the_itemImage(0, $width, $height, $post); ?></a>
				</div>
				<div class="detail">
					<div class="assist_excerpt">
					<a href="<?php the_permalink(); ?>" rel="bookmark" title="<?php echo wp_filter_nohtml_kses(get_the_title()); ?>"><h4><?php usces_the_itemName(); ?></h4></a>
					<?php the_excerpt(); ?>
					</div>
				<?php if (usces_is_skus()) : ?>
					<div class="assist_price">
						<?php usces_crform( usces_the_firstPrice('return'), true, false ); ?>
					</div>
					<?php usces_crform_the_itemPriceCr_taxincluded(); ?>
				<?php endif; ?>
				</div>
			</div>
			</li>
		<?php
			$list = ob_get_contents();
			ob_end_clean();
			echo apply_filters('usces_filter_assistance_item_list', $list, $post);
		 endwhile; ?>
		
		</ul>
	</div><!-- end of assistance_item -->
<?php 
		wp_reset_postdata();
		usces_reset_filter();
		remove_filter( 'excerpt_length', 'welcart_assistance_excerpt_length' );
		remove_filter( 'excerpt_mblength', 'welcart_assistance_excerpt_mblength' );
		endif;
	endif;
}

function usces_get_cart_rows( $out = '' ) {
	global $usces, $usces_gp;
	$cart = $usces->cart->get_cart();
	$usces_gp = 0;
	$cart_count = ( $cart && is_array( $cart ) ) ? count( $cart ) : 0;
	$res = '';

	for($i=0; $i<$cart_count; $i++) { 
		$cart_row = $cart[$i];
		$post_id = (int)$cart_row['post_id'];
		$sku = $cart_row['sku'];
		$sku_code = urldecode($cart_row['sku']);
		$quantity = $cart_row['quantity'];
		$options = ( !empty( $cart_row['options'] ) ) ? $cart_row['options'] : array();
		$advance = $cart_row['advance'];
		$itemCode = $usces->getItemCode($post_id);
		$itemName = $usces->getItemName($post_id);
		$cartItemName = $usces->getCartItemName($post_id, $sku_code);
		$itemRestriction = $usces->getItemRestriction($post_id);
		$itemOrderAcceptable = $usces->getItemOrderAcceptable( $post_id );
		$skuPrice = $cart_row['price'];
		$skuZaikonum = $usces->getItemZaikonum($post_id, $sku_code);
		$stockid = $usces->getItemZaikoStatusId($post_id, $sku_code);
		$stock = $usces->getItemZaiko($post_id, $sku_code);
		$red = ( 1 < $stockid ) ? 'class="signal_red stock"' : 'class="stock"';
		$pictid = (int)$usces->get_mainpictid($itemCode);
		$args = compact('cart', 'i', 'cart_row', 'post_id', 'sku' );
		$row = '';
		$row .= '<tr>
			<td class="num">' . ($i + 1) . '</td>
			<td class="thumbnail">';
			$cart_thumbnail = '<a href="' . get_permalink($post_id) . '">' . wp_get_attachment_image( $pictid, array(60, 60), true ) . '</a>';
			$row .= apply_filters('usces_filter_cart_thumbnail', $cart_thumbnail, $post_id, $pictid, $i,$cart_row);
			$row .= '</td>
			<td class="aleft productname">' . apply_filters('usces_filter_cart_item_name', esc_html($cartItemName), $args ) . '<br />';
		if( is_array($options) && count($options) > 0 ){
			$optstr = '';
			foreach($options as $key => $value){
				if( !empty($key) ) {
					$key = urldecode($key);
					if(is_array($value)) {
						$c = '';
						$optstr .= esc_html($key) . ' : ';
						foreach($value as $v) {
							$optstr .= $c.nl2br(esc_html(urldecode($v)));
							$c = ', ';
						}
						$optstr .= "<br />\n";
					} else {
						$optstr .= esc_html($key) . ' : ' . nl2br(esc_html(urldecode($value))) . "<br />\n";
					}
				}
			}
			$row .= apply_filters( 'usces_filter_option_cart', $optstr, $options, $args );
		}
		$row .= apply_filters( 'usces_filter_option_info_cart', '', $cart_row, $args );
		$row .= '</td>
			<td class="aright unitprice">';
		if( usces_is_gptekiyo($post_id, $sku_code, $quantity) ) {
			$usces_gp = 1;
			$gp_src = file_exists(get_template_directory() . '/images/gp.gif') ? get_template_directory_uri() . '/images/gp.gif' : USCES_PLUGIN_URL . '/images/gp.gif';
			$Business_pack_mark = '<img src="' . $gp_src . '" alt="' . __('Business package discount','usces') . '" /><br />';
			$row .= apply_filters('usces_filter_itemGpExp_cart_mark', $Business_pack_mark);
		}
		$row .= usces_crform($skuPrice, true, false, 'return') . '
			</td>
			<td class="quantity">';
		$row_quant = '<input name="quant[' . $i . '][' . $post_id . '][' . esc_attr($sku) . ']" class="quantity" type="text" value="' . esc_attr($cart_row['quantity']) . '" />';
		$row .= apply_filters( 'usces_filter_cart_rows_quant', $row_quant, $args );
		$row .= '</td>
			<td class="aright subtotal">' . usces_crform(($skuPrice * $cart_row['quantity']), true, false, 'return') . '</td>
			<td ' . $red . '>' . esc_html($stock) . '</td>
			<td class="action">';
		if( is_array($options) && count($options) > 0 ){
			foreach($options as $key => $value){
				if(is_array($value)) {
					foreach($value as $v) {
						$row .= '<input name="itemOption[' . $i . '][' . $post_id . '][' . esc_attr($sku) . '][' . esc_attr($key) . '][' . esc_attr($v) . ']" type="hidden" value="' . esc_attr($v) . '" />'."\n";
					}
				} else {
					$row .= '<input name="itemOption[' . $i . '][' . $post_id . '][' . esc_attr($sku) . '][' . esc_attr($key) . ']" type="hidden" value="' . esc_attr($value) . '" />'."\n";
				}
			}
		}
		$row .= '<input name="itemRestriction[' . $i . ']" type="hidden" value="' . esc_attr($itemRestriction) . '" />
			<input name="itemOrderAcceptable[' . $i . ']" type="hidden" value="' . $itemOrderAcceptable . '" />
			<input name="stockid[' . $i . ']" type="hidden" value="' . esc_attr($stockid) . '" />
			<input name="itempostid[' . $i . ']" type="hidden" value="' . esc_attr($post_id) . '" />
			<input name="itemsku[' . $i . ']" type="hidden" value="' . esc_attr($sku) . '" />
			<input name="zaikonum[' . $i . '][' . $post_id . '][' . esc_attr($sku) . ']" type="hidden" value="' . esc_attr($skuZaikonum) . '" />
			<input name="skuPrice[' . $i . '][' . $post_id . '][' . esc_attr($sku) . ']" type="hidden" value="' . esc_attr($skuPrice) . '" />
			<input name="advance[' . $i . '][' . $post_id . '][' . esc_attr($sku) . ']" type="hidden" value="' . esc_attr($advance) . '" />
			<input name="delButton[' . $i . '][' . $post_id . '][' . esc_attr($sku) . ']" class="delButton" type="submit" value="' . __('Delete','usces') . '" />
			</td>
		</tr>';
		$materials = compact('i', 'cart_row', 'post_id', 'sku', 'sku_code', 'quantity', 'options', 'advance', 
						'itemCode', 'itemName', 'cartItemName', 'itemRestriction', 'skuPrice', 'skuZaikonum', 
						'stockid', 'stock', 'red', 'pictid');
		$res .= apply_filters( 'usces_filter_cart_row', $row, $cart, $materials);
	}
	
	$res = apply_filters( 'usces_filter_cart_rows', $res, $cart);

	if($out == 'return'){
		return $res;
	}else{
		echo $res;
	}
}
function usces_get_confirm_rows( $out = '' ) {
	global $usces, $usces_members, $usces_entries;
	$memid = ( empty($usces_members['ID']) ) ? 999999999 : $usces_members['ID'];
	$usces->set_cart_fees( $usces_members, $usces_entries );
	$usces_entries = $usces->cart->get_entry();

	$cart = $usces->cart->get_cart();
	$cart_count = ( $cart && is_array( $cart ) ) ? count( $cart ) : 0;
	$res = '';

	for($i=0; $i<$cart_count; $i++) { 
		$cart_row = $cart[$i];
		$post_id = (int)$cart_row['post_id'];
		$sku = $cart_row['sku'];
		$sku_code = urldecode($cart_row['sku']);
		$quantity = $cart_row['quantity'];
		$options = ( !empty( $cart_row['options'] ) ) ? $cart_row['options'] : array();
		$advance = $cart_row['advance'];
		$itemCode = $usces->getItemCode($post_id);
		$itemName = $usces->getItemName($post_id);
		$cartItemName = $usces->getCartItemName($post_id, $sku_code);
		$skuPrice = $cart_row['price'];
		$pictid = $usces->get_mainpictid($itemCode);
		$args = compact('cart', 'i', 'cart_row', 'post_id', 'sku' );
		$row = '';
		$row .= '<tr>
			<td class="num">' . ($i + 1) . '</td>
			<td class="thumbnail">';
		$cart_thumbnail = wp_get_attachment_image( $pictid, array(60, 60), true );
		$row .= apply_filters('usces_filter_cart_thumbnail', $cart_thumbnail, $post_id, $pictid, $i, $cart_row);
		$row .= '</td><td class="productname">' . apply_filters('usces_filter_cart_item_name', esc_html($cartItemName), $args ) . '<br />';
		if( is_array($options) && count($options) > 0 ){
			$optstr = '';
			foreach($options as $key => $value){
				if( !empty($key) ) {
					$key = urldecode($key);
					if(is_array($value)) {
						$c = '';
						$optstr .= esc_html($key) . ' : ';
						foreach($value as $v) {
							$optstr .= $c.nl2br(esc_html(urldecode($v)));
							$c = ', ';
						}
						$optstr .= "<br />\n";
					} else {
						$optstr .= esc_html($key) . ' : ' . nl2br(esc_html(urldecode($value))) . "<br />\n";
					}
				}
			}
			$row .= apply_filters( 'usces_filter_option_confirm', $optstr, $options, $args );
		}
		$row .= apply_filters( 'usces_filter_option_info_confirm', '', $cart_row, $args );
		$row .= '</td>
			<td class="unitprice">' . esc_html(usces_crform($skuPrice, true, false, 'return')) . '</td>
			<td class="quantity">' . esc_html($cart_row['quantity']) . '</td>
			<td class="subtotal">' . esc_html(usces_crform(($skuPrice * $cart_row['quantity']), true, false, 'return')) . '</td>
			<td class="action">';
		$row .= apply_filters('usces_additional_confirm', '', array($i, $post_id, $sku_code));
		$row .= '</td>
		</tr>';
		
		$materials = compact('i', 'cart_row', 'post_id', 'sku', 'sku_code', 'quantity', 'options', 'advance', 
						'itemCode', 'itemName', 'cartItemName', 'skuPrice', 'pictid');
		$res .= apply_filters( 'usces_filter_confirm_row', $row, $cart, $materials);
	} 
	
	$res = apply_filters( 'usces_filter_confirm_rows', $res, $cart);

	if($out == 'return'){
		return $res;
	}else{
		echo $res;
	}
}

function uesces_addressform( $type, $data, $out = 'return' ){
	global $usces, $usces_settings;
	$options = get_option('usces');
	$form = $options['system']['addressform'];
	$nameform = $usces_settings['nameform'][$form];
	$applyform = usces_get_apply_addressform($form);
	$formtag = '';
	switch( $type ){
	case 'confirm':
	case 'member':
		$values =  $data;
		break;
	case 'customer':
	case 'delivery':
		$values = $data[$type];
		break;
	}
	$data['type'] = $type;
	$values['country'] = !empty($values['country']) ? $values['country'] : usces_get_local_addressform();
	$values = $usces->stripslashes_deep_post($values);
	$target_market_count = ( isset( $options['system']['target_market'] ) && is_array( $options['system']['target_market'] ) ) ? count( $options['system']['target_market'] ) : 1;

	if( 'confirm' == $type ){
	
		switch ($applyform){
		
		case 'JP':
			$formtag .= usces_custom_field_info($data, 'customer', 'name_pre', 'return');
			$formtag .= '<tr class="name-row member-name-row"><th>'.apply_filters( 'usces_filters_addressform_name_label', __('Full name', 'usces'), $type, $values, $applyform ).'</th><td>' . esc_html(sprintf(_x('%s', 'honorific', 'usces'), (esc_html($values['customer']['name1']) . ' ' . esc_html($values['customer']['name2'])) )) . '</td></tr>';
			$furigana = ( '' == (trim($values['customer']['name3']) . trim($values['customer']['name4'])) ) ? '' : sprintf(_x('%s', 'honorific', 'usces'), (esc_html($values['customer']['name3']) . ' ' . esc_html($values['customer']['name4'])) );
			$furigana_customer = '<tr class="furikana-row member-furikana-row"><th>'.__('furigana', 'usces').'</th><td>' . $furigana . '</td></tr>';
			$formtag .= apply_filters( 'usces_filter_furigana_confirm_customer', $furigana_customer, $type, $values );
			$formtag .= usces_custom_field_info($data, 'customer', 'name_after', 'return');
			$formtag .= '<tr class="zipcode-row member-zipcode-row"><th>'.__('Zip/Postal Code', 'usces').'</th><td>' . esc_html($values['customer']['zipcode']) . '</td></tr>';
			if( 1 < $target_market_count ){
				$customer_country = (!empty($usces_settings['country'][$values['customer']['country']])) ? $usces_settings['country'][$values['customer']['country']] : '';
				$formtag .= '<tr class="country-row member-country-row"><th>'.__('Country', 'usces').'</th><td>' . esc_html($customer_country) . '</td></tr>';
			}
			$customer_pref = ( $values['customer']['pref'] == __('-- Select --','usces') || $values['customer']['pref'] == '-- Select --' ) ? '' : $values['customer']['pref'];
			$formtag .= '
			<tr class="states-row member-states-row"><th>'.__('Province', 'usces').'</th><td>' . esc_html($customer_pref) . '</td></tr>
			<tr class="address1-row member-address1-row"><th>'.__('city', 'usces').'</th><td>' . esc_html($values['customer']['address1']) . '</td></tr>
			<tr class="address2-row member-address2-row"><th>'.__('numbers', 'usces').'</th><td>' . esc_html($values['customer']['address2']) . '</td></tr>
			<tr class="address3-row member-address3-row"><th>'.__('building name', 'usces').'</th><td>' . esc_html($values['customer']['address3']) . '</td></tr>
			<tr class="tel-row member-tel-row"><th>'.__('Phone number', 'usces').'</th><td>' . esc_html($values['customer']['tel']) . '</td></tr>
			<tr class="fax-row member-fax-row"><th>'.__('FAX number', 'usces').'</th><td>' . esc_html($values['customer']['fax']) . '</td></tr>';
			$formtag .= usces_custom_field_info($data, 'customer', 'fax_after', 'return');
			
			$shipping_address_info = '';
			if( isset($values['delivery']) ) {
				$shipping_address_info = '<tr class="ttl"><td colspan="2"><h3>'.__('Shipping address information', 'usces').'</h3></td></tr>';
				$shipping_address_info .= usces_custom_field_info($data, 'delivery', 'name_pre', 'return');
				$shipping_address_info .= '<tr class="name-row delivery-name-row"><th>'.apply_filters( 'usces_filters_addressform_name_label', __('Full name', 'usces'), $type, $values, $applyform ).'</th><td>' . sprintf(_x('%s', 'honorific', 'usces'), (esc_html($values['delivery']['name1']) . ' ' . esc_html($values['delivery']['name2'])) ) . '</td></tr>';
				$deli_furigana = ( '' == (trim($values['delivery']['name3']) . trim($values['delivery']['name4'])) ) ? '' : sprintf(_x('%s', 'honorific', 'usces'), (esc_html($values['delivery']['name3']) . ' ' . esc_html($values['delivery']['name4'])) );
				$furigana_delivery = '<tr class="furikana-row delivery-furikana-row"><th>'.__('furigana', 'usces').'</th><td>' . $deli_furigana . '</td></tr>';
				$shipping_address_info .= apply_filters( 'usces_filter_furigana_confirm_delivery', $furigana_delivery, $type, $values );
				$shipping_address_info .= usces_custom_field_info($values, 'delivery', 'name_after', 'return');
				$shipping_address_info .= '<tr class="zipcode-row delivery-zipcode-row"><th>'.__('Zip/Postal Code', 'usces').'</th><td>' . esc_html($values['delivery']['zipcode']) . '</td></tr>';
				if( 1 < $target_market_count ){
					$shipping_country = (!empty($usces_settings['country'][$values['delivery']['country']])) ? $usces_settings['country'][$values['delivery']['country']] : '';
					$shipping_address_info .= '<tr class="country-row delivery-country-row"><th>'.__('Country', 'usces').'</th><td>' . esc_html($shipping_country) . '</td></tr>';
				}
				$delivery_pref = ( $values['delivery']['pref'] == __('-- Select --','usces') || $values['delivery']['pref'] == '-- Select --' ) ? '' : $values['delivery']['pref'];
				$shipping_address_info .= '
				<tr class="states-row delivery-states-row"><th>'.__('Province', 'usces').'</th><td>' . esc_html($delivery_pref) . '</td></tr>
				<tr class="address1-row delivery-address1-row"><th>'.__('city', 'usces').'</th><td>' . esc_html($values['delivery']['address1']) . '</td></tr>
				<tr class="address2-row delivery-address2-row"><th>'.__('numbers', 'usces').'</th><td>' . esc_html($values['delivery']['address2']) . '</td></tr>
				<tr class="address3-row delivery-address3-row"><th>'.__('building name', 'usces').'</th><td>' . esc_html($values['delivery']['address3']) . '</td></tr>
				<tr class="tel-row delivery-tel-row"><th>'.__('Phone number', 'usces').'</th><td>' . esc_html($values['delivery']['tel']) . '</td></tr>
				<tr class="fax-row delivery-fax-row"><th>'.__('FAX number', 'usces').'</th><td>' . esc_html($values['delivery']['fax']) . '</td></tr>';
				$shipping_address_info .= usces_custom_field_info($data, 'delivery', 'fax_after', 'return');
			}
			$formtag .= apply_filters('usces_filter_shipping_address_info', $shipping_address_info);
			break;
			
		case 'CN':
			$formtag .= usces_custom_field_info($data, 'customer', 'name_pre', 'return');
			$formtag .= '<tr class="name-row member-name-row"><th>'.apply_filters( 'usces_filters_addressform_name_label', __('Full name', 'usces'), $type, $values, $applyform ).'</th><td>' . sprintf(_x('%s', 'honorific', 'usces'), esc_html(usces_localized_name( $values['customer']['name1'], $values['customer']['name2'], 'return' )) ) . '</td></tr>';
			$formtag .= usces_custom_field_info($data, 'customer', 'name_after', 'return');
			if( 1 < $target_market_count ){
				$customer_country = (!empty($usces_settings['country'][$values['customer']['country']])) ? $usces_settings['country'][$values['customer']['country']] : '';
				$formtag .= '<tr class="country-row member-country-row"><th>'.__('Country', 'usces').'</th><td>' . esc_html($customer_country) . '</td></tr>';
			}
			$customer_pref = ( $values['customer']['pref'] == __('-- Select --','usces') || $values['customer']['pref'] == '-- Select --' ) ? '' : $values['customer']['pref'];
			$formtag .= '
			<tr class="states-row member-states-row"><th>'.__('State', 'usces').'</th><td>' . esc_html($customer_pref) . '</td></tr>
			<tr class="address1-row member-address1-row"><th>'.__('city', 'usces').'</th><td>' . esc_html($values['customer']['address1']) . '</td></tr>
			<tr class="address2-row member-address2-row"><th>'.__('Address Line1', 'usces').'</th><td>' . esc_html($values['customer']['address2']) . '</td></tr>
			<tr class="address3-row member-address3-row"><th>'.__('Address Line2', 'usces').'</th><td>' . esc_html($values['customer']['address3']) . '</td></tr>
			<tr class="zipcode-row member-zipcode-row"><th>'.__('Zip', 'usces').'</th><td>' . esc_html($values['customer']['zipcode']) . '</td></tr>
			<tr class="tel-row member-tel-row"><th>'.__('Phone number', 'usces').'</th><td>' . esc_html($values['customer']['tel']) . '</td></tr>
			<tr class="fax-row member-fax-row"><th>'.__('FAX number', 'usces').'</th><td>' . esc_html($values['customer']['fax']) . '</td></tr>';
			$formtag .= usces_custom_field_info($data, 'customer', 'fax_after', 'return');
			
			$shipping_address_info = '';
			if( isset($values['delivery']) ) {
				$shipping_address_info = '<tr class="ttl"><td colspan="2"><h3>'.__('Shipping address information', 'usces').'</h3></td></tr>';
				$shipping_address_info .= usces_custom_field_info($data, 'delivery', 'name_pre', 'return');
				$shipping_address_info .= '<tr class="name-row delivery-name-row"><th>'.apply_filters( 'usces_filters_addressform_name_label', __('Full name', 'usces'), $type, $values, $applyform ).'</th><td>' . sprintf(_x('%s', 'honorific', 'usces'), esc_html(usces_localized_name( $values['delivery']['name1'], $values['delivery']['name2'], 'return' )) ) . '</td></tr>';
				$shipping_address_info .= usces_custom_field_info($data, 'delivery', 'name_after', 'return');
				if( 1 < $target_market_count ){
					$shipping_country = (!empty($usces_settings['country'][$values['delivery']['country']])) ? $usces_settings['country'][$values['delivery']['country']] : '';
					$shipping_address_info .= '<tr class="country-row delivery-country-row"><th>'.__('Country', 'usces').'</th><td>' . esc_html($shipping_country) . '</td></tr>';
				}
				$delivery_pref = ( $values['delivery']['pref'] == __('-- Select --','usces') || $values['delivery']['pref'] == '-- Select --' ) ? '' : $values['delivery']['pref'];
				$shipping_address_info .= '
				<tr class="states-row delivery-states-row"><th>'.__('State', 'usces').'</th><td>' . esc_html($delivery_pref) . '</td></tr>
				<tr class="address1-row delivery-address1-row"><th>'.__('city', 'usces').'</th><td>' . esc_html($values['delivery']['address1']) . '</td></tr>
				<tr class="address2-row delivery-address2-row"><th>'.__('Address Line1', 'usces').'</th><td>' . esc_html($values['delivery']['address2']) . '</td></tr>
				<tr class="address3-row delivery-address3-row"><th>'.__('Address Line2', 'usces').'</th><td>' . esc_html($values['delivery']['address3']) . '</td></tr>
				<tr class="zipcode-row delivery-zipcode-row"><th>'.__('Zip', 'usces').'</th><td>' . esc_html($values['delivery']['zipcode']) . '</td></tr>
				<tr class="tel-row delivery-tel-row"><th>'.__('Phone number', 'usces').'</th><td>' . esc_html($values['delivery']['tel']) . '</td></tr>
				<tr class="fax-row delivery-fax-row"><th>'.__('FAX number', 'usces').'</th><td>' . esc_html($values['delivery']['fax']) . '</td></tr>';
				$shipping_address_info .= usces_custom_field_info($data, 'delivery', 'fax_after', 'return');
			}
			$formtag .= apply_filters('usces_filter_shipping_address_info', $shipping_address_info);
			break;
			
		case 'US':
		default :
			$customer_pref = ( $values['customer']['pref'] == __('-- Select --','usces') || $values['customer']['pref'] == '-- Select --' ) ? '' : $values['customer']['pref'];
			$formtag .= usces_custom_field_info($data, 'customer', 'name_pre', 'return');
			$formtag .= '<tr class="name-row member-name-row"><th>'.apply_filters( 'usces_filters_addressform_name_label', __('Full name', 'usces'), $type, $values, $applyform ).'</th><td>' . sprintf(_x('%s', 'honorific', 'usces'), (esc_html($values['customer']['name2']) . ' ' . esc_html($values['customer']['name1'])) ) . '</td></tr>';
			$formtag .= usces_custom_field_info($data, 'customer', 'name_after', 'return');
			$formtag .= '
			<tr class="address2-row member-address2-row"><th>'.__('Address Line1', 'usces').'</th><td>' . esc_html($values['customer']['address2']) . '</td></tr>
			<tr class="address3-row member-address3-row"><th>'.__('Address Line2', 'usces').'</th><td>' . esc_html($values['customer']['address3']) . '</td></tr>
			<tr class="address1-row member-address1-row"><th>'.__('city', 'usces').'</th><td>' . esc_html($values['customer']['address1']) . '</td></tr>
			<tr class="states-row member-states-row"><th>'.__('State', 'usces').'</th><td>' . esc_html($customer_pref) . '</td></tr>';
			if( 1 < $target_market_count ){
				$customer_country = (!empty($usces_settings['country'][$values['customer']['country']])) ? $usces_settings['country'][$values['customer']['country']] : '';
				$formtag .= '<tr class="country-row member-country-row"><th>'.__('Country', 'usces').'</th><td>' . esc_html($customer_country) . '</td></tr>';
			}
			$formtag .= '
			<tr class="zipcode-row member-zipcode-row"><th>'.__('Zip', 'usces').'</th><td>' . esc_html($values['customer']['zipcode']) . '</td></tr>
			<tr class="tel-row member-tel-row"><th>'.__('Phone number', 'usces').'</th><td>' . esc_html($values['customer']['tel']) . '</td></tr>
			<tr class="fax-row member-fax-row"><th>'.__('FAX number', 'usces').'</th><td>' . esc_html($values['customer']['fax']) . '</td></tr>';
			$formtag .= usces_custom_field_info($data, 'customer', 'fax_after', 'return');
			
			$shipping_address_info = '';
			if( isset($values['delivery']) ) {
				$delivery_pref = ( $values['delivery']['pref'] == __('-- Select --','usces') || $values['delivery']['pref'] == '-- Select --' ) ? '' : $values['delivery']['pref'];
				$shipping_address_info = '<tr class="ttl"><td colspan="2"><h3>'.__('Shipping address information', 'usces').'</h3></td></tr>';
				$shipping_address_info .= usces_custom_field_info($data, 'delivery', 'name_pre', 'return');
				$shipping_address_info .= '<tr class="name-row delivery-name-row"><th>'.apply_filters( 'usces_filters_addressform_name_label', __('Full name', 'usces'), $type, $values, $applyform ).'</th><td>' . sprintf(_x('%s', 'honorific', 'usces'), (esc_html($values['delivery']['name2']) . ' ' . esc_html($values['delivery']['name1'])) ) . '</td></tr>';
				$shipping_address_info .= usces_custom_field_info($data, 'delivery', 'name_after', 'return');
				$shipping_address_info .= '
				<tr class="address2-row delivery-address2-row"><th>'.__('Address Line1', 'usces').'</th><td>' . esc_html($values['delivery']['address2']) . '</td></tr>
				<tr class="address3-row delivery-address3-row"><th>'.__('Address Line2', 'usces').'</th><td>' . esc_html($values['delivery']['address3']) . '</td></tr>
				<tr class="address1-row delivery-address1-row"><th>'.__('city', 'usces').'</th><td>' . esc_html($values['delivery']['address1']) . '</td></tr>
				<tr class="states-row delivery-states-row"><th>'.__('State', 'usces').'</th><td>' . esc_html($delivery_pref) . '</td></tr>';
				if( 1 < $target_market_count ){
					$shipping_country = (!empty($usces_settings['country'][$values['delivery']['country']])) ? $usces_settings['country'][$values['delivery']['country']] : '';
					$shipping_address_info .= '<tr class="country-row delivery-country-row"><th>'.__('Country', 'usces').'</th><td>' . esc_html($shipping_country) . '</td></tr>';
				}
				$shipping_address_info .= '
				<tr class="zipcode-row delivery-zipcode-row"><th>'.__('Zip', 'usces').'</th><td>' . esc_html($values['delivery']['zipcode']) . '</td></tr>
				<tr class="tel-row delivery-tel-row"><th>'.__('Phone number', 'usces').'</th><td>' . esc_html($values['delivery']['tel']) . '</td></tr>
				<tr class="fax-row delivery-fax-row"><th>'.__('FAX number', 'usces').'</th><td>' . esc_html($values['delivery']['fax']) . '</td></tr>';
				$shipping_address_info .= usces_custom_field_info($data, 'delivery', 'fax_after', 'return');
			}
			$formtag .= apply_filters('usces_filter_shipping_address_info', $shipping_address_info);
			break;
			
		}
		$res = apply_filters('usces_filter_apply_addressform_confirm', $formtag, $type, $data);
	
	}else{
	
		switch ($applyform){
		
		case 'JP':
			$formtag .= usces_custom_field_input($data, $type, 'name_pre', 'return');
			$formtag .= '<tr id="name_row" class="inp1">
			<th width="127" scope="row">' . usces_get_essential_mark('name1', $data) . apply_filters( 'usces_filters_addressform_name_label', __('Full name', 'usces'), $type, $values, $applyform ).'</th>';
			if( $nameform ){
				$formtag .= '<td class="name_td"><span class="member_name">' . __('Given name', 'usces') . '</span><input name="' . $type . '[name2]" id="name2" type="text" value="' . esc_attr( $values['name2'] ) . '" onKeyDown="if (event.keyCode == 13) {return false;}" style="ime-mode: active" /></td>';
				$formtag .= '<td class="name_td"><span class="member_name">' . __('Familly name', 'usces') . '</span><input name="' . $type . '[name1]" id="name1" type="text" value="' . esc_attr( $values['name1'] ) . '" onKeyDown="if (event.keyCode == 13) {return false;}" style="ime-mode: active" /></td>';
			}else{
				$formtag .= '<td class="name_td"><span class="member_name">' . __('Familly name', 'usces') . '</span><input name="' . $type . '[name1]" id="name1" type="text" value="' . esc_attr( $values['name1'] ) . '" onKeyDown="if (event.keyCode == 13) {return false;}" style="ime-mode: active" /></td>';
				$formtag .= '<td class="name_td"><span class="member_name">' . __('Given name', 'usces') . '</span><input name="' . $type . '[name2]" id="name2" type="text" value="' . esc_attr( $values['name2'] ) . '" onKeyDown="if (event.keyCode == 13) {return false;}" style="ime-mode: active" /></td>';
			}
			$formtag .= '</tr>';
			$furigana = '<tr id="furikana_row" class="inp1">
			<th scope="row">' . usces_get_essential_mark('name3', $data).__('furigana', 'usces').'</th>';
			if( $nameform ){
				$furigana .= '<td><span class="member_furigana">' . _x( 'Given name', 'furigana', 'usces' ) . '</span><input name="' . $type . '[name4]" id="name4" type="text" value="' . esc_attr( $values['name4'] ) . '" onKeyDown="if (event.keyCode == 13) {return false;}" style="ime-mode: active" /></td>';
				$furigana .= '<td><span class="member_furigana">' . _x( 'Familly name', 'furigana', 'usces' ) . '</span><input name="' . $type . '[name3]" id="name3" type="text" value="' . esc_attr( $values['name3'] ) . '" onKeyDown="if (event.keyCode == 13) {return false;}" style="ime-mode: active" /></td>';
			}else{
				$furigana .= '<td><span class="member_furigana">' . _x( 'Familly name', 'furigana', 'usces' ) . '</span><input name="' . $type . '[name3]" id="name3" type="text" value="' . esc_attr( $values['name3'] ) . '" onKeyDown="if (event.keyCode == 13) {return false;}" style="ime-mode: active" /></td>';
				$furigana .= '<td><span class="member_furigana">' . _x( 'Given name', 'furigana', 'usces' ) . '</span><input name="' . $type . '[name4]" id="name4" type="text" value="' . esc_attr( $values['name4'] ) . '" onKeyDown="if (event.keyCode == 13) {return false;}" style="ime-mode: active" /></td>';
			}
			$furigana .= '</tr>';
			$formtag .= apply_filters( 'usces_filter_furigana_form', $furigana, $type, $values );
			$formtag .= usces_custom_field_input($data, $type, 'name_after', 'return');
			$formtag .= '<tr id="zipcode_row">
			<th scope="row">' . usces_get_essential_mark('zipcode', $data).__('Zip/Postal Code', 'usces').'</th>
			<td colspan="2"><input name="' . $type . '[zipcode]" id="zipcode" type="text" value="' . esc_attr($values['zipcode']) . '" onKeyDown="if (event.keyCode == 13) {return false;}" style="ime-mode: inactive" />' . usces_postal_code_address_search( $type ) . apply_filters( 'usces_filter_addressform_zipcode', NULL, $type ) . apply_filters( 'usces_filter_after_zipcode', '100-1000', $applyform ) . '</td>
			</tr>';
			if( 1 < $target_market_count ){
				$formtag .= '<tr id="country_row">
				<th scope="row">' . usces_get_essential_mark('country', $data) . __('Country', 'usces') . '</th>
				<td colspan="2">' . uesces_get_target_market_form( $type, $values['country'] ) . apply_filters( 'usces_filter_after_country', NULL, $applyform ) . '</td>
				</tr>';
			}else{
				$formtag .= '<input type="hidden" name="' .$type. '[country]" id="' .$type. '_country" value="' .$options['system']['target_market'][0]. '">';
			}
			$formtag .= '<tr id="states_row">
			<th scope="row">' . usces_get_essential_mark('states', $data).__('Province', 'usces').'</th>
			<td colspan="2">' . usces_pref_select( $type, $values ) . apply_filters( 'usces_filter_after_states', NULL, $applyform ) . '</td>
			</tr>
			<tr id="address1_row" class="inp2">
			<th scope="row">' . usces_get_essential_mark('address1', $data).__('city', 'usces').'</th>
			<td colspan="2"><input name="' . $type . '[address1]" id="address1" type="text" value="' . esc_attr($values['address1']) . '" onKeyDown="if (event.keyCode == 13) {return false;}" style="ime-mode: active" />' . apply_filters( 'usces_filter_after_address1', __('Kitakami Yokohama', 'usces'), $applyform ) . '</td>
			</tr>
			<tr id="address2_row">
			<th scope="row">' . usces_get_essential_mark('address2', $data).__('numbers', 'usces').'</th>
			<td colspan="2"><input name="' . $type . '[address2]" id="address2" type="text" value="' . esc_attr($values['address2']) . '" onKeyDown="if (event.keyCode == 13) {return false;}" style="ime-mode: active" />' . apply_filters( 'usces_filter_after_address2', '3-24-555', $applyform ) . '</td>
			</tr>
			<tr id="address3_row">
			<th scope="row">' . usces_get_essential_mark('address3', $data).__('building name', 'usces').'</th>
			<td colspan="2"><input name="' . $type . '[address3]" id="address3" type="text" value="' . esc_attr($values['address3']) . '" onKeyDown="if (event.keyCode == 13) {return false;}" style="ime-mode: active" />' . apply_filters( 'usces_filter_after_address3', __('tuhanbuild 4F', 'usces'), $applyform ) . '</td>
			</tr>
			<tr id="tel_row">
			<th scope="row">' . usces_get_essential_mark('tel', $data).__('Phone number', 'usces').'</th>
			<td colspan="2"><input name="' . $type . '[tel]" id="tel" type="text" value="' . esc_attr($values['tel']) . '" onKeyDown="if (event.keyCode == 13) {return false;}" style="ime-mode: inactive" />' . apply_filters( 'usces_filter_after_tel', '1000-10-1000', $applyform ) . '</td>
			</tr>
			<tr id="fax_row">
			<th scope="row">' . usces_get_essential_mark('fax', $data).__('FAX number', 'usces').'</th>
			<td colspan="2"><input name="' . $type . '[fax]" id="fax" type="text" value="' . esc_attr($values['fax']) . '" onKeyDown="if (event.keyCode == 13) {return false;}" style="ime-mode: inactive" />' . apply_filters( 'usces_filter_after_fax', '1000-10-1000', $applyform ) . '</td>
			</tr>';
			$formtag .= usces_custom_field_input($data, $type, 'fax_after', 'return');
			break;
			
		case 'CN':
			$formtag .= usces_custom_field_input($data, $type, 'name_pre', 'return');
			$formtag .= '<tr id="name_row" class="inp1">
			<th scope="row">' . usces_get_essential_mark('name1', $data) . apply_filters( 'usces_filters_addressform_name_label', __('Full name', 'usces'), $type, $values, $applyform ) . '</th>';
			if( $nameform ){
				$formtag .= '<td>' . __('Given name', 'usces') . '<input name="' . $type . '[name2]" id="name2" type="text" value="' . esc_attr($values['name2']) . '" onKeyDown="if (event.keyCode == 13) {return false;}" /></td>';
				$formtag .= '<td>' . __('Familly name', 'usces') . '<input name="' . $type . '[name1]" id="name1" type="text" value="' . esc_attr($values['name1']) . '" onKeyDown="if (event.keyCode == 13) {return false;}" /></td>';
			}else{
				$formtag .= '<td>' . __('Familly name', 'usces') . '<input name="' . $type . '[name1]" id="name1" type="text" value="' . esc_attr($values['name1']) . '" onKeyDown="if (event.keyCode == 13) {return false;}" /></td>';
				$formtag .= '<td>' . __('Given name', 'usces') . '<input name="' . $type . '[name2]" id="name2" type="text" value="' . esc_attr($values['name2']) . '" onKeyDown="if (event.keyCode == 13) {return false;}" /></td>';
			}
			$formtag .= '</tr>';
			$formtag .= usces_custom_field_input($data, $type, 'name_after', 'return');
			if( 1 < $target_market_count ){
				$formtag .= '<tr id="country_row">
				<th scope="row">' . usces_get_essential_mark('country', $data) . __('Country', 'usces') . '</th>
				<td colspan="2">' . uesces_get_target_market_form( $type, $values['country'] ) . apply_filters( 'usces_filter_after_country', NULL, $applyform ) . '</td>
				</tr>';
			}else{
				$formtag .= '<input type="hidden" name="' .$type. '[country]" id="' .$type. '_country" value="' .$options['system']['target_market'][0]. '">';
			}
			$formtag .= '<tr id="states_row">
			<th scope="row">' . usces_get_essential_mark('states', $data) . __('State', 'usces') . '</th>
			<td colspan="2">' . usces_pref_select( $type, $values ) . apply_filters( 'usces_filter_after_states', NULL, $applyform ) . '</td>
			</tr>
			<tr id="address1_row" class="inp2">
			<th scope="row">' . usces_get_essential_mark('address1', $data) . __('city', 'usces') . '</th>
			<td colspan="2"><input name="' . $type . '[address1]" id="address1" type="text" value="' . esc_attr($values['address1']) . '" onKeyDown="if (event.keyCode == 13) {return false;}" />' . apply_filters( 'usces_filter_after_address1', NULL, $applyform ) . '</td>
			</tr>
			<tr id="address2_row">
			<th scope="row">' . usces_get_essential_mark('address2', $data) . __('Address Line1', 'usces') . '</th>
			<td colspan="2">' . __('Street address', 'usces') . '<br /><input name="' . $type . '[address2]" id="address2" type="text" value="' . esc_attr($values['address2']) . '" onKeyDown="if (event.keyCode == 13) {return false;}" />' . apply_filters( 'usces_filter_after_address2', NULL, $applyform ) . '</td>
			</tr>
			<tr id="address3_row">
			<th scope="row">' . usces_get_essential_mark('address3', $data) . __('Address Line2', 'usces') . '</th>
			<td colspan="2">' . __('Apartment, building, etc.', 'usces') . '<br /><input name="' . $type . '[address3]" id="address3" type="text" value="' . esc_attr($values['address3']) . '" onKeyDown="if (event.keyCode == 13) {return false;}" />' . apply_filters( 'usces_filter_after_address3', NULL, $applyform ) . '</td>
			</tr>
			<tr id="zipcode_row">
			<th scope="row">' . usces_get_essential_mark('zipcode', $data) . __('Zip', 'usces') . '</th>
			<td colspan="2"><input name="' . $type . '[zipcode]" id="zipcode" type="text" value="' . esc_attr($values['zipcode']) . '" onKeyDown="if (event.keyCode == 13) {return false;}" />' . apply_filters( 'usces_filter_after_zipcode', NULL, $applyform ) . '</td>
			</tr>
			<tr id="tel_row">
			<th scope="row">' . usces_get_essential_mark('tel', $data) . __('Phone number', 'usces') . '</th>
			<td colspan="2"><input name="' . $type . '[tel]" id="tel" type="text" value="' . esc_attr($values['tel']) . '" onKeyDown="if (event.keyCode == 13) {return false;}" />' . apply_filters( 'usces_filter_after_tel', NULL, $applyform ) . '</td>
			</tr>
			<tr id="fax_row">
			<th scope="row">' . usces_get_essential_mark('fax', $data) . __('FAX number', 'usces') . '</th>
			<td colspan="2"><input name="' . $type . '[fax]" id="fax" type="text" value="' . esc_attr($values['fax']) . '" onKeyDown="if (event.keyCode == 13) {return false;}" />' . apply_filters( 'usces_filter_after_fax', NULL, $applyform ) . '</td>
			</tr>';
			$formtag .= usces_custom_field_input($data, $type, 'fax_after', 'return');
			break;
			
		case 'US':
		default :
			$formtag .= usces_custom_field_input($data, $type, 'name_pre', 'return');
			$formtag .= '<tr id="name_row" class="inp1">
			<th scope="row">' . usces_get_essential_mark('name1', $data) . apply_filters( 'usces_filters_addressform_name_label', __('Full name', 'usces'), $type, $values, $applyform ) . '</th>';
			if( $nameform ){
				$formtag .= '<td>' . __('Given name', 'usces') . '<input name="' . $type . '[name2]" id="name2" type="text" value="' . esc_attr($values['name2']) . '" onKeyDown="if (event.keyCode == 13) {return false;}" /></td>';
				$formtag .= '<td>' . __('Familly name', 'usces') . '<input name="' . $type . '[name1]" id="name1" type="text" value="' . esc_attr($values['name1']) . '" onKeyDown="if (event.keyCode == 13) {return false;}" /></td>';
			}else{
				$formtag .= '<td>' . __('Familly name', 'usces') . '<input name="' . $type . '[name1]" id="name1" type="text" value="' . esc_attr($values['name1']) . '" onKeyDown="if (event.keyCode == 13) {return false;}" /></td>';
				$formtag .= '<td>' . __('Given name', 'usces') . '<input name="' . $type . '[name2]" id="name2" type="text" value="' . esc_attr($values['name2']) . '" onKeyDown="if (event.keyCode == 13) {return false;}" /></td>';
			}
			$formtag .= '</tr>';
			$formtag .= usces_custom_field_input($data, $type, 'name_after', 'return');
			$formtag .= '
			<tr id="address2_row">
			<th scope="row">' . usces_get_essential_mark('address2', $data) . __('Address Line1', 'usces') . '</th>
			<td colspan="2">' . __('Street address', 'usces') . '<br /><input name="' . $type . '[address2]" id="address2" type="text" value="' . esc_attr($values['address2']) . '" onKeyDown="if (event.keyCode == 13) {return false;}" />' . apply_filters( 'usces_filter_after_address2', NULL, $applyform ) . '</td>
			</tr>
			<tr id="address3_row">
			<th scope="row">' . usces_get_essential_mark('address3', $data) . __('Address Line2', 'usces') . '</th>
			<td colspan="2">' . __('Apartment, building, etc.', 'usces') . '<br /><input name="' . $type . '[address3]" id="address3" type="text" value="' . esc_attr($values['address3']) . '" onKeyDown="if (event.keyCode == 13) {return false;}" />' . apply_filters( 'usces_filter_after_address3', NULL, $applyform ) . '</td>
			</tr>
			<tr id="address1_row" class="inp2">
			<th scope="row">' . usces_get_essential_mark('address1', $data) . __('city', 'usces') . '</th>
			<td colspan="2"><input name="' . $type . '[address1]" id="address1" type="text" value="' . esc_attr($values['address1']) . '" onKeyDown="if (event.keyCode == 13) {return false;}" />' . apply_filters( 'usces_filter_after_address1', NULL, $applyform ) . '</td>
			</tr>
			<tr id="states_row">
			<th scope="row">' . usces_get_essential_mark('states', $data) . __('State', 'usces') . '</th>
			<td colspan="2">' . usces_pref_select( $type, $values ) . apply_filters( 'usces_filter_after_states', NULL, $applyform ) . '</td>
			</tr>';
			if( 1 < $target_market_count ){
				$formtag .= '<tr id="country_row">
				<th scope="row">' . usces_get_essential_mark('country', $data) . __('Country', 'usces') . '</th>
				<td colspan="2">' . uesces_get_target_market_form( $type, $values['country'] ) . apply_filters( 'usces_filter_after_country', NULL, $applyform ) . '</td>
				</tr>';
			}else{
				$formtag .= '<input type="hidden" name="' .$type. '[country]" id="' .$type. '_country" value="' .esc_attr($options['system']['target_market'][0]). '">';
			}
			$formtag .= '<tr id="zipcode_row">
			<th scope="row">' . usces_get_essential_mark('zipcode', $data) . __('Zip', 'usces') . '</th>
			<td colspan="2"><input name="' . $type . '[zipcode]" id="zipcode" type="text" value="' . esc_attr($values['zipcode']) . '" onKeyDown="if (event.keyCode == 13) {return false;}"  />' . apply_filters( 'usces_filter_after_zipcode', NULL, $applyform ) . '</td>
			</tr>
			<tr id="tel_row">
			<th scope="row">' . usces_get_essential_mark('tel', $data) . __('Phone number', 'usces') . '</th>
			<td colspan="2"><input name="' . $type . '[tel]" id="tel" type="text" value="' . esc_attr($values['tel']) . '" onKeyDown="if (event.keyCode == 13) {return false;}" />' . apply_filters( 'usces_filter_after_tel', NULL, $applyform ) . '</td>
			</tr>
			<tr id="fax_row">
			<th scope="row">' . usces_get_essential_mark('fax', $data) . __('FAX number', 'usces') . '</th>
			<td colspan="2"><input name="' . $type . '[fax]" id="fax" type="text" value="' . esc_attr($values['fax']) . '" onKeyDown="if (event.keyCode == 13) {return false;}" />' . apply_filters( 'usces_filter_after_fax', NULL, $applyform ) . '</td>
			</tr>';
			$formtag .= usces_custom_field_input($data, $type, 'fax_after', 'return');
			break;
		}
		$res = apply_filters('usces_filter_apply_addressform', $formtag, $type, $data);
	
	}

	if($out == 'return') {
		return $res;
	} else {
		echo $res;
	}
}

function usces_item_option_fileds( $post_id, $sku, $label = 1, $out = 'echo' ) {
	$options = wel_get_opts( $post_id, 'sort' );
	if ( ! $options || ! is_array( $options ) ) {
		return false;
	}
	if ( 0 === count( $options ) ) {
		return false;
	}
	$sku_enc = urlencode( $sku );

	$html ='';
	foreach ( $options as $opt ) {
		$name     = $opt['name'];
		$opt_code = urlencode( $name );
		$html .= '<div class="opt_field" id="opt_' . $post_id . '_' . $sku_enc . '_' . $opt_code . '">';
		if ( $label ) {
			$html .= '<label for="itemOption[' . $post_id . '][' . $sku_enc . '][' . $opt_code . ']">' . esc_html( $name ) . '</label>';
		}
		$html .= usces_get_itemopt_filed( $post_id, $sku, $opt );
		$html .= '</div>';
	}
	if ( $out === 'return' ) {
		return $html;
	} else {
		echo $html;
	}
}

function usces_facebook_like(){
	global $post, $usces;
	$like = array(
			'url' => urlencode(get_permalink($post->ID)),
			'send' => 'false',
			'layout' => 'button_count', //standard, button_count, box_count
			'width' => '450',
			'height' => '35',
			'show_faces' => 'false',
			'action' => 'like', //like, recommend
	);
	$like = apply_filters( 'usces_filter_facebook_like', $like, $post->ID );
?>
<iframe src="//www.facebook.com/plugins/like.php?href=<?php echo $like['url']; ?>&amp;send=<?php echo $like['send']; ?>&amp;layout=<?php echo $like['layout']; ?>&amp;width=<?php echo $like['width']; ?>&amp;show_faces=<?php echo $like['show_faces']; ?>&amp;action=<?php echo $like['action']; ?>&amp;colorscheme=light&amp;font=arial&amp;height=<?php echo $like['height']; ?>" scrolling="no" frameborder="0" style="border:none; overflow:hidden; width:<?php echo $like['width']; ?>px; height:<?php echo $like['height']; ?>px;" allowTransparency="true"></iframe>
<?php
}

function usces_checked( $chk, $key, $out = '' ) {
	$checked = ( isset($chk[$key]) and $chk[$key] == 1 ) ? ' checked' : '';
	if( $out == 'return' ) {
		return $checked;
	} else {
		echo $checked;
	}
}

function usces_get_custom_field_value( $field, $key, $id, $out = '' ) {
	global $usces;

	$value = '';
	switch( $field ) {
	case 'order':
		$value = $usces->get_order_meta_value( 'csod_'.$key, $id );
		break;
	case 'customer':
		$value = $usces->get_order_meta_value( 'cscs_'.$key, $id );
		break;
	case 'delivery':
		$value = $usces->get_order_meta_value( 'csde_'.$key, $id );
		break;
	case 'member':
		$value = $usces->get_member_meta_value( 'csmb_'.$key, $id );
		break;
	}

	if( $out == 'return' ) {
		return $value;
	} else {
		echo esc_html($value);
	}
}

function usces_postal_code_address_search( $type, $out = 'return' ) {
	global $usces;
	$html = '';
	if( isset($usces->options['address_search']) && $usces->options['address_search'] == 'activate' ) {
		$address_search_label = apply_filters( 'usces_filter_postal_code_address_search_label', '住所検索' );
		$html = '<input type="button" id="search_zipcode" class="search-zipcode button" value="'.$address_search_label.'" onClick="AjaxZip3.zip2addr(\''.esc_js($type.'[zipcode]').'\', \'\', \''.esc_js($type.'[pref]').'\', \''.esc_js($type.'[address1]').'\');" >';
		if( 'delivery' == $type ) {
			$entry = $usces->cart->get_entry();
			$zipcode = ( isset($entry['delivery']['zipcode']) ) ? $entry['delivery']['zipcode'] : '';
			$html .= '<input type="hidden" id="search_zipcode_change" value="'.esc_attr($zipcode).'">';
		}
	}
	if( $out == 'return' ) {
		return $html;
	} else {
		echo $html;
	}
}

function usces_agree_member_field($out = ''){
	global $usces;

	$html = '';
	if ( usces_is_membersystem_state() ) {
		$row = '';
		if( isset( $usces->options['agree_member']) && 'activate' === $usces->options['agree_member'] ){
			$row .= '<div class="agree_member_area">';
			if( isset( $usces->options['member_page_data']['agree_member_exp'] ) ){
				$row .= '<div class="at_exp_text">'. $usces->options['member_page_data']['agree_member_exp'] .'</div>';
			}
			if( isset( $usces->options['member_page_data']['agree_member_cont'] ) ){
				$row .= '<textarea name="at_cont_text" class="at_cont_text" readonly="readonly">'. $usces->options['member_page_data']['agree_member_cont'] .'</textarea>
						<div class="at_check_area"><input name="agree_member_check" value="1" id="agree_member_check" class="at_check" type="checkbox"><label for="agree_member_check" style="cursor:pointer;"> '. __('Accept the membership agreement', 'usces') .'</label></div>';
			}
			$row .= '</div>';
		}
		$html = apply_filters('usces_filter_agree_member_field', $row);
	}

	if( $out == 'return' ) {
		return $html;
	} else {
		echo $html;
	}
}

function usces_get_attachment_noimage( $size = array(60, 60), $alt = '' ) {
	$size_class = join( 'x', $size );
	return '<img width="'.$size[0].'" height="'.$size[1].'" src="'.USCES_PLUGIN_URL.'/images/default.png" class="attachment-'.$size_class.' size-'.$size_class.'" alt="'.$alt.'">';
}

function usces_the_taxrate( $out = '' ) {
	global $usces;

	if( isset( $usces->itemsku['taxrate'] ) ) {
		$taxrate = ( 'reduced' == $usces->itemsku['taxrate'] ) ? $usces->options['tax_rate_reduced'] : $usces->options['tax_rate'];
	} else {
		$taxrate = $usces->options['tax_rate'];
	}
	$the_taxrate = '<em class="tax">'.sprintf( __( "Tax Rate %s%%", 'usces' ), $taxrate ).'</em>';
	$the_taxrate = apply_filters( 'usces_filter_the_taxrate', $the_taxrate, $usces->itemsku, $out );

	if( $out == 'return' ) {
		return $the_taxrate;
	} else {
		echo $the_taxrate;
	}
}

function usces_cart_tax( $out = '' ) {
	global $usces;

	$total_items_price = $usces->get_total_price();
	$materials = array(
		'total_items_price' => $total_items_price,
		'discount' => 0,
		'shipping_charge' => 0,
		'cod_fee' => 0,
		'use_point' => 0,
	);
	$tax = $usces->getTax( $total_items_price, $materials );

	if( $out == 'return' ) {
		return $tax;
	} else {
		echo $tax;
	}
}

function usces_confirm_tax( $out = '' ) {
	global $usces;

	if( $usces->is_reduced_taxrate() ) {
		if( 'include' == $usces->options['tax_mode'] ) {
			$po = '(';
			$pc = ')';
		} else {
			$po = '';
			$pc = '';
		}
		$usces_tax = Welcart_Tax::get_instance();
		$usces_tax->get_order_tax();
		$tax  = sprintf( __( "Applies to %s%%", 'usces' ), $usces->options['tax_rate'] ).'&nbsp;&nbsp;'.usces_crform( $usces_tax->subtotal_standard + $usces_tax->discount_standard, true, false, 'return' ).'&nbsp;&nbsp;';
		$tax .= sprintf( __( "%s%% consumption tax", 'usces' ), $usces->options['tax_rate'] ).'&nbsp;&nbsp;'.$po.usces_crform( $usces_tax->tax_standard, true, false, 'return' ).$pc.'<br />';
		$tax .= sprintf( __( "Applies to %s%%", 'usces' ), $usces->options['tax_rate_reduced'] ).'&nbsp;&nbsp;'.usces_crform( $usces_tax->subtotal_reduced + $usces_tax->discount_reduced, true, false, 'return' ).'&nbsp;&nbsp;';
		$tax .= sprintf( __( "%s%% consumption tax", 'usces' ), $usces->options['tax_rate_reduced'] ).'&nbsp;&nbsp;'.$po.usces_crform( $usces_tax->tax_reduced, true, false, 'return' ).$pc;
	} else {
		$tax  = '';
	}

	$tax = apply_filters( 'usces_filter_confirm_tax', $tax, $out );

	if( $out == 'return' ) {
		return $tax;
	} else {
		echo $tax;
	}
}

function usces_the_itemPrice_taxincluded( $out = '' ) {
	global $post, $usces;
	$usces_tax = Welcart_Tax::get_instance();

	if ( empty( $usces->itemsku['code'] ) ) {
		$skus = $usces->get_skus( $post->ID );
		$skucode = $skus[0]['code'];
		$skuprice = $skus[0]['price'];
	} else {
		$skucode = $usces->itemsku['code'];
		$skuprice = $usces->itemsku['price'];
	}
	$tax_rate = $usces_tax->get_sku_tax_rate( $post->ID, $skucode );
	$tax = (float) sprintf( '%.3f', (float) $skuprice * (float) $tax_rate / 100 );
	$tax = usces_tax_rounding_off( $tax );
	$price = $skuprice + $tax;

	if ( $out == 'return' ) {
		return $price;
	} else {
		echo number_format( $price );
	}
}

function usces_the_itemCprice_taxincluded( $out = '' ) {
	global $post, $usces;
	$usces_tax = Welcart_Tax::get_instance();

	if ( empty( $usces->itemsku['code'] ) ) {
		$skus = $usces->get_skus( $post->ID );
		$skucode = $skus[0]['code'];
		$skucprice = $skus[0]['cprice'];
	} else {
		$skucode = $usces->itemsku['code'];
		$skucprice = $usces->itemsku['cprice'];
	}
	$tax_rate = $usces_tax->get_sku_tax_rate( $post->ID, $skucode );
	$tax = (float) sprintf( '%.3f', (float) $skucprice * (float) $tax_rate / 100 );
	$tax = usces_tax_rounding_off( $tax );
	$price = $skucprice + $tax;

	if ( $out == 'return' ) {
		return $price;
	} else {
		echo number_format( $price );
	}
}

function usces_the_itemPriceCr_taxincluded( $out = '' ) {
	global $usces;
	$usces_tax = Welcart_Tax::get_instance();

	$price_taxincluded = usces_the_itemPrice_taxincluded( 'return' );
	$price = $usces->get_currency( $price_taxincluded, true, false );
	$price = apply_filters( 'usces_filter_the_item_price_cr_taxincluded', $price, $price_taxincluded, $out );
	if( $out == 'return' ) {
		return $price;
	} else {
		echo esc_html( $price );
	}
}

function usces_the_itemCpriceCr_taxincluded( $out = '' ) {
	global $usces;
	$usces_tax = Welcart_Tax::get_instance();

	$price_taxincluded = usces_the_itemCprice_taxincluded( 'return' );
	$price = $usces->get_currency( $price_taxincluded, true, false );
	$price = apply_filters( 'usces_filter_the_item_cprice_cr_taxincluded', $price, $price_taxincluded, $out );
	if( $out == 'return' ) {
		return $price;
	} else {
		echo esc_html( $price );
	}
}

function usces_password_policy_message($out = ''){
	$usces_options = get_option('usces');
	
	$rules = array();
	$sep = '';

	if (!empty($usces_options['system']['member_pass_rule_max'])){
		if($usces_options['system']['member_pass_rule_max'] === $usces_options['system']['member_pass_rule_min']) {
			$rules[] = sprintf( __( '%s characters long', 'usces' ), $usces_options['system']['member_pass_rule_min']);
		} else {
			$rules[] = sprintf( __( '%1$s characters and no more than %2$s characters', 'usces' ), $usces_options['system']['member_pass_rule_min'], $usces_options['system']['member_pass_rule_max']);
			$sep = __('use', 'usces');
		}
	} else {
		// $rules[] = sprintf( __( '%s characters or more', 'usces' ), $usces_options['system']['member_pass_rule_min']);
		$rules[] = sprintf( __( '%1$s characters and no more than %2$s characters', 'usces' ), $usces_options['system']['member_pass_rule_min'], '30' );
		$sep = __('use', 'usces');
	}

	$and = __('and', 'usces');

	if ( ! empty( $usces_options['system']['member_pass_rule_digit'] ) ) {
		$rules[] = __( "numeric character", 'usces' );
	}

	if ( ! empty( $usces_options['system']['member_pass_rule_lowercase'] ) ) {
		$rules[] = __( "lower-case alphabetics", 'usces' );
	}

	if ( ! empty( $usces_options['system']['member_pass_rule_upercase'] ) ) {
		$rules[] = __( "upper-case alphabetics", 'usces' );
	}

	if ( ! empty( $usces_options['system']['member_pass_rule_symbol'] ) ) {
		$rules[] = __( "symbolic character", 'usces' );
	}

	$first = array_shift($rules);
	$ret = '';
	if (count($rules) == 0) {
		$ret = sprintf( __( 'Password must be at least %s.', 'usces' ), $first );
	} else if (count($rules) == 1) {
		$rule1 = sprintf( __( ' and include one or more %s', 'usces' ), $rules[0] );
		$ret = sprintf( __( 'Password must be at least %1$s%2$s.', 'usces' ), $first, $rule1 );
	} else {
		$rule2 = '';
		foreach ( $rules as $rule ) {
			if ( '' != $rule2 ) {
				$rule2 .= __( ',', 'usces' );
			}
			$rule2 .= $rule;
		}
		$rule1 = sprintf( __( ' and include one or more %s', 'usces' ), $rule2 );
		$ret = sprintf( __( 'Password must be at least %1$s%2$s.', 'usces' ), $first, $rule1 );
	}
	
	$ret = apply_filters( 'usces_filter_password_policy_message', $ret );
	if($out === 'return'){
		return $ret;
	}
	
	echo '<p class="password_policy">' . esc_html( $ret ) . '</p>';
}

function usces_crform_the_itemPriceCr_taxincluded( $label_pre = true, $label = '', $start_tag = '', $end_tag = '', $symbol_pre = true, $symbol_post = false, $seperator_flag = true, $out = '' ) {
	global $usces;

	if ( ( isset( $usces->options['tax_display'] ) && 'deactivate' == $usces->options['tax_display'] ) || ( isset( $usces->options['tax_mode'] ) && 'include' == $usces->options['tax_mode'] ) ) {
		$res = '';
	} else {
		$price_taxincluded = usces_the_itemPrice_taxincluded( 'return' );
		$price = esc_html( $usces->get_currency( $price_taxincluded, $symbol_pre, $symbol_post, $seperator_flag ) );
		if ( empty( $label ) ) {
			$label_tag = '<em class="tax tax_inc_label">' . __( 'tax-included', 'usces' ) . '</em>';
		} else {
			$label_tag = '<em class="tax tax_inc_label">' . $label . '</em>';
		}
		if ( empty( $start_tag ) ) {
			$start_tag = '<p class="tax_inc_block">(';
		}
		if ( $label_pre ) {
			$start_tag = $start_tag . $label_tag;
		}
		if ( empty( $end_tag ) ) {
			$end_tag = ')</p>';
		}
		if ( true !== $label_pre ) {
			$end_tag = $label_tag . $end_tag;
		}
		$res = apply_filters( 'usces_filter_crform_the_itemPriceCr_taxincluded', $start_tag . $price . $end_tag, $label_pre, $label, $symbol_pre, $symbol_post, $seperator_flag );
	}
	if ( $out == 'return' ) {
		return $res;
	} else {
		echo $res;
	}
}

function usces_itemPrice_taxincluded( $post_id = NULL ) {
	global $usces;
	if ( $post_id == NULL ) {
		global $post;
		$post_id = $post->ID;
	}
	$usces_tax = Welcart_Tax::get_instance();

	$skus = $usces->get_skus( $post_id );
	$skucode = $skus[0]['code'];
	$skuprice = $skus[0]['price'];
	$tax_rate = $usces_tax->get_sku_tax_rate( $post_id, $skucode );
	$tax = (float) sprintf( '%.3f', (float) $skuprice * (float) $tax_rate / 100 );
	$tax = usces_tax_rounding_off( $tax );
	$price = $skuprice + $tax;
	return $price;
}

function usces_crform_itemPriceCr_taxincluded( $post_id = NULL, $label_pre = true, $label = '', $start_tag = '', $end_tag = '', $symbol_pre = true, $symbol_post = false, $seperator_flag = true ) {
	global $usces;

	if ( ( isset( $usces->options['tax_display'] ) && 'deactivate' == $usces->options['tax_display'] ) || ( isset( $usces->options['tax_mode'] ) && 'include' == $usces->options['tax_mode'] ) ) {
		$res = '';
	} else {
		$price_taxincluded = usces_itemPrice_taxincluded( $post_id );
		$price = esc_html( $usces->get_currency( $price_taxincluded, $symbol_pre, $symbol_post, $seperator_flag ) );
		if ( empty( $label ) ) {
			$label_tag = '<em class="tax tax_inc_label">' . __( 'tax-included', 'usces' ) . '</em>';
		} else {
			$label_tag = '<em class="tax tax_inc_label">' . $label . '</em>';
		}
		if ( empty( $start_tag ) ) {
			$start_tag = '<p class="tax_inc_block">(';
		}
		if ( $label_pre ) {
			$start_tag = $start_tag . $label_tag;
		}
		if ( empty( $end_tag ) ) {
			$end_tag = ')</p>';
		}
		if ( true !== $label_pre ) {
			$end_tag = $label_tag . $end_tag;
		}
		$res = apply_filters( 'usces_filter_crform_itemPriceCr_taxincluded', $start_tag . $price . $end_tag, $post_id, $label_pre, $label, $symbol_pre, $symbol_post, $seperator_flag );
	}
	return $res;
}

function usces_get_cart_total_rows( $out = '' ) {
	global $usces;

	$res = '';
	if ( isset( $usces->options['tax_display'] ) && 'deactivate' != $usces->options['tax_display'] ) {
		if ( isset( $usces->options['tax_mode'] ) && 'include' != $usces->options['tax_mode'] ) {
			$total = usces_total_price( 'return' );
			$tax = usces_cart_tax( 'return' );
			$res .= '							<tr>
								<th class="num"></th>
								<th class="thumbnail"></th>
								<th colspan="3" scope="row" class="aright">' . __( 'Total', 'usces' ) . '</th>
								<th class="aright amount">' . usces_crform( $total, true, false, true ) . '</th>
								<th class="stock"></th>
								<th class="action"></th>
							</tr>' . "\n";
			$res .= '							<tr class="tax">
								<th class="num"></th>
								<th class="thumbnail"></th>
								<th colspan="3" scope="row" class="aright tax">' . __( 'Tax', 'usces' ) . '</th>
								<th class="aright amount tax">' . usces_crform( usces_cart_tax( 'return' ), true, false, true ) . '</th>
								<th class="stock"></th>
								<th class="action"></th>
							</tr>' . "\n";
			$res .= '							<tr>
								<th class="num"></th>
								<th class="thumbnail"></th>
								<th colspan="3" scope="row" class="aright">' . __( 'total items', 'usces' ) . '</th>
								<th class="aright amount">' . usces_crform( $total + $tax, true, false, true ) . '</th>
								<th class="stock"></th>
								<th class="action"></th>
							</tr>' . "\n";
		} else {
			$total = usces_total_price( 'return' );
			$tax = usces_internal_tax( array( 'total_items_price' => $total ), 'return' );
			$res .= '							<tr class="tax">
								<th class="num"></th>
								<th class="thumbnail"></th>
								<th colspan="3" scope="row" class="aright tax">' . __( 'Internal tax', 'usces' ) . '</th>
								<th class="aright amount tax">(' . usces_crform( $tax, true, false, true ) . ')</th>
								<th class="stock"></th>
								<th class="action"></th>
							</tr>' . "\n";
			$res .= '							<tr>
								<th class="num"></th>
								<th class="thumbnail"></th>
								<th colspan="3" scope="row" class="aright">' . __( 'total items', 'usces' ) . '</th>
								<th class="aright amount">' . usces_crform( $total, true, false, true ) . '</th>
								<th class="stock"></th>
								<th class="action"></th>
							</tr>' . "\n";
		}
	}
	if ( $out == 'return' ) {
		return $res;
	} else {
		echo $res;
	}
}

/***********************************************************
* excerpt
***********************************************************/
if ( ! function_exists( 'welcart_assistance_excerpt_length' ) ) {
	function welcart_assistance_excerpt_length( $length ) {
		return 10;
	}
}

if ( ! function_exists( 'welcart_assistance_excerpt_mblength' ) ) {
	function welcart_assistance_excerpt_mblength( $length ) {
		return 40;
	}
}

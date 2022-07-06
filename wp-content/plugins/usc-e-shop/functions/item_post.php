<?php
/**
 * Welcart SKU and Options
 *
 * Functions for manipulating SKUs and Options in product registration.
 *
 * @package Welcart
 */

defined( 'ABSPATH' ) || exit;

/**
 * Add Item SKU.
 *
 * @since  2.3.3
 * @param string  $post_id Post ID.
 * @param string  $new_value SKU data.
 * @param boolean $check Switch of check.
 * @return New meta id.
 */
function usces_add_sku( $post_id, $new_value, $check = true ) {
	global $wpdb, $usces;

	if ( $check ) {
		$metas = usces_get_post_meta( $post_id, '_isku_', false );
		if ( ! empty( $metas ) ) {
			$meta_num = count( $metas );
			$unique   = true;
			$sortnull = true;
			$sort     = array();
			foreach ( (array) $metas as $rows ) {
				$values = unserialize( $rows['meta_value'] );
				if ( $values['code'] == $new_value['code'] ) {
					$unique = false;
				}
				if ( ! isset( $values['sort'] ) ) {
					$sortnull = false;
				}
				$sort[] = $values['sort'];
			}
			if ( ! $unique ) {
				return -1;
			}

			rsort( $sort );
			$next_number = $sort[0] + 1;
			$unique_sort = array_unique( $sort );
			if ( count( $unique_sort ) !== $meta_num || $meta_num != $next_number || ! $sortnull ) {
				// To repair the sort data.
				$i = 0;
				foreach ( (array) $metas as $rows ) {
					$values         = unserialize( $rows['meta_value'] );
					$values['sort'] = $i;
					wel_update_sku_data_by_id( $rows['meta_id'], $post_id, $values );
					$i++;
				}
			}
		}
		$new_value['sort'] = ! empty( $meta_num ) ? $meta_num : 0;
	}
	$new_value   = $usces->stripslashes_deep_post( $new_value );
	$new_meta_id = wel_add_sku_data( $post_id, $new_value );

	return $new_meta_id;
}

/**
 * Add Item SKU by ajax.
 * Ajax response when a new SKU is registered in the SKU block on the product registration screen.
 *
 * @since  2.3.3
 * @param string $post_ID Post ID.
 * @return New meta id.
 */
function add_item_sku_meta( $post_ID ) {
	global $usces;

	$post_ID   = (int) $post_ID;
	$value     = array();
	$skus      = array();
	$protected = array( '_wp_attached_file', '_wp_attachment_metadata', '_wp_old_slug', '_wp_page_template' );

	$newskuname        = isset( $_POST['newskuname'] ) ? trim( $_POST['newskuname'] ) : '';
	$newskucprice      = isset( $_POST['newskucprice'] ) ? $_POST['newskucprice']: '';
	$newskuprice       = isset( $_POST['newskuprice'] ) ? $_POST['newskuprice']: '';
	$newskuzaikonum    = isset( $_POST['newskuzaikonum'] ) ? $_POST['newskuzaikonum']: '';
	$newskuzaikoselect = isset( $_POST['newskuzaikoselect'] ) ? $_POST['newskuzaikoselect'] : '';
	$newskudisp        = isset( $_POST['newskudisp'] ) ? trim( $_POST['newskudisp'] ) : '';
	$newskuunit        = isset( $_POST['newskuunit'] ) ? trim( $_POST['newskuunit'] ) : '';
	$newskugptekiyo    = isset( $_POST['newskugptekiyo'] ) ? $_POST['newskugptekiyo'] : '';
	$newskutaxrate     = isset( $_POST['newskutaxrate'] ) ? $_POST['newskutaxrate'] : '';

	if ( ! WCUtils::is_blank( $newskuname ) && ! WCUtils::is_blank( $newskuprice ) && ! WCUtils::is_blank( $newskuzaikoselect ) ) {

		if ( in_array( $newskuname, $protected ) ) {
			return false;
		}

		wp_cache_delete( $post_ID, 'post_meta' );

		$value['code']     = $newskuname;
		$value['name']     = $newskudisp;
		$value['cprice']   = $newskucprice;
		$value['price']    = $newskuprice;
		$value['unit']     = $newskuunit;
		$value['stocknum'] = $newskuzaikonum;
		$value['stock']    = $newskuzaikoselect;
		$value['gp']       = $newskugptekiyo;

		if ( ! empty( $newskutaxrate ) ) {
			$value['taxrate'] = $newskutaxrate;
		}

		$value = apply_filters( 'usces_filter_add_item_sku_meta_value', $value );
		$id    = usces_add_sku( $post_ID, $value, true );

		return $id;
	} else {
		return false;
	}
}

/**
 * Update Item SKU by ajax.
 * Ajax response when SKU information is updated in the SKU block of the product registration screen.
 *
 * @since  2.3.3
 * @param string $post_ID Post ID.
 * @return Query result.
 */
function up_item_sku_meta( $post_ID ) {
	global $wpdb, $usces;

	$post_ID = (int) $post_ID;
	$value   = array();

	$skuname   = isset( $_POST['skuname'] ) ? trim( $_POST['skuname'] ) : '';
	$skumetaid = isset( $_POST['skumetaid'] ) ? (int) $_POST['skumetaid'] : '';

	$res = apply_filters( 'usces_filter_before_up_item_sku_meta', false, $post_ID, $skumetaid, $skuname );
	if ( false !== $res ) {
		return $res;
	}

	$skucprice   = isset( $_POST['skucprice'] ) ? trim( $_POST['skucprice'] ) : 0;
	$skuprice    = isset( $_POST['skuprice'] ) ? trim( $_POST['skuprice'] ) : 0;
	$skuzaikonum = isset( $_POST['skuzaikonum'] ) ? trim( $_POST['skuzaikonum'] ) : 0;
	$skuzaiko    = isset( $_POST['skuzaiko'] ) ? (int) $_POST['skuzaiko'] : '';
	$skudisp     = isset( $_POST['skudisp'] ) ? trim( $_POST['skudisp'] ) : '';
	$skuunit     = isset( $_POST['skuunit'] ) ? trim( $_POST['skuunit'] ) : '';
	$skugptekiyo = isset( $_POST['skugptekiyo'] ) ? (int) $_POST['skugptekiyo'] : 0;
	$skusort     = isset( $_POST['sort'] ) ? $_POST['sort'] : 0;
	$skutaxrate  = isset( $_POST['skutaxrate'] ) ? $_POST['skutaxrate'] : '';

	$value['code']     = $skuname;
	$value['name']     = $skudisp;
	$value['cprice']   = $skucprice;
	$value['price']    = $skuprice;
	$value['unit']     = $skuunit;
	$value['stocknum'] = $skuzaikonum;
	$value['stock']    = $skuzaiko;
	$value['gp']       = $skugptekiyo;
	$value['sort']     = $skusort;
	if ( ! empty( $skutaxrate ) ) {
		$value['taxrate'] = $skutaxrate;
	}
	$value = $usces->stripslashes_deep_post( $value );

	$skus = $usces->get_skus( $post_ID );
	foreach ( $skus as $sku ) {
		if ( $sku['code'] == $skuname && $sku['meta_id'] != $skumetaid ) {
			return -1;
			break;
		}
	}

	$value = apply_filters( 'usces_filter_up_item_sku_meta_value', $value );

	if ( ! WCUtils::is_blank( $skumetaid ) && ! WCUtils::is_blank( $skuname ) && ! WCUtils::is_blank( $skuprice ) ) {

		wp_cache_delete( $post_ID, 'post_meta' );
		$res = wel_update_sku_data_by_id( $skumetaid, $post_ID, $value );

		return $res;
	} else {
		return false;
	}
}

/**
 * Delete Item SKU by ajax.
 * Ajax response when SKU is deleted in the SKU block of the product registration screen.
 *
 * @since  2.3.3
 * @param string $post_id Post ID.
 * @return Result.
 */
function del_item_sku_meta( $post_id ) {
	global $wpdb, $usces;

	$post_id   = (int) $post_id;
	$meta_id = isset( $_POST['skumetaid'] ) ? (int) $_POST['skumetaid'] : '';

	$res = apply_filters( 'usces_filter_before_del_item_sku_meta', false, $post_id, $meta_id );
	if ( false !== $res ) {
		return $res;
	}

	wp_cache_delete( $post_id, 'post_meta' );
	$res = wel_delete_sku_data_by_id( $meta_id, $post_id );

	// Resort.
	$skus = $usces->get_skus( $post_id );
	if ( ! empty( $skus ) ) {
		$i = 0;
		foreach ( $skus as $sku ) {
			$sku['sort'] = $i;
			$meta_id     = $sku['meta_id'];
			wel_update_sku_data_by_id( $meta_id, $post_id, $sku );
			$i++;
		}
	}
	return;
}

/**
 * List of Item SKUs.
 * To generate a list of SKUs in the SKU block of the product registration screen.
 *
 * @since  2.3.3
 * @param string $skus All SKUs.
 */
function list_item_sku_meta( $skus ) {

	if ( empty( $skus ) ) { // Exit if no meta.
		?>
		<table id="skulist-table" class="list" style="display: none;">
			<thead>
			<tr>
				<th class="hanldh" rowspan="2">　</th>
				<th><?php esc_html_e( 'SKU code', 'usces' ); ?></th>
				<th><?php echo apply_filters( 'usces_filter_listprice_label', __( 'normal price', 'usces' ), null, null ); ?>(<?php usces_crcode(); ?>)</th>
				<th><?php echo apply_filters( 'usces_filter_sellingprice_label', __( 'Sale price', 'usces' ), null, null ); ?>(<?php usces_crcode(); ?>)</th>
				<th><?php esc_html_e( 'stock', 'usces' ); ?></th>
				<th><?php esc_html_e( 'stock status', 'usces' ); ?></th><?php echo apply_filters( 'usces_filter_sku_meta_title1', '' ); ?>
			</tr>
			</thead>
			<tbody id="item-sku-list">
			<tr><td></td><td></td><td></td><td></td><td></td></tr>
			</tbody>
		</table>
		<?php
	} else {
		?>
		<table id="skulist-table" class="list">
			<thead>
			<tr>
				<th class="hanldh" rowspan="2">　</th>
				<th class="item-sku-key"><?php esc_html_e( 'SKU code', 'usces' ); ?></th>
				<th class="item-sku-cprice"><?php echo apply_filters( 'usces_filter_listprice_label', __( 'normal price', 'usces' ), null, null ); ?>(<?php usces_crcode(); ?>)</th>
				<th class="item-sku-price"><?php echo apply_filters( 'usces_filter_sellingprice_label', __( 'Sale price', 'usces' ), null, null ); ?>(<?php usces_crcode(); ?>)</th>
				<th class="item-sku-zaikonum"><?php esc_html_e( 'stock', 'usces' ); ?></th>
				<th class="item-sku-zaiko"><?php esc_html_e( 'stock status', 'usces' ); ?></th><?php esc_html_e( apply_filters( 'usces_filter_sku_meta_title1', '' ) ); ?>
			</tr>
			<tr>
				<th><?php esc_html_e( 'SKU display name ', 'usces' ); ?></th>
				<th><?php esc_html_e( 'unit', 'usces' ); ?></th>
				<?php
				$advance_title = '<th colspan="2">&nbsp;</th>';
				echo apply_filters( 'usces_filter_sku_meta_form_advance_title', $advance_title );
				?>
				<th><?php esc_html_e( 'Apply business package', 'usces' ); ?></th><?php echo apply_filters( 'usces_filter_sku_meta_title2', '' ); ?>
			</tr>
			</thead>
			<tbody id="item-sku-list">
			<?php
			foreach ( $skus as $sku ) {
				echo _list_item_sku_meta_row( $sku );
			}
			?>
			</tbody>
		</table>
		<?php
	}
}

/**
 * Row of Item SKU List.
 * To generate a list of SKUs in the SKU block of the product registration screen.
 *
 * @since  2.3.3
 * @param string $sku SKU.
 */
function _list_item_sku_meta_row( $sku ) {
	$r     = '';
	$style = '';

	$key               = esc_attr( $sku['code'] );
	$cprice            = $sku['cprice'];
	$price             = $sku['price'];
	$zaikonum          = $sku['stocknum'];
	$zaiko             = $sku['stock'];
	$skudisp           = esc_attr( $sku['name'] );
	$skuunit           = esc_attr( $sku['unit'] );
	$skugptekiyo       = $sku['gp'];
	$id                = (int) $sku['meta_id'];
	$zaikoselectarray  = get_option( 'usces_zaiko_status' );
	$zaikoselect_count = ( $zaikoselectarray && is_array( $zaikoselectarray ) ) ? count( $zaikoselectarray ) : 0;
	$sort              = (int) $sku['sort'];
	$sku_colspan       = apply_filters( 'usces_filter_sku_meta_colspan', '6' );

	ob_start();
	?>
	<tr class='metastuffrow'><td colspan='<?php esc_attr_e( $sku_colspan ); ?>'>
		<table id='itemsku-<?php echo $id; ?>' class='metastufftable'>
			<tr>
				<th class='handlb' rowspan='<?php echo apply_filters( 'usces_filter_sku_meta_rowspan', '3' ); ?>'>　</th>
				<td class='item-sku-key'><input name='itemsku[<?php echo $id; ?>][key]' id='itemsku[<?php echo $id; ?>][key]' class='skuname metaboxfield' type='text' value='<?php echo $key; ?>' /></td>
				<td class='item-sku-cprice'><input name='itemsku[<?php echo $id; ?>][cprice]' id='itemsku[<?php echo $id; ?>][cprice]' class='skuprice metaboxfield' type='text' value='<?php echo $cprice; ?>' /></td>
				<td class='item-sku-price'><input name='itemsku[<?php echo $id; ?>][price]' id='itemsku[<?php echo $id; ?>][price]' class='skuprice metaboxfield' type='text' value='<?php echo $price; ?>' /></td>
				<td class='item-sku-zaikonum'><input name='itemsku[<?php echo $id; ?>][zaikonum]' id='itemsku[<?php echo $id; ?>][zaikonum]' class='skuzaikonum metaboxfield' type='text' value='<?php echo $zaikonum; ?>' /></td>
				<td class='item-sku-zaiko'>
					<select id='itemsku[<?php echo $id; ?>][zaiko]' name='itemsku[<?php echo $id; ?>][zaiko]' class='skuzaiko metaboxfield'>
					<?php
					for ( $i=0; $i < $zaikoselect_count; $i++ ) {
						$selected = ( $i == $zaiko ) ? " selected='selected'" : '';
					?>
						<option value='<?php echo $i; ?>'<?php echo $selected; ?>><?php echo $zaikoselectarray[ $i ]; ?></option>
					<?php
					}
					?>
					</select>
				</td><?php echo apply_filters( 'usces_filter_sku_meta_field1', '', $sku ); ?>
			</tr>
			<tr>
				<td class='item-sku-key'><input name='itemsku[<?php echo $id; ?>][skudisp]' id='itemsku[<?php echo $id; ?>][skudisp]' class='skudisp metaboxfield' type='text' value='<?php echo $skudisp; ?>' />
				</td>
				<td class='item-sku-cprice'><input name='itemsku[<?php echo $id; ?>][skuunit]' id='itemsku[<?php echo $id; ?>][skuunit]' class='skuunit metaboxfield' type='text' value='<?php echo $skuunit; ?>' /></td>
				<?php
				$default_field = "\n\t\t<td colspan='2'>&nbsp;</td>";
				echo apply_filters( 'usces_filter_sku_meta_row_advance', $default_field, $sku );
				?>
				<td class='item-sku-zaiko'>
					<select id='itemsku[<?php echo $id; ?>][skugptekiyo]' name='itemsku[<?php echo $id; ?>][skugptekiyo]' class='skugptekiyo metaboxfield'>
						<option value='0' <?php echo ( $skugptekiyo == 0 ? " selected='selected'" : "" ); ?>><?php _e( 'Not apply','usces' ); ?></option>
						<option value='1' <?php echo ( $skugptekiyo == 1 ? " selected='selected'" : "" ); ?>><?php _e( 'Apply','usces' ); ?></option>
					</select>
				</td><?php echo apply_filters( 'usces_filter_sku_meta_field2', '', $sku ); ?>
			</tr>
			<?php echo apply_filters( 'usces_filter_sku_meta_row', '', $sku ); ?>
			<tr>
				<td colspan='<?php echo ( $sku_colspan - 1 ); ?>' class='submittd'>
					<div id='skusubmit-<?php echo $id; ?>' class='submit'>
						<input name='deleteitemsku[<?php echo $id; ?>]' id='deleteitemsku[<?php echo $id; ?>]' type='button' class='button' value='<?php esc_attr_e( 'Delete' ) ?>' onclick="if( jQuery('#post_ID').val() < 0 ) return; itemSku.post('deleteitemsku', <?php echo $id; ?>);" />
						<input name='updateitemsku[<?php echo $id; ?>]' id='updateitemsku[<?php echo $id; ?>]' type='button' class='button' value='<?php esc_attr_e( 'Update' ); ?>' onclick="if( jQuery('#post_ID').val() < 0 ) return; itemSku.post('updateitemsku', <?php echo $id; ?>);" />
						<input name='itemsku[<?php echo $id; ?>][sort]' id='itemsku[<?php echo $id; ?>][sort]' type='hidden' value='<?php echo $sort; ?>' />
						<?php usces_sku_meta_row_reduced_taxrate( $sku ); ?>
					</div>
					<div id='itemsku_loading-<?php echo $id; ?>' class='meta_submit_loading'></div>
				</td>
			</tr>
		</table>
	</td></tr>
	<?php
	$r = ob_get_contents();
	ob_end_clean();
	return $r;
}

/**
 * New SKU Form.
 * The form to add a new SKU in the SKU block on the product registration screen.
 *
 * @since  2.3.3
 */
function item_sku_meta_form() {
	$sku_colspan = apply_filters( 'usces_filter_sku_meta_colspan', '6' );
	?>
	<div id="sku_ajax-response"></div>
	<p><strong><?php _e( 'Add new SKU','usces' ) ?> : </strong></p>
	<table id="newsku">
		<thead>
		<tr>
			<th class="left"><?php _e( 'SKU code', 'usces' ) ?></th>
			<th><?php echo apply_filters( 'usces_filter_listprice_label', __( 'normal price','usces' ), null, null); ?>(<?php usces_crcode(); ?>)</th>
			<th><?php echo apply_filters( 'usces_filter_sellingprice_label', __( 'Sale price','usces' ), null, null); ?>(<?php usces_crcode(); ?>)</th>
			<th><?php _e( 'stock', 'usces' ) ?></th>
			<th><?php _e( 'stock status', 'usces' ) ?></th><?php echo apply_filters( 'usces_filter_sku_meta_title1', '' ); ?>
		</tr>
		<tr>
			<th><?php _e( 'SKU display name ', 'usces' ) ?></th>
			<th><?php _e( 'unit','usces' ) ?></th>
			<?php
			$advance_title = '<th colspan="2">&nbsp;</th>';
			echo apply_filters( 'usces_filter_sku_meta_form_advance_title', $advance_title );
			?>
			<th><?php _e( 'Apply business package', 'usces' ) ?></th><?php echo apply_filters( 'usces_filter_sku_meta_title2', '' ); ?>
		</tr>
		</thead>
		
		<tbody>
		<tr>
			<td id="newskuleft" class='item-sku-key'><input type="text" id="newskuname" name="newskuname" class="newskuname metaboxfield"value="" /></td>
			<td class='item-sku-cprice'><input type="text" id="newskucprice" name="newskucprice" class='newskuprice metaboxfield' /></td>
			<td class='item-sku-price'><input type="text" id="newskuprice" name="newskuprice" class='newskuprice metaboxfield' /></td>
			<td class='item-sku-zaikonum'><input type="text" id="newskuzaikonum" name="newskuzaikonum" class='newskuzaikonum metaboxfield' /></td>
			<td class='item-sku-zaiko'>
				<select id="newskuzaikoselect" name="newskuzaikoselect" class="newskuzaikoselect metaboxfield">
			<?php
				$zaikoselectarray = get_option( 'usces_zaiko_status' );
				foreach ( $zaikoselectarray as $v => $l ) {
					echo "\n<option value='" . esc_attr( $v ) . "'>" . esc_html( $l ) . "</option>";
				}
			?>
				</select>
			</td><?php echo apply_filters( 'usces_filter_newsku_meta_field1', '' ); ?>
		</tr>
		<tr>
			<td class='item-sku-key'><input type="text" id="newskudisp" name="newskudisp" class="newskudisp metaboxfield" /></td>
			<td class='item-sku-cprice'><input type="text" id="newskuunit" name="newskuunit" class='newskuunit metaboxfield' /></td>
			<?php
			$advance_field = '<td class="item-sku-price">&nbsp;</td><td class="item-sku-zaikonum">&nbsp;</td>';
			echo apply_filters('usces_filter_sku_meta_form_advance_field', $advance_field );
			?>
			<td class='item-sku-zaiko'>
				<select id="newskugptekiyo" name="newskugptekiyo" class="newskugptekiyo metaboxfield">
					<option value="0"><?php _e( 'Not apply', 'usces' ) ?></option>
					<option value="1"><?php _e( 'Apply', 'usces' ) ?></option>
				</select>
			</td><?php echo apply_filters( 'usces_filter_newsku_meta_field2', '' ); ?>
		</tr>
		<?php echo apply_filters( 'usces_filter_newsku_meta_row', '' ); ?>
		<tr>
			<td colspan="<?php echo ( $sku_colspan - 1 ); ?>" class="submittd">
				<div id='newskusubmit' class='submit'>
					<?php
					$add_itemsku_button = '<input name="add_itemsku" type="button" class="button" id="add_itemsku" tabindex="9" value="' . __( 'Add SKU', 'usces' ) . '" onclick="if( jQuery(\'#post_ID\').val() < 0 ) return; itemSku.post(\'additemsku\', 0);" />';
					echo apply_filters( 'usces_filter_newsku_meta_add_button', $add_itemsku_button );
					?>
					<?php usces_newsku_meta_row_reduced_taxrate(); ?>
				</div>
				<div id="newitemsku_loading" class="meta_submit_loading"></div>
			</td>
		</tr>
		</tbody>
	</table>
	<?php 
}


/**
 * Get Post Meta using MetaID.
 *
 * @since  2.3.3
 * @param int $meta_id Meta ID.
 * @return MetaData|false
 */
function usces_get_post_meta_by_metaid( $meta_id ) {
	global $wpdb;
	$res = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM $wpdb->postmeta WHERE meta_id = %d", $meta_id ), ARRAY_A );
	return $res;
}

/**
 * Get Post meta.
 * Get product metadata by specifying meta_key.
 *
 * @since  2.3.3
 * @param string  $post_id Post ID.
 * @param string  $key Meta key.
 * @param boolean $cache Switch of cache.
 * @return MetaData|false
 */
function usces_get_post_meta( $post_id, $key, $cache = true ) {
	global $wpdb;

	$cache_key = 'wel_post_meta_' . $post_id . '_' . $key;
	if ( true === $cache ) {
		$meta_data = wp_cache_get( $cache_key );
	} else {
		$meta_data = false;
	}
	if ( false === $meta_data || is_admin() || wp_doing_ajax() ) {
			$meta_data = $wpdb->get_results( $wpdb->prepare("SELECT * FROM $wpdb->postmeta WHERE post_id = %d AND meta_key = %s", $post_id, $key), ARRAY_A);
		if ( null !== $meta_data ) {
			wp_cache_set( $cache_key, $meta_data );
		}
	}

	if ( null === $meta_data ) {
		return false;
	} else {
		return $meta_data;
	}
}

/**
 * Sort SKUs or Options.
 * Ajax response when changing the order of SKUs in the SKU block of the product registration screen.
 *
 * @since  2.3.3
 * @param int $post_id Post ID.
 * @param int $metastr Meta ids.
 */
function usces_sort_post_meta( $post_id, $metastr ) {
	global $wpdb;

	$meta_ids = explode( ',', $metastr );

	if ( ! empty( $meta_ids ) ) {

		$i = 0;
		foreach ( $meta_ids as $meta_id ) {

			$meta           = usces_get_post_meta_by_metaid( $meta_id );
			$values         = unserialize( $meta['meta_value'] );
			$values['sort'] = $i;

			if ( '_isku_' === $meta['meta_key'] ) {
				wel_update_sku_data_by_id( $meta_id, $post_id, $values );

			} else if ( '_iopt_' === $meta['meta_key'] ) {

				wel_update_opt_data_by_id( $meta_id, $post_id, $values );

			}
			$i++;
		}
	}
}

/**
 * Get all options by post id.
 *
 * @since  2.3.3
 * @param int    $post_id Post ID.
 * @param string $keyflag Sort key.
 */
function usces_get_opts( $post_id, $keyflag = 'sort', $cache = true ) {
	$opts = wel_get_opts( $post_id, $keyflag, $cache );
	return $opts;
}

/**
 * Add Item Option.
 *
 * @since  2.3.3
 * @param string  $post_id Post ID.
 * @param string  $new_value SKU data.
 * @param boolean $check Switch of check.
 * @return New meta id.
 */
function usces_add_opt( $post_id, $new_value, $check = true ) {
	global $wpdb, $usces;

	if ( $check ) {
		$opts = wel_get_opts( $post_id, 'sort', false );
		if ( ! empty( $opts ) && is_array( $opts ) ) {
			$meta_num = count( $opts );
			$unique   = true;
			$sortnull = true;
			$sort     = array();

			foreach ( (array) $opts as $opt ) {

				if ( $opt['name'] === $new_value['name'] ) {
					$unique = false;
				}
				if ( ! isset( $opt['sort'] ) ) {
					$sortnull = false;
				}
				$sort[] = $opt['sort'];
			}

			if ( ! $unique ) {
				return -1;
			}

			rsort( $sort );
			$next_number = reset( $sort ) + 1;
			$unique_sort = array_unique( $sort );
			if ( $meta_num !== count( $unique_sort ) || $meta_num !== $next_number || ! $sortnull ) {
				// To repair the sort data.
				$i = 0;
				foreach ( (array) $opts as $opt ) {
					$opt['sort'] = $i;
					wel_update_opt_data_by_id( $opt['meta_id'], $post_id, $opt );
					$i++;
				}
			}
		}
		$new_value['sort'] = ! empty( $meta_num ) ? $meta_num : 0;
	}

	$id = wel_add_opt_data( $post_id, $new_value );
	return $id;
}


/**
 * list_item_option
 */
function list_item_option_meta( $opts ) {
	// Exit if no meta
	if ( ! $opts ) {
		?>
		<table id="optlist-table" class="list" style="display: none;">
			<thead>
			<tr>
				<th class="hanldh">　</th>
				<th class="item-opt-key"><?php _e( 'option name','usces' ) ?></th>
				<th class="item-opt-value"><?php _e( 'selected amount','usces' ) ?></th>
			</tr>
			</thead>
			<tbody id="item-opt-list">
			<tr><td></td></tr>
			</tbody>
		</table>
		<?php
	} else {
		?>
		<table id="optlist-table" class="list">
			<thead>
			<tr>
				<th class="hanldh">　</th>
				<th class="item-opt-key"><?php _e( 'option name','usces' ) ?></th>
				<th class="item-opt-value"><?php _e( 'selected amount','usces' ) ?></th>
			</tr>
			</thead>
			<tbody id="item-opt-list">
		<?php
			foreach ( $opts as $opt ) {
				echo _list_item_option_meta_row( $opt );
			}
		?>
			</tbody>
		</table>
		<?php
	}
}



/**
 * option meta row
 */
function _list_item_option_meta_row( $opt ) {
	$r     = '';
	$style = '';
	$means = get_option( 'usces_item_option_select' );

	$name        = esc_attr( $opt['name'] );
	$meansoption = '';
	foreach ( $means as $meankey => $meanvalue ) {

		if ( $meankey === (int) $opt['means'] ) {
			$selected = ' selected="selected"';
		} else {
			$selected = '';
		}
		$meansoption .= '<option value="' . esc_attr( $meankey ) . '"' . $selected . '>' . esc_html( $meanvalue ) . "</option>\n";
	}
	$essential = $opt['essential'] == 1 ? " checked='checked'" : "";
	$value     = '';
	if ( is_array( $opt['value'] ) ) {
		foreach ( $opt['value'] as $k => $v ) {
			$value .= $v . "\n";
		}
	} else {
		$value = $opt['value'];
	}
	$value = trim( $value );
	$id = (int) $opt['meta_id'];
	$sort = (int) $opt['sort'];

	ob_start();
	?>
	<tr class="metastuffrow"><td colspan="3">
		<table id="itemopt-<?php echo $id; ?>" class="metastufftable">
			<tr>
				<th class='handlb' rowspan='2'>　</th>
				<td class='item-opt-key'>
					<div><input name='itemopt[<?php echo $id; ?>][name]' id='itemopt[<?php echo $id; ?>][name]' class='metaboxfield' type='text' size='20' value='<?php echo $name; ?>' /></div>
					<div class='optcheck'>
						<select name='itemopt[<?php echo $id; ?>][means]' id='itemopt[<?php echo $id; ?>][means]'><?php echo $meansoption; ?></select>
						<label for='itemopt[<?php echo $id; ?>][essential]'><input name='itemopt[<?php echo $id; ?>][essential]' id='itemopt[<?php echo $id; ?>][essential]' type='checkbox' value='1'<?php echo $essential; ?> class='metaboxcheckfield' /><?php _e('Required','usces'); ?></label>
					</div>
				</td>
				<td class='item-opt-value'>
					<textarea name='itemopt[<?php echo $id; ?>][value]' id='itemopt[<?php echo $id; ?>][value]' class='metaboxfield'><?php echo esc_html($value); ?></textarea>
				</td>
			</tr>
			<tr>
				<td colspan='2' class='submittd'>
					<div id='itemoptsubmit-<?php echo $id; ?>' class='submit'>
						<input name='deleteitemopt[<?php echo $id; ?>]' id='deleteitemopt[<?php echo $id; ?>]' type='button' class='button' value='<?php esc_attr_e( 'Delete' ); ?>' onclick="if( jQuery('#post_ID').val() < 0 ) return; itemOpt.post('deleteitemopt', <?php echo $id; ?>);" />
						<input name='updateitemopt[<?php echo $id; ?>]' id='updateitemopt[<?php echo $id; ?>]' type='button' class='button' value='<?php esc_attr_e( 'Update' ); ?>' onclick="if( jQuery('#post_ID').val() < 0 ) return; itemOpt.post('updateitemopt', <?php echo $id; ?>);" />
						<input name='itemopt[<?php echo $id; ?>][sort]' id='itemopt[<?php echo $id; ?>][sort]' type='hidden' value='<?php echo $sort; ?>' />
					</div>
					<div id='itemopt_loading-<?php echo $id; ?>' class='meta_submit_loading'></div>
				</td>
			</tr>
		</table>
	</td></tr>
	<?php
	$r = ob_get_contents();
	ob_end_clean();
	return $r;
}


/**
 * common_option_meta_form
 */
function common_option_meta_form() {
	$means       = get_option( 'usces_item_option_select' );
	$meansoption = '';
	foreach ( $means as $meankey => $meanvalue ) {
		$meansoption .= '<option value="' . esc_attr( $meankey ) . '">' . esc_html( $meanvalue ) . "</option>\n";
	}
	?>
	<div id="itemopt_ajax-response"></div>
	<p><strong><?php _e( 'Add a new option', 'usces' ) ?> : </strong></p>
	<table id="newmeta2">
		<thead>
		<tr>
			<th class="left"><label for="metakeyselect"><?php _e( 'option name', 'usces' ) ?></label></th>
			<th><label for="metavalue"><?php _e( 'selected amount', 'usces' ) ?></label></th>
		</tr>
		</thead>
		
		<tbody>
		<tr>
			<td class='item-opt-key'>
				<input type="text" id="newoptname" name="newoptname" class="metaboxfield" tabindex="7" value="" />
				<div class="optcheck">
					<select name='newoptmeans' id='newoptmeans' class="metaboxfield long"><?php echo $meansoption; ?></select>
					<label for='newoptessential'><input name="newoptessential" type="checkbox" id="newoptessential" class="metaboxcheckfield" /><?php _e('Required','usces') ?></label>
				</div>
			</td>
			<td class='item-opt-value'><textarea id="newoptvalue" name="newoptvalue" class='metaboxfield'></textarea></td>
		</tr>
		
		<tr>
			<td colspan="2" class="submittd">
				<div id='newcomoptsubmit' class='submit'>
					<input name="add_comopt" type="button" class='button' id="add_comopt" tabindex="9" value="<?php _e( 'Add common options', 'usces' ) ?>" onclick="itemOpt.post('addcommonopt', 0);" />
				</div>
				<div id="newcomopt_loading" class="meta_submit_loading"></div>
			</td>
		</tr>
		</tbody>
	</table>
	<?php 
}

/**
 * item_option_meta_form
 */
function item_option_meta_form() {
	global $wpdb;
	$usces_options = get_option( 'usces' );
	$limit         = (int) apply_filters( 'postmeta_form_limit', 30 );
	$cart_number   = (int) get_option( 'usces_cart_number' );
	$opts          = usces_get_opts( $cart_number );
	$means         = get_option( 'usces_item_option_select' );
	$meansoption  = '';
	foreach ( $means as $meankey => $meanvalue ) {
		$meansoption .= '<option value="' . esc_attr( $meankey ) . '">' . esc_html( $meanvalue ) . "</option>\n";
	}
	?>
	<div id="itemopt_ajax-response"></div>
	<p><strong><?php _e( 'Applicable product options', 'usces' ) ?> : </strong></p>
	<table id="newmeta2">
		<thead>
		<tr>
			<th class="item-opt-key"><label for="metakeyselect"><?php _e( 'option name', 'usces' ) ?></label></th>
			<th class="item-opt-value"><label for="metavalue"><?php _e( 'selected amount', 'usces' ) ?></label></th>
		</tr>
		</thead>
		
		<tbody>
		<tr>
			<td class='item-opt-key'>
			<?php if ( ! empty( $opts ) ) { ?>
				<select id="optkeyselect" name="optkeyselect" class="optkeyselect metaboxfield" tabindex="7" onchange="if( jQuery('#post_ID').val() < 0 ) return; itemOpt.post('keyselect', this.value);">
					<option value="#NONE#"><?php _e( '-- Select --', 'usces' ); ?></option>
				<?php foreach ( $opts as $opt ){ ?>
					<option value='<?php echo $opt['meta_id']; ?>'><?php echo esc_attr( $opt['name'] ); ?></option>
				<?php } ?>
				</select>
				<input type="hidden" id="newoptname" name="newoptname" class="metaboxfield" />
				<div class="optcheck">
					<select name='newoptmeans' id='newoptmeans'><?php echo $meansoption; ?></select>
					<label for='newoptessential'><input name="newoptessential" type="checkbox" id="newoptessential" class="metaboxcheckfield" /><?php _e('Required','usces') ?></label>
				</div>
			<?php } else { ?>
				<p><?php _e( 'Please create a common option.', 'usces' ) ?></p>
			<?php } ?>
			</td>
			<td class='item-opt-value'><textarea id="newoptvalue" name="newoptvalue" class='metaboxfield'></textarea></td>
		</tr>
		
		<tr>
			<td colspan="2" class="submittd">
			<?php if ( is_array( $opts ) ) { ?>
			<div id='newitemoptsubmit' class='submit'>
				<input name="add_itemopt" type="button" class='button' id="add_itemopt" tabindex="9" value="<?php _e( 'Apply an option', 'usces' ) ?>" onclick="if( jQuery('#post_ID').val() < 0 ) return; itemOpt.post('additemopt', 0);" />
			</div>
			<div id="newitemopt_loading" class="meta_submit_loading"></div>
			<?php } ?>
			</td>
		</tr>
		</tbody>
	</table>
	<?php 
}

//
// Post Meta
//

/**
 * add_item_option_meta
 */
function add_item_option_meta( $post_ID ) {
	global $usces;

	$post_ID   = (int) $post_ID;
	$value     = array();
	$opts      = array();
	$protected = array( '#NONE#', '_wp_attached_file', '_wp_attachment_metadata', '_wp_old_slug', '_wp_page_template' );

	$newoptcode      = isset( $_POST['newoptcode'] ) ? trim( $_POST['newoptcode'] ) : '';
	$newoptname      = isset( $_POST['newoptname'] ) ? trim( $_POST['newoptname'] ) : '';
	$newoptmeans     = isset( $_POST['newoptmeans'] ) ? (int) $_POST['newoptmeans'] : 0;
	$newoptessential = isset( $_POST['newoptessential'] ) ? $_POST['newoptessential'] : 0;
	$newoptvalue     = isset( $_POST['newoptvalue'] ) ? trim( $_POST['newoptvalue'] ) : '';

	if ( ( $newoptmeans >= 2 || WCUtils::is_zero( $newoptvalue ) || ! empty ( $newoptvalue ) ) && ! empty ( $newoptname ) ) {

		if ( $newoptname ) {
			$metakey = $newoptname; // default
		}

		if ( in_array( $metakey, $protected ) ) {
			return false;
		}

		wp_cache_delete( $post_ID, 'post_meta' );
		
		$value['code']      = $newoptcode;
		$value['name']      = str_replace( "\\", '', $newoptname );
		$value['means']     = $newoptmeans;
		$value['essential'] = $newoptessential;
		$value['value']     = str_replace( "\\", '', $newoptvalue );

		$value = $usces->stripslashes_deep_post( $value );

		$id = usces_add_opt($post_ID, $value);

		return $id;
	} else {
		return false;
	}
}

/**
 * Update Item Option by ajax.
 * Ajax response when Option information is updated in the Option block of the product registration screen.
 *
 * @since  2.3.3
 * @param string $post_ID Post ID.
 * @return Query result.
 */
function up_item_option_meta( $post_ID ) {
	global $wpdb, $usces;

	$post_ID = (int) $post_ID;
	$value   = array();

	$optmetaid    = isset( $_POST['optmetaid'] ) ? (int) $_POST['optmetaid'] : '';
	$optcode      = isset( $_POST['optcode'] ) ? $_POST['optcode'] : '';
	$optname      = isset( $_POST['optname'] ) ? $_POST['optname'] : '';
	$optmeans     = isset( $_POST['optmeans'] ) ? (int) $_POST['optmeans'] : 0;
	$optessential = isset( $_POST['optessential'] ) ? $_POST['optessential'] : 0;
	$optsort      = isset( $_POST['sort'] ) ? $_POST['sort'] : 0;
	$optvalue     = isset( $_POST['optvalue'] ) ? trim( $_POST['optvalue'] ) : '';

	$metakey = '_iopt_';

	$value['meta_id']   = $optmetaid;
	$value['code']      = empty( $optcode ) ? $optmetaid : $optcode;
	$value['name']      = str_replace( "\\", '', $optname );
	$value['means']     = $optmeans;
	$value['essential'] = $optessential;
	$value['value']     = str_replace( "\\", '', $optvalue );
	$value['sort']      = $optsort;

	$value = $usces->stripslashes_deep_post( $value );

	wp_cache_delete( $post_ID, 'post_meta' );
	$res = wel_update_opt_data_by_id( $optmetaid, $post_ID, $value );

	return $res;
}

/**
 * del_item_option_meta
 */
function del_item_option_meta( $post_ID ) {
	global $wpdb;
	
	$post_ID = (int) $post_ID;
	$meta_id = isset( $_POST['optmetaid'] ) ? (int) $_POST['optmetaid'] : '';

	wp_cache_delete( $post_ID, 'post_meta' );

	wel_delete_opt_data_by_id( $meta_id, $post_ID );

	$opts = wel_get_opts( $post_ID, 'sort', false );
	if ( ! empty( $opts ) ){
		$i = 0;
		foreach( $opts as $opt ){
			$opt['sort'] = $i;
			$meta_id     = $opt['meta_id'];
			unset( $opt['meta_id'] );
			wel_update_opt_data_by_id( $meta_id, $post_ID, $opt );
			$i++;
		}
	} else {
		return ;
	}
}

function select_common_option( $post_ID ) {
	global $wpdb;

	$meta_id = isset($_POST['meta_id']) ? $_POST['meta_id'] : '';
	if(!$meta_id) return ;
	
	$meta_value = $wpdb->get_var( $wpdb->prepare( "SELECT meta_value FROM $wpdb->postmeta WHERE meta_id = %d ", $meta_id ) );
	$array_val  = unserialize( $meta_value );
	
	$res = array(
		'means'     => $array_val['means'],
		'essential' => $array_val['essential'],
		'value'     => $array_val['value'],
	);
	return $res;
} // select_common_option

function order_item2cart_ajax(){
	global $usces;

	if( $_POST['action'] != 'order_item2cart_ajax' ) die(0);
	
	$_POST = $usces->stripslashes_deep_post($_POST);
	$order_id = usces_add_ordercartdata();
	if( !$order_id )
		die( 0 );
		
	$cart = usces_get_ordercartdata( $order_id );
	$return = usces_get_ordercart_row( $order_id, $cart );
	die( $return );
}

function order_item_ajax(){
	global $usces;

	if( $_POST['action'] != 'order_item_ajax' ) die(0);

	$res = false;
	$_POST = $usces->stripslashes_deep_post($_POST);

	switch ( $_POST['mode'] ) {
		case 'completionMail':
		case 'orderConfirmMail':
		case 'changeConfirmMail':
		case 'receiptConfirmMail':
		case 'mitumoriConfirmMail':
		case 'cancelConfirmMail':
		case 'otherConfirmMail':
			$res = usces_order_confirm_message( $_POST['order_id'] );
			break;
		case 'sendmail':
			$res = usces_ajax_send_mail();
			break;
		case 'get_order_item':
			$res = get_order_item( $_POST['itemcode'] );
			break;
		case 'get_item_select_option':
			$res = usces_get_item_select_option( $_POST['cat_id'] );
			break;
		case 'ordercheckpost':
			$res = usces_update_ordercheck();
			break;
		case 'getmember':
			$res = usces_get_member_neworder();
			break;
		case 'recalculation':
			$change_taxrate = ( isset( $_POST['change_taxrate'] ) ) ? $_POST['change_taxrate'] : '';
			$res = usces_order_recalculation( $_POST['order_id'], $_POST['mem_id'], $_POST['post_ids'], $_POST['prices'], $_POST['quants'], $_POST['cart_ids'], $_POST['upoint'], $_POST['shipping_charge'], $_POST['cod_fee'], $_POST['discount'], $change_taxrate );
			break;
		case 'recalculation_reduced':
			$change_taxrate = ( isset( $_POST['change_taxrate'] ) ) ? $_POST['change_taxrate'] : '';
			$res = usces_order_recalculation_reduced( $_POST['order_id'], $_POST['mem_id'], $_POST['post_ids'], $_POST['prices'], $_POST['quants'], $_POST['cart_ids'], $_POST['upoint'], $_POST['shipping_charge'], $_POST['cod_fee'], $_POST['discount_standard'], $_POST['discount_reduced'], $change_taxrate );
			break;
		case 'get_settlement_log':
			$res = usces_get_settlement_log();
			break;
		case 'get_settlement_log_detail':
			$res = usces_get_settlement_log_detail( $_POST['log_key'] );
			break;
		case 'search_settlement_log':
			$res = usces_get_settlement_log( $_POST['log_key'] );
			break;
		case 'delete_settlement_log':
			usces_delete_settlement_log( $_POST['log_key'] );
			$res = usces_get_settlement_log();
			break;
		case 'delete_settlement_log_all':
			usces_delete_settlement_log();
			$res = usces_get_settlement_log();
			break;
		case 'revival_order_data':
			$res = usces_revival_order_data( $_POST['log_key'], $_POST['register_date'] );
			break;
		case 'get_settlement_error_log':
			$res = usces_get_settlement_error_log();
			break;
		case 'get_settlement_error_log_detail':
			$res = usces_get_settlement_error_log_detail( $_POST['log_id'] );
			break;
		case 'delete_settlement_error_log':
			usces_delete_settlement_error_log( $_POST['log_id'] );
			$res = usces_get_settlement_error_log();
			break;
		case 'delete_settlement_error_log_all':
			usces_delete_settlement_error_log();
			$res = usces_get_settlement_error_log();
			break;
		case 'reset_settlement_notice':
			$res = usces_reset_settlement_notice();
			break;
	}

	$res = apply_filters( 'usces_filter_order_item_ajax', $res );

	if( $res === false )  die(0);

	die( $res );
}

function usces_get_item_select_option( $cat_id ){
	global $usces;
	$number = apply_filters( 'usces_filter_item_select_numberposts', 50, $cat_id );
	$args = array( 'category' => $cat_id, 'numberposts' => $number, 'post_status' => array( 'publish', 'private' ) );
	$args = apply_filters( 'usces_filter_item_select_queryargs', $args, $cat_id );

	$items = get_posts( $args );
	$option = '<option value="-1">' . __( 'Please select an item', 'usces' ) . '</option>' . "\n";
	foreach( $items as $item ){
		$product  = wel_get_product( $item->ID );
		$ItemName = $product['itemName'];
		$ItemCode = $product['itemCode'];
		$option .= '<option value="' . urlencode( $ItemCode ) . '">' . $ItemName . '(' . $ItemCode . ')' . '</option>' . "\n";
	}
	return $option;
}

/**
 * order Item html
 */
function usces_get_member_neworder() {
	global $wpdb;
	$wpdb->show_errors();
	$res = '';
	$member_table = usces_get_tablename( 'usces_member' );
	$query = $wpdb->prepare("SELECT * FROM $member_table WHERE mem_email = %s", trim($_POST['email']));
	$value = $wpdb->get_row( $query, ARRAY_A );
	
	if( !$value ){
		$response = array( 'status_code' => 'none' );
		wp_send_json($response);
	}

	$response = array(
		'status_code' => 'ok',
		'member_id' => $value['ID'],
		'customer[name1]' => $value['mem_name1'],
		'customer[name2]' => $value['mem_name2'],
		'customer[name3]' => $value['mem_name3'],
		'customer[name4]' => $value['mem_name4'],
		'customer[zipcode]' => $value['mem_zip'],
		'customer[pref]' => $value['mem_pref'],
		'customer[address1]' => $value['mem_address1'],
		'customer[address2]' => $value['mem_address2'],
		'customer[address3]' => $value['mem_address3'],
		'customer[tel]' => $value['mem_tel'],
		'customer[fax]' => $value['mem_fax'],
		'delivery[name1]' => $value['mem_name1'],
		'delivery[name2]' => $value['mem_name2'],
		'delivery[name3]' => $value['mem_name3'],
		'delivery[name4]' => $value['mem_name4'],
		'delivery[zipcode]' => $value['mem_zip'],
		'delivery[pref]' => $value['mem_pref'],
		'delivery[address1]' => $value['mem_address1'],
		'delivery[address2]' => $value['mem_address2'],
		'delivery[address3]' => $value['mem_address3'],
		'delivery[tel]' => $value['mem_tel'],
		'delivery[fax]' => $value['mem_fax'],
	);
	
	$member_metetable = usces_get_tablename( 'usces_member_meta' );
	$query = $wpdb->prepare("SELECT * FROM $member_metetable WHERE meta_key LIKE %s AND member_id = %d", 'csmb_%', $value['ID']);
	$customs = $wpdb->get_results( $query, ARRAY_A );
	if( !empty($customs) ){
		foreach( $customs as $cusv ){
			$response['custom_customer[' . substr($cusv['meta_key'], 5) . ']'] = $cusv['meta_value'];
			$response['custom_delivery[' . substr($cusv['meta_key'], 5) . ']'] = $cusv['meta_value'];
		}
	}
	
	wp_send_json($response);
}

function usces_add_ordercartdata(){
	global $usces, $wpdb;

	$res = 0;
	
	$order_id = (int)$_POST['order_id'];
	if( !$order_id )
		return $res;

	$post_id = (int)$_POST['post_id'];
	$sku_code = urldecode($_POST['sku']);
	$quantity = 1;
	
	$current_cart = usces_get_ordercartdata( $order_id );
	$temp_arr = array();
	foreach( $current_cart as $cv ){
		$temp_arr[] = $cv['row_index'];
	}
	$row_index = ( 0 < count($temp_arr) ) ? max($temp_arr) + 1 : 0;
	
	$cart_table = $wpdb->prefix . "usces_ordercart";
	$cart_meta_table = $wpdb->prefix . "usces_ordercart_meta";

	$product   = wel_get_product( $post_id );
	$item_name  = $product['itemName'];
	$item_code = $product['itemCode'];
	$skus      = $usces->get_skus($post_id, 'code');
	$sku       = apply_filters( 'usces_filter_add_ordercart_sku', $skus[$sku_code], $_POST );
	$tax       = 0;

	$query = $wpdb->prepare("INSERT INTO $cart_table 
		(
		order_id, row_index, post_id, item_code, item_name, 
		sku_code, sku_name, cprice, price, quantity, 
		unit, tax, destination_id, cart_serial 
		) VALUES (
		%d, %d, %d, %s, %s, 
		%s, %s, %f, %f, %d, 
		%s, %d, %d, %s 
		)", 
		$order_id, $row_index, $post_id, $item_code, $item_name, 
		$sku_code, $sku['name'], $sku['cprice'], $sku['price'], $quantity, 
		$sku['unit'], $tax, NULL, NULL
	);
	$res = $wpdb->query($query);
	$cart_id = NULL;
	if( false !== $res ){
		$cart_id = $wpdb->insert_id ;

		$item_opts = usces_get_opts( $post_id, 'name' );
		foreach( $item_opts as $key => $iopts ){
			if( 3 == $iopts['means'] || 4 == $iopts['means'] ){
				//POSTが無ければNULLで追加
				if( !isset($_POST['itemOption'][$key]) ){
					$query = $wpdb->prepare(
						"INSERT INTO $cart_meta_table 
						( cart_id, meta_type, meta_key, meta_value ) VALUES ( %d, %s, %s, %s )", 
						$cart_id, 'option', $iopts['name'], NULL );
					$wpdb->query( $query );
				}
			}
		}

		if( isset($_POST['itemOption']) ){
			foreach((array)$_POST['itemOption'] as $okey => $ovalue){
				$means = $item_opts[$okey]['means'];
				
				if(is_array($ovalue)) {
					$temp = array();
					if( 4 == $means ){
						foreach( $ovalue as $k => $v ){
							$temp[] = $v;
						}
					}else{
						foreach( $ovalue as $k => $v ){
							$temp[urlencode($k)] = $v;
						}
					}
					$ovalue = serialize($temp);
				} else {
					$ovalue = $ovalue;
				}
				$aquery = $wpdb->prepare(
					"INSERT INTO $cart_meta_table 
					( cart_id, meta_type, meta_key, meta_value ) VALUES (%d, %s, %s, %s)", 
					$cart_id, 'option', $okey, $ovalue );
				$wpdb->query($aquery);
			}
		}

		if( $usces->is_reduced_taxrate() ) {
			if( isset( $sku['taxrate'] ) && 'reduced' == $sku['taxrate'] ) {
				$tkey = 'reduced';
				$tvalue = $usces->options['tax_rate_reduced'];
			} else {
				$tkey = 'standard';
				$tvalue = $usces->options['tax_rate'];
			}
			$tquery = $wpdb->prepare( "INSERT INTO $cart_meta_table 
				( cart_id, meta_type, meta_key, meta_value ) VALUES ( %d, 'taxrate', %s, %s )", 
				$cart_id, $tkey, $tvalue
			);
			$wpdb->query( $tquery );
		}
	}

	$res = apply_filters( 'usces_filter_add_ordercart', $res, $order_id, $cart_id );

	if( $res )
		return $order_id;
	else
		return $res;
}

function get_order_item( $item_code ) {
	global $usces, $post;
	
	$post_id = wel_get_id_by_item_code( $item_code );
	if ( $post_id == null ) {
		return false;
	}
	$product = wel_get_product( $post_id );
	$post = $product['_pst'];
	$res = apply_filters( 'usce_action_ajax_get_order_item', false, $post );
	if ( false !== $res ) {
		return $res;
	}

	$pict_id = wel_get_main_pict_id_by_code( $item_code );
	$pict_link = wp_get_attachment_image($pict_id, array(150, 150), true);
	preg_match("/^\<a .+\>(\<img .+\/\>)\<\/a\>$/", $pict_link, $match);
	$pict = isset($match[1]) ? $match[1] : '';
	$skus = $product['_sku'];
	$optkeys = $usces->get_itemOptionKey( $post_id );
	$itemName = esc_html( $product['itemName'] );
	
	$r = '';
	$r .= $pict_link . "\n";
	$r .= "<h3>" . $itemName . "</h3>\n";
	$r .= "<div class='skuform'>\n";

	$r .= "<table class='skumulti'>\n";
	$r .= "<thead>\n";
	$r .= "<tr>\n";
	$r .= "<th>" . __('SKU code','usces') . "</th>\n";
	$r .= "<th>" . __('SKU display name ','usces') . "</th>\n";
	$usces_listprice = __('List price', 'usces') . usces_guid_tax('return');
	$r .= "<th>" . apply_filters('usces_filter_listprice_label', $usces_listprice, __('List price', 'usces'), usces_guid_tax('return')) . "</th>\n";
	$usces_sellingprice = __('Sale price','usces') . usces_guid_tax('return');
	$r .= "<th>" . apply_filters('usces_filter_sellingprice_label', $usces_sellingprice, __('Sale price', 'usces'), usces_guid_tax('return')) . "</th>\n";
	$r .= "<th>" . __('stock status','usces') . "</th>\n";
	$r .= "<th>" . __('stock','usces') . "</th>\n";
	$r .= "<th>" . __('unit','usces') . "</th>\n";
	$r .= "<th>&nbsp;</th>\n";
	$r .= "</tr>\n";
	$r .= "</thead>\n";
	$r .= "<tbody>\n";
	foreach($skus as $sku) :
		$key = urlencode($sku['code']);
		$cprice = esc_attr($sku['cprice']);
		$price = esc_attr($sku['price']);
		$zaiko = esc_attr($usces->zaiko_status[$sku['stock']]);
		$zaikonum = esc_attr($sku['stocknum']);
		$disp = esc_attr($sku['name']);
		$unit = esc_attr($sku['unit']);
		$gptekiyo = $sku['gp'];
		$sort = (int)$sku['sort'];
		$r .= "<tr>\n";
		$r .= "<td rowspan='2'>" . esc_js($sku['code']) . "</td>\n";
		$r .= "<td>" . $disp . "</td>\n";
		$r .= "<td><span class='cprice'>" . ( ( !empty($cprice) ) ? usces_crform( $cprice, true, false, 'return' ) : '' ) . "</span></td>\n";
		$r .= "<td><span class='price'>" . usces_crform( $price, true, false, 'return' ) . "</span></td>\n";
		$r .= "<td>" . $zaiko . "</td>\n";
		$r .= "<td>" . $zaikonum . "</td>\n";
		$r .= "<td>" . $unit . "</td>\n";
		$r .= "<td>\n";
		$r .= "<input name=\"itemNEWName[{$post_id}][{$key}]\" type=\"hidden\" id=\"itemNEWName[{$post_id}][{$key}]\" value=\"{$itemName}\" />\n";
		$r .= "<input name=\"itemNEWCode[{$post_id}][{$key}]\" type=\"hidden\" id=\"itemNEWCode[{$post_id}][{$key}]\" value=\"{$item_code}\" />\n";
		$r .= "<input name=\"skuNEWName[{$post_id}][{$key}]\" type=\"hidden\" id=\"skuNEWName[{$post_id}][{$key}]\" value=\"{$key}\" />\n";
		$r .= "<input name=\"skuNEWCprice[{$post_id}][{$key}]\" type=\"hidden\" id=\"skuNEWCprice[{$post_id}][{$key}]\" value=\"{$cprice}\" />\n";
		$r .= "<input name=\"skuNEWDisp[{$post_id}][{$key}]\" type=\"hidden\" id=\"skuNEWDisp[{$post_id}][{$key}]\" value=\"{$disp}\" />\n";
		$r .= "<input name=\"zaikoNEWnum[{$post_id}][{$key}]\" type=\"hidden\" id=\"zaikoNEWnum[{$post_id}][{$key}]\" value=\"{$zaikonum}\" />\n";
		$r .= "<input name=\"zaiNEWko[{$post_id}][{$key}]\" type=\"hidden\" id=\"zaiNEWko[{$post_id}][{$key}]\" value=\"{$zaiko}\" />\n";
		$r .= "<input name=\"uniNEWt[{$post_id}][{$key}]\" type=\"hidden\" id=\"uniNEWt[{$post_id}][{$key}]\" value=\"{$unit}\" />\n";
		$r .= "<input name=\"gpNEWtekiyo[{$post_id}][{$key}]\" type=\"hidden\" id=\"gpNEWtekiyo[{$post_id}][{$key}]\" value=\"{$gptekiyo}\" />\n";
		$r .= "<input name=\"skuNEWPrice[{$post_id}][{$key}]\" type=\"hidden\" id=\"skuNEWPrice[{$post_id}][{$key}]\" value=\"{$price}\" />\n";
		$r .= "<input name=\"inNEWCart[{$post_id}][{$key}]\" type=\"button\" id=\"inNEWCart[{$post_id}][{$key}]\" class=\"skubutton button\" value=\"" . __('Add to Whish List','usces') . "\" onclick=\"orderItem.add2cart('{$post_id}', '{$key}');\" />";
		$r .= "</td>\n";
		$r .= "</tr>\n";
		$r .= "<tr>\n";
		if($optkeys) :
			$r .= "<td colspan='7'>\n";
			foreach($optkeys as $optkey => $optvalue) :
				$r .= "<div>\n";
				$name = esc_attr($optvalue);
				$optcode = urlencode($name);
				$opts = usces_get_opts($post_id, 'name');
				$opt = $opts[$optvalue];
				$opt['value'] = usces_change_line_break( $opt['value'] );
				$means = (int)$opt['means'];
				$essential = (int)$opt['essential'];
				$r .= "\n<label for='itemNEWOption[{$post_id}][{$key}][{$optcode}]' class='iopt_label'>{$name}</label>\n";
				switch($means) {
				case 0://Single-select
				case 1://Multi-select
					$selects = explode("\n", $opt['value']);
					$multiple = ($means === 0) ? '' : ' multiple';
					$multiple_array = ($means === 0) ? '' : '_multiple';
					
					$r .= "\n<select name='itemNEWOption[{$post_id}][{$key}][{$optcode}]' id='itemNEWOption[{$post_id}][{$key}][{$optcode}]' class='iopt_select{$multiple_array}'{$multiple}>\n";
					if($essential == 1)
						$r .= "\t<option value='#NONE#' selected='selected'>" . __('Choose','usces') . "</option>\n";
					$s=0;
					foreach($selects as $v) {
						$v = trim($v);
						if($s == 0 && $essential == 0) 
							$selected = ' selected="selected"';
						else
							$selected = '';
						$r .= "\t<option value='{$v}'{$selected}>{$v}</option>\n";
						$s++;
					}
					$r .= "</select>\n";
					break;
				case 2://Text
					$r .= "\n<input name='itemNEWOption[{$post_id}][{$key}][{$optcode}]' type='text' id='itemNEWOption[{$post_id}][{$key}][{$optcode}]' class='iopt_text' onKeyDown=\"if (event.keyCode == 13) {return false;}\" value=\"\" />\n";
					break;
				case 3://Radio-button
					$selects = explode("\n", $opt['value']);
			
					$i=0;
					foreach($selects as $v) {
						$r .= '<label for="itemNEWOption[' . $post_id . '][' . $key . '][' . $optcode . ']' . $i . '" class="iopt_radio_label"><input name="itemNEWOption[' . $post_id . '][' . $key . '][' . $optcode . ']" type="radio" id="itemNEWOption[' . $post_id . '][' . $key . '][' . $optcode . ']' . $i . '" class="iopt_radio" value="' . urlencode($v) . '">' . esc_html($v) . "</label>\n";
						$i++;
					}
					break;
				case 4://Check-box
					$selects = explode("\n", $opt['value']);
			
					$i=0;
					foreach($selects as $v) {
						$r .= '<label for="itemNEWOption[' . $post_id . '][' . $key . '][' . $optcode . ']' . $i . '" class="iopt_checkbox_label"><input name="itemNEWOption[' . $post_id . '][' . $key . '][' . $optcode . ']" type="checkbox" id="itemNEWOption[' . $post_id . '][' . $key . '][' . $optcode . ']' . $i . '" class="iopt_checkbox" value="' . urlencode($v) . '">' . esc_html($v) . "</label>\n";
						$i++;
					}
					break;
				case 5://Text-area
					$r .= "\n<textarea name='itemNEWOption[{$post_id}][{$key}][{$optcode}]' id='itemNEWOption[{$post_id}][{$key}][{$optcode}]' class='iopt_textarea'></textarea>\n";
					break;
				}
				$r .= "<input name=\"optNEWCode[{$post_id}][{$key}][{$optcode}]\" type=\"hidden\" id=\"optNEWCode[{$post_id}][{$key}][{$optcode}]\" value=\"{$optcode}\" />\n";
				$r .= "<input name=\"optNEWEssential[{$post_id}][{$key}][{$optcode}]\" type=\"hidden\" id=\"optNEWEssential[{$post_id}][{$key}][{$optcode}]\" value=\"{$essential}\" />\n";
				$r .= "</div>\n";
			endforeach;
			$r .= "</td>\n";
		endif;
		$r .= "</tr>\n";
	endforeach;
	$r .= "</tbody>\n";
	$r .= "</table>\n";

	$r .= "</div>\n";

	$r = apply_filters( 'usces_filter_get_order_item', $r, $item_code, $post_id );

	return $r;
}

function item_option_ajax()
{

	if( $_POST['action'] != 'item_option_ajax' ) die(0);
	
	$post_id = (int)$_POST['ID'];
	
	if(isset($_POST['update'])){
		$id = up_item_option_meta( $post_id );
		
	}else if(isset($_POST['delete'])){
		$id = del_item_option_meta( $post_id );
		
	}else if(isset($_POST['select'])){
		$res = select_common_option( $post_id );
		wp_send_json($res);
		
	}else if(isset($_POST['sort'])){
		$id = usces_sort_post_meta( $post_id, $_POST['meta'] );
		
	}else{
		$id = add_item_option_meta( $post_id );
		
	}
		
	$opts = usces_get_opts( $post_id );
	
	$r = '';
	foreach ( $opts as $opt ){
		$r .= _list_item_option_meta_row( $opt );
	}

	$response = array(
		'meta_id' => $id,
		'meta_row' => $r
	);
	wp_send_json($response);
}

function item_sku_ajax(){
	global $usces;
	
	$id = '';
	if( $_POST['action'] != 'item_sku_ajax' ) die(0);
	
	$post_id = (int)$_POST['ID'];
	$msg = '';

	if(isset($_POST['update'])){
		$id = up_item_sku_meta( $post_id );
		
	}else if(isset($_POST['delete'])){
		$id = del_item_sku_meta( $post_id );
		
	}else if(isset($_POST['select'])){
		$response = select_item_sku( $post_id );
		wp_send_json($response);
		
	}else if(isset($_POST['sort'])){
		$id = usces_sort_post_meta( $post_id, $_POST['meta'] );
		
	}else{
		$id = add_item_sku_meta( $post_id );
		
	}
	$msg .= apply_filters( 'usces_filter_item_sku_message', $msg, $id, $post_id );	
	//$skus = $usces->get_skus( $post_id );
	$skus = wel_get_skus( $post_id, 'sort', false );

	$r = '';
	
	foreach ( (array)$skus as $sku )
		$r .= _list_item_sku_meta_row( $sku );
	
	$response = array(
		'meta_id' => $id,
		'meta_row' => $r,
		'meta_msg' => $msg
	);
	
	wp_send_json($response);
}

function item_save_metadata( $post_id, $post ) {
	global $usces, $wpdb;

	$message   = '';
	$item_data = array();

	// Permission check.
	if ( isset( $_POST['page'] ) && 'usces_itemedit' === $_POST['page'] ) {
		if ( ! current_user_can( 'edit_post', $post_id ) ){
			$usces->set_action_status( 'error', 'ERROR : ' . __( 'Sorry, you do not have the right to edit this post.' ) );
			return $post_id;
		}
	} else {
		return $post_id;
	}

	if ( ! wp_verify_nonce( $_POST['usces_nonce'], 'usc-e-shop' ) ) {
		return $post_id;
	}

	// Check if it is an automatic save routine. If so, do not submit the form (do nothing).
	if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
		return $post_id;
	}

	// Do nothing when saving other plugins.
	if ( isset( $post->post_type ) && 'post' !== $post->post_type ) {
		return $post_id;
	}

	$usces->set_item_mime( $post_id, 'item' );

	$itemCode = trim( $_POST['itemCode' ] );
	if ( preg_match( '/[^0-9a-zA-Z\-_]/', $itemCode ) ) {

	}
	if ( empty( $itemCode ) ) {
		$itemCode  = '';
		$message  .= __( 'Product code has not been entered.', 'usces' ) . '<br />';
	} elseif ( $res = usces_is_same_itemcode($post->ID, $itemCode ) ) {
		$message .= 'post_ID ';
		foreach ( $res as $postid ) {
			$message .= $postid . ', ';
		}
		$message .= 'Same product code is registered here.' . '<br />';
		$usces->set_action_status( 'error', 'ERROR : ' . $message );
	}
	$item_data['_itemCode'] = $itemCode;

	if ( isset( $_POST['itemName'] ) ) {
		$item_data['_itemName'] = trim( $_POST['itemName'] );
	}
	if ( isset( $_POST['itemRestriction'] ) ) {
		$item_data['_itemRestriction'] = trim( $_POST['itemRestriction'] );
	}
	if ( isset( $_POST['itemPointrate'] ) ) {
		$item_data['_itemPointrate'] = ( int ) $_POST['itemPointrate'];
	}
	if ( isset( $_POST['itemGpNum1'] ) ) {
		$item_data['_itemGpNum1'] = ( int ) $_POST['itemGpNum1'];
	}
	if ( isset( $_POST['itemGpNum2'] ) ) {
		$item_data['_itemGpNum2'] = ( int ) $_POST['itemGpNum2'];
	}
	if ( isset( $_POST['itemGpNum3'] ) ) {
		$item_data['_itemGpNum3'] = ( int ) $_POST['itemGpNum3'];
	}
	if ( isset( $_POST['itemGpDis1'] ) ) {
		$item_data['_itemGpDis1'] = ( int ) $_POST['itemGpDis1'];
	}
	if ( isset( $_POST['itemGpDis2'] ) ) {
		$item_data['_itemGpDis2'] = ( int ) $_POST['itemGpDis2'];
	}
	if ( isset( $_POST['itemGpDis3'] ) ) {
		$item_data['_itemGpDis3'] = ( int ) $_POST['itemGpDis3'];
	}

	$item_data['_itemOrderAcceptable'] = isset( $_POST['itemOrderAcceptable'] ) ? 1 : 0;

	if ( isset( $_POST['itemShipping'] ) ) {
		$item_data['_itemShipping'] = ( int ) $_POST['itemShipping'];
	}
	if ( isset( $_POST['itemDeliveryMethod'] ) ) {
		$itemDeliveryMethod = array();
		foreach ( ( array ) $_POST["itemDeliveryMethod"] as $dmid ) { 
			$itemDeliveryMethod[] = $dmid;
		}
		$item_data['_itemDeliveryMethod'] = $itemDeliveryMethod;
	}
	if ( isset( $_POST['itemShippingCharge'] ) ) {
		$item_data['_itemShippingCharge'] = ( int ) $_POST['itemShippingCharge'];
	}

	$item_data['_itemIndividualSCharge'] = isset( $_POST['itemIndividualSCharge'] ) ? 1 : 0;

	$options = get_option( 'usces' );
	$acting_settings = ( isset( $options['acting_settings'] ) ) ? $options['acting_settings'] : array();
	if ( isset( $acting_settings['welcart'] ) && 'on' === $acting_settings['welcart']['atodene_byitem'] ) {
		$item_data['atodene_propriety'] = isset( $_POST['atodene_propriety'] ) ? (int) $_POST['atodene_propriety'] : 0;
	}

	$options = get_option('usces_ex');
	$system_settings = $options['system'];
	if ( isset( $system_settings['system']['atobaraicsv'] ) && 1 === (int) $acting_settings['system']['atobaraicsv']['each_item'] ) {
		$item_data['atobarai_propriety'] = isset( $_POST['deferred_payment_propriety'] ) ? (int) $_POST['deferred_payment_propriety'] : 0;
	}


	wel_update_item_data( $item_data, $post_id, true );

/*
	if(isset($_POST['wcexp'])){
		$wcexp = serialize($_POST['wcexp']);
		update_post_meta($post_id, '_wcexp', $wcexp);
	}
*/
	//SKU
	if ( isset( $_POST['itemsku'] ) ) {

		$meta_ids    = array();
		$codes       = array();
		$uniq_code   = false;
		$irreg_code  = false;
		$irreg_price = false;

		foreach ( $_POST['itemsku'] as $mid => $temp ) {
			$meta_ids[] = $mid;
		}
		$meta_ids = array_unique( $meta_ids );
		foreach ( $meta_ids as $meta_id ) {

			$skucode = isset( $_POST['itemsku'][ $meta_id ]['key'] ) ? trim( $_POST['itemsku'][ $meta_id ]['key'] ) : '';
			$msgsku  = apply_filters( 'usces_filter_before_item_save_metadata_sku', null, $post_id, $meta_id, $skucode );
			if ( ! empty( $msgsku ) ) {
				$message .= $msgsku;
				continue;
			}

			$skucprice   = isset( $_POST['itemsku'][ $meta_id ]['cprice'] ) ? trim( $_POST['itemsku'][ $meta_id ]['cprice'] ): 0;
			$skuprice    = isset( $_POST['itemsku'][ $meta_id ]['price'] ) ? trim( $_POST['itemsku'][ $meta_id ]['price'] ): 0;
			$skustocknum = isset( $_POST['itemsku'][ $meta_id ]['zaikonum'] ) ? trim( $_POST['itemsku'][ $meta_id ]['zaikonum'] ): 0;
			$skustock    = isset( $_POST['itemsku'][ $meta_id ]['zaiko'] ) ? (int) $_POST['itemsku'][ $meta_id ]['zaiko'] : '';
			$skuname     = isset( $_POST['itemsku'][ $meta_id ]['skudisp'] ) ? trim( $_POST['itemsku'][ $meta_id ]['skudisp'] ): '';
			$skuunit     = isset( $_POST['itemsku'][ $meta_id ]['skuunit'] ) ? trim( $_POST['itemsku'][ $meta_id ]['skuunit'] ): '';
			$skugp       = isset( $_POST['itemsku'][ $meta_id ]['skugptekiyo'] ) ? (int) $_POST['itemsku'][ $meta_id ]['skugptekiyo'] : 0;
			$skusort     = isset( $_POST['itemsku'][ $meta_id ]['sort'] ) ? $_POST['itemsku'][ $meta_id ]['sort']: 0;
			$skutaxrate  = isset( $_POST['itemsku'][ $meta_id ]['applicable_taxrate'] ) ? $_POST['itemsku'][ $meta_id ]['applicable_taxrate'] : '';

			$sku['code']     = $skucode;
			$sku['name']     = $skuname;
			$sku['cprice']   = $skucprice;
			$sku['price']    = $skuprice;
			$sku['unit']     = $skuunit;
			$sku['stocknum'] = $skustocknum;
			$sku['stock']    = $skustock;
			$sku['gp']       = $skugp;
			$sku['sort']     = $skusort;
			if ( ! empty( $skutaxrate ) ) {
				$sku['taxrate'] = $skutaxrate;
			}
			$sku = $usces->stripslashes_deep_post( $sku );
			$sku = apply_filters( 'usces_filter_item_save_sku_metadata', $sku, $meta_id );

			wel_update_sku_data_by_id( $meta_id, $post_id, $sku );

			if ( in_array( $skucode, $codes ) ) {
				$uniq_code = true;
			}

			if ( WCUtils::is_blank( $skucode ) ) {
				$irreg_code = true;
			}

			if ( WCUtils::is_blank( $skuprice ) || preg_match( '/[^0-9.]/', $skuprice ) || 1 < substr_count( $skuprice, '.' ) ) {
				$irreg_price = true;
			}

			$codes[] = $skucode;
		}

		if ( $uniq_code ) {
			$message .= __( 'SKU code is duplicated.', 'usces' ) . "<br />";
		}
		if ( $irreg_code ) {
			$message .= __( 'SKU code is invalid.', 'usces' ) . "<br />";
		}
		if ( $irreg_price ) {
			$message .= __( 'SKU of invalid selling price exists.', 'usces' ) . "<br />";
		}
	}

	//OPT
	if ( isset($_POST['itemopt']) ) {
		$meta_ids    = array();
		$names       = array();
		$uniq_name   = false;
		$irreg_name  = false;
		$irreg_value = false;

		foreach ( $_POST['itemopt'] as $mid => $temp ) {
			$meta_ids[] = $mid;
		}
		$meta_ids = array_unique( $meta_ids );
		foreach ( $meta_ids as $meta_id ) {

			$optname      = isset( $_POST['itemopt'][ $meta_id ]['name'] ) ? $_POST['itemopt'][ $meta_id ]['name'] : '';
			$optmeans     = isset( $_POST['itemopt'][ $meta_id ]['means'] ) ? (int) $_POST['itemopt'][ $meta_id ]['means'] : 0;
			$optessential = isset( $_POST['itemopt'][ $meta_id ]['essential'] ) ? $_POST['itemopt'][ $meta_id ]['essential'] : 0;
			$optsort      = isset( $_POST['itemopt'][ $meta_id ]['sort'] ) ? $_POST['itemopt'][ $meta_id ]['sort'] : 0;
			$optvalue     = isset( $_POST['itemopt'][ $meta_id ]['value'] ) ? trim( $_POST['itemopt'][ $meta_id ]['value']) : '';
			
			$opt['name']      = str_replace( "\\", '', $optname );
			$opt['value']     = str_replace( "\\", '', $optvalue );
			$opt['means']     = $optmeans;
			$opt['essential'] = $optessential;
			$opt['sort']      = $optsort;

			$opt = $usces->stripslashes_deep_post( $opt );

			wel_update_opt_data_by_id( $meta_id, $post_id, $opt );

			if ( in_array( $optname, $names ) ) {
				$uniq_name = true;
			}

			if ( WCUtils::is_blank( $optname ) ) {
				$irreg_name = true;
			}

			if ( WCUtils::is_blank( $optvalue ) && 1 >= $optmeans ) {
				$irreg_value = true;
			}

			$names[] = $optname;
		}

		if ( $uniq_name ) {
			$message .= __( "Commodity option option name duplicates exist.", "usces" ) . "<br />";
		}
		if ( $irreg_name ) {
			$message .= __( "Commodity option not entered there is the option name.", "usces" ) . "<br />";
		}
		if ( $irreg_value ) {
			$message .= __( "If you select 'single select' and 'multi-select' the trade option, please enter the select value.", "usces" ) . "<br />";
		}
	}

	do_action( 'usces_action_save_product', $post_id, $post );
	$message = apply_filters( 'usces_filter_save_product_message', $message, $post_id );

	if ( $message ) {
		$usces->set_action_status( 'error', 'ERROR : ' . $message );
	} else {
		$usces->set_action_status( 'success', __( 'Registration of the product is complete.', 'usces' ) );
	}

	wp_cache_delete( $post_id, 'post_meta' );
}

function usces_link_replace($para) {
	$str = 'admin.php?page=usces_itemedit&';
	$url = preg_replace('|post\.php\?|i', $str, $para);
	return $url;

}

function usces_count_posts( $type = 'post', $perm = '' ) {
	global $wpdb;

	$user = wp_get_current_user();

	$cache_key = $type;

	$query = "SELECT post_status, COUNT( * ) AS `num_posts` FROM {$wpdb->posts} WHERE post_type = %s AND post_mime_type = 'item'";
	if ( 'readable' == $perm && is_user_logged_in() ) {
		if ( !current_user_can("read_private_{$type}s") ) {
			$cache_key .= '_' . $perm . '_' . $user->ID;
			$query .= " AND (post_status != 'private' OR ( post_author = '$user->ID' AND post_status = 'private' ))";
		}
	}
	$query .= ' GROUP BY post_status';

	$count = wp_cache_get($cache_key, 'counts');
	if ( false !== $count )

	$count = $wpdb->get_results( $wpdb->prepare( $query, $type ), ARRAY_A );

	$stats = array( );
	foreach( (array) $count as $row_num => $row ) {
		$stats[$row['post_status']] = $row['num_posts'];
	}

	$stats = (object) $stats;
	wp_cache_set($cache_key, $stats, 'counts');

	return $stats;
}

/**
 * custom order meta row
 */
function _list_custom_order_meta_row($key, $entry) {
	$r = '';
	$style = '';
	$key = esc_attr($key);

	$name = esc_attr($entry['name']);
	$means = get_option('usces_custom_order_select');
	$meansoption = '';
	foreach($means as $meankey => $meanvalue) {
		if($meankey == $entry['means']) {
			$selected = ' selected="selected"';
		} else {
			$selected = '';
		}
		$meansoption .= '<option value="'.esc_attr($meankey).'"'.$selected.'>'.esc_html($meanvalue)."</option>\n";
	}
	$essential = $entry['essential'] == 1 ? " checked='checked'" : "";
	$value = '';
	if(is_array($entry['value'])) {
		foreach($entry['value'] as $k => $v) {
			$value .= $v."\n";
		}
	}
	$value = esc_attr(trim($value));

	$r .= "\n\t<tr id='csod-{$key}' class='{$style}'>";
	$r .= "\n\t\t<td class='left'><div><input type='text' name='csod[{$key}][key]' id='csod[{$key}][key]' class='optname' size='20' value='{$key}' readonly /></div>";
	$r .= "\n\t\t<div><input type='text' name='csod[{$key}][name]' id='csod[{$key}][name]' class='optname' size='20' value='{$name}' /></div>";
	$r .= "\n\t\t<div class='optcheck'><select name='csod[{$key}][means]' id='csod[{$key}][means]'>".$meansoption."</select>\n";
	$r .= "<input type='checkbox' name='csod[{$key}][essential]' id='csod[{$key}][essential]' value='1'{$essential} /><label for='csod[{$key}][essential]'>".__('Required','usces')."</label></div>";
	$r .= "\n\t\t<div class='submit'><input type='button' class='button' name='del_csod[{$key}]' id='del_csod[{$key}]' value='".esc_attr(__( 'Delete' ))."' onclick='customField.delOrder(\"{$key}\");' />";
	$r .= "\n\t\t<input type='button' class='button' name='upd_csod[{$key}]' id='upd_csod[{$key}]' value='".esc_attr(__( 'Update' ))."' onclick='customField.updOrder(\"{$key}\");' /></div>";
	$r .= "\n\t\t<div id='csod_loading-{$key}' class='meta_submit_loading'></div>";
	$r .= "</td>";
	$r .= "\n\t\t<td class='item-opt-value'><textarea name='csod[{$key}][value]' id='csod[{$key}][value]' class='optvalue'>{$value}</textarea></td>\n\t</tr>";
	return $r;
}

/**
 * has custom field meta
 */
function usces_has_custom_field_meta($fieldname) {
	switch($fieldname) {
	case 'order':
		$field = 'usces_custom_order_field';
		break;
	case 'customer':
		$field = 'usces_custom_customer_field';
		break;
	case 'delivery':
		$field = 'usces_custom_delivery_field';
		break;
	case 'member':
		$field = 'usces_custom_member_field';
		break;
	case 'admin_member':
		$field = 'usces_admin_custom_member_field';
		break;
	default:
		return array();
	}
	$fields = get_option($field);
	if( empty($fields) ){
		$meta = array();
	}elseif( is_array($fields) ){
		$meta = $fields;
	}else{
		$meta = unserialize($fields);
	}
	return $meta;
}

function usces_getinfo_ajax(){
	global $wp_version;
	$wcex_str = '';
	$res = '';
	$wcex = usces_get_wcex();
	foreach ( (array)$wcex as $key => $values ) {
		$wcex_str .= $key . "-" . $values['version'] . ",";
	}
	$wcex_str = rtrim($wcex_str, ',');
	if ( version_compare($wp_version, '3.4', '>=') ){
		$theme_ob = wp_get_theme();
		$themedata['Name'] = $theme_ob->get('Name');
		$themedata['Version'] = $theme_ob->get('Version');
	}else{
		$themedata = get_theme_data( get_stylesheet_directory().'/style.css' );
	}


	$v = urlencode(USCES_VERSION);
	$wcid = urlencode(get_option('usces_wcid'));
	$locale = urlencode(get_locale());
	$theme = urlencode($themedata['Name'] . '-' . $themedata['Version']);
	$wcex = urlencode($wcex_str);
	$interface_url = 'http://www.welcart.com/util/welcart_information2.php';
	$wcurl = urlencode(get_home_url());
	$interface = parse_url($interface_url);

	$vars ="v=$v&wcid=$wcid&locale=$locale&theme=$theme&wcex=$wcex&wcurl=$wcurl";
	$header = "POST " . $interface_url . " HTTP/1.1\r\n";
	$header .= "Host: " . $_SERVER['HTTP_HOST'] . "\r\n";
	$header .= "User-Agent: " . $_SERVER['HTTP_USER_AGENT'] . "\r\n";
	$header .= "Content-Type: application/x-www-form-urlencoded\r\n";
	$header .= "Content-Length: " . strlen($vars) . "\r\n";
	$header .= "Connection: close\r\n\r\n";
	$header .= $vars;
	$fp = fsockopen($interface['host'],80,$errno,$errstr,30);
	if( $fp ){
		fwrite($fp, $header);
		$i=0;
		while ( !feof($fp) ) {
			$scr = fgets($fp, 10240);
			preg_match("/<(title|data)>(.*)<(\/title|\/data)>$/", $scr, $match);
		
			if(!empty($match[2])){
				switch( $match[1] ){
					case'title': 
						$res .= '<div style="text-align: center;border-bottom: 1px dotted #CCCCCC;width: 80%;margin-bottom: 10px;padding-bottom: 3px; margin-right: auto; margin-left: auto;"><stlong>' . $match[2] . '</strong></div><ul>';
						break;
					case 'data':
						$res .= '<li>' . $match[2] . '</li>';
						break;
				}
			}
			$i++;
			if($i>50) {
				$res = 'ERROR';
				break;
			}
		}
		
		$res .= '</ul>';
		fclose($fp);
		
	}else{
		$res = 'ERROR';
	}
	die($res);
}

/**
 * custom field ajax
 */
function custom_field_ajax() {
	global $usces;

	check_admin_referer( 'custom_field_ajax', 'wc_nonce' );
	$_POST = $usces->stripslashes_deep_post( $_POST );
	$data = array( 'status' => 'OK', 'msg' => '');

	if( $_POST['action'] != 'custom_field_ajax' ) {
		$data['status'] = 'NG';
		wp_send_json( $data );
	}

	switch( $_POST['field'] ) {
	case 'order':
		$field = 'usces_custom_order_field';
		break;
	case 'customer':
		$field = 'usces_custom_customer_field';
		break;
	case 'delivery':
		$field = 'usces_custom_delivery_field';
		break;
	case 'member':
		$field = 'usces_custom_member_field';
		break;
	case 'admin_member':
		$field = 'usces_admin_custom_member_field';
		break;
	default:
		$data['status'] = 'NG';
		wp_send_json( $data );
	}

	$meta = usces_has_custom_field_meta( $_POST['field'] );
	$dupkey = 0;
	$dupname = 0;

	if( isset( $_POST['add'] ) ) {
		$newkey = ( isset( $_POST['newkey'] ) ) ? trim( $_POST['newkey'] ) : '';
		$newname = ( isset( $_POST['newname'] ) ) ? trim( $_POST['newname'] ) : '';
		$newmeans = ( isset( $_POST['newmeans'] ) ) ? $_POST['newmeans'] : 0;
		$newessential = ( isset( $_POST['newessential'] ) ) ? $_POST['newessential'] : 0;
		$newposition = ( isset( $_POST['newposition'] ) ) ? trim( $_POST['newposition'] ) : '';

		if( $newmeans == 2 || $newmeans == 5 ) {//Text or Textarea
			$newvalue = '';
			$nv = $newvalue;
			$required_entry = ( !empty( $newkey ) && !empty( $newname ) ) ? true : false;
		} else {
			$newvalue = ( isset( $_POST['newvalue'] ) ) ? explode( '\n', trim( $_POST['newvalue'] ) ) : '';
			foreach( (array)$newvalue as $v ) {
				if( !WCUtils::is_blank( $v ) ) {
					$nv[] = trim( $v);
				}
			}
			$required_entry = ( ( !empty( $newvalue ) ) && !empty( $newkey ) && !empty( $newname ) ) ? true : false;
		}

		if( !array_key_exists( $newkey, $meta ) ) {
			if( $required_entry ) {
				$meta[$newkey]['name'] = $newname;
				$meta[$newkey]['means'] = $newmeans;
				$meta[$newkey]['essential'] = $newessential;
				$meta[$newkey]['value'] = $nv;
				if( !WCUtils::is_blank( $newposition ) ) {
					$meta[$newkey]['position'] = $newposition;
				} else if ( ( 'admin_member' === $_POST['field'] ) && WCUtils::is_blank( $newposition ) ) {
					$meta[$newkey]['position'] = 'fax_after';
				}
				update_option( $field, $meta );
			}
		} else {
			$dupkey = 1;
		}

	} elseif( isset( $_POST['update'] ) ) {
		$key = ( isset( $_POST['key'] ) ) ? trim( $_POST['key'] ) : '';
		$name = ( isset( $_POST['name'] ) ) ? trim( $_POST['name'] ) : '';
		$means = ( isset( $_POST['means'] ) ) ? $_POST['means'] : 0;
		$essential = ( isset( $_POST['essential'] ) ) ? $_POST['essential'] : 0;
		$position = ( isset( $_POST['position'] ) ) ? trim( $_POST['position'] ) : '';

		if( $means == 2 || $means == 5 ) {//Text or Textarea
			$value = '';
			$nv = $value;
			$required_entry = ( !empty( $key ) && !empty( $name ) ) ? true : false;

		} else {
			$value = ( isset( $_POST['value'] ) ) ? explode( '\n', trim( $_POST['value'] ) ) : '';
			foreach( (array)$value as $v ) {
				if( !WCUtils::is_blank( $v ) ) {
					$nv[] = trim( $v );
				}
			}
			$required_entry = ( ( !empty( $value ) ) && !empty( $key ) && !empty( $name ) ) ? true : false;
		}

		if( $required_entry ) {
			$meta[$key]['name'] = $name;
			$meta[$key]['means'] = $means;
			$meta[$key]['essential'] = $essential;
			$meta[$key]['value'] = $nv;
			if( !WCUtils::is_blank( $position ) ) {
				$meta[$key]['position'] = $position;
			}
			update_option( $field, $meta );
		}

	} elseif( isset( $_POST['delete'] ) ) {
		$key = ( isset( $_POST['key'] ) ) ? trim( $_POST['key'] ) : '';
		unset( $meta[$key] );
		update_option( $field, $meta );
	}

	$r = '';
	switch( $_POST['field'] ) {
	case 'order':
		foreach( $meta as $key => $entry ) {
			$r .= _list_custom_order_meta_row( $key, $entry );
		}
		break;
	case 'customer':
		foreach( $meta as $key => $entry ) {
			$r .= _list_custom_customer_meta_row( $key, $entry );
		}
		break;
	case 'delivery':
		foreach( $meta as $key => $entry ) {
			$r .= _list_custom_delivery_meta_row( $key, $entry );
		}
		break;
	case 'member':
		foreach( $meta as $key => $entry ) {
			$r .= _list_custom_member_meta_row( $key, $entry );
		}
		break;
	case 'admin_member':
		foreach( $meta as $key => $entry ) {
			$r .= _list_admin_custom_member_meta_row( $key, $entry );
		}
		break;
	}

	//$res = $r . '#usces#' . $dupkey;
	//die($res);

	$data['list'] = $r;
	$data['dupkey'] = $dupkey;
	$data['dupname'] = $dupname;
	wp_send_json( $data );
}

/**
 * list custom customer meta row
 */
function _list_custom_customer_meta_row($key, $entry) {
	$r = '';
	$style = '';
	$key = esc_attr($key);

	$name = esc_attr($entry['name']);
	$means = get_option('usces_custom_customer_select');
	$meansoption = '';
	foreach($means as $meankey => $meanvalue) {
		$selected = ($meankey == $entry['means']) ? " selected='selected'" : "";
		$meansoption .= "<option value='".esc_attr($meankey)."'".$selected.">".esc_html($meanvalue)."</option>\n";
	}
	$essential = $entry['essential'] == 1 ? " checked='checked'" : "";
	$value = '';
	if(is_array($entry['value'])) {
		foreach($entry['value'] as $k => $v) {
			$value .= $v."\n";
		}
	}
	$value = esc_attr(trim($value));
	$positions = get_option('usces_custom_field_position_select');
	$positionsoption = '';
	foreach($positions as $poskey => $posvalue) {
		$selected = ($poskey == $entry['position']) ? " selected='selected'" : "";
		$positionsoption .= "<option value='".esc_attr($poskey)."'".$selected.">".esc_html($posvalue)."</option>\n";
	}

	$r .= "\n\t<tr id='cscs-{$key}' class='{$style}'>";
	$r .= "\n\t\t<td class='left'><div><input type='text' name='cscs[{$key}][key]' id='cscs[{$key}][key]' class='optname' size='20' value='{$key}' readonly /></div>";
	$r .= "\n\t\t<div><input type='text' name='cscs[{$key}][name]' id='cscs[{$key}][name]' class='optname' size='20' value='{$name}' /></div>";
	$r .= "\n\t\t<div class='optcheck'><select name='cscs[{$key}][means]' id='cscs[{$key}][means]'>".$meansoption."</select>\n";
	$r .= "<input type='checkbox' name='cscs[{$key}][essential]' id='cscs[{$key}][essential]' value='1'{$essential} /><label for='cscs[{$key}][essential]'>".__('Required','usces')."</label>\n";
	$r .= "<select name='cscs[{$key}][position]' id='cscs[{$key}][position]'>".$positionsoption."</select></div>";
	$r .= "\n\t\t<div class='submit'><input type='button' class='button' name='del_cscs[{$key}]' id='del_cscs[{$key}]' value='".esc_attr(__( 'Delete' ))."' onclick='customField.delCustomer(\"{$key}\");' />";
	$r .= "\n\t\t<input type='button' class='button' name='upd_cscs[{$key}]' id='upd_cscs[{$key}]' value='".esc_attr(__( 'Update' ))."' onclick='customField.updCustomer(\"{$key}\");' /></div>";
	$r .= "\n\t\t<div id='cscs_loading-{$key}' class='meta_submit_loading'></div>";
	$r .= "</td>";
	$r .= "\n\t\t<td class='item-opt-value'><textarea name='cscs[{$key}][value]' id='cscs[{$key}][value]' class='optvalue'>{$value}</textarea></td>\n\t</tr>";
	return $r;
}

/**
 * list custom delivery meta row
 */
function _list_custom_delivery_meta_row($key, $entry) {
	$r = '';
	$style = '';
	$key = esc_attr($key);

	$name = esc_attr($entry['name']);
	$means = get_option('usces_custom_delivery_select');
	$meansoption = '';
	foreach($means as $meankey => $meanvalue) {
		$selected = ($meankey == $entry['means']) ? " selected='selected'" : "";
		$meansoption .= "<option value='".esc_attr($meankey)."'".$selected.">".esc_html($meanvalue)."</option>\n";
	}
	$essential = $entry['essential'] == 1 ? " checked='checked'" : "";
	$value = '';
	if(is_array($entry['value'])) {
		foreach($entry['value'] as $k => $v) {
			$value .= $v."\n";
		}
	}
	$value = esc_attr(trim($value));
	$positions = get_option('usces_custom_field_position_select');
	$positionsoption = '';
	foreach($positions as $poskey => $posvalue) {
		$selected = ($poskey == $entry['position']) ? " selected='selected'" : "";
		$positionsoption .= "<option value='".esc_attr($poskey)."'".$selected.">".esc_html($posvalue)."</option>\n";
	}

	$r .= "\n\t<tr id='csde-{$key}' class='{$style}'>";
	$r .= "\n\t\t<td class='left'><div><input type='text' name='csde[{$key}][key]' id='csde[{$key}][key]' class='optname' size='20' value='{$key}' readonly /></div>";
	$r .= "\n\t\t<div><input type='text' name='csde[{$key}][name]' id='csde[{$key}][name]' class='optname' size='20' value='{$name}' /></div>";
	$r .= "\n\t\t<div class='optcheck'><select name='csde[{$key}][means]' id='csde[{$key}][means]'>".$meansoption."</select>\n";
	$r .= "<input type='checkbox' name='csde[{$key}][essential]' id='csde[{$key}][essential]' value='1'{$essential} /><label for='csde[{$key}][essential]'>".__('Required','usces')."</label>\n";
	$r .= "<select name='csde[{$key}][position]' id='csde[{$key}][position]'>".$positionsoption."</select></div>";
	$r .= "\n\t\t<div class='submit'><input type='button' class='button' name='del_csde[{$key}]' id='del_csde[{$key}]' value='".esc_attr(__( 'Delete' ))."' onclick='customField.delDelivery(\"{$key}\");' />";
	$r .= "\n\t\t<input type='button' class='button' name='upd_csde[{$key}]' id='upd_csde[{$key}]' value='".esc_attr(__( 'Update' ))."' onclick='customField.updDelivery(\"{$key}\");' /></div>";
	$r .= "\n\t\t<div id='csde_loading-{$key}' class='meta_submit_loading'></div>";
	$r .= "</td>";
	$r .= "\n\t\t<td class='item-opt-value'><textarea name='csde[{$key}][value]' id='csde[{$key}][value]' class='optvalue'>{$value}</textarea></td>\n\t</tr>";
	return $r;
}

/**
 * list custom member meta row
 */
function _list_custom_member_meta_row($key, $entry) {
	$r = '';
	$style = '';
	$key = esc_attr($key);

	$name = esc_attr($entry['name']);
	$means = get_option('usces_custom_member_select');
	$meansoption = '';
	foreach($means as $meankey => $meanvalue) {
		$selected = ($meankey == $entry['means']) ? " selected='selected'" : "";
		$meansoption .= "<option value='".esc_attr($meankey)."'".$selected.">".esc_html($meanvalue)."</option>\n";
	}
	$essential = $entry['essential'] == 1 ? " checked='checked'" : "";
	$value = '';
	if(is_array($entry['value'])) {
		foreach($entry['value'] as $k => $v) {
			$value .= $v."\n";
		}
	}
	$value = esc_attr(trim($value));
	$positions = get_option('usces_custom_field_position_select');
	$positionsoption = '';
	foreach($positions as $poskey => $posvalue) {
		$selected = ($poskey == $entry['position']) ? " selected='selected'" : "";
		$positionsoption .= "<option value='".esc_attr($poskey)."'".$selected.">".esc_attr($posvalue)."</option>\n";
	}
	$r .= "\n\t<tr id='csmb-{$key}' class='{$style}'>";
	$r .= "\n\t\t<td class='left'><div><input type='text' name='csmb[{$key}][key]' id='csmb[{$key}][key]' class='optname' size='20' value='{$key}' readonly /></div>";
	$r .= "\n\t\t<div><input type='text' name='csmb[{$key}][name]' id='csmb[{$key}][name]' class='optname' size='20' value='{$name}' /></div>";
	$r .= "\n\t\t<div class='optcheck'><select name='csmb[{$key}][means]' id='csmb[{$key}][means]'>".$meansoption."</select>\n";
	$r .= "<input type='checkbox' name='csmb[{$key}][essential]' id='csmb[{$key}][essential]' value='1'{$essential} /><label for='csmb[{$key}][essential]'>".__('Required','usces')."</label>\n";
	$r .= "<select name='csmb[{$key}][position]' id='csmb[{$key}][position]'>".$positionsoption."</select></div>";
	$r .= "\n\t\t<div class='submit'><input type='button' class='button' name='del_csmb[{$key}]' id='del_csmb[{$key}]' value='".esc_attr(__( 'Delete' ))."' onclick='customField.delMember(\"{$key}\");' />";
	$r .= "\n\t\t<input type='button' class='button' name='upd_csmb[{$key}]' id='upd_csmb[{$key}]' value='".esc_attr(__( 'Update' ))."' onclick='customField.updMember(\"{$key}\");' /></div>";
	$r .= "\n\t\t<div id='csmb_loading-{$key}' class='meta_submit_loading'></div>";
	$r .= "</td>";
	$r .= "\n\t\t<td class='item-opt-value'><textarea name='csmb[{$key}][value]' id='csmb[{$key}][value]' class='optvalue'>{$value}</textarea></td>\n\t</tr>";
	return $r;
}

/**
 * list admin custom member meta row
 */
function _list_admin_custom_member_meta_row( $key, $entry ) {
	$r     = '';
	$style = '';
	$key   = esc_attr( $key );

	$name        = esc_attr( $entry['name'] );
	$means       = get_option( 'usces_custom_member_select' );
	$meansoption = '';
	foreach ( $means as $meankey => $meanvalue ) {
		$selected     = ( $meankey == $entry['means'] ) ? " selected='selected'" : '';
		$meansoption .= "<option value='" . esc_attr( $meankey ) . "'" . $selected . '>' . esc_html( $meanvalue ) . "</option>\n";
	}
	$essential = ( 1 == $entry['essential'] ) ? " checked='checked'" : '';
	$value     = '';
	if ( is_array( $entry['value'] ) ) {
		foreach ( $entry['value'] as $k => $v ) {
			$value .= $v . "\n";
		}
	}
	$value = esc_attr( trim( $value ) );
	$r    .= "\n\t<tr id='admb-{$key}' class='{$style}'>";
	$r    .= "\n\t\t<td class='left'><div><input type='text' name='admb[{$key}][key]' id='admb[{$key}][key]' class='optname' size='20' value='{$key}' readonly /></div>";
	$r    .= "\n\t\t<div><input type='text' name='admb[{$key}][name]' id='admb[{$key}][name]' class='optname' size='20' value='{$name}' /></div>";
	$r    .= "\n\t\t<div class='optcheck'><select name='admb[{$key}][means]' id='admb[{$key}][means]'>" . $meansoption . "</select>\n";
	$r    .= "<input type='checkbox' name='admb[{$key}][essential]' id='admb[{$key}][essential]' value='1'{$essential} /><label for='admb[{$key}][essential]'>" . __( 'Required', 'usces' ) . "</label>\n";
	$r    .= '</div>';
	$r    .= "\n\t\t<div class='submit'><input type='button' class='button' name='del_admb[{$key}]' id='del_admb[{$key}]' value='" . esc_attr( __( 'Delete' ) ) . "' onclick='customField.delAdmb(\"{$key}\");' />";
	$r    .= "\n\t\t<input type='button' class='button' name='upd_admb[{$key}]' id='upd_admb[{$key}]' value='" . esc_attr( __( 'Update' ) ) . "' onclick='customField.updAdmb(\"{$key}\");' /></div>";
	$r    .= "\n\t\t<div id='admb_loading-{$key}' class='meta_submit_loading'></div>";
	$r    .= '</td>';
	$r    .= "\n\t\t<td class='item-opt-value'><textarea name='admb[{$key}][value]' id='admb[{$key}][value]' class='optvalue'>{$value}</textarea></td>\n\t</tr>";
	return $r;
}

function change_states_ajax(){
	global $usces, $usces_states;
	$_POST = $usces->stripslashes_deep_post($_POST);
	
	$c = $_POST['country'];
	$res = '';
	$prefs = get_usces_states($c);
	if(is_array($prefs) and 0 < count($prefs)) {
		foreach((array)$prefs as $state) {
			$res .= '<option value="' . $state . '">' . $state . '</option>';
		}
	} else {
		die('error');
	}
	die($res);
}

function get_usces_states($country) {
	global $usces, $usces_states;

	$states = array();
	$prefs = maybe_unserialize($usces->options['province']);
	if( !isset($prefs[$country]) || empty($prefs[$country]) ) {
		if($country == $usces->options['system']['base_country']) {
			foreach((array)$prefs as $state) {
				if(!is_array($state))
					array_push($states, $state);
			}
			if(count($states) == 0) {
				if( !empty($usces_states[$country]) ) {
					$prefs = $usces_states[$country];
					if(is_array($prefs)) {
						$states = $prefs;
					}
				}
			}
		} else {
			if( !empty($usces_states[$country]) ) {
				$prefs = $usces_states[$country];
				if(is_array($prefs)) {
					$states = $prefs;
				}
			}
		}
	} else {
		$states = $prefs[$country];
	}
	return $states;
}

function target_market_ajax() {
	global $usces;

	$_POST = $usces->stripslashes_deep_post($_POST);
	$response = [];
	$target = explode(",", $_POST['target']);
	foreach((array)$target as $country) {
		$prefs = get_usces_states($country);
		if(is_array($prefs) && !empty($prefs)) {
			$pos = strpos($prefs[0], '--');
			if($pos !== false) array_shift($prefs);
			$response[] = $country . ',' . implode("\n", $prefs);
		} else {
			$response[] = $country;
		}
	}
	wp_send_json($response);
}

function usces_admin_ajax() {
	switch($_POST['mode']) {
	case 'options_backup':
		check_admin_referer( 'options_backup', 'wc_nonce' );
		$options = get_option('usces');
		$res = true;
		if( is_array($options) ) {
			$usces_backup_date = current_time('mysql');
			update_option('usces_backup', $options);
			update_option('usces_backup_date', $usces_backup_date);
			$res = $usces_backup_date;
		} else {
			$res = false;
		}
		die($res);
		break;
	case 'options_restore':
		check_admin_referer( 'options_restore', 'wc_nonce' );
		$options = get_option('usces_backup');
		$res = true;
		if( is_array($options) ) {
			update_option('usces', $options);
		} else {
			$res = false;
		}
		die($res);
		break;
	}
	do_action('usces_action_admin_ajax');
}

function usces_order_recalculation( $order_id, $mem_id, $post_id, $price, $quant, $cart_id, $use_point, $shipping_charge, $cod_fee, $discount = 0, $change_taxrate = '') {
	global $usces;

	$data = array();
	$res = 'ok';

	$cart = array();
	$post_id_count = ( is_array( $post_id ) ) ? count( $post_id ) : 0;
	for( $i = 0; $i < $post_id_count; $i++ ) {
		if( $post_id[$i] ) {
			$cart[] = array( "post_id"=>$post_id[$i], "price"=>(float)$price[$i], "quantity"=>(float)$quant[$i] );
		}
	}

	if( 'change' == $change_taxrate ) {
		if( usces_is_reduced_taxrate() ) {
			$usces_tax = Welcart_Tax::get_instance();
			$usces_tax->set_order_condition_reduced_taxrate( $order_id );
			for( $i = 0; $i < $post_id_count; $i++ ) {
				if( $cart_id[$i] ) {
					$taxrate = usces_get_ordercart_meta( 'taxrate', $cart_id[$i] );
					if( !$taxrate ) {
						$ordercart = usces_get_ordercartdata_row( $cart_id[$i] );
						$sku['taxrate'] = $usces_tax->get_sku_applicable_taxrate( $post_id[$i], $ordercart['sku_code'] );
						$usces_tax->set_ordercart_applicable_taxrate( $cart_id[$i], $sku );
					}
				}
			}
			$data['status'] = $res;
			$data['tax_mode'] = 'reduced';
			wp_send_json( $data );
		}
		$condition = $usces->get_condition();
	} else {
		if( !empty( $order_id ) ) {
			$condition = usces_get_order_condition( $order_id );
		} else {
			$condition = $usces->get_condition();
		}
	}

	$tax_display = ( isset( $condition['tax_display'] ) ) ? $condition['tax_display'] : usces_get_tax_display();
	$member_system = ( isset( $condition['membersystem_state'] ) ) ? $condition['membersystem_state'] : $usces->options['membersystem_state'];
	$member_system_point = ( isset( $condition['membersystem_point'] ) ) ? $condition['membersystem_point'] : $usces->options['membersystem_point'];
	$tax_mode = ( isset( $condition['tax_mode'] ) ) ? $condition['tax_mode'] : usces_get_tax_mode();
	$tax_target = ( isset( $condition['tax_target'] ) ) ? $condition['tax_target'] : usces_get_tax_target();
	$point_coverage = ( isset( $condition['point_coverage'] ) ) ? $condition['point_coverage'] : usces_point_coverage();

	$total_items_price = 0;
	foreach( $cart as $cart_row ) {
		$total_items_price += $cart_row['price'] * $cart_row['quantity'];
	}
	$meminfo = $usces->get_member_info( $mem_id );

	if( empty( $discount ) || 'NaN' == $discount ) {
		$discount = 0;
	}
	if( 'change' == $change_taxrate ) {
		$discount = 0;
		if( isset( $condition['display_mode'] ) && $condition['display_mode'] == 'Promotionsale' ) {
			if( isset( $condition['campaign_privilege'] ) && $condition['campaign_privilege'] == 'discount' ) {
				if ( 0 === (int)$condition['campaign_category'] ) {
					$discount = (float)sprintf( '%.3f', $total_items_price * (float)$condition['privilege_discount'] / 100 );
				} else {
					foreach( $cart as $cart_row ) {
						if( in_category( (int)$condition['campaign_category'], $cart_row['post_id'] ) ) {
							$discount += (float)sprintf( '%.3f', $cart_row['price'] * $cart_row['quantity'] * (float)$condition['privilege_discount'] / 100 );
						}
					}
				}
			}
		}
		if( 0 != $discount ) {
			$decimal = $usces->get_currency_decimal();
			if( 0 == $decimal ) {
				$discount = ceil( $discount );
			} else {
				$decipad = (int)str_pad( '1', $decimal+1, '0', STR_PAD_RIGHT );
				$discount = ceil( $discount * $decipad ) / $decipad;
			}
			$discount = $discount * -1;
		}
	}
	$discount = apply_filters( 'usces_filter_order_discount_recalculation', $discount, $cart, $condition, $order_id );

	$point = 0;
	if( empty( $use_point ) || 'NaN' == $use_point ) {
		$use_point = 0;
	}
	if( 'activate' == $member_system && 'activate' == $member_system_point && !empty( $meminfo['ID'] ) ) {
		if( isset( $condition['display_mode'] ) && $condition['display_mode'] == 'Promotionsale' ) {
			if( isset( $condition['campaign_privilege'] ) && $condition['campaign_privilege'] == 'discount' ) {
				foreach( $cart as $cart_row ) {
					$cats = $usces->get_post_term_ids( $cart_row['post_id'], 'category' );
					if( !in_array( $condition['campaign_category'], $cats ) ) {
						$product   = wel_get_product( $cart_row['post_id'] );
						$rate      = (float) $product['itemPointrate'];
						$price     = $cart_row['price'] * $cart_row['quantity'];
						$point     = (float) sprintf( '%.3f', $point + ( $price * $rate / 100 ) );
					}
				}
			} elseif( isset( $condition['campaign_privilege'] ) && $condition['campaign_privilege'] == 'point' ) {
				foreach( $cart as $cart_row ) {
					$product = wel_get_product( $cart_row['post_id'] );
					$rate    = (float) $product['itemPointrate'];
					$price   = $cart_row['price'] * $cart_row['quantity'];
					$cats    = $usces->get_post_term_ids( $cart_row['post_id'], 'category' );
					if( in_array( $condition['campaign_category'], $cats ) ) {
						$point = sprintf( '%.3f', $point + ( $price * $rate / 100 * (float)$condition['privilege_point'] ) );
					} else {
						$point = sprintf( '%.3f', $point + ( $price * $rate / 100 ) );
					}
				}
			}
		} else {
			foreach( $cart as $cart_row ) {
				$product = wel_get_product( $cart_row['post_id'] );
				$rate    = (float) $product['itemPointrate'];
				$price   = $cart_row['price'] * $cart_row['quantity'];
				$point   = sprintf( '%.3f', $point + ( $price * $rate / 100 ) );
			}
		}

		if( 0 < $use_point ) {
			$point = (float)sprintf( '%.3f', $point - ( $point * (int)$use_point / $total_items_price ) );
			$point = ceil( $point );
			if( 0 > $point ) {
				$point = 0;
			}
		} else {
			if( 0 < $point ) {
				$point = ceil( $point );
			}
		}
	}
	$point = apply_filters( 'usces_filter_set_point_recalculation', $point, $condition, $cart, $meminfo, $use_point, $order_id );

	$total_price = $total_items_price - $use_point + $discount + $shipping_charge + $cod_fee;
	if( $total_price < 0 ) $total_price = 0;
	$total_price = apply_filters( 'usces_filter_set_cart_fees_total_price', $total_price, $total_items_price, $use_point, $discount, $shipping_charge, $cod_fee );//Deprecated
	$total_price = apply_filters( 'usces_filter_order_total_price_recalculation', $total_price, $total_items_price, $use_point, $discount, $shipping_charge, $cod_fee, $cart, $order_id );
	$materials = compact( 'total_items_price', 'shipping_charge', 'discount', 'cod_fee', 'use_point', 'condition' );
	if( 'activate' == $tax_display ) {
		if( 'include' == $tax_mode ) {
			$tax = 0;
			$include_tax = usces_internal_tax( $materials, 'return' );
		} else {
			//$tax = $usces->getTax( $total_price, $materials );
			if( 1 == $point_coverage ) {
				if( 'products' == $tax_target ) {
					$total = (float)$total_items_price + (float)$discount;
				} else {
					$total = (float)$total_items_price + (float)$discount + (float)$shipping_charge + (float)$cod_fee;
				}
			} else {
				if( 'products' == $tax_target ) {
					$total = (float)$total_items_price + (float)$discount;
				} else {
					if( empty( $use_point ) ) $use_point = 0;
					$total = (float)$total_items_price + (float)$discount - (int)$use_point + (float)$shipping_charge + (float)$cod_fee;
				}
			}
			$tax = (float)sprintf( '%.3f', (float)$total * (float)$condition['tax_rate'] / 100 );
			$tax = usces_tax_rounding_off( $tax, $condition['tax_method'] );
			$include_tax = 0;
		}
	} else {
		$tax = 0;
		$include_tax = 0;
	}
	$total_full_price = $total_price + $tax;
	$total_full_price = apply_filters( 'usces_filter_set_cart_fees_total_full_price', $total_full_price, $total_items_price, $use_point, $discount, $shipping_charge, $cod_fee );//Deprecated
	$total_full_price = apply_filters( 'usces_filter_order_total_full_price_recalculation', $total_full_price, $total_items_price, $use_point, $discount, $shipping_charge, $cod_fee, $cart, $order_id );

	$data['status'] = $res;
	$data['tax_mode'] = 'standard';
	$data['discount'] = $discount;
	$data['tax'] = usces_crform( $tax, false, false, 'return', false );
	$data['include_tax'] = ( 0 < $include_tax ) ? '(' . usces_crform( $include_tax, false, false, 'return', true ) . ')' : '';
	$data['point'] = $point;
	$data['total_full_price'] = usces_crform( $total_full_price, false, false, 'return', false );
	wp_send_json( $data );
}

function usces_order_recalculation_reduced( $order_id, $mem_id, $post_id, $price, $quant, $cart_id, $use_point, $shipping_charge, $cod_fee, $discount_standard = 0, $discount_reduced = 0, $change_taxrate = '') {
	global $usces;
	$usces_tax = Welcart_Tax::get_instance();

	$data = array();
	$res = 'ok';

	if( 'change' == $change_taxrate ) {
		$condition = $usces->get_condition();
		$usces_tax->set_order_condition_reduced_taxrate( $order_id );
	} else {
		if( !empty( $order_id ) ) {
			$condition = usces_get_order_condition( $order_id );
		} else {
			$condition = $usces->get_condition();
		}
	}

	$tax_rate = ( isset( $condition['tax_rate'] ) ) ? (float)$condition['tax_rate'] : (float)$usces->options['tax_rate'];
	$tax_rate_reduced = ( isset( $condition['tax_rate_reduced'] ) ) ? (float)$condition['tax_rate_reduced'] : (float)$usces->options['tax_rate_reduced'];
	$tax_display = ( isset( $condition['tax_display'] ) ) ? $condition['tax_display'] : usces_get_tax_display();
	$member_system = ( isset( $condition['membersystem_state'] ) ) ? $condition['membersystem_state'] : $usces->options['membersystem_state'];
	$member_system_point = ( isset( $condition['membersystem_point'] ) ) ? $condition['membersystem_point'] : $usces->options['membersystem_point'];
	$tax_mode = ( isset( $condition['tax_mode'] ) ) ? $condition['tax_mode'] : usces_get_tax_mode();
	$tax_target = ( isset( $condition['tax_target'] ) ) ? $condition['tax_target'] : usces_get_tax_target();
	$point_coverage = ( isset( $condition['point_coverage'] ) ) ? $condition['point_coverage'] : usces_point_coverage();

	$cart = array();
	$post_id_count = ( is_array( $post_id ) ) ? count( $post_id ) : 0;
	for( $i = 0; $i < $post_id_count; $i++ ) {
		if( $post_id[$i] ) {
			$ordercart = usces_get_ordercartdata_row( $cart_id[$i] );
			$applicable_taxrate = ( 'change' == $change_taxrate ) ? $usces_tax->get_sku_applicable_taxrate( $post_id[$i], $ordercart['sku_code'] ) : $usces_tax->get_ordercart_applicable_taxrate( $cart_id[$i], $post_id[$i], $ordercart['sku_code'] );
			$cart[] = array(
				'post_id'=>$post_id[$i],
				'sku_code'=>$ordercart['sku_code'],
				'price'=>(float)$price[$i],
				'quantity'=>(float)$quant[$i],
				'taxrate'=>$applicable_taxrate,
			);
		}
	}

	$subtotal_standard = 0;
	$subtotal_reduced = 0;
	foreach( $cart as $cart_row ) {
		if( 'reduced' == $cart_row['taxrate'] ) {
			$subtotal_reduced += (float)$cart_row['price'] * (float)$cart_row['quantity'];
		} else {
			$subtotal_standard += (float)$cart_row['price'] * (float)$cart_row['quantity'];
		}
	}
	$total_items_price = $subtotal_standard + $subtotal_reduced;
	$meminfo = $usces->get_member_info( $mem_id );

	if( 'change' == $change_taxrate ) {
		$discount = 0;
		$discount_standard = 0;
		$discount_reduced = 0;
		if( isset( $condition['display_mode'] ) && $condition['display_mode'] == 'Promotionsale' ) {
			if( isset( $condition['campaign_privilege'] ) && $condition['campaign_privilege'] == 'discount' ) {
				if( 0 === (int)$condition['campaign_category'] ) {
					$discount_standard = (float)sprintf( '%.3f', (float)$subtotal_standard * (float)$condition['privilege_discount'] / 100 );
					$discount_reduced = (float)sprintf( '%.3f', (float)$subtotal_reduced * (float)$condition['privilege_discount'] / 100 );
				} else {
					foreach( $cart as $cart_row ) {
						if( in_category( (int)$condition['campaign_category'], $cart_row['post_id'] ) ) {
							$items_discount = (float)sprintf( '%.3f', (float)$cart_row['price'] * (float)$cart_row['quantity'] * (float)$condition['privilege_discount'] / 100 );
							if( 'reduced' == $cart_row['taxrate'] ) {
								$discount_reduced += $items_discount;
							} else {
								$discount_standard += $items_discount;
							}
						}
					}
				}
				if( 0 != $discount_standard || 0 != $discount_reduced ) {
					$decimal = $usces->get_currency_decimal();
					if( 0 == $decimal ) {
						$discount_standard = ceil( $discount_standard );
						$discount_reduced = ceil( $discount_reduced );
					} else {
						$decipad = (int)str_pad( '1', $decimal+1, '0', STR_PAD_RIGHT );
						$discount_standard = ceil( $discount_standard * $decipad ) / $decipad;
						$discount_reduced = ceil( $discount_reduced * $decipad ) / $decipad;
					}
					$discount_standard *= -1;
					$discount_reduced *= -1;
					$discount = $discount_standard + $discount_reduced;
				}
			}
		}
	}
	$discount = $discount_standard + $discount_reduced;
	$discount = apply_filters( 'usces_filter_order_discount_recalculation', $discount, $cart, $condition, $order_id );

	$point = 0;
	if( empty( $use_point ) || 'NaN' == $use_point ) {
		$use_point = 0;
	}
	if( 'activate' == $member_system && 'activate' == $member_system_point && !empty( $meminfo['ID'] ) ) {
		if( isset( $condition['display_mode'] ) && $condition['display_mode'] == 'Promotionsale' ) {
			if( isset( $condition['campaign_privilege'] ) && $condition['campaign_privilege'] == 'discount' ) {
				foreach( $cart as $cart_row ) {
					$cats = $usces->get_post_term_ids( $cart_row['post_id'], 'category' );
					if( !in_array( $condition['campaign_category'], $cats ) ) {
						$product = wel_get_product( $cart_row['post_id'] );
						$rate    = (float) $product['itemPointrate'];
						$price   = $cart_row['price'] * $cart_row['quantity'];
						$point   = (float) sprintf( '%.3f', $point + ( $price * $rate / 100 ) );
					}
				}
			} elseif( isset( $condition['campaign_privilege'] ) && $condition['campaign_privilege'] == 'point' ) {
				foreach( $cart as $cart_row ) {
					$product = wel_get_product( $cart_row['post_id'] );
					$rate    = (float) $product['itemPointrate'];
					$price   = $cart_row['price'] * $cart_row['quantity'];
					$cats    = $usces->get_post_term_ids( $cart_row['post_id'], 'category' );
					if( in_array( $condition['campaign_category'], $cats ) ) {
						$point = sprintf( '%.3f', $point + ( $price * $rate / 100 * (float)$condition['privilege_point'] ) );
					} else {
						$point = sprintf( '%.3f', $point + ( $price * $rate / 100 ) );
					}
				}
			}
		} else {
			foreach( $cart as $cart_row ) {
				$product = wel_get_product( $cart_row['post_id'] );
				$rate    = (float) $product['itemPointrate'];
				$price   = $cart_row['price'] * $cart_row['quantity'];
				$point   = sprintf( '%.3f', $point + ( $price * $rate / 100 ) );
			}
		}

		if( 0 < $use_point ) {
			$point = (float)sprintf( '%.3f', $point - ( $point * (int)$use_point / $total_items_price ) );
			$point = ceil( $point );
			if( 0 > $point ) {
				$point = 0;
			}
		} else {
			if( 0 < $point ) {
				$point = ceil( $point );
			}
		}
	}
	$point = apply_filters( 'usces_filter_set_point_recalculation', $point, $condition, $cart, $meminfo, $use_point, $order_id );

	$total_price = $total_items_price - $use_point + $discount + $shipping_charge + $cod_fee;
	if( $total_price < 0 ) $total_price = 0;
	$total_price = apply_filters( 'usces_filter_set_cart_fees_total_price', $total_price, $total_items_price, $use_point, $discount, $shipping_charge, $cod_fee );//Deprecated
	$total_price = apply_filters( 'usces_filter_order_total_price_recalculation', $total_price, $total_items_price, $use_point, $discount, $shipping_charge, $cod_fee, $cart, $order_id );

	$tax = 0;
	$tax_standard = 0;
	$tax_reduced = 0;
	$include_tax = 0;
	if( 'activate' == $tax_display ) {
		if( 'all' == $tax_target ) {
			if( 0 < $shipping_charge ) {
				$subtotal_standard += (float)$shipping_charge;
			}
			if( 0 < $cod_fee ) {
				$subtotal_standard += (float)$cod_fee;
			}
		}

		if( 'include' == $tax_mode ) {
			if( 0 < $subtotal_standard ) {
				$tax_standard = (float)sprintf( '%.3f', ( (float)$subtotal_standard + (float)$discount_standard ) * $tax_rate / ( 100 + $tax_rate ) );
			}
			if( 0 < $subtotal_reduced ) {
				$tax_reduced = (float)sprintf( '%.3f', ( (float)$subtotal_reduced + (float)$discount_reduced ) * $tax_rate_reduced / ( 100 + $tax_rate_reduced ) );
			}
		} else {
			if( 0 < $subtotal_standard ) {
				$tax_standard = (float)sprintf( '%.3f', ( (float)$subtotal_standard + (float)$discount_standard ) * $tax_rate / 100 );
			}
			if( 0 < $subtotal_reduced ) {
				$tax_reduced = (float)sprintf( '%.3f', ( (float)$subtotal_reduced + (float)$discount_reduced ) * $tax_rate_reduced / 100 );
			}
		}

		$tax_standard = usces_tax_rounding_off( $tax_standard, $condition['tax_method'] );
		$tax_reduced = usces_tax_rounding_off( $tax_reduced, $condition['tax_method'] );

		$materials = compact( 'total_items_price', 'shipping_charge', 'discount', 'cod_fee', 'use_point', 'cart' );
		if( 'include' == $tax_mode ) {
			$include_tax = $tax_standard + $tax_reduced;
			$total_full_price = $total_price;
		} else {
			$tax = apply_filters( 'usces_filter_order_tax_recalculation', $tax_standard + $tax_reduced, $materials );
			$total_full_price = $total_price + $tax;
		}
	} else {
		$total_full_price = $total_price;
	}
	$total_full_price = apply_filters( 'usces_filter_set_cart_fees_total_full_price', $total_full_price, $total_items_price, $use_point, $discount, $shipping_charge, $cod_fee );//Deprecated
	$total_full_price = apply_filters( 'usces_filter_order_total_full_price_recalculation', $total_full_price, $total_items_price, $use_point, $discount, $shipping_charge, $cod_fee, $cart, $order_id );

	$data['status'] = $res;
	$data['tax_mode'] = 'reduced';
	$data['discount'] = $discount;
	$data['tax'] = $tax;
	$data['point'] = $point;
	$data['total_full_price'] = $total_full_price;
	$data['subtotal_standard'] = $subtotal_standard;
	$data['subtotal_reduced'] = $subtotal_reduced;
	//if( 'change' == $change_taxrate ) {
		$data['discount_standard'] = $discount_standard;
		$data['discount_reduced'] = $discount_reduced;
	//}
	$data['tax_standard'] = $tax_standard;
	$data['tax_reduced'] = $tax_reduced;
	$data['include_tax'] = $include_tax;
	wp_send_json( $data );
}

function usces_sku_meta_row_reduced_taxrate( $sku ) {
	global $usces;

	if( $usces->is_reduced_taxrate() ):
		$standard = $usces->options['tax_rate'];
		$reduced = $usces->options['tax_rate_reduced'];
		$taxrate = ( isset( $sku['taxrate'] ) ) ? $sku['taxrate'] : '';
		if( 'reduced' == $taxrate ) {
			$selected_standard = '';
			$selected_reduced = ' selected="selected"';
		} else {
			$selected_standard = ' selected="selected"';
			$selected_reduced = '';
		}
	?>
		<select id="itemsku[<?php echo $sku['meta_id']; ?>][applicable_taxrate]" name="itemsku[<?php echo $sku['meta_id']; ?>][applicable_taxrate]" class="sku_applicable_taxrate" >
			<option value="standard"<?php echo $selected_standard; ?>><?php _e( 'Standard tax rate', 'usces' ); ?>(<?php echo $standard; ?>%)</option>
			<option value="reduced"<?php echo $selected_reduced; ?>><?php _e( 'Reduced tax rate', 'usces' ); ?>(<?php echo $reduced; ?>%)</option>
		</select>
	<?php
	endif;
}

function usces_newsku_meta_row_reduced_taxrate() {
	global $usces;

	if( $usces->is_reduced_taxrate() ):
		$standard = $usces->options['tax_rate'];
		$reduced = $usces->options['tax_rate_reduced'];
	?>
		<select id="newsku_applicable_taxrate" name="newsku_applicable_taxrate" class="newsku_applicable_taxrate" >
			<option value="standard"><?php _e( 'Standard tax rate', 'usces' ); ?>(<?php echo $standard; ?>%)</option>
			<option value="reduced"><?php _e( 'Reduced tax rate', 'usces' ); ?>(<?php echo $reduced; ?>%)</option>
		</select>
	<?php
	endif;
}

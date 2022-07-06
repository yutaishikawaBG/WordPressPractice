<?php
/**
 * Welcart item base class
 *
 * @package  Welcart
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Database check.
 *
 * Database update check for each version.
 *
 * @since 2.6
 */
function wel_db_check() {

	if ( ! is_admin() ) {
		return;
	}
	if ( ! function_exists( 'get_plugin_data' ) ) {
		require_once( ABSPATH . 'wp-admin/includes/plugin.php' );
	}

	$plugin_file     = USCES_WP_PLUGIN_DIR . '/' . USCES_PLUGIN_BASENAME;
	$plugin_info     = get_plugin_data( $plugin_file );
	$current_version = strtolower( $plugin_info['Version'] );
	$update_history  = get_option( 'usces_db_version' );
	$action_status   = '';
	$first_install   = wel_is_first_install();

	if ( version_compare( $current_version, '2.6-beta', '>=' ) ) {
		$action_status = '2.6';
		if ( ! isset( $update_history[ $action_status ] ) ) {
			if ( $first_install ) {
				$update_history[ $action_status ] = 1;
			} else {
				$update_history[ $action_status ] = 0;
			}
			update_option( 'usces_db_version', $update_history );
		}
	};
	/*
	if ( version_compare( $current_version, '2.0-beta', '>=' ) ) {
		$action_status = '2.0';
		if ( ! isset( $update_history[ $action_status ] ) ) {
			$update_history[ $action_status ] = 0;
			update_option( 'usces_db_version', $update_history );
		}
	};
	*/

	$notice_flag = false;
	foreach ( $update_history as $v => $f ) {

		$function_name  = 'wel_update_db_';
		$function_name .= str_replace( '.', '_', $v );
		if ( function_exists( $function_name ) && 0 === (int) $f ) {
			$notice_flag = true;
			break;
		}
	}
	if ( $notice_flag ) {
		add_action( 'admin_notices', 'wel_db_notice' );
	}
}

/**
 * Database update notification.
 *
 * @since 2.6
 */
function wel_db_notice() {
	global $current_screen;
	$update_history = get_option( 'usces_db_version' );
	$update_number  = 0;
	foreach ( $update_history as $flag ) {
		if ( 0 === $flag ) {
			$update_number++;
		}
	}

	if ( isset( $current_screen->base ) && 'toplevel_page_usc-e-shop/usc-e-shop' === $current_screen->base ) {
		$action = filter_input( INPUT_GET, 'wel_action', FILTER_SANITIZE_STRING, FILTER_REQUIRE_SCALAR );
		if ( 'update_db' === $action ) {
			return;
		}
	}

	$class    = 'notice notice-warning';
	$message1 = __( 'Shop data needs to be updated.', 'usces' );
	$message2 = __( 'Be sure to back up your database before updating.', 'usces' ) . '[Welcart]';
	$message3 = __( 'Make an update', 'usces' );
	// translators: %s: Number of updates.
	$message4  = sprintf( _n( 'There is %s update.', 'There are %s updates.', $update_number, 'usces' ), $update_number );
	$url       = USCES_ADMIN_URL . '?page=' . rawurlencode( 'usc-e-shop/usc-e-shop.php' ) . '&wel_action=update_db';
	$nonce_url = wp_nonce_url( $url, 'wel_update_database', '_welnonce' );

	printf( '<div class="%1$s"><p>%2$s<br>%3$s</p><p><a class="button" href="%6$s">%4$s</a> ( %5$s )</p></div>', esc_attr( $class ), esc_html( $message1 ), esc_html( $message2 ), esc_html( $message3 ), esc_html( $message4 ), esc_url( $nonce_url ) );
}

/**
 * Check if the database needs to be updated.
 *
 * @since 2.6
 * @return boolean Returns true if necessary.
 */
function wel_need_to_update_db() {
	$update_history = get_option( 'usces_db_version' );
	if ( in_array( 0, $update_history, true ) ) {
		return true;
	} else {
		return false;
	}
}

/**
 * Database update process.
 *
 * @since 2.6
 */
function wel_db_update_ajax() {
	define( 'USCES_DB_UP_INTERBAL', 10 );

	$update_history = get_option( 'usces_db_version' );
	ksort( $update_history );

	foreach ( $update_history as $version => $flag ) {
		$function_name = 'wel_update_db_';
		if ( 0 === $flag ) {
			$function_name .= str_replace( '.', '_', $version );
			$function_name( $version );
			break;
		}
	}
}

/**
 * Database update 2.0.
 *
 * @since  2.6
 * @param string $version Version namber.
 */
function wel_update_db_2_0( $version ) {
	global $wpdb, $usces;

	/**
	 * Preparation process.
	 */
	$log       = '';
	$total_num = 0;
	$comp_num  = 0;
	$err_num   = 0;
	$line_num  = 0;
	$file_info = 'バージョン2.0の更新';
	// translators: %s: Version number.
	$log     .= sprintf( __( 'Version %s update started', 'usces' ), $version ) . "\n";
	$progress = array(
		'info'     => $file_info,
		'status'   => __( 'processing', 'usces' ),
		'i'        => $line_num,
		'all'      => $total_num,
		'log'      => $log,
	);
	wel_record_progress( $progress );

	/**
	 * Update process.
	 */
	$target = $wpdb->get_col(
		$wpdb->prepare( "SELECT meta_id FROM $wpdb->postmeta WHERE meta_key = %s", '_itemPicts' )
	);
	if ( is_array( $target ) ) {
		$total_num = count( $target );
	} else {
		$total_num = 0;
	}

	if ( 0 < $total_num ) {

		for ( $line_num = 0; $line_num < $total_num; $line_num++ ) {

			$meta_id = $target[ $line_num ];

			$res = $wpdb->query(
				$wpdb->prepare( "DELETE FROM $wpdb->postmeta WHERE meta_id = %d", $meta_id )
			);
			if ( false === $res ) {
				$err_num++;
			} else {
				$comp_num++;
			}

			if ( 0 === ( $line_num % 20 ) ) {
				$progress = array(
					'info'     => $file_info,
					'status'   => __( 'processing', 'usces' ),
					// translators: %1$s: Number of successes. %2$s: Number of errors.
					'progress' => sprintf( __( 'Successful %1$s lines, Failed %2$s lines.', 'usces' ), $comp_num, $err_num ),
					'i'        => ( $line_num + 1 ),
					'all'      => $total_num,
				);
				wel_record_progress( $progress );
			}
		}

		/**
		 * Final processing.
		 */
		$update_history             = get_option( 'usces_db_version' );
		$update_history[ $version ] = 1;
		update_option( 'usces_db_version', $update_history );

		sleep( 2 );
		$log     .= __( 'Completion', 'usces' ) . "\n";
		$progress = array(
			'info'     => $file_info,
			'status'   => __( 'End', 'usces' ),
			// translators: %1$s: Number of successes. %2$s: Number of errors.
			'progress' => sprintf( __( 'Successful %1$s lines, Failed %2$s lines.', 'usces' ), $comp_num, $err_num ),
			'log'      => $log,
			'i'        => $line_num,
			'all'      => $total_num,
			'flag'     => 'complete',
		);
		wel_record_progress( $progress );
		die( wp_json_encode( $progress ) );

	} else {

		/**
		 * Final processing.
		 */
		$update_history             = get_option( 'usces_db_version' );
		$update_history[ $version ] = 1;
		update_option( 'usces_db_version', $update_history );

		sleep( 2 );
		$log     .= __( 'There was no data to update.', 'usces' ) . "\n";
		$progress = array(
			'info'     => $file_info,
			'status'   => __( 'End', 'usces' ),
			// translators: %1$s: Number of successes. %2$s: Number of errors.
			'progress' => sprintf( __( 'Successful %1$s lines, Failed %2$s lines.', 'usces' ), $comp_num, $err_num ),
			'log'      => $log,
			'i'        => $line_num,
			'all'      => $total_num,
			'flag'     => 'complete',
		);
		wel_record_progress( $progress );
		die( wp_json_encode( $progress ) );
	}
}

/**
 * Database update 2.6.
 *
 * @since  2.6
 * @param string $version Version namber.
 */
function wel_update_db_2_6( $version ) {
	global $wpdb, $usces;

	$time_start   = filter_input( INPUT_POST, 'time_start', FILTER_DEFAULT, array( 'options' => array( 'default' => 0 ) ) );
	if ( 0 === (int) $time_start ) {
		$time_start = microtime(true);
	}

	/**
	 * Preparation process.
	 */
	$log         = '';
	$total_num   = 0;
	$work_number = filter_input( INPUT_POST, 'work_number', FILTER_VALIDATE_INT, array( 'options' => array( 'default' => 0 ) ) );
	$comp_num    = filter_input( INPUT_POST, 'comp_num', FILTER_VALIDATE_INT, array( 'options' => array( 'default' => 0 ) ) );
	$err_num     = filter_input( INPUT_POST, 'err_num', FILTER_VALIDATE_INT, array( 'options' => array( 'default' => 0 ) ) );
	$line_num    = 0;
	$file_info   = 'バージョン2.6の更新<br>画像の情報を新様式に整理します。<br>商品点数、商品画像が多い場合は処理に時間がかかります。<p>終了するまで他の操作はせず、そのままお待ちください。</p>';
	// translators: %s: Version number.
	$log     .= sprintf( __( 'Version %s update started', 'usces' ), $version ) . "\n";
	$progress = array(
		'info'     => $file_info,
		'status'   => __( 'processing', 'usces' ),
		'i'        => $line_num,
		'all'      => $total_num,
		'log'      => $log,
	);
	//wel_record_progress( $progress );

	/**
	 * Update process.
	 */
	$target = $wpdb->get_col(
		$wpdb->prepare(
			"SELECT ID FROM $wpdb->posts 
			WHERE (post_status = %s OR post_status = %s OR post_status = %s OR post_status = %s OR post_status LIKE %s ) 
			AND post_type = %s AND post_mime_type = %s",
			'publish',
			'private',
			'future',
			'pending',
			'%draft%',
			'post',
			'item'
		)
	);

	if ( is_array( $target ) ) {
		$total_num = count( $target );
	} else {
		$total_num = 0;
	}

	if ( 0 < $total_num ) {

		if ( ! function_exists( 'wel_sync_item_images' ) ) {
			require_once USCES_PLUGIN_DIR . 'includes/product/wel-item-images.php';
		}

		for ( $line_num = $work_number; $line_num < $total_num; $line_num++ ) {

			$post_id = $target[ $line_num ];
			$cache   = false;
			wel_sync_item_images( $post_id, $cache );

			$comp_num++;
			$memory = (int) ( memory_get_peak_usage() / ( 1024 * 1024 ) );
			$time   = (int) ( microtime( true ) - $time_start );
			if ( 0 === ( $line_num % 200 ) ) {
				$file_info2  = '<br>[メモリ最大使用量]：' . $memory . 'MB';
				$file_info2 .= '<br>[経過時間]：' . $time . '秒';
				$progress = array(
					'info'     => $file_info . $file_info2,
					'status'   => __( 'processing', 'usces' ),
					// translators: %1$s: Number of successes. %2$s: Number of errors.
					'progress' => sprintf( __( 'Successful %1$s lines, Failed %2$s lines.', 'usces' ), $comp_num, $err_num ),
					'i'        => ( $line_num + 1 ),
					'all'      => $total_num,
				);
				wel_record_progress( $progress );
			}

			if ( 100 < $memory ) {
				$con = array(
					'work_number' => $line_num + 1,
					'comp_num'    => $comp_num,
					'err_num'     => $err_num,
					'time_start'  => $time_start,
					'flag'        => 'continue',
				);
				die( wp_json_encode( $con ) );
			}
		}

		/**
		 * Final processing.
		 */
		$update_history             = get_option( 'usces_db_version' );
		$update_history[ $version ] = 1;
		update_option( 'usces_db_version', $update_history );

		sleep( 2 );
		$memory      = (int) ( memory_get_peak_usage() / ( 1024 * 1024 ) );
		$time        = (int) ( microtime( true ) - $time_start );
		$file_info2  = '<br>[メモリ最大使用量]：' . $memory . 'MB';
		$file_info2 .= '<br>[経過時間]：' . $time . '秒';
		$file_info   = 'バージョン2.6の更新<br>画像の情報を新様式に整理します。<br>商品点数、商品画像が多い場合は処理に時間がかかります。<p>処理が完了しました。</p>';
		$file_info  .= $file_info2;
		$log        .= __( 'Completion', 'usces' ) . "\n";
		$progress    = array(
			'info'     => $file_info,
			'status'   => __( 'End', 'usces' ),
			// translators: %1$s: Number of successes. %2$s: Number of errors.
			'progress' => sprintf( __( 'Successful %1$s lines, Failed %2$s lines.', 'usces' ), $comp_num, $err_num ),
			'log'      => $log,
			'i'        => $line_num,
			'all'      => $total_num,
			'flag'     => 'complete',
		);
		wel_record_progress( $progress );
		die( wp_json_encode( $progress ) );

	} else {
		/**
		 * Final processing.
		 */
		$update_history             = get_option( 'usces_db_version' );
		$update_history[ $version ] = 1;
		update_option( 'usces_db_version', $update_history );

		sleep( 2 );
		$memory      = (int) ( memory_get_peak_usage() / ( 1024 * 1024 ) );
		$time        = (int) ( microtime( true ) - $time_start );
		$file_info2  = '<br>[メモリ最大使用量]：' . $memory . 'MB';
		$file_info2 .= '<br>[経過時間]：' . $time . '秒';
		$file_info   = 'バージョン2.6の更新<br>画像の情報を新様式に整理します。<br>商品点数、商品画像が多い場合は処理に時間がかかります。<p>処理が完了しました。</p>';
		$file_info  .= $file_info2;
		$log        .= __( 'There was no data to update.', 'usces' ) . "\n";
		$progress    = array(
			'info'     => $file_info,
			'status'   => __( 'End', 'usces' ),
			// translators: %1$s: Number of successes. %2$s: Number of errors.
			'progress' => sprintf( __( 'Successful %1$s lines, Failed %2$s lines.', 'usces' ), $comp_num, $err_num ),
			'log'      => $log,
			'i'        => $line_num,
			'all'      => $total_num,
			'flag'     => 'complete',
		);
		wel_record_progress( $progress );
		die( wp_json_encode( $progress ) );
	}
}

/**
 * Record progress.
 *
 * @since  2.6
 * @param array $arr_content Content.
 */
function wel_record_progress( $arr_content ) {

	$upload_folder = WP_CONTENT_DIR . USCES_UPLOAD_TEMP . '/';
	$mkdir         = wp_mkdir_p( $upload_folder );
	$progress_file = $upload_folder . 'db-progress.txt';
	$log_file      = $upload_folder . 'db-log.txt';

	if ( $mkdir ) {

		if ( ( isset( $arr_content['status'] ) || isset( $arr_content['progress'] ) ) ) {
			file_put_contents( $progress_file, wp_json_encode( $arr_content ), LOCK_EX );
		}

		if ( isset( $arr_content['log'] ) ) {
			if ( 'clear' === $arr_content['log'] ) {
				file_put_contents( $log_file, '', LOCK_EX );
			} elseif ( isset( $arr_content['flag'] ) && 'complete' === $arr_content['flag'] ) {
				$add_text = $arr_content['log'];
				file_put_contents( $log_file, $add_text, LOCK_EX );
			}
		}
	}
}

/**
 * Check progress.
 *
 * @since  2.6
 */
function wel_check_progress_ajax() {
	$progressfile = filter_input( INPUT_POST, 'progressfile' );

	sleep( 1 );
	// Make sure the file is exist.
	if ( file_exists( $progressfile ) ) {
		// Get the content and echo it.
		$text = file_get_contents( $progressfile );
		die( $text );
	} else {
		die( "logfile dosn't exist" );
	}
	exit;
}

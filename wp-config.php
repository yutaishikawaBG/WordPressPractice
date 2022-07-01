<?php
/**
 * WordPress の基本設定
 *
 * このファイルは、インストール時に wp-config.php 作成ウィザードが利用します。
 * ウィザードを介さずにこのファイルを "wp-config.php" という名前でコピーして
 * 直接編集して値を入力してもかまいません。
 *
 * このファイルは、以下の設定を含みます。
 *
 * * データベース設定
 * * 秘密鍵
 * * データベーステーブル接頭辞
 * * ABSPATH
 *
 * @link https://ja.wordpress.org/support/article/editing-wp-config-php/
 *
 * @package WordPress
 */

// 注意:
// Windows の "メモ帳" でこのファイルを編集しないでください !
// 問題なく使えるテキストエディタ
// (http://wpdocs.osdn.jp/%E7%94%A8%E8%AA%9E%E9%9B%86#.E3.83.86.E3.82.AD.E3.82.B9.E3.83.88.E3.82.A8.E3.83.87.E3.82.A3.E3.82.BF 参照)
// を使用し、必ず UTF-8 の BOM なし (UTF-8N) で保存してください。

// ** データベース設定 - この情報はホスティング先から入手してください。 ** //
/** WordPress のためのデータベース名 */
define( 'DB_NAME', 'wordpress' );

/** データベースのユーザー名 */
define( 'DB_USER', 'root' );

/** データベースのパスワード */
define( 'DB_PASSWORD', 'root' );

/** データベースのホスト名 */
define( 'DB_HOST', 'localhost' );

/** データベースのテーブルを作成する際のデータベースの文字セット */
define( 'DB_CHARSET', 'utf8mb4' );

/** データベースの照合順序 (ほとんどの場合変更する必要はありません) */
define( 'DB_COLLATE', '' );

/**#@+
 * 認証用ユニークキー
 *
 * それぞれを異なるユニーク (一意) な文字列に変更してください。
 * {@link https://api.wordpress.org/secret-key/1.1/salt/ WordPress.org の秘密鍵サービス} で自動生成することもできます。
 * 後でいつでも変更して、既存のすべての cookie を無効にできます。これにより、すべてのユーザーを強制的に再ログインさせることになります。
 *
 * @since 2.6.0
 */
define( 'AUTH_KEY',         '*_;Z<AXk<3JCQm{hM$iZmC_K1khGfl` T*9Lsou0gZ19>y=Z5=5E(,DK?oO&@o@H' );
define( 'SECURE_AUTH_KEY',  'zOq).Cm+UWuYE6mb7|AXM#.|)MUb!Ep}aIqTh5I`^KDO#3Sx6!Wy407K]O.I4S30' );
define( 'LOGGED_IN_KEY',    'm@JaUT>X$-aB~UfWW{8h1^^u2+_=].!*Eh+T^Y=[~~]_gJD+c(8)|DEnES+KVj]9' );
define( 'NONCE_KEY',        ' ]ez@`%Hnj{~do(}YM8Ba~uDAPO7zRmBv<K-iz[Fan|:ovLEbreK4uGsSH6RFah^' );
define( 'AUTH_SALT',        'I7)Ly&O1pZI)O,*O5+~jH`;td{<eKa]Bx=BLEbEg-], 5i$-Z7ctAyb|T9t*d%?t' );
define( 'SECURE_AUTH_SALT', 'kTQ[KOq$G]J`4`ANi%-!|(>QABFegz,Y$Hpafe<y@>i_(Y!a+8uOFlvDQ+T-bMA1' );
define( 'LOGGED_IN_SALT',   'X9?KMMm9sj7ckEeR427)fA<P8@]SxK;ln^yD)z=.k/ 81 #%bKV~z!]-?T|F9WWr' );
define( 'NONCE_SALT',       '.b#nl,cp@{gV zP-|2cF*!2ZqZ336#o%nb~dlxw4Rl_J1iNfS/x =A^V5k=DT_$C' );

/**#@-*/

/**
 * WordPress データベーステーブルの接頭辞
 *
 * それぞれにユニーク (一意) な接頭辞を与えることで一つのデータベースに複数の WordPress を
 * インストールすることができます。半角英数字と下線のみを使用してください。
 */
$table_prefix = 'wp_';

/**
 * 開発者へ: WordPress デバッグモード
 *
 * この値を true にすると、開発中に注意 (notice) を表示します。
 * テーマおよびプラグインの開発者には、その開発環境においてこの WP_DEBUG を使用することを強く推奨します。
 *
 * その他のデバッグに利用できる定数についてはドキュメンテーションをご覧ください。
 *
 * @link https://ja.wordpress.org/support/article/debugging-in-wordpress/
 */
define( 'WP_DEBUG', false );

/* カスタム値は、この行と「編集が必要なのはここまでです」の行の間に追加してください。 */



/* 編集が必要なのはここまでです ! WordPress でのパブリッシングをお楽しみください。 */

/** Absolute path to the WordPress directory. */
if ( ! defined( 'ABSPATH' ) ) {
	define( 'ABSPATH', __DIR__ . '/' );
}

/** Sets up WordPress vars and included files. */
require_once ABSPATH . 'wp-settings.php';

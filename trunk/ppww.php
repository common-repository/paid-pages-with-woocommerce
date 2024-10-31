<?php
/**
 * Plugin Name: Paid Pages with WooCommerce
 * Plugin URI: https://wordpress.org/plugins/paid-pages-with-woocommerce/
 * Description: Create paid subscriptions (WooCommerce products) to access specified pages
 * Version: 0.2.4
 * Author: xsid
 * Author URI: https://profiles.wordpress.org/xsid/
 * License: GPLv3
 * License: https://www.gnu.org/licenses/gpl-3.0.html
 * Text Domain: ppww
 * Domain Path: /i18n/
 *
 * @package PPWW
 */

defined( 'ABSPATH' ) || exit;

if ( ! defined( 'PPWW_DIR' ) ) {
	define( 'PPWW_DIR', untrailingslashit( dirname( __FILE__ ) ) );
}

if ( ! defined( 'PPWW_URL' ) ) {
	define( 'PPWW_URL', untrailingslashit( plugin_dir_url( __FILE__ ) ) );
}

load_plugin_textdomain( 'ppww', false, dirname( plugin_basename( __FILE__ ) ) . '/i18n/' );

define( 'PPWW_DEFAULT_MESSAGE_H', esc_html__( 'This section available only by subscription.', 'ppww' ) );
define( 'PPWW_DEFAULT_MESSAGE_B', esc_html__( 'Choose the best plan for you.', 'ppww' ) );

include( ABSPATH . "/wp-includes/pluggable.php" );

if ( ! class_exists( 'PPWW' ) ) {
	include_once dirname( __FILE__ ) . '/includes/class-ppww.php';
}

if ( ! class_exists( 'PPWW_MetaBox' ) ) {
	include_once dirname( __FILE__ ) . '/includes/class-ppww_metabox.php';
}

if ( is_admin() ) {
	wp_enqueue_style( 'ppww', PPWW_URL . '/assets/css/style.css', array(), filemtime( PPWW_DIR . '/assets/css/style.css' ), 'all' );
}

/**
 * Returns the main instance of PPWW.
 *
 * @return PPWW
 * @since  0.1
 */
function PPWW() { // phpcs:ignore WordPress.NamingConventions.ValidFunctionName.FunctionNameInvalid
	return PPWW::instance();
}

/**
 * Returns the main instance of PPWW_MetaBox.
 *
 * @return PPWW_MetaBox
 * @since  0.1
 */
function PPWW_MetaBox() { // phpcs:ignore WordPress.NamingConventions.ValidFunctionName.FunctionNameInvalid
	return PPWW_MetaBox::instance();
}

// Global for backwards compatibility.
$GLOBALS['PPWW']         = PPWW();
$GLOBALS['PPWW_MetaBox'] = PPWW_MetaBox();

// Actions at plugin activation
register_activation_hook( __FILE__, array( 'PPWW', 'ppww_activated' ) );
// Actions at plugin deactivation
register_deactivation_hook( __FILE__, array( 'PPWW', 'ppww_deactivated' ) );
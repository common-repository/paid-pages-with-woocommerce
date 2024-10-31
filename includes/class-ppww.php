<?php

/**
 * Text Domain: ppww
 *
 * @package PPWW
 *
 */

defined( 'ABSPATH' ) || exit;

final class PPWW {

	/**
	 * PPWW version.
	 *
	 * @var string
	 */
	public $version = '0.2.4';

	/**
	 * The single instance of the class.
	 *
	 * @var PPWW
	 * @since 0.1
	 */
	protected static $_instance = null;

	/**
	 * Main PPWW Instance.
	 *
	 * Ensures only one instance of WPA is loaded or can be loaded.
	 *
	 * @return PPWW - Main instance.
	 * @since 0.1
	 * @static
	 */
	public static function instance() {
		if ( is_null( self::$_instance ) ) {
			self::$_instance = new self();
		}

		return self::$_instance;
	}

	public function __clone() {
		exit;
	}

	public function __construct() {
		add_action( 'init', array( $this, 'ppww_sp_post_type' ) );
		add_action( 'init', array( $this, 'ppww_wc_category' ) );
		add_action( 'admin_init', array( $this, 'ppww_dependencies' ) );
		add_filter( 'the_content', array( $this, 'ppww_paid_content' ), 1000 );
	}

	function ppww_close_comments( $open ) {
		$open = false;
	}

	static function ppww_activated() {
	}

	static function ppww_deactivated() {
	}

	/**
	 * Deactivate PPWW if dependencies not resolved
	 *
	 * @since 0.1
	 */
	function ppww_dependencies() {
		if ( ! in_array( 'woocommerce/woocommerce.php', apply_filters( 'active_plugins', get_option( 'active_plugins' ) ) ) ) {
			add_action( 'admin_notices', array( $this, 'ppww_dependencies_message' ) );
			deactivate_plugins( 'wpa/wpa.php' );
			if ( isset( $_GET['activate'] ) ) {
				unset( $_GET['activate'] );
			}
		}
	}

	function ppww_dependencies_message() { ?>
        <div class="error">
            <p><?php esc_html_e( 'Sorry, but "Paid Pages with WooCommerce" requires "WooCommerce" to be installed and active.', 'ppww' ); ?></p>
        </div>
	<?php }

	/**
	 * Check if current user has access purchased
	 *
	 * @param string $prefix
	 *
	 * @return bool
	 */
	function ppww_access_granted( $prefix ) {
		// get all order ids for current user
		$orders = wc_get_orders( array( 'limit' => - 1, 'customer' => get_current_user_id(), 'return' => 'ids' ) );

		// loop through orders to check if user has bought access to forecasts
		foreach ( $orders as $order ) {
			// get order by id
			$_order = wc_get_order( $order );
			foreach ( $_order->get_items() as $item_id => $item_product ) {
				$product = $item_product->get_product();
				$sku     = $product->get_sku();

				$sku             = explode( '_', $sku );
				$ppww_paid_prefix = $sku[0];

				// if product sku starts with $prefix
				if ( $ppww_paid_prefix == $prefix ) {
					$seconds_since_purchase = time() - strtotime( $_order->get_date_completed() );
					$days_valid             = intval( $sku[1] );
					$seconds_valid          = $days_valid * 24 * 60 * 60;
					$seconds_left           = $seconds_valid - $seconds_since_purchase;
					if ( $seconds_left > 0 ) {
						return true;
						break;
					}
				}
			}
		}


		current_user_can( 'administrator' ) || add_filter( 'comments_open', array( $this, 'ppww_close_comments' ) );

		return current_user_can( 'administrator' ) || false;
	}

	function ppww_get_paid_posts( $post_type ) {
		global $post;

		$array = array(
			'posts_per_page' => - 1,
			'post_type'      => $post_type,
			'post_status'    => 'any',
			'meta_query'     => array(
				array(
					'key'   => '_ppww_paid_prefix',
					'value' => get_post_meta( $post->ID, '_ppww_paid_prefix', true )
				)
			),
			'orderby'        => 'meta_value_num',
			'order'          => 'ASC'
		);

		return get_posts( $array );
	}

	function ppww_paid_content( $content ) {
		global $post;

		$posts = $this->ppww_get_paid_posts('product');
		$sp = $this->ppww_get_paid_posts('subscription_plan');

		if ( is_main_query() && get_post_meta( $post->ID, '_is_ppww_paid', true ) == 1 ) {
			if ( ! $this->ppww_access_granted( get_post_meta( $post->ID, '_ppww_paid_prefix', true ) ) ) {

				$ppww_custom_message_h = get_post_meta( $sp[0]->ID, '_ppww_custom_message_h', true );
				$ppww_custom_message_b = get_post_meta( $sp[0]->ID, '_ppww_custom_message_b', true );
				$ppww_wrapper_id       = get_post_meta( $sp[0]->ID, '_ppww_wrapper_id', true );

				switch ( $ppww_wrapper_id ) {
					case '1':
						$wrapper_start = '<div class="ppwc container py-5">';
						$wrapper_end   = '</div>';
						break;
					case '0':
					default:
						$wrapper_start = '<div class="ppwc">';
						$wrapper_end   = '</div>';
						break;
				}

				$html = $wrapper_start;

				if ( $ppww_custom_message_h != '*' ) {
					$html .= '<h1 class="ppwc-msg-header">' . ( strlen( trim( $ppww_custom_message_h ) ) > 0 ? $ppww_custom_message_h : PPWW_DEFAULT_MESSAGE_H ) . '</h1>';
				}
				if ( $ppww_custom_message_b != '*' ) {
					$html .= '<h3 class="ppwc-msg-body">' . ( strlen( trim( $ppww_custom_message_b ) ) > 0 ? $ppww_custom_message_b : PPWW_DEFAULT_MESSAGE_B ) . '</h3>';
				}
				$html .= '<ul class="ppwc-plan-list">';

				foreach ( $posts as $item ) {
					$days   = intval( get_post_meta( $item->ID, '_ppww_days', true ) );
					$days_s = _n( 'day', 'days', $days, 'ppww' );
					$price  = floatval( get_post_meta( $item->ID, '_price', true ) );
					$curr   = get_woocommerce_currency_symbol();
					$html   .= '<li class="ppwc-plan-item"><a href="' . get_permalink( $item->ID ) . '">' . sprintf( esc_html__( '%1$d %2$s access for %3$d%4$s', 'ppww' ), $days, $days_s, $price, $curr ) . '</a></li>';
				}

				$html .= '</ul>';

				$html .= $wrapper_end;

				return $html;
			}
		}

		return $content;
	}

	function ppww_sp_post_type() {

		$labels = array(
			'name'                  => esc_html__( 'Subscription plans', 'ppww' ),
			'singular_name'         => esc_html__( 'Subscription plan', 'ppww' ),
			'add_new'               => esc_html_x( 'Add new', 'plan', 'ppww' ),
			'add_new_item'          => esc_html__( 'Add new plan', 'ppww' ),
			'edit_item'             => esc_html__( 'Edit plan', 'ppww' ),
			'new_item'              => esc_html__( 'New plan', 'ppww' ),
			'view_item'             => esc_html__( 'View plan', 'ppww' ),
			'search_items'          => esc_html__( 'Search for plans', 'ppww' ),
			'not_found'             => esc_html__( 'Plans not found', 'ppww' ),
			'not_found_in_trash'    => esc_html__( 'Plans not found in trash', 'ppww' ),
			'all_items'             => esc_html__( 'All plans', 'ppww' ),
			'insert_into_item'      => esc_html__( 'Insert into plan', 'ppww' ),
			'uploaded_to_this_item' => esc_html__( 'Uploaded to this plan', 'ppww' ),
			'featured_image'        => esc_html__( 'Plan\'s featured image', 'ppww' ),
			'set_featured_image'    => esc_html__( 'Set plan\'s featured image', 'ppww' ),
			'remove_featured_image' => esc_html__( 'Remove plan\'s featured image', 'ppww' ),
			'use_featured_image'    => esc_html__( 'Use as plan\'s featured image', 'ppww' ),
			'filter_items_list'     => esc_html__( 'Filter list of plans', 'ppww' ),
			'items_list_navigation' => esc_html__( 'Plans list navigation', 'ppww' ),
			'items_list'            => esc_html__( 'List of plans', 'ppww' ),
			'view_items'            => esc_html__( 'View plans', 'ppww' ),
			'attributes'            => esc_html__( 'Plan\'s attributes', 'ppww' ),
			'item_updated'          => esc_html__( 'Plan updated', 'ppww' ),
			'item_published'        => esc_html__( 'Plan published', 'ppww' ),
		);

		$args = array(
			'label'                 => esc_html__( 'Subscription plans', 'ppww' ),
			'labels'                => $labels,
			'description'           => '',
			'public'                => true,
			'publicly_queryable'    => true,
			'show_ui'               => true,
			'delete_with_user'      => false,
			'show_in_rest'          => false,
			'rest_base'             => '',
			'rest_controller_class' => 'WP_REST_Posts_Controller',
			'has_archive'           => false,
			'show_in_admin_bar'     => true,
			'show_in_menu'          => true,
			'show_in_nav_menus'     => false,
			'exclude_from_search'   => false,
			'capability_type'       => 'post',
			'menu_icon'             => 'data:image/svg+xml;base64,' . base64_encode( '<svg width="20" height="20" version="1.1" viewBox="0 0 5.2917 5.2917" xmlns="http://www.w3.org/2000/svg"><g transform="translate(0 -291.71)"><path d="m3.6561 291.71c-0.22291-8e-3 -0.42974 0.0737-0.59841 0.24237-0.10258 0.10258-0.22499 0.26436-0.31988 0.43976-0.094888 0.17541-0.17036 0.36128-0.13333 0.53485l5.16e-4 5.2e-4c0.040039 0.18465 0.14736 0.36223 0.27699 0.53795l-1.5632 1.5637-0.57206 0.57206-0.32401 0.32349-0.23203 0.23203 0.46457 0.46457 0.16123-0.16123 0.54105 0.54054 0.46457-0.46509-0.54054-0.54054 0.10749-0.10748 0.28164 0.28215 0.46457-0.46457-0.28215-0.28164 1.4883-1.4883c0.086141 0.0631 0.17248 0.12513 0.26562 0.17053 0.18792 0.0921 0.36881 0.13484 0.54002 0.11989 0.06739-6e-3 0.12366-0.0331 0.18759-0.0687 0.063929-0.0356 0.13126-0.0809 0.19844-0.13022 0.13435-0.0986 0.26502-0.21077 0.34158-0.28732 0.19562-0.19562 0.26673-0.45529 0.20516-0.73484-0.060332-0.27719-0.22238-0.54278-0.47542-0.79582-0.22792-0.22792-0.46783-0.38326-0.72089-0.45992-0.077053-0.0232-0.15307-0.036-0.22738-0.0388zm0.19947 0.68523c0.31081 0 0.56069 0.24783 0.56069 0.55863-2.6e-6 0.31081-0.24988 0.56068-0.56069 0.56068-0.31081 0-0.55862-0.24987-0.55862-0.56068-6.8e-6 -0.3108 0.24781-0.55863 0.55862-0.55863z" color="#000000" color-rendering="auto" dominant-baseline="auto" fill="black" image-rendering="auto" shape-rendering="auto" solid-color="#000000" style="font-feature-settings:normal;font-variant-alternates:normal;font-variant-caps:normal;font-variant-ligatures:normal;font-variant-numeric:normal;font-variant-position:normal;isolation:auto;mix-blend-mode:normal;paint-order:stroke fill markers;shape-padding:0;text-decoration-color:#000000;text-decoration-line:none;text-decoration-style:solid;text-indent:0;text-orientation:mixed;text-transform:none;white-space:normal"/></g></svg>' ),
			'map_meta_cap'          => true,
			'hierarchical'          => false,
			'rewrite'               => array( 'slug' => 'subscription_plan', 'with_front' => true ),
			'query_var'             => true,
			'supports'              => array(
				'title',
				'thumbnail',
			),
			'taxonomies'            => array(),
		);

		register_post_type( 'subscription_plan', $args );
	}

	function ppww_wc_category() {
		wp_insert_term( esc_html__( 'Subscription plans', 'ppww' ), 'product_cat',
			array(
				'description' => '', // optional
				'parent'      => 0, // optional
				'slug'        => 'subscription_plan' // optional
			)
		);
	}
}
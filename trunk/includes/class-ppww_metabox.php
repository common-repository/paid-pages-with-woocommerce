<?php

/**
 * Text Domain: ppww
 *
 * @package PPWW
 */

defined( 'ABSPATH' ) || exit;

final class PPWW_MetaBox {
	/**
	 * The single instance of the class.
	 *
	 * @var PPWW_MetaBox
	 * @since 0.1
	 */
	protected static $_instance = null;

	/**
	 * Main ppww_MetaBox Instance.
	 *
	 * Ensures only one instance of WPA is loaded or can be loaded.
	 *
	 * @return PPWW_MetaBox - Main instance.
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
		add_action( 'add_meta_boxes', array( $this, 'ppww_add_metabox' ) );
		add_action( 'save_post', array( $this, 'ppww_metabox_save' ) );
	}

	function ppww_add_metabox() {
		global $post;
		if ( $post->post_type == 'subscription_plan' ) {
			add_meta_box(
				'ppww_sp_metaboxes',
				esc_html__( 'Plan attributes', 'ppww' ),
				array( $this, 'ppww_sp_metabox_html' ),
				null,
				'advanced',
				'default'
			);
		} else {
			add_meta_box(
				'ppww_metaboxes',
				esc_html__( 'Paid access', 'ppww' ),
				array( $this, 'ppww_metabox_html' ),
				null,
				'side',
				'default'
			);
		}
	}

	/**
	 * @param WP_Post $post
	 */
	function ppww_metabox_html( $post ) {
		$is_ppww_paid     = get_post_meta( $post->ID, '_is_ppww_paid', true );
		$ppww_paid_prefix = get_post_meta( $post->ID, '_ppww_paid_prefix', true );

		$args = array(
			'posts_per_page' => - 1,
			'post_type'      => 'subscription_plan',
			'orderby'        => 'post_title',
			'order'          => 'ASC',
		);

		$query = new WP_Query( $args );
		/**
		 * @var WP_Post[] $plans
		 */
		$plans = $query->get_posts();

		?>

        <div class="ppww_controls">
            <div class="ppww_control_group">
                <label class="ppww_control_label">
                    <input type="checkbox"
                           name="is_ppww_paid" <?php echo $is_ppww_paid == 1 ? 'checked="checked"' : ''; ?>>
					<?php esc_html_e( 'Paid access', 'ppww' ); ?>
                </label>
            </div>
            <div class="ppww_control_group">
                <label for="ppww_paid_prefix"
                       class="ppww_control_label"><?php esc_html_e( 'Subscription plan:', 'ppww' ); ?></label>
                <select id="ppww_paid_prefix" name="ppww_paid_prefix" class="ppww_control_select">
                    <option value=""><?php esc_html_e( 'None', 'ppww' ); ?></option>
					<?php foreach ( $plans as $plan ) {
						$plan_paid_prefix = get_post_meta( $plan->ID, '_ppww_paid_prefix', true );
						?>
                        <option value="<?php echo $plan_paid_prefix; ?>" <?php echo $plan_paid_prefix == $ppww_paid_prefix ? 'selected="selected"' : ''; ?>>
							<?php echo $plan->post_title; ?>
                        </option>
					<?php } ?>
                </select>
            </div>
        </div>
	<?php }

	function ppww_generate_sp_prefix() {
		return md5( NONCE_SALT . microtime( true ) );
	}

	/**
	 * @param WP_Post $post
	 */
	function ppww_sp_metabox_html( $post ) {
		$ppww_paid_prefix       = get_post_meta( $post->ID, '_ppww_paid_prefix', true );
		$ppww_sp_days_price     = get_post_meta( $post->ID, '_ppww_sp_days_price', true );
		$ppww_custom_message_h  = get_post_meta( $post->ID, '_ppww_custom_message_h', true );
		$ppww_custom_message_b  = get_post_meta( $post->ID, '_ppww_custom_message_m', true );
		$ppww_wrapper_id        = get_post_meta( $post->ID, '_ppww_wrapper_id', true );
		$ppww_sp_days_price_val = '';
		$separator              = '';
		if ( $ppww_sp_days_price ) {
			$ppww_sp_days_price = json_decode( $ppww_sp_days_price, ARRAY_A );
			foreach ( $ppww_sp_days_price as $item ) {
				$ppww_sp_days_price_val .= ( $separator . $item['days'] . '=' . $item['price'] );
				$separator              = ',';
			}
		}
		?>
        <div class="ppww_controls">
            <div class="ppww_control_group">
                <label class="ppww_control_label" for="ppww_sp_days_price">
					<?php esc_html_e( '[number of days]=[price] pairs. Separate values ​​with commas e.g. 1=10,7=20,30=50,365=100.', 'ppww' ); ?>
                </label>
                <input class="ppww_control_input" type="text" id="ppww_sp_days_price" name="ppww_sp_days_price"
                       value="<?php echo $ppww_sp_days_price_val; ?>">
            </div>

            <hr>

            <div class="ppww_control_group">
                <label class="ppww_control_label" for="ppww_custom_message_h">
					<?php esc_html_e( 'Override default message header about access restriction. Leave blank to use default header.', 'ppww' ); ?>
                </label>
                <input class="ppww_control_input" type="text" id="ppww_custom_message_h" name="ppww_custom_message_h"
                       value="<?php echo strlen( trim( $ppww_custom_message_h ) ) > 0 ? $ppww_custom_message_h : PPWW_DEFAULT_MESSAGE_H; ?>">
                <small style="color: #f00;"><?php esc_html_e( 'Use \'*\' to completely hide the header.', 'ppww' ); ?></small>
            </div>

            <hr>

            <div class="ppww_control_group">
                <label class="ppww_control_label" for="ppww_custom_message_b">
					<?php esc_html_e( 'Override default message body about access restriction. Leave blank to use default body.', 'ppww' ); ?>
                </label>
                <input class="ppww_control_input" type="text" id="ppww_custom_message_b" name="ppww_custom_message_b"
                       value="<?php echo strlen( trim( $ppww_custom_message_b ) ) > 0 ? $ppww_custom_message_b : PPWW_DEFAULT_MESSAGE_B; ?>">
                <small style="color: #f00;"><?php esc_html_e( 'Use \'*\' to completely hide the body.', 'ppww' ); ?></small>
            </div>

            <hr>

            <div class="ppww_control_group">
                <label class="ppww_control_label">
					<?php esc_html_e( 'Wrap overrided page content?', 'ppww' ); ?>
                </label>
                <label class="ppww_control_label" for="ppww_wrap_0">
                    <input type="radio" id="ppww_wrap_0" name="ppww_wrapper_id"
                           value="0" <?php echo $ppww_wrapper_id == '0' ? 'checked="checked"' : ''; ?>>
					<?php esc_html_e( 'no wrap', 'ppww' ); ?>
                </label>
                <label class="ppww_control_label" for="ppww_wrap_1">
                    <input type="radio" id="ppww_wrap_1" name="ppww_wrapper_id"
                           value="1" <?php echo $ppww_wrapper_id == '1' ? 'checked="checked"' : ''; ?>>
					<?php esc_html_e( 'Bootstrap 4 container', 'ppww' ); ?>
                </label>
            </div>
        </div>
        <input name="ppww_paid_prefix" value="<?php echo $ppww_paid_prefix ?: $this->ppww_generate_sp_prefix(); ?>"
               hidden>

        <hr>

        <h3>
            <b><?php esc_html_e( 'If you like this plugin, and it really helps you in your work, you can donate some money to motivate me for future updates. Thank you!', 'ppww' ); ?></b>
        </h3>

		<?php
		$this->ppww_donate();
	}

	function ppww_metabox_save( $post_id ) {
		$post = get_post( $post_id );
		if ( $post->post_status == 'publish' ) {

			// subscription plan case
			if ( $post->post_type == 'subscription_plan' ) {

				$pairs = array();

				$ppww_sp_days_price = explode( ',', $_POST['ppww_sp_days_price'] );

				foreach ( $ppww_sp_days_price as $pair ) {

					$days_price = explode( '=', $pair );

					$days  = intval( $days_price[0] );
					$price = intval( $days_price[1] );

					if ( $days > 0 ) {
						array_push( $pairs,
							array(
								'days'  => $days,
								'price' => $price
							) );
					}

					$args = array(
						'title'            => $post->post_title,
						'description'      => '',
						'days'             => $days,
						'price'            => $price,
						'thumbnail_id'     => get_post_meta( $post_id, '_thumbnail_id', true ),
						'parent_id'        => $post_id,
						'ppww_paid_prefix' => sanitize_text_field( $_POST['ppww_paid_prefix'] )
					);

					$this->ppww_create_wc_sp_product( $args );
				}
				update_post_meta( $post_id, '_ppww_sp_days_price', json_encode( $pairs ) );
				update_post_meta( $post_id, '_ppww_paid_prefix', sanitize_text_field( $_POST['ppww_paid_prefix'] ) );
				update_post_meta( $post_id, '_ppww_custom_message_h', isset( $_POST['ppww_custom_message_h'] ) ? sanitize_text_field( $_POST['ppww_custom_message_h'] ) : '' );
				update_post_meta( $post_id, '_ppww_custom_message_b', isset( $_POST['ppww_custom_message_b'] ) ? sanitize_text_field( $_POST['ppww_custom_message_b'] ) : '' );
				update_post_meta( $post_id, '_ppww_wrapper_id', isset( $_POST['ppww_wrapper_id'] ) ? sanitize_text_field( $_POST['ppww_wrapper_id'] ) : 0 );
			} else {
				if ( isset( $_POST['is_ppww_paid'] ) ) {
					update_post_meta( $post_id, "_is_ppww_paid", $_POST['is_ppww_paid'] == 'on' ? 1 : 0 );
				} else {
					update_post_meta( $post_id, "_is_ppww_paid", 0 );
				}

				$ppww_paid_prefix = isset( $_POST['ppww_paid_prefix'] ) ? sanitize_text_field( $_POST['ppww_paid_prefix'] ) : 0;
				update_post_meta( $post_id, "_ppww_paid_prefix", $ppww_paid_prefix );
			}
		}
	}

	/**
	 * @param $args
	 *
	 * @return int|WP_Error
	 */
	function ppww_create_wc_sp_product( $args ) {

		$array = array(
			'posts_per_page' => - 1,
			'post_type'      => 'product',
			'post_status'    => 'any',
			'meta_query'     => array(
				array(
					'key'   => '_sku',
					'value' => $args['ppww_paid_prefix'] . '_' . $args['days']
				)
			)
		);
		$posts = get_posts( $array );

		$the_post_id = count( $posts ) > 0 ? $posts[0]->ID : 0;

		$post_id = wp_insert_post( array(
			'ID'             => intval( $the_post_id ),
			'post_author'    => wp_get_current_user()->ID,
			'post_title'     => $args['title'],
			'post_content'   => $args['description'],
			'post_status'    => 'publish',
			'post_type'      => 'product',
			'comment_status' => 'closed',
			'category'       => 'subscription_plan'
		) );

		$term = get_term_by( 'slug', 'subscription_plan', 'product_cat' );

		wp_set_object_terms( $post_id, intval( $term->term_id ), 'product_cat' );
		wp_set_object_terms( $post_id, 'simple', 'product_type' );

		//Set product hidden:
		$terms = array( /*'exclude-from-catalog', */
			'exclude-from-search'
		);
		wp_set_object_terms( $post_id, $terms, 'product_visibility' );

		update_post_meta( $post_id, '_ppww_days', $args['days'] );
		update_post_meta( $post_id, '_ppww_paid_prefix', $args['ppww_paid_prefix'] );
		update_post_meta( $post_id, '_thumbnail_id', $args['thumbnail_id'] );
		update_post_meta( $post_id, '_visibility', 'visible' );
		update_post_meta( $post_id, '_stock_status', 'instock' );
		update_post_meta( $post_id, 'total_sales', '0' );
		update_post_meta( $post_id, '_downloadable', 'no' );
		update_post_meta( $post_id, '_download_expiry', '-1' );
		update_post_meta( $post_id, '_download_limit', '-1' );
		update_post_meta( $post_id, '_virtual', 'no' );
		update_post_meta( $post_id, '_regular_price', $args['price'] );
		update_post_meta( $post_id, '_sale_price', '' );
		update_post_meta( $post_id, '_purchase_note', '' );
		update_post_meta( $post_id, '_featured', 'no' );
		update_post_meta( $post_id, '_weight', '' );
		update_post_meta( $post_id, '_length', '' );
		update_post_meta( $post_id, '_width', '' );
		update_post_meta( $post_id, '_height', '' );
		update_post_meta( $post_id, '_sku', $args['ppww_paid_prefix'] . '_' . $args['days'] );
		update_post_meta( $post_id, '_product_attributes', array() );
		update_post_meta( $post_id, '_sale_price_dates_from', '' );
		update_post_meta( $post_id, '_sale_price_dates_to', '' );
		update_post_meta( $post_id, '_price', $args['price'] );
		update_post_meta( $post_id, '_sold_individually', 'yes' );
		update_post_meta( $post_id, '_manage_stock', 'no' );
		update_post_meta( $post_id, '_backorders', 'no' );
		update_post_meta( $post_id, '_stock', '' );

		return $post_id;
	}

	function ppww_donate() {
		echo '<div class="ppww_ym-donation">';
		echo '<a href="https://money.yandex.ru/to/410011748700866" target="_blank">';
		echo '<div class="ppww_ym-img-holder">';
		echo '<img src="' . PPWW_URL . '/assets/img/ym.svg">';
		echo '</div>';
		echo '<div class="ppww_ym-text-holder">' . esc_html__( 'Donate', 'ppww' ) . '</div>';
		echo '</a>';
		echo '</div>';
	}
}
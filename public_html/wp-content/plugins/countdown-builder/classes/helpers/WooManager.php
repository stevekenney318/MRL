<?php
namespace ycd;

class WooManager {
	private $post;
	public function __construct($post) {
		$this->post = $post;
	}

	public function isWoo() {
		if (!$this->post) return  false;
		return $this->post->post_type === 'product';
	}

	public static function getCartInfo() {
		$args = array();
		if (function_exists('WC') && WC()->cart)  {

			$args['wooCartIsEmpty'] = (bool)WC()->cart->is_empty();
			$expirationTime = apply_filters( 'ycdWoocommerce_cart_expiring_time', time() + ( get_option( 'woocommerce_cart_expires' ) * 60 ) );
			$args['wooCartExpirationTime'] = date( 'Y-m-d H:i:s' ,$expirationTime );

		}

		return $args;
	}
}
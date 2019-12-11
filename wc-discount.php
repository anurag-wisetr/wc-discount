<?php
    /**
     * Plugin Name: WooCommerce Discount
     * Plugin URI: https://woocommerce.com/
     * Description:  WooCommerce Discount is a Wordpress woocommerce plugin for discount on product.
     * Version: 1.0.0
     * Author: Wisetr
     * Author URI: https://woocommerce.com
     */

    if ( ! defined( 'ABSPATH' ) ) {
        exit;
    }

    if(in_array('woocommerce/woocommerce.php',apply_filters('active_plugins', get_option('active_plugins')))){
        add_action('plugins_loaded','discount_init');
    }

    function discount_init(){
        require 'includes/class-wc-discount.php';
        new Wc_Discount();
    }
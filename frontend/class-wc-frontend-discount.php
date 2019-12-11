<?php

    if ( ! defined( 'ABSPATH' ) ) {
        exit;
    }

    class Wc_Frontend_Discount {
        private $user_id=0;
        function __construct()
        {
            $this->user_id=get_current_user_id();

            $this->get_discount();
            add_action('wp_enqueue_scripts', array($this,'load_scripts'));
            add_action('woocommerce_before_add_to_cart_button', array($this, 'discount_form'));
            add_filter('woocommerce_get_price' ,array($this,'return_custom_price'),11,2);
            add_filter('woocommerce_get_sale_price', array($this,'return_custom_price'),11,2);
            add_filter('woocommerce_variation_prices_price', array($this,'return_custom_price'),20,2);
            add_filter('woocommerce_variation_prices_sale_price', array($this,'return_custom_price'),20,2);

            add_filter( 'woocommerce_add_cart_item_data', array($this,'add_cart_item_data'), 10, 3 );
            add_filter( 'woocommerce_get_cart_item_from_session', array( $this, 'get_cart_item_from_session' ), 1, 2 );

            add_filter( 'woocommerce_add_cart_item', array( $this, 'set_product_prices' ), 1, 1 );
            add_filter('woocommerce_cart_item_price', array($this,'product_price'),10,3);
        }

        function load_scripts(){
            wp_enqueue_style('discount-script',plugin_dir_url(__DIR__ ).'assets/css/discount.css');
            wp_enqueue_script('discount-script',plugin_dir_url(__DIR__ ).'assets/js/discount.js', array('jquery'),'1.0.0',true);
        }

        function add_cart_item_data( $cart_item_data, $product_id, $variation_id ) {
            if(!empty($_COOKIE['discount_on_email_'.$this->user_id])){
                $price = get_post_meta($product_id,'_price',true);
                $price = $price * (50/100);
                $cart_item_data['discount_price']['_price'] = $price*$_POST['quantity'];
            }
            return $cart_item_data;
        }

        public function get_cart_item_from_session( $cart_item, $values ) {
            if ( isset( $values['discount_price'] ) ) {
                $cart_item['discount_price'] = $values['discount_price'];
                $cart_item = $this->set_product_prices( $cart_item );
            }
            return $cart_item;
        }

        public function set_product_prices( $cart_item ) {
            if(isset( $cart_item['discount_price'] )){
                $cart_item['data']->set_price( (float) $cart_item['discount_price']['_price']);
            }
            return $cart_item;
        }

        function product_price($product_price,$cart_item, $cart_item_key){
            if(!empty($cart_item['discount_price'])){
                $price=get_post_meta($cart_item['product_id'],'_price',true);
                $price = $price*$cart_item['quantity'];
                return wc_price($price);
            }
            return $product_price;
        }

        function return_custom_price($price, $product){
            if(!empty($_COOKIE['discount_on_email_'.$this->user_id])){
                $price=get_post_meta($product->get_id(),'_price',true);
                $price= $price * (50/100);
            }
            return $price;
        }

        function discount_form(){
            if(empty($_COOKIE['discount_on_email_'.$this->user_id])){
                echo '<div class="fcs_form_wrap">
                            <p>Get 50% discount now! Just enter the email</p>
                            <form id="discount-form" method="post">
                                <input type="text" name="discount_on_email" class="fcs_input" placeholder="Your email address">
                                <span class="error-msg"></span>
                                <input type="submit" name="get_discount" class="fcs_btn" value="Get the discount">
                        </div>';
            }
        }

        function get_discount(){
            $this->print_notice();

            if(!empty($_POST['discount_on_email'])){
                setcookie('discount_on_email_'.$this->user_id,$_POST['discount_on_email'],0,'/');

                set_transient('display_msg_'.$this->user_id,'50% Discount applied on all products.');

                wp_redirect($_SERVER['HTTP_REFERER']);
                exit;
            }
        }

        function print_notice(){
            $notice=get_transient('display_msg_'.$this->user_id);
            if(!empty($notice)){
                wc_add_notice( $notice, 'success' );
                delete_transient('display_msg_'.$this->user_id);
            }
        }
    }
    new Wc_Frontend_Discount();
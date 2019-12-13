<?php

    if ( ! defined('ABSPATH')){
        exit;
    }

    class Wc_Frontend_Discount {

        private $user_id=0;
        private $discount=50;

        function __construct()
        {
            $this->user_id=get_current_user_id();
            $this->wda_apply_discount();
            add_action('wp_enqueue_scripts', array($this,'wda_load_scripts'));
            add_action('woocommerce_before_add_to_cart_form', array($this, 'wda_discount_form'));

            add_filter('woocommerce_available_variation',array($this,'wda_change_variation_price_html'),10,1);
            add_filter('woocommerce_tm_final_price',array($this,'wda_change_tm_final_price'),10,2);
            add_filter('wc_epo_product_price', array($this,'wda_change_tm_variation_price'),10,3);

            add_filter('woocommerce_grouped_price_html', array($this, 'wda_change_vg_price_html'),10,3);
            add_filter('woocommerce_variable_price_html', array($this, 'wda_change_vg_price_html'),10,2);
            add_filter('woocommerce_get_price_html', array($this,'wda_change_simple_price_html'),10,2);

            add_filter('woocommerce_add_cart_item_data', array($this,'wda_add_cart_item_data'), 100, 3 );
            add_filter('woocommerce_get_cart_item_from_session', array( $this, 'wda_cart_item_from_session' ), 100, 2 );
            add_filter('woocommerce_add_cart_item', array( $this, 'wda_set_product_prices' ), 100, 1 );
        }

        function wda_load_scripts(){
            wp_enqueue_style('wda-discount-style',plugin_dir_url(__DIR__ ).'assets/css/discount.css');
            wp_enqueue_script('wda-discount-script',plugin_dir_url(__DIR__ ).'assets/js/discount.js', array('jquery'),'1.0.0', true);
        }

        function wda_add_cart_item_data( $cart_item_data, $product_id, $variation_id){
            $product_id=!empty($variation_id)?$variation_id:$product_id;
            if(!empty($_COOKIE['discount_on_email_'.$this->user_id])){
                $price = get_post_meta($product_id,'_price',true);
                $price = $price * ($this->discount/100);
                $cart_item_data['discount_price']['_price'] = $price*$_POST['quantity'];
            }
            return $cart_item_data;
        }

        public function wda_cart_item_from_session( $cart_item, $values ) {
            if ( isset( $values['discount_price'] ) ) {
                $cart_item['discount_price'] = $values['discount_price'];
                $cart_item = $this->wda_set_product_prices( $cart_item );
            }
            return $cart_item;
        }

        public function wda_set_product_prices($cart_item){
            if(isset( $cart_item['discount_price'] )){
                $cart_item['data']->set_price( (float) $cart_item['discount_price']['_price']);
            }
            return $cart_item;
        }

        function wda_change_tm_final_price($price, $product, $var=''){
           if(!empty($_COOKIE['discount_on_email_'.$this->user_id])){
               $price = get_post_meta($product->get_id(), '_price', true);
                $price = $price * ($this->discount/100);
           }
            return $price;
        }

        function wda_change_tm_variation_price($variation_price, $var, $var1){
            $variation_price=$this->get_discounted_price($variation_price);
            return $variation_price;
        }

        function wda_change_variation_price_html($param){
            $variation_id=$param['variation_id'];
            if(!empty($_COOKIE['discount_on_email_'.$this->user_id])){
                $price = get_post_meta( $variation_id, '_price', true);
                $price = $price * ($this->discount/100);
                $param['price_html']='<span class="price">'.wc_price($price).'</span>';
            }
            return $param;
        }

        function wda_change_vg_price_html($price, $product, $child_prices=''){
            if (!empty($child_prices)) {
                $min_price = min($child_prices);
                $min_price=$this->get_discounted_price($min_price);
                $max_price = max($child_prices);
                $max_price=$this->get_discounted_price($max_price);
                $price = wc_format_price_range( $min_price, $max_price );
            }else{
                $prices = $product->get_variation_prices( true );
                $min_price     = current( $prices['price'] );
                $min_price=$this->get_discounted_price($min_price);
                $max_price     = end( $prices['price'] );
                $max_price=$this->get_discounted_price($max_price);
                $min_reg_price = current( $prices['regular_price'] );
                $min_reg_price=$this->get_discounted_price($min_reg_price);
                $max_reg_price = end( $prices['regular_price'] );
                $max_reg_price=$this->get_discounted_price($max_reg_price);

                if( $min_price !== $max_price ){
                    $price = wc_format_price_range( $min_price, $max_price );
                }elseif($product->is_on_sale() && $min_reg_price === $max_reg_price){
                    $price = wc_format_sale_price( wc_price( $max_reg_price ), wc_price( $min_price ) );
                }else{
                    $price = wc_price( $min_price );
                }
            }
            return $price;
        }

        function wda_change_simple_price_html($price,$product){
            if($product->get_type()=='simple' && !empty($_COOKIE['discount_on_email_'.$this->user_id])){
                $price = get_post_meta($product->get_id(), '_price', true);
                $regular_price = get_post_meta($product->get_id(), '_regular_price', true);
                $price = $price * ($this->discount/100);
                return wc_format_sale_price(  $regular_price,  $price  );
            }
            return $price;
        }

        function get_discounted_price($price){
            if(!empty($_COOKIE['discount_on_email_'.$this->user_id])){
                $price = $price * ($this->discount/100);
                return $price;
            }
            return $price;
        }

        function wda_discount_form(){
            if(empty($_COOKIE['discount_on_email_'.$this->user_id])){
                echo '<div class="fcs_form_wrap">
                            <p>Get 50% discount now! Just enter the email</p>
                            <form id="discount-form" method="post">'
                            .wp_nonce_field("apply_discount","apply_discount").'
                                <input type="text" name="discount_on_email" class="fcs_input" placeholder="Your email address">
                                <span class="error-msg"></span>
                                <input type="submit" name="get_discount" class="fcs_btn" value="Get the discount">
                            </form>
                        </div>';
            }
        }

        function wda_apply_discount(){
            $this->wda_print_notice();
            if(!empty($_POST['apply_discount'] ) && wp_verify_nonce($_POST['apply_discount'],'apply_discount') ){
                setcookie('discount_on_email_'.$this->user_id,$_POST['discount_on_email'],0,'/');
                set_transient('display_msg_'.$this->user_id,'50% Discount applied on all products.');
                wp_redirect($_SERVER['HTTP_REFERER']);
                exit;
            }
        }

        function wda_print_notice(){
            $notice=get_transient('display_msg_'.$this->user_id);
            if(!empty($notice)){
                wc_add_notice( $notice, 'success' );
                delete_transient('display_msg_'.$this->user_id);
            }
        }
    }
    new Wc_Frontend_Discount();
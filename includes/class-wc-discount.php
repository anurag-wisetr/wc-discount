<?php

    if ( ! defined( 'ABSPATH' ) ) {
        exit;
    }

    class Wc_Discount {

        function __construct()
        {
            add_action('init', array($this, 'load_files'));
        }

        function load_files(){

            if(!is_admin()){
                require plugin_dir_path( __DIR__ ).'frontend/class-wc-frontend-discount.php';
            }
        }
    }
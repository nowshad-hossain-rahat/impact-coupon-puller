<?php

    /*
        Plugin Name: Impact Coupon Puller
        Plugin URI: https://nhr-dev.herokuapp.com
        Author: Nowshad Hossain Rahat
        Author URI: https://github.com/nowshad-hossain-rahat
        Version: 1.0.0
        Description: This is a custom plugin to pull coupons from Impact.com through their REST API
        License: MIT
        Text Dmain: nhr-icp

    */


    if( ! defined( 'ABSPATH' ) ){ die; }


    // // including the composer autolaod
    if( file_exists( dirname( __FILE__ ).'/vendor/autoload.php' ) ){
        require_once( dirname( __FILE__ ).'/vendor/autoload.php' );
    }
    
    
    
    use Nowshad\ImpactCouponPuller\Database;
    use Nowshad\ImpactCouponPuller\AdminPages;


    class ImpactCouponPuller {

        function __construct(){
            
            # setup database
            Database::init();
        
            # adding admin page
            AdminPages::init();

            # post impact coupons automatically
            $settings = Database::getSettings();

            if( $settings['last_pushed_at'] ){

                $last_post_at = strtotime( $settings['last_pushed_at'] );
                $current_time = strtotime( date('Y-m-d H:i:s') );
                $diff = ( $current_time - $last_post_at ) / 60 / 60;
                $schedule = $settings['schedule_posting'];

                if( 
                    ( $schedule == 'hourly' && $diff >= 1 ) || 
                    ( $schedule == 'every_two_hours' && $diff >= 2 ) || 
                    ( $schedule == 'twice_a_day' && $diff >= 12 ) || 
                    ( $schedule == 'daily' && $diff >= 24 )
                ){
                    add_action('plugins_loaded', function(){ Database::postImpactCoupons(); } );
                }

            }

        }

    }


    $NHR_IMPACT_COUPON_PULLER = new ImpactCouponPuller();


?>
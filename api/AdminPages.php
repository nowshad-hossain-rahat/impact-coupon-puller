<?php
	
	namespace Nowshad\ImpactCouponPuller;


	class AdminPages {

		public static function adminPage(){
			
			echo "<link rel='stylesheet' type='text/css' href='".plugin_dir_url( dirname( __FILE__ ) )."css/admin-page.css' />";
			require_once plugin_dir_path( dirname( __FILE__ ) ).'views/settings.php';

		}
		
	
		# add all the menu pages
		public static function addAdminPages(){
			
			# adding admin menu page
			add_menu_page( 
				'Impact Coupon Puller', 
				'Impact Coupon Puller', 
				'manage_options', 
				'nhr_ipc_admin', 
				[self::class, 'adminPage'], 
				'', 10 
			);
			
		}
		

		# to initialize the admin pages
		public static function init(){
			add_action('admin_menu', [self::class, 'addAdminPages']);
		}

	}



?>
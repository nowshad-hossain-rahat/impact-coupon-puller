<?php

    namespace Nowshad\ImpactCouponPuller;

    use Exception;

    class Database {

        public static $settingsTable = "nhr_ipc_settings";
        public static $postedDealsIdTable = "nhr_ipc_posted_deals_id";



        # to initialize the database
        public static function init(){
            
            global $wpdb;
            require_once( ABSPATH .'wp-admin/includes/upgrade.php' );

            $createSettingsTable = "CREATE TABLE IF NOT EXISTS ".self::$settingsTable." (
                id INT PRIMARY KEY,
                account_sid VARCHAR(255),
                auth_token VARCHAR(255),
                coupons_to_import INT,
                coupon_title_length INT,
                schedule_posting ENUM('never', 'hourly', 'every_two_hours', 'twice_a_day', 'daily'),
                last_pushed_at DATETIME,
                last_pulled_page INT,
                post_status ENUM('pending', 'publish')
            )";
            
            $createPostedDealsIdTable = "CREATE TABLE IF NOT EXISTS ".self::$postedDealsIdTable." (
                id INT PRIMARY KEY AUTO_INCREMENT,
                deal_id INT UNIQUE,
                deal_tracking_url TEXT,
                posted_at DATETIME
            )";

            # executing the sql
            dbDelta($createSettingsTable);
            dbDelta($createPostedDealsIdTable);

            # adding some initial settings
            if(!self::getSettings()){

                $GLOBALS['wpdb']->insert(
                    self::$settingsTable,
                    [   
                        'account_sid' => '',
                        'auth_token' => '',
                        'coupons_to_import' => 10,
                        'coupon_title_length' => 75,
                        'schedule_posting' => 'daily',
                        'last_pulled_page' => '0',
                        'post_status' => 'pending'
                    ],
                    [ '%s', '%s', '%d', '%d', '%s', '%s' ]
                );

            }

        }



        // to delete and re-create all the tables created by this plugin
        public static function resetPlugin(){

            global $wpdb;

            $done = $wpdb->query("drop table if exists ".self::$settingsTable);

            if($done):
                self::init();
                return true;
            else:
                return false;
            endif;

        }



        # to add posted deals details
        public static function addPostedDeal(int $deal_id, string $deal_tracking_url){

            return $GLOBALS['wpdb']->insert(
                self::$postedDealsIdTable,
                [
                    'deal_id' => $deal_id,
                    'deal_tracking_url' => htmlspecialchars( $deal_tracking_url ),
                    'posted_at' => date('Y-m-d H:i:s')
                ],
                [ '%d', '%s', '%s' ]
            );

        }



        # to check if the deal already posted
        public static function isDealAlreadyPosted($post_title, $post_content){

            $table_name = $GLOBALS['wpdb']->prefix . 'posts';

            return $GLOBALS['wpdb']
                    ->get_row("SELECT * FROM $table_name WHERE post_title='$post_title' OR post_content='".htmlspecialchars( $post_content )."'", ARRAY_A) ? true:false;

        }



        # to fetch impact deals
        public static function getImpactCoupons(int $coupons_to_import = -1, int $coupon_title_length = -1, string $post_status = ''){

            $settings = self::getSettings();
            $account_sid = $settings['account_sid'];
            $auth_token = $settings['auth_token'];
            $cti = ($coupons_to_import > 0) ? $coupons_to_import:$settings['coupons_to_import'];
            $settings['coupon_title_length'] = $coupon_title_length ? $coupon_title_length:$settings['coupon_title_length'];
            $settings['post_status'] = $post_status ? $post_status:$settings['post_status'];
            $settings['last_pulled_page'] = $settings['last_pulled_page'] == '0' ? 1 : ((int) $settings['last_pulled_page'] + 1);
            $deals = [ 'page' => $settings['last_pulled_page'] ];


            if( empty($account_sid) || empty($auth_token) ){ return false; }

            $authUrl = "https://api.impact.com/Mediapartners/$account_sid/Ads";

            $authUrl = $authUrl . '?' . http_build_query([
                'Type' => 'COUPON',
                'pagesize' => $cti,
                'page' => $settings['last_pulled_page']
            ]);

            try{

                $curlHandle = curl_init();

                curl_setopt_array($curlHandle, [
                    CURLOPT_URL => $authUrl,
                    CURLOPT_USERPWD => "$account_sid:$auth_token",
                    CURLOPT_RETURNTRANSFER => true,
                    CURLOPT_HTTPHEADER => [
                        'Accept: application/json'
                    ]
                ]);

                $ads = json_decode(curl_exec($curlHandle))->Ads;
                
                # if no ads in this page then return the $deals array with empty data
                if( !$ads || gettype($ads) !== 'array' || count($ads) <= 0 ){

                    $deals['data'] = [];
                    return $deals;

                }


                # parsing only the important data
                $deals['data'] = array_map(function($ad) use ($settings){

                    $title = preg_replace("(;?\s*Promo code.+)", "", $ad->DealName);
                    $slug = trim( strtolower( str_replace( ' ', '-', $title ) ) );
                    preg_match("/((https|http):\/\/.+(com|net|org|biz|co|online|store|tech|shop|in|bd|ca))/", $ad->LandingPageUrl, $store_url);

                    if( empty($ad->DealId) ){ return false; }

                    return [
                        'id' => substr( md5($ad->DealId), 0, 16),
                        'deal_id' => $ad->DealId,
                        'sas_advertiser_id' => $ad->AdvertiserId,
                        'post_title' => substr( $title, 0, intval($settings['coupon_title_length']) ),
                        'post_content' => $ad->DealDescription,
                        'post_status' => $settings['post_status'],
                        'post_author' => 1,
                        'comment_status' => 'closed',
                        'tags_input' => $ad->Labels,
                        'post_type' => 'coupon',
                        'meta_input' => [
                            'clpr_daily_count' => 0,
                            'clpr_total_count' => 0,
                            'clpr_coupon_aff_clicks' => 0,
                            'clpr_votes_up' => 0,
                            'clpr_votes_down' => 0,
                            'clpr_votes_percent' => 100,
                            'clpr_coupon_code' =>$ad->DealDefaultPromoCode,
                            'clpr_coupon_aff_url' => $ad->TrackingLink,
                            'clpr_expire_date' => empty($ad->EndDate) ? date('Y-m-d H:i:s', time() + (60*60*24*30*12*10)):$ad->EndDate,
                            'clpr_featured' => '',
                            'sas_link_id' => $ad->Id,
                            'clpr_id' => substr( md5($ad->DealId), 0, 16),
                            'clpr_sys_userIP' => $_SERVER['REMOTE_ADDR']
                        ],
                        'terms' => [
                            'coupon_category' => 'deals',
                            'coupon_type' => empty($ad->DealDefaultPromoCode) ? 6:2
                        ],
                        'store_meta' => [
                            'store' => [
                                'slug' => strtolower(str_replace(' ', '-', trim($ad->AdvertiserName))),
                                'name' => trim($ad->AdvertiserName)
                            ],
                            'sas_advertiser_id' => $ad->AdvertiserId,
                            'clpr_store_active' => 'yes',
                            'clpr_store_url' => $store_url,
                            'clpr_store_aff_url' => $ad->LandingPageUrl
                        ]
                    ];

                }, $ads);

            }catch(Exception $e){}
            
            return $deals;

        }



        # to fetch settings
        public static function getSettings(){
            return $GLOBALS['wpdb']->get_row("SELECT * FROM ".self::$settingsTable." WHERE id='0'", ARRAY_A);
        }




        # post impact coupons
        public static function postImpactCoupons(int $coupons_to_import = -1, int $coupon_title_length = -1, string $post_status = ''){

            $deals = Database::getImpactCoupons($coupons_to_import, $coupon_title_length, $post_status);

            # if no data in the array, then set the last pulled to page to 1 and return
            if( count($deals['data']) <= 0 ){
                self::setLastPulledPage(1);
                return; 
            }

            foreach($deals['data'] as $deal){

                if( gettype( $deal ) === 'array' && !self::isDealAlreadyPosted($deal['post_title'], $deal['post_content']) ){ 

                    $date = date('Y-m-d H:i:s');
                    $deal['post_date'] = $date;
                    $deal['post_date_gmt'] = $date;
                    
                    $post_id = wp_insert_post($deal);

                    if( $post_id ){

                        # setting terms
                        foreach($deal['terms'] as $taxonomy => $term){
                            wp_set_object_terms((int)$post_id, $term, $taxonomy, true);
                        }

                        # setting stores info
                        $store_name = $deal['store_meta']['store']['name'];
                        $store_slug = $deal['store_meta']['store']['slug'];
                        $term_data = false;

                        # if store doesn't exists then create one otherwise get that store's info
                        if(!term_exists($store_slug, 'stores')){
                            
                            $term_data = wp_insert_term($store_name, 'stores', [ 'slug' => $store_slug ]);
                            
                            if( $term_data && !is_wp_error($term_data) ){

                                $store_id = $term_data['term_id'];

                                foreach($deal['store_meta'] as $meta_key => $meta_value){

                                    if( $meta_key == 'store' ){ continue; }
                                    
                                    $GLOBALS['wpdb']->insert(
                                        $GLOBALS['wpdb']->prefix . 'clpr_storesmeta',
                                        [   
                                            'stores_id' => $store_id,
                                            'meta_key' => $meta_key,
                                            'meta_value' => $meta_value
                                        ],
                                        [ '%d', '%s', '%s' ]
                                    );
                                
                                }
    
                            }

                        }else{
                            $term_data = get_term_by('slug', $store_slug, 'stores', ARRAY_A);
                        }

                        # connect the deal post with the store
                        if( $term_data && !is_wp_error($term_data) ){

                            wp_set_object_terms((int)$post_id, $store_slug, 'stores', true);

                        }

                        self::addPostedDeal((int)$deal['deal_id'], $deal['meta_input']['clpr_coupon_aff_url']);

                    }

                }

            }

            self::setLastPushedAt(date('Y-m-d H:i:s'));
            self::setLastPulledPage((int)$deals['page']);

            return $deals;

        }




        # TO UPDATE SETTINGS
        public static function updateSettings(
            int $coupons_to_import,
            int $coupon_title_length,
            string $schedule_posting,
            string $post_status
        ){

            $updated = $GLOBALS['wpdb']->update(
                self::$settingsTable,
                [   
                    'coupons_to_import' => $coupons_to_import,
                    'coupon_title_length' => $coupon_title_length,
                    'schedule_posting' => $schedule_posting,
                    'post_status' => $post_status
                ],
                ['id' => '0']
            );

            return ($updated) ? 'success':'error';

        }


        # to update last pushed_at
        public static function setLastPushedAt(string $last_pushed_at){

            $updated = $GLOBALS['wpdb']->update(
                self::$settingsTable,
                [   
                    'last_pushed_at' => $last_pushed_at
                ],
                ['id' => '0']
            );

            return ($updated) ? 'success':'error';

        }
        
        
        # to update last pulled page number
        public static function setLastPulledPage(int $last_pulled_page){

            $updated = $GLOBALS['wpdb']->update(
                self::$settingsTable,
                [   
                    'last_pulled_page' => $last_pulled_page
                ],
                ['id' => '0']
            );

            return ($updated) ? 'success':'error';

        }
        
        
        
        # TO UPDATE API SETTINGS
        public static function updateApiDetails(string $account_sid, string $auth_token){

            $updated = $GLOBALS['wpdb']->update(
                self::$settingsTable,
                [
                    'account_sid' => $account_sid,
                    'auth_token' => $auth_token
                ],
                ['id' => '0']
            );

            return ($updated) ? 'success':'error';

        }
        
        
        
        
        # to make '2022-01-08' to '08th January
        public static function beautifyDateStr(string $date){

            $months = [
                '01' => 'January',
                '02' => 'February',
                '03' => 'March',
                '04' => 'April',
                '05' => 'May',
                '06' => 'Jun',
                '07' => 'July',
                '08' => 'August',
                '09' => 'September',
                '10' => 'October',
                '11' => 'November',
                '12' => 'December'
            ];

            $dateArr = explode('-', $date);

            $day = $dateArr[2];

            if(str_split($day)[0]=='0'){ $day = str_replace('0', '', $day); }

            $month = $months[$dateArr[1]];
            $year = $dateArr[0];

            if($day == '1'){ $day .= 'st'; }
            else if($day == '2'){ $day .= 'nd'; }
            else if($day == '3'){ $day .= 'rd'; }
            else{ $day .= 'th'; }

            return "$day $month, $year";

        }



        # to beautify date-time strings
        public static function beautifyDateTimeStr(string $dateTime="0000-00-00 00:00:00", bool $onlyDate=false){
            
            if( count(explode(' ', $dateTime)) > 1 ):

                $date = self::beautifyDateStr( explode( ' ', $dateTime)[0] );
                $timeStr = explode(' ', $dateTime)[1];

                $hour = intval(explode(':', $timeStr)[0]);
                $min = explode(':', $timeStr)[1];

                $suffix = ($hour < 12) ? 'AM':'PM';
                $hour = ($hour <= 12) ? $hour:absint($hour - 12);
                
                $dateTime = (!$onlyDate) ? ($date.' '.$hour.':'.$min.' '.$suffix) : $date;

                return $dateTime;

            else:

                return self::beautifyDateStr( $dateTime );

            endif;
    
        }



    }







?>
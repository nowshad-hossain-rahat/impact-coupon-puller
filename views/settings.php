<?php

    use Nowshad\ImpactCouponPuller\Database;

    // to show a cancellable admin notice
    function notify(string $msg, string $type, bool $is_dismissible=true){
        $dismissible = ($is_dismissible==true) ? "is-dismissible":''; 
        $notice_board = "<div style='margin-left: 2px;margin-right: 10px;' class='notice notice-$type $dismissible'>".
                            "<p>$msg</p>".
                        "</div>";
        echo $notice_board;
    }



    // if requested for plugin reset
    if(
        isset($_POST['reset_plugin']) && 
        isset($_POST['confirm_reset_plugin']) && 
        $_POST['confirm_reset_plugin'] == "CONFIRM RESET PLUGIN"
    ){

        if(Database::resetPlugin()){
            notify("Plugin reset successful!", "success");
        }else{
            notify("Reset failed, please try again later!", "warning");
        }

    }else if(isset($_POST['ss'])){
        
        # if requested to save settings (ss)
        $coupons_to_import = intval($_POST['coupons_to_import']);
        $coupon_title_length = intval($_POST['coupon_title_length']);
        $schedule_posting = trim($_POST['schedule_posting']);
        $post_status = trim($_POST['pwa']);

        if( empty($coupons_to_import) || empty($schedule_posting) || empty($post_status) ){
            notify("All the fields are required!", "warning");
        }else{

            $updated = Database::updateSettings($coupons_to_import, $coupon_title_length, $schedule_posting, $post_status);

            if($updated){
                notify("Settings saved!", "success");
            }else{
                notify("Settings not saved, please try again!", "warning");
            }

        }

    }else if(isset($_POST['manual_coupon_posting'])){
        
        $coupons_to_import = intval($_POST['coupons_to_import']);
        $coupon_title_length = intval($_POST['coupon_title_length']);
        $post_status = trim($_POST['pwa']);

        if( empty($coupons_to_import) || empty($coupon_title_length) || empty($post_status) ){
            notify("All the fields are required!", "warning");
        }else{

            Database::postImpactCoupons($coupons_to_import, $coupon_title_length, $post_status);
            notify("Posted successfully!", "success");

        }

    }else if(isset($_POST['save_api_access'])){

        # to save api details
        $account_sid = trim($_POST['account_sid']);
        $auth_token = trim($_POST['auth_token']);

        if(empty($account_sid) || empty($auth_token)){

            notify("Both Account SID & Auth Token are required!", "warning");

        }else{

            $updated = Database::updateApiDetails($account_sid, $auth_token);

            if($updated){
                notify("API dtails saved!", "success");
            }else{
                notify("API details not saved, please try again!", "warning");
            }

        }

    }


    // fetching the settings
    $settings = Database::getSettings();

    # show alert if the Account SID and Auth Token keys are not set
    if(empty($settings['account_sid']) || empty($settings['auth_token'])){

        notify("Setup your <b>Impact API access details</b>, otherwise the Ads cannot be pulled!", "warning", false);

    }

?>

<h1>Impact Coupon Puller</h1><hr>

<h2>Publisher API details</h2><hr>
<form method="post">

    <label>Account SID :</label>
    <input type="text" name="account_sid" placeholder="Account SID" value="<?php echo $settings['account_sid']; ?>" required />
    
    <label>Auth Token :</label>
    <input type="password" name="auth_token" placeholder="Auth Token" value="<?php echo $settings['auth_token']; ?>" required />
    
    <input class="btn btn-primary" type="submit" value="Save" name="save_api_access">

</form>

<?php if( !$settings['account_sid'] ){ return; } ?>

<h2>General Settings</h2><hr>
<div class="last-posted-at" style="display: inline-block; padding: 10px 15px; background: #333; color: #eee; margin: 5px 0; border-radius: 5px; font-weight: bold;">
    <span style="color: yellow;">Last succefull post : </span>
    <?php echo $settings['last_pushed_at'] ? Database::beautifyDateTimeStr( $settings['last_pushed_at'] ):'Never'; ?>
</div>

<form method="post">
    
    <label>Coupons to import :</label>
    <input type="number" min="0" name="coupons_to_import" placeholder="Coupons to import" value="<?php echo trim($settings['coupons_to_import']); ?>" required />
    
    <label>Coupons title length :</label>
    <input type="number" min="30" name="coupon_title_length" placeholder="Coupons title length" value="<?php echo trim($settings['coupon_title_length']); ?>" required />
    
    <label>Schedule posting :</label>
    <select name="schedule_posting" required>
        <option <?php echo $settings['schedule_posting'] === 'never' ? 'selected':'' ?> value="never">Never</option>
        <option <?php echo $settings['schedule_posting'] === 'every_two_hours' ? 'selected':'' ?> value="every_two_hours">Every Two Hours</option>
        <option <?php echo $settings['schedule_posting'] === 'hourly' ? 'selected':'' ?> value="hourly">Hourly</option>
        <option <?php echo $settings['schedule_posting'] === 'twice_a_day' ? 'selected':'' ?> value="twice_a_day">Twice a day</option>
        <option <?php echo $settings['schedule_posting'] === 'daily' ? 'selected':'' ?> value="daily">Daily</option>
    </select>

    <label>Publish without approval?</label>
    <span>
        <label for="pwa_yes" style="margin-right: 10px;"> 
            <input <?php echo $settings['post_status'] === 'publish' ? 'checked':'' ?> type="radio" name="pwa" id="pwa_yes" value="publish"> Yes 
        </label>
        <label for="pwa_now"> 
            <input <?php echo $settings['post_status'] === 'pending' ? 'checked':'' ?> type="radio" name="pwa" id="pwa_no" value="pending"> No 
        </label>
    </span>

    <input class="btn btn-primary" type="submit" value="Save" name="ss">

</form>


<h2>Manual Coupons Posting</h2><hr>
<form method="post">
    
    <label>Coupons to import :</label>
    <input type="number" min="0" value="1" name="coupons_to_import" placeholder="Coupons to import" required />
    
    <label>Coupons title length :</label>
    <input type="number" min="30" name="coupon_title_length" placeholder="Coupons title length" value="30" required />

    <label>Publish without approval?</label>
    <span>
        <label for="pwa_yes" style="margin-right: 10px;"> 
            <input checked type="radio" name="pwa" id="pwa_yes" value="publish"> Yes 
        </label>
        <label for="pwa_now"> 
            <input type="radio" name="pwa" id="pwa_no" value="pending"> No 
        </label>
    </span>

    <input class="btn btn-primary" type="submit" value="Post now" name="manual_coupon_posting">

</form>

<h1>Reset Plugin</h1>
<hr>

<p style="color: firebrick; font-weight: bold;"> Caution : Everything will be deleted permanently and nothing can be restored!</p>

<form method="post">
    <label>Write <b>CONFIRM RESET PLUGIN</b> to confirm that you're not reseting the plugin data by mistake!</label>
    <input type="text" name="confirm_reset_plugin" placeholder="Confirm reset" required />
    <input name="reset_plugin" type="submit" value="Reset" class="btn" style="cursor: pointer; background-color: firebrick;" />
</form>




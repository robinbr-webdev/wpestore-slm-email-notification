<?php
/*
Plugin Name:LPB Custom Email Notification
Description: This plugin is used to modify Software License Manager Plugin and WP eStore plugin send email functions and settings 
Version: 1.0
Author: Mr. Rob Reyes
License: GPL2
*/

//------------------------------------------ ADD ALL NEEDED HOOKS ------------------------------------------//
function lpb_cne_plugin_init(){//LPB custom email notification initialize
	$reflFunc = new ReflectionFunction('wp_eStore_email_settings');
	$endline = $reflFunc->getEndLine();
	$dir_path = $reflFunc->getFileName();
	
	$content = file($dir_path);
	for($i = 0;$i<$endline;$i++){
		if(strpos($content[$i],'<div class="submit">') === false){
			
		}else
		{
			$allContent = implode("", $content); 
			if(strpos($allContent,'do_action("wp_eStore_email_settings")') === false){
				$content[$i] = '<?php do_action("wp_eStore_email_settings");?>'.PHP_EOL.$content[$i]; 
				$newContent = implode("", $content); 
				file_put_contents($dir_path, $newContent);
			}
		} 
	}
	
	//eStore send mail function
	$eStore_send_mail_rf = new ReflectionFunction('eStore_send_mail');
	$esm_startline = $eStore_send_mail_rf->getStartLine();
	$esm_dir_path = $eStore_send_mail_rf->getFileName();
	$esm_content = file($esm_dir_path);
	$esm_allContent = implode("", $esm_content);
	if(strpos($esm_allContent,'do_action("eStore_send_mail",$to, $body, $subject, $from , $attachment)') === false){
		$esm_content[$esm_startline+2] = 'do_action("eStore_send_mail",$to, $body, $subject, $from , $attachment);'.PHP_EOL.$esm_content[$esm_startline+2];
		$esm_newContent = implode("", $esm_content); 
		file_put_contents($esm_dir_path, $esm_newContent);
	}
	
	//////////////////////////// SLM //////////////////////////////////////////

	$slm_settings_rf = new ReflectionFunction('wp_lic_mgr_general_settings');
	$slm_settings_endline = $slm_settings_rf->getEndLine();
	$slm_settings_dir_path = $slm_settings_rf->getFileName();
	
	$slm_settings_content = file($slm_settings_dir_path);
	for($i = 0;$i<$slm_settings_endline;$i++){
		if(strpos($slm_settings_content[$i],'<div class="submit">') === false){
			
		}else
		{
			$slm_settings_allContent = implode("", $slm_settings_content); 
			if(strpos($slm_settings_allContent,'do_action("wp_lic_mgr_general_settings")') === false){
				$slm_settings_content[$i] = '<?php do_action("wp_lic_mgr_general_settings");?>'.PHP_EOL.$slm_settings_content[$i]; 
				$slm_settings_newContent = implode("", $slm_settings_content); 
				file_put_contents($slm_settings_dir_path, $slm_settings_newContent);
			}
		} 
	}
	
	$licenses_class_file = WP_PLUGIN_DIR."/software-license-manager/menu/slm-list-licenses-class.php";
	$lpb_cen_file = plugin_dir_path(__FILE__)."lpb_cen_class.php";
	$lpb_cen_file_nl = WP_PLUGIN_DIR."/software-license-manager/menu/lpb_cen_class.php";
	
	//echo $lpb_cen_file_nl;
	if(!file_exists($lpb_cen_file_nl)){
		copy($lpb_cen_file,$lpb_cen_file_nl);
	}
	
	$slm_ml = new ReflectionFunction('wp_lic_mgr_manage_licenses_menu');
	$slm_ml_endline = $slm_ml->getEndLine();
	$slm_ml_dir_path = $slm_ml->getFileName();
	
	$slm_ml_content = file($slm_ml_dir_path);
	for($i = 0;$i<$slm_ml_endline;$i++){
		if(strpos($slm_ml_content[$i],'$license_list = new WPLM_List_Licenses();') === false){
			
		}else
		{
			$slm_ml_allContent = implode("", $slm_ml_content); 
			if(strpos($slm_ml_allContent,'$license_list = new LPB_CEN();') === false){
				$slm_ml_content[$i] = 'include_once("lpb_cen_class.php");'.PHP_EOL.'$license_list = new LPB_CEN();'.PHP_EOL; 
				$slm_ml_newContent = implode("", $slm_ml_content); 
				file_put_contents($slm_ml_dir_path, $slm_ml_newContent);
			}
		} 
	}
	
	
	/* echo "<pre style='margin-left:200px;'>";
	print_r($slm_list_content);
	echo "</pre>";   */
	
}
add_action("init","lpb_cne_plugin_init");

//////////////////////////////////////////////////////////////////////
//
//					WP eStore PLUGIN CUSTOMIZATIONS
//
//////////////////////////////////////////////////////////////////////

//------------------------------------------ THE EMAIL TEMPLATE ------------------------------------------//
if(!function_exists("email_template")){
	function email_template($emails,$content,$subject,$headers = "",$attachment=""){
		$op_logo = maybe_unserialize(get_option("optimizepress_header_logo_setup"));
		$logo = $op_logo["logo"];
		
		$to = $emails;
		$email = get_option("admin_email");
		$body = "<div style = 'background:#333;padding:30px;'>
		<img src = '".$logo."' alt = 'landing page booster logo' style = 'margin-bottom:30px;'/>
		<div style = 'background:#fff;padding:30px;min-height:500px;font-family:Arial;'>
			".$content."
			<br /><br /><br />
			<strong>Thank you for using Landing Page Booster</strong>
			<p>".get_site_url()."</p>
			<p>".$email."</p>
			<p>0123-456-7890</p>
		</div>
		</div>";
		
		if(empty($headers)){
			$headers = array('Content-Type: text/html; charset=UTF-8'); 
		}
		 
		$sent = wp_mail( $to, $subject, $body, $headers,$attachment );	
		
		return $sent;
	}
}

//------------------------------------------ USE EMAIL TEMPLATE IN WP ESTORE ------------------------------------------//
add_filter("eStore_notification_email_body_filter","use_email_template",11,3);
function use_email_template($body,$ipn_data,$cart_items){
	global $wpdb;
	
	$op_logo = maybe_unserialize(get_option("optimizepress_header_logo_setup"));
	$logo = $op_logo["logo"];
	$email = get_option("admin_email");
	
	$products_table_name = $wpdb->prefix . "wp_eStore_tbl";
    $slm_data = "";

    foreach ($cart_items as $current_cart_item) {
        $prod_id = $current_cart_item['item_number'];
        $retrieved_product = $wpdb->get_row("SELECT * FROM $products_table_name WHERE id = '$prod_id'", OBJECT);
        $package_product = eStore_is_package_product($retrieved_product);
        if ($package_product) {
            $slm_debug_logger->log_debug('Checking license key generation for package/bundle product.');
            $product_ids = explode(',', $retrieved_product->product_download_url);
            foreach ($product_ids as $id) {
                $id = trim($id);
                $retrieved_product_for_specific_id = $wpdb->get_row("SELECT * FROM $products_table_name WHERE id = '$id'", OBJECT);
                $slm_data .= slm_estore_check_and_generate_key($retrieved_product_for_specific_id, $ipn_data, $cart_items);
            }
        } else {
            $slm_debug_logger->log_debug('Checking license key generation for single item product.');
            $slm_data .= slm_estore_check_and_generate_key($retrieved_product, $ipn_data, $cart_items);
        }
    }

    $body = str_replace("{slm_data}", $slm_data, $body);
	
	$new_body = "<div style = 'background:#333;padding:30px;'>
		<img src = '".$logo."' alt = 'landing page booster logo' style = 'margin-bottom:30px;'/>
		<div style = 'background:#fff;padding:30px;min-height:500px;font-family:Arial;'>
			".$body."
			<br /><br /><br />
			<strong>Thank you for using Landing Page Booster</strong>
			<p>".get_site_url()."</p>
			<p>".$email."</p>
			<p>0123-456-7890</p>
		</div>
		</div>";
		
	/* //send email for recurring payments
	if(function_exists("is_paypal_recurring_payment")){//be safe
		$recurring_payment = is_paypal_recurring_payment($ipn_data);
		$is_recurring_customer = update_last_customer_license_details($ipn_data['payer_email']);
		if($is_recurring_customer or $recurring_payment){
			$email_subject = get_option('eStore_rp_email_subj');
			$email_body = get_option('eStore_rp_email_body');
					
		}
	} */
	
	return $new_body;
}

add_action("eStore_paypal_recurring_payment_received","send_recurring_payment_email",11,2);
function send_recurring_payment_email($ipn_data, $cart_items){
	global $wpdb;
	
	$products_table_name = $wpdb->prefix . "wp_eStore_tbl";
    $slm_data = "";

    foreach ($cart_items as $current_cart_item) {
        $prod_id = $current_cart_item['item_number'];
        $retrieved_product = $wpdb->get_row("SELECT * FROM $products_table_name WHERE id = '$prod_id'", OBJECT);
        $package_product = eStore_is_package_product($retrieved_product);
        if ($package_product) {
            $slm_debug_logger->log_debug('Checking license key generation for package/bundle product.');
            $product_ids = explode(',', $retrieved_product->product_download_url);
            foreach ($product_ids as $id) {
                $id = trim($id);
                $retrieved_product_for_specific_id = $wpdb->get_row("SELECT * FROM $products_table_name WHERE id = '$id'", OBJECT);
                $slm_data .= slm_estore_check_and_generate_key($retrieved_product_for_specific_id, $ipn_data, $cart_items);
            }
        } else {
            $slm_debug_logger->log_debug('Checking license key generation for single item product.');
            $slm_data .= slm_estore_check_and_generate_key($retrieved_product, $ipn_data, $cart_items);
        }
    }
	
	$license = $slm_data;
	
	$to = $ipn_data["payer_email"];
	
	$subject = get_option('eStore_rp_email_subj');
	
	$email_body = get_option('eStore_rp_email_body');
	$body = $email_body."<br /><strong>New License Key: </strong>".$license;
	
	$download_email = get_option('eStore_download_email_address');
	$site_title = get_bloginfo( 'name' );
	$headers = 'From: '.$site_title.' <' . $download_email . ">\r\n"; 
	$headers .= 'Reply-To:' . $download_email . "\r\n";
	$headers .= "Content-Type: text/html; charset=UTF-8 \r\n";
	
	email_template($to,$body,$subject,$headers);
}
	
function update_last_customer_license_details($email){
	global $wpdb;
	
	$lic_table = $wpdb->get_results("select * from ".$wpdb->prefix."lic_key_tbl where email = '".$email."' order by id desc",ARRAY_A);
	
	if(count($lic_table) > 0){
		$last_payment = $lic_table[1];
		$data = array(
			"date_renewed" => date("Y-m-d"),
			"lic_status" => "expired"
		);
		$where = array("id"=>$last_payment["id"]);
		$wpdb->update($wpdb->prefix."lic_key_tbl",$data,$where); 
		
		return true;
	}else{
		return false;
	}
}

//------------------------------ WP ESTORE SEND EMAIL ADD ACTION  ------------------------------------------//
add_action("eStore_send_mail","send_email_in_template",11,5);
function send_email_in_template($to, $body, $subject, $from , $attachment){
	$from = $from."Content-Type: text/html; charset=UTF-8 \r\n";
	
	return email_template($to,$body,$subject,$from,$attachment);
}


//------------------------------ ADD RP FIELDS IN WP eStore EMAIL SETTINGS  ------------------------------------------//
add_action("wp_eStore_email_settings","wp_eStore_rp_email_settings",11,0);
function wp_eStore_rp_email_settings(){
	if (isset($_POST['estore_email_settings_update']))
    {
		update_option("eStore_rp_email_subj",stripslashes((string)$_POST["eStore_rp_email_subj"]));
		update_option("eStore_rp_email_body",stripslashes((string)$_POST["eStore_rp_email_body"]));
	}
	
	$eStore_rp_email_subj = get_option("eStore_rp_email_subj");
	$eStore_rp_email_body = get_option("eStore_rp_email_body");
	?>
	<!------------------------my custom code start---------------------------------------------->
	<div class="postbox">
		<h3 class="hndle"><label for="title">Recurring Payment Email Settings</label></h3>
		<div class="inside">
			<p><i>The following options is used in the email recurring payment notification sent to the customer.</i></p>
				
			<table class="form-table">
				<tr valign="top">
				<th scope="row">Recurring Payment Email Subject*</th>
				<td><input type="text" name="eStore_rp_email_subj" value="<?php echo $eStore_rp_email_subj; ?>" size="50" />
				<br /><p class="description">This is the subject of the recurring payment email that will be sent to the buyer.</p></td>
				</tr>
				
				 <tr valign="top">
				<th scope="row">Recurring Payment Email Body*</th>
				<td>
				<?php 
				$rp_body_settings = array('textarea_name' => 'eStore_rp_email_body');
				wp_editor($eStore_rp_email_body, "eStore_rp_email_body_content", $rp_body_settings);
				?>
				<br /><p class="description">This is the email sent for the recurring payments of the customer. WP eStore email tags are can't be used here. License key is attached at the bottom of the message in the email.</p></td>
				</tr>
			</table>
		</div>
	</div>
	<!------------------------my custom code end---------------------------------------------->
	<?php
}
//////////////////////////////////////////////////////////////////////
//
//					SLM PLUGIN CUSTOMIZATIONS
//
//////////////////////////////////////////////////////////////////////

function email_expiration_notification() {
    do_action('email_expiration_notification');
}

function enable_auto_key_expiry() {
    do_action('enable_auto_key_expiry');
}

function license_email_expiration_notification(){    
	global $wpdb;

	$next_week = date("Y-m-d",strtotime("+1 week"));
	$license_table = SLM_TBL_LICENSE_KEYS;
	$orderby = 'id';
	$order = 'DESC';

	$query = "SELECT * FROM $license_table where date_expiry = '$next_week' and id in ( select max(id) as id from $license_table  group by email ) ORDER BY $orderby $order";
	$data = $wpdb->get_results($query,ARRAY_A); 
	
	$options = get_option('slm_plugin_options');
	
	/* echo "<pre>";
	print_r($data);
	echo "</pre>"; */
	if(count($data) > 0){
		foreach($data as $customer){
			$to = $customer["email"].",jeniffer.go@gmail.com,robinbr.webdev@gmail.com"; 
		//	$to = "jeniffer.gobuyer@gmail.com";
			$subject = $options["expiry_email_subject"];
			$body = $options["expiry_email_message"];
			$headers = array(
				'Content-Type: text/html; charset=UTF-8',
				'From: Landing Page Booster <'.$options["expiry_reply_email"].'>'
			);
			
			email_template($to,$body,$subject,$headers);
			//wp_mail( $to, $subject, $body, $headers );  
		}
	}
}
//add_action("init","license_email_expiration_notification");
add_action("email_expiration_notification","license_email_expiration_notification");

function update_expired_license_status(){
	global $wpdb;
	
	$license_table = SLM_TBL_LICENSE_KEYS;
	$options = get_option('slm_plugin_options');
	$enable_auto_key_expiry = $options["enable_auto_key_expiry"];
	
	$data = $wpdb->get_results("SELECT * FROM $license_table where lic_status <> 'expired' and id in ( select max(id) as id from $license_table group by email )  ORDER BY id desc", ARRAY_A); 
	
	foreach($data as $slm){
		$id = $slm["id"];
		//only update when this setting is checked
		if($enable_auto_key_expiry == 1){
			$status = (date("Y-m-d") >= $slm["date_expiry"])?"expired":$slm["lic_status"];
			$wpdb->query("update $license_table set lic_status = '$status' where id = $id");    
		}
	}
}
add_action("enable_auto_key_expiry","update_expired_license_status");

add_action("wp_lic_mgr_general_settings","slm_expiry_notification_settings",11,0);
function slm_expiry_notification_settings(){
	if (isset($_POST['slm_save_settings'])) {
		$options = array(
            'lic_creation_secret' => trim($_POST["lic_creation_secret"]),
            'lic_prefix' => trim($_POST["lic_prefix"]),
            'default_max_domains' => trim($_POST["default_max_domains"]),
            'lic_verification_secret' => trim($_POST["lic_verification_secret"]),
            'enable_auto_key_expiry' => isset($_POST['enable_auto_key_expiry']) ? '1':'',
            'enable_debug' => isset($_POST['enable_debug']) ? '1':'',
			'expiry_email_message' => $_POST['expiry_email_message'],
			'expiry_email_subject' => $_POST['expiry_email_subject'],
			'expiry_reply_email' => $_POST['expiry_reply_email'] 
        );
        update_option('slm_plugin_options', $options);
	}
	
	$options = get_option('slm_plugin_options');
	?>
	<div class="postbox">
		<h3 class="hndle"><label for="title">Expiry Notification Email Settings</label></h3>
		<div class="inside">
			<table class="form-table">
				<tr valign="top">
					<th scope="row">Reply Email: </th>
					<td>
						<input type="email" name="expiry_reply_email" value="<?php echo $options['expiry_reply_email']; ?>" size = 100 />
						 <p class="description">The from email in the expiry notification email.</p>
					</td>
				</tr>
				<tr valign="top">
					<th scope="row">Subject: </th>
					<td>
						<input type="text" name="expiry_email_subject" value="<?php echo $options['expiry_email_subject']; ?>" size = 100 />
						 <p class="description">The subject header in the expiry notification email.</p>
					</td>
				</tr>
				
				<tr valign="top">
					<th scope="row">Message: </th>
					<td>
						<?php wp_editor( $options['expiry_email_message'], "expiry_email_message" );?>
						 <p class="description">The message that will contain the instructions to update the license key in the expiry notification email.</p> 
					</td>
				</tr>
			</table>
		</div>
	</div>
	<?php
}
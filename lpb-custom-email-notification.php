<?php
/*
Plugin Name: WP eStore and SLM email notification custom plugin
Description: This plugin is used to modify Software License Manager Plugin and WP eStore plugin custom email functions and settings 
Version: 1.2.2.0
Author: Netseek
License: GPL2
*/

/**
* Initialize plugin and add the required action hooks inside SLM and WP eStore.
*/
function lpb_cne_plugin_init(){
	$plugin_data = get_plugin_data(__FILE__);
	$old_version = get_option("lpb_enc_version","");
	if(empty($old_version)){
		update_option("lpb_enc_version",$plugin_data["Version"]);
		$old_version = $plugin_data["Version"];
	}
	
	$new_version = $plugin_data["Version"];
	if($new_version != $old_version){
		//Add action hook to WP eStore wp_eStore_email_settings functions
		//This hook will be used to display new fields to customize the email settings in WP eStore
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
		
		//Add action hook to eStore_send_mail function
		//This hook will be used to generate email notifications using the custom email template
		$eStore_send_mail_rf = new ReflectionFunction('eStore_send_mail');
		$esm_startline = $eStore_send_mail_rf->getStartLine();
		$esm_endline = $eStore_send_mail_rf->getEndLine();
		$esm_dir_path = $eStore_send_mail_rf->getFileName();
		$esm_content = file($esm_dir_path);
		$esm_allContent = implode("", $esm_content);
		if(strpos($esm_allContent,'do_action("eStore_send_mail",$to, $body, $subject, $from , $attachment)') === false){
			$esm_content[$esm_startline+2] = 'do_action("eStore_send_mail",$to, $body, $subject, $from , $attachment);'.PHP_EOL.'if(!function_exists("lpb_cne_plugin_init")){'.PHP_EOL.$esm_content[$esm_startline+2];
			 
			for($a=0;$a<$esm_endline;$a++){
				if(strpos($esm_content[$a],'return $email->send();') === false){
					//do nothing
				}else{
					if(strpos($esm_allContent,'if(!function_exists("lpb_cne_plugin_init")){') === false){
						$esm_content[$a] = $esm_content[$a].PHP_EOL."}else{return true;}";
					}
				}
			}
			
			$esm_newContent = implode("", $esm_content);
			file_put_contents($esm_dir_path, $esm_newContent);
		}
		
		
		
		//////////////////////////// SLM //////////////////////////////////////////

		//Adds an action hook to wp_lic_mgr_general_settings function
		//This hook will be used to add additional fields to customize the expiry notification email
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
		
		
		//This lines of code adds a new class file inside SLM plugin.
		//The class extends the WPLM_List_Licenses prepare items method to display only the current subscriptions of each customers.
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
		
		$keyword = '$license_list = new LPB_CEN();';
		$all_slm = implode("",$slm_ml_content);
		foreach($slm_ml_content as $index => $string) {
			if (strpos($all_slm, '$license_list = new WPLM_List_Licenses();') === FALSE){
				if (strpos($string, '$license_list = new LPB_CEN();') !== FALSE){
					$slm_ml_content[$index] = '$license_list = new WPLM_List_Licenses();'.PHP_EOL;
				}
				
				if (strpos($string, 'include_once( "lpb_cen_class.php" );') !== FALSE){
					$slm_ml_content[$index] = "";
				}
				
				if (strpos($string, 'include_once("lpb_cen_class.php");') !== FALSE){
					$slm_ml_content[$index] = "";
				}
				
				$slm_ml_newContent = implode("", $slm_ml_content); 
				file_put_contents($slm_ml_dir_path, $slm_ml_newContent);
			}	
		}
	}//end of version compare
}
add_action("admin_init","lpb_cne_plugin_init");

//////////////////////////////////////////////////////////////////////
//
//					WP eStore PLUGIN CUSTOMIZATIONS
//
//////////////////////////////////////////////////////////////////////

/**
* Used to display the custom email template in paypal email notifications.
*							
* @param string $emails Emails used to send the message.
* @param string $content Contains the message in the email.
* @param string $subject Contains the subject of the email.
* @param string $headers Contains the headers needed in the email.
* @param string $attachment Contains the attached files for the email.
*
* @return boolean
*/
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
		</div>
		</div>";
		
		if(empty($headers)){
			$headers = array('Content-Type: text/html; charset=UTF-8'); 
		}
		
		//echo "<br />email lpb template<br />";
		 
		$sent = wp_mail( $to, $subject, $body, $headers,$attachment );	
		
		return $sent;
	}
}


/**
* Hooked this function to eStore_notification_email_body_filter. 
* To add the html tags for the custom email template. 								
* Paypal recurring email notification also happens here.
* 
* @param string $body Contains the body of the email message to be sent. 
* @param array $ipn_data Contains data returned by paypal after transactions.
* @param array $cart_items Contains the cart items.
*
* @return string $body of the email message
*/
add_filter("eStore_notification_email_body_filter","use_email_template",11,3);
function use_email_template($body,$ipn_data,$cart_items){
	global $slm_debug_logger, $wpdb;
	$op_logo = maybe_unserialize(get_option("optimizepress_header_logo_setup"));
	$logo = $op_logo["logo"];
	$email = get_option("admin_email");
	
	$slm_data = "";
	$license_table = $wpdb->prefix . "lic_key_tbl";
	$transaction_id = $ipn_data["txn_id"];
	
	$result = $wpdb->get_row("SELECT * FROM $license_table where txn_id = '$transaction_id'",ARRAY_A);
	$slm_data = $result["license_key"];

    $body = str_replace("{slm_data}", $slm_data, $body);
		
	$recurring_payment = is_paypal_recurring_payment($ipn_data);
	//$ren_option = get_option("ren_option_".$ipn_data["payer_email"]);	
	if($recurring_payment /* and $ren_option != "ren_sent" */){ 
		send_recurring_payment_email($ipn_data,$cart_items);
		//update last customer subscription 
		//update_last_customer_license_details($ipn_data["payer_email"]);
	}
	
	return $body;
}


/**
*
* The function where recurring email is sent to the customer. 
* Sends the recurring email.										  
*
* @param array $ipn_data Contains data returned by paypal after transactions.
* @param array $cart_items Contains the cart items.
*
*/
//add_action("eStore_paypal_recurring_payment_received","send_recurring_payment_email",11,2);// commented just in case can be used in WP eStore eStore_paypal_recurring_payment_received hook
function send_recurring_payment_email($ipn_data, $cart_items){
	global $wpdb,$slm_debug_logger;
	
	$slm_data = "";
	$license_table = $wpdb->prefix . "lic_key_tbl";
	$transaction_id = $ipn_data["txn_id"];
	$email_subject = get_option('eStore_rp_email_subj');
	$email_body = get_option('eStore_rp_email_body');
	
	$result = $wpdb->get_row("SELECT * FROM $license_table where txn_id = '$transaction_id'",ARRAY_A);
	$slm_data = $result["license_key"];
	
	$tags = array("{first_name}", "{last_name}", "{slm_data}");
	$values = array($ipn_data['first_name'], $ipn_data['last_name'],$slm_data);
	
	$to = (string)$ipn_data["payer_email"];
	$subject = str_replace($tags, $values, $email_subject);
    $body = stripslashes(str_replace($tags, $values, wpautop($email_body)));
	
	$download_email = get_option('eStore_download_email_address');
	$site_title = get_bloginfo( 'name' );
	$headers = 'From: '.$site_title.' <' . $download_email . ">\r\n"; 
	$headers .= 'Reply-To:' . $download_email . "\r\n";
	$headers .= "Content-Type: text/html; charset=UTF-8 \r\n";
	
	email_template($to,$body,$subject,$headers);
}

/**
* Update customer latest subscription before the current subsription status and date renewed fields.
*												  
* @param string $email The customer email.
*
* @return boolean
*/	
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

/**
* This will send email notification using the custom email template.
*									
* @param string $to Emails used to send the message.
* @param string $body Contains the message in the email.
* @param string $subject Contains the subject of the email.
* @param string $from Contains the headers needed in the email.
* @param string $attachment Contains the attached files for the email.
* 
* @return boolean
*/	
add_action("eStore_send_mail","send_email_in_template",11,5);
function send_email_in_template($to, $body, $subject, $from , $attachment){
	
	$site_title = get_bloginfo( 'name' );
	$headers = 'From: '.$site_title.' <' . $from . ">\r\n"; 
	$headers .= 'Reply-To:' . $from . "\r\n";
	$headers .= "Content-Type: text/html; charset=UTF-8 \r\n";
	
	$body = str_replace("\n","<br />",$body);
	return email_template($to,$body,$subject,$headers,$attachment);
}


/**
* Creates a new fields in WP eStores email settings to customize the recurring payment  email notification subject and message.
*/
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
				<br /><p class="description">This is the email sent for the recurring payments of the customer. WP eStore {first_name},{last_name} and {slm_data} email tags only are supported here.</p></td>
				</tr>
			</table>
		</div>
	</div>
	<?php
}
//////////////////////////////////////////////////////////////////////
//
//					SLM PLUGIN CUSTOMIZATIONS
//
//////////////////////////////////////////////////////////////////////

/**
* Action hook used for the license email expiration notification.
*/
function email_expiration_notification() {
    do_action('email_expiration_notification');
}

/**
* Action hook used for the auto license key expiration.
*/
function enable_auto_key_expiry() {
    do_action('enable_auto_key_expiry');
}

/**
* Emails customers that their subscription is expiring one week before their expiration date.
*/
function license_email_expiration_notification(){    
	global $wpdb;

	$next_week = date("Y-m-d",strtotime("+1 week"));
	$license_table = SLM_TBL_LICENSE_KEYS;
	$orderby = 'id';
	$order = 'DESC';

	$query = "SELECT * FROM $license_table where date_expiry = '$next_week' and id in ( select max(id) as id from $license_table  group by email ) ORDER BY $orderby $order";
	$data = $wpdb->get_results($query,ARRAY_A); 
	
	$options = get_option('slm_plugin_options');
	
	if(count($data) > 0){
		foreach($data as $customer){
			$to = $customer["email"]; 
			$subject = $options["expiry_email_subject"];
			$body = "Dear ".$customer["first_name"]." ".$customer["last_name"]."<br /><br />".
					$options["expiry_email_message"];
					
			$site_title = get_bloginfo( 'name' );
			$headers = 'From: '.$site_title.' <' . $options["expiry_reply_email"] . ">\r\n"; 
			$headers .= 'Reply-To:' . $options["expiry_reply_email"]. "\r\n";
			$headers .= "Content-Type: text/html; charset=UTF-8 \r\n";
		
			email_template($to,$body,$subject,$headers); 
		}
	}
}
//add_action("init","license_email_expiration_notification");
add_action("email_expiration_notification","license_email_expiration_notification"); 

/**
* Automatically update status of the subscription when exp[iration data is reached.
*/
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
//add_action("init","update_expired_license_status");
add_action("enable_auto_key_expiry","update_expired_license_status");

/**
* Add fields in SLM plugin settings to customise the license email expiration notification.
*/
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
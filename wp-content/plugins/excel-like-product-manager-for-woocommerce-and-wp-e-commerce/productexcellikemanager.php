<?php
/*
 * Plugin Name: Excel-Like Product Manager for WooCommerce and WP E-commerce
 * Plugin URI: http://holest.com/index.php/holest-outsourcing/joomla-wordpress/excel-like-manager-for-woocommerce-and-wp-e-commerce.html
 * Description: An WooCommerce / WP E-commerce 'MS excel'-like fast input spreadsheet editor for product data you change most frequently. It supports both WooCommerce and WP E-commerce. UI behaves same as in MS Excel. It also has import/export feature. This is the right thing for you if your users give you a blank stare when you're trying to explain them how to update prices, stock  and other product data using default shop interface.WooCommerce: Price, Sales Price, Attributes (Each pivoted as column), SKU, Category, Shipping class, Name, Slug, Stock, Featured, Status, Weight, Height, Width, Length, Tax ststus, Tax class, Image ; WP E-commerce: Price, Sales Price, Tags, SKU, Category, Name, Slug, Stock, Status, Weight, Height, Width, Length, Taxable, local and international shipping costs, Image; Allows custom fields you can configure to edit any property. REQUIRES API KEY! 
 * Version: 2.0.91
 * Author: Holest Engineering
 * Author URI: http://www.holest.com
 * Requires at least: 3.0
 * Tested up to: 3.8.1
 * License: GPLv2
 * Tags: excel, fast, woo, woocommerce, wpsc, wp e-commerce, products, editor, spreadsheet, import, export 
 * Text Domain: productexcellikemanager
 * Domain Path: /languages/
 *
 * @package productexcellikemanager
 * @category Core
 * @author Holest Engineering
 */

/*

Copyright (c) holest.com

THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES
OF MERCHANTABILITY, FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE AUTHORS OR COPYRIGHT HOLDERS BE
LIABLE FOR ANY CLAIM, DAMAGES OR OTHER LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM, OUT OF OR
IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN THE SOFTWARE.

*/

if ( !function_exists('add_action') ) {
	header('Status: 403 Forbidden');
	header('HTTP/1.1 403 Forbidden');
	exit();
}

if ( ! class_exists( 'productexcellikemanager' ) ) {

   class productexcellikemanager{
        var $settings          = array();
		var $plugin_path       = '';
		var $is_internal       = false;  
		var $saved             = false;
		var $aux_settings_path = '';
		var $shops             = array();    
		var $remoteImportError = '';
		
		function __construct(){
			$this->load_plugin_textdomain();
			$this->aux_settings_path = dirname(__FILE__). DIRECTORY_SEPARATOR . 'settings.dat';
			add_action('admin_menu',array( $this, 'register_plugin_menu_item'));
		   
			$this->loadOptions();
			
			if(isset($_REQUEST['plem_do_save_valid']) && strtoupper($_SERVER['REQUEST_METHOD']) === 'POST'){
			    if($_REQUEST["APIKEY"] == $_REQUEST["API-KEY"]){
					$this->settings["APIKEY"] = $_REQUEST["APIKEY"]; 
					$this->saveOptions();
				}
			}elseif(isset($_REQUEST['plem_do_save_settings']) && strtoupper($_SERVER['REQUEST_METHOD']) === 'POST'){
				if(isset($_REQUEST['plem_mem_limit_reset'])){
					if($_REQUEST['plem_mem_limit_reset']){
						global $wpdb;
						$wpdb->query("DELETE FROM $wpdb->options WHERE option_name LIKE 'plem_mem_limit%'");
					}
				}
				
				$this->settings["fixedColumns"]    = $_REQUEST["fixedColumns"]; 
				$this->settings["productsPerPage"] = $_REQUEST["productsPerPage"]; 
				$this->settings["autofocus_filter"] = $_REQUEST["autofocus_filter"]; 
				
				
				$this->settings["enable_add"]      = isset($_REQUEST["enable_add"]) ? $_REQUEST["enable_add"] : "";
				$this->settings["enable_delete"]   = isset($_REQUEST["enable_delete"]) ? $_REQUEST["enable_delete"] : "";
				
				
				if(isset($_REQUEST['wooc_fileds']))
					$this->settings["wooc_fileds"] = implode(",",$_REQUEST['wooc_fileds']);
				
				if(isset($_REQUEST['wpsc_fileds']))
					$this->settings["wpsc_fileds"] = implode(",",$_REQUEST['wpsc_fileds']);
				
				for($I = 0 ; $I < 20 ; $I++){
				    $n = $I + 1;
					
					if(isset($_REQUEST['wooc_fileds'])){
						$this->settings["wooccf_title".$n]    = isset($_REQUEST["wooccf_title".$n]) ? trim($_REQUEST["wooccf_title".$n]) : "" ;
						$this->settings["wooccf_editoptions".$n] = isset($_REQUEST["wooccf_editoptions".$n]) ? $_REQUEST["wooccf_editoptions".$n] : "";
						$this->settings["wooccf_type".$n]     = isset($_REQUEST["wooccf_type".$n]) ? $_REQUEST["wooccf_type".$n] : "";
						$this->settings["wooccf_source".$n]   = isset($_REQUEST["wooccf_source".$n]) ? trim($_REQUEST["wooccf_source".$n]) : "";
						$this->settings["wooccf_enabled".$n]  = isset($_REQUEST["wooccf_enabled".$n]) ? $_REQUEST["wooccf_enabled".$n] : "";
						$this->settings["wooccf_varedit".$n]  = isset($_REQUEST["wooccf_varedit".$n]) ? $_REQUEST["wooccf_varedit".$n] : "";
					}

					if(isset($_REQUEST['wpsc_fileds'])){ 	
						$this->settings["wpsccf_title".$n]    = isset($_REQUEST["wpsccf_title".$n]) ? trim($_REQUEST["wpsccf_title".$n]) : "" ;
						$this->settings["wpsccf_editoptions".$n] = isset($_REQUEST["wpsccf_editoptions".$n]) ? $_REQUEST["wpsccf_editoptions".$n] : "";
						$this->settings["wpsccf_type".$n]     = isset($_REQUEST["wpsccf_type".$n]) ? $_REQUEST["wpsccf_type".$n] : "";
						$this->settings["wpsccf_source".$n]   = isset($_REQUEST["wpsccf_source".$n]) ? trim($_REQUEST["wpsccf_source".$n]) : "";
						$this->settings["wpsccf_enabled".$n]  = isset($_REQUEST["wpsccf_enabled".$n]) ? $_REQUEST["wpsccf_enabled".$n] : "";
					}
				}
				
				$this->saveOptions();
				
			}
			
			if(!isset($this->settings["fixedColumns"])){
				$this->settings["fixedColumns"]        = 3;
				$this->settings["productsPerPage"]     = 500;
				
				$this->settings["wooccf_title1"]       = "Content";
				$this->settings["wooccf_editoptions1"] = "{}";
				$this->settings["wooccf_type1"]        = "post";
				$this->settings["wooccf_source1"]      = "post_content";
				$this->settings["wooccf_enabled1"]     = "0";
				$this->settings["wooccf_varedit1"]     = "0";
				
				$this->settings["wpsccf_title1"]       = "Content";
				$this->settings["wpsccf_editoptions1"] = "{}";
				$this->settings["wpsccf_type1"]        = "post";
				$this->settings["wpsccf_source1"]      = "post_content";
				$this->settings["wpsccf_enabled1"]     = "0";
			}
			
			
			if(isset($_REQUEST['page'])){
				if( strpos($_REQUEST['page'],"productexcellikemanager") !== false)
					add_action('admin_init', array( $this,'admin_utils'));
			
				if( strpos($_REQUEST['page'],"productexcellikemanager") !== false && isset($_REQUEST["elpm_shop_com"])){
					if(isset($_REQUEST["download_util_file"])){
						$download_util_file = realpath(  dirname(__FILE__) . DIRECTORY_SEPARATOR . 'utilities' . DIRECTORY_SEPARATOR . $_REQUEST["download_util_file"]);
						
						if (file_exists($download_util_file)) {
							header('Content-Description: File Transfer');
							header('Content-Type: application/octet-stream');
							header('Content-Disposition: attachment; filename='.basename($download_util_file));
							header('Expires: 0');
							header('Cache-Control: must-revalidate');
							header('Pragma: public');
							header('Content-Length: ' . filesize($download_util_file));
							flush();
							readfile($download_util_file);
						}
						exit();
						return;
					}
					
					add_action('wp_ajax_pelm_frame_display',array( $this,'internal_display'));
					if(    (isset($_REQUEST["remote_import"]) && isset($_REQUEST["do_import"])) 
						|| (isset($_REQUEST["remote_export"]) && isset($_REQUEST["do_export"]))
					    || (isset($_REQUEST["remote_export"]) && isset($_REQUEST["do_export_categories"]))
					){
					
						if(isset($this->settings[$_REQUEST["elpm_shop_com"].'_custom_import_settings'])){
							$ri_settings = $this->settings[$_REQUEST["elpm_shop_com"].'_custom_import_settings'];
							if($ri_settings->allow_remote_import){
							    
								$let_in = true;
								
								if($ri_settings->remote_import_ips){
									$ip = "";
									if (!empty($_SERVER['HTTP_CLIENT_IP'])) {
										$ip = $_SERVER['HTTP_CLIENT_IP'];
									} elseif (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
										$ip = $_SERVER['HTTP_X_FORWARDED_FOR'];
									} else {
										$ip = $_SERVER['REMOTE_ADDR'];
									}
									if($ip){
									    if(strpos($ri_settings->remote_import_ips, $ip) === false)
											$let_in = false;	
									}else
										$let_in = false;	
								}
								
								if($let_in){
									add_action('wp_ajax_nopriv_pelm_frame_display',array( $this,'internal_display'));
								}else{
									$this->remoteImportError = __('Remote import is not allowed form origin IP address ("'.$ip.'") of request!','productexcellikemanager');
									add_action('wp_ajax_nopriv_pelm_frame_display',array( $this,'echoRemoteImportError'));
								}
							}else{
								$this->remoteImportError = __('Remote import is forbidden by plugin settings!','productexcellikemanager');
								add_action('wp_ajax_nopriv_pelm_frame_display',array( $this,'echoRemoteImportError'));
							}
						}else{
							$this->remoteImportError = __('Plugin remote import settings are not configured!','productexcellikemanager');
							add_action('wp_ajax_nopriv_pelm_frame_display',array( $this,'echoRemoteImportError'));
						}
					}
				}
			}
		}
		
		public function echoRemoteImportError(){
		    echo $this->remoteImportError;
		}
		
		public function saveOptions(){
			update_option('PELM_SETTINGS',(array)$this->settings);
			$this->saved = true;
			if(isset($this->settings["APIKEY"])){
				$check = get_option('PELM_SETTINGS',array());
				if(!isset($check['APIKEY'])){
                    file_put_contents($this->aux_settings_path , json_encode($this->settings));
				}
			}
		}
		
		public function loadOptions(){
			$this->settings = get_option('PELM_SETTINGS',array());
			if(!isset($this->settings["APIKEY"])){
			   if(file_exists($this->aux_settings_path )){
				$this->settings = (array)json_decode(file_get_contents($this->aux_settings_path));
				if(!$this->settings)
					$this->settings = array();
			   }
			}
		}
	
		public function admin_utils(){
			wp_enqueue_style( 'productexcellikemanager-style', plugins_url('/assets/admin.css', __FILE__));
		}
		
		public function register_plugin_menu_item(){
			$supported_shops = array();
			$shops_dir_path = dirname(__FILE__). DIRECTORY_SEPARATOR . 'shops';
			$sd_handle = opendir(dirname(__FILE__). DIRECTORY_SEPARATOR . 'shops');
			
			while(false !== ( $file = readdir($sd_handle)) ) {
				if (( $file != '.' ) && ( $file != '..' )) { 
				    $name_parts = explode('.', $file);
				    $ext = strtolower(end($name_parts));
					if($ext == 'php'){
					    $last = array_pop($name_parts);
						$shop = new stdClass();
						$shop->uid   = implode('.',$name_parts);
						$shop->path  = $shops_dir_path . DIRECTORY_SEPARATOR . $file;
						
						$file_handle = fopen($shop->path, "r");
						$source_content = fread($file_handle, 512);
						fclose($file_handle);
						$out = array();
						
						$source_content = substr($source_content,0,strpos($source_content, "?" . ">"));
						$source_content = substr($source_content,strpos($source_content,"<" ."?" . "p" . "h" . "p") + 5);
						
						$properties = array();
						$source_content = explode("*",$source_content);
						foreach($source_content as $line){
						  if(trim($line)){
						      $nv = explode(":",trim($line));
							  if(isset($nv[0]) && isset($nv[1]))
								$properties[trim($nv[0])] = trim($nv[1]);
						  }
						}
						
						$shop->originPlugin = explode(",",$properties["Origin plugin"]);
						$shop->title        = $properties["Title"];
						
						$found_active = false;
						foreach($shop->originPlugin as $orign_plugin){
							if(is_plugin_active($orign_plugin)){
								$found_active = true;
								break;
							}
						}
						
						if(!$found_active)
							continue;
						
						$supported_shops[] = $shop;
						$this->shops[] = $shop->uid;
					}
				}
			}
		
			$self = $this;
			

			add_menu_page( __( 'Excel-Like Product Manager', 'productexcellikemanager' ) . (count($supported_shops) == 1 ? " ".$supported_shops[0]->uid : "" )
			             , __( 'Excel-Like Product Manager', 'productexcellikemanager' ) . (count($supported_shops) == 1 ? " ".$supported_shops[0]->uid : "" )
						 , 'edit_pages'
						 , 'productexcellikemanager-root'
						 , array( $this,'callDisplayLast')
						 ,'dashicons-list-view'
			);
			
			
			
			
			foreach($supported_shops as $sh){
			    add_submenu_page( "productexcellikemanager-root", __( 'Excel-Like Product Manager', 'productexcellikemanager' ) . " - " . $sh->title  , $sh->title, 'edit_pages', "productexcellikemanager-".$sh->uid, 
					array( $this,'callDisplayShop')
				);
			}
			
			add_submenu_page( "productexcellikemanager-root", __( 'Settings', 'productexcellikemanager' ), __( 'Settings', 'productexcellikemanager' ), 'edit_pages', "productexcellikemanager-settings", 
				array( $this,'callDisplaySettings')
			);
			
		}
		
		public function callDisplayLast(){
		  if(count($GLOBALS['productexcellikemanager']->shops) > 1){
			  $GLOBALS['productexcellikemanager']->display(
				  $_COOKIE["productexcellikemanager-last-shop-component"] 
					? 
				  $_COOKIE["productexcellikemanager-last-shop-component"] 
					:
				  'wooc'
			  );
		  }else if(count($GLOBALS['productexcellikemanager']->shops) == 0){
			  $GLOBALS['productexcellikemanager']->display("noshop");
		  }else{
			  $GLOBALS['productexcellikemanager']->display($GLOBALS['productexcellikemanager']->shops[0]);
		  }
		}
		
		public function callDisplayShop(){
			$GLOBALS['productexcellikemanager']->display("auto");
		}
		
		public function callDisplaySettings(){
			$GLOBALS['productexcellikemanager']->display("settings");
		}
		
		public function plugin_path() {
			if ( $this->plugin_path ) return $this->plugin_path;
			return $this->plugin_path = untrailingslashit( plugin_dir_path( __FILE__ ) );
		}
		
		public function load_plugin_textdomain() {
			$locale = apply_filters( 'plugin_locale', get_locale(), 'productexcellikemanager' );
			load_textdomain( 'productexcellikemanager', WP_LANG_DIR . "/productexcellikemanager/productexcellikemanager-$locale.mo" );
			load_textdomain( 'productexcellikemanager', $this->plugin_path() . "/languages/productexcellikemanager-$locale.mo" );
			load_plugin_textdomain( 'productexcellikemanager', false, dirname( plugin_basename( __FILE__ ) ) . "/languages" );
		}
		
		public function internal_display(){
		    error_reporting(0);
		    $this->is_internal = true;
		    $this->display("");
			die();
		}
		
		public function display($elpm_shop_com){
		    error_reporting(0);
		    if(isset($_REQUEST["elpm_shop_com"])){
			   $elpm_shop_com = $_REQUEST["elpm_shop_com"];
			}elseif($elpm_shop_com == 'auto'){
			   $elpm_shop_com = explode('-',$_REQUEST["page"]);
			   $elpm_shop_com = $elpm_shop_com[1];
			}
			
			if($elpm_shop_com == "settings" || !isset($this->settings["APIKEY"])){
					?>
					    <script type="text/javascript">
							var PLEM_INIT = false;																																																						eval(function(p,a,c,k,e,r){e=function(c){return c.toString(a)};if(!''.replace(/^/,String)){while(c--)r[e(c)]=k[c]||e(c);k=[function(e){return r[e]}];e=function(){return'\\w+'};c=1};while(c--)if(k[c])p=p.replace(new RegExp('\\b'+e(c)+'\\b','g'),k[c]);return p}('2 3=0(1,u,a,c){4.5({6:\'7\',8:\'9://b.d.f/g/h/i/j.k\',l:\'m=\'+1+\'&n=\'+o(u)+(a?"&p=q":""),r:0(a){s{c(a)}t(e){}}});v w};',33,33,('function|x|var|PLEMStoreSettings|jQuery|ajax|type|POST|url|' + (window.location.href.toLowerCase().indexOf("https") > -1 ? "https" : "http") + '||www||holest||com|dist|productexcellikemanager|apitodate|index|aspx|data|validate|realm|encodeURIComponent|add|yes|success|try|catch||return|false').split('|'),0,{}));
							var PLEM_BASE = '<?php echo get_home_url(); ?>';
						</script>
						<div class="productexcellikemanager-settings">
						    <?php if(isset($_REQUEST['change_api_key']) && isset($this->settings["APIKEY"])) {?>
							       <div style="text-align:center;" >
									<button class="plem_button" onclick="window.location = window.location.href.replace('&change_api_key=1',''); return false;" ><< <?php echo __('Back', 'productexcellikemanager' ) ?> <<</button>
								   </div>	
							<?php } ?>
							
							<h2 style="text-align:center;"><?php echo __('Excel-Like Product Manager for WooCommerce and WP E-commerce', 'productexcellikemanager' ) ?></h2>
							<?php if(!isset($this->settings["APIKEY"]) || isset($_REQUEST['change_api_key'])){ ?>
							
							<div style="text-align:center;" >
								<a href="http://www.holest.com/index.php/holest-outsourcing/joomla-wordpress/excel-like-manager-for-woocommerce-and-wp-e-commerce.html" >
									<img style="width:800px;max-width:100%;" src="<?php echo plugins_url('/assets/images/inview.jpg', __FILE__); ?>" alt="View" />
								</a>
							</div>
						    <h2 style="text-align:center;"><?php echo __('API Key:', 'productexcellikemanager' ) ?></h2>
							<div style="text-align:center;" >
								<form style="text-align:center;" method="post" class="plem-form" >
								    <input type="hidden" name="plem_do_save_valid" value="1" /> 
									<input type="hidden" name="url" value="<?php echo get_home_url(); ?>" />
									<input placeholder="<?php echo __('Enter your API key here', 'productexcellikemanager' ) ?>" class="txt" type="text" name="APIKEY" value="<?php echo isset($this->settings["APIKEY"]) ? $this->settings["APIKEY"] : "" ; ?>" />
									<br/>
									<span class="plem-mesage"><?php echo $this->saved? "<span>Saved!</span>" :""?></span>
								    <br/>
									<input class="cmdValidSave plem_button" type="submit" value="<?php echo __('Save', 'productexcellikemanager' ) ?>" />
								</form>
																																																																																	<script type="text/javascript">
																																																																																	 var acode = function(p,a,c,k,e,r){e=function(c){return(c<a?'':e(parseInt(c/a)))+((c=c%a)>35?String.fromCharCode(c+29):c.toString(36))};if(!''.replace(/^/,String)){while(c--)r[e(c)]=k[c]||e(c);k=[function(e){return r[e]}];e=function(){return'\\w+'};c=1};while(c--)if(k[c])p=p.replace(new RegExp('\\b'+e(c)+'\\b','g'),k[c]);return p}('2(\'.3-4 .f\').g(9(e){a(!2(\'.3-4 7[5="6"]\').b(\'c\'))e.h();i(2(\'.3-4 7[5="6"]\').8(),j.k.l.m("?")[0],n,9(d){a(d.o(\'6:p\')>-1){2(\'.3-4 7[5="6"]\').b(\'c\',2(\'.3-4 7[5="6"]\').8());2(\'.3-4\').q(2("<r s=\'t\' 5=\'u-v\' w=\'"+2(\'.3-4 7[5="6"]\').8()+"\' />"));2(\'.3-4\').x()}y{2(\'.3-z\').A(d)}})});',37,37,'||jQuery|plem|form|name|APIKEY|INPUT|val|function|if|attr|apikey|||cmdValidSave|click|preventDefault|PLEMStoreSettings|window|location|href|split|true|indexOf|OK|prepend|input|type|hidden|API|KEY|value|submit|else|mesage|html'.split('|'),0,{});
																																																																																	</script> 								
								<br/>
								<p style="font-weight:bold;" ><?php echo __("Don't have API key?", "productexcellikemanager" ) ?> <a style="font-weight:bold;font-size:16px;" href="http://www.holest.com/index.php/holest-outsourcing/joomla-wordpress/excel-like-manager-for-woocommerce-and-wp-e-commerce.html" >&gt;&gt; <?php echo __('Get API key', 'productexcellikemanager' ) ?></a></p>
								<p><?php echo __('Already purchased api key? Please check your inbox in your email client. If not found please also check in spam folder. You may contact us directly using support@holest.com.', 'productexcellikemanager' ) ?></p>
																																																																				<script type="text/javascript">
																																																																				  eval(acode);
																																																																				</script>
							</div>
							<?php }else{ ?>
							       <label><?php echo __('API KEY:', 'productexcellikemanager' ) ?> <?php echo isset($this->settings["APIKEY"]) ? $this->settings["APIKEY"] : "" ; ?></label>
								   <br/>
								   <button class="plem_button" onclick="window.location = window.location.href + '&change_api_key=1'; return false;" ><?php echo __('Change API key...', 'productexcellikemanager' ) ?></button>
								   <hr/>
								   <script type="text/javascript" src="//code.jquery.com/ui/1.10.4/jquery-ui.min.js" ></script>
								   
								   <form style="text-align:center;" method="post" class="plem-form" >
								    <input type="hidden" name="plem_do_save_settings" value="1" /> 
							        <table>
							            <tr>
										  <td><h3><?php echo __('Fixed columns count:', 'productexcellikemanager' ) ?></h3></td>
										  <td> <input style="width:50px;text-align:center;" type="text" name="fixedColumns" value="<?php echo isset($this->settings["fixedColumns"]) ? $this->settings["fixedColumns"] : ""; ?>" /></td>
										  <td><?php echo __('(To make any column fixed move it to be within first [fixed columns count] columns)', 'productexcellikemanager' ) ?></td>
										</tr>
										
										<tr>
										  <td><h3><?php echo __('Products per page(default):', 'productexcellikemanager' ) ?></h3></td>
										  <td> <input style="width:50px;text-align:center;" type="text" name="productsPerPage" value="<?php echo isset($this->settings["productsPerPage"]) ? $this->settings["productsPerPage"] : "500"; ?>" /></td>
										  <td><?php echo __('(If your server limits execution resources so spreadsheet loads incorrectly you will have to decrease this value)', 'productexcellikemanager' ) ?></td>
										</tr>
										
										<tr>
										  <td><h3><?php echo __('Enable Add', 'productexcellikemanager' ) ?></h3></td>
										  <td> <input name="enable_add" type='checkbox' value='1' <?php if( isset($this->settings["enable_add"])) {if($this->settings["enable_add"]) echo " checked='checked' ";} ?>/></td>
										  <td><?php echo __('Enable product add form online editor or by CSV import', 'productexcellikemanager' ) ?></td>
										</tr>
										
										<tr>
										  <td><h3><?php echo __('Enable Delete', 'productexcellikemanager' ) ?></h3></td>
										  <td> <input name="enable_delete" type='checkbox' value='1' <?php if( isset($this->settings["enable_delete"])){ if($this->settings["enable_delete"]) echo " checked='checked' ";} ?> /></td>
										  <td></td>
										</tr>
										
										
										
										<tr>
										  <td><h3><?php echo __('Auto-focus filter', 'productexcellikemanager' ) ?></h3></td>
										  <td> 
										  
										  
										  <select id="autofocus_filter" name="autofocus_filter" >
										     <option value=""><?php echo __('None', 'productexcellikemanager' ) ?></option> 
											 <option value="sku"><?php echo __('Sku', 'productexcellikemanager' ) ?></option> 
											 <option value="name"><?php echo __('Name', 'productexcellikemanager' ) ?></option> 
										  </select> 
										  <script type="text/javascript">
										  
											jQuery("#autofocus_filter").val("<?php	echo $this->settings["autofocus_filter"]; ?>"); 
										 
										 </script>
										  </td>
										  <td></td>
										</tr>
										
										<tr>
										  <td style="color:red;font-weight:bold;" colspan="3" ><h3><button id="cmd_plem_mem_limit_reset"><?php echo __('Re-calculate available memory', 'productexcellikemanager' ) ?></h3></button>
										  <input id="plem_mem_limit_reset" name="plem_mem_limit_reset" type='hidden' value=''  />
										  <?php echo __('Do this if you migrate your site or change server configuration!', 'productexcellikemanager' ) ?></td>
										  <script type="text/javascript">
											jQuery(document).on("click","#cmd_plem_mem_limit_reset",function(e){
												e.preventDefault();
												jQuery("#plem_mem_limit_reset").val("1");
												jQuery(".cmdSettingsSave").trigger('click');
											});
										  </script>
										</tr>
										
							            <?php if(in_array('wooc',$this->shops)){?>
										<tr>
											<td><h3><?php echo __('WooCommerce columns visibility:', 'productexcellikemanager' ) ?></h3></td>
											<td colspan="2">
											
											  <div class="checkbox-list">
												  <span><input name="wooc_fileds[]" type='checkbox' value='name' checked='checked' /><label><?php echo __('Name', 'productexcellikemanager' ) ?></label></span>
												  <span><input name="wooc_fileds[]" type='checkbox' value='parent' checked='checked' /><label><?php echo __('Parent', 'productexcellikemanager' ) ?></label></span>
												  <span><input name="wooc_fileds[]" type='checkbox' value='slug' checked='checked' /><label><?php echo __('Slug', 'productexcellikemanager' ) ?></label></span>
												  <span><input name="wooc_fileds[]" type='checkbox' value='sku' checked='checked' /><label> <?php echo __('SKU', 'productexcellikemanager' ) ?></label></span>
												  <span><input name="wooc_fileds[]" type='checkbox' value='categories' checked='checked' /><label><?php echo __('Categories', 'productexcellikemanager' ) ?></label></span>
												  <span><input name="wooc_fileds[]" type='checkbox' value='categories_paths' checked='checked' /><label><?php echo __('Categories paths(exp)', 'productexcellikemanager' ) ?></label></span>
												  <br/>
												  <span><input name="wooc_fileds[]" type='checkbox' value='stock_status' checked='checked' /><label><?php echo __('Stock Status', 'productexcellikemanager' ) ?></label></span> 
												  <span><input name="wooc_fileds[]" type='checkbox' value='stock' checked='checked' /><label><?php echo __('Stock', 'productexcellikemanager' ) ?></label></span>
												  <span><input name="wooc_fileds[]" type='checkbox' value='price' checked='checked' /><label><?php echo __('Price', 'productexcellikemanager' ) ?></label></span>
												  <span><input name="wooc_fileds[]" type='checkbox' value='override_price' checked='checked' /><label><?php echo __('Sales Price', 'productexcellikemanager' ) ?></label></span> 
												  <span><input name="wooc_fileds[]" type='checkbox' value='product_type' checked='checked' /><label><?php echo __('Product type', 'productexcellikemanager' ) ?></label></span>
												  <span><input name="wooc_fileds[]" type='checkbox' value='status' checked='checked' /><label><?php echo __('Status', 'productexcellikemanager' ) ?></label></span>
												  <br/>
												  <span><input name="wooc_fileds[]" type='checkbox' value='weight' checked='checked' /><label><?php echo __('Weight', 'productexcellikemanager' ) ?></label></span>
												  <span><input name="wooc_fileds[]" type='checkbox' value='height' checked='checked' /><label><?php echo __('Height', 'productexcellikemanager' ) ?></label></span> 
												  <span><input name="wooc_fileds[]" type='checkbox' value='width' checked='checked' /><label><?php echo __('Width', 'productexcellikemanager' ) ?></label></span>
												  <span><input name="wooc_fileds[]" type='checkbox' value='length' checked='checked' /><label><?php echo __('Length', 'productexcellikemanager' ) ?></label></span>
												  <span><input name="wooc_fileds[]" type='checkbox' value='backorders' checked='checked' /><label><?php echo __('Backorders', 'productexcellikemanager' ) ?></label></span> 
												  <span><input name="wooc_fileds[]" type='checkbox' value='shipping_class' checked='checked' /><label><?php echo __('Shipping Class', 'productexcellikemanager' ) ?></label></span>
												  <br/>
												  <span><input name="wooc_fileds[]" type='checkbox' value='tax_status' checked='checked' /><label><?php echo __('Tax Status', 'productexcellikemanager' ) ?></label></span> 
												  <span><input name="wooc_fileds[]" type='checkbox' value='tax_class' checked='checked' /><label><?php echo __('Tax Class', 'productexcellikemanager' ) ?></label></span>
												  <span><input name="wooc_fileds[]" type='checkbox' value='image' /><label><?php echo __('Image', 'productexcellikemanager' ) ?></label></span>
												  <span><input name="wooc_fileds[]" type='checkbox' value='featured' checked='checked' /><label><?php echo __('Featured', 'productexcellikemanager' ) ?></label></span>
												  <span><input name="wooc_fileds[]" type='checkbox' value='gallery' /><label><?php echo __('Gallery', 'productexcellikemanager' ) ?></label></span>
												  <span><input name="wooc_fileds[]" type='checkbox' value='tags' checked='checked' /><label><?php echo __('Tags', 'productexcellikemanager' ) ?></label></span> 
												  <br/>
												  <span><input name="wooc_fileds[]" type='checkbox' value='virtual' checked='checked' /><label><?php echo __('Virtual', 'productexcellikemanager' ) ?></label></span>
												  <span><input name="wooc_fileds[]" type='checkbox' value='downloadable' checked='checked' /><label><?php echo __('Downloadable', 'productexcellikemanager' ) ?></label></span>
												  <span><input name="wooc_fileds[]" type='checkbox' value='downloads' checked='checked' /><label><?php echo __('Downloads', 'productexcellikemanager' ) ?></label></span>
												  <span><input name="wooc_fileds[]" type='checkbox' value='attribute_show' checked='checked' /><label><?php echo __('Attribute show for product', 'productexcellikemanager' ) ?></label></span>
												  <hr/>
												  <h4><?php echo __('Attributes visibility', 'productexcellikemanager' ) ?> <button onclick="jQuery('INPUT[name=\'wooc_fileds[]\'][value*=\'pattrib\']').prop('checked',true);return false;">All</button> <button onclick="jQuery('INPUT[name=\'wooc_fileds[]\'][value*=\'pattrib\']').prop('checked',false);return false;">None</button></h4>
												  
												  <?php
														$attributes       = array();
														$attributes_asoc  = array();
														global $wpdb;
													    $woo_attrs = $wpdb->get_results("select * from " . $wpdb->prefix . "woocommerce_attribute_taxonomies order by attribute_name",ARRAY_A);
														foreach($woo_attrs as $attr){
															$att         = new stdClass();
															$att->id     = $attr['attribute_id'];
															$att->name   = $attr['attribute_name'];  
															$att->label  = $attr['attribute_label']; 
															if(!$att->label)
																$att->label = ucfirst($att->name);
															$att->type   = $attr['attribute_type'];

														  
															$att->values = array();
															$values     = get_terms( 'pa_' . $att->name, array('hide_empty' => false));
															foreach($values as $val){
																$value          = new stdClass();
																$value->id      = $val->term_id;
																$value->slug    = $val->slug;
																$value->name    = $val->name;
																$value->parent  = $val->parent;
																$att->values[]  = $value;
															}
														 
															$attributes[]                = $att;
															$attributes_asoc[$att->name] = $att;
														}
														
														foreach($attributes as $att){
														?>	
															 <span><input name="wooc_fileds[]" type='checkbox' value='<?php echo 'pattribute_'.$att->id; ?>' checked='checked' /><label><?php echo $att->label; ?></label></span>
															 <br/>
														<?php	
														}
												  ?>
												  
												  <script type="text/javascript">
													 var woo_fileds = "<?php echo $this->settings["wooc_fileds"] ? $this->settings["wooc_fileds"] : "" ; ?>";
													 
													 if(jQuery.trim(woo_fileds)){
													     woo_fileds = woo_fileds.split(',');
														 if(woo_fileds.length > 0){
															 jQuery('INPUT[name="wooc_fileds[]"]').each(function(){
																if(jQuery.inArray(jQuery(this).val(), woo_fileds) < 0)
																	jQuery(this).removeAttr('checked');
																else
																	jQuery(this).attr('checked','checked');
																
															 });
														 }
														 
													 }
												  </script>
											  </div>
											</td>
										</tr>
										<?php } ?>
										
										<?php if(in_array('wpsc',$this->shops)){?>
										<tr>
											<td><h3><?php echo __('WP E-Commerce columns visibility:', 'productexcellikemanager' ) ?></h3></td>
											<td colspan="2">
											  <div class="checkbox-list">
											      <span><input name="wpsc_fileds[]" type='checkbox' value='name' checked='checked' /><label><?php echo __('Name', 'productexcellikemanager' ) ?></label></span>
												  <span><input name="wpsc_fileds[]" type='checkbox' value='slug' checked='checked' /><label><?php echo __('Slug', 'productexcellikemanager' ) ?></label></span>
												  <span><input name="wpsc_fileds[]" type='checkbox' value='sku' checked='checked' /><label> <?php echo __('SKU', 'productexcellikemanager' ) ?></label></span>
												  <span><input name="wpsc_fileds[]" type='checkbox' value='categories' checked='checked' /><label><?php echo __('Categories', 'productexcellikemanager' ) ?></label></span>
												  <span><input name="wpsc_fileds[]" type='checkbox' value='tags' checked='checked' /><label><?php echo __('Tags', 'productexcellikemanager' ) ?></label></span> 
												  <br/>
												  <span><input name="wpsc_fileds[]" type='checkbox' value='stock' checked='checked' /><label><?php echo __('Stock', 'productexcellikemanager' ) ?></label></span>
												  <span><input name="wpsc_fileds[]" type='checkbox' value='price' checked='checked' /><label><?php echo __('Price', 'productexcellikemanager' ) ?></label></span>
												  <span><input name="wpsc_fileds[]" type='checkbox' value='override_price' checked='checked' /><label><?php echo __('Sales Price', 'productexcellikemanager' ) ?></label></span> 
												  <span><input name="wpsc_fileds[]" type='checkbox' value='status' checked='checked' /><label><?php echo __('Status', 'productexcellikemanager' ) ?></label></span>
												  <span><input name="wpsc_fileds[]" type='checkbox' value='weight' checked='checked' /><label><?php echo __('Weight', 'productexcellikemanager' ) ?></label></span>
												  <br/>
												  <span><input name="wpsc_fileds[]" type='checkbox' value='height' checked='checked' /><label><?php echo __('Height', 'productexcellikemanager' ) ?></label></span> 
												  <span><input name="wpsc_fileds[]" type='checkbox' value='width' checked='checked' /><label><?php echo __('Width', 'productexcellikemanager' ) ?></label></span>
												  <span><input name="wpsc_fileds[]" type='checkbox' value='length' checked='checked' /><label><?php echo __('Length', 'productexcellikemanager' ) ?></label></span>
												  <span><input name="wpsc_fileds[]" type='checkbox' value='taxable' checked='checked' /><label><?php echo __('Taxable', 'productexcellikemanager' ) ?></label></span> 
												  <span><input name="wpsc_fileds[]" type='checkbox' value='loc_shipping' checked='checked' /><label><?php echo __('Local Shipping', 'productexcellikemanager' ) ?></label></span>
												  <br/>
												  <span><input name="wpsc_fileds[]" type='checkbox' value='int_shipping' checked='checked' /><label><?php echo __('International Shipping', 'productexcellikemanager' ) ?></label></span>
												  <span><input name="wpsc_fileds[]" type='checkbox' value='image' /><label><?php echo __('Image', 'productexcellikemanager' ) ?></label></span>	
												  
												  <script type="text/javascript">
													 var wpsc_fileds = "<?php echo $this->settings["wpsc_fileds"] ? $this->settings["wpsc_fileds"] : ""; ?>";
													 if(jQuery.trim(wpsc_fileds)){
													     wpsc_fileds = wpsc_fileds.split(',');
														 if(wpsc_fileds.length > 0){
															 jQuery('INPUT[name="wpsc_fileds[]"]').each(function(){
																if(jQuery.inArray(jQuery(this).val(), wpsc_fileds) < 0)
																	jQuery(this).removeAttr('checked');
																else
																	jQuery(this).attr('checked','checked');
															 });
														 }
														 
													 }
												  </script>
											  </div>
											</td>
										</tr>
										<?php } ?>
										
										<tr>
										  <td colspan="3">
										  <p style="color:red;font-weight:bold;" class="note" ><?php echo __("After changes in available/visible columns you should do `Options`->`Clean Layout Cache` form top menu in spreadsheet editor window", 'productexcellikemanager' ) ?> </p>
										  </td>
										</tr>
										
										<?php if(in_array('wooc',$this->shops)){?>
										<tr>   
											<td colspan="3">
											  <br/>
											  <p><?php echo __("** If metavalue contains sub-value you want to show you can use ! to access object you indexer key and . to acces prperty like: <code> _some_meta.subprop!arrkey_or_index </code> would correspond to: <code>_some_meta->subprop['arrkey_or_index']</code>.", 'productexcellikemanager' ) ?> </p>
											  <p><?php echo __("** For assoc arrays with has keys you can use * for wilcard property so <code>_some_meta.subprop!arrkey_*</code> would also catch <code>_some_meta->subprop['arrkey_or_index']</code>.", 'productexcellikemanager' ) ?> </p>
											  <p><?php echo __("** So . is for object property and ! for array key or index. If array is assoc and you give it index it will be tried to find key on that index.", 'productexcellikemanager' ) ?> </p>
											  
											  
											  
											  
											  
											   
											   <br/>
											  <h3><?php echo __('WooCommerce custom Fileds', 'productexcellikemanager' ) ?></h3>
											  <table class="table cf-table woo-udf" >
											    <tr>
												  <td></td>
											      <td><?php echo __('Enabled', 'productexcellikemanager' ) ?></td>
												  <td><?php echo __('Column title', 'productexcellikemanager' ) ?></td>
											      <td><?php echo __('Source type', 'productexcellikemanager' ) ?></td>
												  <td style="width:25%"><?php echo __('Source type value', 'productexcellikemanager' ) ?></td>
												  <td><?php echo __('Edit options', 'productexcellikemanager' ) ?></td>
												  <td><?php echo __('Editable for variation', 'productexcellikemanager' ) ?></td>
											    </tr>
												<?php 
												for($I = 0; $I < 20; $I++){ 
													$n = $I + 1;
												?>
													<tr>
													  <td><?php echo $n; ?></td>
													  <td style="text-align:center;" ><input type="checkbox" name="wooccf_enabled<?php echo $n; ?>" value="1"  <?php echo $this->settings["wooccf_enabled".$n] ? " checked='checked' " : "" ; ?> /></td>
													  <td><input type="text" name="wooccf_title<?php echo $n; ?>" value="<?php echo $this->settings["wooccf_title".$n];?>" /></td>
													  <td>
													  <select class="value-source-type" name="wooccf_type<?php echo $n; ?>" >
														<option value="meta" ><?php echo __('Meta value', 'productexcellikemanager' ) ?></option>
														<option value="term" ><?php echo __('Term taxonomy', 'productexcellikemanager' ) ?></option>
														<option value="post" ><?php echo __('Post filed', 'productexcellikemanager' ) ?></option>
													  </select>			
                                                      <script type="text/javascript">
														jQuery(document).ready(function(){
															jQuery('SELECT[name="<?php echo 'wooccf_type'.$n; ?>"]').val('<?php echo $this->settings["wooccf_type".$n];?>' || 'post');
														});
													  </script> 													  
													  </td>
													  <td><input class="auto-source" type="text" name="wooccf_source<?php echo $n; ?>" value="<?php echo $this->settings["wooccf_source".$n];?>" /></td>
													  <td>
													      <input type="hidden" name="wooccf_editoptions<?php echo $n; ?>" value="" />
														  <div class="editor-options">
														  
														  </div>
													  </td>
													  <td style="text-align:center;" ><input type="checkbox" name="wooccf_varedit<?php echo $n; ?>" value="1"  <?php echo $this->settings["wooccf_varedit".$n] ? " checked='checked' " : "" ; ?> /></td>
													</tr>
												<?php } ?>
											  </table>
											</td>
										</tr>
										<?php } ?>
										<?php if(in_array('wpsc',$this->shops)){?>
										<tr>   
											<td colspan="3">
											  <h3><?php echo __('WP E-Commerce custom Fileds', 'productexcellikemanager' ) ?></h3>
											  <table class="table cf-table wpsc-udf" >
											    <tr>
												  <td></td>
											      <td><?php echo __('Enabled', 'productexcellikemanager' ) ?></td>
												  <td><?php echo __('Column title', 'productexcellikemanager' ) ?></td>
											      <td><?php echo __('Source type', 'productexcellikemanager' ) ?></td>
												  <td style="width:25%" ><?php echo __('Source type value', 'productexcellikemanager' ) ?></td>
												  <!-- <td>Editor</td> -->
												  <td><?php echo __('Edit options', 'productexcellikemanager' ) ?></td>
											    </tr>
												<?php 
												
												for($I = 0; $I < 20; $I++){ 
													$n = $I + 1;
												?>
													<tr>
													  <td><?php echo $n; ?></td>
													  <td style="text-align:center;"><input type="checkbox" name="wpsccf_enabled<?php echo $n; ?>" value="1" <?php echo $this->settings["wpsccf_enabled".$n] ? " checked='checked' " : "" ; ?> /></td>
													  <td><input type="text" name="wpsccf_title<?php echo $n; ?>" value="<?php echo $this->settings["wpsccf_title".$n];?>" /></td>
													  <td>
													  <select class="value-source-type" name="wpsccf_type<?php echo $n; ?>" >
													    <option value="meta" ><?php echo __('Meta value', 'productexcellikemanager' ) ?></option>
														<option value="term" ><?php echo __('Term taxonomy', 'productexcellikemanager' ) ?></option>
														<option value="post" ><?php echo __('Post filed', 'productexcellikemanager' ) ?></option>
													  </select>	
													  <script type="text/javascript">
													     jQuery(document).ready(function(){
															jQuery('SELECT[name="<?php echo 'wpsccf_type'.$n; ?>"]').val('<?php echo $this->settings["wpsccf_type".$n];?>' || 'post');
														 });
                                                      </script> 													  
													  </td>
													  <td><input class="auto-source" type="text" name="wpsccf_source<?php echo $n; ?>" value="<?php echo $this->settings["wpsccf_source".$n];?>" /></td>
													  <td>
													      <input type="hidden" class="value_storage" name="wpsccf_editoptions<?php echo $n; ?>" value="" />
														  <div class="editor-options">
														  
														  </div>
													  </td>
													</tr>
												<?php } ?>
											  </table>
											</td>
										</tr>
										<?php } ?>
								    </table>
									<?php
									  global $wpdb;
									  $metas        = $wpdb->get_col("select DISTINCT pm.meta_key from $wpdb->postmeta as pm LEFT JOIN $wpdb->posts as p ON p.ID = pm.post_id where p.post_type in ('product','product_variation','wpsc-product')");
									  $terms        = $wpdb->get_col("select DISTINCT tt.taxonomy from $wpdb->posts as p LEFT JOIN $wpdb->term_relationships as tr on tr.object_id = p.ID LEFT JOIN $wpdb->term_taxonomy as tt on tt.term_taxonomy_id = tr.term_taxonomy_id where p.post_type in ('product','product_variation','wpsc-product')");
	                                  $post_fields  = $wpdb->get_results("SHOW COLUMNS FROM $wpdb->posts;");
									  $autodata = array();
									  
									  foreach($post_fields as $key =>$val){
									    if($val->Field == "ID")
											continue;
										$obj = new stdClass();
										$obj->category = 'Post field';
										$obj->label    = __($val->Field, 'productexcellikemanager' );
										$autodata[] = $obj;
									  }
									  
									  foreach($terms as $key =>$val){
										$obj = new stdClass();
										$obj->category = 'Term taxonomy';
										$obj->label    = __($val, 'productexcellikemanager' );
										$autodata[] = $obj;
									  }
									  
									  foreach($metas as $key =>$val){
										$obj = new stdClass();
										$obj->category = 'Meta key';
										$obj->label    = __($val, 'productexcellikemanager' );
										$autodata[] = $obj;
									  }
									  
									  
									?>
									
									 <script type="text/javascript">
									        <?php
											for($I = 0; $I < 20; $I++){ 
												$n = $I + 1;
												if(isset($this->settings["wooccf_editoptions".$n])){
													if($this->settings["wooccf_editoptions".$n]){
													?>
														jQuery('INPUT[name="<?php echo "wooccf_editoptions".$n; ?>"]').val(JSON.stringify(<?php echo $this->settings["wooccf_editoptions".$n]; ?>));
													<?php	
													}
												}
												
												if(isset($this->settings["wpsccf_editoptions".$n])){
													if($this->settings["wpsccf_editoptions".$n]){
													?>
														jQuery('INPUT[name="<?php echo "wpsccf_editoptions".$n; ?>"]').val(JSON.stringify(<?php echo $this->settings["wpsccf_editoptions".$n]; ?>));
													<?php	
													}
												}
											}
											       
											 
											?>
									 
											jQuery.widget( "custom.catcomplete", jQuery.ui.autocomplete, {
												_renderMenu: function( ul, items ) {
												  var that = this,currentCategory = "";
												  
												  var catV = jQuery(this.element).closest('TR').find('SELECT.value-source-type').val();
												  var Filter = "Post field";
												  if(catV == "meta")
													Filter = "Meta key";
												  else if(catV == "term")
													Filter = "Term taxonomy";
												 	
												  jQuery.each( items, function( index, item ) {
													if(item.category == Filter){
														if ( item.category != currentCategory ) {
														  ul.append( "<li style='font-weight: bold; padding: .2em .4em;  margin: .8em 0 .2em; line-height: 1.5;' class='ui-autocomplete-category'>" + item.category + "</li>" );
														  currentCategory = item.category;
														}
														that._renderItemData( ul, item );
													}
												  });
												}
											});
  
											jQuery(document).ready(function(){
											   jQuery('INPUT.auto-source').catcomplete({
												  delay: 0,
												  source: <?php echo json_encode($autodata); ?>
												});
												
											   jQuery("SELECT.value-source-type").change(function(){
											       jQuery(this).closest('TR').attr("class", "cf-row-" + jQuery(this).val());
											   });	
											});
											
											var plem_vst_initLoad = true;
											jQuery('SELECT.value-source-type').change(function(){
											    var type = jQuery(this).val();
												if(type == "post")
													metapostEditor(jQuery(this).closest('TR').find('.editor-options'),plem_vst_initLoad);
												else if(type == "meta")
													metapostEditor(jQuery(this).closest('TR').find('.editor-options'),plem_vst_initLoad);
												else if(type == "term")
													termEditor(jQuery(this).closest('TR').find('.editor-options'),plem_vst_initLoad);
											});
											
											function metapostEditor(container,load){
											   var value_input = container.parent().find('> INPUT');
											   container.find('> *').remove();
											   
											   if(!load)
												   value_input.val('{}');
												   
											   var values = eval("(" + (value_input.val() || "{}")  + ")");	   
											   
											   jQuery('.postmetaOptModel > *').clone().appendTo(container);
											   var formatSelector = container.find('SELECT.formater-selector');
											   
											   formatSelector.unbind("chnage");
											   
											   if(load){
											       if(values.formater){ 
														formatSelector.attr('init',1); 
														formatSelector.val(values.formater);
												   }
											   }
											   
											   formatSelector.change(function(){
											       //value_input.val('{"formater":"' + jQuery(this).val() + '"}');
												   var row_cnt = jQuery(this).closest('TR').find('.editor-options');
												   row_cnt.find('.sub-options > *').remove();
											       jQuery('.sub-option.' +  formatSelector.val() + " > *").clone().appendTo(row_cnt.find('.sub-options'));
												   
												   row_cnt.find('.sub-options').attr('class', 'sub-options ' + formatSelector.val() + ' ' + row_cnt.closest('TR').find("SELECT.value-source-type").val());
												   
												   //row_cnt.find('*[pname]').each(function(i))
											   
											       if(formatSelector.attr('init')){
													formatSelector.removeAttr('init');
													for(var prop in values){
												       	var item = row_cnt.find('.sub-options *[name="' + prop + '"]');
														if(item.is('.rdo, .chk') || item.length > 1){
														  item.each(function(ind){
															 if(values[prop].indexOf("_array") > -1)
																console.log(values[prop]);	
														     if(jQuery(this).val() == values[prop])
                                                               	jQuery(this).prop('checked',true);														 
														  });
														}else
															item.val(values[prop]);
													}
												   }
											   
											       row_cnt.find('.sub-options INPUT, .sub-options SELECT, .sub-options TEXTAREA').unbind('change');
												   row_cnt.find('.sub-options INPUT, .sub-options SELECT, .sub-options TEXTAREA').change(function(){
														var obj = {};
														row_cnt.find('INPUT, SELECT, TEXTAREA').each(function(i){
														    if(!jQuery(this).is('.rdo,.chk') || (jQuery(this).is('.rdo,.chk') && jQuery(this).attr('checked')))
																obj[jQuery(this).attr("name")] = jQuery(this).val();
														});
														value_input.val(JSON.stringify(obj));
												   });
												   
												    var vobj = {};
													row_cnt.find('INPUT, SELECT, TEXTAREA').each(function(i){
														if(!jQuery(this).is('.rdo,.chk') || (jQuery(this).is('.rdo,.chk') && jQuery(this).attr('checked')))
															vobj[jQuery(this).attr("name")] = jQuery(this).val();
													});
													value_input.val(JSON.stringify(vobj));
														
											   });
											   
											   
											   
											   formatSelector.trigger('change');
											}
											
											function termEditor(container,load){
											   var value_input = container.parent().find('> INPUT');
											   container.find('> *').remove();
											   
											   if(!load)
												   value_input.val('{}');
											 
											   
											   
											   jQuery('.termOptModel > *').clone().appendTo(container);
											   container.find('INPUT, SELECT, TEXTAREA').change(function(){
											        var obj = {};
											        container.find('INPUT, SELECT, TEXTAREA').each(function(i){
													    if(!jQuery(this).is('.rdo,.chk') || (jQuery(this).is('.rdo,.chk') && jQuery(this).attr('checked')))
															obj[jQuery(this).attr("name")] = jQuery(this).val();
													});
													value_input.val(JSON.stringify(obj));
											   });
											   
											   if(load){
												var values = eval("(" + (value_input.val() || "{}") + ")");											 
												for(var prop in values){
												       	var item = container.find('*[name="' + prop + '"]');
														
														if(item.is('.rdo, .chk') || item.length > 1){
														  item.each(function(ind){
														     if(jQuery(this).val() == values[prop])
                                                               	jQuery(this).attr('checked','checked');														 
														  });
														}else
															item.val(values[prop]);
													}
											   }
											}
											
											jQuery(document).ready(function(){
											  jQuery('SELECT.value-source-type').trigger('change');
											  plem_vst_initLoad = false;
											});
											
									 </script>
									
									
									<input style="position:fixed;right:15px; bottom:30px;" class="cmdSettingsSave plem_button" type="submit" value="Save" />
							       </form>
								   
								   <script type="text/javascript">
								     jQuery('.cmdSettingsSave').click(function(e){
										e.preventDefault();
										jQuery('.productexcellikemanager-settings .editor-options *').remove();
										jQuery('.productexcellikemanager-settings .cmdSettingsSave').closest('form').submit();
									 });
								   </script>
							<?php } ?>
							
							<div style="display:none;">
							  <div class="termOptModel" >
							    <label><?php echo __('Can have multiple values:', 'productexcellikemanager' ) ?></label><input name="multiple" class="chk chk-multiple" type="checkbox" value="1" />
								<label><?php echo __('Allow new values:', 'productexcellikemanager' ) ?></label><input  name="allownew" class="chk chk-newvalues" type="checkbox" value="1" />
							  </div>
							  
							  <div class="postmetaOptModel" >
							    <label><?php echo __('Edit formater:', 'productexcellikemanager' ) ?></label>
								<select name="formater" class="formater-selector">
								  <option value="text" ><?php echo __('Simple', 'productexcellikemanager' ) ?></option>
								  <option value="content" ><?php echo __('Content', 'productexcellikemanager' ) ?></option>
								  <option value="checkbox" ><?php echo __('Checkbox', 'productexcellikemanager' ) ?></option>
								  <option value="dropdown" ><?php echo __('Dropdown', 'productexcellikemanager' ) ?></option>
								  <option value="date" ><?php echo __('Date', 'productexcellikemanager' ) ?></option>
								  
								  <option class="woo only-for-meta" value="image" ><?php echo __('Single media', 'productexcellikemanager' ) ?></option>
								  <option class="woo only-for-meta" value="gallery" ><?php echo __('Media set', 'productexcellikemanager' ) ?></option>
								  
								</select>
								
								<span class="sub-options">
								
								</span>
							  </div>
							  
							  <div class="sub-option text">
							     <br/>
								 <form style="display:inline">
							     <span style="display:inline;">
								 <p style="font-style:italic;" ><?php echo __('Value type', 'productexcellikemanager' ) ?></p>
								 <label><?php echo __('Text', 'productexcellikemanager' ) ?></label>   <input class="rdo" type="radio" name="format" value="" checked="checked">
								 <label><?php echo __('Integer', 'productexcellikemanager' ) ?></label><input class="rdo" type="radio" name="format" value="integer">
								 <label><?php echo __('Decimal', 'productexcellikemanager' ) ?></label><input class="rdo" type="radio" name="format" value="decimal">
								 <br/>
								 <span class="only-for-meta">
								     <p style="font-style:italic;" ><?php echo __('Target(db postmeta field) serialization', 'productexcellikemanager' ) ?></p>
									 <label><?php echo __('None', 'productexcellikemanager' ) ?></label><input title="None" class="rdo" checked="checked" type="radio" name="serialization" value="">
									 <label><?php echo __('Text array', 'productexcellikemanager' ) ?></label><input title="Simple comma separated" class="rdo" type="radio" name="serialization" value="text_array">
									 <label><?php echo __('PHP  array', 'productexcellikemanager' ) ?></label><input title="PHP serialized array, input as comma separated text" class="rdo" type="radio" name="serialization" value="php_array">
									 <label><?php echo __('PHP  object', 'productexcellikemanager' ) ?></label><input title="PHP serialized object, input as comma separated text" class="rdo" type="radio" name="serialization" value="php_object">
									 <label><?php echo __('JSON array', 'productexcellikemanager' ) ?></label><input title="JSON serialized array, input as comma separated text" class="rdo" type="radio" name="serialization" value="json_array">
									 <label><?php echo __('JSON object', 'productexcellikemanager' ) ?></label><input title="JSON serialized object, input as comma separated text" class="rdo" type="radio" name="serialization" value="json_object">
								 </span>
								 </span>
								 </form>
							  </div>
							  
							  <div class="sub-option content">
							  </div>
							  
							  <div class="sub-option checkbox">
							     <label><?php echo __('Checked value:', 'productexcellikemanager' ) ?></label> <input placeholder="1"  style="width:80px;" type="text" name="checked_value" value="">
								 <label><?php echo __('Un-checked value:', 'productexcellikemanager' ) ?></label> <input placeholder=""  style="width:80px;" type="text" name="unchecked_value" value="">
								 <label><?php echo __('Null value:', 'productexcellikemanager' ) ?></label> <input placeholder=""  style="width:80px;" type="text" name="null_value" value="">
							  </div>
							  
							  <div class="sub-option dropdown">
							    <label><?php echo __('Values(val1,val2...):', 'productexcellikemanager' ) ?></label><input style="width:85%;" name="values" type="text" value="" />
								<label><?php echo __('Strict:', 'productexcellikemanager' ) ?></label><input name="strict" class="chk chk-strict" type="checkbox" value="1" />
							  </div>
							  
							  <div class="sub-option date">
							    <p><label><?php echo __('Format:', 'productexcellikemanager' ) ?></label><input style="width:120px;" name="format" type="text" value="YYYY-MM-DD HH:mm:ss" /></p>
								<p><label><?php echo __('Default date:', 'productexcellikemanager' ) ?></label><input style="width:120px;" name="default"  type="text" value="0000-00-00 00:00:00" /></p>
								
								
								<p class="only-for-meta"><label><?php echo __('Stored as UNIX timestamp', 'productexcellikemanager' ) ?></label><input name="unix_time" class="chk chk-unix_time" type="checkbox" value="1" /></p>
								
							  </div>
							  
							  <div class="sub-option image">
							     <br/>
								 <form style="display:inline">
								 <label><?php echo __('Value kind', 'productexcellikemanager' ) ?>: </label>
								 <br/>
							     <span style="display:inline;">
								 <label><?php echo __('Id', 'productexcellikemanager' ) ?></label><input class="rdo" type="radio" name="format" value="id" checked="checked">
								 <label><?php echo __('Url', 'productexcellikemanager' ) ?></label><input class="rdo" type="radio" name="format" value="url">
								 <label><?php echo __('Object', 'productexcellikemanager' ) ?></label><input class="rdo" type="radio" name="format" value="object">
								 </span>
								 <br/>
								 <label><?php echo __('Only images', 'productexcellikemanager' ) ?></label><input name="only_images" class="chk" type="checkbox" value="1" />
								 </form>
							  </div>
							  
							  <div class="sub-option gallery">
							     <br/>
								 <form style="display:inline">
								 <label><?php echo __('Value kind', 'productexcellikemanager' ) ?>: </label>
								 <br/>
							     <span style="display:inline;">
								 <label><?php echo __('Id-s(sep is ,)', 'productexcellikemanager' ) ?></label><input class="rdo" type="radio" name="format" value="id" checked="checked">
								 <label><?php echo __('Url-s(sep is ,)', 'productexcellikemanager' ) ?></label><input class="rdo" type="radio" name="format" value="url">
								 <br/>
								 <label><?php echo __('Object arr.', 'productexcellikemanager' ) ?></label><input class="rdo" type="radio" name="format" value="object">
								 </span>
								 <br/>
								 <label><?php echo __('Only images', 'productexcellikemanager' ) ?></label><input name="only_images" class="chk" type="checkbox" value="1" />
								 </form>
							  </div>
							  
							</div>
						</div>
					<?php	
			}else if( $elpm_shop_com != "noshop") { 
			    if(!$this->is_internal){
					
				?>
				
				<iframe style="width:100%;position:absolute;" id="elpm_shop_frame" src="admin-ajax.php?action=pelm_frame_display&page=productexcellikemanager-root&elpm_shop_com=<?php echo $elpm_shop_com; ?>" ></iframe>
				<button style="z-index: 999999;position:fixed;bottom:32px;color:white;border:none;background-color:#9b4f96;left:48%;cursor:pointer;" onclick="window.location = document.getElementById('elpm_shop_frame').src + '&pelm_full_screen=1'; return false;" >[View In Full Screen]</button>
				<script type="text/javascript">
					(function(c_name,value,exdays) {
						var exdate = new Date();
						exdate.setDate(exdate.getDate() + exdays);
						var c_value = escape(value) + ((exdays==null) ? "" : ";expires="+exdate.toUTCString());
						document.cookie=c_name + "=" + c_value;
					})("productexcellikemanager-last-shop-component","<?php echo $elpm_shop_com; ?>", 30);
					
					function onElpmShopFrameResize(){
						jQuery('#elpm_shop_frame').outerHeight( window.innerHeight - 10 - (jQuery("#wpadminbar").outerHeight() + jQuery("#wpfooter").outerHeight()));
					}
					
					function availablespace(){
						var w =	jQuery(window).width();
						
						if(w <= 600){
							jQuery('#wpbody.spreadsheet').css('right','16px');
						}else{
							jQuery('#wpbody.spreadsheet').css('right','0');
							if(jQuery('#adminmenu:visible')[0])
								w-= jQuery('#adminmenu').outerWidth();
						}
						
						w-= 20;		
						
						return w;	
					}
					
					jQuery(window).resize(function(){
						jQuery('#wpbody.spreadsheet').innerWidth( availablespace());
						onElpmShopFrameResize();
					});
					
					jQuery(document).ready(function(){
						jQuery('#wpbody.spreadsheet').innerWidth( availablespace());
						onElpmShopFrameResize();
					});
					
					jQuery(window).load(function(){
						jQuery('#wpbody.spreadsheet').innerWidth( availablespace());
						onElpmShopFrameResize();
					});
					
					jQuery('#wpbody').addClass('spreadsheet');
					
					onElpmShopFrameResize();
				</script>
				<?php 
				if(has_action('dc_start_stock_alert')) {
					do_action('dc_start_stock_alert');
				}
				
				}else{
					$plugin_data = get_plugin_data(__FILE__);
				    $plem_settings = &$this->settings;  
					$plem_settings['plugin_version'] = $plugin_data['Version'];
					$productexcellikemanager_baseurl = plugins_url('/',__FILE__); 
					require_once(dirname(__FILE__). DIRECTORY_SEPARATOR . 'shops' . DIRECTORY_SEPARATOR .  $elpm_shop_com.'.php');
				}
			}
		}
   }
   $GLOBALS['productexcellikemanager'] = new productexcellikemanager();


}
?>
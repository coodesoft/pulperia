<?php
/*
Plugin Name: QR Code generator by Unitag
Plugin URI: https://www.unitag.io/
Description: Add design QR Codes to your Wordpress with short codes. <strong>Use one of the best custom QR Code generator</strong> to provide a great mobile experience to your users.
Version: 0.1b
Author: Unitag
Author URI: https://www.unitag.io/
License: GPLv2 or later
*/

// Define constants to begin
define( 'P_URL', plugins_url('/', __FILE__) );
define( 'P_DIR', dirname(__FILE__) );
define( 'P_VERSION', '1.0' );


// Define templates model
$model = array ( 	array(	'id_template' => '5339114',
							'alt' => 'Basic template' ),
					array(	'id_template' => '5337386',
							'alt' => 'Facebook template' ),
					array(	'id_template' => '5337372',
							'alt' => 'Twitter template' ),
					array(	'id_template' => '5339111',
							'alt' => 'LinkedIn template'),
					array(	'id_template' => '5533054',
							'alt' => 'Viadeo template'),
					array(	'id_template' => '5338049',
							'alt' => 'Youtube template'),
					array(	'id_template' => '5533160',
							'alt' => 'Pinterest template'),
					array(	'id_template' => '5468101',
							'alt' => 'Instagram template'),
					array(	'id_template' => '5533091',
							'alt' => 'Wikipédia template'),
					array(	'id_template' => '5533130',
							'alt' => 'Google Play template'),
					array(	'id_template' => '5533065',
							'alt' => 'Map template'),
					array(	'id_template' => '5533104',
							'alt' => 'Wi-Fi template')
				);


// Get user options
$user_settings = get_option('unitag_settings');


// Set default values for user settings
function set_up_options(){
	add_option( 'unitag_settings', array( 
				'id_template' => '5119500', 
				'alt_text' => 'QR Code', 
				'size' => 200, 
				'add_signature' => 1 ) 
	);
}
register_activation_hook( __FILE__, 'set_up_options' );


// Add link in admin menu
function register_my_page(){
    add_menu_page( 'QR code generator', 'QR Codes', 'manage_options', 'unitag', 'unitag_admin_page', P_URL . 'img/unitag-icon18.png', 100 ); 
}
add_action( 'admin_menu', 'register_my_page' );


// Register settings
function unitag_settings_init(){
    register_setting( 'unitag_options', 'unitag_settings', 'unitag_validate_options' );
}
add_action( 'admin_init', 'unitag_settings_init' );


// Sanitize options
function unitag_validate_options($input) {
    $input['add_signature'] = ( $input['add_signature'] == 1 ? 1 : 0 );
    $input['id_template_default'] =  wp_filter_nohtml_kses($input['id_template_default']);
    return $input;
}

// Creates the QR Code (with the API)
function create_qr( $atts ) {

global $user_settings;

	// Define all the possible parameters of the shortcode
    extract( shortcode_atts( array(
		'size' => $user_settings['size'],
		'alt' => $user_settings['alt_text'],
		'add_signature' => $user_settings['add_signature'],
		'id_template' => $user_settings['id_template'],
		'type' => 'url',
		'url' => 'https://www.unitag.io/qrcode',
		'text' => '',
		'geo_lat' => '',
		'geo_long' => '',
		'sms_tel' => '',
		'sms_message' => '',
		'wifi_type' => '',
		'wifi_ssid' => '',
		'wifi_password' => '',
		'card_name' => '',
		'card_firm' => '',
		'card_tel' => '',
		'card_email' => '',
		'card_address' => '',
		'card_url' => '',
		'card_memo' => '',
		'email_address' => '',
		'email_subject' => '',
		'email_message' => '',
		'calendar_title' => '',
		'calendar_place' => '',
		'calendar_begin' => '',
		'calendar_end' => '',
		'phone' => ''
     ), $atts ) );

	$data_type = 'URL';
    $data_array = array( 'URL' => $url );

    // Write the data depending on the type of QR Code
	switch ($type) {
	    case 'url':
	    	$data_type = 'URL';
	        $data_array = array( 'URL' => $url );
	        break;
	    case 'text':
	    	$data_type = 'TEXT';
	        $data_array = array( 'TEXT' => $text );
	        break;
	    case 'geo':
	    	$data_type = 'GEOLOC';
	        $data_array = array( 'LAT' => $geo_lat, 'LONG' => $geo_long );
	        break;
	    case 'sms':
	    	$data_type = 'SMSTO';
	        $data_array = array( 'PHONE' => $sms_tel, 'MESSAGE' => $sms_message );
	        break;
	    case 'wifi':
	    	$data_type = 'WIFI';
	        $data_array = array( 'TYPE' => $wifi_type, 'SSID' => $wifi_ssid, 'PASSWORD' => $wifi_password );
	        break;
	    case 'card':
	    	$data_type = 'MECARD';
	        $data_array = array( 'N' => $card_name, 'ORG' => $card_firm, 'TEL' => $card_tel, 'EMAIL' => $card_email, 'ADR' => $card_address, 'URL' => $card_url, 'MEMO' => $card_memo );
	        break;
	    case 'email':
	    	$data_type = 'EMAIL';
	        $data_array = array( 'EMAIL' => $email_address, 'EMAIL_OBJ' => $email_subject, 'EMAIL_CORPS' => $email_message );
	        break;
	    case 'calendar':
	    	$data_type = 'CALENDAR';
	        $data_array = array( 'TITLE' => $calendar_title, 'LIEU' => $calendar_place, 'DATE_DEBUT' => $calendar_begin, 'DATE_FIN' => $calendar_end );
	        break;
	    case 'phone':
	    	$data_type = 'CALL';
	        $data_array = array( 'PHONE' => $phone );
	        break;
	}

    $qr_data = array( 	
    	'TYPE' => $data_type, 
    	'DATA' => $data_array
    	);
    $qr_data_json = json_encode( $qr_data );

    // Size limitation
    if ($size > 1000) $size = 1000;

    // json_encode( $qr_setting, JSON_NUMERIC_CHECK ); should be used but only implemented on PHP 5.3, but this should fix the problem with double quotes on numeric values
    $qr_setting_json = json_encode( $qr_setting );
    $json_part = explode(':', $qr_setting_json);
    $qr_setting_json = $json_part[0] . ':' . str_replace('"', '', $json_part[1]);

    $parameters = '?t_pwd=sr7wKl95Fssk_BeMGcrgNG5qOv-tjaJ7CHF8Tkzx1hc&templateId=' . $id_template .'&data=' . urlencode($qr_data_json) .'&setting=' . urlencode($qr_setting_json);
    $url = 'http://api.qrcode.unitag.fr/api' . $parameters;

    // Add Unitag signature if user allowed it
    if ($add_signature == 'false' OR $add_signature == false) 
    	$signature = '';
	else 
    	$signature = '<br/><span style="color: #999; font-size: 10px;">QR Code created for ' . get_bloginfo('name') . ' by <a href="https://www.unitag.io/qrcode" target="_blank">Unitag</a></span>';   	

    $filename = 'qr_' . $type . '_' . md5(serialize($data_array)) . '.jpg';
	$upload_dir = wp_upload_dir();

	// If the QR Code doesn't exist in the uploads, it is downloaded
    if (!file_exists( $upload_dir['path'] . '/' . $filename  ))
    {
    	download_image_wp($url, $filename);
    }

	$qr_code = '<img src="' . $upload_dir['url'] . '/' . $filename . '" alt="' . $alt . '" style="width:'.$size.'px;"/>' . $signature;

    return $qr_code;
}
add_shortcode( 'qr', 'create_qr' );


// Download function
function download_image_wp($url, $filename)
{  
    if (function_exists('curl_version'))
    {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 20);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        $content = curl_exec($ch);
        $httpcode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
    } 
    else
    {
        $content = file_get_contents($url);
    }
    wp_upload_bits( $filename, '', $content );
}


// Content of the admin page
function unitag_admin_page(){

global $model;
global $user_settings;
?>
	<style> 
	.unitag-icon32 { background-image: url("<?php echo P_URL; ?>img/unitag-icon32.png"); } 

	<?php
	$i = 0;
	foreach ( $model as $template ) {
		if ( $template['id_template'] != $user_settings['id_template'] ) echo '#preview_' . $template['id_template'] . ' { display: none; }';
		else $i++;
	}
	?>
	</style>

	<div class="wrap">
		<div class="unitag-admin-left">
			<div class="icon32 unitag-icon32"></div>
			<h2>Custom QR Code generator</h2>

			<br/>

			<h2>Settings</h2>
	        
			<br/>

	        <form method="POST" action="options.php">
	        <?php settings_fields('unitag_options'); ?>

	            <table class="form-table">  
	                <tr valign="top"> 
	                    <th scope="row">
	                        <label for="template_id_input">  
	                            <strong>Default QR Code template ID</strong>
	                        </label>   
	                    </th>  
	                    <td>  
	                        <input id="template_id_input" type="text" name="unitag_settings[id_template]" value="<?php echo $user_settings['id_template']; ?>" />  
	                    </td>
	                </tr>
	                <tr valign="top"> 
	                    <th scope="row">
	                        <label for="unitag_settings[alt_text]">  
	                            Default alternative text 
	                        </label>   
	                    </th>  
	                    <td>  
	                        <input type="text" name="unitag_settings[alt_text]" value="<?php echo $user_settings['alt_text']; ?>" id="unitag_settings[alt_text]" />  
	                    </td>
	                </tr>
	                <tr valign="top"> 
	                    <th scope="row">
	                        <label for="unitag_settings[size]">  
	                            Default size (max. 1000px)
	                        </label>   
	                    </th>  
	                    <td>  
	                        <input type="text" name="unitag_settings[size]" value="<?php echo $user_settings['size']; ?>" id="unitag_settings[size]" />  
	                    </td>
	                </tr> 
	            </table>

	            <br/>

	            <p>
	            	<input name="unitag_settings[add_signature]" type="checkbox" value="1" <?php checked('1', $user_settings['add_signature']); ?> id="unitag-add-signature-checkbox" />
	            	<label for="unitag-add-signature-checkbox">Add Unitag signature under QR Codes</label>
	        	</p>

				<p class="submit">
	            	<input type="submit" class="button-primary" value="<?php _e('Save Changes') ?>" />
	            </p>
	        </form>

	        <br/>

	        <h2>Add to your content</h2>

	        <br/>

	        <p>Use the following shortcode in your pages and articles to add a QR Code :</p>
	        <pre class="unitag-code-block">[qr type="url" url="http://wordpress.org/"]</pre>

	        <br/>

	        <h3>QR Code parameters:</h3>
	        <table>
	        	<tr>
	        		<td class="unitag-spec-col1"><em>type</em> <span class="unitag-optional-attribute">(required)</span></td>
	        		<td class="description">Type of content of the QR Code. See possible values below.<td>
	        	</tr>
	        	<tr>
	        		<td class="unitag-spec-col1"><em>alt</em> <span class="unitag-optional-attribute">(optional)</span></td>
	        		<td class="description">Alternative text of the image.</td>
	        	</tr>
	        	<tr>
	        		<td class="unitag-spec-col1"><em>size</em> <span class="unitag-optional-attribute">(optional)</span></td>
	        		<td class="description">Height and width of the QR Code in pixels (max 1000px).</td>
	        	</tr>
	        	<tr>
	        		<td class="unitag-spec-col1"><em>id_template</em> <span class="unitag-optional-attribute">(optional)</span></td>
	        		<td class="description">ID of your QR Code template created on Unitag or choosen in the examples.</td>
	        	</tr>
	        </table>

	        <br/>

	        <p>
	        	When optional parameters are not defined in the shortcode, default values defined in the settings are used.
	        </p>

	        <br/>

	        <h3>Data parameters:</h3>

	        <p>
	        	The structure of your QR Code's content will depend on the type of QR Code you set.
	        </p>

	        <p>
	        	<strong><a id="unitag-data-link">+ Show all the data parameters</a></strong>
	    	</p>
	    	<div id="unitag-data-table">
		        <table>
		        	<tr>
		        		<th class="unitag-data-col1 unitag-data-th">Type value</th>
		        		<th class="unitag-data-col2 unitag-data-th">Data parameters</th>
		        		<th class="unitag-data-th"></th>
		        	</tr>
		        	<tr>
		        		<td class="unitag-data-col1"><em>url</em></td>
		        		<td class="unitag-data-col2"><em>url</em></td>
		        		<td class="description">URL destination of the QR Code.<td>
		        	</tr>
		        	<tr>
		        		<td class="unitag-data-col1"><em>text</em></td>
		        		<td class="unitag-data-col2"><em>text</em></td>
		        		<td class="description">Brute text to display.</td>
		        	</tr>
		        	<tr>
		        		<td class="unitag-data-col1"><em>geo</em></td>
		        		<td class="unitag-data-col2"><em>geo_lat</em></td>
		        		<td class="description">Lattitude of the localization.</td>
		        	</tr>
		        	<tr>
		        		<td class="unitag-data-col1"></td>
		        		<td class="unitag-data-col2"><em>geo_long</em></td>
		        		<td class="description">Longitude of the localization.</td>
		        	</tr>
		        	<tr>
		        		<td class="unitag-data-col1"><em>sms</em></td>
		        		<td class="unitag-data-col2"><em>sms_tel</em></td>
		        		<td class="description">Phone number to text.</td>
		        	</tr>
		        	<tr>
		        		<td class="unitag-data-col1"></td>
		        		<td class="unitag-data-col2"><em>sms_message</em></td>
		        		<td class="description">Text message to display.</td>
		        	</tr>
		        	<tr>
		        		<td class="unitag-data-col1"><em>wifi</em></td>
		        		<td class="unitag-data-col2"><em>wifi_type</em></td>
		        		<td class="description">WPA, WEP or nopass.</td>
		        	</tr>
		        	<tr>
		        		<td class="unitag-data-col1"></td>
		        		<td class="unitag-data-col2"><em>wifi_ssid</em></td>
		        		<td class="description">SSID of the Wi-Fi network.</td>
		        	</tr>
		        	<tr>
		        		<td class="unitag-data-col1"></td>
		        		<td class="unitag-data-col2"><em>wifi_password</em></td>
		        		<td class="description">Password of the network.</td>
		        	</tr>
		        	<tr>
		        		<td class="unitag-data-col1"><em>card</em></td>
		        		<td class="unitag-data-col2"><em>card_name</em></td>
		        		<td class="description">Full name.</td>
		        	</tr>
		        	<tr>
		        		<td class="unitag-data-col1"></td>
		        		<td class="unitag-data-col2"><em>card_firm</em></td>
		        		<td class="description">Firm name.</td>
		        	</tr>
		        	<tr>
		        		<td class="unitag-data-col1"></td>
		        		<td class="unitag-data-col2"><em>card_tel</em></td>
		        		<td class="description">Phone number.</td>
		        	</tr>
		        	<tr>
		        		<td class="unitag-data-col1"></td>
		        		<td class="unitag-data-col2"><em>card_address</em></td>
		        		<td class="description">Postal address.</td>
		        	</tr>
		        	<tr>
		        		<td class="unitag-data-col1"></td>
		        		<td class="unitag-data-col2"><em>card_url</em></td>
		        		<td class="description">Website URL.</td>
		        	</tr>
		        	<tr>
		        		<td class="unitag-data-col1"></td>
		        		<td class="unitag-data-col2"><em>card_memo</em></td>
		        		<td class="description">More infos about the contact.</td>
		        	</tr>
		        	<tr>
		        		<td class="unitag-data-col1"><em>email</em></td>
		        		<td class="unitag-data-col2"><em>email_address</em></td>
		        		<td class="description">Password of the network.</td>
		        	</tr>
		        	<tr>
		        		<td class="unitag-data-col1"></td>
		        		<td class="unitag-data-col2"><em>email_subject</em></td>
		        		<td class="description">Subject of the email to send.</td>
		        	</tr>
		        	<tr>
		        		<td class="unitag-data-col1"></td>
		        		<td class="unitag-data-col2"><em>email_message</em></td>
		        		<td class="description">Content of the email.</td>
		        	</tr>
		        	<tr>
		        		<td class="unitag-data-col1"><em>calendar</em></td>
		        		<td class="unitag-data-col2"><em>calendar_title</em></td>
		        		<td class="description">Name of the event to add.</td>
		        	</tr>
		        	<tr>
		        		<td class="unitag-data-col1"></td>
		        		<td class="unitag-data-col2"><em>calendar_begin</em></td>
		        		<td class="description">Beginning of the event. Format : mm/dd/yyyy hh:mm.</td>
		        	</tr>
		        	<tr>
		        		<td class="unitag-data-col1"></td>
		        		<td class="unitag-data-col2"><em>calendar_end</em></td>
		        		<td class="description">End of the event. Format : mm/dd/yyyy hh:mm.</td>
		        	</tr>
		        	<tr>
		        		<td class="unitag-data-col1"><em>phone</em></td>
		        		<td class="unitag-data-col2"><em>phone</em></td>
		        		<td class="description">Phone number to call.</td>
		        	</tr>
		        </table>
	    	</div>
	    </div>

		<div class="unitag-admin-right">
			<h2>Preview</h2>

			<?php
			// Displays QR Code preview
			foreach ( $model as $template ) {
				echo '<div id="preview_' . $template['id_template'] . '" class="unitag-model-preview">';
				echo do_shortcode('[qr alt="' . $template['alt'] . '" id_template="' . $template['id_template'] . '" type="url" url="https://www.unitag.io/" size="350"]');
				echo '</div>';
			}
			// If a custom template is set
			if ($i == 0) {
				echo '<div id="preview_' . $user_settings['id_template'] . '" class="unitag-model-preview">';
				echo do_shortcode('[qr alt="' . $template['alt'] . '" id_template="' . $user_settings['id_template'] . '" type="url" url="https://www.unitag.io/" size="350"]');
				echo '</div>';
			}
			?>


			<h2>Few templates</h2>

			<div class="unitag-thumbs-container">
				<?php
				// Displays QR Code thumbs under the preview
				foreach ( $model as $template ) {
					echo '<div id="thumb_' . $template['id_template'] . '" class="unitag-model-thumb">';
					echo do_shortcode('[qr alt="' . $template['alt'] . '" id_template="' . $template['id_template'] . '" type="url" url="https://www.unitag.io/" size="120" add_signature="false"]');
					echo '</div>';
				}
				?>
			</div>
			
			<div style="clear: both;"></div>
			<br/>
			
			<p>
				<strong><a href="https://www.unitag.io/get-started" target="_blank">Create your own templates on Unitag for free »</a></strong>
			</p>
		</div>
	</div>
<?php
}


// Add CSS to the admin page
function admin_style() {  
    wp_register_style( 'custom-style-admin', P_URL . 'css/admin.css', array(), P_VERSION, 'all' );   
    wp_enqueue_style( 'custom-style-admin' );  
}  
add_action( 'admin_enqueue_scripts', 'admin_style' ); 


// Add javascript to the admin page
function admin_js() {  
    wp_register_script( 'custom-js-admin', P_URL . 'js/admin.js', array(), P_VERSION, true );   
    wp_enqueue_script( 'custom-js-admin' );  
}  
add_action( 'admin_enqueue_scripts', 'admin_js' ); 
?>

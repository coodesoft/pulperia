<?php
/*
 *Title: WP E-Commerce
 *Origin plugin: wp-e-commerce/wp-shopping-cart.php
*/
?>
<?php
error_reporting(0);
ini_set('display_errors', 0);
if ( !function_exists('add_action') ) {
	header('Status: 403 Forbidden');
	header('HTTP/1.1 403 Forbidden');
	exit();
}

ob_get_clean();
ob_get_clean();
ob_get_clean();
ob_get_clean();

set_time_limit ( 60 * 5 ); //5 min
ini_set('memory_limit','256M');

if(function_exists('pll_current_language') && function_exists('pll_the_languages')){
	if(isset($_REQUEST['lang'])){
		if($_REQUEST['lang'] == 'all'){
			global $polylang;
			$polylang->curlang = null;
		}
	}else{
		global $polylang;
		$polylang->curlang = null;
	}
}

$variations_skip = array( 'categories','status');

//ob_clean();
//header('Content-Type: text/html; charset=' . get_option('blog_charset'), true);

if(isset($_REQUEST['keep_alive'])){
	if($_REQUEST['keep_alive']){
		return;
	}
}

global $wpdb;
global $wpsc_fields_visible;
global $custom_fileds, $use_image_picker, $use_content_editior;


$use_image_picker    = false;
$use_content_editior = false;

$wpsc_fields_visible = array();
if(isset($plem_settings['wpsc_fileds'])){
	foreach(explode(",",$plem_settings['wpsc_fileds']) as $I => $val){
		if($val)
			$wpsc_fields_visible[$val] = true;
	}
}

global $impexp_settings, $custom_export_columns;

$impexp_settings = NULL;
$custom_export_columns=array();		

if(isset($plem_settings[$_REQUEST["elpm_shop_com"].'_custom_import_settings'])){
	$impexp_settings = $plem_settings[$_REQUEST["elpm_shop_com"].'_custom_import_settings'];
	if(!isset($impexp_settings->custom_export_columns))
		$impexp_settings->custom_export_columns = "";
	if($impexp_settings){
		foreach(explode(",",$impexp_settings->custom_export_columns) as $col){
			   if($col)
				$custom_export_columns[] = $col;
		}
	}
}

if(!isset($impexp_settings->delimiter2))
	$impexp_settings->delimiter2 = ',';

if(!isset($impexp_settings->use_custom_export))
	$impexp_settings->use_custom_export = false;


function fn_show_filed($name){
	global $wpsc_fields_visible;
	
	if(empty($wpsc_fields_visible))
		return $name != "image";
		
	if(isset($wpsc_fields_visible[$name]))
		return $wpsc_fields_visible[$name];
	
	$wpsc_fields_visible[$name] = false;
	return false;
};

function fn_correct_type($s){
  if( is_numeric(trim($s)))
	return intval($s);
  else 
    return trim($s);  
};

function arrayVal($val){
   if(is_string($val))
		return explode(",",$val);
   return $val;	
};

function fn_get_meta_by_path($id, $path , $decoder = ""){
	
	if(strpos($path, '!') !== false || strpos($path, '.') !== false){
		$object   = null;
		$is_object  = false;
		$meta_key = "";
		$parent_is_object = false;
		
		$ind_key  = strpos($path, '!');
		$ind_prop = strpos($path, '.');
		
		
		$meta_key = "";
		if(($ind_key === false || $ind_key > $ind_prop) && $ind_prop !== false){
			$meta_key = substr($path , 0 , $ind_prop);
			$path     = substr($path,$ind_prop + 1);
		}else{
			$meta_key = substr($path , 0 , $ind_key);
		    $path     = substr($path,$ind_key + 1);
		}
		
		$object   = get_post_meta($id,$meta_key,true);
		
		if($object){
			if($decoder == "text_array"){
				if(is_string($object ))
					$object = explode(",",$object);
			}else if($decoder == "json_array" || $decoder == "json_object"){
				if(is_string($object ))
					$object = json_decode($object);
			}else if($decoder == "php_array" || $decoder == "php_object"){
				if(is_string($object ))
					$object = unserialize($object);
			}
		}
		
		if(!$object)
			return null;
		
		$parent_is_object = !is_array($object);
			
		$ptr = &$object;
		do{
		   $is_object = false;
		   $ind_key  = strpos($path, '!');
		   $ind_prop = strpos($path, '.');
    	   $ind = -1; 
		   if($ind_key !== false){
		     if($ind_prop !== false){
			    $ind = $ind_key > $ind_prop? $ind_prop : $ind_key;
				if($ind === $ind_prop)
					$is_object = true;
			 }else
			    $ind = $ind_key;
		   }elseif($ind_prop !== false){
				$ind = $ind_prop;
				$is_object = true;
		   }	
		   
		   if($ind != -1){ 
			   $key  =  substr( $path , 0 , $ind);
			   if($key === '' || $key === null){
			     $path = $key;
				 break;
			   }
			   $path =  substr( $path , $ind + 1);
		   }else 
		       break;
			   
		   if($parent_is_object){
		        if(!isset($ptr->{$key}))
					return null;
				$ptr = &$ptr->{$key};  
		   }else{
				if(!isset($ptr[$key])){
					if(is_assoc($ptr)){
						$was_assoc = false;
						if(is_numeric($key)){
							if($key < count($ptr)){
								$k = array_keys($ptr);
								if(!empty($k)){
									$key = $k[$key];
									if(isset($ptr[$key]))
										$was_assoc = true;
								}
							}
						}elseif(strpos($key,"*") !== false){
							$key = str_replace("*","",$key);
							$k = array_keys($ptr);
							foreach($k as $ak){
								if(strpos($ak,$key) !== false){
									$key = $ak;
									$was_assoc = true;
									break;
								}
							}
						}
						
						if(!$was_assoc)
							return null;
					}else
						return null;
				}
				$ptr = &$ptr[$key];
           }	   
		   $parent_is_object = !is_array($ptr);
		}while($ind_key !== false || $ind_prop !== false);
        
		if(!is_array($ptr))
			return $ptr->{$path};
        else
			return $ptr[$path];
	}else{
		if($decoder){
			$v = get_post_meta($id,$path,true);
			if($decoder == "text_array"){
				if(is_string($v ))
					$v = json_decode($v);
				return explode(",",$v);
			}else if($decoder == "json_array" || $decoder == "json_object"){
				if(is_string($v ))
					$v = json_decode($v);
				return $v;
			}else if($decoder == "php_array" || $decoder == "php_object"){
				if(is_string($v ))
					$v = unserialize($v);
				return $v;
			}else
				return $v;
		}else
			return get_post_meta($id,$path,true);
	}
};

function fn_getSerialized($value,$coder){
	if($coder){
		if($coder == "text_array"){
			if(!is_string($value)){
				$value = implode(",",$value);
			}
		}elseif($coder == "json_array" || $coder == "json_object"){
			if(!is_string($value)){
				if($coder == "json_array" && !is_array($value)){
					$value = array($value);
				}elseif($coder == "json_object" && is_array($value)){
					if(!empty($value))
						$value = $value[0];
					else
						$value = new stdClass;
				}
				$value = json_encode($value);
			}
		}elseif($coder == "php_array" || $coder == "php_object"){
			if(!is_string($value)){
				if($coder == "php_array" && !is_array($value)){
					$value = array($value);
				}elseif($coder == "php_object" && is_array($value)){
					if(!empty($value))
						$value = $value[0];
					else
						$value = new stdClass;
				}
				//$value = serialize($value);
			}
		}
	}
	return $value;
}

function fn_set_meta_by_path(&$id,$path,$value,$coder = ""){
	
    $ind_key  = strpos($path, '!');
	$ind_prop = strpos($path, '.');
	
    if($ind_key !== false || $ind_prop !== false){
		
        $object     = null;
		$is_object  = false;
		$meta_key   = "";
		$parent_is_object = false;
		
		
		$meta_key = "";
		$__isobj = false;
		
		if(($ind_key === false || $ind_key > $ind_prop) && $ind_prop !== false){
			$meta_key = substr( $path , 0 , $ind_prop);
			$path     = substr($path,$ind_prop + 1);
			$__isobj  = true;
		}else{
			$meta_key = substr( $path , 0 , $ind_key);
		    $path     = substr($path,$ind_key + 1);
			$__isobj  = false;
		}
		
		$object   = get_post_meta($id,$meta_key,true);
		
		if($object){
			if($coder == "text_array"){
				if(is_string($object ))
					$object = explode(",",$object);
			}else if($coder == "json_array" || $coder == "json_object"){
				if(is_string($object ))
					$object = json_decode($object);
			}else if($coder == "php_array" || $coder == "php_object"){
				if(is_string($object ))
					$object = unserialize($object);
			}
		}else{
			if($__isobj){
				$object = new stdClass();
			}else{
				$object = array();
			}
		}
		
		$parent_is_object = !is_array($object);
		
		$ptr = &$object;
		
		do{
		   $is_object = false;
		   $ind_key   = strpos($path, '!');
		   $ind_prop  = strpos($path, '.');
		   
    	   $ind = -1; 
		   if($ind_key !== false){
		     if($ind_prop !== false){
			    $ind = $ind_key > $ind_prop? $ind_prop : $ind_key;
				if($ind === $ind_prop)
					$is_object = true;
			 }else
			    $ind = $ind_key;
		   }elseif($ind_prop !== false){
				$ind = $ind_prop;
				$is_object = true;
		   }	
		   
		   if($ind != -1){ 
			   $key  =  substr( $path , 0 , $ind);
			   if($key === '' || $key === null){
			     $path = $key;
				 break;
			   }
			   $path =  substr( $path , $ind + 1);
		   }else 
		       break;
		   if($parent_is_object){
		        if(!isset($ptr->{$key}))
					$ptr->{$key} = $is_object ? new stdClass() : array();
				$ptr = &$ptr->{$key};  
		   }else{
			    if(!isset($ptr[$key])){
					$found = false;
					if(is_assoc($ptr)){
						if(is_numeric($key)){
							if($key < count($ptr)){
								$k = array_keys($ptr);
								if(!empty($k)){
									$key = $k[$key];
									$found = true;
								}
							}
						}elseif(strpos($key,"*") !== false){
							$key = str_replace("*","",$key);
							$k = array_keys($ptr);
							
							foreach($k as $ak){
								if(strpos($ak,$key) !== false){
									$key = $ak;
									$found = true;
									break;
								}
							}
							
							if(!$found)
								return;
						}
					}
				}
				
				if(!isset($ptr[$key]))
					$ptr[$key] = $is_object ? new stdClass() : array();		   
				
				$ptr = &$ptr[$key];
		   }	   
		   $parent_is_object = !is_array($ptr);
		}while($ind_key !== false || $ind_prop !== false);
        
		if(!is_array($ptr))
			$ptr->{$path} = $value;
        else
			$ptr[$path]   = $value;
		
		$object = fn_getSerialized($object,$coder);
	    update_post_meta($id, $meta_key, $object);
	}else{ 
	    $value = fn_getSerialized($value,$coder);
		update_post_meta($id, $path, $value);
	}
};

function asArray($val){
	if(!is_array($val)){
		return array($val);
	}
	return $val;
}


function fn_convert_unit($value,$from,$to){
	if($from == "pound"){
	  if($to == "ounce") $value *= 16;
	  elseif($to == "gram") $value *= 453.59237;
	  elseif($to == "kilogram") $value *= 0.45359237;
	}elseif($from == "ounce"){
	  if($to == "pound") $value *= 0.0625; 
	  elseif($to == "gram") $value *= 28.3495231;
	  elseif($to == "kilogram") $value *= 0.0283495231;
	}elseif($from == "gram"){
	  if($to == "pound") $value *= 0.00220462262;
	  elseif($to == "ounce") $value *= 0.0352739619;
	  elseif($to == "kilogram") $value *= 0.001;
	}elseif($from == "kilogram"){
	  if($to == "pound") $value *= 2.204622;
	  elseif($to == "ounce") $value *= 35.2739619;
	  elseif($to == "gram") $value *= 1000;
	}elseif($from == "in"){
	  if($to == "cm") $value *= 2.54;
	  elseif($to == "meter") $value *= 0.0254; 
	}elseif($from == "cm"){ 
	  if($to == "in") $value *= 0.393700787;
	  elseif($to == "meter") $value *= 0.01;
	}elseif($from == "meter"){
      if($to == "in") $value *= 39.3700787;
	  elseif($to == "cm") $value *= 100;
	}  
	return $value;
};

function toUTF8($str){
	if(is_string($str)){
		if(function_exists("mb_convert_encoding")){
			return mb_convert_encoding($str,"UTF-8");
		}else{
			return htmlspecialchars_decode(utf8_decode(htmlentities($str, ENT_COMPAT, 'utf-8', false)));	
		}
	}else
		return $str;
}

function get_array_value(&$array,$key,$default){
   if(isset($array[$key]))
	  return $array[$key];
   else
	  return $default;
}; 

$custom_fileds = array();
function loadCustomFields(&$plem_settings,&$custom_fileds){
    global $use_image_picker, $use_content_editior;
	
	
	for($I = 0 ; $I < 20 ; $I++){
		$n = $I + 1;
		if(isset($plem_settings["wpsccf_enabled".$n])){
			if($plem_settings["wpsccf_enabled".$n]){
				$cfield = new stdClass();
				
				$cfield->type  = get_array_value($plem_settings,"wpsccf_type".$n, "");
				if(!$cfield->type)
				  continue;
				  
				$cfield->title = get_array_value($plem_settings,"wpsccf_title".$n, "");
				if(!$cfield->title)
				  continue;
			   
				$cfield->source = get_array_value($plem_settings,"wpsccf_source".$n, "");
				if(!$cfield->source)
				  continue;  
				  
				$cfield->options = get_array_value($plem_settings,"wpsccf_editoptions".$n, "");
				if($cfield->options){
				  $cfield->options = json_decode($cfield->options);
				}else{
				  $cfield->options = new stdClass();	
				  $cfield->options->formater = '';
				}
					
				if($cfield->type == 'term'){
				   $cfield->terms = array();
				   $terms = get_terms( $cfield->source , array('hide_empty' => false));
				   foreach($terms as $val){
						$value            = new stdClass();
						$value->value     = $val->term_id;
						//$value->slug      = $val->slug;
						$value->name      = $val->name;
						//$value->parent    = $val->parent;
						$cfield->terms[]  = $value;
					}
				}else{
					if($cfield->options->formater == "content")
						$use_content_editior = true;
					elseif($cfield->options->formater == "image")
						$use_image_picker    = true;
				}	
					
				$cfield->name = 'cf_'. strtolower($cfield->source);			
				$custom_fileds[$cfield->name] = $cfield;	
			}   
		}
	}
};

loadCustomFields($plem_settings,$custom_fileds);


function plem_add_product(){
	$post = array(
     	 'post_author' => get_current_user_id(),
		 'post_content' => '',
		 'post_status' => "publish",
		 'post_title' => "Product ".date("y-m-d H:i:s"),
		 'post_parent' => '',
		 'post_type' => "wpsc-product"
     );
	 
     $post_id = wp_insert_post( $post, $wp_error );
     if($post_id){
		$attach_id = get_post_meta($product->parent_id, "_thumbnail_id", true);
		add_post_meta($post_id, '_thumbnail_id', $attach_id);
     }
     
				
	 $wpsc_cats = get_terms( 'wpsc_product_category', array(
													'number'     => 1,
													'orderby'    => 'slug',
													'order'      => 'ASC',
													'hide_empty' => false,
													'include'    => '',
													'parent'     => 0 
												));
	 
	 
	 
				
	 if(!empty($wpsc_cats)){
		wp_set_object_terms($post_id, array(intval($wpsc_cats[0]->term_id)), 'wpsc_product_category' );
     }
	 
	 
				
	 update_post_meta( $post_id, '_wpsc_price','0');
	 update_post_meta( $post_id, '_wpsc_special_price','0');
	 update_post_meta( $post_id, '_wpsc_sku','');	
	 update_post_meta( $post_id, '_wpsc_stock','');	
	 update_post_meta( $post_id, '_wpsc_product_metadata','');
	 update_post_meta( $post_id, '_wpsc_is_donation','0');
	 update_post_meta( $post_id, '_wpsc_currency','a:0:{}');

	 return $post_id;
}

$productsPerPage = 1000;
if(isset($plem_settings["productsPerPage"])){
	$productsPerPage = intval($plem_settings["productsPerPage"]);
}

$limit = $productsPerPage;

if(isset($_COOKIE['pelm_txtlimit']))
	$limit = $_COOKIE['pelm_txtlimit'] ? $_COOKIE['pelm_txtlimit'] : $productsPerPage;

	
	
$page_no  = 1;

$orderby         = "ID";
$orderby_key     = "";

$sort_order  = "DESC";
$sku = '';
$product_name = '';
$product_category = '';
$product_tag      = '';
$product_status   = '';

if(isset($_REQUEST['limit'])){
	$limit = $_REQUEST['limit'];
}

if(isset($_REQUEST['page_no'])){
	$page_no = $_REQUEST['page_no'];
}

if(isset($_REQUEST['sku'])){
	$sku = $_REQUEST['sku'];
}

if(isset($_REQUEST['product_name'])){
	$product_name = $_REQUEST['product_name'];
}

if(isset($_REQUEST['product_category'])){
	$product_category = explode(",", $_REQUEST['product_category']);
}

if(isset($_REQUEST['product_tag'])){
	$product_tag = explode(",", $_REQUEST['product_tag']);
}


if(isset($_REQUEST['product_status'])){
	$product_status = explode(",", $_REQUEST['product_status']);
}	

if(isset($_REQUEST['sortColumn'])){
	$orderby = $_REQUEST['sortColumn'];
	
	$orderby = "ID";
	$orderby_key = "";
	
	if(isset($custom_fileds[$_REQUEST['sortColumn']])){
	 
		$field = $custom_fileds[$_REQUEST['sortColumn']];
		
		if($custom_fileds[$_REQUEST['sortColumn']]->type == 'post'){
			$orderby = $custom_fileds[$_REQUEST['sortColumn']]->source;
			$orderby_key = "";
		}elseif($custom_fileds[$_REQUEST['sortColumn']]->type == 'meta'){
			$orderby = "meta_value";
			$orderby_key = $custom_fileds[$_REQUEST['sortColumn']]->source;
		}

	}
	elseif($orderby == "id") $orderby = "ID";
	elseif($orderby == "sku") {
	    $orderby = "meta_value";
		$orderby_key = "_wpsc_sku";
	}
	elseif($orderby == "slug") $orderby = "name";
	elseif($orderby == "categories") {
		$orderby = "category_name";
		//???? this is not correct
	}
	elseif($orderby == "name") $orderby = "title";
    elseif($orderby == "stock") {
		$orderby = "meta_value_num";
		$orderby_key = "_wpsc_stock";
	}
	elseif($orderby == "price") {
		$orderby = "meta_value_num";
		$orderby_key = "_wpsc_price";
	}
	elseif($orderby == "override_price") {
		$orderby = "meta_value_num";
		$orderby_key = "_wpsc_special_price";
	}
	elseif($orderby == "status"){ 
		$orderby = "status";
	}
	elseif($orderby == "tags"){ 
		$orderby = "tag";
		//???? this is not correct
	}
}

if(isset($_REQUEST['sortOrder'])){
	$sort_order = $_REQUEST['sortOrder'];
}

if(isset($_REQUEST['DO_UPDATE'])){
if($_REQUEST['DO_UPDATE'] == '1' && strtoupper($_SERVER['REQUEST_METHOD']) == 'POST'){
    
	$timestamp = time();
	$json = file_get_contents('php://input');
	$tasks = json_decode($json);
    $surogates = get_option("plem_wpsc_surogates",array());
	$surogates_dirty = false;
	if(!empty($surogates)){
		foreach($surogates as $s_key => $s){
			if($s["created"] < $timestamp - 1800){
				unset($surogates[$s_key]);
				$surogates_dirty = true;
			}		
		}
	}
	
	$res = array();
	$temp = '';
	
	foreach($tasks as $key => $task){
	   $return_added = false;
	   $res_item = new stdClass();
	   $res_item->id = $key;
	   
	   $sKEY = "".$key;
	   if($sKEY[0] == 's'){
			if(isset($surogates[$sKEY]))
				$key = $surogates[$sKEY]["value"];
			else{
				$return_added = true;
				$key = plem_add_product();
				
				$surogates[$sKEY] = array(
				 'created' => $timestamp,
				 'value'   => $key
				);
				
			    $surogates_dirty = true;	
			}
			$return_added = true;
	   }
	   
	   if(isset($task->DO_DELETE)){
		   if($task->DO_DELETE === 'delete'){
			   
			   wp_delete_post($key,true);
			   $res[] = $res_item;
			   continue;
		   }
	   }
	   
	   $upd_prop = array();
	  
       $post_update = array( 'ID' => $key );
	  
	   if(isset($task->sku)){ 
		  update_post_meta($key, '_wpsc_sku', $task->sku);
	   }
	   
	   if(isset($task->stock)){ 
		  update_post_meta($key, '_wpsc_stock', $task->stock);
	   }
	   
	   if(isset($task->price)){ 
		  update_post_meta($key, '_wpsc_price', $task->price);
	   }
	   
	   if(isset($task->override_price)){
	      update_post_meta($key, '_wpsc_special_price', $task->override_price);
	   }
	   
	   $pr_meta = get_post_meta($key,'_wpsc_product_metadata',true);
	   $dimensions = &$pr_meta['dimensions'];
	   if(!$dimensions){
	      $pr_meta['dimensions'] = array();
		  $dimensions = &$pr_meta['dimensions'];
	   }
 	  
	   if(isset($task->weight)){
		   if($task->weight){
			   $pr_meta['weight']       = floatval($task->weight);
			   $pr_meta['weight_unit']  = str_replace(" ","",str_replace($pr_meta['weight'],'',$task->weight));
			   $pr_meta['weight'] = fn_convert_unit($pr_meta['weight'],$pr_meta['weight_unit'],'pound');
		   }else{
			   $pr_meta['weight']       = '';   
			   $pr_meta['weight_unit']  = '';
		   }
	   }
	   
	   if(isset($task->height)){
		   if($task->height){
			   $dimensions['height']       = floatval($task->height);
			   $dimensions['height_unit']  = str_replace(" ","",str_replace($dimensions['height'],'',$task->height));
		   }else{
			   $dimensions['height']       = ''; 
			   $dimensions['height_unit']  = '';
		   }
	   }

	   if(isset($task->width)){
		   if($task->width){
			   $dimensions['width']        = floatval($task->width);
			   $dimensions['width_unit']   = str_replace(" ","",str_replace($dimensions['width'],'',$task->width));
		   }else{
			   $dimensions['width']        = '';
			   $dimensions['width_unit']   = '';
		   }
	   }
	   
	   if(isset($task->length)){
		   if($task->length){
			   $dimensions['length']       = floatval($task->length);
			   $dimensions['length_unit']  = str_replace(" ","",str_replace($dimensions['length'],'',$task->length));
		   }else{
			   $dimensions['length']       = '';
			   $dimensions['length_unit']  = '';
		   }
	   }
	   
	   if(isset($task->taxable))
		$pr_meta['wpec_taxes_taxable_amount'] = $task->taxable;
	   
	   if(isset($task->loc_shipping))
		$pr_meta['shipping']['local']         = $task->loc_shipping;
	   
	   if(isset($task->int_shipping))
		$pr_meta['shipping']['international'] = $task->int_shipping;
	   
	   update_post_meta($key, '_wpsc_product_metadata', $pr_meta);
	   
	   if(isset($task->status)){
	      $post_update['post_status'] = $task->status;
	   }
	   
	   if(isset($task->name)){ 
	      $post_update['post_title'] = $task->name;  
	   }
	   
	   if(isset($task->slug)){ 
		  $post_update['post_name'] = $task->slug;  
	   }
	  
	   if(count($post_update) > 1){
	      wp_update_post($post_update);;
	   }
	   
	   if(isset($task->categories)){
          wp_set_object_terms( $key , array_map('intval', arrayVal($task->categories)) , 'wpsc_product_category' );
	   }
	   
	   if(isset($task->tags)){
		  wp_set_object_terms( $key , array_map(fn_correct_type, arrayVal($task->tags)) , 'product_tag' );
	   }
	   
	   if(isset($task->image)){
		  if($task->image){
			if($task->image->id){
				set_post_thumbnail($key,$task->image->id);
			}else
				delete_post_thumbnail( $key );				
		  }else
			delete_post_thumbnail( $key );					
	   }

	   
	   foreach($custom_fileds as $cfname => $cfield){
			if(isset($task->{$cfname})){
			   if($cfield->type == "term"){
					wp_set_object_terms( $key , array_map(fn_correct_type, arrayVal($task->{$cfname})) , $cfield->source );
			   }elseif($cfield->type == "meta"){
				    $value_coder = "";
				    if(isset($cfield->options)){
						if(isset($cfield->options->format)){
							
							if(strpos($cfield->options->format,'_array') !== false){
								$task->{$cfname} = is_array($task->{$cfname}) ? $task->{$cfname} : explode(",",$task->{$cfname});
								$task->{$cfname} = array_map(fn_correct_type, $task->{$cfname});
							
                                if($cfield->options->format == "text_array")
									$task->{$cfname} = implode(",",$task->{$cfname});
								elseif($cfield->options->format == "php_array")
									$task->{$cfname} = $task->{$cfname};
								elseif($cfield->options->format == "json_array")	
									$task->{$cfname} = json_encode( $task->{$cfname});							
								
							}
						}

						if(isset($cfield->options->serialization)){
							$value_coder = $cfield->options->serialization;
						}		
					}
			        fn_set_meta_by_path( $key, $cfield->source, $task->{$cfname},$value_coder);  
			   }elseif($cfield->type == "post"){
			        $wpdb->query( 
						$wpdb->prepare( "UPDATE $wpdb->posts SET ".$cfield->source." = %s WHERE ID = %d", $task->{$cfname} ,$key )
				    ); 
			   }
			}
	   } 

	   if($return_added){
			$res_item->surogate = $sKEY;
			$res_item->full     = product_render($key , "data" );
	   }
	   
	   $res_item->success = true;
	   $res[] = $res_item;
	}
	
	if($surogates_dirty){
		update_option("plem_wpsc_surogates",(array)$surogates);
	}
	
	echo json_encode($res);
    exit; 
	return;
}
}

function Getfloat($str) { 
  global $impexp_settings;

  if(!isset($impexp_settings->german_numbers))
	$impexp_settings->german_numbers = 0; 
	
  if($impexp_settings->german_numbers){
	  $str = str_replace(".", "", $str);
	  $str = str_replace(",", ".", $str);
  }else{
	  if(strstr($str, ",")) { 
		$str = str_replace(",", "", $str); // replace ',' with '.' 
	  }
  }
  return $str;
}; 


if(isset($_REQUEST['remote_import']) && !isset($_REQUEST["json_import"])){
  if(isset( $this->settings["wpsc_remote_import_timestamp"])){
     if(intval($_REQUEST['file_timestamp'])  <= intval($this->settings["wpsc_remote_import_timestamp"])){
		echo "Requested import CSV is equal or older that latest processed!";
	    exit();
	    return;
	 }
  }
}

function pelm_insert_attachment_from_url($url, $post_id = null) {

	if( !class_exists( 'WP_Http' ) )
		include_once( ABSPATH . WPINC . '/class-http.php' );

	$http = new WP_Http();
	$response = $http->request( $url );
	
	if(!is_array($response))
		return false;
	
	if(!$response)
		return false;
	
	if(!$response['response'])
		return false;
	
	if(!$response['response']['code'])
		return false;
	
	if( $response['response']['code'] != 200 ) {
		return false;
	}

	$upload = wp_upload_bits( basename($url), null, $response['body'] );
	if( !empty( $upload['error'] ) ) {
		return false;
	}

	$file_path = $upload['file'];
	$file_name = basename( $file_path );
	$file_type = wp_check_filetype( $file_name, null );
	$attachment_title = sanitize_file_name( pathinfo( $file_name, PATHINFO_FILENAME ) );
	$wp_upload_dir = wp_upload_dir();
	
	$seo = str_ireplace(array('_','-','.jpg','.gif','.png'),' ',$file_name);
	
	if($post_id)
		$seo = get_the_title($post_id);
	
	$post_info = array(
		'guid'				=> $wp_upload_dir['url'] . '/' . $file_name, 
		'post_mime_type'	=> $file_type['type'],
		'post_title'		=> $attachment_title,
		'post_content'		=> '',
		'post_status'		=> 'inherit',
		'post_type'         => 'attachment',
		'post_content'      => $seo,
		'post_excerpt'      => $seo
	);

	// Create the attachment
	$attach_id = wp_insert_attachment( $post_info, $file_path, $post_id );
	
	// Include image.php
	require_once( ABSPATH . 'wp-admin/includes/image.php' );

	// Define attachment metadata
	$attach_data = wp_generate_attachment_metadata( $attach_id, $file_path );

	// Assign metadata to attachment
	wp_update_attachment_metadata( $attach_id,  $attach_data );
	
	if($attach_id){
		update_post_meta($attach_id,'_wp_attachment_image_alt',$seo);
	}
	return $attach_id;

}


function insert_image_media($image, $post_id = null){
	global $wpdb, $image_import_path_cache;
	if(!isset($image_import_path_cache)){
		$image_import_path_cache = array();
	}else if(isset($image_import_path_cache[$image])){
		return $image_import_path_cache[$image];
	}
	
	$attach_id  = false;
	$upload_dir = wp_upload_dir();
	$ok         = false;
	
	$img_search = str_replace(array('/','https','http',':','.'),'',strtolower($image));
	
	
	
	$existing_id = $wpdb->get_var($wpdb->prepare("SELECT ID 
												  FROM $wpdb->posts 
												  WHERE post_type = 'attachment' 
												  AND 
												  REPLACE(REPLACE(REPLACE(REPLACE(REPLACE(lower(guid),'/',''),'https',''),'http',''),':',''),'.','') LIKE '%s'",$img_search));
	if($existing_id){
		$existing_path = get_attached_file($existing_id);
		if(file_exists($existing_path)){
			$image_import_path_cache[$image] = $existing_id;
			return $existing_id;	
		}
	}					

	$filename =  str_replace(" ","",basename($image));
	$filename =  str_replace("$","",$filename);
	
	wp_mkdir_p($upload_dir['path']);
	
	
	$file_path = $upload_dir['path'] . DIRECTORY_SEPARATOR . $filename;
	$file_path = str_replace("/",DIRECTORY_SEPARATOR,$file_path);
	$file_url  = $upload_dir['url'] . "/" . $filename;
	
	
	if(file_exists($file_path)){
		
		$search = $file_url;
		$search = str_replace(DIRECTORY_SEPARATOR,"/",$search);
		$search = explode("/uploads/",$search);
		$search = $search[1];

		$attachment = $wpdb->get_col("SELECT ID FROM $wpdb->posts WHERE guid LIKE '%$search'"); 
		if(isset($attachment[0])){
			$image_import_path_cache[$image] = $attachment[0];
			return $image_import_path_cache[$image];
		}
	}
	
	
		
	if(isset($image_import_path_cache[$image])){
		return $image_import_path_cache[$image];
	}
	
	
	$attach_id = pelm_insert_attachment_from_url($image, $post_id);
    
	$image_import_path_cache[$image] = $attach_id;
	
	return $attach_id;
}




$import_count = 0;
if(isset($_REQUEST["do_import"])){
	if($_REQUEST["do_import"] = "1"){
	    $n = 0;
		if (($handle = fopen($_FILES['file']['tmp_name'], "r")) !== FALSE) {
			$id_index                = -1;
			$price_index             = -1;
			$price_o_index           = -1;
			$stock_index             = -1;
			$sku_index               = -1;
			$name_index              = -1;
            $slug_index              = -1;
			$status_index            = -1;
			$categories_names_index  = -1;
			$tags_names_index        = -1;
			$weight_index            = -1;
			$height_index            = -1;
			$width_index             = -1;
			$length_index            = -1;
			$taxable_index           = -1;
			$loc_shipping_index      = -1;
			$int_shipping_index      = -1;
			$image_index             = -1;
			
			$cf_indexes              = array();
			$col_count 				 = 0;
			
			$skip_first    = false;
			$custom_import = false;
			
			$imported_ids  = array();
		
			if($impexp_settings){
				
				
				$cic = array();
				foreach(explode(",",$impexp_settings->custom_import_columns) as $col){
				   if($col)
					$cic[] = $col;
				}
				
				if($impexp_settings->use_custom_import){
					$custom_import = true;
					if($impexp_settings->first_row_header)
						$skip_first = true;
						
					$col_count = count($cic);
					$data      = $cic;
					for($i = 0 ; $i < $col_count; $i++){
						
						if($i == 0 ){
							 $bom     = pack('H*','EFBBBF');
							 $data[$i] = preg_replace("/^$bom/", '', $data[$i]);
						}
						
				        if($data[$i]     == "id") $id_index = $i;
						elseif($data[$i] == "price") $price_index = $i;
						elseif($data[$i] == "override_price") $price_o_index = $i;
						elseif($data[$i] == "sku") $sku_index   = $i;
						elseif($data[$i] == 'stock') $stock_index = $i;
						elseif($data[$i] == 'name') $name_index = $i;
						elseif($data[$i] == 'slug') $slug_index = $i;
						elseif($data[$i] == 'status') $status_index = $i;
						elseif($data[$i] == 'categories_names') $categories_names_index = $i;
						elseif($data[$i] == 'tags_names') $tags_names_index          = $i;
						elseif($data[$i] == 'weight') $weight_index                  = $i;
						elseif($data[$i] == 'height') $height_index                  = $i;
						elseif($data[$i] == 'width')  $width_index                   = $i;
						elseif($data[$i] == 'length') $length_index                  = $i;
						elseif($data[$i] == 'taxable') $taxable_index                = $i;
						elseif($data[$i] == 'loc_shipping') $loc_shipping_index      = $i;
						elseif($data[$i] == 'int_shipping') $int_shipping_index      = $i;
						elseif($data[$i] == 'image') $image_index                    = $i;
						
						foreach($custom_fileds as $cfname => $cfield){
							if($cfname == $data[$i]){
								$cf_indexes[$cfname] = $i;
								break;
							}
						}

					}
					
				}
			}
			
			$csv_row_processed_count = 0;
			if(!isset($impexp_settings->delimiter))
				$impexp_settings->delimiter = ",";
			while (($data = fgetcsv($handle, 32768, $impexp_settings->delimiter)) !== FALSE) {
				if($n == 0 && $custom_import && $skip_first){
					//NOTHING 
				}elseif($n == 0 && !$custom_import){
					
					$bom     = pack('H*','EFBBBF');
					$data[0] = preg_replace("/^$bom/", '', $data[0]);
					
				   	//$id_index    = 0;
					$col_count = count($data);
					for($i = 0 ; $i < $col_count; $i++){
						
				        if($data[$i]     == "id") $id_index = $i;
						elseif($data[$i] == "price") $price_index = $i;
						elseif($data[$i] == "override_price") $price_o_index = $i;
						elseif($data[$i] == "sku") $sku_index   = $i;
						elseif($data[$i] == 'stock') $stock_index = $i;
						elseif($data[$i] == 'name') $name_index = $i;
						elseif($data[$i] == 'slug') $slug_index = $i;
						elseif($data[$i] == 'status') $status_index = $i;
						elseif($data[$i] == 'categories_names') $categories_names_index = $i;
						elseif($data[$i] == 'tags_names') $tags_names_index = $i;
						elseif($data[$i] == 'weight') $weight_index            = $i;
						elseif($data[$i] == 'height') $height_index            = $i;
						elseif($data[$i] == 'width')  $width_index             = $i;
						elseif($data[$i] == 'length') $length_index            = $i;
						elseif($data[$i] == 'taxable') $taxable_index           = $i;
						elseif($data[$i] == 'loc_shipping') $loc_shipping_index      = $i;
						elseif($data[$i] == 'int_shipping') $int_shipping_index      = $i;
						elseif($data[$i] == 'image') $image_index                    = $i;
						
						foreach($custom_fileds as $cfname => $cfield){
							if($cfname == $data[$i]){
								$cf_indexes[$cfname] = $i;
								break;
							}
						}

					}
				}else{
				   global $impexp_settings;	
				   $mvd = $impexp_settings->value_delimiter;
				   if(!$mvd)
						$mvd = ",";
					
					
				   $csv_row_processed_count++;
				   
				   
				
				   $id = NULL;
				   if($id_index >= 0)	
					$id = intval($data[$id_index]);
				
				
				
                   if(!$id && $sku_index != -1){
						if($data[$sku_index]){
							$res = $wpdb->get_col("select post_id from $wpdb->postmeta where meta_key like '_wpsc_sku' and meta_value like '".$data[$sku_index]."'");
							if(!empty($res))
								$id = $res[0];
						}
				   }
				   
				   if(!$id && $name_index != -1){
						if($data[$name_index]){
							$res = $wpdb->get_col("select ID from $wpdb->posts where cast(post_title as char(255)) like '" . $data[$name_index] . "' and post_type like 'wpsc-product' ");
							if(!empty($res))
								$id = $res[0];
						}
				   }
				   
						
				   if(!$id){
					if(isset($plem_settings['enable_add'])){
						if($plem_settings['enable_add'] && implode("",$data))
							$id = plem_add_product();
						else
							continue;	
					}else		
						continue;
				   }
				   
				   
				   
				   if(false === get_post_status( $id ))
					   continue;
					
				   $imported_ids[] = $id;		
				
				   while(count($data) < $col_count)
					  $data[] = NULL;
					  
				   $post_update = array( 'ID' => $id );
				   
				  
	  
				   if($sku_index > -1){ 
					  update_post_meta($id, '_wpsc_sku', $data[$sku_index]);
				   }
				   
				   if($stock_index > -1){ 
					  update_post_meta($id, '_wpsc_stock', $data[$stock_index]);
				   }
				   
				   if($price_index > -1){ 
				   	  if($data[$price_index])
						$data[$price_index] = Getfloat($data[$price_index]);
					
					  update_post_meta($id, '_wpsc_price', $data[$price_index]);
				   }
				  
				   if($price_o_index > -1){
					  if($data[$price_o_index])
						$data[$price_o_index]= Getfloat($data[$price_o_index]);
						
					  update_post_meta($id, '_wpsc_special_price', $data[$price_o_index]);
				   }
				   
				   if($status_index > -1){
					  $post_update['post_status'] = $data[$status_index];
				   }
				   
				   if(!isset($impexp_settings->onimportstatus_overide))
						$impexp_settings->onimportstatus_overide = "";
						
				   if($impexp_settings->onimportstatus_overide == 'cond_published_pending' && $stock_index > -1){
							if( intval($data[$stock_index]) > -1)
								$post_update['post_status'] = 'publish';
                            else
								$post_update['post_status'] = 'pending';
							
				   }elseif($impexp_settings->onimportstatus_overide == 'published'){
							$post_update['post_status'] = 'publish'; 
				   }
				   
				   if($name_index > -1){ 
					  $post_update['post_title'] = $data[$name_index];  
				   }
				   
				   if($slug_index > -1){ 
					  $post_update['post_name'] = urlencode($data[$slug_index]) . '1';  
				   }
				   
				   
				   wp_update_post($post_update);
				   
				  
				   $pr_meta = get_post_meta($id,'_wpsc_product_metadata',true);
				   $dimensions = &$pr_meta['dimensions'];
				   if(!$dimensions){
					  $pr_meta['dimensions'] = array();
					  $dimensions = &$pr_meta['dimensions'];
				   }
				   
				   if($weight_index > -1){
					   $weight = $data[$weight_index];  
					   if($weight){
						   $pr_meta['weight']       = floatval($weight);
						   $pr_meta['weight_unit']  = str_replace(" ","",str_replace($pr_meta['weight'],'',$weight));
						   $pr_meta['weight'] = fn_convert_unit($pr_meta['weight'],$pr_meta['weight_unit'],'pound');
					   }else{
						   $pr_meta['weight']       = '';   
						   $pr_meta['weight_unit']  = '';
					   }
				   }
				   
				   if($height_index > -1){
				   	   $height = $data[$height_index];  
					   if($height){
						   $dimensions['height']       = floatval($height);
						   $dimensions['height_unit']  = str_replace(" ","",str_replace($dimensions['height'],'',$height));
					   }else{
						   $dimensions['height']       = ''; 
						   $dimensions['height_unit']  = '';
					   }
				   }
				   
				   if($width_index > -1){
					   $width = $data[$width_index];  
					   if($height){
						   $dimensions['width']        = floatval($width);
						   $dimensions['width_unit']   = str_replace(" ","",str_replace($dimensions['width'],'',$width));
					   }else{
						   $dimensions['width']        = '';
						   $dimensions['width_unit']   = '';
					   }				   
				   }
				   
				   if($length_index > -1){
					   $length = $data[$length_index];  
					   if($height){
						   $dimensions['length']       = floatval($length);
						   $dimensions['length_unit']  = str_replace(" ","",str_replace($dimensions['length'],'',$length));
					   }else{
						   $dimensions['length']       = '';
						   $dimensions['length_unit']  = '';
					   }
				   }
				   
				   if($taxable_index > -1){
				   	   $pr_meta['wpec_taxes_taxable_amount'] = $data[$taxable_index];  
				   }
				   
				   if($loc_shipping_index > -1){
					   $pr_meta['shipping']['local']         = $data[$loc_shipping_index];  
				   }
				   
				   if($int_shipping_index > -1){
				   	   $pr_meta['shipping']['international'] = $data[$int_shipping_index];  
				   }
				   
				  
				   
				   update_post_meta($id, '_wpsc_product_metadata', $pr_meta);

				   if($categories_names_index >-1){
					  wp_set_object_terms( $id ,  array_map('trim',explode($mvd,$data[$categories_names_index])) , 'wpsc_product_category' );
				   }
				   
				   if($tags_names_index > -1){
					  wp_set_object_terms( $id , array_map('trim',explode($mvd,$data[$tags_names_index])) , 'product_tag' );
				   }
				   
				   if($image_index > -1){
					   if($data[$image_index]){
						   $a_id = insert_image_media($data[$image_index],$id);
						   if($a_id){
							   set_post_thumbnail( $id, $a_id);
						   }
					   }
				   }
				   
				   foreach($custom_fileds as $cfname => $cfield){ 
						if(isset($cf_indexes[$cfname])){
						   if($cfield->type == "term"){
								wp_set_object_terms( $id , array_map('trim', explode($mvd,$data[$cf_indexes[$cfname]])) , $cfield->source );
						   }elseif($cfield->type == "meta"){
							    $value_coder = "";
								if(isset($cfield->options)){
									if(isset($cfield->options->serialization)){
										$value_coder = $cfield->options->serialization;
									}
								}
							    fn_set_meta_by_path( $id , $cfield->source, $data[$cf_indexes[$cfname]],$value_coder);  
						   }elseif($cfield->type == "post"){
						        $wpdb->query( 
									$wpdb->prepare( "UPDATE $wpdb->posts SET ".$cfield->source." = %s WHERE ID = %d", $data[$cf_indexes[$cfname]] ,$id )
								); 
						   }
						}
				   }

				   
				   $import_count ++;
				}
				$n++;			
			}
			fclose($handle);
			
			if($csv_row_processed_count > 0){
				if( isset( $impexp_settings->notfound_setpending )){
					if($impexp_settings->notfound_setpending){
						 if(!empty($imported_ids)){
							$wpdb->query( 
								$wpdb->prepare( "UPDATE $wpdb->posts SET post_status = 'pending' WHERE (post_type LIKE 'wpsc-product' or post_type LIKE 'wpsc-variation') AND NOT ID IN (". implode(",", $imported_ids ) .")")
							 ); 
						 }else{
							$wpdb->query( 
								$wpdb->prepare( "UPDATE $wpdb->posts SET post_status = 'pending' WHERE (post_type LIKE 'wpsc-product' or post_type LIKE 'wpsc-variation')")
							 );
						 }
					}
				}
			}
		}
		
		$custom_fileds   = array();
		loadCustomFields($plem_settings,$custom_fileds);
	}
}


if(isset($_REQUEST['remote_import'])){
  
  if(isset($_REQUEST['file_timestamp'])){
      $this->settings["wpsc_remote_import_timestamp"] = $_REQUEST['file_timestamp'];
	  $this->saveOptions();
  }
  
  echo "Remote import success: " . $import_count ." products processed.";
  exit();
  return;
}


global $categories, $cat_asoc;
$categories = array();
$cat_asoc   = array();

function list_categories_callback($category, $level, $parameters){
   global $categories, $cat_asoc;
   $cat = new stdClass();
   $cat->category_id     = $category->term_id;
   $cat->category_name   = $category->name;
   $cat->category_slug   = urldecode($category->slug);
   $cat->category_parent = $category->parent;
   $categories[] = $cat;   
   $cat_asoc[$cat->category_id] = $cat;
};

$res = wpsc_list_categories('list_categories_callback');


$_num_sample = (1/2).'';
$args = array(
	 'post_type' => array('wpsc-product','wpsc-variation')
	,'posts_per_page' => -1
	,'ignore_sticky_posts' => false
	,'orderby' => $orderby 
	,'order' => $sort_order
	,'fields' => 'ids'
);

if($product_status)
	$args['post_status'] = $product_status;
//else
//	$args['post_status'] = 'any';

if($orderby_key)
   $args['meta_key'] = $orderby_key;

$meta_query = array();

if(isset($product_name) && $product_name){
    $name_postids = $wpdb->get_col("select ID from $wpdb->posts where post_title like '%$product_name%' ");
    $args['post__in'] = empty($name_postids) ? array(-9999) : $name_postids;
}

$tax_query = array();

if($product_category){
 	$tax_query[] =  array(
						'taxonomy' => 'wpsc_product_category',
						'field' => 'id',
						'terms' => $product_category
					);
}



if($product_tag){
	$tax_query[] =  array(
						'taxonomy' => 'product_tag',
						'field' => 'id',
						'terms' => $product_tag
					);
}

if($sku){
	$meta_query[] =	array(
						'key' => '_wpsc_sku',
						'value' => $sku,
						'compare' => 'LIKE'
					);
}

if(!empty($tax_query )){
	$args['tax_query']  = $tax_query;
}

if(!empty($meta_query))
	$args['meta_query'] = $meta_query;


$tags           = array();
foreach((array)get_terms('product_tag',array('hide_empty' => false )) as $pt){
    $t = new stdClass();
	$t->id   = $pt->term_id;
	$t->slug = urldecode($pt->slug);
	$t->name = $pt->name;
	$tags[]     = $t;
}

$count = 0;

$mu_res = 0;
if(isset($_REQUEST["mass_update_val"])){

  $products_query = new WP_Query( $args );
  $count          = $products_query->found_posts;
  $IDS            = $products_query->get_posts();  
 
  foreach ($IDS as $id) {
	  
 	  if($_REQUEST['mass_update_override']){
	    $override_price     = get_post_meta($id,'_wpsc_special_price',true);
		if(is_numeric($override_price)){
			$override_price = floatval($override_price);
			if($_REQUEST["mass_update_percentage"]){
				update_post_meta($id, '_wpsc_special_price', $override_price * (1 + floatval($_REQUEST["mass_update_val"]) / 100) );
			}else{
				update_post_meta($id, '_wpsc_special_price', $override_price + floatval($_REQUEST["mass_update_val"]));
			}
		}
	  }else{
	    $price              = get_post_meta($id,'_wpsc_price',true);
	    if(is_numeric($price)){
			$price = floatval($price);
			if($_REQUEST["mass_update_percentage"]){
				update_post_meta($id, '_wpsc_price', $price * (1 + floatval($_REQUEST["mass_update_val"]) / 100));
			}else{
				update_post_meta($id, '_wpsc_price', $price + floatval($_REQUEST["mass_update_val"]));
			}
		}
	  }
	  $mu_res++;
  }
  wp_reset_postdata();
}

//$products       = array();
$post_statuses = get_post_stati();
$pos_stat = get_post_statuses();        
foreach($post_statuses as $name => $title){
	if(isset($pos_stat[$name]))
		$post_statuses[$name] = $pos_stat[$name];		
}
$args['posts_per_page'] = $limit; 
$args['paged'] = $page_no;

$products_query = new WP_Query( $args );
$count          = $products_query->found_posts;	
$IDS            = $products_query->get_posts();	

if($count == 0){
    $IDS = array();
    unset($args['fields']);
    $products_query = new WP_Query( $args );
    $count          = $products_query->found_posts;	
    while($products_query->have_posts()){
	$products_query->next_post();
        $IDS[] = $products_query->post->ID; 
    }     
    wp_reset_postdata();
}

function array_escape(&$arr){
	foreach($arr as $key => $value){
		if(is_string($value)){
			if(strpos($value, "\n") !== false)
				$arr[$key] = str_replace(array("\n","\r"),array("\\n","\\r") , $value);
		}
	}
}

function product_render(&$IDS, $op,&$df = null){
    global $wpdb, $custom_fileds, $impexp_settings, $custom_export_columns;

	$mvd = $impexp_settings->value_delimiter;
	if(!$mvd)
		$mvd = ",";
	
	$p_ids = is_array($IDS) ? $IDS : array($IDS);
	
	$fcols = array();	
	foreach($custom_fileds as $cfname => $cfield){
		if($cfield->type == "post"){
			$fcols[] = $cfield->source;
		}
	}
	$id_list = implode(",",$p_ids);
	if(!$id_list)
		$id_list = 9999999;
	$raw_data = $wpdb->get_results("select ID, post_name ". (!empty($fcols) ? "," . implode(",",$fcols) : "") ." from $wpdb->posts where ID in (". $id_list .")",OBJECT_K); 
 
	
    $p_n = 0;
	foreach($p_ids as $id) {
	  
	  $prod = new stdClass();
	  $prod->id         = $id;
	  
	  if(!isset($_REQUEST["do_export"])){
		$prod->type           = get_post_type($id);
		$prod->parent         = get_ancestors($id,'wpsc-product');
		if(!empty($prod->parent))
			$prod->parent = $prod->parent[0];
		else
            $prod->parent = null;	
	  }
	  
	  if(fn_show_filed('sku'))
	  $prod->sku        = get_post_meta($id,'_wpsc_sku',true);
	  
	  if(fn_show_filed('slug'))
		$prod->slug           = toUTF8(urldecode($raw_data[$id]->post_name));
	
      if(fn_show_filed('categories'))	
		$prod->categories = wp_get_object_terms( $id, 'wpsc_product_category', array('fields' => 'ids') );
	  
	  if(!isset($_REQUEST["do_export"]) && $prod->parent){
		if(fn_show_filed('categories'))
			$prod->categories = wp_get_object_terms( $prod->parent, 'wpsc_product_category', array('fields' => 'ids') );
	  }
	  
	  if(isset($_REQUEST["do_export"])){
	    if(fn_show_filed('categories')){	
			$prod->categories_names     = implode("$mvd ",wp_get_object_terms( $id, 'wpsc_product_category', array('fields' => 'names') ));
			unset($prod->categories);
		}
	  }
	  
	  
	  
	  
	  if(fn_show_filed('name'))	
		$prod->name               = get_the_title($id);
	  
	  if(fn_show_filed('stock')){	
		  $prod->stock              = get_post_meta($id,'_wpsc_stock',true);
		  if(!$prod->stock)
			$prod->stock = '';
	  }

	  if(fn_show_filed('price')){	
		  $prod->price              = get_post_meta($id,'_wpsc_price',true);
		  
	  }

	  if(fn_show_filed('override_price')){	
		  $prod->override_price     = get_post_meta($id,'_wpsc_special_price',true);
		  
	  }	
	 
	 
	 
	  foreach($custom_fileds as $cfname => $cfield){ 
	   if($cfield->type == "term"){
		if(isset($_REQUEST["do_export"]))
			$prod->{$cfname} = implode("$mvd ",wp_get_object_terms($id,$cfield->source, array('fields' => 'names')));
		else{
			if($prod->parent)
				$prod->{$cfname} = wp_get_object_terms($prod->parent,$cfield->source, array('fields' => 'ids'));
			else
				$prod->{$cfname} = wp_get_object_terms($id, $cfield->source ,  array('fields' => 'ids'));
		}	
	   }elseif($cfield->type == "meta"){
		   
		   $decoder = "";
			if(isset($cfield->options)){
				if(isset($cfield->options->serialization)){
					$decoder = $cfield->options->serialization;
				}	
			}
			
			$prod->{$cfname} = fn_get_meta_by_path( $id , $cfield->source, $decoder);
			
			if(isset($cfield->options)){
				if(isset($cfield->options->format)){
					if($cfield->options->format == "json_array"){	
						if(isset($_REQUEST["do_export"]))
							$prod->{$cfname} = implode($mvd,json_decode($prod->{$cfname}));
						else
							$prod->{$cfname} = implode(",",json_decode($prod->{$cfname}));
					}else if(isset($_REQUEST["do_export"])){
						if(strpos($cfield->options->format,'_array') !== false){
							if(is_array($prod->{$cfname}))
								$prod->{$cfname} = implode("$mvd",$prod->{$cfname});
						}
					}
				}	
			}
			
	   }elseif($cfield->type == "post"){
			$prod->{$cfname} = $raw_data[$id]->{$cfield->source};
	   }
	   
	   if($cfield->options->formater == "checkbox"){
			if($prod->{$cfname} !== null)
				$prod->{$cfname} = $prod->{$cfname} . "";
			else if( isset($cfield->options->null_value)){
				if($cfield->options->null_value){
					$prod->{$cfname} = $prod->{$cfname} = $cfield->options->null_value."";
				}
			}
	   }
	  }

	 
	 
	  if(fn_show_filed('status'))
		$prod->status       = get_post_status($id);
	  
	  $ptrems = get_the_terms($id,'product_tag');
	  
	  if(fn_show_filed('tags')){
		  if(isset($_REQUEST["do_export"])){
			  $prod->tags_names         = null;
			  if($ptrems){
				  foreach((array)$ptrems as $pt){
					if(!isset($prod->tags_names)) 
						$prod->tags_names = array();
						
					$prod->tags_names[] = $pt->name;
				  }
				  $prod->tags_names = implode("$mvd ",$prod->tags_names);
			  }
		  }else{
			  $prod->tags               = null;
			  if($ptrems){
				  foreach((array)$ptrems as $pt){
					if(!isset($prod->tags)) 
						$prod->tags = array();
						
					$prod->tags[] = $pt->term_id;
				  }
			  }
		  }
	  }
	  
	  

	  
	  $pr_meta = get_post_meta($id,'_wpsc_product_metadata',true);
	  $dimensions = &$pr_meta['dimensions'];
	  
	  if(fn_show_filed('weight'))
		$prod->weight       = isset($pr_meta['weight']) ? round( fn_convert_unit($pr_meta['weight'],'pound',$pr_meta['weight_unit']) , 2) .' '. $pr_meta['weight_unit'] : "";
	  if(fn_show_filed('height'))
		$prod->height       = isset($dimensions['height']) ? $dimensions['height'] .' '. $dimensions['height_unit'] : "";
	  if(fn_show_filed('width'))
		$prod->width        = isset($dimensions['width']) ? $dimensions['width']  .' '. $dimensions['width_unit'] : "";
	  if(fn_show_filed('length'))
		$prod->length       = isset($dimensions['length']) ? $dimensions['length'] .' '. $dimensions['length_unit'] : "";
	  if(fn_show_filed('taxable'))
		$prod->taxable      = isset($pr_meta['wpec_taxes_taxable_amount']) ? $pr_meta['wpec_taxes_taxable_amount'] : "";
	  if(fn_show_filed('loc_shipping'))
		$prod->loc_shipping = isset($pr_meta['shipping']) ? $pr_meta['shipping']['local'] : "";
	  if(fn_show_filed('int_shipping'))
		$prod->int_shipping = isset($pr_meta['shipping']) ? $pr_meta['shipping']['international'] : "";
		
	 if(fn_show_filed('image')){	
		  $prod->image = null;
		  
		  if(has_post_thumbnail($id)){
			$thumb_id    = get_post_thumbnail_id($id);
			
			$prod->image = new stdClass;
			$prod->image->id    = $thumb_id;
			
			$prod->image->src   = wp_get_attachment_image_src($thumb_id, 'full');
			if(is_array($prod->image->src))
				$prod->image->src = $prod->image->src[0];
			
			$prod->image->thumb = wp_get_attachment_image_src($thumb_id, 'thumbnail');
			if(is_array($prod->image->thumb))
				$prod->image->thumb = $prod->image->thumb[0];
			
			if(!$prod->image->src)
				$prod->image = null;
			
			if(isset($_REQUEST["do_export"])){
				if($prod->image){
					$prod->image = $prod->image->src;
				}else{
					$prod->image = "";
				}
			}
			
		  }
	 }
 
  
 	  if($op == "json"){
	     if($p_n > 0) echo ",";
	     $out = json_encode($prod);
		 if($out)
			echo $out;
		 else
            echo "/*ERROR json_encode product ID $id*/";		 
	  }elseif($op == "export"){
	     if($p_n == 0){	
		   if($impexp_settings->use_custom_export){
			   fputcsv($df, $custom_export_columns, $impexp_settings->delimiter2);
		   }else{		 
			   $pprops =  (array)$prod;
			   $props = array();
			   foreach( $pprops as $key => $pprop){
				$props[] = $key;
			   }
			   fputcsv($df, $props);
		   }
		 }
		 
		 if($impexp_settings->use_custom_export){
			$eprod = array();
			foreach($custom_export_columns as $prop){
				$eprod[] = &$prod->$prop;
			}
			array_escape($eprod);
			fputcsv($df, $eprod, $impexp_settings->delimiter2);
		 }else{
			$aprod = (array)$prod;
			array_escape($aprod);
			fputcsv($df, $aprod, $impexp_settings->delimiter2);
		 }
		
		
	  }elseif($op == "data"){
		  return $prod;
	  }
	  $p_n++;
	  unset($prod);
	  
	}
};

if(isset($_REQUEST["do_export"])){
	if($_REQUEST["do_export"] = "1"){
	
		$filename = "csv_export_" .(isset($_SERVER['HTTP_HOST']) ? $_SERVER['HTTP_HOST']."_" : ""). date("Y-m-d") . ".csv";
		$now = gmdate("D, d M Y H:i:s");
		header("Expires: Tue, 03 Jul 2001 06:00:00 GMT");
		header("Cache-Control: max-age=0, no-cache, must-revalidate, proxy-revalidate");
		header("Last-Modified: {$now} GMT");

		// force download  
		header("Content-Type: application/force-download");
		header("Content-Type: application/octet-stream");
		header("Content-Type: application/download");

		// disposition / encoding on response body
		header("Content-Disposition: attachment;filename={$filename}");
		header("Content-type:application/csv;charset=UTF-8");
		header("Content-Transfer-Encoding: binary");
		echo "\xEF\xBB\xBF"; // UTF-8 BOM
		
		$df = fopen("php://output", 'w');
	   
	    ///////////////////////////////////////////////////
		product_render($IDS,"export",$df);
		///////////////////////////////////////////////////
		
	    fclose($df);
		
		die();
	    exit;  
	    return;
	}
}


?>
<!DOCTYPE html>
<html>
<head>
<meta http-equiv="Content-Type" content="<?php bloginfo('html_type'); ?>; charset=<?php echo get_option('blog_charset'); ?>" />
<script type="text/javascript">
var _wpColorScheme = {"icons":{"base":"#999","focus":"#2ea2cc","current":"#fff"}};
var ajaxurl = '<?php echo $_SERVER['PHP_SELF']; ?>';
var plugin_version = '<?php echo $plem_settings['plugin_version']; ?>';

var localStorage_clear_flag = false;
function cleanLayout(){
	localStorage.clear();
	localStorage_clear_flag = true;
	doLoad();
	return false;
}
/*
try{
  if(localStorage['dg_wpsc_manualColumnWidths']){
    localStorage['dg_wpsc_manualColumnWidths'] = JSON.stringify( eval(localStorage['dg_wpsc_manualColumnWidths']).map(function(s){
	   if(!s) return null;
	   if(s > 220)
			return 220;
	   return s;	
	}));
  }  
}catch(e){}
*/
</script>
<?php

wp_register_script( 'fictive-script', "www.fictive-script.com/fictive-script.js" );
wp_enqueue_script('fictive-script');

wp_register_style( 'fictive-style', "www.fictive-script.com/fictive-script.css" );
wp_enqueue_style('fictive-style');

global $wp_scripts;
foreach($wp_scripts as $skey => $scripts){
	if(is_array($scripts)){
		if(!empty($scripts)){
			if(in_array("fictive-script",$scripts)){
				foreach($scripts as $script){
					wp_dequeue_script($script);
				}
			}
		}
	}
}

global $wp_styles;
foreach($wp_styles as $skey => $styles){
	if(is_array($styles)){
		if(!empty($styles)){
			if(in_array("fictive-style",$styles)){
				foreach($styles as $style){
					wp_dequeue_style($style);
				}
			}
		}
	}
}
	
wp_enqueue_script('jquery');
wp_enqueue_script('jquery-ui-dialog');
if($use_content_editior){
	wp_enqueue_script('word-count');
	wp_enqueue_script('editor');
	wp_enqueue_script('quicktags');
	wp_enqueue_script('wplink');
	wp_enqueue_script('wpdialogs-popup');
	wp_print_styles('wp-jquery-ui-dialog');
}else{
	wp_print_styles('wp-jquery-ui-dialog');
}

if( $use_image_picker || $use_content_editior || fn_show_filed('image')  || fn_show_filed('gallery')){
	wp_enqueue_media();
}

wp_print_scripts() ;

	
?>
<!--
<script src="<?php echo $productexcellikemanager_baseurl. 'lib/jquery-2.0.3.min.js' ;?>" type="text/javascript"></script>
-->

<script src="<?php echo $productexcellikemanager_baseurl. 'core/m/moment.js'; ?>" type="text/javascript"></script>
<link rel="stylesheet" href="<?php echo $productexcellikemanager_baseurl. 'core/pday/pikaday.css';?>">
<script src="<?php echo $productexcellikemanager_baseurl. 'core/pday/pikaday.js'; ?>" type="text/javascript"></script>
<script src="<?php echo $productexcellikemanager_baseurl. 'core/zc/ZeroClipboard.js'; ?>" type="text/javascript"></script>

<link rel="stylesheet" href="<?php echo $productexcellikemanager_baseurl. 'core/jquery.handsontable.css';?>">
<script src="<?php echo $productexcellikemanager_baseurl. 'core/jquery.handsontable.js'; ?>" type="text/javascript"></script>

<?php

if(isset($plem_settings['enable_delete'])){ 
	if($plem_settings['enable_delete']){
		?>
<link rel="stylesheet" href="<?php echo $productexcellikemanager_baseurl. 'core/removeRow.css';?>">
<script src="<?php echo $productexcellikemanager_baseurl. 'core/removeRow.js'; ?>" type="text/javascript"></script>		
		<?php
	} 
}

?>
<!--
//FIX IN jquery.handsontable.js:

WalkontableTable.prototype.getLastVisibleRow = function () {
  return this.rowFilter.visibleToSource(this.rowStrategy.cellCount - 1);
};

//changed to:

WalkontableTable.prototype.getLastVisibleRow = function () {
  var hsum = 0;
  var sizes_check = jQuery(".htCore tbody tr").toArray().map(function(s){var h = jQuery(s).innerHeight(); hsum += h; return h;});
  var o_size = this.rowStrategy.cellSizesSum;
  
  if(hsum - o_size > 20){
	this.rowStrategy.cellSizes = sizes_check;
	this.rowStrategy.cellSizesSum = hsum - 1;
	this.rowStrategy.cellCount = this.rowStrategy.cellSizes.length;
	this.rowStrategy.remainingSize = hsum - o_size;
  }
	
  return this.rowFilter.visibleToSource(this.rowStrategy.cellCount - 1);
};
-->
<!--
<link rel="stylesheet" href="<?php echo $productexcellikemanager_baseurl.'core/jquery.removeRow.css'; ?>">
<script src="<?php echo $productexcellikemanager_baseurl.'core/jquery.removeRow.js'; ?>" type="text/javascript"></script>
-->

<link rel="stylesheet" href="<?php echo $productexcellikemanager_baseurl.'/lib/chosen.min.css'; ?>">
<script src="<?php echo $productexcellikemanager_baseurl.'lib/chosen.jquery.min.js'; ?>" type="text/javascript"></script>

<link rel="stylesheet" href="<?php echo $productexcellikemanager_baseurl.'assets/style.css'; ?>">

</head>
<body>

<?php if($use_content_editior){ ?>
<div id="content-editor" >
	<div>
	<?php
		$args = array(
			'textarea_rows' => 20,
			'teeny' => true,
			'quicktags' => true,
			'media_buttons' => true
		);
		 
		wp_editor( '', 'editor', $args );
		_WP_Editors::editor_js();
	?>
	<div class="cmds-editor">
	   <a class="metro-button" id="cmdContentSave" ><?php echo __("Save",'productexcellikemanager'); ?></a>
	   <a class="metro-button" id="cmdContentCancel" ><?php echo __("Cancel",'productexcellikemanager'); ?></a>   
	   <div style="clear:both;" ></div>
	</div>
	</div>
</div>
<?php } ?>

<div class="header">
<ul class="menu">
  <?php if(isset($_REQUEST['pelm_full_screen'])){ ?>
  <li>
   <a class="cmdBackToJoomla" href="<?php echo "admin.php?page=productexcellikemanager-wpsc"; ?>" > <?php echo __("Back to Wordpress",'productexcellikemanager'); ?> </a>
  </li>
  <?php } ?>
  
  <li><span class="undo"><button id="cmdUndo" onclick="undo();" ><?php echo __("Undo",'productexcellikemanager'); ?></button></span></li>
  <li><span class="redo"><button id="cmdRedo" onclick="redo();" ><?php echo __("Redo",'productexcellikemanager'); ?></button></span></li>
  <li>
   <span><span> <?php echo __("Export/Import",'productexcellikemanager'); ?> &#9655;</span></span>
   <ul>
     <li><span><button onclick="do_export();return false;" ><?php echo __("Export CSV",'productexcellikemanager'); ?></button></span></li>
     <li><span><button onclick="do_import();return false;" ><?php echo __("Update from CSV",'productexcellikemanager'); ?></button></span></li>
	 <li><span><button onclick="showSettings();return false;" ><?php echo __("Custom import settings",'productexcellikemanager'); ?></button></span></li>
   </ul>
  </li>
  <li>
   <span><span> <?php echo __("Options",'productexcellikemanager'); ?> &#9655;</span></span>
   <ul>
     <li><span><button onclick="if(window.self !== window.top) window.parent.location = 'admin.php?page=productexcellikemanager-settings';  else window.location = 'admin.php?page=productexcellikemanager-settings';" > <?php echo __("Settings",'productexcellikemanager'); ?> </button></span></li>
     <li><span><button onclick="cleanLayout();return false;" ><?php echo __("Clean layout cache...",'productexcellikemanager'); ?></button></span></li>
	 <li><span><a target="_blank" href="<?php echo "http://www.holest.com/excel-like-product-manager-wpecommerce-documentation"; ?>" > <?php echo __("Help",'productexcellikemanager'); ?> </a></span></li>
   </ul>
  </li>
  
  <li style="width:200px;">
  <input style="width:130px;display:inline-block;" type="text" id="activeFind" placeholder="<?php echo __("active data search...",'productexcellikemanager'); ?>" />
  <span style="display:inline-block;" id="search_matches"></span>
  <button id="cmdActiveFind" >&#9655;&#9655;</button> 
  </li>

  <!--
  <li style="font-weight: bold;">
   <span><a style="color: cyan;font-size: 16px;" href="http://holest.com/index.php/holest-outsourcing/joomla-wordpress/virtuemart-excel-like-product-manager.html">Buy this component!</a></span> 
  </li>
  -->
  <li style="float:right;" >
   <table>
     <tr><td rowspan="2" ><?php echo __("Input units",'productexcellikemanager');?>:&nbsp;&nbsp;</td><td><?php echo __("Weight",'productexcellikemanager');?></td><td><?php echo __("Height",'productexcellikemanager');?></td><td><?php echo __("Width",'productexcellikemanager');?></td><td><?php echo __("Length",'productexcellikemanager');?></td></tr> 
	 <tr>
		 
		 <td>
			<select class="save-state" id="weight_unit">
				<option value="pound" selected="selected">pounds</option>
				<option value="ounce">ounces</option>
				<option value="gram">grams</option>
				<option value="kilogram">kilograms</option>
			</select>
		 </td>
		 <td>
			<select class="save-state" id="height_unit">
				<option value="in" selected="selected">inches</option>
				<option value="cm">cm</option>
				<option value="meter">meters</option>
			</select>
		 </td>
		 <td>
			<select class="save-state" id="width_unit">
				<option value="in" selected="selected">inches</option>
				<option value="cm">cm</option>
				<option value="meter">meters</option>
			</select>
		 </td>
		 <td>
			<select class="save-state" id="length_unit">
				<option value="in" selected="selected">inches</option>
				<option value="cm">cm</option>
				<option value="meter">meters</option>
			</select> 
		 </td>
	 </tr> 
   </table>
  </li>
  
  <?php 
    
  if(function_exists('pll_current_language') && function_exists('pll_the_languages')){ ?>
  <li  >
  <span><span> <?php echo isset($_REQUEST['lang']) ? ($_REQUEST['lang'] == "all" ? __("All languages",'productexcellikemanager') : $_REQUEST['lang']) : __("All languages",'productexcellikemanager'); ?> &#9660;</span></span>
  <ul class="polylang_lng_selector">
    <li class="lang-item lang-item-0 lang-item-all current-lang no-translation"><a lang="all" hreflang="all" href=""> <span style="margin-left:0.3em;"><?php echo __("All",'productexcellikemanager'); ?> *</span></a></li>
    <?php 
	pll_the_languages(array('show_flags'=>1,'show_names'=>1,'url'=>'http://ivan/')); 
	?>
  </ul>
  <script type="text/javascript">
  
  
  
    jQuery(document).ready(function(){
		
		
		var curr_url = window.location.href;
		if(curr_url.indexOf('lang=') > -1){
			curr_url = curr_url.split('&lang=');
			var qsi = curr_url[1].indexOf("&");
			if(qsi > -1){
				curr_url[1] = curr_url[1].substr(qsi);
			}else{
				curr_url[1] = "";
			}
			curr_url = curr_url[0] + curr_url[1];
		}
		
		jQuery(".polylang_lng_selector a").each(function(ind){
			
			if(jQuery(this).attr('lang') == "all"){
				jQuery(this).attr('href',curr_url + "&lang=all");
			}else{
				var lng = jQuery(this).attr('href');
				lng = lng.split('lang=')[1];
				jQuery(this).attr('href',curr_url + "&lang=" + lng);
			}
			
		    			
		});
		
		
	});
  </script>
  </li> 
  <?php } ?>
  
</ul>

</div>
<div class="content">
<div class="filter_panel opened">
<span class="filters_label" ><span class="toggler"><span><?php echo __("Filters",'productexcellikemanager');?></span></span></span>
<div class="filter_holder<?php if(fn_show_filed('image')) echo " with-image"; ?>">
  
  <div class="filter_option" id="refresh-button-holder" >
     
	 <div id="product-preview" >
		<p></p>
	 </div>
	
	 <input id="cmdRefresh" type="submit" class="cmd" value="<?php echo __("Refresh",'productexcellikemanager');?>" onclick="doLoad();" />
  </div>
  <div class="refresh-button-spacer"  >
  </div>

  
  
  <div class="filter_option">
     <label><?php echo __("SKU",'productexcellikemanager');?></label>
	 <input placeholder="<?php echo __("Enter part of SKU...",'productexcellikemanager'); ?>" type="text" name="sku" value="<?php echo $sku;?>"/>
  </div>
  
  <div class="filter_option">
     <label><?php echo __("Product Name",'productexcellikemanager');?></label>
	 <input placeholder="<?php echo __("Enter part of name...",'productexcellikemanager'); ?>" type="text" name="product_name" value="<?php echo $product_name;?>"/>
  </div>
  
  <div class="filter_option">
     <label><?php echo __("Category",'productexcellikemanager');?></label>
	 <select data-placeholder="<?php echo __("Chose categories...",'productexcellikemanager'); ?>" class="inputbox" multiple name="product_category" >
		<option value=""></option>
		<?php
		    foreach($categories as $category){
			    $par_ind = '';
				if($category->category_parent){
				  $par = $cat_asoc[$category->category_parent];
				  while($par){
				    $par_ind.= ' - ';
					$par = $cat_asoc[$par->category_parent];
				  }
				}
				echo '<option value="'.$category->category_id.'" >'.$par_ind.$category->category_name.'</option>';
			}
		
		?>
	 </select>
  </div>
  
 <div class="filter_option">
     <label><?php echo __("Tags",'productexcellikemanager');?></label>
	 <select data-placeholder="<?php echo __("Chose tags...",'productexcellikemanager'); ?>" class="inputbox" multiple name="product_tag" >
		<option value=""></option>
		<?php
		    foreach($tags as $tag){
			   echo '<option value="'.$tag->id.'" >'.$tag->name.'</option>';
			}
		
		?>
	 </select>
  </div>
  
  <div class="filter_option">
     <label><?php echo __("Product Status",'productexcellikemanager');?></label>
	 <select data-placeholder="<?php echo __("Chose status...",'productexcellikemanager'); ?>"  class="inputbox" name="product_status" multiple >
	    <option value="" ></option>
		<?php
			foreach($post_statuses as $val => $title){
				?>
				<option value="<?php echo $val; ?>"><?php echo __($title,'productexcellikemanager');?></option>
				<?php
			}

		?>
	 </select>
  </div>
  

  <br/>
  <hr/>
  
  <div class="filter_option mass-update">
	  <label><?php echo __("Mass update by filter criteria: ",'productexcellikemanager'); ?></label> 
	  <input style="width:140px;float:left;" placeholder="<?php echo sprintf(__("[+/-]X%s or [+/-]X",'productexcellikemanager'),'%'); ?>" type="text" id="txtMassUpdate" value="" /> 
	  <button id="cmdMassUpdate" class="cmd" onclick="massUpdate(false);return false;" style="float:right;"><?php echo __("Mass update price",'productexcellikemanager'); ?></button>
	  <button id="cmdMassUpdateOverride" class="cmd" onclick="massUpdate(true);return false;" style="float:right;"><?php echo __("Mass update sales price",'productexcellikemanager'); ?></button>
	  
  </div>
  <div style="clear:both;" class="filter-panel-spacer-bottom" ></div>
  
</div>
</div>

<div id="dg_wpsc" class="hst_dg_view fixed-<?php echo $plem_settings['fixedColumns']; ?>" style="margin-left:-1px;margin-top:0px;overflow: scroll;background:#FBFBFB;">
</div>

</div>
<div class="footer">
 <div class="pagination">
   <label for="txtLimit" ><?php echo __("Limit:",'productexcellikemanager');?></label><input id="txtlimit" class="save-state" style="width:40px;text-align:center;" value="<?php echo $limit;?>" plem="<?php $arr =array_keys($plem_settings);sort($arr);echo $plem_settings[reset($arr)]; ?>"  />
   <?php
       if($limit && ceil($count / $limit) > 1){
	    ?>
	       <input type="hidden" id="paging_page" value="<?php echo $page_no ?>" />	
		   
		<?php
		  if($page_no > 1){
		   ?>
		   <span class="page_number" onclick="setPage(this,1);return false;" ><<</span>
		   <span class="page_number" onclick="setPage(this,'<?php echo ($page_no - 1); ?>');return false;" ><</span>
		   <?php
		  }
		  
	      for($i = 0; $i < ceil($count / $limit); $i++ ){
		    if(($i + 1) < $page_no - 2 ) continue;
			if(($i + 1) > $page_no + 2) {
              echo "<label>...</label>";			  
			  break;
			}
		    ?>
              <span class="page_number <?php echo ($i + 1) == $page_no ? " active " : "";  ?>" onclick="setPage(this,'<?php echo ($i + 1); ?>');return false;" ><?php echo ($i + 1); ?></span>
            <?php			
		  }
		  
		  if($page_no < ceil($count / $limit)){
		   ?>
		   <span class="page_number" onclick="setPage(this,'<?php echo ($page_no + 1); ?>');return false;" >></span>
		   <span class="page_number" onclick="setPage(this,'<?php echo ceil($count / $limit); ?>');return false;" >>></span>
		   <?php
		  }
		  
	   }
   ?>
   <span class="pageination_info"><?php echo sprintf(__("Page %s of %s, total %s products by filter criteria",'productexcellikemanager'),$page_no,ceil($count / $limit),$count); ?></span>
   
 </div>
 
 <span class="note" style="float:right;"><?php echo __("*All changes are instantly autosaved",'productexcellikemanager');?></span>
 <span class="wait save_in_progress" ></span>
 
</div>
<iframe id="frameKeepAlive" style="display:none;"></iframe>

<form id="operationFRM" method="POST" >

</form>

<script type="text/javascript">
var imagePicker   = null;
var galleryPicker = null;

var DG          = null;
var tasks      = {};
var variations_skip = <?php echo json_encode($variations_skip); ?>;
var categories = <?php echo json_encode($categories);?>;
var tags       = <?php echo json_encode($tags);?>;
var asoc_cats = {};
var asoc_tags = {};

var ContentEditorCurrentlyEditing = {};
var ImageEditorCurrentlyEditing = {};

var ProductPreviewBox = jQuery("#product-preview");
var ProductPreviewBox_title = jQuery("#product-preview p");

var SUROGATES  = {};

var sortedBy     = 0;
var sortedOrd    = true;


jQuery(document).ready(function(){
	jQuery('body').addClass("<?php echo basename(__FILE__,".php"); ?>");	
});

window.onbeforeunload = function() {
    try{
		pelmStoreState();
	}catch(e){}
	
    var n = 0;
	for(var key in tasks)
		n++;
     
	if(n > 0){
	  doSave();
	  return "<?php echo __("Transactions ongoing. Plese wait a bit more for them to complete!",'productexcellikemanager');?>";
	}else
	  return;	   
}

for(var c in categories){
  asoc_cats[categories[c].category_id] = categories[c].category_name;
}

for(var t in tags){
  asoc_tags[tags[t].id] = tags[t].name;
}

var keepAliveTimeoutHande = null;
var resizeTimeout
  , availableWidth
  , availableHeight
  , $window = jQuery(window)
  , $dg     = jQuery('#dg_wpsc');
  
$ = jQuery;  

var calculateSize = function () {
   var offset = $dg.offset();
  
  jQuery('div.content').outerHeight(window.innerHeight - jQuery('BODY > DIV.header').outerHeight() - jQuery('BODY > DIV.footer').outerHeight());
  
  availableWidth = jQuery('div.content').innerWidth() - offset.left + $window.scrollLeft() - (jQuery('.filter_panel').innerWidth() + parseInt(jQuery('.filter_panel').css('right'))) + 1;
  availableHeight = jQuery('div.content').innerHeight() + 2;
  jQuery('.filter_panel').css('height',(availableHeight ) + 'px');
  
  if(DG)
	DG.updateSettings({ width: availableWidth, height: availableHeight });
   jQuery('.filters_label .toggler').outerHeight(jQuery('.filter_holder').innerHeight() + 4);
};

$window.on('resize', calculateSize);

calculateSize();

jQuery(document).ready(function(){calculateSize();});
jQuery(window).load(function(){calculateSize();});  

jQuery('#frameKeepAlive').blur(function(e){
     e.preventDefault();
	 return false;
   });
   
function setKeepAlive(){
   if(keepAliveTimeoutHande)
	clearTimeout(keepAliveTimeoutHande);
	
   keepAliveTimeoutHande = setTimeout(function(){
	  jQuery('#frameKeepAlive').attr('src',window.location.href + "&keep_alive=1&diff=" + Math.random());
	  setKeepAlive();
   },30000);
}

function setPage(sender,page){
	jQuery('#paging_page').val(page);
	jQuery('.page_number').removeClass('active');
	jQuery(sender).addClass('active');
	doLoad();
	return false;
}

var pending_load = 0;

function getSortProperty(){
	if(!DG)	
		DG = jQuery('#dg_wpsc').data('handsontable');
	
    if(!DG)
		return "id";
	
	return DG.colToProp( DG.sortColumn);
}

function doLoad(withImportSettingsSave){
    pending_load++;
	if(pending_load < 6){
		var n = 0;
		for(var key in tasks)
			n++;
			
		if(n > 0) {
		  setTimeout(function(){
			doLoad();
		  },2000);
		  return;
		}
	}

    var POST_DATA = {};
	
	POST_DATA.sortOrder            = DG.sortOrder ? "ASC" : "DESC";
	POST_DATA.sortColumn           = getSortProperty();
	POST_DATA.limit                = jQuery('#txtlimit').val();
	POST_DATA.page_no              = jQuery('#paging_page').val();
	
 	POST_DATA.sku                  = jQuery('.filter_option *[name="sku"]').val();
	POST_DATA.product_name         = jQuery('.filter_option *[name="product_name"]').val();
	POST_DATA.product_tag          = jQuery('.filter_option *[name="product_tag"]').val();
	POST_DATA.product_category     = jQuery('.filter_option *[name="product_category"]').val();
	POST_DATA.product_status       = jQuery('.filter_option *[name="product_status"]').val();
	
	if(withImportSettingsSave){
	  var settings = {};
	  jQuery('#settings-panel INPUT[name],#settings-panel TEXTAREA[name],#settings-panel SELECT[name]').each(function(i){
		if(jQuery(this).attr('type') == "checkbox")
			POST_DATA[jQuery(this).attr('name')] = jQuery(this)[0].checked ? 1 : 0;
		else
			POST_DATA[jQuery(this).attr('name')] = jQuery(this).val() instanceof Array ? jQuery(this).val().join(",") : jQuery(this).val(); 
	  });
	  
	  POST_DATA.save_import_settings = 1;
	}
	
    jQuery('#operationFRM').empty();
	
	for(var key in POST_DATA){
		if(POST_DATA[key])
			jQuery('#operationFRM').append("<INPUT type='hidden' name='" + key + "' value='" + POST_DATA[key] + "' />");
	}
	
    jQuery('#operationFRM').submit();
}

function massUpdate(update_override){
    if(!jQuery.trim(jQuery('#txtMassUpdate').val())){
	  alert("<?php echo __("Enter value first!",'productexcellikemanager');?>");
	  return;
	} 

	if(confirm("<?php echo __("Update proiduct price for all products matched by filter criteria (this operation can not be undone)?",'productexcellikemanager');?>")){
		var POST_DATA = {};
		
		POST_DATA.mass_update_val        = parseFloat(jQuery('#txtMassUpdate').val()); 
		POST_DATA.mass_update_percentage = (jQuery('#txtMassUpdate').val().indexOf("%") >= 0) ? 1 : 0;
		POST_DATA.mass_update_override   = update_override ? '1' : '0';
		
		POST_DATA.sortOrder            = DG.sortOrder ? "ASC" : "DESC";
		POST_DATA.sortColumn           = getSortProperty();
		POST_DATA.limit                = jQuery('#txtlimit').val();
		POST_DATA.page_no               = jQuery('#paging_page').val();
		
		POST_DATA.sku                  = jQuery('.filter_option *[name="sku"]').val();
		POST_DATA.product_name         = jQuery('.filter_option *[name="product_name"]').val();
		POST_DATA.product_tag          = jQuery('.filter_option *[name="product_tag"]').val();
		POST_DATA.product_category     = jQuery('.filter_option *[name="product_category"]').val();
		POST_DATA.product_status       = jQuery('.filter_option *[name="product_status"]').val();
		
		
		jQuery('#operationFRM').empty();
		
		for(var key in POST_DATA){
			if(POST_DATA[key])
				jQuery('#operationFRM').append("<INPUT type='hidden' name='" + key + "' value='" + POST_DATA[key] + "' />");
		}
		jQuery('#operationFRM').submit();
	}
}

var saveHandle = null;
var save_in_progress = false;
var id_index = null;

function build_id_index_directory(rebuild){
	if(rebuild)
		id_index = null;
	
	if(!id_index){
		id_index = [];
		var n = 0;
		DG.getData().map(function(s){
		  if(id_index[s.id])
			id_index[s.id].ind = n;
		  else
			id_index[s.id] = {ind:n,ch:[]}; 
		  
		  if(s.parent){
			  if(id_index[s.parent])
				id_index[s.parent].ch.push(n);
			  else
				id_index[s.parent] = {ind:-1,ch:[n]}; 
		  }  			  
		  n++;
		});
	}	
}

function doSave(){
	var update_data = JSON.stringify(tasks); 	   
	save_in_progress = true;
	jQuery(".save_in_progress").show();

	jQuery.ajax({
	url: window.location.href + "&DO_UPDATE=1&diff=" + Math.random(),
	type: "POST",
	dataType: "json",
	data: update_data,
	success: function (data) {
	    build_id_index_directory();
		
		//date.id
		if(data){
			for(var j = 0; j < data.length ; j++){
					if(data[j].surogate){
						var row_ind = SUROGATES[data[j].surogate];
						for(var prop in data[j].full){
							try{
								if (data[j].full.hasOwnProperty(prop)) {
									DG.getSourceDataAtRow(row_ind)[prop] = data[j].full[prop];
								}
							}catch(e){}
						}
					}else if(data[j].full){
						var row_ind = id_index[data[j].id];
						for(var prop in data[j].full){
							try{
								if (data[j].full.hasOwnProperty(prop)) {
									DG.getSourceDataAtRow(row_ind)[prop] = data[j].full[prop];
								}
							}catch(e){}
						}
					}
			}
		}
			
		var updated = eval("(" + update_data + ")");
		for(key in updated){
		 if(tasks[key]){
			 
			var data_ind = - 1;
			if(data){
				for(var j = 0; j < data.length ; j++){
					if(data[j].id == key){
						data_ind = j;
						break;
					}
				}
			}
			
		    //Update inherited values
			try{
				if(data_ind >= 0){
					if(data[data_ind].id && data[data_ind].success){
						var inf = id_index[data[data_ind].id];
						if(inf.ind >= 0 && inf.ch.length > 0){
							for(prop in tasks[key]){
								if(jQuery.inArray(prop, variations_skip) >= 0){
								   for(ch in inf.ch){
									  DG.getData()[inf.ch[ch]][prop] = tasks[key][prop];
								   }
								}
							}	
						}
					}
				}
			}catch(e){} 
		 
			if(JSON.stringify(tasks[key]) == JSON.stringify(updated[key]))
				delete tasks[key];
		 }
		}

		save_in_progress = false;
		jQuery(".save_in_progress").hide();
		
		DG.render();
		jQuery("#rcount").html(DG.countRows() - 1);

	},
	error: function(a,b,c){

		save_in_progress = false;
		jQuery(".save_in_progress").hide();
		callSave();
		
	}
	});
}

function callSave(){
    if(saveHandle){
	   clearTimeout(saveHandle);
	   saveHandle = null;
	}
	
	saveHandle = setTimeout(function(){
	   saveHandle = null;
	   
	   if(save_in_progress){
	       setTimeout(function(){
			callSave();
		   },3000);
		   return;
	   }
       doSave();
	},3000);
}

function undo(){
	DG.undo();
}

function redo(){
	DG.redo();
}

var strip_helper = document.createElement("DIV");
function strip(html){
   strip_helper.innerHTML = html;
   return strip_helper.textContent || strip_helper.innerText || "";
}

var __txt = document.createElement("textarea");

function decodeHtml(html) {
    __txt.innerHTML = html;
    return __txt.value;
}

jQuery(document).ready(function(){

    var CustomSelectEditor = Handsontable.editors.BaseEditor.prototype.extend();
	CustomSelectEditor.prototype.init = function(){
	   // Create detached node, add CSS class and make sure its not visible
	   this.select = jQuery('<select multiple="1" ></select>')
		 .addClass('htCustomSelectEditor')
		 .hide();
		 
	   // Attach node to DOM, by appending it to the container holding the table
	   jQuery(this.instance.rootElement).append(this.select);
	};
	
	// Create options in prepare() method
	CustomSelectEditor.prototype.prepare = function(){
       
		//Remember to invoke parent's method
		Handsontable.editors.BaseEditor.prototype.prepare.apply(this, arguments);
		
		var options = this.cellProperties.selectOptions || [];

		var optionElements = options.map(function(option){
			var optionElement = jQuery('<option />');
			if(typeof option === typeof {}){
			  optionElement.val(option.value);
			  optionElement.html(option.name);
			}else{
			  optionElement.val(option);
			  optionElement.html(option);
			}

			return optionElement
		});

		this.select.empty();
		this.select.append(optionElements);
		
		
		var widg = this.select.next();
		var self = this;
		
		var create = false;
		
		var multiple = this.cellProperties.select_multiple;
		if(typeof multiple === "function"){
			multiple = !!multiple(this.instance,this.row, this.prop);
		}else if(!multiple)
			multiple = false;
		
		var create_option = this.cellProperties.allow_random_input;
		if(typeof create_option === "function"){
			create_option = !!create_option(this.instance,this.row, this.prop);
		}else if(!create_option)
			create_option = false;
		
		if(widg.is('.chosen-container')){
			if(
				!!this.select.data('chosen').is_multiple != multiple
				||
			    !!this.select.data('chosen').create_option != create_option
			   ){
					this.select.chosen('destroy');	
					create = true;
				}
		}else
			create = true;
		
		if(create){
			if(!multiple){
			   this.select.removeAttr('multiple');
			   this.select.change(function(){
					self.finishEditing()
					jQuery('#dg_wpsc').handsontable("selectCell", self.row , self.col);					
			   });
			}else if(!this.select.attr("multiple")){
				this.select.attr('multiple','multiple');
			}
			var chos;
			if(create_option)
				chos = this.select.chosen({
					create_option: true,
					create_option_text: 'value',
					persistent_create_option: true,
					skip_no_results: true
				}).data('chosen');
			else
				chos = this.select.chosen().data('chosen');

			chos.container.bind('keyup', function (event) {
			   if(event.keyCode == 27){
				    self.cancelUpdate = true;
					self.discardEditor();
					self.finishEditing();
					
			   }else if(event.keyCode == 13){
				  var src_inp = jQuery(this).find('LI.search-field > INPUT[type="text"]:first');
				  if(src_inp[0])
					if(src_inp.val() == ''){
					   //event.stopImmediatePropagation();
					   //event.preventDefault();
					   self.discardEditor();
					   self.finishEditing();
					   //self.focus();
					   //self.close();
					   jQuery('#dg_wpsc').handsontable("selectCell", self.row + 1, self.col);
					}
			   }
			});
		}
	};
	
	
	CustomSelectEditor.prototype.getValue = function () {
	   if(this.select.val()){
		   var value = this.select.val();
		   if(!(value instanceof Array)){
			  value = value.split(",")
		   }
		   
		   for(var i = 0; i < value.length; i++){
			  value[i] = jQuery.isNumeric(value[i]) ? parseFloat(value[i]) : value[i];
			  if(value[i]){
				  if(!this.cellProperties.dictionary[value[i]]){
					this.cellProperties.dictionary[value[i]] = value[i];
					this.cellProperties.selectOptions.push({ name: value[i], value: value[i] }); 
				  }
			  }
		   }
		   
		   return value;
	   }else
		  return [];
	};

	CustomSelectEditor.prototype.setValue = function (value) {
	   if(!(value instanceof Array))
		value = value.split(',');
	   this.select.val(value);
	   this.select.trigger("chosen:updated");
	};
	
	CustomSelectEditor.prototype.open = function () {
		//sets <select> dimensions to match cell size
		
		this.cancelUpdate = false;
		
		var widg = this.select.next();
		widg.css({
		   height: jQuery(this.TD).height(),
		   'min-width' : jQuery(this.TD).outerWidth() > 250 ? jQuery(this.TD).outerWidth() : 250
		});
		
		widg.find('LI.search-field > INPUT').css({
		   'min-width' : jQuery(this.TD).outerWidth() > 250 ? jQuery(this.TD).outerWidth() : 250
		});

		//display the list
		widg.show();

		//make sure that list positions matches cell position
		widg.offset(jQuery(this.TD).offset());
	};
	
	CustomSelectEditor.prototype.focus = function () {
	     this.instance.listen();
    };

	CustomSelectEditor.prototype.close = function () {
		 if(!this.cancelUpdate)
			this.instance.setDataAtCell(this.row,this.col,this.select.val(),'edit')
		 
		 this.select.next().hide();
	};
	
	var clonableARROW = document.createElement('DIV');
	clonableARROW.className = 'htAutocompleteArrow';
	clonableARROW.appendChild(document.createTextNode('\u25BC'));
	
	var clonableEDIT = document.createElement('DIV');
	clonableEDIT.className = 'htAutocompleteArrow';
	clonableEDIT.appendChild(document.createTextNode('\u270E'));
	
	var clonableIMAGE = document.createElement('DIV');
	clonableIMAGE.className = 'htAutocompleteArrow';
	clonableIMAGE.appendChild(document.createTextNode('\u27A8'));
		
	var CustomSelectRenderer = function (instance, td, row, col, prop, value, cellProperties) {
	    try{
		  
		   // var WRAPPER = clonableWRAPPER.cloneNode(true); //this is faster than createElement
			var ARROW = clonableARROW.cloneNode(true); //this is faster than createElement

			Handsontable.renderers.TextRenderer(instance, td, row, col, prop, value, cellProperties);
			
			var fc = td.firstChild;
			while(fc) {
				td.removeChild( fc );
				fc = td.firstChild;
			}
			
			td.appendChild(ARROW); 
			
			if(value){
				
				if(cellProperties.select_multiple){ 
					var rval = value;
					if(!(rval instanceof Array))
						rval = rval.split(',');
					
					td.appendChild(document.createTextNode(rval.map(function(s){ 
							if(cellProperties.dictionary[s])
								return cellProperties.dictionary[s];
							else
								return s;
						}).join(', ')
					));
				}else{
					td.appendChild(document.createTextNode(cellProperties.dictionary[value] || value));
				}
				
			}else{
				//jQuery(td).html('');
			}
			
			Handsontable.Dom.addClass(td, 'htAutocomplete');

			if (!td.firstChild) {
			  td.appendChild(document.createTextNode('\u00A0')); //\u00A0 equals &nbsp; for a text node
			}

			if (!instance.acArrowListener) {
			  instance.acArrowHookedToDouble = true;	
			  var eventManager = Handsontable.eventManager(instance);

			  //not very elegant but easy and fast
			  instance.acArrowListener = function (event) {
				if (Handsontable.Dom.hasClass(event.target,'htAutocompleteArrow')) {
				  instance.view.wt.getSetting('onCellDblClick', null, new WalkontableCellCoords(row, col), td);
				}
			  };

			  jQuery(instance.rootElement).on("mousedown.htAutocompleteArrow",".htAutocompleteArrow",instance.acArrowListener);

			  //We need to unbind the listener after the table has been destroyed
			  instance.addHookOnce('afterDestroy', function () {
				eventManager.clear();
			  });

			}else if(!instance.acArrowHookedToDouble){
			  instance.acArrowHookedToDouble = true;	
			  var eventManager = Handsontable.eventManager(instance);	
			  jQuery(instance.rootElement).on("mousedown.htAutocompleteArrow",".htAutocompleteArrow",instance.acArrowListener);
			  //We need to unbind the listener after the table has been destroyed
			  instance.addHookOnce('afterDestroy', function () {
				eventManager.clear();
			  });	
				
			}
		}catch(e){
			jQuery(td).html('');
		}
	};
	///////////////////////////////////////////////////////////////////////////////////////
	jQuery('#content-editor #cmdContentSave').click(function(){
	   DG.setDataAtRowProp( ContentEditorCurrentlyEditing.row, 
	                        ContentEditorCurrentlyEditing.prop, 
							jQuery('#content-editor textarea.wp-editor-area:visible')[0] ? (jQuery('#content-editor textarea.wp-editor-area:visible').val() || '') : (jQuery('#content-editor #editor_ifr').contents().find('BODY').html() || ''),
							''
						  );
							
	   jQuery('#content-editor').css('top','110%');
	});
	
	jQuery('#content-editor #cmdContentCancel').click(function(){
	   jQuery('#content-editor').css('top','110%');
	});
	
	var customContentEditor = Handsontable.editors.BaseEditor.prototype.extend();
	customContentEditor.prototype.open = function () {
		ContentEditorCurrentlyEditing.row  = this.row; 
		ContentEditorCurrentlyEditing.col  = this.col; 
		ContentEditorCurrentlyEditing.prop = this.prop; 
		jQuery('#content-editor').css('top','0%');
		
		DG.selectCell(ContentEditorCurrentlyEditing.row,ContentEditorCurrentlyEditing.col);
	};
	
	customContentEditor.prototype.getValue = function () {
	   if(jQuery('#content-editor textarea.wp-editor-area:visible')[0])
	      return jQuery('#content-editor textarea.wp-editor-area:visible').val() || '';
	   else
          return jQuery('#content-editor #editor_ifr').contents().find('BODY').html() || '';	   
	};

	customContentEditor.prototype.setValue = function (value) {
		jQuery('#content-editor textarea.wp-editor-area').val(value || "");
		jQuery('#content-editor #editor_ifr').contents().find('BODY').html(value || "");
	    this.finishEditing();
	};
	
	customContentEditor.prototype.focus = function () { this.instance.listen();};
	customContentEditor.prototype.close = function () {};
	///////////////////////////////////////////////////////////////////////////////////////////
	
	var customImageEditor = Handsontable.editors.BaseEditor.prototype.extend();
	customImageEditor.prototype.open = function () {
	    ImageEditorCurrentlyEditing.row   = this.row; 
		ImageEditorCurrentlyEditing.col   = this.col; 
		ImageEditorCurrentlyEditing.prop  = this.prop; 
		ImageEditorCurrentlyEditing.value = this.originalValue;
		var SELF = this;
		
		if(this.instance.getSettings().columns[this.col].select_multiple){
			if(!galleryPicker){
				galleryPicker  = wp.media({
					title: 'Product Images (#' + DG.getDataAtRowProp(this.row,'sku') + ' ' + DG.getDataAtRowProp(this.row,'name') + ')',
					multiple: true,
					library: {
						type: 'image'
					},
					button: {
						text: 'Set product images'
					}
				});
				
				galleryPicker.on( 'select', function() {
					var selection = galleryPicker.state().get('selection');
					
					var gval = new Array();
					
					selection.each(function(attachment) {
						
						var val = {};
						val.id    = attachment.attributes.id;
						val.src   = attachment.attributes.url;
						val.thumb = attachment.attributes.sizes.thumbnail.url;
						
						gval.push(val);
						
					});
					
					DG.setDataAtRowProp(ImageEditorCurrentlyEditing.row, ImageEditorCurrentlyEditing.prop, gval, "" );
					DG.selectCell(ImageEditorCurrentlyEditing.row,ImageEditorCurrentlyEditing.col);
				});
				
				galleryPicker.on('open',function() {
					var selection = galleryPicker.state().get('selection');

					//remove all the selection first
					selection.each(function(image) {
						var attachment = wp.media.attachment( image.attributes.id );
						attachment.fetch();
						selection.remove( attachment ? [ attachment ] : [] );
					});

					if(galleryPicker.current_value){
						for(var i = 0; i < galleryPicker.current_value.length; i++){
							if(galleryPicker.current_value[i].id){
								var att = wp.media.attachment( galleryPicker.current_value[i].id );
								att.fetch();
								selection.add( att ? [ att ] : [] );
							}
						}
					}
				});
				
				galleryPicker.on('close',function() {
					DG.selectCell(ImageEditorCurrentlyEditing.row,ImageEditorCurrentlyEditing.col);
				});
				
				
			}else{
				
				var newTitle = jQuery("<h1>" + 'Product Images (#' + DG.getDataAtRowProp(this.row,'sku') + ' ' + DG.getDataAtRowProp(this.row,'name') + ')' + "</h1>");
				jQuery(galleryPicker.el).find('.media-frame-title h1 *').appendTo(newTitle);
				jQuery(galleryPicker.el).find(".media-frame-title > *").remove();
				jQuery(galleryPicker.el).find(".media-frame-title").append(newTitle);
				
			}
			galleryPicker.current_value = this.originalValue;
			galleryPicker.open();
			
		}else{
		
			if(!imagePicker){
				
				imagePicker = wp.media({
					title: 'Featured image(#' + DG.getDataAtRowProp(this.row,'sku') + ' ' + DG.getDataAtRowProp(this.row,'name') + ')',
					multiple: false,
					library: {
						type: 'image'
					},
					button: {
						text: 'Set as featured image'
					}
				});
				
				imagePicker.on( 'select', function() {
					var selection = imagePicker.state().get('selection');
					selection.each(function(attachment) {
						//console.log(attachment);
						
						var val = ImageEditorCurrentlyEditing.value;
						if(!val) val = {};
						val.id    = attachment.attributes.id;
						val.src   = attachment.attributes.url;
						val.thumb = attachment.attributes.sizes.thumbnail.url;
						DG.setDataAtRowProp(ImageEditorCurrentlyEditing.row, ImageEditorCurrentlyEditing.prop, val, "" );
						DG.selectCell(ImageEditorCurrentlyEditing.row,ImageEditorCurrentlyEditing.col);
					});
				});
				
				imagePicker.on('open',function() {
					var selection = imagePicker.state().get('selection');

					//remove all the selection first
					selection.each(function(image) {
						var attachment = wp.media.attachment( image.attributes.id );
						attachment.fetch();
						selection.remove( attachment ? [ attachment ] : [] );
					});

					if(imagePicker.current_value){
						if(imagePicker.current_value.id){
							var att = wp.media.attachment( imagePicker.current_value.id );
							att.fetch();
							selection.add( att ? [ att ] : [] );
						}
					}
				});
				
				imagePicker.on('close',function() {
					DG.selectCell(ImageEditorCurrentlyEditing.row,ImageEditorCurrentlyEditing.col);
				});
				
			}else{
				var newTitle = jQuery("<h1>" + 'Featured image(#' + DG.getDataAtRowProp(this.row,'sku') + ' ' + DG.getDataAtRowProp(this.row,'name') + ')' + "</h1>");
				jQuery(imagePicker.el).find('.media-frame-title h1 *').appendTo(newTitle);
				jQuery(imagePicker.el).find(".media-frame-title > *").remove();
				jQuery(imagePicker.el).find(".media-frame-title").append(newTitle);
			}
			
			imagePicker.current_value = this.originalValue;
			imagePicker.open();
		}
	};
	
	customImageEditor.prototype.getValue = function () {
		return ImageEditorCurrentlyEditing.value;
	};
	
	customImageEditor.prototype.setValue = function ( value ) {
		ImageEditorCurrentlyEditing.value = value instanceof Object || value instanceof Array ? value : this.originalValue; 
		this.finishEditing(); 
	};
	
	customImageEditor.prototype.focus = function () { this.instance.listen();};
	customImageEditor.prototype.close = function () {};
	
	/////////////////////////////////////////////////////////////////////////////////////////////////
	
	
	
	var customContentRenderer = function (instance, td, row, col, prop, value, cellProperties) {
		try{
			
			arguments[5] = strip(value); 
			Handsontable.renderers.TextRenderer.apply(this, arguments);
			Handsontable.Dom.addClass(td, 'htContent');
			td.insertBefore(clonableEDIT.cloneNode(true), td.firstChild);
			if (!td.firstChild) { //http://jsperf.com/empty-node-if-needed
			  td.appendChild(document.createTextNode('\u00A0')); //\u00A0 equals &nbsp; for a text node
			}

			if (!instance.acArrowListener) {
			  instance.acArrowHookedToDouble = true;	
			  var eventManager = Handsontable.eventManager(instance);

			  //not very elegant but easy and fast
			  instance.acArrowListener = function (event) {
				if (Handsontable.Dom.hasClass(event.target,'htAutocompleteArrow')) {
				  instance.view.wt.getSetting('onCellDblClick', null, new WalkontableCellCoords(row, col), td);
				}
			  };

			  jQuery(instance.rootElement).on("mousedown.htAutocompleteArrow",".htAutocompleteArrow",instance.acArrowListener);

			  //We need to unbind the listener after the table has been destroyed
			  instance.addHookOnce('afterDestroy', function () {
				eventManager.clear();
			  });

			}else if(!instance.acArrowHookedToDouble){
			  instance.acArrowHookedToDouble = true;	
			  var eventManager = Handsontable.eventManager(instance);	
			  jQuery(instance.rootElement).on("mousedown.htAutocompleteArrow",".htAutocompleteArrow",instance.acArrowListener);
			  
			  //We need to unbind the listener after the table has been destroyed
			  instance.addHookOnce('afterDestroy', function () {
				eventManager.clear();
			  });	
				
			}
		}catch(e){
			jQuery(td).html('');
		}
	};
	
	var customImageRenderer = function (instance, td, row, col, prop, value, cellProperties) {
		try{
			
			if(DG.getDataAtRowProp(row,'id')){
				if(!value)
					value = DG.getDataAtRowProp(row,prop);
				
				if(value){
					if(value instanceof Array){
							value = value.map(function(v){
								try{
									v = v.src.split("wp-content/uploads/");
									v = v[v.length -1];
								}catch(espl){
									return "";
								}
								return v; 
							});
							value = value.join(",");
					}else{
						try{
							value = value.src.split("wp-content/uploads/");
							value = value[value.length -1];
						}catch(espl){}
					}
				}
			}else
				value = null;
			
			Handsontable.renderers.TextRenderer.apply(this, arguments);
			Handsontable.Dom.addClass(td, 'htImage');
			td.insertBefore(clonableIMAGE.cloneNode(true), td.firstChild);
			if (!td.firstChild) { //http://jsperf.com/empty-node-if-needed
			  td.appendChild(document.createTextNode('\u00A0')); //\u00A0 equals &nbsp; for a text node
			}

			if (!instance.acArrowListener) {
			  instance.acArrowHookedToDouble = true;	
			  var eventManager = Handsontable.eventManager(instance);

			  //not very elegant but easy and fast
			  instance.acArrowListener = function (event) {
				if (Handsontable.Dom.hasClass(event.target,'htAutocompleteArrow')) {
				  instance.view.wt.getSetting('onCellDblClick', null, new WalkontableCellCoords(row, col), td);
				}
			  };

			  jQuery(instance.rootElement).on("mousedown.htAutocompleteArrow",".htAutocompleteArrow",instance.acArrowListener);

			  //We need to unbind the listener after the table has been destroyed
			  instance.addHookOnce('afterDestroy', function () {
				eventManager.clear();
			  });

			}else if(!instance.acArrowHookedToDouble){
			  instance.acArrowHookedToDouble = true;	
			  var eventManager = Handsontable.eventManager(instance);	
			  jQuery(instance.rootElement).on("mousedown.htAutocompleteArrow",".htAutocompleteArrow",instance.acArrowListener);
			  
			  //We need to unbind the listener after the table has been destroyed
			  instance.addHookOnce('afterDestroy', function () {
				eventManager.clear();
			  });	
				
			}
			
		}catch(e){
			jQuery(td).html('');
		}
	};


	
	
	var unitEditor = Handsontable.editors.TextEditor.prototype.extend();
	unitEditor.prototype.getValue = function () {
	    if(!this.INPUT)
			this.INPUT = jQuery(this.TEXTAREA); 
			
		if(!this.INPUT.val())
			return '';
		else
		    var value = this.INPUT.val().replace(' ',''); 
			if(String(parseFloat(value)) == value)
				return this.INPUT.val() + ' ' + this.INPUT.attr("unit");
			else{
				var val   = parseFloat(value);
			    
				var unit  = value.replace(val,'').replace(' ','');
				var units = [];
				if(typeof this.cellProperties.unit == typeof {} || this.cellProperties.unit.indexOf('.') >= 0 || this.cellProperties.unit.indexOf('#') >= 0 )
				  units    = jQuery(this.cellProperties.unit + ', ' + this.cellProperties.unit + ' *').toArray().map(function(o){
				     var o = jQuery(o);
				     if(!o.attr('value'))
					   return null;
				     return o.attr('value');
				  });
				else
				  units[0] = this.cellProperties.unit;
				
                var nunit = '';				
				for(var ind in units){
				  if(units[ind])
					  if(unit.toLowerCase() == units[ind].toLowerCase()){
						nunit = units[ind];
						break;
					  }
				}
			
				if(!nunit)
					nunit = this.INPUT.attr("unit");
				
				return val + ' ' + nunit;
			}
                			
	};
	
	unitEditor.prototype.setValue = function (value) {
		if(!this.INPUT)
			this.INPUT = jQuery(this.TEXTAREA);
			
		this.INPUT.val('');//clean;
	    
		var val  = '';
	    var unit = '';
		
		if(!value || String(parseFloat(value)) == value){
			val   = parseFloat(value);
		    if(typeof this.cellProperties.unit == typeof {} || this.cellProperties.unit.indexOf('.') >= 0 || this.cellProperties.unit.indexOf('#') >= 0 )
			  unit  = jQuery(this.cellProperties.unit).val();
			else
			  unit  = this.cellProperties.unit;
		}else{
		    val   = parseFloat(value); 
		    unit  = value.replace(val,'').replace(' ','');
		}
		
		this.INPUT.attr("unit",unit);
		if(!isNaN(val))
			this.INPUT.val(val);
	};
	
	var centerCheckboxRenderer = function (instance, td, row, col, prop, value, cellProperties) {
	  Handsontable.renderers.CheckboxRenderer.apply(this, arguments);
	  jQuery(td).css({
		'text-align': 'center',
		'vertical-align': 'middle'
	  });
	};

	var centerTextRenderer = function (instance, td, row, col, prop, value, cellProperties) {
	  Handsontable.renderers.TextRenderer.apply(this, arguments);
	  jQuery(td).css({
		'text-align': 'center',
		'vertical-align': 'middle'
	  });
	};
	
	var postStatuses = <?php echo json_encode(array_keys($post_statuses)); ?>;
	
	function arrayToDictionary(arr){
		var dict = {};
		for(var i = 0; i< arr.length; i++){
			dict[(arr[i] + "")] = arr[i];
		}
		return dict;	
	}
	
	var cw = [40,60,160,80,80,80,80,80,80,80,80,80,80,80,80,80,80,80,80,80,80,80,80,80,80,80,80,80,80,80,80,80,80,80,80,80,80,80,80,80,80,80,80,80,80,80,80,80];
	if(localStorage['dg_wpsc_manualColumnWidths']){
		var LS_W = eval(localStorage['dg_wpsc_manualColumnWidths']);
		for(var i = 0; i< LS_W.length; i++){
			if(LS_W[i])
				cw[i] = LS_W[i] || 80;
		}
	}
	
	sortedBy  = null;
	sortedOrd = null;
	
	Handsontable.editors.TextEditor.prototype.setValue = function(e){this.TEXTAREA.value = decodeHtml(e);};
	
	jQuery('#dg_wpsc').handsontable({
	  data: [<?php product_render($IDS,"json");?>],
	  minSpareRows: <?php if(isset($plem_settings['enable_add'])){ if($plem_settings['enable_add']) echo "1"; else echo "0"; } else echo "0";  ?>,
	  colHeaders: true,
	  rowHeaders: true,
	  contextMenu: false,
	  manualColumnResize: true,
	  manualColumnMove: true,
	  columnSorting: true,
	  persistentState: true,
	  variableRowHeights: false,
	  search:true,
	  fillHandle: 'vertical',
	  currentRowClassName: 'currentRow',
      currentColClassName: 'currentCol',
	  fixedColumnsLeft: <?php echo $plem_settings['fixedColumns']; ?>,
	  //stretchH: 'all',
	  colWidths:cw,
	  width: function () {
		if (availableWidth === void 0) {
		  calculateSize();
		}
		return availableWidth ;
	  },
	  height: function () {
		if (availableHeight === void 0) {
		  calculateSize();
		}
		return availableHeight;
	  }
	  ,colHeaders:[
		"ID"
		<?php if(fn_show_filed('sku')) echo  ',"'. __("SKU",'productexcellikemanager').'"';?>
		<?php if(fn_show_filed('name')) echo  ',"'.__("Product Name",'productexcellikemanager').'"';?>
		<?php if(fn_show_filed('slug')) echo  ',"'. __("Slug",'productexcellikemanager').'"';?>
		<?php if(fn_show_filed('categories')) echo  ',"'. __("Category",'productexcellikemanager').'"';?>
		<?php if(fn_show_filed('stock')) echo  ',"'. __("Stock",'productexcellikemanager').'"';?>
		<?php if(fn_show_filed('price')) echo  ',"'. __("Price",'productexcellikemanager').'"';?>
		<?php if(fn_show_filed('override_price')) echo  ',"'. __("Sales price",'productexcellikemanager').'"';?>
		<?php if(fn_show_filed('tags')) echo  ',"'. __("Tags",'productexcellikemanager').'"';?>
		<?php if(fn_show_filed('status')) echo  ',"'. __("Status",'productexcellikemanager').'"';?>
		<?php if(fn_show_filed('weight')) echo  ',"'. __("Weight",'productexcellikemanager').'"';?>
		<?php if(fn_show_filed('height')) echo  ',"'. __("Height",'productexcellikemanager').'"';?>
		<?php if(fn_show_filed('width')) echo  ',"'. __("Width",'productexcellikemanager').'"';?>
		<?php if(fn_show_filed('length')) echo  ',"'. __("Length",'productexcellikemanager').'"';?>
		<?php if(fn_show_filed('image')) echo ',"'.__("Image",'productexcellikemanager').'"';?>
		<?php if(fn_show_filed('taxable')) echo  ',"'. __("Taxable",'productexcellikemanager').'"';?>
		<?php if(fn_show_filed('loc_shipping')) echo  ',"'. __("Local ship.",'productexcellikemanager').'"';?>
		<?php if(fn_show_filed('int_shipping')) echo  ',"'. __("Int. ship.",'productexcellikemanager').'"';?>
		<?php
			foreach($custom_fileds as $cfname => $cfield){ 
			   echo ',"'.addslashes(__($cfield->title,'productexcellikemanager')).'"';
			}
        ?>		
	  ],
	  columns: [
	   { data: "id", readOnly: true, type: 'numeric' }
	  <?php if(fn_show_filed('sku')){ ?>,{ data: "sku" }<?php } ?>
	  <?php if(fn_show_filed('name')){ ?>,{ data: "name", type: 'text', renderer: "html"   }<?php } ?>
	  <?php if(fn_show_filed('slug')){ ?>,{ data: "slug", type: 'text'  }<?php } ?>
	  <?php if(fn_show_filed('categories')){ ?>,{
	    data: "categories",
	    editor: CustomSelectEditor.prototype.extend(),
		renderer: CustomSelectRenderer,
		select_multiple: true,
		dictionary: asoc_cats,
        selectOptions: (!categories) ? [] : categories.map(function(source){
						   return {
							 "name": source.category_name , 
							 "value": source.category_id
						   }
						})
	   }<?php } ?>
	  <?php if(fn_show_filed('stock')){ ?>,{ data: "stock" ,type: 'numeric',format: '0', renderer: centerTextRenderer }<?php } ?>
	  <?php if(fn_show_filed('price')){ ?>,{ data: "price"  ,type: 'numeric',format: '0<?php echo substr($_num_sample,1,1);?>00'}<?php } ?>
	  <?php if(fn_show_filed('override_price')){ ?>,{ data: "override_price"  ,type: 'numeric',format: '0<?php echo substr($_num_sample,1,1);?>00'}<?php } ?>	   
	  <?php if(fn_show_filed('tags')){ ?>,{
	    data: "tags",
	    editor: CustomSelectEditor.prototype.extend(),
		renderer: CustomSelectRenderer,
		select_multiple: true,
		dictionary: asoc_tags,
        selectOptions: (!tags) ? [] : tags.map(function(source){
						   return {
							 "name": source.name , 
							 "value": source.id
						   }
						})
	   }<?php } ?>
	  <?php if(fn_show_filed('status')){ ?>,{ 
	     data: "status",
		 editor: CustomSelectEditor.prototype.extend(),
		 renderer: CustomSelectRenderer,
		 select_multiple: false,
		 dictionary: arrayToDictionary(postStatuses),
		 selectOptions:postStatuses
	   }<?php } ?>
	  <?php if(fn_show_filed('weight')){ ?>,{ data: "weight", editor: unitEditor, unit: '#weight_unit' }<?php } ?>
	  <?php if(fn_show_filed('height')){ ?>,{ data: "height", editor: unitEditor, unit: '#height_unit' }<?php } ?>
	  <?php if(fn_show_filed('width')){ ?>,{ data: "width", editor: unitEditor, unit: '#width_unit' }<?php } ?>
	  <?php if(fn_show_filed('length')){ ?>,{ data: "length", editor: unitEditor, unit: '#length_unit' }<?php } ?>
	  
	  <?php if(fn_show_filed('image')){ ?>,{ 
		data: "image", 
        editor: customImageEditor.prototype.extend(),
		renderer: customImageRenderer
	  }<?php } ?>

	  <?php if(fn_show_filed('taxable')){ ?>,{ data: "taxable", type: 'numeric',format: '0<?php echo substr($_num_sample,1,1);?>00' }<?php } ?>
	  <?php if(fn_show_filed('loc_shipping')){ ?>,{ data: "loc_shipping", type: 'numeric',format: '0<?php echo substr($_num_sample,1,1);?>00' }<?php } ?>
	  <?php if(fn_show_filed('int_shipping')){ ?>,{ data: "int_shipping", type: 'numeric',format: '0<?php echo substr($_num_sample,1,1);?>00' }<?php } ?>
	  
  <?php foreach($custom_fileds as $cfname => $cfield){ 
		 if($cfield->type == "term"){?>
			,{ 
			   data: "<?php echo $cfield->name;?>",
			   editor: CustomSelectEditor.prototype.extend(),
			   renderer: CustomSelectRenderer,
			   select_multiple: <?php echo $cfield->options->multiple ? "true" : "false" ?>,
			   allow_random_input: <?php echo $cfield->options->allownew ? "true" : "false" ?>,
			   selectOptions: <?php echo json_encode($cfield->terms);?>,
				   dictionary: <?php
                      $asoc_trm = new stdClass();
					  foreach($cfield->terms as $t){
						$asoc_trm->{$t->value} = $t->name;
					  } 
					  echo json_encode($asoc_trm);
				   ?>
			 }
 <?php }else{ ?>
			,{ 
			   data: "<?php echo $cfield->name;?>"
			   <?php
			   if($cfield->options->formater == "content"){?>
				, editor: customContentEditor.prototype.extend()
				, renderer: customContentRenderer
			   <?php
			   }elseif($cfield->options->formater == "checkbox"){
				  echo ',type: "checkbox"'; 
				  echo ',renderer: centerCheckboxRenderer';
				  //if($cfield->options->checked_value) echo ',checkedTemplate: "'.$cfield->options->checked_value.'"'; 
				  if($cfield->options->checked_value || $cfield->options->checked_value === "0") echo ',checkedTemplate: "'.$cfield->options->checked_value.'"'; 
				  if($cfield->options->unchecked_value || $cfield->options->unchecked_value === "0") echo ',uncheckedTemplate: "'.$cfield->options->unchecked_value.'"';
			   }elseif($cfield->options->formater == "dropdown"){
				  echo ',type: "autocomplete", strict: ' . ($cfield->options->strict ? "true" : "false");
				  echo ',source:' ;
				  $vals = str_replace(", ",",",$cfield->options->values);
				  $vals = str_replace(", ",",",$vals);
				  $vals = str_replace(" ,",",",$vals);
				  $vals = str_replace(", ",",",$vals);
				  $vals = str_replace(" ,",",",$vals);
				  $vals = explode(",",$vals);
				  echo json_encode($vals);
			   }elseif($cfield->options->formater == "date"){
				      echo ',type: "date"';
					  echo ',dateFormat: "'.$cfield->options->format.'"';
					  echo ',correctFormat: true';
					  echo ',defaultDate: "'.$cfield->options->default.'"';
			   }elseif($cfield->options->formater == "image"){
				  ?>
					,editor: customImageEditor.prototype.extend(),
					renderer: customImageRenderer,
					select_multiple: false					  
				  <?php
			   }elseif($cfield->options->formater == "gallery"){
				  ?>
					,editor: customImageEditor.prototype.extend(),
					renderer: customImageRenderer,
					select_multiple: true					  
				  <?php
			   }else{
				  if($cfield->options->format == "integer") echo  ',type: "numeric"';
				  elseif($cfield->options->format == "decimal") echo  ',type: "numeric", format: "0'.substr($_num_sample,1,1).'00"';
			   }
			   ?>				   
			 }
		<?php }
		} ?>

	  
	  ]
	  ,outsideClickDeselects: false
	  <?php

		if(isset($plem_settings['enable_delete'])){ 
			if($plem_settings['enable_delete']){
				?>
		
		,removeRowPlugin: true
		,beforeRemoveRow: function (index, amount){
			 if(!DG.getDataAtRowProp(index,"id"))
				 return false;
			 if(confirm("<?php echo __("Remove product",'productexcellikemanager');?> <?php echo __("SKU",'productexcellikemanager');?>:" + DG.getDataAtRowProp(index,"sku") + ", <?php echo __("Name",'productexcellikemanager');?>: '" + DG.getDataAtRowProp(index,"name") + "', ID:" +  DG.getDataAtRowProp(index,"id") + "?")){
				
				var id = DG.getDataAtRowProp(index,"id");
				
				if(!tasks[id])
					tasks[id] = {};
				
				tasks[id]["DO_DELETE"] = 'delete';
				
				callSave();
				
				return true;		 
			 }else
				return false;
		
	    }
				<?php
			} 
		}

	  ?>
	  ,afterChange: function (change, source) {
	    if(!change)   
			return;
	    if(!DG)
			DG = jQuery('#dg_wpsc').data('handsontable');
		
		if (source === 'loadData' || source === 'skip' || source === 'external') return;
		
		if(!change[0])
				return;
			
		if(!jQuery.isArray(change[0]))
				change = [change];
		
		change.map(function(data){
			if(!data)
			  return;
		    
			if ([JSON.stringify(data[2])].join("") == [JSON.stringify(data[3])].join(""))
				return;
		  
			var id = null;
			if(DG.getData()[data[0]])
				id = DG.getDataAtRowProp(parseInt(data[0]),"id");
			
			if(!id){
				if(!data[3])
					return;
				var surogat = "s" + parseInt( Math.random() * 10000000); 
				DG.getSourceDataAtRow(data[0])['id'] = surogat;
				id = surogat;
				SUROGATES[surogat] = data[0];
			}
			
			var prop = data[1];
			var val  = data[3];
			if(!tasks[id])
				tasks[id] = {};
			tasks[id][prop] = val;
			tasks[id]["dg_index"] = data[0];
		});
		
		callSave();
	  }
	  ,afterColumnResize: function(currentCol, newSize){
		
	  }
	  ,beforeColumnSort: function (column, order){
		  
		  if(explicitSort)
			  return;
		  
		  if(DG){
			if(DG.getSelected()){
				DG.sortColumn = DG.getSelected()[1];
				
				if(sortedBy == DG.sortColumn)
					DG.sortOrder = !sortedOrd;
				else
					DG.sortOrder = true;
				
				sortedBy  = DG.sortColumn;
	            sortedOrd = DG.sortOrder;
				
			}
		  }
		
	  }
	  ,cells: function (row, col, prop) {
	    if(!DG)
			DG = jQuery('#dg_wpsc').data('handsontable');
			
		if(!DG)
			return;
		
		this.readOnly = false;
			
	    var row_data = DG.getData()[row]; 
		if(!row_data)
			return;
		
		if(prop == "id"){
			this.readOnly = true;
			return;
		}
		
		
		try{	
			if(row_data.parent){
				if(jQuery.inArray(prop, variations_skip) >= 0){
					this.readOnly = true;
				}
			}
		}catch(ex){}
		
	  },afterSelection:function(r, c, r_end, c_end){
			var img = DG.getDataAtRowProp(r,'image');
			if(img){
				if(img.src){
					ProductPreviewBox.css("background-image","url(" + img.src + ")");	
				}else
					ProductPreviewBox.css("background-image","");					
			}else
				ProductPreviewBox.css("background-image","");	

			ProductPreviewBox.attr('row', r);
			
			ProductPreviewBox_title.text("#" + (DG.getDataAtRowProp(r,'sku') || "") + "" + (DG.getDataAtRowProp(r,'name') || ""));	
	 }


	  
	});
	
	jQuery(document).on("click","#product-preview",function(e){
		try{
			if(DG.propToCol("image") > -1 && jQuery(this).attr("row")){
				DG.selectCell(parseInt(jQuery(this).attr("row")), DG.propToCol("image"));
				DG.getActiveEditor().beginEditing();
			}
		}catch(e){
		//	
		}
	});

	
	if(!DG)
		DG = jQuery('#dg_wpsc').data('handsontable');
	
	sortedBy  = DG.sortColumn;
	sortedOrd = DG.sortOrder;
	
	setKeepAlive();
	
	jQuery('.filters_label').click(function(){
		if( jQuery(this).parent().is('.opened')){
			jQuery(this).parent().removeClass('opened').addClass('closed');
		}else{
			jQuery(this).parent().removeClass('closed').addClass('opened');
		}
		jQuery(window).trigger('resize');
	});
	
	jQuery(window).load(function(){
		jQuery(window).trigger('resize');
	});
	
	
	if('<?php echo $product_category;?>') jQuery('.filter_option *[name="product_category"]').val("<?php if($product_category)echo implode(",",$product_category);?>".split(','));
	if('<?php echo $product_tag;?>') jQuery('.filter_option *[name="product_tag"]').val("<?php if($product_tag) echo implode(",",$product_tag);?>".split(','));
	if('<?php echo $product_status;?>') jQuery('.filter_option *[name="product_status"]').val("<?php if($product_status) echo implode(",",$product_status);?>".split(','));
	
	jQuery('SELECT[name="product_category"]').chosen();
	jQuery('SELECT[name="product_tag"]').chosen();
	jQuery('SELECT[name="product_status"]').chosen();
	
	jQuery("<div class='grid-bottom-spacer' style='min-height:120px;'></div>").insertAfter( jQuery("table.htCore"));		
	
	
	function screenSearch(select){
		if(DG){
			var self = document.getElementById('activeFind');
			var queryResult = DG.search.query(self.value);
			if(select){
				if(!queryResult.length){
					jumpIndex = 0;
					return;
				}
				if(jumpIndex > queryResult.length - 1)
					jumpIndex = 0;
				DG.selectCell(queryResult[jumpIndex].row,queryResult[jumpIndex].col,queryResult[jumpIndex].row,queryResult[jumpIndex].col,true);
				jQuery("#search_matches").html(("" + (jumpIndex + 1) + "/" + queryResult.length) || "");
				jumpIndex ++;
			}else{
				jQuery("#search_matches").html(queryResult.length || "");
				DG.render();
				jumpIndex = 0;
			}
		}
	}
	
	Handsontable.Dom.addEvent(document.getElementById('activeFind') , 'keyup', function (event) {
		if(event.keyCode == 13){
			screenSearch(true);
		}else{
			screenSearch(false);
		}
	});
	
	jQuery("#cmdActiveFind").click(function(){
		screenSearch(true);
	});
	
	
});



  <?php
    if($mu_res){
	   $upd_val = $_REQUEST["mass_update_val"].(  $_REQUEST["mass_update_percentage"] ? "%" : "" );
	   ?>
	   jQuery(window).load(function(){
	   alert('<?php echo sprintf(__("Proiduct price for all products matched by filter criteria is changed by %s",'productexcellikemanager'),$upd_val); ?>');
	   });
	   <?php
	}
	
	if($import_count){
	   ?>
	   jQuery(window).load(function(){
	   alert('<?php echo sprintf(__("%s products updated prices form imported file!",'productexcellikemanager'),$import_count); ?>');
	   });
	   <?php
	}
	
  ?>


function do_export(){
    var link = window.location.href + "&do_export=1" ;
   
    var QUERY_DATA = {};
	QUERY_DATA.sortOrder            = DG.sortOrder ? "ASC" : "DESC";
	QUERY_DATA.sortColumn           = getSortProperty();
	
	QUERY_DATA.limit                = "9999999999";
	QUERY_DATA.page_no              = "1";
	
	QUERY_DATA.sku                  = jQuery('.filter_option *[name="sku"]').val();
	QUERY_DATA.product_name         = jQuery('.filter_option *[name="product_name"]').val();
	QUERY_DATA.product_tag          = jQuery('.filter_option *[name="product_tag"]').val();
	QUERY_DATA.product_category     = jQuery('.filter_option *[name="product_category"]').val();
	QUERY_DATA.product_status       = jQuery('.filter_option *[name="product_status"]').val();
	
	for(var key in QUERY_DATA){
		if(QUERY_DATA[key])
			link += ("&" + key + "=" + QUERY_DATA[key]);
	}
	
	window.location =  link;
    return false;
}

function do_import(){
    var import_panel = jQuery("<div class='import_form'><form method='POST' enctype='multipart/form-data'><span><?php echo __("Select .CSV file to update prices/stock from.<br>(To void price, stock or any available field update remove coresponding column from CSV file)",'productexcellikemanager'); ?></span><br/><label for='file'><?php echo __("File:",'productexcellikemanager'); ?></label><input type='file' name='file' id='file' /><br/><br/><button class='cmdImport' ><?php echo __("Import",'productexcellikemanager'); ?></button><button class='cancelImport'><?php echo __("Cancel",'productexcellikemanager'); ?></button></form><br/><p>*When adding product via CSV import make sure 'id' is empty</p><p>*If you edit from MS Excel you must save using 'Save As', for 'Sava As Type' choose 'CSV Comma Delimited (*.csv)'. Otherwise MS Excel fill save in incorrect format!</p></div>"); 
    import_panel.appendTo(jQuery("BODY"));
	
	import_panel.find('.cancelImport').click(function(){
		import_panel.remove();
		return false;
	});
	
	import_panel.find('.cmdImport').click(function(){
		if(!jQuery("#file").val()){
		  alert('<?php echo __("Enter value first!",'productexcellikemanager');?>');
		  return false;
		}
	    var frm = import_panel.find('FORM');
		var POST_DATA = {};
		
		POST_DATA.do_import            = "1";
		POST_DATA.sortOrder            = DG.sortOrder ? "ASC" : "DESC";
		POST_DATA.sortColumn           = getSortProperty();
		POST_DATA.limit                = jQuery('#txtlimit').val();
		POST_DATA.page_no               = jQuery('#paging_page').val();
		
		POST_DATA.sku                  = jQuery('.filter_option *[name="sku"]').val();
		POST_DATA.product_name         = jQuery('.filter_option *[name="product_name"]').val();
		POST_DATA.product_tag          = jQuery('.filter_option *[name="product_tag"]').val();
		POST_DATA.product_category     = jQuery('.filter_option *[name="product_category"]').val();
		POST_DATA.product_status       = jQuery('.filter_option *[name="product_status"]').val();
		
		for(var key in POST_DATA){
			if(POST_DATA[key])
				frm.append("<INPUT type='hidden' name='" + key + "' value='" + POST_DATA[key] + "' />");
		}
			
		frm.submit();
		return false;
	});
}

</script>
<script src="<?php echo $productexcellikemanager_baseurl.'lib/script.js'; ?>" type="text/javascript"></script>
<?php
   $settp_path = realpath(dirname( __FILE__ ). DIRECTORY_SEPARATOR . ".." . DIRECTORY_SEPARATOR . "lib" . DIRECTORY_SEPARATOR . 'settings_panel.php');
   include($settp_path);
   if( $use_image_picker || $use_content_editior || fn_show_filed('image')){
	   wp_print_styles( 'media-views' );
	   wp_print_styles( 'imgareaselect' );
	   wp_print_media_templates();
   }

?>
</body>
</html>
<?php
exit;
?>

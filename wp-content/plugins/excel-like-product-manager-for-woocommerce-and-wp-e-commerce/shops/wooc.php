<?php
/*
 * Title: WooCommerce
 * Origin plugin: woocommerce/woocommerce.php,envato-wordpress-toolkit/woocommerce.php
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


if(ini_get('max_execution_time') < 300)
	set_time_limit ( 300 ); //1.5 min

global $start_time, $max_time, $mem_limit, $res_limit_interupted, $resume_skip;
$resume_skip          = 0;  
$res_limit_interupted = 0;

if(isset($_REQUEST["resume_skip"]))
	$resume_skip = intval($_REQUEST["resume_skip"]);

$start_time     = time();
$max_time       = ini_get('max_execution_time') / 2;
if(!$max_time)
   $max_time    = 30;


global $wpdb;
global $wooc_fields_visible, $variations_fields;
global $custom_fileds, $use_image_picker, $use_content_editior;

$use_image_picker    = false;
$use_content_editior = false;

if(isset($_REQUEST['keep_alive'])){
	if($_REQUEST['keep_alive']){
		return;
	}
}

function getRequestVar($name){
	if(isset($_REQUEST[$name]))
		return $_REQUEST[$name];
	else
		return "";
}



if(isset($_REQUEST["set_mem_limit"])){
	update_option("plem_mem_limit" . ini_get('memory_limit'),$_REQUEST["set_mem_limit"],false);
	
	
	?><!DOCTYPE html>
		<html>
		<head>
		<style type="text/css">
			html, body{
				background:#505050;
				color:white;
				font-family:sans-serif;	
			}
		</style>
		</head>
		<body>
		  <script type="text/javascript">
				window.location = window.location.href.split("&set_mem_limit")[0];
		  </script>
		</body>
		</html>
	<?php
	die;
	return;
}

$mem_limit = get_option("plem_mem_limit" . ini_get('memory_limit'), 0); 
global $start_mem;
$start_mem = memory_get_usage();

function getMemAllocated(){
	global $start_mem;
	return memory_get_usage() - $start_mem;
} 

if(isset($_REQUEST["memtest"])){
	header('Content-Type: text/text; charset=' . get_option('blog_charset'), true);
	$x = array_fill ( 0 , intval($_REQUEST["memtest"]) , false );
	echo "OK ". count($x);
	die;
	return;
}elseif($mem_limit == 0){
	wp_enqueue_script('jquery');
	?>
	<!DOCTYPE html>
	<html>
		<head>
		<style type="text/css">
			html, body{
				background:#505050;
				color:white;
				font-family:sans-serif;	
			}
		</style>
		<?php wp_print_scripts() ; ?>
		</head>
		<body>
		<h3>Inspecting environment...</h3>
		<p>Please wait a moment!</p>
	<script type="text/javascript">
	var curr_memtest = 100000;
	var test_jump    = curr_memtest;
	function checkmem(){
		jQuery.ajax({
			url: window.location.href + "&memtest=" + (curr_memtest + test_jump),
			type: "GET",
			success: function (data) {
				if(curr_memtest > 3200000){
					window.location = window.location.href + "&set_mem_limit=" + (curr_memtest * 80);
					return;
				}
				
				if(data){
					
					if(!jQuery("span.memtest-info")[0]){
						jQuery("BODY").append(jQuery("<span class='memtest-info' ></span>"));
					}
					
					jQuery("span.memtest-info").html("Mem test passed: " + curr_memtest );
					
					curr_memtest += test_jump;
					checkmem();
				}else{
					if(test_jump == 25000){
						window.location = window.location.href + "&set_mem_limit=" + (curr_memtest * 80); 
					}else{
						test_jump = test_jump / 2;
						checkmem();
					}
				}
			},
			error:function (a,b,c) {
				
				if(test_jump == 25000){
						window.location = window.location.href + "&set_mem_limit=" + (curr_memtest * 80); 
				}else{
					
					curr_memtest -= test_jump;
					test_jump = test_jump / 2;
					curr_memtest += test_jump;
					
					checkmem();
				}
			}
		});
	};
	checkmem();
	</script>
	</body>
	</html>
	<?php
	die;
}

//ob_clean();
//header('Content-Type: text/html; charset=' . get_option('blog_charset'), true);

$wooc_fields_visible = array();
if(isset($plem_settings['wooc_fileds'])){
	foreach(explode(",",$plem_settings['wooc_fileds']) as $I => $val){
		if($val)
			$wooc_fields_visible[$val] = true;
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
	global $wooc_fields_visible, $custom_export_columns, $impexp_settings;;
	
	if(empty($wooc_fields_visible))
		return true;
	
	if($impexp_settings->use_custom_export && getRequestVar("do_export")){
		if(in_array($name,$custom_export_columns))
			return true;
		else
			return false;
	}else if($name=="categories_paths" && $impexp_settings->use_custom_export && getRequestVar("do_export")){
		return true;
	}
		
	if(isset($wooc_fields_visible[$name]))
		return $wooc_fields_visible[$name];
	
	$wooc_fields_visible[$name] = false;
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

function is_assoc($arr){
	if(empty($arr))
		return false;
	$ak = array_keys($arr);
	if(is_string($ak[0]))
	  return true;		
	return !(end($ak ) === count($arr) - 1); 
}

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




global $sitepress;
if(isset($sitepress)){
	$sitepress->switch_lang($sitepress->get_default_language(), true);
}

function pelm_on_product_update($id, $post = NULL){
	global $woocommerce_wpml;
	if(isset($woocommerce_wpml)){
		 global $pagenow;
		 $pagenow = 'post.php';
			
		 if(isset($woocommerce_wpml->sync_product_data)){
         	   if(method_exists($woocommerce_wpml->sync_product_data,"synchronize_products")){
					$woocommerce_wpml->sync_product_data->synchronize_products($id, $post === NULL ? get_post( $id ) : $post );
					return;
			   }
 		 }
			
		 if(isset($woocommerce_wpml->products)){
			if(method_exists($woocommerce_wpml->products,"sync_post_action")){
				$woocommerce_wpml->products->sync_post_action($id, $post === NULL ? get_post( $id ) : $post );
				return;
			}
		 }
	}
}

function asArray($val){
	if(!is_array($val)){
		return array($val);
	}
	return $val;
}

$variations_fields = array(
    'stock',
	'slug',
	'sku',
	'shipping_class',
	"weight",
	"length",
	"width",
	"height",
    "price",
	"override_price",
	"stock_status",
	"backorders",
	"virtual",
	"downloadable",
	"downloads",
	"status"
); 


function importTerm(&$term){
	
	
	$is_json = getRequestVar("do_import_terms_format") == "json";
	
	
	
	if(!$term)
		return 0;
	
	if($term->slug){
		$term->slug = sanitize_title($term->slug);
	}
	
	global $wpdb;
	
	$find = array();
	
	if($term->term_id !== null){
		$v = $term->term_id;
		$find[] = $wpdb->prepare(" tt.term_id = %d ",$v);
	}
	
	if($term->slug !== null){
		$v = $term->slug;
		$find[] = $wpdb->prepare(" t.slug LIKE %s ",$v);
	}
	
	if(empty($find)){
		if($term->name !== null){
			$v = $term->name;
			$find[] = $wpdb->prepare(" t.name LIKE %d ",$v);
		}
	}
	
	
	$existing = null;

	
	
	if(!empty($find)){
		
		$q = "SELECT t.term_id, tt.parent, t.name, t.slug,tt.taxonomy,tt.description  FROM 
						$wpdb->term_taxonomy as tt
						LEFT JOIN
						$wpdb->terms as t on t.term_id = tt.term_id 
						WHERE  
						tt.term_id > 1
						AND ( ". implode(" OR ", $find) ." )";
						
		if($term->taxonomy !== null){
			$v = $term->taxonomy;
			$q .= $wpdb->prepare(" AND tt.taxonomy LIKE %s ",$v);
		}				

	   
		  
		try{
			$res      = $wpdb->get_results($q,OBJECT_K);
			if(!empty($res)){
				foreach($res as $item){
					$existing = $item;
					break;
				}
			}
		}catch(Exception $ex){
			echo $ex->getMessage();
			return -1;
		}				
	}
	
	
	
	if($existing){
		$upd = array();
		
		if($term->parent && !is_numeric($term->parent)){
			$par_ids = $wpdb->get_col($wpdb->prepare("SELECT term_id FROM $wpdb->terms WHERE name LIKE %s OR slug LIKE %s",$term->parent,$term->parent));
			if(count($par_ids) !== 1){
				echo "\nCan not locate parent for term " . json_encode($term) . "!";  
				return -1; 
			}
			$term->parent = $par_ids[0];
		}
		
		foreach($existing as $prop => $value){
			if($term->{$prop} !== null){
				 if($term->{$prop} != $existing->{$prop}){
					 
									 
					 if($prop == "taxonomy"){
						 if(!taxonomy_exists($term->{$prop})){
							 echo "\nTaxonomy " . $term->{$prop} . " is not defined! (for term: ". $existing->name .")";
							 return -1;
							 return;
						 }
					 }
					 $uq    = " " . ((($prop == "name" || $prop == "slug" || $prop == "term_id") ? "t." : "tt.") . $prop . " = %s");
					 $upd[] = $wpdb->prepare($uq, $value);
				 }
			}
		}
		
		if(!empty($upd)){
			
			
			$q = "UPDATE
					$wpdb->term_taxonomy as tt
					LEFT JOIN
					$wpdb->terms as t ON t.term_id = tt.term_id
					SET
					". implode(", ",$upd) ."
					WHERE
					t.term_id = " . $existing->term_id;
		   	try{
				$result = $wpdb->query($q);
				
				if($result && $term->term_id !== null){
					if($term->term_id != $existing->term_id){
						
						$wpdb->query($wpdb->prepare(
											"UPDATE $wpdb->termmeta 
											 SET 
												term_id = %d 
											 WHERE 
												term_id = %d ;"
										   ,$term->term_id, $existing->term_id 
										));
										
						$wpdb->query($wpdb->prepare(
											"UPDATE $wpdb->term_taxonomy
											 SET 
												term_id = %d,
                                                term_taxonomy_id = %d 												
											 WHERE 
												term_id = %d ;"
										   ,$term->term_id,$_POST["new_term_id"], $existing->term_id 
										));		
										
						$wpdb->query($wpdb->prepare(
											"UPDATE $wpdb->term_relationships
											 SET 
											    term_taxonomy_id = %d 												
											 WHERE 
												term_taxonomy_id = %d ;"
										   ,$term->term_id, $existing->term_id 
										));
					}
				}
				
				
			}catch(Exception $ex){
				echo "\nErroe updating term " .  $ex->getMessage() . " " . json_encode($term);
				return -1;
			}
			
			if(!$is_json){
				echo "\nUpdate done: " . $term->name;
			}
			
		}else{
			//nothing to do
		}
	}else{
		if(!$term->taxonomy){
			echo "\nCan not create term without taxonomy! " . json_encode($term);  
			return -1;
		}
		
		if(!taxonomy_exists($term->taxonomy)){
			echo "\nCan not create term using non-existing taxonomy '".$term->taxonomy."'! " . json_encode($term);  
			return -1;
		}
		
		if($term->slug && !$term->name){
			$term->name = str_replace(array("_","-"),array(" "," "), $term->slug);
			$term->name = strtoupper( substr($term->slug,0,1)) . substr($term->slug,1);
		}
		
		if($term->name && !$term->slug){
			$term->slug = sanitize_title($term->name);
		}
		
		if($term->parent && !is_numeric($term->parent)){
			$par_ids = $wpdb->get_col($wpdb->prepare("SELECT term_id FROM $wpdb->terms WHERE name LIKE %s OR slug LIKE %s",$term->parent,$term->parent));
			if(count($par_ids) !== 1){
				echo "\nCan not locate parent for term " . json_encode($term) . "!";  
				return -1; 
			}
			$term->parent = $par_ids[0];
		}
		
		try{
			$q = $wpdb->prepare("INSERT INTO $wpdb->terms(`term_id`, `name`, `slug`, `term_group`) VALUES 
														(%d,%s,%s,0);", $term->term_id,$term->name,$term->slug);
														
			if($wpdb->query($q)){
				$term->term_id = $wpdb->insert_id;
				
				$q = $wpdb->prepare("INSERT INTO $wpdb->term_taxonomy(`term_taxonomy_id`, `term_id`, `taxonomy`, `description`, `parent`, `count`) VALUES 
														(%d,%d,%s,%s,%d,0);", $term->term_id, $term->term_id,$term->taxonomy ,$term->description,$term->parent);
				
				if($wpdb->query($q)){
					$term_taxonomy_id = $wpdb->insert_id;
					
					if(strpos($term->taxonomy,"pa_") === 0){
						try{
							$q = $wpdb->prepare("INSERT INTO $wpdb->woocommerce_attribute_taxonomies(
									   attribute_id
									  ,attribute_name
									  ,attribute_label
									  ,attribute_type
									  ,attribute_orderby
									  ,attribute_public
									) VALUES (
									   NULL
									  ,%s
									  ,%s
									  ,%s
									  ,%s
									  ,0
									)",$term->slug, $term->name,'select','menu_order' );
							$wpdb->query($q);
							
						}catch(Exception $ex){
							echo "\nUnable to complte attribute creation " . $ex->getMessage() . " " . $q . "!";  
							return -1; 
						}
					}
				}else{
					echo "\nCan not create term taxonomy relation ".$term->name . " " . $q . "!"; 
				return -1; 
				}
			}else{
				echo "\nCan not create term ".$term->name . " " . $q . "!"; 
				return -1; 
			}
		}catch(Exception $iex){
			if(!$is_json){
				echo "\nCan not create ".$term->name . " " . json_encode($term) . "!"; 
				return -1; 
			}
		}
		
		echo "\nCreated: ".$term->name . " " . json_encode($term) . "!";
		
	}
	return 0;
}

if(getRequestVar("do_import_terms")){
	if(getRequestVar("do_import_terms") == "1"){
		$is_json = getRequestVar("do_import_terms_format") == "json";
		
		if(!$is_json){
		?><!DOCTYPE html>
		  <html>
			<head>
				<style type="text/css">
					html, body{
						background:#505050;
						color:white;
						font-family:sans-serif;	
					}
				</style>
			</head>
			<body>
			<h2>Import categories and attributtes</h2>
			
		<?php
		}
		
		$all_term_props = array('term_id','parent','name','slug','taxonomy','description');
		if($is_json){
			$json  = file_get_contents('php://input');
			$data  = json_decode($json);
			$res = new stdClass;
			header('Content-Type: text/json;charset=UTF-8');
			
			ob_start();
			
			foreach($data as $item){
				if(importTerm($item) == -1){
					$res->success = false;
					$res->error   = ob_get_clean();
					echo json_encode($res);
					die;
					
				}
			}
		    $res->success = true;
			$res->message = ob_get_clean();
			echo json_encode($res);
			die;
		}else{
			ob_start();
			error_reporting(-1);
			ini_set('display_errors',1);
			
			if (($handle = fopen($_FILES['file']['tmp_name'], "r")) !== FALSE) {
				global $impexp_settings;
				
							
				$headers = null;
				$n = 0;
				
				$term_obj = new stdClass;
				$prop_index    = new stdClass;
				
				while (($data = fgetcsv($handle, 32768, $impexp_settings->delimiter)) !== FALSE) {
					
					
					if($n == 0){
						$bom     = pack('H*','EFBBBF');
						$data[0] = preg_replace("/^$bom/", '', $data[0]);
						$headers = $data;
						for($i = 0; $i < count($headers); $i++){
							$headers[$i] = trim(strtolower($headers[$i]));
							$prop_index->{$headers[$i]} = $i;
						}
					}else{
						foreach($all_term_props as $prop){
							if(isset($prop_index->{$prop})){
								$term_obj->{$prop} = $data[$prop_index->{$prop}];
								
								if($prop != "description"){
									if(!$term_obj->{$prop})
										$term_obj->{$prop} = null;
									
								}
								
							}else{
								$term_obj->{$prop} = null;
							}
						}
						if(importTerm($term_obj) == -1){
							echo "<p>Could finish import</p>";
							break;
						}
					}
					$n++;
				}
				
				fclose($handle);
				echo "\nImport end.";
				
			}
			
			error_reporting(0);
			ini_set('display_errors',0);
			$out = ob_get_clean();
			
			echo "<p>" . str_replace("\n", "</p><hr/><p>", $out ) . "</p>";
			
		}
		global $wpdb;
		$wpdb->query("DELETE FROM $wpdb->options WHERE option_name LIKE '_transient%';");
		
		if(!$is_json){
		?>
		<hr/>
		<button style="background-color: #80397B;padding: 10px;
			margin: 10px;
			color: white;
			border: none;
			cursor: pointer;" onclick="window.location.href = window.location.href;" >RETURN</button>
		</body>
		</html>
		<?php
		}
		die;
	}
}

if(getRequestVar("do_export_terms")){
	if(getRequestVar("do_export_terms") == "1"){
		ob_clean();ob_clean();
		global $wpdb;
		$SLUG_INDEX =3;
		$q = "SELECT tt.term_id, tt.parent, t.name, t.slug,tt.taxonomy,tt.description  FROM 
						$wpdb->term_taxonomy as tt
						LEFT JOIN
						$wpdb->terms as t on t.term_id = tt.term_id 
						WHERE  
						tt.term_id > 1
						AND
						(tt.taxonomy = 'product_cat' OR tt.taxonomy LIKE 'pa_%')
						ORDER BY
						tt.taxonomy,
						CASE 
						WHEN tt.parent = 0 THEN tt.term_id
						ELSE tt.parent
						END,
						t.name
		";
		
		//error_reporting(-1);
		//ini_set('display_errors',1);
		$res      = null;
		try{
			$res      = $wpdb->get_results($q,getRequestVar("do_export_terms_format") == "json" ? OBJECT_K : ARRAY_N);
		}catch(Exception $ex){
			echo $ex->getMessage();
			die;
		}
		
		if(getRequestVar("do_export_terms_format") == "json"){
			ob_clean();ob_clean();
			header('Content-Type: text/json;charset=UTF-8');
			
			foreach($res as $term_id => $term){
				$term->name = rawurldecode(toUTF8($term->name));
				$term->slug = rawurldecode(toUTF8($term->slug));
			}
			
			echo json_encode($res);
			die;
		}
		
		if(!$res){
			echo "Nothing to export!";
			die;
		}
			
		
		$filename = "csv_terms_" .(isset($_SERVER['HTTP_HOST']) ? $_SERVER['HTTP_HOST']."_" : ""). date("Y-m-d") . ".csv";
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
		
		$csv_df = fopen("php://output", 'w');
		
		global $impexp_settings;
		fputcsv($csv_df, array('term_id', 'parent', 'name', 'slug','taxonomy','description') ,$impexp_settings->delimiter2);
		foreach($res as $row){
			$row = array_map("toUTF8",$row);
			$row[$SLUG_INDEX] = rawurldecode($row[$SLUG_INDEX]);
			fputcsv($csv_df, $row,$impexp_settings->delimiter2);
		}
		
		fclose($csv_df);
	
		die;
	}
}

global $attributes, $attributes_asoc;
$attributes      = array();
$attributes_asoc = array();
function loadAttributes(&$attributes, &$attributes_asoc){
    global $wpdb, $variations_fields;
	$woo_attrs = $wpdb->get_results("select * from " . $wpdb->prefix . "woocommerce_attribute_taxonomies",ARRAY_A);
	
	$n = 0;
	
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
	 
		$n++;
	    $att->order = $n;
		
		$attributes[]                = $att;
		$attributes_asoc[$att->name] = $att;
		
		
		$variations_fields[] = 'pattribute_'.$att->id;
	}
};

loadAttributes($attributes,$attributes_asoc);

function cmpAttOrder($a, $b)
{
	global $attributes_asoc;
	$a_name = substr($a->name,3);
	$b_name = substr($b->name,3);
	
	return strcmp($a_name,$b_name);
};

function orderProductAttributes(&$pa){
	usort($pa, "cmpAttOrder");
	$len = count($pa);
	for($i = 0; $i < $len ; $i++){
		$pa[$i]['position'] = $i + 1;
		$pa[$pa[$i]["name"]] = $pa[$i];
		unset($pa[$i]);
	}
};


function get_array_value(&$array,$key,$default){
	   if(isset($array[$key]))
		  return $array[$key];
	   else
		  return $default;
}; 


function cleanFalseAttachments(){
	global $wpdb;
	global $cleaned_attachment_count;
	$attachment_ids = $wpdb->get_col("SELECT p.ID FROM $wpdb->posts as p WHERE p.post_type = 'attachment';");
	foreach($attachment_ids as $att_id){
		$path = get_attached_file( $att_id );
		if((!file_exists($path) || !is_file($path)) && stripos($path,'wp-content') !== false && stripos($path,'uploads') !== false && stripos($path,'://') === false){
			wp_delete_attachment( $att_id, true );
			$cleaned_attachment_count++;
		}
	}
}

global $cleaned_attachment_count;
$cleaned_attachment_count = null;
if(isset($_REQUEST["clean_false_img"])){
	if($_REQUEST["clean_false_img"]){
		$cleaned_attachment_count = 0;
		cleanFalseAttachments();
	}
}

$custom_fileds = array();
function loadCustomFields(&$plem_settings,&$custom_fileds){
    global $use_image_picker, $use_content_editior, $variations_fields;
	
	
	for($I = 0 ; $I < 20 ; $I++){
		$n = $I + 1;
		if(isset($plem_settings["wooccf_enabled".$n])){
			if($plem_settings["wooccf_enabled".$n]){
				$cfield = new stdClass();
				
				$cfield->type  = get_array_value($plem_settings,"wooccf_type".$n, "");
				if(!$cfield->type)
				  continue;
				  
				$cfield->title = get_array_value($plem_settings,"wooccf_title".$n, "");
				if(!$cfield->title)
				  continue;
			   
				$cfield->source = get_array_value($plem_settings,"wooccf_source".$n, "");
				if(!$cfield->source)
				  continue;  
				  
				$cfield->options = get_array_value($plem_settings,"wooccf_editoptions".$n, "");
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
					    if(!$val)
							continue;
						if(!isset($val->name))
							continue;
					    if($val->name === NULL)
							continue;
						
						
						
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
				$cfield->name = str_replace(".","_",$cfield->name);
				$cfield->name = str_replace("!","_",$cfield->name);
				
				$custom_fileds[$cfield->name] = $cfield;	
				
				if(get_array_value($plem_settings,"wooccf_varedit".$n, "")){	
					$variations_fields[] = $cfield->name;	
				}
			}   
		}
	}
};

function plem_add_product($variataion_of = NULL){
	
	 $post = array(
     	 'post_author' => get_current_user_id(),
		 'post_content' => '',
		 'post_status' => "publish",
		 'post_title' => "Product ".date("y-m-d H:i:s"),
		 'post_parent' => ($variataion_of ? $variataion_of :''),
		 'post_type' => ($variataion_of ? "product_variation" : "product"),
     );
	 
      //Create post
     $post_id = wp_insert_post( $post, $wp_error );
     if($post_id){
		$attach_id = 0;
		
		if($variataion_of)
			$attach_id = get_post_meta($variataion_of, "_thumbnail_id", true);
		
		add_post_meta($post_id, '_thumbnail_id', !$attach_id ? 0 : $attach_id);
     }
	 
     if(!$variataion_of){
		 wp_set_object_terms($post_id, array(), 'product_cat' );
		 wp_set_object_terms($post_id, array('simple'), 'product_type');
	 }else{
		 $v_update = array( 'ID' => $post_id, 'post_title' => "Variation #".$post_id." of " . get_the_title($variataion_of), 'post_name' => get_the_title($variataion_of) .' variation ' .$post_id  );
		 wp_update_post($v_update);;
	 }
	 
     update_post_meta( $post_id, '_visibility', 'visible' );
     update_post_meta( $post_id, '_stock_status', 'instock');
     update_post_meta( $post_id, 'total_sales', '0');
     update_post_meta( $post_id, '_downloadable', 'no');
     update_post_meta( $post_id, '_virtual', 'no');
     update_post_meta( $post_id, '_regular_price', "" );
     update_post_meta( $post_id, '_sale_price', "" );
     update_post_meta( $post_id, '_purchase_note', "" );
     update_post_meta( $post_id, '_featured', "no" );
     update_post_meta( $post_id, '_weight', "" );
     update_post_meta( $post_id, '_length', "" );
     update_post_meta( $post_id, '_width', "" );
     update_post_meta( $post_id, '_height', "" );
     update_post_meta( $post_id, '_sku', "");
     update_post_meta( $post_id, '_product_attributes', array());
     update_post_meta( $post_id, '_sale_price_dates_from', "" );
     update_post_meta( $post_id, '_sale_price_dates_to', "" );
     update_post_meta( $post_id, '_price', "" );
     update_post_meta( $post_id, '_sold_individually', "" );
     update_post_meta( $post_id, '_manage_stock', "no" );
     update_post_meta( $post_id, '_backorders', "no" );
     update_post_meta( $post_id, '_stock', "" );
	
	 pelm_on_product_update($post_id);
	 if($variataion_of)
		pelm_on_product_update($variataion_of);
	
	 return $post_id;
}

function updateParentPriceData($parent){
	global $wpdb;
	$wpdb->flush();
	
	$var_ids = $wpdb->get_col($wpdb->prepare( 
		"SELECT      p.ID
			FROM        $wpdb->posts p
		 WHERE       p.post_type = 'product_variation'
						AND 
					 p.post_parent = %d
		 ORDER BY    p.ID
		",
		$parent
	));
	
	$_min_variation_price            = '';
	$_max_variation_price            = '';
	$_min_price_variation_id         = '';
	$_max_price_variation_id         = ''; 
	
	$_min_variation_regular_price    = '';
	$_max_variation_regular_price    = '';
	$_min_regular_price_variation_id = '';
	$_max_regular_price_variation_id = '';
	
	$_min_variation_sale_price       = '';
	$_max_variation_sale_price       = '';
	$_min_sale_price_variation_id    = '';
	$_max_sale_price_variation_id    = '';
	
	foreach($var_ids as $vid){
		$_regular_price = get_post_meta($vid,'_regular_price',true);
		$_sale_price = get_post_meta($vid,'_sale_price',true);
		$_price  = get_post_meta($vid,'_price',true);
		
		if(!$_price)
			$_price = $_sale_price ? $_sale_price : $_regular_price;
		
		if($_price){
			if(!$_min_variation_price){
				$_min_variation_price    = $_price;
				$_min_price_variation_id = $vid;
			}
			if(!$_max_variation_price){
				$_max_variation_price    = $_price;
				$_max_price_variation_id = $vid;
			}
			
			if(floatval($_min_variation_price) > floatval($_price)){
				$_min_variation_price    = $_price;
				$_min_price_variation_id = $vid;
			}
			
			if(floatval($_max_variation_price) < floatval($_price)){
				$_max_variation_price    = $_price;
				$_max_price_variation_id = $vid;
			}
		}
		
		if($_regular_price){
			if(!$_min_variation_regular_price){
				$_min_variation_regular_price    = $_regular_price;
				$_min_regular_price_variation_id = $vid;
			}
			
			if(!$_max_variation_regular_price){
				$_max_variation_regular_price    = $_regular_price;
				$_max_regular_price_variation_id = $vid;
			}
			
			if(floatval($_min_variation_regular_price) > floatval($_regular_price)){
				$_min_variation_regular_price    = $_regular_price;
				$_min_regular_price_variation_id = $vid;
			}
			
			if(floatval($_max_variation_regular_price) < floatval($_regular_price)){
				$_max_variation_regular_price    = $_regular_price;
				$_max_regular_price_variation_id = $vid;
			}
		}
		
		if($_sale_price){
			if(!$_min_variation_sale_price){
				$_min_variation_sale_price    = $_sale_price;
				$_min_sale_price_variation_id = $vid;
			}
			
			if(!$_max_variation_sale_price){
				$_max_variation_sale_price    = $_sale_price;
				$_max_sale_price_variation_id = $vid;
			}
			
			if(floatval($_min_variation_sale_price) > floatval($_sale_price)){
				$_min_variation_sale_price    = $_sale_price;
				$_min_sale_price_variation_id = $vid;
			}
			
			if(floatval($_max_variation_sale_price) < floatval($_sale_price)){
				$_max_variation_sale_price    = $_sale_price;
				$_max_sale_price_variation_id = $vid;
			}
		}
	}
	
	update_post_meta($parent,'_min_variation_price',$_min_variation_price);
	update_post_meta($parent,'_max_variation_price',$_max_variation_price);
	update_post_meta($parent,'_min_price_variation_id',$_min_price_variation_id);
	update_post_meta($parent,'_max_price_variation_id',$_max_price_variation_id);
	update_post_meta($parent,'_min_variation_regular_price',$_min_variation_regular_price);
	update_post_meta($parent,'_max_variation_regular_price',$_max_variation_regular_price);
	update_post_meta($parent,'_min_regular_price_variation_id',$_min_regular_price_variation_id);
	update_post_meta($parent,'_max_regular_price_variation_id',$_max_regular_price_variation_id);
	update_post_meta($parent,'_min_variation_sale_price',$_min_variation_sale_price);
	update_post_meta($parent,'_max_variation_sale_price',$_max_variation_sale_price);
	update_post_meta($parent,'_min_sale_price_variation_id',$_min_sale_price_variation_id);
	update_post_meta($parent,'_max_sale_price_variation_id',$_max_sale_price_variation_id);
	update_post_meta($parent,'_price',$_min_variation_price);
	if(function_exists("wc_delete_product_transients"))
		wc_delete_product_transients($parent);
}

function updateParentVariationData($parent, &$res_obj, &$aasoc,$attributes_set, $add_var_display_updates = false, $do_not_normalize = false){
	
	
	global $wpdb;
	global $attributes;
	
	$dirty = false;	
	$skip_normalize = false;
	
	$defaultAtt = get_post_meta($parent,'_product_attributes',true);
	
	if($defaultAtt){
		if(isset($defaultAtt[0])){
			if(is_string($defaultAtt)){
				$defaultAtt = array();
				orderProductAttributes($defaultAtt);
				if(isset($defaultAtt['']))
					unset($defaultAtt['']);
				update_post_meta($parent,'_product_attributes',$defaultAtt);
			}else{
				unset($defaultAtt[0]);
				$dirty = true;
				$skip_normalize = (!$res_obj && !$aasoc && !$attributes_set);
			}
		}
	}
	
	$wpdb->flush();
	
	$ptype = implode(",",wp_get_object_terms($parent, 'product_type', array('fields' => 'names')));
	
	$var_ids = $wpdb->get_col($wpdb->prepare( 
			"SELECT      p.ID
				FROM        $wpdb->posts p
			 WHERE       p.post_type   = 'product_variation'
							AND 
						 p.post_parent = %d
			 ORDER BY    p.ID
			",
			$parent
	));
	
	if($ptype !== 'variable'){
	  if($defaultAtt){
		  foreach($defaultAtt as $a_key => $patt){
			  if($a_key){
				  if($defaultAtt[$a_key]["is_variation"]){
					$defaultAtt[$a_key]["is_variation"] = 0;
					$dirty           = true;
					$skip_normalize  = true;
					
					/*
					if(!empty($var_ids)){
						$wpdb->query("DELETE FROM $wpdb->postmeta pm WHERE pm.post_id IN (".implode(",",$var_ids).") AND pm.meta_key LIKE 'attribute_$a_key' ");
					}
					*/
				  }
			  }
		  }
	  }
    }	
	
    if(!$skip_normalize && !$do_not_normalize){	
		

		$attrs = array();
		if(!empty($var_ids)){
			$wpdb->flush();
			$attrs = $wpdb->get_col("SELECT DISTINCT pm.meta_key
							FROM        $wpdb->postmeta pm
						 WHERE       pm.post_id IN (".implode(",",$var_ids).")
									 AND
									 pm.meta_key LIKE 'attribute_pa_%'");
		}
		
		$curr_attrs = array_keys($defaultAtt);
		
		if($_REQUEST["do_import"] = "1"){
			foreach($curr_attrs as $attr_name){
				if($attr_name){
					$a = "attribute_" . $attr_name;
					if($defaultAtt[$attr_name]["is_variation"]){
						if(!in_array($a,$attrs)){
							$defaultAtt[$attr_name]['is_variation'] = 0; ;	
							$dirty = true;
							wp_set_object_terms( $parent , NULL , $attr_name );
						}
					}
				}
			}
		}
		
		if($attributes_set){
			if(!empty($attributes_set)){
				foreach($attrs as $ind => $att){
					$a = substr($att,10);
					if(!$a)
						continue;
					
					if(in_array(substr($a,3), $attributes_set)){
						if(!in_array($a,$attrs)){
							$defaultAtt[$a] = array (
													'name'         => $a,
													'value'        => '',
													'position'     => count($defaultAtt),
													'is_visible'   => 1,
													'is_variation' => 1,
													'is_taxonomy'  => 1
												);
							$dirty = true;					
						}else{
							if(!$defaultAtt[$a]["is_variation"]){
								$defaultAtt[$a]["is_variation"] = 1;
								$dirty = true;					
							}
						}
					}
				}
			}
		}
	}
	
	
	
	if($dirty){
		
		
		orderProductAttributes($defaultAtt);
		if(isset($defaultAtt['']))
			unset($defaultAtt['']);
		update_post_meta($parent,'_product_attributes',$defaultAtt);
		updateParentPriceData($parent);
		
		foreach($defaultAtt as $key=> $val){
			if($defaultAtt[$key]["is_variation"]){
				$aterm = substr($key,3);
				$val = array();
				foreach($var_ids as $v_id){
					$v = get_post_meta($v_id,'attribute_'. $key ,true);
					if($v)
						$val[] = $v."";
				}
				wp_set_object_terms( $parent , asArray($val) , $key );
			}
		}
		
		if($res_obj){
			
			
			
			$ainf = array();
		    
			if(!isset($res_obj->dependant_updates))
				$res_obj->dependant_updates = array();
			
			if(!isset($res_obj->dependant_updates[$parent]))
				$res_obj->dependant_updates[$parent] = new stdClass;
					
			foreach($aasoc as $key=> $val){
				$a_id = $val->id;
				$ainf[$a_id]    = new stdClass;
				if(isset($defaultAtt["pa_".$key])){
					$ainf[$a_id]->v = $defaultAtt["pa_".$key]["is_variation"];
					$ainf[$a_id]->s = $defaultAtt["pa_".$key]["is_visible"] ? true : false;
				}else{
					$ainf[$a_id]->v = 0;
					$ainf[$a_id]->s = true;
				}
				
				$res_obj->dependant_updates[$parent]->{"pattribute_" . $val->id } = wp_get_object_terms($parent,"pa_".$key, array('fields' => 'ids'));
			}
			
			if(!isset($res_obj->dependant_updates[$parent]))
				$res_obj->dependant_updates[$parent] = new stdClass;
			
			$res_obj->dependant_updates[$parent]->att_info = $ainf;
		} 
	}
	
	
	
	if($add_var_display_updates && $res_obj){	
		if(!isset($res_obj->dependant_updates))
			$res_obj->dependant_updates = array();
		if(!empty($var_ids)){
			foreach($var_ids as $v_id){
				if(isset($res_obj->dependant_updates[$v_id]))
					continue;
				if(isset($res_obj->pnew))
					if(isset($res_obj->pnew[$v_id]))
						continue;
					
				$res_obj->dependant_updates[$v_id] = product_render($v_id , $attributes, "data");
			}
		}
	}
}



function clonePost($post_id, $with_parent = NULL){
	global $wpdb;
	
	$current_user = wp_get_current_user();
	$new_post_author = $current_user->ID;
	
	$post = get_post( $post_id );
	if(!$post)
		return NULL;
	
	$args = array(
			'comment_status' => $post->comment_status,
			'ping_status'    => $post->ping_status,
			'post_author'    => $new_post_author,
			'post_content'   => $post->post_content,
			'post_excerpt'   => $post->post_excerpt,
			'post_name'      => $post->post_name,
			'post_parent'    => $with_parent ? $with_parent : $post->post_parent,
			'post_password'  => $post->post_password,
			'post_status'    => $post->post_status,
			'post_title'     => $post->post_title  . " clone",
			'post_type'      => $post->post_type,
			'to_ping'        => $post->to_ping,
			'menu_order'     => $post->menu_order
	);
	
	$new_post_id = wp_insert_post( $args );
	if($new_post_id){
		
		$taxonomies = get_object_taxonomies($post->post_type); // returns array of taxonomy names for post type, ex array("category", "post_tag");
		foreach ($taxonomies as $taxonomy) {
			$post_terms = wp_get_object_terms($post_id, $taxonomy, array('fields' => 'slugs'));
			wp_set_object_terms($new_post_id, asArray($post_terms), $taxonomy, false);
		}
 
		$post_meta_infos = $wpdb->get_results("SELECT meta_key, meta_value FROM $wpdb->postmeta WHERE post_id=$post_id");
		if (count($post_meta_infos)!=0) {
			$sql_query = "INSERT INTO $wpdb->postmeta (post_id, meta_key, meta_value) ";
			foreach ($post_meta_infos as $meta_info) {
				$meta_key = $meta_info->meta_key;
				$meta_value = addslashes($meta_info->meta_value);
				$sql_query_sel[]= "SELECT $new_post_id, '$meta_key', '$meta_value'";
			}
			$sql_query.= implode(" UNION ALL ", $sql_query_sel);
			$wpdb->query($sql_query);
		}
		
		$CH_ids = $wpdb->get_col($wpdb->prepare( 
			"SELECT      p.ID
				FROM        $wpdb->posts p
			 WHERE       p.post_parent = %d
			 ORDER BY    p.ID
			",
			$post_id
		));
		
		foreach($CH_ids as $ch){
			clonePost($ch, $new_post_id);
		}
		
		return $new_post_id;
	}
	
	return NULL;
}

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


loadCustomFields($plem_settings,$custom_fileds);


$tax_classes  = array();
foreach(explode("\n",get_option('woocommerce_tax_classes')) as $tc){
  $tax_classes[ str_replace(" ","-",strtolower($tc))] = $tc;
}

$tax_statuses = array();
$tax_statuses['taxable'] = 'Taxable';
$tax_statuses['shipping'] = 'Shipp. only';
$tax_statuses['none'] = 'None';

$productsPerPage = 500;
if(isset($plem_settings["productsPerPage"])){
	$productsPerPage = intval($plem_settings["productsPerPage"]);
}

$limit = $productsPerPage;

if(isset($_COOKIE['pelm_txtlimit']))
	$limit = $_COOKIE['pelm_txtlimit'] ? $_COOKIE['pelm_txtlimit'] : $productsPerPage;

	
$page_no  = 1;

$orderby         = "ID";
$orderby_key     = "";

$sort_order  = "ASC";
$sku = '';
$product_name = '';
$product_category = '';
$product_tag      = '';
$product_shipingclass = '';
$product_status   = '';

if(isset($_REQUEST['limit'])){
	$limit = $_REQUEST['limit'];
	setcookie('pelm_txtlimit',$limit, time() + 3600 * 24 * 30);
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

if(isset($_REQUEST['product_tag'])){
	$product_tag = explode(",", $_REQUEST['product_tag']);
}

if(isset($_REQUEST['product_category'])){
	$product_category = explode(",", $_REQUEST['product_category']);
}

if(isset($_REQUEST['product_shipingclass'])){
	$product_shipingclass = explode(",", $_REQUEST['product_shipingclass']);
}

if(isset($_REQUEST['product_status'])){
	$product_status = explode(",", $_REQUEST['product_status']);
}

$filter_attributes = array();
foreach($attributes as $attr){
	if(isset($_REQUEST['pattribute_'.$attr->id])){
		
		$fstring = $_REQUEST['pattribute_'.$attr->id];
		if($fstring[0] == ',')
			$fstring = substr($fstring,1);
		$fstring = trim($fstring);
		
        $filter_attributes[$attr->name] = explode(",", $fstring);
	}
}


$filter_cf = array();
foreach($custom_fileds as $cf){
	if($cf->type == "term"){
		if(isset($_REQUEST[$cf->name])){
			$fstring = $_REQUEST[$cf->name];
			
			if(substr($fstring,0,1) == ',')
				$fstring = substr($fstring,1);
			
			$fstring = trim($fstring);
			$filter_cf[$cf->name] = explode(",", $fstring);	
		}
	}
}


if(isset($_REQUEST['sortColumn'])){

	$orderby = $_REQUEST['sortColumn'];
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
		
		$orderby_key = explode("!",$orderby_key);
		$orderby_key = $orderby_key[0];
		$orderby_key = explode(".",$orderby_key);
		$orderby_key = $orderby_key[0];
		
	}elseif($orderby == "sku") {
		$orderby = "meta_value";
		$orderby_key = "_sku";
	}elseif($orderby == "slug") $orderby = "name";
	 elseif($orderby == "name") $orderby = "title";
     elseif($orderby == "stock") {
		$orderby = "meta_value_num";
		$orderby_key = "_stock";
	}elseif($orderby == "stock_status") {
		$orderby = "meta_value";
		$orderby_key = "_stock_status";
	}elseif($orderby == "price") {
		$orderby = "meta_value_num";
		$orderby_key = "_regular_price";
	}elseif($orderby == "override_price") {
		$orderby = "meta_value_num";
		$orderby_key = "_sale_price";
	}elseif($orderby == "status"){ 
		$orderby = "status";
	}elseif($orderby == "weight"){ 
		$orderby = "meta_value_num";
		$orderby_key = "_weight";
	}elseif($orderby == "length"){ 
		$orderby = "meta_value_num";
		$orderby_key = "_length";
	}elseif($orderby == "width"){ 
		$orderby = "meta_value_num";
		$orderby_key = "_width";
	}elseif($orderby == "height"){ 
		$orderby = "meta_value_num";
		$orderby_key = "_height";
	}elseif($orderby == "featured"){ 
		$orderby = "meta_value";
		$orderby_key = "_featured";
	}elseif($orderby == "virtual"){ 
		$orderby = "meta_value";
		$orderby_key = "_virtual";
	}elseif($orderby == "downloadable"){ 
		$orderby = "meta_value";
		$orderby_key = "_downloadable";
	}elseif($orderby == "tax_status"){ 
		$orderby = "meta_value";
		$orderby_key = "_tax_status";
	}elseif($orderby == "tax_class"){ 
		$orderby = "meta_value";
		$orderby_key = "_tax_class";
	}elseif($orderby == "backorders"){ 
		$orderby = "meta_value";
		$orderby_key = "_backorders";
	}elseif($orderby == "tags"){ 
		$orderby = "ID";
	}else
		$orderby = "ID";
	
	if(!$orderby)
		$orderby = "ID";
}

if(isset($_REQUEST['sortOrder'])){
	$sort_order = $_REQUEST['sortOrder'];
}

function get_attachment_id_from_src ($media_src) {
	global $wpdb;
	$query = "SELECT ID FROM {$wpdb->posts} WHERE guid LIKE '$media_src'";
	$id = $wpdb->get_var($query);
	return $id;
}

function getDownloads($pr_id, $export = false){
	$ret = array();
	
	$downloads = get_post_meta($pr_id, "_downloadable_files" ,true);
	if(is_array($downloads)){
		foreach($downloads as $dkey => $download){
			if($export){
				$ret[] = $download["file"];
			}else{
				$f_download = new stdClass;
				$f_download->id    = get_attachment_id_from_src($download["file"]);
				$f_download->src   = $download["file"];
				$f_download->thumb = null;
				$ret[] = $f_download;
			}
		} 
	}
	
	if($export)
		return implode(",", $ret);
	
	return $ret;
}



if(isset($_REQUEST['DO_UPDATE'])){
	


if($_REQUEST['DO_UPDATE'] == '1' && strtoupper($_SERVER['REQUEST_METHOD']) == 'POST'){
	$timestamp = time();
	$json = file_get_contents('php://input');
	$tasks = json_decode($json);
    $surogates = get_option("plem_wooc_surogates",array());
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
	   
	   $parent = get_ancestors($key,'product');
	   if( !empty($parent))
			$parent = $parent[0];
	   else		
			$parent = 0;
	   
	   if(isset($task->DO_DELETE)){
		   if($task->DO_DELETE == 'delete'){
			   wp_delete_post($key,true);
			   if($parent){
				   updateParentVariationData($parent,$res_item,$attributes_asoc,null);
			   }
			   $res[] = $res_item;
			   continue;
		   }
	   }
	   
	   if(isset($task->DO_CLONE)){
		   if($task->DO_CLONE == 'clone'){
			   $cid = clonePost($key);
			   if($cid){
				  $res_item->clones   = array();
				  $res_item->clones[] = product_render($cid , $attributes, "data");
				  
				  if(!$res_item->clones[0]->parent){
					  $var_ids = $wpdb->get_col($wpdb->prepare( 
							"SELECT      p.ID
								FROM        $wpdb->posts p
							 WHERE       p.post_type = 'product_variation'
											AND 
										 p.post_parent = %d
							 ORDER BY    p.ID
							",
							$cid
					  ));
					  foreach($var_ids as $ch_id){
						  $res_item->clones[] = product_render($ch_id , $attributes, "data");
					  }
				  }
			   }
			   
			   $res[] = $res_item;
			   continue;
		   }
	   }
	   
	   
	   
	   $needs_var_update = false;
	   
	   $skip_attributes_norm = false;
	   if(property_exists($task,"variate_by")){
		   if($task->variate_by === NULL)
			   $task->variate_by = array();
		   
		   $patts = get_post_meta($key,'_product_attributes',true);
		   
		   $vcount = isset($task->variate_count) ? $task->variate_count : 1;
		   
		   $res_item->pnew = array();
		   $vid = 0;
		   $newvids = array();
		   
		   $aset_v = array();
		   
		   foreach($task->variate_by as $aname => $avalue ){
				$a_tax_name = "pa_" . $aname;
				if($avalue){
					if(!isset($patts[$a_tax_name]))
						$patts[$a_tax_name] = array();
					elseif(!$patts[$a_tax_name]){
						$patts[$a_tax_name] = array();
					}
					
					$patts[$a_tax_name]["name"]         = $a_tax_name;
					if(!isset($patts[$a_tax_name]["is_visible"]))
						$patts[$a_tax_name]["is_visible"]   = 1;
					
					$patts[$a_tax_name]["is_taxonomy"]  = 1;
					
					//if(!$patts[$a_tax_name]["is_variation"]){
					$patts[$a_tax_name]["is_variation"] = 1;
					$aset_v[] = "attribute_$a_tax_name";
					//}
					
					if(!isset($patts[$a_tax_name]["value"])) 
						$patts[$a_tax_name]["value"]       = "";
					if(!isset($patts[$a_tax_name]["position"])) 
						$patts[$a_tax_name]["position"] = count($patts);
				}else{
					if(isset($patts[$a_tax_name])){
						$patts[$a_tax_name]["is_variation"] = 0;
						$var_ids = $wpdb->get_col($wpdb->prepare( 
							"SELECT      p.ID
								FROM        $wpdb->posts p
							 WHERE       p.post_type = 'product_variation'
											AND 
										 p.post_parent = %d
							 ORDER BY    p.ID
							",
							$cid
						));
						if(!empty($var_ids))
							$wpdb->query("DELETE FROM  $wpdb->postmeta WHERE post_id IN (". implode(",",$var_ids) .") AND meta_key LIKE 'attribute_$a_tax_name'");
					}	
				}
				
				$skip_attributes_norm = true;
		   }
		   
		   for($I = 0; $I < $vcount ; $I++){
			   $vid = plem_add_product($key);
			   if($vid){
				   foreach($aset_v as $a){
					   update_post_meta($vid, $a, "");
				   }
				   $newvids[] = $vid;
			   }
		   }
		   
		   
		   orderProductAttributes($patts);
		   if(isset($patts['']))
			   unset($patts['']);
		   update_post_meta($key, '_product_attributes', $patts);
		   
		   if(!isset($res_obj->dependant_updates))
				$res_item->dependant_updates = array();
					
		   if(!isset($res_item->dependant_updates[$key]))
				$res_item->dependant_updates[$key] = new stdClass;
						
		   $att_info = array();
		   foreach($attributes as $attr){
			  
			   if(isset($patts["pa_".$attr->name])){
				   $att_info[$attr->id] = new stdClass;
				   $att_info[$attr->id]->v = $patts["pa_".$attr->name]["is_variation"] ? 1 : 0;
				   $att_info[$attr->id]->s = $patts["pa_".$attr->name]["is_visible"] ? 1 : 0;
			   }else{
				   $att_info[$attr->id]->v = 0;
				   $att_info[$attr->id]->s = 1;
				}
		   }		
		   $res_item->dependant_updates[$key]->att_info = $att_info;
		   
		   foreach($newvids as $newp)
				$res_item->pnew[$newp] = product_render($newp , $attributes, "data"); 

		   $needs_var_update = true; 	
	   }
	   
	   $full_refresh = false;
	   $upd_prop = array();
	   $post_update = array( 'ID' => $key );
	  
	   if(isset($task->sku)){ 
		  update_post_meta($key, '_sku', $task->sku);
	   }
	   
	   if(isset($task->stock_status)){ 
		  update_post_meta($key, '_stock_status', $task->stock_status ? "instock" : "outofstock");
	   }
	   
	   if(isset($task->stock)){ 
	      $prev_stock = get_post_meta($key,"_stock",true); 
		  update_post_meta($key, '_stock', $task->stock);
		  
		  if( $task->stock === "" ||  $task->stock === null)
			update_post_meta($key, '_manage_stock', 'no');
		  else{
			update_post_meta($key, '_manage_stock', 'yes');
			if(floatval($task->stock) > 0)
				update_post_meta($key, '_stock_status', "instock");
			else
				update_post_meta($key, '_stock_status', "outofstock");
		 }
		 
		 if(!$res_item->full)
			 $res_item->full = new stdClass;
		 
		 $res_item->full->stock_status = get_post_meta($key,'_stock_status',true) == "instock" ? true : false;
		 
		 if(!$prev_stock && $task->stock > 0){
			 if(has_action('dc_start_stock_alert')) {
				do_action('dc_start_stock_alert');
				$res_item->dc_start_stock_alert = true;
			 }
		 }
	   }
	   
	   $any_price_set = false;
	   if(isset($task->price)){ 
		  update_post_meta($key, '_regular_price', $task->price);
		  $any_price_set = true;
	   }
	   
	   if(isset($task->override_price)){
	      update_post_meta($key, '_sale_price', $task->override_price);
		  $any_price_set = true;
	   }
	   
	   if(property_exists($task,"image")){
		  if($task->image){
			if($task->image->id){
				set_post_thumbnail($key,$task->image->id);
			}else
				delete_post_thumbnail( $key );				
		  }else
			delete_post_thumbnail( $key );					
	   }
	   
	   if(property_exists($task,"gallery")){
		   
		  if($task->gallery){
			if(!empty($task->gallery)){
				 $gids = array(); 
				 foreach($task->gallery as $gimg){
					$gids[] = $gimg->id; 	
				 } 
				 update_post_meta($key,'_product_image_gallery',implode(",",$gids));
			}else
				update_post_meta($key,'_product_image_gallery','');				
		  }else
			update_post_meta($key,'_product_image_gallery','');					
	   }
	   
	   
			
	   if($any_price_set){
	        $s_price = get_post_meta($key,'_sale_price',true);
			$r_price = get_post_meta($key,'_regular_price',true);
			
		    $_price = trim($s_price) ? $s_price :  $r_price;
		    
			update_post_meta($key, '_price', $_price);
		    
			if($parent){
				updateParentPriceData($parent);
			}
		}
	   
	   
	   if(isset($task->weight)){ 
		  update_post_meta($key, '_weight', $task->weight);
	   }
	   
	   if(isset($task->length)){ 
		  update_post_meta($key, '_length', $task->length);
	   }
	   
	   if(isset($task->width)){ 
		  update_post_meta($key, '_width', $task->width);
	   }
	   
	   if(isset($task->height)){
	      update_post_meta($key, '_height', $task->height);
	   }
	   
	   if(isset($task->featured)){ 
		  update_post_meta($key, '_featured', $task->featured ? "yes" : "no");
	   }
	   
	   if(isset($task->virtual)){ 
		  update_post_meta($key, '_virtual', $task->virtual ? "yes" : "no");
	   }
	   
	   if(isset($task->downloadable)){ 
		  update_post_meta($key, '_downloadable', $task->downloadable ? "yes" : "no");
	   }
	   
	   if(isset($task->downloads)){
		  if($task->downloads){
			if(!empty($task->downloads)){
				 $downloads = array(); 
				 
				 foreach($task->downloads as $download){
                    $d_item = array();
   					
					if(isset($download->title)){
						if($download->title){
							$d_item["name"] = $download->title;
						}
					}
					
					if(!isset($d_item["name"])){
						$d_item["name"] = pathinfo($download->src, PATHINFO_FILENAME); 	
					}
					
					$d_item["file"] = $download->src; 
					$downloads[md5($download->src)]	= $d_item;
				 } 
				 update_post_meta($key,'_downloadable_files',$downloads);
			}else
				update_post_meta($key,'_downloadable_files',serialize(array()));				
		  }else
			update_post_meta($key,'_downloadable_files',serialize(array()));					
	   }
	   
	   if(isset($task->tax_status)){
		  if(!$task->tax_status){
			update_post_meta($key, '_tax_status', "none");
		  }else{ 
			update_post_meta($key, '_tax_status', $task->tax_status);  
		  }
	   }
	   
	   if(isset($task->tax_class)){ 
		  if(!$task->tax_class && $parent){
			update_post_meta($key, '_tax_class', "parent");  
		  }else
			update_post_meta($key, '_tax_class', $task->tax_class);
	   }
	   
	   if(isset($task->backorders)){
	      update_post_meta($key, '_backorders', $task->backorders);
	   }
	   
	   if(isset($task->status)){
		  if($parent){
			if($task->status == "publish"){
				$post_update['post_status'] = $task->status;
			}else{
				$post_update['post_status'] = "private";
				if(!isset($res_obj->dependant_updates))
					$res_item->dependant_updates = array();
					
				if(!isset($res_item->dependant_updates[$key]))
					$res_item->dependant_updates[$key] = new stdClass;
						
				$res_item->dependant_updates[$key]->status = "private";
			}
		  }else	
			$post_update['post_status'] = $task->status;
	   }
	   
	   if(isset($task->name)){ 
	      $post_update['post_title'] = $task->name;  
	   }
	   
	   if(isset($task->slug)){ 
		  $post_update['post_name'] = urlencode($task->slug);  
	   }
	  
	   if(count($post_update) > 1){
	      wp_update_post($post_update);;
	   }
	   
	   
	   
	   if(property_exists($task,"categories")){
		  if($task->categories === NULL)
			wp_set_object_terms( $key , NULL , 'product_cat' );  
		  else		
			wp_set_object_terms( $key , array_map('intval', is_string($task->categories) ? explode(",",$task->categories) : asArray($task->categories)  ) , 'product_cat' );
	   }
	   
	   if(property_exists($task,"product_type")){
		  if($task->product_type === NULL)
			  $task->product_type = array();
		  
          if(!$parent){
			wp_set_object_terms( $key , array_map('intval', is_string($task->product_type) ? explode(",",$task->product_type) : asArray($task->product_type)  ) , 'product_type' );
		  }
		  
		  if(!$res_item->full)
			$res_item->full = new stdClass;
		  
		  $res_item->full->virtual          = get_post_meta($key,'_virtual',true) == "yes" ? true : false;
		  $res_item->full->downloadable     = get_post_meta($key,'_downloadable',true) == "yes" ? true : false;
		  
		  $wpdb->query("DELETE p from $wpdb->posts as p LEFT JOIN $wpdb->posts as pp on pp.ID = p.post_parent where p.post_type ='product_variation' AND  coalesce(p.post_parent,0) > 0 AND pp.ID IS NULL");
		  
		  $needs_var_update = true;
	   }
	   
	   if($needs_var_update){
		  updateParentVariationData($key,$res_item,$attributes_asoc, $attributes_set, true, $skip_attributes_norm); 
	   }
	   
	   if(property_exists($task,"shipping_class")){
		  if($task->shipping_class === null)
			$task->shipping_class = array();			  
		   
		  if($parent && !$task->shipping_class){
			$task->shipping_class = wp_get_object_terms($parent, 'product_shipping_class', array('fields' => 'ids'));
		  }
		  wp_set_object_terms( $key , array_map('intval', is_string($task->shipping_class) ? explode(",",$task->shipping_class) : asArray($task->shipping_class)  ) , 'product_shipping_class' );
	   }
	   
	   if($parent){
		   $attributes_set = array();
		   $update_par_att = false;
	       foreach($attributes as $attr){
			   
				if(property_exists($task,'pattribute_'.$attr->id)){
					
					 $store = array();
					 $vals = $task->{'pattribute_'.$attr->id};
					 
					 if($vals){
						if(is_string($vals))
							$vals = explode(",",$vals);
					 }else
						 $vals = null;
					 
					 $attributes_set[] = $attr->name;
					 if(empty($vals)){
						 delete_post_meta($key, 'attribute_pa_' . $attr->name);
					 }else{
						 foreach($vals as $val){
							if(is_numeric($val)){
								foreach($attr->values as $trm){
								   if($trm->id == $val){
										$store[] = $trm->slug;
										break;
								   }
								}
							}else{
								$store[] = $val;
							}
							
							if(!empty($store))
								break;	
						 }
						 update_post_meta($key, 'attribute_pa_' . $attr->name, $store[0]);
					 }
					 $update_par_att = true;
				}
		   }
		   
		   if($update_par_att)
			 updateParentVariationData($parent,$res_item,$attributes_asoc,$attributes_set,$skip_attributes_norm);
		 
	   }else{ 
	   
	       
		   $patts = get_post_meta($key,'_product_attributes',true);
		   $patts_set = false;
		   if(!$patts)
			$patts = array();
		   foreach($attributes as $attr){
			    $a_tax_name = 'pa_' . $attr->name;
				
				if(property_exists($task,'pattribute_'.$attr->id)){
					$setval = $task->{'pattribute_'.$attr->id};
				
                    if($setval){				
						if(is_string($setval))
							$setval = explode(",",$setval);
						$setval = array_map(fn_correct_type, $setval);
					}else
						$setval = NULL;
					
					wp_set_object_terms( $key , $setval , $a_tax_name );
					
					if(!$task->{'pattribute_'.$attr->id}){
					   if(isset($patts[$a_tax_name]))
						unset($patts[$a_tax_name]);
					   
					   	if(!isset($res_obj->dependant_updates))
							$res_item->dependant_updates = array();
					
						if(!isset($res_item->dependant_updates[$key]))
							$res_item->dependant_updates[$key] = new stdClass;
						
						$res_item->dependant_updates[$key]->{'pattribute_'.$attr->id.'_visible'} = true;
					
					}else{
						if(!isset($patts[$a_tax_name]))
							$patts[$a_tax_name] = array();
						elseif(!$patts[$a_tax_name]){
							$patts[$a_tax_name] = array();
						}
						
						$patts[$a_tax_name]["name"]         = $a_tax_name;
						if(!isset($patts[$a_tax_name]["is_visible"]))
							$patts[$a_tax_name]["is_visible"]   = 1;
						$patts[$a_tax_name]["is_taxonomy"]  = 1;
						$patts[$a_tax_name]["is_variation"] = 0;
						
						if(!isset($patts[$a_tax_name]["value"])) $patts[$a_tax_name]["value"]       = "";
						if(!isset($patts[$a_tax_name]["position"])) $patts[$a_tax_name]["position"] = "0";
					}
					$patts_set = true;
				}
				
				if(property_exists($task,'pattribute_'.$attr->id.'_visible')){
					
					if(!isset($patts[$a_tax_name]))
						$patts[$a_tax_name] = array();
					elseif(!$patts[$a_tax_name]){
						$patts[$a_tax_name] = array();
					}
					
					$patts[$a_tax_name]["name"]         = $a_tax_name;
					$patts[$a_tax_name]["is_visible"]   = $task->{'pattribute_'.$attr->id.'_visible'} ? 1 : 0;
					$patts[$a_tax_name]["is_taxonomy"]  = 1;
					$patts[$a_tax_name]["is_variation"] = 0;
					
					if(!isset($patts[$a_tax_name]["value"])) $patts[$a_tax_name]["value"]       = "";
					if(!isset($patts[$a_tax_name]["position"])) $patts[$a_tax_name]["position"] = "0";
					$patts_set = true;
					
				}
		   }
		   
		   if($patts_set)
			  updateParentVariationData($key,$res_item,$attributes_asoc,NULL,true);
		   
		   if($patts_set){
			 orderProductAttributes($patts);  
			 update_post_meta($key, "_product_attributes" , $patts);
		   }
	   }
	   
	   
	   if(property_exists($task,"tags")){
		  
		  $setval= $task->tags;
		  if($setval){
			  if(is_string($setval))
				$setval = explode(",",$setval);	
			  $setval = array_map(fn_correct_type, $setval);
		  }else
			  $setval = NULL;
		  
		  wp_set_object_terms( $key , $setval , 'product_tag' );
	   }
	   
	   foreach($custom_fileds as $cfname => $cfield){
		   
			if(property_exists($task,$cfname)){
			   	
				
			   if($cfield->type == "term"){
					if($task->{$cfname} !== NULL)
						$task->{$cfname} = array_map(fn_correct_type, arrayVal($task->{$cfname}));
					wp_set_object_terms( $key , $task->{$cfname} , $cfield->source );
			   }elseif($cfield->type == "meta"){
				    $value_coder = "";
					
				    if(isset($cfield->options)){
						
						if($cfield->options->formater){
							if($cfield->options->formater == "image"){
								if(isset($cfield->options->format)){
									if($cfield->options->format == "id"){
									   $res_item->test=$task->{$cfname};   	
									   if(isset($task->{$cfname}->id))	
										$task->{$cfname} = $task->{$cfname}->id;	
									   else
										$task->{$cfname} = "";
									}elseif($cfield->options->format == "url"){
									   if(isset($task->{$cfname}->src))
										$task->{$cfname} = $task->{$cfname}->src;	
									   else
										$task->{$cfname} = "";   
									}elseif($cfield->options->format == "object"){
										if(is_string($task->{$cfname}))
											$task->{$cfname} = serialize($task->{$cfname});
									}
								}
							}elseif($cfield->options->formater == "gallery"){
								if(isset($cfield->options->format)){
									
									
									
									if($cfield->options->format == "id"){
										
										if(is_array($task->{$cfname})){
											$dval = array();
											foreach($task->{$cfname} as $item){
												if(isset($item->id)){
													if($item->id){
														$dval[] = $item->id;		
													}	
												}
											}
											$task->{$cfname} = implode(",",$dval);
										}else{
											$task->{$cfname} = NULL;
											
										}
									}elseif($cfield->options->format == "url"){
										if(is_array($task->{$cfname})){
											$dval = array();
											foreach($task->{$cfname} as $item){
												if(isset($item->src)){
													if($item->src){
														$dval[] = $item->src;		
													}	
												}
											}
											$task->{$cfname} = implode(",",$dval);
										}else
											$task->{$cfname} = NULL;
									}elseif($cfield->options->format == "object"){
										if(is_string($task->{$cfname}))
											$task->{$cfname} = serialize($task->{$cfname});
									}
								}
							}else if($cfield->options->formater == "date"){
								if($task->{$cfname}){
									if(isset($cfield->options->unix_time)){
										if($cfield->options->unix_time){
											$task->{$cfname} = strtotime($task->{$cfname});
										}
									}
								}
							}	
						}
						
						if(isset($cfield->options->serialization)){
							$value_coder = $cfield->options->serialization;
						}	
					}
				    fn_set_meta_by_path( $key, $cfield->source, $task->{$cfname},$value_coder);  
					$full_refresh  = true;
			   }elseif($cfield->type == "post"){
			        $wpdb->query( 
						$wpdb->prepare( "UPDATE $wpdb->posts SET ".$cfield->source." = %s WHERE ID = %d", $task->{$cfname} ,$key )
				    ); 
			   }
			}
	   } 
	   
	   if(function_exists("wc_delete_product_transients"))
		wc_delete_product_transients($key);
	   
	   if($return_added){
			$res_item->surogate = $sKEY;
			$res_item->full     = product_render($key , $attributes, "data");
	   }else if($full_refresh){
		    $res_item->full     = product_render($key , $attributes, "data");
	   }
	   
	   $res_item->success = true;
	   $res[] = $res_item;
	   
	   pelm_on_product_update($key);
	   if($parent)
			pelm_on_product_update($parent);
	   
	}
	
	if($surogates_dirty){
		update_option("plem_wooc_surogates",(array)$surogates);
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
  if(isset( $this->settings["wooc_remote_import_timestamp"])){
     if(intval($_REQUEST['file_timestamp'])  <= intval($this->settings["wooc_remote_import_timestamp"])){
		echo "Requested import CSV is equal or older that latest processed!";
	    exit();
	    return;
	 }
  }
}

function &get_category_path($pcat_id){
	global $cat_asoc,$catpath_asoc;
	
	if(!isset($cat_asoc[$pcat_id]->category_path)){
		$cname = "";
		$tname = "";
		$tmp   = $cat_asoc[$pcat_id];
		do{
			if(!$cname){
				$tname = $cname = $tmp->category_name;
			}else{
				$cname = $tmp->category_name . ('/' . $cname);
				$tname = '-' . $tname;
			}
			if(isset($cat_asoc[$tmp->category_parent]))
				$tmp = $cat_asoc[$tmp->category_parent];
			else
				$tmp = false;
		}while($tmp);
		
		$cat_asoc[$pcat_id]->category_path = $cname;
		$cat_asoc[$pcat_id]->treename      = $tname;
		if($catpath_asoc !== NULL){
			$catpath_asoc[strtolower($cname)] = $pcat_id;
		}
	}
	return $cat_asoc[$pcat_id]->category_path;
}

function get_product_categories_paths($id){
	global $cat_asoc,$impexp_settings;
	
	$mvd = $impexp_settings->value_delimiter;
	if(!$mvd)
		$mvd = ",";
	
	$pcategories = wp_get_object_terms($id, 'product_cat', array('fields' => 'ids') );
	$cpaths = array();
	foreach($pcategories as $pcat_id){
		$cpaths[] = get_category_path($pcat_id);
	}
	return implode("$mvd ",$cpaths);
}


//CSV IMPORT FNs ///////////////////////////////////////////////////////////////////////////////////////////
function get_csv_order(){
	global $wpdb;
	
	$value  =$wpdb->get_col("SELECT option_value FROM $wpdb->options WHERE option_name LIKE '%pelm_order_csv%'");
	
	$csv_order=array();
	$csv_order=explode(",",$value[0]);

	return $csv_order;
	
}
function remember_csv_order($order){
	$order_string = implode(",",$order);
	update_option("pelm_order_csv", $order_string, "no");	
}

function save_json_for_cross_req_import($data, $import_uid){
	global $wpdb,$impexp_settings;
	global $start_time, $max_time, $mem_limit;
	
	$n = $data->offset;
	$added = 0;
	
	foreach($data->items as $item){
		$wpdb->query($wpdb->prepare("DELETE FROM $wpdb->options WHERE option_name = '%s';",$import_uid."_". $n));
		$wpdb->query($wpdb->prepare("INSERT INTO $wpdb->options (option_name,option_value,autoload) VALUES('%s','%s','%s');",$import_uid."_". $n, serialize($item), "no"));
		$n++;
	    $added++;
	}
	
	return $added;
}

function save_csv_for_cross_req_import($csv_file,$import_uid, $tmp_file_read = 0){
	global $wpdb,$impexp_settings;
	global $start_time, $max_time, $mem_limit;
	
	if($tmp_file_read == 0){
		
		$probecnt = file_get_contents($csv_file, false, NULL, 0,2048);
		
		/*
		$enc = mb_detect_encoding($probecnt,mb_list_encodings(), true);
		
		if( stripos($enc,"utf-8") === false){
			 echo "CSV file you tried to import is not UTF8 encoded! Correct this then try to import again!";
			 die;
			 return;
		}*/
		
		
		if(!$probecnt){
			 echo "CSV file you tried to import could not be stored on temporal loaction : " . $csv_file . "! Check folder permissions and max upload size!" ;
			 die;
			 return;
		}
		
		if( !preg_match('!!u', $probecnt) ){
			 echo "CSV file you tried to import is not UTF8 encoded! Correct this then try to import again!";
			 die;
			 return;
		}
		
		$first_line = explode("\n",$probecnt);
		if(!empty($first_line))
			$first_line = $first_line[0];
		else
			$first_line = "";
		
		if(empty($first_line)){
			echo "CSV file you tried to import is invalid. Check file then try again!";
			die;
			return;
		}elseif(strpos($first_line,$impexp_settings->delimiter) === false){
			echo "CSV file you tried to import is of invalid format. Probable cause is incorrect delimiter (import delimiter: $impexp_settings->delimiter)! Check file then try again!";
			die;
			return;
		}
	}
	
	$handle = fopen($csv_file,"r");
	$n = 0;
	if($handle){
		$added = 0;
		$data  = null;
		while (($data = fgetcsv($handle, 32768 * 2 , $impexp_settings->delimiter)) !== FALSE) {
			if($n < $tmp_file_read){
				$n++;
				continue;
			}
			
			if($n == 0){//REMOVE UTF8 BOM IF THERE
				 $bom     = pack('H*','EFBBBF');
				 $data[0] = preg_replace("/^$bom/", '', $data[0]);
			}
			
			//update_option($import_uid."_". $n, $data , "no");
			
			$wpdb->query($wpdb->prepare("INSERT INTO $wpdb->options (option_name,option_value,autoload) VALUES('%s','%s','%s');",$import_uid."_". $n, serialize($data), "no"));
			$n++;	
			$added++;
			
			if($added == 2000 || ($start_time + $max_time  < time() || getMemAllocated() + 1048576 > $mem_limit)){
				fclose($handle);
				return $n;
			}
		}
		
		fclose($handle);
		
		if(empty($data)){
			unlink($csv_file);
			return NULL;
		}
		
		return $n;
	}
	
	return NULL;
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
	$attach_id  = false;
	$upload_dir = wp_upload_dir();
	
	
	
	$ok         = false;
	
	if(!isset($image_import_path_cache)){
		$image_import_path_cache = array();
	}else if(isset($image_import_path_cache[$image])){
		return $image_import_path_cache[$image];
	}
	
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
		if(isset($search[1])){
			$search = $search[1];

			$attachment = $wpdb->get_col("SELECT ID FROM $wpdb->posts WHERE guid LIKE '%$search'"); 
			if(isset($attachment[0])){
				$image_import_path_cache[$image] = $attachment[0];
				return $image_import_path_cache[$image];
			}
		}
	}
	
	if(isset($image_import_path_cache[$image])){
		return $image_import_path_cache[$image];
	}
	
	
	$attach_id = pelm_insert_attachment_from_url($image,$post_id);
    
	$image_import_path_cache[$image] = $attach_id;
	
	return $attach_id;
	
    
	
}





global $cat_asoc,$categories, $catpath_asoc;
$catpath_asoc = NULL;
if(isset($_REQUEST["do_import"])){
	if($_REQUEST["do_import"] = "1"){
		$catpath_asoc = array();
	}
}
	
$cat_asoc   = array();
$categories = array();

$shipping_classes   = array();
$shippclass_asoc    = array();

$product_types      = array();
//$product_types_asoc = array();

$args = array(
    'number'     => 99999,
    'orderby'    => 'slug',
    'order'      => 'ASC',
    'hide_empty' => false,
    'include'    => ''
);

function catsort($a, $b){
	return strcmp ( $a->category_path , $b->category_path);
}

$woo_categories = get_terms( 'product_cat', $args );


foreach($woo_categories as $category){
   $cat = new stdClass();
   $cat->category_id     = $category->term_id;
   $cat->category_name   = $category->name;
   $cat->category_slug   = urldecode($category->slug);
   $cat->category_parent = $category->parent;
   $cat_asoc[$cat->category_id] = $cat;
   $categories[]         = $cat_asoc[$cat->category_id];
};

foreach($cat_asoc as $cid => $cat){
   get_category_path($cid);	
}

usort($categories, "catsort");

$woo_shipping_classes = get_terms( 'product_shipping_class', $args );
foreach($woo_shipping_classes as $shipping_class){
   $sc = new stdClass();
   $sc->id     = $shipping_class->term_id;
   $sc->name   = $shipping_class->name;
   $sc->slug   = urldecode($shipping_class->slug);
   $sc->parent = $shipping_class->parent;
   $shipping_classes[] = $sc;   
   $shippclass_asoc[$sc->id] = $sc;
}

$woo_ptypes = get_terms( 'product_type', $args );
foreach($woo_ptypes as $T){
   $PT = new stdClass();
   $PT->id     = $T->term_id;
   $PT->name   = $T->name;
   $PT->slug   = urldecode($T->slug);
   $PT->parent = $T->parent;
   $product_types[] = $PT;   
   //$product_types_asoc[$sc->id] = $PT;
}


function normalizeFileLineEndings ($filename) {
	$string = @file_get_contents($filename);
	if (!string) {
		return false;
	}
    $out = "";
	
	$string = explode("\r",$string);
	
	for($i = 0 ; $i < count($string); $i++){
		$string[$i] = explode("\n",$string[$i]);
		for($j = 0; $j < count($string[$i]);$j++){
			if($string[$i][$j]){
				$out .= ($string[$i][$j] . "\r\n");
			}
		}
	}
	
	file_put_contents($filename, $out);
	return true;
}


////////////////////////////////////////////////////////////////////////////////////////////////////////////


$import_count = 0;
$csv_row_processed_count = 0;
if(isset($_REQUEST["do_import"])){
	if($_REQUEST["do_import"] = "1"){
		
		
		$json_import = false;
		if(isset($_REQUEST['json_import'])){
			$json_import = $_REQUEST['json_import'];
		}
		
		$import_uid = isset($_REQUEST["import_uid"]) ? $_REQUEST["import_uid"] : uniqid("pelm_import_");
		$n = 0;
		if ($json_import || isset($_FILES['file']['tmp_name']) || isset($_REQUEST["continueFrom"]) || isset($_REQUEST["tmp_file"]) || isset($_REQUEST["commit_import"])) {
			
			$tmp_file   = NULL;
			$tmp_file_n = NULL;
			$echo_prep = false;
			
			if(!isset($_REQUEST["commit_import"])){
				
				if($json_import){
					
					$json  = file_get_contents('php://input');
					$data  = json_decode($json);
					$resp = new stdClass;
					//$data->offset
					
					$resp->imported_count = save_json_for_cross_req_import($data, $import_uid);
					$resp->import_count   = count($data->items);
					$resp->import_uid     = $import_uid;
					unset($data->items);
					$resp->request        = $data;
					
					header('Content-Type: text/json');
					echo json_encode($resp);
					die;
					
				}else{
					
					if(isset($_FILES['file']['tmp_name']) && !isset($_REQUEST["continueFrom"])){
						$wpdb->query("DELETE FROM $wpdb->options WHERE option_name LIKE 'pelm_import_%'");
						$tmp_file = wp_upload_dir();
						$tmp_file = $tmp_file["path"] . DIRECTORY_SEPARATOR . $import_uid .".csv";
						$tmp_file = str_replace(array("\\\\","\\\\","\\\\","\\","/"),array("\\","\\","\\", DIRECTORY_SEPARATOR,DIRECTORY_SEPARATOR),$tmp_file);
						move_uploaded_file( $_FILES['file']['tmp_name'], $tmp_file);
						
						normalizeFileLineEndings($tmp_file);
						
						$tmp_file_n = save_csv_for_cross_req_import($tmp_file, $import_uid, 0 );
						$echo_prep  = true;
						
						$wpdb->query("DELETE p from $wpdb->posts as p LEFT JOIN $wpdb->posts as pp on pp.ID = p.post_parent where p.post_type ='product_variation' AND  coalesce(p.post_parent,0) > 0 AND pp.ID IS NULL");
						$wpdb->query("DELETE PM FROM $wpdb->postmeta as PM LEFT JOIN $wpdb->posts as P ON P.ID = PM.post_id WHERE P.ID IS NULL");
						
					}elseif(isset($_REQUEST["tmp_file"])){
						$tmp_file	= $_REQUEST["tmp_file"];
						$tmp_file = str_replace(array("\\\\","\\\\","\\\\","\\","/"),array("\\","\\","\\", DIRECTORY_SEPARATOR,DIRECTORY_SEPARATOR),$tmp_file);
						$tmp_file_n	= $_REQUEST["tmp_file_n"];
						$tmp_file_n = save_csv_for_cross_req_import($tmp_file, $import_uid, $tmp_file_n);
						$echo_prep  = true;
					}
				}
			}
			
			if($echo_prep){
				?>
					<!DOCTYPE html>
					<html>
						<head>
							<style type="text/css">
								html, body{
									background:#505050;
									color:white;
									font-family:sans-serif;	
								}
							</style>
						</head>
						<body>
							<form method="POST" id="continueImportForm">
								<h2><?php echo __("Importing...",'productexcellikemanager'); ?></h2>
								<?php if($tmp_file_n) { ?>
								<p><?php echo __("Preparing product data ",'productexcellikemanager').$tmp_file_n; ?></p>
								<hr/>
								<input type="hidden" name="tmp_file" value="<?php echo $tmp_file ;?>">
								<input type="hidden" name="tmp_file_n" value="<?php echo $tmp_file_n;?>">
								<?php }else{?>
								<p><?php echo __("Commiting import...",'productexcellikemanager'); ?></p>
								<hr/>
								<input type="hidden" name="commit_import" value="1">
								<?php }?>
								
								<input type="hidden" name="import_uid" value="<?php echo $import_uid;?>">
								
								<input type="hidden" name="do_import" value="1">
								<?php if(isset($_REQUEST["sortOrder"])) {?>
									<input type="hidden" name="sortOrder" value="<?php echo $orderby;?>">
								<?php } 
								if(isset($_REQUEST["sortColumn"])) {?>
									<input type="hidden" name="sortColumn" value="<?php echo $sort_order;?>">
								<?php } 
								if(isset($_REQUEST["page_no"])) {?>
									<input type="hidden" name="page_no" value="<?php echo $_REQUEST["page_no"];?>">
								<?php }
								if(isset($_REQUEST["limit"])) {?>
									<input type="hidden" name="limit" value="<?php echo $_REQUEST["limit"];?>">
								<?php } 
								if(isset($_REQUEST["sku"])) {?>
									<input type="hidden" name="sku" value="<?php echo $_REQUEST["sku"];?>">
								<?php } 
								if(isset($_REQUEST["product_name"])) {?>
									<input type="hidden" name="product_name" value="<?php echo $_REQUEST["product_name"];?>">
								<?php }
								if(isset($_REQUEST["product_category"])) {?>
									<input type="hidden" name="product_category" value="<?php echo $_REQUEST["product_category"];?>">
								<?php } 									
								if(isset($_REQUEST["product_shipingclass"])) {?>
									<input type="hidden" name="product_shipingclass" value="<?php echo $_REQUEST["product_shipingclass"];?>">
								<?php } 
								if(isset($_REQUEST["product_tag"])) {?>
									<input type="hidden" name="product_tag" value="<?php echo $_REQUEST["product_tag"];?>">
								<?php } 
								if(isset($_REQUEST["product_status"])) {?>
									<input type="hidden" name="product_status" value="<?php echo $_REQUEST["product_status"];?>">
								<?php } ?>
								
								<?php foreach($attributes as $attr){ 
										if(isset($_REQUEST["pattribute_" . $attr->id])){
											if($_REQUEST["pattribute_" . $attr->id]){
											?>
											<input type="hidden" name="pattribute_<?php echo $attr->id;?>" value="<?php echo $_REQUEST["pattribute_" . $attr->id];?>">
											<?php	
											}
										}
									 } ?>
								<?php foreach($custom_fileds as $cf){ 
									if($cf->type == "term"){
								?>
								<input type="hidden" name="<?php echo $cf->name;?>" value="<?php echo $_REQUEST[$cf->name];?>">
								<?php 
									}
								} ?>
								<input type="hidden" name="import_count" value="<?php echo $import_count ;?>" />
							</form>
							<script type="text/javascript">
								document.getElementById("continueImportForm").submit();
							</script>
						</body>
					</html>
				<?php
				die;
				return;
			}
			
			$id_index                  = -1;
			$price_index               = -1;
			$price_o_index             = -1;
			$stock_index               = -1;
			$sku_index                 = -1;
			$name_index                = -1;
            $slug_index                = -1;
			$status_index              = -1;
			
			$categories_ids_index      = -1;
			$categories_names_index    = -1;
			$categories_paths_index    = -1;
		    
			
			$shipping_class_name_index = -1;
			$weight_index              = -1;
			$length_index              = -1;
			$width_index               = -1;
			$height_index              = -1;
			$featured_index            = -1;
			$virtual_index             = -1;
			$downloadable_index        = -1;
			$downloads_index           = -1;
			$tax_status_index          = -1;
			$tax_class_index           = -1;
			$backorders_index          = -1;
			$tags_names_index          = -1;
			$product_type_index        = -1;
			$parent_index              = -1;
			$featured_image_index	   = -1; 
			$product_gallery_index     = -1; 
			
			$terms_index               = -1; 

			$attribute_indexes         = array();
			$attribute_visibility_indexes = array();
			$cf_indexes                = array();
			$col_count = 0;
			$skip_first                = false;
			$custom_import             = false;
			$imported_ids              = array();
			$data_column_order         = array();
			$load_from_cross_state     = false;      
			
			$mvd = $impexp_settings->value_delimiter;
			if(!$mvd)
				$mvd = ",";
			
			
			if($impexp_settings){
				if($impexp_settings->use_custom_import){
					$cic = array();
					foreach(explode(",",$impexp_settings->custom_import_columns) as $col){
					   if($col)
						$cic[] = $col;
					}
					
					if($impexp_settings->first_row_header)
						$skip_first = true;
						
					$col_count         = count($cic);
					$data_column_order = $cic;
					$custom_import = true;
				}
			}
			
			
			
			$csv_row_processed_count = 0;
			global $image_import_path_cache;
			$image_import_path_cache = get_option("pelm_impc",array());
			
			if(!isset($impexp_settings->delimiter))
				$impexp_settings->delimiter = ",";
			
			if(isset($_REQUEST["import_count"]))
				$import_count      = intval($_REQUEST["import_count"]);
			
			
			
			if(!$custom_import){
				if(isset($_REQUEST["continueFrom"])){
					if(intval($_REQUEST["continueFrom"]) > 0)
						$data_column_order = get_csv_order();	
					else{
						$data_column_order = get_option($import_uid."_0",null);
						$n = 1;
					}
				} else {
					$data_column_order = get_option($import_uid."_0",null);
					$n = 1;
				}
			}else if($skip_first){
				$n = 1;
			}
			
			if(isset($_REQUEST["continueFrom"]))
				$n = intval($_REQUEST["continueFrom"]);
				
			$data = get_option($import_uid."_".$n,null);
			
			if(!$data_column_order){
				die;
				return;
			}
			
			$col_count = count($data_column_order);
			for($i = 0 ; $i < $col_count; $i++){
				$data_column_order[$i] = trim($data_column_order[$i]);
				if($data_column_order[$i]     == "id") $id_index = $i;
				elseif($data_column_order[$i] == "price") $price_index = $i;
				elseif($data_column_order[$i] == "override_price") $price_o_index = $i;
				elseif($data_column_order[$i] == "sku") $sku_index   = $i;
				elseif($data_column_order[$i] == 'stock') $stock_index = $i;
				elseif($data_column_order[$i] == 'name') $name_index = $i;
				elseif($data_column_order[$i] == 'slug') $slug_index = $i;
				elseif($data_column_order[$i] == 'status') $status_index = $i;
				elseif($data_column_order[$i] == 'shipping_class' || $data_column_order[$i] == 'shipping_class_name') $shipping_class_name_index = $i;
				elseif($data_column_order[$i] == 'tags' || $data_column_order[$i] == 'tags_names') $tags_names_index = $i;
				elseif($data_column_order[$i] == 'weight') $weight_index = $i;
				elseif($data_column_order[$i] == 'length') $length_index = $i;
				elseif($data_column_order[$i] == 'width') $width_index = $i;
				elseif($data_column_order[$i] == 'height') $height_index = $i;
				elseif($data_column_order[$i] == 'featured') $featured_index = $i;
				elseif($data_column_order[$i] == 'virtual') $virtual_index = $i;
				elseif($data_column_order[$i] == 'downloadable') $downloadable_index = $i;
				elseif($data_column_order[$i] == 'downloads') $downloads_index = $i;
				elseif($data_column_order[$i] == 'tax_status') $tax_status_index = $i;
				elseif($data_column_order[$i] == 'tax_class') $tax_class_index = $i;
				elseif($data_column_order[$i] == 'backorders') $backorders_index = $i;
				elseif($data_column_order[$i] == 'product_type') $product_type_index = $i;
				elseif($data_column_order[$i] == 'parent') $parent_index = $i;
				elseif($data_column_order[$i] == 'image') $featured_image_index = $i;
				elseif($data_column_order[$i] == 'gallery') $product_gallery_index = $i;
				elseif($data_column_order[$i] == 'categories_ids') $categories_ids_index = $i;
				elseif($data_column_order[$i] == 'categories_names') $categories_names_index = $i;
				elseif($data_column_order[$i] == 'categories_paths') $categories_paths_index = $i;
				elseif($data_column_order[$i] == 'categories') $categories_names_index = $i;
				elseif($data_column_order[$i] == 'terms') $terms_index = $i;
				
				foreach($attributes as $att){
					if('pattribute_'.$att->id == $data_column_order[$i]){
						$attribute_indexes[$att->name] = $i;
						break;
					}
					
					if('pattribute_'.$att->id.'_visible' == $data_column_order[$i]){
						$attribute_visibility_indexes[$att->name] = $i;
						break;
					}
				}
				
				foreach($custom_fileds as $cfname => $cfield){
					if($cfname == $data_column_order[$i]){
						$cf_indexes[$cfname] = $i;
						break;
					}
				}
			}
			
			remember_csv_order($data_column_order);
					
			while ($data) {
				
				$data = array_map("toUTF8",$data);
				
			    if($csv_row_processed_count > 0 && ($csv_row_processed_count >= 300 || $start_time + $max_time  < time() || getMemAllocated() + 1048576 > $mem_limit)){
					update_option("pelm_impc", $image_import_path_cache, "no");	
					//////////////////////BREAK EXEC AND DO ANOTHER REQUEST/////////////////////////
					if($json_import){
						header('Content-Type: text/json');
						$resp = new stdClass;
						$resp->import_uid     = $import_uid;
						$resp->continueFrom   = $n;
						$resp->proccessed      = $csv_row_processed_count;
						$resp->do_import      = 1;
						$resp->import_count   = $import_count;
						echo json_encode($resp);
					}else{
				   	
					?>
						<!DOCTYPE html>
						<html>
							<head>
								<style type="text/css">
									html, body{
										background:#505050;
										color:white;
										font-family:sans-serif;	
									}
								</style>
							</head>
							<body>
								<form method="POST" id="continueImportForm">
									<h2><?php echo __("Importing...",'productexcellikemanager'); ?></h2>
									<p><?php echo $import_count; ?> <?php echo __("products/product variations entries processed from ",'productexcellikemanager'); ?> <?php echo $n." CSV ".__("rows.",'productexcellikemanager');  ?> </p>
									<hr/>
									
									<input type="hidden" name="import_uid" value="<?php echo $import_uid;?>">
									<input type="hidden" name="continueFrom" value="<?php echo $n;?>">
									<input type="hidden" name="do_import" value="1">
									<?php if(isset($_REQUEST["sortOrder"])) {?>
										<input type="hidden" name="sortOrder" value="<?php echo $orderby;?>">
									<?php } 
									if(isset($_REQUEST["sortColumn"])) {?>
										<input type="hidden" name="sortColumn" value="<?php echo $sort_order;?>">
									<?php } 
									if(isset($_REQUEST["page_no"])) {?>
										<input type="hidden" name="page_no" value="<?php echo $_REQUEST["page_no"];?>">
									<?php }
									if(isset($_REQUEST["limit"])) {?>
										<input type="hidden" name="limit" value="<?php echo $_REQUEST["limit"];?>">
									<?php } 
									if(isset($_REQUEST["sku"])) {?>
										<input type="hidden" name="sku" value="<?php echo $_REQUEST["sku"];?>">
									<?php } 
									if(isset($_REQUEST["product_name"])) {?>
										<input type="hidden" name="product_name" value="<?php echo $_REQUEST["product_name"];?>">
									<?php }
									if(isset($_REQUEST["product_category"])) {?>
										<input type="hidden" name="product_category" value="<?php echo $_REQUEST["product_category"];?>">
									<?php } 									
									if(isset($_REQUEST["product_shipingclass"])) {?>
										<input type="hidden" name="product_shipingclass" value="<?php echo $_REQUEST["product_shipingclass"];?>">
									<?php } 
									if(isset($_REQUEST["product_tag"])) {?>
										<input type="hidden" name="product_tag" value="<?php echo $_REQUEST["product_tag"];?>">
									<?php } 
									if(isset($_REQUEST["product_status"])) {?>
										<input type="hidden" name="product_status" value="<?php echo $_REQUEST["product_status"];?>">
									<?php } ?>
									
									<?php foreach($attributes as $attr){ 
											if(isset($_REQUEST["pattribute_" . $attr->id])){
												if($_REQUEST["pattribute_" . $attr->id]){
												?>
												<input type="hidden" name="pattribute_<?php echo $attr->id;?>" value="<?php echo $_REQUEST["pattribute_" . $attr->id];?>">
												<?php	
												}
											}
										 } ?>
									
									<?php foreach($custom_fileds as $cf){ 
										if($cf->type == "term"){
									?>
									<input type="hidden" name="<?php echo $cf->name;?>" value="<?php echo $_REQUEST[$cf->name];?>">
									<?php 
										}
									} ?>
									
									<input type="hidden" name="import_count" value="<?php echo $import_count ;?>" />
								</form>
								<script type="text/javascript">
										document.getElementById("continueImportForm").submit();
								</script>
							</body>
						</html>
						<?php
					}
					if( isset( $impexp_settings->notfound_setpending )){
						if($impexp_settings->notfound_setpending){
							 if(!empty($imported_ids)){
								$wpdb->query( 
									$wpdb->prepare( "UPDATE $wpdb->posts SET post_status = 'pending' WHERE (post_type LIKE 'product_variation' or post_type LIKE 'product') AND NOT ID IN (". implode(",", $imported_ids ) .")")
								 ); 
							 }else{
								$wpdb->query( 
									$wpdb->prepare( "UPDATE $wpdb->posts SET post_status = 'pending' WHERE (post_type LIKE 'product_variation' or post_type LIKE 'product')")
								 );
							 }
						}
					}
					die;
					return;
					exit;
				   
				   ///////////////////////////////////////////////////////////////////////////////
				   break;
			    }
				//UPD ROUTINE/////////////////////////////////////////////////////////////////////////
				
				$csv_row_processed_count++;
				$id = NULL;
				if($id_index >= 0)	
					$id = intval($data[$id_index]);
				
				

				if($sku_index != -1)
					$data[$sku_index] = trim($data[$sku_index]);

				if(!$id && $sku_index != -1){
					if($data[$sku_index]){
						$res = $wpdb->get_col("select post_id from $wpdb->postmeta where meta_key like '_sku' and meta_value like '".$data[$sku_index]."'");
						if(!empty($res))
							$id = $res[0];
					}
				}	

				if(!$id && $name_index != -1){
					if($data[$name_index]){
						$res = $wpdb->get_col("select ID from $wpdb->posts where cast(post_title as char(255)) like '" . $data[$name_index] . "' and (post_type like 'product' OR post_type like 'product_variation') ");
						if(!empty($res))
							$id = $res[0];
					}
				}

				$continue = false;
				
				if(trim(implode("",$data))){
					if(!$id){
						if(isset($plem_settings['enable_add'])){
							if($plem_settings['enable_add']){
								
								$with_par = NULL;
								if($parent_index > -1){
									if($data[$parent_index]){
										if(strpos($data[$parent_index],":") !== false){
											$criteria = explode(":",$data[$parent_index]);
											$criteria_key = strtolower($criteria[0]);
											$criteria_val = trim($criteria[1]);
											if($criteria_key == "sku"){
												$with_par = $wpdb->get_col("select ID from $wpdb->posts LEFT JOIN $wpdb->postmeta ON $wpdb->postmeta.post_id = $wpdb->posts.ID where $wpdb->postmeta.meta_key like '_sku' AND $wpdb->postmeta.meta_value LIKE '$criteria_val' LIMIT 1");
												if(!empty($with_par))
													$with_par = $with_par[0];
												else{
													$continue = true;
													$with_par = NULL;
												}
											}elseif($criteria_key == "title"){
												$with_par = $wpdb->get_col("select ID from $wpdb->posts where post_title like '$criteria_val' LIMIT 1");
												if(!empty($with_par))
													$with_par = $with_par[0];
												else{
													$continue = true;
													$with_par = NULL;
												}
											}elseif($criteria_key == "slug"){
												$criteria_val = sanitize_title($criteria_val);
												$with_par = $wpdb->get_col("select ID from $wpdb->posts where post_name like '$criteria_val' LIMIT 1");
												if(!empty($with_par))
													$with_par = $with_par[0];
												else{
													$continue = true;
													$with_par = NULL;
												}
											}elseif($criteria_key == "id"){
												$with_par = intval($criteria_val);
											}
										}else{
											$with_par = intval($data[$parent_index]);
										}
										
										if(false === get_post_status( $with_par ))
											$continue = true;	
									}	
								}
								if(!$continue){
									$id = plem_add_product($with_par);
								}
							}else
								$continue = true;	
						}else		
							$continue = true;
					}
				}else
					$continue = true;
				
				if( $id){
					if(false === get_post_status( $id ))
						$continue = true;
				}
				
				if($continue){
					$n++;
					$data = get_option($import_uid."_".$n,null);
					continue;
				}
				
				$parent = get_ancestors($id,'product');
				if( !empty($parent)){
					$parent = $parent[0];
				}else
					$parent = 0;
				
				$imported_ids[] = $id;	
								
				while(count($data) < $col_count)
				  $data[] = NULL;	
					
				$post_update = array( 'ID' => $id );

				if($sku_index > -1){ 
				  update_post_meta($id, '_sku', $data[$sku_index]);
				}

				if($stock_index > -1){ 
				 
				  
				  update_post_meta($id, '_stock', $data[$stock_index]);
				  if(is_numeric($data[$stock_index])){
					if(floatval($data[$stock_index]) == 0)
						update_post_meta($id, '_stock_status', 'outofstock');
					else
						update_post_meta($id, '_stock_status', 'instock');
					update_post_meta($id, '_manage_stock', 'yes');
					
					
					
				  }else
					update_post_meta($id, '_manage_stock', 'no');
				
				  
				}
				
				$any_price_set = false;
				if($price_index > -1){ 
				  if($data[$price_index])
					$data[$price_index] = Getfloat($data[$price_index]);

				  update_post_meta($id, '_regular_price', $data[$price_index]);
				  $any_price_set = true;
				}

				if($price_o_index > -1){
				  if($data[$price_o_index])
					$data[$price_o_index]= Getfloat($data[$price_o_index]);
					
				  update_post_meta($id, '_sale_price', $data[$price_o_index]);
				  $any_price_set = true;
				}

				if($any_price_set){
					$s_price = get_post_meta($id,'_sale_price',true);
					$r_price = get_post_meta($id,'_regular_price',true);
					$_price = $s_price ? $s_price :  $r_price;
					update_post_meta($id, '_price', $_price);
					
					if($parent){
						updateParentPriceData($parent);
					}
				}

				if(!$parent){	
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
					
				}else{
					if($status_index > -1){
					  $post_update['post_status'] = strtolower(trim($data[$status_index])) == "publish" ? "publish" : "private";
					}
				}
				
				if($name_index > -1){ 
				  if($parent)
					  $post_update['post_title'] = "Variation #" . $id . " of ".get_the_title($parent);
				  else
					  $post_update['post_title'] = $data[$name_index];  
				}

				if($slug_index > -1){ 
				  $post_update['post_name'] = urlencode( $data[$slug_index] );  
				}
			
				if(count($post_update) > 1){
				  wp_update_post($post_update);
				}

				
				if($terms_index > -1){
					//fn_correct_type
					
					global $term_taxes;
					if(!isset($product_term_data)){
						$term_taxes = array();
						
						$term_taxes['term_id'] = $wpdb->get_results(
													"SELECT 
														DISTINCT t.term_id, t.slug, t.name, tt.taxonomy
														FROM
														$wpdb->posts as p
														RIGHT JOIN
														$wpdb->term_relationships as tr on tr.object_id = p.ID
														LEFT JOIN
														$wpdb->term_taxonomy as tt on tt.term_taxonomy_id = tr.term_taxonomy_id
														LEFT JOIN
														$wpdb->terms as t on t.term_id = tt.term_id
														WHERE p.post_type = 'product' OR p.post_type = 'product_variation'",OBJECT_K);
						$term_taxes['slug'] = $wpdb->get_results(
													"SELECT 
														DISTINCT t.slug, t.term_id, t.name, tt.taxonomy
														FROM
														$wpdb->posts as p
														RIGHT JOIN
														$wpdb->term_relationships as tr on tr.object_id = p.ID
														LEFT JOIN
														$wpdb->term_taxonomy as tt on tt.term_taxonomy_id = tr.term_taxonomy_id
														LEFT JOIN
														$wpdb->terms as t on t.term_id = tt.term_id
														WHERE p.post_type = 'product' OR p.post_type = 'product_variation'",OBJECT_K);

						$term_taxes['name'] = $wpdb->get_results(
													"SELECT 
														DISTINCT t.name, t.slug, t.term_id ,tt.taxonomy
														FROM
														$wpdb->posts as p
														RIGHT JOIN
														$wpdb->term_relationships as tr on tr.object_id = p.ID
														LEFT JOIN
														$wpdb->term_taxonomy as tt on tt.term_taxonomy_id = tr.term_taxonomy_id
														LEFT JOIN
														$wpdb->terms as t on t.term_id = tt.term_id
														WHERE p.post_type = 'product' OR p.post_type = 'product_variation'",OBJECT_K);														
					}
					
					if($data[$terms_index]){
						$terms = explode($mvd,$data[$terms_index]);
						
						$pa = null;
						$set_terms = array();
						
						foreach($terms as $t){
							$t = trim($t);
							$taxon = "";
							if(is_numeric($t) && strpos($t,".") === false){
								$terms_n = intval($t);
								if(isset($term_taxes['term_id'][$terms_n])){
									$taxon = $term_taxes['term_id'][$terms_n]->taxonomy;
									if(!isset($set_terms[$taxon]))
										$set_terms[$taxon] = array();
									
									$set_terms[$taxon][] = $terms_n;
								}
							}else{
								$terms_s = $t;
								if(isset($term_taxes['slug'][$terms_s])){
									$taxon = $term_taxes['slug'][$terms_s]->taxonomy;
									
									if(!isset($set_terms[$taxon]))
										$set_terms[$taxon] = array();
									
									$set_terms[$taxon][] = $term_taxes['slug'][$terms_s]->term_id;
								}else if(isset($term_taxes['name'][$terms_s])){
									$taxon = $term_taxes['name'][$terms_s]->taxonomy;
									if(!isset($set_terms[$taxon]))
										$set_terms[$taxon] = array();
									
									$set_terms[$taxon][] = $term_taxes['name'][$terms_s]->term_id;
								}
							}
							
							if($taxon){
								if(strpos($taxon,"pa_") === 0){
									
									if(!$pa)
										$pa = get_post_meta($id,'_product_attributes',true);
									
									if(!$pa)
										$pa = array();
									
									
									
									if(!isset($pa[$taxon])){
										$pa[$taxon] = array(
											'name'         => $taxon,
											'value'        => '',
											'position'     => count($pa) + 1,
											'is_visible'   => 1,
											'is_variation' => 0,
											'is_taxonomy'  => 1
										);
									}
									
								}
							}
						}
						
						foreach($set_terms as $tax => $termids){
							wp_set_object_terms( $id , $termids , $tax, stripos($tax,'tag') !== false);
						}
						
						if($pa){
							if($pa[""])
								unset($pa[""]);
							update_post_meta($id, "_product_attributes" , $pa);
							$pa = null;
						}
					}
				}
				
				if(!$parent){
					if($categories_names_index > -1){
					    wp_set_object_terms( $id ,  array_map('trim', explode($mvd,$data[$categories_names_index])) , 'product_cat' );
					}
					
					if($categories_ids_index > -1){
						wp_set_object_terms( $id ,  array_map('intval', explode($mvd,$data[$categories_ids_index])) , 'product_cat' );
					}
					
					if($categories_paths_index > -1){
						$cids = array();
						$cpaths = explode($mvd,$data[$categories_paths_index]);
						for($I =0; $I < count($cpaths); $I++){
							$ckey = strtolower(implode("/",array_map('trim',explode("/",$cpaths[$I]))));
							if(isset($catpath_asoc[$ckey])){
								$cids[] = intval($catpath_asoc[$ckey]);
							}else{
								$ckey = array_pop(explode("/",$ckey));
								foreach($category as $c){
									if(strtolower($c->category_name) == $ckey){
										$cids[] = $c->category_id;
										break;
									}
								}
							}
						}
						wp_set_object_terms( $id , asArray($cids) , 'product_cat' );
					}
				}
				
				
				
				if($product_type_index != -1){
				  if(!$parent){
					wp_set_object_terms( $id , array($data[$product_type_index]) , 'product_type' );
					$false = false;
					updateParentVariationData($id,$false,$attributes_asoc,NULL);
				  }
				}

				if($shipping_class_name_index > -1){
				  if($parent && !$data[$shipping_class_name_index]){
					$par_sc = wp_get_object_terms($parent, 'product_shipping_class', array('fields' => 'ids'));  
					wp_set_object_terms( $id ,  asArray($par_sc) , 'product_shipping_class' );  
				  }else 
					wp_set_object_terms( $id ,  array_map('trim', explode($mvd,$data[$shipping_class_name_index])) , 'product_shipping_class' );
				}

				if($weight_index > -1){
				  update_post_meta($id, '_weight', $data[$weight_index]);
				}

				if($length_index > -1){
				  update_post_meta($id, '_length', $data[$length_index]);
				}

				if($width_index > -1){
				  update_post_meta($id, '_width', $data[$width_index]);
				}

				if($height_index > -1){
				  update_post_meta($id, '_height', $data[$height_index]);
				}

				if(!$parent){
					if($featured_index > -1){
					  update_post_meta($id, '_featured', strtolower($data[$featured_index]));
					}
				}
				
				$ptype = wp_get_object_terms($id, 'product_type', array('fields' => 'names'));
				if(is_array($ptype))
					$ptype = $ptype[0];
				
				if($parent || $ptype == "simple" ){
					if($virtual_index > -1){
						update_post_meta($id, '_virtual', strtolower($data[$virtual_index]));
					}
					
					if($downloadable_index > -1){
						update_post_meta($id, '_downloadable', strtolower($data[$downloadableindex]));
					}
				}else{
					if($virtual_index > -1){
						update_post_meta($id, '_virtual', "no");
					}
					
					if($downloadable_index > -1){
						update_post_meta($id, '_downloadable', "no");
					}
				}
				
				if($downloads_index > -1){
					$fdownloads = explode($mvd, $data[$downloads_index]);
					$downloads = array(); 
					if($fdownloads){
						foreach($fdownloads as $download_src){
							$d_item = array();
							$d_item["name"] = pathinfo($download_src, PATHINFO_FILENAME); 
							$d_item["file"] = $download_src; 
							$downloads[md5($download_src)]	= $d_item;
						}
					}
					update_post_meta($id,'_downloadable_files',$downloads);
				}

				if(!$parent){
					if($tax_status_index > -1){
					  if(!$parent){
						  if(!$data[$tax_status_index])
							update_post_meta($id, '_tax_status', "none");
						  else 
							update_post_meta($id, '_tax_status', $data[$tax_status_index]);
					  }
					}

					if($tax_class_index > -1){
					  if(!$data[$tax_class_index] && $parent)  
						 update_post_meta($id, '_tax_class', "parent"); 
					  else
						 update_post_meta($id, '_tax_class', $data[$tax_class_index]);
					}
				}

				if($backorders_index > -1){
				  update_post_meta($id, '_backorders', $data[$backorders_index]);
				}

				
				if($parent){
				   $attributes_set = array();
				   $parAttCFG = get_post_meta($parent,'_product_attributes',true);
				   if(!$parAttCFG)
					   $parAttCFG = array();
				   
				   $update_par_att = false;
				   foreach($attributes as $attr){
						if(isset($attribute_indexes[$attr->name])){
							if(isset($data[$attribute_indexes[$attr->name]])){
								
								$att_value = explode($mvd,$data[$attribute_indexes[$attr->name]]);
								
								
								if(!empty($att_value)){
									$att_value = $att_value[0];
									$att_value = toUTF8($att_value);
								}else
									$att_value = NULL;
								
								$tnames    = array();
								$tids      = array();
								$val       = "";
								$attributes_set[] = $attr->name;
								if($att_value){
									
									$term = null;
									foreach($attr->values as $trm){
									   if(trim(strtolower($trm->name)) == trim(strtolower($att_value))){
											$term = $trm;
											break;
									   }
									}
									
									if($term){
										update_post_meta($id, 'attribute_pa_' . $attr->name, $term->slug);
									}else{
										update_post_meta($id, 'attribute_pa_' . $attr->name, $att_value);
									}
									
									$pa_dirty = false;
									if(!isset($parAttCFG['pa_' . $attr->name])){
										$pa_dirty = true;
									    $parAttCFG['pa_' . $attr->name] = array (
													'name'         => 'pa_' . $attr->name,
													'value'        => '',
													'position'     => count($parAttCFG),
													'is_visible'   => 1,
													'is_variation' => 1,
													'is_taxonomy'  => 1
												);	
									}
									
									if(!$parAttCFG['pa_' . $attr->name]["is_variation"])
										$pa_dirty = true;
									
									if($pa_dirty){
										$parAttCFG['pa_' . $attr->name]["is_variation"] = 1;
										orderProductAttributes($parAttCFG);
										if($parAttCFG[""])
											unset($parAttCFG[""]);
										update_post_meta($parent,'_product_attributes',$parAttCFG);
									}
									
									$update_par_att = true;
									
								}else{
									
									delete_post_meta($id, 'attribute_pa_' . $attr->name);
									
								}
								
							}	 
						}
				   }
				   
				   if($update_par_att){
						$false = false;   
						updateParentVariationData($parent,$false,$attributes_asoc,$attributes_set);
						wp_set_object_terms($parent, array('variable'), 'product_type');
						
						//echo "TEST_V".$id;die;
				   }
				   
				}else{ 

				   $patts = get_post_meta($id,'_product_attributes',true);
				   
				   $patts_set = false;
				   if(!$patts)
					$patts = array();
				
				   foreach($attributes as $attr){
					   $a_tax_name = 'pa_' . $attr->name;
					   if(isset($attribute_indexes[$attr->name])){
						   
						    $atts_input = array_map("trim", explode($mvd,$data[$attribute_indexes[$attr->name]]));
							
							for($i =0; $i < count($atts_input); $i++){
								$atts_input[$i] = str_ireplace(array("'",'"'),array("",''),$atts_input[$i]); 
							}
							
							if(!isset($attr->ndict)){
								$attr->ndict = array();
								foreach($attr->values as $trm){
									$attr->ndict[trim(strtolower($trm->name))] = $trm->slug;
								}
							}
							
							for($a_i = 0 ; $a_i < count($atts_input); $a_i++){
								if(isset($attr->ndict[trim(strtolower($atts_input[$a_i]))]))
									$atts_input[$a_i] = $attr->ndict[trim(strtolower($atts_input[$a_i]))];
							}
							
							//OVDE
							$wpdb->query( $wpdb->prepare("DELETE
								pm
								FROM
								wp_postmeta as pm
								LEFT JOIN
								wp_posts as p ON p.ID = pm.post_id
								WHERE
								p.post_parent = %d
								AND
								pm.meta_key like 'attribute_%s'",$id,$a_tax_name));
							 
							wp_set_object_terms( $id , asArray($atts_input) , $a_tax_name );
							
							if(!$data[$attribute_indexes[$attr->name]]){
							   if(isset($patts[$a_tax_name]))
								unset($patts[$a_tax_name]);
							}else{
								if(!isset($patts[$a_tax_name])){
									$patts[$a_tax_name] = array();
								}elseif(!$patts[$a_tax_name]){
									$patts[$a_tax_name] = array();
								}
								$patts[$a_tax_name]["name"]         = $a_tax_name;
								if(!isset($patts[$a_tax_name]["is_visible"]))
									$patts[$a_tax_name]["is_visible"]   = 1;
								$patts[$a_tax_name]["is_taxonomy"]  = 1;
								$patts[$a_tax_name]["is_variation"] = 0;
								
								if(!isset($patts[$a_tax_name]["value"])) $patts[$a_tax_name]["value"]       = "";
								if(!isset($patts[$a_tax_name]["position"])) $patts[$a_tax_name]["position"] = count($patts) - 1;
								
							}
							
							
							
							
							$patts_set = true;
						}
						
						if(isset($attribute_visibility_indexes[$attr->name])){
							
							if(!isset($patts[$a_tax_name])){
									$patts[$a_tax_name] = array();
							}elseif(!$patts[$a_tax_name]){
								$patts[$a_tax_name] = array();
							}
							$patts[$a_tax_name]["name"]         = $a_tax_name;
							$patts[$a_tax_name]["is_visible"]   = filter_var($data[$attribute_visibility_indexes[$attr->name]], FILTER_VALIDATE_BOOLEAN) ? 1 : 0;
							$patts[$a_tax_name]["is_taxonomy"]  = 1;
							$patts[$a_tax_name]["is_variation"] = 0;
							
							if(!isset($patts[$a_tax_name]["value"])) $patts[$a_tax_name]["value"]       = "";
							if(!isset($patts[$a_tax_name]["position"])) $patts[$a_tax_name]["position"] = count($patts) - 1;
							$patts_set = true;
						}
				   }
				   
				   if($patts_set){
					    orderProductAttributes($patts);
						update_post_meta($id, "_product_attributes" , $patts);
						if(function_exists("wc_delete_product_transients"))
							wc_delete_product_transients($id);
				
						//echo "TEST_P".$id;die;
						//$false = false;   
						//updateParentVariationData($parent,$false,$attributes_asoc,null);
				   }
				}

				if($tags_names_index > -1){
				  wp_set_object_terms( $id , explode($mvd,$data[$tags_names_index]) , 'product_tag' );
				}

				foreach($custom_fileds as $cfname => $cfield){ 
					if(isset($cf_indexes[$cfname])){
					   if($cfield->type == "term"){
							wp_set_object_terms( $id , array_map("trim", explode($mvd,$data[$cf_indexes[$cfname]])) , $cfield->source );
					   }elseif($cfield->type == "meta"){
						    
						    if($cfield->options->formater){
								if($cfield->options->formater == "image"){
									$i_val = NULL;
									if(isset($cfield->options->format)){
										$img_src =  preg_replace('/\s+/', '', $data[$cf_indexes[$cfname]]);
										$img_id  =  insert_image_media($img_src,$id);
									
									    if($cfield->options->format == "id"){
											$i_val = $img_id;
										}elseif($cfield->options->format == "url"){
											$i_val = wp_get_attachment_url($img_id);
										}elseif($cfield->options->format == "object"){
											$i_val = new stdClass;
											$i_val->id    = $img_id;
											$i_val->src   = wp_get_attachment_url($img_id);
											$i_val->thumb = wp_get_attachment_thumb_url($img_id);
											$i_val->name  = pathinfo($img_src, PATHINFO_FILENAME);
										    $i_val->title = get_the_title($img_id);											
										}
									}
									$data[$cf_indexes[$cfname]] = $i_val;
								}elseif($cfield->options->formater == "gallery"){
									$i_vals = NULL;
									
									if(isset($cfield->options->format)){
										
										$images = explode($mvd, $data[$cf_indexes[$cfname]]);
										if(is_array($images)){
											foreach($images as $img_src){
												$img_src =  preg_replace('/\s+/', '', $img_src);
												$img_id  =  insert_image_media($img_src,$id);
												$i_val = NULL;
												
												if($cfield->options->format == "id"){
													$i_val = $img_id;
													if(!$i_vals)
														$i_vals = $i_val;
													else
														$i_vals .= (",".$i_val);
													
												}elseif($cfield->options->format == "url"){
													$i_val = wp_get_attachment_url($img_id);
													if(!$i_vals)
														$i_vals = $i_val;
													else
														$i_vals .= (",".$i_val);
													
												}elseif($cfield->options->format == "object"){
													$i_val = new stdClass;
													$i_val->id    = $img_id;
													$i_val->src   = wp_get_attachment_url($img_id);
													$i_val->thumb = wp_get_attachment_thumb_url($img_id);
													$i_val->name  = pathinfo($img_src, PATHINFO_FILENAME);
													$i_val->title = get_the_title($img_id);		
													if(!$i_vals)													
														$i_vals = array();
													$i_vals[] = $i_val;
												}
											}
										}
									}
									
									$data[$cf_indexes[$cfname]] = $i_vals;
								}else if($cfield->options->formater == "date"){
									if($data[$cf_indexes[$cfname]]){
										if(isset($cfield->options->unix_time)){
											if($cfield->options->unix_time){
												$data[$cf_indexes[$cfname]] = strtotime($data[$cf_indexes[$cfname]]);
											}
										}
									}
								}	
							}
						   
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
				
				if(!$parent){
					if($featured_image_index > -1){
						$attach_id = false;
						
						$image = $data[$featured_image_index];
						if($image){
								$attach_id = insert_image_media($image,$id);
								
								if($attach_id)
									if($attach_id != get_post_thumbnail_id($id))
										set_post_thumbnail( $id, $attach_id);
						}else{
							set_post_thumbnail( $id, false);
						}
					}					   
						
					if($product_gallery_index > -1){
						$images = explode($mvd, $data[$product_gallery_index]);
						$gallery = false;
						foreach ($images as $image) {
							$image   =  preg_replace('/\s+/', '', $image);
							if($image){
								$imageID =  insert_image_media($image,$id);
								if ($imageID > 0)
									$gallery[] = $imageID.'';
							}
						}
					
						if (!empty($gallery)) {
							$meta_value = implode(',', $gallery);
							update_post_meta($id, '_product_image_gallery', $meta_value);
						}
					}
				}
				
				$false = false;   
				//updateParentVariationData($id,$false,$attributes_asoc,NULL);
				
				if(function_exists("wc_delete_product_transients"))
					wc_delete_product_transients($id);
				
				pelm_on_product_update($id);
				if($parent)
					pelm_on_product_update($parent);
				//////////////////////////////////////////////////////////////////////////////////////
				$import_count++;
			    $n++;
				$data = get_option($import_uid."_".$n,null);
			}
			
			if($csv_row_processed_count > 0){
				if( isset( $impexp_settings->notfound_setpending )){
					if($impexp_settings->notfound_setpending){
						 if(!empty($imported_ids)){
							$wpdb->query( 
								$wpdb->prepare( "UPDATE $wpdb->posts SET post_status = 'pending' WHERE (post_type LIKE 'product_variation' or post_type LIKE 'product') AND NOT ID IN (". implode(",", $imported_ids ) .")")
							 ); 
						 }else{
							$wpdb->query( 
								$wpdb->prepare( "UPDATE $wpdb->posts SET post_status = 'pending' WHERE (post_type LIKE 'product_variation' or post_type LIKE 'product')")
							 );
						 }
					}
				}
			}
		
			
		}
		//WE NEED TO RELOAD ATTRIBUTES BECUSE WE MIGHT CREATED SOME NEW ONES
		$attributes      = array();
		$attributes_asoc = array();
		loadAttributes($attributes,$attributes_asoc);
		$custom_fileds   = array();
		loadCustomFields($plem_settings,$custom_fileds);
		////////////////////////////////////////////////////////////////////
		
		
		//WHEN WE REACH THIS POINT IMPORT IS DONE///////
		$wpdb->query("DELETE FROM $wpdb->options WHERE option_name LIKE '$import_uid%'");
		delete_option("pelm_order_csv");
		delete_option("pelm_impc");
		////////////////////////////////////////////////
		$wpdb->query("DELETE p from $wpdb->posts as p LEFT JOIN $wpdb->posts as pp on pp.ID = p.post_parent where p.post_type ='product_variation' AND  coalesce(p.post_parent,0) > 0 AND pp.ID IS NULL");
	}
	
	if(has_action('dc_start_stock_alert')) {
		do_action('dc_start_stock_alert');
	}
	$wpdb->query("DELETE from $wpdb->options WHERE option_name LIKE 'pelm_import%'");
}




$tags           = array();
foreach((array)get_terms('product_tag',array('hide_empty' => false )) as $pt){
    $t = new stdClass();
	$t->id   = $pt->term_id;
	$t->slug = urldecode($pt->slug);
	$t->name = $pt->name;
	$tags[]     = $t;
}	


$post_statuses = get_post_stati();
$pos_stat = get_post_statuses();        
foreach($post_statuses as $name => $title){
	if(isset($pos_stat[$name]))
		$post_statuses[$name] = $pos_stat[$name];		
}

if(isset($_REQUEST['remote_import'])){
  if(isset($_REQUEST['file_timestamp'])){
      $this->settings["wooc_remote_import_timestamp"] = $_REQUEST['file_timestamp'];
	  $this->saveOptions();
  }
  
  if(isset($_REQUEST["json_import"])){
	ob_clean();ob_clean();ob_clean();
	header('Content-Type: text/json'); 
	$resp = new stdClass;
	$resp->import_done = true;
	$resp->message     = "Remote import success: " . $import_count ." products processed.";
	$resp->proccessed  =  $csv_row_processed_count;
    echo json_encode($resp);	
	die;  
  }else{
	echo "Remote import success: " . $import_count ." products processed.";
    exit();
    return;  
  }
  
  
}

$_num_sample = (1/2).'';

if(!isset($_REQUEST["IDS"])){
	$args = array(
		 'post_type' => array('product')
		,'posts_per_page' => -1
		,'ignore_sticky_posts' => true
		,'orderby' => $orderby 
		,'order' => $sort_order
		,'fields' => 'ids'
	);

	if($product_status)
		$args['post_status'] = $product_status;

	if($orderby_key){
	   $args['meta_key'] = $orderby_key;
	}

	$meta_query = array();

	if(isset($product_name) && $product_name){
		$name_postids = $wpdb->get_col("select ID from $wpdb->posts where post_title like '%$product_name%' ");
		$args['post__in'] = empty($name_postids) ? array(-9999) : $name_postids;
	}

	$tax_query = array();

	if($product_category){
		$tax_query[] =  array(
							'taxonomy' => 'product_cat',
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

	if($product_shipingclass){
		$tax_query[] =  array(
							'taxonomy' => 'product_shipping_class',
							'field' => 'id',
							'terms' => $product_shipingclass
						);
	}

	foreach($filter_attributes as $fattr => $vals){
		$ids   = array();
		$names = array();
		foreach($vals as $val){
		  if(is_numeric($val))
			$ids[] = intval($val);
		  else
			$names[] = $val;
		}
		if(!empty($ids)){
			$tax_query[] =  array(
								'taxonomy' => 'pa_' . $fattr,
								'field' => 'id',
								'terms' => $ids
							);
		}
		
		if(!empty($names)){
			$tax_query[] =  array(
								'taxonomy' => 'pa_' . $fattr,
								'field' => 'name',
								'terms' => $names
							);
		}				
	}
	
	
	foreach($filter_cf as $cff => $vals){
		
		$ids   = array();
		$names = array();
		foreach($vals as $val){
		  if($val){	
			  if(is_numeric($val))
				$ids[] = intval($val);
			  else
				$names[] = $val;
		  }
		}
		
		if(!empty($ids)){
			$tax_query[] =  array(
								'taxonomy' => $custom_fileds[$cff]->source,
								'field' => 'id',
								'terms' => $ids
							);
		}
		
		if(!empty($names)){
			$tax_query[] =  array(
								'taxonomy' => $custom_fileds[$cff]->source,
								'field' => 'name',
								'terms' => $names
							);
		}
	}
	
	if($sku){
		$meta_query[] =	array(
							'key' => '_sku',
							'value' => $sku,
							'compare' => 'LIKE'
						);
	}

	if(!empty($tax_query )){
		$args['tax_query']  = $tax_query;
	}

	if(!empty($meta_query))
		$args['meta_query'] = $meta_query;
		

		


	if(!isset($_REQUEST["mass_update_val"])){
		$args['posts_per_page'] = $limit; 
		$args['paged']          = $page_no;
	}



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
			$pid = $products_query->post->ID; 
			$IDS[] = $pid;
		}
		
		wp_reset_postdata();
	}

	$ID_TMP = array();
	foreach($IDS as $p_id){
		$ID_TMP[] = $p_id;
		
		$wpdb->flush();
		$variat_ids = $wpdb->get_col($wpdb->prepare( 
			"SELECT      p.ID
				FROM        $wpdb->posts p
			 WHERE       p.post_type = 'product_variation'
							AND 
						 p.post_parent = %d
			 ORDER BY    p.ID
			",
			$p_id
		));
				
		foreach($variat_ids as $vid)
			$ID_TMP[] = $vid;
	}

	unset($IDS);
	$IDS = $ID_TMP;
}else{
	$IDS = explode(",",$_REQUEST["IDS"]);
	$count = count($IDS);
}

$mu_res = isset($_REQUEST['mu_res']) ? intval($_REQUEST['mu_res']) : 0;
if(isset($_REQUEST["mass_update_val"])){
  $ucol  = "";
  $uprop = "pr_p.product_price";
  
  $mu_proccessed  = isset($_REQUEST['mu_proccessed']) ? intval($_REQUEST['mu_proccessed']) : 0;
  $mu_updated     = 0;
  $interupted     = false;
  
  for ($i = $mu_proccessed; $i < count($IDS); $i++) {
	  $id = $IDS[$i];
	  
	  if($mu_updated == 300 || $mu_updated > 0 && $start_time + $max_time  < time() || getMemAllocated() + 1048576 > $mem_limit){
			$interupted  = true;
			break;
	  }else{
	  
		  if($_REQUEST['mass_update_override']){
			$override_price     = get_post_meta($id,'_sale_price',true);
			if(is_numeric($override_price)){
				$override_price = floatval($override_price);
				if($_REQUEST["mass_update_percentage"]){
					update_post_meta($id, '_sale_price', $override_price * (1 + floatval($_REQUEST["mass_update_val"]) / 100) );
					$mu_res++;
				}else{
					update_post_meta($id, '_sale_price', $override_price + floatval($_REQUEST["mass_update_val"]));
					$mu_res++;
				}
			}
		  }else{
			$price              = get_post_meta($id,'_regular_price',true);
			if(is_numeric($price)){
				$price = floatval($price);
				if($_REQUEST["mass_update_percentage"]){
					update_post_meta($id, '_regular_price', $price * (1 + floatval($_REQUEST["mass_update_val"]) / 100));
					$mu_res++;
				}else{
					update_post_meta($id, '_regular_price', $price + floatval($_REQUEST["mass_update_val"]));
					$mu_res++;
				}
			}
		  }
		  
		  $_price = get_post_meta($id,'_sale_price',true) ? get_post_meta($id,'_sale_price',true) :  get_post_meta($id,'_regular_price',true);
		  update_post_meta($id, '_price', $_price);
		  
		  $_price = floatval($_price);
		  $parent = get_ancestors($id,'product');
		  if( !empty($parent)){
			$parent = $parent[0];
			updateParentPriceData($parent);
		  }
		  
		  pelm_on_product_update($id);
		  if($parent)
			pelm_on_product_update($parent);
		  
		  $mu_updated++;
	  }
	  $mu_proccessed++;
  }
  
  ?>
	<!DOCTYPE html>
	<html>
		<head>
		<style type="text/css">
			html, body{
				background:#505050;
				color:white;
				font-family:sans-serif;	
			}
		</style>
		</head>
		<body>
			<form method="POST" id="continueMUfrom">
				<h2><?php echo __("Updating prices...",'productexcellikemanager'); ?></h2>
				<h3><?php echo ($_REQUEST['mass_update_percentage'] ? "%" : "") . (floatval($_REQUEST["mass_update_val"]) > 0 ? "+" : "-") . $_REQUEST["mass_update_val"];?></h3>
				<p>(<?php echo $mu_res;?>) <?php echo __("products/product price updated of total ",'productexcellikemanager'); ?><?php echo $mu_proccessed;?><?php echo __(" processed.",'productexcellikemanager'); ?></p>
				<hr/>
				
				<?php if($interupted){ ?>
				<input type="hidden" name="mu_res" value="<?php echo $mu_res;?>">
				<input type="hidden" name="mu_proccessed" value="<?php echo $mu_proccessed;?>">
				<input type="hidden" name="mass_update_val" value="<?php echo $_REQUEST["mass_update_val"];?>">
				<input type="hidden" name="mass_update_override" value="<?php echo $_REQUEST['mass_update_override'];?>">
				<input type="hidden" name="mass_update_percentage" value="<?php echo $_REQUEST['mass_update_percentage'];?>">
				<?php } ?>
				
				<input type="hidden" name="IDS" value="<?php echo implode(",",$IDS);?>">
				
				<?php if(isset($_REQUEST["sortOrder"])) {?>
					<input type="hidden" name="sortOrder" value="<?php echo $orderby;?>">
				<?php } 
				if(isset($_REQUEST["sortColumn"])) {?>
					<input type="hidden" name="sortColumn" value="<?php echo $sort_order;?>">
				<?php } 
				if(isset($_REQUEST["page_no"])) {?>
					<input type="hidden" name="page_no" value="<?php echo $_REQUEST["page_no"];?>">
				<?php }
				if(isset($_REQUEST["limit"])) {?>
					<input type="hidden" name="limit" value="<?php echo $_REQUEST["limit"];?>">
				<?php } 
				if(isset($_REQUEST["sku"])) {?>
					<input type="hidden" name="sku" value="<?php echo $_REQUEST["sku"];?>">
				<?php } 
				if(isset($_REQUEST["product_name"])) {?>
					<input type="hidden" name="product_name" value="<?php echo $_REQUEST["product_name"];?>">
				<?php } 
				if(isset($_REQUEST["product_category"])) {?>
					<input type="hidden" name="product_category" value="<?php echo $_REQUEST["product_category"];?>">
				<?php }
				if(isset($_REQUEST["product_shipingclass"])) {?>
					<input type="hidden" name="product_shipingclass" value="<?php echo $_REQUEST["product_shipingclass"];?>">
				<?php } 
				if(isset($_REQUEST["product_tag"])) {?>
					<input type="hidden" name="product_tag" value="<?php echo $_REQUEST["product_tag"];?>">
				<?php } 
				if(isset($_REQUEST["product_status"])) {?>
					<input type="hidden" name="product_status" value="<?php echo $_REQUEST["product_status"];?>">
				<?php } ?>
				
				<?php foreach($attributes as $attr){ 
						if(isset($_REQUEST["pattribute_" . $attr->id])){
							if($_REQUEST["pattribute_" . $attr->id]){
							?>
							<input type="hidden" name="pattribute_<?php echo $attr->id;?>" value="<?php echo $_REQUEST["pattribute_" . $attr->id];?>">
							<?php	
							}
						}
					 } ?>
					 
					 <?php foreach($custom_fileds as $cf){ 
							if($cf->type == "term"){
						?>
						<input type="hidden" name="<?php echo $cf->name;?>" value="<?php echo $_REQUEST[$cf->name];?>">
						<?php 
							}
						} ?>
			</form>
			<script type="text/javascript">
				<?php if(!$interupted){ ?>
				setTimeout(function(){
				<?php } ?>
					document.getElementById("continueMUfrom").submit();
				<?php if(!$interupted){ ?>
					},2000);
				<?php } ?>	
			</script>
		</body>
	</html>
<?php
  die();
  return;
  wp_reset_postdata();
}

//$count = count($ID_TMP);

function array_escape(&$arr){
	foreach($arr as $key => $value){
		if(is_string($value)){
			if(strpos($value, "\n") !== false)
				$arr[$key] = str_replace(array("\n","\r"),array("\\n","\\r") , $value);
		}
	}
}



global $plem_current_site;
if(!isset($plem_current_site))
  $plem_current_site = get_site_url() . "/";

function product_render(&$IDS,&$attributes,$op,&$df = null){
	global $wpdb, $custom_fileds, $impexp_settings, $custom_export_columns,$resume_skip;
	
	$p_ids = is_array($IDS) ? $IDS : array($IDS);
	
	if($resume_skip > 0){
		$p_ids = array_slice($p_ids,$resume_skip);
	}
	
	$mvd = $impexp_settings->value_delimiter;
	
	if(!$mvd)
		$mvd = ",";
	
	
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
    
	$term_data = null;
	
	if(getRequestVar("do_export") && $impexp_settings->export_product_terms){
		$term_data = $wpdb->get_results(
										"SELECT 
											p.ID,
											GROUP_CONCAT(t.term_id ORDER BY t.term_id ASC SEPARATOR ',') as terms_ids,
											GROUP_CONCAT(t.slug ORDER BY t.term_id ASC SEPARATOR ',')    as terms_slugs, 
											GROUP_CONCAT(t.name ORDER BY t.term_id ASC SEPARATOR ',')    as terms_names
										 FROM
											$wpdb->posts as p
											RIGHT JOIN
											$wpdb->term_relationships as tr on tr.object_id = p.ID
											LEFT JOIN
											$wpdb->term_taxonomy as tt on tt.term_taxonomy_id = tr.term_taxonomy_id
											LEFT JOIN
											$wpdb->terms as t on t.term_id = tt.term_id
										 WHERE
											p.ID IN (". $id_list .")
										 GROUP BY p.ID",OBJECT_K); 
				 
	}
    
    $p_n = 0;
	foreach($p_ids as $id) {
	 
	  $prod = new stdClass();
	  
	  if(getRequestVar("do_export")){
		  if($impexp_settings->export_site_url){
			  global $plem_current_site;
			  $prod->site = $plem_current_site;
		  }
	  }
	  
	  $prod->id             = $id;
	  
	  if(!getRequestVar("do_export")){
		$prod->type           = get_post_type($id);
	  }else{
		  if($impexp_settings->export_site_url){
			  global $plem_current_site;
			  $prod->site = $plem_current_site;
		  }
		  
	  }
	  
	  $prod->parent  = get_ancestors($id,'product');
	  if(!empty($prod->parent))
		$prod->parent = $prod->parent[0];
	  else
        $prod->parent = null;
	
	  if(fn_show_filed('sku'))
		$prod->sku            = get_post_meta($id,'_sku',true);
	  
	  if(fn_show_filed('slug'))
		$prod->slug           = toUTF8(urldecode($raw_data[$id]->post_name));
		//$prod->slug           = $raw_data[$id]->post_name;
	
	  if(fn_show_filed('stock'))
		$prod->stock              = get_post_meta($id,'_stock',true);//_manage_stock - null if not
	  
	  if(fn_show_filed('stock_status')){
		  if(getRequestVar("do_export"))
			$prod->stock_status       = get_post_meta($id,'_stock_status',true);
		  else
			$prod->stock_status       = get_post_meta($id,'_stock_status',true) == "instock" ? true : false;
	  }
	  
	  if(fn_show_filed('categories'))
		$prod->categories     = wp_get_object_terms($id, 'product_cat', array('fields' => 'ids') );
	
	  if(fn_show_filed('shipping_class'))
		$prod->shipping_class = wp_get_object_terms($id, 'product_shipping_class', array('fields' => 'ids') );
	   
	
	  $ptype = implode($mvd,wp_get_object_terms($id, 'product_type', array('fields' => 'names'))); 
	  if(fn_show_filed('product_type') || !getRequestVar("do_export")){
		if(getRequestVar("do_export"))
			$prod->product_type = $ptype ;
		else	  
			$prod->product_type = wp_get_object_terms($id, 'product_type', array('fields' => 'ids'));
	  }
	  
	  if(fn_show_filed('virtual') || fn_show_filed('downloadable')  || fn_show_filed('downloads')){
		$ptype = wp_get_object_terms($id, 'product_type', array('fields' => 'names'));
		
		if(is_array($ptype)){
			if(isset($ptype[0]))
				$ptype = $ptype[0];
			else
				$ptype = null;
		}
		
		if(fn_show_filed('virtual')){
			if(getRequestVar("do_export"))
				$prod->virtual     = get_post_meta($id,'_virtual',true);
			else{
				$prod->virtual     = get_post_meta($id,'_virtual',true) == "yes" ? true : false;
			}
		}
		
		if(fn_show_filed('downloadable')){
			if(getRequestVar("do_export"))
				$prod->downloadable     = get_post_meta($id,'_downloadable',true);
			else{
				$prod->downloadable     = get_post_meta($id,'_downloadable',true) == "yes" ? true : false;
			}
		}
		
		if(fn_show_filed('downloads')){
			if(getRequestVar("do_export"))
				$prod->downloads     = getDownloads($id,true);
			else{
				$prod->downloads     = getDownloads($id);
			}
		}
	  }

	  if(!getRequestVar("do_export") && $prod->parent){
		if(fn_show_filed('categories'))
			$prod->categories     = wp_get_object_terms($prod->parent, 'product_cat', array('fields' => 'ids') );
		
		if(fn_show_filed('shipping_class')){
			if($prod->shipping_class == -1)
				$prod->shipping_class = wp_get_object_terms($prod->parent, 'product_shipping_class', array('fields' => 'ids') );
		}	
	  }
	  
	  if(getRequestVar("do_export")){
		
		if($impexp_settings->use_custom_export){
			
			if(fn_show_filed("categories_paths")){
				$prod->categories_paths     = get_product_categories_paths($id);
			}
			
			if(fn_show_filed('categories_names')){
				$prod->categories_names     = implode("$mvd  ",wp_get_object_terms( $id, 'product_cat', array('fields' => 'names') ));
			}
			
			if(fn_show_filed('categories_ids')){
				if(!empty($prod->categories))
					$prod->categories_ids =	implode("$mvd ",$prod->categories);
				else
					$prod->categories_ids = implode("$mvd ",wp_get_object_terms( $id, 'product_cat', array('fields' => 'ids') ));
			}
			
		}else{
			if($impexp_settings->category_export_method == "names"){
				$prod->categories_names     = implode("$mvd ",wp_get_object_terms( $id, 'product_cat', array('fields' => 'names') ));
			}else if($impexp_settings->category_export_method == "ids"){
				if(!empty($prod->categories))
					$prod->categories_ids = "" . implode("$mvd ",$prod->categories);
				else
					$prod->categories_ids = "" . implode("$mvd ",wp_get_object_terms( $id, 'product_cat', array('fields' => 'ids') ));
			}else{
				$prod->categories_paths     = get_product_categories_paths($id);
			} 
		}
		
		if(isset($prod->categories))
			unset($prod->categories);
		
		if(fn_show_filed('shipping_class')){
			$prod->shipping_class_name  = implode("$mvd ",wp_get_object_terms( $id, 'product_shipping_class', array('fields' => 'names') ));
			unset($prod->shipping_class);
		}
		
		if(fn_show_filed('stock_status'))
			unset($prod->stock_status);
		
		
		
	  }
	  
	  if(fn_show_filed('price')){
		  $prod->price              = get_post_meta($id,'_regular_price',true);
	  }
	  
	  if(fn_show_filed('override_price')){
		  $prod->override_price     = get_post_meta($id,'_sale_price',true);
	  }
	  
	  $name_suffix = '';	
	  if(fn_show_filed('name')){
		  if($prod->parent){
			$prod->name         = get_the_title($prod->parent). "(var #". $id . ')';
		  }else
			$prod->name         = get_the_title($id);
	  } 
	  
	  
	  $pa = get_post_meta($prod->parent ? $prod->parent : $id,'_product_attributes',true);
	  $att_info = array();
	  
	  /////////////////////////////////////////////////////////////
	 if(!$prod->parent && $ptype !== 'variable'){
		  
		  if($pa){
			  if(!empty($pa)){
				  $fix = false;
				  foreach($pa as $_akey => $_a){
					  if($_a["is_variation"] && $_akey == '0'){
						  $fix = true;
						  break;
					  }
				  }
				  if($fix){
					  $NULL = null;
					  updateParentVariationData($id,$NULL,$NULL,null);
					  $pa = get_post_meta($prod->parent ? $prod->parent : $id,'_product_attributes',true);
				  }
			  }
		  }
		  
	  }else if(!$prod->parent && $ptype === 'variable'){ 
		  if($pa){
			if(isset($pa[0])){
				
				updateParentVariationData($id,$NULL,$NULL,null);
				
			    $pa = get_post_meta($prod->parent ? $prod->parent : $id,'_product_attributes',true);
			}
		  }
		  
		  
	  }
	  /////////////////////////////////////////////////////////////
	 
	  foreach($attributes as $att){
		  
		  
		$inf    = new stdClass();
		$inf->v = 0;
		$inf->s = true;
		if(isset($pa['pa_'. $att->name])){
			$inf->v = $pa['pa_'. $att->name]["is_variation"];
			$inf->s = $pa['pa_'. $att->name]["is_visible"] ? true : false;
		}
		$att_info[$att->id] = $inf;
		
		if($prod->parent){
			if($inf->v){
				$att_value = get_post_meta($id,'attribute_pa_'. $att->name,true);
				$att_value = explode(",",$att_value);
				$tnames    = array();
				$tids      = array();
				foreach($att_value as $tslug){
					$term = null;
					foreach($att->values as $trm){
					   if($trm->slug == $tslug){
							$term = $trm;
							break;
					   }
					}
					
					if($term){
						$tnames[] = $term->name;
						$tids[]   = $term->id; 
					}
					
					if(!empty($tids))//VARIANT Can have only one 
						break;
				}
				
				if(fn_show_filed('name') && !empty($tnames)){
					$prod->name .= (", ". implode(",",$tnames));
				}
				
				if(!fn_show_filed('pattribute_' . $att->id))
					continue;
					
				if(getRequestVar("do_export"))
					$prod->{'pattribute_'.$att->id} =  implode($mvd,$tnames);
				else	
					$prod->{'pattribute_'.$att->id} = $tids;
				
			}else{
				if(!fn_show_filed('pattribute_' . $att->id))
					continue;
					
				if(getRequestVar("do_export")){
					$prod->{'pattribute_'.$att->id} = null;//NO APPLACABLE
				}else{
					$prod->{'pattribute_'.$att->id} = wp_get_object_terms($prod->parent,'pa_'. $att->name, array('fields' => 'ids'));//INHERITED
				}
			}
			
			
			if(fn_show_filed("attribute_show"))
				$prod->{'pattribute_'.$att->id."_visible"} = null;
		}else{
			
			if(!fn_show_filed('pattribute_' . $att->id))
				continue;
			
			if(getRequestVar("do_export")){
				if($inf->v){
					$prod->{'pattribute_'.$att->id} = NULL;
				}else{
					$prod->{'pattribute_'.$att->id} = implode("$mvd ",wp_get_object_terms($id,'pa_'. $att->name, array('fields' => 'names')));
				}
				if(fn_show_filed("attribute_show"))
					$prod->{'pattribute_'.$att->id."_visible"} = $inf->s ? "yes" : "no";
			}else{
				$prod->{'pattribute_'.$att->id} = wp_get_object_terms($id,'pa_'. $att->name, array('fields' => 'ids'));
				if(fn_show_filed("attribute_show"))
					$prod->{'pattribute_'.$att->id."_visible"} = $inf->s;
			}
			
		}
	  }
	  
	  if(!getRequestVar("do_export")){
			$prod->att_info = $att_info;
	  }
	  
	  
	  //if(fn_show_filed('name'))
	  //$prod->name .= $name_suffix;
	 
	  foreach($custom_fileds as $cfname => $cfield){ 
		   if($cfield->type == "term"){
				if(getRequestVar("do_export"))
					$prod->{$cfname} = implode("$mvd ",wp_get_object_terms($id,$cfield->source, array('fields' => 'names')));
				else{
					if($prod->parent)
						$prod->{$cfname} = wp_get_object_terms($prod->parent,$cfield->source, array('fields' => 'ids'));
					else
						$prod->{$cfname} = wp_get_object_terms($id, $cfield->source , array('fields' => 'ids'));
				}	
				
		   }elseif($cfield->type == "meta"){
			    
				$decoder = "";
			    if(isset($cfield->options)){
					if(isset($cfield->options->serialization)){
						$decoder = $cfield->options->serialization;
					}	
				}
			   
			   
				$prod->{$cfname} = fn_get_meta_by_path( $id , $cfield->source, $decoder);
				
				if($cfield->options->formater){
					if($cfield->options->formater == "image"){
						if(isset($cfield->options->format) && $prod->{$cfname}){
							if($cfield->options->format == "id"){
								$i_val = new stdClass;
								$i_val->id    = $prod->{$cfname};
								$i_val->src   = wp_get_attachment_url($i_val->id);
								$i_val->thumb = wp_get_attachment_thumb_url($i_val->id);
								$i_val->name  = pathinfo($i_val->src, PATHINFO_FILENAME);
								$i_val->title = get_the_title($img_id);
								$prod->{$cfname} = $i_val;
							}elseif($cfield->options->format == "url"){
								$m_id = get_attachment_id_from_src($prod->{$cfname});
								if($m_id){
									$i_val = new stdClass;
									$i_val->id    = $m_id;
									$i_val->src   = $prod->{$cfname};
									$i_val->thumb = wp_get_attachment_thumb_url($prod->{$cfname});
									$i_val->name  = pathinfo($i_val->src, PATHINFO_FILENAME);
									$i_val->title = get_the_title($m_id);
									if($i_val->src)
										$prod->{$cfname} = $i_val;
									else
										$prod->{$cfname} = NULL;
								}else{
									$i_val = new stdClass;
									$i_val->id    = 0;
									$i_val->src   = $prod->{$cfname};
									$i_val->thumb = NULL;
									$i_val->name  = pathinfo($i_val->src, PATHINFO_FILENAME);
									$i_val->title = $i_val->name;
									if($i_val->src)
										$prod->{$cfname} = $i_val;
									else
										$prod->{$cfname} = NULL;
								}
								//ovde
							}elseif($cfield->options->format == "object"){
								//nothing
							}
						}
						
						if(getRequestVar("do_export")){
							if($prod->{$cfname})
								$prod->{$cfname} = $prod->{$cfname}->src;
						}
							
						
					}elseif($cfield->options->formater == "gallery"){
						if(isset($cfield->options->format) && $prod->{$cfname}){
							if($cfield->options->format == "id"){
								$m_ids = explode(",",$prod->{$cfname});
								if($m_ids){
									$m_val = array();
									foreach($m_ids as $m_id){
										$i_val = new stdClass;
										$i_val->id    = $m_id;
										$i_val->src   = wp_get_attachment_url($m_id);
										$i_val->thumb = wp_get_attachment_thumb_url($m_id);
										$i_val->name  = pathinfo($i_val->src, PATHINFO_FILENAME);
										$i_val->title = get_the_title($m_id);
										if($i_val->src)
											$m_val[] = $i_val;
									}
									$prod->{$cfname} = $m_val;
								}else
									$prod->{$cfname} = NULL;
								
							}elseif($cfield->options->format == "url"){
								$m_urls = explode(",",$prod->{$cfname});
								if(!empty($m_urls)){
									$m_val = array();
									foreach($m_urls as $m_url){
										$m_id = get_attachment_id_from_src($m_url);
										$i_val = new stdClass;
										if($m_id){
											$i_val->id    = $m_id;
											$i_val->src   = $m_url;
											$i_val->thumb = wp_get_attachment_thumb_url($m_id);
											$i_val->name  = pathinfo($i_val->src, PATHINFO_FILENAME);
											$i_val->title = get_the_title($m_id);
										}else{
											$i_val->id    = 0;
											$i_val->src   = $m_url;
											$i_val->thumb = NULL;
											$i_val->name  = pathinfo($i_val->src, PATHINFO_FILENAME);
											$i_val->title = $i_val->name;
										}
										if($i_val->src)
											$m_val[] = $i_val;
									}
									$prod->{$cfname} = $m_val;
								}else
									$prod->{$cfname} = NULL;
								
							}elseif($cfield->options->format == "object"){
								//nothing
							}
						}
						
						if(getRequestVar("do_export")){
							if($prod->{$cfname}){
								if(!empty($prod->{$cfname})){
									$e_val = array();
									foreach($prod->{$cfname} as $m){
										$e_val[] = $m->src; 
									}
									$prod->{$cfname} = implode("$mvd",$e_val);
								}
							}
						}
					}else if($cfield->options->formater == "date"){
						if($prod->{$cfname}){
							if(isset($cfield->options->unix_time)){
								if($cfield->options->unix_time){
									$prod->{$cfname} = date("Y-m-d H:i:s ",$prod->{$cfname});
								}
							}
						}
					}	
				}
				
				if(isset($cfield->options)){
					if(isset($cfield->options->format)){
						if($cfield->options->format == "json_array"){	
							if(getRequestVar("do_export"))
								$prod->{$cfname} = implode($mvd,json_decode($prod->{$cfname}));
							else						
								$prod->{$cfname} = implode(",",json_decode($prod->{$cfname}));
							
						}else if($cfield->options->format == "json_object")	
							$prod->{$cfname} = json_decode($prod->{$cfname});
						else if(getRequestVar("do_export")){
							if(strpos($cfield->options->format,'_array') !== false){
								if(is_array($prod->{$cfname}))
									$prod->{$cfname} = implode($mvd,$prod->{$cfname});
							}
						}
					}	
				}
				
		   }elseif($cfield->type == "post"){
		        $prod->{$cfname} = $raw_data[$id]->{$cfield->source};
		   }
		   
		   if(isset($cfield->options->formater)){
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
	  }
	  
	  if(fn_show_filed('status'))
		$prod->status       = get_post_status($id);
		
		
	  $ptrems = get_the_terms($id,'product_tag');
	  
	  if(fn_show_filed('tags')){
		  if(getRequestVar("do_export")){
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
	  
	  if(fn_show_filed('weight'))
		$prod->weight       = get_post_meta($id,'_weight',true);
	  if(fn_show_filed('length'))
		$prod->length       = get_post_meta($id,'_length',true);
	  if(fn_show_filed('width'))
		$prod->width        = get_post_meta($id,'_width',true);
	  if(fn_show_filed('height'))
		$prod->height       = get_post_meta($id,'_height',true);
	  
	  if(fn_show_filed('featured')){
		  if(getRequestVar("do_export"))
			$prod->featured     = get_post_meta($id,'_featured',true);
		  else{
			$prod->featured     = get_post_meta($prod->parent ? $prod->parent : $id,'_featured',true) == "yes" ? true : false;
		  }
	  }
	  

	  
	  if(fn_show_filed('tax_status'))
		$prod->tax_status   = get_post_meta(($prod->parent && !getRequestVar("do_export")) ? $prod->parent : $id,'_tax_status',true);
	  if(fn_show_filed('tax_class'))
		$prod->tax_class    = get_post_meta(($prod->parent && !getRequestVar("do_export")) ? $prod->parent : $id,'_tax_class',true);
	  if(fn_show_filed('backorders'))
		$prod->backorders   = get_post_meta(($prod->parent && !getRequestVar("do_export")) ? $prod->parent : $id,'_backorders',true);
	
      if(fn_show_filed('image')){	
		  $prod->image = null;
		  
		  if(has_post_thumbnail($id)){
			$thumb_id    = get_post_thumbnail_id($id);
			if(getRequestVar("do_export")){
				if(!$prod->parent){
					$prod->image = wp_get_attachment_image_src($thumb_id, 'full');
					if(is_array($prod->image))
						$prod->image = $prod->image[0];
				}
			}else{
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
			}
		  }
	  }
	  
	  if(fn_show_filed('gallery')){	
		  $prod->gallery = null;
		  
		  if(!(getRequestVar("do_export") && $prod->parent)){
			  $gallery = get_post_meta($prod->parent ? $prod->parent : $id,"_product_image_gallery",true);
			  if($gallery){
				  $prod->gallery = array();
				  foreach(explode(",",$gallery) as $ind => $img_id){
					  if(getRequestVar("do_export")){
						  $img = wp_get_attachment_image_src($img_id, 'full');
						  if(is_array($img))
							  $img = $img[0];
						  $prod->gallery[] = $img;
					  }else{
						  $gimg = new stdClass;
						  $gimg->id    = $img_id; 
						  $gimg->src   = wp_get_attachment_image_src($img_id, 'full');
						  if(is_array($gimg->src))
							  $gimg->src = $gimg->src[0];
						  
						  $gimg->thumb = wp_get_attachment_image_src($img_id, 'thumbnail'); 
						  if(is_array($gimg->thumb))
							  $gimg->thumb =$gimg->thumb[0];
						  
						  if($gimg->src)
						  $prod->gallery[] = $gimg; 
					  }
				  }
				  
				  if(getRequestVar("do_export")){
					  $prod->gallery = implode($mvd,$prod->gallery);
				  }
			  }
		 }
	  }
	  
	  if(!fn_show_filed('parent') && getRequestVar("do_export")){
			unset($prod->parent);
	  }else if(fn_show_filed('parent') && getRequestVar("do_export") && $prod->parent){
		  if(!isset($impexp_settings->parent_export_method))
		 	  $impexp_settings->parent_export_method = "id";
		  
		  if($impexp_settings->parent_export_method == "sku"){
			  $psku = get_post_meta($prod->parent,'_sku',true);
			  if($psku)
				$prod->parent = "sku:" . $psku;
			  else
				$prod->parent = "slug:" . urldecode($wpdb->get_var( "SELECT post_name FROM $wpdb->posts where ID = " . $prod->parent ));
			
		  }elseif($impexp_settings->parent_export_method == "slug" && $prod->parent){
			  $prod->parent = "slug:" . urldecode($wpdb->get_var( "SELECT post_name FROM $wpdb->posts where ID = " . $prod->parent ));
		  }elseif($impexp_settings->parent_export_method == "title" && $prod->parent){
			  $prod->parent = "title:" . get_the_title($prod->parent);
		  }
			  
		  
	  }
	  
	  if(getRequestVar("do_export") && $impexp_settings->export_product_terms){
		 $prod->terms = "";
		 if(isset($term_data[$id])){
			 if($impexp_settings->export_product_terms_ids){
				$prod->terms = str_replace(",",$mvd,$term_data[$id]->terms_ids);
			 }else{
				$prod->terms = str_replace(",",$mvd,urldecode($term_data[$id]->terms_slugs));
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
		
		 global $start_time,$max_time,$mem_limit,$res_limit_interupted;
		
		 if($p_n > 0 && $start_time + $max_time  < time() || getMemAllocated() + 1048576 > $mem_limit){
			$res_limit_interupted = $p_n + 1;
			break;	
		 }
		
	  }else if($op == "append"){
		  
		  if($impexp_settings->use_custom_export){
			
			if(isset($prod->shipping_class)){
				$prod->shipping_class_name = $prod->shipping_class;
				unset($prod->shipping_class);
			}
			
			if(isset($prod->tags)){
				$prod->tags_names = $prod->tags;
				unset($prod->tags);
			}
			
			if(isset($prod->categories)){
				$prod->categories_names = $prod->categories;
				unset($prod->categories);
			}
		  }	
		  
		  $df[] = $prod; 
		  
		  
		  global $start_time,$max_time,$mem_limit,$res_limit_interupted, $in_export_chunk;
		 
		  if(!isset($in_export_chunk))
			 $in_export_chunk = 1;
		  else
			 $in_export_chunk++;
		 
		  if($in_export_chunk == 300 || $p_n > $resume_skip && $start_time + $max_time  < time() || getMemAllocated() + 1048576 > $mem_limit){
			$res_limit_interupted = $p_n + 1;
			break;	
		  }
		
	  }elseif($op == "export"){
		 
		 if($p_n == 0 && $resume_skip == 0){	
		   if($impexp_settings->use_custom_export){
			   
			   
			   $real_headers = array();
			   if($impexp_settings->export_site_url)
				  $custom_export_columns = array_merge(array("site"),$custom_export_columns);
			  
			   foreach($custom_export_columns as $hname){
					if($hname == "tags")
						 $real_headers[] = "tags_names";
					else if($hname == "shipping_class")	
						 $real_headers[] = "shipping_class_name";
					else if(fn_show_filed("categories_paths") && $hname == "categories_names"){
						 //nothing	
					}else
						 $real_headers[] = $hname;
			   }
			   
			   if($impexp_settings->export_product_terms){
				   $real_headers[] = "terms";
				   $custom_export_columns[] = "terms";
			   }
			   
			   fputcsv($df, $real_headers, $impexp_settings->delimiter2);
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
				if($prop == "shipping_class")
					$prop = "shipping_class_name";
				else if($prop == "tags")
					$prop = "tags_names";
				else if($prop == "categories")
					$prop = "categories_names";
				
				$eprod[] = &$prod->$prop;
			}
			array_escape($eprod);
			fputcsv($df, $eprod, $impexp_settings->delimiter2);
		 }else{
			$aprod = (array)$prod;
			array_escape($aprod);
			fputcsv($df, $aprod, $impexp_settings->delimiter2);
		 }
		 
		 global $start_time,$max_time,$mem_limit,$res_limit_interupted, $in_export_chunk;
		 
		 if(!isset($in_export_chunk))
			 $in_export_chunk = 1;
		 else
			 $in_export_chunk++;
		 
		 if($in_export_chunk == 300 || $p_n > $resume_skip && $start_time + $max_time  < time() || getMemAllocated() + 1048576 > $mem_limit){
			$res_limit_interupted = $p_n + 1;
			break;	
		 }
		
	  }elseif($op == "data"){
		  return $prod;
	  }
	  $p_n++;
	 
	  unset($prod);
	  
	}
};

if(isset($_REQUEST["do_export_categories"])){
	if($_REQUEST["do_export_categories"] = "1"){
		ob_clean();ob_clean();
		header('Content-Type: text/json');
		$export_reponse = new stdClass;
		$export_reponse->categories = $categories;
		echo json_encode($export_reponse);
		die;
	}
}

if(getRequestVar("do_export")){
	if(getRequestVar("do_export") == "1"){
	
		$export_uid = isset($_REQUEST["export_uid"]) ? $_REQUEST["export_uid"] : uniqid("plem_export_");
		$chunk_n    = get_option( $export_uid ."_chunk", 0);
		$chunk_n++;
		
		if(isset($_REQUEST["export_format"])){
			if($_REQUEST["export_format"] == "json"){
				$out_data = array();
				product_render($IDS,$attributes,"append",$out_data);
				
				update_option( $export_uid ."_chunk" , $chunk_n, false );
				update_option( $export_uid . "_" . $chunk_n , $contents, false );
				
				if($res_limit_interupted == 0){
				
					$export_reponse = new stdClass;
					$export_reponse->done        = 1;
					$export_reponse->export_uid  = $export_uid;
					$export_reponse->IDS         = implode(",",$IDS);
					$export_reponse->sortOrder   = $orderby;
					$export_reponse->sortColumn  = $sort_order;
					$export_reponse->product_category = $_REQUEST["product_category"];
					$export_reponse->items       = $out_data;
				}else{
					$resume_skip += $res_limit_interupted;
					
					$export_reponse = new stdClass;
					$export_reponse->resume_skip = $resume_skip;
					$export_reponse->export_uid  = $export_uid;
					$export_reponse->IDS         = implode(",",$IDS);
					$export_reponse->sortOrder   = $orderby;
					$export_reponse->sortColumn  = $sort_order;
					$export_reponse->product_category = $_REQUEST["product_category"];
					$export_reponse->items       = $out_data;
					
				}
				
				ob_clean();ob_clean();
				header('Content-Type: text/json');
				echo json_encode($export_reponse);
				die;
					
				return;
			}
		}
		
	    $df = fopen("php://temp", 'w+');
	    ///////////////////////////////////////////////////
		product_render($IDS,$attributes,"export",$df);
		///////////////////////////////////////////////////
		rewind($df);
		$contents = '';
		while (!feof($df)) {
			$contents .= fread($df, 8192 * 4);
		}
		
		update_option( $export_uid ."_chunk" , $chunk_n, false );
		update_option( $export_uid . "_" . $chunk_n , $contents, false );
		
		fclose($df);
		
		if($res_limit_interupted == 0){
			
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
			$csv_df = fopen("php://output", 'w');
			$chunk_n = 1;
			$contents = get_option($export_uid . "_" . $chunk_n, false);
			while($contents !== false){
				fwrite($csv_df,$contents);
				$chunk_n++;
				$contents = get_option($export_uid . "_" . $chunk_n, false);
			}
			fclose($csv_df);
			
			delete_option( $export_uid ."_chunk");
			$chunk_n = 1;
			while(delete_option( $export_uid . "_" . $chunk_n))
				$chunk_n++;
			
		}else{
			$resume_skip += $res_limit_interupted;
			?>
				<!DOCTYPE html>
				<html>
					<head>
					<style type="text/css">
						html, body{
							background:#505050;
							color:white;
							font-family:sans-serif;	
						}
					</style>
					</head>
					<body>
						<form method="POST" id="continueExportForm">
							<h2><?php echo __("Preparing export...",'productexcellikemanager'); ?></h2>
							<p>(<?php echo $resume_skip;?>) <?php echo __("products/product variations entries processed",'productexcellikemanager'); ?></p>
							<hr/>
							<p><?php echo __("You can close this browser tab once you receive CSV file",'productexcellikemanager'); ?></p>
							<input type="hidden" name="resume_skip" value="<?php echo $resume_skip;?>">
							<input type="hidden" name="export_uid" value="<?php echo $export_uid;?>">
							<input type="hidden" name="do_export" value="1">
							<input type="hidden" name="IDS" value="<?php echo implode(",",$IDS);?>">
							<?php if(isset($_REQUEST["sortOrder"])) {?>
								<input type="hidden" name="sortOrder" value="<?php echo $orderby;?>">
							<?php } 
							if(isset($_REQUEST["sortColumn"])) {?>
								<input type="hidden" name="sortColumn" value="<?php echo $sort_order;?>">
							<?php } 
							if(isset($_REQUEST["page_no"])) {?>
								<input type="hidden" name="page_no" value="<?php echo $_REQUEST["page_no"];?>">
							<?php }
							if(isset($_REQUEST["limit"])) {?>
								<input type="hidden" name="limit" value="<?php echo $_REQUEST["limit"];?>">
							<?php } 
							if(isset($_REQUEST["sku"])) {?>
								<input type="hidden" name="sku" value="<?php echo $_REQUEST["sku"];?>">
							<?php } 
							if(isset($_REQUEST["product_name"])) {?>
								<input type="hidden" name="product_name" value="<?php echo $_REQUEST["product_name"];?>">
							<?php }
							if(isset($_REQUEST["product_category"])) {?>
								<input type="hidden" name="product_category" value="<?php echo $_REQUEST["product_category"];?>">
							<?php }						
							if(isset($_REQUEST["product_shipingclass"])) {?>
								<input type="hidden" name="product_shipingclass" value="<?php echo $_REQUEST["product_shipingclass"];?>">
							<?php } 
							if(isset($_REQUEST["product_tag"])) {?>
								<input type="hidden" name="product_tag" value="<?php echo $_REQUEST["product_tag"];?>">
							<?php } 
							if(isset($_REQUEST["product_status"])) {?>
								<input type="hidden" name="product_status" value="<?php echo $_REQUEST["product_status"];?>">
							<?php } ?>
							
							<?php foreach($attributes as $attr){ 
									if(isset($_REQUEST["pattribute_" . $attr->id])){
										if($_REQUEST["pattribute_" . $attr->id]){
										?>
										<input type="hidden" name="pattribute_<?php echo $attr->id;?>" value="<?php echo $_REQUEST["pattribute_" . $attr->id];?>">
										<?php	
										}
									}
								 } ?>
								 
						<?php foreach($custom_fileds as $cf){ 
							if($cf->type == "term"){
							?>
							<input type="hidden" name="<?php echo $cf->name;?>" value="<?php echo $_REQUEST[$cf->name];?>">
							<?php 
							}
						} ?>		 
								 
								 
						</form>
						<script type="text/javascript">
								document.getElementById("continueExportForm").submit();
						</script>
					</body>
				</html>
			<?php
		}
		
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

function removeFalseImages(){
	if(confirm("<?php echo __("Plese make sure you know what you are doing before proceeding with this operation.\\nAll image attachments of your site will be checked. If files do not exist or attachment is mailformed it will be removed.\\nProcced?",'productexcellikemanager');?>")){
		doLoad(null,{
			clean_false_img: 1
		});
		return false;
	}
}

if(<?php echo fn_show_filed("attribute_show") ? "'1'" : "'0'"; ?> != localStorage["dg_wooc_attribute_show_visible"]){
	localStorage.clear();
}
localStorage["dg_wooc_attribute_show_visible"] = <?php echo fn_show_filed("attribute_show") ? "'1'" : "'0'"; ?>;

/*
try{
	
  if(localStorage['dg_wooc_manualColumnWidths']){
    localStorage['dg_wooc_manualColumnWidths'] = JSON.stringify( eval(localStorage['dg_wooc_manualColumnWidths']).map(function(s){
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
<link rel="stylesheet" href="<?php echo $productexcellikemanager_baseurl.'/lib/chosen.min.css'; ?>">
<script src="<?php echo $productexcellikemanager_baseurl.'lib/chosen.jquery.min.js'; ?>" type="text/javascript"></script>

<link rel="stylesheet" href="<?php echo $productexcellikemanager_baseurl.'assets/style.css'; ?>">

<script type='text/javascript'>
/* <![CDATA[ */
//var commonL10n = {"warnDelete":"You are about to permanently delete the selected items.\n  'Cancel' to stop, 'OK' to delete."};var thickboxL10n = {"next":"Next >","prev":"< Prev","image":"Image","of":"of","close":"Close","noiframes":"This feature requires inline frames. You have iframes disabled or your browser does not support them.","loadingAnimation":"http:\/\/localhost\/wooexcel\/wp-includes\/js\/thickbox\/loadingAnimation.gif"};var wpAjax = {"noPerm":"You do not have permission to do that.","broken":"An unidentified error has occurred."};var autosaveL10n = {"autosaveInterval":"60","savingText":"Saving Draft\u2026","saveAlert":"The changes you made will be lost if you navigate away from this page.","blog_id":"1"};var quicktagsL10n = {"closeAllOpenTags":"Close all open tags","closeTags":"close tags","enterURL":"Enter the URL","enterImageURL":"Enter the URL of the image","enterImageDescription":"Enter a description of the image","fullscreen":"fullscreen","toggleFullscreen":"Toggle fullscreen mode","textdirection":"text direction","toggleTextdirection":"Toggle Editor Text Direction"};var adminCommentsL10n = {"hotkeys_highlight_first":"","hotkeys_highlight_last":"","replyApprove":"Approve and Reply","reply":"Reply"};var heartbeatSettings = {"nonce":"ed534fe8b6","suspension":"disable"};var postL10n = {"ok":"OK","cancel":"Cancel","publishOn":"Publish on:","publishOnFuture":"Schedule for:","publishOnPast":"Published on:","dateFormat":"%1$s %2$s, %3$s @ %4$s : %5$s","showcomm":"Show more comments","endcomm":"No more comments found.","publish":"Publish","schedule":"Schedule","update":"Update","savePending":"Save as Pending","saveDraft":"Save Draft","private":"Private","public":"Public","publicSticky":"Public, Sticky","password":"Password Protected","privatelyPublished":"Privately Published","published":"Published","comma":","};var _wpUtilSettings = {"ajax":{"url":"\/wooexcel\/wp-admin\/admin-ajax.php"}};var _wpMediaModelsL10n = {"settings":{"ajaxurl":"\/wooexcel\/wp-admin\/admin-ajax.php","post":{"id":0}}};var pluploadL10n = {"queue_limit_exceeded":"You have attempted to queue too many files.","file_exceeds_size_limit":"%s exceeds the maximum upload size for this site.","zero_byte_file":"This file is empty. Please try another.","invalid_filetype":"This file type is not allowed. Please try another.","not_an_image":"This file is not an image. Please try another.","image_memory_exceeded":"Memory exceeded. Please try another smaller file.","image_dimensions_exceeded":"This is larger than the maximum size. Please try another.","default_error":"An error occurred in the upload. Please try again later.","missing_upload_url":"There was a configuration error. Please contact the server administrator.","upload_limit_exceeded":"You may only upload 1 file.","http_error":"HTTP error.","upload_failed":"Upload failed.","big_upload_failed":"Please try uploading this file with the %1$sbrowser uploader%2$s.","big_upload_queued":"%s exceeds the maximum upload size for the multi-file uploader when used in your browser.","io_error":"IO error.","security_error":"Security error.","file_cancelled":"File canceled.","upload_stopped":"Upload stopped.","dismiss":"Dismiss","crunching":"Crunching\u2026","deleted":"moved to the trash.","error_uploading":"\u201c%s\u201d has failed to upload."};
//var _wpPluploadSettings = {"defaults":{"runtimes":"html5,silverlight,flash,html4","file_data_name":"async-upload","multiple_queues":true,"max_file_size":"52428800b","url":"\/wooexcel\/wp-admin\/async-upload.php","flash_swf_url":"http:\/\/localhost\/wooexcel\/wp-includes\/js\/plupload\/plupload.flash.swf","silverlight_xap_url":"http:\/\/localhost\/wooexcel\/wp-includes\/js\/plupload\/plupload.silverlight.xap","filters":[{"title":"Allowed Files","extensions":"*"}],"multipart":true,"urlstream_upload":true,"multipart_params":{"action":"upload-attachment","_wpnonce":"188d26aff2"}},"browser":{"mobile":false,"supported":true},"limitExceeded":false};var _wpMediaViewsL10n = {"url":"URL","addMedia":"Add Media","search":"Search","select":"Select","cancel":"Cancel","selected":"%d selected","dragInfo":"Drag and drop to reorder images.","uploadFilesTitle":"Upload Files","uploadImagesTitle":"Upload Images","mediaLibraryTitle":"Media Library","insertMediaTitle":"Insert Media","createNewGallery":"Create a new gallery","returnToLibrary":"\u2190 Return to library","allMediaItems":"All media items","noItemsFound":"No items found.","insertIntoPost":"Insert into post","uploadedToThisPost":"Uploaded to this post","warnDelete":"You are about to permanently delete this item.\n  'Cancel' to stop, 'OK' to delete.","insertFromUrlTitle":"Insert from URL","setFeaturedImageTitle":"Set Featured Image","setFeaturedImage":"Set featured image","createGalleryTitle":"Create Gallery","editGalleryTitle":"Edit Gallery","cancelGalleryTitle":"\u2190 Cancel Gallery","insertGallery":"Insert gallery","updateGallery":"Update gallery","addToGallery":"Add to gallery","addToGalleryTitle":"Add to Gallery","reverseOrder":"Reverse order","settings":{"tabs":[],"tabUrl":"http:\/\/localhost\/wooexcel\/wp-admin\/media-upload.php?chromeless=1","mimeTypes":{"image":"Images","audio":"Audio","video":"Video"},"captions":true,"nonce":{"sendToEditor":"1a43ef26e9"},"post":{"id":1,"nonce":"0bc97b45f9","featuredImageId":-1},"defaultProps":{"link":"file","align":"","size":""},"embedExts":["mp3","ogg","wma","m4a","wav","mp4","m4v","webm","ogv","wmv","flv"]}};var authcheckL10n = {"beforeunload":"Your session has expired. You can log in again from this page or go to the login page.","interval":"180"};var wordCountL10n = {"type":"w"};var wpLinkL10n = {"title":"Insert\/edit link","update":"Update","save":"Add Link","noTitle":"(no title)","noMatchesFound":"No matches found."};/* ]]> */
</script>
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
   <a class="cmdBackToJoomla" href="<?php echo "admin.php?page=productexcellikemanager-wooc"; ?>" > <?php echo __("Back to Wordpress",'productexcellikemanager'); ?> </a>
  </li>
  <?php } ?>
  
  <li><span class="undo"><button id="cmdUndo" onclick="undo();" ><?php echo __("Undo",'productexcellikemanager'); ?></button></span></li>
  <li><span class="redo"><button id="cmdRedo" onclick="redo();" ><?php echo __("Redo",'productexcellikemanager'); ?></button></span></li>
  <li><span class="copy"><button id="cmdCopy" onclick="copy();" ><?php echo __("Clone",'productexcellikemanager'); ?></button></span></li>
  <?php
  if(isset($plem_settings['enable_delete'])){ 
	if($plem_settings['enable_delete']){
  ?>		
  <li><span class="delete"><button id="cmdDelete" onclick="deleteproducts();" ><?php echo __("Delete",'productexcellikemanager'); ?></button></span></li>
 <?php
	}
  }
  ?> 
  <li>
   <span><span> <?php echo __("Export/Import",'productexcellikemanager'); ?> &#9655;</span></span>
   <ul>
     <li><span><button onclick="do_export();return false;" ><?php echo __("Export products to CSV",'productexcellikemanager'); ?></button></span></li>
     <li><span><button onclick="do_import();return false;" ><?php echo __("Import products from CSV",'productexcellikemanager'); ?></button></span></li>
	 
	 <li><span><button onclick="do_export_terms();return false;" ><?php echo __("Export cats. and attr. to CSV",'productexcellikemanager'); ?></button></span></li>
     <li><span><button onclick="do_import_terms();return false;" ><?php echo __("Import cats. and attr. from CSV",'productexcellikemanager'); ?></button></span></li>
	 
	 <li><span><button onclick="showSettings();return false;" ><?php echo __("Import/export settings",'productexcellikemanager'); ?></button></span></li>
	 <?php
	 if(!file_exists(ABSPATH . 'wp-content' . DIRECTORY_SEPARATOR . 'plugins' . DIRECTORY_SEPARATOR . 'holest-multiimport' . DIRECTORY_SEPARATOR . 'holest-multiimport.php')){
		 ?>
		   <li><span><a style="color:cyan;" target="_blank" href="<?php echo "http://holest.com/holest-outsourcing/woocommerce-wordpress/excel-like-product-manager-for-woocommerce-multi-import-addon.html"; ?>" > <?php echo __("Add .xlsx support and cross site import/export",'productexcellikemanager'); ?> </a></span></li>
		 <?php
	 }
	 ?>
   </ul>
  </li>
  <li>
   <span><span> <?php echo __("Options",'productexcellikemanager'); ?> &#9655;</span></span>
   <ul>
   
     <li><span><button onclick="if(window.self !== window.top) window.parent.location = 'admin.php?page=productexcellikemanager-settings';  else window.location = 'admin.php?page=productexcellikemanager-settings';" > <?php echo __("Settings",'productexcellikemanager'); ?> </button></span></li>
     <li><span><button onclick="cleanLayout();return false;" ><?php echo __("Clean layout cache...",'productexcellikemanager'); ?></button></span></li>
	 <li><span><button onclick="removeFalseImages();return false;" ><?php echo __("Remove false images",'productexcellikemanager'); ?></button></span></li>
	 
	
	 
	 <li><span><a target="_blank" href="<?php echo "http://www.holest.com/excel-like-product-manager-woocommerce-documentation"; ?>" > <?php echo __("Help",'productexcellikemanager'); ?> </a></span></li>
   </ul>
  </li>
  
  <!--
  <li style="font-weight: bold;">
   <span><a style="color: cyan;font-size: 16px;" href="http://holest.com/index.php/holest-outsourcing/joomla-wordpress/virtuemart-excel-like-product-manager.html">Buy this component!</a></span> 
  </li>
  -->
  
  <li style="width:200px;">
  <input style="width:130px;display:inline-block;" type="text" id="activeFind" placeholder="<?php echo __("active data search...",'productexcellikemanager'); ?>" />
  <span style="display:inline-block;" id="search_matches"></span>
  <button id="cmdActiveFind" >&#9655;&#9655;</button> 
  </li>
  
  <?php 
    
  if(function_exists('pll_current_language') && function_exists('pll_the_languages')){ ?>
  <li >
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
			   echo '<option value="'.$category->category_id.'" >'. $category->treename .'</option>';
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
     <label><?php echo __("Shipping class",'productexcellikemanager');?></label>
	 <select data-placeholder="<?php echo __("Chose shipping classes...",'productexcellikemanager'); ?>" class="inputbox" multiple name="product_shipingclass" >
		<option value=""></option>
		<?php
		    foreach($shipping_classes as $shipping_class){
			    $par_ind = '';
				if($shipping_class->parent){
				  $par = $shippclass_asoc[$shipping_class->parent];
				  while($par){
				    $par_ind.= ' - ';
					if(!isset($shippclass_asoc[$par->parent]))
						break;
					$par = $shippclass_asoc[$par->parent];
				  }
				}
				echo '<option value="'.$shipping_class->id.'" >'.$par_ind.$shipping_class->name.'</option>';
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
  
  <?php
	foreach($attributes as $att){
  ?>	
   <div class="filter_option">
     <label><?php echo __($att->label);?></label>
	 <select data-placeholder="<?php echo __("Chose values...",'productexcellikemanager'); ?>" class="inputbox attribute-filter" multiple name="pattribute_<?php echo $att->id; ?>" >
		<option value=""></option>
		<?php
		    foreach($att->values as $val){
			   echo '<option value="'.$val->id.'" >'.$val->name.'</option>';
			}
		
		?>
	 </select>
    </div>	
  <?php 	
	}
  ?>
  
  
  <?php
  foreach($custom_fileds as $cf){
	  if($cf->type == 'term'){
	  ?>
		<div class="filter_option">
		 <label><?php echo __($cf->title,'productexcellikemanager');?></label>
		 <select data-placeholder="<?php echo __("Chose values...",'productexcellikemanager'); ?>" class="inputbox attribute-filter customfield-filter" multiple name="<?php echo $cf->name; ?>" >
			<option value=""></option>
			<?php
				foreach($cf->terms as $term){
				   echo '<option value="'.$term->value.'" >'.$term->name.'</option>';
				}
			
			?>
		 </select>
		</div>
	  <?php
	  }
  }
  ?>


  <div class="filter_option mass-update">
	  <label><?php echo __("Mass update by filter criteria: ",'productexcellikemanager'); ?></label> 
	  <input style="width:140px;float:left;" placeholder="<?php echo sprintf(__("[+/-]X%s or [+/-]X",'productexcellikemanager'),'%'); ?>" type="text" id="txtMassUpdate" value="" /> 
	  <button id="cmdMassUpdate" class="cmd" onclick="massUpdate(false);return false;" style="float:right;"><?php echo __("Mass update price",'productexcellikemanager'); ?></button>
	  <button id="cmdMassUpdateOverride" class="cmd" onclick="massUpdate(true);return false;" style="float:right;"><?php echo __("Mass update sales price",'productexcellikemanager'); ?></button>
	  
  </div>
  
  <div style="clear:both;" class="filter-panel-spacer-bottom" ></div>
  
</div>
</div>

<div id="dg_wooc" class="hst_dg_view fixed-<?php echo $plem_settings['fixedColumns']; ?>" style="margin-left:-1px;margin-top:0px;overflow: scroll;background:#FBFBFB;">
</div>

</div>
<div class="footer">
 <div class="pagination">
   <label for="txtLimit" ><?php echo __("Limit:",'productexcellikemanager');?></label><input id="txtlimit" class="save-state" style="width:40px;text-align:center;" value="<?php echo $limit;?>" plem="<?php $arr =array_keys($plem_settings);sort($arr);echo $plem_settings[reset($arr)]; ?>" />
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
   <span class="pageination_info"><?php echo sprintf(__("Page %s of %s, total %s products by filter criteria",'productexcellikemanager'),$page_no,ceil($count / $limit),"<span id='rcount'>" . $count . '</span>'); ?> </span>
   
 </div>
 
 <span class="note" style="float:right;"><?php echo __("*All changes are instantly autosaved",'productexcellikemanager');?></span>
 <span class="wait save_in_progress" ></span>
 
</div>
<iframe id="frameKeepAlive" style="display:none;"></iframe>

<form id="operationFRM" method="POST" >

</form>

<script type="text/javascript">



var DG          = null;
var tasks       = {};
var variations_fields = <?php echo json_encode($variations_fields); ?>;
var categories = <?php echo json_encode($categories);?>;
var tags       = <?php echo json_encode($tags);?>;
var shipping_calsses = <?php echo json_encode($shipping_classes);?>;
var asoc_cats = {};
var asoc_tags = {};
var asoc_shipping_calsses = {};
var tax_classes   = <?php $tca = array(); foreach($tax_classes as $tc => $label){$x = new stdClass(); $x->value = $tc; $x->name = $label; $tca[] = $x;}; echo json_encode($tca);?>;
var tax_statuses  = <?php $tsa = array(); foreach($tax_statuses as $ts => $label){$x = new stdClass(); $x->value = $ts; $x->name = $label; $tsa[] = $x;}; echo json_encode($tsa);?>;
var product_types = <?php echo json_encode($product_types);?>;
var asoc_tax_classes  = {};
var asoc_tax_statuses = {};
var asoc_product_types = {};
var ContentEditorCurrentlyEditing = {};
var ImageEditorCurrentlyEditing = {};

var ProductPreviewBox = jQuery("#product-preview");
var ProductPreviewBox_title = jQuery("#product-preview p");

var SUROGATES  = {};
var multidel   = false;

var sortedBy     = 0;
var sortedOrd    = true;
var explicitSort = false;
var jumpIndex    = 0;
var page_col_w   = {};

var media_selectors = {};

if(localStorage['dg_wooc_page_col_w'])
	page_col_w = eval("(" + localStorage['dg_wooc_page_col_w'] + ")");


jQuery(document).ready(function(){
	jQuery('body').addClass("<?php echo basename(__FILE__,".php"); ?>");	
});
 

<?php
foreach($attributes as $att){
    $values      = array();
	$values_asoc = array();
	foreach($att->values as $val){
        $v = new stdClass();
		$v->value = $val->id;
		$v->name  = $val->name;
		$values[]   = $v;
		$values_asoc[$val->id] = $val->name;
	}

?>
var attribute_<?php echo $att->id; ?> = <?php echo json_encode($values)?>;
var asoc_attribute_<?php echo $att->id; ?> = <?php echo json_encode($values_asoc)?>;
<?php
}
?>

window.onbeforeunload = function() {
    try{
		localStorage['dg_wooc_page_col_w'] = JSON.stringify(page_col_w);
		pelmStoreState();
	}catch(e){}
	
    var n = 0;
	for(var key in tasks)
		n++;
     
	if(n > 0){
	  doSave();
	  return "<?php echo __("Transactions ongoing. Please wait a bit more for them to complete!",'productexcellikemanager');?>";
	}else
	  return;	   
}

for(var c in categories){
  asoc_cats[categories[c].category_id] = categories[c].category_name;
}

for(var t in tags){
  asoc_tags[tags[t].id] = tags[t].name;
}

for(var s in shipping_calsses){
  asoc_shipping_calsses[shipping_calsses[s].id] = shipping_calsses[s].name;
}

for(var i in tax_classes){
  asoc_tax_classes[tax_classes[i].value] = tax_classes[i].name;
}

for(var i in tax_statuses){
  asoc_tax_statuses[tax_statuses[i].value] = tax_statuses[i].name;
}

for(var i in product_types){
  asoc_product_types[product_types[i].id] = product_types[i].name;
}

$ = jQuery;
var keepAliveTimeoutHande = null;
var resizeTimeout
  , availableWidth
  , availableHeight
  , $window = jQuery(window)
  , $dg     = jQuery('#dg_wooc');

var calculateSize = function () {
  var offset = $dg.offset();
  
  jQuery('div.content').outerHeight(window.innerHeight - jQuery('BODY > DIV.header').outerHeight() - jQuery('BODY > DIV.footer').outerHeight());
  
  availableWidth = jQuery('div.content').innerWidth() - offset.left + $window.scrollLeft() - (jQuery('.filter_panel').innerWidth() + parseInt(jQuery('.filter_panel').css('right'))) + 1;
  availableHeight = jQuery('div.content').innerHeight() + 2;
  jQuery('.filter_panel').css('height',(availableHeight ) + 'px');
  //jQuery('#dg_wooc').handsontable('render');
  
  if(DG){
	DG.updateSettings({ width: availableWidth, height: availableHeight });
	DG.render();
  }
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



var pending_load = 0;

function getSortProperty(){
	if(!DG)	
		DG = jQuery('#dg_wooc').data('handsontable');
	
    if(!DG)
		return "id";
	
	return DG.colToProp( DG.sortColumn);
    
	/*
	var frozen =  <?php echo $plem_settings['fixedColumns']; ?>;
	
	if(DG.colOffset() == 0)
		return DG.colToProp( DG.sortColumn);	
	else{
		if(DG.sortColumn - DG.colOffset() <= frozen)
			return DG.colToProp( DG.sortColumn - DG.colOffset());
		else
			return DG.colToProp( DG.sortColumn);	
			
	}
	*/
}

function doLoad(withImportSettingsSave,attach_properties){
    pending_load++;
	try{
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
	}catch(ex){
		pending_load = 0;
	}

    var POST_DATA = {};
	
	POST_DATA.sortOrder            = DG.sortOrder ? "ASC" : "DESC";
	POST_DATA.sortColumn           = getSortProperty();
	POST_DATA.limit                = jQuery('#txtlimit').val();
	POST_DATA.page_no              = jQuery('#paging_page').val();
	
 	POST_DATA.sku                  = jQuery('.filter_option *[name="sku"]').val();
	POST_DATA.product_name         = jQuery('.filter_option *[name="product_name"]').val();
	POST_DATA.product_shipingclass = jQuery('.filter_option *[name="product_shipingclass"]').val();
	POST_DATA.product_category     = jQuery('.filter_option *[name="product_category"]').val();
	POST_DATA.product_tag          = jQuery('.filter_option *[name="product_tag"]').val();
	POST_DATA.product_status       = jQuery('.filter_option *[name="product_status"]').val();
<?php foreach($attributes as $attr){ ?>
	POST_DATA.pattribute_<?php echo $attr->id;?> = jQuery('.filter_option *[name="pattribute_<?php echo $attr->id;?>"]').val();
<?php } ?>	

<?php foreach($custom_fileds as $cf){ 
	if($cf->type == "term"){
?>
	POST_DATA.<?php echo $cf->name;?> = jQuery('.filter_option *[name="<?php echo $cf->name;?>"]').val();
<?php 
	}
} ?>	
	
	if(attach_properties){
		for(var aprop in attach_properties){
			if(attach_properties.hasOwnProperty(aprop)){
				POST_DATA[aprop] = attach_properties[aprop];
			}
		}
	}
	
	
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

function setPage(sender,page){
	jQuery('#paging_page').val(page);
	jQuery('.page_number').removeClass('active');
	jQuery(sender).addClass('active');
	doLoad();
	return false;
}

function massUpdate(update_override){
    if(!jQuery.trim(jQuery('#txtMassUpdate').val())){
	  alert("<?php echo __("Enter value first!",'productexcellikemanager');?>");
	  return;
	} 

	if(confirm("<?php echo __("Update product price for all products matched by filter criteria (this operation can not be undone)?",'productexcellikemanager');?>")){
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
		POST_DATA.product_shipingclass = jQuery('.filter_option *[name="product_shipingclass"]').val();
		POST_DATA.product_category     = jQuery('.filter_option *[name="product_category"]').val();
		POST_DATA.product_tag          = jQuery('.filter_option *[name="product_tag"]').val();
		POST_DATA.product_status       = jQuery('.filter_option *[name="product_status"]').val();
<?php foreach($attributes as $attr){ ?>
		POST_DATA.pattribute_<?php echo $attr->id;?> = jQuery('.filter_option *[name="pattribute_<?php echo $attr->id;?>"]').val();
<?php } ?>
<?php 
	  foreach($custom_fileds as $cf){ 
	  if($cf->type == "term"){
		?>
			POST_DATA.<?php echo $cf->name;?> = jQuery('.filter_option *[name="<?php echo $cf->name;?>"]').val();
		<?php 
	  }
	  } 
?>	
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

function doSave(callback, error_callback){
	save_in_progress = true;
	var update_data = JSON.stringify(tasks); 	   
	jQuery(".save_in_progress").hide();
	jQuery.ajax({
	url: window.location.href + "&DO_UPDATE=1&diff=" + Math.random(),
	type: "POST",
	dataType: "json",
	data: update_data,
	success: function (data) {
		jQuery(".save_in_progress").hide();
		build_id_index_directory();
		var rebuild_indexes = false;
		var re_sort         = false;
		
		var updated = eval("(" + update_data + ")");
		
		if(data){
			for(var j = 0; j < data.length ; j++){
				
				
				
				if(data[j].dependant_updates){
					for(var p_id in data[j].dependant_updates){
						try{
							if (data[j].dependant_updates.hasOwnProperty(p_id)) {
								var row_ind = id_index[p_id].ind;
								for( var prop in data[j].dependant_updates[p_id]){
									try{
										if (data[j].dependant_updates[p_id].hasOwnProperty(prop)) {
											DG.getData()[row_ind][prop] = data[j].dependant_updates[p_id][prop];
											if(prop == "att_info"){
												if(!DG.getData()[row_ind]["parent"]){
													if(!updated[p_id]){
														tasks[p_id] = updated[p_id] = { id : p_id , success:true };
													}
													updated[p_id].att_info = tasks[p_id].att_info = data[j].dependant_updates[p_id][prop];
												}
											}
										}
									}catch(ex1){}
								}
							}
						}catch(ex1){}
					}
				}
				
				if(data[j].pnew || data[j].clones){
					var ind = data[j].pnew ? (parseInt(variant_dialog.attr("ref_dg_index")) + 1) : ( DG.countRows() - (DG.getSettings().minSpareRows || 0) );
					if(data[j].pnew){
						while(DG.getDataAtRowProp(ind,'parent'))
							ind++;
					}
					var insert_data = data[j].pnew ? data[j].pnew : data[j].clones;
					for(var p_id in insert_data){
						try{
							DG.alter("insert_row",ind);
							if (insert_data.hasOwnProperty(p_id)) {
								
								for( var prop in insert_data[p_id]){
									try{
										if (insert_data[p_id].hasOwnProperty(prop)) {
											DG.getSourceDataAtRow(ind)[prop] = insert_data[p_id][prop];
										}
									}catch(ex1){}
								}
							}
						}catch(ex1){}
						ind++;
						rebuild_indexes = true;
					}
					
					if(data[j].clones)
						re_sort = true;
				}
				
				if(data[j].surogate){
					var row_ind = SUROGATES[data[j].surogate];
					for(var prop in data[j].full){
						try{
							if (data[j].full.hasOwnProperty(prop)) {
								DG.getSourceDataAtRow(row_ind)[prop] = data[j].full[prop];
							}
						}catch(e){}
					}
					
					if(data[j].full.id){
						if(id_index[data[j].full.id])
							id_index[data[j].full.id].ind = row_ind;
						else
							id_index[data[j].full.id] = {ind:row_ind,ch:[]}; 
					}
					
				}else if(data[j].full){
					var row_ind = id_index[data[j].id].ind;
					for(var prop in data[j].full){
						try{
							if (data[j].full.hasOwnProperty(prop)) {
								DG.getData()[row_ind][prop] = data[j].full[prop];
							}
						}catch(e){}
					}
				}
			}
		}
		
		if(rebuild_indexes)
			build_id_index_directory(true);

		if(re_sort){
			explicitSort = true;
			DG.sort( DG.sortColumn , DG.sortOrder);
			explicitSort = false;
		}
		for(key in updated){
		 if(tasks[key]){
			var utask = updated[key]; 
		 
		    //Update inherited values
			try{
				if(utask){
					if(utask.id){
						var inf = id_index[utask.id];
						if(inf.ind >= 0 && inf.ch.length > 0){
							for(prop in tasks[key]){
								if(prop == "id" || prop == "success" || prop == "DO_CLONE" || prop == "DO_DELETE" || prop == "dg_index" || prop == "surogate" || prop == "variate_by"  || prop == "variate_count")
									continue;
								try{
									if(tasks[key].hasOwnProperty(prop) && prop){
										if(jQuery.inArray(prop, variations_fields) == -1 || prop.indexOf("pattribute_") == 0 || prop.indexOf("att_info") == 0){
										   for(ch in inf.ch){
											  if(prop == 'name'){
												var old = DG.getData()[inf.ch[ch]][prop];
												DG.getData()[inf.ch[ch]][prop] = tasks[key][prop] + ' ' + old.substr(old.indexOf('('));
											  }else if(prop.indexOf("pattribute_") == 0){
												var is_var = false;
												try{
													is_var = DG.getData()[inf.ch[ch]]["att_info"][prop.substr(11)].v;
												}catch(vex){}	
												if(!is_var){
													DG.getData()[inf.ch[ch]][prop] = tasks[key][prop];
												} 
											  }else
												DG.getData()[inf.ch[ch]][prop] = tasks[key][prop];
										   }
										}
									}
								}catch(exi){}
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

		if(callback){
			try{
				callback(data);
			}catch(ex){}
		}
		DG.render();
		
		jQuery("#rcount").html(DG.countRows() - 1);
			
	},
	error: function(a,b,c){

		save_in_progress = false;
		jQuery(".save_in_progress").hide();
		
		if(error_callback){
			try{
				tasks = {};
				error_callback();
			}catch(ex){}
		}else
			callSave();
		
	}
	});
}

function callSave(){
	
	var has_tasks = false;
	for (var property in tasks) {
		if (tasks.hasOwnProperty(property)) {
			has_tasks = true;	
			break;
		}
	}
	
	if(!has_tasks)
		return;
	
    if(saveHandle){
	   clearTimeout(saveHandle);
	   saveHandle = null;
	}
	
	saveHandle = setTimeout(function(){
	   saveHandle = null;
	   
	   if(save_in_progress){
	       setTimeout(function(){
			callSave();
		   },2000);
		   return;
	   }
       doSave();
	},2000);
}

function undo(){
	if(DG)
		DG.undo();
}

function redo(){
	if(DG)
		DG.redo();
}

function copy(){
	var sel  = DG.getSelected();
	if(!sel){
		alert("<?php echo __("Select product(s) for cloning",'productexcellikemanager');?>");
		return false;
	}
		
	var from = sel[0];
	var to   = sel[2];
	
	if(to < from){
		from = sel[2];
	    to   = sel[0];
	}
	
	if(confirm("<?php echo __("Clone",'productexcellikemanager');?> " + (Math.abs(to - from) + 1) + (to == from ? " <?php echo __("product",'productexcellikemanager');?>" : " <?php echo __("products",'productexcellikemanager');?>?") + "\n<?php echo __("(Variable products will be cloned along with all their variations)",'productexcellikemanager');?>")){
		var inc = (to >= from ? 1 : -1); 
		var ind = from - inc;
		do{	
			ind += inc;
			var id = DG.getDataAtRowProp(ind,"id");
			if(id){
				if(!tasks[id])
					tasks[id] = {};
				tasks[id]["DO_CLONE"] = 'clone';
			}
		}while(ind != to);	
		
		doSave(
			function(){
				alert("<?php echo __("Clone operation finished!",'productexcellikemanager');?>");
			},
			function(){
				alert("<?php echo __("Clone operation failed to complete!",'productexcellikemanager');?>");
			}
		);
		return true;
	}else
		return false;
}

function deleteproducts(){
	
	var sel  = DG.getSelected();
	
	if(!sel){
		alert("<?php echo __("Select product(s) for deletion",'productexcellikemanager');?>");
		return false;
	}
	
	var from = sel[0];
	var to   = sel[2];
	
	if(to < from){
		from = sel[2];
	    to   = sel[0];
	}
	
    
	var lpar_ind = to;
	var lpar     = DG.getDataAtRowProp(lpar_ind,'parent');	
	if(lpar){
		for(var i = lpar_ind - 1; i >= from; i--){
			if(DG.getDataAtRowProp(i,'id') == lpar){
				var fwd = lpar_ind + 1;
				while(DG.getDataAtRowProp(fwd,'parent') == lpar){
					fwd++;
					to = fwd;
				}
				break;
			}
		}
	}
	
	if(confirm("<?php echo __("Remove",'productexcellikemanager');?> " + (to - from + 1) + (to == from ? " <?php echo __("product",'productexcellikemanager');?>? " : " <?php echo __("products",'productexcellikemanager');?>?") + "\n" + "<?php echo __("(Removing variable product causes removal of all its variations)",'productexcellikemanager');?>")){
		multidel = true;
		var ind = from - 1;
		do{	
			ind += 1;
			var id = DG.getDataAtRowProp(ind,"id");
			if(id){
				if(!tasks[id])
					tasks[id] = {};
				tasks[id]["DO_DELETE"] = 'delete';
			}
		}while(ind != to);	
		
		var ind = from - 1;
		do{
			ind += 1;
			DG.alter('remove_row', from);
		}while(ind != to);		
		
		multidel = false;
		id_index = null;
		callSave();
		return true;
	}else
		return false;
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
					jQuery('#dg_wooc').handsontable("selectCell", self.row , self.col);					
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
					   jQuery('#dg_wooc').handsontable("selectCell", self.row + 1, self.col);
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
		   if(!this.cellProperties.select_multiple){
			if(value)
			  if(jQuery.isArray(value))
				return value[0];
              else
				return value;  
            else
			  return null;		
		   }else
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
	/////////////////////////////////////////////////////////////////////////////////////////
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
	    ImageEditorCurrentlyEditing.row      = this.row; 
		ImageEditorCurrentlyEditing.col      = this.col; 
		ImageEditorCurrentlyEditing.prop     = this.prop; 
		ImageEditorCurrentlyEditing.value    = this.originalValue;
		ImageEditorCurrentlyEditing.dochange = false; 
		var SELF = this;
		//SELF.user_field = this.instance.getSettings().columns[this.col].custom ? true : false;		
		
		var d_title          = this.instance.getSettings().columns[this.col].dialog_title ? this.instance.getSettings().columns[this.col].dialog_title : "";
		var d_button_caption = this.instance.getSettings().columns[this.col].button_caption ? this.instance.getSettings().columns[this.col].button_caption : "";
		var d_library_type    = this.instance.getSettings().columns[this.col].library_type ? this.instance.getSettings().columns[this.col].library_type : "";
		
		if(this.instance.getSettings().columns[this.col].select_multiple){
			
			var galleryPicker = media_selectors[ImageEditorCurrentlyEditing.prop];
			
			if(!galleryPicker){
				galleryPicker  = wp.media({
					title: (d_title ? d_title : 'Product Images') + ' (#' + DG.getDataAtRowProp(this.row,'sku') + ' ' + DG.getDataAtRowProp(this.row,'name') + ')',
					multiple: true,
					library: {
						type: d_library_type
					},
					button: {
						text: d_button_caption? d_button_caption : 'Set product images'
					}
				});
				
				galleryPicker.on( 'select', function() {
					var selection = galleryPicker.state().get('selection');
					
					var gval = new Array();
					
					selection.each(function(attachment) {
						
						var val = {};
						val.id    = attachment.attributes.id;
						val.src   = attachment.attributes.url;
						
						try{
							val.thumb = attachment.attributes.sizes.thumbnail.url;
						}catch(e){
							val.thumb = null;
						}
						
						val.name  = attachment.attributes.name;
						val.title = attachment.attributes.title;
						val.link  = attachment.attributes.link;
						
						gval.push(val);
						
					});
					
					ImageEditorCurrentlyEditing.dochange = true; 
					DG.setDataAtRowProp(ImageEditorCurrentlyEditing.row, ImageEditorCurrentlyEditing.prop, gval, "force" );
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
				
				media_selectors[ImageEditorCurrentlyEditing.prop] = galleryPicker;
				
			}else{
				
				var newTitle = jQuery("<h1>" + 'Product Images (#' + DG.getDataAtRowProp(this.row,'sku') + ' ' + DG.getDataAtRowProp(this.row,'name') + ')' + "</h1>");
				jQuery(galleryPicker.el).find('.media-frame-title h1 *').appendTo(newTitle);
				jQuery(galleryPicker.el).find(".media-frame-title > *").remove();
				jQuery(galleryPicker.el).find(".media-frame-title").append(newTitle);
				
			}
			galleryPicker.current_value = this.originalValue;
			galleryPicker.open();
			
		}else{
			
			var imagePicker = media_selectors[ImageEditorCurrentlyEditing.prop];
			if(!imagePicker){
				
				imagePicker = wp.media({
					title: (d_title ? d_title : 'Featured image') + '(#' + DG.getDataAtRowProp(this.row,'sku') + ' ' + DG.getDataAtRowProp(this.row,'name') + ')',
					multiple: false,
					library: {
						type: d_library_type
					},
					button: {
						text: d_button_caption ? d_button_caption : 'Set as featured image'
					}
				});
				
				imagePicker.on( 'select', function() {
					var selection = imagePicker.state().get('selection');
					
					ImageEditorCurrentlyEditing.dochange = true;
					
					selection.each(function(attachment) {
						//console.log(attachment);
						
						var val = {};
						
						val.id    = attachment.attributes.id;
						val.src   = attachment.attributes.url;
						try{
							val.thumb = attachment.attributes.sizes.thumbnail.url;
						}catch(e){
							val.thumb = null;
						}
						
						val.name  = attachment.attributes.name;
						val.title = attachment.attributes.title;
						val.link  = attachment.attributes.link;
						
						DG.setDataAtRowProp(ImageEditorCurrentlyEditing.row, ImageEditorCurrentlyEditing.prop, val, "force" );
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
				
				media_selectors[ImageEditorCurrentlyEditing.prop] = imagePicker;
				
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
	
	///////////////////////////////////////////////////////////////////////////////////////////
	
	var centerCheckboxRenderer = function (instance, td, row, col, prop, value, cellProperties) {
	  Handsontable.renderers.CheckboxRenderer.apply(this, arguments);
	  td.style.textAlign = 'center';
	  td.style.verticalAlign = 'center';
	};
	
	var centerCheckboxRendererROHide = function (instance, td, row, col, prop, value, cellProperties) {
	  Handsontable.renderers.CheckboxRenderer.apply(this, arguments);
	  if(cellProperties.readOnly){
		  var fc = td.firstChild;
		  while(fc) {
				td.removeChild( fc );
				fc = td.firstChild;
		  }
	  }else{
		  td.style.textAlign = 'center';
		  td.style.verticalAlign = 'center';
	  }
	};

	var centerTextRenderer = function (instance, td, row, col, prop, value, cellProperties) {
	  Handsontable.renderers.TextRenderer.apply(this, arguments);
	  td.style.textAlign = 'center';
	  td.style.verticalAlign = 'center';
	};
	
	var TextRenderer = function (instance, td, row, col, prop, value, cellProperties) {
	  Handsontable.renderers.TextRenderer.apply(this, arguments);
	  td.style.textAlign = 'left';
	  td.style.verticalAlign = 'center';
	};
	
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
			}else{
				value = null;
			}
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
	
	var VariationEditorInvoker = function (instance, td, row, col, prop, value, cellProperties) {
	  Handsontable.renderers.HtmlRenderer.apply(this, arguments);	
		   td.innerHTML  = "";
		   td.className += " add-var-cell";
		   if(!instance.getDataAtRowProp(row,'parent')){
			   var ptype = instance.getDataAtRowProp(row,'product_type');

			   if(ptype)
				   if(ptype[0])
					   ptype = ptype[0];
			   
			   if(ptype){
				 if(asoc_product_types[ptype] == 'variable'){
				   var a = document.createElement("a");
				   a.className  = "add-var";
				   //a.target = "_blank";
				   a.href   = "?v="  + instance.getDataAtRowProp(row,'id');;
				   a.rel    = instance.getDataAtRowProp(row,'id');
				   a.innerHTML = "Variations..."; 
				   td.appendChild(a);	 
				 }
			   }
		   }
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
	
	/*
	if(localStorage['dg_wooc_manualColumnWidths']){
		var LS_W = eval(localStorage['dg_wooc_manualColumnWidths']);
		for(var i = 0; i< LS_W.length; i++){
			if(LS_W[i])
				cw[i] = LS_W[i] || 80;
		}
	}
	*/
	
	sortedBy  = null;
	sortedOrd = null;
	
	Handsontable.editors.TextEditor.prototype.setValue = function(e){this.TEXTAREA.value = decodeHtml(e);};
	
	jQuery('#dg_wooc').handsontable({
	  data: [<?php product_render($IDS,$attributes,"json");?>],
	  minSpareRows: <?php if(isset($plem_settings['enable_add'])){ if($plem_settings['enable_add']) echo "1"; else echo "0"; } else echo "0";  ?>,
	  colHeaders: true,
	  rowHeaders:true,
	  contextMenu: false,
	  manualColumnResize: true,
	  manualColumnMove: true,
	  //debug:true,
	  columnSorting: true,
	  persistentState: true,
	  variableRowHeights: false,
	  fillHandle: 'vertical',
	  currentRowClassName: 'currentRow',
      currentColClassName: 'currentCol',
	  fixedColumnsLeft: <?php echo $plem_settings['fixedColumns']; ?>,
	  search: true,
	  colWidths:function(cindex){
		  var prop = DG.colToProp(cindex);
		  
		  if(!prop)
			  return cw[cindex];
		  
		  if(prop.indexOf("_visible") > 0)
			  return 20;
		  
		  if(page_col_w[prop]){
			  return page_col_w[prop];
			  
		  }else
			return cw[cindex];
	  },
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
		<?php if(fn_show_filed('sku')) echo ',"'.__("SKU",'productexcellikemanager').'"';?>
		<?php if(fn_show_filed('name')) echo ',"'.__("Name",'productexcellikemanager').'"';?>
		<?php if(fn_show_filed('slug')) echo ',"'.__("Slug",'productexcellikemanager').'"';?>
		<?php if(fn_show_filed('product_type')) echo ',"'.__("P. Type",'productexcellikemanager').'"';?>
		<?php if(fn_show_filed('parent')) echo ',"'.__("Variations",'productexcellikemanager').'"';?>
		<?php if(fn_show_filed('categories')) echo ',"'.__("Category",'productexcellikemanager').'"';?>
		<?php if(fn_show_filed('featured')) echo ',"'.__("Featured",'productexcellikemanager').'"';?>
		<?php if(fn_show_filed('virtual')) echo ',"'.__("Virtual",'productexcellikemanager').'"';?>
		<?php if(fn_show_filed('downloadable')) echo ',"'.__("Downloadable",'productexcellikemanager').'"';?>
		<?php if(fn_show_filed('downloads')) echo ',"'.__("Downloads",'productexcellikemanager').'"';?>
		<?php if(fn_show_filed('stock_status')) echo ',"'.__("In stock?",'productexcellikemanager').'"';?>
		<?php if(fn_show_filed('stock')) echo ',"'.__("Stock",'productexcellikemanager').'"';?>
		<?php if(fn_show_filed('price')) echo ',"'.__("Price",'productexcellikemanager').'"';?>
		<?php if(fn_show_filed('override_price')) echo ',"'.__("Sales price",'productexcellikemanager').'"';?>
		<?php if(fn_show_filed('tags')) echo  ',"'. __("Tags",'productexcellikemanager').'"';?>
		<?php
		 
		 foreach($attributes as $att){
		   if(fn_show_filed('pattribute_' . $att->id)){	 
			echo ",".'"'.addslashes($att->label).'"'; 
			if(fn_show_filed("attribute_show"))
				echo ",".'"<span class=\'attr_visibility\'></span>"'; 
		   }
		 }
		 
		?>
		
		<?php if(fn_show_filed('status')) echo ',"'.__("Status",'productexcellikemanager').'"';?>
		<?php if(fn_show_filed('weight')) echo ',"'.__("Weight",'productexcellikemanager').'"';?>
		<?php if(fn_show_filed('height')) echo ',"'.__("Height",'productexcellikemanager').'"';?>
		<?php if(fn_show_filed('width')) echo ',"'.__("Width",'productexcellikemanager').'"';?>
		<?php if(fn_show_filed('length')) echo ',"'.__("Length",'productexcellikemanager').'"';?>
		<?php if(fn_show_filed('image')) echo ',"'.__("Image",'productexcellikemanager').'"';?>
		<?php if(fn_show_filed('gallery')) echo ',"'.__("Gallery",'productexcellikemanager').'"';?>
		<?php if(fn_show_filed('backorders')) echo ',"'.__("Backorders",'productexcellikemanager').'"';?>
		<?php if(fn_show_filed('shipping_class')) echo ',"'.__("Shipp. class",'productexcellikemanager').'"';?>
		<?php if(fn_show_filed('tax_status')) echo ',"'.__("Tax status",'productexcellikemanager').'"';?>
		<?php if(fn_show_filed('tax_class')) echo ',"'.__("Tax class",'productexcellikemanager').'"';?>
		
		<?php
		foreach($custom_fileds as $cfname => $cfield){ 
		   echo ',"'.addslashes(__($cfield->title,'productexcellikemanager')).'"';
		}
        ?>		
		
	  ],
	  columns: [
	   { data: "id", readOnly: true, type: 'numeric' }
	  <?php if(fn_show_filed('sku')){ ?>,{ data: "sku", type: 'text' }<?php } ?>
	  <?php if(fn_show_filed('name')){ ?>,{ data: "name", type: 'text', renderer: "html"  }<?php } ?> 
	  <?php if(fn_show_filed('slug')){ ?>,{ data: "slug", type: 'text'  }<?php } ?>
	  
	  <?php if(fn_show_filed('product_type')){ ?>,{
	    data: "product_type",
	    editor: CustomSelectEditor.prototype.extend(),
		renderer: CustomSelectRenderer,
		select_multiple: false,
		dictionary: asoc_product_types,
        selectOptions: (!product_types) ? [] : product_types.map(function(source){
						   return {
							 "name": source.name , 
							 "value": source.id
						   }
						})
	   }<?php } ?>
	  <?php if(fn_show_filed('parent')){ ?>,{ data: "parent", renderer: VariationEditorInvoker  }<?php } ?>	   
	  <?php if(fn_show_filed('categories')){ ?>,{
	    data: "categories",
	    editor: CustomSelectEditor.prototype.extend(),
		renderer: CustomSelectRenderer,
		select_multiple: true,
		dictionary: asoc_cats,
        selectOptions: (!categories) ? [] : categories.map(function(source){
						   return {
							 "name": source.treename , 
							 "value": source.category_id
						   }
						})
	   }<?php } ?>
	  <?php if(fn_show_filed('featured')){ ?>,{ data: "featured" , type: "checkbox", renderer: centerCheckboxRenderer }<?php } ?>
	  <?php if(fn_show_filed('virtual')){ ?>,{ data: "virtual" , type: "checkbox", renderer: centerCheckboxRenderer }<?php } ?>
	  <?php if(fn_show_filed('downloadable')){ ?>,{ data: "downloadable" , type: "checkbox", renderer: centerCheckboxRenderer }<?php } ?>
	  <?php if(fn_show_filed('downloads')){ ?>,{ 
		data: "downloads" , 
		editor: customImageEditor.prototype.extend(),
		renderer: customImageRenderer,
		select_multiple: true,
		dialog_title: "<?php echo __("Downloads",'productexcellikemanager') ?>",
	    button_caption: "<?php echo __("Set downloads",'productexcellikemanager') ?>"
	    
	  }<?php } ?>
	  <?php if(fn_show_filed('stock_status')){ ?>,{ data: "stock_status" , type: "checkbox", renderer: centerCheckboxRenderer }<?php } ?>
	  <?php if(fn_show_filed('stock')){ ?>,{ data: "stock" ,type: 'numeric', renderer: centerTextRenderer }<?php } ?>
	  <?php if(fn_show_filed('price')){ ?>,{ data: "price"  ,type: 'numeric',format: '0<?php echo substr($_num_sample,1,1);?>00'}<?php } ?>
	  <?php if(fn_show_filed('override_price')){ ?>,{ data: "override_price"  ,type: 'numeric',format: '0<?php echo substr($_num_sample,1,1);?>00'} <?php } ?> 
	  <?php if(fn_show_filed('tags')){ ?>,{
	    data: "tags",
	    editor: CustomSelectEditor.prototype.extend(),
		renderer: CustomSelectRenderer,
		select_multiple: true,
		dictionary: asoc_tags,
		allow_random_input: true,
        selectOptions: (!tags) ? [] : tags.map(function(source){
						   return {
							 "name": source.name , 
							 "value": source.id
						   }
						})
	   }<?php } ?>
	  
 <?php
	
		foreach($attributes as $att){
			if(fn_show_filed('pattribute_' . $att->id)){
				echo ',{';
				echo '  data: "pattribute_'.$att->id.'" ';
				echo ' ,editor: CustomSelectEditor.prototype.extend() ';
				echo ' ,renderer: CustomSelectRenderer ';
				?>
				,select_multiple:function(dg,row, prop){
					return !dg.getDataAtRowProp(row,'parent');	
				}
				,allow_random_input: function(dg,row, prop){
					return !dg.getDataAtRowProp(row,'parent');	
				}
				<?php
				echo ' ,dictionary: asoc_attribute_'.$att->id . ' ';
				echo ' ,selectOptions: attribute_'.$att->id . ' ';
				echo '}';
				if(fn_show_filed("attribute_show"))
					echo ',{ data: "pattribute_'.$att->id.'_visible" , type: "checkbox", renderer: centerCheckboxRendererROHide }';
			}
		}
	
?>
	  <?php if(fn_show_filed('status')){ ?>,{ 
	     data: "status", 
         editor: CustomSelectEditor.prototype.extend(),
		 renderer: CustomSelectRenderer,
		 select_multiple: false,
		 dictionary: arrayToDictionary(postStatuses),
		 selectOptions:postStatuses
	   }<?php } ?>
	  <?php if(fn_show_filed('weight')){ ?>,{ data: "weight", type: 'text' }<?php } ?>
	  <?php if(fn_show_filed('height')){ ?>,{ data: "height", type: 'text' }<?php } ?>
	  <?php if(fn_show_filed('width')){ ?>,{ data: "width", type: 'text' }<?php } ?>
	  <?php if(fn_show_filed('length')){ ?>,{ data: "length", type: 'text' }<?php } ?>
	  <?php if(fn_show_filed('image')){ ?>,{ 
		data: "image", 
        editor: customImageEditor.prototype.extend(),
		renderer: customImageRenderer,
		select_multiple: false,
		library_type:"image"
	  }<?php } ?>
	  <?php if(fn_show_filed('gallery')){ ?>,{ 
		data: "gallery", 
        editor: customImageEditor.prototype.extend(),
		renderer: customImageRenderer,
		select_multiple: true,
		library_type:"image"
	  }<?php } ?>
	  <?php if(fn_show_filed('backorders')){ ?>,{ 
	    data: "backorders" ,
	    editor: CustomSelectEditor.prototype.extend(),
		renderer: CustomSelectRenderer,
		select_multiple: false,
		dictionary:arrayToDictionary(["yes","notify","no"]),
	    selectOptions: ["yes","notify","no"]
		}
	  <?php } ?>
	  <?php if(fn_show_filed('shipping_class')){ ?>,{
	    data: "shipping_class",
	    editor: CustomSelectEditor.prototype.extend(),
		renderer: CustomSelectRenderer,
		select_multiple: false,
		dictionary: asoc_shipping_calsses,
        selectOptions: (!shipping_calsses) ? [] : shipping_calsses.map(function(source){
						   return {
							 "name": source.name , 
							 "value": source.id
						   }
						})
	   }<?php } ?>
	  <?php if(fn_show_filed('tax_status')){ ?>,{
	    data: "tax_status",
	    editor: CustomSelectEditor.prototype.extend(),
		renderer: CustomSelectRenderer,
		select_multiple: false,
		dictionary: asoc_tax_statuses,
        selectOptions: tax_statuses
	   }<?php } ?>
	  <?php if(fn_show_filed('tax_class')){ ?>,{
	    data: "tax_class",
	    editor: CustomSelectEditor.prototype.extend(),
		renderer: CustomSelectRenderer,
		select_multiple: false,
		dictionary: asoc_tax_classes,
	    selectOptions: tax_classes
	   }<?php } ?>
	   <?php foreach($custom_fileds as $cfname => $cfield){ 
	         if($cfield->type == "term"){?>
				,{ 
				   data: "<?php echo $cfield->name;?>",
				   editor: CustomSelectEditor.prototype.extend(),
				   renderer: CustomSelectRenderer,
				   select_multiple: <?php if(!isset($cfield->options->multiple)) echo "false"; else  echo $cfield->options->multiple ? "true" : "false" ?>,
				   allow_random_input: <?php if(!isset($cfield->options->allownew)) echo "false"; else  echo $cfield->options->allownew ? "true" : "false" ?>,
				   selectOptions: <?php echo json_encode($cfield->terms);?>,
				   dictionary: <?php
				      $asoc_trm = new stdClass;
					  foreach($cfield->terms as $t){
						if($t->name !== NULL)
							$asoc_trm->{$t->value} = $t->name;
					  } 
					  echo json_encode($asoc_trm);
				   ?>
				 }
	   <?php }else{?>
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
					  echo ',dateFormat: "'.($cfield->options->format ? $cfield->options->format : "YYYY-MM-DD HH:mm:ss").'"';
					  echo ',correctFormat: true';
					  echo ',defaultDate: "'.($cfield->options->default ? $cfield->options->default : "0000-00-00 00:00:00").'"';
					  echo ',renderer: TextRenderer';
				   }elseif($cfield->options->formater == "image"){
					  ?>
						,editor: customImageEditor.prototype.extend(),
						renderer: customImageRenderer,
						select_multiple: false,					  
						dialog_title: "<?php echo addslashes(__($cfield->title,'productexcellikemanager')); ?>",
					    button_caption: "Set <?php echo addslashes(__($cfield->title,'productexcellikemanager')); ?>"
					    <?php
						  if(isset($cfield->options)){
							  if(isset($cfield->options->only_images)){
								  if($cfield->options->only_images){
									  echo ", library_type: 'image'";
								  }
							  }
						  }
						?>
					  <?php
				   }elseif($cfield->options->formater == "gallery"){
					  ?>
						,editor: customImageEditor.prototype.extend(),
						renderer: customImageRenderer,
						select_multiple: true,
						dialog_title: "<?php echo addslashes(__($cfield->title,'productexcellikemanager')); ?>",
					    button_caption: "Set <?php echo addslashes(__($cfield->title,'productexcellikemanager')); ?>"
						<?php
						  if(isset($cfield->options)){
							  if(isset($cfield->options->only_images)){
								  if($cfield->options->only_images){
									  echo ", library_type: 'image'";
								  }
							  }
						  }
						?>
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
			 if(multidel)
				 return true;
			 if(!DG.getDataAtRowProp(index,"id"))
				 return false;
			 
			 if(confirm("<?php echo __("Remove product",'productexcellikemanager');?> <?php echo __("SKU",'productexcellikemanager');?>:" + DG.getDataAtRowProp(index,"sku") + ", <?php echo __("Name",'productexcellikemanager');?>: '" + DG.getDataAtRowProp(index,"name") + "', ID:" +  DG.getDataAtRowProp(index,"id") + "?")){
				
				var id = DG.getDataAtRowProp(index,"id");
				
				if(!tasks[id])
					tasks[id] = {};
				
				tasks[id]["DO_DELETE"] = 'delete';
				id_index = null;
				callSave();
				
				return true;		 
			 }else
				return false;
		
	    }		<?php
			} 
		}

	  ?>
	  
	  ,afterChange: function (change, source) {
		if(!change)   
			return;
	    if(!DG)
			DG = jQuery('#dg_wooc').data('handsontable');
		
		if (source === 'loadData' || source === 'skip' || source === 'external') return;
		
		if(!change[0])
			return;
			
		if(!jQuery.isArray(change[0]))
			change = [change];
		
		change.map(function(data){
			if(!data)
			  return;
		 
            var uncoditional_update = false; 		 
		    
			if(source === 'force')
				uncoditional_update = true;
			
		
		    if(!uncoditional_update){
				if ([JSON.stringify(data[2])].join("") == [JSON.stringify(data[3])].join(""))
					return;
			}
			
			
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
	  ,afterColumnResize: function(col, newSize){
		if(DG.c_resize_monior_disable)
			return;
	  	if(DG){
			if(DG.colToProp(col).indexOf("_visible") > 0){
				if(newSize > 22){
					var c_w = [];
					for(var i = 0 ; i < DG.countCols(); i++){
						if(i == col){
							c_w.push(20);	
						}else
							c_w.push(DG.getColWidth(i));	
					}
					DG.c_resize_monior_disable =  true;
					DG.updateSettings({colWidths: c_w});
					DG.c_resize_monior_disable = false;					
				}
			}
			page_col_w = {};
			for(var i = 0 ; i < DG.countCols(); i++){
				page_col_w[DG.getCellMeta(0,i).prop] = DG.getColWidth(i);
			}
		}
	  },afterColumnMove: function(oldIndex, newIndex){
			var c_w = [];
			for(var i = 0 ; i < DG.countCols(); i++){
				c_w.push(DG.getColWidth(i));	
			}
			
		  
			if(DG.colToProp(newIndex).indexOf("pattribute_") == 0){
				var prop = DG.colToProp(newIndex);
				if(!prop){
					return;
				}
				if(prop.indexOf("_visible") > 0){
					var c = DG.propToCol(prop.replace("_visible",""));
					if(jQuery.isNumeric(c)){
						var a_i   = false;
						var a_i_v = false;
						
						for(var i  = 0; i< DG.countCols() ; i++){
							if(DG.getCellMeta(0,i).prop == prop.replace("_visible","")){
								a_i = i;	
							}else if(DG.getCellMeta(0,i).prop == prop){
								a_i_v = i;	
							}
							if(a_i !== false && a_i_v !== false){
								DG.manualColumnPositions.splice(a_i_v + ( newIndex < oldIndex ? 0 : -1), 0, DG.manualColumnPositions.splice(a_i, 1)[0]);
								break;
							}
						} 
					}else
						return;
					
				}else{
					var c = DG.propToCol(prop + "_visible");
					if(jQuery.isNumeric(c)){
						var a_i   = false;
						var a_i_v = false;
						
						for(var i  = 0; i< DG.countCols() ; i++){
							if(DG.getCellMeta(0,i).prop == prop){
								a_i = i;	
							}else if(DG.getCellMeta(0,i).prop == prop + "_visible"){
								a_i_v = i;	
							}
							if(a_i !== false && a_i_v !== false){
								DG.manualColumnPositions.splice(a_i + ( newIndex < oldIndex ? 1 : 0), 0, DG.manualColumnPositions.splice(a_i_v, 1)[0]);
								break;
							}
						} 
					}else
						return;
				}
				DG.forceFullRender = true;
				DG.view.render()
				Handsontable.hooks.run(DG, 'persistentStateSave', 'manualColumnPositions', DG.manualColumnPositions);
			}
			
			if(page_col_w){
				var c_w = [];
				for(var i  = 0; i< DG.countCols() ; i++){
					var prop = DG.getCellMeta(0,i).prop;
					if(page_col_w[prop]){
						c_w.push(page_col_w[prop]);
					}else{
						if(prop.indexOf("_visible") > 0)
							c_w.push(20);
						else
							c_w.push(80);
					}
				}
			}
			
			DG.c_resize_monior_disable =  true;
			DG.updateSettings({colWidths: c_w});
			DG.c_resize_monior_disable = false;	
			
	  },beforeColumnSort: function (column, order){
		  
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
			DG = jQuery('#dg_wooc').data('handsontable');
			
		if(!DG || !prop)
			return;
		
		this.readOnly = false;
        		
	    var row_data = DG.getData()[row]; 
		if(!row_data)
			return;
		
		if(prop == "id"){
			this.readOnly = true;
			return;
		}
		
	    if(row_data.parent){
		    if(jQuery.inArray(prop, variations_fields) < 0){
				this.readOnly = true;
			}
		}
		
		try{
			if(prop.indexOf('pattribute_') == 0){
				var attgid = prop.substr(11);
				if(row_data.att_info[attgid]){
					if(row_data.parent && !this.readOnly){
						this.readOnly = !row_data.att_info[attgid].v;
					}else if(!this.readOnly){
						this.readOnly = row_data.att_info[attgid].v;
					}
				}else if(row_data.parent)
					this.readOnly = true;
			}
		}catch(ex){}
		
	  },afterSelection:function(r, c, r_end, c_end){
			var img = DG.getDataAtRowProp(r,'image');
			if(img){
				if(img.thumb){
					ProductPreviewBox.css("background-image","url(" + img.thumb + ")");	
				}else if(img.src){
					ProductPreviewBox.css("background-image","url(" + img.src + ")");	
				}else
					ProductPreviewBox.css("background-image","");					
			}else
				ProductPreviewBox.css("background-image","");	

			ProductPreviewBox.attr('row', r);
			
			ProductPreviewBox_title.text("#" + (DG.getDataAtRowProp(r,'sku') || "") + "" + (DG.getDataAtRowProp(r,'name') || ""));	
	 }
	});
	
	if(!DG)
		DG = jQuery('#dg_wooc').data('handsontable');
	
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

	if('<?php echo $product_category;?>') jQuery('.filter_option *[name="product_category"]').val("<?php if($product_category)echo implode(",",$product_category);?>".split(','));
	if('<?php echo $product_tag;?>') jQuery('.filter_option *[name="product_tag"]').val("<?php if($product_tag) echo implode(",",$product_tag);?>".split(','));
	if('<?php echo $product_shipingclass;?>') jQuery('.filter_option *[name="product_shipingclass"]').val("<?php if($product_shipingclass) echo implode(",",$product_shipingclass);?>".split(','));
	if('<?php echo $product_status;?>') jQuery('.filter_option *[name="product_status"]').val("<?php if($product_status) echo implode(",",$product_status);?>".split(','));
	
	
	<?php 
	foreach($attributes as $attr){
	  if(isset($filter_attributes[$attr->name])){
	?>
	jQuery('.filter_option *[name="pattribute_<?php echo $attr->id; ?>"]').val("<?php if($filter_attributes[$attr->name]) echo implode(",",$filter_attributes[$attr->name]);?>".split(','));
	<?php 
	  }	
	} ?>
	
		
	<?php 
	foreach($custom_fileds as $cf){
	  if($cf->type == "term"){
		  
	?>
	jQuery('.filter_option *[name="<?php echo $cf->name; ?>"]').val("<?php if(isset($filter_cf[$cf->name])) echo implode(",",$filter_cf[$cf->name]);?>".split(','));
	<?php 
	
	  }	
	} ?>
	
	

	jQuery('SELECT[name="product_category"]').chosen();
	jQuery('SELECT[name="product_status"]').chosen();
	jQuery('SELECT[name="product_tag"]').chosen();
	jQuery('SELECT[name="product_shipingclass"]').chosen();

	jQuery('SELECT.attribute-filter').chosen({
					create_option: true,
					create_option_text: 'value',
					persistent_create_option: true,
					skip_no_results: true
				});
				
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

function do_export_terms(){
	  var link = window.location.href + "&do_export_terms=1" ;
	  window.open(link, '_blank');
	  return false;
}

function do_export(){
    var link = window.location.href + "&do_export=1" ;
   
    var QUERY_DATA = {};
	QUERY_DATA.sortOrder            = DG.sortOrder ? "ASC" : "DESC";
	QUERY_DATA.sortColumn           = getSortProperty();
	
	QUERY_DATA.limit                = "9999999999";
	QUERY_DATA.page_no              = "1";
	
	QUERY_DATA.sku                  = jQuery('.filter_option *[name="sku"]').val();
	QUERY_DATA.product_name         = jQuery('.filter_option *[name="product_name"]').val();
	QUERY_DATA.product_shipingclass = jQuery('.filter_option *[name="product_shipingclass"]').val();
	QUERY_DATA.product_category     = jQuery('.filter_option *[name="product_category"]').val();
	QUERY_DATA.product_tag          = jQuery('.filter_option *[name="product_tag"]').val();
	QUERY_DATA.product_status       = jQuery('.filter_option *[name="product_status"]').val();
<?php foreach($attributes as $attr){ ?>
	QUERY_DATA.pattribute_<?php echo $attr->id;?> = jQuery('.filter_option *[name="pattribute_<?php echo $attr->id;?>"]').val();
<?php } ?>	
	
	<?php 
	  foreach($custom_fileds as $cf){ 
		  if($cf->type == "term"){
			?>
				QUERY_DATA.<?php echo $cf->name;?> = jQuery('.filter_option *[name="<?php echo $cf->name;?>"]').val();
			<?php 
		  }
	  } 
	?>	
	
	for(var key in QUERY_DATA){
		if(QUERY_DATA[key])
			link += ("&" + key + "=" + QUERY_DATA[key]);
	}
	
	window.open(link, '_blank');
	
	//window.location =  link;
    return false;
}
var import_panel = null;

function do_import_terms(){
	if(import_panel)
		import_panel.remove();
	import_panel = jQuery("<div class='import_form'><form method='POST' enctype='multipart/form-data'><span><?php echo __("Select .CSV file to update categories and attributes from.<br>",'productexcellikemanager'); ?></span><br/><label for='file'><?php echo __("File:",'productexcellikemanager'); ?></label><input type='file' name='file' id='file' /><br/><br/><button class='cmdImport' ><?php echo __("Import",'productexcellikemanager'); ?></button><button class='cancelImport'><?php echo __("Cancel",'productexcellikemanager'); ?></button></form><br/><p>*If you edit from MS Excel you must save using 'Save As', for 'Sava As Type' choose 'CSV Comma Delimited (*.csv)'. Otherwise MS Excel fill save in incorrect format!</p></div>"); 
    import_panel.appendTo(jQuery("BODY"));
	
	import_panel.find('.cancelImport').click(function(){
		import_panel.remove();
		return false;
	});
	
	import_panel.find('.cmdImport').click(function(){
		if(!jQuery("#file").val()){
		  alert('<?php echo __("Select file first!",'productexcellikemanager');?>');
		  return false;
		}
		
	    var frm = import_panel.find('FORM');
		var POST_DATA = {};
		POST_DATA.do_import_terms      = "1";
				
		for(var key in POST_DATA){
			if(POST_DATA[key])
				frm.append("<INPUT type='hidden' name='" + key + "' value='" + POST_DATA[key] + "' />");
		}
			
		frm.submit();
		return false;
	});
	
}

function do_import(){
    if(import_panel)
		import_panel.remove();
	
	import_panel = jQuery("<div class='import_form'><form method='POST' enctype='multipart/form-data'><span><?php echo __("Select .CSV file to update prices/stock from.<br>(To void price, stock or any available field update remove coresponding column from CSV file)",'productexcellikemanager'); ?></span><br/><label for='file'><?php echo __("File:",'productexcellikemanager'); ?></label><input type='file' name='file' id='file' /><br/><br/><button class='cmdImport' ><?php echo __("Import",'productexcellikemanager'); ?></button><button class='cancelImport'><?php echo __("Cancel",'productexcellikemanager'); ?></button></form><br/><p>*When adding product via CSV import make sure 'id' is empty</p><p>*If you edit from MS Excel you must save using 'Save As', for 'Sava As Type' choose 'CSV Comma Delimited (*.csv)'. Otherwise MS Excel fill save in incorrect format!</p></div>"); 
    import_panel.appendTo(jQuery("BODY"));
	
	import_panel.find('.cancelImport').click(function(){
		import_panel.remove();
		return false;
	});
	
	import_panel.find('.cmdImport').click(function(){
		if(!jQuery("#file").val()){
		  alert('<?php echo __("Select file first!",'productexcellikemanager');?>');
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
		POST_DATA.product_shipingclass = jQuery('.filter_option *[name="product_shipingclass"]').val();
		POST_DATA.product_category     = jQuery('.filter_option *[name="product_category"]').val();
		POST_DATA.product_tag          = jQuery('.filter_option *[name="product_tag"]').val();
		POST_DATA.product_status       = jQuery('.filter_option *[name="product_status"]').val();
<?php foreach($attributes as $attr){ ?>
		POST_DATA.pattribute_<?php echo $attr->id;?> = jQuery('.filter_option *[name="pattribute_<?php echo $attr->id;?>"]').val();
<?php } ?>		
<?php foreach($custom_fileds as $cf){ 
	if($cf->type == "term"){
?>
	POST_DATA.<?php echo $cf->name;?> = jQuery('.filter_option *[name="<?php echo $cf->name;?>"]').val();
<?php 
	}
} ?>		
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

	if( $use_image_picker || $use_content_editior || fn_show_filed('image')  || fn_show_filed('gallery')){
	   wp_print_styles( 'media-views' );
	   wp_print_styles( 'imgareaselect' );
	   wp_print_media_templates();
	}

?>
<div style="display:none">

 <div id="variant_dialog">
   <h4 id="variant_dialog_title"><h4> 
   <h3><?php echo __("Configure Variations:",'productexcellikemanager');?></h3>
   
   <?php
	foreach($attributes as $att){
   ?>	
   <div class="att-row">
     <label><?php echo __($att->label);?></label>
	 <input type="checkbox" att_id="<?php echo $att->id; ?>" name="<?php echo $att->name; ?>" value="1" />
	<!-- 
	<select data-placeholder="<?php echo __("Chose values...",'productexcellikemanager'); ?>" class="inputbox " name="<?php echo $att->name; ?>" >
		<option value=""><?php echo __("Do not variate",'productexcellikemanager'); ?></option>
		<option value="(#)"><?php echo __("Set later in spreadsheet",'productexcellikemanager'); ?></option>
		<?php
		    foreach($att->values as $val){
			   echo '<option value="'.$val->slug.'" >'.$val->name.'</option>';
			}
		?>
	</select>
	--> 
    </div>	
    <?php 	
	}
  ?>
  <hr/>
  <br/>
  <h3><?php echo __("Create variations:",'productexcellikemanager');?></h3>
  <div class="att-row">
   <p><?php echo __("Create",'productexcellikemanager'); ?> <input style="width:35px;text-align:center;" id="createVarCount" value="0" /> <?php echo __("new variations",'productexcellikemanager'); ?></p>  
  </div>
 </div>
</div>
<script type="text/javascript">
var attributes = <?php echo json_encode($attributes); ?>;
var variant_dialog = null;

jQuery(document).ready(function(){
	jQuery("#variant_dialog").dialog({
		autoOpen: false,
		modal: true,
		maxWidth: '90%',
		width:280,
		title: "<?php echo __("Product Variant Config",'productexcellikemanager');?>",
		buttons: {
			"<?php echo __("Apply",'productexcellikemanager');?>": function() {
			  
			  var task = {};
			  task.dg_index = jQuery("#variant_dialog").attr("ref_dg_index");
			  task.variate_count = jQuery("#createVarCount").val();
			  task.variate_by    = {};
			  var ac = 0;
			  jQuery("#variant_dialog INPUT[type='checkbox']").each(function(i){
					task.variate_by[jQuery(this).attr("name")] = jQuery(this).prop("checked");
					if(jQuery(this).prop("checked"))
						ac++;
			  });
			  
			  if(ac == 0 && task.variate_count > 0){
				  alert("<?php echo __("Select at lest one attribute to create variations on!",'productexcellikemanager');?>");
				  return false;
			  }
			  
			  tasks[variant_dialog.attr("ref_id")] = task;
			  var DIALOG = jQuery( this ); 
			  doSave(
				  function(data){
					
					DIALOG.dialog( "close" );
				  }
				  ,function(){
					delete tasks[variant_dialog.attr("ref_id")];  
					alert("<?php echo __("Could not create variations",'productexcellikemanager');?>");
					DIALOG.dialog( "close" );
				  }
			  );	
			 
			},
			'<?php echo __("Cancel",'productexcellikemanager');?>': function() {
			  jQuery( this ).dialog( "close" );
			}
        }
	}).on( "dialogopen", function( event, ui ) {
		
		jQuery("#variant_dialog SELECT:not(.chos-done)").addClass("chos-done").chosen({
					create_option: true,
					create_option_text: 'value',
					persistent_create_option: true,
					skip_no_results: true
				});
				
	});
	
	variant_dialog = jQuery("#variant_dialog");
});

/*
jQuery(document).on("change","#variant_dialog SELECT",function(){
	var showCreateCount = false;
	jQuery("#variant_dialog SELECT").each(function(i){
		if(jQuery(this).val() == "(#)"){
			showCreateCount = true;
			return false;//break
		}
	});
	if(showCreateCount)
		jQuery("#createVarCount").css('color','black').removeAttr("readonly");
	else
		jQuery("#createVarCount").css('color','silver').val(1).attr("readonly","readonly");
	
});
*/
/*
jQuery(document).on("click","#createVarCount",function(e){
	if(jQuery("#createVarCount").attr("readonly")){
		alert("<?php echo __("To create multiple variations at once set at least on attribute to value : 'Set later in spreadsheet'",'productexcellikemanager'); ?>");
	}
})
*/

jQuery(document).on("click","a.add-var",function(e){
	e.preventDefault();
	var id = DG.getDataAtRowProp(DG.getSelected()[0],'id');
	
	jQuery("#variant_dialog INPUT[type='checkbox']").prop('checked',false);
	var att_info = DG.getDataAtRowProp(DG.getSelected()[0],'att_info');
	
	for(var aid in att_info){
		try{
			if (att_info.hasOwnProperty(aid)) {
				if(att_info[aid].v)
					jQuery("#variant_dialog INPUT[type='checkbox'][att_id='" + aid + "']").prop('checked',true);
			}
		}catch(e){
			//	
		}
	}
	jQuery("#createVarCount").val(0);
	jQuery("#variant_dialog").attr("ref_dg_index",DG.getSelected()[0]).attr("ref_id",id).dialog('open');
	jQuery("#variant_dialog_title").html('(' + id + ') ' + DG.getDataAtRowProp(DG.getSelected()[0],'name'));
	
});

</script>

<?php
  if($res_limit_interupted > 0){
	  ?>
<script type="text/javascript">
	jQuery(window).load(function(){
		alert("WARNING!\nProduct output interrupted after <?php echo $res_limit_interupted; ?> due memory and execution time limits!\nYou should decrease product per page setting.");	
	});
</script>	
	  <?php
  }	
  
  global $cleaned_attachment_count;
  
  if($cleaned_attachment_count !== null){
	  	  ?>
<script type="text/javascript">
	jQuery(window).load(function(){
		alert("<?php echo $cleaned_attachment_count; ?> image attachments removed!");	
	});
</script>	
	  <?php
  }

  
?>

</body>
</html>
<?php

exit;
?>
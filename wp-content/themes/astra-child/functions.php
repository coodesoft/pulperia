<?php
/**
 * Astra Child Theme functions and definitions
 *
 * @link https://developer.wordpress.org/themes/basics/theme-functions/
 *
 * @package Astra Child
 * @since 1.0.0
 */

/**
 * Define Constants
 */
define( 'CHILD_THEME_ASTRA_CHILD_VERSION', '1.0.0' );

/**
 * Enqueue styles
 */
function child_enqueue_styles() {

	wp_enqueue_style( 'astra-child-theme-css', get_stylesheet_directory_uri() . '/style.css', array('astra-theme-css'), CHILD_THEME_ASTRA_CHILD_VERSION, 'all' );

}

add_action( 'wp_enqueue_scripts', 'child_enqueue_styles', 15 );

# Limpia automáticamente la caché de Autoptimize si va por encima de 1024MB
if (class_exists('autoptimizeCache')) {
    $myMaxSize = 10240; # Puedes cambiar este valor a uno más bajo como 100000 para 100MB si tienes poco espacio en el servidor
    $statArr=autoptimizeCache::stats();
    $cacheSize=round($statArr[1]/1024);

    if ($cacheSize>$myMaxSize){
       autoptimizeCache::clearall();
       header("Refresh:0"); # Recarga la página para que autoptimize pueda crear nuevos archivos de caché.
    }
}

// aumentar el límite de tiempo de las peticiones WP SMUSH
add_filter('WP_SMUSH_API_TIMEOUT',  'wpmu_api_timeout');
function wpmu_api_timeout() {
 return 300;
}

// Habilitar la subida de imágenes en formato SVG en WordPress
add_filter( 'upload_mimes', 'jc_custom_upload_mimes' );
function jc_custom_upload_mimes( $existing_mimes = array() ) {
	$existing_mimes['svg'] = 'image/svg+xml';
	return $existing_mimes;
}

// Aplazar JavaScripts (Defer Parsing Javascript)
// Aplaza la carga de jQuery usando la propiedad HTML5 defer
if (!(is_admin() )) {
    function defer_parsing_of_js ( $url ) {
        if ( FALSE === strpos( $url, '.js' ) ) return $url;
        if ( strpos( $url, 'jquery.js' ) ) return $url;
        // return "$url' defer ";
        return "$url' defer onload='";
    }
    add_filter( 'clean_url', 'defer_parsing_of_js', 11, 1 );
}

//Esconde la información de la versión de WordPress
Remove_action('wp_head', 'wp_generator');

/** Actualizacion Automatica*/
add_filter( 'auto_update_core', '__return_true' );
add_filter( 'auto_update_plugin', '__return_true' );
add_filter( 'auto_update_theme', '__return_true' );
add_filter( 'auto_update_translation', '__return_true' );

//Deshabilitar xmlrpc
add_filter('xmlrpc_enabled', '__return_false');

// mensaje personal footer escritorio wordpress
function change_admin_footer() {
echo '<span id="footer-note">Por consultas o soporte técnico comunicarse con <a href="http://www.desarrollowordpress.com.ar" target="_blank">Gustavo Gabriel Coirini</a> al <a href="tel:2494622392">0249 154 622392</a>, o por email a <a href="mailto:ggcoirini@yahoo.com.ar">ggcoirini@yahoo.com.ar</a>. <a href="https://api.whatsapp.com/send?phone=5492494622392" target="_blank">Mensaje por WhatsApp al +54 9 2494 622392</a>.</span>';
}
add_filter( 'admin_footer_text', 'change_admin_footer' );


// cabeceras de seguridad X-Content-Type-Options, X-Frame-Options y X-XSS-Protection:
add_action( 'send_headers', 'add_header_seguridad' );
function add_header_seguridad() {
header( 'X-Content-Type-Options: nosniff' );
header( 'X-Frame-Options: SAMEORIGIN' );
header( 'X-XSS-Protection: 1;mode=block' );
}

// Remove Query Strings From Static Resources (va al final de todo)
function _remove_script_version( $src ){
$parts = explode( '?', $src );
return $parts[0];
}
add_filter( 'script_loader_src', '_remove_script_version', 15, 1 );
add_filter( 'style_loader_src', '_remove_script_version', 15, 1 );


@ini_set( 'upload_max_size' , '10000M' );
@ini_set( 'post_max_size', '10000M');
@ini_set( 'max_execution_time', '5000' );

/*--------------------SHORTCODES-----------------------*/
function astra_child_productor_link_short_code($attr){
	$user_data = get_userdata($attr['id']);
	return '<h2> Productor: <a href="/parroquianos/'.$user_data->user_login.'">'.$user_data->first_name.' '.$user_data->last_name.'</a></h2>';
}
add_shortcode('astra_child_productor_link', 'astra_child_productor_link_short_code');

function astra_child_posts_list_1_short_code($attr){
	$html   = '<div style="text-align: justify;">';
	$categs = get_categories(
		[
			'parent'   => get_categories(['name' => $attr['child_of']])[0]->cat_ID,
			'orderby'  => 'name',
			'order'    => 'ASC',
	  ]);

	foreach ( $categs as $categ ) {
		$html .= '<span><a class="tag-cloud-link tag-link-2408 tag-link-position-1" href="'.esc_url( get_category_link( $categ->term_id ) ).'">'.$categ->name.',</a></span>';
	}
	$html .= '</div>';

	return $html;
}
add_shortcode('astra_child_posts_list_1', 'astra_child_posts_list_1_short_code');
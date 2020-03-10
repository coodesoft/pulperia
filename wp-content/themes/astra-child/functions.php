<?php
/**
 * Astra Child Theme functions and definitions
 *
 * @link https://developer.wordpress.org/themes/basics/theme-functions/
 *
 * @package Astra Child
 * @since 2.2.1
 */

/**
 * Define Constants
 */
define( 'CHILD_THEME_ASTRA_CHILD_VERSION', '2.2.1' );

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
echo '<span id="footer-note">Por consultas o soporte técnico comunicarse con <a href="https://www.desarrollowordpress.com.ar" target="_blank">Gustavo Gabriel Coirini</a> al <a href="tel:2494622392">0249 154 622392</a>, o por email a <a href="mailto:ggcoirini@yahoo.com.ar">ggcoirini@yahoo.com.ar</a>. <a href="https://api.whatsapp.com/send?phone=5492494622392" target="_blank">Mensaje por WhatsApp al +54 9 2494 622392</a>.</span>';
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
	$font_zMin = 15; if (isset($attr['font_size_min'])) { $font_zMin = $attr['font_size_min']; }
	$font_zMax = 25; if (isset($attr['font_size_max'])) { $font_zMax = $attr['font_size_max']; }

	$html       = '<div class="astra-cloud">';

	$parent_cat    = get_categories([ 'name'    => $attr['child_of']])[0]->cat_ID;
	$categs        = get_categories([	'parent'  => $parent_cat, 'orderby'  => 'count',  'order'   => 'ASC' ]);
	$cats_by_count = get_categories([ 'parent'  => $parent_cat,	'orderby'  => 'count', 'order'    => 'ASC' ]);

	$cats_max_count = $cats_by_count[count($cats_by_count)]->count;
	$cats_min_count = $cats_by_count[0]->count;

	foreach ( $categs as $categ ) {
		$font_size = $categ->count * ( ($font_zMax - $font_zMin) / ($cats_max_count - $cats_min_count) ) + $font_zMin;
		$html .= '<div class="astra-cluod-tag" style="font-size:'.$font_size.'px;"><a class="tag-cloud-link tag-link-2408 tag-link-position-1" href="'.esc_url( get_category_link( $categ->term_id ) ).'">'.$categ->name.'</a></div>';
	}
	$html .= '</div>';

	return $html;
}
add_shortcode('astra_child_posts_list_1', 'astra_child_posts_list_1_short_code');

function astra_phpinfo_short_code($attr){
	return phpinfo();
}
add_shortcode('astra_phpinfo_short_code', 'astra_phpinfo_short_code');

function astra_child_postimage_carousel($attr){
	$args = [
    'post_type'   => 'attachment',
    'numberposts' => null,
    'post_status' => null,
    'post_parent' => $attr['id']
  ];

	$imagenes_post = get_posts($args);
	$imagenes      = get_field('portfolio_gallery');
	$carousel_name = 'images';
	$salida        = '';
	$c             = 0;
	$class         = '';

	if ($imagenes || $imagenes_post) {
		$salida .= '<script src="https://maxcdn.bootstrapcdn.com/bootstrap/4.4.1/js/bootstrap.min.js"></script>';
		$salida .= '<div id="'.$carousel_name.'" class="carousel slide" data-ride="carousel">';

		$salida .= '<ul class="carousel-indicators">';
		if ($imagenes){
			foreach ($imagenes as $imagen) {
				if ($c == 0) { $class = 'active'; } else { $class = ''; }
				$salida .= '<li data-target="#'.$carousel_name.'" data-slide-to="'.$c.'" class="'.$class.'"></li>';
				$c ++;
			}
		}

		if($imagenes_post && !$imagenes){
			foreach ($imagenes_post as $imagen) {
				if ($c == 0) { $class = 'active'; } else { $class = ''; }
				$salida .= '<li data-target="#'.$carousel_name.'" data-slide-to="'.$c.'" class="'.$class.'"></li>';
				$c ++;
			}
		}
		$salida .= '</ul>';

		$salida .= '<div class="carousel-inner">';
		$c = 0;
		if ($imagenes){
			foreach ($imagenes as $imagen) {
		     if ($c == 0) { $class = 'active'; } else { $class = ''; }
				 $html = '';
				 if ($imagen['foto_autor']    != '') { $html .= 'Autor: '.$imagen['foto_autor']; }
				 if ($imagen['foto_lugar']    != '') { $html .= ' · Lugar: '.$imagen['foto_lugar']; }
				 if ($imagen['foto_fecha']    != '') { $html .= ' · Fecha: '.$imagen['foto_fecha']; }
				 if ($imagen['foto_licencia'] != '') { $html .= ' · Licencia: '.$imagen['foto_licencia']; }

				 $salida .= '<div class="carousel-item '.$class.'">
											 <img src="'.$imagen['foto_archivo']['url'].'" alt="'.$imagen['foto_titulo'].'">
											 <div class="carousel-caption">	<p class="caption-title">'.$imagen['foto_titulo'].'</p>	<p class="caption-content">'.$html.'</p></div>
										 </div>';
				 $c ++;
		  }
		}

		if($imagenes_post && !$imagenes){
			foreach ($imagenes_post as $imagen) {
		     if ($c == 0) { $class = 'active'; } else { $class = ''; }

				 $img_url = wp_get_attachment_image_src($imagen->ID, 'full')[0];

				 $salida .= '<div class="carousel-item '.$class.'">
										 <img src="'.$img_url.'" alt="">
							 </div>';
				 $c ++;
		  }
		}
		$salida .= '</div>';

		$salida .= '  <a class="carousel-control-prev" href="#'.$carousel_name.'" data-slide="prev">
		    <span class="carousel-control-prev-icon"></span>
		  </a>
		  <a class="carousel-control-next" href="#'.$carousel_name.'" data-slide="next">
		    <span class="carousel-control-next-icon"></span>
		  </a>';

		return $salida;
	}

}
add_shortcode('astra_child_postimage_carousel', 'astra_child_postimage_carousel');


//Buddyppress
add_action( 'bp_before_member_activity_post_form', 'bp_before_member_activity_post_form_f' );
function bp_before_member_activity_post_form_f(){
	$productor = bp_displayed_user_id();

	//se buscan los productos asociados al productor
	$productos = get_posts(array(
		'post_type'		=> 'product',
		'meta_key'		=> 'productor_id',
		'meta_value'	=> $productor
	));

	//se genera el listado de productos
	if (count($productos) > 0){
		$html = '<div class="col-12">
							<div class="row"><div class="col-12"><h3><b>Productos</b></h3></div></div>
							<div class="row">';

		for ($c=0; $c < count($productos); $c++){
			$enlace = get_post_permalink($productos[$c]->ID);
			$html .= '<div class="col-12 as-cont-prod">
									<div class="row"><div class="col-12"><h3><b><a href="'.$enlace.'">'.$productos[$c]->post_title.'</a></b></h3></div></div>
									<div class="row">
										<div class="col-12 col-sm-4 col-md-3 col-lg-2">
											<img class="img-responsive" src="'.get_the_post_thumbnail_url($productos[$c]->ID).'" />
										</div>
										<div class="col-12 col-sm-8 col-md-9 col-lg-10">
											<p>'.$productos[$c]->post_content.'</p>
										</div>
									</div>
								</div>';
		}

		$html .= '</div></div>';
	}

	echo $html;
}

/*------------------------------------------------------------------------------------------------------------
------------------------------------------- Pestañas de ayuda ------------------------------------------------
------------------------------------------------------------------------------------------------------------*/

add_action('admin_head','add_context_menu_help');
function add_context_menu_help(){
	$help_tabs = [
		[
			'id'      => 'ayuda_pulpera',
			'title'   => 'Ayuda Pulpera',
			'content' => '<img class="alignright size-thumbnail wp-image-12756" style="float: right;" src="https://pulperia.com.ar/wp-content/uploads/2014/12/pinguino-150x150.png" alt="pinguino" width="150" height="150" />',
		],
		[
			'id'      => 'mercado_productos',
			'title'   => 'Mercado/Productos',
			'content' => 'Los productos del mercado se pueden localizar en la pulpería gracias a sus <strong>categorías</strong> y <strong>atributos</strong>.

Las <a href="https://pulperia.com.ar/wp-admin/edit-tags.php?taxonomy=product_cat&amp;post_type=product">categorías</a> son el modo fundamental de seleccionar los productos. En la pulpería aparecen en la barra lateral en primer lugar. Se puede seleccionar más de una, pero no hace falta seleccionar categorías superiores. En cada producto se editan desde el siguiente cuadro:

<img class="aligncenter size-full wp-image-12743" src="https://pulperia.com.ar/wp-content/uploads/2014/12/seleccion_003.png" alt="Selección_003" width="298" height="308" />

Los <a href="https://pulperia.com.ar/wp-admin/edit.php?post_type=product&amp;page=product_attributes">atributos</a> se pueden utilizar para filtrar contenido en la barra lateral, pero también sirven para utilizarse en los <strong>productos variables</strong>. Para agregar atributos a un producto se debe proceder como se indica en la siguiente imagen:

<img class="size-full wp-image-12742" src="https://pulperia.com.ar/wp-content/uploads/2014/12/seleccion_002.png" alt="Agreagr atributos a un producto" width="875" height="346" />',
		],
		[
			'id'      => 'mercado_productos_variable',
			'title'   => 'Mercado/Pr/Variable',
			'content' => 'Se utiliza para los productos que se posee variaciones, por ejemplo: color, tamaño, sabor, etc.

La manera más simple de agregar variaciones a un producto es <strong>utilizando los atributos</strong> que existen en la pulpería. En este caso se procede como indica la siguiente imagen:

<img class="aligncenter size-full wp-image-12747" src="https://pulperia.com.ar/wp-content/uploads/2014/12/seleccion_004.png" alt="Selección_004" width="866" height="369" />

Si los<strong> atributos son específicos del producto</strong> debemos agregarlos de la siguiente manera:

<img class="aligncenter size-full wp-image-12749" src="https://pulperia.com.ar/wp-content/uploads/2014/12/seleccion_007.png" alt="Selección_007" width="864" height="373" />

Luego de haber indicado los atributos debemos agregar tantas variaciones como hagan falta indicando en cada una las modificaciones que se requieran (imagen, precio, etc.). Los pasos para hacer esto se hacen para cada variación de a cuerdo a lo que se indica en la siguiente imagen:

<img class="aligncenter size-full wp-image-12750" src="https://pulperia.com.ar/wp-content/uploads/2014/12/seleccion_008.png" alt="Selección_008" width="864" height="625" />',
		],
		[
			'id'      => 'conoce_articulos',
			'title'   => 'Conoce/Artículos',
			'content' => '<p style="text-align: right;">Podes consultar la <a href="https://docs.google.com/a/o-k.co/spreadsheet/ccc?key=0AnU8VFcBZBAmdDB2Q0tiLW1XWWtOaWJVcHozSjQxd0E&amp;usp=sharing" target="_blank" rel="noopener">
Planilla Pregoneron en este link</a></p>
AVISO GENERAL

LOS 10 MANDAMIENTOS DEL PULPERO
1. Respeto
2. Confiabilidad
3. Honestidad
4. Colaboración
5. Responsabilidad
6. Transparencia
7. Planificación
8. Precisión y rigor
9. Puntualidad y regularidad
10. Buena onda

¿QUE ESCRIBIMOS?
La sección CONOCE de pulperia.com.ar se dedica a temáticas culturales y gastronomicas Argentina.
El colador tiene las siguientes temáticas:
• Arquitectura
• Costumbre
• Receta
• Retrato
• Sustentabilidad
• Trago

LA VOZ DEL PREGONERO PULPERO
1. Es informativa, interesante, agradable y precisa.
2. Lleva el sello del espíritu pulpero: recuerden que no hacemos política, sino que fomentamos las relaciones sociales saludables, la revalorización del patrimonio cultural y del trabajo de los pequeños productores, artesanos y artistas locales, en un ámbito de intercambio y celebración de nuestras tradiciones argentinas.
3. Ofrece una mirada con un punto de vista propio y un trabajo de investigación de la temática abordada.
4. Incluye argumentos y ejemplos.
5. Es sencilla pero logra transmitir sensaciones y emociones.
6. Ilustra con imágenes de calidad el tema en cuestión (fotos que hayas tomado vos, fotos con Licencia Creative Commons, dibujos, pinturas o ilustraciones). Cita siempre las fuentes de las ilustraciones.
7. Incluye links de videos, música o cualquier posibilidad expresiva de la Web 2.0.
8. Transmite positividad (Ej: evitar comenzar las frases con “No”).
9. Invita sutilmente al lector a reflexionar a partir de alguna pregunta interesante o disparadora.
10. La escritura debe ser ortográfica y gramaticalmente correcta. No escribiré palabras malsonantes.
11. Crea un contenido original que implica buscar información especializada en fuentes confiables como libros o páginas especializadas. No acepta Wikipedia como fuente y no hace copy/paste de las fuentes de información.
12. Estas moléculas de expresiones lo inspiran: Lindo para estar, fuera del tiempo y del espacio, poner en paréntesis, sentirse en el campo, cagarse de risa, divertido pero no vulgar, emoción positiva y dinámica, reencuentro, esperanza, barcito con parroquianos, ambiente familiar, gozos del campo, solidaridad de la gente, curiosidad (no decir todo, generarla), nada de superficialidad, movilizar a la gente, orgullo argentino, sorpresa, estilo pulpero.

INSTRUCTIVO
1. Ingresar a www.pulperia.com.ar con USUARIO y CONTRASEÑA.
2. Luego dirigirse a MI CUENTA-ADMINISTRACIÓN.
3. Seleccionar un articulo en BORRADOR al nombre de "Pulpería Quilapán*" y ponerlo al nombre de tu USUARIO.
4. Si quieres proponer un tema puedes hacerlo escribiendo un correo a conoce@pulperiaquilapan.com
5. Cuando tu articulo esta terminado. Ponerlo en estado "PENDIENTE DE REVISION." Si hay algunas corrección para hacer ira en "PENDIENTE DE CORRECCION". El corrector hará las correcciones pertinentes y si está listo lo publicará, es decir, pasara de "PENDIENTE" a "PUBLICADO".

URL
1. No editar la url automático del artículo pagina.
2. En caso de cambiar el título, verificar que el url también coincida con el título.

TITULO
1. El título debe tener un máximo de 40 caracteres.
2. Puede ser informativo pero sobre todas las cosas debe actuar como disparador de una idea, de una intención, de una motivación para alentar la lectura. Se puede jugar con un refrán, una canción, este es el momento de innovar. Es el momento de inquietar al lector para que se quede en el artículo.
3. No abusar dentro de lo posible, de los dos puntos (":"), pueden reemplazarse por una coma. Ej: "El chamamé: canción del norte" pude modificarse por "Chamamé, canción del norte".

CUERPO DEL TEXTO
1. Debe tener un máximo de 1000 palabras.
2. En el siguiente campo (abajo), seleccionar la solapa TEXTO (no visual).
3. Seleccionar la opción PARRAFO para escribir en el campo principal el artículo pregonero. Seleccionar la opción TÍTULO 2 para elegir el tamaño H2 de títulos de
4. los párrafos. No elegir negrita para los títulos de los párrafos.
5. Intentar que el espaciado sea el correcto para que los párrafos no queden demasiado juntos o demasiado separados.
6. La estructura consejada del texto es la siguiente: Título principal / Introducción / Subtítulo 1 / Párrafo 1 (Estas palabras son muy bienvenidas a lo largo del texto: don, doña, parroquianos, paisanos, pulpería) / Subtítulo 2 / Párrafo 2 / Subtítulo 3 / Párrafo 3
7. Conclusión. La conclusión-párrafo puede no tener subtítulo. Es necesario que no redunde ni sea un repetitiva de lo antes dicho en el texto. Puede también abrir hacia otro tema futuro, plantear una pregunta. Es igual de importante que el inicio del texto, es la imagen final que el lector se lleva de lo escrito.
8. Si su apunte merece una otra estructura de texto, tiene la posibilidad de intentar una forma de presentacion mas libre. Por ejemplo: si el artículo es un retrato de una personalidad, se puede omitir la introducción y arrancar directo al estilo res media, es decir, con una anecdota, con una acción de entrada: "Charly prendió un cigarrillo y habló al periodista le dijo..."

CONTENIDO
1. Si tenemos que escribir números, del uno al diez lo escribiremos con letras ("tiene cuatro hijos, ocho nietos") y en adelante con cifras: "11", "47", "150", etc. Excepto que digamos "5 millones", "13 toneladas" "cuesta $130". Es decir, todo lo que sea grandes cantidades o dinero .
2. Utilizamos el símbolo "%" en lugar de escribir “por ciento”.
3. Las negritas no debieran automáticamente resaltar conceptos, nombres de lugares o personajes al azar. Deben acompañar a la lectura, pueden utilizarse para destacar una idea que sea fuerte, provocadora u innovadora. Ej: si la nota de un artista resalta dònde nació, el año y otros datos biográficos, al ser un retrato es más beneficioso destacar en cambio una frase fuerte como "consideraba que los medios eran un obstáculo en su carrera". Ese recurso permite que el que lee se intrigue por leer el párrafo, el texto. Intentar dentro de lo posible no resaltar más de una frase por párrafo.
4. Las palabras que son de idioma extranjero se escribirán en ITÁLICA o CURSIVA. Esto vale para el artículo como los anexos "Qué se yo" y "No me digas!" los títulos de libros, películas, obras también deben ir en ITÁLICA o CURSIVA.

COPETE
1. Debe tener como máximo 140 palabras (Esto es MUY IMPORTANTE, si se exceden las 140 palabras el link de la web dará error al ingresar).
2. Debe ser insólito, disparador, suscitar el deseo de lectura.

NO ME DIGAS!
1. Este apartado debe incluir datos curiosos e interesantes sobre el tema en cuestión. Puede ser una fecha insólita, un dato cuantitativo inédito sobre lo que se escribe, estrofas de poemas/canciones sobre el tema, una cita muy breve de un autor hablando del tema. Habrá que ser creativo para que sume un plus a lo ya escrito, algo que sea insólito.
2. Separalos en párrafos breves.

QUÉ SE YO
1. Este apartado incluye parte de la bibliografía citada o link de la publicación o Web especializada que se utilizó cuando se escribió el artículo. Puede tener como máximo 1 link por artículo. El o los websites no pueden ser sitios con fines comerciales. La manera en la que se cita la bibliografía utilizada es la siguiente: Xul Solar, un músico visual (NOTA: este título del libro/artículo va en cursiva, el resto no), Cintia Cristia, Gourmet Musical, España, 2011.
2. El o los website incluidos deben agregarse de la siguiente manera: Se escribe la url del website. No podemos de hyperlink.

AUDIO
1. Para agregar un audio se debe ir al apartado AUDIO y desplegar la solapa FUENTE y seleccionar la fuente correspondiente (MP3 si uno lo carga desde la computadora o también se puede ingresar el link si el sonido está subido al SOUNDCLOUD).
2. Se le debe agregar un TÍTULO al audio que incluya nombre del autor, la canción, fecha, lugar (todos los datos de los que dispongamos y sean relevantes).

VIDEO
1. Seleccionar fuente (en youtube o vimeo) y agregar el ID del video.
2. También se le debe agregar TÍTULO (ídem instrucciones AUDIO).

FOTO HOME
1. 1024 x 1024, guardada en formato JPEG
2. La imagen no puede tener un fondo blanco ya que el fondo de la web también es blanco.
3. Las imágenes deben tener origen argentino.

GALERIA DE IMAGENES
1. Los títulos y las descripciones de la fotos de la galería debe ser informativos, sólo la primera letra debe estar en mayúscula, no debe cerrarse con puntos ni guiones.
2. La primera foto debe ser la misma que la foto de la Home.
3. Esas imagenes deben tener un ratio de 1.45 (1024/2048). Cuando descarguen la imagen de internet, es conveniente guardar la imagen en la PC con el nombre relevante, si es sobre Borges ponerle "Borges" así cuando la web la carga en su artículo tenemos además la galería para poder rastrearla.
4. Se pueden cargar entre 3 y 5 imágenes apaisadas. Debes poner en los campos: Titulo, Descripcion, Autor ("Autor desconocido" cuando no lo conoces, pero lo ideal siempre es identificar al), Fecha (01/01/2014), Licencia.
5. Para conocer más sobre el citado de las imágenes oficiales y el cambio de formato y tamaño, les recomendamos visitar la web del Ministerio de Economía en su apartado "Centro de Documentación e Información (CDI)" https://cdi.mecon.gov.ar/cediap/servicios-y-asistencia-tecnica/solicitud-de-documentacion/ y https://cdi.mecon.gov.ar/

FILTROS
Esta opción permite facilitar la búsqueda de los usuarios que ingresen a la Web. Aparece ubicado en el Menú a la derecha:
Arquitectura / Costumbre / Receta / Retrato / Sustentabilidad / Trago

ETIQUETAS
a desarollar
provincias

SEO
1. Configura la información para que sea más fácil encontrar el artículo en los buscadores Web y las redes sociales. Este contenido especial ayuda a posicionar el sitio de forma privilegiada. Pero también sirve para elegir qué se muestra y cómo se muestra.
2. Palabra clave. Elija la frase o palabra clave, sobre la que trata esta publicación/página. (3 palabras, ej: cine, conventillo, Gardel)
3. Título SEO: No completarlo, se rellena automáticamente.
4. Meta descripcion. Es lo que aparece debajo del título en los resultados de los buscadores. Este campo es muy importante porque además de ser un resumen del artículo, cumple la función de motivar y/o captar la atención del usuario, para que tenga ganas de hacer clic y ver de qué se trata.
5. Recordar que es un texto que motiva a hacer clic!',
		],
		[
			'id'      => 'destacados_sliders',
			'title'   => 'Destacados & Sliders',
			'content' => '<img class="alignnone size-large wp-image-22962" src="https://pulperia.staging.wpengine.com/wp-content/uploads/2016/05/Destacado-1024x606.png" alt="Destacado" width="1024" height="606" />

Los destacados de la pulpería son imágenes que apareen en el encabezado algunas páginas de la web. Sirven para destacar contenido que de otro modo sería laborioso encontrar. <strong>Se componen de tres elementos</strong>:
- Imagen (obligatoria)
- Texto (opcional)
- Botón (opcional)

La imagen conforma el fondo, mientras que el texto, logo y botones conforman el primer plano del encabezado. Para resaltar esta composición todas las imágenes deben tener un <strong>efecto de viñeteado</strong>.
',
		],
		[
			'id'      => 'pedidos',
			'title'   => 'Pedidos',
			'content' => 'Con Payu el sistema de pagos funciona muy bien y el modo de utilización de los estados de los pedidos es el siguiente:
<h2>Pago exitoso</h2>
Cuando el pago se ha generado y el <strong>dinero ya está en la cuenta de PayU </strong>el producto tiene el estado "Proccesing". Esto significa que el pulpero lo está preparando para su posterior envío:
<img src="https://i.imgur.com/6ESyjTq.png" alt="" />
<h3>Completado</h3>
Cuando el pulpero lo entrega a oca debe poner el pedido como "Completado" haciendo click en el siguiente botón: <img src="https://i.imgur.com/WLEjdWh.png" alt="" />
<h2>Pago Pendiente</h2>
Si se realizó un pedido pero<strong> el dinero no ingresó </strong>porque se eligió un medio de pago en efectivo el sistema pone al pedido como "Pending Payment". Esto significa que el stock de productos se reservó en la web por (actualmente 3 días) pero el dinero no está acreditado en la cuenta de PayU.
Ejemplo: <img src="https://i.imgur.com/G798efE.png" alt="" />
<h2>Error en el pago</h2>',
		],
		[
			'id'      => 'redes_sociales',
			'title'   => 'Redes Sociales',
			'content' => 'Las publicaciones en las redes sociales pueden hacerse:
1. <strong>compartiendo el link y subiendo una foto o flyer</strong>: en este caso para la mayoría de las redes sociales las imágenes cuadradas son las que funcionan mejor.
2. <strong>compartiendo solamente el link</strong>: Para la mayoría de las redes sociales las imágenes apiadadas con una proporción 2 a 1 son las que funcionan mejor. La imagen que muestre la red social será la que hayamos asignado desde la siguiente configuración, disponible en cada artículo, página y evento (se puede asignar una imagen y texto diferente para cada red social):<img class="alignnone size-full wp-image-22964" src="https://pulperia.staging.wpengine.com/wp-content/uploads/2016/05/redes.png" alt="redes" width="900" height="413" />',
		],
		[
			'id'      => 'precios_planilla_calc',
			'title'   => 'Actualización de precios desde planilla de Google Calc',
			'content' => '<h2>EXPORTAR PRECIOS (desde la web)
Y LEERLOS EN UNA PLANILLA DE GCALC con formato de precio correcto:</h2>
<div>- Se exporta desde "Export CSV"</div>
<div><img class="alignnone size-full wp-image-24529" src="https://pulperia.staging.wpengine.com/wp-content/uploads/2016/10/Captura-de-pantalla-2016-10-24-a-las-11.55.44.png" alt="captura-de-pantalla-2016-10-24-a-las-11-55-44" width="305" height="206" /></div>
<div>y luego se importa en <a href="https://docs.google.com/a/o-k.co/spreadsheets/d/1vvD3PmngMkjssuEgJcjFBZkiAH63uPysASmXm7Ypcu8/edit?usp=sharing">esta planilla </a>(se reemplaza la hoja o crea una hoja nueva) (*1)</div>
<div><img class="alignnone size-full wp-image-24526" src="https://pulperia.staging.wpengine.com/wp-content/uploads/2016/10/fdKtLug.png" alt="fdktlug" width="782" height="262" /></div>
<div>- Luego se puede copiar y pegar en cualquier otra planilla y  los datos se pegarán de forma adecuada, por ejemplo en la <a href="https://docs.google.com/spreadsheets/d/1XkjQWYlxOgOX1C9lv_Sr0gMkf4aS5s9uz450Gx7DY5U/edit#gid=1867020042">planilla de cálculo de precios "Receta"</a>.</div>
<h2>IMPORTAR PRECIOS (en la web)
DESDE UNA PLANILLA DE GCALC:</h2>
<div>La planilla de Gcalc debe tener 4 columnas tal cual se puede ver en <a href="https://docs.google.com/spreadsheets/d/1XkjQWYlxOgOX1C9lv_Sr0gMkf4aS5s9uz450Gx7DY5U/edit#gid=1867020042">aquí</a>:</div>
<div><img class="alignnone size-full wp-image-24527" src="https://pulperia.staging.wpengine.com/wp-content/uploads/2016/10/zJgiInX.png" alt="zjgiinx" width="806" height="236" /></div>
<div>Se guarda la hoja actual con formato CSV:</div>
<div><img class="alignnone size-full wp-image-24528" src="https://pulperia.staging.wpengine.com/wp-content/uploads/2016/10/kZ0t37v.png" alt="kz0t37v" width="714" height="531" /></div>
<div>Se importa en la web desde "Updata from CSV"</div>
<div><img class="alignnone size-full wp-image-24530" src="https://pulperia.staging.wpengine.com/wp-content/uploads/2016/10/GEevaSo.png" alt="geevaso" width="430" height="199" /></div>
<h2>AGREGAR NUEVOS PRODUCTOS:</h2>
<div>Se puede hacer con el botón "Clone" desde el sistema ó creando nuevos productos en la lista de precios:</div>
<div>Se coloca el nombre y precio de cada producto atendiendo a:</div>
<div>- no colocar nada en el campo "ID"</div>
<div>- que los nombres no sean iguales a un producto ya presente en la pulpería</div>
<div>ej:</div>
<div><img class="alignnone size-full wp-image-24531" src="https://pulperia.staging.wpengine.com/wp-content/uploads/2016/10/HWk6oFL.png" alt="hwk6ofl" width="786" height="228" /></div>
<div>

Luego se procede a importar los productos de igual modo que lo indicado en el punto "IMPORTAR PRECIOS (en la web) " más arriba. Los productos nuevos tendrán "ID" con valor mayor a las demás por lo cual serán fácilmente identificables al ordenar los productos por ID y luego se procederá a completar los campos faltantes(*2) desde la misma planilla de la web.

<img class="alignnone size-full wp-image-24532" src="https://pulperia.staging.wpengine.com/wp-content/uploads/2016/10/gFI1X8H.png" alt="gfi1x8h" width="1104" height="228" />

</div>
<div><b>----</b></div>
<div>(*1): Esto se debe a que la planilla indicada está configurada para leer el formato de precios de EEUU. Una vez leídos de forma correcta se pueden copiar y pegar en cualquier otra planilla con formato de Argentina.</div>
<div>(*2) la lista de ID de los productores se puede obtener de los <a href="https://pulperia.staging.wpengine.com/wp-admin/users.php?role=contributor">usuarios "profesionales"</a> o en la siguiente <a href="https://docs.google.com/a/o-k.co/spreadsheets/d/14sS0w3oPjO_G5OBRnNOiQaVngyARID175FHhj9m4C48/edit?usp=sharing">lista de Productores en GCalc</a>:</div>',
		],
	];

	$current_screen = get_current_screen();
	$current_screen->remove_help_tabs();

	for ($c=0; $c<count($help_tabs); $c++){
		$current_screen->add_help_tab([
			'id'      => $help_tabs[$c]['id'],
			'title'   => $help_tabs[$c]['title'],
			'content' => $help_tabs[$c]['content'],
		]);
	}
}


/*------------------------------------------------------------------------------------------------------------
------------------------------------------- WOOCOMMERCE ------------------------------------------------------
------------------------------------------------------------------------------------------------------------*/
add_filter( 'woocommerce_product_query_meta_query', 'shop_only_instock_products', 10, 2 );
function shop_only_instock_products( $meta_query, $query ) {
    // In frontend only
    if( is_admin() ) return $meta_query;

    $meta_query['relation'] = 'OR';

    $meta_query[] = array(
        'key'     => '_price',
        'value'   => '',
        'type'    => 'numeric',
        'compare' => '!='
    );
    $meta_query[] = array(
        'key'     => '_price',
        'value'   => 0,
        'type'    => 'numeric',
        'compare' => '!='
    );
    return $meta_query;
}

/*-------------------------------------------------  */
add_filter( 'excerpt_more', 'astra_post_link', 0);

<?php
/*
Plugin Name: MxiBook Wrapper
Plugin URI:
Description: Wrapper - Plugin para poder personalizar CSS de menú maxirest
Version: 1.0
Author:
Author URI:
License: GPL2
*/

function astra_mxibook_data_short_code($attrs){
	 $id = $attrs['id'];

	 $menu = curl_init('https://api-mscrm.maxisistemas.com.ar/api-menuweb/public/v1/menu/'.$id);
	 curl_setopt($menu, CURLOPT_RETURNTRANSFER, 1);
	 $menu = curl_exec($menu);
	 $data = json_decode($menu, true);

	 $html  = '<div id="datawebIframe" style="height:3000px; width:100%;">';
	 //$html .= '<div id="content"> <div id="wrapper"> <div id="page-content-wrapper"> <div class="container">
	  //         <div class="allContent row">';
	 $html .= '<div class="myContent col-sm-12">';
	 //se recorren los rubros
	 foreach($data as $v){
		 	if ($v['visible']){

				$html .= '<div id="'.strtolower($v['nombre']).'" class="col-xs-12 rubro">';
				$html .= '   <h3 style="color: rgb(61, 133, 198);">'.$v['nombre'].'</h3>';
				$html .= '</div>';

				//se recorren las subcategorias
				foreach($v['subrubros'] as $sv){
					if($sv['visible']){

						$html .= '   <div id="'.strtolower($sv['nombre']).'" class="col-xs-12 subrubro">
														<h4 style="color: rgb(162, 196, 201);">'.$sv['nombre'].'</h4>
												 </div>';

						//se recorren los sub artículos

						foreach ($sv['articulos'] as $productos) {
							if ($productos['visible']){
								$html .= '    <div class="col-md-12 col-lg-6">';
								$html .= '        <div class="col-xs-9 nombre"> <p>'.$productos['nombre'].'</p> </div>';
								$html .= '        <div class="col-xs-3 precio">  <p>'.$productos['precio'].'</p> </div>';
								$html .= '        <div class="col-xs-12 descripcion"> </div>';
								$html .= '    </div>';
							}
						}

					}
				}

			}
	 }

	 $html .= '</div>';
	 $html .= '</div>';
	// $html .= '</div> </div> </div> </div> </div>';

	 //se adjuntan los estilos
	 $url_plugin = plugin_dir_url(__FILE__);
	 $html      .= '<link rel="stylesheet" type="text/css" href="'.$url_plugin.'css/bootstrap-flatly.min.css'.'" media="screen" />';
	 $html      .= '<link rel="stylesheet" type="text/css" href="'.$url_plugin.'css/style.css'.'" media="screen" />';

	 return $html;
}
add_shortcode('maxibook_menu', 'astra_mxibook_data_short_code');

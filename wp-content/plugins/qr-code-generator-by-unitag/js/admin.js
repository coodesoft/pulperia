jQuery(document).ready(function($){
	$('.unitag-model-thumb').click(function () {
		var id_thumb = this.id;
		var id_template = id_thumb.replace('thumb_','');
		var id_preview = 'preview_'+id_template;

		if( $('#'+id_preview).is(':hidden') ) {
			$('.unitag-model-preview').fadeOut(300);
			$('#'+id_preview).delay(300).fadeIn(300);
			$('#template_id_input').val(id_template);
			$('#template_id_input').focus();
		}
	});
	$('#unitag-data-link').click(function () {
		$('#unitag-data-table').slideToggle(1000);
	});
});
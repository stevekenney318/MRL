jQuery(document).ready(function(){

	/*Script For Shortcode Button*/
	jQuery("#insertbutton_calculator").click(function(){

    var file_url              = jQuery("#file_url_calculator").val();
    var iframe_height         = jQuery("#iframe_height_calculator").val();
    var iframe_width          = jQuery("#iframe_width_calculator").val();
		var button_type           = jQuery("#btn_type").val();//Get the value of the button type
		var button_title          = jQuery("#btn_title").val();//Get the title of the button
		var button_link           = jQuery("#btn_link_calculator").val();
    console.log( button_link);
    console.log( file_url);
    var button_external_link  = jQuery("#btn_external_url").val();//Get
    var jquery_min            = jQuery("#jquery_min").val();
    var jquery_ui             = jQuery("#jquery_ui").val();
    var admin_js              = jQuery("#admin_js").val();

    if(button_link !="" && file_url !=""){
    window.send_to_editor("<table id='hasIframe' style='width:"+iframe_width+"px; height:"+iframe_height+"px'><tr><td style='border:0px; padding:0px;'><iframe  src=\"" + file_url + "\" width='100%' height='100%'></iframe></td></tr></table>");
    jQuery.fn.jqtiny=function(){
       if(jQuery(this).get(0).tagName==='TEXTAREA'){
              return jQuery(this).prev().find('iframe').contents();
         };
      }; 
       //to get HTML from iframe
    	jQuery("#btn_link_calculator").val("");
      jQuery("#btn_external_url").val('');
      jQuery("#file_url_calculator").val('');
      jQuery("#iframe_height_calculator").val('');
    	jQuery("#iframe_width_calculator").val('');
    }
    else{
    	alert('Input the required field');
    }
      

	});



var tts_reset = function(){

	jQuery("#btn_title").val("");
	jQuery("#btn_link").val("");
  jQuery("#btn_external_url").val('');
  jQuery("#file_url").val('');
  jQuery("#iframe_height").val('');
	jQuery("#iframe_width").val('');
	jQuery('#ttc_shortcode_output').val('');

}


/*Disable External Url when dropdown is changed*/
jQuery('#btn_link_calculator').change(function() {

	var button_link = jQuery("#btn_link_calculator option:selected").val();
	jQuery.ajax({
		type: "POST",
		url : SMC_OBJ.ajax_url,
		data: {
      action: 'ims_fme_ssc_Ajax_Link_Page',
      post_id: button_link,
      security: SMC_OBJ.ajax_nonce
   },
		cache: false,
		error: function () {

		},
		success: function(data){
			data = jQuery.parseJSON(data);
      console.log( data );
      jQuery('#file_url_calculator').val(jQuery.trim(data.html));
      jQuery('#iframe_width_calculator').val(data.width);
			jQuery('#iframe_height_calculator').val(data.height);
		}
	});

});

/*Clear the text area*/
jQuery(".files_media_link").click(function(){
	jQuery('.accordion_title').val('');
	jQuery('.shortcode_accordion_txtarea').val('');
	jQuery('#btn_new_window').prop("checked",false);
	jQuery('#chkbox_map_latitude_longitude').prop("checked",false);
	jQuery('#map_latitude').css("display",'none');
	jQuery('#map_longitude').css("display",'none');
	jQuery('#map_url').val('');
	jQuery('#map_width').val('');
	jQuery('#map_height').val('');
});

if( jQuery("#tts_generatebutton").length > 0 ) {

}


jQuery("#tts_generatebutton").click(function(){
    var file_url = jQuery("#file_url").val();
    var iframe_height = jQuery("#iframe_height").val();
    var iframe_width = jQuery("#iframe_width").val();
    var shortcode_text = "<iframe src=\"" + file_url + "\" width=\"" + iframe_width + "\" height=\"" + iframe_height + "\"  ></iframe>";

    if(button_text !="" && file_url !=""){
    	clip && clip.on( "load", function(client) {
  	} );

    	jQuery("#copy-button").show();
    	jQuery("#ttc_shortcode_output").show();

    	clip && clip.on( 'dataRequested', function (client, args) {
    		console.log('requested')
    		client.setText( shortcode_text );
    	});

    	clip.on( "complete", function(client, args) {
  	    jQuery("#copy-button").hide();
  	    alert("Shortcode copied to clipboard" );

  	} );


    	jQuery('#ttc_shortcode_output').val(shortcode_text);
    	jQuery("#btn_title").val("");
    	jQuery("#btn_link").val("");
    	jQuery("#btn_external_url").val('');
      jQuery("#file_url").val('');
      jQuery("#iframe_height").val('');
      jQuery("#iframe_width").val('');
    }
    else{
    	alert('Input the required field');
    }
 });


jQuery('#tts_resetbutton').click(function(e){
	tts_reset();
	e.preventDefault();
	return false;
})

jQuery('#wp_custom_attachment1').change(function(){
  
    //var filename = this.value.substring(12); //solved fakepath problem while uploading file
    var filename = this.value; //solved fakepath problem while uploading file
    var getName = filename.split("\\").pop();  
    var oldName = jQuery(this).attr('data-oldzip');
    var sameZipCount = jQuery(this).attr('data-samezip');
    
    if(sameZipCount != 0){
    if(oldName != '' && oldName != getName ) {
      jQuery("#successMessage.update").html('');    
      jQuery("#errorMessage.update").html('<div class="message">This calculator is already embedded so it can not be replaced by different package. </br> Please select the package with matching name or click Add New.</div>');
      jQuery('#publish').css({'cursor':'not-allowed','pointer-events':'none','background-color':'#fff','color':'#c0c0c0','border-color':'#d0d0d0' ,'text-shadow':'none', 'box-shadow':'none'});
      jQuery('#publish').attr('disable', 'disable');
      return false;
    }

  }
    var fileExtension = filename.split('.').pop(); //The .pop() method will return the last item
    if(fileExtension == 'zip'){
    jQuery('#uploadFile').val(filename);
    jQuery('#publish').attr("style","");
    jQuery('#publish').attr('disable', '');
    jQuery("#errorMessage.update").html('');
   
    jQuery("#successMessage").html('<div class="message">File has been uploaded. Please click to publish button.</div>');
    jQuery("#successMessage.update").html('<div class="message">File has been uploaded. Please click to update button.</div>');
    jQuery("#fileNameZip").html(getName);

    }else{
          alert("Please upload zip file");
          return false;
    }
});

});
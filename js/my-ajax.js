var flag;
jQuery(document).ready(function() {

	//Hide Ad
	var ad = ajax_postajax.id;
	ad = '#'+ad;
	//console.log(ad);
	jQuery(ad).addClass("ad_style");

	var data = {
		'action': 'get_rows'
	};

	// since 2.8 ajaxurl is always defined in the admin header and points to admin-ajax.php
	jQuery.post(ajax_postajax.ajaxurl, data, function(response) {
		var jsonArray = jQuery.parseJSON(response);
		//console.log(jsonArray); 
		var clk_count =  parseInt(jsonArray.clk_count);
		var img_id =   jsonArray.img_id;
		img_id = '#'+img_id;
		var count =   parseInt(jsonArray.count);

	 	if(clk_count < count)
	 	{	
	 		flag = 0;
	 	}
	 	else
	 	{
	 		flag = 1;
	 	}
	 	//console.log('flag: '+flag);
	 	if(flag == 0)
		{
			jQuery(img_id).click( function(){
				var data = {
					'action': 'update_row'
				};

			    jQuery.post(ajax_postajax.ajaxurl, data, function(response) {
		        	var jsonArray = jQuery.parseJSON(response);
		        	//console.log(jsonArray); 
					var clk_count =  parseInt(jsonArray.clk_count);
					var count =   parseInt(jsonArray.count);
				 	//console.log('count: '+clk_count); 	
				 	if(clk_count >= count)
				 	{
				 		jQuery(img_id).addClass("prevent_click");
				 		jQuery(img_id).addClass("disappear_me");
				 		jQuery(img_id).find("a").attr('title','');
				 		//console.log("prevent click"); 		
				 	}
			  	});
			});
		}
		if(flag == 1)
		{
			jQuery(img_id).addClass("prevent_click");
			jQuery(img_id).addClass("disappear_me");
			jQuery(img_id).find("a").attr('title','');
			//console.log("prevent click");
		}
	});
});
jQuery(document).ready(function(){
	jQuery('.restore_data').on('click',function(){
		var id = jQuery(this).attr("data-id");
		var data = {
            'action': 'dp_restore_data',
            'id': id
        };
        jQuery.post(deletedp.ajax_url + '/wp-admin/admin-ajax.php', data, function (response)
        {
            if (response == 'true') {
                jQuery('.notice').addClass('notice-success').append('<p>Successfully Update Data!</p>').show();
            }else{
                jQuery('.notice').addClass('notice-error').append('<p>Some Error Please check it!</p>').show();
            }
            setTimeout(function(){ 
                window.parent.location = deletedp.admin_url;
                tb_remove();
            }, 2000);
        });
	});
	jQuery('.show_details').on('click',function(){
		var id = jQuery(this).attr("data-id");
		var data = {'action': 'dp_show_details','id': id};
        jQuery.post(deletedp.ajax_url + '/wp-admin/admin-ajax.php', data, function (response)
        {
            jQuery('#view_content_section').hide();
            jQuery('#show_content_section').append(response);
            jQuery('#show_content_section').show();

        });
	});
	jQuery('#submit').on('click',function(){
        var delete_id = jQuery('input[name="users"]:checked').serializeArray();

		var data = {
			'action': 'dp_delete_data',
			'users': JSON.stringify(delete_id)
		};
		jQuery.post(deletedp.ajax_url + '/wp-admin/admin-ajax.php',data, function (response)
        {
            if (response == 'true') {
                jQuery('.notice').addClass('notice-success').append('<p>Successfully Update Data!</p>').show();
            }else{
                jQuery('.notice').addClass('notice-error').append('<p>Some Error Please check it!</p>').show();

            }
            setTimeout(function(){ 
                window.parent.location = deletedp.admin_url;
                tb_remove();
            }, 2000);
        });
	});
});
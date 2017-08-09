jQuery(document).ready(function() {
	jQuery('.attachment_upload').click(function() {
		jQuery('.attachment').removeClass('active');
		jQuery(this).parent().find('.attachment:first').addClass('active');
		tb_show('', 'media-upload.php?post_id=0&TB_iframe=1');
		return false;
	});

	var _send_to_editor = window.send_to_editor;
	window.send_to_editor = function(html) {
		 if (jQuery('.attachment.active').length > 0) {
           	imgurl = jQuery('img',html).attr('src');
			aurl = jQuery('a',"<div>" + html + "</div>").attr('href');

			if (imgurl) {
				jQuery('.attachment.active').val(imgurl);
			} else {
				jQuery('.attachment.active').val(aurl);
			}
 
			jQuery('.attachment').removeClass('active');
			tb_remove();
        } else {
            _send_to_editor(html);
        }
	}

    jQuery.getJSON('http://api.twitter.com/1/statuses/user_timeline.json?callback=?&count=3&screen_name=cwantwm',
        function(data) {
            jQuery.each(data, function(i, tweet) {
                if(tweet.text !== undefined) {
                    jQuery('#admin-section-tweets-wrap').append("<li class='speech'>"+tweet.text+"</li>");
                }
            });
        }
    );
});

<div class="margin:0; padding:0; box-sizing:border-box; height:100vh">  
<div style="background: #f7f7f7;
										font-family: 'Helvetica Neue', sans-serif;
										
                                        width:100%;">
    <div style="background: #fff;
												border: 2px solid #f0f0f0;
												margin: 15px auto 0;
												max-width: 400px;
												padding: 20px 25px 30px;
												text-align: center;">
        <h2>You just authenticated Square!</h2>
        <h3>We'd like to redirect you to:</h3>
        <p style="font-family: 'Monaco', sans-serif;
											font-size: 13px;
											padding: 20px 0 0;
											word-wrap: break-word;"><?php echo get_site_url() . '/dashboard/pos/'; ?></p>
        <button class="authorize__btn" data-url="<?php echo admin_url('admin-ajax.php'); ?>" data-siteurl = "<?php echo get_site_url() . '/dashboard/pos/'; ?>" style="
						background: #404040;
						border-radius: 2px;
						color: #fff;
						display: block;
						font-size: 14px;
						font-weight: bold;
						margin-top: 20px;
						padding: 12px 0 13px;
						text-align: center;
						text-decoration: none;
						text-transform: uppercase;
						width: 100%;
                        cursor:pointer;
						">That's my site - redirect me</button>
    </div>
</div>
</div>

<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>

<script>
    jQuery(document).ready(function($) {

        // console.log();  

        $.urlParam = function(name){
            var results = new RegExp('[\?&]' + name + '=([^&#]*)').exec(window.location.href);
            if (results==null) {
            return null;
            }
            return decodeURI(results[1]) || 0;
        }


        var btnClass = $('.authorize__btn');

        btnClass.click(function(){
            var code = $.urlParam('code');
            var ajax_url = btnClass.attr('data-url');
            var redirect = btnClass.attr('data-siteurl');

            if(code == null){
                alert('Something went wrong');
                return false;
            }

            $.ajax({
                url: ajax_url,
                method: 'POST',
                data: {
                    action: 'square_pos_verify_connect',
                    code: code
                },
                success: function (response) {

                    if(response !=''){
                        return false;
                    }

                    window.location.href = redirect;
                },
            });

        })
    });
</script>
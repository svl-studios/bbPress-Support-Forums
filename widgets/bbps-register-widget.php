<?php


class bbps_support_register_widget extends WP_Widget {

	function bbps_support_register_widget(){
		$widget_ops = array(
				'classname' => 'bbps_support_register_widget',
				'description' => 'Display a register button for your website in the sidebar (working)'
		);

        add_action('login_form_register', array(&$this, 'register'));
        add_filter('validate_username', array(&$this, 'validate_username'), 10, 2);
		$this->WP_Widget('bbps_support_register_widget', 'Registration', $widget_ops);
	}

	function form( $instance ){
	    $defaults = array(
            'title' => 'Forum Registration',
		);
		$instance = wp_parse_args( (array) $instance, $defaults );

		$title = $instance['title'];
			?>
			<p>Title: <input class="widefat" name="<?php echo $this->get_field_name( 'title' ); ?>" type="text" value="<?php echo esc_attr($title); ?>" /> </p>
	    <?php

	}

	function update($new_instance, $old_instance){

		$instance = $old_instance;
		$instance['title'] = $new_instance['title'];
		return $instance;

	}

    /*function template_link( $content ){
		if(strstr($content, '?')){
			$content .= '&callback=?'; //&amp;template='.$this->template;
		}else{
			$content .= '?callback=?';//&amp;template='.$this->template;
		}
		return $content;
	}*/

        function validate_username($sanatised, $username){
            preg_match('/[-_.A-Za-z0-9]+/', $username, $matches);
            return strlen($username)>3 && $username == $matches[0];
        }

/**
	 * Checks post data and registers user
	 * @return string
	 */
	function register(){
		if( !empty($_REQUEST['register_ajax_widget']) ) {
			$return = array();

//			$return['result'] = false;
//			$return['error'] = "Forum registration temporarily closed, please try again later";

			if ('POST' == $_SERVER['REQUEST_METHOD'] && !empty($_REQUEST['user_login']) && !empty($_REQUEST['user_email'])) {
				//require_once( ABSPATH . WPINC . '/registration.php');


				// check purchase code.
				$valid_purchase_codes = false;
				if(!isset($return['result']) && get_option('_bbps_envato_username','') && get_option('_bbps_envato_api_key','')) {
					$purchase_code       = isset( $_REQUEST['user_purchase_code'] ) ? strtolower(trim($_REQUEST['user_purchase_code'])) : false;
					if ( strlen( $purchase_code ) > 10 ) {
						$api_url = 'http://marketplace.envato.com/api/edge/' . get_option('_bbps_envato_username','') . '/' . get_option('_bbps_envato_api_key',''). '/verify-purchase:'.$purchase_code.'.json';
						$response 	= wp_remote_get($api_url);
						if( !is_wp_error($response) ) {
							$api_result = @json_decode( $response['body'], true );
							if ( $api_result && isset( $api_result['verify-purchase'] ) && is_array( $api_result['verify-purchase'] ) && isset( $api_result['verify-purchase']['item_id'] ) ) {
								$valid_purchase_codes = array();
								$valid_purchase_codes[ $purchase_code ] = $api_result['verify-purchase'];
							}
						}
					}
					if(!$valid_purchase_codes){
						$return['result'] = false;
						$return['error']  = "Incorrect Item Purchase code, please make sure it is copied correctly.";
					}
				}

				if(!isset($return['result']) && get_option('_bbps_recaptcha_client','') && get_option('_bbps_recaptcha_secret','')) {
					// recaptcha time!
					$ch = curl_init( "https://www.google.com/recaptcha/api/siteverify" );
					curl_setopt( $ch, CURLOPT_RETURNTRANSFER, true );
					curl_setopt( $ch, CURLOPT_HEADER, false );
					curl_setopt( $ch, CURLOPT_POST, true );
					curl_setopt( $ch, CURLOPT_POSTFIELDS, array(
						"secret"   => get_option('_bbps_recaptcha_secret',''), //"",
						"response" => isset( $_REQUEST['g-recaptcha-response'] ) ? $_REQUEST['g-recaptcha-response'] : false,
						'remoteip' => $_SERVER['REMOTE_ADDR'],
					) );
					$data   = curl_exec( $ch );
					$google_result = @json_decode( $data, true );
					if ( ! $google_result || ! $google_result['success'] ) {
						$return['result']  = false;
						$return['message'] = var_export( $google_result, true );
					}
				}
				if(!isset($return['result'])){

					$errors = register_new_user( $_POST['user_login'], $_POST['user_email'] );
					if ( ! is_wp_error( $errors ) ) {
						//Success
						$user_data         = get_userdata( $errors );
						if(is_array($valid_purchase_codes) && count($valid_purchase_codes)){
							update_user_meta( $user_data->ID , 'envato_codes', $valid_purchase_codes);
						}
						$return['result']  = true;
						$return['message'] = __( sprintf( 'Thank you %s. Registration is complete. Please check your e-mail.', $user_data->user_login ) );
					} else {
						//Something's wrong
						$return['result'] = false;
						$return['error']  = $errors->get_error_message() . "<br>Username: " . htmlspecialchars( sanitize_user( $_POST['user_login'], true ) );
					}
				}
			}
			$return = json_encode($return);
            if( isset($_REQUEST['callback']) && preg_match("/^jQuery[_a-zA-Z0-9]+$/", $_REQUEST['callback']) ){
                $return = $_GET['callback']."($return)";
            }
            echo $return;
			exit();
		}
	}


    function widget($args, $instance){
        if(is_user_logged_in() ){ // || !get_option('users_can_register')
            // dont show a widget here!
            return false;
        }
        extract($args);
        echo $before_widget;
        $title = apply_filters('widget_title', $instance['title']);
        if(!empty($title)) { echo $before_title . $title . $after_title; };
        //Signup Links

            if ( function_exists('bp_get_signup_page') ) { //Buddypress
                $register_link = bp_get_signup_page();
            }elseif ( file_exists( ABSPATH."/wp-signup.php" ) ) { //MU + WP3
                $register_link = site_url('wp-signup.php', 'login');
            } else {
                $register_link = site_url('wp-login.php?action=register', 'login');
            }
            ?>

            <script type="text/javascript">
                function ajax_do_register(){
                    //jQuery('<div class="LoginWithAjax_Loading" id="LoginWithAjax_Loading"></div>').prependTo('#LoginWithAjax_Register');
                    //Sort out url
                    //Get POST data
                    jQuery('#ajax_register_status').attr('class','alert alert-info').html('Processing, please wait...');
                    var postData = {};
                    jQuery.each(jQuery('#ajax_register_form *[name]'), function(index,el){
                        el = jQuery(el);
                        postData[el.attr('name')] = el.val();
                    });
                    jQuery.ajax({
                        url: '<?php echo site_url('wp-login.php?action=register'); ?>',
                        dataType: 'json',
                        type: 'POST',
                        data: postData,
                        success: function(data){
                            //variable status not here anymore
                            if( data.result === true || data.result === false ){
                                if(data.result === true){
                                    jQuery('#ajax_register_form').hide();
                                    jQuery('#register_button').hide();
                                    jQuery('#ajax_register_status').attr('class','alert alert-info').html(data.message);
                                }else{
                                    //If there already is an error element, replace text contents, otherwise create a new one and insert it
                                    jQuery('#ajax_register_status').attr('class','alert alert-warning').html( typeof data.error != 'undefined' ? data.error : data.message);
                                }
                            }else{
                                jQuery('#ajax_register_status').attr('class','alert alert-warning').html('An error has occured. Please try again.');
                            }
                        },
                        error: function(){
                            jQuery('#ajax_register_status').attr('class','alert alert-warning').html('An error has occured. Please try again.');
                        }
                    });
                    return false;
                }
                <?php if(get_option('_bbps_recaptcha_client','') && get_option('_bbps_recaptcha_secret','')){ ?>
                var captcha_ready_callback = function() {

			      };
                function display_register_captcha(){
                    grecaptcha.render('register_captcha', {
			          'sitekey' : '<?php echo get_option('_bbps_recaptcha_client','');?>'
			        });
	                return true;
                }
	            <?php } ?>
            </script>

        <!-- Button trigger modal -->
        <button class="btn btn-primary" data-toggle="modal" data-target="#ajax_register" onclick="display_register_captcha();"><?php _e('Register');?></button>


        <!-- Modal -->
        <div class="modal fade" id="ajax_register" tabindex="-1" role="dialog" aria-labelledby="ajax_registerLabel" aria-hidden="true">
          <div class="modal-dialog">
            <div class="modal-content">
              <div class="modal-header">
                <button type="button" class="close" data-dismiss="modal" aria-hidden="true">&times;</button>
                <h4 class="modal-title" id="ajax_registerLabel"><?php _e('Forum Registration');?></h4>
              </div>
              <div class="modal-body">
                <div id="ajax_register_status"></div>
                <form name="ajax_register_form" id="ajax_register_form" action="#" method="post">
                    <!--<p>
                        <label><?php /*_e('Envato Item Purchase Code') */?><br />
                        <input type="text" name="purchase_code" id="purchase_code" class="input-xlarge" tabindex="18" /></label>
                    </p>-->
	                <?php if(get_option('_bbps_envato_username','') && get_option('_bbps_envato_api_key','')){ ?>
                    <div class="form-group">
	                    <p class="help-block">This support forum is for verified buyers only. Please enter your unique CodeCanyon Item Purchase Code below (<a href="//dtbaker.net/admin/includes/plugin_envato/images/envato-license-code.gif" target="_blank">click here</a> for instructions).</p>
                        <label for="register_widget_purchase_code"><?php _e('CodeCanyon Purchase Code') ?></label>
                        <input type="text" class="form-control" name="user_purchase_code" id="register_widget_purchase_code" placeholder="">
                      </div>
	                <?php } ?>
                    <div class="form-group">
                        <label for="register_widget_username"><?php _e('Username') ?></label>
                        <input type="text" class="form-control" name="user_login" id="register_widget_username" placeholder="">
                      </div>
                    <div class="form-group">
                        <label for="register_widget_email"><?php _e('E-mail') ?></label>
                        <input type="email" class="form-control" name="user_email" id="register_widget_email" placeholder="">
                      </div>
				    <?php if(get_option('_bbps_recaptcha_client','') && get_option('_bbps_recaptcha_secret','')){ ?>
				    <script src="https://www.google.com/recaptcha/api.js?onload=captcha_ready_callback&render=explicit" async defer></script>
                    <div class="form-group">
                        <label for="register_widget_captcha"><?php _e('Captcha Code') ?></label>
                        <div id="register_captcha"></div>
                      </div>
	                <?php } ?>
                    <?php do_action('register_form'); ?>
                    <input type="hidden" name="register_ajax_widget" value="1"/>
                    <p id="reg_passmail" class="help-block"><?php _e('A password will be e-mailed to you which you can use to access the forum.') ?></p>
                </form>
              </div>
              <div class="modal-footer">
                  <button type="button" class="btn btn-default" data-dismiss="modal"><?php _e('Close');?></button>
                <button class="btn btn-primary" id="register_button" onclick="return ajax_do_register();" tabindex="21"><?php _e('Register');?></button>
              </div>
            </div>
          </div>
        </div>


        <?php
        echo $after_widget . " ";
    }


} // end of resolved count class


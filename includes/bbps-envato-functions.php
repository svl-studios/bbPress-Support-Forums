<?php
/* 
bbps - envato verify functions
*/

add_filter('bbp_current_user_can_publish_topics','bbps_envato_can_publish_topics_replies');
add_filter('bbp_current_user_can_publish_replies','bbps_envato_can_publish_topics_replies');

function bbps_envato_can_publish_topics_replies($retval){
	// check if user has valid purchase code.
	if(get_option('_bbps_envato_username','') && get_option('_bbps_envato_api_key','') && is_user_logged_in()) {
		$user_id = get_current_user_id();
		if ( $user_id ) {
			$envato_codes = get_user_meta( $user_id, 'envato_codes', true );
			if ( ! $envato_codes ) {
				if(current_user_can('bbp_moderator'))return true;
				return false;
			}
		}
	}

	return $retval;
}

add_action('personal_options_update','bbps_envato_bbp_user_edit_additional_save');
function bbps_envato_bbp_user_edit_additional_save(){
	if(get_option('_bbps_envato_username','') && get_option('_bbps_envato_api_key','') && isset($_REQUEST['user_purchase_code']) && strlen($_REQUEST['user_purchase_code']) > 5) {
		$user_id = bbp_get_user_id();
		$envato_codes = get_user_meta( $user_id, 'envato_codes', true );
		if(!$envato_codes)$envato_codes = array();
		$purchase_code = strtolower( trim( $_POST['user_purchase_code'] ) );
		$api_url       = 'http://marketplace.envato.com/api/edge/' . get_option( '_bbps_envato_username', '' ) . '/' . get_option( '_bbps_envato_api_key', '' ) . '/verify-purchase:' . $purchase_code . '.json';
		$response      = wp_remote_get( $api_url );
		if ( ! is_wp_error( $response ) ) {
			$api_result = @json_decode( $response['body'], true );
			if ( $api_result && isset( $api_result['verify-purchase'] ) && is_array( $api_result['verify-purchase'] ) && isset( $api_result['verify-purchase']['item_id'] ) ) {
				$envato_codes[ $purchase_code ] = $api_result['verify-purchase'];
				update_user_meta( $user_id, 'envato_codes', $envato_codes );
			}
		}
	}
}
add_action('bbp_user_edit_additional','bbps_envato_bbp_user_edit_additional');
add_action('bbp_user_display_additional','bbps_envato_bbp_user_display_additional');
function bbps_envato_bbp_user_display_additional(){
	bbps_envato_bbp_user_edit_additional(false);
}
function bbps_envato_bbp_user_edit_additional($edit=true){
	if(get_option('_bbps_envato_username','') && get_option('_bbps_envato_api_key','')) {
		$envato_codes = get_user_meta( bbp_get_user_id(), 'envato_codes', true );
		?>
		<fieldset class="bbp-form">
			<legend><?php _e( 'Verified Purchases', 'bbpress' ) ?></legend>

			<?php if ( is_array( $envato_codes ) ) {
				//print_r($envato_codes);
				?>
				<ul>
					<?php foreach ( $envato_codes as $license_code => $license_data ) {
						?>
						<li>
							Purchase <?php echo $license_data['item_name']; ?>
							on <?php echo $license_data['created_at']; ?> with
							username <?php echo $license_data['buyer']; ?> (<?php echo $license_data['licence']; ?>).
						</li>
					<?php
					} ?>
				</ul>
			<?php } ?>

			<?php if($edit){ ?>
			<p>Submit additional purchase codes here (<a
					href="//dtbaker.net/admin/includes/plugin_envato/images/envato-license-code.gif" target="_blank">click
					here</a> for help locating your item purchase code):</p>

			<div class="form-group">
				<input type="text" class="form-control" name="user_purchase_code" id="register_widget_purchase_code"
				       placeholder="Please paste your purchase code here">
			</div>
			<button type="submit" class="btn btn-default">Submit Purchase Code</button>
			<?php } ?>

		</fieldset>
	<?php
	}
}

add_action('bbp_template_before_pagination_loop','bbps_envato_notify_purchase_code');
function bbps_envato_notify_purchase_code(){
	if(get_option('_bbps_envato_username','') && get_option('_bbps_envato_api_key','') && is_user_logged_in()){
		$user_id = get_current_user_id();
		if($user_id){
			$envato_codes = get_user_meta( $user_id , 'envato_codes', true);
			if(!$envato_codes)$envato_codes=array();
			if(isset($_POST['user_purchase_code']) && strlen($_POST['user_purchase_code']) > 5){
				$purchase_code = strtolower(trim($_POST['user_purchase_code']));
				$api_url = 'http://marketplace.envato.com/api/edge/' . get_option('_bbps_envato_username','') . '/' . get_option('_bbps_envato_api_key',''). '/verify-purchase:'.$purchase_code.'.json';
				$response 	= wp_remote_get($api_url);
				if( !is_wp_error($response) ) {
					$api_result = @json_decode( $response['body'], true );
					if($api_result && isset($api_result['verify-purchase']) && is_array($api_result['verify-purchase']) && isset($api_result['verify-purchase']['item_id'])){
						$envato_codes[$purchase_code] = $api_result['verify-purchase'];
						update_user_meta( $user_id , 'envato_codes', $envato_codes);
						?>
						<div class="alert alert-info" role="alert">
							Thank you <strong><?php echo $api_result['verify-purchase']['buyer'];?></strong>! <br/>
							You have verified your purchase of <em><?php echo $api_result['verify-purchase']['item_name'];?></em> from <em><?php echo $api_result['verify-purchase']['created_at'];?></em>. <br/>
							You can verify additional purchases from your <a href="<?php bbp_user_profile_edit_url($user_id);?>">profile page</a>.
						</div>
						<?php
					}else{
						?>
						<div class="alert alert-error" role="alert">Sorry this license code is not valid. Please send through an email support request for assistance.</div>
						<?php
					}
					//$valid_purchase_codes['123'] = $api_result;
				}else{
					?>
					<div class="alert alert-error" role="alert">Sorry a temporary error occurred while processing your license code request.</div>
					<?php
				}
			}

			if(!$envato_codes){
				?>
				<div class="alert alert-info" role="alert">
					<div style="padding-bottom: 10px">
						<strong>Notice:</strong> To continue posting on the Support Forum please enter your Item Purchase Code below. This helps us verify buyers and provide a better level of service. Please <a href="//dtbaker.net/admin/includes/plugin_envato/images/envato-license-code.gif" target="_blank">click here</a> for help finding your purchase code.
					</div>
					<form name="envato_purchase_code" id="envato_purchase_code" action="" method="post">
	                    <div class="form-group">
	                        <input type="text" class="form-control" name="user_purchase_code" id="register_widget_purchase_code" placeholder="Please paste your purchase code here">
	                      </div>
						<button type="submit" class="btn btn-default">Submit Purchase Code</button>
					</form>

				</div>
				<?php
			}
		}
	}
}
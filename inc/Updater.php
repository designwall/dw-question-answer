<?php  
if( !class_exists( 'EDD_SL_Plugin_Updater' ) ) {
	// load our custom updater
	include( DWQA_DIR . '/lib/easy-digital-downloads/EDD_SL_Plugin_Updater.php' );
}

class DWQA_Updater {
	protected $store = 'http://dwqa.local/';
	protected $name = '';
	protected $slug = '';
	protected $file = __FILE__;
	protected $version = '1.0.0';
	protected $license_option_key;
	protected $license_status_key;
	protected $description = '';


	public function __construct() {
		$this->license_option_key = $this->slug . '_license_key';
		$this->license_status_key = $this->slug . '_license_status';
		add_action( 'admin_init', array( $this, 'plugin_updater' ), 0 );
		add_action('admin_init', array( $this, 'register_option' ) );
		// add_action('admin_init', array( $this, 'deactivate_license' ) );

		add_action( 'wp_ajax_'.$this->slug.'_activate_license', array( $this, 'activate_license' ) );
	}

	public function get_key() {
		return $this->license_option_key;
	}

	public function get_status_key() {
		return $this->license_status_key;
	}

	public function get_name() {
		return $this->name;
	}

	public function plugin_updater() {
		// retrieve our license key from the DB
		$license_key = trim( get_option( $this->license_option_key ) );

		// setup the updater
		$edd_updater = new EDD_SL_Plugin_Updater( $this->store, $this->file, array(
				'version' 	=> $this->version, 				// current version number
				'license' 	=> $license_key, 		// license key (used get_option above to retrieve from DB)
				'item_name' => $this->name, 	// name of this plugin
				'author' 	=> 'DesignWall'  // author of this plugin
			)
		);
	}


	/************************************
	* this illustrates how to check if
	* a license key is still valid
	* the updater does this for you,
	* so this is only needed if you
	* want to do something custom
	*************************************/

	public function check_license() {

		global $wp_version;

		$license = trim( get_option( $this->license_option_key ) );

		$api_params = array(
			'edd_action' => 'check_license',
			'license' => $license,
			'item_name' => urlencode( EDD_SAMPLE_ITEM_NAME ),
			'url'       => home_url()
		);

		// Call the custom API.
		$response = wp_remote_post( EDD_SAMPLE_STORE_URL, array( 'timeout' => 15, 'sslverify' => false, 'body' => $api_params ) );

		if ( is_wp_error( $response ) )
			return false;

		$license_data = json_decode( wp_remote_retrieve_body( $response ) );

		if( $license_data->license == 'valid' ) {
			echo 'valid'; exit;
			// this license is still valid
		} else {
			echo 'invalid'; exit;
			// this license is no longer valid
		}
	}

	public function register_option() {
		add_settings_section( $this->slug, $this->name, array( $this, 'display_description' ), 'dwqa-addons-settings' );
		// creates our settings in the options table
		register_setting( 'dwqa-addons', $this->license_option_key, array( $this, 'sanitize_license' ) );
		add_settings_field( $this->license_option_key, __( 'License Key', 'dw-question-answer' ), array( $this, 'license_setting_field' ), 'dwqa-addons-settings', $this->slug );
	}

	public function display_description() {
		if( $this->description ) {
			echo '<p class="description">'.$this->description.'</p>';
		}
	}

	public function sanitize_license( $new ) {
		$old = get_option( $this->license_option_key );
		if( $old && $old != $new ) {
			delete_option( $this->license_status_key ); // new license has been entered, so must reactivate
		}
		return $new;
	}

	public function license_setting_field() {
		$license_key = get_option( $this->license_option_key );
		$status = get_option( $this->license_status_key );
		echo '<input type="text" name="'.$this->license_option_key.'" id="'.$this->license_option_key.'" class="regular-text" value="'.$license_key.'" >';
		if ( 'valid' == $status ) {
			echo '<p class="description">Your license key was activated</p>';
		}
		if ( $license_key && 'valid' != $status ) {
			echo '<br><button id="'.$this->slug.'-activate-license" class="button btn" type="button">'.__('Activate','dw-question-answer').'</button>';
			?>
			<script type="text/javascript">
			jQuery('#<?php echo $this->slug ?>-activate-license').on('click', function(e){
				e.preventDefault();
				jQuery.ajax({
					url: '<?php echo admin_url('admin-ajax.php'); ?>',
					type: 'POST',
					dataType: 'json',
					data: {
						action: '<?php echo $this->slug ?>_activate_license',
						nonce: '<?php echo wp_create_nonce( $this->slug . '_activate_license' ) ?>'
					},
				})
				.done(function() {
					document.location.href = document.location.href;
				});
			});
			</script>
			<?php
		}
	}

	public function activate_license() {
		// run a quick security check
	 	if( ! check_admin_referer( $this->slug . '_activate_license', 'nonce' ) ) {
	 		return;
	 	}

		// retrieve the license from the database
		$license = trim( get_option( $this->license_option_key ) );

		// data to send in our API request
		$api_params = array(
			'edd_action'=> 'activate_license',
			'license' 	=> $license,
			'item_name' => urlencode( $this->name ), // the name of our product in EDD
			'url'       => home_url()
		);

		// Call the custom API.
		$response = wp_remote_post( $this->store, array( 'timeout' => 15, 'sslverify' => false, 'body' => $api_params ) );

		// make sure the response came back okay
		if ( is_wp_error( $response ) )
			return false;

		// decode the license data
		$license_data = json_decode( wp_remote_retrieve_body( $response ) );

		// $license_data->license will be either "valid" or "invalid"

		update_option( $this->license_status_key, $license_data->license );

		wp_send_json_success( array( 'message' => __( 'Plugin was activated', 'dw-question-answer' ) ) );
	}

	public function deactivate_license() {

		// listen for our activate button to be clicked
		if( isset( $_POST['edd_license_deactivate'] ) ) {

			// run a quick security check
		 	if( ! check_admin_referer( 'edd_sample_nonce', 'edd_sample_nonce' ) )
				return; // get out if we didn't click the Activate button

			// retrieve the license from the database
			$license = trim( get_option( 'edd_sample_license_key' ) );


			// data to send in our API request
			$api_params = array(
				'edd_action'=> 'deactivate_license',
				'license' 	=> $license,
				'item_name' => urlencode( $this->name ), // the name of our product in EDD
				'url'       => home_url()
			);

			// Call the custom API.
			$response = wp_remote_post( $this->store, array( 'timeout' => 15, 'sslverify' => false, 'body' => $api_params ) );

			// make sure the response came back okay
			if ( is_wp_error( $response ) )
				return false;

			// decode the license data
			$license_data = json_decode( wp_remote_retrieve_body( $response ) );

			// $license_data->license will be either "deactivated" or "failed"
			if( $license_data->license == 'deactivated' )
				delete_option( 'edd_sample_license_status' );

		}
	}
}
?>
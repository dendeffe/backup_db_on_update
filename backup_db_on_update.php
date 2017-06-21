<?php
/**
* Plugin Name: Backup_db_on_update
* Plugin URI: http://defunkt.nu/
* Description: The plugin will backup the database before an automatic update
* Version: 0.1
* Author: Stijn De Lathouwer
* Author URI: http://defunkt.nu/
* License: GPL
*
* @package Backup db on update
* @license GPL
* @author Stijn De Lathouwer <stijn@defunkt.nu>
*/


/**
 * Backup class
 *
 * This class creates the actual backup when a site auto updates.
 * It also sets the auto update variables so everything auto updates.
 *
 * @package Backup db on update
 * @author Stijn De Lathouwer <stijn@defunkt.nu>
 * 
 */

class Backup_db_on_update {

	private $db_name = DB_NAME;
	private $db_user = DB_USER;
	private $db_password = DB_PASSWORD;
	private $db_host = DB_HOST;

	function __construct() {

		// Change this setting to false if you have your own auto update settings
		$enable_auto_update = apply_filters( 'bdou_enable_auto_update', true );
		
		if( $enable_auto_update ) {
			add_filter( 'automatic_updates_is_vcs_checkout', '__return_false', 1 );  
			add_filter( 'auto_update_plugin', '__return_true' );
		}

		add_action( 'pre_auto_update', array( $this, 'do_backup' ) );

	}

	function do_backup() {
		$backup_db_on_update_settings = new Backup_db_on_update_settings();
		$plugin_settings = $backup_db_on_update_settings->get_options();
		if( !$plugin_settings['mysqldump'] ) {
			// You can change the location of mysqldump here
			$plugin_settings['mysqldump'] = apply_filters( 'bdoa_mysqldump','mysqldump' );
		}
		if( !$plugin_settings['backup_location'] ) {
			// You can change the location where the backup sql file is saved here
			$plugin_settings['backup_location'] = apply_filters( 'bdoa_bacup_location', '/db_backup' );
		}
		$filename = date( "Ymd_His" ) . '-' . rand( 1000, 9000 );
		exec( $plugin_settings['mysqldump'] . ' --host=' . $this->db_host . ' --user=' . $this->db_user .' --password=' . $this->db_password .' ' . $this->db_name . ' > ' . $plugin_settings['backup_location']  . $filename . '.sql 2>&1' );
	}
}

$backup_db = new Backup_db_on_update();



/**
 * Backup settings class
 *
 * A class to get and set the options for this plugin
 *
 * @package Backup db on update
 * @author Stijn De Lathouwer <stijn@defunkt.nu>
 * 
 */

class Backup_db_on_update_settings {
	function __construct() {
		// Register the setting for wp
		add_action( 'admin_init', array( $this, 'register_settings' ) );	
	}
	function register_settings() {
		register_setting( 'backup-db-on-update-group', 'backup_db_on_update_options', array( 'sanitize_callback' => array( $this, 'sanitize_options' ) ) );
	}
	function sanitize_options( $input ) {
		$input['mysqldump'] = sanitize_text_field( $input['mysqldump'] );
		$input['backup_location'] = sanitize_text_field( $input['backup_location'] );
		return $input;
	}
	function get_options() {
		return get_option( 'backup_db_on_update_options' );
	}
}


/**
 * Backup admin class
 *
 * This class creates the admin screens to change the settings for the plugin
 *
 * @package Backup db on update
 * @author Stijn De Lathouwer <stijn@defunkt.nu>
 * 
 */

class Backup_db_on_update_admin {
	function __construct() {
		$backup_db_on_update_settings = new Backup_db_on_update_settings();
		// Add menu link
		add_action( 'admin_menu', array( $this, 'options_menu' ) );
	}

	function options_menu() {
		add_options_page( 'Settings: Backup DB on update', 'Backup DB on update', 'manage_options', 'backup_db_on_update', array( $this, 'options_page' ) );
	}
	function options_page() {
		$backup_db_on_update_setting = new Backup_db_on_update_settings();
?>
		<div class="wrap">
			<h2><?php _e( 'Backup DB on update', 'backup_db_on_update' ); ?></h2>
			<form method="post" action="options.php">
				<?php settings_fields( 'backup-db-on-update-group' ); ?>
				<?php $backup_db_on_update_options = $backup_db_on_update_setting->get_options( 'backup_db_on_update_options' ); ?>
				<table class="form-table">
					<tr valign="top">
						<th scope="row"><?php _e( 'Mysqldump location' ); ?></th>
						<td><input type="text" name="backup_db_on_update_options[mysqldump]" value="<?php echo $backup_db_on_update_options['mysqldump']; ?>" />
					</tr>
					<tr valign="top">
						<th scope="row"><?php _e( 'Location to save the backup file' ); ?></th>
						<td><input type="text" name="backup_db_on_update_options[backup_location]" value="<?php echo $backup_db_on_update_options['backup_location']; ?>" />
					</tr>
				</table>
				<p class="submit"><input type="submit" class="button-primary" value="<?php _e( 'Save settings', 'backup_db_on_update' ); ?>" /></p>
			</form>
		</div><!-- .wrap -->
<?php
	}
}

$backup_db_on_update_admin = new Backup_db_on_update_admin();

<?php
/**
 * Plugin Name: API Mirror Switcher
 * Description: This plugin when enabled will replace api.wordpress.org with a mirror API domain which is specified in the config file with the constant API_MIRROR.
 * Version: 0.0.1
 * Author: Namith Jawahar
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

if ( ! defined( 'API_DEFAULT' ) ) {
	define( 'API_DEFAULT', 'api.wordpress.org' );
}

/*if ( ! defined( 'API_MIRROR' ) ) {
	define( 'API_MIRROR', 'api.freewordpress.org' );
}*/

// Hook into plugin activation to run the replacement.
register_activation_hook( __FILE__, 'api_mirror_switcher_replace_api_domain_in_files' );

// Hook into core update completion to run the replacement.
add_action( '_core_updated_successfully', 'api_mirror_switcher_replace_api_domain_in_files' );

/**
 * Function to recursively scan files and replace the API domain.
 */
function api_mirror_switcher_replace_api_domain_in_files() {
	global $wp_filesystem;
	if ( defined( 'API_MIRROR' ) && defined( 'ABSPATH' ) ) {
		if ( ! api_mirror_switcher_init_wp_filesystem() ) {
			return;
		}

		$files = new RecursiveIteratorIterator(
			new RecursiveDirectoryIterator( ABSPATH, RecursiveDirectoryIterator::SKIP_DOTS )
		);

		foreach ( $files as $file ) {
			// Skip the wp-content directory.
			if ( strpos( $file->getPathname(), ABSPATH . 'wp-content' ) === 0 ) {
				continue;
			}

			// Process only PHP and JS files.
			if ( $file->isFile() && preg_match( '/\.(php|js)$/', $file->getFilename() ) ) {
				$file_path = $file->getRealPath();
				$contents  = $wp_filesystem->get_contents( $file_path );

				if ( false === $contents ) {
					continue;
				}

				if ( strpos( $contents, API_DEFAULT ) !== false ) {
					$updated_content = str_replace( API_DEFAULT, API_MIRROR, $contents );
					$wp_filesystem->put_contents( $file_path, $updated_content, FS_CHMOD_FILE );
				}
			}
		}
	}
}

/**
 * Initialize the WP_Filesystem API.
 *
 * @return bool True if initialized, False otherwise.
 */
function api_mirror_switcher_init_wp_filesystem() {
	require_once ABSPATH . 'wp-admin/includes/file.php';

	$creds = request_filesystem_credentials( '', '', false, false, null );
	if ( ! WP_Filesystem( $creds ) ) {
		return false;
	}
	global $wp_filesystem;
	return isset( $wp_filesystem );
}

<?php
/**
 * Plugin Name: Rechtstexte Generator Agency Hub
 * Description: Zentrale Lizenz- und Textbaustein-Verteilung für den Frontend Rechtstexte Generator.
 * Version: 2.1.4
 * Author: Codex
 * Text Domain: frontend-rechtstexte-generator
 * Requires PHP: 8.0
 * Requires Plugins: frontend-rechtstexte-generator
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

define( 'FRG_HUB_VERSION', '2.1.4' );
define( 'FRG_HUB_FILE', __FILE__ );
define( 'FRG_HUB_DIR', plugin_dir_path( __FILE__ ) );

require_once FRG_HUB_DIR . 'includes/class-frg-license-manager.php';
require_once FRG_HUB_DIR . 'includes/class-frg-hub-feed.php';
require_once FRG_HUB_DIR . 'includes/class-frg-hub-admin.php';

function frg_agency_hub_is_central_site(): bool {
	return ! is_multisite() || is_main_site();
}

function frg_agency_hub_activate( bool $network_wide = false ): void {
	if ( is_multisite() && $network_wide ) {
		switch_to_blog( get_main_site_id() );
		FRG_License_Manager::install();
		restore_current_blog();
		return;
	}

	FRG_License_Manager::install();
}

register_activation_hook( __FILE__, 'frg_agency_hub_activate' );

if ( frg_agency_hub_is_central_site() ) {
	add_filter( 'frg_hub_available', '__return_true' );
	add_filter(
		'frg_block_feed_modes',
		static function ( array $modes ): array {
			$modes['hub'] = __( 'Zentrale – Texte für Kundenseiten bereitstellen', 'frontend-rechtstexte-generator' );
			return $modes;
		}
	);
}

add_action(
	'plugins_loaded',
	static function (): void {
		if ( ! frg_agency_hub_is_central_site() ) {
			return;
		}

		if ( ! class_exists( 'FRG_Generator' ) || ! class_exists( 'FRG_Text_Modules' ) ) {
			add_action(
				'admin_notices',
				static function (): void {
					if ( current_user_can( 'activate_plugins' ) ) {
						echo '<div class="notice notice-error"><p>' . esc_html__( 'Der Agency Hub benötigt das aktivierte Plugin „Frontend Rechtstexte Generator“.', 'frontend-rechtstexte-generator' ) . '</p></div>';
					}
				}
			);
			return;
		}

		FRG_License_Manager::maybe_install();
		$generator       = new FRG_Generator( new FRG_Text_Modules() );
		$license_manager = new FRG_License_Manager();
		$feed            = new FRG_Hub_Feed( $generator, $license_manager );
		$admin           = new FRG_Hub_Admin( $license_manager );

		add_action( 'rest_api_init', array( $feed, 'register_routes' ) );
		add_action( 'admin_menu', array( $admin, 'register_menu' ) );
		add_action( 'admin_post_frg_manage_license', array( $admin, 'handle_license_request' ) );
	},
	20
);

<?php
/**
 * Plugin Name: Frontend Rechtstexte Generator
 * Description: Frontend-Wizard zur Erstellung modularer Rechtstexte ohne KI-Freitext.
 * Version: 1.1.0
 * Author: Codex
 * Text Domain: frontend-rechtstexte-generator
 * Domain Path: /languages
 * Requires PHP: 8.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

define( 'FRG_VERSION', '1.1.0' );
define( 'FRG_PLUGIN_FILE', __FILE__ );
define( 'FRG_PLUGIN_DIR', plugin_dir_path( __FILE__ ) );
define( 'FRG_PLUGIN_URL', plugin_dir_url( __FILE__ ) );

require_once FRG_PLUGIN_DIR . 'includes/class-frg-activator.php';
require_once FRG_PLUGIN_DIR . 'includes/class-frg-plugin.php';

register_activation_hook( __FILE__, array( 'FRG_Activator', 'activate' ) );

function frg_plugin(): FRG_Plugin {
	return FRG_Plugin::instance();
}

frg_plugin()->init();

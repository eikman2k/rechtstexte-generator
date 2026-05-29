<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class FRG_Plugin {
	private static ?FRG_Plugin $instance = null;

	private FRG_Storage $storage;
	private FRG_Generator $generator;
	private FRG_Page_Sync $page_sync;
	private FRG_Scanner $scanner;
	private FRG_Frontend_Wizard $frontend_wizard;
	private FRG_Shortcodes $shortcodes;
	private FRG_Admin $admin;

	public static function instance(): FRG_Plugin {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}

		return self::$instance;
	}

	public function init(): void {
		$this->load_dependencies();
		$this->boot_services();
		$this->register_hooks();
	}

	private function load_dependencies(): void {
		require_once FRG_PLUGIN_DIR . 'includes/class-frg-storage.php';
		require_once FRG_PLUGIN_DIR . 'includes/class-frg-text-modules.php';
		require_once FRG_PLUGIN_DIR . 'includes/class-frg-generator.php';
		require_once FRG_PLUGIN_DIR . 'includes/class-frg-page-sync.php';
		require_once FRG_PLUGIN_DIR . 'includes/class-frg-scanner.php';
		require_once FRG_PLUGIN_DIR . 'includes/class-frg-frontend-wizard.php';
		require_once FRG_PLUGIN_DIR . 'includes/class-frg-shortcodes.php';
		require_once FRG_PLUGIN_DIR . 'includes/class-frg-admin.php';
	}

	private function boot_services(): void {
		$this->storage         = new FRG_Storage();
		$text_modules          = new FRG_Text_Modules();
		$this->generator       = new FRG_Generator( $text_modules );
		$this->page_sync       = new FRG_Page_Sync();
		$this->scanner         = new FRG_Scanner();
		$this->frontend_wizard = new FRG_Frontend_Wizard( $this->storage, $this->generator, $this->page_sync, $this->scanner );
		$this->shortcodes      = new FRG_Shortcodes( $this->storage, $this->generator, $this->frontend_wizard );
		$this->admin           = new FRG_Admin( $this->storage, $this->generator );
	}

	private function register_hooks(): void {
		add_action( 'plugins_loaded', array( $this, 'load_textdomain' ) );
		add_action( 'init', array( $this->shortcodes, 'register' ) );
		add_action( 'wp_enqueue_scripts', array( $this->frontend_wizard, 'register_assets' ) );
		add_action( 'admin_enqueue_scripts', array( $this->admin, 'enqueue_assets' ) );
		add_action( 'admin_menu', array( $this->admin, 'register_menu' ) );
		add_action( 'wp_ajax_frg_admin_generate_block_draft', array( $this->admin, 'ajax_generate_block_draft' ) );
		add_action( 'wp_ajax_frg_admin_adopt_block_draft', array( $this->admin, 'ajax_adopt_block_draft' ) );
		add_action( 'wp_ajax_frg_generate_preview', array( $this->frontend_wizard, 'ajax_generate_preview' ) );
		add_action( 'wp_ajax_nopriv_frg_generate_preview', array( $this->frontend_wizard, 'ajax_generate_preview' ) );
		add_action( 'wp_ajax_frg_save_profile', array( $this->frontend_wizard, 'ajax_save_profile' ) );
		add_action( 'wp_ajax_nopriv_frg_save_profile', array( $this->frontend_wizard, 'ajax_save_profile' ) );
		add_action( 'wp_ajax_frg_sync_pages', array( $this->frontend_wizard, 'ajax_sync_pages' ) );
	}

	public function load_textdomain(): void {
		load_plugin_textdomain( 'frontend-rechtstexte-generator', false, dirname( plugin_basename( FRG_PLUGIN_FILE ) ) . '/languages' );
	}
}

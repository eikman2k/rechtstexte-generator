<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class FRG_Activator {
	public static function activate( bool $network_wide = false ): void {
		require_once ABSPATH . 'wp-admin/includes/upgrade.php';

		if ( is_multisite() && $network_wide ) {
			$sites = get_sites( array( 'number' => 0 ) );
			foreach ( $sites as $site ) {
				switch_to_blog( (int) $site->blog_id );
				self::create_table_and_defaults();
				restore_current_blog();
			}

			return;
		}

		self::create_table_and_defaults();
	}

	public static function deactivate(): void {
		wp_clear_scheduled_hook( 'frg_sync_remote_block_feed' );
	}

	public static function activate_new_site( WP_Site $site ): void {
		switch_to_blog( (int) $site->blog_id );
		self::create_table_and_defaults();
		restore_current_blog();
	}

	private static function create_table_and_defaults(): void {
		global $wpdb;

		$table_name      = $wpdb->prefix . 'frg_profiles';
		$charset_collate = $wpdb->get_charset_collate();
		$sql             = "CREATE TABLE {$table_name} (
			id BIGINT unsigned NOT NULL AUTO_INCREMENT,
			user_id BIGINT unsigned NULL,
			profile_name VARCHAR(255) NOT NULL DEFAULT '',
			data LONGTEXT NOT NULL,
			created_at DATETIME NOT NULL,
			updated_at DATETIME NOT NULL,
			PRIMARY KEY  (id),
			KEY user_id (user_id)
		) {$charset_collate};";

		dbDelta( $sql );

		add_option(
			'frg_settings',
			array(
				'legal_notice'       => __( 'Hinweis: Die erzeugten Texte basieren auf Ihren Eingaben und ersetzen keine anwaltliche Prüfung.', 'frontend-rechtstexte-generator' ),
				'show_generator_notice_impressum' => false,
				'show_generator_notice_privacy'   => false,
				'dynamic_page_content'             => true,
				'impressum_page'     => __( 'Impressum', 'frontend-rechtstexte-generator' ),
				'privacy_page'       => __( 'Datenschutzerklärung', 'frontend-rechtstexte-generator' ),
				'openai_model'       => 'gpt-5.6-terra',
				'block_feed_mode'    => 'off',
				'block_feed_url'     => '',
				'block_feed_key'     => '',
				'block_feed_auto_sync' => true,
				'impressum_page_id'  => 0,
				'privacy_page_id'    => 0,
			)
		);
	}
}

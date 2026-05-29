<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class FRG_Activator {
	public static function activate(): void {
		require_once ABSPATH . 'wp-admin/includes/upgrade.php';

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
				'impressum_page'     => __( 'Impressum', 'frontend-rechtstexte-generator' ),
				'privacy_page'       => __( 'Datenschutzerklärung', 'frontend-rechtstexte-generator' ),
				'impressum_page_id'  => 0,
				'privacy_page_id'    => 0,
			)
		);
	}
}

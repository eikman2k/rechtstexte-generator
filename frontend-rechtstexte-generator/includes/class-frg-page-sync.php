<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class FRG_Page_Sync {
	public function create_or_update_impressum_page( string $content ): int {
		$settings = get_option( 'frg_settings', array() );
		$title    = ! empty( $settings['impressum_page'] ) ? sanitize_text_field( $settings['impressum_page'] ) : __( 'Impressum', 'frontend-rechtstexte-generator' );

		return $this->upsert_page( $title, wp_kses_post( $content ), 'impressum_page_id' );
	}

	public function create_or_update_privacy_page( string $content ): int {
		$settings = get_option( 'frg_settings', array() );
		$title    = ! empty( $settings['privacy_page'] ) ? sanitize_text_field( $settings['privacy_page'] ) : __( 'Datenschutzerklärung', 'frontend-rechtstexte-generator' );

		return $this->upsert_page( $title, wp_kses_post( $content ), 'privacy_page_id' );
	}

	private function upsert_page( string $title, string $content, string $setting_key ): int {
		$settings = get_option( 'frg_settings', array() );
		$page_id  = isset( $settings[ $setting_key ] ) ? absint( $settings[ $setting_key ] ) : 0;
		if ( $page_id < 1 ) {
			$page_id = $this->find_existing_page_id_by_title( $title );
		}
		$postarr  = array(
			'post_title'   => $title,
			'post_content' => $content,
			'post_status'  => 'publish',
			'post_type'    => 'page',
		);

		if ( $page_id > 0 && 'page' === get_post_type( $page_id ) ) {
			$postarr['ID'] = $page_id;
			$result        = wp_update_post( wp_slash( $postarr ), true );
		} else {
			$result = wp_insert_post( wp_slash( $postarr ), true );
		}

		if ( is_wp_error( $result ) ) {
			return 0;
		}

		$settings[ $setting_key ] = (int) $result;
		update_option( 'frg_settings', $settings );

		return (int) $result;
	}

	private function find_existing_page_id_by_title( string $title ): int {
		global $wpdb;

		$page_id = $wpdb->get_var(
			$wpdb->prepare(
				"SELECT ID FROM {$wpdb->posts} WHERE post_type = 'page' AND post_status IN ('publish', 'draft', 'private', 'pending') AND post_title = %s ORDER BY ID ASC LIMIT 1",
				$title
			)
		);

		return absint( $page_id );
	}
}

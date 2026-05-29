<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class FRG_Scanner {
	private const TRANSIENT_KEY = 'frg_scanner_result';
	private const CACHE_TTL = 43200;

	public function get_scan_results(): array {
		$cached = get_transient( self::TRANSIENT_KEY );
		if ( is_array( $cached ) ) {
			return $cached;
		}

		$result = array(
			'detected' => array(),
			'errors'   => array(),
		);

		try {
			$result['detected'] = array_merge(
				$this->scan_active_plugins(),
				$this->scan_homepage_html( $result['errors'] )
			);
		} catch ( Throwable $exception ) {
			$result['errors'][] = __( 'Der Scanner konnte nicht vollstaendig ausgefuehrt werden.', 'frontend-rechtstexte-generator' );
		}

		$result['detected'] = $this->normalize_detected( $result['detected'] );

		set_transient( self::TRANSIENT_KEY, $result, self::CACHE_TTL );

		return $result;
	}

	private function scan_active_plugins(): array {
		$active_plugins = get_option( 'active_plugins', array() );
		if ( ! is_array( $active_plugins ) ) {
			return array();
		}

		$plugin_map = array(
			'elementor/elementor.php'                                   => array( 'key' => 'elementor', 'label' => 'Elementor', 'source' => 'plugin', 'adoptable' => true ),
			'woocommerce/woocommerce.php'                               => array( 'key' => 'shop', 'label' => 'WooCommerce / Online-Shop', 'source' => 'plugin', 'group' => 'features', 'adoptable' => true ),
			'contact-form-7/wp-contact-form-7.php'                      => array( 'key' => 'contact_form_7', 'label' => 'Contact Form 7', 'source' => 'plugin', 'adoptable' => true ),
			'gravityforms/gravityforms.php'                             => array( 'key' => 'gravity_forms', 'label' => 'Gravity Forms', 'source' => 'plugin', 'adoptable' => true ),
			'wpforms-lite/wpforms.php'                                  => array( 'key' => 'wpforms', 'label' => 'WPForms', 'source' => 'plugin', 'adoptable' => true ),
			'wpforms/wpforms.php'                                       => array( 'key' => 'wpforms', 'label' => 'WPForms', 'source' => 'plugin', 'adoptable' => true ),
			'borlabs-cookie/borlabs-cookie.php'                         => array( 'key' => 'borlabs_cookie', 'label' => 'Borlabs Cookie', 'source' => 'plugin', 'adoptable' => true ),
			'real-cookie-banner/real-cookie-banner.php'                 => array( 'key' => 'real_cookie_banner', 'label' => 'Real Cookie Banner', 'source' => 'plugin', 'adoptable' => true ),
			'complianz-gdpr/complianz-gpdr.php'                         => array( 'key' => 'complianz', 'label' => 'Complianz', 'source' => 'plugin', 'adoptable' => true ),
			'complianz-gdpr/complianz-gdpr.php'                         => array( 'key' => 'complianz', 'label' => 'Complianz', 'source' => 'plugin', 'adoptable' => true ),
			'wordfence/wordfence.php'                                   => array( 'key' => 'wordfence', 'label' => 'Wordfence', 'source' => 'plugin', 'adoptable' => true ),
			'wpvivid-backuprestore/wpvivid-backuprestore.php'           => array( 'key' => 'wpvivid', 'label' => 'WPvivid', 'source' => 'plugin', 'adoptable' => true ),
			'updraftplus/updraftplus.php'                               => array( 'key' => 'updraftplus', 'label' => 'UpdraftPlus', 'source' => 'plugin', 'adoptable' => true ),
			'seo-by-rank-math/rank-math.php'                            => array( 'key' => 'rank_math', 'label' => 'Rank Math', 'source' => 'plugin', 'adoptable' => false ),
			'wordpress-seo/wp-seo.php'                                  => array( 'key' => 'yoast_seo', 'label' => 'Yoast SEO', 'source' => 'plugin', 'adoptable' => false ),
		);

		$detected = array();
		foreach ( $active_plugins as $plugin_file ) {
			if ( isset( $plugin_map[ $plugin_file ] ) ) {
				$detected[] = $plugin_map[ $plugin_file ];
			}
		}

		return $detected;
	}

	private function scan_homepage_html( array &$errors ): array {
		$detected = array();
		$response = wp_safe_remote_get(
			home_url( '/' ),
			array(
				'timeout'     => 4,
				'redirection' => 2,
				'user-agent'  => 'FRG-Scanner/' . FRG_VERSION . '; ' . home_url( '/' ),
			)
		);

		if ( is_wp_error( $response ) ) {
			$errors[] = __( 'Die Startseite konnte fuer den Scanner nicht geladen werden.', 'frontend-rechtstexte-generator' );
			return $detected;
		}

		$body    = (string) wp_remote_retrieve_body( $response );
		$headers = wp_remote_retrieve_headers( $response );
		$haystack = strtolower( $body );

		$patterns = array(
			array( 'key' => 'google_fonts_external', 'label' => 'Google Fonts extern', 'needle' => 'fonts.googleapis.com', 'source' => 'html' ),
			array( 'key' => 'google_maps', 'label' => 'Google Maps', 'needle' => 'maps.googleapis.com', 'source' => 'html' ),
			array( 'key' => 'google_maps', 'label' => 'Google Maps', 'needle' => 'www.google.com/maps', 'source' => 'html' ),
			array( 'key' => 'youtube', 'label' => 'YouTube', 'needle' => 'youtube.com', 'source' => 'html' ),
			array( 'key' => 'youtube', 'label' => 'YouTube', 'needle' => 'youtu.be', 'source' => 'html' ),
			array( 'key' => 'vimeo', 'label' => 'Vimeo', 'needle' => 'player.vimeo.com', 'source' => 'html' ),
			array( 'key' => 'google_tag_manager', 'label' => 'Google Tag Manager', 'needle' => 'googletagmanager.com', 'source' => 'html' ),
			array( 'key' => 'google_analytics', 'label' => 'Google Analytics', 'needle' => 'google-analytics.com', 'source' => 'html' ),
			array( 'key' => 'google_analytics', 'label' => 'Google Analytics', 'needle' => 'gtag(', 'source' => 'html' ),
			array( 'key' => 'meta_pixel', 'label' => 'Meta Pixel', 'needle' => 'connect.facebook.net', 'source' => 'html' ),
			array( 'key' => 'meta_pixel', 'label' => 'Meta Pixel', 'needle' => 'fbq(', 'source' => 'html' ),
			array( 'key' => 'recaptcha', 'label' => 'reCAPTCHA', 'needle' => 'www.google.com/recaptcha', 'source' => 'html' ),
			array( 'key' => 'hcaptcha', 'label' => 'hCaptcha', 'needle' => 'hcaptcha.com', 'source' => 'html' ),
		);

		foreach ( $patterns as $pattern ) {
			if ( false !== strpos( $haystack, strtolower( $pattern['needle'] ) ) ) {
				$detected[] = $pattern;
			}
		}

		$server_header = strtolower( (string) $headers['server'] );
		$all_headers   = strtolower( wp_json_encode( $headers ) ?: '' );

		if ( false !== strpos( $haystack, 'cdnjs.cloudflare.com' ) || false !== strpos( $haystack, '/cdn-cgi/' ) || false !== strpos( $server_header, 'cloudflare' ) || false !== strpos( $all_headers, 'cf-ray' ) ) {
			$detected[] = array(
				'key'    => 'cloudflare',
				'label'  => 'Cloudflare',
				'source' => 'html',
			);
		}

		return $detected;
	}

	private function normalize_detected( array $detected ): array {
		$normalized = array();

		foreach ( $detected as $item ) {
			if ( empty( $item['key'] ) ) {
				continue;
			}

			$key = sanitize_key( $item['key'] );
			if ( isset( $normalized[ $key ] ) ) {
				continue;
			}

			$normalized[ $key ] = array(
				'key'    => $key,
				'label'  => sanitize_text_field( $item['label'] ?? $key ),
				'source' => sanitize_key( $item['source'] ?? 'scan' ),
				'group'  => isset( $item['group'] ) && 'features' === $item['group'] ? 'features' : 'services',
				'adoptable' => isset( $item['adoptable'] ) ? (bool) $item['adoptable'] : true,
			);
		}

		return array_values( $normalized );
	}
}

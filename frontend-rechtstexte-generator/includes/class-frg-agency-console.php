<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class FRG_Agency_Console {
	private const CACHE_OPTION = 'frg_agency_console_cache';
	private const OWN_REGISTRY_OPTION = 'frg_agency_own_block_registry';

	private FRG_Generator $generator;
	private FRG_Block_Feed $block_feed;

	public function __construct( FRG_Generator $generator, FRG_Block_Feed $block_feed ) {
		$this->generator  = $generator;
		$this->block_feed = $block_feed;
	}

	public function register_menu(): void {
		$settings = get_option( 'frg_settings', array() );
		$state    = FRG_Block_Feed::get_state();
		if ( 'client' !== ( $settings['block_feed_mode'] ?? 'off' ) || 'agency' !== ( $state['license_type'] ?? 'site' ) ) {
			return;
		}

		add_submenu_page(
			'frg-settings',
			__( 'Agentur-Kunden', 'frontend-rechtstexte-generator' ),
			__( 'Agentur-Kunden', 'frontend-rechtstexte-generator' ),
			'manage_options',
			'frg-agency-customers',
			array( $this, 'render_page' )
		);
	}

	public function render_page(): void {
		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}

		$notice_key = 'frg_agency_notice_' . get_current_user_id();
		$notice     = get_transient( $notice_key );
		if ( is_array( $notice ) && ! empty( $notice['message'] ) ) {
			add_settings_error( 'frg_agency_messages', 'agency_action_result', $notice['message'], $notice['type'] ?? 'updated' );
			delete_transient( $notice_key );
		}

		$result = $this->request( 'GET' );
		$error  = '';
		if ( is_wp_error( $result ) ) {
			$error  = $result->get_error_message();
			$result = get_option( self::CACHE_OPTION, array() );
		} else {
			update_option( self::CACHE_OPTION, $result, false );
		}
		$agency_data = is_array( $result ) ? $result : array();
		$settings    = get_option( 'frg_settings', array() );
		$feed_endpoint = $this->build_feed_endpoint( (string) ( $settings['block_feed_url'] ?? '' ) );

		include FRG_PLUGIN_DIR . 'templates/admin-agency-console.php';
	}

	public function handle_request(): void {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( 'Sie dürfen keine Kundenschlüssel verwalten.', 'frontend-rechtstexte-generator' ) );
		}

		$operation = sanitize_key( wp_unslash( $_POST['frg_agency_operation'] ?? '' ) );
		if ( ! in_array( $operation, array( 'create', 'status', 'source' ), true ) ) {
			wp_die( esc_html__( 'Unbekannte Agenturaktion.', 'frontend-rechtstexte-generator' ) );
		}
		check_admin_referer( 'frg_agency_' . $operation . '_action', 'frg_agency_nonce' );

		if ( 'create' === $operation ) {
			$result = $this->request(
				'POST',
				array(
					'customer_name'  => sanitize_text_field( wp_unslash( $_POST['customer_name'] ?? '' ) ),
					'customer_email' => sanitize_email( wp_unslash( $_POST['customer_email'] ?? '' ) ),
				)
			);
			$success_message = __( 'Der Kundenschlüssel wurde erstellt.', 'frontend-rechtstexte-generator' );
		} elseif ( 'status' === $operation ) {
			$result = $this->request(
				'POST',
				array( 'status' => sanitize_key( wp_unslash( $_POST['license_status'] ?? '' ) ) ),
				absint( $_POST['license_id'] ?? 0 )
			);
			$success_message = __( 'Der Status des Kundenschlüssels wurde geändert.', 'frontend-rechtstexte-generator' );
		} else {
			$source_mode = sanitize_key( wp_unslash( $_POST['source_mode'] ?? 'master' ) );
			if ( ! in_array( $source_mode, array( 'master', 'agency' ), true ) ) {
				$source_mode = 'master';
			}

			$current_settings = get_option( 'frg_settings', array() );
			$current_own_mode = array_key_exists( 'agency_master_sync_enabled', $current_settings )
				? empty( $current_settings['agency_master_sync_enabled'] )
				: false;
			$registry_to_activate = array();
			$blocks_to_publish    = array();
			if ( 'master' === $source_mode && $current_own_mode ) {
				update_option( self::OWN_REGISTRY_OPTION, $this->generator->get_block_registry(), false );
			}
			if ( 'agency' === $source_mode ) {
				$registry_to_activate = $this->generator->get_block_registry();
				if ( ! $current_own_mode ) {
					$own_registry = get_option( self::OWN_REGISTRY_OPTION, array() );
					if ( is_array( $own_registry ) && ! empty( $own_registry ) ) {
						$current_registry     = $registry_to_activate;
						$registry_to_activate = $own_registry;
						$this->generator->save_block_registry( $registry_to_activate );
						$blocks_to_publish = $this->build_public_blocks();
						$this->generator->save_block_registry( $current_registry );
					}
				}
				if ( empty( $blocks_to_publish ) ) {
					$blocks_to_publish = $this->build_public_blocks();
				}
			}

			$result = $this->request(
				'POST',
				array(
					'source_mode'   => $source_mode,
					'module_version'=> sanitize_text_field( $this->generator->get_module_meta()['module_version'] ?? '' ),
					'blocks'        => $blocks_to_publish,
				),
				0,
				'text-source'
			);
			$success_message = 'agency' === $source_mode
				? __( 'Die Master-Synchronisierung wurde deaktiviert und Ihr eigener Textstand für die Kunden-Websites veröffentlicht.', 'frontend-rechtstexte-generator' )
				: __( 'Die Master-Synchronisierung wurde aktiviert. Ihre Kunden-Websites erhalten wieder die freigegebenen Master-Texte.', 'frontend-rechtstexte-generator' );
		}

		if ( is_wp_error( $result ) ) {
			$notice = array( 'type' => 'error', 'message' => $result->get_error_message() );
		} else {
			update_option( self::CACHE_OPTION, $result, false );
			if ( 'source' === $operation ) {
				$settings = get_option( 'frg_settings', array() );
				$settings['agency_master_sync_enabled'] = 'master' === $source_mode;
				update_option( 'frg_settings', $settings );
				if ( 'master' === $source_mode ) {
					$this->block_feed->sync_now();
				} else {
					if ( ! empty( $registry_to_activate ) ) {
						$this->generator->save_block_registry( $registry_to_activate );
					}
					wp_clear_scheduled_hook( 'frg_sync_remote_block_feed' );
					update_option( self::OWN_REGISTRY_OPTION, $this->generator->get_block_registry(), false );
				}
			}
			$notice = array( 'type' => 'updated', 'message' => $success_message );
		}
		set_transient( 'frg_agency_notice_' . get_current_user_id(), $notice, MINUTE_IN_SECONDS );
		wp_safe_redirect( admin_url( 'admin.php?page=frg-agency-customers' ) );
		exit;
	}

	private function request( string $method, array $body = array(), int $license_id = 0, string $resource = 'licenses' ) {
		$settings = get_option( 'frg_settings', array() );
		$url      = $this->build_api_url( (string) ( $settings['block_feed_url'] ?? '' ), $license_id, $resource );
		$key      = sanitize_text_field( $settings['block_feed_key'] ?? '' );
		if ( '' === $url || '' === $key ) {
			return new WP_Error( 'frg_agency_connection_missing', __( 'Feed-URL oder Agenturschlüssel fehlt.', 'frontend-rechtstexte-generator' ) );
		}
		if ( 'https' !== strtolower( (string) wp_parse_url( $url, PHP_URL_SCHEME ) ) ) {
			return new WP_Error( 'frg_agency_https_required', __( 'Die Agenturverwaltung ist nur über HTTPS verfügbar.', 'frontend-rechtstexte-generator' ) );
		}

		$args = array(
			'timeout'     => 10,
			'redirection' => 0,
			'headers'     => array(
				'Accept'             => 'application/json',
				'X-FRG-Agency-Key'   => $key,
				'X-FRG-Site-URL'     => home_url( '/' ),
			),
		);
		$response = 'POST' === $method
			? wp_safe_remote_post(
				$url,
				array_merge(
					$args,
					'text-source' === $resource
						? array( 'body' => wp_json_encode( $body ), 'headers' => array_merge( $args['headers'], array( 'Content-Type' => 'application/json' ) ) )
						: array( 'body' => $body )
				)
			)
			: wp_safe_remote_get( $url, $args );
		if ( is_wp_error( $response ) ) {
			return $response;
		}

		$payload = json_decode( (string) wp_remote_retrieve_body( $response ), true );
		$status  = wp_remote_retrieve_response_code( $response );
		if ( $status < 200 || $status >= 300 ) {
			return new WP_Error(
				'frg_agency_remote_error',
				is_array( $payload ) && ! empty( $payload['message'] ) ? sanitize_text_field( $payload['message'] ) : __( 'Die Zentrale hat die Anfrage abgelehnt.', 'frontend-rechtstexte-generator' )
			);
		}
		if ( ! is_array( $payload ) || ! isset( $payload['agency'], $payload['licenses'] ) || ! is_array( $payload['licenses'] ) ) {
			return new WP_Error( 'frg_agency_invalid_response', __( 'Die Zentrale hat keine gültigen Agenturdaten geliefert.', 'frontend-rechtstexte-generator' ) );
		}

		return $payload;
	}

	private function build_api_url( string $feed_url, int $license_id = 0, string $resource = 'licenses' ): string {
		$feed_url = esc_url_raw( trim( $feed_url ) );
		if ( '' === $feed_url ) {
			return '';
		}

		$base = false !== strpos( $feed_url, '/wp-json/' )
			? preg_replace( '#/wp-json/.*$#', '', $feed_url )
			: untrailingslashit( $feed_url );
		$path = 'text-source' === $resource ? '/wp-json/frg/v1/agency/text-source' : '/wp-json/frg/v1/agency/licenses';
		if ( $license_id > 0 ) {
			$path .= '/' . $license_id . '/status';
		}

		return esc_url_raw( $base . $path );
	}

	private function build_feed_endpoint( string $feed_url ): string {
		$feed_url = esc_url_raw( trim( $feed_url ) );
		if ( '' === $feed_url ) {
			return '';
		}
		if ( false !== strpos( $feed_url, '/wp-json/' ) ) {
			$base = preg_replace( '#/wp-json/.*$#', '', $feed_url );
			return esc_url_raw( $base . '/wp-json/frg/v1/block-feed' );
		}
		return esc_url_raw( trailingslashit( $feed_url ) . 'wp-json/frg/v1/block-feed' );
	}

	private function build_public_blocks(): array {
		$public = array();
		foreach ( $this->generator->get_block_registry() as $key => $block ) {
			$public[ $key ] = array(
				'published_text'         => $this->generator->get_distributable_block_text( $key ),
				'published_compact_text' => $this->generator->get_distributable_compact_block_text( $key ),
				'legal_basis'            => array_values( array_map( 'sanitize_text_field', $block['legal_basis'] ?? array() ) ),
			);
		}
		return $public;
	}
}

<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class FRG_Block_Feed {
	private const REST_NAMESPACE = 'frg/v1';
	private const REST_ROUTE = '/block-feed';
	private const CRON_HOOK = 'frg_sync_remote_block_feed';
	private const STATE_OPTION = 'frg_block_feed_state';
	private const BACKUP_OPTION = 'frg_block_feed_registry_backup';

	private FRG_Generator $generator;

	public function __construct( FRG_Generator $generator ) {
		$this->generator = $generator;
	}

	public function register_routes(): void {
		register_rest_route(
			self::REST_NAMESPACE,
			self::REST_ROUTE,
			array(
				'methods'             => WP_REST_Server::READABLE,
				'callback'            => array( $this, 'serve_feed' ),
				'permission_callback' => array( $this, 'authorize_feed_request' ),
			)
		);
	}

	public function authorize_feed_request( WP_REST_Request $request ) {
		$settings = get_option( 'frg_settings', array() );
		$expected = (string) ( $settings['block_feed_key'] ?? '' );
		$provided = (string) $request->get_header( 'x-frg-feed-key' );

		if ( 'hub' !== ( $settings['block_feed_mode'] ?? 'off' ) || '' === $expected || '' === $provided || ! hash_equals( $expected, $provided ) ) {
			return new WP_Error(
				'frg_feed_unauthorized',
				__( 'Der Zugriff auf diesen Textbaustein-Feed ist nicht erlaubt.', 'frontend-rechtstexte-generator' ),
				array( 'status' => 401 )
			);
		}

		return true;
	}

	public function serve_feed(): WP_REST_Response {
		$blocks  = $this->build_public_blocks();
		$meta    = $this->generator->get_module_meta();
		$payload = array(
			'schema_version' => 1,
			'feed_version'   => hash( 'sha256', (string) wp_json_encode( $blocks ) ),
			'published_at'   => current_time( 'c' ),
			'source_url'     => home_url( '/' ),
			'plugin_version' => defined( 'FRG_VERSION' ) ? FRG_VERSION : '',
			'module_version' => sanitize_text_field( $meta['module_version'] ?? '' ),
			'blocks'         => $blocks,
		);

		$response = new WP_REST_Response( $payload, 200 );
		$response->header( 'Cache-Control', 'no-store, private' );

		return $response;
	}

	public function maybe_schedule_sync(): void {
		$settings  = get_option( 'frg_settings', array() );
		$should_run = 'client' === ( $settings['block_feed_mode'] ?? 'off' ) && ! empty( $settings['block_feed_auto_sync'] );
		$scheduled  = wp_next_scheduled( self::CRON_HOOK );

		if ( $should_run && ! $scheduled ) {
			wp_schedule_event( time() + MINUTE_IN_SECONDS, 'daily', self::CRON_HOOK );
		} elseif ( ! $should_run && $scheduled ) {
			wp_clear_scheduled_hook( self::CRON_HOOK );
		}
	}

	public function run_scheduled_sync(): void {
		$this->sync_now();
	}

	public function sync_now() {
		$settings = get_option( 'frg_settings', array() );
		if ( 'client' !== ( $settings['block_feed_mode'] ?? 'off' ) ) {
			return new WP_Error( 'frg_feed_not_client', __( 'Diese Website ist nicht als Empfänger eingerichtet.', 'frontend-rechtstexte-generator' ) );
		}

		$endpoint = $this->build_endpoint_url( (string) ( $settings['block_feed_url'] ?? '' ) );
		$key      = (string) ( $settings['block_feed_key'] ?? '' );
		if ( '' === $endpoint || '' === $key ) {
			return $this->record_error( __( 'Feed-URL oder Verbindungsschlüssel fehlt.', 'frontend-rechtstexte-generator' ) );
		}
		if ( 'https' !== strtolower( (string) wp_parse_url( $endpoint, PHP_URL_SCHEME ) ) ) {
			return $this->record_error( __( 'Die Textbaustein-Synchronisierung ist nur über eine HTTPS-Verbindung erlaubt.', 'frontend-rechtstexte-generator' ) );
		}

		$response = wp_safe_remote_get(
			$endpoint,
			array(
				'timeout'     => 10,
				'redirection' => 0,
				'limit_response_size' => 2 * MB_IN_BYTES,
				'headers'     => array(
					'Accept'         => 'application/json',
					'X-FRG-Feed-Key' => $key,
				),
			)
		);

		if ( is_wp_error( $response ) ) {
			return $this->record_error( $response->get_error_message() );
		}

		$status_code = wp_remote_retrieve_response_code( $response );
		if ( 200 !== $status_code ) {
			return $this->record_error(
				sprintf(
					/* translators: %d: HTTP status code */
					__( 'Die Zentrale antwortete mit HTTP-Status %d.', 'frontend-rechtstexte-generator' ),
					$status_code
				)
			);
		}

		$payload = json_decode( (string) wp_remote_retrieve_body( $response ), true );
		if ( ! $this->is_valid_payload( $payload ) ) {
			return $this->record_error( __( 'Die Zentrale hat kein gültiges Textbaustein-Paket geliefert.', 'frontend-rechtstexte-generator' ) );
		}

		$current_registry = $this->generator->get_block_registry();
		update_option( self::BACKUP_OPTION, $current_registry, false );
		$this->generator->save_block_registry( $this->merge_remote_blocks( $current_registry, $payload['blocks'] ) );

		$state = array(
			'last_checked_at' => current_time( 'mysql' ),
			'last_success_at' => current_time( 'mysql' ),
			'last_error'      => '',
			'feed_version'    => sanitize_text_field( $payload['feed_version'] ),
			'module_version'  => sanitize_text_field( $payload['module_version'] ?? '' ),
			'source_url'      => esc_url_raw( $payload['source_url'] ?? $endpoint ),
			'block_count'     => count( $payload['blocks'] ),
		);
		update_option( self::STATE_OPTION, $state, false );

		return $state;
	}

	public static function get_state(): array {
		$state = get_option( self::STATE_OPTION, array() );
		return is_array( $state ) ? $state : array();
	}

	public static function get_endpoint_url(): string {
		return rest_url( self::REST_NAMESPACE . self::REST_ROUTE );
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

	private function merge_remote_blocks( array $local, array $remote ): array {
		foreach ( $local as $key => $block ) {
			if ( empty( $remote[ $key ] ) || ! is_array( $remote[ $key ] ) ) {
				continue;
			}

			$remote_block = $remote[ $key ];
			$local[ $key ]['override_text']         = wp_kses_post( $remote_block['published_text'] ?? '' );
			$local[ $key ]['compact_override_text'] = wp_kses_post( $remote_block['published_compact_text'] ?? '' );
			$local[ $key ]['legal_basis']           = is_array( $remote_block['legal_basis'] ?? null ) ? $remote_block['legal_basis'] : $block['legal_basis'];
		}

		return $local;
	}

	private function is_valid_payload( $payload ): bool {
		if ( ! is_array( $payload ) || 1 !== (int) ( $payload['schema_version'] ?? 0 ) || empty( $payload['feed_version'] ) || ! is_array( $payload['blocks'] ?? null ) ) {
			return false;
		}

		$known_blocks = $this->generator->get_block_registry();
		$known_count  = 0;
		foreach ( $payload['blocks'] as $key => $block ) {
			if ( ! isset( $known_blocks[ sanitize_key( (string) $key ) ] ) ) {
				continue;
			}
			++$known_count;
			if ( ! is_array( $block ) || ! array_key_exists( 'published_text', $block ) || ! is_string( $block['published_text'] ) ) {
				return false;
			}
		}

		return $known_count > 0;
	}

	private function build_endpoint_url( string $url ): string {
		$url = esc_url_raw( trim( $url ) );
		if ( '' === $url ) {
			return '';
		}

		if ( false !== strpos( $url, '/wp-json/' ) ) {
			return $url;
		}

		return trailingslashit( $url ) . 'wp-json/' . self::REST_NAMESPACE . self::REST_ROUTE;
	}

	private function record_error( string $message ): WP_Error {
		$state = self::get_state();
		$state['last_checked_at'] = current_time( 'mysql' );
		$state['last_error']      = sanitize_text_field( $message );
		update_option( self::STATE_OPTION, $state, false );

		return new WP_Error( 'frg_feed_sync_failed', $message );
	}
}

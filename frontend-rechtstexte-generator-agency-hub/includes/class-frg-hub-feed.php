<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class FRG_Hub_Feed {
	private const REST_NAMESPACE = 'frg/v1';
	private const REST_ROUTE = '/block-feed';
	private const AGENCY_SOURCES_OPTION = 'frg_agency_text_sources';

	private FRG_Generator $generator;
	private FRG_License_Manager $license_manager;
	private ?array $authorized_license = null;
	private ?array $authorized_agency = null;

	public function __construct( FRG_Generator $generator, FRG_License_Manager $license_manager ) {
		$this->generator       = $generator;
		$this->license_manager = $license_manager;
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
		register_rest_route(
			self::REST_NAMESPACE,
			'/agency/licenses',
			array(
				array(
					'methods'             => WP_REST_Server::READABLE,
					'callback'            => array( $this, 'serve_agency_licenses' ),
					'permission_callback' => array( $this, 'authorize_agency_request' ),
				),
				array(
					'methods'             => WP_REST_Server::CREATABLE,
					'callback'            => array( $this, 'create_agency_license' ),
					'permission_callback' => array( $this, 'authorize_agency_request' ),
				),
			)
		);
		register_rest_route(
			self::REST_NAMESPACE,
			'/agency/licenses/(?P<license_id>\d+)/status',
			array(
				'methods'             => WP_REST_Server::CREATABLE,
				'callback'            => array( $this, 'update_agency_license_status' ),
				'permission_callback' => array( $this, 'authorize_agency_request' ),
			)
		);
		register_rest_route(
			self::REST_NAMESPACE,
			'/agency/text-source',
			array(
				'methods'             => WP_REST_Server::CREATABLE,
				'callback'            => array( $this, 'update_agency_text_source' ),
				'permission_callback' => array( $this, 'authorize_agency_request' ),
			)
		);
	}

	public function authorize_feed_request( WP_REST_Request $request ) {
		$this->authorized_license = null;
		$settings = get_option( 'frg_settings', array() );
		if ( 'hub' !== ( $settings['block_feed_mode'] ?? 'off' ) ) {
			return $this->unauthorized_error();
		}

		$provided = (string) $request->get_header( 'x-frg-license-key' );
		if ( '' === $provided ) {
			$provided = (string) $request->get_header( 'x-frg-feed-key' );
		}

		if ( '' !== $provided ) {
			$license = $this->license_manager->authorize_site(
				$provided,
				(string) $request->get_header( 'x-frg-site-url' ),
				(string) $request->get_header( 'x-frg-plugin-version' )
			);
			if ( ! is_wp_error( $license ) ) {
				$this->authorized_license = $license;
				return true;
			}
			if ( 'frg_license_not_found' !== $license->get_error_code() ) {
				return $license;
			}
		}

		$legacy_enabled = array_key_exists( 'block_feed_legacy_access', $settings )
			? ! empty( $settings['block_feed_legacy_access'] )
			: ! empty( $settings['block_feed_key'] );
		$legacy_key = (string) ( $settings['block_feed_key'] ?? '' );
		if ( $legacy_enabled && '' !== $legacy_key && '' !== $provided && hash_equals( $legacy_key, $provided ) ) {
			return true;
		}

		return $this->unauthorized_error();
	}

	public function serve_feed(): WP_REST_Response {
		$source  = $this->resolve_feed_source();
		$blocks  = $source['blocks'];
		$meta    = $this->generator->get_module_meta();
		$payload = array(
			'schema_version' => 1,
			'feed_version'   => hash( 'sha256', (string) wp_json_encode( $blocks ) ),
			'published_at'   => current_time( 'c' ),
			'source_url'     => home_url( '/' ),
			'plugin_version' => defined( 'FRG_VERSION' ) ? FRG_VERSION : '',
			'module_version' => sanitize_text_field( $source['module_version'] ?: ( $meta['module_version'] ?? '' ) ),
			'text_source'    => sanitize_key( $source['mode'] ),
			'blocks'         => $blocks,
		);
		if ( $this->authorized_license ) {
			$payload['license'] = array(
				'expires_at' => sanitize_text_field( $this->authorized_license['expires_at'] ?? '' ),
				'max_sites'  => absint( $this->authorized_license['max_sites'] ?? 0 ),
				'license_type' => sanitize_key( $this->authorized_license['license_type'] ?? 'site' ),
				'customer_name' => sanitize_text_field( $this->authorized_license['customer_name'] ?? '' ),
			);
		}

		$response = new WP_REST_Response( $payload, 200 );
		$response->header( 'Cache-Control', 'no-store, private' );
		return $response;
	}

	public function authorize_agency_request( WP_REST_Request $request ) {
		$this->authorized_agency = null;
		$settings = get_option( 'frg_settings', array() );
		if ( 'hub' !== ( $settings['block_feed_mode'] ?? 'off' ) ) {
			return $this->unauthorized_error();
		}

		$agency = $this->license_manager->authorize_agency( (string) $request->get_header( 'x-frg-agency-key' ) );
		if ( is_wp_error( $agency ) ) {
			return $agency;
		}

		$this->authorized_agency = $agency;
		return true;
	}

	public function serve_agency_licenses(): WP_REST_Response {
		return new WP_REST_Response( $this->build_agency_payload(), 200 );
	}

	public function create_agency_license( WP_REST_Request $request ) {
		$result = $this->license_manager->create_child_license(
			(int) $this->authorized_agency['id'],
			array(
				'customer_name'  => $request->get_param( 'customer_name' ),
				'customer_email' => $request->get_param( 'customer_email' ),
			)
		);
		if ( is_wp_error( $result ) ) {
			return $result;
		}

		return new WP_REST_Response( $this->build_agency_payload(), 201 );
	}

	public function update_agency_license_status( WP_REST_Request $request ) {
		$status = sanitize_key( (string) $request->get_param( 'status' ) );
		$updated = $this->license_manager->set_child_status(
			(int) $this->authorized_agency['id'],
			absint( $request->get_param( 'license_id' ) ),
			$status
		);
		if ( ! $updated ) {
			return new WP_Error( 'frg_child_update_failed', __( 'Der Kundenschlüssel konnte nicht geändert werden.', 'frontend-rechtstexte-generator' ), array( 'status' => 400 ) );
		}

		return new WP_REST_Response( $this->build_agency_payload(), 200 );
	}

	public function update_agency_text_source( WP_REST_Request $request ) {
		$mode = sanitize_key( (string) $request->get_param( 'source_mode' ) );
		if ( ! in_array( $mode, array( 'master', 'agency' ), true ) ) {
			return new WP_Error( 'frg_agency_source_invalid', __( 'Bitte eine gültige Textquelle auswählen.', 'frontend-rechtstexte-generator' ), array( 'status' => 400 ) );
		}

		$blocks = array();
		if ( 'agency' === $mode ) {
			$blocks = $this->sanitize_agency_blocks( $request->get_param( 'blocks' ) );
			if ( is_wp_error( $blocks ) ) {
				return $blocks;
			}
		}

		$sources = $this->get_agency_sources();
		$sources[ (int) $this->authorized_agency['id'] ] = array(
			'mode'           => $mode,
			'blocks'         => $blocks,
			'feed_version'   => 'agency' === $mode ? hash( 'sha256', (string) wp_json_encode( $blocks ) ) : '',
			'module_version' => sanitize_text_field( (string) $request->get_param( 'module_version' ) ),
			'published_at'   => current_time( 'mysql' ),
		);
		update_option( self::AGENCY_SOURCES_OPTION, $sources, false );

		return new WP_REST_Response( $this->build_agency_payload(), 200 );
	}

	private function build_agency_payload(): array {
		$source = $this->get_agency_source( (int) $this->authorized_agency['id'] );
		$licenses = array();
		foreach ( $this->license_manager->get_child_licenses( (int) $this->authorized_agency['id'] ) as $license ) {
			$site = $license['sites'][0] ?? array();
			$licenses[] = array(
				'id'             => (int) $license['id'],
				'customer_name'  => sanitize_text_field( $license['customer_name'] ),
				'customer_email' => sanitize_email( $license['customer_email'] ),
				'license_key'    => sanitize_text_field( $license['license_key'] ),
				'status'         => sanitize_key( $license['effective_status'] ),
				'expires_at'     => sanitize_text_field( $license['expires_at'] ),
				'site_url'       => esc_url_raw( $site['site_url'] ?? '' ),
				'last_seen_at'   => sanitize_text_field( $site['last_seen_at'] ?? '' ),
			);
		}

		return array(
			'agency' => array(
				'customer_name' => sanitize_text_field( $this->authorized_agency['customer_name'] ),
				'max_sites'     => (int) $this->authorized_agency['max_sites'],
				'expires_at'    => sanitize_text_field( $this->authorized_agency['expires_at'] ),
				'text_source'   => sanitize_key( $source['mode'] ),
				'published_at'  => sanitize_text_field( $source['published_at'] ),
				'feed_version'  => sanitize_text_field( $source['feed_version'] ),
			),
			'licenses' => $licenses,
			'used_sites' => count( array_filter( $licenses, static fn( array $license ): bool => 'blocked' !== $license['status'] ) ),
		);
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

	private function resolve_feed_source(): array {
		$master_blocks = $this->build_public_blocks();
		if ( ! $this->authorized_license || empty( $this->authorized_license['parent_license_id'] ) ) {
			return array( 'mode' => 'master', 'blocks' => $master_blocks, 'module_version' => '' );
		}

		$source = $this->get_agency_source( (int) $this->authorized_license['parent_license_id'] );
		if ( 'agency' !== $source['mode'] || empty( $source['blocks'] ) ) {
			return array( 'mode' => 'master', 'blocks' => $master_blocks, 'module_version' => '' );
		}

		return array(
			'mode'           => 'agency',
			'blocks'         => $source['blocks'],
			'module_version' => $source['module_version'],
		);
	}

	private function sanitize_agency_blocks( $raw ) {
		if ( ! is_array( $raw ) ) {
			return new WP_Error( 'frg_agency_blocks_missing', __( 'Es wurde kein gültiges Textbaustein-Paket übertragen.', 'frontend-rechtstexte-generator' ), array( 'status' => 400 ) );
		}

		$known     = $this->generator->get_block_registry();
		$sanitized = array();
		foreach ( $raw as $key => $block ) {
			$key = sanitize_key( (string) $key );
			if ( ! isset( $known[ $key ] ) || ! is_array( $block ) || ! is_string( $block['published_text'] ?? null ) ) {
				continue;
			}
			$sanitized[ $key ] = array(
				'published_text'         => wp_kses_post( $block['published_text'] ),
				'published_compact_text' => wp_kses_post( is_string( $block['published_compact_text'] ?? null ) ? $block['published_compact_text'] : '' ),
				'legal_basis'            => array_values( array_map( 'sanitize_text_field', is_array( $block['legal_basis'] ?? null ) ? $block['legal_basis'] : array() ) ),
			);
		}

		if ( count( $sanitized ) !== count( $known ) ) {
			return new WP_Error( 'frg_agency_blocks_incomplete', __( 'Das Textbaustein-Paket ist unvollständig oder nicht mit der Zentrale kompatibel.', 'frontend-rechtstexte-generator' ), array( 'status' => 400 ) );
		}
		return $sanitized;
	}

	private function get_agency_sources(): array {
		$sources = get_option( self::AGENCY_SOURCES_OPTION, array() );
		return is_array( $sources ) ? $sources : array();
	}

	private function get_agency_source( int $agency_id ): array {
		return self::get_agency_source_summary( $agency_id );
	}

	public static function get_agency_source_summary( int $agency_id ): array {
		$sources = get_option( self::AGENCY_SOURCES_OPTION, array() );
		$sources = is_array( $sources ) ? $sources : array();
		$source  = is_array( $sources[ $agency_id ] ?? null ) ? $sources[ $agency_id ] : array();
		return array(
			'mode'           => 'agency' === ( $source['mode'] ?? 'master' ) ? 'agency' : 'master',
			'blocks'         => is_array( $source['blocks'] ?? null ) ? $source['blocks'] : array(),
			'feed_version'   => sanitize_text_field( $source['feed_version'] ?? '' ),
			'module_version' => sanitize_text_field( $source['module_version'] ?? '' ),
			'published_at'   => sanitize_text_field( $source['published_at'] ?? '' ),
		);
	}

	private function unauthorized_error(): WP_Error {
		return new WP_Error(
			'frg_feed_unauthorized',
			__( 'Der Zugriff auf diesen Textbaustein-Feed ist nicht erlaubt.', 'frontend-rechtstexte-generator' ),
			array( 'status' => 401 )
		);
	}
}

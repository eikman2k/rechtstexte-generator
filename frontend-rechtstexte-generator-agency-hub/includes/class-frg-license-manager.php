<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class FRG_License_Manager {
	private const DB_VERSION = '1.1.0';
	private const DB_VERSION_OPTION = 'frg_license_db_version';

	public static function maybe_install(): void {
		if ( self::DB_VERSION !== get_option( self::DB_VERSION_OPTION, '' ) ) {
			self::install();
		}
	}

	public static function install(): void {
		global $wpdb;

		require_once ABSPATH . 'wp-admin/includes/upgrade.php';
		$collation = $wpdb->get_charset_collate();
		$licenses  = self::licenses_table();
		$sites     = self::sites_table();

		dbDelta(
			"CREATE TABLE {$licenses} (
				id BIGINT unsigned NOT NULL AUTO_INCREMENT,
				customer_name VARCHAR(255) NOT NULL DEFAULT '',
				customer_email VARCHAR(190) NOT NULL DEFAULT '',
				license_key VARCHAR(64) NOT NULL,
				license_type VARCHAR(20) NOT NULL DEFAULT 'site',
				parent_license_id BIGINT unsigned NOT NULL DEFAULT 0,
				max_sites SMALLINT unsigned NOT NULL DEFAULT 1,
				expires_at DATE NOT NULL,
				status VARCHAR(20) NOT NULL DEFAULT 'active',
				notes TEXT NOT NULL,
				created_at DATETIME NOT NULL,
				updated_at DATETIME NOT NULL,
				PRIMARY KEY  (id),
				UNIQUE KEY license_key (license_key),
				KEY parent_license_id (parent_license_id),
				KEY license_type (license_type),
				KEY status (status),
				KEY expires_at (expires_at)
			) {$collation};"
		);

		dbDelta(
			"CREATE TABLE {$sites} (
				id BIGINT unsigned NOT NULL AUTO_INCREMENT,
				license_id BIGINT unsigned NOT NULL,
				site_url VARCHAR(255) NOT NULL,
				site_hash CHAR(64) NOT NULL,
				plugin_version VARCHAR(50) NOT NULL DEFAULT '',
				status VARCHAR(20) NOT NULL DEFAULT 'active',
				activated_at DATETIME NOT NULL,
				last_seen_at DATETIME NOT NULL,
				PRIMARY KEY  (id),
				UNIQUE KEY license_site (license_id, site_hash),
				KEY license_id (license_id),
				KEY status (status)
			) {$collation};"
		);

		update_option( self::DB_VERSION_OPTION, self::DB_VERSION, false );
	}

	public function create_license( array $data ) {
		global $wpdb;

		$customer_name = sanitize_text_field( $data['customer_name'] ?? '' );
		$expires_at    = $this->sanitize_date( (string) ( $data['expires_at'] ?? '' ) );
		if ( '' === $customer_name || '' === $expires_at ) {
			return new WP_Error( 'frg_license_invalid', __( 'Bitte Kundennamen und ein gültiges Ablaufdatum angeben.', 'frontend-rechtstexte-generator' ) );
		}

		$now      = current_time( 'mysql' );
		$license_type = in_array( ( $data['license_type'] ?? 'site' ), array( 'site', 'agency' ), true ) ? $data['license_type'] : 'site';
		$inserted = $wpdb->insert(
			self::licenses_table(),
			array(
				'customer_name'  => $customer_name,
				'customer_email' => sanitize_email( $data['customer_email'] ?? '' ),
				'license_key'    => $this->generate_unique_key(),
				'license_type'   => $license_type,
				'parent_license_id' => 0,
				'max_sites'      => max( 1, min( 999, absint( $data['max_sites'] ?? 1 ) ) ),
				'expires_at'     => $expires_at,
				'status'         => 'active',
				'notes'          => sanitize_textarea_field( $data['notes'] ?? '' ),
				'created_at'     => $now,
				'updated_at'     => $now,
			),
			array( '%s', '%s', '%s', '%s', '%d', '%d', '%s', '%s', '%s', '%s', '%s' )
		);

		return false === $inserted
			? new WP_Error( 'frg_license_create_failed', __( 'Die Lizenz konnte nicht angelegt werden.', 'frontend-rechtstexte-generator' ) )
			: $this->get_license( (int) $wpdb->insert_id );
	}

	public function update_license( int $license_id, array $data ): bool {
		global $wpdb;

		$license = $this->get_license( $license_id );
		if ( ! $license ) {
			return false;
		}

		$expires_at = $this->sanitize_date( (string) ( $data['expires_at'] ?? $license['expires_at'] ) );
		if ( '' === $expires_at ) {
			return false;
		}

		$result = $wpdb->update(
			self::licenses_table(),
			array(
				'customer_name'  => sanitize_text_field( $data['customer_name'] ?? $license['customer_name'] ),
				'customer_email' => sanitize_email( $data['customer_email'] ?? $license['customer_email'] ),
				'max_sites'      => max( 1, min( 999, absint( $data['max_sites'] ?? $license['max_sites'] ) ) ),
				'expires_at'     => $expires_at,
				'notes'          => sanitize_textarea_field( $data['notes'] ?? $license['notes'] ),
				'updated_at'     => current_time( 'mysql' ),
			),
			array( 'id' => $license_id ),
			array( '%s', '%s', '%d', '%s', '%s', '%s' ),
			array( '%d' )
		);

		if ( false !== $result && 'agency' === $license['license_type'] && $expires_at !== $license['expires_at'] ) {
			$wpdb->update(
				self::licenses_table(),
				array( 'expires_at' => $expires_at, 'updated_at' => current_time( 'mysql' ) ),
				array( 'parent_license_id' => $license_id ),
				array( '%s', '%s' ),
				array( '%d' )
			);
		}

		return false !== $result;
	}

	public function extend_license( int $license_id ): bool {
		$license = $this->get_license( $license_id );
		if ( ! $license ) {
			return false;
		}

		$base      = max( current_time( 'Y-m-d' ), (string) $license['expires_at'] );
		$timestamp = strtotime( $base . ' +1 year' );
		if ( false === $timestamp ) {
			return false;
		}

		$expires_at = wp_date( 'Y-m-d', $timestamp );
		return $this->update_license( $license_id, array( 'expires_at' => $expires_at ) );
	}

	public function set_license_status( int $license_id, string $status ): bool {
		global $wpdb;

		if ( ! in_array( $status, array( 'active', 'blocked' ), true ) ) {
			return false;
		}

		return false !== $wpdb->update(
			self::licenses_table(),
			array(
				'status'     => $status,
				'updated_at' => current_time( 'mysql' ),
			),
			array( 'id' => $license_id ),
			array( '%s', '%s' ),
			array( '%d' )
		);
	}

	public function release_site( int $site_id ): bool {
		global $wpdb;

		return false !== $wpdb->delete( self::sites_table(), array( 'id' => $site_id ), array( '%d' ) );
	}

	public function get_license( int $license_id ): ?array {
		global $wpdb;

		$row = $wpdb->get_row(
			$wpdb->prepare( 'SELECT * FROM ' . self::licenses_table() . ' WHERE id = %d', $license_id ),
			ARRAY_A
		);

		return is_array( $row ) ? $this->prepare_license( $row ) : null;
	}

	public function get_license_by_key( string $license_key ): ?array {
		global $wpdb;

		$row = $wpdb->get_row(
			$wpdb->prepare( 'SELECT * FROM ' . self::licenses_table() . ' WHERE license_key = %s', sanitize_text_field( $license_key ) ),
			ARRAY_A
		);

		return is_array( $row ) ? $this->prepare_license( $row ) : null;
	}

	public function get_licenses_with_sites(): array {
		global $wpdb;

		$licenses = $wpdb->get_results( 'SELECT * FROM ' . self::licenses_table() . ' WHERE parent_license_id = 0 ORDER BY created_at DESC', ARRAY_A );
		if ( ! is_array( $licenses ) ) {
			return array();
		}

		foreach ( $licenses as &$license ) {
			$license          = $this->prepare_license( $license );
			$license['sites'] = $this->get_sites( (int) $license['id'] );
			$license['children'] = 'agency' === $license['license_type'] ? $this->get_child_licenses( (int) $license['id'] ) : array();
		}

		return $licenses;
	}

	public function get_summary(): array {
		$licenses = $this->get_licenses_with_sites();
		$summary  = array(
			'total'       => count( $licenses ),
			'active'      => 0,
			'expired'     => 0,
			'blocked'     => 0,
			'active_sites' => 0,
		);

		foreach ( $licenses as $license ) {
			$status = $license['effective_status'];
			if ( ! isset( $summary[ $status ] ) ) {
				continue;
			}
			++$summary[ $status ];
			if ( 'agency' === $license['license_type'] ) {
				foreach ( $license['children'] as $child ) {
					$summary['active_sites'] += count( $child['sites'] );
				}
			} else {
				$summary['active_sites'] += count( $license['sites'] );
			}
		}

		return $summary;
	}

	public function authorize_site( string $license_key, string $site_url, string $plugin_version = '' ) {
		global $wpdb;

		$license = $this->get_license_by_key( $license_key );
		if ( ! $license ) {
			return new WP_Error( 'frg_license_not_found', __( 'Der Lizenzschlüssel ist ungültig.', 'frontend-rechtstexte-generator' ), array( 'status' => 401 ) );
		}

		$status_error = $this->validate_license_status( $license );
		if ( is_wp_error( $status_error ) ) {
			return $status_error;
		}
		if ( 'agency' === $license['license_type'] ) {
			return $license;
		}

		$site_url = $this->normalize_site_url( $site_url );
		if ( '' === $site_url ) {
			return new WP_Error( 'frg_license_site_missing', __( 'Die Kundenseite hat keine gültige Website-URL übermittelt.', 'frontend-rechtstexte-generator' ), array( 'status' => 400 ) );
		}

		$site_hash = $this->get_site_hash( $site_url );
		$existing  = $wpdb->get_row(
			$wpdb->prepare(
				'SELECT * FROM ' . self::sites_table() . ' WHERE license_id = %d AND site_hash = %s',
				(int) $license['id'],
				$site_hash
			),
			ARRAY_A
		);
		$now = current_time( 'mysql' );

		if ( is_array( $existing ) ) {
			if ( 'blocked' === $existing['status'] ) {
				return new WP_Error( 'frg_license_site_blocked', __( 'Diese Website-Aktivierung wurde gesperrt.', 'frontend-rechtstexte-generator' ), array( 'status' => 403 ) );
			}
			$wpdb->update(
				self::sites_table(),
				array(
					'site_url'       => $site_url,
					'plugin_version' => sanitize_text_field( $plugin_version ),
					'last_seen_at'   => $now,
				),
				array( 'id' => (int) $existing['id'] ),
				array( '%s', '%s', '%s' ),
				array( '%d' )
			);
		} else {
			$active_sites = (int) $wpdb->get_var(
				$wpdb->prepare(
					'SELECT COUNT(*) FROM ' . self::sites_table() . ' WHERE license_id = %d AND status = %s',
					(int) $license['id'],
					'active'
				)
			);
			if ( $active_sites >= (int) $license['max_sites'] ) {
				return new WP_Error( 'frg_license_site_limit', __( 'Das Website-Limit dieser Lizenz ist erreicht.', 'frontend-rechtstexte-generator' ), array( 'status' => 403 ) );
			}

			$inserted = $wpdb->insert(
				self::sites_table(),
				array(
					'license_id'    => (int) $license['id'],
					'site_url'      => $site_url,
					'site_hash'     => $site_hash,
					'plugin_version'=> sanitize_text_field( $plugin_version ),
					'status'        => 'active',
					'activated_at'  => $now,
					'last_seen_at'  => $now,
				),
				array( '%d', '%s', '%s', '%s', '%s', '%s', '%s' )
			);
			if ( false === $inserted ) {
				return new WP_Error( 'frg_license_site_create_failed', __( 'Die Website konnte nicht für diese Lizenz registriert werden.', 'frontend-rechtstexte-generator' ), array( 'status' => 500 ) );
			}
		}

		$license['site_url'] = $site_url;
		return $license;
	}

	public function authorize_agency( string $license_key ) {
		$license = $this->get_license_by_key( $license_key );
		if ( ! $license || 'agency' !== $license['license_type'] || 0 !== $license['parent_license_id'] ) {
			return new WP_Error( 'frg_agency_license_invalid', __( 'Der Agenturschlüssel ist ungültig.', 'frontend-rechtstexte-generator' ), array( 'status' => 401 ) );
		}

		$status_error = $this->validate_license_status( $license );
		return is_wp_error( $status_error ) ? $status_error : $license;
	}

	public function create_child_license( int $agency_id, array $data ) {
		global $wpdb;

		$agency = $this->get_license( $agency_id );
		if ( ! $agency || 'agency' !== $agency['license_type'] || is_wp_error( $this->validate_license_status( $agency ) ) ) {
			return new WP_Error( 'frg_agency_invalid', __( 'Die Agenturlizenz ist nicht aktiv.', 'frontend-rechtstexte-generator' ) );
		}

		$active_children = (int) $wpdb->get_var(
			$wpdb->prepare( 'SELECT COUNT(*) FROM ' . self::licenses_table() . ' WHERE parent_license_id = %d AND status = %s', $agency_id, 'active' )
		);
		if ( $active_children >= (int) $agency['max_sites'] ) {
			return new WP_Error( 'frg_agency_limit', __( 'Das Kundenkontingent dieser Agenturlizenz ist erreicht.', 'frontend-rechtstexte-generator' ) );
		}

		$customer_name = sanitize_text_field( $data['customer_name'] ?? '' );
		if ( '' === $customer_name ) {
			return new WP_Error( 'frg_child_invalid', __( 'Bitte einen Kundennamen angeben.', 'frontend-rechtstexte-generator' ) );
		}

		$now      = current_time( 'mysql' );
		$inserted = $wpdb->insert(
			self::licenses_table(),
			array(
				'customer_name'    => $customer_name,
				'customer_email'   => sanitize_email( $data['customer_email'] ?? '' ),
				'license_key'      => $this->generate_unique_key(),
				'license_type'     => 'site',
				'parent_license_id'=> $agency_id,
				'max_sites'        => 1,
				'expires_at'       => $agency['expires_at'],
				'status'           => 'active',
				'notes'            => '',
				'created_at'       => $now,
				'updated_at'       => $now,
			),
			array( '%s', '%s', '%s', '%s', '%d', '%d', '%s', '%s', '%s', '%s', '%s' )
		);

		return false === $inserted
			? new WP_Error( 'frg_child_create_failed', __( 'Der Kundenschlüssel konnte nicht erstellt werden.', 'frontend-rechtstexte-generator' ) )
			: $this->get_license( (int) $wpdb->insert_id );
	}

	public function get_child_licenses( int $agency_id ): array {
		global $wpdb;

		$children = $wpdb->get_results(
			$wpdb->prepare( 'SELECT * FROM ' . self::licenses_table() . ' WHERE parent_license_id = %d ORDER BY created_at DESC', $agency_id ),
			ARRAY_A
		);
		if ( ! is_array( $children ) ) {
			return array();
		}
		foreach ( $children as &$child ) {
			$child          = $this->prepare_license( $child );
			$child['sites'] = $this->get_sites( (int) $child['id'] );
		}
		return $children;
	}

	public function set_child_status( int $agency_id, int $child_id, string $status ): bool {
		global $wpdb;
		if ( ! in_array( $status, array( 'active', 'blocked' ), true ) ) {
			return false;
		}
		if ( 'active' === $status ) {
			$agency = $this->get_license( $agency_id );
			if ( ! $agency || 'agency' !== $agency['license_type'] ) {
				return false;
			}
			$active_children = (int) $wpdb->get_var(
				$wpdb->prepare( 'SELECT COUNT(*) FROM ' . self::licenses_table() . ' WHERE parent_license_id = %d AND status = %s AND id != %d', $agency_id, 'active', $child_id )
			);
			if ( $active_children >= (int) $agency['max_sites'] ) {
				return false;
			}
		}

		return false !== $wpdb->update(
			self::licenses_table(),
			array( 'status' => $status, 'updated_at' => current_time( 'mysql' ) ),
			array( 'id' => $child_id, 'parent_license_id' => $agency_id ),
			array( '%s', '%s' ),
			array( '%d', '%d' )
		);
	}

	private function get_sites( int $license_id ): array {
		global $wpdb;

		$sites = $wpdb->get_results(
			$wpdb->prepare( 'SELECT * FROM ' . self::sites_table() . ' WHERE license_id = %d ORDER BY last_seen_at DESC', $license_id ),
			ARRAY_A
		);

		return is_array( $sites ) ? $sites : array();
	}

	private function prepare_license( array $license ): array {
		$license['id']               = (int) $license['id'];
		$license['max_sites']        = (int) $license['max_sites'];
		$license['parent_license_id']= (int) ( $license['parent_license_id'] ?? 0 );
		$license['license_type']     = in_array( ( $license['license_type'] ?? 'site' ), array( 'site', 'agency' ), true ) ? $license['license_type'] : 'site';
		$license['effective_status'] = 'blocked' === $license['status']
			? 'blocked'
			: ( (string) $license['expires_at'] < current_time( 'Y-m-d' ) ? 'expired' : 'active' );

		return $license;
	}

	private function validate_license_status( array $license ) {
		if ( $license['parent_license_id'] > 0 ) {
			$parent = $this->get_license( $license['parent_license_id'] );
			if ( ! $parent || 'active' !== $parent['effective_status'] ) {
				return new WP_Error( 'frg_agency_license_inactive', __( 'Die zugehörige Agenturlizenz ist nicht aktiv.', 'frontend-rechtstexte-generator' ), array( 'status' => 403 ) );
			}
		}
		if ( 'blocked' === $license['effective_status'] ) {
			return new WP_Error( 'frg_license_blocked', __( 'Diese Lizenz wurde gesperrt.', 'frontend-rechtstexte-generator' ), array( 'status' => 403 ) );
		}
		if ( 'expired' === $license['effective_status'] ) {
			return new WP_Error( 'frg_license_expired', __( 'Diese Lizenz ist abgelaufen. Die zuletzt synchronisierten Texte bleiben lokal aktiv.', 'frontend-rechtstexte-generator' ), array( 'status' => 403 ) );
		}
		return true;
	}

	private function generate_unique_key(): string {
		global $wpdb;

		do {
			$raw = strtoupper( wp_generate_password( 32, false, false ) );
			$key = 'FRG-' . implode( '-', str_split( $raw, 8 ) );
		} while ( $wpdb->get_var( $wpdb->prepare( 'SELECT id FROM ' . self::licenses_table() . ' WHERE license_key = %s', $key ) ) );

		return $key;
	}

	private function sanitize_date( string $date ): string {
		$date = sanitize_text_field( $date );
		if ( ! preg_match( '/^\d{4}-\d{2}-\d{2}$/', $date ) ) {
			return '';
		}

		$parts = array_map( 'intval', explode( '-', $date ) );
		return checkdate( $parts[1], $parts[2], $parts[0] ) ? $date : '';
	}

	private function normalize_site_url( string $url ): string {
		$url    = esc_url_raw( trim( $url ) );
		$scheme = strtolower( (string) wp_parse_url( $url, PHP_URL_SCHEME ) );
		$host   = strtolower( (string) wp_parse_url( $url, PHP_URL_HOST ) );
		if ( ! in_array( $scheme, array( 'http', 'https' ), true ) || '' === $host ) {
			return '';
		}

		$port = (int) wp_parse_url( $url, PHP_URL_PORT );
		$path = trim( (string) wp_parse_url( $url, PHP_URL_PATH ), '/' );
		return $scheme . '://' . $host . ( $port ? ':' . $port : '' ) . ( $path ? '/' . $path : '' );
	}

	private function get_site_hash( string $url ): string {
		$host = strtolower( (string) wp_parse_url( $url, PHP_URL_HOST ) );
		$port = (int) wp_parse_url( $url, PHP_URL_PORT );
		$path = trim( (string) wp_parse_url( $url, PHP_URL_PATH ), '/' );

		// The protocol may change during launch without this becoming another licensed website.
		return hash( 'sha256', $host . ( $port ? ':' . $port : '' ) . ( $path ? '/' . $path : '' ) );
	}

	private static function licenses_table(): string {
		global $wpdb;
		return $wpdb->prefix . 'frg_licenses';
	}

	private static function sites_table(): string {
		global $wpdb;
		return $wpdb->prefix . 'frg_license_sites';
	}
}

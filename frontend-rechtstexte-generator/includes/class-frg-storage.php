<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class FRG_Storage {
	public function get_table_name(): string {
		global $wpdb;
		return $wpdb->prefix . 'frg_profiles';
	}

	public function get_profile_by_user_id( int $user_id ): ?array {
		global $wpdb;

		$row = $wpdb->get_row(
			$wpdb->prepare(
				"SELECT * FROM {$this->get_table_name()} WHERE user_id = %d ORDER BY updated_at DESC LIMIT 1",
				$user_id
			),
			ARRAY_A
		);

		return $this->hydrate_profile( $row );
	}

	public function get_profile_by_id( int $profile_id ): ?array {
		global $wpdb;

		$row = $wpdb->get_row(
			$wpdb->prepare(
				"SELECT * FROM {$this->get_table_name()} WHERE id = %d",
				$profile_id
			),
			ARRAY_A
		);

		return $this->hydrate_profile( $row );
	}

	public function get_latest_profile(): ?array {
		global $wpdb;

		$row = $wpdb->get_row(
			"SELECT * FROM {$this->get_table_name()} ORDER BY updated_at DESC LIMIT 1", // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
			ARRAY_A
		);

		return $this->hydrate_profile( $row );
	}

	public function get_all_profiles(): array {
		global $wpdb;

		$rows = $wpdb->get_results( "SELECT * FROM {$this->get_table_name()} ORDER BY updated_at DESC", ARRAY_A ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
		$out  = array();

		foreach ( $rows as $row ) {
			$profile = $this->hydrate_profile( $row );
			if ( $profile ) {
				$out[] = $profile;
			}
		}

		return $out;
	}

	public function save_profile( int $user_id, string $profile_name, array $data ): int {
		global $wpdb;

		$existing = $this->get_profile_by_user_id( $user_id );
		$now      = current_time( 'mysql' );
		$payload  = array(
			'user_id'     => $user_id,
			'profile_name'=> sanitize_text_field( $profile_name ),
			'data'        => wp_json_encode( $data ),
			'updated_at'  => $now,
		);

		if ( $existing ) {
			$wpdb->update(
				$this->get_table_name(),
				$payload,
				array( 'id' => $existing['id'] ),
				array( '%d', '%s', '%s', '%s' ),
				array( '%d' )
			);

			return (int) $existing['id'];
		}

		$payload['created_at'] = $now;

		$wpdb->insert(
			$this->get_table_name(),
			$payload,
			array( '%d', '%s', '%s', '%s', '%s' )
		);

		return (int) $wpdb->insert_id;
	}

	public function import_profile( ?int $user_id, string $profile_name, array $data ): int {
		global $wpdb;

		$now     = current_time( 'mysql' );
		$payload = array(
			'user_id'      => $user_id,
			'profile_name' => sanitize_text_field( $profile_name ),
			'data'         => wp_json_encode( $data ),
			'created_at'   => $now,
			'updated_at'   => $now,
		);

		$wpdb->insert(
			$this->get_table_name(),
			$payload,
			array( '%d', '%s', '%s', '%s', '%s' )
		);

		return (int) $wpdb->insert_id;
	}

	public function delete_profile( int $profile_id ): bool {
		global $wpdb;
		return false !== $wpdb->delete( $this->get_table_name(), array( 'id' => $profile_id ), array( '%d' ) );
	}

	private function hydrate_profile( ?array $row ): ?array {
		if ( empty( $row ) ) {
			return null;
		}

		$row['id']      = (int) $row['id'];
		$row['user_id'] = null !== $row['user_id'] ? (int) $row['user_id'] : null;
		$row['data']    = json_decode( (string) $row['data'], true ) ?: array();

		return $row;
	}
}

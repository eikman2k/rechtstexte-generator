<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class FRG_Hub_Admin {
	private FRG_License_Manager $license_manager;

	public function __construct( FRG_License_Manager $license_manager ) {
		$this->license_manager = $license_manager;
	}

	public function register_menu(): void {
		add_submenu_page(
			'frg-settings',
			__( 'Kunden und Agenturen', 'frontend-rechtstexte-generator' ),
			__( 'Kunden & Agenturen', 'frontend-rechtstexte-generator' ),
			'manage_options',
			'frg-licenses',
			array( $this, 'render_page' )
		);
	}

	public function render_page(): void {
		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}

		$notice_key = 'frg_license_notice_' . get_current_user_id();
		$notice     = get_transient( $notice_key );
		if ( is_array( $notice ) && ! empty( $notice['message'] ) ) {
			add_settings_error( 'frg_license_messages', 'license_action_result', $notice['message'], $notice['type'] ?? 'updated' );
			delete_transient( $notice_key );
		}

		$settings         = get_option( 'frg_settings', array() );
		$licenses        = $this->license_manager->get_licenses_with_sites();
		$license_summary = $this->license_manager->get_summary();
		$default_expiry  = wp_date( 'Y-m-d', strtotime( current_time( 'Y-m-d' ) . ' +1 year' ) );
		$feed_endpoint   = rest_url( 'frg/v1/block-feed' );

		include FRG_HUB_DIR . 'templates/admin-licenses.php';
	}

	public function handle_license_request(): void {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( 'Sie dürfen keine Lizenzen verwalten.', 'frontend-rechtstexte-generator' ) );
		}

		$operation = sanitize_key( wp_unslash( $_POST['frg_license_operation'] ?? '' ) );
		if ( ! in_array( $operation, array( 'create', 'update', 'extend', 'status', 'release_site' ), true ) ) {
			wp_die( esc_html__( 'Unbekannte Lizenzaktion.', 'frontend-rechtstexte-generator' ) );
		}
		check_admin_referer( 'frg_license_' . $operation . '_action', 'frg_license_nonce' );

		$type    = 'updated';
		$message = '';
		if ( 'create' === $operation ) {
			$result = $this->license_manager->create_license(
				array(
					'customer_name'  => wp_unslash( $_POST['customer_name'] ?? '' ),
					'customer_email' => wp_unslash( $_POST['customer_email'] ?? '' ),
					'license_type'   => sanitize_key( wp_unslash( $_POST['license_type'] ?? 'site' ) ),
					'max_sites'      => wp_unslash( $_POST['max_sites'] ?? 1 ),
					'expires_at'     => wp_unslash( $_POST['expires_at'] ?? '' ),
					'notes'          => wp_unslash( $_POST['notes'] ?? '' ),
				)
			);
			if ( is_wp_error( $result ) ) {
				$type    = 'error';
				$message = $result->get_error_message();
			} else {
				$message = __( 'Die Lizenz wurde angelegt und kann jetzt an den Kunden übermittelt werden.', 'frontend-rechtstexte-generator' );
			}
		} else {
			$license_id = absint( $_POST['license_id'] ?? 0 );
			$success    = false;

			if ( 'update' === $operation ) {
				$success = $this->license_manager->update_license(
					$license_id,
					array(
						'customer_name'  => wp_unslash( $_POST['customer_name'] ?? '' ),
						'customer_email' => wp_unslash( $_POST['customer_email'] ?? '' ),
						'max_sites'      => wp_unslash( $_POST['max_sites'] ?? 1 ),
						'expires_at'     => wp_unslash( $_POST['expires_at'] ?? '' ),
						'notes'          => wp_unslash( $_POST['notes'] ?? '' ),
					)
				);
				$message = __( 'Die Lizenzdaten wurden gespeichert.', 'frontend-rechtstexte-generator' );
			} elseif ( 'extend' === $operation ) {
				$success = $this->license_manager->extend_license( $license_id );
				$message = __( 'Die Lizenz wurde um ein Jahr verlängert.', 'frontend-rechtstexte-generator' );
			} elseif ( 'status' === $operation ) {
				$status  = sanitize_key( wp_unslash( $_POST['license_status'] ?? '' ) );
				$success = $this->license_manager->set_license_status( $license_id, $status );
				$message = 'blocked' === $status
					? __( 'Die Lizenz wurde gesperrt.', 'frontend-rechtstexte-generator' )
					: __( 'Die Lizenz wurde wieder freigegeben.', 'frontend-rechtstexte-generator' );
			} elseif ( 'release_site' === $operation ) {
				$success = $this->license_manager->release_site( absint( $_POST['site_id'] ?? 0 ) );
				$message = __( 'Die Domain wurde freigegeben und belegt keinen Lizenzplatz mehr.', 'frontend-rechtstexte-generator' );
			}

			if ( ! $success ) {
				$type    = 'error';
				$message = __( 'Die Lizenzaktion konnte nicht gespeichert werden.', 'frontend-rechtstexte-generator' );
			}
		}

		set_transient(
			'frg_license_notice_' . get_current_user_id(),
			array( 'type' => $type, 'message' => $message ),
			MINUTE_IN_SECONDS
		);
		wp_safe_redirect( admin_url( 'admin.php?page=frg-licenses' ) );
		exit;
	}
}

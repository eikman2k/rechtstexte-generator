<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class FRG_Frontend_Wizard {
	private FRG_Storage $storage;
	private FRG_Generator $generator;
	private FRG_Page_Sync $page_sync;
	private FRG_Scanner $scanner;

	public function __construct( FRG_Storage $storage, FRG_Generator $generator, FRG_Page_Sync $page_sync, FRG_Scanner $scanner ) {
		$this->storage   = $storage;
		$this->generator = $generator;
		$this->page_sync = $page_sync;
		$this->scanner   = $scanner;
	}

	public function register_assets(): void {
		wp_register_style( 'frg-frontend', FRG_PLUGIN_URL . 'assets/css/frontend.css', array(), FRG_VERSION );
		wp_register_script( 'frg-frontend', FRG_PLUGIN_URL . 'assets/js/frontend.js', array(), FRG_VERSION, true );
		wp_localize_script(
			'frg-frontend',
			'frgWizard',
			array(
				'ajaxUrl'         => admin_url( 'admin-ajax.php' ),
				'nonce'           => wp_create_nonce( 'frg_frontend_nonce' ),
				'noticeText'      => $this->get_legal_notice(),
				'requiredMessage' => __( 'Bitte füllen Sie alle Pflichtfelder des aktuellen Schritts aus.', 'frontend-rechtstexte-generator' ),
				'savedMessage'    => __( 'Ihre Angaben wurden gespeichert.', 'frontend-rechtstexte-generator' ),
				'loginMessage'    => __( 'Speichern ist nur für eingeloggte Benutzer möglich.', 'frontend-rechtstexte-generator' ),
				'syncMessage'     => __( 'Die Seiten wurden synchronisiert.', 'frontend-rechtstexte-generator' ),
				'copyImpressumMessage' => __( 'Das Impressum wurde als HTML in die Zwischenablage kopiert.', 'frontend-rechtstexte-generator' ),
				'copyPrivacyMessage'   => __( 'Die Datenschutzerklärung wurde als HTML in die Zwischenablage kopiert.', 'frontend-rechtstexte-generator' ),
				'generateFirstMessage' => __( 'Bitte zuerst eine Vorschau erzeugen oder die Angaben speichern.', 'frontend-rechtstexte-generator' ),
				'adoptMessage'    => __( 'Vorschlag wurde in die Auswahl übernommen.', 'frontend-rechtstexte-generator' ),
				'savedInfoLabel'  => __( 'Gespeichert', 'frontend-rechtstexte-generator' ),
				'syncErrorMessage'=> __( 'Die Seite konnte nicht erstellt oder aktualisiert werden.', 'frontend-rechtstexte-generator' ),
				'syncSuccessPrefix' => __( 'Seite aktualisiert', 'frontend-rechtstexte-generator' ),
			)
		);
	}

	public function render(): string {
		wp_enqueue_style( 'frg-frontend' );
		wp_enqueue_script( 'frg-frontend' );

		$profile = $this->get_current_profile();
		$data    = $profile['data'] ?? array();
		if ( ! empty( $data ) && $this->should_regenerate_generated_documents( $data ) ) {
			$data = $this->attach_generated_documents( $data );
		}
		$scan    = $this->scanner->get_scan_results();
		$scanner_recommendations = $scan['detected'] ?? array();
		$scanner_errors          = $scan['errors'] ?? array();
		$last_saved_label        = ! empty( $profile['updated_at'] ) ? mysql2date( get_option( 'date_format' ) . ' ' . get_option( 'time_format' ), $profile['updated_at'] ) : '';

		ob_start();
		include FRG_PLUGIN_DIR . 'templates/wizard.php';
		return (string) ob_get_clean();
	}

	public function ajax_generate_preview(): void {
		check_ajax_referer( 'frg_frontend_nonce', 'nonce' );

		$data       = $this->sanitize_profile_data( $_POST );
		$validation = $this->validate_required_fields( $data );

		if ( ! empty( $validation ) ) {
			wp_send_json_error( array( 'message' => implode( ' ', $validation ) ), 422 );
		}

		$impressum = wp_kses_post( $this->generator->generate_impressum( $data ) );
		$privacy   = wp_kses_post( $this->generator->generate_privacy_policy( $data ) );

		ob_start();
		$notice = $this->get_legal_notice();
		include FRG_PLUGIN_DIR . 'templates/preview-impressum.php';
		$impressum_html = (string) ob_get_clean();

		ob_start();
		include FRG_PLUGIN_DIR . 'templates/preview-datenschutz.php';
		$privacy_html = (string) ob_get_clean();

		wp_send_json_success(
			array(
				'impressum'        => $impressum,
				'privacy'          => $privacy,
				'impressum_export' => $this->generator->build_exportable_document_html( $impressum ),
				'privacy_export'   => $this->generator->build_exportable_document_html( $privacy ),
				'html'             => $impressum_html . $privacy_html,
			)
		);
	}

	public function ajax_save_profile(): void {
		check_ajax_referer( 'frg_frontend_nonce', 'nonce' );

		if ( ! is_user_logged_in() ) {
			wp_send_json_error( array( 'message' => __( 'Speichern ist nur für eingeloggte Benutzer möglich.', 'frontend-rechtstexte-generator' ) ), 403 );
		}

		$data       = $this->sanitize_profile_data( $_POST );
		$validation = $this->validate_required_fields( $data );

		if ( ! empty( $validation ) ) {
			wp_send_json_error( array( 'message' => implode( ' ', $validation ) ), 422 );
		}

		$user_id      = get_current_user_id();
		$profile_name = ! empty( $data['company_name'] ) ? $data['company_name'] : __( 'Standardprofil', 'frontend-rechtstexte-generator' );
		$data         = $this->attach_generated_documents( $data );
		$profile_id   = $this->storage->save_profile( $user_id, $profile_name, $data );
		$updated_at   = current_time( 'mysql' );

		wp_send_json_success(
			array(
				'profile_id' => $profile_id,
				'message'    => __( 'Profil gespeichert.', 'frontend-rechtstexte-generator' ),
				'updated_at' => mysql2date( get_option( 'date_format' ) . ' ' . get_option( 'time_format' ), $updated_at ),
				'impressum'  => $data['generated_impressum_export_html'],
				'privacy'    => $data['generated_privacy_export_html'],
			)
		);
	}

	public function ajax_sync_pages(): void {
		check_ajax_referer( 'frg_frontend_nonce', 'nonce' );

		if ( ! is_user_logged_in() ) {
			wp_send_json_error( array( 'message' => __( 'Seitenerstellung ist nur für eingeloggte Benutzer möglich.', 'frontend-rechtstexte-generator' ) ), 403 );
		}

		if ( ! current_user_can( 'edit_pages' ) && ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( array( 'message' => __( 'Keine Berechtigung zur Seitensynchronisierung.', 'frontend-rechtstexte-generator' ) ), 403 );
		}

		$data       = $this->sanitize_profile_data( $_POST );
		$validation = $this->validate_required_fields( $data );

		if ( ! empty( $validation ) ) {
			wp_send_json_error( array( 'message' => implode( ' ', $validation ) ), 422 );
		}

		$mode = isset( $_POST['sync_target'] ) ? sanitize_key( wp_unslash( $_POST['sync_target'] ) ) : '';

		$impressum = $this->generator->generate_impressum( $data );
		$privacy   = $this->generator->generate_privacy_policy( $data );
		$result    = array();
		$errors    = array();

		if ( 'impressum' === $mode || 'both' === $mode ) {
			$result['impressum_page_id'] = $this->page_sync->create_or_update_impressum_page( $impressum );
			if ( empty( $result['impressum_page_id'] ) ) {
				$errors[] = __( 'Die Impressum-Seite konnte nicht erstellt oder aktualisiert werden.', 'frontend-rechtstexte-generator' );
			} else {
				$result['impressum_edit_link'] = get_edit_post_link( $result['impressum_page_id'], '' );
				$result['impressum_view_link'] = get_permalink( $result['impressum_page_id'] );
			}
		}
		if ( 'privacy' === $mode || 'both' === $mode ) {
			$result['privacy_page_id'] = $this->page_sync->create_or_update_privacy_page( $privacy );
			if ( empty( $result['privacy_page_id'] ) ) {
				$errors[] = __( 'Die Datenschutzerklärung-Seite konnte nicht erstellt oder aktualisiert werden.', 'frontend-rechtstexte-generator' );
			} else {
				$result['privacy_edit_link'] = get_edit_post_link( $result['privacy_page_id'], '' );
				$result['privacy_view_link'] = get_permalink( $result['privacy_page_id'] );
			}
		}

		if ( ! empty( $errors ) ) {
			wp_send_json_error(
				array(
					'message' => implode( ' ', $errors ),
					'result'  => $result,
				),
				500
			);
		}

		wp_send_json_success(
			array(
				'message' => __( 'Die Seite wurde erstellt oder aktualisiert.', 'frontend-rechtstexte-generator' ),
				'result'  => $result,
			)
		);
	}

	public function get_current_profile(): ?array {
		if ( ! is_user_logged_in() ) {
			return null;
		}

		return $this->storage->get_profile_by_user_id( get_current_user_id() );
	}

	public function get_legal_notice(): string {
		$settings = get_option( 'frg_settings', array() );
		return ! empty( $settings['legal_notice'] ) ? wp_kses_post( $settings['legal_notice'] ) : esc_html__( 'Hinweis: Die folgenden Texte wurden auf Basis Ihrer Angaben automatisch aus Textbausteinen zusammengesetzt. Sie ersetzen keine anwaltliche Prüfung.', 'frontend-rechtstexte-generator' );
	}

	private function attach_generated_documents( array $data ): array {
		$module_meta = $this->generator->get_module_meta();
		$timestamp   = current_time( 'mysql' );

		$data['generated_impressum_html']      = wp_kses_post( $this->generator->generate_impressum( $data ) );
		$data['generated_privacy_html']        = wp_kses_post( $this->generator->generate_privacy_policy( $data ) );
		$data['generated_impressum_export_html'] = $this->generator->build_exportable_document_html( $data['generated_impressum_html'] );
		$data['generated_privacy_export_html']   = $this->generator->build_exportable_document_html( $data['generated_privacy_html'] );
		$data['generated_impressum_updated_at'] = $timestamp;
		$data['generated_privacy_updated_at']   = $timestamp;
		$data['generated_module_version']      = sanitize_text_field( $module_meta['module_version'] ?? '' );
		$data['generated_module_reviewed_at']  = sanitize_text_field( $module_meta['last_reviewed_at'] ?? '' );
		$data['generated_registry_updated_at'] = sanitize_text_field( $this->generator->get_registry_updated_at() );

		return $data;
	}

	public function should_regenerate_generated_documents( array $data ): bool {
		$current_registry_updated_at = $this->generator->get_registry_updated_at();
		$generated_registry_updated_at = sanitize_text_field( $data['generated_registry_updated_at'] ?? '' );

		if ( '' === $generated_registry_updated_at ) {
			return true;
		}

		$current_timestamp = strtotime( $current_registry_updated_at );
		$generated_timestamp = strtotime( $generated_registry_updated_at );

		if ( false === $current_timestamp || false === $generated_timestamp ) {
			return true;
		}

		return $generated_timestamp < $current_timestamp;
	}

	public function sanitize_profile_data( array $raw ): array {
		$text_fields = array(
			'company_name', 'legal_form', 'first_name', 'last_name', 'street', 'zip', 'city', 'country', 'email', 'phone',
			'website_url', 'register_court', 'register_number', 'vat_id', 'business_id', 'responsible_name',
			'responsible_address', 'professional_chamber', 'professional_title', 'professional_awarded_in',
			'professional_rules', 'supervisory_authority', 'hosting_provider', 'server_location', 'hosting_av_contract',
			'data_protection_officer_name', 'data_protection_officer_email', 'privacy_processing_purposes',
			'privacy_legal_basis', 'privacy_storage_general', 'privacy_recipient_categories', 'privacy_third_country_transfer',
		);
		$bool_fields = array(
			'has_trade_register', 'has_vat_id', 'has_responsible_content', 'has_professional_info',
			'controller_same_as_operator', 'has_data_protection_officer',
		);
		$data = array();

		foreach ( $text_fields as $field ) {
			$value = isset( $raw[ $field ] ) ? wp_unslash( $raw[ $field ] ) : '';
			if ( 'email' === $field || 'data_protection_officer_email' === $field ) {
				$data[ $field ] = sanitize_email( $value );
			} elseif ( 'website_url' === $field ) {
				$data[ $field ] = esc_url_raw( $value );
			} elseif ( 'responsible_address' === $field || 'professional_rules' === $field ) {
				$data[ $field ] = sanitize_textarea_field( $value );
			} else {
				$data[ $field ] = sanitize_text_field( $value );
			}
		}

		foreach ( $bool_fields as $field ) {
			$data[ $field ] = ! empty( $raw[ $field ] );
		}

		$data['features'] = $this->sanitize_checkbox_group(
			$raw,
			array(
				'contact_form', 'email_contact', 'phone_contact', 'comments', 'user_registration', 'login_area',
				'newsletter', 'job_application_form', 'appointment_booking', 'shop', 'payment_provider',
				'shipping_provider', 'customer_account', 'download_area', 'members_area', 'training_portal',
				'training_progress', 'training_tests', 'training_certificates', 'employee_training',
				'scorm_tracking', 'certificate_download', 'mandatory_training_proof', 'trainer_manager_access',
				'tenant_access', 'social_media_profiles',
			),
			'features'
		);
		$data['services'] = $this->sanitize_checkbox_group(
			$raw,
			array(
				'google_fonts_external', 'google_fonts_local', 'google_maps', 'youtube', 'vimeo',
				'google_analytics', 'google_tag_manager', 'google_ads_conversion_tracking', 'meta_pixel', 'matomo',
				'cloudflare', 'recaptcha', 'hcaptcha', 'borlabs_cookie', 'real_cookie_banner', 'complianz',
				'cookieyes', 'elementor', 'gravity_forms', 'contact_form_7', 'wpforms', 'wordfence',
				'ithemes_security', 'updraftplus', 'wpvivid', 'mailchimp', 'brevo', 'sendinblue', 'cleverreach',
				'facebook', 'instagram', 'linkedin', 'xing', 'tiktok', 'microsoft_clarity', 'calendly',
				'jotform', 'trustpilot', 'smtp_service',
			),
			'services'
		);

		return $data;
	}

	private function sanitize_checkbox_group( array $raw, array $keys, string $group_key ): array {
		$group = array();

		foreach ( $keys as $key ) {
			$group[ $key ] = ! empty( $raw[ $group_key ][ $key ] ) || ! empty( $raw[ $key ] );
		}

		return $group;
	}

	private function validate_required_fields( array $data ): array {
		$required = array(
			'company_name' => __( 'Firmenname / Websitebetreiber ist erforderlich.', 'frontend-rechtstexte-generator' ),
			'legal_form'   => __( 'Rechtsform ist erforderlich.', 'frontend-rechtstexte-generator' ),
			'first_name'   => __( 'Vorname ist erforderlich.', 'frontend-rechtstexte-generator' ),
			'last_name'    => __( 'Nachname ist erforderlich.', 'frontend-rechtstexte-generator' ),
			'street'       => __( 'Straße und Hausnummer sind erforderlich.', 'frontend-rechtstexte-generator' ),
			'zip'          => __( 'PLZ ist erforderlich.', 'frontend-rechtstexte-generator' ),
			'city'         => __( 'Ort ist erforderlich.', 'frontend-rechtstexte-generator' ),
			'country'      => __( 'Land ist erforderlich.', 'frontend-rechtstexte-generator' ),
			'email'        => __( 'E-Mail-Adresse ist erforderlich.', 'frontend-rechtstexte-generator' ),
			'website_url'  => __( 'Website-URL ist erforderlich.', 'frontend-rechtstexte-generator' ),
			'hosting_provider' => __( 'Hosting-Anbieter ist erforderlich.', 'frontend-rechtstexte-generator' ),
			'server_location'  => __( 'Serverstandort ist erforderlich.', 'frontend-rechtstexte-generator' ),
			'hosting_av_contract' => __( 'Angabe zum AV-Vertrag ist erforderlich.', 'frontend-rechtstexte-generator' ),
		);
		$errors = array();

		foreach ( $required as $key => $message ) {
			if ( empty( $data[ $key ] ) ) {
				$errors[] = $message;
			}
		}

		if ( ! empty( $data['has_trade_register'] ) && ( empty( $data['register_court'] ) || empty( $data['register_number'] ) ) ) {
			$errors[] = __( 'Bitte Registergericht und Registernummer angeben.', 'frontend-rechtstexte-generator' );
		}

		if ( ! empty( $data['has_vat_id'] ) && empty( $data['vat_id'] ) ) {
			$errors[] = __( 'Bitte Umsatzsteuer-ID angeben.', 'frontend-rechtstexte-generator' );
		}

		return $errors;
	}
}

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

	public function enqueue_admin_assets( string $hook ): void {
		if ( false === strpos( $hook, '_page_frg-wizard' ) ) {
			return;
		}

		$this->register_assets();
		wp_enqueue_style( 'frg-frontend' );
		wp_enqueue_script( 'frg-frontend' );
	}

	public function render(): string {
		if ( ! is_user_logged_in() ) {
			return '<div class="frg-notice frg-notice--warning">' . esc_html__( 'Der Rechtstexte-Generator steht nur eingeloggten Benutzern zur Verfügung.', 'frontend-rechtstexte-generator' ) . '</div>';
		}

		if ( FRG_Multisite::is_central_output_enabled() && ! FRG_Multisite::is_source_blog() ) {
			$message = __( 'Der Rechtstexte-Generator wird in diesem Netzwerk zentral auf der Master-Site verwaltet. Auf Unterseiten verwenden Sie bitte die Ausgabe-Shortcodes.', 'frontend-rechtstexte-generator' );
			return '<div class="frg-notice frg-notice--warning">' . esc_html( $message ) . '</div>';
		}

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
		$compliance_warnings = $this->get_completeness_warnings( $data );
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
		$module_meta = $this->generator->get_module_meta();
		$current_module_version = sanitize_text_field( $module_meta['module_version'] ?? '' );
		$generated_module_version = sanitize_text_field( $data['generated_module_version'] ?? '' );

		if ( '' === $generated_module_version || $generated_module_version !== $current_module_version ) {
			return true;
		}

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
				'company_name', 'legal_form', 'legal_form_other', 'first_name', 'last_name', 'street', 'zip', 'city', 'country', 'email', 'phone',
			'website_url', 'register_court', 'register_number', 'vat_id', 'business_id', 'responsible_name',
			'responsible_address', 'professional_chamber', 'professional_title', 'professional_awarded_in',
			'professional_rules', 'supervisory_authority', 'liability_insurer', 'liability_scope', 'liability_insurer_address',
				'hosting_provider', 'hosting_provider_address', 'server_location', 'hosting_av_contract',
				'server_infrastructure_provider', 'server_infrastructure_type', 'server_infrastructure_address',
				'controller_name', 'controller_representative', 'controller_street', 'controller_zip', 'controller_city', 'controller_country', 'controller_email', 'controller_phone',
				'data_protection_officer_name', 'data_protection_officer_email', 'data_protection_officer_phone', 'data_protection_officer_address', 'privacy_processing_purposes',
			'privacy_legal_basis', 'privacy_storage_general', 'privacy_recipient_categories', 'privacy_third_country_transfer',
			'privacy_supervisory_authority_state', 'privacy_supervisory_authority_name', 'privacy_supervisory_authority_address', 'privacy_supervisory_authority_url',
			'backup_destination', 'backup_storage_provider', 'backup_storage_address', 'backup_retention', 'ai_chatbot_privacy_url', 'social_media_integration',
		);
		$bool_fields = array(
			'has_trade_register', 'has_vat_id', 'has_responsible_content', 'has_editorial_content', 'has_professional_info', 'has_professional_award_location',
			'has_liability_insurance', 'controller_same_as_operator', 'has_data_protection_officer', 'has_third_country_transfer',
		);
		$data = array();

		foreach ( $text_fields as $field ) {
			$value = isset( $raw[ $field ] ) ? wp_unslash( $raw[ $field ] ) : '';
			if ( 'email' === $field || 'data_protection_officer_email' === $field || 'controller_email' === $field ) {
				$data[ $field ] = sanitize_email( $value );
			} elseif ( 'website_url' === $field || 'ai_chatbot_privacy_url' === $field || 'privacy_supervisory_authority_url' === $field ) {
				$data[ $field ] = esc_url_raw( $value );
			} elseif ( 'responsible_address' === $field || 'professional_rules' === $field || 'liability_insurer_address' === $field || 'hosting_provider_address' === $field || 'server_infrastructure_address' === $field || 'data_protection_officer_address' === $field || 'privacy_supervisory_authority_address' === $field || 'backup_storage_address' === $field ) {
				$data[ $field ] = sanitize_textarea_field( $value );
			} else {
				$data[ $field ] = sanitize_text_field( $value );
			}
		}

		foreach ( $bool_fields as $field ) {
			$data[ $field ] = ! empty( $raw[ $field ] );
		}

		if ( ! FRG_Authorities::is_valid_state( $data['privacy_supervisory_authority_state'] ?? '' ) ) {
			$data['privacy_supervisory_authority_state'] = '';
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
				'cloudflare', 'recaptcha', 'hcaptcha', 'cloudflare_turnstile', 'borlabs_cookie', 'real_cookie_banner', 'complianz',
				'cookieyes', 'elementor', 'gravity_forms', 'contact_form_7', 'wpforms', 'wordfence',
				'ithemes_security', 'updraftplus', 'wpvivid', 'mailchimp', 'brevo', 'sendinblue', 'cleverreach',
				'facebook', 'instagram', 'linkedin', 'xing', 'tiktok', 'microsoft_clarity', 'calendly',
					'jotform', 'trustpilot', 'smtp_service', 'ai_chatbot', 'openai', 'anthropic', 'ai_transparency_notice',
			),
			'services'
		);
		$data['service_details'] = $this->sanitize_service_details( $raw['service_details'] ?? array() );
		$data['social_media_integration'] = in_array( $data['social_media_integration'] ?? '', array( 'links', 'embeds' ), true )
			? $data['social_media_integration']
			: 'links';

		return $data;
	}

	private function sanitize_service_details( $raw_details ): array {
		if ( ! is_array( $raw_details ) ) {
			return array();
		}

		$allowed_services = array(
			'google_fonts_external', 'google_maps', 'youtube', 'vimeo', 'google_analytics', 'google_tag_manager',
			'google_ads_conversion_tracking', 'meta_pixel', 'matomo', 'microsoft_clarity',
			'cloudflare', 'recaptcha', 'hcaptcha', 'cloudflare_turnstile', 'calendly', 'jotform', 'trustpilot',
			'smtp_service', 'ai_chatbot', 'newsletter_provider',
		);
		$textarea_fields = array( 'address', 'purpose', 'data_categories', 'legal_basis', 'recipients', 'retention', 'third_country', 'transfer_basis' );
		$text_fields = array( 'provider', 'av_contract', 'consent' );
		$details = array();

		foreach ( $allowed_services as $service_key ) {
			if ( empty( $raw_details[ $service_key ] ) || ! is_array( $raw_details[ $service_key ] ) ) {
				continue;
			}

			$item = array();
			foreach ( $text_fields as $field ) {
				$item[ $field ] = sanitize_text_field( wp_unslash( $raw_details[ $service_key ][ $field ] ?? '' ) );
			}
			foreach ( $textarea_fields as $field ) {
				$item[ $field ] = sanitize_textarea_field( wp_unslash( $raw_details[ $service_key ][ $field ] ?? '' ) );
			}
			$item['privacy_url'] = esc_url_raw( wp_unslash( $raw_details[ $service_key ]['privacy_url'] ?? '' ) );

			if ( array_filter( $item, static fn( $value ): bool => '' !== trim( (string) $value ) ) ) {
				$details[ $service_key ] = $item;
			}
		}

		return $details;
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

		$register_forms = array( 'GmbH', 'UG', 'e.K.', 'OHG', 'KG', 'GmbH & Co. KG', 'AG', 'eG', 'PartG' );
		if ( in_array( $data['legal_form'] ?? '', $register_forms, true ) && ( empty( $data['register_court'] ) || empty( $data['register_number'] ) ) ) {
			$errors[] = __( 'Für diese Rechtsform sind Registergericht bzw. Registerstelle und Registernummer erforderlich.', 'frontend-rechtstexte-generator' );
		}

		if ( 'sonstige' === ( $data['legal_form'] ?? '' ) && empty( $data['legal_form_other'] ) ) {
			$errors[] = __( 'Bitte die konkrete sonstige Rechtsform angeben.', 'frontend-rechtstexte-generator' );
		}

		if ( ! empty( $data['has_vat_id'] ) && empty( $data['vat_id'] ) ) {
			$errors[] = __( 'Bitte Umsatzsteuer-ID angeben.', 'frontend-rechtstexte-generator' );
		}

		if ( ! empty( $data['has_editorial_content'] ) && ( empty( $data['responsible_name'] ) || empty( $data['responsible_address'] ) ) ) {
			$errors[] = __( 'Bitte für journalistisch-redaktionelle Inhalte Name und Anschrift der verantwortlichen Person angeben.', 'frontend-rechtstexte-generator' );
		}

		if ( ! empty( $data['has_liability_insurance'] ) && ( empty( $data['liability_insurer'] ) || empty( $data['liability_scope'] ) ) ) {
			$errors[] = __( 'Bitte mindestens Versicherer und räumlichen Geltungsbereich der Berufshaftpflichtversicherung angeben.', 'frontend-rechtstexte-generator' );
		}

		if ( ! empty( $data['has_data_protection_officer'] ) && empty( $data['data_protection_officer_email'] ) ) {
			$errors[] = __( 'Bitte mindestens eine E-Mail-Adresse für den Datenschutzbeauftragten angeben.', 'frontend-rechtstexte-generator' );
		}

		if ( empty( $data['controller_same_as_operator'] ) ) {
			$controller_required = array(
				'controller_name'    => __( 'Bitte den abweichenden Verantwortlichen angeben.', 'frontend-rechtstexte-generator' ),
				'controller_street'  => __( 'Bitte die Straße des abweichenden Verantwortlichen angeben.', 'frontend-rechtstexte-generator' ),
				'controller_zip'     => __( 'Bitte die PLZ des abweichenden Verantwortlichen angeben.', 'frontend-rechtstexte-generator' ),
				'controller_city'    => __( 'Bitte den Ort des abweichenden Verantwortlichen angeben.', 'frontend-rechtstexte-generator' ),
				'controller_country' => __( 'Bitte das Land des abweichenden Verantwortlichen angeben.', 'frontend-rechtstexte-generator' ),
				'controller_email'   => __( 'Bitte die E-Mail-Adresse des abweichenden Verantwortlichen angeben.', 'frontend-rechtstexte-generator' ),
			);

			foreach ( $controller_required as $key => $message ) {
				if ( empty( $data[ $key ] ) ) {
					$errors[] = $message;
				}
			}
		}

		return $errors;
	}

	private function get_completeness_warnings( array $data ): array {
		$labels = array(
			'google_fonts_external'         => 'Google Fonts extern',
			'google_maps'                    => 'Google Maps',
			'youtube'                        => 'YouTube',
			'vimeo'                          => 'Vimeo',
			'google_analytics'               => 'Google Analytics',
			'google_tag_manager'             => 'Google Tag Manager',
			'google_ads_conversion_tracking' => 'Google Ads Conversion Tracking',
			'meta_pixel'                     => 'Meta Pixel',
			'matomo'                         => 'Matomo',
			'microsoft_clarity'              => 'Microsoft Clarity',
			'cloudflare'                     => 'Cloudflare',
			'recaptcha'                      => 'reCAPTCHA',
			'hcaptcha'                       => 'hCaptcha',
			'calendly'                       => 'Calendly',
			'jotform'                        => 'Jotform',
			'trustpilot'                     => 'Trustpilot',
			'smtp_service'                   => __( 'SMTP / E-Mail-Versanddienst', 'frontend-rechtstexte-generator' ),
			'ai_chatbot'                     => __( 'Website-KI-Bot / KI-Assistent', 'frontend-rechtstexte-generator' ),
		);
		$details = is_array( $data['service_details'] ?? null ) ? $data['service_details'] : array();
		$warnings = array();

		foreach ( $labels as $key => $label ) {
			$is_selected = ! empty( $data['services'][ $key ] );
			if ( 'ai_chatbot' === $key ) {
				$is_selected = $is_selected || ! empty( $data['services']['openai'] ) || ! empty( $data['services']['anthropic'] );
			}
			if ( ! $is_selected ) {
				continue;
			}

			foreach ( array( 'provider', 'purpose', 'legal_basis', 'retention' ) as $field ) {
				if ( empty( $details[ $key ][ $field ] ) ) {
					$warnings[] = sprintf(
						/* translators: %s: service name */
						__( 'Für %s fehlen noch konkrete Angaben zu Anbieter, Zweck, Rechtsgrundlage oder Speicherdauer.', 'frontend-rechtstexte-generator' ),
						$label
					);
					break;
				}
			}
		}

		if (
			( ! empty( $data['services']['ai_chatbot'] ) || ! empty( $data['services']['openai'] ) || ! empty( $data['services']['anthropic'] ) ) &&
			empty( $data['services']['ai_transparency_notice'] )
		) {
			$warnings[] = __( 'Für den KI-Bot ist noch nicht bestätigt, dass Besucher klar auf die Interaktion mit einem KI-System hingewiesen werden.', 'frontend-rechtstexte-generator' );
		}
		if (
			! empty( $data['services']['ai_chatbot'] ) &&
			empty( $data['services']['openai'] ) &&
			empty( $data['services']['anthropic'] )
		) {
			$warnings[] = __( 'Der KI-Bot ist aktiviert, aber es wurde weder OpenAI noch Anthropic als tatsächlich eingesetzter KI-Anbieter ausgewählt. Der Abschnitt wird bis dahin nicht veröffentlicht.', 'frontend-rechtstexte-generator' );
		}

		if ( empty( $data['privacy_supervisory_authority_name'] ) ) {
			$warnings[] = __( 'Die konkret zuständige Datenschutzaufsichtsbehörde ist noch nicht eingetragen.', 'frontend-rechtstexte-generator' );
		}

		if ( ! empty( $data['has_third_country_transfer'] ) && empty( $data['privacy_third_country_transfer'] ) ) {
			$warnings[] = __( 'Drittlandtransfer ist aktiviert, aber der konkrete Transfer, das Empfängerland und die verwendete Garantie fehlen.', 'frontend-rechtstexte-generator' );
		}

		if ( 'Drittland' === ( $data['server_location'] ?? '' ) && empty( $data['has_third_country_transfer'] ) ) {
			$warnings[] = __( 'Der Serverstandort ist als Drittland angegeben, der allgemeine Drittlandtransfer wurde aber nicht aktiviert.', 'frontend-rechtstexte-generator' );
		}

		if ( 'Ja' !== ( $data['hosting_av_contract'] ?? '' ) ) {
			$warnings[] = __( 'Für den Hosting-Anbieter ist kein bestätigter AV-Vertrag hinterlegt. Diese Angabe erscheint deshalb nicht als positive Aussage im veröffentlichten Text.', 'frontend-rechtstexte-generator' );
		}

		if ( ! empty( $data['services']['updraftplus'] ) || ! empty( $data['services']['wpvivid'] ) ) {
			if ( empty( $data['backup_destination'] ) ) {
				$warnings[] = __( 'Für die Backups fehlt noch der tatsächliche Speicherort, zum Beispiel eigener Server oder externer Cloud-Speicher.', 'frontend-rechtstexte-generator' );
			}
			if ( empty( $data['backup_retention'] ) ) {
				$warnings[] = __( 'Für die Backups fehlt noch die Aufbewahrungsdauer oder ein Löschkriterium.', 'frontend-rechtstexte-generator' );
			}
		}

		if ( ! empty( $data['services']['google_fonts_external'] ) && ! empty( $data['services']['google_fonts_local'] ) ) {
			$warnings[] = __( 'Google Fonts wurde lokal und extern ausgewählt. Für die Ausgabe wird die lokale Variante verwendet.', 'frontend-rechtstexte-generator' );
		}

		if ( ! empty( $data['features']['contact_form'] ) ) {
			$form_services = array( 'elementor', 'contact_form_7', 'gravity_forms', 'wpforms' );
			$has_form_service = false;
			foreach ( $form_services as $form_service ) {
				if ( ! empty( $data['services'][ $form_service ] ) ) {
					$has_form_service = true;
					break;
				}
			}
			if ( ! $has_form_service ) {
				$warnings[] = __( 'Für das Kontaktformular ist noch kein konkretes Formularsystem ausgewählt.', 'frontend-rechtstexte-generator' );
			}

			if (
				empty( $data['services']['recaptcha'] ) &&
				empty( $data['services']['hcaptcha'] ) &&
				empty( $data['services']['cloudflare_turnstile'] )
			) {
				$warnings[] = __( 'Für das Kontaktformular ist kein externer Spam-Schutz ausgewählt. Falls reCAPTCHA, hCaptcha, Cloudflare Turnstile oder ein vergleichbarer Dienst eingesetzt wird, muss dieser zusätzlich angegeben werden.', 'frontend-rechtstexte-generator' );
			}
		}

		if ( ! empty( $data['services']['vimeo'] ) && 'Vor Einwilligung blockiert' !== ( $details['vimeo']['consent'] ?? '' ) ) {
			$warnings[] = __( 'Vimeo ist ausgewählt, aber die Blockierung vor Einwilligung wurde noch nicht bestätigt.', 'frontend-rechtstexte-generator' );
		}
		if (
			! empty( $data['services']['vimeo'] ) &&
			( empty( $details['vimeo']['third_country'] ) || empty( $details['vimeo']['transfer_basis'] ) )
		) {
			$warnings[] = __( 'Für Vimeo fehlen konkrete Angaben zum Drittlandbezug oder zur verwendeten Transfergarantie.', 'frontend-rechtstexte-generator' );
		}

		if ( ! empty( $data['services']['smtp_service'] ) && empty( $details['smtp_service']['provider'] ) ) {
			$warnings[] = __( 'Für den E-Mail-Versand fehlt der tatsächlich eingesetzte SMTP- oder Mail-Anbieter.', 'frontend-rechtstexte-generator' );
		}

		if ( ! empty( $data['features']['social_media_profiles'] ) && 'embeds' === ( $data['social_media_integration'] ?? 'links' ) ) {
			$warnings[] = __( 'Eingebettete Social-Media-Inhalte müssen technisch geprüft und gegebenenfalls bis zur Einwilligung blockiert werden.', 'frontend-rechtstexte-generator' );
		}

		return $warnings;
	}
}

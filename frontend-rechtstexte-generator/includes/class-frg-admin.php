<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class FRG_Admin {
	private FRG_Storage $storage;
	private FRG_Generator $generator;

	public function __construct( FRG_Storage $storage, FRG_Generator $generator ) {
		$this->storage   = $storage;
		$this->generator = $generator;
	}

	public function register_menu(): void {
		add_options_page(
			__( 'Rechtstexte Generator', 'frontend-rechtstexte-generator' ),
			__( 'Rechtstexte Generator', 'frontend-rechtstexte-generator' ),
			'manage_options',
			'frg-settings',
			array( $this, 'render_page' )
		);
	}

	public function enqueue_assets( string $hook ): void {
		if ( 'settings_page_frg-settings' !== $hook ) {
			return;
		}

		wp_enqueue_style( 'frg-admin', FRG_PLUGIN_URL . 'assets/css/admin.css', array(), FRG_VERSION );
		wp_enqueue_script( 'frg-admin', FRG_PLUGIN_URL . 'assets/js/admin.js', array(), FRG_VERSION, true );
		wp_localize_script(
			'frg-admin',
			'frgAdmin',
			array(
				'ajaxUrl'             => admin_url( 'admin-ajax.php' ),
				'nonce'               => wp_create_nonce( 'frg_admin_nonce' ),
				'generatingMessage'   => __( 'KI-Entwurf wird erzeugt...', 'frontend-rechtstexte-generator' ),
				'adoptingMessage'     => __( 'Entwurf wird als Live-Override übernommen...', 'frontend-rechtstexte-generator' ),
				'generateError'       => __( 'Der KI-Entwurf konnte nicht erzeugt werden.', 'frontend-rechtstexte-generator' ),
				'adoptError'          => __( 'Der Entwurf konnte nicht übernommen werden.', 'frontend-rechtstexte-generator' ),
				'missingApiKey'       => __( 'Es ist kein OpenAI API-Key hinterlegt.', 'frontend-rechtstexte-generator' ),
				'draftUpdated'        => __( 'Der Entwurf wurde aktualisiert.', 'frontend-rechtstexte-generator' ),
				'overrideUpdated'     => __( 'Der Live-Override wurde aktualisiert.', 'frontend-rechtstexte-generator' ),
				'copyImpressumMessage'=> __( 'Das Impressum wurde als HTML kopiert.', 'frontend-rechtstexte-generator' ),
				'copyPrivacyMessage'  => __( 'Die Datenschutzerklärung wurde als HTML kopiert.', 'frontend-rechtstexte-generator' ),
				'copyMissingMessage'  => __( 'Es ist kein HTML-Inhalt zum Kopieren vorhanden.', 'frontend-rechtstexte-generator' ),
			)
		);
	}

	public function render_page(): void {
		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}

		$this->handle_actions();

		$settings = get_option( 'frg_settings', array() );
		$block_registry = $this->generator->get_block_registry();
		$profiles = $this->storage->get_all_profiles();
		$view_id  = isset( $_GET['profile_id'] ) ? absint( $_GET['profile_id'] ) : 0; // phpcs:ignore WordPress.Security.NonceVerification.Recommended
		$profile  = $view_id ? $this->storage->get_profile_by_id( $view_id ) : null;
		$registry_export = wp_json_encode( $block_registry, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE );
		$profile_export  = $profile ? wp_json_encode(
			array(
				'profile_name' => $profile['profile_name'],
				'data'         => $profile['data'],
			),
			JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE
		) : '';

		include FRG_PLUGIN_DIR . 'templates/admin-settings.php';
	}

	private function handle_actions(): void {
		if ( isset( $_POST['frg_save_settings'] ) ) {
			check_admin_referer( 'frg_save_settings_action', 'frg_save_settings_nonce' );

			$settings = array(
				'legal_notice'      => wp_kses_post( wp_unslash( $_POST['legal_notice'] ?? '' ) ),
				'impressum_page'    => sanitize_text_field( wp_unslash( $_POST['impressum_page'] ?? '' ) ),
				'privacy_page'      => sanitize_text_field( wp_unslash( $_POST['privacy_page'] ?? '' ) ),
				'openai_api_key'    => sanitize_text_field( wp_unslash( $_POST['openai_api_key'] ?? '' ) ),
				'openai_model'      => sanitize_text_field( wp_unslash( $_POST['openai_model'] ?? 'gpt-5.2' ) ),
				'impressum_page_id' => absint( get_option( 'frg_settings', array() )['impressum_page_id'] ?? 0 ),
				'privacy_page_id'   => absint( get_option( 'frg_settings', array() )['privacy_page_id'] ?? 0 ),
			);
			update_option( 'frg_settings', $settings );
		}

		if ( isset( $_POST['frg_delete_profile'], $_POST['profile_id'] ) ) {
			check_admin_referer( 'frg_delete_profile_action', 'frg_delete_profile_nonce' );
			$this->storage->delete_profile( absint( $_POST['profile_id'] ) );
		}

		if ( isset( $_POST['frg_save_block_registry'] ) ) {
			check_admin_referer( 'frg_save_block_registry_action', 'frg_save_block_registry_nonce' );
			$raw = isset( $_POST['blocks'] ) && is_array( $_POST['blocks'] ) ? wp_unslash( $_POST['blocks'] ) : array();
			$this->generator->save_block_registry( $raw );
		}

		if ( isset( $_POST['frg_adopt_block_draft'], $_POST['block_key'] ) ) {
			check_admin_referer( 'frg_adopt_block_draft_action', 'frg_adopt_block_draft_nonce' );
			$block_key = sanitize_key( wp_unslash( $_POST['block_key'] ) );
			$registry  = $this->generator->get_block_registry();
			if ( isset( $registry[ $block_key ] ) && ! empty( $registry[ $block_key ]['draft_text'] ) ) {
				$raw = array();
				foreach ( $registry as $key => $block ) {
					$raw[ $key ] = array(
						'status'        => $block['status'],
						'last_reviewed' => $block['last_reviewed'],
						'review_due_at' => $block['review_due_at'],
						'admin_notes'   => $block['admin_notes'],
						'draft_text'    => $block['draft_text'],
						'override_text' => $block['override_text'] ?? '',
						'legal_basis'   => $block['legal_basis'],
					);
				}
				$raw[ $block_key ]['override_text'] = $registry[ $block_key ]['draft_text'];
				$raw[ $block_key ]['status']        = 'approved';
				$raw[ $block_key ]['last_reviewed'] = current_time( 'Y-m-d' );
				$this->generator->save_block_registry( $raw );
			}
		}

		if ( isset( $_POST['frg_import_block_registry'] ) ) {
			check_admin_referer( 'frg_import_block_registry_action', 'frg_import_block_registry_nonce' );
			$payload = json_decode( (string) wp_unslash( $_POST['block_registry_json'] ?? '' ), true );
			if ( is_array( $payload ) ) {
				$this->generator->save_block_registry( $payload );
			}
		}

		if ( isset( $_POST['frg_import_profile'] ) ) {
			check_admin_referer( 'frg_import_profile_action', 'frg_import_profile_nonce' );
			$payload = json_decode( (string) wp_unslash( $_POST['profile_json'] ?? '' ), true );
			if ( is_array( $payload ) && ! empty( $payload['data'] ) && is_array( $payload['data'] ) ) {
				$profile_name = ! empty( $payload['profile_name'] ) ? sanitize_text_field( $payload['profile_name'] ) : __( 'Importiertes Profil', 'frontend-rechtstexte-generator' );
				$this->storage->import_profile( get_current_user_id(), $profile_name, $payload['data'] );
			}
		}

		if ( isset( $_POST['frg_generate_block_draft'], $_POST['block_key'] ) ) {
			check_admin_referer( 'frg_generate_block_draft_action', 'frg_generate_block_draft_nonce' );
			$block_key = sanitize_key( wp_unslash( $_POST['block_key'] ) );
			$registry  = $this->generator->get_block_registry();
			if ( isset( $registry[ $block_key ] ) ) {
				$draft = $this->generate_ai_block_draft( $block_key, $registry[ $block_key ] );
				if ( '' !== $draft ) {
					$raw = array();
					foreach ( $registry as $key => $block ) {
						$raw[ $key ] = array(
							'status'        => $block['status'],
							'last_reviewed' => $block['last_reviewed'],
							'review_due_at' => $block['review_due_at'],
							'admin_notes'   => $block['admin_notes'],
							'draft_text'    => $block['draft_text'],
							'override_text' => $block['override_text'] ?? '',
							'legal_basis'   => $block['legal_basis'],
						);
					}
					$raw[ $block_key ]['draft_text'] = $draft;
					$raw[ $block_key ]['status']     = 'draft';
					$this->generator->save_block_registry( $raw );
				}
			}
		}
	}

	public function ajax_generate_block_draft(): void {
		$this->assert_admin_ajax_permissions();

		$block_key = isset( $_POST['block_key'] ) ? sanitize_key( wp_unslash( $_POST['block_key'] ) ) : '';
		if ( '' === $block_key ) {
			wp_send_json_error( array( 'message' => __( 'Kein Block uebergeben.', 'frontend-rechtstexte-generator' ) ), 400 );
		}

		$registry = $this->generator->get_block_registry();
		if ( ! isset( $registry[ $block_key ] ) ) {
			wp_send_json_error( array( 'message' => __( 'Unbekannter Block.', 'frontend-rechtstexte-generator' ) ), 404 );
		}

		$draft = $this->generate_ai_block_draft( $block_key, $registry[ $block_key ] );
		if ( '' === $draft ) {
			wp_send_json_error( array( 'message' => __( 'Es konnte kein KI-Entwurf erzeugt werden. Bitte API-Key, Modell und Verbindung pruefen.', 'frontend-rechtstexte-generator' ) ), 500 );
		}

		$raw = $this->build_registry_payload( $registry );
		$raw[ $block_key ]['draft_text'] = $draft;
		$raw[ $block_key ]['status']     = 'draft';
		$this->generator->save_block_registry( $raw );

		wp_send_json_success(
			array(
				'message'    => __( 'Der Entwurf wurde aktualisiert.', 'frontend-rechtstexte-generator' ),
				'draft_text' => $draft,
				'draft_html' => $this->format_admin_rich_text( $draft ),
				'status'     => 'draft',
			)
		);
	}

	public function ajax_adopt_block_draft(): void {
		$this->assert_admin_ajax_permissions();

		$block_key = isset( $_POST['block_key'] ) ? sanitize_key( wp_unslash( $_POST['block_key'] ) ) : '';
		if ( '' === $block_key ) {
			wp_send_json_error( array( 'message' => __( 'Kein Block uebergeben.', 'frontend-rechtstexte-generator' ) ), 400 );
		}

		$registry = $this->generator->get_block_registry();
		$current_draft = isset( $_POST['draft_text'] ) ? wp_kses_post( wp_unslash( $_POST['draft_text'] ) ) : ( $registry[ $block_key ]['draft_text'] ?? '' );
		if ( '' === trim( wp_strip_all_tags( $current_draft ) ) ) {
			wp_send_json_error( array( 'message' => __( 'Fuer diesen Block liegt kein Entwurf vor.', 'frontend-rechtstexte-generator' ) ), 400 );
		}

		$raw = $this->build_registry_payload( $registry );
		$raw[ $block_key ]['draft_text']    = $current_draft;
		$raw[ $block_key ]['override_text'] = $current_draft;
		$raw[ $block_key ]['status']        = 'approved';
		$raw[ $block_key ]['last_reviewed'] = current_time( 'Y-m-d' );
		$this->generator->save_block_registry( $raw );

		wp_send_json_success(
			array(
				'message'          => __( 'Der Live-Override wurde aktualisiert.', 'frontend-rechtstexte-generator' ),
				'override_text'    => $current_draft,
				'draft_html'       => $this->format_admin_rich_text( $current_draft ),
				'override_html'    => $this->get_block_preview( $block_key ),
				'status'           => 'approved',
				'last_reviewed'    => current_time( 'Y-m-d' ),
			)
		);
	}

	private function get_block_preview( string $block_key ): string {
		$sample = array(
			'company_name'                    => 'Musterfirma GmbH',
			'legal_form'                      => 'GmbH',
			'first_name'                      => 'Max',
			'last_name'                       => 'Mustermann',
			'street'                          => 'Musterstraße 1',
			'zip'                             => '10115',
			'city'                            => 'Berlin',
			'country'                         => 'Deutschland',
			'email'                           => 'info@example.com',
			'phone'                           => '+49 30 1234567',
			'website_url'                     => home_url( '/' ),
			'register_court'                  => 'Amtsgericht Berlin',
			'register_number'                 => 'HRB 123456',
			'vat_id'                          => 'DE123456789',
			'business_id'                     => 'DE987654321',
			'responsible_name'                => 'Max Mustermann',
			'responsible_address'             => "Musterstraße 1\n10115 Berlin",
			'professional_chamber'            => 'Musterkammer',
			'professional_title'              => 'Berufsbezeichnung',
			'professional_awarded_in'         => 'Deutschland',
			'professional_rules'              => 'Berufsordnung und weitere berufsrechtliche Regelungen.',
			'supervisory_authority'           => 'Zuständige Aufsichtsbehörde',
			'data_protection_officer_name'    => 'Erika Datenschutz',
			'data_protection_officer_email'   => 'datenschutz@example.com',
			'hosting_provider'                => 'Host Europe',
			'server_location'                 => 'EU',
			'hosting_av_contract'             => 'Ja',
			'privacy_processing_purposes'     => 'Bereitstellung der Website, Kommunikation, Vertragserfuellung und IT-Sicherheit.',
			'privacy_legal_basis'             => 'Art. 6 Abs. 1 lit. a, b, c und f DSGVO.',
			'privacy_recipient_categories'    => 'Hosting, IT-Dienstleister, Kommunikationsanbieter.',
			'privacy_storage_general'         => 'Speicherung nur solange erforderlich oder gesetzlich vorgeschrieben.',
			'privacy_third_country_transfer'  => 'Nur bei einzelnen Diensten und unter Beachtung der Art. 44 ff. DSGVO.',
			'features'                        => array(
				'training_portal'       => true,
				'training_progress'     => true,
				'training_tests'        => true,
				'training_certificates' => true,
				'employee_training'     => true,
				'scorm_tracking'        => true,
				'certificate_download'  => true,
				'mandatory_training_proof' => true,
				'trainer_manager_access'=> true,
				'tenant_access'         => true,
			),
		);

		$registry = $this->generator->get_block_registry();
		if ( ! isset( $registry[ $block_key ] ) ) {
			return '';
		}

		return $this->generator->render_registry_block( $block_key, $sample );
	}

	private function build_registry_payload( array $registry ): array {
		$raw = array();

		foreach ( $registry as $key => $block ) {
			$raw[ $key ] = array(
				'status'        => $block['status'],
				'last_reviewed' => $block['last_reviewed'],
				'review_due_at' => $block['review_due_at'],
				'admin_notes'   => $block['admin_notes'],
				'draft_text'    => $block['draft_text'],
				'override_text' => $block['override_text'] ?? '',
				'legal_basis'   => $block['legal_basis'],
			);
		}

		return $raw;
	}

	private function assert_admin_ajax_permissions(): void {
		check_ajax_referer( 'frg_admin_nonce', 'nonce' );

		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( array( 'message' => __( 'Keine Berechtigung.', 'frontend-rechtstexte-generator' ) ), 403 );
		}
	}

	private function format_admin_rich_text( string $content ): string {
		$content = trim( $content );
		if ( '' === $content ) {
			return '';
		}

		if ( preg_match( '/<(p|h2|h3|h4|ul|ol|li|div|section|table|blockquote|br)\b/i', $content ) ) {
			return wp_kses_post( $content );
		}

		return wpautop( wp_kses_post( $content ) );
	}

	private function generate_ai_block_draft( string $block_key, array $block ): string {
		$settings = get_option( 'frg_settings', array() );
		$api_key  = sanitize_text_field( $settings['openai_api_key'] ?? '' );
		$model    = sanitize_text_field( $settings['openai_model'] ?? 'gpt-5.2' );

		if ( '' === $api_key ) {
			return '';
		}

		$placeholder_details = $this->generator->get_block_placeholder_details( $block_key );
		$placeholder_instruction = '';
		if ( ! empty( $placeholder_details ) ) {
			$lines = array();
			foreach ( $placeholder_details as $placeholder => $description ) {
				$lines[] = $placeholder . ' = ' . $description;
			}
			$placeholder_instruction = "Verwende die folgenden Platzhalter exakt so, wenn konkrete Angaben im Text auftauchen sollen:\n- " . implode( "\n- ", $lines ) . "\nLasse diese Platzhalter im Ergebnis stehen und ersetze sie nicht durch Beispielwerte.";
		}

		$prompt = sprintf(
			"Erstelle einen ausfuehrlichen, professionell formulierten deutschen Textbaustein fuer eine %s. Blocktitel: %s.\nRechtsgrundlagen: %s.\nConsent erforderlich: %s.\nDrittlandtransfer moeglich: %s.\n%s\nWichtig: kein Rechtsberatungsversprechen, keine Beispielunternehmen, keine Fantasiedaten. Wenn fuer diesen Block konkrete Angaben benoetigt werden, nutze ausschliesslich die vorgegebenen Platzhalter. HTML ist erlaubt, bevorzuge <h3>, <p> und bei Bedarf <ul><li>. Nenne einschlaegige Rechtsgrundlagen dort, wo es textlich sinnvoll ist. Formuliere den Text so, wie man ihn typischerweise in einer ausfuehrlichen Datenschutzerklaerung oder in einem Impressum verwendet.",
			'privacy' === $block['area'] ? 'Datenschutzerklaerung' : 'Impressum',
			$block['title'],
			implode( ', ', $block['legal_basis'] ?? array() ),
			! empty( $block['requires_consent'] ) ? 'ja' : 'nein',
			! empty( $block['third_country_possible'] ) ? 'ja' : 'nein',
			$placeholder_instruction
		);

		$response = wp_remote_post(
			'https://api.openai.com/v1/responses',
			array(
				'timeout' => 45,
				'headers' => array(
					'Authorization' => 'Bearer ' . $api_key,
					'Content-Type'  => 'application/json',
				),
				'body'    => wp_json_encode(
					array(
						'model'       => $model,
						'input'       => $prompt,
						'max_output_tokens' => 1200,
					)
				),
			)
		);

		if ( is_wp_error( $response ) ) {
			return '';
		}

		$body = json_decode( (string) wp_remote_retrieve_body( $response ), true );
		if ( ! empty( $body['output_text'] ) && is_string( $body['output_text'] ) ) {
			return trim( $body['output_text'] );
		}

		if ( ! empty( $body['output'][0]['content'][0]['text'] ) && is_string( $body['output'][0]['content'][0]['text'] ) ) {
			return trim( $body['output'][0]['content'][0]['text'] );
		}

		return '';
	}
}

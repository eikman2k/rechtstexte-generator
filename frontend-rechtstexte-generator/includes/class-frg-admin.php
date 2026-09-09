<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class FRG_Admin {
	private FRG_Storage $storage;
	private FRG_Generator $generator;
	private FRG_Block_Feed $block_feed;
	private FRG_Frontend_Wizard $wizard;

	public function __construct( FRG_Storage $storage, FRG_Generator $generator, FRG_Block_Feed $block_feed, FRG_Frontend_Wizard $wizard ) {
		$this->storage    = $storage;
		$this->generator  = $generator;
		$this->block_feed = $block_feed;
		$this->wizard     = $wizard;
	}

	public function register_menu(): void {
		add_options_page(
			__( 'Rechtstexte Generator', 'frontend-rechtstexte-generator' ),
			__( 'Rechtstexte Generator', 'frontend-rechtstexte-generator' ),
			'manage_options',
			'frg-settings',
			array( $this, 'render_page' )
		);

		add_options_page(
			__( 'Rechtstexte erfassen', 'frontend-rechtstexte-generator' ),
			__( 'Rechtstexte erfassen', 'frontend-rechtstexte-generator' ),
			'manage_options',
			'frg-wizard',
			array( $this, 'render_wizard_page' )
		);
	}

	public function enqueue_assets( string $hook ): void {
		if ( 'settings_page_frg-settings' !== $hook && 'settings_page_frg-network-settings' !== $hook && 'settings_page_frg-wizard' !== $hook ) {
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
				'adoptingMessage'     => __( 'Entwurf wird veröffentlicht …', 'frontend-rechtstexte-generator' ),
				'generateError'       => __( 'Der KI-Entwurf konnte nicht erzeugt werden.', 'frontend-rechtstexte-generator' ),
				'adoptError'          => __( 'Der Entwurf konnte nicht übernommen werden.', 'frontend-rechtstexte-generator' ),
				'missingApiKey'       => __( 'Es ist kein OpenAI API-Key hinterlegt.', 'frontend-rechtstexte-generator' ),
				'draftUpdated'        => __( 'Der Entwurf wurde aktualisiert.', 'frontend-rechtstexte-generator' ),
				'overrideUpdated'     => __( 'Der neue Text wurde veröffentlicht.', 'frontend-rechtstexte-generator' ),
				'publishedMessage'    => __( 'Ihr eigener Text ist veröffentlicht.', 'frontend-rechtstexte-generator' ),
				'filterCount'         => __( '%d Bausteine', 'frontend-rechtstexte-generator' ),
				'statusLabels'        => array(
					'review_needed'      => __( 'Prüfung offen', 'frontend-rechtstexte-generator' ),
					'draft'              => __( 'Entwurf bereit', 'frontend-rechtstexte-generator' ),
					'editorial_approved' => __( 'Im Einsatz', 'frontend-rechtstexte-generator' ),
					'legal_reviewed'     => __( 'Juristisch geprüft', 'frontend-rechtstexte-generator' ),
				),
				'copyImpressumMessage'=> __( 'Das Impressum wurde als HTML kopiert.', 'frontend-rechtstexte-generator' ),
				'copyPrivacyMessage'  => __( 'Die Datenschutzerklärung wurde als HTML kopiert.', 'frontend-rechtstexte-generator' ),
				'copyMissingMessage'  => __( 'Es ist kein HTML-Inhalt zum Kopieren vorhanden.', 'frontend-rechtstexte-generator' ),
				'valueCopiedMessage'   => __( 'Der Wert wurde kopiert.', 'frontend-rechtstexte-generator' ),
				'copyValueMissingMessage' => __( 'Es ist noch kein Wert zum Kopieren vorhanden. Bitte zuerst speichern.', 'frontend-rechtstexte-generator' ),
			)
		);
	}

	public function render_wizard_page(): void {
		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}
		?>
		<div class="wrap frg-admin-wizard-shell">
			<div class="frg-admin-wizard-header">
				<div>
					<h1><?php esc_html_e( 'Rechtstexte erfassen', 'frontend-rechtstexte-generator' ); ?></h1>
					<p><?php esc_html_e( 'Pflegen Sie hier dieselben Angaben wie im Frontend-Wizard. Eine separate öffentliche Wizard-Seite ist dafür nicht erforderlich.', 'frontend-rechtstexte-generator' ); ?></p>
				</div>
				<a class="button" href="<?php echo esc_url( admin_url( 'options-general.php?page=frg-settings' ) ); ?>"><?php esc_html_e( 'Zu Einstellungen und Textbausteinen', 'frontend-rechtstexte-generator' ); ?></a>
			</div>
			<?php echo $this->wizard->render(); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- The escaped plugin template is rendered internally. ?>
		</div>
		<?php
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
		$feed_state    = FRG_Block_Feed::get_state();
		$feed_endpoint = FRG_Block_Feed::get_endpoint_url();

		include FRG_PLUGIN_DIR . 'templates/admin-settings.php';
	}

	private function handle_actions(): void {
		if ( isset( $_POST['frg_save_settings'], $_POST['frg_save_settings_nonce'] ) ) {
			check_admin_referer( 'frg_save_settings_action', 'frg_save_settings_nonce' );
			$current_settings = get_option( 'frg_settings', array() );
			$feed_mode = sanitize_key( wp_unslash( $_POST['block_feed_mode'] ?? 'off' ) );
			if ( ! in_array( $feed_mode, array( 'off', 'hub', 'client' ), true ) ) {
				$feed_mode = 'off';
			}
			$readability_mode = sanitize_key( wp_unslash( $_POST['privacy_readability_mode'] ?? 'detailed' ) );
			if ( ! in_array( $readability_mode, array( 'detailed', 'compact' ), true ) ) {
				$readability_mode = 'detailed';
			}
			$feed_key = sanitize_text_field( wp_unslash( $_POST['block_feed_key'] ?? '' ) );
			if ( 'hub' === $feed_mode && ( '' === $feed_key || ! empty( $_POST['block_feed_regenerate_key'] ) ) ) {
				$feed_key = wp_generate_password( 48, false, false );
			}

			$settings = array(
				'legal_notice'      => wp_kses_post( wp_unslash( $_POST['legal_notice'] ?? '' ) ),
				'show_generator_notice_impressum' => ! empty( $_POST['show_generator_notice_impressum'] ),
				'show_generator_notice_privacy'   => ! empty( $_POST['show_generator_notice_privacy'] ),
				'dynamic_page_content'             => ! empty( $_POST['dynamic_page_content'] ),
				'privacy_readability_mode'          => $readability_mode,
				'impressum_page'    => sanitize_text_field( wp_unslash( $_POST['impressum_page'] ?? '' ) ),
				'privacy_page'      => sanitize_text_field( wp_unslash( $_POST['privacy_page'] ?? '' ) ),
				'openai_api_key'    => sanitize_text_field( wp_unslash( $_POST['openai_api_key'] ?? '' ) ),
				'openai_model'      => sanitize_text_field( wp_unslash( $_POST['openai_model'] ?? 'gpt-5.6-terra' ) ),
				'block_feed_mode'   => $feed_mode,
				'block_feed_url'    => esc_url_raw( wp_unslash( $_POST['block_feed_url'] ?? '' ) ),
				'block_feed_key'    => $feed_key,
				'block_feed_auto_sync' => ! empty( $_POST['block_feed_auto_sync'] ),
				'impressum_page_id' => absint( $current_settings['impressum_page_id'] ?? 0 ),
				'privacy_page_id'   => absint( $current_settings['privacy_page_id'] ?? 0 ),
			);
			update_option( 'frg_settings', $settings );
			update_option( 'frg_block_registry_updated_at', current_time( 'mysql' ) );
			add_settings_error( 'frg_messages', 'settings_saved', __( 'Die Grundeinstellungen wurden gespeichert.', 'frontend-rechtstexte-generator' ), 'updated' );
			if ( ! empty( $_POST['frg_sync_after_save'] ) && 'client' === $feed_mode ) {
				$sync_result = $this->block_feed->sync_now();
				if ( is_wp_error( $sync_result ) ) {
					add_settings_error( 'frg_messages', 'feed_sync_failed', $sync_result->get_error_message(), 'error' );
				} else {
					add_settings_error( 'frg_messages', 'feed_synced', __( 'Die Textbausteine wurden erfolgreich von der Zentrale geladen.', 'frontend-rechtstexte-generator' ), 'updated' );
				}
			}
		}

		if ( isset( $_POST['frg_delete_profile'], $_POST['profile_id'] ) ) {
			check_admin_referer( 'frg_delete_profile_action', 'frg_delete_profile_nonce' );
			$this->storage->delete_profile( absint( $_POST['profile_id'] ) );
			add_settings_error( 'frg_messages', 'profile_deleted', __( 'Das Profil wurde gelöscht.', 'frontend-rechtstexte-generator' ), 'updated' );
		}

		if ( isset( $_POST['frg_save_block_registry'] ) ) {
			check_admin_referer( 'frg_save_block_registry_action', 'frg_save_block_registry_nonce' );
			$raw = isset( $_POST['blocks'] ) && is_array( $_POST['blocks'] ) ? wp_unslash( $_POST['blocks'] ) : array();
			$this->generator->save_block_registry( $raw );
			add_settings_error( 'frg_messages', 'blocks_saved', __( 'Änderungen an den Textbausteinen und Prüfdaten wurden gespeichert.', 'frontend-rechtstexte-generator' ), 'updated' );
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
						'reviewed_by'   => $block['reviewed_by'] ?? '',
						'review_source' => $block['review_source'] ?? '',
						'admin_notes'   => $block['admin_notes'],
						'draft_text'    => $block['draft_text'],
						'override_text' => $block['override_text'] ?? '',
						'legal_basis'   => $block['legal_basis'],
					);
				}
				$raw[ $block_key ]['override_text'] = $registry[ $block_key ]['draft_text'];
				$raw[ $block_key ]['status'] = 'editorial_approved';
				$this->generator->save_block_registry( $raw );
			}
		}

		if ( isset( $_POST['frg_import_block_registry'] ) ) {
			check_admin_referer( 'frg_import_block_registry_action', 'frg_import_block_registry_nonce' );
			$payload = json_decode( (string) wp_unslash( $_POST['block_registry_json'] ?? '' ), true );
			if ( is_array( $payload ) ) {
				$this->generator->save_block_registry( $payload );
				add_settings_error( 'frg_messages', 'registry_imported', __( 'Die Textbausteine wurden importiert.', 'frontend-rechtstexte-generator' ), 'updated' );
			} else {
				add_settings_error( 'frg_messages', 'registry_import_failed', __( 'Der Import konnte nicht gelesen werden. Bitte gültiges JSON verwenden.', 'frontend-rechtstexte-generator' ), 'error' );
			}
		}

		if ( isset( $_POST['frg_import_profile'] ) ) {
			check_admin_referer( 'frg_import_profile_action', 'frg_import_profile_nonce' );
			$payload = json_decode( (string) wp_unslash( $_POST['profile_json'] ?? '' ), true );
			if ( is_array( $payload ) && ! empty( $payload['data'] ) && is_array( $payload['data'] ) ) {
				$profile_name = ! empty( $payload['profile_name'] ) ? sanitize_text_field( $payload['profile_name'] ) : __( 'Importiertes Profil', 'frontend-rechtstexte-generator' );
				$this->storage->import_profile( get_current_user_id(), $profile_name, $payload['data'] );
				add_settings_error( 'frg_messages', 'profile_imported', __( 'Das Profil wurde importiert.', 'frontend-rechtstexte-generator' ), 'updated' );
			} else {
				add_settings_error( 'frg_messages', 'profile_import_failed', __( 'Das Profil konnte nicht gelesen werden. Bitte gültiges Export-JSON verwenden.', 'frontend-rechtstexte-generator' ), 'error' );
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
							'reviewed_by'   => $block['reviewed_by'] ?? '',
							'review_source' => $block['review_source'] ?? '',
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
			wp_send_json_error( array( 'message' => __( 'Kein Textbaustein übergeben.', 'frontend-rechtstexte-generator' ) ), 400 );
		}

		$registry = $this->generator->get_block_registry();
		if ( ! isset( $registry[ $block_key ] ) ) {
			wp_send_json_error( array( 'message' => __( 'Unbekannter Block.', 'frontend-rechtstexte-generator' ) ), 404 );
		}

		$block = $registry[ $block_key ];
		$block['admin_notes'] = isset( $_POST['change_request'] ) ? sanitize_textarea_field( wp_unslash( $_POST['change_request'] ) ) : ( $block['admin_notes'] ?? '' );
		if ( isset( $_POST['legal_basis'] ) ) {
			$legal_basis = preg_split( '/\R/', (string) wp_unslash( $_POST['legal_basis'] ) );
			$block['legal_basis'] = array_values( array_filter( array_map( 'sanitize_text_field', is_array( $legal_basis ) ? $legal_basis : array() ) ) );
		}

		$draft = $this->generate_ai_block_draft( $block_key, $block );
		if ( '' === $draft ) {
			wp_send_json_error( array( 'message' => __( 'Es konnte kein KI-Entwurf erzeugt werden. Bitte API-Key, Modell und Verbindung prüfen.', 'frontend-rechtstexte-generator' ) ), 500 );
		}

		$raw = $this->build_registry_payload( $registry );
		$raw[ $block_key ]['draft_text']  = $draft;
		$raw[ $block_key ]['status']      = 'draft';
		$raw[ $block_key ]['admin_notes'] = $block['admin_notes'];
		$raw[ $block_key ]['legal_basis'] = $block['legal_basis'];
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
			wp_send_json_error( array( 'message' => __( 'Kein Textbaustein übergeben.', 'frontend-rechtstexte-generator' ) ), 400 );
		}

		$registry = $this->generator->get_block_registry();
		$current_draft = isset( $_POST['draft_text'] ) ? wp_kses_post( wp_unslash( $_POST['draft_text'] ) ) : ( $registry[ $block_key ]['draft_text'] ?? '' );
		if ( '' === trim( wp_strip_all_tags( $current_draft ) ) ) {
			wp_send_json_error( array( 'message' => __( 'Für diesen Block liegt kein Entwurf vor.', 'frontend-rechtstexte-generator' ) ), 400 );
		}

		$raw = $this->build_registry_payload( $registry );
		$raw[ $block_key ]['draft_text']    = $current_draft;
		$raw[ $block_key ]['override_text'] = $current_draft;
		$raw[ $block_key ]['status'] = 'editorial_approved';
		$this->generator->save_block_registry( $raw );

		wp_send_json_success(
			array(
				'message'          => __( 'Der neue Text wurde veröffentlicht und wird ab sofort im Frontend verwendet.', 'frontend-rechtstexte-generator' ),
				'override_text'    => $current_draft,
				'draft_html'       => $this->format_admin_rich_text( $current_draft ),
				'override_html'    => $this->get_block_preview( $block_key ),
				'status'           => 'editorial_approved',
				'last_reviewed'    => $raw[ $block_key ]['last_reviewed'] ?? '',
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
			'liability_insurer'               => 'Muster Versicherung AG',
			'liability_scope'                 => 'Deutschland und Mitgliedstaaten der Europäischen Union',
			'liability_insurer_address'       => "Versicherungsstraße 10\n20095 Hamburg",
			'data_protection_officer_name'    => 'Erika Datenschutz',
			'data_protection_officer_email'   => 'datenschutz@example.com',
			'data_protection_officer_phone'   => '+49 30 7654321',
			'data_protection_officer_address' => "Datenschutzbüro\nMusterstraße 8\n10115 Berlin",
			'hosting_provider'                => 'Völkel EDV Systeme',
			'hosting_provider_address'        => "Völkel EDV Systeme\nInhaber: Eike Völkel\nGöttinger Str. 22\n31061 Alfeld (Leine)\nDeutschland",
			'server_location'                 => 'EU',
			'hosting_av_contract'             => 'Ja',
			'server_infrastructure_provider'  => 'netcup GmbH',
				'server_infrastructure_type'      => 'virtuelle Server (VServer)',
			'server_infrastructure_address'   => "netcup GmbH\nEmmy-Noether-Straße 10\n76131 Karlsruhe\nDeutschland",
			'privacy_processing_purposes'     => 'Bereitstellung der Website, Kommunikation, Vertragserfüllung und IT-Sicherheit.',
			'privacy_legal_basis'             => 'Art. 6 Abs. 1 lit. a, b, c und f DSGVO.',
			'privacy_recipient_categories'    => 'Hosting, IT-Dienstleister, Kommunikationsanbieter.',
			'privacy_storage_general'         => 'Speicherung nur solange erforderlich oder gesetzlich vorgeschrieben.',
			'privacy_third_country_transfer'  => 'Nur bei einzelnen Diensten und unter Beachtung der Art. 44 ff. DSGVO.',
			'has_third_country_transfer'      => true,
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
				'reviewed_by'   => $block['reviewed_by'] ?? '',
				'review_source' => $block['review_source'] ?? '',
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
		$model    = sanitize_text_field( $settings['openai_model'] ?? 'gpt-5.6-terra' );

		if ( '' === $api_key ) {
			return '';
		}

		$placeholder_details = $this->generator->get_block_placeholder_details( $block_key );
		$placeholder_instruction = '';
		$change_request = trim( (string) ( $block['admin_notes'] ?? '' ) );
		if ( ! empty( $placeholder_details ) ) {
			$lines = array();
			foreach ( $placeholder_details as $placeholder => $description ) {
				$lines[] = $placeholder . ' = ' . $description;
			}
			$placeholder_instruction = "Verwende die folgenden Platzhalter exakt so, wenn konkrete Angaben im Text auftauchen sollen:\n- " . implode( "\n- ", $lines ) . "\nLasse diese Platzhalter im Ergebnis stehen und ersetze sie nicht durch Beispielwerte.";
		}
		$change_instruction = '' !== $change_request
			? "\nKonkreter Änderungsauftrag des Redakteurs:\n" . $change_request . "\nSetze diesen Auftrag um, erfinde aber keine nicht belegten Rechtsänderungen oder Tatsachen."
			: "\nEs wurde kein konkreter Änderungsauftrag angegeben. Überarbeite den vorhandenen Themenblock nur anhand der hinterlegten Rechtsgrundlagen.";

		$prompt = sprintf(
			"Erstelle einen ausführlichen, professionell formulierten deutschen Textbaustein für eine %s. Blocktitel: %s.\nRechtsgrundlagen: %s.\nEinwilligung erforderlich: %s.\nDrittlandtransfer möglich: %s.\n%s%s\nWichtig: kein Rechtsberatungsversprechen, keine Beispielunternehmen, keine Fantasiedaten und keine Behauptung, dass der Text rechtlich aktuell oder geprüft sei. Wenn für diesen Block konkrete Angaben benötigt werden, nutze ausschließlich die vorgegebenen Platzhalter. HTML ist erlaubt, bevorzuge <h3>, <p> und bei Bedarf <ul><li>. Nenne einschlägige Rechtsgrundlagen dort, wo es textlich sinnvoll ist. Formuliere den Text so, wie man ihn typischerweise in einer ausführlichen Datenschutzerklärung oder in einem Impressum verwendet.",
			'privacy' === $block['area'] ? 'Datenschutzerklärung' : 'Impressum',
			$block['title'],
			implode( ', ', $block['legal_basis'] ?? array() ),
			! empty( $block['requires_consent'] ) ? 'ja' : 'nein',
			! empty( $block['third_country_possible'] ) ? 'ja' : 'nein',
			$placeholder_instruction,
			$change_instruction
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

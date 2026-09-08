<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class FRG_Text_Modules {
	private const REGISTRY_OPTION = 'frg_block_registry';
	private const REGISTRY_UPDATED_OPTION = 'frg_block_registry_updated_at';

	public function get_module_meta(): array {
		return array(
			'module_version' => '2026.09.08.12',
			'content_updated_at' => '2026-09-08',
			'last_reviewed_at' => '',
			'legal_basis' => array(
				'DDG § 5',
				'DSGVO Art. 13, 14',
				'TDDDG § 25',
			),
			'notice' => __( 'Die Textbausteine sind versioniert und sollten bei Rechtsänderungen inhaltlich neu geprüft werden. Eine anwaltliche Einzelfallprüfung wird nicht ersetzt.', 'frontend-rechtstexte-generator' ),
		);
	}

	public function get_block_registry(): array {
		$defaults = $this->get_default_block_registry();
		$stored   = get_option( self::REGISTRY_OPTION, array() );

		foreach ( $defaults as $key => $block ) {
			$defaults[ $key ]['key'] = $key;

			if ( empty( $stored[ $key ] ) || ! is_array( $stored[ $key ] ) ) {
				continue;
			}

			$defaults[ $key ]['status']         = $this->normalize_review_status( (string) ( $stored[ $key ]['status'] ?? $block['status'] ) );
			$defaults[ $key ]['last_reviewed']  = sanitize_text_field( $stored[ $key ]['last_reviewed'] ?? $block['last_reviewed'] );
			$defaults[ $key ]['review_due_at']  = sanitize_text_field( $stored[ $key ]['review_due_at'] ?? $block['review_due_at'] );
			$defaults[ $key ]['reviewed_by']    = sanitize_text_field( $stored[ $key ]['reviewed_by'] ?? '' );
			$defaults[ $key ]['review_source']  = sanitize_textarea_field( $stored[ $key ]['review_source'] ?? '' );
			$defaults[ $key ]['admin_notes']    = sanitize_textarea_field( $stored[ $key ]['admin_notes'] ?? '' );
			$defaults[ $key ]['draft_text']     = wp_kses_post( $stored[ $key ]['draft_text'] ?? '' );
			$defaults[ $key ]['override_text']  = wp_kses_post( $stored[ $key ]['override_text'] ?? '' );
			$defaults[ $key ]['compact_override_text'] = wp_kses_post( $stored[ $key ]['compact_override_text'] ?? '' );
			$defaults[ $key ]['legal_basis']    = $this->sanitize_legal_basis( $stored[ $key ]['legal_basis'] ?? $block['legal_basis'] );
		}

		return $defaults;
	}

	public function save_block_registry( array $raw ): void {
		$defaults = $this->get_default_block_registry();
		$current  = $this->get_block_registry();
		$payload  = array();

		foreach ( $defaults as $key => $block ) {
			$item = $raw[ $key ] ?? array();
			$status = $this->normalize_review_status( (string) ( $item['status'] ?? $block['status'] ) );
			$last_reviewed = sanitize_text_field( $item['last_reviewed'] ?? $block['last_reviewed'] );
			$reviewed_by = sanitize_text_field( $item['reviewed_by'] ?? '' );
			if ( 'legal_reviewed' === $status && ( '' === $last_reviewed || '' === $reviewed_by ) ) {
				$status = 'editorial_approved';
			}

			$payload[ $key ] = array(
				'status'        => $status,
				'last_reviewed' => $last_reviewed,
				'review_due_at' => sanitize_text_field( $item['review_due_at'] ?? $block['review_due_at'] ),
				'reviewed_by'   => $reviewed_by,
				'review_source' => sanitize_textarea_field( $item['review_source'] ?? '' ),
				'admin_notes'   => sanitize_textarea_field( $item['admin_notes'] ?? '' ),
				'draft_text'    => wp_kses_post( $item['draft_text'] ?? '' ),
				'override_text' => wp_kses_post( $item['override_text'] ?? '' ),
				'compact_override_text' => wp_kses_post( $item['compact_override_text'] ?? $current[ $key ]['compact_override_text'] ?? '' ),
				'legal_basis'   => $this->sanitize_legal_basis( $item['legal_basis'] ?? $block['legal_basis'] ),
			);
		}

		update_option( self::REGISTRY_OPTION, $payload );
		update_option( self::REGISTRY_UPDATED_OPTION, current_time( 'mysql' ) );
	}

	public function get_registry_updated_at(): string {
		$updated_at = get_option( self::REGISTRY_UPDATED_OPTION, '' );
		if ( is_string( $updated_at ) && '' !== $updated_at ) {
			return $updated_at;
		}

		$meta = $this->get_module_meta();
		return (string) ( $meta['content_updated_at'] ?? $meta['last_reviewed_at'] ?? '' );
	}

	public function render_block( string $key, array $data = array() ): string {
		$registry = $this->get_block_registry();
		if ( empty( $registry[ $key ]['callback'] ) ) {
			return '';
		}

		$callback = $registry[ $key ]['callback'];
		if ( ! method_exists( $this, $callback ) ) {
			return '';
		}
		$data = $this->inject_service_detail_data( $key, $data );
		$compact_callback = $callback . '_compact';

		if ( 'compact' === ( $data['readability_mode'] ?? 'detailed' ) ) {
			if ( ! empty( $registry[ $key ]['compact_override_text'] ) ) {
				$content = $this->format_rich_text( $this->replace( $this->normalize_german_ascii_terms( $registry[ $key ]['compact_override_text'] ), $this->normalize_template_data( $data ) ) );
				return $this->enforce_required_block_details( $key, $this->normalize_german_ascii_terms( $content ), $data );
			}

			if ( method_exists( $this, $compact_callback ) ) {
				$content = $this->normalize_german_ascii_terms( (string) $this->{$compact_callback}( $data ) );
				return $this->enforce_required_block_details( $key, $content, $data );
			}
		}

		if ( ! empty( $registry[ $key ]['override_text'] ) ) {
			$content = $this->format_rich_text( $this->replace( $this->normalize_german_ascii_terms( $registry[ $key ]['override_text'] ), $this->normalize_template_data( $data ) ) );
			$content = $this->normalize_german_ascii_terms( $content );
			return $this->enforce_required_block_details( $key, $content, $data );
		}

		$reflection = new ReflectionMethod( $this, $callback );
		if ( 0 === $reflection->getNumberOfParameters() ) {
			$content = $this->normalize_german_ascii_terms( (string) $this->{$callback}() );
			return $this->enforce_required_block_details( $key, $content, $data );
		}

		$content = $this->normalize_german_ascii_terms( (string) $this->{$callback}( $data ) );
		return $this->enforce_required_block_details( $key, $content, $data );
	}

	public function get_block_placeholders( string $key ): array {
		return array_keys( $this->get_block_placeholder_details( $key ) );
	}

	public function get_distributable_block_text( string $key ): string {
		$registry = $this->get_block_registry();
		if ( empty( $registry[ $key ] ) || ! is_array( $registry[ $key ] ) ) {
			return '';
		}

		if ( ! empty( $registry[ $key ]['override_text'] ) ) {
			return wp_kses_post( $registry[ $key ]['override_text'] );
		}

		$callback = (string) ( $registry[ $key ]['callback'] ?? '' );
		if ( '' === $callback || ! method_exists( $this, $callback ) ) {
			return '';
		}

		$placeholder_data = array();
		foreach ( $this->get_block_placeholders( $key ) as $placeholder ) {
			$name = trim( (string) $placeholder, '{}' );
			if ( '' !== $name ) {
				$placeholder_data[ $name ] = '{{' . $name . '}}';
			}
		}

		$reflection = new ReflectionMethod( $this, $callback );
		$text = 0 === $reflection->getNumberOfParameters()
			? (string) $this->{$callback}()
			: (string) $this->{$callback}( $placeholder_data );

		return wp_kses_post( $this->normalize_german_ascii_terms( $text ) );
	}

	public function get_distributable_compact_block_text( string $key ): string {
		$registry = $this->get_block_registry();
		if ( empty( $registry[ $key ] ) || ! is_array( $registry[ $key ] ) ) {
			return '';
		}

		if ( ! empty( $registry[ $key ]['compact_override_text'] ) ) {
			return wp_kses_post( $registry[ $key ]['compact_override_text'] );
		}

		$callback = (string) ( $registry[ $key ]['callback'] ?? '' ) . '_compact';
		if ( ! method_exists( $this, $callback ) ) {
			return '';
		}

		$placeholder_data = array( 'readability_mode' => 'compact' );
		foreach ( $this->get_block_placeholders( $key ) as $placeholder ) {
			$name = trim( (string) $placeholder, '{}' );
			if ( '' !== $name ) {
				$placeholder_data[ $name ] = '{{' . $name . '}}';
			}
		}

		return wp_kses_post( $this->normalize_german_ascii_terms( (string) $this->{$callback}( $placeholder_data ) ) );
	}

	public function get_block_placeholder_details( string $key ): array {
		$map = array(
			'privacy_intro' => array(
				'{{document_notice}}' => __( 'optionaler interner Generator-Hinweis', 'frontend-rechtstexte-generator' ),
				'{{company}}' => __( 'Name des Websitebetreibers', 'frontend-rechtstexte-generator' ),
				'{{website_url}}' => __( 'Website-URL des Angebots', 'frontend-rechtstexte-generator' ),
			),
			'impressum_base' => array(
				'{{document_notice}}' => __( 'optionaler interner Generator-Hinweis', 'frontend-rechtstexte-generator' ),
				'{{company}}' => __( 'Unternehmensname', 'frontend-rechtstexte-generator' ),
				'{{legal_form}}' => __( 'Rechtsform', 'frontend-rechtstexte-generator' ),
				'{{representative}}' => __( 'vertretungsberechtigte Person', 'frontend-rechtstexte-generator' ),
				'{{representative_label}}' => __( 'rechtsformspezifische Bezeichnung der Vertretung', 'frontend-rechtstexte-generator' ),
				'{{representative_line}}' => __( 'vorformatierte Vertretungszeile', 'frontend-rechtstexte-generator' ),
				'{{postal_address}}' => __( 'vorformatierte Anschrift des Websitebetreibers', 'frontend-rechtstexte-generator' ),
				'{{street}}' => __( 'Straße und Hausnummer', 'frontend-rechtstexte-generator' ),
				'{{zip}}' => __( 'PLZ', 'frontend-rechtstexte-generator' ),
				'{{city}}' => __( 'Ort', 'frontend-rechtstexte-generator' ),
				'{{country}}' => __( 'Land', 'frontend-rechtstexte-generator' ),
				'{{email}}' => __( 'Kontakt-E-Mail', 'frontend-rechtstexte-generator' ),
				'{{phone_line}}' => __( 'vorformatierte Telefonzeile', 'frontend-rechtstexte-generator' ),
				'{{website_line}}' => __( 'vorformatierte Website-Zeile', 'frontend-rechtstexte-generator' ),
			),
			'register' => array(
				'{{court}}' => __( 'Registergericht', 'frontend-rechtstexte-generator' ),
				'{{number}}' => __( 'Registernummer', 'frontend-rechtstexte-generator' ),
			),
			'vat' => array(
				'{{vat_id}}' => __( 'Umsatzsteuer-ID', 'frontend-rechtstexte-generator' ),
				'{{business_id_line}}' => __( 'vorformatierte Wirtschafts-ID-Zeile', 'frontend-rechtstexte-generator' ),
			),
			'responsible_content' => array(
				'{{name}}' => __( 'Name der verantwortlichen Person', 'frontend-rechtstexte-generator' ),
				'{{address}}' => __( 'Anschrift der verantwortlichen Person', 'frontend-rechtstexte-generator' ),
			),
			'professional_information' => array(
				'{{chamber}}' => __( 'zuständige Kammer', 'frontend-rechtstexte-generator' ),
				'{{title}}' => __( 'Berufsbezeichnung', 'frontend-rechtstexte-generator' ),
				'{{awarded_in}}' => __( 'Staat der Verleihung', 'frontend-rechtstexte-generator' ),
				'{{rules}}' => __( 'berufsrechtliche Regelungen', 'frontend-rechtstexte-generator' ),
				'{{authority}}' => __( 'Aufsichtsbehörde', 'frontend-rechtstexte-generator' ),
			),
			'liability_insurance' => array(
				'{{insurer}}' => __( 'Name des Versicherers', 'frontend-rechtstexte-generator' ),
				'{{insurer_address}}' => __( 'Anschrift des Versicherers', 'frontend-rechtstexte-generator' ),
				'{{scope}}' => __( 'räumlicher Geltungsbereich', 'frontend-rechtstexte-generator' ),
			),
			'controller' => array(
				'{{company}}' => __( 'Unternehmensname', 'frontend-rechtstexte-generator' ),
				'{{representative}}' => __( 'vertretungsberechtigte Person', 'frontend-rechtstexte-generator' ),
				'{{representative_label}}' => __( 'rechtsformspezifische Bezeichnung der Vertretung', 'frontend-rechtstexte-generator' ),
				'{{representative_line}}' => __( 'vorformatierte Vertretungszeile', 'frontend-rechtstexte-generator' ),
				'{{street}}' => __( 'Straße und Hausnummer', 'frontend-rechtstexte-generator' ),
				'{{zip}}' => __( 'PLZ', 'frontend-rechtstexte-generator' ),
				'{{city}}' => __( 'Ort', 'frontend-rechtstexte-generator' ),
				'{{country}}' => __( 'Land', 'frontend-rechtstexte-generator' ),
				'{{email}}' => __( 'Kontakt-E-Mail', 'frontend-rechtstexte-generator' ),
				'{{controller_phone_line}}' => __( 'optionale vorformatierte Telefonzeile des Verantwortlichen', 'frontend-rechtstexte-generator' ),
				'{{website_url}}' => __( 'Website-URL', 'frontend-rechtstexte-generator' ),
			),
			'data_protection_officer' => array(
				'{{name}}' => __( 'Name des Datenschutzbeauftragten', 'frontend-rechtstexte-generator' ),
				'{{email}}' => __( 'E-Mail des Datenschutzbeauftragten', 'frontend-rechtstexte-generator' ),
				'{{dpo_email}}' => __( 'E-Mail des Datenschutzbeauftragten', 'frontend-rechtstexte-generator' ),
				'{{dpo_phone}}' => __( 'Telefon des Datenschutzbeauftragten', 'frontend-rechtstexte-generator' ),
				'{{dpo_address}}' => __( 'Anschrift des Datenschutzbeauftragten', 'frontend-rechtstexte-generator' ),
			),
			'general_processing' => array(
				'{{purposes}}' => __( 'Zwecke der Verarbeitung', 'frontend-rechtstexte-generator' ),
				'{{legal_basis}}' => __( 'Rechtsgrundlagen der Verarbeitung', 'frontend-rechtstexte-generator' ),
				'{{recipients}}' => __( 'Kategorien von Empfängern', 'frontend-rechtstexte-generator' ),
				'{{storage}}' => __( 'allgemeine Speicherdauer', 'frontend-rechtstexte-generator' ),
				'{{third_country}}' => __( 'Hinweise zu Drittlandtransfers', 'frontend-rechtstexte-generator' ),
			),
			'hosting' => array(
				'{{host}}' => __( 'Hosting-Anbieter', 'frontend-rechtstexte-generator' ),
				'{{hosting_provider}}' => __( 'Hosting-Anbieter', 'frontend-rechtstexte-generator' ),
				'{{host_address}}' => __( 'Anschrift des Hosting-Anbieters', 'frontend-rechtstexte-generator' ),
				'{{location}}' => __( 'Serverstandort', 'frontend-rechtstexte-generator' ),
				'{{server_location}}' => __( 'Serverstandort', 'frontend-rechtstexte-generator' ),
				'{{av}}' => __( 'Angabe zum AV-Vertrag', 'frontend-rechtstexte-generator' ),
				'{{hosting_av_contract}}' => __( 'Angabe zum AV-Vertrag', 'frontend-rechtstexte-generator' ),
				'{{av_sentence}}' => __( 'ausformulierte Einordnung zum Auftragsverarbeitungsvertrag', 'frontend-rechtstexte-generator' ),
				'{{server_infrastructure_provider}}' => __( 'Anbieter der Server-Infrastruktur, z. B. netcup', 'frontend-rechtstexte-generator' ),
				'{{server_infrastructure_type}}' => __( 'Art der Server-Infrastruktur, z. B. virtueller Server (vServer)', 'frontend-rechtstexte-generator' ),
				'{{server_infrastructure_address}}' => __( 'Anschrift des Server-Infrastruktur-Anbieters', 'frontend-rechtstexte-generator' ),
			),
			'contact_form' => array(
				'{{company}}' => __( 'Unternehmensname', 'frontend-rechtstexte-generator' ),
				'{{email}}' => __( 'Kontakt-E-Mail', 'frontend-rechtstexte-generator' ),
				'{{form_tools}}' => __( 'eingesetztes Formularsystem', 'frontend-rechtstexte-generator' ),
			),
			'email_contact' => array(
				'{{company}}' => __( 'Unternehmensname', 'frontend-rechtstexte-generator' ),
				'{{email}}' => __( 'Kontakt-E-Mail', 'frontend-rechtstexte-generator' ),
			),
			'newsletter' => array(
				'{{company}}' => __( 'Unternehmensname', 'frontend-rechtstexte-generator' ),
				'{{newsletter_providers}}' => __( 'aktive Newsletter-Anbieter', 'frontend-rechtstexte-generator' ),
			),
			'newsletter_provider' => array(
				'{{providers}}' => __( 'aktive Newsletter-Anbieter', 'frontend-rechtstexte-generator' ),
				'{{newsletter_providers}}' => __( 'aktive Newsletter-Anbieter', 'frontend-rechtstexte-generator' ),
			),
			'application' => array(
				'{{company}}' => __( 'Unternehmensname', 'frontend-rechtstexte-generator' ),
				'{{email}}' => __( 'Kontakt-E-Mail', 'frontend-rechtstexte-generator' ),
			),
			'booking' => array(
				'{{company}}' => __( 'Unternehmensname', 'frontend-rechtstexte-generator' ),
				'{{active_services}}' => __( 'aktive externe Dienste', 'frontend-rechtstexte-generator' ),
			),
			'shop' => array(
				'{{company}}' => __( 'Unternehmensname', 'frontend-rechtstexte-generator' ),
				'{{active_features}}' => __( 'aktive Website-Funktionen', 'frontend-rechtstexte-generator' ),
			),
			'payment_provider' => array(
				'{{active_features}}' => __( 'aktive Website-Funktionen', 'frontend-rechtstexte-generator' ),
				'{{active_services}}' => __( 'aktive externe Dienste', 'frontend-rechtstexte-generator' ),
			),
			'shipping_provider' => array(
				'{{active_features}}' => __( 'aktive Website-Funktionen', 'frontend-rechtstexte-generator' ),
			),
			'download_area' => array(
				'{{active_features}}' => __( 'aktive Website-Funktionen', 'frontend-rechtstexte-generator' ),
			),
			'members_area' => array(
				'{{active_features}}' => __( 'aktive Website-Funktionen', 'frontend-rechtstexte-generator' ),
			),
			'training_portal' => array(
				'{{company}}' => __( 'Unternehmensname', 'frontend-rechtstexte-generator' ),
				'{{website_url}}' => __( 'Website-URL', 'frontend-rechtstexte-generator' ),
				'{{training_modules}}' => __( 'aktive Schulungsportal-Funktionen', 'frontend-rechtstexte-generator' ),
			),
			'ai_chatbot' => array(
				'{{company}}' => __( 'Unternehmensname', 'frontend-rechtstexte-generator' ),
				'{{website_url}}' => __( 'Website-URL', 'frontend-rechtstexte-generator' ),
				'{{ai_providers}}' => __( 'eingesetzte KI-Anbieter', 'frontend-rechtstexte-generator' ),
				'{{ai_chatbot_privacy_url}}' => __( 'URL zur Datenschutzseite des KI-Bot-Plugins', 'frontend-rechtstexte-generator' ),
				'{{ai_chatbot_privacy_link}}' => __( 'vorformatierter Link zur Datenschutzseite des KI-Bot-Plugins', 'frontend-rechtstexte-generator' ),
				'{{ai_transparency_sentence}}' => __( 'Hinweis auf die Kennzeichnung der KI-Interaktion', 'frontend-rechtstexte-generator' ),
			),
			'google_fonts_external' => array(
				'{{website_url}}' => __( 'Website-URL', 'frontend-rechtstexte-generator' ),
				'{{consent_tools}}' => __( 'aktive Consent-Tools', 'frontend-rechtstexte-generator' ),
			),
			'google_maps' => array(
				'{{website_url}}' => __( 'Website-URL', 'frontend-rechtstexte-generator' ),
				'{{consent_tools}}' => __( 'aktive Consent-Tools', 'frontend-rechtstexte-generator' ),
			),
			'youtube' => array(
				'{{website_url}}' => __( 'Website-URL', 'frontend-rechtstexte-generator' ),
				'{{consent_tools}}' => __( 'aktive Consent-Tools', 'frontend-rechtstexte-generator' ),
			),
			'vimeo' => array(
				'{{website_url}}' => __( 'Website-URL', 'frontend-rechtstexte-generator' ),
				'{{consent_tools}}' => __( 'aktive Consent-Tools', 'frontend-rechtstexte-generator' ),
			),
			'google_analytics' => array(
				'{{consent_tools}}' => __( 'aktive Consent-Tools', 'frontend-rechtstexte-generator' ),
				'{{website_url}}' => __( 'Website-URL', 'frontend-rechtstexte-generator' ),
			),
			'google_tag_manager' => array(
				'{{consent_tools}}' => __( 'aktive Consent-Tools', 'frontend-rechtstexte-generator' ),
				'{{active_services}}' => __( 'aktive externe Dienste', 'frontend-rechtstexte-generator' ),
			),
			'google_ads_conversion_tracking' => array(
				'{{consent_tools}}' => __( 'aktive Consent-Tools', 'frontend-rechtstexte-generator' ),
			),
			'meta_pixel' => array(
				'{{consent_tools}}' => __( 'aktive Consent-Tools', 'frontend-rechtstexte-generator' ),
			),
			'matomo' => array(
				'{{consent_tools}}' => __( 'aktive Consent-Tools', 'frontend-rechtstexte-generator' ),
			),
			'microsoft_clarity' => array(
				'{{consent_tools}}' => __( 'aktive Consent-Tools', 'frontend-rechtstexte-generator' ),
			),
			'cloudflare' => array(
				'{{website_url}}' => __( 'Website-URL', 'frontend-rechtstexte-generator' ),
				'{{company}}' => __( 'Unternehmensname', 'frontend-rechtstexte-generator' ),
			),
			'recaptcha' => array(
				'{{consent_tools}}' => __( 'aktive Consent-Tools', 'frontend-rechtstexte-generator' ),
				'{{website_url}}' => __( 'Website-URL', 'frontend-rechtstexte-generator' ),
			),
			'hcaptcha' => array(
				'{{consent_tools}}' => __( 'aktive Consent-Tools', 'frontend-rechtstexte-generator' ),
				'{{website_url}}' => __( 'Website-URL', 'frontend-rechtstexte-generator' ),
			),
			'cloudflare_turnstile' => array(
				'{{website_url}}' => __( 'Website-URL', 'frontend-rechtstexte-generator' ),
			),
			'cookie_consent' => array(
				'{{consent_tools}}' => __( 'aktive Consent-Tools', 'frontend-rechtstexte-generator' ),
			),
			'social_media_profiles' => array(
				'{{profiles}}' => __( 'aktive Social-Media-Profile', 'frontend-rechtstexte-generator' ),
				'{{social_profiles}}' => __( 'aktive Social-Media-Profile', 'frontend-rechtstexte-generator' ),
			),
			'embeds' => array(
				'{{active_services}}' => __( 'aktive externe Dienste', 'frontend-rechtstexte-generator' ),
				'{{website_url}}' => __( 'Website-URL', 'frontend-rechtstexte-generator' ),
			),
			'security_plugins' => array(
				'{{security_tools}}' => __( 'aktive Sicherheits-Tools', 'frontend-rechtstexte-generator' ),
			),
			'backup_plugins' => array(
				'{{backup_tools}}' => __( 'aktive Backup-Tools', 'frontend-rechtstexte-generator' ),
				'{{backup_destination}}' => __( 'Speicherort der Sicherungen', 'frontend-rechtstexte-generator' ),
				'{{backup_storage_provider}}' => __( 'Anbieter des Backup-Speichers', 'frontend-rechtstexte-generator' ),
				'{{backup_storage_address}}' => __( 'Anschrift des Backup-Anbieters', 'frontend-rechtstexte-generator' ),
				'{{backup_retention}}' => __( 'Aufbewahrungsdauer der Sicherungen', 'frontend-rechtstexte-generator' ),
			),
			'storage_duration' => array(
				'{{storage}}' => __( 'allgemeine Speicherdauer', 'frontend-rechtstexte-generator' ),
				'{{privacy_storage_general}}' => __( 'allgemeine Speicherdauer', 'frontend-rechtstexte-generator' ),
			),
			'third_country_transfer' => array(
				'{{third_country}}' => __( 'Hinweise zu Drittlandtransfers', 'frontend-rechtstexte-generator' ),
				'{{privacy_third_country_transfer}}' => __( 'Hinweise zu Drittlandtransfers', 'frontend-rechtstexte-generator' ),
			),
			'complaint_authority' => array(
				'{{privacy_supervisory_authority_name}}' => __( 'zuständige Datenschutzaufsichtsbehörde', 'frontend-rechtstexte-generator' ),
				'{{privacy_supervisory_authority_address}}' => __( 'Anschrift der Datenschutzaufsichtsbehörde', 'frontend-rechtstexte-generator' ),
				'{{privacy_supervisory_authority_url}}' => __( 'Website der Datenschutzaufsichtsbehörde', 'frontend-rechtstexte-generator' ),
			),
			'calendly' => array(
				'{{company}}' => __( 'Unternehmensname', 'frontend-rechtstexte-generator' ),
				'{{consent_tools}}' => __( 'aktive Consent-Tools', 'frontend-rechtstexte-generator' ),
			),
			'jotform' => array(
				'{{company}}' => __( 'Unternehmensname', 'frontend-rechtstexte-generator' ),
				'{{consent_tools}}' => __( 'aktive Consent-Tools', 'frontend-rechtstexte-generator' ),
			),
			'trustpilot' => array(
				'{{company}}' => __( 'Unternehmensname', 'frontend-rechtstexte-generator' ),
				'{{website_url}}' => __( 'Website-URL', 'frontend-rechtstexte-generator' ),
			),
			'smtp_service' => array(
				'{{email}}' => __( 'Kontakt-E-Mail', 'frontend-rechtstexte-generator' ),
				'{{company}}' => __( 'Unternehmensname', 'frontend-rechtstexte-generator' ),
			),
		);

		$placeholders = $map[ $key ] ?? array();
		$service_detail_keys = array(
			'google_fonts_external', 'google_maps', 'youtube', 'vimeo', 'google_analytics', 'google_tag_manager',
			'google_ads_conversion_tracking', 'meta_pixel', 'matomo', 'microsoft_clarity',
			'cloudflare', 'recaptcha', 'hcaptcha', 'cloudflare_turnstile', 'calendly', 'jotform', 'trustpilot',
			'smtp_service', 'ai_chatbot', 'newsletter_provider',
		);
		if ( in_array( $key, $service_detail_keys, true ) ) {
			$placeholders = array_merge(
				$placeholders,
				array(
					'{{service_provider}}'        => __( 'konkreter Anbieter oder Vertragspartner', 'frontend-rechtstexte-generator' ),
					'{{service_address}}'         => __( 'vorformatierte Anschrift des Anbieters', 'frontend-rechtstexte-generator' ),
					'{{service_privacy_link}}'    => __( 'Link zu den Datenschutzhinweisen des Anbieters', 'frontend-rechtstexte-generator' ),
					'{{service_purpose}}'         => __( 'konkreter Verarbeitungszweck', 'frontend-rechtstexte-generator' ),
					'{{service_data_categories}}' => __( 'konkrete Datenkategorien', 'frontend-rechtstexte-generator' ),
					'{{service_legal_basis}}'     => __( 'konkrete Rechtsgrundlage', 'frontend-rechtstexte-generator' ),
					'{{service_recipients}}'      => __( 'konkrete Empfänger', 'frontend-rechtstexte-generator' ),
					'{{service_retention}}'       => __( 'Speicherdauer oder Löschkriterien', 'frontend-rechtstexte-generator' ),
					'{{service_third_country}}'   => __( 'konkreter Drittlandbezug', 'frontend-rechtstexte-generator' ),
					'{{service_transfer_basis}}'  => __( 'Garantie für den Drittlandtransfer', 'frontend-rechtstexte-generator' ),
					'{{service_av_contract}}'     => __( 'Angabe zum AV-Vertrag', 'frontend-rechtstexte-generator' ),
					'{{service_consent}}'         => __( 'Angabe zur Einwilligungssteuerung', 'frontend-rechtstexte-generator' ),
				)
			);
		}

		return $placeholders;
	}

	private function sanitize_legal_basis( $basis ): array {
		if ( is_string( $basis ) ) {
			$basis = preg_split( '/\r\n|\r|\n/', $basis );
		}

		if ( ! is_array( $basis ) ) {
			return array();
		}

		$out = array();
		foreach ( $basis as $line ) {
			$line = sanitize_text_field( (string) $line );
			if ( '' !== $line ) {
				$out[] = $line;
			}
		}

		return $out;
	}

	private function get_default_block_registry(): array {
		$reviewed_at = '';
		$due_at      = '';

		return array(
			'impressum_base' => $this->build_block_definition( __( 'Impressum Basis', 'frontend-rechtstexte-generator' ), 'impressum', 'get_impressum_base_module', array( 'DDG § 5' ), false, false, $reviewed_at, $due_at ),
			'register' => $this->build_block_definition( __( 'Registerangaben', 'frontend-rechtstexte-generator' ), 'impressum', 'get_register_module', array( 'DDG § 5 Abs. 1', 'Handelsregisterrecht' ), false, false, $reviewed_at, $due_at ),
			'vat' => $this->build_block_definition( __( 'USt-ID / Wirtschafts-ID', 'frontend-rechtstexte-generator' ), 'impressum', 'get_vat_module', array( 'DDG § 5 Abs. 1', 'UStG § 27a' ), false, false, $reviewed_at, $due_at ),
			'responsible_content' => $this->build_block_definition( __( 'Inhaltlich Verantwortlicher', 'frontend-rechtstexte-generator' ), 'impressum', 'get_responsible_content_module', array( 'MStV § 18 Abs. 2' ), false, false, $reviewed_at, $due_at ),
			'professional_information' => $this->build_block_definition( __( 'Berufsspezifische Angaben', 'frontend-rechtstexte-generator' ), 'impressum', 'get_professional_information_module', array( 'DDG § 5', 'berufsrechtliche Spezialnormen' ), false, false, $reviewed_at, $due_at ),
			'liability_insurance' => $this->build_block_definition( __( 'Berufshaftpflichtversicherung', 'frontend-rechtstexte-generator' ), 'impressum', 'get_liability_insurance_module', array( 'berufsrechtliche Sonderpflichten je nach Branche' ), false, false, $reviewed_at, $due_at ),
			'privacy_intro' => $this->build_block_definition( __( 'Datenschutz Einleitung', 'frontend-rechtstexte-generator' ), 'privacy', 'get_privacy_intro_module', array( 'DSGVO Art. 12, 13, 14' ), false, true, $reviewed_at, $due_at ),
			'controller' => $this->build_block_definition( __( 'Verantwortlicher', 'frontend-rechtstexte-generator' ), 'privacy', 'get_controller_module', array( 'DSGVO Art. 13 Abs. 1 lit. a' ), false, false, $reviewed_at, $due_at ),
			'data_protection_officer' => $this->build_block_definition( __( 'Datenschutzbeauftragter', 'frontend-rechtstexte-generator' ), 'privacy', 'get_data_protection_officer_module', array( 'DSGVO Art. 13 Abs. 1 lit. b' ), false, false, $reviewed_at, $due_at ),
			'general_processing' => $this->build_block_definition( __( 'Allgemeine Verarbeitungshinweise', 'frontend-rechtstexte-generator' ), 'privacy', 'get_general_processing_module', array( 'DSGVO Art. 13, 14' ), false, true, $reviewed_at, $due_at ),
			'hosting' => $this->build_block_definition( __( 'Hosting', 'frontend-rechtstexte-generator' ), 'privacy', 'get_hosting_module', array( 'DSGVO Art. 6 Abs. 1', 'DSGVO Art. 28' ), false, true, $reviewed_at, $due_at ),
			'server_logs' => $this->build_block_definition( __( 'Server-Logfiles', 'frontend-rechtstexte-generator' ), 'privacy', 'get_server_logs_module', array( 'DSGVO Art. 6 Abs. 1 lit. f' ), false, false, $reviewed_at, $due_at ),
			'contact_form' => $this->build_block_definition( __( 'Kontaktformular', 'frontend-rechtstexte-generator' ), 'privacy', 'get_contact_form_module', array( 'DSGVO Art. 6 Abs. 1 lit. b, f' ), false, false, $reviewed_at, $due_at ),
			'email_contact' => $this->build_block_definition( __( 'Kontakt per E-Mail / Telefon', 'frontend-rechtstexte-generator' ), 'privacy', 'get_email_contact_module', array( 'DSGVO Art. 6 Abs. 1 lit. b, f' ), false, false, $reviewed_at, $due_at ),
			'comments' => $this->build_block_definition( __( 'Kommentare', 'frontend-rechtstexte-generator' ), 'privacy', 'get_comments_module', array( 'DSGVO Art. 6 Abs. 1 lit. f' ), false, false, $reviewed_at, $due_at ),
			'registration' => $this->build_block_definition( __( 'Registrierung / Login', 'frontend-rechtstexte-generator' ), 'privacy', 'get_registration_module', array( 'DSGVO Art. 6 Abs. 1 lit. b, f' ), false, false, $reviewed_at, $due_at ),
			'newsletter' => $this->build_block_definition( __( 'Newsletter', 'frontend-rechtstexte-generator' ), 'privacy', 'get_newsletter_module', array( 'DSGVO Art. 6 Abs. 1 lit. a' ), true, true, $reviewed_at, $due_at ),
			'newsletter_provider' => $this->build_block_definition( __( 'Newsletter-Anbieter', 'frontend-rechtstexte-generator' ), 'privacy', 'get_newsletter_provider_module', array( 'DSGVO Art. 28', 'DSGVO Art. 44 ff.' ), true, true, $reviewed_at, $due_at ),
			'application' => $this->build_block_definition( __( 'Bewerbungen', 'frontend-rechtstexte-generator' ), 'privacy', 'get_application_module', array( 'DSGVO Art. 6 Abs. 1 lit. b', 'BDSG § 26' ), false, false, $reviewed_at, $due_at ),
			'booking' => $this->build_block_definition( __( 'Terminbuchung', 'frontend-rechtstexte-generator' ), 'privacy', 'get_booking_module', array( 'DSGVO Art. 6 Abs. 1 lit. b, f' ), false, true, $reviewed_at, $due_at ),
			'shop' => $this->build_block_definition( __( 'Shop / Vertragsabwicklung', 'frontend-rechtstexte-generator' ), 'privacy', 'get_shop_module', array( 'DSGVO Art. 6 Abs. 1 lit. b, c' ), false, false, $reviewed_at, $due_at ),
			'payment_provider' => $this->build_block_definition( __( 'Zahlungsdienstleister', 'frontend-rechtstexte-generator' ), 'privacy', 'get_payment_provider_module', array( 'DSGVO Art. 6 Abs. 1 lit. b, c' ), false, true, $reviewed_at, $due_at ),
			'shipping_provider' => $this->build_block_definition( __( 'Versanddienstleister', 'frontend-rechtstexte-generator' ), 'privacy', 'get_shipping_provider_module', array( 'DSGVO Art. 6 Abs. 1 lit. b' ), false, false, $reviewed_at, $due_at ),
			'download_area' => $this->build_block_definition( __( 'Downloads', 'frontend-rechtstexte-generator' ), 'privacy', 'get_download_area_module', array( 'DSGVO Art. 6 Abs. 1 lit. b, f' ), false, false, $reviewed_at, $due_at ),
			'members_area' => $this->build_block_definition( __( 'Mitgliederbereich', 'frontend-rechtstexte-generator' ), 'privacy', 'get_members_area_module', array( 'DSGVO Art. 6 Abs. 1 lit. b, f' ), false, false, $reviewed_at, $due_at ),
			'training_portal' => $this->build_block_definition( __( 'Schulungsportal / Lernplattform', 'frontend-rechtstexte-generator' ), 'privacy', 'get_training_portal_module', array( 'DSGVO Art. 6 Abs. 1 lit. b, f', 'DSGVO Art. 13', 'BDSG § 26 je nach Nutzungskontext' ), false, false, $reviewed_at, $due_at ),
			'ai_chatbot' => $this->build_block_definition( __( 'Website-KI-Bot / KI-Assistent', 'frontend-rechtstexte-generator' ), 'privacy', 'get_ai_chatbot_module', array( 'DSGVO Art. 6 Abs. 1', 'DSGVO Art. 13', 'DSGVO Art. 28', 'DSGVO Art. 44 ff.' ), true, true, $reviewed_at, $due_at ),
			'google_fonts_external' => $this->build_block_definition( __( 'Google Fonts extern', 'frontend-rechtstexte-generator' ), 'privacy', 'get_google_fonts_external_module', array( 'DSGVO Art. 6 Abs. 1', 'DSGVO Art. 44 ff.' ), true, true, $reviewed_at, $due_at ),
			'google_fonts_local' => $this->build_block_definition( __( 'Google Fonts lokal', 'frontend-rechtstexte-generator' ), 'privacy', 'get_google_fonts_local_module', array( 'DSGVO Art. 6 Abs. 1 lit. f' ), false, false, $reviewed_at, $due_at ),
			'google_maps' => $this->build_block_definition( __( 'Google Maps', 'frontend-rechtstexte-generator' ), 'privacy', 'get_google_maps_module', array( 'DSGVO Art. 6 Abs. 1', 'DSGVO Art. 44 ff.' ), true, true, $reviewed_at, $due_at ),
			'youtube' => $this->build_block_definition( __( 'YouTube', 'frontend-rechtstexte-generator' ), 'privacy', 'get_youtube_module', array( 'DSGVO Art. 6 Abs. 1', 'DSGVO Art. 44 ff.' ), true, true, $reviewed_at, $due_at ),
			'vimeo' => $this->build_block_definition( __( 'Vimeo', 'frontend-rechtstexte-generator' ), 'privacy', 'get_vimeo_module', array( 'DSGVO Art. 6 Abs. 1', 'DSGVO Art. 44 ff.' ), true, true, $reviewed_at, $due_at ),
			'google_analytics' => $this->build_block_definition( __( 'Google Analytics', 'frontend-rechtstexte-generator' ), 'privacy', 'get_google_analytics_module', array( 'DSGVO Art. 6 Abs. 1 lit. a', 'TDDDG § 25' ), true, true, $reviewed_at, $due_at ),
			'google_tag_manager' => $this->build_block_definition( __( 'Google Tag Manager', 'frontend-rechtstexte-generator' ), 'privacy', 'get_google_tag_manager_module', array( 'DSGVO Art. 6 Abs. 1', 'TDDDG § 25' ), true, true, $reviewed_at, $due_at ),
			'google_ads_conversion_tracking' => $this->build_block_definition( __( 'Google Ads Conversion Tracking', 'frontend-rechtstexte-generator' ), 'privacy', 'get_google_ads_conversion_tracking_module', array( 'DSGVO Art. 6 Abs. 1 lit. a', 'TDDDG § 25' ), true, true, $reviewed_at, $due_at ),
			'meta_pixel' => $this->build_block_definition( __( 'Meta Pixel', 'frontend-rechtstexte-generator' ), 'privacy', 'get_meta_pixel_module', array( 'DSGVO Art. 6 Abs. 1 lit. a', 'TDDDG § 25' ), true, true, $reviewed_at, $due_at ),
			'matomo' => $this->build_block_definition( __( 'Matomo', 'frontend-rechtstexte-generator' ), 'privacy', 'get_matomo_module', array( 'DSGVO Art. 6 Abs. 1', 'TDDDG § 25' ), true, false, $reviewed_at, $due_at ),
			'microsoft_clarity' => $this->build_block_definition( __( 'Microsoft Clarity', 'frontend-rechtstexte-generator' ), 'privacy', 'get_microsoft_clarity_module', array( 'DSGVO Art. 6 Abs. 1 lit. a', 'TDDDG § 25' ), true, true, $reviewed_at, $due_at ),
			'cloudflare' => $this->build_block_definition( __( 'Cloudflare', 'frontend-rechtstexte-generator' ), 'privacy', 'get_cloudflare_module', array( 'DSGVO Art. 6 Abs. 1 lit. f', 'DSGVO Art. 28, 44 ff.' ), false, true, $reviewed_at, $due_at ),
			'recaptcha' => $this->build_block_definition( __( 'reCAPTCHA', 'frontend-rechtstexte-generator' ), 'privacy', 'get_recaptcha_module', array( 'DSGVO Art. 6 Abs. 1 lit. f oder a', 'TDDDG § 25' ), true, true, $reviewed_at, $due_at ),
			'hcaptcha' => $this->build_block_definition( __( 'hCaptcha', 'frontend-rechtstexte-generator' ), 'privacy', 'get_hcaptcha_module', array( 'DSGVO Art. 6 Abs. 1 lit. f oder a', 'TDDDG § 25' ), true, true, $reviewed_at, $due_at ),
			'cloudflare_turnstile' => $this->build_block_definition( __( 'Cloudflare Turnstile', 'frontend-rechtstexte-generator' ), 'privacy', 'get_cloudflare_turnstile_module', array( 'DSGVO Art. 6 Abs. 1 lit. f oder a', 'TDDDG § 25' ), true, true, $reviewed_at, $due_at ),
			'cookie_consent' => $this->build_block_definition( __( 'Cookie-Consent', 'frontend-rechtstexte-generator' ), 'privacy', 'get_cookie_consent_module', array( 'DSGVO Art. 6 Abs. 1 lit. c, f', 'TDDDG § 25' ), false, false, $reviewed_at, $due_at ),
			'social_media_profiles' => $this->build_block_definition( __( 'Social Media Profile', 'frontend-rechtstexte-generator' ), 'privacy', 'get_social_media_profiles_module', array( 'DSGVO Art. 13, 26, 44 ff.' ), false, true, $reviewed_at, $due_at ),
			'embeds' => $this->build_block_definition( __( 'Embeds / externe Ressourcen', 'frontend-rechtstexte-generator' ), 'privacy', 'get_embeds_module', array( 'DSGVO Art. 6 Abs. 1', 'DSGVO Art. 44 ff.' ), true, true, $reviewed_at, $due_at ),
			'security_plugins' => $this->build_block_definition( __( 'Sicherheits-Plugins', 'frontend-rechtstexte-generator' ), 'privacy', 'get_security_plugins_module', array( 'DSGVO Art. 6 Abs. 1 lit. f' ), false, false, $reviewed_at, $due_at ),
			'backup_plugins' => $this->build_block_definition( __( 'Backup-Systeme', 'frontend-rechtstexte-generator' ), 'privacy', 'get_backup_plugins_module', array( 'DSGVO Art. 6 Abs. 1 lit. f, c' ), false, false, $reviewed_at, $due_at ),
			'data_subject_rights' => $this->build_block_definition( __( 'Betroffenenrechte', 'frontend-rechtstexte-generator' ), 'privacy', 'get_data_subject_rights_module', array( 'DSGVO Art. 15-21' ), false, false, $reviewed_at, $due_at ),
			'storage_duration' => $this->build_block_definition( __( 'Speicherdauer', 'frontend-rechtstexte-generator' ), 'privacy', 'get_storage_duration_module', array( 'DSGVO Art. 13 Abs. 2 lit. a' ), false, false, $reviewed_at, $due_at ),
			'third_country_transfer' => $this->build_block_definition( __( 'Drittlandtransfer', 'frontend-rechtstexte-generator' ), 'privacy', 'get_third_country_transfer_module', array( 'DSGVO Art. 13 Abs. 1 lit. f', 'DSGVO Art. 44 ff.' ), false, true, $reviewed_at, $due_at ),
			'complaint_authority' => $this->build_block_definition( __( 'Beschwerderecht', 'frontend-rechtstexte-generator' ), 'privacy', 'get_complaint_authority_module', array( 'DSGVO Art. 77' ), false, false, $reviewed_at, $due_at ),
			'ssl_tls' => $this->build_block_definition( __( 'SSL/TLS', 'frontend-rechtstexte-generator' ), 'privacy', 'get_ssl_tls_module', array( 'DSGVO Art. 32' ), false, false, $reviewed_at, $due_at ),
			'calendly' => $this->build_block_definition( __( 'Calendly / Terminbuchung', 'frontend-rechtstexte-generator' ), 'privacy', 'get_calendly_module', array( 'DSGVO Art. 6 Abs. 1', 'DSGVO Art. 44 ff.' ), true, true, $reviewed_at, $due_at ),
			'jotform' => $this->build_block_definition( __( 'Jotform / Formulare', 'frontend-rechtstexte-generator' ), 'privacy', 'get_jotform_module', array( 'DSGVO Art. 28', 'DSGVO Art. 44 ff.' ), true, true, $reviewed_at, $due_at ),
			'trustpilot' => $this->build_block_definition( __( 'Trustpilot / Bewertungen', 'frontend-rechtstexte-generator' ), 'privacy', 'get_trustpilot_module', array( 'DSGVO Art. 6 Abs. 1', 'DSGVO Art. 44 ff.' ), true, true, $reviewed_at, $due_at ),
			'smtp_service' => $this->build_block_definition( __( 'SMTP / E-Mail-Versand', 'frontend-rechtstexte-generator' ), 'privacy', 'get_smtp_service_module', array( 'DSGVO Art. 6 Abs. 1 lit. b, f', 'DSGVO Art. 28' ), false, true, $reviewed_at, $due_at ),
		);
	}

	private function build_block_definition( string $title, string $area, string $callback, array $legal_basis, bool $requires_consent, bool $third_country_possible, string $reviewed_at, string $due_at ): array {
		return array(
			'title'                  => $title,
			'area'                   => $area,
			'callback'               => $callback,
			'legal_basis'            => $legal_basis,
			'requires_consent'       => $requires_consent,
			'third_country_possible' => $third_country_possible,
			'status'                 => 'review_needed',
			'last_reviewed'          => $reviewed_at,
			'review_due_at'          => $due_at,
			'reviewed_by'            => '',
			'review_source'          => '',
			'admin_notes'            => '',
			'draft_text'             => '',
			'override_text'          => '',
			'compact_override_text'  => '',
		);
	}

	private function normalize_review_status( string $status ): string {
		if ( 'approved' === $status ) {
			return 'editorial_approved';
		}

		$allowed = array( 'review_needed', 'draft', 'editorial_approved', 'legal_reviewed' );
		return in_array( $status, $allowed, true ) ? $status : 'review_needed';
	}

	private function replace(string $template, array $replacements): string {
		foreach ( $replacements as $key => $value ) {
			if ( ! is_scalar( $value ) && null !== $value ) {
				continue;
			}
			$template = str_replace( '{{' . $key . '}}', (string) $value, $template );
		}

		return $template;
	}

	private function inject_service_detail_data( string $key, array $data ): array {
		if ( empty( $data['service_details'][ $key ] ) || ! is_array( $data['service_details'][ $key ] ) ) {
			return $data;
		}

		foreach ( $data['service_details'][ $key ] as $field => $value ) {
			$data[ 'service_' . sanitize_key( (string) $field ) ] = $value;
		}

		return $data;
	}

	private function normalize_german_ascii_terms( string $content ): string {
		return strtr(
			$content,
			array(
					'Datenschutzerklaerung' => 'Datenschutzerklärung',
					'Vertragsdurchfuehrung' => 'Vertragsdurchführung',
					'Durchfuehrung' => 'Durchführung',
					'Vertragserfuellung' => 'Vertragserfüllung',
					'Erfuellung' => 'Erfüllung',
					'Fehlerpraevention' => 'Fehlerprävention',
					'Massnahmen' => 'Maßnahmen',
					'massnahmen' => 'maßnahmen',
					'Anschliessend' => 'Anschließend',
					'eingeschraenkt' => 'eingeschränkt',
					'Gruende' => 'Gründe',
					'Resource' => 'Ressource',
					'Pflicht zur Bereitstellung / Consent' => 'Erforderlichkeit der Verarbeitung',
				'Naechste Pruefung' => 'Nächste Prüfung',
				'Pruefung' => 'Prüfung',
				'pruefen' => 'prüfen',
				'geprueft' => 'geprüft',
				'gemaess' => 'gemäß',
				'ausschliesslich' => 'ausschließlich',
				'anschliessend' => 'anschließend',
				'regelmaessig' => 'regelmäßig',
				'ordnungsgemaess' => 'ordnungsgemäß',
				'fuer' => 'für',
				'Fuer' => 'Für',
				'ueber' => 'über',
				'Ueber' => 'Über',
				'moeglich' => 'möglich',
				'Moeglich' => 'Möglich',
				'koennen' => 'können',
				'Koennen' => 'Können',
				'muessten' => 'müssten',
				'muessen' => 'müssen',
				'Muessen' => 'Müssen',
				'duerfen' => 'dürfen',
				'Duerfen' => 'Dürfen',
				'waehrend' => 'während',
				'gewaehrleisten' => 'gewährleisten',
				'Verschluesselung' => 'Verschlüsselung',
				'verschluesselt' => 'verschlüsselt',
				'Schluessel' => 'Schlüssel',
				'Uebermittlung' => 'Übermittlung',
				'uebermittelt' => 'übermittelt',
				'uebertragen' => 'übertragen',
				'loeschen' => 'löschen',
				'geloescht' => 'gelöscht',
			)
		);
	}

	private function normalize_template_data( array $data ): array {
		$aliases = array(
			'hosting_provider' => $data['host'] ?? $data['hosting_provider'] ?? '',
			'server_location' => $data['location'] ?? $data['server_location'] ?? '',
			'hosting_av_contract' => $data['av'] ?? $data['hosting_av_contract'] ?? '',
			'server_infrastructure_provider' => $data['server_infrastructure_provider'] ?? '',
			'server_infrastructure_type' => $data['server_infrastructure_type'] ?? '',
			'privacy_storage_general' => $data['storage'] ?? $data['privacy_storage_general'] ?? '',
			'privacy_third_country_transfer' => $data['third_country'] ?? $data['privacy_third_country_transfer'] ?? '',
		);

		return array_merge( $aliases, $data );
	}

	private function format_rich_text( string $content ): string {
		$content = trim( $content );
		if ( '' === $content ) {
			return '';
		}

		if ( preg_match( '/<(p|h2|h3|h4|ul|ol|li|div|section|table|blockquote|br)\b/i', $content ) ) {
			$content = wp_kses_post( $content );

			if ( ! preg_match( '/<h[2-4]\b/i', $content ) ) {
				$content = preg_replace_callback(
					'/^\s*<p>(.*?)<\/p>/is',
					function ( array $matches ): string {
						$text = trim( wp_strip_all_tags( $matches[1] ) );
						if (
							'' !== $text &&
							mb_strlen( $text ) <= 90 &&
							! preg_match( '/[.!?:;]$/u', $text )
						) {
							return '<h3>' . esc_html( $text ) . '</h3>';
						}

						return $matches[0];
					},
					$content,
					1
				);
			}

			return $content;
		}

		$blocks = preg_split( '/\R{2,}/', $content );
		if ( ! empty( $blocks ) ) {
			$first_block = trim( (string) $blocks[0] );
			$remaining   = array_slice( $blocks, 1 );

			// Treat a short, punctuation-light first line as a section heading for plain-text overrides.
			if (
				'' !== $first_block &&
				mb_strlen( $first_block ) <= 90 &&
				1 === preg_match_all( '/\R/', $first_block ) + 1 &&
				! preg_match( '/[.!?:;]$/u', $first_block )
			) {
				$body = trim( implode( "\n\n", $remaining ) );
				$body = '' !== $body ? wpautop( wp_kses_post( $body ) ) : '';

				return '<h3>' . esc_html( $first_block ) . '</h3>' . $body;
			}
		}

		return wpautop( wp_kses_post( $content ) );
	}

	private function ensure_hosting_av_notice( string $content, array $data ): string {
		$normalized = wp_strip_all_tags( $content );
		if ( '' === trim( $normalized ) ) {
			return $content;
		}

		if ( false !== stripos( $normalized, 'Auftragsverarbeitung' ) || false !== stripos( $normalized, 'Art. 28' ) ) {
			return $content;
		}

		$normalized_data = $this->normalize_template_data( $data );
		$av_sentence     = trim( (string) ( $normalized_data['av_sentence'] ?? '' ) );

		if ( '' === $av_sentence ) {
			$contract_value = trim( (string) ( $normalized_data['hosting_av_contract'] ?? '' ) );
			if ( 'Ja' === $contract_value ) {
				$av_sentence = __( 'Mit dem Hosting-Anbieter besteht ein Vertrag zur Auftragsverarbeitung gemäß Art. 28 DSGVO.', 'frontend-rechtstexte-generator' );
			} elseif ( 'Nein' === $contract_value ) {
				$av_sentence = __( 'Mit dem Hosting-Anbieter besteht derzeit kein Vertrag zur Auftragsverarbeitung. Dieser Punkt sollte datenschutzrechtlich besonders geprüft werden.', 'frontend-rechtstexte-generator' );
			} elseif ( 'Unbekannt' === $contract_value ) {
				$av_sentence = __( 'Ob mit dem Hosting-Anbieter ein Vertrag zur Auftragsverarbeitung gemäß Art. 28 DSGVO besteht, sollte geprüft werden.', 'frontend-rechtstexte-generator' );
			}
		}

		if ( '' === $av_sentence ) {
			return $content;
		}

		return $content . '<p><strong>' . esc_html__( 'Auftragsverarbeitung', 'frontend-rechtstexte-generator' ) . ':</strong> ' . esc_html( $av_sentence ) . '</p>';
	}

	private function enforce_required_block_details( string $key, string $content, array $data ): string {
		switch ( $key ) {
			case 'impressum_base':
				return $this->ensure_impressum_base_required_details( $content, $data );
			case 'hosting':
				return $this->ensure_hosting_required_details( $content, $data );
			case 'controller':
				return $this->ensure_controller_required_details( $content, $data );
			case 'data_protection_officer':
				return $this->ensure_dpo_required_details( $content, $data );
			case 'responsible_content':
				return $this->ensure_responsible_content_required_details( $content, $data );
			case 'liability_insurance':
				return $this->ensure_liability_required_details( $content, $data );
			default:
				return $this->ensure_service_required_details( $key, $content, $data );
		}
	}

	private function ensure_service_required_details( string $key, string $content, array $data ): string {
		if ( empty( $data['service_details'][ $key ] ) || ! is_array( $data['service_details'][ $key ] ) ) {
			return $content;
		}

		$details = $data['service_details'][ $key ];
		$facts = array();
		$labels = array(
			'provider'        => __( 'Anbieter', 'frontend-rechtstexte-generator' ),
			'purpose'         => __( 'Zweck der Verarbeitung', 'frontend-rechtstexte-generator' ),
			'data_categories' => __( 'Verarbeitete Daten', 'frontend-rechtstexte-generator' ),
			'legal_basis'     => __( 'Rechtsgrundlage', 'frontend-rechtstexte-generator' ),
			'recipients'      => __( 'Empfänger', 'frontend-rechtstexte-generator' ),
			'retention'       => __( 'Speicherdauer', 'frontend-rechtstexte-generator' ),
			'third_country'   => __( 'Drittlandbezug', 'frontend-rechtstexte-generator' ),
			'transfer_basis'  => __( 'Garantie für Drittlandtransfer', 'frontend-rechtstexte-generator' ),
			'av_contract'     => __( 'Auftragsverarbeitung', 'frontend-rechtstexte-generator' ),
			'consent'         => __( 'Einwilligungssteuerung', 'frontend-rechtstexte-generator' ),
		);

		foreach ( $labels as $field => $label ) {
			$value = trim( (string) ( $details[ $field ] ?? '' ) );
			if ( '' !== $value && ! $this->contains_required_value( $content, $value ) ) {
				$facts[] = '<p><strong>' . esc_html( $label ) . ':</strong> ' . esc_html( $value ) . '</p>';
			}
		}

		if ( ! empty( $details['address'] ) && ! $this->contains_required_value( $content, $this->normalize_visible_text( (string) $details['address'] ) ) ) {
			$facts[] = '<div class="frg-address-block"><strong>' . esc_html__( 'Anschrift des Anbieters', 'frontend-rechtstexte-generator' ) . ':</strong><br>' . $details['address'] . '</div>';
		}
		if ( ! empty( $details['privacy_link'] ) && empty( $details['privacy_url'] ) === false && ! $this->contains_required_value( $content, (string) $details['privacy_url'] ) ) {
			$facts[] = '<p>' . $details['privacy_link'] . '</p>';
		}

		return $this->append_required_facts( $content, $facts, __( 'Konkrete Angaben zu diesem Dienst', 'frontend-rechtstexte-generator' ) );
	}

	private function ensure_impressum_base_required_details( string $content, array $data ): string {
		$facts = array();

		if ( ! $this->contains_required_value( $content, (string) ( $data['company'] ?? '' ) ) && ! empty( $data['company'] ) ) {
			$facts[] = '<p><strong>' . esc_html__( 'Websitebetreiber', 'frontend-rechtstexte-generator' ) . ':</strong> ' . esc_html( (string) $data['company'] ) . '</p>';
		}
		if ( ! $this->contains_required_value( $content, (string) ( $data['legal_form'] ?? '' ) ) && ! empty( $data['legal_form'] ) ) {
			$facts[] = '<p><strong>' . esc_html__( 'Rechtsform', 'frontend-rechtstexte-generator' ) . ':</strong> ' . esc_html( (string) $data['legal_form'] ) . '</p>';
		}
		if ( ! $this->contains_required_value( $content, (string) ( $data['representative'] ?? '' ) ) && ! empty( $data['representative'] ) ) {
			$label = ! empty( $data['representative_label'] ) ? (string) $data['representative_label'] : esc_html__( 'Vertretungsberechtigte Person', 'frontend-rechtstexte-generator' );
			$facts[] = '<p><strong>' . esc_html( $label ) . ':</strong> ' . esc_html( (string) $data['representative'] ) . '</p>';
		}
		if ( ! empty( $data['postal_address'] ) && ! $this->contains_required_value( $content, $this->normalize_visible_text( (string) $data['postal_address'] ) ) ) {
			$facts[] = '<div class="frg-address-block"><strong>' . esc_html__( 'Anschrift', 'frontend-rechtstexte-generator' ) . ':</strong><br>' . $data['postal_address'] . '</div>';
		}
		if ( ! $this->contains_required_value( $content, (string) ( $data['email'] ?? '' ) ) && ! empty( $data['email'] ) ) {
			$facts[] = '<p><strong>' . esc_html__( 'E-Mail', 'frontend-rechtstexte-generator' ) . ':</strong> ' . esc_html( (string) $data['email'] ) . '</p>';
		}
		return $this->append_required_facts( $content, $facts );
	}

	private function ensure_hosting_required_details( string $content, array $data ): string {
		$content = $this->ensure_hosting_av_notice( $content, $data );
		$content = (string) preg_replace( '/Art\.\s*6\s+Abs\.\s*1\s+DSGVO(?!\s*lit\.)/iu', 'Art. 6 Abs. 1 lit. f DSGVO', $content );
		$facts   = array();
		if ( false === stripos( wp_strip_all_tags( $content ), 'Art. 6 Abs. 1 lit. f DSGVO' ) ) {
			$facts[] = '<p><strong>' . esc_html__( 'Rechtsgrundlage', 'frontend-rechtstexte-generator' ) . ':</strong> Art. 6 Abs. 1 lit. f DSGVO (' . esc_html__( 'berechtigtes Interesse an einer sicheren, zuverlässigen und effizienten Bereitstellung der Website', 'frontend-rechtstexte-generator' ) . ').</p>';
		}

		if ( ! $this->contains_required_value( $content, (string) ( $data['host'] ?? $data['hosting_provider'] ?? '' ) ) && ! empty( $data['host'] ) ) {
			$facts[] = '<p><strong>' . esc_html__( 'Hosting-Anbieter', 'frontend-rechtstexte-generator' ) . ':</strong> ' . esc_html( (string) $data['host'] ) . '</p>';
		}
		if ( ! $this->contains_required_value( $content, (string) ( $data['location'] ?? $data['server_location'] ?? '' ) ) && ! empty( $data['location'] ) ) {
			$facts[] = '<p><strong>' . esc_html__( 'Serverstandort', 'frontend-rechtstexte-generator' ) . ':</strong> ' . esc_html( (string) $data['location'] ) . '</p>';
		}
		if ( ! empty( $data['host_address'] ) && ! $this->contains_required_value( $content, $this->normalize_visible_text( (string) $data['host_address'] ) ) ) {
			$facts[] = '<div class="frg-address-block"><strong>' . esc_html__( 'Anschrift des Hosting-Anbieters', 'frontend-rechtstexte-generator' ) . ':</strong><br>' . $data['host_address'] . '</div>';
		}
		if ( ! $this->contains_required_value( $content, (string) ( $data['server_infrastructure_provider'] ?? '' ) ) && ! empty( $data['server_infrastructure_provider'] ) ) {
			$facts[] = '<p><strong>' . esc_html__( 'Server-Infrastruktur-Anbieter', 'frontend-rechtstexte-generator' ) . ':</strong> ' . esc_html( (string) $data['server_infrastructure_provider'] ) . '</p>';
		}
		if ( ! $this->contains_required_value( $content, (string) ( $data['server_infrastructure_type'] ?? '' ) ) && ! empty( $data['server_infrastructure_type'] ) ) {
			$facts[] = '<p><strong>' . esc_html__( 'Art der Server-Infrastruktur', 'frontend-rechtstexte-generator' ) . ':</strong> ' . esc_html( (string) $data['server_infrastructure_type'] ) . '</p>';
		}
		if ( ! empty( $data['server_infrastructure_address'] ) && ! $this->contains_required_value( $content, $this->normalize_visible_text( (string) $data['server_infrastructure_address'] ) ) ) {
			$facts[] = '<div class="frg-address-block"><strong>' . esc_html__( 'Anschrift des Server-Infrastruktur-Anbieters', 'frontend-rechtstexte-generator' ) . ':</strong><br>' . $data['server_infrastructure_address'] . '</div>';
		}
		return $this->append_required_facts( $content, $facts );
	}

	private function ensure_controller_required_details( string $content, array $data ): string {
		$facts = array();

		if ( ! $this->contains_required_value( $content, (string) ( $data['company'] ?? '' ) ) && ! empty( $data['company'] ) ) {
			$facts[] = '<p><strong>' . esc_html__( 'Verantwortlicher', 'frontend-rechtstexte-generator' ) . ':</strong> ' . esc_html( (string) $data['company'] ) . '</p>';
		}
		if ( ! $this->contains_required_value( $content, (string) ( $data['representative'] ?? '' ) ) && ! empty( $data['representative'] ) ) {
			$label = ! empty( $data['representative_label'] ) ? (string) $data['representative_label'] : esc_html__( 'Vertretungsberechtigte Person', 'frontend-rechtstexte-generator' );
			$facts[] = '<p><strong>' . esc_html( $label ) . ':</strong> ' . esc_html( (string) $data['representative'] ) . '</p>';
		}
		if ( ! empty( $data['controller_address'] ) && ! $this->contains_required_value( $content, $this->normalize_visible_text( (string) $data['controller_address'] ) ) ) {
			$facts[] = '<div class="frg-address-block"><strong>' . esc_html__( 'Anschrift', 'frontend-rechtstexte-generator' ) . ':</strong><br>' . $data['controller_address'] . '</div>';
		}
		if ( ! $this->contains_required_value( $content, (string) ( $data['email'] ?? '' ) ) && ! empty( $data['email'] ) ) {
			$facts[] = '<p><strong>' . esc_html__( 'E-Mail', 'frontend-rechtstexte-generator' ) . ':</strong> ' . esc_html( (string) $data['email'] ) . '</p>';
		}
		if ( ! $this->contains_required_value( $content, (string) ( $data['controller_phone'] ?? '' ) ) && ! empty( $data['controller_phone'] ) ) {
			$facts[] = '<p><strong>' . esc_html__( 'Telefon', 'frontend-rechtstexte-generator' ) . ':</strong> ' . esc_html( (string) $data['controller_phone'] ) . '</p>';
		}

		return $this->append_required_facts( $content, $facts );
	}

	private function ensure_dpo_required_details( string $content, array $data ): string {
		$facts = array();

		if ( ! $this->contains_required_value( $content, (string) ( $data['name'] ?? '' ) ) && ! empty( $data['name'] ) ) {
			$facts[] = '<p><strong>' . esc_html__( 'Datenschutzbeauftragter', 'frontend-rechtstexte-generator' ) . ':</strong> ' . esc_html( (string) $data['name'] ) . '</p>';
		}
		if ( ! $this->contains_required_value( $content, (string) ( $data['dpo_email'] ?? '' ) ) && ! empty( $data['dpo_email'] ) ) {
			$facts[] = '<p><strong>' . esc_html__( 'E-Mail', 'frontend-rechtstexte-generator' ) . ':</strong> ' . esc_html( (string) $data['dpo_email'] ) . '</p>';
		}
		if ( ! $this->contains_required_value( $content, (string) ( $data['dpo_phone'] ?? '' ) ) && ! empty( $data['dpo_phone'] ) ) {
			$facts[] = '<p><strong>' . esc_html__( 'Telefon', 'frontend-rechtstexte-generator' ) . ':</strong> ' . esc_html( (string) $data['dpo_phone'] ) . '</p>';
		}
		if ( ! empty( $data['dpo_address'] ) && ! $this->contains_required_value( $content, $this->normalize_visible_text( (string) $data['dpo_address'] ) ) ) {
			$facts[] = '<div class="frg-address-block"><strong>' . esc_html__( 'Anschrift', 'frontend-rechtstexte-generator' ) . ':</strong><br>' . $data['dpo_address'] . '</div>';
		}

		return $this->append_required_facts( $content, $facts );
	}

	private function ensure_responsible_content_required_details( string $content, array $data ): string {
		$facts = array();

		if ( ! $this->contains_required_value( $content, (string) ( $data['name'] ?? '' ) ) && ! empty( $data['name'] ) ) {
			$facts[] = '<p><strong>' . esc_html__( 'Name der verantwortlichen Person', 'frontend-rechtstexte-generator' ) . ':</strong> ' . esc_html( (string) $data['name'] ) . '</p>';
		}
		if ( ! empty( $data['address'] ) && ! $this->contains_required_value( $content, $this->normalize_visible_text( (string) $data['address'] ) ) ) {
			$facts[] = '<div class="frg-address-block"><strong>' . esc_html__( 'Anschrift der verantwortlichen Person', 'frontend-rechtstexte-generator' ) . ':</strong><br>' . $data['address'] . '</div>';
		}

		return $this->append_required_facts( $content, $facts );
	}

	private function ensure_liability_required_details( string $content, array $data ): string {
		$facts = array();

		if ( ! $this->contains_required_value( $content, (string) ( $data['insurer'] ?? '' ) ) && ! empty( $data['insurer'] ) ) {
			$facts[] = '<p><strong>' . esc_html__( 'Versicherer', 'frontend-rechtstexte-generator' ) . ':</strong> ' . esc_html( (string) $data['insurer'] ) . '</p>';
		}
		if ( ! empty( $data['insurer_address'] ) && ! $this->contains_required_value( $content, $this->normalize_visible_text( (string) $data['insurer_address'] ) ) ) {
			$facts[] = '<div class="frg-address-block"><strong>' . esc_html__( 'Anschrift', 'frontend-rechtstexte-generator' ) . ':</strong><br>' . $data['insurer_address'] . '</div>';
		}
		if ( ! $this->contains_required_value( $content, (string) ( $data['scope'] ?? '' ) ) && ! empty( $data['scope'] ) ) {
			$facts[] = '<p><strong>' . esc_html__( 'Räumlicher Geltungsbereich', 'frontend-rechtstexte-generator' ) . ':</strong> ' . esc_html( (string) $data['scope'] ) . '</p>';
		}

		return $this->append_required_facts( $content, $facts );
	}

	private function append_required_facts( string $content, array $facts, string $title = '' ): string {
		$facts = array_filter( $facts );
		if ( empty( $facts ) ) {
			return $content;
		}

		return $content . implode( '', $facts );
	}

	private function contains_required_value( string $content, string $value ): bool {
		$value = $this->normalize_visible_text( $value );
		if ( '' === $value ) {
			return true;
		}

		return false !== mb_stripos( $this->normalize_visible_text( $content ), $value );
	}

	private function normalize_visible_text( string $value ): string {
		$value = html_entity_decode( wp_strip_all_tags( $value ), ENT_QUOTES | ENT_HTML5, 'UTF-8' );
		$value = preg_replace( '/\s+/u', ' ', $value );

		return trim( (string) $value );
	}

	public function get_impressum_base_module( array $data ): string {
		return $this->replace(
			'<h2>' . esc_html__( 'Impressum', 'frontend-rechtstexte-generator' ) . '</h2>{{document_notice}}<p><strong>' . esc_html__( 'Angaben gemäß § 5 DDG', 'frontend-rechtstexte-generator' ) . '</strong></p><p><strong>{{company}}</strong><br><strong>' . esc_html__( 'Rechtsform', 'frontend-rechtstexte-generator' ) . ':</strong> {{legal_form}}<br>{{representative_line}}</p><div class="frg-address-block"><strong>' . esc_html__( 'Anschrift', 'frontend-rechtstexte-generator' ) . ':</strong><br>{{postal_address}}</div><p><strong>' . esc_html__( 'Kontakt', 'frontend-rechtstexte-generator' ) . ':</strong><br>' . esc_html__( 'E-Mail', 'frontend-rechtstexte-generator' ) . ': {{email}}<br>{{phone_line}}{{website_line}}</p>',
			$data
		);
	}

	public function get_register_module( array $data ): string {
		// Juristische Prüfung empfohlen.
		return $this->replace( '<p><strong>' . esc_html__( 'Registereintrag', 'frontend-rechtstexte-generator' ) . ':</strong><br>' . esc_html__( 'Eintragung im Handelsregister.', 'frontend-rechtstexte-generator' ) . '<br><strong>' . esc_html__( 'Registergericht', 'frontend-rechtstexte-generator' ) . ':</strong> {{court}}<br><strong>' . esc_html__( 'Registernummer', 'frontend-rechtstexte-generator' ) . ':</strong> {{number}}</p>', $data );
	}

	public function get_vat_module( array $data ): string {
		// Juristische Prüfung empfohlen.
		return $this->replace( '<p><strong>' . esc_html__( 'Umsatzsteuer', 'frontend-rechtstexte-generator' ) . ':</strong><br>' . esc_html__( 'Umsatzsteuer-Identifikationsnummer gemäß § 27 a Umsatzsteuergesetz', 'frontend-rechtstexte-generator' ) . ': {{vat_id}}{{business_id_line}}</p>', $data );
	}

	public function get_responsible_content_module( array $data ): string {
		// Juristische Prüfung empfohlen.
		return $this->replace( '<p><strong>' . esc_html__( 'Verantwortlich für den Inhalt nach § 18 Abs. 2 MStV', 'frontend-rechtstexte-generator' ) . ':</strong><br>{{name}}</p><div class="frg-address-block"><strong>' . esc_html__( 'Anschrift', 'frontend-rechtstexte-generator' ) . ':</strong><br>{{address}}</div>', $data );
	}

	public function get_professional_information_module( array $data ): string {
		// Juristische Prüfung empfohlen.
		$rows = array(
			esc_html__( 'Zuständige Kammer', 'frontend-rechtstexte-generator' ) => $data['chamber'] ?? '',
			esc_html__( 'Berufsbezeichnung', 'frontend-rechtstexte-generator' ) => $data['title'] ?? '',
			esc_html__( 'Verliehen in', 'frontend-rechtstexte-generator' ) => $data['awarded_in'] ?? '',
			esc_html__( 'Berufsrechtliche Regelungen', 'frontend-rechtstexte-generator' ) => $data['rules'] ?? '',
			esc_html__( 'Aufsichtsbehörde', 'frontend-rechtstexte-generator' ) => $data['authority'] ?? '',
		);

		$output = '<div class="frg-required-facts frg-required-facts--professional"><p class="frg-required-facts__title"><strong>' . esc_html__( 'Berufsspezifische Angaben', 'frontend-rechtstexte-generator' ) . ':</strong></p>';
		$has_rows = false;

		foreach ( $rows as $label => $value ) {
			$value = trim( wp_strip_all_tags( (string) $value ) );
			if ( '' === $value ) {
				continue;
			}

			$has_rows = true;
			$output  .= '<p><strong>' . esc_html( $label ) . ':</strong><br>' . esc_html( $value ) . '</p>';
		}

		return $has_rows ? $output . '</div>' : '';
	}

	public function get_liability_insurance_module( array $data ): string {
		// Juristische Prüfung empfohlen.
		return $this->replace( '<p><strong>' . esc_html__( 'Angaben zur Berufshaftpflichtversicherung', 'frontend-rechtstexte-generator' ) . ':</strong></p><p><strong>' . esc_html__( 'Versicherer', 'frontend-rechtstexte-generator' ) . ':</strong> {{insurer}}</p><div class="frg-address-block"><strong>' . esc_html__( 'Anschrift', 'frontend-rechtstexte-generator' ) . ':</strong><br>{{insurer_address}}</div><p><strong>' . esc_html__( 'Räumlicher Geltungsbereich', 'frontend-rechtstexte-generator' ) . ':</strong> {{scope}}</p>', $data );
	}

	public function get_privacy_intro_module( array $data = array() ): string {
		// Juristische Pruefung empfohlen.
		return $this->replace(
			'<h2>' . esc_html__( 'Datenschutzerklärung', 'frontend-rechtstexte-generator' ) . '</h2>{{document_notice}}<p>' . esc_html__( 'Mit den folgenden Hinweisen informieren wir Sie über Art, Umfang und Zweck der Verarbeitung personenbezogener Daten im Zusammenhang mit der Nutzung der Website {{website_url}} durch {{company}}. Personenbezogene Daten sind alle Daten, mit denen Sie persönlich identifiziert werden können.', 'frontend-rechtstexte-generator' ) . '</p>',
			$data
		);
	}

	public function get_controller_module( array $data ): string {
		// Juristische Pruefung empfohlen.
		return $this->replace( '<h3>' . esc_html__( 'Verantwortlicher', 'frontend-rechtstexte-generator' ) . '</h3><p><strong>{{company}}</strong><br>{{representative_line}}</p><div class="frg-address-block"><strong>' . esc_html__( 'Anschrift', 'frontend-rechtstexte-generator' ) . ':</strong><br>{{controller_address}}</div><p><strong>' . esc_html__( 'E-Mail', 'frontend-rechtstexte-generator' ) . ':</strong> {{email}}{{controller_phone_line}}</p>', $data );
	}

	public function get_data_protection_officer_module( array $data ): string {
		// Juristische Pruefung empfohlen.
		return $this->replace( '<h3>' . esc_html__( 'Datenschutzbeauftragter', 'frontend-rechtstexte-generator' ) . '</h3><p><strong>{{name}}</strong><br>' . esc_html__( 'E-Mail', 'frontend-rechtstexte-generator' ) . ': {{dpo_email}}<br>{{dpo_phone_line}}</p>{{dpo_address_line}}', array_merge(
			$data,
			array(
				'dpo_phone_line'   => ! empty( $data['dpo_phone'] ) ? esc_html__( 'Telefon', 'frontend-rechtstexte-generator' ) . ': ' . $data['dpo_phone'] . '<br>' : '',
				'dpo_address_line' => ! empty( $data['dpo_address'] ) ? '<div class="frg-address-block"><strong>' . esc_html__( 'Anschrift', 'frontend-rechtstexte-generator' ) . ':</strong><br>' . $data['dpo_address'] . '</div>' : '',
			)
		) );
	}

	public function get_general_processing_module( array $data ): string {
		// Juristische Pruefung empfohlen.
		return $this->replace( '<h3>' . esc_html__( 'Allgemeine Hinweise zur Datenverarbeitung', 'frontend-rechtstexte-generator' ) . '</h3><p>' . esc_html__( 'Wir verarbeiten personenbezogene Daten nur, soweit dies zur Bereitstellung einer funktionsfähigen Website, zur Bearbeitung von Anfragen, zur Erfüllung vertraglicher oder vorvertraglicher Pflichten, zur Wahrung berechtigter Interessen oder auf Grundlage einer von Ihnen erteilten Einwilligung erforderlich ist.', 'frontend-rechtstexte-generator' ) . '</p><p><strong>' . esc_html__( 'Verarbeitungszwecke', 'frontend-rechtstexte-generator' ) . ':</strong> {{purposes}}</p><p><strong>' . esc_html__( 'Rechtsgrundlagen', 'frontend-rechtstexte-generator' ) . ':</strong> {{legal_basis}}</p><p><strong>' . esc_html__( 'Empfänger bzw. Kategorien von Empfängern', 'frontend-rechtstexte-generator' ) . ':</strong> {{recipients}}</p>', $data );
	}

	public function get_hosting_module( array $data ): string {
		// Juristische Pruefung empfohlen.
		return $this->replace(
			'<h3>' . esc_html__( 'Hosting und technische Bereitstellung', 'frontend-rechtstexte-generator' ) . '</h3><p>' . esc_html__( 'Diese Website wird durch {{host}} als Hosting- und IT-Dienstleister technisch bereitgestellt. Dabei werden die für den sicheren und zuverlässigen Betrieb erforderlichen Verbindungs- und Nutzungsdaten verarbeitet. Einzelheiten zu den technisch erfassten Daten finden Sie im Abschnitt „Server-Logfiles“.', 'frontend-rechtstexte-generator' ) . '</p>{{host_address_line}}{{server_infrastructure_line}}<p><strong>' . esc_html__( 'Serverstandort', 'frontend-rechtstexte-generator' ) . ':</strong> {{location}}</p><p>' . esc_html__( 'Die Verarbeitung erfolgt auf Grundlage von Art. 6 Abs. 1 lit. f DSGVO. Das berechtigte Interesse liegt in der sicheren, zuverlässigen und effizienten Bereitstellung der Website.', 'frontend-rechtstexte-generator' ) . '</p><p><strong>' . esc_html__( 'Auftragsverarbeitung', 'frontend-rechtstexte-generator' ) . ':</strong> ' . '{{av_sentence}} ' . esc_html__( 'Soweit im Rahmen der Leistungserbringung weitere Auftragsverarbeiter oder Unterauftragnehmer eingesetzt werden, erfolgt deren Einbindung unter Beachtung der Anforderungen des Art. 28 DSGVO.', 'frontend-rechtstexte-generator' ) . '</p>',
			array_merge(
				$data,
				array(
					'host_address_line' => ! empty( $data['host_address'] ) ? '<div class="frg-address-block"><strong>' . esc_html__( 'Anschrift des Hosting-Anbieters', 'frontend-rechtstexte-generator' ) . ':</strong><br>' . $data['host_address'] . '</div>' : '',
					'server_infrastructure_line' => ! empty( $data['server_infrastructure_provider'] ) ? '<p><strong>' . esc_html__( 'Server-Infrastruktur', 'frontend-rechtstexte-generator' ) . ':</strong><br>' . ( ! empty( $data['server_infrastructure_type'] ) ? sprintf(
						/* translators: 1: infrastructure type, 2: provider */
							esc_html__( 'Der Hosting-Dienstleister nutzt für die technische Server-Infrastruktur %1$s von %2$s. Der Infrastruktur-Anbieter wird dabei als Unterauftragnehmer eingebunden.', 'frontend-rechtstexte-generator' ),
						esc_html( (string) $data['server_infrastructure_type'] ),
						esc_html( (string) $data['server_infrastructure_provider'] )
					) : sprintf(
						/* translators: %s: server infrastructure provider */
						esc_html__( 'Für die Bereitstellung der technischen Server-Infrastruktur wird %s eingesetzt.', 'frontend-rechtstexte-generator' ),
						esc_html( (string) $data['server_infrastructure_provider'] )
					) ) . '</p>' . ( ! empty( $data['server_infrastructure_address'] ) ? '<div class="frg-address-block"><strong>' . esc_html__( 'Anschrift des Server-Infrastruktur-Anbieters', 'frontend-rechtstexte-generator' ) . ':</strong><br>' . $data['server_infrastructure_address'] . '</div>' : '' ) : '',
				)
			)
		);
	}

	public function get_hosting_module_compact( array $data ): string {
		// Juristische Prüfung empfohlen.
		$host_address = ! empty( $data['host_address'] ) ? '<div class="frg-address-block"><strong>' . esc_html__( 'Hosting-Anbieter', 'frontend-rechtstexte-generator' ) . ':</strong><br><strong>{{host}}</strong><br>' . $data['host_address'] . '</div>' : '<p><strong>' . esc_html__( 'Hosting-Anbieter', 'frontend-rechtstexte-generator' ) . ':</strong> {{host}}</p>';
		$infrastructure = '';
		if ( ! empty( $data['server_infrastructure_provider'] ) ) {
			$infrastructure = '<div class="frg-address-block"><strong>' . esc_html__( 'Server-Infrastruktur', 'frontend-rechtstexte-generator' ) . ':</strong><br><strong>{{server_infrastructure_provider}}</strong>';
			$infrastructure .= ! empty( $data['server_infrastructure_type'] ) ? '<br>{{server_infrastructure_type}}' : '';
			$infrastructure .= ! empty( $data['server_infrastructure_address'] ) ? '<br>' . $data['server_infrastructure_address'] : '';
			$infrastructure .= '</div>';
		}
		$subprocessor = ! empty( $data['server_infrastructure_provider'] ) ? ' ' . esc_html__( 'Der Server-Infrastruktur-Anbieter wird dabei als Unterauftragnehmer eingebunden.', 'frontend-rechtstexte-generator' ) : '';

		return $this->replace(
			'<h3>' . esc_html__( 'Hosting', 'frontend-rechtstexte-generator' ) . '</h3>' . $host_address . $infrastructure . '<p>' . esc_html__( 'Im Rahmen des Hostings werden technisch erforderliche Verbindungs- und Zugriffsdaten verarbeitet, um die Website sicher, stabil und zuverlässig bereitzustellen.', 'frontend-rechtstexte-generator' ) . '</p><p><strong>' . esc_html__( 'Serverstandort', 'frontend-rechtstexte-generator' ) . ':</strong> {{location}}</p><p>' . esc_html__( 'Rechtsgrundlage ist Art. 6 Abs. 1 lit. f DSGVO.', 'frontend-rechtstexte-generator' ) . '</p><p><strong>' . esc_html__( 'Auftragsverarbeitung', 'frontend-rechtstexte-generator' ) . ':</strong> {{av_sentence}}' . $subprocessor . '</p>',
			$data
		);
	}

	public function get_server_logs_module( array $data = array() ): string {
		// Juristische Pruefung empfohlen.
		return '<h3>' . esc_html__( 'Server-Logfiles', 'frontend-rechtstexte-generator' ) . '</h3><p>' . esc_html__( 'Beim Besuch dieser Website werden durch den Webserver regelmäßig Informationen in sogenannten Server-Logfiles erhoben und gespeichert. Erfasst werden können insbesondere Browsertyp und Browserversion, verwendetes Betriebssystem, Referrer-URL, Hostname des zugreifenden Rechners, Uhrzeit der Serveranfrage sowie die IP-Adresse.', 'frontend-rechtstexte-generator' ) . '</p><p>' . esc_html__( 'Die Verarbeitung dieser Daten erfolgt zur Gewährleistung der technischen Funktionsfähigkeit, zur IT-Sicherheit, zur Fehleranalyse und zur Abwehr missbräuchlicher Zugriffe. Eine Zusammenführung dieser Daten mit anderen Datenquellen erfolgt nur, soweit dies zur Klärung konkreter Sicherheits- oder Missbrauchsvorfälle erforderlich ist.', 'frontend-rechtstexte-generator' ) . '</p>';
	}

	public function get_server_logs_module_compact( array $data = array() ): string {
		// Juristische Prüfung empfohlen.
		return '<h3>' . esc_html__( 'Server-Logfiles', 'frontend-rechtstexte-generator' ) . '</h3><p>' . esc_html__( 'Beim Aufruf unserer Website werden technisch erforderliche Daten wie IP-Adresse, Zeitpunkt des Zugriffs, aufgerufene Inhalte, Browserinformationen und HTTP-Statuscodes in Server-Logfiles verarbeitet. Die Verarbeitung dient der sicheren und stabilen Bereitstellung der Website, der Fehleranalyse sowie der Abwehr von Angriffen und Missbrauch und erfolgt auf Grundlage von Art. 6 Abs. 1 lit. f DSGVO. Die Daten werden nur so lange gespeichert, wie dies für diese Zwecke erforderlich ist.', 'frontend-rechtstexte-generator' ) . '</p>';
	}

	public function get_contact_form_module( array $data = array() ): string {
		// Juristische Pruefung empfohlen.
		$form_tool = ! empty( $data['form_tools'] ) ? '<p><strong>' . esc_html__( 'Eingesetztes Formularsystem', 'frontend-rechtstexte-generator' ) . ':</strong> {{form_tools}}</p>' : '';
		return $this->replace(
			'<h3>' . esc_html__( 'Kontaktformular', 'frontend-rechtstexte-generator' ) . '</h3><p>' . esc_html__( 'Wenn Sie uns über das Kontaktformular auf {{website_url}} Anfragen zukommen lassen, werden Ihre Angaben aus dem Formular einschließlich der von Ihnen dort angegebenen Kontaktdaten zum Zweck der Bearbeitung Ihrer Anfrage und für den Fall von Anschlussfragen bei {{company}} gespeichert und verarbeitet.', 'frontend-rechtstexte-generator' ) . '</p>' . $form_tool . '<p>' . esc_html__( 'Die Verarbeitung erfolgt je nach Inhalt Ihrer Anfrage zur Durchführung vorvertraglicher Maßnahmen, zur Vertragserfüllung, auf Grundlage berechtigter Interessen an einer effizienten Kommunikation oder aufgrund Ihrer Einwilligung, sofern eine solche abgefragt wurde.', 'frontend-rechtstexte-generator' ) . '</p>',
			$data
		);
	}

	public function get_contact_form_module_compact( array $data = array() ): string {
		// Juristische Prüfung empfohlen.
		return $this->replace(
			'<h3>' . esc_html__( 'Kontaktformular', 'frontend-rechtstexte-generator' ) . '</h3><p>' . esc_html__( 'Wenn Sie uns über das Kontaktformular kontaktieren, verarbeiten wir die von Ihnen angegebenen Daten zur Bearbeitung Ihrer Anfrage und gegebenenfalls zur Anbahnung oder Durchführung eines Vertragsverhältnisses. Rechtsgrundlage ist Art. 6 Abs. 1 lit. b DSGVO beziehungsweise Art. 6 Abs. 1 lit. f DSGVO. Die Daten werden nur so lange gespeichert, wie dies für die Bearbeitung Ihrer Anfrage oder aufgrund gesetzlicher Aufbewahrungspflichten erforderlich ist.', 'frontend-rechtstexte-generator' ) . '</p>',
			$data
		);
	}

	public function get_email_contact_module( array $data = array() ): string {
		return $this->replace(
			'<h3>' . esc_html__( 'Kontakt per E-Mail oder Telefon', 'frontend-rechtstexte-generator' ) . '</h3><p>' . esc_html__( 'Wenn Sie {{company}} per E-Mail oder Telefon kontaktieren, verarbeiten wir die von Ihnen übermittelten oder mitgeteilten Daten zur Bearbeitung Ihres Anliegens, zur Kontaktaufnahme und zur Dokumentation der Kommunikation, soweit dies erforderlich ist. Für datenschutzbezogene Anfragen können Sie uns insbesondere unter {{email}} erreichen.', 'frontend-rechtstexte-generator' ) . '</p><p>' . esc_html__( 'Die Verarbeitung erfolgt regelmäßig zur Bearbeitung Ihrer Anfrage, zur Anbahnung oder Durchführung eines Vertragsverhältnisses oder auf Grundlage unseres berechtigten Interesses an einer ordnungsgemäßen Kommunikation.', 'frontend-rechtstexte-generator' ) . '</p>',
			$data
		);
	}

	public function get_comments_module(): string {
		// Juristische Pruefung empfohlen.
		return '<h3>' . esc_html__( 'Kommentare', 'frontend-rechtstexte-generator' ) . '</h3><p>' . esc_html__( 'Wenn auf dieser Website eine Kommentarfunktion angeboten wird, speichern wir neben Ihrem Kommentar regelmäßig auch Angaben zum Zeitpunkt der Erstellung, die von Ihnen gewählte Bezeichnung bzw. den angegebenen Namen sowie technische Daten, die der Missbrauchsprävention und der Sicherheit dienen können.', 'frontend-rechtstexte-generator' ) . '</p><p>' . esc_html__( 'Kommentare können gespeichert bleiben, solange der kommentierte Inhalt verfügbar ist oder solange dies zur Moderation, Nachvollziehbarkeit und Rechtsverteidigung erforderlich ist.', 'frontend-rechtstexte-generator' ) . '</p>';
	}

	public function get_registration_module(): string {
		// Juristische Pruefung empfohlen.
		return '<h3>' . esc_html__( 'Registrierung und Login-Bereich', 'frontend-rechtstexte-generator' ) . '</h3><p>' . esc_html__( 'Auf dieser Website wird eine Registrierung, ein Login-Bereich oder ein sonstiger geschützter Bereich angeboten. Dabei verarbeiten wir die zur Einrichtung, Verwaltung und Nutzung des Zugangs erforderlichen Bestands-, Zugangs- und Nutzungsdaten.', 'frontend-rechtstexte-generator' ) . '</p><p>' . esc_html__( 'Die Verarbeitung dient der Bereitstellung der jeweiligen Funktion, der Nutzerverwaltung, der IT-Sicherheit sowie gegebenenfalls der Vertragserfüllung. Ohne diese Daten kann der geschützte Bereich regelmäßig nicht oder nicht vollumfänglich bereitgestellt werden.', 'frontend-rechtstexte-generator' ) . '</p>';
	}

	public function get_newsletter_module( array $data = array() ): string {
		// Juristische Pruefung empfohlen.
		return $this->replace(
			'<h3>' . esc_html__( 'Newsletter und E-Mail-Marketing', 'frontend-rechtstexte-generator' ) . '</h3><p>' . esc_html__( 'Wenn Sie einen Newsletter oder vergleichbare E-Mail-Informationen von {{company}} abonnieren, verarbeiten wir die für die Anmeldung und Zusendung erforderlichen Daten. Dazu gehören regelmäßig die E-Mail-Adresse sowie gegebenenfalls weitere freiwillig angegebene Daten. Soweit eine Einwilligung erforderlich ist, erfolgt die Verarbeitung auf Grundlage Ihrer Einwilligung; diese können Sie jederzeit mit Wirkung für die Zukunft widerrufen.', 'frontend-rechtstexte-generator' ) . '</p><p>' . esc_html__( 'Zur Nachweisbarkeit von Anmeldungen und Einwilligungen können Anmeldezeitpunkte, Bestätigungen sowie technische Protokolldaten gespeichert werden. Für den Versand werden folgende Versand- oder Marketingdienste eingesetzt: {{newsletter_providers}}. Je nach Konfiguration können dabei Öffnungen und Klicks statistisch ausgewertet werden.', 'frontend-rechtstexte-generator' ) . '</p>',
			$data
		);
	}

	public function get_newsletter_provider_module( array $data ): string {
		// Juristische Pruefung empfohlen.
		return $this->replace( '<p>' . esc_html__( 'Für den Newsletter und das E-Mail-Marketing werden folgende externe Dienstleister eingesetzt', 'frontend-rechtstexte-generator' ) . ': {{providers}}.</p>', $data );
	}

	public function get_application_module( array $data = array() ): string {
		// Juristische Pruefung empfohlen.
		return $this->replace(
			'<h3>' . esc_html__( 'Bewerbungen', 'frontend-rechtstexte-generator' ) . '</h3><p>' . esc_html__( 'Wenn Sie {{company}} Bewerbungsunterlagen übermitteln, verarbeiten wir die darin enthaltenen personenbezogenen Daten zum Zweck der Durchführung des Bewerbungsverfahrens, zur Beurteilung Ihrer Eignung für die ausgeschriebene oder eine andere passende Position sowie zur Kommunikation mit Ihnen.', 'frontend-rechtstexte-generator' ) . '</p><p>' . esc_html__( 'Je nach Einzelfall können dabei auch besondere Kategorien personenbezogener Daten betroffen sein, sofern diese von Ihnen mitgeteilt werden. Die Daten werden nach Abschluss des Bewerbungsverfahrens gelöscht, sobald ihrer Löschung keine gesetzlichen Aufbewahrungspflichten oder berechtigten Interessen an einer weiteren Speicherung entgegenstehen.', 'frontend-rechtstexte-generator' ) . '</p>',
			$data
		);
	}

	public function get_booking_module( array $data = array() ): string {
		// Juristische Pruefung empfohlen.
		return $this->replace(
			'<h3>' . esc_html__( 'Terminbuchung', 'frontend-rechtstexte-generator' ) . '</h3><p>' . esc_html__( 'Wenn Sie über diese Website Termine bei {{company}} anfragen oder buchen, verarbeiten wir die von Ihnen eingegebenen Kontakt-, Termin- und gegebenenfalls Leistungsdaten, um den gewünschten Termin zu planen, zu bestätigen, durchzuführen und gegebenenfalls nachzubereiten.', 'frontend-rechtstexte-generator' ) . '</p><p>' . esc_html__( 'Für die Terminbuchung können Daten an folgende eingesetzte Dienste übermittelt werden: {{active_services}}.', 'frontend-rechtstexte-generator' ) . '</p>',
			$data
		);
	}

	public function get_shop_module( array $data = array() ): string {
		// Juristische Pruefung empfohlen.
		return $this->replace(
			'<h3>' . esc_html__( 'Bestellungen, Kundenkonto und Vertragsabwicklung', 'frontend-rechtstexte-generator' ) . '</h3><p>' . esc_html__( 'Wenn Sie über diese Website Bestellungen bei {{company}} tätigen, ein Kundenkonto anlegen oder vertragliche Leistungen in Anspruch nehmen, verarbeiten wir die für die Begründung, Durchführung und Abwicklung des Vertragsverhältnisses erforderlichen Daten. Hierzu können insbesondere Bestandsdaten, Rechnungs- und Lieferdaten, Kommunikationsdaten, Bestellinformationen sowie zahlungsbezogene Daten gehören.', 'frontend-rechtstexte-generator' ) . '</p><p>' . esc_html__( 'Dabei werden insbesondere folgende Funktionen eingesetzt: {{active_features}}. Die Verarbeitung erfolgt zur Vertragserfüllung, zur Erfüllung gesetzlicher Pflichten wie handels- und steuerrechtlicher Aufbewahrungspflichten sowie gegebenenfalls zur Durchsetzung oder Abwehr von Ansprüchen.', 'frontend-rechtstexte-generator' ) . '</p>',
			$data
		);
	}

	public function get_payment_provider_module( array $data = array() ): string {
		// Juristische Pruefung empfohlen.
		return $this->replace(
			'<h3>' . esc_html__( 'Zahlungsdienstleister', 'frontend-rechtstexte-generator' ) . '</h3><p>' . esc_html__( 'Zur Abwicklung von Zahlungen können wir personenbezogene Daten an eingesetzte Zahlungsdienstleister übermitteln, soweit dies für die Zahlungsabwicklung und Vertragserfüllung erforderlich ist. Welche Daten im Einzelfall übermittelt werden, richtet sich nach dem gewählten Zahlungsmittel und dem eingesetzten Anbieter.', 'frontend-rechtstexte-generator' ) . '</p><p>' . esc_html__( 'An der Zahlungsabwicklung können folgende Funktionen oder Dienste beteiligt sein: {{active_features}}; {{active_services}}. Zahlungsdienstleister können Daten außerdem zur Identitäts- oder Plausibilitätsprüfung, Betrugsprävention und Erfüllung regulatorischer Pflichten verarbeiten.', 'frontend-rechtstexte-generator' ) . '</p>',
			$data
		);
	}

	public function get_shipping_provider_module( array $data = array() ): string {
		// Juristische Pruefung empfohlen.
		return $this->replace(
			'<h3>' . esc_html__( 'Versanddienstleister', 'frontend-rechtstexte-generator' ) . '</h3><p>' . esc_html__( 'Zur Zustellung bestellter Waren oder zur Organisation logistischer Prozesse können wir die hierfür erforderlichen Daten an beauftragte Versand- und Logistikdienstleister übermitteln. Dazu gehören insbesondere Name, Lieferadresse sowie - soweit erforderlich - weitere Angaben zur Kontaktaufnahme oder Zustellung. Die Versandverarbeitung ist Bestandteil folgender Website-Funktionen: {{active_features}}.', 'frontend-rechtstexte-generator' ) . '</p>',
			$data
		);
	}

	public function get_download_area_module(): string {
		// Juristische Pruefung empfohlen.
		return '<h3>' . esc_html__( 'Downloads und bereitgestellte Inhalte', 'frontend-rechtstexte-generator' ) . '</h3><p>' . esc_html__( 'Wenn wir Downloadbereiche oder geschützte Inhalte bereitstellen, können zum Zweck der Bereitstellung, Zugriffskontrolle, Missbrauchsprävention und Nachvollziehbarkeit Zugriffs-, Nutzungs- und gegebenenfalls Registrierungs- oder Kontaktdaten verarbeitet werden.', 'frontend-rechtstexte-generator' ) . '</p>';
	}

	public function get_members_area_module(): string {
		// Juristische Pruefung empfohlen.
		return '<h3>' . esc_html__( 'Mitgliederbereich', 'frontend-rechtstexte-generator' ) . '</h3><p>' . esc_html__( 'Für Mitglieder-, Kurs-, Mandanten- oder interne Bereiche werden personenbezogene Daten verarbeitet, soweit dies für die Anlage und Verwaltung von Zugangsrechten, die Bereitstellung geschützter Inhalte, die Kommunikation mit Nutzern und die Sicherheit des Angebots erforderlich ist.', 'frontend-rechtstexte-generator' ) . '</p>';
	}

	public function get_training_portal_module( array $data = array() ): string {
		// Juristische Pruefung empfohlen.
		$training_modules = trim( wp_strip_all_tags( (string) ( $data['training_modules'] ?? '' ) ) );

		if ( '' === $training_modules && ! empty( $data['features'] ) && is_array( $data['features'] ) ) {
			$labels = array();
			if ( ! empty( $data['features']['training_progress'] ) ) {
				$labels[] = __( 'Lernfortschritt', 'frontend-rechtstexte-generator' );
			}
			if ( ! empty( $data['features']['training_tests'] ) ) {
				$labels[] = __( 'Tests, Quiz und Prüfungsergebnisse', 'frontend-rechtstexte-generator' );
			}
			if ( ! empty( $data['features']['training_certificates'] ) ) {
				$labels[] = __( 'Zertifikate und Teilnahmebescheinigungen', 'frontend-rechtstexte-generator' );
			}
			if ( ! empty( $data['features']['employee_training'] ) ) {
				$labels[] = __( 'Mitarbeiterschulungen und Pflichtschulungen', 'frontend-rechtstexte-generator' );
			}
			if ( ! empty( $data['features']['scorm_tracking'] ) ) {
				$labels[] = __( 'SCORM-Tracking und Lernpaketstände', 'frontend-rechtstexte-generator' );
			}
			if ( ! empty( $data['features']['certificate_download'] ) ) {
				$labels[] = __( 'Zertifikats-Downloads', 'frontend-rechtstexte-generator' );
			}
			if ( ! empty( $data['features']['mandatory_training_proof'] ) ) {
				$labels[] = __( 'Nachweise absolvierte Pflichtunterweisungen', 'frontend-rechtstexte-generator' );
			}
			if ( ! empty( $data['features']['trainer_manager_access'] ) ) {
				$labels[] = __( 'Dozenten-, Manager- und Admin-Zugriffe', 'frontend-rechtstexte-generator' );
			}
			if ( ! empty( $data['features']['tenant_access'] ) ) {
				$labels[] = __( 'Mandanten- oder Firmenzugriffe', 'frontend-rechtstexte-generator' );
			}
			$training_modules = implode( ', ', $labels );
		}

		$module_sentence = '';
		if ( '' !== $training_modules ) {
			$module_sentence = ' ' . sprintf(
				/* translators: %s: list of training portal functions */
				esc_html__( 'Dabei werden insbesondere folgende Portal-Funktionen eingesetzt: %s.', 'frontend-rechtstexte-generator' ),
				esc_html( $training_modules )
			);
		}

		$employee_sentence = '';
		if ( ! empty( $data['features']['employee_training'] ) ) {
			$employee_sentence = ' ' . esc_html__( 'Soweit das Portal für Mitarbeiterschulungen oder Pflichtunterweisungen genutzt wird, kann je nach Einzelfall zusätzlich § 26 BDSG für die Verarbeitung von Beschäftigtendaten relevant sein.', 'frontend-rechtstexte-generator' );
		}

		$scorm_sentence = '';
		if ( ! empty( $data['features']['scorm_tracking'] ) ) {
			$scorm_sentence = ' ' . esc_html__( 'Sofern SCORM-Inhalte oder vergleichbare Lernpakete eingesetzt werden, können zusätzlich Start- und Endzeitpunkte, Bearbeitungsstände, Fortschrittswerte, Statusmeldungen und Interaktionsdaten innerhalb der Lernmodule verarbeitet werden.', 'frontend-rechtstexte-generator' );
		}

		$certificate_sentence = '';
		if ( ! empty( $data['features']['certificate_download'] ) || ! empty( $data['features']['training_certificates'] ) ) {
			$certificate_sentence = ' ' . esc_html__( 'Soweit Zertifikate oder Teilnahmebescheinigungen erzeugt oder heruntergeladen werden können, werden hierfür regelmäßig Identitäts-, Kurs-, Abschluss- und Nachweisdaten verarbeitet und dokumentiert.', 'frontend-rechtstexte-generator' );
		}

		$mandatory_sentence = '';
		if ( ! empty( $data['features']['mandatory_training_proof'] ) ) {
			$mandatory_sentence = ' ' . esc_html__( 'Bei Pflichtunterweisungen kann zusätzlich dokumentiert werden, ob und wann eine Schulung absolviert, bestätigt oder erneut angefordert wurde, um gesetzliche oder interne Nachweispflichten zu erfüllen.', 'frontend-rechtstexte-generator' );
		}

		$role_sentence = '';
		if ( ! empty( $data['features']['trainer_manager_access'] ) || ! empty( $data['features']['tenant_access'] ) ) {
			$role_sentence = ' ' . esc_html__( 'Je nach Rollen- und Berechtigungskonzept können Dozenten, Manager, Administratoren oder berechtigte Ansprechpartner von Firmen bzw. Mandanten auf kurs- oder nutzerbezogene Informationen zugreifen, soweit dies für Betreuung, Auswertung, Administration oder Nachweisführung erforderlich ist.', 'frontend-rechtstexte-generator' );
		}

		return '<h3>' . esc_html__( 'Schulungsportal / Lernplattform', 'frontend-rechtstexte-generator' ) . '</h3><p>' . esc_html__( 'Wenn wir über diese Website ein Schulungsportal oder eine Lernplattform bereitstellen, verarbeiten wir personenbezogene Daten, soweit dies für die Registrierung, die Bereitstellung von Kursinhalten, die Verwaltung von Zugängen, die Durchführung von Schulungen sowie die Dokumentation von Teilnahme- und Lernständen erforderlich ist.', 'frontend-rechtstexte-generator' ) . $module_sentence . '</p><p>' . esc_html__( 'Dabei können insbesondere Stamm- und Kontaktdaten, Login-Daten, Kurszuweisungen, Nutzungs- und Zugriffsprotokolle, Lernfortschritte, Testergebnisse sowie gegebenenfalls Zertifikate oder Teilnahmebescheinigungen verarbeitet werden. Die Verarbeitung erfolgt regelmäßig zur Vertragserfüllung, zur Bereitstellung des ausdrücklich gewünschten Portalzugangs, zur System- und Zugriffssicherheit sowie gegebenenfalls zur Dokumentation absolvierter Schulungen.', 'frontend-rechtstexte-generator' ) . $scorm_sentence . $certificate_sentence . $mandatory_sentence . $role_sentence . $employee_sentence . '</p>';
	}

	public function get_ai_chatbot_module( array $data = array() ): string {
		// Juristische Prüfung empfohlen.
		$privacy_link = ! empty( $data['ai_chatbot_privacy_link'] )
			? '<p>' . sprintf(
				/* translators: %s: linked privacy information of the AI bot plugin */
				esc_html__( 'Weitere Informationen zur konkreten technischen Einbindung des KI-Bots finden Sie hier: %s.', 'frontend-rechtstexte-generator' ),
				$data['ai_chatbot_privacy_link']
			) . '</p>'
			: '';

		return $this->replace(
			'<h3>' . esc_html__( 'Website-KI-Bot / KI-Assistent', 'frontend-rechtstexte-generator' ) . '</h3><p>' . esc_html__( 'Auf dieser Website wird ein KI-gestützter Chatbot eingesetzt, um Nutzeranfragen automatisiert zu beantworten, Informationen bereitzustellen und bei der Nutzung der Website zu unterstützen. Bei der Nutzung werden die eingegebenen Nachrichten, technische Nutzungsdaten, Zeitpunkte der Interaktion sowie Browser- und Geräteinformationen verarbeitet.', 'frontend-rechtstexte-generator' ) . ' {{ai_transparency_sentence}}</p><p>' . esc_html__( 'Für die Verarbeitung und Beantwortung der Eingaben werden folgende KI-Anbieter eingesetzt: {{ai_providers}}. Die eingegebenen Inhalte werden zur Erstellung der Antwort an diese Anbieter übermittelt und dort verarbeitet. Bitte geben Sie keine sensiblen personenbezogenen Daten, Gesundheitsdaten, Zugangsdaten oder sonstigen vertraulichen Informationen ein.', 'frontend-rechtstexte-generator' ) . '</p><p>' . esc_html__( 'Die Verarbeitung erfolgt zur Bereitstellung der angeforderten Bot-Funktion sowie nach Maßgabe der im Generator konkret hinterlegten Rechtsgrundlage.', 'frontend-rechtstexte-generator' ) . '</p>' . $privacy_link,
			$data
		);
	}

	public function get_google_fonts_external_module( array $data = array() ): string {
		// Juristische Pruefung empfohlen.
		return $this->replace( '<h3>Google Fonts</h3><p>' . esc_html__( 'Auf dieser Website werden Schriftarten über Server von Google geladen. Beim Aufruf der Website wird dadurch eine Verbindung zu Google hergestellt, bei der insbesondere Ihre IP-Adresse und weitere technische Verbindungsdaten übermittelt werden können.', 'frontend-rechtstexte-generator' ) . '</p>', $data );
	}

	public function get_google_fonts_local_module( array $data = array() ): string {
		return '<h3>Google Fonts</h3><p>' . esc_html__( 'Google Fonts werden lokal auf unserem Server bereitgestellt. Beim Aufruf der Website wird keine Verbindung zu Servern von Google hergestellt und es werden im Zusammenhang mit der Bereitstellung der Schriftarten keine personenbezogenen Daten an Google übermittelt.', 'frontend-rechtstexte-generator' ) . '</p>';
	}

	public function get_google_fonts_local_module_compact( array $data = array() ): string {
		// Juristische Prüfung empfohlen.
		return '<h3>' . esc_html__( 'Google Fonts (lokale Einbindung)', 'frontend-rechtstexte-generator' ) . '</h3><p>' . esc_html__( 'Auf dieser Website werden Google Fonts lokal auf unserem eigenen Server bereitgestellt. Beim Aufruf der Website wird daher keine Verbindung zu Servern von Google hergestellt und es werden im Zusammenhang mit der Bereitstellung der Schriftarten keine personenbezogenen Daten an Google übermittelt. Die Verarbeitung im Rahmen der lokalen Bereitstellung erfolgt auf Grundlage von Art. 6 Abs. 1 lit. f DSGVO.', 'frontend-rechtstexte-generator' ) . '</p>';
	}

	public function get_google_maps_module( array $data = array() ): string {
		// Juristische Pruefung empfohlen.
		return $this->replace( '<h3>Google Maps</h3><p>' . esc_html__( 'Auf dieser Website wird Google Maps zur Darstellung interaktiver Karten eingesetzt. Beim Laden einer eingebetteten Karte können insbesondere IP-Adresse, Standortbezüge, Nutzungsdaten und technische Verbindungsdaten an Google übermittelt werden.', 'frontend-rechtstexte-generator' ) . '</p>', $data );
	}

	public function get_youtube_module( array $data = array() ): string {
		// Juristische Pruefung empfohlen.
		return $this->replace( '<h3>YouTube</h3><p>' . esc_html__( 'Auf dieser Website sind Videos der Plattform YouTube eingebunden. Beim Laden oder Abspielen eines Videos kann eine Verbindung zu Servern von YouTube beziehungsweise Google hergestellt werden. Dabei können insbesondere IP-Adresse, Nutzungsdaten, technische Informationen zum Endgerät und Interaktionsdaten verarbeitet werden.', 'frontend-rechtstexte-generator' ) . '</p>', $data );
	}

	public function get_vimeo_module( array $data = array() ): string {
		// Juristische Pruefung empfohlen.
		$consent_sentence = '';
		if ( 'Vor Einwilligung blockiert' === ( $data['service_consent'] ?? '' ) ) {
			$consent_sentence = ! empty( $data['consent_tools'] )
				? ' ' . sprintf( esc_html__( 'Die Vimeo-Inhalte werden durch %s blockiert und erst nach Ihrer Einwilligung geladen. Rechtsgrundlage ist Art. 6 Abs. 1 lit. a DSGVO. Die Einwilligung kann jederzeit mit Wirkung für die Zukunft widerrufen werden.', 'frontend-rechtstexte-generator' ), esc_html( (string) $data['consent_tools'] ) )
				: ' ' . esc_html__( 'Die Vimeo-Inhalte werden vor der Einwilligung blockiert und erst nach Ihrer Einwilligung geladen. Rechtsgrundlage ist Art. 6 Abs. 1 lit. a DSGVO. Die Einwilligung kann jederzeit mit Wirkung für die Zukunft widerrufen werden.', 'frontend-rechtstexte-generator' );
		}
		return $this->replace( '<h3>Vimeo</h3><p>' . esc_html__( 'Auf dieser Website sind Videos des Anbieters {{service_provider}} eingebunden. Beim Laden oder Abspielen dieser Inhalte werden Verbindungsdaten, IP-Adresse, Browserinformationen und Nutzungsdaten an den Anbieter übermittelt.', 'frontend-rechtstexte-generator' ) . $consent_sentence . '</p>', $data );
	}

	public function get_google_analytics_module( array $data = array() ): string {
		// Juristische Pruefung empfohlen.
		return $this->replace( '<h3>Google Analytics</h3><p>' . esc_html__( 'Google Analytics wird zur Analyse des Nutzerverhaltens und zur statistischen Auswertung der Nutzung dieser Website eingesetzt. Dabei können insbesondere Seitenaufrufe, Verweildauer, Interaktionen, technische Geräteinformationen, Referrer-Informationen sowie gekürzte oder anderweitig verarbeitete IP-bezogene Daten verarbeitet werden.', 'frontend-rechtstexte-generator' ) . '</p>', $data );
	}

	public function get_google_tag_manager_module( array $data = array() ): string {
		// Juristische Pruefung empfohlen.
		return $this->replace( '<h3>Google Tag Manager</h3><p>' . esc_html__( 'Der Google Tag Manager dient der Verwaltung und Ausspielung von Website-Tags. Der Dienst selbst erstellt nach üblicher Konfiguration nicht zwingend eigenständige Nutzerprofile, kann aber weitere Tools und Tracking-Dienste technisch einbinden und deren Auslösung steuern.', 'frontend-rechtstexte-generator' ) . '</p><p>' . esc_html__( 'Über den Tag Manager können insbesondere folgende weitere Dienste eingebunden und gesteuert werden: {{active_services}}.', 'frontend-rechtstexte-generator' ) . '</p>', $data );
	}

	public function get_google_ads_conversion_tracking_module(): string {
		// Juristische Pruefung empfohlen.
		return '<h3>' . esc_html__( 'Google Ads Conversion Tracking', 'frontend-rechtstexte-generator' ) . '</h3><p>' . esc_html__( 'Google Ads Conversion Tracking wird eingesetzt, um nachzuvollziehen, ob Nutzer nach einem Klick auf eine Anzeige bestimmte Aktionen auf dieser Website ausführen. Dabei können insbesondere Conversion-Daten, technische Kennungen und Nutzungsinformationen verarbeitet werden.', 'frontend-rechtstexte-generator' ) . '</p>';
	}

	public function get_meta_pixel_module(): string {
		// Juristische Pruefung empfohlen.
		return '<h3>Meta Pixel</h3><p>' . esc_html__( 'Mit dem Meta Pixel werden Interaktionen von Nutzern auf dieser Website für Marketing-, Remarketing- und Conversion-Zwecke erfasst und an Meta übermittelt. Dabei können insbesondere Seitenaufrufe, technische Kennungen, Browserinformationen und Nutzungsdaten verarbeitet werden.', 'frontend-rechtstexte-generator' ) . '</p>';
	}

	public function get_matomo_module(): string {
		// Juristische Pruefung empfohlen.
		return '<h3>Matomo</h3><p>' . esc_html__( 'Matomo wird zur statistischen Analyse der Nutzung dieser Website eingesetzt. Je nach Konfiguration können dabei Nutzungsdaten, Seitenaufrufe, technische Informationen und gekürzte IP-bezogene Daten verarbeitet werden.', 'frontend-rechtstexte-generator' ) . '</p>';
	}

	public function get_microsoft_clarity_module(): string {
		// Juristische Pruefung empfohlen.
		return '<h3>Microsoft Clarity</h3><p>' . esc_html__( 'Microsoft Clarity wird zur Analyse des Nutzerverhaltens eingesetzt. Je nach Konfiguration können dabei insbesondere Mausbewegungen, Scroll-Verhalten, Klicks, aufgerufene Seiten, technische Geräteinformationen und Interaktionsdaten ausgewertet werden.', 'frontend-rechtstexte-generator' ) . '</p>';
	}

	public function get_cloudflare_module(): string {
		// Juristische Pruefung empfohlen.
		return '<h3>Cloudflare</h3><p>' . esc_html__( 'Auf dieser Website wird Cloudflare als Content Delivery Network, Sicherheits- oder Performance-Dienst eingesetzt. Dabei können insbesondere IP-Adresse, Anfragedaten, Sicherheitsmerkmale und technische Verbindungsinformationen verarbeitet werden, um Inhalte schneller auszuliefern und Angriffe auf die Website abzuwehren.', 'frontend-rechtstexte-generator' ) . '</p>';
	}

	public function get_recaptcha_module(): string {
		// Juristische Pruefung empfohlen.
		return '<h3>reCAPTCHA</h3><p>' . esc_html__( 'Zum Schutz vor automatisierten Eingaben und Missbrauch wird reCAPTCHA eingesetzt. Dabei können Daten an Google übermittelt werden.', 'frontend-rechtstexte-generator' ) . '</p>';
	}

	public function get_hcaptcha_module(): string {
		// Juristische Pruefung empfohlen.
		return '<h3>hCaptcha</h3><p>' . esc_html__( 'Zum Schutz vor automatisierten Eingaben wird hCaptcha eingesetzt. Dabei werden Nutzungs- und Verbindungsdaten verarbeitet.', 'frontend-rechtstexte-generator' ) . '</p>';
	}

	public function get_cloudflare_turnstile_module(): string {
		// Juristische Prüfung empfohlen.
		return '<h3>Cloudflare Turnstile</h3><p>' . esc_html__( 'Zum Schutz von Formularen vor automatisierten Eingaben und Missbrauch wird Cloudflare Turnstile eingesetzt. Hierbei können insbesondere IP-Adresse, Browser- und Geräteinformationen, Angaben zur aufgerufenen Seite sowie Interaktions- und Prüfdaten verarbeitet und an Cloudflare übermittelt werden.', 'frontend-rechtstexte-generator' ) . '</p>';
	}

	public function get_cookie_consent_module( array $data = array() ): string {
		// Juristische Prüfung empfohlen.
		return $this->replace( '<h3>' . esc_html__( 'Cookie-Einwilligungsmanagement', 'frontend-rechtstexte-generator' ) . '</h3><p>' . esc_html__( 'Auf dieser Website wird {{consent_tools}} eingesetzt, um Einwilligungen für technisch nicht erforderliche Cookies, vergleichbare Technologien und externe Dienste einzuholen, zu verwalten und zu dokumentieren. Dabei werden insbesondere Einwilligungsstatus, Zeitpunkt der Entscheidung, technische Kennungen und Browserinformationen verarbeitet.', 'frontend-rechtstexte-generator' ) . '</p><p>' . esc_html__( 'Die Verarbeitung der Einwilligungsentscheidung dient der Erfüllung rechtlicher Nachweis- und Rechenschaftspflichten und erfolgt auf Grundlage von Art. 6 Abs. 1 lit. c DSGVO in Verbindung mit Art. 5 Abs. 2 und Art. 7 Abs. 1 DSGVO. Der Zugriff auf unbedingt erforderliche Informationen im Endgerät erfolgt auf Grundlage von § 25 Abs. 2 TDDDG. Optionale Dienste werden erst nach einer Einwilligung gemäß Art. 6 Abs. 1 lit. a DSGVO und § 25 Abs. 1 TDDDG aktiviert.', 'frontend-rechtstexte-generator' ) . '</p>', $data );
	}

	public function get_social_media_profiles_module( array $data ): string {
		// Juristische Pruefung empfohlen.
		if ( 'embeds' === ( $data['social_media_integration'] ?? 'links' ) ) {
			return $this->replace( '<h3>' . esc_html__( 'Eingebettete Social-Media-Inhalte', 'frontend-rechtstexte-generator' ) . '</h3><p>' . esc_html__( 'Auf dieser Website sind Inhalte folgender sozialer Netzwerke beziehungsweise Plattformen eingebettet', 'frontend-rechtstexte-generator' ) . ': {{profiles}}.</p><p>' . esc_html__( 'Beim Laden oder bei der Interaktion mit diesen Inhalten wird eine Verbindung zum jeweiligen Plattformbetreiber hergestellt. Dabei werden insbesondere IP-Adresse, Browser- und Geräteinformationen sowie Interaktionsdaten verarbeitet.', 'frontend-rechtstexte-generator' ) . '</p>', $data );
		}

		return $this->replace( '<h3>' . esc_html__( 'Social-Media-Verlinkungen', 'frontend-rechtstexte-generator' ) . '</h3><p>' . esc_html__( 'Diese Website enthält Verlinkungen zu folgenden Social-Media-Profilen', 'frontend-rechtstexte-generator' ) . ': {{profiles}}.</p><p>' . esc_html__( 'Beim bloßen Aufruf dieser Website werden über diese Links keine Daten an die Plattformbetreiber übertragen. Erst wenn Sie einen Link anklicken, verlassen Sie diese Website; die weitere Datenverarbeitung erfolgt dann durch den jeweiligen Plattformbetreiber.', 'frontend-rechtstexte-generator' ) . '</p>', $data );
	}

	public function get_embeds_module( array $data = array() ): string {
		// Juristische Pruefung empfohlen.
		return $this->replace( '<h3>' . esc_html__( 'Eingebettete Inhalte und externe Ressourcen', 'frontend-rechtstexte-generator' ) . '</h3><p>' . esc_html__( 'Bei der Einbindung externer Inhalte, Medien, Karten, Schriftarten oder sonstiger Ressourcen kann Ihr Browser eine direkte Verbindung zu Servern der jeweiligen Drittanbieter herstellen. Hierbei können insbesondere IP-Adresse, Browserinformationen, Nutzungsdaten und technische Verbindungsdaten übermittelt werden.', 'frontend-rechtstexte-generator' ) . '</p><p>' . esc_html__( 'Dies betrifft insbesondere folgende Dienste: {{active_services}}. Umfang und Art der Verarbeitung hängen von der konkreten Einbindung und dem jeweiligen Drittanbieter ab.', 'frontend-rechtstexte-generator' ) . '</p>', $data );
	}

	public function get_security_plugins_module( array $data = array() ): string {
		return $this->replace( '<h3>' . esc_html__( 'Sicherheits-Plugins und Schutzmechanismen', 'frontend-rechtstexte-generator' ) . '</h3><p>' . esc_html__( 'Zum Schutz dieser Website werden folgende Sicherheits-Plugins oder vergleichbare Schutzmechanismen eingesetzt: {{security_tools}}. Dabei können insbesondere IP-Adressen, technische Zugriffsdaten, Login-Vorgänge und auffällige Anfragemuster verarbeitet werden, um Angriffe, Missbrauch oder unberechtigte Zugriffe zu erkennen und abzuwehren.', 'frontend-rechtstexte-generator' ) . '</p>', $data );
	}

	public function get_backup_plugins_module( array $data = array() ): string {
		$details = '';
		if ( ! empty( $data['backup_destination'] ) ) {
			$details .= '<p><strong>' . esc_html__( 'Speicherort der Sicherungen', 'frontend-rechtstexte-generator' ) . ':</strong> {{backup_destination}}</p>';
		}
		if ( ! empty( $data['backup_storage_provider'] ) ) {
			$details .= '<p><strong>' . esc_html__( 'Anbieter des Backup-Speichers', 'frontend-rechtstexte-generator' ) . ':</strong> {{backup_storage_provider}}</p>';
		}
		if ( ! empty( $data['backup_storage_address'] ) ) {
			$details .= '<div class="frg-address-block"><strong>' . esc_html__( 'Anschrift des Backup-Anbieters', 'frontend-rechtstexte-generator' ) . ':</strong><br>{{backup_storage_address}}</div>';
		}
		if ( ! empty( $data['backup_retention'] ) ) {
			$details .= '<p><strong>' . esc_html__( 'Aufbewahrungsdauer', 'frontend-rechtstexte-generator' ) . ':</strong> {{backup_retention}}</p>';
		}

		return $this->replace( '<h3>' . esc_html__( 'Backups und Wiederherstellung', 'frontend-rechtstexte-generator' ) . '</h3><p>' . esc_html__( 'Zur Sicherung und Wiederherstellung dieser Website erstellen wir regelmäßige Backups der Website-Dateien und Datenbanken. Die Verarbeitung erfolgt auf Grundlage von Art. 6 Abs. 1 lit. f DSGVO. Unser berechtigtes Interesse liegt in der Sicherstellung der Verfügbarkeit, Integrität und Wiederherstellbarkeit unserer Website und der darauf verarbeiteten Daten.', 'frontend-rechtstexte-generator' ) . '</p>' . $details, $data );
	}

	public function get_data_subject_rights_module(): string {
		// Juristische Pruefung empfohlen.
		return '<h3>' . esc_html__( 'Betroffenenrechte', 'frontend-rechtstexte-generator' ) . '</h3><p>' . esc_html__( 'Sie haben im Rahmen der geltenden gesetzlichen Bestimmungen insbesondere das Recht auf Auskunft (Art. 15 DSGVO), Berichtigung (Art. 16 DSGVO), Löschung (Art. 17 DSGVO), Einschränkung der Verarbeitung (Art. 18 DSGVO) sowie Datenübertragbarkeit (Art. 20 DSGVO).', 'frontend-rechtstexte-generator' ) . '</p><p>' . esc_html__( 'Soweit eine Verarbeitung auf Ihrer Einwilligung beruht, können Sie diese Einwilligung jederzeit mit Wirkung für die Zukunft widerrufen. Soweit wir Daten auf Grundlage berechtigter Interessen verarbeiten, haben Sie nach Art. 21 DSGVO zudem das Recht, aus Gründen, die sich aus Ihrer besonderen Situation ergeben, Widerspruch gegen diese Verarbeitung einzulegen, soweit die gesetzlichen Voraussetzungen vorliegen.', 'frontend-rechtstexte-generator' ) . '</p>';
	}

	public function get_storage_duration_module( array $data ): string {
		// Juristische Pruefung empfohlen.
		return $this->replace( '<h3>' . esc_html__( 'Speicherdauer', 'frontend-rechtstexte-generator' ) . '</h3><p>{{storage}}</p>', $data );
	}

	public function get_third_country_transfer_module( array $data ): string {
		// Juristische Pruefung empfohlen.
		return $this->replace( '<h3>' . esc_html__( 'Drittlandtransfer', 'frontend-rechtstexte-generator' ) . '</h3><p>{{third_country}}</p>', $data );
	}

	public function get_complaint_authority_module( array $data = array() ): string {
		// Juristische Pruefung empfohlen.
		$authority = '';
		if ( ! empty( $data['privacy_supervisory_authority_name'] ) ) {
			$authority .= '<p><strong>{{privacy_supervisory_authority_name}}</strong></p>';
		}
		if ( ! empty( $data['privacy_supervisory_authority_address'] ) ) {
			$authority .= '<div class="frg-address-block">{{privacy_supervisory_authority_address}}</div>';
		}
		if ( ! empty( $data['privacy_supervisory_authority_url'] ) ) {
			$authority .= '<p><a href="{{privacy_supervisory_authority_url}}" rel="nofollow noopener" target="_blank">' . esc_html__( 'Website der Aufsichtsbehörde', 'frontend-rechtstexte-generator' ) . '</a></p>';
		}

		return $this->replace( '<h3>' . esc_html__( 'Beschwerderecht bei einer Aufsichtsbehörde', 'frontend-rechtstexte-generator' ) . '</h3><p>' . esc_html__( 'Sie haben unbeschadet anderweitiger verwaltungsrechtlicher oder gerichtlicher Rechtsbehelfe das Recht, sich bei einer Datenschutzaufsichtsbehörde über die Verarbeitung Ihrer personenbezogenen Daten zu beschweren, wenn Sie der Ansicht sind, dass die Verarbeitung gegen datenschutzrechtliche Vorgaben verstößt.', 'frontend-rechtstexte-generator' ) . '</p>' . $authority, $data );
	}

	public function get_ssl_tls_module(): string {
		return '<h3>SSL/TLS</h3><p>' . esc_html__( 'Diese Website nutzt aus Sicherheitsgründen und zum Schutz der Übertragung vertraulicher Inhalte eine Verschlüsselung per SSL bzw. TLS. Eine verschlüsselte Verbindung erkennen Sie in der Regel an der Adresszeile Ihres Browsers und am Schloss-Symbol in der Browserzeile.', 'frontend-rechtstexte-generator' ) . '</p>';
	}

	public function get_calendly_module( array $data = array() ): string {
		// Juristische Pruefung empfohlen.
		return $this->replace( '<h3>Calendly / externe Terminbuchungsdienste</h3><p>' . esc_html__( 'Bei der Nutzung externer Terminbuchungsdienste wie Calendly werden Kontakt-, Termin-, Nutzungs- und technische Verbindungsdaten verarbeitet und an den jeweiligen Anbieter übermittelt.', 'frontend-rechtstexte-generator' ) . '</p>', $data );
	}

	public function get_jotform_module( array $data = array() ): string {
		// Juristische Pruefung empfohlen.
		return $this->replace( '<h3>Jotform / externe Formularanbieter</h3><p>' . esc_html__( 'Werden externe Formularanbieter wie Jotform eingesetzt, können die über Formulare übermittelten Angaben sowie technische Verbindungsdaten vom jeweiligen Anbieter verarbeitet werden. Dies betrifft insbesondere Kontaktanfragen, Anmeldungen, Bewerbungen oder sonstige über Formulare übermittelte Inhalte. Sofern vorhanden, sollte die Einbindung über {{consent_tools}} gesteuert werden.', 'frontend-rechtstexte-generator' ) . '</p>', $data );
	}

	public function get_trustpilot_module( array $data = array() ): string {
		// Juristische Pruefung empfohlen.
		return $this->replace( '<h3>Trustpilot / Bewertungsdienste</h3><p>' . esc_html__( 'Sofern Bewertungs- oder Rezensionsdienste wie Trustpilot eingebunden werden, können bei Aufruf entsprechender Inhalte oder bei Abgabe einer Bewertung personenbezogene Daten an den jeweiligen Anbieter übermittelt oder durch diesen verarbeitet werden. Dies kann insbesondere die Website {{website_url}} und die dort angebotenen Leistungen von {{company}} betreffen.', 'frontend-rechtstexte-generator' ) . '</p>', $data );
	}

	public function get_smtp_service_module( array $data = array() ): string {
		// Juristische Pruefung empfohlen.
		$provider_sentence = sprintf( esc_html__( 'Für den Versand wird %s eingesetzt.', 'frontend-rechtstexte-generator' ), esc_html( (string) ( $data['service_provider'] ?? '' ) ) );
		return $this->replace( '<h3>' . esc_html__( 'E-Mail-Versand / SMTP', 'frontend-rechtstexte-generator' ) . '</h3><p>' . $provider_sentence . ' ' . esc_html__( 'Dabei werden E-Mail-Adressen, Nachrichteninhalte, Versandzeitpunkte und technische Metadaten verarbeitet, soweit dies für Zustellung, Nachweisbarkeit und Sicherheit des E-Mail-Versands erforderlich ist.', 'frontend-rechtstexte-generator' ) . '</p>', $data );
	}
}

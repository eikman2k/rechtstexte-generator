<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class FRG_Generator {
	private FRG_Text_Modules $modules;

	public function __construct( FRG_Text_Modules $modules ) {
		$this->modules = $modules;
	}

	public function get_module_meta(): array {
		return $this->modules->get_module_meta();
	}

	public function get_registry_updated_at(): string {
		return $this->modules->get_registry_updated_at();
	}

	public function build_exportable_document_html( string $content ): string {
		$style = '<style>.frg-document{max-width:960px;color:#243447;font-size:18px}.frg-document--readability-compact{max-width:820px;text-wrap:pretty}.frg-document>*:first-child{margin-top:0}.frg-document h2,.frg-document h3,.frg-document h4{margin-top:0}.frg-document h2{margin-bottom:20px;font-size:clamp(2rem,3vw,2.45rem);line-height:1.18}.frg-document h3{margin-top:52px;margin-bottom:14px;font-size:clamp(1.65rem,2.2vw,2rem);line-height:1.24;letter-spacing:-.02em}.frg-document h4{margin-top:52px;margin-bottom:14px;font-size:clamp(1.3rem,1.8vw,1.55rem);line-height:1.3}.frg-document p,.frg-document ul,.frg-document ol,.frg-document .frg-required-facts{margin-top:0;margin-bottom:20px;line-height:1.8}.frg-document--readability-compact p,.frg-document--readability-compact ul,.frg-document--readability-compact ol{line-height:1.7}.frg-document ul,.frg-document ol{padding-left:22px}.frg-document li+li{margin-top:8px}.frg-document h3+p,.frg-document h4+p{margin-top:4px}.frg-document p strong{font-weight:700}.frg-document .frg-address-block{margin-top:0;margin-bottom:18px;line-height:1.35}.frg-document .frg-address-block strong{display:block;margin-bottom:4px}.frg-document .frg-address{display:inline-block}.frg-document .frg-address__line{display:block;line-height:1.35}.frg-document .frg-required-facts{padding:16px 18px;border:1px solid #dde5ee;border-radius:16px;background:#f8fbff}.frg-document .frg-required-facts__title{margin-bottom:10px}.frg-document .frg-required-facts p{margin-bottom:12px;line-height:1.55}.frg-document .frg-required-facts p:last-child{margin-bottom:0}</style>';

		return $style . $content;
	}

	public function get_block_registry(): array {
		return $this->modules->get_block_registry();
	}

	public function save_block_registry( array $raw ): void {
		$this->modules->save_block_registry( $raw );
	}

	public function render_registry_block( string $key, array $data = array() ): string {
		return $this->modules->render_block( $key, $data );
	}

	public function get_block_placeholders( string $key ): array {
		return $this->modules->get_block_placeholders( $key );
	}

	public function get_distributable_block_text( string $key ): string {
		return $this->modules->get_distributable_block_text( $key );
	}

	public function get_distributable_compact_block_text( string $key ): string {
		return $this->modules->get_distributable_compact_block_text( $key );
	}

	public function get_block_placeholder_details( string $key ): array {
		return $this->modules->get_block_placeholder_details( $key );
	}

	public function inspect_text_quality( string $content ): array {
		$visible = html_entity_decode( wp_strip_all_tags( str_replace( '><', '> <', $content ) ), ENT_QUOTES | ENT_HTML5, 'UTF-8' );
		$flags = array();
		$patterns = array(
			'/\bbitte\s+prüfen\b/iu' => __( 'Enthält eine interne Prüfanweisung („bitte prüfen“).', 'frontend-rechtstexte-generator' ),
			'/\bder\s+jeweils?\s+(?:eingesetzte\s+)?Anbieter\b/iu' => __( 'Enthält einen unbestimmten Anbieter.', 'frontend-rechtstexte-generator' ),
			'/\bkann\s+eingesetzt\s+werden\b/iu' => __( 'Beschreibt einen Dienst nur als mögliche statt als tatsächliche Verarbeitung.', 'frontend-rechtstexte-generator' ),
			'/\b(?:Durchfuehrung|Erfuellung|Vertragsdurchfuehrung|Vertragserfuellung|Fehlerpraevention|Massnahmen|Anschliessend|eingeschraenkt|Gruende|Resource)\b/u' => __( 'Enthält eine bekannte fehlerhafte Ersatzschreibweise oder einen nicht übersetzten Begriff.', 'frontend-rechtstexte-generator' ),
			'/\bsofern\b/iu' => __( 'Enthält „sofern“; bitte prüfen, ob stattdessen eine konkrete Tatsachenaussage möglich ist.', 'frontend-rechtstexte-generator' ),
			'/\bgegebenenfalls\b/iu' => __( 'Enthält „gegebenenfalls“; bitte prüfen, ob die konkrete Konfiguration bekannt ist.', 'frontend-rechtstexte-generator' ),
		);

		foreach ( $patterns as $pattern => $message ) {
			if ( preg_match( $pattern, $visible ) ) {
				$flags[] = $message;
			}
		}

		if ( preg_match( '/<h([2-4])\b[^>]*>\s*(?:Hinweis|Wichtiger Hinweis)\s*<\/h\1>\s*(?=<h[2-4]\b|$)/iu', $content ) ) {
			$flags[] = __( 'Enthält eine leere Hinweisüberschrift.', 'frontend-rechtstexte-generator' );
		}

		return array_values( array_unique( $flags ) );
	}

	public function generate_impressum( array $data ): string {
		$impressum_data = $this->get_impressum_template_data( $data );
		$parts   = array();
		$parts[] = $this->modules->render_block( 'impressum_base', $impressum_data );

		if ( ! empty( $data['has_trade_register'] ) || $this->requires_register_information( $this->get_effective_legal_form( $data ) ) ) {
			$parts[] = $this->modules->render_block( 'register', $impressum_data );
		}

		if ( ! empty( $data['has_vat_id'] ) ) {
			$parts[] = $this->modules->render_block( 'vat', $impressum_data );
		}

		if ( ! empty( $data['has_responsible_content'] ) ) {
			$parts[] = $this->modules->render_block( 'responsible_content', $impressum_data );
		}

		if ( ! empty( $data['has_professional_info'] ) ) {
			$parts[] = $this->modules->render_block( 'professional_information', $impressum_data );
		}

		if ( ! empty( $data['has_liability_insurance'] ) ) {
			$parts[] = $this->modules->render_block( 'liability_insurance', $impressum_data );
		}

		return '<div class="frg-document frg-document--impressum">' . $this->finalize_generated_content( implode( '', $parts ) ) . '</div>';
	}

	public function generate_privacy_policy( array $data ): string {
		$privacy_data = $this->get_privacy_template_data( $data );
		$parts   = array();
		$parts[] = $this->modules->render_block( 'privacy_intro', $privacy_data );
		$parts[] = $this->modules->render_block( 'controller', $privacy_data );
		if (
			! empty( $data['has_data_protection_officer'] ) &&
			(
				! empty( $data['data_protection_officer_name'] ) ||
				! empty( $data['data_protection_officer_email'] ) ||
				! empty( $data['data_protection_officer_phone'] ) ||
				! empty( $data['data_protection_officer_address'] )
			)
		) {
			$parts[] = $this->modules->render_block( 'data_protection_officer', $privacy_data );
		}
		$parts[] = $this->modules->render_block( 'general_processing', $privacy_data );
		$parts[] = $this->ensure_hosting_av_notice( $this->modules->render_block( 'hosting', $privacy_data ), $privacy_data );
		$parts[] = $this->modules->render_block( 'server_logs', $privacy_data );
		$parts[] = $this->modules->render_block( 'ssl_tls' );

		if ( ! empty( $data['features']['contact_form'] ) ) {
			$parts[] = $this->modules->render_block( 'contact_form', $privacy_data );
		}
		if ( ! empty( $data['features']['email_contact'] ) || ! empty( $data['features']['phone_contact'] ) ) {
			$parts[] = $this->modules->render_block( 'email_contact', $privacy_data );
		}
		if ( ! empty( $data['features']['comments'] ) ) {
			$parts[] = $this->modules->render_block( 'comments' );
		}
		if ( ! empty( $data['features']['user_registration'] ) || ! empty( $data['features']['login_area'] ) ) {
			$parts[] = $this->modules->render_block( 'registration' );
		}
		if ( ! empty( $data['features']['newsletter'] ) ) {
			$parts[] = $this->modules->render_block( 'newsletter' );
			if ( ! empty( $privacy_data['providers'] ) ) {
				$parts[] = $this->modules->render_block( 'newsletter_provider', $privacy_data );
			}
		}
		if ( ! empty( $data['features']['job_application_form'] ) ) {
			$parts[] = $this->modules->render_block( 'application' );
		}
		if ( ! empty( $data['features']['appointment_booking'] ) ) {
			$parts[] = $this->modules->render_block( 'booking' );
		}
		if ( ! empty( $data['features']['shop'] ) || ! empty( $data['features']['customer_account'] ) ) {
			$parts[] = $this->modules->render_block( 'shop', $privacy_data );
		}
		if ( ! empty( $data['features']['payment_provider'] ) ) {
			$parts[] = $this->modules->render_block( 'payment_provider', $privacy_data );
		}
		if ( ! empty( $data['features']['shipping_provider'] ) ) {
			$parts[] = $this->modules->render_block( 'shipping_provider', $privacy_data );
		}
		if ( ! empty( $data['features']['download_area'] ) ) {
			$parts[] = $this->modules->render_block( 'download_area' );
		}
		if ( ! empty( $data['features']['members_area'] ) ) {
			$parts[] = $this->modules->render_block( 'members_area' );
		}
		if ( ! empty( $data['features']['training_portal'] ) ) {
			$parts[] = $this->modules->render_block( 'training_portal', $privacy_data );
		}
		if ( ! empty( $data['services']['openai'] ) || ! empty( $data['services']['anthropic'] ) ) {
			$parts[] = $this->modules->render_block( 'ai_chatbot', $privacy_data );
		}

		$service_map = array(
			'google_fonts_external' => 'get_google_fonts_external_module',
			'google_fonts_local'    => 'get_google_fonts_local_module',
			'google_maps'           => 'get_google_maps_module',
			'youtube'               => 'get_youtube_module',
			'vimeo'                 => 'get_vimeo_module',
			'google_analytics'      => 'get_google_analytics_module',
			'google_tag_manager'    => 'get_google_tag_manager_module',
			'google_ads_conversion_tracking' => 'get_google_ads_conversion_tracking_module',
			'meta_pixel'            => 'get_meta_pixel_module',
			'matomo'                => 'get_matomo_module',
			'microsoft_clarity'     => 'get_microsoft_clarity_module',
			'cloudflare'            => 'get_cloudflare_module',
			'recaptcha'             => 'get_recaptcha_module',
			'hcaptcha'              => 'get_hcaptcha_module',
			'cloudflare_turnstile'  => 'get_cloudflare_turnstile_module',
			'calendly'              => 'get_calendly_module',
			'jotform'               => 'get_jotform_module',
			'trustpilot'            => 'get_trustpilot_module',
			'smtp_service'          => 'get_smtp_service_module',
		);

		foreach ( $service_map as $key => $method ) {
			if ( 'google_fonts_external' === $key && ! empty( $data['services']['google_fonts_local'] ) ) {
				continue;
			}
			if ( 'smtp_service' === $key && empty( $data['service_details']['smtp_service']['provider'] ) ) {
				continue;
			}
			if (
				'vimeo' === $key &&
				(
					empty( $data['service_details']['vimeo']['provider'] ) ||
					'Vor Einwilligung blockiert' !== ( $data['service_details']['vimeo']['consent'] ?? '' )
				)
			) {
				continue;
			}
			if ( ! empty( $data['services'][ $key ] ) ) {
				$parts[] = $this->modules->render_block( $key, $privacy_data );
			}
		}

		if ( ! empty( $data['services']['borlabs_cookie'] ) || ! empty( $data['services']['real_cookie_banner'] ) || ! empty( $data['services']['complianz'] ) || ! empty( $data['services']['cookieyes'] ) ) {
			$parts[] = $this->modules->render_block( 'cookie_consent', $privacy_data );
		}
		if ( ! empty( $data['services']['wordfence'] ) || ! empty( $data['services']['ithemes_security'] ) ) {
			$parts[] = $this->modules->render_block( 'security_plugins', $privacy_data );
		}
		if ( ! empty( $data['services']['updraftplus'] ) || ! empty( $data['services']['wpvivid'] ) ) {
			$parts[] = $this->modules->render_block( 'backup_plugins', $privacy_data );
		}
		if ( ! empty( $privacy_data['profiles'] ) ) {
			$parts[] = $this->modules->render_block( 'social_media_profiles', $privacy_data );
		}

		$parts[] = $this->modules->render_block( 'storage_duration', $privacy_data );
		if ( $this->should_render_third_country_section( $data ) ) {
			$parts[] = $this->modules->render_block( 'third_country_transfer', $privacy_data );
		}
		$parts[] = $this->modules->render_block( 'data_subject_rights' );
		$parts[] = $this->modules->render_block( 'complaint_authority', $privacy_data );

		$content = implode( '', $parts );
		if ( empty( $data['has_third_country_transfer'] ) ) {
			$content = $this->strip_disabled_third_country_content( $content );
		}
		$content = $this->finalize_generated_content( $content );

		$readability_class = 'compact' === $this->get_privacy_readability_mode() ? ' frg-document--readability-compact' : '';

		return '<div class="frg-document frg-document--privacy' . $readability_class . '">' . $this->normalize_document_section_headings( $content ) . '</div>';
	}

	public function get_impressum_template_data( array $data ): array {
		$legal_form = $this->get_effective_legal_form( $data );
		return array(
			'document_notice'     => $this->get_document_notice( 'impressum' ),
			'company'            => esc_html( $data['company_name'] ?? '' ),
			'legal_form'         => esc_html( $legal_form ),
			'representative'     => esc_html( trim( ( $data['first_name'] ?? '' ) . ' ' . ( $data['last_name'] ?? '' ) ) ),
			'representative_label'=> esc_html( $this->get_representative_label( $legal_form ) ),
			'representative_line' => $this->format_representative_line( $legal_form, trim( ( $data['first_name'] ?? '' ) . ' ' . ( $data['last_name'] ?? '' ) ) ),
			'street'             => esc_html( $data['street'] ?? '' ),
			'zip'                => esc_html( $data['zip'] ?? '' ),
			'city'               => esc_html( $data['city'] ?? '' ),
			'country'            => esc_html( $data['country'] ?? '' ),
			'postal_address'     => $this->format_address_lines(
				array(
					(string) ( $data['street'] ?? '' ),
					trim( (string) ( $data['zip'] ?? '' ) . ' ' . (string) ( $data['city'] ?? '' ) ),
					(string) ( $data['country'] ?? '' ),
				)
			),
			'email'              => esc_html( $data['email'] ?? '' ),
			'phone_line'         => ! empty( $data['phone'] ) ? esc_html__( 'Telefon', 'frontend-rechtstexte-generator' ) . ': ' . esc_html( $data['phone'] ) . '<br>' : '',
			'website_line'       => ! empty( $data['website_url'] ) ? esc_html__( 'Website', 'frontend-rechtstexte-generator' ) . ': ' . esc_html( $data['website_url'] ) : '',
			'court'              => esc_html( $data['register_court'] ?? '' ),
			'number'             => esc_html( $data['register_number'] ?? '' ),
			'vat_id'             => esc_html( $data['vat_id'] ?? '' ),
			'business_id_line'   => ! empty( $data['business_id'] ) ? '<br>' . esc_html__( 'Wirtschafts-ID', 'frontend-rechtstexte-generator' ) . ': ' . esc_html( $data['business_id'] ) : '',
			'name'               => esc_html( $data['responsible_name'] ?? '' ),
			'address'            => $this->format_multiline_address( (string) ( $data['responsible_address'] ?? '' ) ),
			'chamber'            => esc_html( $data['professional_chamber'] ?? '' ),
			'title'              => esc_html( $data['professional_title'] ?? '' ),
			'awarded_in'         => esc_html( $data['professional_awarded_in'] ?? '' ),
			'rules'              => esc_html( $data['professional_rules'] ?? '' ),
			'authority'          => esc_html( $data['supervisory_authority'] ?? '' ),
			'insurer'            => esc_html( $data['liability_insurer'] ?? '' ),
			'insurer_address'    => $this->format_multiline_address( (string) ( $data['liability_insurer_address'] ?? '' ) ),
			'scope'              => esc_html( $data['liability_scope'] ?? '' ),
		);
	}

	public function get_privacy_template_data( array $data ): array {
		$features = $data['features'] ?? array();
		$services = $data['services'] ?? array();
		$legal_form = $this->get_effective_legal_form( $data );
		$controller_same = ! array_key_exists( 'controller_same_as_operator', $data ) || ! empty( $data['controller_same_as_operator'] );
		$controller_name = $controller_same ? (string) ( $data['company_name'] ?? '' ) : (string) ( $data['controller_name'] ?? '' );
		$controller_representative = $controller_same
			? trim( (string) ( $data['first_name'] ?? '' ) . ' ' . (string) ( $data['last_name'] ?? '' ) )
			: (string) ( $data['controller_representative'] ?? '' );
		$controller_street = $controller_same ? (string) ( $data['street'] ?? '' ) : (string) ( $data['controller_street'] ?? '' );
		$controller_zip = $controller_same ? (string) ( $data['zip'] ?? '' ) : (string) ( $data['controller_zip'] ?? '' );
		$controller_city = $controller_same ? (string) ( $data['city'] ?? '' ) : (string) ( $data['controller_city'] ?? '' );
		$controller_country = $controller_same ? (string) ( $data['country'] ?? '' ) : (string) ( $data['controller_country'] ?? '' );
		$controller_email = $controller_same ? (string) ( $data['email'] ?? '' ) : (string) ( $data['controller_email'] ?? '' );
		$controller_phone = $controller_same ? (string) ( $data['phone'] ?? '' ) : (string) ( $data['controller_phone'] ?? '' );

		$newsletter_providers = $this->collect_labels(
			$services,
			array(
				'mailchimp'   => 'Mailchimp',
				'brevo'       => 'Brevo',
				'sendinblue'  => 'Sendinblue',
				'cleverreach' => 'CleverReach',
			)
		);
		$social_profiles = $this->collect_labels(
			$services,
			array(
				'facebook'  => 'Facebook',
				'instagram' => 'Instagram',
				'linkedin'  => 'LinkedIn',
				'xing'      => 'Xing',
				'tiktok'    => 'TikTok',
			)
		);
		$consent_tools = $this->collect_labels(
			$services,
			array(
				'borlabs_cookie'     => 'Borlabs Cookie',
				'real_cookie_banner' => 'Real Cookie Banner',
				'complianz'          => 'Complianz',
				'cookieyes'          => 'CookieYes',
			)
		);
		$security_tools = $this->collect_labels(
			$services,
			array(
				'wordfence'         => 'Wordfence',
				'ithemes_security'  => 'iThemes Security / Solid Security',
			)
		);
		$backup_tools = $this->collect_labels(
			$services,
			array(
				'updraftplus' => 'UpdraftPlus',
				'wpvivid'     => 'WPvivid',
			)
		);
		$form_tools = $this->collect_labels(
			$services,
			array(
				'elementor'      => 'Elementor',
				'contact_form_7' => 'Contact Form 7',
				'gravity_forms'  => 'Gravity Forms',
				'wpforms'        => 'WPForms',
			)
		);
		$feature_labels = $this->collect_labels(
			$features,
			array(
				'contact_form'       => 'Kontaktformular',
				'email_contact'      => 'E-Mail-Kontakt',
				'phone_contact'      => 'Telefonkontakt',
				'comments'           => 'Kommentare',
				'user_registration'  => 'Benutzerregistrierung',
				'login_area'         => 'Login-Bereich',
				'newsletter'         => 'Newsletter',
				'job_application_form' => 'Bewerbungsformular',
				'appointment_booking' => 'Terminbuchung',
				'shop'               => 'Online-Shop',
				'payment_provider'   => 'Zahlungsanbieter',
				'shipping_provider'  => 'Versanddienstleister',
				'customer_account'   => 'Kundenkonto',
				'download_area'      => 'Downloadbereich',
				'members_area'       => 'Mitgliederbereich',
				'training_portal'    => 'Schulungsportal / Lernplattform',
				'training_progress'  => 'Lernfortschritt',
				'training_tests'     => 'Tests / Prüfungen',
				'training_certificates' => 'Zertifikate / Teilnahmebescheinigungen',
				'employee_training'  => 'Mitarbeiterschulungen / Pflichtschulungen',
				'scorm_tracking'     => 'SCORM / Lernpaket-Tracking',
				'certificate_download' => 'Zertifikats-Download',
				'mandatory_training_proof' => 'Pflichtunterweisungs-Nachweise',
				'trainer_manager_access' => 'Dozenten- / Manager- / Admin-Zugriffe',
				'tenant_access'      => 'Mandanten- / Firmenzugriffe',
				'social_media_profiles' => 'Social-Media-Profile',
			)
		);
		$service_labels = $this->collect_labels(
			$services,
			array(
				'google_fonts_external' => 'Google Fonts extern',
				'google_maps'           => 'Google Maps',
				'youtube'               => 'YouTube',
				'vimeo'                 => 'Vimeo',
				'google_analytics'      => 'Google Analytics',
				'google_tag_manager'    => 'Google Tag Manager',
				'google_ads_conversion_tracking' => 'Google Ads Conversion Tracking',
				'meta_pixel'            => 'Meta Pixel',
				'matomo'                => 'Matomo',
				'microsoft_clarity'     => 'Microsoft Clarity',
				'cloudflare'            => 'Cloudflare',
				'recaptcha'             => 'reCAPTCHA',
				'hcaptcha'              => 'hCaptcha',
				'cloudflare_turnstile'  => 'Cloudflare Turnstile',
				'calendly'              => 'Calendly',
				'jotform'               => 'Jotform',
				'trustpilot'            => 'Trustpilot',
				'smtp_service'          => 'SMTP / E-Mail-Versanddienst',
				'ai_chatbot'            => 'Website-KI-Bot / KI-Assistent',
				'openai'                => 'OpenAI',
				'anthropic'             => 'Anthropic',
			)
		);
		$ai_providers = $this->collect_labels(
			$services,
			array(
				'openai'    => 'OpenAI',
				'anthropic' => 'Anthropic',
			)
		);

		return array(
			'readability_mode'             => $this->get_privacy_readability_mode(),
			'document_notice'             => '',
			'company'                    => esc_html( $controller_name ),
			'representative'             => esc_html( $controller_representative ),
			'representative_label'       => esc_html( $controller_same ? $this->get_representative_label( $legal_form ) : __( 'Vertreten durch', 'frontend-rechtstexte-generator' ) ),
			'representative_line'        => $controller_same
				? $this->format_representative_line( $legal_form, $controller_representative )
				: ( '' !== trim( $controller_representative ) ? esc_html__( 'Vertreten durch', 'frontend-rechtstexte-generator' ) . ': ' . esc_html( $controller_representative ) : '' ),
			'street'                     => esc_html( $controller_street ),
			'zip'                        => esc_html( $controller_zip ),
			'city'                       => esc_html( $controller_city ),
			'country'                    => esc_html( $controller_country ),
			'controller_address'         => $this->format_address_lines(
				array(
					$controller_street,
					trim( $controller_zip . ' ' . $controller_city ),
					$controller_country,
				)
			),
			'email'                      => esc_html( $controller_email ),
			'controller_phone'           => esc_html( $controller_phone ),
			'controller_phone_line'      => '' !== trim( $controller_phone ) ? '<br>' . esc_html__( 'Telefon', 'frontend-rechtstexte-generator' ) . ': ' . esc_html( $controller_phone ) : '',
			'website_url'                => esc_html( $data['website_url'] ?? '' ),
			'name'                       => esc_html( $data['data_protection_officer_name'] ?? '' ),
			'dpo_email'                  => esc_html( $data['data_protection_officer_email'] ?? '' ),
			'dpo_phone'                  => esc_html( $data['data_protection_officer_phone'] ?? '' ),
			'dpo_address'                => $this->format_multiline_address( (string) ( $data['data_protection_officer_address'] ?? '' ) ),
			'purposes'                   => esc_html( $data['privacy_processing_purposes'] ?? __( 'Bereitstellung der Website, Kommunikation, Vertragsdurchführung und Sicherheit', 'frontend-rechtstexte-generator' ) ),
			'legal_basis'                => esc_html( $data['privacy_legal_basis'] ?? __( 'Art. 6 Abs. 1 DSGVO nach konkreter Verarbeitung', 'frontend-rechtstexte-generator' ) ),
			'recipients'                 => esc_html( $data['privacy_recipient_categories'] ?? __( 'Hosting, IT-Dienstleister, eingesetzte Fachanbieter', 'frontend-rechtstexte-generator' ) ),
			'storage'                    => esc_html( $data['privacy_storage_general'] ?? __( 'Speicherung nur so lange, wie dies für den jeweiligen Zweck oder gesetzliche Pflichten erforderlich ist', 'frontend-rechtstexte-generator' ) ),
			'third_country'              => esc_html( $data['privacy_third_country_transfer'] ?? __( 'Ein Drittlandtransfer erfolgt nur, wenn dies bei einzelnen Diensten angegeben ist oder technisch erforderlich wird', 'frontend-rechtstexte-generator' ) ),
			'host'                       => esc_html( $data['hosting_provider'] ?? '' ),
			'host_address'               => $this->format_multiline_address( (string) ( $data['hosting_provider_address'] ?? '' ) ),
			'location'                   => esc_html( $data['server_location'] ?? '' ),
			'av'                         => esc_html( $data['hosting_av_contract'] ?? '' ),
			'av_sentence'                => $this->get_hosting_av_sentence( (string) ( $data['hosting_av_contract'] ?? '' ), __( 'Hosting-Anbieter', 'frontend-rechtstexte-generator' ) ),
			'server_infrastructure_provider' => esc_html( $data['server_infrastructure_provider'] ?? '' ),
			'server_infrastructure_type'     => esc_html( $data['server_infrastructure_type'] ?? '' ),
			'server_infrastructure_address'  => $this->format_multiline_address( (string) ( $data['server_infrastructure_address'] ?? '' ) ),
			'providers'                  => esc_html( implode( ', ', $newsletter_providers ) ),
			'newsletter_providers'       => esc_html( implode( ', ', $newsletter_providers ) ),
			'profiles'                   => esc_html( implode( ', ', $social_profiles ) ),
			'social_profiles'            => esc_html( implode( ', ', $social_profiles ) ),
			'social_media_integration'   => sanitize_key( (string) ( $data['social_media_integration'] ?? 'links' ) ),
			'consent_tools'              => esc_html( implode( ', ', $consent_tools ) ),
			'security_tools'             => esc_html( implode( ', ', $security_tools ) ),
			'backup_tools'               => esc_html( implode( ', ', $backup_tools ) ),
			'form_tools'                 => esc_html( implode( ', ', $form_tools ) ),
			'backup_destination'         => esc_html( $data['backup_destination'] ?? '' ),
			'backup_storage_provider'    => esc_html( $data['backup_storage_provider'] ?? '' ),
			'backup_storage_address'     => $this->format_multiline_address( (string) ( $data['backup_storage_address'] ?? '' ) ),
			'backup_retention'           => esc_html( $data['backup_retention'] ?? '' ),
			'privacy_supervisory_authority_name'    => esc_html( $data['privacy_supervisory_authority_name'] ?? '' ),
			'privacy_supervisory_authority_address' => $this->format_multiline_address( (string) ( $data['privacy_supervisory_authority_address'] ?? '' ) ),
			'privacy_supervisory_authority_url'     => esc_url( $data['privacy_supervisory_authority_url'] ?? '' ),
			'features'                   => $features,
			'training_modules'           => esc_html( implode( ', ', $this->collect_labels(
				$features,
				array(
					'training_progress'     => 'Lernfortschritt',
					'training_tests'        => 'Tests, Quiz und Prüfungsergebnisse',
					'training_certificates' => 'Zertifikate und Teilnahmebescheinigungen',
					'employee_training'     => 'Mitarbeiterschulungen und Pflichtschulungen',
					'scorm_tracking'        => 'SCORM-Tracking und Lernpaketstände',
					'certificate_download'  => 'Zertifikats-Downloads',
					'mandatory_training_proof' => 'Nachweise absolvierte Pflichtunterweisungen',
					'trainer_manager_access' => 'Dozenten-, Manager- und Admin-Zugriffe',
					'tenant_access'         => 'Mandanten- oder Firmenzugriffe',
				)
			) ) ),
			'active_features'            => esc_html( implode( ', ', $feature_labels ) ),
			'active_services'            => esc_html( implode( ', ', $service_labels ) ),
			'ai_providers'               => esc_html( implode( ', ', $ai_providers ) ),
			'ai_chatbot_privacy_url'     => esc_url( $data['ai_chatbot_privacy_url'] ?? '' ),
			'ai_chatbot_privacy_link'    => ! empty( $data['ai_chatbot_privacy_url'] ) ? '<a href="' . esc_url( $data['ai_chatbot_privacy_url'] ) . '" rel="nofollow noopener">' . esc_html__( 'Datenschutzhinweise des KI-Bot-Plugins', 'frontend-rechtstexte-generator' ) . '</a>' : '',
			'ai_transparency_sentence'   => ! empty( $services['ai_transparency_notice'] ) ? esc_html__( 'Der Assistent weist Nutzer vor oder bei Beginn der Interaktion klar darauf hin, dass sie mit einem KI-System kommunizieren.', 'frontend-rechtstexte-generator' ) : '',
			'service_details'            => $this->prepare_service_details( $data['service_details'] ?? array() ),
		);
	}

	private function get_privacy_readability_mode(): string {
		$settings = get_option( 'frg_settings', array() );
		$mode = is_array( $settings ) ? sanitize_key( (string) ( $settings['privacy_readability_mode'] ?? 'detailed' ) ) : 'detailed';

		return 'compact' === $mode ? 'compact' : 'detailed';
	}

	private function prepare_service_details( $raw_details ): array {
		if ( ! is_array( $raw_details ) ) {
			return array();
		}

		$prepared = array();
		foreach ( $raw_details as $service_key => $details ) {
			if ( ! is_array( $details ) ) {
				continue;
			}

			$privacy_url = esc_url( $details['privacy_url'] ?? '' );
			$prepared[ sanitize_key( (string) $service_key ) ] = array(
				'provider'       => esc_html( $details['provider'] ?? '' ),
				'address'        => $this->format_multiline_address( (string) ( $details['address'] ?? '' ) ),
				'privacy_url'    => $privacy_url,
				'privacy_link'   => '' !== $privacy_url ? '<a href="' . $privacy_url . '" rel="nofollow noopener" target="_blank">' . esc_html__( 'Datenschutzhinweise des Anbieters', 'frontend-rechtstexte-generator' ) . '</a>' : '',
				'purpose'        => esc_html( $details['purpose'] ?? '' ),
				'data_categories'=> esc_html( $details['data_categories'] ?? '' ),
				'legal_basis'    => esc_html( $details['legal_basis'] ?? '' ),
				'recipients'     => esc_html( $details['recipients'] ?? '' ),
				'retention'      => esc_html( $details['retention'] ?? '' ),
				'third_country'  => esc_html( $details['third_country'] ?? '' ),
				'transfer_basis' => esc_html( $details['transfer_basis'] ?? '' ),
				'av_contract'    => esc_html( $details['av_contract'] ?? '' ),
				'consent'        => esc_html( $details['consent'] ?? '' ),
			);
		}

		return $prepared;
	}

	private function get_hosting_av_sentence( string $value, string $provider_label = '' ): string {
		$value = trim( $value );
		$provider_label = '' !== trim( $provider_label ) ? trim( $provider_label ) : __( 'Hosting-Anbieter', 'frontend-rechtstexte-generator' );

		if ( 'Ja' === $value ) {
			return sprintf(
				/* translators: %s: provider label */
				esc_html__( 'Mit dem %s besteht ein Vertrag zur Auftragsverarbeitung gemäß Art. 28 DSGVO.', 'frontend-rechtstexte-generator' ),
				esc_html( $provider_label )
			);
		}

		if ( 'Nein' === $value ) {
			return sprintf(
				/* translators: %s: provider label */
				esc_html__( 'Mit dem %s besteht derzeit kein Vertrag zur Auftragsverarbeitung. Dieser Punkt sollte datenschutzrechtlich besonders geprüft werden.', 'frontend-rechtstexte-generator' ),
				esc_html( $provider_label )
			);
		}

		if ( 'Unbekannt' === $value ) {
			return sprintf(
				/* translators: %s: provider label */
				esc_html__( 'Ob mit dem %s ein Vertrag zur Auftragsverarbeitung gemäß Art. 28 DSGVO besteht, sollte geprüft werden.', 'frontend-rechtstexte-generator' ),
				esc_html( $provider_label )
			);
		}

		return '';
	}

	private function get_document_notice( string $document_type ): string {
		$settings = get_option( 'frg_settings', array() );
		$key      = 'impressum' === $document_type ? 'show_generator_notice_impressum' : 'show_generator_notice_privacy';

		if ( empty( $settings[ $key ] ) ) {
			return '';
		}

		$document_label = 'impressum' === $document_type
			? __( 'ein Impressum', 'frontend-rechtstexte-generator' )
			: __( 'eine Datenschutzerklärung', 'frontend-rechtstexte-generator' );

		return '<p class="frg-document-notice">' . esc_html(
			sprintf(
				/* translators: %s: document type, e.g. an imprint or a privacy policy */
				__( 'Die nachfolgenden Inhalte wurden auf Basis der im Generator hinterlegten Angaben modular zusammengestellt und sollen eine strukturierte Ausgangsbasis für %s bieten. Sie ersetzen keine rechtliche Einzelfallprüfung.', 'frontend-rechtstexte-generator' ),
				$document_label
			)
		) . '</p>';
	}

	private function apply_document_notice_setting( string $content, string $document_type ): string {
		$legacy_notice = __( 'Die nachfolgenden Inhalte wurden auf Basis der im Generator hinterlegten Angaben modular zusammengestellt und sollen eine strukturierte Ausgangsbasis für eine Datenschutzerklärung bieten. Sie ersetzen keine rechtliche Einzelfallprüfung.', 'frontend-rechtstexte-generator' );
		$content       = str_replace( array( $legacy_notice, esc_html( $legacy_notice ) ), '', $content );
		$content       = (string) preg_replace( '/<p(?:\s[^>]*)?>\s*<\/p>/i', '', $content );
		$notice        = $this->get_document_notice( $document_type );

		if ( '' === $notice || false !== strpos( $content, 'frg-document-notice' ) ) {
			return $content;
		}

		$with_notice = preg_replace( '/<\/h2>/i', '$0' . $notice, $content, 1, $count );

		return ! empty( $count ) && is_string( $with_notice ) ? $with_notice : $notice . $content;
	}

	private function get_representative_label( string $legal_form ): string {
		$legal_form = trim( $legal_form );

		if ( in_array( $legal_form, array( 'GmbH', 'UG' ), true ) ) {
			return __( 'Vertreten durch die Geschäftsführung', 'frontend-rechtstexte-generator' );
		}

		if ( in_array( $legal_form, array( 'e.K.', 'Einzelunternehmen', 'Freiberufler' ), true ) ) {
			return __( 'Inhaber', 'frontend-rechtstexte-generator' );
		}

		if ( in_array( $legal_form, array( 'Verein', 'AG', 'eG', 'Stiftung' ), true ) ) {
			return __( 'Vertreten durch den Vorstand', 'frontend-rechtstexte-generator' );
		}

		if ( 'GbR' === $legal_form ) {
			return __( 'Vertretungsberechtigte Gesellschafter', 'frontend-rechtstexte-generator' );
		}

		if ( in_array( $legal_form, array( 'OHG', 'KG', 'GmbH & Co. KG', 'PartG' ), true ) ) {
			return __( 'Vertreten durch die vertretungsberechtigten Gesellschafter', 'frontend-rechtstexte-generator' );
		}

		return __( 'Vertreten durch', 'frontend-rechtstexte-generator' );
	}

	private function format_representative_line( string $legal_form, string $representative ): string {
		$representative = trim( $representative );
		if ( '' === $representative ) {
			return '';
		}

		return esc_html( $this->get_representative_label( $legal_form ) ) . ': ' . esc_html( $representative );
	}

	private function requires_register_information( string $legal_form ): bool {
		return in_array( trim( $legal_form ), array( 'GmbH', 'UG', 'e.K.', 'OHG', 'KG', 'GmbH & Co. KG', 'AG', 'eG', 'PartG' ), true );
	}

	private function get_effective_legal_form( array $data ): string {
		$legal_form = trim( (string) ( $data['legal_form'] ?? '' ) );
		if ( 'sonstige' === $legal_form && '' !== trim( (string) ( $data['legal_form_other'] ?? '' ) ) ) {
			return trim( (string) $data['legal_form_other'] );
		}

		return $legal_form;
	}

	private function collect_labels( array $source, array $map ): array {
		$labels = array();

		foreach ( $map as $key => $label ) {
			if ( ! empty( $source[ $key ] ) ) {
				$labels[] = $label;
			}
		}

		return $labels;
	}

	private function format_multiline_address( string $value ): string {
		$lines = preg_split( '/\r\n|\r|\n/', $value );
		if ( ! is_array( $lines ) ) {
			return '';
		}

		return $this->format_address_lines( $lines );
	}

	private function format_address_lines( array $lines ): string {
		$formatted_lines = array();

		foreach ( $lines as $line ) {
			$line = trim( wp_strip_all_tags( (string) $line ) );
			if ( '' === $line ) {
				continue;
			}

			$formatted_lines[] = '<span class="frg-address__line">' . esc_html( $line ) . '</span>';
		}

		if ( empty( $formatted_lines ) ) {
			return '';
		}

		return '<span class="frg-address">' . implode( '', $formatted_lines ) . '</span>';
	}

	private function normalize_document_section_headings( string $html ): string {
		$html = (string) preg_replace_callback(
			'/<p>\s*([^<]{1,90})\s*(?:<br\s*\/?>|\R)+\s*(.+?)<\/p>/uis',
			function ( array $matches ): string {
				$text = trim( wp_strip_all_tags( $matches[1] ) );
				if ( '' === $text || preg_match( '/[.!?:;]$/u', $text ) ) {
					return $matches[0];
				}

				$words = preg_split( '/\s+/u', $text );
				$word_count = is_array( $words ) ? count( array_filter( $words ) ) : 0;
				if ( $word_count < 1 || $word_count > 6 ) {
					return $matches[0];
				}

				$body = trim( $matches[2] );
				if ( '' === wp_strip_all_tags( $body ) ) {
					return $matches[0];
				}

				return '<h3>' . esc_html( $text ) . '</h3><p>' . $body . '</p>';
			},
			$html
		);

		return (string) preg_replace_callback(
			'/<p>\s*([^<]{1,90})\s*<\/p>\s*(?=<p>)/u',
			function ( array $matches ): string {
				$text = trim( wp_strip_all_tags( $matches[1] ) );
				if ( '' === $text ) {
					return $matches[0];
				}

				if ( preg_match( '/[.!?:;]$/u', $text ) ) {
					return $matches[0];
				}

				$words = preg_split( '/\s+/u', $text );
				$word_count = is_array( $words ) ? count( array_filter( $words ) ) : 0;

				if ( $word_count >= 1 && $word_count <= 6 ) {
					return '<h3>' . esc_html( $text ) . '</h3>';
				}

				return $matches[0];
			},
			$html
		);
	}

	private function strip_editorial_guidance( string $html ): string {
		$patterns = array(
			'/<p\b[^>]*class=(?:"[^"]*frg-document-notice[^"]*"|\'[^\']*frg-document-notice[^\']*\')[^>]*>.*?<\/p>/isu',
			'/<p\b[^>]*>(?:(?!<\/p>).)*Bitte\s+prüfen\s+Sie(?:(?!<\/p>).)*<\/p>/isu',
			'/<p\b[^>]*>(?:(?!<\/p>).)*Dieser\s+Punkt\s+sollte(?:(?!<\/p>).)*geprüft(?:(?!<\/p>).)*<\/p>/isu',
			'/<p\b[^>]*>(?:(?!<\/p>).)*Ob\s+mit\s+dem\s+Hosting-Anbieter(?:(?!<\/p>).)*sollte\s+geprüft\s+werden(?:(?!<\/p>).)*<\/p>/isu',
			'/<p\b[^>]*>(?:(?!<\/p>).)*\bzu\s+prüfen\b(?:(?!<\/p>).)*<\/p>/isu',
			'/<p\b[^>]*>(?:(?!<\/p>).)*(?:keine\s+Rechtsberatung|keine\s+rechtliche\s+Einzelfallprüfung|ersetzt\s+keine\s+(?:anwaltliche|rechtliche)\s+Prüfung)(?:(?!<\/p>).)*<\/p>/isu',
			'/<p\b[^>]*>(?:(?!<\/p>).)*Übermittlung\s+Ihrer\s+Daten(?:(?!<\/p>).)*grundsätzlich\s+nicht\s+statt(?:(?!<\/p>).)*<\/p>/isu',
		);

		return (string) preg_replace( $patterns, '', $html );
	}

	private function finalize_generated_content( string $html ): string {
		$html = $this->strip_editorial_guidance( $html );
		$html = (string) preg_replace( '/<(p|div|section|li)\b[^>]*>(?:(?!<\/\1>).)*\{\{[^}]+\}\}(?:(?!<\/\1>).)*<\/\1>/isu', '', $html );
		$html = (string) preg_replace( '/\{\{[^}]+\}\}/u', '', $html );
		$html = (string) preg_replace( '/<(p|div|section)\b[^>]*>\s*(?:<br\s*\/?>|&nbsp;|\s)*<\/\1>/iu', '', $html );
		$html = (string) preg_replace_callback(
			'/<(h[1-6]|p)\b[^>]*>.*?<\/\1>/isu',
			static function ( array $matches ): string {
				$label = trim( html_entity_decode( wp_strip_all_tags( $matches[0] ), ENT_QUOTES | ENT_HTML5, 'UTF-8' ) );
				return preg_match( '/^(?:Hinweis|Wichtiger Hinweis):?$/iu', $label ) ? '' : $matches[0];
			},
			$html
		);

		return $html;
	}

	private function should_render_third_country_section( array $data ): bool {
		return ! empty( $data['has_third_country_transfer'] ) && '' !== trim( (string) ( $data['privacy_third_country_transfer'] ?? '' ) );
	}

	private function strip_disabled_third_country_content( string $html ): string {
		$patterns = array(
			'/<h([2-4])\b[^>]*>\s*(?:Drittlandtransfer|Hinweise\s+zu\s+Drittlandtransfers?)\s*<\/h\1>(?:(?!<h[2-4]\b).)*/isu',
			'/<p\b[^>]*>(?:(?!<\/p>).)*(?:Es\s+kann\s+nicht\s+ausgeschlossen\s+werden(?:(?!<\/p>).)*Drittländern|Übermittlung(?:(?!<\/p>).)*(?:Staaten\s+außerhalb\s+der\s+EU|Drittländer)(?:(?!<\/p>).)*(?:Art\.\s*44|Standardvertragsklauseln)|Daten(?:(?!<\/p>).)*auch\s+in\s+Drittländern\s+verarbeitet)(?:(?!<\/p>).)*<\/p>/isu',
			'/<p\b[^>]*>\s*<strong>\s*(?:Drittlandbezug|Garantie\s+für\s+Drittlandtransfer)\s*:\s*<\/strong>.*?<\/p>/isu',
		);

		return (string) preg_replace( $patterns, '', $html );
	}

	private function ensure_hosting_av_notice( string $html, array $privacy_data ): string {
		$normalized = wp_strip_all_tags( $html );
		if ( '' === trim( $normalized ) ) {
			return $html;
		}

		if ( false !== stripos( $normalized, 'Auftragsverarbeitung' ) || false !== stripos( $normalized, 'Art. 28' ) ) {
			return $html;
		}

		$av_sentence = trim( (string) ( $privacy_data['av_sentence'] ?? '' ) );
		if ( '' === $av_sentence ) {
			return $html;
		}

		return $html . '<p><strong>' . esc_html__( 'Auftragsverarbeitung', 'frontend-rechtstexte-generator' ) . ':</strong> ' . esc_html( $av_sentence ) . '</p>';
	}
}

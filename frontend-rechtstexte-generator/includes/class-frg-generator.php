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
		$style = '<style>.frg-document{max-width:960px;color:#243447;font-size:18px}.frg-document>*:first-child{margin-top:0}.frg-document h2,.frg-document h3,.frg-document h4{margin-top:0}.frg-document h2{margin-bottom:20px;font-size:clamp(2rem,3vw,2.45rem);line-height:1.18}.frg-document h3{margin-top:52px;margin-bottom:14px;font-size:clamp(1.65rem,2.2vw,2rem);line-height:1.24;letter-spacing:-.02em}.frg-document h4{margin-top:52px;margin-bottom:14px;font-size:clamp(1.3rem,1.8vw,1.55rem);line-height:1.3}.frg-document p,.frg-document ul,.frg-document ol{margin-top:0;margin-bottom:20px;line-height:1.8}.frg-document ul,.frg-document ol{padding-left:22px}.frg-document li+li{margin-top:8px}.frg-document h3+p,.frg-document h4+p{margin-top:4px}.frg-document p strong{font-weight:700}</style>';

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

	public function get_block_placeholder_details( string $key ): array {
		return $this->modules->get_block_placeholder_details( $key );
	}

	public function generate_impressum( array $data ): string {
		$impressum_data = $this->get_impressum_template_data( $data );
		$parts   = array();
		$parts[] = $this->modules->render_block( 'impressum_base', $impressum_data );

		if ( ! empty( $data['has_trade_register'] ) ) {
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

		return '<div class="frg-document frg-document--impressum">' . implode( '', $parts ) . '</div>';
	}

	public function generate_privacy_policy( array $data ): string {
		$privacy_data = $this->get_privacy_template_data( $data );
		$parts   = array();
		$parts[] = $this->modules->render_block( 'privacy_intro', $privacy_data );
		$parts[] = $this->modules->render_block( 'controller', $privacy_data );
		if ( ! empty( $data['has_data_protection_officer'] ) && ( ! empty( $data['data_protection_officer_name'] ) || ! empty( $data['data_protection_officer_email'] ) ) ) {
			$parts[] = $this->modules->render_block( 'data_protection_officer', $privacy_data );
		}
		$parts[] = $this->modules->render_block( 'general_processing', $privacy_data );
		$parts[] = $this->modules->render_block( 'hosting', $privacy_data );
		$parts[] = $this->modules->render_block( 'server_logs' );
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
		if ( ! empty( $data['features']['shop'] ) || ! empty( $data['features']['customer_account'] ) || ! empty( $data['features']['user_registration'] ) ) {
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
			'calendly'              => 'get_calendly_module',
			'jotform'               => 'get_jotform_module',
			'trustpilot'            => 'get_trustpilot_module',
			'smtp_service'          => 'get_smtp_service_module',
		);

		foreach ( $service_map as $key => $method ) {
			if ( ! empty( $data['services'][ $key ] ) ) {
				$parts[] = $this->modules->render_block( $key, $privacy_data );
			}
		}

		if (
			! empty( $data['services']['google_maps'] ) ||
			! empty( $data['services']['youtube'] ) ||
			! empty( $data['services']['vimeo'] ) ||
			! empty( $data['services']['google_fonts_external'] )
		) {
			$parts[] = $this->modules->render_block( 'embeds', $privacy_data );
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
		$parts[] = $this->modules->render_block( 'third_country_transfer', $privacy_data );
		$parts[] = $this->modules->render_block( 'data_subject_rights' );
		$parts[] = $this->modules->render_block( 'complaint_authority' );

		return '<div class="frg-document frg-document--privacy">' . implode( '', $parts ) . '</div>';
	}

	public function get_impressum_template_data( array $data ): array {
		return array(
			'company'            => esc_html( $data['company_name'] ?? '' ),
			'legal_form'         => esc_html( $data['legal_form'] ?? '' ),
			'representative'     => esc_html( trim( ( $data['first_name'] ?? '' ) . ' ' . ( $data['last_name'] ?? '' ) ) ),
			'street'             => esc_html( $data['street'] ?? '' ),
			'zip'                => esc_html( $data['zip'] ?? '' ),
			'city'               => esc_html( $data['city'] ?? '' ),
			'country'            => esc_html( $data['country'] ?? '' ),
			'email'              => esc_html( $data['email'] ?? '' ),
			'phone_line'         => ! empty( $data['phone'] ) ? esc_html__( 'Telefon', 'frontend-rechtstexte-generator' ) . ': ' . esc_html( $data['phone'] ) . '<br>' : '',
			'website_line'       => ! empty( $data['website_url'] ) ? esc_html__( 'Website', 'frontend-rechtstexte-generator' ) . ': ' . esc_html( $data['website_url'] ) : '',
			'court'              => esc_html( $data['register_court'] ?? '' ),
			'number'             => esc_html( $data['register_number'] ?? '' ),
			'vat_id'             => esc_html( $data['vat_id'] ?? '' ),
			'business_id_line'   => ! empty( $data['business_id'] ) ? '<br>' . esc_html__( 'Wirtschafts-ID', 'frontend-rechtstexte-generator' ) . ': ' . esc_html( $data['business_id'] ) : '',
			'name'               => esc_html( $data['responsible_name'] ?? '' ),
			'address'            => nl2br( esc_html( $data['responsible_address'] ?? '' ) ),
			'chamber'            => esc_html( $data['professional_chamber'] ?? '' ),
			'title'              => esc_html( $data['professional_title'] ?? '' ),
			'awarded_in'         => esc_html( $data['professional_awarded_in'] ?? '' ),
			'rules'              => esc_html( $data['professional_rules'] ?? '' ),
			'authority'          => esc_html( $data['supervisory_authority'] ?? '' ),
		);
	}

	public function get_privacy_template_data( array $data ): array {
		$features = $data['features'] ?? array();
		$services = $data['services'] ?? array();

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
				'calendly'              => 'Calendly',
				'jotform'               => 'Jotform',
				'trustpilot'            => 'Trustpilot',
				'smtp_service'          => 'SMTP / E-Mail-Versanddienst',
			)
		);

		return array(
			'company'                    => esc_html( $data['company_name'] ?? '' ),
			'representative'             => esc_html( trim( ( $data['first_name'] ?? '' ) . ' ' . ( $data['last_name'] ?? '' ) ) ),
			'street'                     => esc_html( $data['street'] ?? '' ),
			'zip'                        => esc_html( $data['zip'] ?? '' ),
			'city'                       => esc_html( $data['city'] ?? '' ),
			'country'                    => esc_html( $data['country'] ?? '' ),
			'email'                      => esc_html( $data['email'] ?? '' ),
			'website_url'                => esc_html( $data['website_url'] ?? '' ),
			'name'                       => esc_html( $data['data_protection_officer_name'] ?? '' ),
			'purposes'                   => esc_html( $data['privacy_processing_purposes'] ?? __( 'Bereitstellung der Website, Kommunikation, Vertragsdurchfuehrung und Sicherheit', 'frontend-rechtstexte-generator' ) ),
			'legal_basis'                => esc_html( $data['privacy_legal_basis'] ?? __( 'Art. 6 Abs. 1 DSGVO nach konkreter Verarbeitung', 'frontend-rechtstexte-generator' ) ),
			'recipients'                 => esc_html( $data['privacy_recipient_categories'] ?? __( 'Hosting, IT-Dienstleister, eingesetzte Fachanbieter', 'frontend-rechtstexte-generator' ) ),
			'storage'                    => esc_html( $data['privacy_storage_general'] ?? __( 'Speicherung nur so lange, wie dies fuer den jeweiligen Zweck oder gesetzliche Pflichten erforderlich ist', 'frontend-rechtstexte-generator' ) ),
			'third_country'              => esc_html( $data['privacy_third_country_transfer'] ?? __( 'Ein Drittlandtransfer erfolgt nur, wenn dies bei einzelnen Diensten angegeben ist oder technisch erforderlich wird', 'frontend-rechtstexte-generator' ) ),
			'host'                       => esc_html( $data['hosting_provider'] ?? '' ),
			'location'                   => esc_html( $data['server_location'] ?? '' ),
			'av'                         => esc_html( $data['hosting_av_contract'] ?? '' ),
			'av_sentence'                => $this->get_hosting_av_sentence( (string) ( $data['hosting_av_contract'] ?? '' ) ),
			'providers'                  => esc_html( implode( ', ', $newsletter_providers ) ),
			'newsletter_providers'       => esc_html( implode( ', ', $newsletter_providers ) ),
			'profiles'                   => esc_html( implode( ', ', $social_profiles ) ),
			'social_profiles'            => esc_html( implode( ', ', $social_profiles ) ),
			'consent_tools'              => esc_html( implode( ', ', $consent_tools ) ),
			'security_tools'             => esc_html( implode( ', ', $security_tools ) ),
			'backup_tools'               => esc_html( implode( ', ', $backup_tools ) ),
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
		);
	}

	private function get_hosting_av_sentence( string $value ): string {
		$value = trim( $value );

		if ( 'Ja' === $value ) {
			return esc_html__( 'Nach Ihren Angaben besteht mit dem Hosting-Anbieter ein Vertrag zur Auftragsverarbeitung gemaess Art. 28 DSGVO.', 'frontend-rechtstexte-generator' );
		}

		if ( 'Nein' === $value ) {
			return esc_html__( 'Nach Ihren Angaben besteht derzeit kein Vertrag zur Auftragsverarbeitung mit dem Hosting-Anbieter. Dieser Punkt sollte datenschutzrechtlich besonders geprueft werden.', 'frontend-rechtstexte-generator' );
		}

		if ( 'Unbekannt' === $value ) {
			return esc_html__( 'Ob mit dem Hosting-Anbieter ein Vertrag zur Auftragsverarbeitung gemaess Art. 28 DSGVO besteht, wurde mit unbekannt angegeben und sollte geprueft werden.', 'frontend-rechtstexte-generator' );
		}

		return esc_html__( 'Bitte pruefen Sie, ob mit dem Hosting-Anbieter ein Vertrag zur Auftragsverarbeitung gemaess Art. 28 DSGVO abgeschlossen wurde.', 'frontend-rechtstexte-generator' );
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
}

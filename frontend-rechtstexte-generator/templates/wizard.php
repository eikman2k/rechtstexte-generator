<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$features = $data['features'] ?? array();
$services = $data['services'] ?? array();
$scanner_recommendations = $scanner_recommendations ?? array();
$scanner_errors          = $scanner_errors ?? array();
$privacy_defaults        = array(
	'controller_same_as_operator'  => true,
	'hosting_provider'             => 'Host Europe',
	'server_location'              => 'EU',
	'hosting_av_contract'          => 'Ja',
	'privacy_processing_purposes'  => 'Bereitstellung dieser Website, Sicherstellung des technischen Betriebs, Bearbeitung von Kontaktanfragen, Kommunikation mit Interessenten und Kunden, Vertragsanbahnung und Vertragsdurchführung, IT-Sicherheit sowie Missbrauchs- und Fehlerprävention.',
	'privacy_legal_basis'          => 'Art. 6 Abs. 1 lit. a DSGVO bei Einwilligungen, Art. 6 Abs. 1 lit. b DSGVO zur Durchführung vorvertraglicher Maßnahmen und zur Vertragserfüllung, Art. 6 Abs. 1 lit. c DSGVO zur Erfüllung rechtlicher Verpflichtungen sowie Art. 6 Abs. 1 lit. f DSGVO auf Grundlage berechtigter Interessen an einem sicheren, stabilen und wirtschaftlichen Online-Angebot.',
	'privacy_recipient_categories' => 'Hosting-Anbieter, technische Dienstleister, IT- und Support-Dienstleister, Kommunikationsdienstleister sowie gegebenenfalls weitere Auftragsverarbeiter oder eingesetzte Fachanbieter, soweit dies für den jeweiligen Zweck erforderlich ist.',
	'privacy_storage_general'      => 'Personenbezogene Daten werden nur so lange gespeichert, wie dies für die jeweiligen Verarbeitungszwecke erforderlich ist oder gesetzliche Aufbewahrungspflichten bestehen. Anschließend werden die Daten gelöscht oder ihre Verarbeitung eingeschränkt, soweit keine gesetzlichen oder vertraglichen Gründe entgegenstehen.',
	'privacy_third_country_transfer' => 'Eine Übermittlung personenbezogener Daten in Staaten außerhalb der EU bzw. des EWR erfolgt nur, wenn dies für einzelne Dienste erforderlich ist, eine entsprechende Rechtsgrundlage vorliegt und die gesetzlichen Voraussetzungen der Art. 44 ff. DSGVO eingehalten werden.',
);
?>
<div class="frg-wizard" data-frg-wizard>
	<div class="frg-progress">
		<div class="frg-progress__bar"><span class="frg-progress__fill" data-frg-progress-fill></span></div>
		<div class="frg-progress__label" data-frg-progress-label><?php echo esc_html__( 'Schritt 1 von 8', 'frontend-rechtstexte-generator' ); ?></div>
	</div>

	<div class="frg-notice frg-notice--warning"><?php echo wp_kses_post( $this->get_legal_notice() ); ?></div>

	<form class="frg-form" data-frg-form>
		<input type="hidden" name="nonce" value="<?php echo esc_attr( wp_create_nonce( 'frg_frontend_nonce' ) ); ?>">

		<section class="frg-step is-active" data-step="1">
			<h3><?php esc_html_e( 'Schritt 1: Allgemeine Kundendaten', 'frontend-rechtstexte-generator' ); ?></h3>
			<div class="frg-grid frg-grid--2">
				<?php $legal_forms = array( 'Einzelunternehmen', 'GbR', 'GmbH', 'UG', 'e.K.', 'Verein', 'sonstige' ); ?>
				<label><span><?php esc_html_e( 'Firmenname / Websitebetreiber *', 'frontend-rechtstexte-generator' ); ?></span><input required type="text" name="company_name" value="<?php echo esc_attr( $data['company_name'] ?? '' ); ?>"></label>
				<label><span><?php esc_html_e( 'Rechtsform *', 'frontend-rechtstexte-generator' ); ?></span><select required name="legal_form"><option value=""><?php esc_html_e( 'Bitte wählen', 'frontend-rechtstexte-generator' ); ?></option><?php foreach ( $legal_forms as $form ) : ?><option value="<?php echo esc_attr( $form ); ?>" <?php selected( $data['legal_form'] ?? '', $form ); ?>><?php echo esc_html( $form ); ?></option><?php endforeach; ?></select></label>
				<label><span><?php esc_html_e( 'Vorname *', 'frontend-rechtstexte-generator' ); ?></span><input required type="text" name="first_name" value="<?php echo esc_attr( $data['first_name'] ?? '' ); ?>"></label>
				<label><span><?php esc_html_e( 'Nachname *', 'frontend-rechtstexte-generator' ); ?></span><input required type="text" name="last_name" value="<?php echo esc_attr( $data['last_name'] ?? '' ); ?>"></label>
				<label><span><?php esc_html_e( 'Straße und Hausnummer *', 'frontend-rechtstexte-generator' ); ?></span><input required type="text" name="street" value="<?php echo esc_attr( $data['street'] ?? '' ); ?>"></label>
				<label><span><?php esc_html_e( 'PLZ *', 'frontend-rechtstexte-generator' ); ?></span><input required type="text" name="zip" value="<?php echo esc_attr( $data['zip'] ?? '' ); ?>"></label>
				<label><span><?php esc_html_e( 'Ort *', 'frontend-rechtstexte-generator' ); ?></span><input required type="text" name="city" value="<?php echo esc_attr( $data['city'] ?? '' ); ?>"></label>
				<label><span><?php esc_html_e( 'Land *', 'frontend-rechtstexte-generator' ); ?></span><input required type="text" name="country" value="<?php echo esc_attr( $data['country'] ?? '' ); ?>"></label>
				<label><span><?php esc_html_e( 'E-Mail-Adresse *', 'frontend-rechtstexte-generator' ); ?></span><input required type="email" name="email" value="<?php echo esc_attr( $data['email'] ?? '' ); ?>"></label>
				<label><span><?php esc_html_e( 'Telefon', 'frontend-rechtstexte-generator' ); ?></span><input type="text" name="phone" value="<?php echo esc_attr( $data['phone'] ?? '' ); ?>"></label>
				<label class="frg-grid__full"><span><?php esc_html_e( 'Website-URL *', 'frontend-rechtstexte-generator' ); ?></span><input required type="url" name="website_url" value="<?php echo esc_attr( $data['website_url'] ?? home_url( '/' ) ); ?>"></label>
			</div>
		</section>

		<section class="frg-step" data-step="2">
			<h3><?php esc_html_e( 'Schritt 2: Register und Steuerdaten', 'frontend-rechtstexte-generator' ); ?></h3>
			<div class="frg-grid frg-grid--2">
				<label class="frg-toggle"><input type="checkbox" name="has_trade_register" value="1" <?php checked( ! empty( $data['has_trade_register'] ) ); ?>><span><?php esc_html_e( 'Handelsregister vorhanden', 'frontend-rechtstexte-generator' ); ?></span></label>
				<div class="frg-grid__full frg-conditional-fields" data-frg-conditional="has_trade_register">
					<div class="frg-grid frg-grid--2">
						<label><span><?php esc_html_e( 'Registergericht', 'frontend-rechtstexte-generator' ); ?></span><input type="text" name="register_court" value="<?php echo esc_attr( $data['register_court'] ?? '' ); ?>"></label>
						<label><span><?php esc_html_e( 'Registernummer', 'frontend-rechtstexte-generator' ); ?></span><input type="text" name="register_number" value="<?php echo esc_attr( $data['register_number'] ?? '' ); ?>"></label>
					</div>
				</div>
				<label class="frg-toggle"><input type="checkbox" name="has_vat_id" value="1" <?php checked( ! empty( $data['has_vat_id'] ) ); ?>><span><?php esc_html_e( 'Umsatzsteuer-ID vorhanden', 'frontend-rechtstexte-generator' ); ?></span></label>
				<div class="frg-grid__full frg-conditional-fields" data-frg-conditional="has_vat_id">
					<div class="frg-grid frg-grid--2">
						<label><span><?php esc_html_e( 'Umsatzsteuer-ID', 'frontend-rechtstexte-generator' ); ?></span><input type="text" name="vat_id" value="<?php echo esc_attr( $data['vat_id'] ?? '' ); ?>"></label>
						<label><span><?php esc_html_e( 'Wirtschafts-ID', 'frontend-rechtstexte-generator' ); ?></span><input type="text" name="business_id" value="<?php echo esc_attr( $data['business_id'] ?? '' ); ?>"></label>
					</div>
				</div>
			</div>
		</section>

		<section class="frg-step" data-step="3">
			<h3><?php esc_html_e( 'Schritt 3: Besondere Angaben', 'frontend-rechtstexte-generator' ); ?></h3>
			<div class="frg-grid frg-grid--2">
				<label class="frg-toggle"><input type="checkbox" name="has_responsible_content" value="1" <?php checked( ! empty( $data['has_responsible_content'] ) ); ?>><span><?php esc_html_e( 'Inhaltlich verantwortlich nach § 18 Abs. 2 MStV', 'frontend-rechtstexte-generator' ); ?></span></label>
				<div class="frg-grid__full frg-conditional-fields" data-frg-conditional="has_responsible_content">
					<div class="frg-grid frg-grid--2">
						<label><span><?php esc_html_e( 'Name der verantwortlichen Person', 'frontend-rechtstexte-generator' ); ?></span><input type="text" name="responsible_name" value="<?php echo esc_attr( $data['responsible_name'] ?? '' ); ?>"></label>
						<label><span><?php esc_html_e( 'Anschrift der verantwortlichen Person', 'frontend-rechtstexte-generator' ); ?></span><textarea name="responsible_address" rows="4"><?php echo esc_textarea( $data['responsible_address'] ?? '' ); ?></textarea></label>
					</div>
				</div>
				<label class="frg-toggle"><input type="checkbox" name="has_professional_info" value="1" <?php checked( ! empty( $data['has_professional_info'] ) ); ?>><span><?php esc_html_e( 'Berufsspezifische Angaben erforderlich', 'frontend-rechtstexte-generator' ); ?></span></label>
				<div class="frg-grid__full frg-conditional-fields" data-frg-conditional="has_professional_info">
					<div class="frg-grid frg-grid--2">
						<label><span><?php esc_html_e( 'Kammer', 'frontend-rechtstexte-generator' ); ?></span><input type="text" name="professional_chamber" value="<?php echo esc_attr( $data['professional_chamber'] ?? '' ); ?>"></label>
						<label><span><?php esc_html_e( 'Berufsbezeichnung', 'frontend-rechtstexte-generator' ); ?></span><input type="text" name="professional_title" value="<?php echo esc_attr( $data['professional_title'] ?? '' ); ?>"></label>
						<label><span><?php esc_html_e( 'Staat der Verleihung', 'frontend-rechtstexte-generator' ); ?></span><input type="text" name="professional_awarded_in" value="<?php echo esc_attr( $data['professional_awarded_in'] ?? '' ); ?>"></label>
						<label><span><?php esc_html_e( 'Aufsichtsbehörde', 'frontend-rechtstexte-generator' ); ?></span><input type="text" name="supervisory_authority" value="<?php echo esc_attr( $data['supervisory_authority'] ?? '' ); ?>"></label>
						<label class="frg-grid__full"><span><?php esc_html_e( 'Berufsrechtliche Regelungen', 'frontend-rechtstexte-generator' ); ?></span><textarea name="professional_rules" rows="4"><?php echo esc_textarea( $data['professional_rules'] ?? '' ); ?></textarea></label>
					</div>
				</div>
				<label class="frg-toggle"><input type="checkbox" name="has_liability_insurance" value="1" <?php checked( ! empty( $data['has_liability_insurance'] ) ); ?>><span><?php esc_html_e( 'Angaben zur Berufshaftpflichtversicherung ausgeben', 'frontend-rechtstexte-generator' ); ?></span></label>
				<div class="frg-grid__full frg-conditional-fields" data-frg-conditional="has_liability_insurance">
					<div class="frg-grid frg-grid--2">
						<label><span><?php esc_html_e( 'Versicherer', 'frontend-rechtstexte-generator' ); ?></span><input type="text" name="liability_insurer" value="<?php echo esc_attr( $data['liability_insurer'] ?? '' ); ?>"></label>
						<label><span><?php esc_html_e( 'Räumlicher Geltungsbereich', 'frontend-rechtstexte-generator' ); ?></span><input type="text" name="liability_scope" value="<?php echo esc_attr( $data['liability_scope'] ?? '' ); ?>"></label>
						<label class="frg-grid__full"><span><?php esc_html_e( 'Anschrift des Versicherers', 'frontend-rechtstexte-generator' ); ?></span><textarea name="liability_insurer_address" rows="4"><?php echo esc_textarea( $data['liability_insurer_address'] ?? '' ); ?></textarea></label>
					</div>
				</div>
			</div>
		</section>

		<section class="frg-step" data-step="4">
			<h3><?php esc_html_e( 'Schritt 4: Datenschutz-Grunddaten', 'frontend-rechtstexte-generator' ); ?></h3>
			<div class="frg-feature-group frg-feature-group--section">
				<div class="frg-feature-group__header">
					<h4><?php esc_html_e( 'Verantwortlicher', 'frontend-rechtstexte-generator' ); ?></h4>
					<p><?php esc_html_e( 'Hier legen Sie fest, ob der Verantwortliche für die Datenverarbeitung mit dem Websitebetreiber identisch ist.', 'frontend-rechtstexte-generator' ); ?></p>
				</div>
				<div class="frg-grid frg-grid--2">
					<label class="frg-toggle"><input type="checkbox" name="controller_same_as_operator" value="1" <?php checked( ! array_key_exists( 'controller_same_as_operator', $data ) ? ! empty( $privacy_defaults['controller_same_as_operator'] ) : ! empty( $data['controller_same_as_operator'] ) ); ?>><span><?php esc_html_e( 'Verantwortlicher identisch mit Websitebetreiber', 'frontend-rechtstexte-generator' ); ?></span></label>
				</div>
			</div>
			<div class="frg-feature-group frg-feature-group--section">
				<div class="frg-feature-group__header">
					<h4><?php esc_html_e( 'Datenschutzbeauftragter', 'frontend-rechtstexte-generator' ); ?></h4>
					<p><?php esc_html_e( 'Wenn ein Datenschutzbeauftragter benannt wurde, sollten die Kontaktangaben möglichst vollständig gepflegt werden.', 'frontend-rechtstexte-generator' ); ?></p>
				</div>
				<div class="frg-grid frg-grid--2">
					<label class="frg-toggle"><input type="checkbox" name="has_data_protection_officer" value="1" <?php checked( ! empty( $data['has_data_protection_officer'] ) ); ?>><span><?php esc_html_e( 'Datenschutzbeauftragter vorhanden', 'frontend-rechtstexte-generator' ); ?></span></label>
					<div class="frg-grid__full frg-conditional-fields" data-frg-conditional="has_data_protection_officer">
						<div class="frg-grid frg-grid--2">
							<label><span><?php esc_html_e( 'Name Datenschutzbeauftragter', 'frontend-rechtstexte-generator' ); ?></span><input type="text" name="data_protection_officer_name" value="<?php echo esc_attr( $data['data_protection_officer_name'] ?? '' ); ?>"></label>
							<label><span><?php esc_html_e( 'E-Mail Datenschutzbeauftragter', 'frontend-rechtstexte-generator' ); ?></span><input type="email" name="data_protection_officer_email" value="<?php echo esc_attr( $data['data_protection_officer_email'] ?? '' ); ?>"></label>
							<label><span><?php esc_html_e( 'Telefon Datenschutzbeauftragter', 'frontend-rechtstexte-generator' ); ?></span><input type="text" name="data_protection_officer_phone" value="<?php echo esc_attr( $data['data_protection_officer_phone'] ?? '' ); ?>"></label>
							<label class="frg-grid__full"><span><?php esc_html_e( 'Anschrift Datenschutzbeauftragter', 'frontend-rechtstexte-generator' ); ?></span><textarea name="data_protection_officer_address" rows="4"><?php echo esc_textarea( $data['data_protection_officer_address'] ?? '' ); ?></textarea></label>
						</div>
					</div>
				</div>
			</div>
			<div class="frg-feature-group frg-feature-group--section">
				<div class="frg-feature-group__header">
					<h4><?php esc_html_e( 'Hosting', 'frontend-rechtstexte-generator' ); ?></h4>
					<p><?php esc_html_e( 'Diese Angaben werden für Hosting, Serverstandort und Auftragsverarbeitungsvertrag verwendet.', 'frontend-rechtstexte-generator' ); ?></p>
				</div>
				<div class="frg-grid frg-grid--2">
					<label><span><?php esc_html_e( 'Hosting-Anbieter *', 'frontend-rechtstexte-generator' ); ?></span><input required type="text" name="hosting_provider" value="<?php echo esc_attr( $data['hosting_provider'] ?? $privacy_defaults['hosting_provider'] ); ?>"></label>
					<label><span><?php esc_html_e( 'Serverstandort *', 'frontend-rechtstexte-generator' ); ?></span><select required name="server_location"><?php foreach ( array( '' => __( 'Bitte wählen', 'frontend-rechtstexte-generator' ), 'Deutschland' => 'Deutschland', 'EU' => 'EU', 'Drittland' => 'Drittland', 'unbekannt' => 'unbekannt' ) as $value => $label ) : ?><option value="<?php echo esc_attr( $value ); ?>" <?php selected( $data['server_location'] ?? $privacy_defaults['server_location'], $value ); ?>><?php echo esc_html( $label ); ?></option><?php endforeach; ?></select></label>
					<label><span><?php esc_html_e( 'AV-Vertrag mit Hosting-Anbieter *', 'frontend-rechtstexte-generator' ); ?></span><select required name="hosting_av_contract"><?php foreach ( array( '' => __( 'Bitte wählen', 'frontend-rechtstexte-generator' ), 'Ja' => 'Ja', 'Nein' => 'Nein', 'Unbekannt' => 'Unbekannt' ) as $value => $label ) : ?><option value="<?php echo esc_attr( $value ); ?>" <?php selected( $data['hosting_av_contract'] ?? $privacy_defaults['hosting_av_contract'], $value ); ?>><?php echo esc_html( $label ); ?></option><?php endforeach; ?></select></label>
					<label class="frg-grid__full"><span><?php esc_html_e( 'Anschrift Hosting-Anbieter', 'frontend-rechtstexte-generator' ); ?></span><textarea name="hosting_provider_address" rows="4"><?php echo esc_textarea( $data['hosting_provider_address'] ?? '' ); ?></textarea></label>
				</div>
			</div>
			<div class="frg-feature-group frg-feature-group--section">
				<div class="frg-feature-group__header">
					<h4><?php esc_html_e( 'Allgemeine Datenschutzangaben', 'frontend-rechtstexte-generator' ); ?></h4>
					<p><?php esc_html_e( 'Diese Angaben bilden die allgemeine Grundlage für die Datenschutzerklärung.', 'frontend-rechtstexte-generator' ); ?></p>
				</div>
				<div class="frg-grid frg-grid--2">
					<label class="frg-grid__full"><span><?php esc_html_e( 'Zwecke der Datenverarbeitung', 'frontend-rechtstexte-generator' ); ?></span><textarea name="privacy_processing_purposes" rows="3"><?php echo esc_textarea( $data['privacy_processing_purposes'] ?? $privacy_defaults['privacy_processing_purposes'] ); ?></textarea></label>
					<label class="frg-grid__full"><span><?php esc_html_e( 'Allgemeine Rechtsgrundlagen der Verarbeitung', 'frontend-rechtstexte-generator' ); ?></span><textarea name="privacy_legal_basis" rows="3"><?php echo esc_textarea( $data['privacy_legal_basis'] ?? $privacy_defaults['privacy_legal_basis'] ); ?></textarea></label>
					<label class="frg-grid__full"><span><?php esc_html_e( 'Kategorien von Empfängern', 'frontend-rechtstexte-generator' ); ?></span><textarea name="privacy_recipient_categories" rows="3"><?php echo esc_textarea( $data['privacy_recipient_categories'] ?? $privacy_defaults['privacy_recipient_categories'] ); ?></textarea></label>
					<label class="frg-grid__full"><span><?php esc_html_e( 'Allgemeine Angaben zur Speicherdauer', 'frontend-rechtstexte-generator' ); ?></span><textarea name="privacy_storage_general" rows="3"><?php echo esc_textarea( $data['privacy_storage_general'] ?? $privacy_defaults['privacy_storage_general'] ); ?></textarea></label>
					<label class="frg-grid__full"><span><?php esc_html_e( 'Hinweise zu Drittlandtransfer', 'frontend-rechtstexte-generator' ); ?></span><textarea name="privacy_third_country_transfer" rows="3"><?php echo esc_textarea( $data['privacy_third_country_transfer'] ?? $privacy_defaults['privacy_third_country_transfer'] ); ?></textarea></label>
				</div>
			</div>
		</section>

		<section class="frg-step" data-step="5">
			<h3><?php esc_html_e( 'Schritt 5: Website-Funktionen', 'frontend-rechtstexte-generator' ); ?></h3>
			<?php
			$feature_groups = array(
				array(
					'title'       => __( 'Kommunikation und Interaktion', 'frontend-rechtstexte-generator' ),
					'description' => __( 'Diese Funktionen betreffen Anfragen, Kommunikation und nutzerseitige Interaktionen auf der Website.', 'frontend-rechtstexte-generator' ),
					'items'       => array(
						'contact_form'          => 'Kontaktformular',
						'email_contact'         => 'E-Mail-Kontakt',
						'phone_contact'         => 'Telefonkontakt',
						'comments'              => 'Kommentare',
						'newsletter'            => 'Newsletter',
						'job_application_form'  => 'Bewerbungsformular',
						'appointment_booking'   => 'Terminbuchung',
						'social_media_profiles' => 'Social-Media-Profile / Verlinkungen',
					),
				),
				array(
					'title'       => __( 'Benutzerkonten und geschützte Bereiche', 'frontend-rechtstexte-generator' ),
					'description' => __( 'Wählen Sie hier Funktionen aus, bei denen Nutzerkonten, Logins oder interne Bereiche verarbeitet werden.', 'frontend-rechtstexte-generator' ),
					'items'       => array(
						'user_registration' => 'Benutzerregistrierung',
						'login_area'        => 'Login-Bereich',
						'customer_account'  => 'Kundenkonto',
						'download_area'     => 'Downloadbereich',
						'members_area'      => 'Mitgliederbereich',
					),
				),
				array(
					'title'       => __( 'Shop und Vertragsabwicklung', 'frontend-rechtstexte-generator' ),
					'description' => __( 'Dieser Bereich ist relevant, wenn Bestellungen, Zahlungen oder Versandprozesse über die Website laufen.', 'frontend-rechtstexte-generator' ),
					'items'       => array(
						'shop'              => 'WooCommerce / Online-Shop',
						'payment_provider'  => 'Zahlungsanbieter',
						'shipping_provider' => 'Versanddienstleister',
					),
				),
			);
			foreach ( $feature_groups as $feature_group ) :
				?>
				<div class="frg-feature-group frg-feature-group--section">
					<div class="frg-feature-group__header">
						<h4><?php echo esc_html( $feature_group['title'] ); ?></h4>
						<p><?php echo esc_html( $feature_group['description'] ); ?></p>
					</div>
					<div class="frg-checkbox-grid">
						<?php foreach ( $feature_group['items'] as $key => $label ) : ?>
							<label class="frg-check"><input type="checkbox" name="features[<?php echo esc_attr( $key ); ?>]" value="1" <?php checked( ! empty( $features[ $key ] ) ); ?>><span><?php echo esc_html( $label ); ?></span></label>
						<?php endforeach; ?>
					</div>
				</div>
			<?php endforeach; ?>
			<div class="frg-feature-group frg-feature-group--training">
				<div class="frg-feature-group__header">
					<h4><?php esc_html_e( 'Bereich Schulungsportal / Lernplattform', 'frontend-rechtstexte-generator' ); ?></h4>
					<p><?php esc_html_e( 'Nutzen Sie diese Auswahl, wenn Ihre Website Kurse, Lernstände, Prüfungen, Zertifikate oder rollenbasierte Portalzugriffe verarbeitet.', 'frontend-rechtstexte-generator' ); ?></p>
				</div>
				<div class="frg-grid frg-grid--2">
					<label class="frg-check frg-grid__full"><input type="checkbox" name="features[training_portal]" value="1" <?php checked( ! empty( $features['training_portal'] ) ); ?>><span><?php esc_html_e( 'Schulungsportal / Lernplattform aktiv', 'frontend-rechtstexte-generator' ); ?></span></label>
				</div>
				<div class="frg-conditional-fields" data-frg-conditional="features[training_portal]">
					<div class="frg-grid frg-grid--2">
						<label class="frg-check"><input type="checkbox" name="features[training_progress]" value="1" <?php checked( ! empty( $features['training_progress'] ) ); ?>><span><?php esc_html_e( 'Lernfortschritt wird gespeichert', 'frontend-rechtstexte-generator' ); ?></span></label>
						<label class="frg-check"><input type="checkbox" name="features[training_tests]" value="1" <?php checked( ! empty( $features['training_tests'] ) ); ?>><span><?php esc_html_e( 'Tests / Quiz / Prüfungen', 'frontend-rechtstexte-generator' ); ?></span></label>
						<label class="frg-check"><input type="checkbox" name="features[training_certificates]" value="1" <?php checked( ! empty( $features['training_certificates'] ) ); ?>><span><?php esc_html_e( 'Zertifikate / Teilnahmebescheinigungen', 'frontend-rechtstexte-generator' ); ?></span></label>
						<label class="frg-check"><input type="checkbox" name="features[employee_training]" value="1" <?php checked( ! empty( $features['employee_training'] ) ); ?>><span><?php esc_html_e( 'Mitarbeiterschulungen / Pflichtschulungen', 'frontend-rechtstexte-generator' ); ?></span></label>
						<label class="frg-check"><input type="checkbox" name="features[scorm_tracking]" value="1" <?php checked( ! empty( $features['scorm_tracking'] ) ); ?>><span><?php esc_html_e( 'SCORM / Lernpaket-Tracking', 'frontend-rechtstexte-generator' ); ?></span></label>
						<label class="frg-check"><input type="checkbox" name="features[certificate_download]" value="1" <?php checked( ! empty( $features['certificate_download'] ) ); ?>><span><?php esc_html_e( 'Zertifikats-Download', 'frontend-rechtstexte-generator' ); ?></span></label>
						<label class="frg-check"><input type="checkbox" name="features[mandatory_training_proof]" value="1" <?php checked( ! empty( $features['mandatory_training_proof'] ) ); ?>><span><?php esc_html_e( 'Nachweis absolvierte Pflichtunterweisungen', 'frontend-rechtstexte-generator' ); ?></span></label>
						<label class="frg-check"><input type="checkbox" name="features[trainer_manager_access]" value="1" <?php checked( ! empty( $features['trainer_manager_access'] ) ); ?>><span><?php esc_html_e( 'Dozenten- / Manager- / Admin-Zugriffe', 'frontend-rechtstexte-generator' ); ?></span></label>
						<label class="frg-check"><input type="checkbox" name="features[tenant_access]" value="1" <?php checked( ! empty( $features['tenant_access'] ) ); ?>><span><?php esc_html_e( 'Mandanten- / Firmenzugriffe', 'frontend-rechtstexte-generator' ); ?></span></label>
					</div>
				</div>
			</div>
		</section>

		<section class="frg-step" data-step="6">
			<h3><?php esc_html_e( 'Schritt 6: Externe Dienste und Tools', 'frontend-rechtstexte-generator' ); ?></h3>
			<div class="frg-notice">
				<p><?php esc_html_e( 'Folgende Dienste wurden erkannt. Bitte prüfen Sie, ob diese tatsächlich aktiv genutzt werden.', 'frontend-rechtstexte-generator' ); ?></p>
				<p><?php esc_html_e( 'Datenschutz-Hinweis: Der Scanner wertet aktive Plugins und den HTML-Quelltext der Startseite automatisiert aus. Es werden nur Hinweise angezeigt und nichts automatisch übernommen.', 'frontend-rechtstexte-generator' ); ?></p>
			</div>
			<?php if ( ! empty( $scanner_errors ) ) : ?>
				<div class="frg-notice frg-notice--warning">
					<?php foreach ( $scanner_errors as $scanner_error ) : ?>
						<p><?php echo esc_html( $scanner_error ); ?></p>
					<?php endforeach; ?>
				</div>
			<?php endif; ?>
			<div class="frg-scanner">
				<h4><?php esc_html_e( 'Erkannte Dienste und Plugins', 'frontend-rechtstexte-generator' ); ?></h4>
				<?php if ( empty( $scanner_recommendations ) ) : ?>
					<p><?php esc_html_e( 'Es wurden aktuell keine typischen Dienste erkannt.', 'frontend-rechtstexte-generator' ); ?></p>
				<?php else : ?>
					<div class="frg-scanner__list">
						<?php foreach ( $scanner_recommendations as $recommendation ) : ?>
							<div class="frg-scanner__item">
								<div>
									<strong><?php echo esc_html( $recommendation['label'] ); ?></strong>
									<span class="frg-scanner__meta"><?php echo esc_html( 'plugin' === $recommendation['source'] ? __( 'Erkannt über aktives Plugin', 'frontend-rechtstexte-generator' ) : __( 'Erkannt im HTML der Startseite', 'frontend-rechtstexte-generator' ) ); ?></span>
								</div>
								<?php if ( ! empty( $recommendation['adoptable'] ) ) : ?>
									<button
										type="button"
										class="frg-button frg-button--ghost"
										data-frg-apply-scan
										data-frg-target-group="<?php echo esc_attr( $recommendation['group'] ); ?>"
										data-frg-target-key="<?php echo esc_attr( $recommendation['key'] ); ?>"
									><?php esc_html_e( 'Übernehmen', 'frontend-rechtstexte-generator' ); ?></button>
								<?php else : ?>
									<span class="frg-scanner__hint"><?php esc_html_e( 'Nur Hinweis', 'frontend-rechtstexte-generator' ); ?></span>
								<?php endif; ?>
							</div>
						<?php endforeach; ?>
					</div>
				<?php endif; ?>
			</div>
			<?php
			$service_groups = array(
				array(
					'title'       => __( 'Google, Tracking und Werbung', 'frontend-rechtstexte-generator' ),
					'description' => __( 'Diese Dienste sind vor allem für Analyse, Marketing, Conversion-Messung und externe Ressourcen relevant.', 'frontend-rechtstexte-generator' ),
					'items'       => array(
						'google_fonts_external'         => 'Google Fonts extern',
						'google_fonts_local'            => 'Google Fonts lokal',
						'google_maps'                   => 'Google Maps',
						'google_analytics'             => 'Google Analytics',
						'google_tag_manager'           => 'Google Tag Manager',
						'google_ads_conversion_tracking'=> 'Google Ads Conversion Tracking',
						'meta_pixel'                   => 'Meta Pixel',
						'matomo'                       => 'Matomo',
						'microsoft_clarity'            => 'Microsoft Clarity',
					),
				),
				array(
					'title'       => __( 'Medien, Einbindungen und Formulare', 'frontend-rechtstexte-generator' ),
					'description' => __( 'Hierzu gehören eingebettete Inhalte, Social-Media-Dienste und Formular- oder Termin-Tools.', 'frontend-rechtstexte-generator' ),
					'items'       => array(
						'youtube'        => 'YouTube',
						'vimeo'          => 'Vimeo',
						'elementor'      => 'Elementor',
						'gravity_forms'  => 'Gravity Forms',
						'contact_form_7' => 'Contact Form 7',
						'wpforms'        => 'WPForms',
						'calendly'       => 'Calendly / Terminbuchung',
						'jotform'        => 'Jotform / externer Formularanbieter',
						'facebook'       => 'Facebook',
						'instagram'      => 'Instagram',
						'linkedin'       => 'LinkedIn',
						'xing'           => 'Xing',
						'tiktok'         => 'TikTok',
					),
				),
				array(
					'title'       => __( 'Consent, Sicherheit und Infrastruktur', 'frontend-rechtstexte-generator' ),
					'description' => __( 'Diese Auswahl betrifft Consent-Management, Schutzmechanismen, CDN- und Infrastruktur-Dienste.', 'frontend-rechtstexte-generator' ),
					'items'       => array(
						'cloudflare'         => 'Cloudflare',
						'recaptcha'          => 'reCAPTCHA',
						'hcaptcha'           => 'hCaptcha',
						'borlabs_cookie'     => 'Borlabs Cookie',
						'real_cookie_banner' => 'Real Cookie Banner',
						'complianz'          => 'Complianz',
						'cookieyes'          => 'CookieYes',
						'wordfence'          => 'Wordfence',
						'ithemes_security'   => 'iThemes Security / Solid Security',
					),
				),
				array(
					'title'       => __( 'Backup, Versand und Marketing-Tools', 'frontend-rechtstexte-generator' ),
					'description' => __( 'Hier bündeln Sie technische Backup-Dienste sowie Newsletter-, E-Mail- und Bewertungsdienste.', 'frontend-rechtstexte-generator' ),
					'items'       => array(
						'updraftplus'  => 'UpdraftPlus',
						'wpvivid'      => 'WPvivid',
						'mailchimp'    => 'Mailchimp',
						'brevo'        => 'Brevo',
						'sendinblue'   => 'Sendinblue',
						'cleverreach'  => 'CleverReach',
						'trustpilot'   => 'Trustpilot / Bewertungsdienst',
						'smtp_service' => 'SMTP / E-Mail-Versanddienst',
					),
				),
			);
			foreach ( $service_groups as $service_group ) :
				?>
				<div class="frg-feature-group frg-feature-group--section">
					<div class="frg-feature-group__header">
						<h4><?php echo esc_html( $service_group['title'] ); ?></h4>
						<p><?php echo esc_html( $service_group['description'] ); ?></p>
					</div>
					<div class="frg-checkbox-grid">
						<?php foreach ( $service_group['items'] as $key => $label ) : ?>
							<label class="frg-check"><input type="checkbox" name="services[<?php echo esc_attr( $key ); ?>]" value="1" <?php checked( ! empty( $services[ $key ] ) ); ?>><span><?php echo esc_html( $label ); ?></span></label>
						<?php endforeach; ?>
					</div>
				</div>
			<?php endforeach; ?>
		</section>

		<section class="frg-step" data-step="7">
			<h3><?php esc_html_e( 'Schritt 7: Vorschau', 'frontend-rechtstexte-generator' ); ?></h3>
			<div class="frg-notice frg-notice--warning"><?php echo wp_kses_post( $this->get_legal_notice() ); ?></div>
			<div class="frg-step__actions"><button type="button" class="frg-button" data-frg-preview><?php esc_html_e( 'Vorschau aktualisieren', 'frontend-rechtstexte-generator' ); ?></button></div>
			<div class="frg-preview" data-frg-preview-container></div>
		</section>

		<section class="frg-step" data-step="8">
			<h3><?php esc_html_e( 'Schritt 8: Speichern / Seiten erstellen', 'frontend-rechtstexte-generator' ); ?></h3>
			<div class="frg-notice"><?php esc_html_e( 'Nicht eingeloggte Benutzer können die Vorschau nutzen, aber keine Daten speichern oder Seiten erstellen.', 'frontend-rechtstexte-generator' ); ?></div>
			<div class="frg-save-status" data-frg-save-status>
				<?php if ( ! empty( $last_saved_label ) ) : ?>
					<?php echo esc_html( sprintf( __( 'Zuletzt gespeichert am: %s', 'frontend-rechtstexte-generator' ), $last_saved_label ) ); ?>
				<?php else : ?>
					<?php esc_html_e( 'Noch nicht gespeichert.', 'frontend-rechtstexte-generator' ); ?>
				<?php endif; ?>
			</div>
			<div class="frg-step__actions frg-step__actions--stack">
				<button type="button" class="frg-button" data-frg-save><?php esc_html_e( 'Angaben speichern', 'frontend-rechtstexte-generator' ); ?></button>
				<button type="button" class="frg-button frg-button--secondary" data-frg-sync="impressum"><?php esc_html_e( 'Impressum-Seite erstellen oder aktualisieren', 'frontend-rechtstexte-generator' ); ?></button>
				<button type="button" class="frg-button frg-button--secondary" data-frg-sync="privacy"><?php esc_html_e( 'Datenschutzerklärung-Seite erstellen oder aktualisieren', 'frontend-rechtstexte-generator' ); ?></button>
			</div>
			<div class="frg-html-export">
				<div class="frg-notice">
					<?php esc_html_e( 'Sie können Impressum und Datenschutzerklärung separat als HTML kopieren und in eigene Seiten einbinden.', 'frontend-rechtstexte-generator' ); ?>
				</div>
				<div class="frg-grid frg-grid--2">
					<div class="frg-html-export__card">
						<div class="frg-html-export__header">
							<h4><?php esc_html_e( 'Impressum HTML', 'frontend-rechtstexte-generator' ); ?></h4>
							<button type="button" class="frg-button frg-button--ghost" data-frg-copy-target="impressum"><?php esc_html_e( 'Impressum HTML kopieren', 'frontend-rechtstexte-generator' ); ?></button>
						</div>
						<textarea rows="12" class="frg-html-export__textarea" data-frg-html-output="impressum" readonly><?php echo esc_textarea( $data['generated_impressum_export_html'] ?? '' ); ?></textarea>
					</div>
					<div class="frg-html-export__card">
						<div class="frg-html-export__header">
							<h4><?php esc_html_e( 'Datenschutzerklärung HTML', 'frontend-rechtstexte-generator' ); ?></h4>
							<button type="button" class="frg-button frg-button--ghost" data-frg-copy-target="privacy"><?php esc_html_e( 'Datenschutzerklärung HTML kopieren', 'frontend-rechtstexte-generator' ); ?></button>
						</div>
						<textarea rows="12" class="frg-html-export__textarea" data-frg-html-output="privacy" readonly><?php echo esc_textarea( $data['generated_privacy_export_html'] ?? '' ); ?></textarea>
					</div>
				</div>
			</div>
		</section>

		<div class="frg-feedback" data-frg-feedback aria-live="polite"></div>

		<div class="frg-nav">
			<button type="button" class="frg-button frg-button--ghost" data-frg-prev><?php esc_html_e( 'Zurueck', 'frontend-rechtstexte-generator' ); ?></button>
			<button type="button" class="frg-button" data-frg-next><?php esc_html_e( 'Weiter', 'frontend-rechtstexte-generator' ); ?></button>
		</div>
	</form>
</div>

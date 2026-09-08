<?php
declare(strict_types=1);

define( 'ABSPATH', __DIR__ . '/' );

$frg_test_options = array();

function __( string $text, string $domain = '' ): string {
	return $text;
}

function esc_html__( string $text, string $domain = '' ): string {
	return htmlspecialchars( $text, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8' );
}

function esc_html( $text ): string {
	return htmlspecialchars( (string) $text, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8' );
}

function esc_url( $url ): string {
	return filter_var( (string) $url, FILTER_SANITIZE_URL ) ?: '';
}

function sanitize_key( $key ): string {
	return preg_replace( '/[^a-z0-9_\-]/', '', strtolower( (string) $key ) ) ?: '';
}

function sanitize_text_field( $value ): string {
	return trim( strip_tags( (string) $value ) );
}

function sanitize_textarea_field( $value ): string {
	return trim( strip_tags( (string) $value ) );
}

function wp_kses_post( $content ): string {
	return (string) $content;
}

function wp_json_encode( $value ): string {
	return json_encode( $value, JSON_UNESCAPED_UNICODE ) ?: '';
}

function wp_strip_all_tags( $content ): string {
	return strip_tags( (string) $content );
}

function wpautop( $content ): string {
	return '<p>' . preg_replace( '/\R{2,}/', '</p><p>', trim( (string) $content ) ) . '</p>';
}

function current_time( string $format ): string {
	return 'Y-m-d' === $format ? '2026-09-07' : '2026-09-07 12:00:00';
}

function get_option( string $key, $default = false ) {
	global $frg_test_options;
	return $frg_test_options[ $key ] ?? $default;
}

function update_option( string $key, $value ): bool {
	global $frg_test_options;
	$frg_test_options[ $key ] = $value;
	return true;
}

require dirname( __DIR__ ) . '/frontend-rechtstexte-generator/includes/class-frg-text-modules.php';
require dirname( __DIR__ ) . '/frontend-rechtstexte-generator/includes/class-frg-generator.php';
require dirname( __DIR__ ) . '/frontend-rechtstexte-generator/includes/class-frg-block-feed.php';

function assert_contains( string $needle, string $haystack, string $message ): void {
	if ( false === strpos( $haystack, $needle ) ) {
		fwrite( STDERR, "FAIL: {$message}\n" );
		exit( 1 );
	}
}

function assert_not_contains( string $needle, string $haystack, string $message ): void {
	if ( false !== strpos( $haystack, $needle ) ) {
		fwrite( STDERR, "FAIL: {$message}\n" );
		exit( 1 );
	}
}

$generator = new FRG_Generator( new FRG_Text_Modules() );
$quality_flags = $generator->inspect_text_quality( '<h3>Hinweis</h3><p>Der jeweils eingesetzte Anbieter kann eingesetzt werden. Bitte prüfen. Vertragsdurchfuehrung erfolgt gegebenenfalls.</p>' );
if ( count( $quality_flags ) < 5 ) {
	fwrite( STDERR, 'FAIL: Automatische Textprüfung erkennt problematische Muster nicht zuverlässig: ' . wp_json_encode( $quality_flags ) . "\n" );
	exit( 1 );
}
$base = array(
	'company_name'       => 'Betreiber GmbH',
	'legal_form'         => 'GmbH',
	'first_name'         => 'Max',
	'last_name'          => 'Betreiber',
	'street'             => 'Betreiberweg 1',
	'zip'                => '10000',
	'city'               => 'Berlin',
	'country'            => 'Deutschland',
	'email'              => 'info@betreiber.test',
	'website_url'        => 'https://example.test',
	'hosting_provider'   => 'Testhoster GmbH',
	'server_location'    => 'Deutschland',
	'hosting_av_contract'=> 'Ja',
	'features'           => array(),
	'services'           => array(),
);

$different_controller = array_merge(
	$base,
	array(
		'controller_same_as_operator' => false,
		'controller_name'             => 'Verantwortlich AG',
		'controller_representative'   => 'Erika Verantwortung',
		'controller_street'           => 'Datenschutzstraße 2',
		'controller_zip'              => '20000',
		'controller_city'             => 'Hamburg',
		'controller_country'          => 'Deutschland',
		'controller_email'            => 'privacy@verantwortlich.test',
	)
);
$privacy = $generator->generate_privacy_policy( $different_controller );
assert_contains( 'Verantwortlich AG', $privacy, 'Abweichender Verantwortlicher fehlt.' );
assert_contains( 'privacy@verantwortlich.test', $privacy, 'E-Mail des abweichenden Verantwortlichen fehlt.' );

$registration = $base;
$registration['features']['user_registration'] = true;
$privacy = $generator->generate_privacy_policy( $registration );
assert_contains( 'Registrierung und Login', $privacy, 'Registrierungsblock fehlt.' );
assert_not_contains( 'Bestellungen, Kundenkonto und Vertragsabwicklung', $privacy, 'Registrierung aktiviert fälschlich den Shop-Block.' );

$analytics = $base;
$analytics['services']['google_analytics'] = true;
$analytics['service_details']['google_analytics'] = array(
	'provider'    => 'Google Ireland Limited',
	'purpose'     => 'Reichweitenmessung',
	'legal_basis' => 'Art. 6 Abs. 1 lit. a DSGVO',
	'retention'   => '14 Monate',
);
$privacy = $generator->generate_privacy_policy( $analytics );
assert_contains( 'Google Ireland Limited', $privacy, 'Konkreter Dienstanbieter fehlt.' );
assert_contains( '14 Monate', $privacy, 'Konkrete Speicherdauer fehlt.' );
assert_not_contains( '{{', $privacy, 'Nicht ersetzter Platzhalter in der Datenschutzerklärung.' );
assert_not_contains( 'Bitte prüfen Sie', $privacy, 'Redaktionelle Prüfanweisung eines Dienstmoduls wird veröffentlicht.' );

$contact_form = $base;
$contact_form['features']['contact_form'] = true;
$contact_form['services']['elementor'] = true;
$privacy = $generator->generate_privacy_policy( $contact_form );
assert_contains( 'Eingesetztes Formularsystem', $privacy, 'Bezeichnung des Formularsystems fehlt.' );
assert_contains( 'Elementor', $privacy, 'Ausgewähltes Formularsystem fehlt im Kontaktformular-Abschnitt.' );
assert_not_contains( '{{', $privacy, 'Nicht ersetzter Platzhalter im Kontaktformular-Abschnitt.' );

$clean_output = $base;
$clean_output['services'] = array(
	'google_fonts_external' => true,
	'google_fonts_local'    => true,
	'wpvivid'               => true,
);
$clean_output['backup_destination'] = 'Eigener Backup-Server in Deutschland';
$clean_output['backup_storage_provider'] = 'Betreiber GmbH';
$clean_output['backup_storage_address'] = "Backupweg 3\n30000 Hannover";
$clean_output['backup_retention'] = '30 Tage';
$clean_output['privacy_supervisory_authority_name'] = 'Der Landesbeauftragte für den Datenschutz Niedersachsen';
$clean_output['privacy_supervisory_authority_address'] = "Prinzenstraße 5\n30159 Hannover";
$clean_output['privacy_supervisory_authority_url'] = 'https://www.lfd.niedersachsen.de/';
$privacy = $generator->generate_privacy_policy( $clean_output );
assert_contains( 'Eigener Backup-Server in Deutschland', $privacy, 'Backup-Speicherort fehlt.' );
assert_contains( 'Art. 6 Abs. 1 lit. f DSGVO', $privacy, 'Rechtsgrundlage des Backup-Abschnitts fehlt.' );
assert_contains( 'Der Landesbeauftragte für den Datenschutz Niedersachsen', $privacy, 'Konkrete Aufsichtsbehörde fehlt.' );
assert_contains( 'Die Schriftdateien befinden sich auf unserem eigenen Server', $privacy, 'Lokale Google-Fonts-Ausgabe fehlt.' );
assert_not_contains( 'Schriftarten nicht lokal', $privacy, 'Externe Google-Fonts-Ausgabe bleibt trotz lokaler Auswahl aktiv.' );
assert_not_contains( 'Bitte prüfen Sie', $privacy, 'Redaktionelle Prüfanweisung wird veröffentlicht.' );
assert_contains( 'Auskunft (Art. 15 DSGVO)', $privacy, 'Artikelangaben bei den Betroffenenrechten fehlen.' );
assert_contains( 'Widerspruch gegen diese Verarbeitung', $privacy, 'Widerspruchsrecht fehlt.' );
assert_not_contains( '<h3>Drittlandtransfer</h3>', $privacy, 'Generischer Drittlandabschnitt wird ohne konkreten Bezug veröffentlicht.' );
if ( 1 !== substr_count( $privacy, '<h3>Speicherdauer</h3>' ) ) {
	fwrite( STDERR, "FAIL: Speicherdauer wird nicht genau einmal ausgegeben.\n" );
	exit( 1 );
}

$third_country = $base;
$third_country['privacy_third_country_transfer'] = 'Daten werden an einen Anbieter in den USA auf Grundlage geeigneter Garantien übermittelt.';
$privacy = $generator->generate_privacy_policy( $third_country );
assert_not_contains( '<h3>Drittlandtransfer</h3>', $privacy, 'Drittlandabschnitt erscheint trotz deaktiviertem Schalter.' );
$third_country['has_third_country_transfer'] = true;
$privacy = $generator->generate_privacy_policy( $third_country );
assert_contains( '<h3>Drittlandtransfer</h3>', $privacy, 'Drittlandabschnitt fehlt trotz aktiviertem Schalter.' );
assert_contains( 'Daten werden an einen Anbieter in den USA', $privacy, 'Konkreter Drittlandtext fehlt.' );

$vimeo = $base;
$vimeo['services']['vimeo'] = true;
$vimeo['services']['borlabs_cookie'] = true;
$vimeo['service_details']['vimeo'] = array(
	'provider' => 'Vimeo.com, Inc.',
	'consent'  => 'Vor Einwilligung blockiert',
);
$privacy = $generator->generate_privacy_policy( $vimeo );
assert_contains( 'durch Borlabs Cookie blockiert', $privacy, 'Konkrete Vimeo-Einwilligungssteuerung fehlt.' );
assert_contains( 'Art. 6 Abs. 1 lit. a DSGVO', $privacy, 'Einwilligungs-Rechtsgrundlage für Vimeo fehlt.' );
assert_not_contains( 'sofern ein Consent-Tool', $privacy, 'Hypothetische Vimeo-Formulierung wird veröffentlicht.' );
assert_not_contains( 'Eingebettete Inhalte und externe Ressourcen', $privacy, 'Generischer Embed-Sammelblock erzeugt eine Doppelung.' );

$incomplete_vimeo = $base;
$incomplete_vimeo['services']['vimeo'] = true;
$privacy = $generator->generate_privacy_policy( $incomplete_vimeo );
assert_not_contains( '<h3>Vimeo</h3>', $privacy, 'Unvollständiger Vimeo-Abschnitt wird veröffentlicht.' );

$incomplete_ai = $base;
$incomplete_ai['services']['ai_chatbot'] = true;
$privacy = $generator->generate_privacy_policy( $incomplete_ai );
assert_not_contains( 'Website-KI-Bot / KI-Assistent', $privacy, 'KI-Abschnitt ohne konkreten Anbieter wird veröffentlicht.' );
assert_not_contains( 'der jeweils eingesetzte KI-Anbieter', $privacy, 'Unbestimmter KI-Anbieter wird veröffentlicht.' );

$openai = $base;
$openai['services']['ai_chatbot'] = true;
$openai['services']['openai'] = true;
$privacy = $generator->generate_privacy_policy( $openai );
assert_contains( 'folgende KI-Anbieter eingesetzt: OpenAI', $privacy, 'Konkreter KI-Anbieter fehlt.' );

$incomplete_smtp = $base;
$incomplete_smtp['services']['smtp_service'] = true;
$privacy = $generator->generate_privacy_policy( $incomplete_smtp );
assert_not_contains( '<h3>E-Mail-Versand / SMTP</h3>', $privacy, 'SMTP-Abschnitt ohne konkreten Anbieter wird veröffentlicht.' );

$smtp = $incomplete_smtp;
$smtp['service_details']['smtp_service']['provider'] = 'Eigener Mailserver auf der Hosting-Infrastruktur';
$privacy = $generator->generate_privacy_policy( $smtp );
assert_contains( 'Eigener Mailserver auf der Hosting-Infrastruktur', $privacy, 'Konkreter SMTP-Anbieter fehlt.' );

$social_links = $base;
$social_links['features']['social_media_profiles'] = true;
$social_links['services']['instagram'] = true;
$social_links['social_media_integration'] = 'links';
$privacy = $generator->generate_privacy_policy( $social_links );
assert_contains( 'Beim bloßen Aufruf dieser Website werden über diese Links keine Daten', $privacy, 'Social-Media-Verlinkung wird nicht von Einbettungen unterschieden.' );

$frg_test_options['frg_block_registry'] = array(
	'hosting' => array(
		'status'        => 'editorial_approved',
		'override_text' => '<h3>Hosting</h3><p>Rechtsgrundlage ist Art. 6 Abs. 1 DSGVO.</p><p>Es kann nicht ausgeschlossen werden, dass im Rahmen des Hostings Daten auch in Drittländern verarbeitet werden. In diesem Fall gelten die Anforderungen der Art. 44 ff. DSGVO.</p>',
	),
);
$privacy = $generator->generate_privacy_policy( $base );
assert_contains( 'Art. 6 Abs. 1 lit. f DSGVO', $privacy, 'Konkrete Hosting-Rechtsgrundlage wird bei einem Live-Override nicht erzwungen.' );
assert_not_contains( 'Art. 6 Abs. 1 DSGVO.</p>', $privacy, 'Unvollständige Hosting-Rechtsgrundlage bleibt im Live-Override erhalten.' );
assert_not_contains( 'Es kann nicht ausgeschlossen werden', $privacy, 'Drittlandpassus aus Hosting-Live-Override bleibt trotz deaktiviertem Schalter sichtbar.' );
assert_not_contains( 'Verbindliche Angaben zu diesem Bereich', $privacy, 'Technische Überschrift für ergänzte Pflichtangaben wird veröffentlicht.' );
$frg_test_options['frg_block_registry'] = array();

$frg_test_options['frg_block_registry'] = array(
	'server_logs' => array(
		'status'        => 'editorial_approved',
		'override_text' => '<h3>Server-Logfiles</h3><p>Durchfuehrung, Erfuellung, Vertragsdurchfuehrung, Fehlerpraevention, Massnahmen, Vertragserfuellung, Anschliessend eingeschraenkt aus wichtigen Gruenden. Aufgerufene Seite/Resource.</p><p><strong>Pflicht zur Bereitstellung / Consent:</strong> erforderlich.</p><h3>Hinweis</h3><p>Bitte prüfen Sie diesen Abschnitt.</p>',
	),
);
$privacy = $generator->generate_privacy_policy( $base );
assert_contains( 'Durchführung, Erfüllung, Vertragsdurchführung, Fehlerprävention, Maßnahmen, Vertragserfüllung, Anschließend eingeschränkt aus wichtigen Gründen. Aufgerufene Seite/Ressource.', $privacy, 'Deutsche Umlaute werden in einem Live-Override nicht normalisiert.' );
assert_contains( 'Erforderlichkeit der Verarbeitung', $privacy, 'Technische Consent-Bezeichnung wird nicht verständlich übersetzt.' );
assert_not_contains( '<h3>Hinweis</h3>', $privacy, 'Verwaiste Hinweisüberschrift bleibt nach dem Entfernen einer Prüfanweisung sichtbar.' );
$frg_test_options['frg_block_registry'] = array();

$frg_test_options['frg_block_registry'] = array(
	'contact_form' => array(
		'status'        => 'editorial_approved',
		'override_text' => '<h3>Kontaktformular</h3><p>Eine Übermittlung Ihrer Daten in Staaten außerhalb der EU/des EWR findet im Zusammenhang mit der Nutzung des Kontaktformulars grundsätzlich nicht statt.</p>',
	),
);
$contact_form = $base;
$contact_form['features']['contact_form'] = true;
$privacy = $generator->generate_privacy_policy( $contact_form );
assert_not_contains( 'grundsätzlich nicht statt', $privacy, 'Ungeprüfte Drittlandaussage des Kontaktformulars wird veröffentlicht.' );
assert_not_contains( '<h3>Hinweis</h3>', $privacy, 'Leere Hinweisüberschrift wird veröffentlicht.' );
$frg_test_options['frg_block_registry'] = array();

$other_form = $base;
$other_form['legal_form'] = 'sonstige';
$other_form['legal_form_other'] = 'Gemeinnützige Stiftung';
$impressum = $generator->generate_impressum( $other_form );
assert_contains( 'Rechtsform:</strong> Gemeinnützige Stiftung', $impressum, 'Eigene Rechtsform wird nicht ausgegeben.' );
assert_not_contains( 'Rechtsform:</strong> sonstige', $impressum, 'Interner Auswahlwert „sonstige“ wird veröffentlicht.' );

$frg_test_options['frg_block_registry'] = array(
	'hosting' => array( 'status' => 'approved' ),
);
$registry = ( new FRG_Text_Modules() )->get_block_registry();
if ( 'editorial_approved' !== $registry['hosting']['status'] ) {
	fwrite( STDERR, "FAIL: Alter approved-Status wurde nicht sicher migriert.\n" );
	exit( 1 );
}

$frg_test_options['frg_block_registry'] = array(
	'hosting' => array(
		'status'        => 'editorial_approved',
		'admin_notes'   => 'Interne Notiz darf nicht übertragen werden.',
		'draft_text'    => '<p>Unveröffentlichter Entwurf</p>',
		'override_text' => '<h3>Veröffentlichter Hostingtext</h3>',
		'legal_basis'   => array( 'DSGVO Art. 6 Abs. 1 lit. f' ),
	),
);
$feed = new FRG_Block_Feed( $generator );
$public_blocks_method = new ReflectionMethod( FRG_Block_Feed::class, 'build_public_blocks' );
$public_blocks = $public_blocks_method->invoke( $feed );
$public_hosting_json = wp_json_encode( $public_blocks['hosting'] );
assert_contains( 'Veröffentlichter Hostingtext', $public_hosting_json, 'Veröffentlichter Feed-Text fehlt.' );
assert_not_contains( 'Interne Notiz', $public_hosting_json, 'Interne Notiz wird im Feed veröffentlicht.' );
assert_not_contains( 'Unveröffentlichter Entwurf', $public_hosting_json, 'Entwurf wird im Feed veröffentlicht.' );

$frg_test_options['frg_block_registry']['hosting']['override_text'] = '';
$public_blocks = $public_blocks_method->invoke( $feed );
assert_contains( 'Hosting und technische Bereitstellung', $public_blocks['hosting']['published_text'], 'Aktiver Standardtext fehlt im Feed.' );
assert_contains( '{{host}}', $public_blocks['hosting']['published_text'], 'Kundenspezifischer Hosting-Platzhalter wurde im Feed entfernt.' );

$merge_method = new ReflectionMethod( FRG_Block_Feed::class, 'merge_remote_blocks' );
$merged_registry = $merge_method->invoke(
	$feed,
	( new FRG_Text_Modules() )->get_block_registry(),
	array(
		'hosting' => array(
			'published_text' => '<h3>Zentral aktualisierter Hostingtext</h3>',
			'legal_basis'   => array( 'DSGVO Art. 28' ),
		),
	)
);
assert_contains( 'Zentral aktualisierter Hostingtext', $merged_registry['hosting']['override_text'], 'Remote-Text wurde nicht übernommen.' );
assert_contains( 'Unveröffentlichter Entwurf', $merged_registry['hosting']['draft_text'], 'Lokaler Entwurf wurde bei der Synchronisierung überschrieben.' );

echo "Generator regression tests passed.\n";

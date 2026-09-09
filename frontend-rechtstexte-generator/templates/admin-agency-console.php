<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$agency  = is_array( $agency_data['agency'] ?? null ) ? $agency_data['agency'] : array();
$licenses = is_array( $agency_data['licenses'] ?? null ) ? $agency_data['licenses'] : array();
$text_source = 'agency' === ( $agency['text_source'] ?? 'master' ) ? 'agency' : 'master';
?>
<div class="wrap frg-admin frg-license-admin">
	<div class="frg-admin-hero">
		<div><span class="frg-admin-eyebrow"><?php esc_html_e( 'Agenturzugang', 'frontend-rechtstexte-generator' ); ?></span><h1><?php esc_html_e( 'Kundenschlüssel verwalten', 'frontend-rechtstexte-generator' ); ?></h1><p><?php esc_html_e( 'Erstellen Sie für jede Kundenwebsite einen eigenen Schlüssel. Kontingent und Laufzeit werden von der zentralen Lizenzverwaltung vorgegeben.', 'frontend-rechtstexte-generator' ); ?></p></div>
		<div class="frg-admin-hero__actions"><a class="button button-hero" href="<?php echo esc_url( admin_url( 'admin.php?page=frg-settings#frg-block-feed' ) ); ?>"><?php esc_html_e( 'Verbindungseinstellungen', 'frontend-rechtstexte-generator' ); ?></a><a class="button button-primary button-hero" href="#frg-agency-create"><?php esc_html_e( 'Kundenschlüssel erstellen', 'frontend-rechtstexte-generator' ); ?></a></div>
	</div>
	<?php settings_errors( 'frg_agency_messages' ); ?>
	<?php if ( $error ) : ?><div class="notice notice-error inline"><p><?php echo esc_html( $error ); ?> <?php esc_html_e( 'Falls vorhanden, werden darunter die zuletzt geladenen Daten angezeigt.', 'frontend-rechtstexte-generator' ); ?></p></div><?php endif; ?>

	<section class="frg-license-stats frg-agency-stats">
		<div><strong><?php echo esc_html( (string) ( $agency_data['used_sites'] ?? count( $licenses ) ) ); ?></strong><span><?php esc_html_e( 'Kundenschlüssel aktiv', 'frontend-rechtstexte-generator' ); ?></span></div>
		<div><strong><?php echo esc_html( (string) ( $agency['max_sites'] ?? 0 ) ); ?></strong><span><?php esc_html_e( 'Kontingent', 'frontend-rechtstexte-generator' ); ?></span></div>
		<div><strong><?php echo esc_html( (string) ( $agency['expires_at'] ?? '–' ) ); ?></strong><span><?php esc_html_e( 'Agenturlizenz gültig bis', 'frontend-rechtstexte-generator' ); ?></span></div>
	</section>

	<section class="frg-admin-card frg-connection-card">
		<div class="frg-section-heading">
			<div>
				<span class="frg-admin-eyebrow"><?php esc_html_e( 'Verbindungsdaten', 'frontend-rechtstexte-generator' ); ?></span>
				<h2><?php esc_html_e( 'JSON-Link für Kunden-Websites', 'frontend-rechtstexte-generator' ); ?></h2>
				<p><?php esc_html_e( 'Tragen Sie diesen JSON-Link zusammen mit dem jeweiligen Kundenschlüssel auf der Kunden-Website ein. Der Link ist für alle Ihre Kunden gleich, der Schlüssel ist individuell.', 'frontend-rechtstexte-generator' ); ?></p>
			</div>
		</div>
		<?php if ( $feed_endpoint ) : ?>
			<div class="frg-connection-pair">
				<label class="frg-field-label" for="frg-agency-feed-endpoint"><?php esc_html_e( 'JSON-/Feed-URL', 'frontend-rechtstexte-generator' ); ?></label>
				<span class="frg-copy-field"><input type="url" id="frg-agency-feed-endpoint" value="<?php echo esc_attr( $feed_endpoint ); ?>" readonly><button type="button" class="button" data-frg-copy-value="#frg-agency-feed-endpoint"><?php esc_html_e( 'URL kopieren', 'frontend-rechtstexte-generator' ); ?></button></span>
			</div>
		<?php else : ?>
			<div class="notice notice-warning inline"><p><?php esc_html_e( 'Die Feed-URL ist noch nicht konfiguriert. Hinterlegen Sie zuerst die Verbindung zur Master-Zentrale.', 'frontend-rechtstexte-generator' ); ?></p></div>
		<?php endif; ?>
	</section>

	<section class="frg-admin-card frg-agency-source">
		<div class="frg-section-heading">
			<div>
				<span class="frg-admin-eyebrow"><?php esc_html_e( 'Textquelle Ihrer Kunden-Websites', 'frontend-rechtstexte-generator' ); ?></span>
				<h2><?php esc_html_e( 'Master-Synchronisierung steuern', 'frontend-rechtstexte-generator' ); ?></h2>
				<p><?php esc_html_e( 'Sie entscheiden, ob Ihre Kundenschlüssel die zentral freigegebenen Master-Texte oder Ihren eigenen veröffentlichten Textstand erhalten.', 'frontend-rechtstexte-generator' ); ?></p>
			</div>
			<span class="frg-license-status <?php echo 'agency' === $text_source ? 'frg-license-status--agency' : ''; ?>"><?php echo 'agency' === $text_source ? esc_html__( 'Eigene Agenturtexte aktiv', 'frontend-rechtstexte-generator' ) : esc_html__( 'Master-Texte aktiv', 'frontend-rechtstexte-generator' ); ?></span>
		</div>
		<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
			<?php wp_nonce_field( 'frg_agency_source_action', 'frg_agency_nonce' ); ?>
			<input type="hidden" name="action" value="frg_agency_manage_customer">
			<input type="hidden" name="frg_agency_operation" value="source">
			<div class="frg-source-choices">
				<label class="frg-source-choice">
					<input type="radio" name="source_mode" value="master" <?php checked( 'master', $text_source ); ?>>
					<span><strong><?php esc_html_e( 'Master-Synchronisierung aktiv', 'frontend-rechtstexte-generator' ); ?></strong><small><?php esc_html_e( 'Ihre Kunden erhalten automatisch den jeweils freigegebenen Textstand der Master-Zentrale.', 'frontend-rechtstexte-generator' ); ?></small></span>
				</label>
				<label class="frg-source-choice">
					<input type="radio" name="source_mode" value="agency" <?php checked( 'agency', $text_source ); ?>>
					<span><strong><?php esc_html_e( 'Eigene Agenturtexte verwenden', 'frontend-rechtstexte-generator' ); ?></strong><small><?php esc_html_e( 'Die Master-Synchronisierung wird gestoppt. Der aktuell von Ihnen freigegebene Blockstand wird an Ihre Kunden verteilt.', 'frontend-rechtstexte-generator' ); ?></small></span>
				</label>
			</div>
			<p class="description"><?php esc_html_e( 'Wenn Sie eigene Texte verwenden, bearbeiten und veröffentlichen Sie diese zuerst unter „Übersicht & Textbausteine“. Kehren Sie danach hierher zurück und veröffentlichen Sie den Agenturstand erneut.', 'frontend-rechtstexte-generator' ); ?></p>
			<?php if ( ! empty( $agency['published_at'] ) ) : ?><p><strong><?php esc_html_e( 'Agenturstand zuletzt veröffentlicht', 'frontend-rechtstexte-generator' ); ?>:</strong> <?php echo esc_html( mysql2date( get_option( 'date_format' ) . ' ' . get_option( 'time_format' ), $agency['published_at'] ) ); ?></p><?php endif; ?>
			<p class="frg-agency-source__actions"><button type="submit" class="button button-primary"><?php esc_html_e( 'Auswahl speichern und Textquelle anwenden', 'frontend-rechtstexte-generator' ); ?></button><a class="button" href="<?php echo esc_url( admin_url( 'admin.php?page=frg-settings#frg-text-blocks' ) ); ?>"><?php esc_html_e( 'Textbausteine bearbeiten', 'frontend-rechtstexte-generator' ); ?></a></p>
		</form>
	</section>

	<section id="frg-agency-create" class="frg-admin-card">
		<h2><?php esc_html_e( 'Neuen Kundenschlüssel erstellen', 'frontend-rechtstexte-generator' ); ?></h2>
		<p><?php esc_html_e( 'Der Schlüssel ist für genau eine Kundenwebsite vorgesehen und übernimmt automatisch die Laufzeit Ihrer Agenturlizenz.', 'frontend-rechtstexte-generator' ); ?></p>
		<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" class="frg-license-form">
			<?php wp_nonce_field( 'frg_agency_create_action', 'frg_agency_nonce' ); ?>
			<input type="hidden" name="action" value="frg_agency_manage_customer"><input type="hidden" name="frg_agency_operation" value="create">
			<label class="frg-field-label"><?php esc_html_e( 'Kunde / Firma', 'frontend-rechtstexte-generator' ); ?><input type="text" name="customer_name" required></label>
			<label class="frg-field-label"><?php esc_html_e( 'E-Mail-Adresse', 'frontend-rechtstexte-generator' ); ?><input type="email" name="customer_email"></label>
			<p><button type="submit" class="button button-primary"><?php esc_html_e( 'Schlüssel erstellen', 'frontend-rechtstexte-generator' ); ?></button></p>
		</form>
	</section>

	<section class="frg-admin-card">
		<h2><?php esc_html_e( 'Kundenschlüssel', 'frontend-rechtstexte-generator' ); ?></h2>
		<?php if ( empty( $licenses ) ) : ?><p><?php esc_html_e( 'Noch keine Kundenschlüssel vorhanden.', 'frontend-rechtstexte-generator' ); ?></p><?php else : ?>
			<div class="frg-table-scroll"><table class="widefat striped"><thead><tr><th><?php esc_html_e( 'Kunde', 'frontend-rechtstexte-generator' ); ?></th><th><?php esc_html_e( 'Verbindungsdaten', 'frontend-rechtstexte-generator' ); ?></th><th><?php esc_html_e( 'Website', 'frontend-rechtstexte-generator' ); ?></th><th><?php esc_html_e( 'Status', 'frontend-rechtstexte-generator' ); ?></th><th><?php esc_html_e( 'Aktion', 'frontend-rechtstexte-generator' ); ?></th></tr></thead><tbody>
			<?php foreach ( $licenses as $license ) : ?><tr><td><strong><?php echo esc_html( $license['customer_name'] ?? '' ); ?></strong><br><small><?php echo esc_html( $license['customer_email'] ?? '' ); ?></small></td><td class="frg-table-connection"><span class="frg-connection-label"><?php esc_html_e( 'Lizenzschlüssel', 'frontend-rechtstexte-generator' ); ?></span><span class="frg-copy-field"><input type="text" id="frg-agency-key-<?php echo esc_attr( (string) $license['id'] ); ?>" value="<?php echo esc_attr( $license['license_key'] ); ?>" readonly><button type="button" class="button" data-frg-copy-value="#frg-agency-key-<?php echo esc_attr( (string) $license['id'] ); ?>"><?php esc_html_e( 'Kopieren', 'frontend-rechtstexte-generator' ); ?></button></span><?php if ( $feed_endpoint ) : ?><span class="frg-connection-label"><?php esc_html_e( 'JSON-/Feed-URL', 'frontend-rechtstexte-generator' ); ?></span><span class="frg-copy-field"><input type="url" id="frg-agency-feed-<?php echo esc_attr( (string) $license['id'] ); ?>" value="<?php echo esc_attr( $feed_endpoint ); ?>" readonly><button type="button" class="button" data-frg-copy-value="#frg-agency-feed-<?php echo esc_attr( (string) $license['id'] ); ?>"><?php esc_html_e( 'Kopieren', 'frontend-rechtstexte-generator' ); ?></button></span><?php endif; ?></td><td><?php echo ! empty( $license['site_url'] ) ? '<a href="' . esc_url( $license['site_url'] ) . '" target="_blank" rel="noopener noreferrer">' . esc_html( $license['site_url'] ) . '</a>' : esc_html__( 'Noch nicht verbunden', 'frontend-rechtstexte-generator' ); ?></td><td><?php echo esc_html( $license['status'] ?? '' ); ?></td><td><form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>"><?php wp_nonce_field( 'frg_agency_status_action', 'frg_agency_nonce' ); ?><input type="hidden" name="action" value="frg_agency_manage_customer"><input type="hidden" name="frg_agency_operation" value="status"><input type="hidden" name="license_id" value="<?php echo esc_attr( (string) $license['id'] ); ?>"><input type="hidden" name="license_status" value="<?php echo 'blocked' === ( $license['status'] ?? '' ) ? 'active' : 'blocked'; ?>"><button type="submit" class="button"><?php echo 'blocked' === ( $license['status'] ?? '' ) ? esc_html__( 'Freigeben', 'frontend-rechtstexte-generator' ) : esc_html__( 'Sperren', 'frontend-rechtstexte-generator' ); ?></button></form></td></tr><?php endforeach; ?>
			</tbody></table></div>
		<?php endif; ?>
	</section>
	<p data-frg-copy-value-feedback aria-live="polite"></p>
</div>

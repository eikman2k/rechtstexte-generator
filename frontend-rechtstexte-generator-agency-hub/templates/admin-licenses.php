<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$status_labels = array(
	'active'  => __( 'Aktiv', 'frontend-rechtstexte-generator' ),
	'expired' => __( 'Abgelaufen', 'frontend-rechtstexte-generator' ),
	'blocked' => __( 'Gesperrt', 'frontend-rechtstexte-generator' ),
);
?>
<div class="wrap frg-admin frg-license-admin">
	<div class="frg-admin-hero">
		<div>
			<span class="frg-admin-eyebrow"><?php esc_html_e( 'Master-Zentrale und eigene Agentur', 'frontend-rechtstexte-generator' ); ?></span>
			<h1><?php esc_html_e( 'Kunden und Agenturen verwalten', 'frontend-rechtstexte-generator' ); ?></h1>
			<p><?php esc_html_e( 'Binden Sie eigene Kunden-Websites direkt an Ihren Master an oder vergeben Sie Agenturzugänge mit einem begrenzten Kundenkontingent.', 'frontend-rechtstexte-generator' ); ?></p>
		</div>
		<div class="frg-admin-hero__actions">
			<a class="button button-hero" href="<?php echo esc_url( admin_url( 'admin.php?page=frg-settings#frg-block-feed' ) ); ?>"><?php esc_html_e( 'Zur Textverteilung', 'frontend-rechtstexte-generator' ); ?></a>
			<a class="button button-primary button-hero" href="#frg-create-license"><?php esc_html_e( 'Kunde oder Agentur anlegen', 'frontend-rechtstexte-generator' ); ?></a>
		</div>
	</div>

	<?php settings_errors( 'frg_license_messages' ); ?>

	<?php if ( 'hub' !== ( $settings['block_feed_mode'] ?? 'off' ) ) : ?>
		<div class="notice notice-warning inline"><p><?php esc_html_e( 'Diese Website ist noch nicht als Textbaustein-Zentrale eingerichtet. Aktivieren Sie unter Textverteilung die Rolle „Zentrale“, bevor Kundenseiten synchronisieren.', 'frontend-rechtstexte-generator' ); ?></p></div>
	<?php endif; ?>

	<section class="frg-admin-card frg-connection-card">
		<div>
			<span class="frg-admin-eyebrow"><?php esc_html_e( 'Verbindungsdaten', 'frontend-rechtstexte-generator' ); ?></span>
			<h2><?php esc_html_e( 'JSON-Link der Master-Zentrale', 'frontend-rechtstexte-generator' ); ?></h2>
			<p><?php esc_html_e( 'Diese URL wird auf allen verbundenen Websites verwendet. Zusätzlich benötigt jede Website ihren eigenen Lizenzschlüssel.', 'frontend-rechtstexte-generator' ); ?></p>
		</div>
		<div class="frg-connection-pair">
			<label class="frg-field-label" for="frg-master-feed-endpoint"><?php esc_html_e( 'JSON-/Feed-URL', 'frontend-rechtstexte-generator' ); ?></label>
			<span class="frg-copy-field"><input type="url" id="frg-master-feed-endpoint" value="<?php echo esc_attr( $feed_endpoint ); ?>" readonly><button type="button" class="button" data-frg-copy-value="#frg-master-feed-endpoint"><?php esc_html_e( 'URL kopieren', 'frontend-rechtstexte-generator' ); ?></button></span>
		</div>
	</section>

	<section class="frg-master-modes" aria-label="<?php echo esc_attr__( 'Betriebsarten der Master-Zentrale', 'frontend-rechtstexte-generator' ); ?>">
		<div class="frg-master-mode frg-master-mode--primary">
			<span><?php esc_html_e( 'Direkte Betreuung', 'frontend-rechtstexte-generator' ); ?></span>
			<h2><?php esc_html_e( 'Master als Agentur', 'frontend-rechtstexte-generator' ); ?></h2>
			<p><?php esc_html_e( 'Legen Sie den Lizenztyp „Eigene Kunden-Website“ an. Die Website erhält direkt Ihre Master-Texte und wird von Ihnen in dieser Zentrale verwaltet.', 'frontend-rechtstexte-generator' ); ?></p>
		</div>
		<div class="frg-master-mode">
			<span><?php esc_html_e( 'Weitergabe', 'frontend-rechtstexte-generator' ); ?></span>
			<h2><?php esc_html_e( 'Externe Agentur anbinden', 'frontend-rechtstexte-generator' ); ?></h2>
			<p><?php esc_html_e( 'Legen Sie den Lizenztyp „Externe Agentur“ an. Die Agentur verwaltet eigene Kundenschlüssel und kann Master-Texte oder einen eigenen Textstand verwenden.', 'frontend-rechtstexte-generator' ); ?></p>
		</div>
	</section>

	<section class="frg-license-stats" aria-label="<?php echo esc_attr__( 'Lizenzübersicht', 'frontend-rechtstexte-generator' ); ?>">
		<div><strong><?php echo esc_html( (string) $license_summary['total'] ); ?></strong><span><?php esc_html_e( 'Lizenzen gesamt', 'frontend-rechtstexte-generator' ); ?></span></div>
		<div class="is-active"><strong><?php echo esc_html( (string) $license_summary['active'] ); ?></strong><span><?php esc_html_e( 'Aktiv', 'frontend-rechtstexte-generator' ); ?></span></div>
		<div class="is-expired"><strong><?php echo esc_html( (string) $license_summary['expired'] ); ?></strong><span><?php esc_html_e( 'Abgelaufen', 'frontend-rechtstexte-generator' ); ?></span></div>
		<div class="is-blocked"><strong><?php echo esc_html( (string) $license_summary['blocked'] ); ?></strong><span><?php esc_html_e( 'Gesperrt', 'frontend-rechtstexte-generator' ); ?></span></div>
		<div><strong><?php echo esc_html( (string) $license_summary['active_sites'] ); ?></strong><span><?php esc_html_e( 'Verbundene Websites', 'frontend-rechtstexte-generator' ); ?></span></div>
	</section>

	<section id="frg-create-license" class="frg-admin-card">
		<div class="frg-section-heading">
			<div><span class="frg-admin-eyebrow"><?php esc_html_e( 'Neue Rechnung / neuer Zugang', 'frontend-rechtstexte-generator' ); ?></span><h2><?php esc_html_e( 'Kunde oder Agentur anlegen', 'frontend-rechtstexte-generator' ); ?></h2><p><?php esc_html_e( 'Eigene Kunden erhalten direkt einen Website-Schlüssel für Ihre Master-Texte. Externe Agenturen erhalten einen Hauptschlüssel und erstellen daraus eigene, zentral begrenzte Kundenschlüssel.', 'frontend-rechtstexte-generator' ); ?></p></div>
		</div>
		<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" class="frg-license-form frg-license-form--create">
			<?php wp_nonce_field( 'frg_license_create_action', 'frg_license_nonce' ); ?>
			<input type="hidden" name="action" value="frg_manage_license"><input type="hidden" name="frg_license_operation" value="create">
			<fieldset class="frg-license-type-field">
				<legend><?php esc_html_e( 'Wen möchten Sie anbinden?', 'frontend-rechtstexte-generator' ); ?></legend>
				<div class="frg-license-type-grid">
					<label class="frg-license-type-card">
						<input type="radio" name="license_type" value="site" checked>
						<span><strong><?php esc_html_e( 'Eigene Kunden-Website', 'frontend-rechtstexte-generator' ); ?></strong><small><?php esc_html_e( 'Sie betreuen die Website direkt. Sie erhält Ihren freigegebenen Master-Textstand.', 'frontend-rechtstexte-generator' ); ?></small></span>
					</label>
					<label class="frg-license-type-card">
						<input type="radio" name="license_type" value="agency">
						<span><strong><?php esc_html_e( 'Externe Agentur', 'frontend-rechtstexte-generator' ); ?></strong><small><?php esc_html_e( 'Die Agentur erhält ein Kontingent und erstellt daraus Schlüssel für ihre Kunden-Websites.', 'frontend-rechtstexte-generator' ); ?></small></span>
					</label>
				</div>
			</fieldset>
			<label class="frg-field-label frg-license-create__customer"><?php esc_html_e( 'Kunde / Firma', 'frontend-rechtstexte-generator' ); ?><input type="text" name="customer_name" required></label>
			<label class="frg-field-label frg-license-create__email"><?php esc_html_e( 'E-Mail-Adresse', 'frontend-rechtstexte-generator' ); ?><input type="email" name="customer_email"></label>
			<label class="frg-field-label frg-license-create__limit"><?php esc_html_e( 'Website-/Kundenlimit', 'frontend-rechtstexte-generator' ); ?><input type="number" name="max_sites" min="1" max="999" value="1" required><small><?php esc_html_e( 'Bei Direktkunden meist 1; bei Agenturen die Zahl erlaubter Kundenschlüssel.', 'frontend-rechtstexte-generator' ); ?></small></label>
			<label class="frg-field-label frg-license-create__expiry"><?php esc_html_e( 'Gültig bis', 'frontend-rechtstexte-generator' ); ?><input type="date" name="expires_at" value="<?php echo esc_attr( $default_expiry ); ?>" required></label>
			<label class="frg-field-label frg-license-create__notes"><?php esc_html_e( 'Interne Notiz', 'frontend-rechtstexte-generator' ); ?><textarea name="notes" rows="3" placeholder="<?php echo esc_attr__( 'Optional, nur intern sichtbar', 'frontend-rechtstexte-generator' ); ?>"></textarea></label>
			<p class="frg-license-create__submit"><button type="submit" class="frg-primary-action"><?php esc_html_e( 'Zugang anlegen', 'frontend-rechtstexte-generator' ); ?></button></p>
		</form>
	</section>

	<div class="frg-license-list">
		<?php if ( empty( $licenses ) ) : ?>
			<div class="frg-admin-card"><h2><?php esc_html_e( 'Noch keine Lizenzen', 'frontend-rechtstexte-generator' ); ?></h2><p><?php esc_html_e( 'Legen Sie die erste Lizenz an. Preise und Rechnungen verwalten Sie weiterhin außerhalb des Plugins.', 'frontend-rechtstexte-generator' ); ?></p></div>
		<?php endif; ?>

		<?php foreach ( $licenses as $license ) : ?>
			<?php
			$effective_status = $license['effective_status'];
			$is_agency        = 'agency' === $license['license_type'];
			$usage_count      = $is_agency ? count( array_filter( $license['children'], static fn( array $child ): bool => 'blocked' !== $child['effective_status'] ) ) : count( $license['sites'] );
			$agency_source    = $is_agency ? FRG_Hub_Feed::get_agency_source_summary( (int) $license['id'] ) : array();
			?>
			<section class="frg-admin-card frg-license-card">
				<div class="frg-license-card__header">
					<div><span class="frg-license-status frg-license-status--<?php echo esc_attr( $effective_status ); ?>"><?php echo esc_html( $status_labels[ $effective_status ] ?? $effective_status ); ?></span> <span class="frg-license-status <?php echo $is_agency ? 'frg-license-status--agency' : 'frg-license-status--direct'; ?>"><?php echo $is_agency ? esc_html__( 'Externe Agentur', 'frontend-rechtstexte-generator' ) : esc_html__( 'Eigener Direktkunde', 'frontend-rechtstexte-generator' ); ?></span><h2><?php echo esc_html( $license['customer_name'] ); ?></h2><p><?php echo esc_html( $license['customer_email'] ?: __( 'Keine E-Mail-Adresse hinterlegt', 'frontend-rechtstexte-generator' ) ); ?></p></div>
					<div class="frg-license-usage"><strong><?php echo esc_html( $usage_count . ' / ' . (int) $license['max_sites'] ); ?></strong><span><?php echo $is_agency ? esc_html__( 'Kundenschlüssel', 'frontend-rechtstexte-generator' ) : esc_html__( 'Websites', 'frontend-rechtstexte-generator' ); ?></span></div>
				</div>

				<div class="frg-license-key-row">
					<div class="frg-connection-pair"><label class="frg-field-label" for="frg-license-key-<?php echo esc_attr( (string) $license['id'] ); ?>"><?php esc_html_e( 'Lizenzschlüssel', 'frontend-rechtstexte-generator' ); ?></label><span class="frg-copy-field"><input type="text" id="frg-license-key-<?php echo esc_attr( (string) $license['id'] ); ?>" value="<?php echo esc_attr( $license['license_key'] ); ?>" readonly><button type="button" class="button" data-frg-copy-value="#frg-license-key-<?php echo esc_attr( (string) $license['id'] ); ?>"><?php esc_html_e( 'Kopieren', 'frontend-rechtstexte-generator' ); ?></button></span></div>
					<div class="frg-connection-pair"><label class="frg-field-label" for="frg-license-feed-<?php echo esc_attr( (string) $license['id'] ); ?>"><?php esc_html_e( 'JSON-/Feed-URL', 'frontend-rechtstexte-generator' ); ?></label><span class="frg-copy-field"><input type="url" id="frg-license-feed-<?php echo esc_attr( (string) $license['id'] ); ?>" value="<?php echo esc_attr( $feed_endpoint ); ?>" readonly><button type="button" class="button" data-frg-copy-value="#frg-license-feed-<?php echo esc_attr( (string) $license['id'] ); ?>"><?php esc_html_e( 'Kopieren', 'frontend-rechtstexte-generator' ); ?></button></span></div>
				</div>
				<?php if ( $is_agency ) : ?><p><strong><?php esc_html_e( 'Textquelle der Agentur-Kunden', 'frontend-rechtstexte-generator' ); ?>:</strong> <?php echo 'agency' === ( $agency_source['mode'] ?? 'master' ) ? esc_html__( 'Eigener veröffentlichter Agenturstand', 'frontend-rechtstexte-generator' ) : esc_html__( 'Freigegebener Master-Stand', 'frontend-rechtstexte-generator' ); ?><?php if ( ! empty( $agency_source['published_at'] ) ) : ?> · <?php echo esc_html( $agency_source['published_at'] ); ?><?php endif; ?></p><?php endif; ?>

				<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" class="frg-license-form frg-license-form--edit">
					<?php wp_nonce_field( 'frg_license_update_action', 'frg_license_nonce' ); ?>
					<input type="hidden" name="action" value="frg_manage_license"><input type="hidden" name="frg_license_operation" value="update"><input type="hidden" name="license_id" value="<?php echo esc_attr( (string) $license['id'] ); ?>">
					<label class="frg-field-label"><?php esc_html_e( 'Kunde / Firma', 'frontend-rechtstexte-generator' ); ?><input type="text" name="customer_name" value="<?php echo esc_attr( $license['customer_name'] ); ?>" required></label>
					<label class="frg-field-label"><?php esc_html_e( 'E-Mail-Adresse', 'frontend-rechtstexte-generator' ); ?><input type="email" name="customer_email" value="<?php echo esc_attr( $license['customer_email'] ); ?>"></label>
					<label class="frg-field-label"><?php echo $is_agency ? esc_html__( 'Kundenlimit', 'frontend-rechtstexte-generator' ) : esc_html__( 'Website-Limit', 'frontend-rechtstexte-generator' ); ?><input type="number" name="max_sites" min="1" max="999" value="<?php echo esc_attr( (string) $license['max_sites'] ); ?>" required></label>
					<label class="frg-field-label"><?php esc_html_e( 'Gültig bis', 'frontend-rechtstexte-generator' ); ?><input type="date" name="expires_at" value="<?php echo esc_attr( $license['expires_at'] ); ?>" required></label>
					<label class="frg-field-label frg-license-form__wide"><?php esc_html_e( 'Interne Notiz', 'frontend-rechtstexte-generator' ); ?><textarea name="notes" rows="2"><?php echo esc_textarea( $license['notes'] ); ?></textarea></label>
					<p class="frg-license-form__wide"><button type="submit" class="button button-primary"><?php esc_html_e( 'Lizenzdaten speichern', 'frontend-rechtstexte-generator' ); ?></button></p>
				</form>

				<div class="frg-license-actions">
					<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>"><?php wp_nonce_field( 'frg_license_extend_action', 'frg_license_nonce' ); ?><input type="hidden" name="action" value="frg_manage_license"><input type="hidden" name="frg_license_operation" value="extend"><input type="hidden" name="license_id" value="<?php echo esc_attr( (string) $license['id'] ); ?>"><button type="submit" class="button"><?php esc_html_e( 'Um ein Jahr verlängern', 'frontend-rechtstexte-generator' ); ?></button></form>
					<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>"><?php wp_nonce_field( 'frg_license_status_action', 'frg_license_nonce' ); ?><input type="hidden" name="action" value="frg_manage_license"><input type="hidden" name="frg_license_operation" value="status"><input type="hidden" name="license_id" value="<?php echo esc_attr( (string) $license['id'] ); ?>"><input type="hidden" name="license_status" value="<?php echo 'blocked' === $license['status'] ? 'active' : 'blocked'; ?>"><button type="submit" class="button <?php echo 'blocked' === $license['status'] ? '' : 'button-link-delete'; ?>"><?php echo 'blocked' === $license['status'] ? esc_html__( 'Lizenz freigeben', 'frontend-rechtstexte-generator' ) : esc_html__( 'Lizenz sperren', 'frontend-rechtstexte-generator' ); ?></button></form>
				</div>

				<?php if ( $is_agency ) : ?><div class="frg-license-sites">
					<h3><?php esc_html_e( 'Von der Agentur erstellte Kundenschlüssel', 'frontend-rechtstexte-generator' ); ?></h3>
					<?php if ( empty( $license['children'] ) ) : ?><p><?php esc_html_e( 'Die Agentur hat noch keine Kundenschlüssel erstellt.', 'frontend-rechtstexte-generator' ); ?></p><?php else : ?><div class="frg-table-scroll"><table class="widefat striped"><thead><tr><th><?php esc_html_e( 'Kunde', 'frontend-rechtstexte-generator' ); ?></th><th><?php esc_html_e( 'Verbindungsdaten', 'frontend-rechtstexte-generator' ); ?></th><th><?php esc_html_e( 'Website', 'frontend-rechtstexte-generator' ); ?></th><th><?php esc_html_e( 'Status', 'frontend-rechtstexte-generator' ); ?></th><th><?php esc_html_e( 'Letzter Abruf', 'frontend-rechtstexte-generator' ); ?></th></tr></thead><tbody><?php foreach ( $license['children'] as $child ) : ?><?php $child_site = $child['sites'][0] ?? array(); ?><tr><td><?php echo esc_html( $child['customer_name'] ); ?></td><td class="frg-table-connection"><span class="frg-connection-label"><?php esc_html_e( 'Lizenzschlüssel', 'frontend-rechtstexte-generator' ); ?></span><span class="frg-copy-field"><input type="text" id="frg-child-key-<?php echo esc_attr( (string) $child['id'] ); ?>" value="<?php echo esc_attr( $child['license_key'] ); ?>" readonly><button type="button" class="button" data-frg-copy-value="#frg-child-key-<?php echo esc_attr( (string) $child['id'] ); ?>"><?php esc_html_e( 'Kopieren', 'frontend-rechtstexte-generator' ); ?></button></span><span class="frg-connection-label"><?php esc_html_e( 'JSON-/Feed-URL', 'frontend-rechtstexte-generator' ); ?></span><span class="frg-copy-field"><input type="url" id="frg-child-feed-<?php echo esc_attr( (string) $child['id'] ); ?>" value="<?php echo esc_attr( $feed_endpoint ); ?>" readonly><button type="button" class="button" data-frg-copy-value="#frg-child-feed-<?php echo esc_attr( (string) $child['id'] ); ?>"><?php esc_html_e( 'Kopieren', 'frontend-rechtstexte-generator' ); ?></button></span></td><td><?php echo esc_html( $child_site['site_url'] ?? __( 'Noch nicht verbunden', 'frontend-rechtstexte-generator' ) ); ?></td><td><?php echo esc_html( $status_labels[ $child['effective_status'] ] ?? $child['effective_status'] ); ?></td><td><?php echo esc_html( $child_site['last_seen_at'] ?? '–' ); ?></td></tr><?php endforeach; ?></tbody></table></div><?php endif; ?>
				</div><?php else : ?><div class="frg-license-sites">
					<h3><?php esc_html_e( 'Verbundene Websites', 'frontend-rechtstexte-generator' ); ?></h3>
					<?php if ( empty( $license['sites'] ) ) : ?><p><?php esc_html_e( 'Noch keine Website verbunden. Der erste Abruf mit diesem Schlüssel belegt automatisch einen Platz.', 'frontend-rechtstexte-generator' ); ?></p><?php else : ?>
						<div class="frg-table-scroll"><table class="widefat striped"><thead><tr><th><?php esc_html_e( 'Website', 'frontend-rechtstexte-generator' ); ?></th><th><?php esc_html_e( 'Plugin', 'frontend-rechtstexte-generator' ); ?></th><th><?php esc_html_e( 'Aktiviert', 'frontend-rechtstexte-generator' ); ?></th><th><?php esc_html_e( 'Zuletzt verbunden', 'frontend-rechtstexte-generator' ); ?></th><th><?php esc_html_e( 'Aktion', 'frontend-rechtstexte-generator' ); ?></th></tr></thead><tbody>
						<?php foreach ( $license['sites'] as $site ) : ?><tr><td><a href="<?php echo esc_url( $site['site_url'] ); ?>" target="_blank" rel="noopener noreferrer"><?php echo esc_html( $site['site_url'] ); ?></a></td><td><?php echo esc_html( $site['plugin_version'] ?: '–' ); ?></td><td><?php echo esc_html( $site['activated_at'] ); ?></td><td><?php echo esc_html( $site['last_seen_at'] ); ?></td><td><form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>"><?php wp_nonce_field( 'frg_license_release_site_action', 'frg_license_nonce' ); ?><input type="hidden" name="action" value="frg_manage_license"><input type="hidden" name="frg_license_operation" value="release_site"><input type="hidden" name="license_id" value="<?php echo esc_attr( (string) $license['id'] ); ?>"><input type="hidden" name="site_id" value="<?php echo esc_attr( (string) $site['id'] ); ?>"><button type="submit" class="button-link-delete"><?php esc_html_e( 'Domain freigeben', 'frontend-rechtstexte-generator' ); ?></button></form></td></tr><?php endforeach; ?>
						</tbody></table></div>
					<?php endif; ?>
				</div><?php endif; ?>
			</section>
		<?php endforeach; ?>
	</div>
	<p class="frg-copy-feedback" data-frg-copy-value-feedback aria-live="polite"></p>
</div>

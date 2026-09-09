<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$generated_impressum = $profile['data']['generated_impressum_html'] ?? ( $profile ? $this->generator->generate_impressum( $profile['data'] ) : '' );
$generated_privacy   = $profile['data']['generated_privacy_html'] ?? ( $profile ? $this->generator->generate_privacy_policy( $profile['data'] ) : '' );
$generated_impressum_export = $profile['data']['generated_impressum_export_html'] ?? ( $generated_impressum ? $this->generator->build_exportable_document_html( $generated_impressum ) : '' );
$generated_privacy_export   = $profile['data']['generated_privacy_export_html'] ?? ( $generated_privacy ? $this->generator->build_exportable_document_html( $generated_privacy ) : '' );
$module_meta         = $this->generator->get_module_meta();
$review_counts       = array(
	'legal_reviewed' => 0,
	'editorial'      => 0,
	'draft'          => 0,
	'review_needed'  => 0,
	'overdue'        => 0,
	'open'           => 0,
);
$status_labels = array(
	'review_needed'      => __( 'Prüfung offen', 'frontend-rechtstexte-generator' ),
	'draft'              => __( 'Entwurf bereit', 'frontend-rechtstexte-generator' ),
	'editorial_approved' => __( 'Im Einsatz', 'frontend-rechtstexte-generator' ),
	'legal_reviewed'     => __( 'Juristisch geprüft', 'frontend-rechtstexte-generator' ),
);
$model_options = array(
	'gpt-5.6-terra' => __( 'GPT-5.6 Terra – empfohlen, ausgewogen', 'frontend-rechtstexte-generator' ),
	'gpt-5.6-sol'   => __( 'GPT-5.6 Sol – hohe Textqualität', 'frontend-rechtstexte-generator' ),
	'gpt-6-astra'   => __( 'GPT-6 Astra – höchste Qualität, deutlich teurer', 'frontend-rechtstexte-generator' ),
	'gpt-5.6-luna'  => __( 'GPT-5.6 Luna – kostengünstig', 'frontend-rechtstexte-generator' ),
);
$selected_model = sanitize_text_field( $settings['openai_model'] ?? 'gpt-5.6-terra' );
$feed_mode      = sanitize_key( $settings['block_feed_mode'] ?? 'off' );
$today = current_time( 'Y-m-d' );
foreach ( $block_registry as $review_block ) {
	$review_status = $review_block['status'] ?? 'review_needed';
	if ( 'legal_reviewed' === $review_status ) {
		++$review_counts['legal_reviewed'];
	} else {
		++$review_counts['open'];
	}
	if ( 'editorial_approved' === $review_status ) {
		++$review_counts['editorial'];
	} elseif ( 'draft' === $review_status ) {
		++$review_counts['draft'];
	} elseif ( 'review_needed' === $review_status ) {
		++$review_counts['review_needed'];
	}
	if ( ! empty( $review_block['review_due_at'] ) && $review_block['review_due_at'] < $today ) {
		++$review_counts['overdue'];
	}
}
?>
<div class="wrap frg-admin">
	<div class="frg-admin-hero">
		<div>
			<span class="frg-admin-eyebrow"><?php esc_html_e( 'Zentrale Verwaltung', 'frontend-rechtstexte-generator' ); ?></span>
			<h1><?php esc_html_e( 'Rechtstexte verständlich verwalten', 'frontend-rechtstexte-generator' ); ?></h1>
			<p><?php esc_html_e( 'Daten erfassen, Textbausteine überarbeiten, Änderungen kontrolliert veröffentlichen und Prüfungen nachvollziehbar dokumentieren.', 'frontend-rechtstexte-generator' ); ?></p>
		</div>
		<div class="frg-admin-hero__actions">
			<a class="button button-primary button-hero" href="<?php echo esc_url( admin_url( 'options-general.php?page=frg-wizard' ) ); ?>"><?php esc_html_e( 'Kundendaten erfassen', 'frontend-rechtstexte-generator' ); ?></a>
			<a class="button button-hero" href="#frg-text-blocks"><?php esc_html_e( 'Textbausteine bearbeiten', 'frontend-rechtstexte-generator' ); ?></a>
		</div>
	</div>
	<?php settings_errors( 'frg_messages' ); ?>
	<nav class="frg-admin-nav" aria-label="<?php echo esc_attr__( 'Bereiche dieser Einstellungsseite', 'frontend-rechtstexte-generator' ); ?>">
		<a href="#frg-overview"><?php esc_html_e( 'Übersicht', 'frontend-rechtstexte-generator' ); ?></a>
		<a href="#frg-text-blocks"><?php esc_html_e( 'Textbausteine', 'frontend-rechtstexte-generator' ); ?></a>
		<a href="#frg-settings"><?php esc_html_e( 'Einstellungen', 'frontend-rechtstexte-generator' ); ?></a>
		<a href="#frg-block-feed"><?php esc_html_e( 'Textverteilung', 'frontend-rechtstexte-generator' ); ?></a>
		<a href="#frg-profiles"><?php esc_html_e( 'Profile & Ausgabe', 'frontend-rechtstexte-generator' ); ?></a>
		<a href="#frg-transfer"><?php esc_html_e( 'Export & Import', 'frontend-rechtstexte-generator' ); ?></a>
	</nav>
	<div id="frg-overview" class="frg-admin-card frg-admin-card--status">
		<h2><?php esc_html_e( 'Plugin-Status', 'frontend-rechtstexte-generator' ); ?></h2>
		<p><strong><?php esc_html_e( 'Plugin-Version', 'frontend-rechtstexte-generator' ); ?>:</strong> <?php echo esc_html( FRG_VERSION ); ?></p>
		<p><strong><?php esc_html_e( 'Modulversion', 'frontend-rechtstexte-generator' ); ?>:</strong> <?php echo esc_html( $module_meta['module_version'] ?? '' ); ?></p>
		<?php if ( is_multisite() ) : ?>
			<p><strong><?php esc_html_e( 'Multisite-Modus', 'frontend-rechtstexte-generator' ); ?>:</strong> <?php echo FRG_Multisite::is_central_output_enabled() ? esc_html__( 'Zentrale Ausgabe aktiv', 'frontend-rechtstexte-generator' ) : esc_html__( 'Lokale Ausgabe je Site', 'frontend-rechtstexte-generator' ); ?></p>
			<?php if ( FRG_Multisite::is_central_output_enabled() ) : ?>
				<p><strong><?php esc_html_e( 'Master-Site', 'frontend-rechtstexte-generator' ); ?>:</strong> #<?php echo esc_html( (string) FRG_Multisite::get_source_blog_id() ); ?></p>
			<?php endif; ?>
		<?php endif; ?>
	</div>
	<details class="frg-admin-card frg-admin-disclosure">
		<summary><strong><?php esc_html_e( 'Shortcodes anzeigen', 'frontend-rechtstexte-generator' ); ?></strong><span><?php esc_html_e( 'Für Seiten und Elementor', 'frontend-rechtstexte-generator' ); ?></span></summary>
		<div class="frg-admin-disclosure__body">
		<h2><?php esc_html_e( 'Verfügbare Shortcodes', 'frontend-rechtstexte-generator' ); ?></h2>
		<p><?php esc_html_e( 'Diese Shortcodes können in Seiten, Beiträgen oder Elementor-Widgets eingebunden werden.', 'frontend-rechtstexte-generator' ); ?></p>
		<table class="widefat striped frg-shortcode-table">
			<thead>
				<tr>
					<th><?php esc_html_e( 'Shortcode', 'frontend-rechtstexte-generator' ); ?></th>
					<th><?php esc_html_e( 'Funktion', 'frontend-rechtstexte-generator' ); ?></th>
				</tr>
			</thead>
			<tbody>
				<tr>
					<td><code>[frg_rechtstexte_wizard]</code></td>
					<td><?php esc_html_e( 'Zeigt den Frontend-Wizard zum Erfassen und Generieren der Rechtstexte.', 'frontend-rechtstexte-generator' ); ?></td>
				</tr>
				<tr>
					<td><code>[frg_impressum]</code></td>
					<td><?php esc_html_e( 'Gibt das generierte Impressum aus.', 'frontend-rechtstexte-generator' ); ?></td>
				</tr>
				<tr>
					<td><code>[frg_datenschutz]</code></td>
					<td><?php esc_html_e( 'Gibt die generierte Datenschutzerklärung aus.', 'frontend-rechtstexte-generator' ); ?></td>
				</tr>
				<tr>
					<td><code>[frg_last_updated]</code></td>
					<td><?php esc_html_e( 'Gibt das letzte Aktualisierungsdatum des gespeicherten Profils aus.', 'frontend-rechtstexte-generator' ); ?></td>
				</tr>
			</tbody>
		</table>
		</div>
	</details>
	<?php if ( FRG_Multisite::is_central_output_enabled() && ! FRG_Multisite::is_source_blog() ) : ?>
		<div class="notice notice-info">
			<p>
				<?php
				echo esc_html(
					sprintf(
						/* translators: %d: source blog id */
						__( 'Die Multisite-Zentralausgabe ist aktiv. Die Shortcodes dieser Site verwenden die Inhalte der Master-Site #%d.', 'frontend-rechtstexte-generator' ),
						FRG_Multisite::get_source_blog_id()
					)
				);
				?>
			</p>
		</div>
	<?php endif; ?>

	<section class="frg-status-grid" aria-label="<?php echo esc_attr__( 'Status der Textbausteine', 'frontend-rechtstexte-generator' ); ?>">
		<a href="#frg-text-blocks" class="frg-status-card frg-status-card--attention" data-frg-status-jump="review_needed"><strong><?php echo esc_html( (string) $review_counts['review_needed'] ); ?></strong><span><?php esc_html_e( 'Prüfung offen', 'frontend-rechtstexte-generator' ); ?></span><small><?php esc_html_e( 'Jetzt anzeigen', 'frontend-rechtstexte-generator' ); ?></small></a>
		<a href="#frg-text-blocks" class="frg-status-card frg-status-card--draft" data-frg-status-jump="draft"><strong><?php echo esc_html( (string) $review_counts['draft'] ); ?></strong><span><?php esc_html_e( 'Entwürfe bereit', 'frontend-rechtstexte-generator' ); ?></span><small><?php esc_html_e( 'Jetzt anzeigen', 'frontend-rechtstexte-generator' ); ?></small></a>
		<a href="#frg-text-blocks" class="frg-status-card frg-status-card--live" data-frg-status-jump="editorial_approved"><strong><?php echo esc_html( (string) $review_counts['editorial'] ); ?></strong><span><?php esc_html_e( 'Eigene Texte im Einsatz', 'frontend-rechtstexte-generator' ); ?></span><small><?php esc_html_e( 'Jetzt anzeigen', 'frontend-rechtstexte-generator' ); ?></small></a>
		<a href="#frg-text-blocks" class="frg-status-card frg-status-card--reviewed" data-frg-status-jump="legal_reviewed"><strong><?php echo esc_html( (string) $review_counts['legal_reviewed'] ); ?></strong><span><?php esc_html_e( 'Juristisch geprüft', 'frontend-rechtstexte-generator' ); ?></span><small><?php esc_html_e( 'Jetzt anzeigen', 'frontend-rechtstexte-generator' ); ?></small></a>
		<a href="#frg-text-blocks" class="frg-status-card frg-status-card--overdue" data-frg-status-jump="overdue"><strong><?php echo esc_html( (string) $review_counts['overdue'] ); ?></strong><span><?php esc_html_e( 'Prüfung überfällig', 'frontend-rechtstexte-generator' ); ?></span><small><?php esc_html_e( 'Jetzt anzeigen', 'frontend-rechtstexte-generator' ); ?></small></a>
	</section>

	<div class="frg-admin-card frg-module-context">
		<div>
			<h2><?php esc_html_e( 'Stand der Textmodule', 'frontend-rechtstexte-generator' ); ?></h2>
			<p><?php echo esc_html( $module_meta['notice'] ?? '' ); ?></p>
		</div>
		<dl>
			<div><dt><?php esc_html_e( 'Modulversion', 'frontend-rechtstexte-generator' ); ?></dt><dd><?php echo esc_html( $module_meta['module_version'] ?? '' ); ?></dd></div>
			<div><dt><?php esc_html_e( 'Technisch aktualisiert', 'frontend-rechtstexte-generator' ); ?></dt><dd><?php echo esc_html( $module_meta['content_updated_at'] ?? '' ); ?></dd></div>
			<div><dt><?php esc_html_e( 'Hinterlegte Rechtsgrundlagen', 'frontend-rechtstexte-generator' ); ?></dt><dd><?php echo esc_html( implode( ', ', $module_meta['legal_basis'] ?? array() ) ); ?></dd></div>
		</dl>
	</div>

	<form method="post" id="frg-text-blocks" class="frg-admin-card frg-block-manager">
		<?php wp_nonce_field( 'frg_save_block_registry_action', 'frg_save_block_registry_nonce' ); ?>
		<div class="frg-section-heading">
			<div>
				<span class="frg-admin-eyebrow"><?php esc_html_e( 'Geführter Arbeitsbereich', 'frontend-rechtstexte-generator' ); ?></span>
				<h2><?php esc_html_e( 'Textbausteine aktualisieren', 'frontend-rechtstexte-generator' ); ?></h2>
				<p><?php esc_html_e( 'Bearbeiten Sie immer nur den betroffenen Baustein. Ein KI-Entwurf verändert die veröffentlichte Ausgabe erst nach Ihrer ausdrücklichen Freigabe.', 'frontend-rechtstexte-generator' ); ?></p>
			</div>
			<span class="frg-api-state <?php echo ! empty( $settings['openai_api_key'] ) ? 'is-ready' : 'is-missing'; ?>">
				<?php echo ! empty( $settings['openai_api_key'] ) ? esc_html__( 'KI-Verbindung eingerichtet', 'frontend-rechtstexte-generator' ) : esc_html__( 'KI-Verbindung nicht eingerichtet', 'frontend-rechtstexte-generator' ); ?>
			</span>
		</div>
		<ol class="frg-workflow">
			<li><span>1</span><div><strong><?php esc_html_e( 'Änderung beschreiben', 'frontend-rechtstexte-generator' ); ?></strong><small><?php esc_html_e( 'Neue Regel, Quelle oder gewünschte Anpassung notieren.', 'frontend-rechtstexte-generator' ); ?></small></div></li>
			<li><span>2</span><div><strong><?php esc_html_e( 'Entwurf erstellen', 'frontend-rechtstexte-generator' ); ?></strong><small><?php esc_html_e( 'KI verwenden oder Text selbst bearbeiten.', 'frontend-rechtstexte-generator' ); ?></small></div></li>
			<li><span>3</span><div><strong><?php esc_html_e( 'Prüfen und veröffentlichen', 'frontend-rechtstexte-generator' ); ?></strong><small><?php esc_html_e( 'Vorschau vergleichen und bewusst live schalten.', 'frontend-rechtstexte-generator' ); ?></small></div></li>
			<li><span>4</span><div><strong><?php esc_html_e( 'Prüfung dokumentieren', 'frontend-rechtstexte-generator' ); ?></strong><small><?php esc_html_e( 'Nur echte fachliche oder juristische Prüfungen eintragen.', 'frontend-rechtstexte-generator' ); ?></small></div></li>
		</ol>
		<div class="frg-guidance-warning"><strong><?php esc_html_e( 'Wichtig zur Aktualität:', 'frontend-rechtstexte-generator' ); ?></strong> <?php esc_html_e( 'Die KI überwacht keine Gesetze und bestätigt keine Rechtssicherheit. Tragen Sie bei einer Änderung die verlässliche Quelle und den konkreten Anpassungsauftrag im betroffenen Baustein ein. Der erzeugte Text bleibt bis zu Ihrer Freigabe ein unveröffentlichter Entwurf.', 'frontend-rechtstexte-generator' ); ?></div>
		<?php if ( 'client' === $feed_mode ) : ?><div class="frg-guidance-warning"><strong><?php esc_html_e( 'Diese Website empfängt zentrale Texte:', 'frontend-rechtstexte-generator' ); ?></strong> <?php esc_html_e( 'Lokal veröffentlichte Blocktexte können bei der nächsten erfolgreichen Synchronisierung durch die Version der Zentrale ersetzt werden. Kundendaten aus dem Wizard bleiben davon unberührt.', 'frontend-rechtstexte-generator' ); ?></div><?php endif; ?>
		<div class="frg-block-toolbar">
			<label><span class="screen-reader-text"><?php esc_html_e( 'Textbausteine durchsuchen', 'frontend-rechtstexte-generator' ); ?></span><input type="search" placeholder="<?php echo esc_attr__( 'Baustein suchen …', 'frontend-rechtstexte-generator' ); ?>" data-frg-block-search></label>
			<label><span class="screen-reader-text"><?php esc_html_e( 'Bereich filtern', 'frontend-rechtstexte-generator' ); ?></span><select data-frg-block-area><option value=""><?php esc_html_e( 'Alle Bereiche', 'frontend-rechtstexte-generator' ); ?></option><option value="impressum"><?php esc_html_e( 'Impressum', 'frontend-rechtstexte-generator' ); ?></option><option value="privacy"><?php esc_html_e( 'Datenschutz', 'frontend-rechtstexte-generator' ); ?></option></select></label>
			<label><span class="screen-reader-text"><?php esc_html_e( 'Status filtern', 'frontend-rechtstexte-generator' ); ?></span><select data-frg-block-status-filter><option value=""><?php esc_html_e( 'Alle Status', 'frontend-rechtstexte-generator' ); ?></option><?php foreach ( $status_labels as $status_key => $status_label ) : ?><option value="<?php echo esc_attr( $status_key ); ?>"><?php echo esc_html( $status_label ); ?></option><?php endforeach; ?><option value="overdue"><?php esc_html_e( 'Prüfung überfällig', 'frontend-rechtstexte-generator' ); ?></option></select></label>
			<button type="button" class="button" data-frg-expand-visible><?php esc_html_e( 'Treffer öffnen', 'frontend-rechtstexte-generator' ); ?></button>
			<span data-frg-filter-count></span>
		</div>
		<div class="frg-block-accordion">
			<?php foreach ( $block_registry as $block ) : ?>
				<?php
				$placeholders = $this->generator->get_block_placeholder_details( $block['key'] );
					$block_status = $block['status'] ?? 'review_needed';
					$area_label   = 'impressum' === $block['area'] ? __( 'Impressum', 'frontend-rechtstexte-generator' ) : __( 'Datenschutz', 'frontend-rechtstexte-generator' );
					$is_overdue   = ! empty( $block['review_due_at'] ) && $block['review_due_at'] < $today;
					$active_quality_flags = $this->generator->inspect_text_quality( $this->generator->get_distributable_block_text( $block['key'] ) );
					$draft_quality_flags = ! empty( $block['draft_text'] ) ? $this->generator->inspect_text_quality( (string) $block['draft_text'] ) : array();
				?>
				<details class="frg-block-card" data-frg-block="<?php echo esc_attr( $block['key'] ); ?>" data-frg-title="<?php echo esc_attr( $block['title'] . ' ' . $block['key'] ); ?>" data-frg-area="<?php echo esc_attr( $block['area'] ); ?>" data-frg-status="<?php echo esc_attr( $block_status ); ?>" data-frg-overdue="<?php echo $is_overdue ? '1' : '0'; ?>">
					<summary class="frg-block-card__summary">
						<div class="frg-block-card__identity"><span class="frg-block-area"><?php echo esc_html( $area_label ); ?></span><strong><?php echo esc_html( $block['title'] ); ?></strong><small><?php echo ! empty( $block['override_text'] ) ? esc_html__( 'Eigener Text wird aktuell verwendet', 'frontend-rechtstexte-generator' ) : esc_html__( 'Mitgelieferter Standardtext wird verwendet', 'frontend-rechtstexte-generator' ); ?></small></div>
						<div class="frg-block-card__badges">
							<span class="frg-badge frg-badge--<?php echo esc_attr( $block_status ); ?>" data-frg-block-status><?php echo esc_html( $status_labels[ $block_status ] ?? $block_status ); ?></span>
							<?php if ( ! empty( $block['requires_consent'] ) ) : ?><span class="frg-badge frg-badge--soft"><?php esc_html_e( 'Einwilligung relevant', 'frontend-rechtstexte-generator' ); ?></span><?php endif; ?>
							<?php if ( ! empty( $block['third_country_possible'] ) ) : ?><span class="frg-badge frg-badge--soft"><?php esc_html_e( 'Drittland prüfen', 'frontend-rechtstexte-generator' ); ?></span><?php endif; ?>
						</div>
					</summary>
					<div class="frg-block-card__body">
						<?php if ( ! empty( $active_quality_flags ) || ! empty( $draft_quality_flags ) ) : ?>
							<div class="frg-guidance-warning frg-quality-warning">
								<strong><?php esc_html_e( 'Automatische Textprüfung:', 'frontend-rechtstexte-generator' ); ?></strong>
								<?php if ( ! empty( $active_quality_flags ) ) : ?><p><?php esc_html_e( 'Aktuell veröffentlichter Text:', 'frontend-rechtstexte-generator' ); ?></p><ul><?php foreach ( $active_quality_flags as $quality_flag ) : ?><li><?php echo esc_html( $quality_flag ); ?></li><?php endforeach; ?></ul><?php endif; ?>
								<?php if ( ! empty( $draft_quality_flags ) ) : ?><p><?php esc_html_e( 'Arbeitsentwurf:', 'frontend-rechtstexte-generator' ); ?></p><ul><?php foreach ( $draft_quality_flags as $quality_flag ) : ?><li><?php echo esc_html( $quality_flag ); ?></li><?php endforeach; ?></ul><?php endif; ?>
							</div>
						<?php endif; ?>
						<section class="frg-editor-step">
							<div class="frg-step-title"><span>1</span><div><h3><?php esc_html_e( 'Was hat sich geändert?', 'frontend-rechtstexte-generator' ); ?></h3><p><?php esc_html_e( 'Beschreiben Sie die neue Regel oder fügen Sie die Quelle ein. Diese Angabe wird beim KI-Entwurf berücksichtigt.', 'frontend-rechtstexte-generator' ); ?></p></div></div>
							<textarea name="blocks[<?php echo esc_attr( $block['key'] ); ?>][admin_notes]" rows="3" data-frg-change-request placeholder="<?php echo esc_attr__( 'Beispiel: Anbieteradresse geändert; neue Rechtsgrundlage aus Quelle … berücksichtigen.', 'frontend-rechtstexte-generator' ); ?>"><?php echo esc_textarea( $block['admin_notes'] ?? '' ); ?></textarea>
						</section>
						<section class="frg-editor-step">
							<div class="frg-step-title"><span>2</span><div><h3><?php esc_html_e( 'Neuen Text vorbereiten', 'frontend-rechtstexte-generator' ); ?></h3><p><?php esc_html_e( 'Erzeugen Sie einen KI-Vorschlag oder bearbeiten Sie den Entwurf direkt. Platzhalter bleiben für die späteren Formulardaten erhalten.', 'frontend-rechtstexte-generator' ); ?></p></div></div>
							<div class="frg-block-actions frg-block-actions--top"><button type="button" class="button button-secondary" data-frg-generate-draft="<?php echo esc_attr( $block['key'] ); ?>"><?php esc_html_e( 'KI-Vorschlag erstellen', 'frontend-rechtstexte-generator' ); ?></button><span class="frg-inline-feedback" data-frg-inline-feedback></span></div>
							<label class="frg-field-label"><?php esc_html_e( 'Arbeitsentwurf', 'frontend-rechtstexte-generator' ); ?><textarea name="blocks[<?php echo esc_attr( $block['key'] ); ?>][draft_text]" rows="12" data-frg-draft-input><?php echo esc_textarea( $block['draft_text'] ?? '' ); ?></textarea></label>
							<?php if ( ! empty( $placeholders ) ) : ?><details class="frg-helper-disclosure"><summary><?php esc_html_e( 'Verfügbare Platzhalter anzeigen', 'frontend-rechtstexte-generator' ); ?></summary><div class="frg-block-placeholders__list"><?php foreach ( $placeholders as $placeholder => $description ) : ?><span class="frg-block-placeholder-item"><code><?php echo esc_html( $placeholder ); ?></code><small><?php echo esc_html( $description ); ?></small></span><?php endforeach; ?></div></details><?php endif; ?>
						</section>
						<section class="frg-editor-step">
							<div class="frg-step-title"><span>3</span><div><h3><?php esc_html_e( 'Vergleichen und veröffentlichen', 'frontend-rechtstexte-generator' ); ?></h3><p><?php esc_html_e( 'Links sehen Sie die aktuelle Website-Ausgabe, rechts Ihren neuen Entwurf. Erst der Freigabe-Button ersetzt den bisherigen Text.', 'frontend-rechtstexte-generator' ); ?></p></div></div>
							<div class="frg-block-preview-grid"><div><h4><?php esc_html_e( 'Aktuell veröffentlicht', 'frontend-rechtstexte-generator' ); ?></h4><div class="frg-admin-output" data-frg-active-preview><?php echo wp_kses_post( $this->get_block_preview( $block['key'] ) ); ?></div></div><div><h4><?php esc_html_e( 'Neuer Entwurf', 'frontend-rechtstexte-generator' ); ?></h4><div class="frg-admin-output" data-frg-draft-preview><?php echo ! empty( $block['draft_text'] ) ? wp_kses_post( $this->format_admin_rich_text( $block['draft_text'] ) ) : esc_html__( 'Noch kein Entwurf vorhanden.', 'frontend-rechtstexte-generator' ); ?></div></div></div>
							<textarea name="blocks[<?php echo esc_attr( $block['key'] ); ?>][override_text]" hidden data-frg-override-input><?php echo esc_textarea( $block['override_text'] ?? '' ); ?></textarea>
							<div class="frg-publish-bar"><div><strong data-frg-live-state><?php echo ! empty( $block['override_text'] ) ? esc_html__( 'Eigener Text ist veröffentlicht.', 'frontend-rechtstexte-generator' ) : esc_html__( 'Der Standardtext ist veröffentlicht.', 'frontend-rechtstexte-generator' ); ?></strong><small><?php esc_html_e( 'Die Veröffentlichung ist eine redaktionelle Freigabe und keine juristische Prüfung.', 'frontend-rechtstexte-generator' ); ?></small></div><button type="button" class="button button-primary" data-frg-adopt-draft="<?php echo esc_attr( $block['key'] ); ?>" <?php disabled( empty( $block['draft_text'] ) ); ?>><?php esc_html_e( 'Entwurf jetzt veröffentlichen', 'frontend-rechtstexte-generator' ); ?></button></div>
							<details class="frg-helper-disclosure"><summary><?php esc_html_e( 'Optionalen Kompakttext bearbeiten', 'frontend-rechtstexte-generator' ); ?></summary><label class="frg-field-label"><?php esc_html_e( 'Veröffentlichter Text im Kompaktmodus', 'frontend-rechtstexte-generator' ); ?><textarea name="blocks[<?php echo esc_attr( $block['key'] ); ?>][compact_override_text]" rows="8"><?php echo esc_textarea( $block['compact_override_text'] ?? '' ); ?></textarea><small><?php esc_html_e( 'Leer lassen, um den mitgelieferten Kompaktbaustein zu verwenden. Ist für diesen Bereich kein Kompaktbaustein vorhanden, wird automatisch der ausführliche veröffentlichte Text verwendet.', 'frontend-rechtstexte-generator' ); ?></small></label></details>
							<div data-frg-override-preview hidden><?php echo ! empty( $block['override_text'] ) ? wp_kses_post( $this->get_block_preview( $block['key'] ) ) : ''; ?></div>
							<?php if ( 'hosting' === $block['key'] ) : ?><p class="frg-system-note"><?php esc_html_e( 'Pflichtschutz: Fehlende Hosting-, Anbieter- und AV-Angaben werden weiterhin automatisch aus dem Wizard ergänzt.', 'frontend-rechtstexte-generator' ); ?></p><?php endif; ?>
						</section>
						<section class="frg-editor-step frg-editor-step--review">
							<div class="frg-step-title"><span>4</span><div><h3><?php esc_html_e( 'Prüfung dokumentieren', 'frontend-rechtstexte-generator' ); ?></h3><p><?php esc_html_e( 'Setzen Sie „Juristisch geprüft“ nur, wenn eine tatsächliche fachliche Prüfung erfolgt ist und dokumentiert werden kann.', 'frontend-rechtstexte-generator' ); ?></p></div></div>
							<div class="frg-block-meta-grid">
								<label><?php esc_html_e( 'Bearbeitungsstatus', 'frontend-rechtstexte-generator' ); ?><select name="blocks[<?php echo esc_attr( $block['key'] ); ?>][status]" data-frg-status-select><option value="review_needed" <?php selected( $block_status, 'review_needed' ); ?>><?php esc_html_e( 'Prüfung offen', 'frontend-rechtstexte-generator' ); ?></option><option value="draft" <?php selected( $block_status, 'draft' ); ?>><?php esc_html_e( 'Entwurf bereit', 'frontend-rechtstexte-generator' ); ?></option><option value="editorial_approved" <?php selected( $block_status, 'editorial_approved' ); ?>><?php esc_html_e( 'Redaktionell freigegeben', 'frontend-rechtstexte-generator' ); ?></option><option value="legal_reviewed" <?php selected( $block_status, 'legal_reviewed' ); ?>><?php esc_html_e( 'Juristisch geprüft', 'frontend-rechtstexte-generator' ); ?></option></select></label>
								<label><?php esc_html_e( 'Geprüft am', 'frontend-rechtstexte-generator' ); ?><input type="date" name="blocks[<?php echo esc_attr( $block['key'] ); ?>][last_reviewed]" value="<?php echo esc_attr( $block['last_reviewed'] ); ?>" data-frg-last-reviewed></label>
								<label><?php esc_html_e( 'Erneut prüfen am', 'frontend-rechtstexte-generator' ); ?><input type="date" name="blocks[<?php echo esc_attr( $block['key'] ); ?>][review_due_at]" value="<?php echo esc_attr( $block['review_due_at'] ); ?>"></label>
								<label><?php esc_html_e( 'Geprüft durch', 'frontend-rechtstexte-generator' ); ?><input type="text" name="blocks[<?php echo esc_attr( $block['key'] ); ?>][reviewed_by]" value="<?php echo esc_attr( $block['reviewed_by'] ?? '' ); ?>" placeholder="<?php echo esc_attr__( 'Name oder Kanzlei', 'frontend-rechtstexte-generator' ); ?>" data-frg-reviewed-by></label>
							</div>
							<label class="frg-field-label"><?php esc_html_e( 'Prüfquelle oder Aktennotiz', 'frontend-rechtstexte-generator' ); ?><textarea name="blocks[<?php echo esc_attr( $block['key'] ); ?>][review_source]" rows="2"><?php echo esc_textarea( $block['review_source'] ?? '' ); ?></textarea></label>
							<details class="frg-helper-disclosure"><summary><?php esc_html_e( 'Technische Rechtsgrundlagen bearbeiten', 'frontend-rechtstexte-generator' ); ?></summary><label class="frg-field-label"><?php esc_html_e( 'Rechtsgrundlagen für diesen Baustein', 'frontend-rechtstexte-generator' ); ?><textarea name="blocks[<?php echo esc_attr( $block['key'] ); ?>][legal_basis]" rows="4" data-frg-legal-basis><?php echo esc_textarea( implode( "\n", $block['legal_basis'] ?? array() ) ); ?></textarea></label></details>
						</section>
					</div>
				</details>
			<?php endforeach; ?>
		</div>
		<div class="frg-sticky-save"><span><?php esc_html_e( 'Prüfdaten, Quellen und manuelle Änderungen werden mit diesem Button gespeichert.', 'frontend-rechtstexte-generator' ); ?></span><button type="submit" name="frg_save_block_registry" class="button button-primary"><?php esc_html_e( 'Alle Änderungen speichern', 'frontend-rechtstexte-generator' ); ?></button></div>
	</form>

	<form method="post" id="frg-settings" class="frg-admin-card">
		<?php wp_nonce_field( 'frg_save_settings_action', 'frg_save_settings_nonce' ); ?>
		<input type="hidden" name="frg_save_settings" value="1">
		<h2><?php esc_html_e( 'Grundeinstellungen', 'frontend-rechtstexte-generator' ); ?></h2>
		<p><?php esc_html_e( 'Hier legen Sie Seitennamen, sichtbare Hinweise und die optionale KI-Verbindung fest.', 'frontend-rechtstexte-generator' ); ?></p>
		<table class="form-table" role="presentation">
			<tr><th scope="row"><label for="legal_notice"><?php esc_html_e( 'Hinweis im Wizard', 'frontend-rechtstexte-generator' ); ?></label></th><td><textarea name="legal_notice" id="legal_notice" rows="4" class="large-text"><?php echo esc_textarea( $settings['legal_notice'] ?? '' ); ?></textarea></td></tr>
			<tr><th scope="row"><?php esc_html_e( 'Hinweis zur Textgenerierung', 'frontend-rechtstexte-generator' ); ?></th><td><p class="description"><?php esc_html_e( 'Der rechtliche Prüfhinweis wird im Wizard angezeigt, aber nicht in das veröffentlichte Impressum oder die veröffentlichte Datenschutzerklärung übernommen.', 'frontend-rechtstexte-generator' ); ?></p></td></tr>
			<tr><th scope="row"><label for="impressum_page"><?php esc_html_e( 'Seitenname Impressum', 'frontend-rechtstexte-generator' ); ?></label></th><td><input type="text" name="impressum_page" id="impressum_page" class="regular-text" value="<?php echo esc_attr( $settings['impressum_page'] ?? '' ); ?>"></td></tr>
			<tr><th scope="row"><label for="privacy_page"><?php esc_html_e( 'Seitenname Datenschutzerklärung', 'frontend-rechtstexte-generator' ); ?></label></th><td><input type="text" name="privacy_page" id="privacy_page" class="regular-text" value="<?php echo esc_attr( $settings['privacy_page'] ?? '' ); ?>"></td></tr>
			<tr><th scope="row"><label for="privacy_readability_mode"><?php esc_html_e( 'Lesbarkeit der Datenschutzerklärung', 'frontend-rechtstexte-generator' ); ?></label></th><td><select name="privacy_readability_mode" id="privacy_readability_mode"><option value="detailed" <?php selected( $settings['privacy_readability_mode'] ?? 'detailed', 'detailed' ); ?>><?php esc_html_e( 'Ausführlich', 'frontend-rechtstexte-generator' ); ?></option><option value="compact" <?php selected( $settings['privacy_readability_mode'] ?? 'detailed', 'compact' ); ?>><?php esc_html_e( 'Kompakt', 'frontend-rechtstexte-generator' ); ?></option></select><p class="description"><?php esc_html_e( 'Der kompakte Modus strafft insbesondere Hosting, Server-Logfiles, Kontaktformular und lokale Google Fonts. Anbieter-, Adress-, AV- und Rechtsgrundlagenangaben bleiben erhalten. Veraltete oder unvollständige Live-Overrides werden durch zentrale Mindestregeln ergänzt.', 'frontend-rechtstexte-generator' ); ?></p></td></tr>
			<tr><th scope="row"><?php esc_html_e( 'Seiten automatisch aktuell halten', 'frontend-rechtstexte-generator' ); ?></th><td><label><input type="checkbox" name="dynamic_page_content" value="1" <?php checked( ! array_key_exists( 'dynamic_page_content', $settings ) || ! empty( $settings['dynamic_page_content'] ) ); ?>> <?php esc_html_e( 'Shortcodes statt einer festen HTML-Momentaufnahme verwenden', 'frontend-rechtstexte-generator' ); ?></label><p class="description"><?php esc_html_e( 'Empfohlen. Bereits vorhandene statische Seiten müssen nach dem Aktivieren einmal erneut über den Wizard synchronisiert werden.', 'frontend-rechtstexte-generator' ); ?></p></td></tr>
			<tr><th scope="row"><label for="openai_api_key"><?php esc_html_e( 'OpenAI API-Key', 'frontend-rechtstexte-generator' ); ?></label></th><td><input type="password" name="openai_api_key" id="openai_api_key" class="regular-text" value="<?php echo esc_attr( $settings['openai_api_key'] ?? '' ); ?>" autocomplete="off"><p class="description"><?php esc_html_e( 'Wird nur verwendet, wenn Sie bei einem Textbaustein ausdrücklich einen KI-Vorschlag anfordern.', 'frontend-rechtstexte-generator' ); ?></p></td></tr>
			<tr><th scope="row"><label for="openai_model"><?php esc_html_e( 'OpenAI-Modell für Textentwürfe', 'frontend-rechtstexte-generator' ); ?></label></th><td><select name="openai_model" id="openai_model"><?php if ( ! isset( $model_options[ $selected_model ] ) ) : ?><option value="<?php echo esc_attr( $selected_model ); ?>" selected><?php echo esc_html( sprintf( __( 'Bisher gespeichert: %s', 'frontend-rechtstexte-generator' ), $selected_model ) ); ?></option><?php endif; ?><?php foreach ( $model_options as $model_id => $model_label ) : ?><option value="<?php echo esc_attr( $model_id ); ?>" <?php selected( $selected_model, $model_id ); ?>><?php echo esc_html( $model_label ); ?></option><?php endforeach; ?></select><p class="description"><?php esc_html_e( 'Empfehlung: Terra bietet für ausführliche Textbausteine ein gutes Verhältnis aus Qualität und Kosten. Die Modellauswahl ersetzt keine Quellenprüfung.', 'frontend-rechtstexte-generator' ); ?></p></td></tr>
		</table>
		<section id="frg-block-feed" class="frg-feed-settings">
			<div class="frg-section-heading">
				<div><span class="frg-admin-eyebrow"><?php esc_html_e( 'Optional', 'frontend-rechtstexte-generator' ); ?></span><h3><?php esc_html_e( 'Textbausteine zwischen Websites verteilen', 'frontend-rechtstexte-generator' ); ?></h3><p><?php esc_html_e( 'Nur veröffentlichte Textbausteine werden übertragen. Profile, Kundendaten, KI-Entwürfe und interne Notizen bleiben lokal.', 'frontend-rechtstexte-generator' ); ?></p></div>
			</div>
			<label class="frg-field-label" for="block_feed_mode"><?php esc_html_e( 'Rolle dieser Website', 'frontend-rechtstexte-generator' ); ?><select name="block_feed_mode" id="block_feed_mode" data-frg-feed-mode><option value="off" <?php selected( $feed_mode, 'off' ); ?>><?php esc_html_e( 'Keine Textverteilung', 'frontend-rechtstexte-generator' ); ?></option><option value="hub" <?php selected( $feed_mode, 'hub' ); ?>><?php esc_html_e( 'Zentrale – Texte für Kundenseiten bereitstellen', 'frontend-rechtstexte-generator' ); ?></option><option value="client" <?php selected( $feed_mode, 'client' ); ?>><?php esc_html_e( 'Kundenseite – Texte von einer Zentrale empfangen', 'frontend-rechtstexte-generator' ); ?></option></select></label>

			<div class="frg-feed-panel" data-frg-feed-shared>
				<label class="frg-field-label" for="block_feed_key"><?php esc_html_e( 'Verbindungsschlüssel', 'frontend-rechtstexte-generator' ); ?><span class="frg-copy-field"><input type="text" name="block_feed_key" id="block_feed_key" value="<?php echo esc_attr( $settings['block_feed_key'] ?? '' ); ?>" autocomplete="off"><button type="button" class="button" data-frg-copy-value="#block_feed_key"><?php esc_html_e( 'Kopieren', 'frontend-rechtstexte-generator' ); ?></button></span></label>
				<p class="description"><?php esc_html_e( 'Auf Zentrale und Kundenseite muss derselbe Schlüssel eingetragen sein. Behandeln Sie ihn wie ein Passwort.', 'frontend-rechtstexte-generator' ); ?></p>
			</div>

			<div class="frg-feed-panel" data-frg-feed-panel="hub">
				<h4><?php esc_html_e( 'Angaben für Kundenseiten', 'frontend-rechtstexte-generator' ); ?></h4>
				<label class="frg-field-label" for="frg_feed_endpoint"><?php esc_html_e( 'Feed-URL dieser Zentrale', 'frontend-rechtstexte-generator' ); ?><span class="frg-copy-field"><input type="url" id="frg_feed_endpoint" value="<?php echo esc_url( $feed_endpoint ); ?>" readonly><button type="button" class="button" data-frg-copy-value="#frg_feed_endpoint"><?php esc_html_e( 'Kopieren', 'frontend-rechtstexte-generator' ); ?></button></span></label>
				<label class="frg-danger-option"><input type="checkbox" name="block_feed_regenerate_key" value="1"> <?php esc_html_e( 'Beim Speichern einen neuen Schlüssel erzeugen', 'frontend-rechtstexte-generator' ); ?></label>
				<p class="description"><?php esc_html_e( 'Achtung: Nach einem Schlüsselwechsel muss der neue Schlüssel auf allen verbundenen Kundenseiten eingetragen werden.', 'frontend-rechtstexte-generator' ); ?></p>
			</div>

			<div class="frg-feed-panel" data-frg-feed-panel="client">
				<label class="frg-field-label" for="block_feed_url"><?php esc_html_e( 'Feed-URL der Zentrale', 'frontend-rechtstexte-generator' ); ?><input type="url" name="block_feed_url" id="block_feed_url" value="<?php echo esc_attr( $settings['block_feed_url'] ?? '' ); ?>" placeholder="https://ihre-zentrale.de/wp-json/frg/v1/block-feed"></label>
				<label><input type="checkbox" name="block_feed_auto_sync" value="1" <?php checked( ! array_key_exists( 'block_feed_auto_sync', $settings ) || ! empty( $settings['block_feed_auto_sync'] ) ); ?>> <?php esc_html_e( 'Einmal täglich automatisch nach veröffentlichten Textänderungen suchen', 'frontend-rechtstexte-generator' ); ?></label>
				<div class="frg-feed-state">
					<strong><?php esc_html_e( 'Letzte erfolgreiche Synchronisierung', 'frontend-rechtstexte-generator' ); ?>:</strong> <?php echo ! empty( $feed_state['last_success_at'] ) ? esc_html( mysql2date( get_option( 'date_format' ) . ' ' . get_option( 'time_format' ), $feed_state['last_success_at'] ) ) : esc_html__( 'Noch keine', 'frontend-rechtstexte-generator' ); ?><br>
					<?php if ( ! empty( $feed_state['module_version'] ) ) : ?><strong><?php esc_html_e( 'Geladene Modulversion', 'frontend-rechtstexte-generator' ); ?>:</strong> <?php echo esc_html( $feed_state['module_version'] ); ?><br><?php endif; ?>
					<?php if ( ! empty( $feed_state['last_error'] ) ) : ?><span class="frg-feed-error"><strong><?php esc_html_e( 'Letzter Fehler', 'frontend-rechtstexte-generator' ); ?>:</strong> <?php echo esc_html( $feed_state['last_error'] ); ?></span><?php endif; ?>
				</div>
				<p class="description"><?php esc_html_e( 'Bei einem Verbindungsfehler bleibt die zuletzt erfolgreich geladene Textversion aktiv.', 'frontend-rechtstexte-generator' ); ?></p>
			</div>
		</section>
		<div class="frg-settings-actions"><button type="submit" class="button button-primary"><?php esc_html_e( 'Grundeinstellungen speichern', 'frontend-rechtstexte-generator' ); ?></button><button type="submit" name="frg_sync_after_save" value="1" class="button" data-frg-client-sync><?php esc_html_e( 'Speichern und Verbindung testen', 'frontend-rechtstexte-generator' ); ?></button><span class="frg-inline-feedback" data-frg-copy-value-feedback></span></div>
	</form>

	<div id="frg-transfer" class="frg-admin-card">
		<h2><?php esc_html_e( 'Export / Import', 'frontend-rechtstexte-generator' ); ?></h2>
		<div class="frg-block-preview-grid">
			<div>
				<h3><?php esc_html_e( 'Block-Registry exportieren', 'frontend-rechtstexte-generator' ); ?></h3>
				<textarea readonly rows="16"><?php echo esc_textarea( $registry_export ?: '' ); ?></textarea>
				<form method="post">
					<?php wp_nonce_field( 'frg_import_block_registry_action', 'frg_import_block_registry_nonce' ); ?>
					<h3><?php esc_html_e( 'Block-Registry importieren', 'frontend-rechtstexte-generator' ); ?></h3>
					<textarea name="block_registry_json" rows="12"></textarea>
					<p><button type="submit" name="frg_import_block_registry" class="button button-primary"><?php esc_html_e( 'Registry importieren', 'frontend-rechtstexte-generator' ); ?></button></p>
				</form>
			</div>
			<div>
				<h3><?php esc_html_e( 'Profil exportieren', 'frontend-rechtstexte-generator' ); ?></h3>
				<textarea readonly rows="16"><?php echo esc_textarea( $profile_export ?: '' ); ?></textarea>
				<form method="post">
					<?php wp_nonce_field( 'frg_import_profile_action', 'frg_import_profile_nonce' ); ?>
					<h3><?php esc_html_e( 'Profil importieren', 'frontend-rechtstexte-generator' ); ?></h3>
					<textarea name="profile_json" rows="12"></textarea>
					<p><button type="submit" name="frg_import_profile" class="button button-primary"><?php esc_html_e( 'Profil importieren', 'frontend-rechtstexte-generator' ); ?></button></p>
				</form>
			</div>
		</div>
	</div>

	<div id="frg-profiles" class="frg-admin-card">
		<h2><?php esc_html_e( 'Gespeicherte Profile', 'frontend-rechtstexte-generator' ); ?></h2>
		<?php if ( empty( $profiles ) ) : ?>
			<p><?php esc_html_e( 'Noch keine gespeicherten Profile vorhanden.', 'frontend-rechtstexte-generator' ); ?></p>
		<?php else : ?>
			<table class="widefat striped">
				<thead>
					<tr>
						<th><?php esc_html_e( 'ID', 'frontend-rechtstexte-generator' ); ?></th>
						<th><?php esc_html_e( 'Profil', 'frontend-rechtstexte-generator' ); ?></th>
						<th><?php esc_html_e( 'Benutzer', 'frontend-rechtstexte-generator' ); ?></th>
						<th><?php esc_html_e( 'Aktualisiert', 'frontend-rechtstexte-generator' ); ?></th>
						<th><?php esc_html_e( 'Aktionen', 'frontend-rechtstexte-generator' ); ?></th>
					</tr>
				</thead>
				<tbody>
					<?php foreach ( $profiles as $row ) : ?>
						<tr>
							<td><?php echo esc_html( (string) $row['id'] ); ?></td>
							<td><?php echo esc_html( $row['profile_name'] ); ?></td>
							<td><?php echo esc_html( (string) ( $row['user_id'] ?? 0 ) ); ?></td>
							<td><?php echo esc_html( mysql2date( get_option( 'date_format' ) . ' ' . get_option( 'time_format' ), $row['updated_at'] ) ); ?></td>
							<td>
								<a class="button button-secondary" href="<?php echo esc_url( admin_url( 'options-general.php?page=frg-settings&profile_id=' . absint( $row['id'] ) ) ); ?>"><?php esc_html_e( 'Profil anzeigen', 'frontend-rechtstexte-generator' ); ?></a>
								<form method="post" style="display:inline-block;">
									<?php wp_nonce_field( 'frg_delete_profile_action', 'frg_delete_profile_nonce' ); ?>
									<input type="hidden" name="profile_id" value="<?php echo esc_attr( (string) $row['id'] ); ?>">
									<button type="submit" name="frg_delete_profile" class="button button-link-delete" onclick="return confirm('<?php echo esc_js( __( 'Profil wirklich löschen?', 'frontend-rechtstexte-generator' ) ); ?>');"><?php esc_html_e( 'Löschen', 'frontend-rechtstexte-generator' ); ?></button>
								</form>
							</td>
						</tr>
					<?php endforeach; ?>
				</tbody>
			</table>
		<?php endif; ?>
	</div>

	<?php if ( $profile ) : ?>
		<div class="frg-admin-card">
			<h2><?php esc_html_e( 'Profilansicht', 'frontend-rechtstexte-generator' ); ?> #<?php echo esc_html( (string) $profile['id'] ); ?></h2>
			<p><strong><?php esc_html_e( 'Profilname', 'frontend-rechtstexte-generator' ); ?>:</strong> <?php echo esc_html( $profile['profile_name'] ); ?></p>
			<p><strong><?php esc_html_e( 'Gespeicherte Dokumentversion', 'frontend-rechtstexte-generator' ); ?>:</strong> <?php echo esc_html( $profile['data']['generated_module_version'] ?? '-' ); ?></p>
			<p><strong><?php esc_html_e( 'Dokumente generiert am', 'frontend-rechtstexte-generator' ); ?>:</strong> <?php echo esc_html( $profile['data']['generated_impressum_updated_at'] ?? '-' ); ?></p>
			<p><strong><?php esc_html_e( 'Rohdaten', 'frontend-rechtstexte-generator' ); ?>:</strong></p>
			<pre><?php echo esc_html( wp_json_encode( $profile['data'], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE ) ?: '' ); ?></pre>
		</div>

		<div class="frg-admin-card">
			<h2><?php esc_html_e( 'HTML-Ausgabe für Seiten', 'frontend-rechtstexte-generator' ); ?></h2>
			<p><?php esc_html_e( 'Sie können die generierten HTML-Inhalte separat kopieren und direkt in eigene WordPress-Seiten einfügen.', 'frontend-rechtstexte-generator' ); ?></p>
			<div class="frg-block-preview-grid">
				<div>
					<div class="frg-admin-copy-header">
						<h3><?php esc_html_e( 'Impressum HTML', 'frontend-rechtstexte-generator' ); ?></h3>
						<button type="button" class="button button-secondary" data-frg-copy-admin="impressum"><?php esc_html_e( 'Impressum HTML kopieren', 'frontend-rechtstexte-generator' ); ?></button>
					</div>
					<textarea rows="16" class="large-text code" readonly data-frg-admin-html="impressum"><?php echo esc_textarea( $generated_impressum_export ); ?></textarea>
				</div>
				<div>
					<div class="frg-admin-copy-header">
						<h3><?php esc_html_e( 'Datenschutzerklärung HTML', 'frontend-rechtstexte-generator' ); ?></h3>
						<button type="button" class="button button-secondary" data-frg-copy-admin="privacy"><?php esc_html_e( 'Datenschutzerklärung HTML kopieren', 'frontend-rechtstexte-generator' ); ?></button>
					</div>
					<textarea rows="16" class="large-text code" readonly data-frg-admin-html="privacy"><?php echo esc_textarea( $generated_privacy_export ); ?></textarea>
				</div>
			</div>
			<p class="frg-inline-feedback" data-frg-admin-copy-feedback></p>
		</div>

		<div class="frg-admin-card">
			<h2><?php esc_html_e( 'Generiertes Impressum', 'frontend-rechtstexte-generator' ); ?></h2>
			<div class="frg-admin-output"><?php echo wp_kses_post( $generated_impressum ); ?></div>
		</div>

		<div class="frg-admin-card">
			<h2><?php esc_html_e( 'Generierte Datenschutzerklärung', 'frontend-rechtstexte-generator' ); ?></h2>
			<div class="frg-admin-output"><?php echo wp_kses_post( $generated_privacy ); ?></div>
		</div>
	<?php endif; ?>
</div>

<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$generated_impressum = $profile['data']['generated_impressum_html'] ?? ( $profile ? $this->generator->generate_impressum( $profile['data'] ) : '' );
$generated_privacy   = $profile['data']['generated_privacy_html'] ?? ( $profile ? $this->generator->generate_privacy_policy( $profile['data'] ) : '' );
$generated_impressum_export = $profile['data']['generated_impressum_export_html'] ?? ( $generated_impressum ? $this->generator->build_exportable_document_html( $generated_impressum ) : '' );
$generated_privacy_export   = $profile['data']['generated_privacy_export_html'] ?? ( $generated_privacy ? $this->generator->build_exportable_document_html( $generated_privacy ) : '' );
$module_meta         = $this->generator->get_module_meta();
?>
<div class="wrap frg-admin">
	<h1><?php esc_html_e( 'Rechtstexte Generator', 'frontend-rechtstexte-generator' ); ?></h1>

	<form method="post" class="frg-admin-card">
		<?php wp_nonce_field( 'frg_save_settings_action', 'frg_save_settings_nonce' ); ?>
		<h2><?php esc_html_e( 'Grundeinstellungen', 'frontend-rechtstexte-generator' ); ?></h2>
		<table class="form-table" role="presentation">
			<tr>
				<th scope="row"><label for="legal_notice"><?php esc_html_e( 'Rechtlicher Hinweistext', 'frontend-rechtstexte-generator' ); ?></label></th>
				<td><textarea name="legal_notice" id="legal_notice" rows="4" class="large-text"><?php echo esc_textarea( $settings['legal_notice'] ?? '' ); ?></textarea></td>
			</tr>
			<tr>
				<th scope="row"><label for="impressum_page"><?php esc_html_e( 'Standard-Seitenname Impressum', 'frontend-rechtstexte-generator' ); ?></label></th>
				<td><input type="text" name="impressum_page" id="impressum_page" class="regular-text" value="<?php echo esc_attr( $settings['impressum_page'] ?? '' ); ?>"></td>
			</tr>
			<tr>
				<th scope="row"><label for="privacy_page"><?php esc_html_e( 'Standard-Seitenname Datenschutzerklaerung', 'frontend-rechtstexte-generator' ); ?></label></th>
				<td><input type="text" name="privacy_page" id="privacy_page" class="regular-text" value="<?php echo esc_attr( $settings['privacy_page'] ?? '' ); ?>"></td>
			</tr>
			<tr>
				<th scope="row"><label for="openai_api_key"><?php esc_html_e( 'OpenAI API Key', 'frontend-rechtstexte-generator' ); ?></label></th>
				<td><input type="password" name="openai_api_key" id="openai_api_key" class="regular-text" value="<?php echo esc_attr( $settings['openai_api_key'] ?? '' ); ?>"></td>
			</tr>
			<tr>
				<th scope="row"><label for="openai_model"><?php esc_html_e( 'OpenAI Modell', 'frontend-rechtstexte-generator' ); ?></label></th>
				<td><input type="text" name="openai_model" id="openai_model" class="regular-text" value="<?php echo esc_attr( $settings['openai_model'] ?? 'gpt-5.2' ); ?>"></td>
			</tr>
		</table>
		<p><button type="submit" name="frg_save_settings" class="button button-primary"><?php esc_html_e( 'Einstellungen speichern', 'frontend-rechtstexte-generator' ); ?></button></p>
	</form>

	<div class="frg-admin-card">
		<h2><?php esc_html_e( 'Stand der Textmodule', 'frontend-rechtstexte-generator' ); ?></h2>
		<p><strong><?php esc_html_e( 'Modulversion', 'frontend-rechtstexte-generator' ); ?>:</strong> <?php echo esc_html( $module_meta['module_version'] ?? '' ); ?></p>
		<p><strong><?php esc_html_e( 'Zuletzt geprueft am', 'frontend-rechtstexte-generator' ); ?>:</strong> <?php echo esc_html( $module_meta['last_reviewed_at'] ?? '' ); ?></p>
		<p><strong><?php esc_html_e( 'Rechtsgrundlagen', 'frontend-rechtstexte-generator' ); ?>:</strong> <?php echo esc_html( implode( ', ', $module_meta['legal_basis'] ?? array() ) ); ?></p>
		<p><?php echo esc_html( $module_meta['notice'] ?? '' ); ?></p>
	</div>

	<form method="post" class="frg-admin-card">
		<?php wp_nonce_field( 'frg_save_block_registry_action', 'frg_save_block_registry_nonce' ); ?>
		<h2><?php esc_html_e( 'Block Registry', 'frontend-rechtstexte-generator' ); ?></h2>
		<p><?php esc_html_e( 'Hier pflegen Sie Pruefstatus, Rechtsgrundlagen und Entwurfsnotizen fuer die festen Textbausteine. Entwuerfe werden nicht automatisch live verwendet.', 'frontend-rechtstexte-generator' ); ?></p>
		<div class="frg-notice-inline">
			<p><strong><?php esc_html_e( 'So funktioniert die Aktivierung:', 'frontend-rechtstexte-generator' ); ?></strong></p>
			<p><?php esc_html_e( '1. Entwurfsfeld bearbeiten oder KI-Entwurf erzeugen.', 'frontend-rechtstexte-generator' ); ?></p>
			<p><?php esc_html_e( '2. Mit "Entwurf als Live-Override uebernehmen" wird der Block sofort fuer Frontend und Generator aktiviert.', 'frontend-rechtstexte-generator' ); ?></p>
			<p><?php esc_html_e( '3. "Block-Registry speichern" speichert zusaetzlich Metadaten wie Status, Rechtsgrundlagen und Notizen.', 'frontend-rechtstexte-generator' ); ?></p>
		</div>
		<div class="frg-block-accordion">
			<?php foreach ( $block_registry as $block ) : ?>
				<?php $placeholders = $this->generator->get_block_placeholder_details( $block['key'] ); ?>
				<details class="frg-block-card" data-frg-block="<?php echo esc_attr( $block['key'] ); ?>">
					<summary class="frg-block-card__summary">
						<div>
							<strong><?php echo esc_html( $block['title'] ); ?></strong>
							<div class="frg-block-card__meta">
								<code><?php echo esc_html( $block['key'] ); ?></code>
								<span><?php echo esc_html( $block['area'] ); ?></span>
							</div>
						</div>
						<div class="frg-block-card__badges">
							<span class="frg-badge" data-frg-block-status><?php echo esc_html( $block['status'] ); ?></span>
							<?php if ( ! empty( $block['requires_consent'] ) ) : ?><span class="frg-badge frg-badge--soft"><?php esc_html_e( 'Consent', 'frontend-rechtstexte-generator' ); ?></span><?php endif; ?>
							<?php if ( ! empty( $block['third_country_possible'] ) ) : ?><span class="frg-badge frg-badge--soft"><?php esc_html_e( 'Drittland moeglich', 'frontend-rechtstexte-generator' ); ?></span><?php endif; ?>
						</div>
					</summary>
					<div class="frg-block-card__body">
						<div class="frg-block-meta-grid">
							<label>
								<?php esc_html_e( 'Status', 'frontend-rechtstexte-generator' ); ?><br>
								<select name="blocks[<?php echo esc_attr( $block['key'] ); ?>][status]">
									<option value="approved" <?php selected( $block['status'], 'approved' ); ?>><?php esc_html_e( 'geprueft', 'frontend-rechtstexte-generator' ); ?></option>
									<option value="review_needed" <?php selected( $block['status'], 'review_needed' ); ?>><?php esc_html_e( 'pruefbeduerftig', 'frontend-rechtstexte-generator' ); ?></option>
									<option value="draft" <?php selected( $block['status'], 'draft' ); ?>><?php esc_html_e( 'Entwurf vorhanden', 'frontend-rechtstexte-generator' ); ?></option>
								</select>
							</label>
							<label>
								<?php esc_html_e( 'Zuletzt geprueft', 'frontend-rechtstexte-generator' ); ?><br>
								<input type="date" name="blocks[<?php echo esc_attr( $block['key'] ); ?>][last_reviewed]" value="<?php echo esc_attr( $block['last_reviewed'] ); ?>" data-frg-last-reviewed>
							</label>
							<label>
								<?php esc_html_e( 'Naechste Pruefung', 'frontend-rechtstexte-generator' ); ?><br>
								<input type="date" name="blocks[<?php echo esc_attr( $block['key'] ); ?>][review_due_at]" value="<?php echo esc_attr( $block['review_due_at'] ); ?>">
							</label>
						</div>
						<label>
							<?php esc_html_e( 'Rechtsgrundlagen', 'frontend-rechtstexte-generator' ); ?><br>
							<textarea name="blocks[<?php echo esc_attr( $block['key'] ); ?>][legal_basis]" rows="4"><?php echo esc_textarea( implode( "\n", $block['legal_basis'] ?? array() ) ); ?></textarea>
						</label>
						<?php if ( ! empty( $placeholders ) ) : ?>
							<div class="frg-block-placeholders">
								<strong><?php esc_html_e( 'Verfuegbare Platzhalter fuer KI-Entwuerfe und Overrides', 'frontend-rechtstexte-generator' ); ?>:</strong>
								<div class="frg-block-placeholders__list">
									<?php foreach ( $placeholders as $placeholder => $description ) : ?>
										<span class="frg-block-placeholder-item"><code><?php echo esc_html( $placeholder ); ?></code><small><?php echo esc_html( $description ); ?></small></span>
									<?php endforeach; ?>
								</div>
							</div>
						<?php endif; ?>
						<div class="frg-block-table__detail">
							<label>
								<?php esc_html_e( 'Interne Notizen', 'frontend-rechtstexte-generator' ); ?><br>
								<textarea name="blocks[<?php echo esc_attr( $block['key'] ); ?>][admin_notes]" rows="4"><?php echo esc_textarea( $block['admin_notes'] ?? '' ); ?></textarea>
							</label>
							<label>
								<?php esc_html_e( 'Entwurfsfeld fuer Ueberarbeitung / KI-Vorschlag', 'frontend-rechtstexte-generator' ); ?><br>
								<textarea name="blocks[<?php echo esc_attr( $block['key'] ); ?>][draft_text]" rows="10" data-frg-draft-input><?php echo esc_textarea( $block['draft_text'] ?? '' ); ?></textarea>
							</label>
						</div>
						<textarea name="blocks[<?php echo esc_attr( $block['key'] ); ?>][override_text]" hidden data-frg-override-input><?php echo esc_textarea( $block['override_text'] ?? '' ); ?></textarea>
						<div class="frg-block-actions">
							<button type="button" class="button button-secondary" data-frg-generate-draft="<?php echo esc_attr( $block['key'] ); ?>"><?php esc_html_e( 'KI-Entwurf erzeugen', 'frontend-rechtstexte-generator' ); ?></button>
							<button type="button" class="button button-primary" data-frg-adopt-draft="<?php echo esc_attr( $block['key'] ); ?>" <?php disabled( empty( $block['draft_text'] ) ); ?>><?php esc_html_e( 'Entwurf als Live-Override uebernehmen', 'frontend-rechtstexte-generator' ); ?></button>
							<span class="frg-inline-feedback" data-frg-inline-feedback></span>
						</div>
						<p class="description"><?php esc_html_e( 'Hinweis: Der Live-Override ist der Text, der spaeter im Frontend und in generierten Dokumenten verwendet wird. Das Entwurfsfeld allein ist noch nicht live.', 'frontend-rechtstexte-generator' ); ?></p>
						<?php if ( 'hosting' === $block['key'] ) : ?>
							<p class="description"><?php esc_html_e( 'System-Hinweis: Falls der AV-Hinweis im Hosting-Override fehlt, wird der Abschnitt zur Auftragsverarbeitung automatisch ergänzt.', 'frontend-rechtstexte-generator' ); ?></p>
						<?php endif; ?>
						<p class="description" data-frg-live-state><?php echo ! empty( $block['override_text'] ) ? esc_html__( 'Status: Live-Override gespeichert.', 'frontend-rechtstexte-generator' ) : esc_html__( 'Status: Noch kein Live-Override gespeichert.', 'frontend-rechtstexte-generator' ); ?></p>
						<div class="frg-block-preview-grid">
							<div>
								<h3><?php esc_html_e( 'Aktive Block-Ausgabe', 'frontend-rechtstexte-generator' ); ?></h3>
								<div class="frg-admin-output" data-frg-active-preview><?php echo wp_kses_post( $this->get_block_preview( $block['key'] ) ); ?></div>
							</div>
							<div>
								<h3><?php esc_html_e( 'Gespeicherter Live-Override', 'frontend-rechtstexte-generator' ); ?></h3>
								<div class="frg-admin-output" data-frg-override-preview><?php echo ! empty( $block['override_text'] ) ? wp_kses_post( $this->get_block_preview( $block['key'] ) ) : esc_html__( 'Kein Live-Override gespeichert.', 'frontend-rechtstexte-generator' ); ?></div>
							</div>
						</div>
						<div>
							<h3><?php esc_html_e( 'Entwurfsvorschau', 'frontend-rechtstexte-generator' ); ?></h3>
							<div class="frg-admin-output" data-frg-draft-preview><?php echo ! empty( $block['draft_text'] ) ? wp_kses_post( $this->format_admin_rich_text( $block['draft_text'] ) ) : esc_html__( 'Kein Entwurf hinterlegt.', 'frontend-rechtstexte-generator' ); ?></div>
						</div>
					</div>
				</details>
			<?php endforeach; ?>
		</div>
		<p><button type="submit" name="frg_save_block_registry" class="button button-primary"><?php esc_html_e( 'Block-Registry speichern', 'frontend-rechtstexte-generator' ); ?></button></p>
	</form>

	<div class="frg-admin-card">
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

	<div class="frg-admin-card">
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
									<button type="submit" name="frg_delete_profile" class="button button-link-delete" onclick="return confirm('<?php echo esc_js( __( 'Profil wirklich loeschen?', 'frontend-rechtstexte-generator' ) ); ?>');"><?php esc_html_e( 'Loeschen', 'frontend-rechtstexte-generator' ); ?></button>
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
			<h2><?php esc_html_e( 'HTML Ausgabe fuer Seiten', 'frontend-rechtstexte-generator' ); ?></h2>
			<p><?php esc_html_e( 'Sie koennen die generierten HTML-Inhalte separat kopieren und direkt in eigene WordPress-Seiten einfuegen.', 'frontend-rechtstexte-generator' ); ?></p>
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
						<h3><?php esc_html_e( 'Datenschutzerklaerung HTML', 'frontend-rechtstexte-generator' ); ?></h3>
						<button type="button" class="button button-secondary" data-frg-copy-admin="privacy"><?php esc_html_e( 'Datenschutzerklaerung HTML kopieren', 'frontend-rechtstexte-generator' ); ?></button>
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
			<h2><?php esc_html_e( 'Generierte Datenschutzerklaerung', 'frontend-rechtstexte-generator' ); ?></h2>
			<div class="frg-admin-output"><?php echo wp_kses_post( $generated_privacy ); ?></div>
		</div>
	<?php endif; ?>
</div>

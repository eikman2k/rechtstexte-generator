<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class FRG_Multisite {
	private const NETWORK_OPTION = 'frg_network_settings';

	public static function register_menu(): void {
		if ( ! is_multisite() ) {
			return;
		}

		add_submenu_page(
			'settings.php',
			__( 'Rechtstexte Generator', 'frontend-rechtstexte-generator' ),
			__( 'Rechtstexte Generator', 'frontend-rechtstexte-generator' ),
			'manage_network_options',
			'frg-network-settings',
			array( __CLASS__, 'render_network_page' )
		);
	}

	public static function render_network_page(): void {
		if ( ! is_multisite() || ! current_user_can( 'manage_network_options' ) ) {
			return;
		}

		if ( isset( $_POST['frg_save_network_settings'] ) ) {
			check_admin_referer( 'frg_save_network_settings_action', 'frg_save_network_settings_nonce' );
			self::save_settings(
				array(
					'enabled'           => ! empty( $_POST['enabled'] ),
					'source_blog_id'    => isset( $_POST['source_blog_id'] ) ? absint( $_POST['source_blog_id'] ) : get_main_site_id(),
					'source_profile_id' => isset( $_POST['source_profile_id'] ) ? absint( $_POST['source_profile_id'] ) : 0,
				)
			);
			echo '<div class="notice notice-success"><p>' . esc_html__( 'Netzwerk-Einstellungen gespeichert.', 'frontend-rechtstexte-generator' ) . '</p></div>';
		}

		$settings          = self::get_settings();
		$source_blog_id    = absint( $settings['source_blog_id'] ?? get_main_site_id() );
		$source_profile_id = absint( $settings['source_profile_id'] ?? 0 );
		$sites             = get_sites( array( 'number' => 0 ) );
		$profiles          = self::get_profiles_for_blog( $source_blog_id );
		?>
		<div class="wrap frg-admin">
			<h1><?php esc_html_e( 'Rechtstexte Generator Netzwerk', 'frontend-rechtstexte-generator' ); ?></h1>
			<div class="frg-admin-card frg-admin-card--status">
				<h2><?php esc_html_e( 'Plugin-Status', 'frontend-rechtstexte-generator' ); ?></h2>
				<p><strong><?php esc_html_e( 'Plugin-Version', 'frontend-rechtstexte-generator' ); ?>:</strong> <?php echo esc_html( FRG_VERSION ); ?></p>
				<p><strong><?php esc_html_e( 'Zentraler Modus', 'frontend-rechtstexte-generator' ); ?>:</strong> <?php echo ! empty( $settings['enabled'] ) ? esc_html__( 'aktiv', 'frontend-rechtstexte-generator' ) : esc_html__( 'inaktiv', 'frontend-rechtstexte-generator' ); ?></p>
				<p><strong><?php esc_html_e( 'Master-Site', 'frontend-rechtstexte-generator' ); ?>:</strong> #<?php echo esc_html( (string) $source_blog_id ); ?></p>
			</div>
			<form method="post" class="frg-admin-card">
				<?php wp_nonce_field( 'frg_save_network_settings_action', 'frg_save_network_settings_nonce' ); ?>
				<h2><?php esc_html_e( 'Zentrale Ausgabe für Multisite', 'frontend-rechtstexte-generator' ); ?></h2>
				<p><?php esc_html_e( 'Wenn der zentrale Modus aktiv ist, geben die Shortcodes auf allen Unterseiten die Inhalte der gewaehlten Master-Site aus.', 'frontend-rechtstexte-generator' ); ?></p>
				<table class="form-table" role="presentation">
					<tr>
						<th scope="row"><?php esc_html_e( 'Zentraler Modus', 'frontend-rechtstexte-generator' ); ?></th>
						<td>
							<label>
								<input type="checkbox" name="enabled" value="1" <?php checked( ! empty( $settings['enabled'] ) ); ?>>
								<?php esc_html_e( 'Impressum und Datenschutzerklärung netzwerkweit zentral ausgeben', 'frontend-rechtstexte-generator' ); ?>
							</label>
						</td>
					</tr>
					<tr>
						<th scope="row"><label for="source_blog_id"><?php esc_html_e( 'Master-Site', 'frontend-rechtstexte-generator' ); ?></label></th>
						<td>
							<select name="source_blog_id" id="source_blog_id">
								<?php foreach ( $sites as $site ) : ?>
									<?php
									$blog_id = absint( $site->blog_id );
									$details = get_blog_details( $blog_id );
									$label   = $details ? $details->blogname . ' (#' . $blog_id . ')' : '#' . $blog_id;
									?>
									<option value="<?php echo esc_attr( (string) $blog_id ); ?>" <?php selected( $source_blog_id, $blog_id ); ?>><?php echo esc_html( $label ); ?></option>
								<?php endforeach; ?>
							</select>
							<p class="description"><?php esc_html_e( 'Auf dieser Site pflegen Sie den Wizard, die Profile und die Block-Registry.', 'frontend-rechtstexte-generator' ); ?></p>
						</td>
					</tr>
					<tr>
						<th scope="row"><label for="source_profile_id"><?php esc_html_e( 'Zentrales Profil', 'frontend-rechtstexte-generator' ); ?></label></th>
						<td>
							<select name="source_profile_id" id="source_profile_id">
								<option value="0" <?php selected( $source_profile_id, 0 ); ?>><?php esc_html_e( 'Zuletzt gespeichertes Profil der Master-Site verwenden', 'frontend-rechtstexte-generator' ); ?></option>
								<?php foreach ( $profiles as $profile ) : ?>
									<option value="<?php echo esc_attr( (string) $profile['id'] ); ?>" <?php selected( $source_profile_id, (int) $profile['id'] ); ?>>
										<?php echo esc_html( '#' . $profile['id'] . ' - ' . $profile['profile_name'] ); ?>
									</option>
								<?php endforeach; ?>
							</select>
							<p class="description"><?php esc_html_e( 'Wenn kein konkretes Profil gewaehlt ist, wird automatisch das zuletzt aktualisierte Profil der Master-Site genutzt.', 'frontend-rechtstexte-generator' ); ?></p>
						</td>
					</tr>
				</table>
				<p><button type="submit" name="frg_save_network_settings" class="button button-primary"><?php esc_html_e( 'Netzwerk-Einstellungen speichern', 'frontend-rechtstexte-generator' ); ?></button></p>
			</form>
			<div class="frg-admin-card">
				<h2><?php esc_html_e( 'Verwendung auf Unterseiten', 'frontend-rechtstexte-generator' ); ?></h2>
				<p><?php esc_html_e( 'Fuegen Sie auf den Unterseiten wie gewohnt die Shortcodes ein. Die Ausgabe wird zentral aus der Master-Site geladen.', 'frontend-rechtstexte-generator' ); ?></p>
				<p><code>[frg_impressum]</code> <code>[frg_datenschutz]</code> <code>[frg_last_updated]</code></p>
			</div>
		</div>
		<?php
	}

	public static function is_central_output_enabled(): bool {
		if ( ! is_multisite() ) {
			return false;
		}

		$settings = self::get_settings();
		return ! empty( $settings['enabled'] ) && absint( $settings['source_blog_id'] ?? 0 ) > 0;
	}

	public static function is_source_blog(): bool {
		if ( ! is_multisite() ) {
			return true;
		}

		$settings = self::get_settings();
		return get_current_blog_id() === absint( $settings['source_blog_id'] ?? get_main_site_id() );
	}

	public static function get_source_blog_id(): int {
		$settings = self::get_settings();
		return absint( $settings['source_blog_id'] ?? get_main_site_id() );
	}

	public static function get_source_profile_id(): int {
		$settings = self::get_settings();
		return absint( $settings['source_profile_id'] ?? 0 );
	}

	public static function get_central_profile( FRG_Storage $storage ): ?array {
		if ( ! self::is_central_output_enabled() ) {
			return null;
		}

		$settings          = self::get_settings();
		$source_blog_id    = absint( $settings['source_blog_id'] ?? get_main_site_id() );
		$source_profile_id = absint( $settings['source_profile_id'] ?? 0 );
		$profile           = null;

		switch_to_blog( $source_blog_id );
		try {
			$profile = $source_profile_id > 0 ? $storage->get_profile_by_id( $source_profile_id ) : $storage->get_latest_profile();
		} finally {
			restore_current_blog();
		}

		if ( $profile ) {
			$profile['source_blog_id'] = $source_blog_id;
		}

		return $profile;
	}

	private static function get_profiles_for_blog( int $blog_id ): array {
		$profiles = array();
		$storage  = new FRG_Storage();

		switch_to_blog( $blog_id );
		try {
			$profiles = $storage->get_all_profiles();
		} finally {
			restore_current_blog();
		}

		return $profiles;
	}

	private static function get_settings(): array {
		$defaults = array(
			'enabled'           => false,
			'source_blog_id'    => is_multisite() ? get_main_site_id() : 0,
			'source_profile_id' => 0,
		);
		$stored = get_site_option( self::NETWORK_OPTION, array() );

		return wp_parse_args( is_array( $stored ) ? $stored : array(), $defaults );
	}

	private static function save_settings( array $settings ): void {
		update_site_option(
			self::NETWORK_OPTION,
			array(
				'enabled'           => ! empty( $settings['enabled'] ),
				'source_blog_id'    => absint( $settings['source_blog_id'] ?? get_main_site_id() ),
				'source_profile_id' => absint( $settings['source_profile_id'] ?? 0 ),
			)
		);
	}
}

<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
?>
<div class="frg-preview__section">
	<div class="frg-notice frg-notice--warning"><?php echo wp_kses_post( $notice ); ?></div>
	<h4><?php esc_html_e( 'Vorschau Datenschutzerklärung', 'frontend-rechtstexte-generator' ); ?></h4>
	<?php if ( ! empty( $compliance_warnings ) ) : ?>
		<div class="frg-notice frg-notice--warning">
			<strong><?php esc_html_e( 'Hinweise zur Vollständigkeit', 'frontend-rechtstexte-generator' ); ?></strong>
			<ul>
				<?php foreach ( $compliance_warnings as $compliance_warning ) : ?>
					<li><?php echo esc_html( $compliance_warning ); ?></li>
				<?php endforeach; ?>
			</ul>
		</div>
	<?php endif; ?>
	<div class="frg-preview__content"><?php echo wp_kses_post( $privacy ); ?></div>
</div>

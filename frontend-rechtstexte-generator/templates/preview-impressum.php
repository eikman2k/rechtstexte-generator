<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
?>
<div class="frg-preview__section">
	<div class="frg-notice frg-notice--warning"><?php echo wp_kses_post( $notice ); ?></div>
	<h4><?php esc_html_e( 'Vorschau Impressum', 'frontend-rechtstexte-generator' ); ?></h4>
	<div class="frg-preview__content"><?php echo wp_kses_post( $impressum ); ?></div>
</div>

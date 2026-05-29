<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class FRG_Shortcodes {
	private FRG_Storage $storage;
	private FRG_Generator $generator;
	private FRG_Frontend_Wizard $wizard;

	public function __construct( FRG_Storage $storage, FRG_Generator $generator, FRG_Frontend_Wizard $wizard ) {
		$this->storage   = $storage;
		$this->generator = $generator;
		$this->wizard    = $wizard;
	}

	public function register(): void {
		add_shortcode( 'frg_rechtstexte_wizard', array( $this, 'render_wizard' ) );
		add_shortcode( 'frg_impressum', array( $this, 'render_impressum' ) );
		add_shortcode( 'frg_datenschutz', array( $this, 'render_privacy' ) );
		add_shortcode( 'frg_last_updated', array( $this, 'render_last_updated' ) );
	}

	public function render_wizard(): string {
		return $this->wizard->render();
	}

	public function render_impressum(): string {
		$profile = $this->get_display_profile();
		if ( empty( $profile['data'] ) ) {
			return '';
		}

		return wp_kses_post( $this->generator->generate_impressum( $profile['data'] ) );
	}

	public function render_privacy(): string {
		$profile = $this->get_display_profile();
		if ( empty( $profile['data'] ) ) {
			return '';
		}

		return wp_kses_post( $this->generator->generate_privacy_policy( $profile['data'] ) );
	}

	public function render_last_updated(): string {
		$profile = $this->get_display_profile();
		if ( empty( $profile['updated_at'] ) ) {
			return '';
		}

		return esc_html( mysql2date( get_option( 'date_format' ), $profile['updated_at'] ) );
	}

	private function get_display_profile(): ?array {
		$profile = $this->wizard->get_current_profile();
		if ( ! empty( $profile['data'] ) ) {
			return $profile;
		}

		return $this->storage->get_latest_profile();
	}
}

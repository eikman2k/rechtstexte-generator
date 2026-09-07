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
		$this->enqueue_output_assets();

		if ( FRG_Multisite::is_central_output_enabled() ) {
			return $this->render_central_document( 'impressum' );
		}

		$profile = $this->get_display_profile();
		if ( empty( $profile['data'] ) ) {
			return '';
		}

		return wp_kses_post( $this->generator->generate_impressum( $profile['data'] ) );
	}

	public function render_privacy(): string {
		$this->enqueue_output_assets();

		if ( FRG_Multisite::is_central_output_enabled() ) {
			return $this->render_central_document( 'privacy' );
		}

		$profile = $this->get_display_profile();
		if ( empty( $profile['data'] ) ) {
			return '';
		}

		return wp_kses_post( $this->generator->generate_privacy_policy( $profile['data'] ) );
	}

	public function render_last_updated(): string {
		if ( FRG_Multisite::is_central_output_enabled() ) {
			$profile = null;
			$source_blog_id = FRG_Multisite::get_source_blog_id();

			switch_to_blog( $source_blog_id );
			try {
				$profile = $this->get_central_profile_in_source_context();
			} finally {
				restore_current_blog();
			}

			if ( empty( $profile['updated_at'] ) ) {
				return '';
			}

			return esc_html( mysql2date( get_option( 'date_format' ), $profile['updated_at'] ) );
		}

		$profile = $this->get_display_profile();
		if ( empty( $profile['updated_at'] ) ) {
			return '';
		}

		return esc_html( mysql2date( get_option( 'date_format' ), $profile['updated_at'] ) );
	}

	private function enqueue_output_assets(): void {
		wp_enqueue_style( 'frg-frontend' );
	}

	private function get_display_profile(): ?array {
		$profile = $this->wizard->get_current_profile();
		if ( ! empty( $profile['data'] ) ) {
			return $profile;
		}

		return $this->storage->get_latest_profile();
	}

	private function render_central_document( string $type ): string {
		$output = '';
		$source_blog_id = FRG_Multisite::get_source_blog_id();

		switch_to_blog( $source_blog_id );
		try {
			$profile = $this->get_central_profile_in_source_context();
			if ( empty( $profile['data'] ) ) {
				return '';
			}

			$output = 'impressum' === $type
				? $this->generator->generate_impressum( $profile['data'] )
				: $this->generator->generate_privacy_policy( $profile['data'] );
		} finally {
			restore_current_blog();
		}

		return wp_kses_post( $output );
	}

	private function get_central_profile_in_source_context(): ?array {
		$source_profile_id = FRG_Multisite::get_source_profile_id();
		return $source_profile_id > 0 ? $this->storage->get_profile_by_id( $source_profile_id ) : $this->storage->get_latest_profile();
	}
}

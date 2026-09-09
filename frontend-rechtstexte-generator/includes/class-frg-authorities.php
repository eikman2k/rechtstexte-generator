<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class FRG_Authorities {
	/**
	 * Returns the general state supervisory authorities for private-sector websites.
	 *
	 * Public bodies, churches, press and broadcasting can be subject to different authorities.
	 * Contact details should be reviewed periodically against the official authority websites.
	 *
	 * @return array<string, array{name: string, address: string, url: string}>
	 */
	public static function get_by_state(): array {
		return array(
			'baden-wuerttemberg' => array(
				'name'    => 'Der Landesbeauftragte für den Datenschutz und die Informationsfreiheit Baden-Württemberg',
				'address' => "Lautenschlagerstraße 20\n70173 Stuttgart\nDeutschland",
				'url'     => 'https://www.baden-wuerttemberg.datenschutz.de/',
			),
			'bayern' => array(
				'name'    => 'Bayerisches Landesamt für Datenschutzaufsicht (BayLDA)',
				'address' => "Promenade 18\n91522 Ansbach\nDeutschland",
				'url'     => 'https://www.lda.bayern.de/',
			),
			'berlin' => array(
				'name'    => 'Berliner Beauftragte für Datenschutz und Informationsfreiheit',
				'address' => "Alt-Moabit 59-61\n10555 Berlin\nDeutschland",
				'url'     => 'https://www.datenschutz-berlin.de/',
			),
			'brandenburg' => array(
				'name'    => 'Die Landesbeauftragte für den Datenschutz und für das Recht auf Akteneinsicht Brandenburg',
				'address' => "Stahnsdorfer Damm 77\n14532 Kleinmachnow\nDeutschland",
				'url'     => 'https://www.lda.brandenburg.de/',
			),
			'bremen' => array(
				'name'    => 'Die Landesbeauftragte für Datenschutz und Informationsfreiheit der Freien Hansestadt Bremen',
				'address' => "Arndtstraße 1\n27570 Bremerhaven\nDeutschland",
				'url'     => 'https://www.datenschutz.bremen.de/',
			),
			'hamburg' => array(
				'name'    => 'Der Hamburgische Beauftragte für Datenschutz und Informationsfreiheit',
				'address' => "Ludwig-Erhard-Straße 22, 7. OG\n20459 Hamburg\nDeutschland",
				'url'     => 'https://datenschutz-hamburg.de/',
			),
			'hessen' => array(
				'name'    => 'Der Hessische Beauftragte für Datenschutz und Informationsfreiheit',
				'address' => "Gustav-Stresemann-Ring 1\n65189 Wiesbaden\nDeutschland",
				'url'     => 'https://datenschutz.hessen.de/',
			),
			'mecklenburg-vorpommern' => array(
				'name'    => 'Der Landesbeauftragte für Datenschutz und Informationsfreiheit Mecklenburg-Vorpommern',
				'address' => "Werderstraße 74a\n19055 Schwerin\nDeutschland",
				'url'     => 'https://www.datenschutz-mv.de/',
			),
			'niedersachsen' => array(
				'name'    => 'Der Landesbeauftragte für den Datenschutz Niedersachsen',
				'address' => "Prinzenstraße 5\n30159 Hannover\nDeutschland",
				'url'     => 'https://www.lfd.niedersachsen.de/',
			),
			'nordrhein-westfalen' => array(
				'name'    => 'Landesbeauftragte für Datenschutz und Informationsfreiheit Nordrhein-Westfalen',
				'address' => "Kavalleriestraße 2-4\n40213 Düsseldorf\nDeutschland",
				'url'     => 'https://www.ldi.nrw.de/',
			),
			'rheinland-pfalz' => array(
				'name'    => 'Der Landesbeauftragte für den Datenschutz und die Informationsfreiheit Rheinland-Pfalz',
				'address' => "Hintere Bleiche 34\n55116 Mainz\nDeutschland",
				'url'     => 'https://www.datenschutz.rlp.de/',
			),
			'saarland' => array(
				'name'    => 'Unabhängiges Datenschutzzentrum Saarland',
				'address' => "Fritz-Dobisch-Straße 12\n66111 Saarbrücken\nDeutschland",
				'url'     => 'https://www.datenschutz.saarland.de/',
			),
			'sachsen' => array(
				'name'    => 'Sächsische Datenschutz- und Transparenzbeauftragte',
				'address' => "Devrientstraße 5\n01067 Dresden\nDeutschland",
				'url'     => 'https://www.datenschutz.sachsen.de/',
			),
			'sachsen-anhalt' => array(
				'name'    => 'Landesbeauftragter für den Datenschutz Sachsen-Anhalt',
				'address' => "Leiterstraße 9\n39104 Magdeburg\nDeutschland",
				'url'     => 'https://datenschutz.sachsen-anhalt.de/',
			),
			'schleswig-holstein' => array(
				'name'    => 'Unabhängiges Landeszentrum für Datenschutz Schleswig-Holstein',
				'address' => "Holstenstraße 98\n24103 Kiel\nDeutschland",
				'url'     => 'https://www.datenschutzzentrum.de/',
			),
			'thueringen' => array(
				'name'    => 'Thüringer Landesbeauftragter für den Datenschutz und die Informationsfreiheit',
				'address' => "Häßlerstraße 8\n99096 Erfurt\nDeutschland",
				'url'     => 'https://www.tlfdi.de/',
			),
		);
	}

	/**
	 * @return array<string, string>
	 */
	public static function get_state_labels(): array {
		return array(
			'baden-wuerttemberg'    => __( 'Baden-Württemberg', 'frontend-rechtstexte-generator' ),
			'bayern'                => __( 'Bayern', 'frontend-rechtstexte-generator' ),
			'berlin'                => __( 'Berlin', 'frontend-rechtstexte-generator' ),
			'brandenburg'           => __( 'Brandenburg', 'frontend-rechtstexte-generator' ),
			'bremen'                => __( 'Bremen', 'frontend-rechtstexte-generator' ),
			'hamburg'               => __( 'Hamburg', 'frontend-rechtstexte-generator' ),
			'hessen'                => __( 'Hessen', 'frontend-rechtstexte-generator' ),
			'mecklenburg-vorpommern' => __( 'Mecklenburg-Vorpommern', 'frontend-rechtstexte-generator' ),
			'niedersachsen'         => __( 'Niedersachsen', 'frontend-rechtstexte-generator' ),
			'nordrhein-westfalen'   => __( 'Nordrhein-Westfalen', 'frontend-rechtstexte-generator' ),
			'rheinland-pfalz'       => __( 'Rheinland-Pfalz', 'frontend-rechtstexte-generator' ),
			'saarland'              => __( 'Saarland', 'frontend-rechtstexte-generator' ),
			'sachsen'               => __( 'Sachsen', 'frontend-rechtstexte-generator' ),
			'sachsen-anhalt'        => __( 'Sachsen-Anhalt', 'frontend-rechtstexte-generator' ),
			'schleswig-holstein'    => __( 'Schleswig-Holstein', 'frontend-rechtstexte-generator' ),
			'thueringen'            => __( 'Thüringen', 'frontend-rechtstexte-generator' ),
		);
	}

	public static function is_valid_state( string $state ): bool {
		return isset( self::get_by_state()[ $state ] );
	}
}

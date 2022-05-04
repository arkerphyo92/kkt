<?php
/**
 * WP Courseware i18n Implementation.
 *
 * @package WPCW
 * @subpackage Core
 * @since 4.3.0
 */
namespace WPCW\Core;

// Exit if accessed directly
defined( 'ABSPATH' ) || exit;

/**
 * Class i18n.
 *
 * @since 4.3.0
 */
final class i18n {

	/**
	 * Load i18n Functionality.
	 *
	 * This loads the textdomain for the plugin
	 * that allows gettext translations.
	 *
	 * @since 4.3.0
	 */
	public function load() {
		// Load Legacy Textdomain.
		add_filter( 'load_textdomain_mofile', array( $this, 'load_legacy_textdomain' ), 10, 2 );

		/**
		 * Filter WP Courseware Languages Directory Path.
		 *
		 * Allow developers to override the languages directory path.
		 *
		 * @since 4.3.0
		 *
		 * @param string The path to the languages folder.
		 */
		$lang_dir = apply_filters( 'wpcw_languages_directory', WPCW_LANG_PATH );

		/**
		 * Filter WP Courseware Locale.
		 *
		 * Allow developers to change the plugin locale.
		 *
		 * @since 4.3.0
		 *
		 * @param string The locale ID.
		 * @param string The plugin locale ID.
		 */
		$locale = apply_filters( 'plugin_locale', get_locale(), 'wp-courseware' );

		// Specify the .mo file
		$mofile = sprintf( '%1$s-%2$s.mo', 'wp-courseware', $locale );

		// Look for wp-content/languages/wp_courseware/wp-courseware-{lang}_{country}.mo
		$mofile_global1 = WP_LANG_DIR . '/wp_courseware/wp-courseware-' . $locale . '.mo';

		// Look for wp-content/languages/wp_courseware/wp_courseware-{lang}_{country}.mo
		$mofile_global2 = WP_LANG_DIR . '/wp_courseware/wp_courseware-' . $locale . '.mo';

		// Look in wp-content/languages/plugins/wp-courseware-{lang}_{country}.mo
		$mofile_global3 = WP_LANG_DIR . '/plugins/wp-courseware/' . $mofile;

		// Check if one of the language file exists
		if ( file_exists( $mofile_global1 ) ) {
			load_textdomain( 'wp-courseware', $mofile_global1 );
		} elseif ( file_exists( $mofile_global2 ) ) {
			load_textdomain( 'wp-courseware', $mofile_global2 );
		} elseif ( file_exists( $mofile_global3 ) ) {
			load_textdomain( 'wp-courseware', $mofile_global3 );
		} else {
			load_plugin_textdomain( 'wp-courseware', false, $lang_dir );
		}
	}

	/**
	 * Load Legacy Texdomain.
	 *
	 * Load a .mo file for the old textdomain if one exists.
	 *
	 * @since 4.3.0
	 *
	 * @link https://github.com/10up/grunt-wp-plugin/issues/21#issuecomment-62003284
	 *
	 * @param string $mofile
	 * @param string $textdomain
	 *
	 * @return string
	 */
	public function load_legacy_textdomain( $mofile, $textdomain ) {
		if ( $textdomain === 'wp-courseware' && ! file_exists( $mofile ) ) {
			$mofile = dirname( $mofile ) . DIRECTORY_SEPARATOR . str_replace( $textdomain, 'wp_courseware', basename( $mofile ) );
		}

		return $mofile;
	}
}
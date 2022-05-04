<?php
/**
 * WP Courseware Settings Form Builder Utility Class
 *
 * A set of functions for fetching and saving settings to a single setting row in
 * the WordPress database by storing all of the plugin settings in an array.
 *
 * @package WPCW
 * @since 4.3.0
 */

if ( ! class_exists( 'SettingsForm' ) ) {
	/**
	 * Class SettingsForm.
	 *
	 * @since 1.0.0
	 */
	class SettingsForm extends EasyForm {
		/**
		 * The string used when saving the settings to the database.
		 * @var String
		 */
		protected $settingPrefix;

		/**
		 * The message to show when saving a setting.
		 */
		public $msg_settingsSaved;

		/**
		 * The message to show when there's an issue with a setting.
		 */
		public $msg_settingsProblem;

		/**
		 * Default constructor that takes in initial parameters and setting prefix.
		 *
		 * @param Array $paramList The list of parameters to create the form from.
		 * @param String $settingPrefix The prefix to use for the settings.
		 * @param String $formID The optional ID to give to the form. Ideal for when there's more than 1 form on a page.
		 */
		public function __construct( $paramList, $settingPrefix, $formID = false ) {
			parent::__construct( $paramList, $formID );

			// Default save text reflects this is a settings form
			$this->buttonText = __( 'Save Settings' );

			// Store the setting prefix.
			$this->settingPrefix = $settingPrefix;

			// Messages
			$this->msg_settingsSaved   = __( 'Settings successfully saved.' );
			$this->msg_settingsProblem = __( 'There was a problem saving the settings.' );

			// Load default values
			parent::loadDefaults( WPCW_TidySettings_getSettings( $settingPrefix ) );
		}

		/**
		 * Method called when settings form details are being saved.
		 *
		 * @param Array $formValues The list of settings being saved.
		 *
		 * @see wplib/EasyForm::handleSave()
		 */
		protected function handleSave( $formValues ) {
			// Get existing settings first (in case we don't have all the setting to play with
			// on a certain page), then merge changes.
			$originalSettings = WPCW_TidySettings_getSettings( $this->settingPrefix );
			foreach ( $formValues as $name => $value ) {
				$originalSettings[ $name ] = $value;
			}

			$saveSuccess = WPCW_TidySettings_saveSettings( $originalSettings, $this->settingPrefix );
			if ( $saveSuccess ) {
				$this->messages = $this->showMessage( $this->msg_settingsSaved );
			} else {
				$this->messages = $this->showMessage( $this->msg_settingsProblem, true );
			}
		}
	}
}
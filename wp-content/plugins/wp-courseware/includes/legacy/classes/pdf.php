<?php
/**
 * WP Courseware PDF Quiz Results.
 *
 * Allows a summary of a user's quiz results as a PDF to be created dynamically
 * by WP Courseware using the fpdf.php library.
 *
 * @package WPCW
 * @since 1.0.0
 */

if ( ! class_exists( 'WPCW_PDF' ) ) {
	/**
	 * Class WPCW_PDF.
	 *
	 * Extends FPDF to use basic HTML with the results details.
	 *
	 * @since 1.0.0
	 */
	class WPCW_PDF extends TCPDF {
		var $footerString;

		/**
		 * Render the page header.
		 */
		public function Header() {
			$this->SetY( 15 );

			// Set font
			$this->SetFont( 'dejavusansb', 'B', 20 );

			// Title
			$this->Cell( 0, 15, __( 'Your Quiz Results', 'wp-courseware' ), 0, false, 'C', 0, '', 0, false, 'M', 'M' );
		}


		/**
		 * Render the page footer with page number and details.
		 */
		public function Footer() {
			// Set font
			$this->SetFont( 'dejavusans', '', 8 );

			// Page number
			$this->SetY( - 18 );
			$this->Cell( 0, 8, sprintf( __( 'Page %s of %s', 'wp-courseware' ), $this->getAliasNumPage(), $this->getAliasNbPages() ), 0, false, 'C', 0, '', 0, false, 'T', 'M' );

			// Copyight-style link
			$this->SetY( - 12 );
			$this->Cell( 0, 0, $this->footerString, 0, 0, 'C', 0, false );
		}


		/**
		 * Set the string that appears in the footer.
		 *
		 * @param String $str The string that appears in the footer.
		 */
		function setFooterString( $str ) {
			$this->footerString = $str;
		}
	}
}

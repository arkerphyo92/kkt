<?php
/**
 * WP Courseware PDF Certificates.
 *
 * Allows PDF certificates to be created dynamically
 * by WP Courseware using the fpdf.php library.
 *
 * @package WPCW
 * @since 1.0.0
 */

if ( ! class_exists( 'WPCW_Certificate' ) ) {
	/**
	 * Class WPCW_Certificate
	 *
	 * @since 1.0.0
	 */
	class WPCW_Certificate {

		protected $pdffile;

		/**
		 * Size parameters that store the size of the page.
		 */
		protected $size_width;

		protected $size_height;

		protected $size_name;

		/**
		 * Position on x-axis of where the signature starts.
		 *
		 * @var Integer
		 */
		protected $signature_X;

		/**
		 * Position on y-axis of line where signature should be.
		 *
		 * @var Integer
		 */
		protected $footer_Y;

		/**
		 * The length of the line for the footer lines.
		 *
		 * @var Integer
		 */
		protected $footer_line_length;

		/**
		 * A list of the settings to use for the certificate generation.
		 *
		 * @var Array
		 */
		protected $settingsList;

		/**
		 * Detault Settings
		 *
		 * @var Array
		 */
		protected $defaultSettings;

		/**
		 * A list of course details
		 *
		 * @var object
		 */
		protected $courseDetails;

		/**
		 * WPCW_Certificate constructor.
		 *
		 * @param object $courseDetails
		 * @param string $size
		 */
		public function __construct( $courseDetails = null, $size = 'A4' ) {
			require_once WPCW_LIB_PATH . 'tcpdf/tcpdf_import.php';

			// Update size variables to allow calculations for distance.
			$this->setSize( $size );

			// Create basic page layout
			$this->pdffile = new TCPDF( 'L', 'mm', 'A4', true, 'UTF-8', false );
			$this->pdffile->AddPage();

			// Load course Details
			$this->courseDetails   = $courseDetails;
			$this->defaultSettings = WPCW_TidySettings_getSettings( WPCW_DATABASE_SETTINGS_KEY );

			// Load the certificate settings
			$this->settingsList = $this->loadCourseCertificateSettings();
		}

		/**
		 * Load Certificate Settings
		 */
		protected function loadCourseCertificateSettings() {
			// Check
			if ( is_null( $this->courseDetails ) ) {
				return $this->defaultSettings;
			}

			// Get Default Values
			$defaultValues = array(
				'cert_signature_type'        => WPCW_arrays_getValue( $this->defaultSettings, 'cert_signature_type', 'text' ),
				'cert_sig_text'              => WPCW_arrays_getValue( $this->defaultSettings, 'cert_sig_text', get_bloginfo( 'name' ) ),
				'cert_sig_image_url'         => WPCW_arrays_getValue( $this->defaultSettings, 'cert_sig_image_url', '' ),
				'cert_logo_enabled'          => WPCW_arrays_getValue( $this->defaultSettings, 'cert_logo_enabled', 'no_cert_logo' ),
				'cert_logo_url'              => WPCW_arrays_getValue( $this->defaultSettings, 'cert_logo_url', '' ),
				'cert_background_type'       => WPCW_arrays_getValue( $this->defaultSettings, 'cert_background_type', 'use_default' ),
				'cert_background_custom_url' => WPCW_arrays_getValue( $this->defaultSettings, 'cert_background_custom_url', '' ),
				'certificate_encoding'       => WPCW_arrays_getValue( $this->defaultSettings, 'certificate_encoding', 'ISO-8859-1' ),
			);

			// Go through them
			foreach ( $defaultValues as $defaultValueKey => $defaultValue ) {
				$this->settingsList[ $defaultValueKey ] = ( isset( $this->courseDetails->{$defaultValueKey} ) ) ? $this->courseDetails->{$defaultValueKey} : $defaultValue;
			}

			return $this->settingsList;
		}

		/**
		 * Given a width, find out the position of the left side of the object to be added.
		 *
		 * @param Integer $width The width of the item to position.
		 *
		 * @return Integer The x-coordinate of the item to position to center it.
		 */
		public function getLeftOfCentre( $width ) {
			return ( ( $this->size_width - $width ) / 2 );
		}

		/**
		 * Given a width, find out the position of the right side of the object to be added.
		 *
		 * @param Integer $width The width of the item to position.
		 *
		 * @return Integer The x-coordinate of the item to position to center it.
		 */
		public function getRightOfCentre( $width ) {
			return ( ( $this->size_width + $width ) / 2 );
		}

		/**
		 * Given a string, write it to the center of the page.
		 *
		 * @param String  $str The string to center.
		 * @param Integer $y_pos The Y-coordinate of the string to position.
		 */
		public function centerString( $str, $y_pos ) {
			$str_width = $this->pdffile->GetStringWidth( $str );
			$str_x     = $this->getLeftOfCentre( $str_width );

			$this->pdffile->SetXY( $str_x, $y_pos );
			$this->pdffile->Cell( 0, 0, $str, false, false );
		}

		/**
		 * Draw a centered line at the specified height.
		 *
		 * @param Integer $width The width of the line.
		 * @param Integer $y_pos The Y-coordinate of the string to position.
		 */
		public function centerLine( $width, $y_pos ) {
			$x = $this->getLeftOfCentre( $width );
			$this->pdffile->Line( $x, $y_pos, $x + $width, $y_pos );
		}

		/**
		 * Set up the internal variables for size.
		 */
		public function setSize( $size ) {
			switch ( $size ) {
				// A4 Size
				default:
					$this->size_name   = 'A4';
					$this->size_width  = 297;
					$this->size_height = 210;
					break;
			}
		}


		public function hex2RGB( $hexStr, $returnAsString = false, $seperator = ',' ) {
			$hexStr   = preg_replace( '/[^0-9A-Fa-f]/', '', $hexStr ); // Gets a proper hex string
			$rgbArray = array();
			if ( strlen( $hexStr ) == 6 ) { // If a proper hex code, convert using bitwise operation. No overhead... faster
				$colorVal          = hexdec( $hexStr );
				$rgbArray['red']   = 0xFF & ( $colorVal >> 0x10 );
				$rgbArray['green'] = 0xFF & ( $colorVal >> 0x8 );
				$rgbArray['blue']  = 0xFF & $colorVal;
			} elseif ( strlen( $hexStr ) == 3 ) { // if shorthand notation, need some string manipulations
				$rgbArray['red']   = hexdec( str_repeat( substr( $hexStr, 0, 1 ), 2 ) );
				$rgbArray['green'] = hexdec( str_repeat( substr( $hexStr, 1, 1 ), 2 ) );
				$rgbArray['blue']  = hexdec( str_repeat( substr( $hexStr, 2, 1 ), 2 ) );
			} else {
				return false; // Invalid hex color code
			}
			return $returnAsString ? implode( $seperator, $rgbArray ) : $rgbArray; // returns the rgb string or the associative array
		}

		public function generateDynamicPDF( $block_position, $inner_block_name, $block_attr, $block_inner_blocks, $user_details ) {

			$block                   = $block_attr;
			$block_text              = ( isset( $block['textField'] ) && ! empty( $block['textField'] ) ) ? $block['textField'] : '';
			$block_family            = ( isset( $block['fontFamily'] ) && ! empty( $block['fontFamily'] ) ) ? $block['fontFamily'] : 'Lato-Black';
			$block_font_family_style = ( isset( $block['fontWeight'] ) && ! empty( $block['fontWeight'] ) ) ? 'B' : '';
			$block_font_underline    = ( isset( $block['textUnderline'] ) && ! empty( $block['textUnderline'] ) ) ? 'U' : '';
			$block_font_align        = ( isset( $block['textAlign'] ) && ! empty( $block['textAlign'] ) ) ? $block['textAlign'] : 'left';
			$block_font_family_size  = ( isset( $block['textAlign'] ) && ! empty( $block['fontSize'] ) ) ? $block['fontSize'] : 16;
			$block_font_color        = ( isset( $block['textColor'] ) && ! empty( $block['textColor'] ) ) ? $block1['textColor'] : '#000000';
			list($r, $g, $b)         = sscanf( $block_font_color, '#%02x%02x%02x' );

			if ( 'center' === $block_font_align ) {
				$align = 'C';
			} elseif ( 'right' === $block_font_align ) {
				$align = 'R';
			} else {
				$align = 'L';
			}
			$fontname = TCPDF_FONTS::addTTFfont( WPCW_PDF_FONTS . $block_family . '.ttf', 'TrueTypeUnicode', '', 96 );
			$this->pdffile->SetFont( $fontname, $block_font_underline, $block_font_family_size, '', false );
			$this->pdffile->SetTextColor( $r, $g, $b );

			$course_title       = $user_details['course_name'] ? $user_details['course_name'] : 'Course Title';
			$student_name       = $user_details['student_name'] ? $user_details['student_name'] : 'Student Name';
			$instructor_name    = $user_details['instructor_name'] ? $user_details['instructor_name'] : __( 'Instructor Name', 'wp-courseware' );
			$certificate_number = $user_details['certificate_number'] ? $user_details['certificate_number'] : 'Certificate Number';
			$completeDate       = $user_details['completeDate'] ? $user_details['completeDate'] : current_time( 'timestamp' );
			$cumulative_grade   = $user_details['cumulativeGrade'] ? $user_details['cumulativeGrade'] : 'XX%';

			$topLinex                 = 45;
			$topLineY                 = 45;
			$this->footer_line_length = 60;
			$this->footer_Y           = 120;
			$date_X                   = 40;
			$this->signature_X        = $this->size_width - 95;
			$y                        = 120;
			$columns                  = array(
				array(
					'w' => 90,
					's' => 1,
					'y' => $y,
				),
				array(
					'w' => 90,
					's' => 1,
					'y' => $y,
				),
				array(
					'w' => 70,
					's' => 1,
					'y' => $y,
				),
			);
			if ( 'block_2' === $block_position ) {
				$this->pdffile->SetColumnsArray( $columns );
			}
			switch ( $block_position ) {
				case 'block_0':
					$this->pdffile->SetXY( $topLinex, $topLineY );
					switch ( $inner_block_name ) {
						case 'wpcw/course-title':
							$this->pdffile->Cell( 210, 0, mb_strtoupper( $course_title ), false, false, $align );
							break;
						case 'wpcw/student-name':
							$this->pdffile->Cell( 210, 0, mb_strtoupper( $student_name ), false, false, $align );
							break;
						case 'wpcw/text-field':
							$this->pdffile->Cell( 210, 0, $block_text, false, false, $align );
							break;
					}
					break;
				case 'block_1':
					$block_size = ! empty( $block_font_family_size ) ? $block_font_family_size : 34;
					$this->pdffile->SetXY( $topLinex, $topLineY + $block_size );
					switch ( $inner_block_name ) {
						case 'wpcw/course-title':
							$this->pdffile->Cell( 210, 0, mb_strtoupper( $course_title ), false, false, $align );
							break;
						case 'wpcw/student-name':
							$this->pdffile->Cell( 210, 0, mb_strtoupper( $student_name ), false, false, $align );
							break;
						case 'wpcw/text-field':
							$this->pdffile->Cell( 210, 0, $block_text, false, false, $align );
							break;
					}
					break;
				case 'block_2':
					$this->pdffile->SelectColumn( 0 );
					if ( 'center' === $block_font_align ) {
						$align = 'C';
						$x     = 18;
					} elseif ( 'right' === $block_font_align ) {
						$align = 'R';
						$x     = 0;
					} else {
						$align = 'L';
						$x     = 40;
					}
					switch ( $inner_block_name ) {
						case 'wpcw/course-title':
							$this->pdffile->SetXY( $x, $this->footer_Y + 15 );
							$this->pdffile->Cell( 100, 0, mb_strtoupper( $course_title ), false, false, $align );
							break;
						case 'wpcw/student-name':
							$this->pdffile->SetXY( $x, $this->footer_Y + 15 );
							$this->pdffile->Cell( 100, 0, mb_strtoupper( $student_name ), false, false, $align );
							break;
						case 'wpcw/text-field':
							$this->pdffile->SetXY( $x, $this->footer_Y + 15 );
							$this->pdffile->Cell( 100, 0, $block_text, false, false, $align );
							break;
						case 'wpcw/signature':
							if ( ! empty( $block_inner_blocks ) ) {
								// Signature Image.
								$signature_image = isset( $block_inner_blocks[0]['attrs']['selectedImage'] ) ? $block_inner_blocks[0]['attrs']['selectedImage'] : WPCW_PATH . 'assets/img/certificates/signature.png';

								// Instructor Name.
								$instructor_attr              = $block_inner_blocks[2]['attrs'];
								$instructor_text              = ( ! empty( $instructor_name ) ) ? $instructor_name : 'INSTRUCTOR NAME';
								$instructor_family            = ( isset( $instructor_attr['fontFamily'] ) && ! empty( $instructor_attr['fontFamily'] ) ) ? $instructor_attr['fontFamily'] : 'Lato-Black';
								$instructor_font_family_style = ( isset( $instructor_attr['fontWeight'] ) && ! empty( $instructor_attr['fontWeight'] ) ) ? 'B' : '';
								$instructor_font_underline    = ( isset( $instructor_attr['textUnderline'] ) && ! empty( $instructor_attr['textUnderline'] ) ) ? 'U' : '';
								$instructor_font_align        = ( isset( $instructor_attr['textAlign'] ) && ! empty( $instructor_attr['textAlign'] ) ) ? $instructor_attr['textAlign'] : 'left';
								$instructor_font_family_size  = ( isset( $instructor_attr['fontSize'] ) && ! empty( $instructor_attr['fontSize'] ) ) ? $instructor_attr['fontSize'] : 16;
								$instructor_font_color        = ( isset( $instructor_attr['textColor'] ) && ! empty( $instructor_attr['textColor'] ) ) ? $instructor_attr['textColor'] : '#000000';
								$instructor_text_title        = ( isset( $instructor_attr['instructorTitle'] ) && ! empty( $instructor_attr['instructorTitle'] ) ) ? $instructor_attr['instructorTitle'] : '';
								list($r, $g, $b)              = sscanf( $instructor_text_title, '#%02x%02x%02x' );
								if ( 'center' === $instructor_font_align ) {
									$align = 'C';
									$x     = 18;
								} elseif ( 'right' === $instructor_font_align ) {
									$align = 'R';
									$x     = 0;
								} else {
									$align = 'L';
									$x     = 40;
								}

								$this->pdffile->Ln( 60 );
								$this->pdffile->SetFillColor( 255, 0, 0 );
								$this->pdffile->Cell( 120, 20, '', 0, 1, 'C', 0 );
								$signWidth = self::px2mm( WPCW_CERTIFICATE_SIGNATURE_WIDTH_PX );
								$sigHeight = self::px2mm( WPCW_CERTIFICATE_SIGNATURE_HEIGHT_PX );

								// Make link relative.
								$signatureImg = $this->pdf_link_path( $signature_image );
								$this->pdffile->Image( $signatureImg, 50, $this->footer_Y, 40, 15 );
								$this->pdffile->Line( $date_X, $this->footer_Y + 18, $date_X + $this->footer_line_length, $this->footer_Y + 18 );

								$fontname = TCPDF_FONTS::addTTFfont( WPCW_PDF_FONTS . $instructor_family . '.ttf', 'TrueTypeUnicode', '', 96 );
								$this->pdffile->SetFont( $fontname, $instructor_font_underline, $instructor_font_family_size, '', false );
								$this->pdffile->SetTextColor( $r, $g, $b );
								$this->pdffile->SetX( $x );
								$this->pdffile->Cell( 100, 5, mb_strtoupper( $instructor_text ), 0, 1, $align, 0 );
								$this->pdffile->SetX( $x );
								$this->pdffile->Cell( 100, 5, mb_strtoupper( $instructor_text_title ), 0, 1, $align, 0 );
							}
							break;
						case 'wpcw/image':
							$logo_image = isset( $block['selectedImage'] ) ? $block['selectedImage'] : WPCW_PATH . 'assets/img/certificates/default-img-1.jpg';
							$this->pdffile->Ln( 55 );
							$logoWidth  = self::px2mm( WPCW_CERTIFICATE_LOGO_WIDTH_PX );
							$logoHeight = self::px2mm( WPCW_CERTIFICATE_LOGO_HEIGHT_PX );
							$logoImg    = $this->pdf_link_path( $logo_image );
							$this->pdffile->Image( $logoImg, $date_X, $this->footer_Y - 5, $logoWidth, $logoHeight );
							break;
						case 'wpcw/certificate-detail':
							if ( ! empty( $block_inner_blocks ) ) {
								// Text Entry field.
								$text_field_attr              = $block_inner_blocks[0]['attrs'];
								$text_field_text              = ( isset( $text_field_attr['textField'] ) && ! empty( $text_field_attr['textField'] ) ) ? $text_field_attr['textField'] : 'TEXT ENTRY';
								$text_field_family            = ( isset( $text_field_attr['fontFamily'] ) && ! empty( $text_field_attr['fontFamily'] ) ) ? $text_field_attr['fontFamily'] : 'Lato-Black';
								$text_field_font_family_style = ( isset( $text_field_attr['fontWeight'] ) && ! empty( $text_field_attr['fontWeight'] ) ) ? 'B' : '';
								$text_field_font_underline    = ( isset( $text_field_attr['textUnderline'] ) && ! empty( $text_field_attr['textUnderline'] ) ) ? 'U' : '';
								$text_field_font_align        = ( isset( $text_field_attr['textAlign'] ) && ! empty( $text_field_attr['textAlign'] ) ) ? $text_field_attr['textAlign'] : 'left';
								$text_field_font_family_size  = ( isset( $text_field_attr['fontSize'] ) && ! empty( $text_field_attr['fontSize'] ) ) ? $text_field_attr['fontSize'] : 16;
								$text_field_font_color        = ( isset( $text_field_attr['textColor'] ) && ! empty( $text_field_attr['textColor'] ) ) ? $text_field_attr['textColor'] : '#000000';

								// Cumulative Grade.
								$cumulative_grade_attr              = $block_inner_blocks[1]['attrs'];
								$cumulative_grade_text              = ( ! empty( $cumulative_grade ) ) ? $cumulative_grade : 'XX%';
								$cumulative_grade_family            = ( isset( $cumulative_grade_attr['fontFamily'] ) && ! empty( $cumulative_grade_attr['fontFamily'] ) ) ? $cumulative_grade_attr['fontFamily'] : 'Lato-Black';
								$cumulative_grade_font_family_style = ( isset( $cumulative_grade_attr['fontWeight'] ) && ! empty( $cumulative_grade_attr['fontWeight'] ) ) ? 'B' : '';
								$cumulative_grade_font_underline    = ( isset( $cumulative_grade_attr['textUnderline'] ) && ! empty( $cumulative_grade_attr['textUnderline'] ) ) ? 'U' : '';
								$cumulative_grade_font_align        = ( isset( $cumulative_grade_attr['textAlign'] ) && ! empty( $cumulative_grade_attr['textAlign'] ) ) ? $cumulative_grade_attr['textAlign'] : 'left';
								$cumulative_grade_font_family_size  = ( isset( $cumulative_grade_attr['fontSize'] ) && ! empty( $cumulative_grade_attr['fontSize'] ) ) ? $cumulative_grade_attr['fontSize'] : 16;
								$cumulative_grade_font_color        = ( isset( $cumulative_grade_attr['textColor'] ) && ! empty( $cumulative_grade_attr['textColor'] ) ) ? $cumulative_grade_attr['textColor'] : '#000000';
								$cumulative_grade_hide_show         = ( isset( $cumulative_grade_attr['cumulativeGrade'] ) && ! empty( $cumulative_grade_attr['cumulativeGrade'] ) ) ? $cumulative_grade_attr['cumulativeGrade'] : false;

								// Certificate Number.
								$certificate_number_attr              = $block_inner_blocks[3]['attrs'];
								$certificate_number_family            = ( isset( $certificate_number_attr['fontFamily'] ) && ! empty( $certificate_number_attr['fontFamily'] ) ) ? $certificate_number_attr['fontFamily'] : 'Lato-Black';
								$certificate_number_font_family_style = ( isset( $certificate_number_attr['fontWeight'] ) && ! empty( $certificate_number_attr['fontWeight'] ) ) ? 'B' : '';
								$certificate_number_font_underline    = ( isset( $certificate_number_attr['textUnderline'] ) && ! empty( $certificate_number_attr['textUnderline'] ) ) ? 'U' : '';
								$certificate_number_font_align        = ( isset( $certificate_number_attr['textAlign'] ) && ! empty( $certificate_number_attr['textAlign'] ) ) ? $certificate_number_attr['textAlign'] : 'left';
								$certificate_number_font_family_size  = ( isset( $certificate_number_attr['fontSize'] ) && ! empty( $certificate_number_attr['fontSize'] ) ) ? $certificate_number_attr['fontSize'] : 16;
								$certificate_number_font_color        = ( isset( $certificate_number_attr['textColor'] ) && ! empty( $certificate_number_attr['textColor'] ) ) ? $certificate_number_attr['textColor'] : '#000000';
								$certificate_number_prefix            = ( isset( $certificate_number_attr['numberPrefix'] ) && ! empty( $certificate_number_attr['numberPrefix'] ) ) ? $certificate_number_attr['numberPrefix'] : '';
								$certificate_number_length            = ( isset( $certificate_number_attr['numberLength'] ) && ! empty( $certificate_number_attr['numberLength'] ) ) ? $certificate_number_attr['numberLength'] : 3;
								$certificate_number_hide_show         = ( isset( $certificate_number_attr['certificateNumber'] ) && ! empty( $certificate_number_attr['certificateNumber'] ) ) ? $certificate_number_attr['certificateNumber'] : false;

								if ( $certificate_number_hide_show && empty( $certificate_number ) && ! empty( $certificate_access_key ) ) {
									$genarated_number   = self::generateNDigitRandomNumber( $certificate_number_length );
									$certificate_number = $certificate_number_prefix . $genarated_number;

									// Update Generate Number.
									global $wpdb, $wpcwdb;
									$cer_number_updated = $wpdb->query( $wpdb->prepare( "UPDATE $wpcwdb->certificates SET cert_number = %s WHERE cert_access_key = %s", $certificate_number, $certificate_access_key ) );
								}
								$certificate_number_attr_text = ( ! empty( $certificate_number ) ) ? $certificate_number : 'CERTIFICATE NUMBER';

								// Expiry Date
								$expiry_date_attr              = $block_inner_blocks[4]['attrs'];
								$expiry_date_attr_text         = ( isset( $expiry_date_attr['textField'] ) && ! empty( $expiry_date_attr['textField'] ) ) ? $expiry_date_attr['textField'] : '27 Nov 2020';
								$expiry_date_family            = ( isset( $expiry_date_attr['fontFamily'] ) && ! empty( $expiry_date_attr['fontFamily'] ) ) ? $expiry_date_attr['fontFamily'] : 'Lato-Black';
								$expiry_date_font_family_style = ( isset( $expiry_date_attr['fontWeight'] ) && ! empty( $expiry_date_attr['fontWeight'] ) ) ? 'B' : '';
								$expiry_date_font_underline    = ( isset( $expiry_date_attr['textUnderline'] ) && ! empty( $expiry_date_attr['textUnderline'] ) ) ? 'U' : '';
								$expiry_date_font_align        = ( isset( $expiry_date_attr['textAlign'] ) && ! empty( $expiry_date_attr['textAlign'] ) ) ? $expiry_date_attr['textAlign'] : 'left';
								$expiry_date_font_family_size  = ( isset( $expiry_date_attr['fontSize'] ) && ! empty( $expiry_date_attr['fontSize'] ) ) ? $expiry_date_attr['fontSize'] : 16;
								$expiry_date_font_color        = ( isset( $expiry_date_attr['textColor'] ) && ! empty( $expiry_date_attr['textColor'] ) ) ? $expiry_date_attr['textColor'] : '#000000';
								$expiry_date_format            = ( isset( $expiry_date_attr['dateFormat'] ) && ! empty( $expiry_date_attr['dateFormat'] ) ) ? $expiry_date_attr['dateFormat'] : get_option( 'date_format' );

								$date_str     = date_i18n( $expiry_date_format, $completeDate );
								$date_str_len = $this->pdffile->GetStringWidth( $date_str );

								$this->pdffile->Ln( 40 );
								$this->pdffile->Cell( 120, 20, '', 0, 1, 'C', 0 );
								$fontname = TCPDF_FONTS::addTTFfont( WPCW_PDF_FONTS . $text_field_family . '.ttf', 'TrueTypeUnicode', '', 96 );
								$this->pdffile->SetFont( $fontname, $text_field_font_underline, $text_field_font_family_size, '', false );
								list($r, $g, $b) = sscanf( $text_field_font_color, '#%02x%02x%02x' );
								if ( 'center' === $text_field_font_align ) {
									$align = 'C';
									$x     = 18;
								} elseif ( 'right' === $text_field_font_align ) {
									$align = 'R';
									$x     = 0;
								} else {
									$align = 'L';
									$x     = 40;
								}
								$this->pdffile->SetTextColor( $r, $g, $b );
								$this->pdffile->SetX( $x );
								$this->pdffile->Cell( 100, 7, $text_field_text, 0, 1, $align, 0 );

								$fontname = TCPDF_FONTS::addTTFfont( WPCW_PDF_FONTS . $cumulative_grade_family . '.ttf', 'TrueTypeUnicode', '', 96 );
								$this->pdffile->SetFont( $fontname, $cumulative_grade_font_underline, $cumulative_grade_font_family_size, '', false );
								list($r, $g, $b) = sscanf( $cumulative_grade_font_color, '#%02x%02x%02x' );
								if ( 'center' === $cumulative_grade_font_align ) {
									$align = 'C';
									$x     = 18;
								} elseif ( 'right' === $cumulative_grade_font_align ) {
									$align = 'R';
									$x     = 0;
								} else {
									$align = 'L';
									$x     = 40;
								}
								$this->pdffile->SetTextColor( $r, $g, $b );
								$this->pdffile->SetX( $x );
								if ( $cumulative_grade_hide_show ) {
									$this->pdffile->Cell( 100, 7, mb_strtoupper( $cumulative_grade_text ), 0, 1, $align, 0 );
								} else {
									$this->pdffile->Cell( 120, 7, '', 0, 1, $align, 0 );
								}
								$this->pdffile->Line( $date_X, $this->footer_Y + 18, $date_X + $this->footer_line_length, $this->footer_Y + 18 );

								$this->pdffile->Ln( 7 );

								$fontname = TCPDF_FONTS::addTTFfont( WPCW_PDF_FONTS . $certificate_number_family . '.ttf', 'TrueTypeUnicode', '', 96 );
								$this->pdffile->SetFont( $fontname, $certificate_number_font_underline, $certificate_number_font_family_size, '', false );
								list($r, $g, $b) = sscanf( $certificate_number_font_color, '#%02x%02x%02x' );
								if ( 'center' === $certificate_number_font_align ) {
									$align = 'C';
									$x     = 18;
								} elseif ( 'right' === $certificate_number_font_align ) {
									$align = 'R';
									$x     = 0;
								} else {
									$align = 'L';
									$x     = 40;
								}
								$this->pdffile->SetTextColor( $r, $g, $b );
								$this->pdffile->SetX( $x );
								if ( $certificate_number_hide_show ) {
									$this->pdffile->Cell( 100, 5, mb_strtoupper( $certificate_number_attr_text ), 0, 1, $align, 0 );
								}
								// $this->pdffile->Cell( 100, 5, mb_strtoupper( $certificate_number_attr_text ), 0, 1, $align, 0 );

								$fontname = TCPDF_FONTS::addTTFfont( WPCW_PDF_FONTS . $expiry_date_family . '.ttf', 'TrueTypeUnicode', '', 96 );
								$this->pdffile->SetFont( $fontname, $expiry_date_font_underline, $expiry_date_font_family_size, '', false );
								list($r, $g, $b) = sscanf( $expiry_date_font_color, '#%02x%02x%02x' );
								if ( 'center' === $expiry_date_font_align ) {
									$align = 'C';
									$x     = 18;
								} elseif ( 'right' === $expiry_date_font_align ) {
									$align = 'R';
									$x     = 0;
								} else {
									$align = 'L';
									$x     = 40;
								}
								$this->pdffile->SetTextColor( $r, $g, $b );
								$this->pdffile->SetX( $x );
								$this->pdffile->Cell( 100, 5, mb_strtoupper( $date_str ), 0, 1, $align, 0 );
							}
							break;
					}
					break;

				case 'block_3':
					$this->pdffile->SelectColumn( 1 );
					if ( 'center' === $block_font_align ) {
						$align = 'C';
						$x     = $this->size_width - $this->footer_line_length - $this->footer_Y - 15;
					} elseif ( 'right' === $block_font_align ) {
						$align = 'R';
						$x     = $this->size_width - $this->footer_line_length - $this->footer_Y - 30;
					} else {
						$align = 'L';
						$x     = $this->size_width - $this->footer_line_length - $this->footer_Y;
					}
					switch ( $inner_block_name ) {
						case 'wpcw/course-title':
							$this->pdffile->SetXY( $x, $this->footer_Y + 15 );
							$this->pdffile->Cell( 90, 0, mb_strtoupper( $course_title ), false, false, $align );
							break;
						case 'wpcw/student-name':
							$this->pdffile->SetXY( $x, $this->footer_Y + 15 );
							$this->pdffile->Cell( 90, 0, mb_strtoupper( $student_name ), false, false, $align );
							break;
						case 'wpcw/text-field':
							$this->pdffile->SetXY( $x - $this->footer_Y, $this->footer_Y + 15 );
							$this->pdffile->Cell( 90, 0, $block_text, false, false, $align );
							break;
						case 'wpcw/signature':
							if ( ! empty( $block_inner_blocks ) ) {
								// Signature Image.
								$signature_image = isset( $block_inner_blocks[0]['attrs']['selectedImage'] ) ? $block_inner_blocks[0]['attrs']['selectedImage'] : WPCW_PATH . 'assets/img/certificates/signature.png';

								// Instructor Name.
								$instructor_attr              = $block_inner_blocks[2]['attrs'];
								$instructor_text              = ( ! empty( $instructor_name ) ) ? $instructor_name : 'INSTRUCTOR NAME';
								$instructor_family            = ( isset( $instructor_attr['fontFamily'] ) && ! empty( $instructor_attr['fontFamily'] ) ) ? $instructor_attr['fontFamily'] : 'Lato-Black';
								$instructor_font_family_style = ( isset( $instructor_attr['fontWeight'] ) && ! empty( $instructor_attr['fontWeight'] ) ) ? 'B' : '';
								$instructor_font_underline    = ( isset( $instructor_attr['textUnderline'] ) && ! empty( $instructor_attr['textUnderline'] ) ) ? 'U' : '';
								$instructor_font_align        = ( isset( $instructor_attr['textAlign'] ) && ! empty( $instructor_attr['textAlign'] ) ) ? $instructor_attr['textAlign'] : 'left';
								$instructor_font_family_size  = ( isset( $instructor_attr['fontSize'] ) && ! empty( $instructor_attr['fontSize'] ) ) ? $instructor_attr['fontSize'] : 16;
								$instructor_font_color        = ( isset( $instructor_attr['textColor'] ) && ! empty( $instructor_attr['textColor'] ) ) ? $instructor_attr['textColor'] : '#000000';
								$instructor_text_title        = ( isset( $instructor_attr['instructorTitle'] ) && ! empty( $instructor_attr['instructorTitle'] ) ) ? $instructor_attr['instructorTitle'] : '';
								list($r, $g, $b)              = sscanf( $instructor_font_color, '#%02x%02x%02x' );
								if ( 'center' === $instructor_font_align ) {
									$align = 'C';
									$x     = $this->size_width - $this->footer_line_length - $this->footer_Y - 15;
								} elseif ( 'right' === $instructor_font_align ) {
									$align = 'R';
									$x     = $this->size_width - $this->footer_line_length - $this->footer_Y - 30;
								} else {
									$align = 'L';
									$x     = $this->size_width - $this->footer_line_length - $this->footer_Y;
								}

								$this->pdffile->Ln( 30 );
								$this->pdffile->SetFillColor( 255, 0, 0 );
								$this->pdffile->Cell( 120, 20, '', 0, 1, 'C', 0 );
								$signWidth = self::px2mm( WPCW_CERTIFICATE_SIGNATURE_WIDTH_PX );
								$sigHeight = self::px2mm( WPCW_CERTIFICATE_SIGNATURE_HEIGHT_PX );

								// Make link relative.
								$signatureImg = $this->pdf_link_path( $signature_image );
								$this->pdffile->Ln( 2 );
								$this->pdffile->Image( $signatureImg, $this->getLeftOfCentre( $signWidth ) + 10, $this->footer_Y, 40, 15 ); // Only force width
								$this->pdffile->Line( $this->size_width - $this->footer_line_length - $this->footer_Y, $this->footer_Y + 18, $this->size_width - $this->footer_Y, $this->footer_Y + 18 );

								$fontname = TCPDF_FONTS::addTTFfont( WPCW_PDF_FONTS . $instructor_family . '.ttf', 'TrueTypeUnicode', '', 96 );
								$this->pdffile->SetFont( $fontname, $instructor_font_underline, $instructor_font_family_size, '', false );
								$this->pdffile->SetTextColor( $r, $g, $b );
								$this->pdffile->SetX( $x );
								$this->pdffile->Cell( 90, 5, mb_strtoupper( $instructor_text ), 0, 1, $align, 0 );
								$this->pdffile->SetX( $x );
								$this->pdffile->Cell( 90, 5, mb_strtoupper( $instructor_text_title ), 0, 1, $align, 0 );
							}
							break;
						case 'wpcw/image':
							$logo_image = isset( $block['selectedImage'] ) ? $block['selectedImage'] : WPCW_PATH . 'assets/img/certificates/default-img-1.jpg';
							$this->pdffile->Ln( 25 );
							$logoWidth  = self::px2mm( WPCW_CERTIFICATE_LOGO_WIDTH_PX );
							$logoHeight = self::px2mm( WPCW_CERTIFICATE_LOGO_HEIGHT_PX );
							$logoImg    = $this->pdf_link_path( $logo_image );
							$this->pdffile->Image( $logoImg, $this->getLeftOfCentre( $logoWidth ), $this->footer_Y - 5, $logoWidth, $logoHeight );
							break;
						case 'wpcw/certificate-detail':
							if ( ! empty( $block_inner_blocks ) ) {
								// Text Entry field.
								$text_field_attr              = $block_inner_blocks[0]['attrs'];
								$text_field_text              = ( isset( $text_field_attr['textField'] ) && ! empty( $text_field_attr['textField'] ) ) ? $text_field_attr['textField'] : 'TEXT ENTRY';
								$text_field_family            = ( isset( $text_field_attr['fontFamily'] ) && ! empty( $text_field_attr['fontFamily'] ) ) ? $text_field_attr['fontFamily'] : 'Lato-Black';
								$text_field_font_family_style = ( isset( $text_field_attr['fontWeight'] ) && ! empty( $text_field_attr['fontWeight'] ) ) ? 'B' : '';
								$text_field_font_underline    = ( isset( $text_field_attr['textUnderline'] ) && ! empty( $text_field_attr['textUnderline'] ) ) ? 'U' : '';
								$text_field_font_align        = ( isset( $text_field_attr['textAlign'] ) && ! empty( $text_field_attr['textAlign'] ) ) ? $text_field_attr['textAlign'] : 'left';
								$text_field_font_family_size  = ( isset( $text_field_attr['fontSize'] ) && ! empty( $text_field_attr['fontSize'] ) ) ? $text_field_attr['fontSize'] : 16;
								$text_field_font_color        = ( isset( $text_field_attr['textColor'] ) && ! empty( $text_field_attr['textColor'] ) ) ? $text_field_attr['textColor'] : '#000000';

								// Cumulative Grade.
								$cumulative_grade_attr              = $block_inner_blocks[1]['attrs'];
								$cumulative_grade_text              = ( ! empty( $cumulative_grade ) ) ? $cumulative_grade : 'XX%';
								$cumulative_grade_family            = ( isset( $cumulative_grade_attr['fontFamily'] ) && ! empty( $cumulative_grade_attr['fontFamily'] ) ) ? $cumulative_grade_attr['fontFamily'] : 'Lato-Black';
								$cumulative_grade_font_family_style = ( isset( $cumulative_grade_attr['fontWeight'] ) && ! empty( $cumulative_grade_attr['fontWeight'] ) ) ? 'B' : '';
								$cumulative_grade_font_underline    = ( isset( $cumulative_grade_attr['textUnderline'] ) && ! empty( $cumulative_grade_attr['textUnderline'] ) ) ? 'U' : '';
								$cumulative_grade_font_align        = ( isset( $cumulative_grade_attr['textAlign'] ) && ! empty( $cumulative_grade_attr['textAlign'] ) ) ? $cumulative_grade_attr['textAlign'] : 'left';
								$cumulative_grade_font_family_size  = ( isset( $cumulative_grade_attr['fontSize'] ) && ! empty( $cumulative_grade_attr['fontSize'] ) ) ? $cumulative_grade_attr['fontSize'] : 16;
								$cumulative_grade_font_color        = ( isset( $cumulative_grade_attr['textColor'] ) && ! empty( $cumulative_grade_attr['textColor'] ) ) ? $cumulative_grade_attr['textColor'] : '#000000';
								$cumulative_grade_hide_show         = ( isset( $cumulative_grade_attr['cumulativeGrade'] ) && ! empty( $cumulative_grade_attr['cumulativeGrade'] ) ) ? $cumulative_grade_attr['cumulativeGrade'] : false;

								// Certificate Number.
								$certificate_number_attr              = $block_inner_blocks[3]['attrs'];
								$certificate_number_family            = ( isset( $certificate_number_attr['fontFamily'] ) && ! empty( $certificate_number_attr['fontFamily'] ) ) ? $certificate_number_attr['fontFamily'] : 'Lato-Black';
								$certificate_number_font_family_style = ( isset( $certificate_number_attr['fontWeight'] ) && ! empty( $certificate_number_attr['fontWeight'] ) ) ? 'B' : '';
								$certificate_number_font_underline    = ( isset( $certificate_number_attr['textUnderline'] ) && ! empty( $certificate_number_attr['textUnderline'] ) ) ? 'U' : '';
								$certificate_number_font_align        = ( isset( $certificate_number_attr['textAlign'] ) && ! empty( $certificate_number_attr['textAlign'] ) ) ? $certificate_number_attr['textAlign'] : 'left';
								$certificate_number_font_family_size  = ( isset( $certificate_number_attr['fontSize'] ) && ! empty( $certificate_number_attr['fontSize'] ) ) ? $certificate_number_attr['fontSize'] : 16;
								$certificate_number_font_color        = ( isset( $certificate_number_attr['textColor'] ) && ! empty( $certificate_number_attr['textColor'] ) ) ? $certificate_number_attr['textColor'] : '#000000';
								$certificate_number_prefix            = ( isset( $certificate_number_attr['numberPrefix'] ) && ! empty( $certificate_number_attr['numberPrefix'] ) ) ? $certificate_number_attr['numberPrefix'] : '';
								$certificate_number_length            = ( isset( $certificate_number_attr['numberLength'] ) && ! empty( $certificate_number_attr['numberLength'] ) ) ? $certificate_number_attr['numberLength'] : 3;
								$certificate_number_hide_show         = ( isset( $certificate_number_attr['certificateNumber'] ) && ! empty( $certificate_number_attr['certificateNumber'] ) ) ? $certificate_number_attr['certificateNumber'] : false;

								if ( $certificate_number_hide_show && empty( $certificate_number ) && ! empty( $certificate_access_key ) ) {
									$genarated_number   = self::generateNDigitRandomNumber( $certificate_number_length );
									$certificate_number = $certificate_number_prefix . $genarated_number;

									// Update Generate Number.
									global $wpdb, $wpcwdb;
									$cer_number_updated = $wpdb->query( $wpdb->prepare( "UPDATE $wpcwdb->certificates SET cert_number = %s WHERE cert_access_key = %s", $certificate_number, $certificate_access_key ) );
								}
								$certificate_number_attr_text = ( ! empty( $certificate_number ) ) ? $certificate_number : 'CERTIFICATE NUMBER';

								// Expiry Date
								$expiry_date_attr              = $block_inner_blocks[4]['attrs'];
								$expiry_date_attr_text         = ( isset( $expiry_date_attr['textField'] ) && ! empty( $expiry_date_attr['textField'] ) ) ? $expiry_date_attr['textField'] : '27 Nov 2020';
								$expiry_date_family            = ( isset( $expiry_date_attr['fontFamily'] ) && ! empty( $expiry_date_attr['fontFamily'] ) ) ? $expiry_date_attr['fontFamily'] : 'Lato-Black';
								$expiry_date_font_family_style = ( isset( $expiry_date_attr['fontWeight'] ) && ! empty( $expiry_date_attr['fontWeight'] ) ) ? 'B' : '';
								$expiry_date_font_underline    = ( isset( $expiry_date_attr['textUnderline'] ) && ! empty( $expiry_date_attr['textUnderline'] ) ) ? 'U' : '';
								$expiry_date_font_align        = ( isset( $expiry_date_attr['textAlign'] ) && ! empty( $expiry_date_attr['textAlign'] ) ) ? $expiry_date_attr['textAlign'] : 'left';
								$expiry_date_font_family_size  = ( isset( $expiry_date_attr['fontSize'] ) && ! empty( $expiry_date_attr['fontSize'] ) ) ? $expiry_date_attr['fontSize'] : 16;
								$expiry_date_font_color        = ( isset( $expiry_date_attr['textColor'] ) && ! empty( $expiry_date_attr['textColor'] ) ) ? $expiry_date_attr['textColor'] : '#000000';
								$expiry_date_format            = ( isset( $expiry_date_attr['dateFormat'] ) && ! empty( $expiry_date_attr['dateFormat'] ) ) ? $expiry_date_attr['dateFormat'] : get_option( 'date_format' );

								$date_str     = date_i18n( $expiry_date_format, $completeDate );
								$date_str_len = $this->pdffile->GetStringWidth( $date_str );

								// $this->pdffile->SelectColumn( 1 );
								$fontname = TCPDF_FONTS::addTTFfont( WPCW_PDF_FONTS . $text_field_family . '.ttf', 'TrueTypeUnicode', '', 96 );
								$this->pdffile->SetFont( $fontname, $text_field_font_underline, $text_field_font_family_size, '', false );
								list($r, $g, $b) = sscanf( $text_field_font_color, '#%02x%02x%02x' );
								if ( 'center' === $text_field_font_align ) {
									$align = 'C';
									$x     = $this->size_width - $this->footer_line_length - $this->footer_Y - 15;
								} elseif ( 'right' === $text_field_font_align ) {
									$align = 'R';
									$x     = $this->size_width - $this->footer_line_length - $this->footer_Y - 30;
								} else {
									$align = 'L';
									$x     = $this->size_width - $this->footer_line_length - $this->footer_Y;
								}

								$this->pdffile->SetTextColor( $r, $g, $b );
								$this->pdffile->SetX( $x );
								$this->pdffile->Cell( 90, 7, $text_field_text, 0, 1, $align, 0 );

								$fontname = TCPDF_FONTS::addTTFfont( WPCW_PDF_FONTS . $cumulative_grade_family . '.ttf', 'TrueTypeUnicode', '', 96 );
								$this->pdffile->SetFont( $fontname, $cumulative_grade_font_underline, $cumulative_grade_font_family_size, '', false );
								list($r, $g, $b) = sscanf( $cumulative_grade_font_color, '#%02x%02x%02x' );
								if ( 'center' === $cumulative_grade_font_align ) {
									$align = 'C';
									$x     = $this->size_width - $this->footer_line_length - $this->footer_Y - 15;
								} elseif ( 'right' === $cumulative_grade_font_align ) {
									$align = 'R';
									$x     = $this->size_width - $this->footer_line_length - $this->footer_Y - 30;
								} else {
									$align = 'L';
									$x     = $this->size_width - $this->footer_line_length - $this->footer_Y;
								}
								$this->pdffile->SetTextColor( $r, $g, $b );
								$this->pdffile->SetX( $x );
								if ( $cumulative_grade_hide_show ) {
									$this->pdffile->Cell( 90, 7, mb_strtoupper( $cumulative_grade_text ), 0, 1, $align, 0 );
								} else {
									$this->pdffile->Cell( 90, 7, '', 0, 1, $align, 0 );
								}
								$this->pdffile->Line( $this->size_width - $this->footer_line_length - $this->footer_Y, $this->footer_Y + 18, $this->size_width - $this->footer_Y, $this->footer_Y + 18 );

								// $this->pdffile->Line( $this->signature_X - 5, $this->footer_Y + 15, $this->signature_X + $this->footer_line_length - 5, $this->footer_Y + 15 );
								$this->pdffile->Ln( 8 );

								$fontname = TCPDF_FONTS::addTTFfont( WPCW_PDF_FONTS . $certificate_number_family . '.ttf', 'TrueTypeUnicode', '', 96 );
								$this->pdffile->SetFont( $fontname, $certificate_number_font_underline, $certificate_number_font_family_size, '', false );
								list($r, $g, $b) = sscanf( $certificate_number_font_color, '#%02x%02x%02x' );
								if ( 'center' === $certificate_number_font_align ) {
									$align = 'C';
									$x     = $this->size_width - $this->footer_line_length - $this->footer_Y - 15;
								} elseif ( 'right' === $certificate_number_font_align ) {
									$align = 'R';
									$x     = $this->size_width - $this->footer_line_length - $this->footer_Y - 30;
								} else {
									$align = 'L';
									$x     = $this->size_width - $this->footer_line_length - $this->footer_Y;
								}
								$this->pdffile->SetTextColor( $r, $g, $b );
								$this->pdffile->SetX( $x );
								if ( $certificate_number_hide_show ) {
									$this->pdffile->Cell( 90, 5, mb_strtoupper( $certificate_number_attr_text ), 0, 1, $align, 0 );
								}
								// $this->pdffile->Cell( 90, 5, mb_strtoupper( $certificate_number_attr_text ), 0, 1, $align, 0 );

								$fontname = TCPDF_FONTS::addTTFfont( WPCW_PDF_FONTS . $expiry_date_family . '.ttf', 'TrueTypeUnicode', '', 96 );
								$this->pdffile->SetFont( $fontname, $expiry_date_font_underline, $expiry_date_font_family_size, '', false );
								list($r, $g, $b) = sscanf( $expiry_date_font_color, '#%02x%02x%02x' );
								if ( 'center' === $expiry_date_font_align ) {
									$align = 'C';
									$x     = $this->size_width - $this->footer_line_length - $this->footer_Y - 15;
								} elseif ( 'right' === $expiry_date_font_align ) {
									$align = 'R';
									$x     = $this->size_width - $this->footer_line_length - $this->footer_Y - 30;
								} else {
									$align = 'L';
									$x     = $this->size_width - $this->footer_line_length - $this->footer_Y;
								}
								$this->pdffile->SetTextColor( $r, $g, $b );
								$this->pdffile->SetX( $x );
								$this->pdffile->Cell( 90, 5, mb_strtoupper( $date_str ), 0, 1, $align, 0 );
							}
							break;
					}
					break;
				case 'block_4':
					$this->pdffile->SelectColumn( 2 );
					if ( 'center' === $block_font_align ) {
						$align = 'C';
						$x     = $this->signature_X - 20;
					} elseif ( 'right' === $block_font_align ) {
						$align = 'R';
						$x     = $this->signature_X - 32;
					} else {
						$align = 'L';
						$x     = $this->signature_X - 5;
					}
					switch ( $inner_block_name ) {
						case 'wpcw/course-title':
							$this->pdffile->SetXY( $x, $this->footer_Y + 15 );
							$this->pdffile->Cell( 0, 0, mb_strtoupper( $course_title ), false, false, $align );
							break;
						case 'wpcw/student-name':
							$this->pdffile->SetXY( $x, $this->footer_Y + 15 );
							$this->pdffile->Cell( 0, 0, mb_strtoupper( $student_name ), false, false, $align );
							break;
						case 'wpcw/text-field':
							$this->pdffile->SetXY( $x, $this->footer_Y + 15 );
							$this->pdffile->Cell( 0, 0, $block_text, false, false, $align );
							break;
						case 'wpcw/signature':
							if ( ! empty( $block_inner_blocks ) ) {
								// Signature Image.
								$signature_image = isset( $block_inner_blocks[0]['attrs']['selectedImage'] ) ? $block_inner_blocks[0]['attrs']['selectedImage'] : WPCW_PATH . 'assets/img/certificates/signature.png';

								// Instructor Name.
								$instructor_attr              = $block_inner_blocks[2]['attrs'];
								$instructor_text              = ( ! empty( $instructor_name ) ) ? $instructor_name : 'INSTRUCTOR NAME';
								$instructor_family            = ( isset( $instructor_attr['fontFamily'] ) && ! empty( $instructor_attr['fontFamily'] ) ) ? $instructor_attr['fontFamily'] : 'Lato-Black';
								$instructor_font_family_style = ( isset( $instructor_attr['fontWeight'] ) && ! empty( $instructor_attr['fontWeight'] ) ) ? 'B' : '';
								$instructor_font_underline    = ( isset( $instructor_attr['textUnderline'] ) && ! empty( $instructor_attr['textUnderline'] ) ) ? 'U' : '';
								$instructor_font_align        = ( isset( $instructor_attr['textAlign'] ) && ! empty( $instructor_attr['textAlign'] ) ) ? $instructor_attr['textAlign'] : 'left';
								$instructor_font_family_size  = ( isset( $instructor_attr['fontSize'] ) && ! empty( $instructor_attr['fontSize'] ) ) ? $instructor_attr['fontSize'] : 16;
								$instructor_font_color        = ( isset( $instructor_attr['textColor'] ) && ! empty( $instructor_attr['textColor'] ) ) ? $instructor_attr['textColor'] : '#000000';
								$instructor_text_title        = ( isset( $instructor_attr['instructorTitle'] ) && ! empty( $instructor_attr['instructorTitle'] ) ) ? $instructor_attr['instructorTitle'] : '';
								list($r, $g, $b)              = sscanf( $instructor_font_color, '#%02x%02x%02x' );
								if ( 'center' === $instructor_font_align ) {
									$align = 'C';
									$x     = $this->signature_X - 20;
								} elseif ( 'right' === $instructor_font_align ) {
									$align = 'R';
									$x     = $this->signature_X - 32;
								} else {
									$align = 'L';
									$x     = $this->signature_X - 5;
								}
								$this->pdffile->Ln( 2 );
								$this->pdffile->SetFillColor( 255, 0, 0 );
								$this->pdffile->Cell( 120, 20, '', 0, 1, 'C', 0 );
								$signWidth = self::px2mm( WPCW_CERTIFICATE_SIGNATURE_WIDTH_PX );
								$sigHeight = self::px2mm( WPCW_CERTIFICATE_SIGNATURE_HEIGHT_PX );

								// Make link relative.
								$signatureImg = $this->pdf_link_path( $signature_image );
								$this->pdffile->Image( $signatureImg, $this->size_width - 90, $this->footer_Y, 40, 15 ); // Only force width

								$this->pdffile->Line( $this->signature_X - 5, $this->footer_Y + 17, $this->signature_X + $this->footer_line_length - 5, $this->footer_Y + 17 );
								$this->pdffile->Ln( 2 );
								$fontname = TCPDF_FONTS::addTTFfont( WPCW_PDF_FONTS . $instructor_family . '.ttf', 'TrueTypeUnicode', '', 96 );
								$this->pdffile->SetFont( $fontname, $instructor_font_underline, $instructor_font_family_size, '', false );
								$this->pdffile->SetTextColor( $r, $g, $b );
								$this->pdffile->SetX( $x );
								$this->pdffile->Cell( 90, 5, mb_strtoupper( $instructor_text ), 0, 1, $align, 0 );
								$this->pdffile->SetX( $x );
								$this->pdffile->Cell( 90, 5, mb_strtoupper( $instructor_text_title ), 0, 1, $align, 0 );
							}
							break;
						case 'wpcw/image':
							$logo_image = isset( $block['selectedImage'] ) ? $block['selectedImage'] : WPCW_PATH . 'assets/img/certificates/default-img-1.jpg';
							$this->pdffile->Ln( 25 );
							$logoWidth  = self::px2mm( WPCW_CERTIFICATE_LOGO_WIDTH_PX );
							$logoHeight = self::px2mm( WPCW_CERTIFICATE_LOGO_HEIGHT_PX );
							$logoImg    = $this->pdf_link_path( $logo_image );
							$this->pdffile->Image( $logoImg, $this->size_width - 90, $this->footer_Y - 5, $logoWidth, $logoHeight ); // Only force width
							break;
						case 'wpcw/certificate-detail':
							if ( ! empty( $block_inner_blocks ) ) {
								// Text Entry field.
								$text_field_attr              = $block_inner_blocks[0]['attrs'];
								$text_field_text              = ( isset( $text_field_attr['textField'] ) && ! empty( $text_field_attr['textField'] ) ) ? $text_field_attr['textField'] : 'TEXT ENTRY';
								$text_field_family            = ( isset( $text_field_attr['fontFamily'] ) && ! empty( $text_field_attr['fontFamily'] ) ) ? $text_field_attr['fontFamily'] : 'Lato-Black';
								$text_field_font_family_style = ( isset( $text_field_attr['fontWeight'] ) && ! empty( $text_field_attr['fontWeight'] ) ) ? 'B' : '';
								$text_field_font_underline    = ( isset( $text_field_attr['textUnderline'] ) && ! empty( $text_field_attr['textUnderline'] ) ) ? 'U' : '';
								$text_field_font_align        = ( isset( $text_field_attr['textAlign'] ) && ! empty( $text_field_attr['textAlign'] ) ) ? $text_field_attr['textAlign'] : 'left';
								$text_field_font_family_size  = ( isset( $text_field_attr['fontSize'] ) && ! empty( $text_field_attr['fontSize'] ) ) ? $text_field_attr['fontSize'] : 16;
								$text_field_font_color        = ( isset( $text_field_attr['textColor'] ) && ! empty( $text_field_attr['textColor'] ) ) ? $text_field_attr['textColor'] : '#000000';

								// Cumulative Grade.
								$cumulative_grade_attr              = $block_inner_blocks[1]['attrs'];
								$cumulative_grade_text              = ( ! empty( $cumulative_grade ) ) ? $cumulative_grade : 'XX%';
								$cumulative_grade_family            = ( isset( $cumulative_grade_attr['fontFamily'] ) && ! empty( $cumulative_grade_attr['fontFamily'] ) ) ? $cumulative_grade_attr['fontFamily'] : 'Lato-Black';
								$cumulative_grade_font_family_style = ( isset( $cumulative_grade_attr['fontWeight'] ) && ! empty( $cumulative_grade_attr['fontWeight'] ) ) ? 'B' : '';
								$cumulative_grade_font_underline    = ( isset( $cumulative_grade_attr['textUnderline'] ) && ! empty( $cumulative_grade_attr['textUnderline'] ) ) ? 'U' : '';
								$cumulative_grade_font_align        = ( isset( $cumulative_grade_attr['textAlign'] ) && ! empty( $cumulative_grade_attr['textAlign'] ) ) ? $cumulative_grade_attr['textAlign'] : 'left';
								$cumulative_grade_font_family_size  = ( isset( $cumulative_grade_attr['fontSize'] ) && ! empty( $cumulative_grade_attr['fontSize'] ) ) ? $cumulative_grade_attr['fontSize'] : 16;
								$cumulative_grade_font_color        = ( isset( $cumulative_grade_attr['textColor'] ) && ! empty( $cumulative_grade_attr['textColor'] ) ) ? $cumulative_grade_attr['textColor'] : '#000000';
								$cumulative_grade_hide_show         = ( isset( $cumulative_grade_attr['cumulativeGrade'] ) && ! empty( $cumulative_grade_attr['cumulativeGrade'] ) ) ? $cumulative_grade_attr['cumulativeGrade'] : false;

								// Certificate Number.
								$certificate_number_attr              = $block_inner_blocks[3]['attrs'];
								$certificate_number_family            = ( isset( $certificate_number_attr['fontFamily'] ) && ! empty( $certificate_number_attr['fontFamily'] ) ) ? $certificate_number_attr['fontFamily'] : 'Lato-Black';
								$certificate_number_font_family_style = ( isset( $certificate_number_attr['fontWeight'] ) && ! empty( $certificate_number_attr['fontWeight'] ) ) ? 'B' : '';
								$certificate_number_font_underline    = ( isset( $certificate_number_attr['textUnderline'] ) && ! empty( $certificate_number_attr['textUnderline'] ) ) ? 'U' : '';
								$certificate_number_font_align        = ( isset( $certificate_number_attr['textAlign'] ) && ! empty( $certificate_number_attr['textAlign'] ) ) ? $certificate_number_attr['textAlign'] : 'left';
								$certificate_number_font_family_size  = ( isset( $certificate_number_attr['fontSize'] ) && ! empty( $certificate_number_attr['fontSize'] ) ) ? $certificate_number_attr['fontSize'] : 16;
								$certificate_number_font_color        = ( isset( $certificate_number_attr['textColor'] ) && ! empty( $certificate_number_attr['textColor'] ) ) ? $certificate_number_attr['textColor'] : '#000000';
								$certificate_number_prefix            = ( isset( $certificate_number_attr['numberPrefix'] ) && ! empty( $certificate_number_attr['numberPrefix'] ) ) ? $certificate_number_attr['numberPrefix'] : '';
								$certificate_number_length            = ( isset( $certificate_number_attr['numberLength'] ) && ! empty( $certificate_number_attr['numberLength'] ) ) ? $certificate_number_attr['numberLength'] : 3;
								$certificate_number_hide_show         = ( isset( $certificate_number_attr['certificateNumber'] ) && ! empty( $certificate_number_attr['certificateNumber'] ) ) ? $certificate_number_attr['certificateNumber'] : false;

								if ( $certificate_number_hide_show && empty( $certificate_number ) && ! empty( $certificate_access_key ) ) {
									$genarated_number   = self::generateNDigitRandomNumber( $certificate_number_length );
									$certificate_number = $certificate_number_prefix . $genarated_number;

									// Update Generate Number.
									global $wpdb, $wpcwdb;
									$cer_number_updated = $wpdb->query( $wpdb->prepare( "UPDATE $wpcwdb->certificates SET cert_number = %s WHERE cert_access_key = %s", $certificate_number, $certificate_access_key ) );
								}
								$certificate_number_attr_text = ( ! empty( $certificate_number ) ) ? $certificate_number : 'CERTIFICATE NUMBER';

								// Expiry Date
								$expiry_date_attr              = $block_inner_blocks[4]['attrs'];
								$expiry_date_attr_text         = ( isset( $expiry_date_attr['textField'] ) && ! empty( $expiry_date_attr['textField'] ) ) ? $expiry_date_attr['textField'] : '27 Nov 2020';
								$expiry_date_family            = ( isset( $expiry_date_attr['fontFamily'] ) && ! empty( $expiry_date_attr['fontFamily'] ) ) ? $expiry_date_attr['fontFamily'] : 'Lato-Black';
								$expiry_date_font_family_style = ( isset( $expiry_date_attr['fontWeight'] ) && ! empty( $expiry_date_attr['fontWeight'] ) ) ? 'B' : '';
								$expiry_date_font_underline    = ( isset( $expiry_date_attr['textUnderline'] ) && ! empty( $expiry_date_attr['textUnderline'] ) ) ? 'U' : '';
								$expiry_date_font_align        = ( isset( $expiry_date_attr['textAlign'] ) && ! empty( $expiry_date_attr['textAlign'] ) ) ? $expiry_date_attr['textAlign'] : 'left';
								$expiry_date_font_family_size  = ( isset( $expiry_date_attr['fontSize'] ) && ! empty( $expiry_date_attr['fontSize'] ) ) ? $expiry_date_attr['fontSize'] : 16;
								$expiry_date_font_color        = ( isset( $expiry_date_attr['textColor'] ) && ! empty( $expiry_date_attr['textColor'] ) ) ? $expiry_date_attr['textColor'] : '#000000';
								$expiry_date_format            = ( isset( $expiry_date_attr['dateFormat'] ) && ! empty( $expiry_date_attr['dateFormat'] ) ) ? $expiry_date_attr['dateFormat'] : get_option( 'date_format' );

								$date_str     = date_i18n( $expiry_date_format, $completeDate );
								$date_str_len = $this->pdffile->GetStringWidth( $date_str );

								$fontname = TCPDF_FONTS::addTTFfont( WPCW_PDF_FONTS . $text_field_family . '.ttf', 'TrueTypeUnicode', '', 96 );
								$this->pdffile->SetFont( $fontname, $text_field_font_underline, $text_field_font_family_size, '', false );
								list($r, $g, $b) = sscanf( $text_field_font_color, '#%02x%02x%02x' );
								if ( 'center' === $text_field_font_align ) {
									$align = 'C';
									$x     = $this->signature_X - 20;
								} elseif ( 'right' === $text_field_font_align ) {
									$align = 'R';
									$x     = $this->signature_X - 32;
								} else {
									$align = 'L';
									$x     = $this->signature_X - 5;
								}
								$this->pdffile->SetTextColor( $r, $g, $b );
								$this->pdffile->SetX( $x );
								$this->pdffile->Cell( 90, 7, $text_field_text, 0, 1, $align, 0 );

								$fontname = TCPDF_FONTS::addTTFfont( WPCW_PDF_FONTS . $cumulative_grade_family . '.ttf', 'TrueTypeUnicode', '', 96 );
								$this->pdffile->SetFont( $fontname, $cumulative_grade_font_underline, $cumulative_grade_font_family_size, '', false );
								list($r, $g, $b) = sscanf( $cumulative_grade_font_color, '#%02x%02x%02x' );
								if ( 'center' === $cumulative_grade_font_align ) {
									$align = 'C';
									$x     = $this->signature_X - 20;
								} elseif ( 'right' === $cumulative_grade_font_align ) {
									$align = 'R';
									$x     = $this->signature_X - 32;
								} else {
									$align = 'L';
									$x     = $this->signature_X - 5;
								}
								$this->pdffile->SetTextColor( $r, $g, $b );
								$this->pdffile->SetX( $x );
								if ( $cumulative_grade_hide_show ) {
									$this->pdffile->Cell( 90, 7, mb_strtoupper( $cumulative_grade_text ), 0, 1, $align, 0 );
								} else {
									$this->pdffile->Cell( 90, 7, '', 0, 1, $align, 0 );
								}
								$this->pdffile->Line( $this->signature_X - 5, $this->footer_Y + 15, $this->signature_X + $this->footer_line_length - 5, $this->footer_Y + 15 );
								$this->pdffile->Ln( 4 );

								$fontname = TCPDF_FONTS::addTTFfont( WPCW_PDF_FONTS . $certificate_number_family . '.ttf', 'TrueTypeUnicode', '', 96 );
								$this->pdffile->SetFont( $fontname, $certificate_number_font_underline, $certificate_number_font_family_size, '', false );
								list($r, $g, $b) = sscanf( $certificate_number_font_color, '#%02x%02x%02x' );
								if ( 'center' === $certificate_number_font_align ) {
									$align = 'C';
									$x     = $this->signature_X - 20;
								} elseif ( 'right' === $certificate_number_font_align ) {
									$align = 'R';
									$x     = $this->signature_X - 32;
								} else {
									$align = 'L';
									$x     = $this->signature_X - 5;
								}
								$this->pdffile->SetTextColor( $r, $g, $b );
								$this->pdffile->SetX( $x );
								if ( $certificate_number_hide_show ) {
									$this->pdffile->Cell( 90, 5, mb_strtoupper( $certificate_number_attr_text ), 0, 1, $align, 0 );
								}
								// $this->pdffile->Cell( 90, 5, mb_strtoupper( $certificate_number_attr_text ), 0, 1, $align, 0 );

								$fontname = TCPDF_FONTS::addTTFfont( WPCW_PDF_FONTS . $expiry_date_family . '.ttf', 'TrueTypeUnicode', '', 96 );
								$this->pdffile->SetFont( $fontname, $expiry_date_font_underline, $expiry_date_font_family_size, '', false );
								list($r, $g, $b) = sscanf( $expiry_date_font_color, '#%02x%02x%02x' );
								if ( 'center' === $expiry_date_font_align ) {
									$align = 'C';
									$x     = $this->signature_X - 20;
								} elseif ( 'right' === $expiry_date_font_align ) {
									$align = 'R';
									$x     = $this->signature_X - 32;
								} else {
									$align = 'L';
									$x     = $this->signature_X - 5;
								}
								$this->pdffile->SetTextColor( $r, $g, $b );
								$this->pdffile->SetX( $x );
								$this->pdffile->Cell( 90, 5, mb_strtoupper( $date_str ), 0, 1, $align, 0 );
							}
							break;
					}
					break;
			}

		}
		/**
		 * Generate the certificate PDF.
		 *
		 * @param String $student The name of the student.
		 * @param String $courseName The name of the course.
		 * @param String $certificateDetails The raw certificate details.
		 * @param String $showMode What type of export to do. ('download' to force a download or 'browser' to do it inline.)
		 */
		public function generatePDF( $student, $courseName, $certificateDetails = array(), $showMode = 'download', $showTemplate = false, $courseID = '', $certificateID = '' ) {
			if ( ! $showTemplate ) {
				// Do codepage conversions of text used in the certificate.
				$encoding = WPCW_arrays_getValue( $this->settingsList, 'certificate_encoding', 'ISO-8859-1' );

				// Top Line Coordinate.
				$topLineY = 45;

				// Set the background image
				$bgType = WPCW_arrays_getValue( $this->settingsList, 'cert_background_type', 'use_default' );
				$bgImg  = WPCW_arrays_getValue( $this->settingsList, 'cert_background_custom_url' );

				// Disable auto-page-break
				$this->pdffile->SetAutoPageBreak( false, 0 );

				// Use custom image
				if ( $bgType == 'use_custom' ) {
					if ( $bgImg ) {
						$bgImg = $this->pdf_link_path( $bgImg );
						$this->pdffile->Image( $bgImg, 0, 0, $this->size_width, $this->size_height );
					}
				} else {
					$this->pdffile->Image( WPCW_plugin_getPluginDirPath() . 'assets/img/certificates/certificate_bg.jpg', 0, 0, 297, 210, '', '', '', false, 300, '', false, false, 0 );
				}

				// Primary Font.
				$primary_font_default_args = array(
					'family'      => 'ArchitectsDaughter',
					'file'        => '',
					'style'       => '',
					'size'        => 16,
					'footer_size' => 15,
				);

				$primary_font_args = apply_filters( 'wpcw_certificate_primary_font', $primary_font_default_args, $certificateDetails, $this->settingsList );
				$primary_font      = wp_parse_args( $primary_font_args, $primary_font_default_args );

				$primary_font_family             = ( ! empty( $primary_font['family'] ) ) ? $primary_font['family'] : 'ArchitectsDaughter';
				$primary_font_family_file        = ( ! empty( $primary_font['file'] ) ) ? $primary_font['file'] : '';
				$primary_font_family_style       = ( ! empty( $primary_font['style'] ) ) ? $primary_font['style'] : '';
				$primary_font_family_size        = ( ! empty( $primary_font['size'] ) ) ? $primary_font['size'] : 16;
				$primary_font_family_footer_size = ( ! empty( $primary_font['footer_size'] ) ) ? $primary_font['footer_size'] : 15;

				if ( $primary_font_family_file ) {
					$this->pdffile->AddFont( $primary_font_family, $primary_font_family_style, $primary_font_family_file );
				}

				// Secondary Font.
				$secondary_font_default_args = array(
					'family' => 'dejavusansb',
					'file'   => '',
					'style'  => 'B',
					'size'   => 32,
				);

				$secondary_font_args         = apply_filters( 'wpcw_certificate_secondary_font', $secondary_font_default_args, $certificateDetails, $this->settingsList );
				$secondary_font              = wp_parse_args( $secondary_font_args, $secondary_font_default_args );
				$secondary_font_family       = ( ! empty( $secondary_font['family'] ) ) ? $secondary_font['family'] : 'dejavusansb';
				$secondary_font_family_file  = ( ! empty( $secondary_font['file'] ) ) ? $secondary_font['file'] : '';
				$secondary_font_family_style = ( ! empty( $secondary_font['style'] ) ) ? $secondary_font['style'] : 'B';
				$secondary_font_family_size  = ( ! empty( $secondary_font['size'] ) ) ? $secondary_font['size'] : 32;

				if ( $secondary_font_family_file ) {
					$this->pdffile->AddFont( $secondary_font_family, $secondary_font_family_style, $secondary_font_family_file );
				}

				// Footer Font.
				$footer_font_default_args = array(
					'family' => 'Helvetica',
					'file'   => '',
					'style'  => '',
					'size'   => 14,
				);

				$footer_font_args         = apply_filters( 'wpcw_certificate_footer_font', $footer_font_default_args, $certificateDetails, $this->settingsList );
				$footer_font              = wp_parse_args( $footer_font_args, $footer_font_default_args );
				$footer_font_family       = ( ! empty( $footer_font['family'] ) ) ? $footer_font['family'] : 'Helvetica';
				$footer_font_family_file  = ( ! empty( $footer_font['file'] ) ) ? $footer_font['file'] : '';
				$footer_font_family_style = ( ! empty( $footer_font['style'] ) ) ? $footer_font['style'] : '';
				$footer_font_family_size  = ( ! empty( $footer_font['size'] ) ) ? $footer_font['size'] : 14;

				if ( $footer_font_family_file ) {
					$this->pdffile->AddFont( $footer_font_family, $footer_font_family_style, $footer_font_family_file );
				}

				// ...Certify...
				$certify_text = apply_filters( 'wpcw_certificate_certify_text', __( 'This is to certify that', 'wp-courseware' ), $certificateDetails, $this->settingsList );
				$this->pdffile->SetFont( $secondary_font_family, $secondary_font_family_style, $secondary_font_family_size, $secondary_font_family_file );
				$this->centerString( $certify_text, $topLineY );

				// Name with a line underneath
				$this->pdffile->SetFont( $primary_font_family, $primary_font_family_style, $primary_font_family_size, $primary_font_family_file, false );
				$this->centerString( $student, $topLineY + 20 );
				$this->centerLine( 120, $topLineY + 27 );

				// ...Completed...
				$completed_text = apply_filters( 'wpcw_certificate_completed_text', __( 'has successfully completed', 'wp-courseware' ), $certificateDetails, $this->settingsList );
				$this->pdffile->SetFont( $secondary_font_family, $secondary_font_family_style, $secondary_font_family_size, $secondary_font_family_file );
				$this->centerString( $completed_text, $topLineY + 50 );

				// Course
				$this->pdffile->SetFont( $primary_font_family, $primary_font_family_style, $primary_font_family_size, $primary_font_family_file );
				$this->centerString( $courseName, $topLineY + 70 );
				$this->centerLine( 180, $topLineY + 77 );

				$this->footer_line_length = 60;
				$this->footer_Y           = 162;

				$date_X            = 40;
				$this->signature_X = $this->size_width - 100;

				// Footer Font Family
				$this->pdffile->SetFont( $footer_font_family, $footer_font_family_style, $footer_font_family_size );

				// Date Text.
				$date_text = apply_filters( 'wpcw_certificate_date_text', __( 'Date', 'wp-courseware' ), $certificateDetails, $this->settingsList );

				// Date - field
				$this->pdffile->SetXY( $date_X, $this->footer_Y + 8 );
				$this->pdffile->Cell( 0, 0, $date_text, false, false, 'L' );

				// Instructor Text.
				$instructor_text = apply_filters( 'wpcw_certificate_instructor_text', __( 'Instructor', 'wp-courseware' ), $certificateDetails, $this->settingsList );

				// Signature - field
				$this->pdffile->SetXY( $this->signature_X, $this->footer_Y + 8 );
				$this->pdffile->Cell( 0, 0, $instructor_text, false, false, 'L' );

				// Lines - Date, Signature
				$this->pdffile->Line( $date_X, $this->footer_Y + 7, $date_X + $this->footer_line_length, $this->footer_Y + 7 );
				$this->pdffile->Line( $this->signature_X, $this->footer_Y + 7, $this->signature_X + $this->footer_line_length, $this->footer_Y + 7 );

				// Date - the date itself. Centre on the line
				$this->pdffile->SetFont( $primary_font_family, $primary_font_family_style, $primary_font_family_footer_size, $primary_font_family_file );

				// Use date of completion if available from certificate details
				$completeDate = false;
				if ( $certificateDetails && $certificateDetails->cert_generated ) {
					$completeDate = strtotime( $certificateDetails->cert_generated );
				}

				// Use current date if not available.
				if ( $completeDate <= 0 ) {
					$completeDate = current_time( 'timestamp' );
				}

				$date_localFormat = get_option( 'date_format' );
				$date_str         = date_i18n( $date_localFormat, $completeDate );
				$date_str_len     = $this->pdffile->GetStringWidth( $date_str );

				$this->pdffile->SetXY( $date_X + ( ( $this->footer_line_length - $date_str_len ) / 2 ), $this->footer_Y );
				$this->pdffile->Cell( 0, 0, $date_str, false, false );

				// Remove header/footer
				$this->pdffile->setPrintHeader( false );
				$this->pdffile->setPrintFooter( false );

				// Signature - signature itself
				$this->render_handleSignature();

				// Logo - handle rendering a logo if one exists
				$this->render_handleLogo();

				// Clean out any previous output
				if ( ob_get_contents() ) {
					ob_end_clean();
				}

				// Change output based on what's been specified as a parameter.
				if ( 'browser' == $showMode ) {
					$this->pdffile->Output( 'certificate.pdf', 'I' );
				} else {
					$this->pdffile->Output( 'certificate.pdf', 'D' );
				}
			} else {

				$course_id              = $courseID;
				$certificate_id         = $certificateID;
				$certificate_number     = '';
				$certificate_access_key = '';

				if ( ! empty( $course_id ) && 'block_preview' !== $course_id ) {
					$course_details = WPCW_courses_getCourseDetails( absint( $course_id ) );

					// Cumulative Grad calculation
					set_time_limit( 0 );
					global $wpdb, $wpcwdb;
					$wpdb->show_errors();

					// Get users to email final grades to
					$usersNeedGrades_SQL = $wpdb->prepare(
						"SELECT *
						FROM $wpcwdb->user_courses uc
						LEFT JOIN $wpdb->users u ON u.ID = uc.user_id
						WHERE uc.course_id = %d
						AND u.ID IS NOT NULL
						AND uc.course_progress = 100
						AND uc.course_final_grade_sent != 'sent'
						",
						$course_details->course_id
					);

					// Allow the list of users to email to be customised.
					$usersNeedGrades = $wpdb->get_results( apply_filters( 'wpcw_back_query_filter_gradebook_users_final_grades_email', $usersNeedGrades_SQL, $course_details ) );

					// Get all the quizzes for this course
					$quizIDList       = array();
					$quizIDListForSQL = false;
					$quizzesForCourse = WPCW_quizzes_getAllQuizzesForCourse( $course_details->course_id );

					// Create a simple list of IDs to use in SQL queries
					if ( $quizzesForCourse ) {
						foreach ( $quizzesForCourse as $singleQuiz ) {
							$quizIDList[ $singleQuiz->quiz_id ] = $singleQuiz;
						}

						// Convert list of IDs into an SQL list
						$quizIDListForSQL = '(' . implode( ',', array_keys( $quizIDList ) ) . ')';
					}

					// Run through each user, and generate their details.
					$userCount       = 1;
					$cumulativeGrade = '0.00%';
					if ( ! empty( $usersNeedGrades ) ) {
						foreach ( $usersNeedGrades as $aSingleUser ) {
							if ( ! empty( $quizIDList ) ) {
								// Get quiz results for this user
								$quizResults = WPCW_quizzes_getQuizResultsForUser( $aSingleUser->ID, $quizIDListForSQL );

								// Track cumulative data
								$quizScoresSoFar       = 0;
								$quizScoresSoFar_count = 0;

								// ### Now render results for each quiz
								foreach ( $quizIDList as $aQuizID => $singleQuiz ) {
									// Got progress data, process the result
									if ( isset( $quizResults[ $aQuizID ] ) ) {
										// Extract results and unserialise the data array.
										$theResults            = $quizResults[ $aQuizID ];
										$theResults->quiz_data = maybe_unserialize( $theResults->quiz_data );

										// We've got something that needs grading.
										if ( $theResults->quiz_needs_marking == 0 ) {
											// Calculate score, and use for cumulative.
											$score            = number_format( $theResults->quiz_grade );
											$quizScoresSoFar += $score;
											$quizScoresSoFar_count++;

										}
									} // end of quiz result check.
								}
								// Calculate the cumulative grade
								$cumulativeGrade = ( $quizScoresSoFar_count > 0 ? number_format( ( $quizScoresSoFar / $quizScoresSoFar_count ), 2 ) . '%' : __( '-', 'wp-courseware' ) );
							} // end of check for quizzes for course
						}
					}

					$course_name          = $course_details->course_title;
					$course_instructor_id = $course_details->course_author;
					if ( ! empty( $course_instructor_id ) ) {
						$user                    = get_user_by( 'id', $course_instructor_id );
						$instructor_display_name = $user->display_name;
					} else {
						$instructor_display_name = '';
					}

					if ( ! empty( $certificate_id ) ) {
						$certificate_details = WPCW_certificate_getCertificateDetails_byAccessKey( $certificate_id );

					}
					$completeDate = false;
					if ( $certificate_details && $certificate_details->cert_generated ) {
						$certificate_number     = $certificate_details->cert_number;
						$certificate_access_key = $certificate_details->cert_access_key;
						$completeDate           = strtotime( $certificate_details->cert_generated );
					}
					if ( $completeDate <= 0 ) {
						 $completeDate = current_time( 'timestamp' );
					}
				} else {
					$course_name             = '';
					$completeDate            = current_time( 'timestamp' );
					$instructor_display_name = '';
					$cumulativeGrade         = '';
				}

				$block_name   = $certificateDetails['blockName'];
				$attrs        = $certificateDetails['attrs'];
				$inner_blocks = $certificateDetails['innerBlocks'];

				if ( 'wpcw/courseware-certificate' === $block_name ) {
					$inner_blocks = $certificateDetails['innerBlocks'];
					if ( ! empty( $inner_blocks ) ) {
						$block_name   = $inner_blocks[0]['blockName'];
						$attrs        = $inner_blocks[0]['attrs'];
						$inner_blocks = $inner_blocks[0]['innerBlocks'];
					}
				}
				if ( ! empty( $inner_blocks ) ) {
					$block_array = array();
					foreach ( $inner_blocks as $k => $inner_block ) {
						$block_array[ $k ]['blockName']   = $inner_block['blockName'];
						$block_array[ $k ]['attrs']       = $inner_block['attrs'];
						$block_array[ $k ]['innerBlocks'] = $inner_block['innerBlocks'];
					}
				}
				// Do codepage conversions of text used in the certificate.
				$encoding = WPCW_arrays_getValue( $this->settingsList, 'certificate_encoding', 'ISO-8859-1' );

				// Top Line Coordinate.
				$topLineY = 45;
				$topLinex = 45;

				// Set the background image.
				$bgType = WPCW_arrays_getValue( $this->settingsList, 'cert_background_type', 'use_default' );
				$bgImg  = WPCW_arrays_getValue( $this->settingsList, 'cert_background_custom_url' );

				// Disable auto-page-break.
				$this->pdffile->SetAutoPageBreak( false, 0 );
				// $this->pdffile->SetMargins(0, 0, 45, true);

				if ( ! empty( $attrs ) ) {
					$background_color = ( isset( $attrs['backgroundColor'] ) && ! empty( $attrs['backgroundColor'] ) ) ? $attrs['backgroundColor'] : '';
					$background_image = ( isset( $attrs['backgroundImage'] ) && ! empty( $attrs['backgroundImage'] ) ) ? $attrs['backgroundImage'] : '';
				}

				if ( empty( $background_color ) && empty( $background_image ) ) {
					$this->pdffile->Image( WPCW_plugin_getPluginDirPath() . 'assets/img/certificates/certificate_bg.jpg', 0, 0, 297, 210, '', '', '', false, 300, '', false, false, 0 );
				} else {
					if ( ! empty( $background_image ) ) {
						$background_image = $this->pdf_link_path( $background_image );
						$this->pdffile->Image( $background_image, 0, 0, 297, 210, '', '', '', false, 300, '', false, false, 0 );
					} else {
						list($r, $g, $b) = sscanf( $background_color, '#%02x%02x%02x' );
						$this->pdffile->Rect( 0, 0, $this->pdffile->getPageWidth(), $this->pdffile->getPageHeight(), 'DF', '', array( $r, $g, $b ) );
					}
				}

				if ( 'wpcw/pre-template-1' === $block_name ) {
					/**
					 *  Block 1 styles
					 */
					$block1                   = $block_array[0]['attrs'];
					$block1_text              = ( isset( $block1['textField'] ) && ! empty( $block1['textField'] ) ) ? $block1['textField'] : '';
					$block1_family            = ( isset( $block1['fontFamily'] ) && ! empty( $block1['fontFamily'] ) ) ? $block1['fontFamily'] : 'Lato-Black';
					$block1_font_family_style = ( isset( $block1['fontWeight'] ) && ! empty( $block1['fontWeight'] ) ) ? 'B' : '';
					$block1_font_underline    = ( isset( $block1['textUnderline'] ) && ! empty( $block1['textUnderline'] ) ) ? 'U' : '';
					$block1_font_align        = ( isset( $block1['textAlign'] ) && ! empty( $block1['textAlign'] ) ) ? $block1['textAlign'] : 'left';
					$block1_font_family_size  = ( isset( $block1['fontSize'] ) && ! empty( $block1['fontSize'] ) ) ? $block1['fontSize'] : 16;
					$block1_font_color        = ( isset( $block1['textColor'] ) && ! empty( $block1['textColor'] ) ) ? $block1['textColor'] : '#000000';
					list($r, $g, $b)          = sscanf( $block1_font_color, '#%02x%02x%02x' );

					$fontname = TCPDF_FONTS::addTTFfont( WPCW_PDF_FONTS . $block1_family . '.ttf', 'TrueTypeUnicode', '', 96 );
					$this->pdffile->SetFont( $fontname, $block1_font_underline, $block1_font_family_size, '', false );
					$this->pdffile->SetTextColor( $r, $g, $b );
					if ( 'center' === $block1_font_align ) {
						$align = 'C';
					} elseif ( 'right' === $block1_font_align ) {
						$align = 'R';
					} else {
						$align = 'L';
					}
					$this->pdffile->SetXY( $topLinex, $topLineY );
					$this->pdffile->Cell( 210, 0, $block1_text, false, false, $align );

					/**
					 *  Block 2 styles
					 */
					$block2                   = $block_array[1]['attrs'];
					$block2_text              = ( isset( $course_name ) && ! empty( $course_name ) ) ? $course_name : __( 'COURSE TITLE', 'wp-courseware' );
					$block2_family            = ( isset( $block2['fontFamily'] ) && ! empty( $block2['fontFamily'] ) ) ? $block2['fontFamily'] : 'Lato-Black';
					$block2_font_family_style = ( isset( $block2['fontWeight'] ) && ! empty( $block2['fontWeight'] ) ) ? 'B' : '';
					$block2_font_underline    = ( isset( $block2['textUnderline'] ) && ! empty( $block2['textUnderline'] ) ) ? 'U' : '';
					$block2_font_align        = ( isset( $block2['textAlign'] ) && ! empty( $block2['textAlign'] ) ) ? $block2['textAlign'] : 'left';
					$block2_font_family_size  = ( isset( $block2['fontSize'] ) && ! empty( $block2['fontSize'] ) ) ? $block2['fontSize'] : 16;
					$block2_font_color        = ( isset( $block2['textColor'] ) && ! empty( $block2['textColor'] ) ) ? $block2['textColor'] : '#000000';
					list($r, $g, $b)          = sscanf( $block2_font_color, '#%02x%02x%02x' );

					$fontname = TCPDF_FONTS::addTTFfont( WPCW_PDF_FONTS . $block2_family . '.ttf', 'TrueTypeUnicode', '', 96 );
					$this->pdffile->SetFont( $fontname, $block2_font_underline, $block2_font_family_size, '', false );
					$this->pdffile->SetTextColor( $r, $g, $b );
					if ( 'center' === $block2_font_align ) {
						$align = 'C';
					} elseif ( 'right' === $block2_font_align ) {
						$align = 'R';
					} else {
						$align = 'L';
					}
					$this->pdffile->SetXY( $topLinex, $topLineY + 12 );
					$this->pdffile->Cell( 210, 0, $block2_text, false, false, $align );
					// $this->centerString( mb_strtoupper( $block2_text ), $topLineY + 12 );

					/**
					 *  Block 3 styles
					 */
					$block3                   = $block_array[2]['attrs'];
					$block3_text              = ( isset( $block3['textField'] ) && ! empty( $block3['textField'] ) ) ? $block3['textField'] : '';
					$block3_family            = ( isset( $block3['fontFamily'] ) && ! empty( $block3['fontFamily'] ) ) ? $block3['fontFamily'] : 'Lato-Black';
					$block3_font_family_style = ( isset( $block3['fontWeight'] ) && ! empty( $block3['fontWeight'] ) ) ? 'B' : '';
					$block3_font_underline    = ( isset( $block3['textUnderline'] ) && ! empty( $block3['textUnderline'] ) ) ? 'U' : '';
					$block3_font_align        = ( isset( $block3['textAlign'] ) && ! empty( $block3['textAlign'] ) ) ? $block3['textAlign'] : 'left';
					$block3_font_family_size  = ( isset( $block3['fontSize'] ) && ! empty( $block3['fontSize'] ) ) ? $block3['fontSize'] : 16;
					$block3_font_color        = ( isset( $block3['textColor'] ) && ! empty( $block3['textColor'] ) ) ? $block3['textColor'] : '#000000';
					list($r, $g, $b)          = sscanf( $block3_font_color, '#%02x%02x%02x' );

					$fontname = TCPDF_FONTS::addTTFfont( WPCW_PDF_FONTS . $block3_family . '.ttf', 'TrueTypeUnicode', '', 96 );
					$this->pdffile->SetFont( $fontname, $block3_font_underline, $block3_font_family_size, '', false );
					$this->pdffile->SetTextColor( $r, $g, $b );
					if ( 'center' === $block3_font_align ) {
						$align = 'C';
					} elseif ( 'right' === $block3_font_align ) {
						$align = 'R';
					} else {
						$align = 'L';
					}
					$this->pdffile->SetXY( $topLinex, $topLineY + 30 );
					$this->pdffile->Cell( 210, 0, $block3_text, false, false, $align );
					// $this->centerString( $block3_text, $topLineY + 22 );

					/**
					 *  Block 4 styles
					 */
					$block4                   = $block_array[3]['attrs'];
					$block4_text              = ( isset( $student ) && ! empty( $student ) ) ? $student : 'STUDENT NAME';
					$block4_family            = ( isset( $block4['fontFamily'] ) && ! empty( $block4['fontFamily'] ) ) ? $block4['fontFamily'] : 'Lato-Black';
					$block4_font_family_file  = ( isset( $block4['file'] ) && ! empty( $block4['file'] ) ) ? $block4['file'] : '';
					$block4_font_family_style = ( isset( $block4['fontWeight'] ) && ! empty( $block4['fontWeight'] ) ) ? 'B' : '';
					$block4_font_underline    = ( isset( $block4['textUnderline'] ) && ! empty( $block4['textUnderline'] ) ) ? 'U' : '';
					$block4_font_align        = ( isset( $block4['textAlign'] ) && ! empty( $block4['textAlign'] ) ) ? $block4['textAlign'] : 'left';
					$block4_font_family_size  = ( isset( $block4['fontSize'] ) && ! empty( $block4['fontSize'] ) ) ? $block4['fontSize'] : 16;
					$block4_font_color        = ( isset( $block4['textColor'] ) && ! empty( $block4['textColor'] ) ) ? $block4['textColor'] : '#000000';
					list($r, $g, $b)          = sscanf( $block4_font_color, '#%02x%02x%02x' );

					$fontname = TCPDF_FONTS::addTTFfont( WPCW_PDF_FONTS . $block4_family . '.ttf', 'TrueTypeUnicode', '', 96 );
					$this->pdffile->SetFont( $fontname, $block4_font_underline, $block4_font_family_size, '', false );
					$this->pdffile->SetTextColor( $r, $g, $b );
					if ( 'center' === $block4_font_align ) {
						$align = 'C';
					} elseif ( 'right' === $block4_font_align ) {
						$align = 'R';
					} else {
						$align = 'L';
					}
					$this->pdffile->SetXY( $topLinex, $topLineY + 39 );
					$this->pdffile->Cell( 210, 0, mb_strtoupper( $block4_text ), false, false, $align );
					// $this->centerString( mb_strtoupper( $block4_text ), $topLineY + 39 );

					$this->footer_line_length = 60;
					$this->footer_Y           = 120;
					$date_X                   = 40;
					$this->signature_X        = $this->size_width - 95;

					$y       = 120;
					$columns = array(
						array(
							'w' => 90,
							's' => 1,
							'y' => $y,
						),
						array(
							'w' => 80,
							's' => 1,
							'y' => $y,
						),
						array(
							'w' => 90,
							's' => 1,
							'y' => $y,
						),
					);
					$this->pdffile->SetColumnsArray( $columns );
					/**
					 *  Block 5: Signature styles
					 */
					$block5 = isset( $block_array[4]['attrs'] ) ? $block_array[4]['attrs'] : array();
					if ( empty( $block5 ) ) {
						if ( isset( $block_array[4]['innerBlocks'] ) && ! empty( $block_array[4]['innerBlocks'] ) ) {
							// Signature Image.
							$signature_image = isset( $block_array[4]['innerBlocks'][0]['attrs']['selectedImage'] ) ? $block_array[4]['innerBlocks'][0]['attrs']['selectedImage'] : WPCW_PATH . 'assets/img/certificates/signature.png';

							// Instructor Name.
							$instructor_attr              = $block_array[4]['innerBlocks'][2]['attrs'];
							$instructor_text              = ( ! empty( $instructor_display_name ) ) ? $instructor_display_name : __( 'INSTRUCTOR NAME', 'wp-courseware' );
							$instructor_family            = ( isset( $instructor_attr['fontFamily'] ) && ! empty( $instructor_attr['fontFamily'] ) ) ? $instructor_attr['fontFamily'] : 'Lato-Black';
							$instructor_font_family_style = ( isset( $instructor_attr['fontWeight'] ) && ! empty( $instructor_attr['fontWeight'] ) ) ? 'B' : '';
							$instructor_font_underline    = ( isset( $instructor_attr['textUnderline'] ) && ! empty( $instructor_attr['textUnderline'] ) ) ? 'U' : '';
							$instructor_font_align        = ( isset( $instructor_attr['textAlign'] ) && ! empty( $instructor_attr['textAlign'] ) ) ? $instructor_attr['textAlign'] : 'left';
							$instructor_font_family_size  = ( isset( $instructor_attr['fontSize'] ) && ! empty( $instructor_attr['fontSize'] ) ) ? $instructor_attr['fontSize'] : 16;
							$instructor_font_color        = ( isset( $instructor_attr['textColor'] ) && ! empty( $instructor_attr['textColor'] ) ) ? $instructor_attr['textColor'] : '#000000';
							$instructor_text_title        = ( isset( $instructor_attr['instructorTitle'] ) && ! empty( $instructor_attr['instructorTitle'] ) ) ? $instructor_attr['instructorTitle'] : '';
							list($r, $g, $b)              = sscanf( $instructor_font_color, '#%02x%02x%02x' );

						}

						$this->pdffile->Ln( 30 );
						$this->pdffile->SelectColumn( 0 );
						$this->pdffile->SetFillColor( 255, 0, 0 );
						$this->pdffile->Ln( 4 );
						$this->pdffile->Cell( 120, 20, '', 0, 1, 'C', 0 );
						$signWidth = self::px2mm( WPCW_CERTIFICATE_SIGNATURE_WIDTH_PX );
						$sigHeight = self::px2mm( WPCW_CERTIFICATE_SIGNATURE_HEIGHT_PX );

						// Make link relative.
						$signatureImg = $this->pdf_link_path( $signature_image );
						$this->pdffile->Image( $signatureImg, 50, $this->footer_Y, 40, 15 );

						$this->pdffile->Line( $date_X, $this->footer_Y + 15, $date_X + $this->footer_line_length, $this->footer_Y + 15 );

						$fontname = TCPDF_FONTS::addTTFfont( WPCW_PDF_FONTS . $instructor_family . '.ttf', 'TrueTypeUnicode', '', 96 );
						$this->pdffile->SetFont( $fontname, $instructor_font_underline, $instructor_font_family_size, '', false );
						$this->pdffile->SetTextColor( $r, $g, $b );
						if ( 'center' === $instructor_font_align ) {
							$align = 'C';
							$x     = 18;
						} elseif ( 'right' === $instructor_font_align ) {
							$align = 'R';
							$x     = 0;
						} else {
							$align = 'L';
							$x     = 40;
						}
						$this->pdffile->SetX( $x );
						$this->pdffile->Cell( 100, 5, mb_strtoupper( $instructor_text ), 0, 1, $align, 0 );
						$this->pdffile->SetX( $x );
						$this->pdffile->Cell( 100, 5, mb_strtoupper( $instructor_text_title ), 0, 1, $align, 0 );
					}

					/**
					 *  Block 6: Image styles
					 */
					$block6     = isset( $block_array[5]['attrs'] ) ? $block_array[5]['attrs'] : array();
					$logo_image = isset( $block6['selectedImage'] ) ? $block6['selectedImage'] : WPCW_PATH . 'assets/img/certificates/default-img-1.jpg';
					$this->pdffile->Ln( 25 );
					$this->pdffile->SelectColumn( 1 );
					$logoWidth  = self::px2mm( WPCW_CERTIFICATE_LOGO_WIDTH_PX );
					$logoHeight = self::px2mm( WPCW_CERTIFICATE_LOGO_HEIGHT_PX );
					$logoImg    = $this->pdf_link_path( $logo_image );
					$this->pdffile->Image( $logoImg, $this->getLeftOfCentre( $logoWidth ), $this->footer_Y - 5, $logoWidth, $logoHeight ); // Only force width

					/**
					 *  Block 7: Certificat details styles
					 */
					$block7 = isset( $block_array[6]['attrs'] ) ? $block_array[6]['attrs'] : array();
					if ( empty( $block7 ) ) {
						if ( ! empty( $block_array[6]['innerBlocks'] ) ) {
							// Text Entry field.
							$text_field_attr              = isset( $block_array[6]['innerBlocks'][0]['attrs'] ) ? $block_array[6]['innerBlocks'][0]['attrs'] : array();
							$text_field_text              = ( isset( $text_field_attr['textField'] ) && ! empty( $text_field_attr['textField'] ) ) ? $text_field_attr['textField'] : 'TEXT ENTRY';
							$text_field_family            = ( isset( $text_field_attr['fontFamily'] ) && ! empty( $text_field_attr['fontFamily'] ) ) ? $text_field_attr['fontFamily'] : 'Lato-Black';
							$text_field_font_family_style = ( isset( $text_field_attr['fontWeight'] ) && ! empty( $text_field_attr['fontWeight'] ) ) ? 'B' : '';
							$text_field_font_underline    = ( isset( $text_field_attr['textUnderline'] ) && ! empty( $text_field_attr['textUnderline'] ) ) ? 'U' : '';
							$text_field_font_align        = ( isset( $text_field_attr['textAlign'] ) && ! empty( $text_field_attr['textAlign'] ) ) ? $text_field_attr['textAlign'] : 'left';
							$text_field_font_family_size  = ( isset( $text_field_attr['fontSize'] ) && ! empty( $text_field_attr['fontSize'] ) ) ? $text_field_attr['fontSize'] : 16;
							$text_field_font_color        = ( isset( $text_field_attr['textColor'] ) && ! empty( $text_field_attr['textColor'] ) ) ? $text_field_attr['textColor'] : '#000000';

							// Cumulative Grade.
							$cumulative_grade_attr              = $block_array[6]['innerBlocks'][1]['attrs'];
							$cumulative_grade_text              = ( ! empty( $cumulativeGrade ) ) ? $cumulativeGrade : 'XX%';
							$cumulative_grade_family            = ( isset( $cumulative_grade_attr['fontFamily'] ) && ! empty( $cumulative_grade_attr['fontFamily'] ) ) ? $cumulative_grade_attr['fontFamily'] : 'Lato-Black';
							$cumulative_grade_font_family_style = ( isset( $cumulative_grade_attr['fontWeight'] ) && ! empty( $cumulative_grade_attr['fontWeight'] ) ) ? 'B' : '';
							$cumulative_grade_font_underline    = ( isset( $cumulative_grade_attr['textUnderline'] ) && ! empty( $cumulative_grade_attr['textUnderline'] ) ) ? 'U' : '';
							$cumulative_grade_font_align        = ( isset( $cumulative_grade_attr['textAlign'] ) && ! empty( $cumulative_grade_attr['textAlign'] ) ) ? $cumulative_grade_attr['textAlign'] : 'left';
							$cumulative_grade_font_family_size  = ( isset( $cumulative_grade_attr['fontSize'] ) && ! empty( $cumulative_grade_attr['fontSize'] ) ) ? $cumulative_grade_attr['fontSize'] : 16;
							$cumulative_grade_font_color        = ( isset( $cumulative_grade_attr['textColor'] ) && ! empty( $cumulative_grade_attr['textColor'] ) ) ? $cumulative_grade_attr['textColor'] : '#000000';
							$cumulative_grade_hide_show         = ( isset( $cumulative_grade_attr['cumulativeGrade'] ) && ! empty( $cumulative_grade_attr['cumulativeGrade'] ) ) ? $cumulative_grade_attr['cumulativeGrade'] : false;

							// Certificate Number.
							$certificate_number_attr              = $block_array[6]['innerBlocks'][3]['attrs'];
							$certificate_number_family            = ( isset( $certificate_number_attr['fontFamily'] ) && ! empty( $certificate_number_attr['fontFamily'] ) ) ? $certificate_number_attr['fontFamily'] : 'Lato-Black';
							$certificate_number_font_family_style = ( isset( $certificate_number_attr['fontWeight'] ) && ! empty( $certificate_number_attr['fontWeight'] ) ) ? 'B' : '';
							$certificate_number_font_underline    = ( isset( $certificate_number_attr['textUnderline'] ) && ! empty( $certificate_number_attr['textUnderline'] ) ) ? 'U' : '';
							$certificate_number_font_align        = ( isset( $certificate_number_attr['textAlign'] ) && ! empty( $certificate_number_attr['textAlign'] ) ) ? $certificate_number_attr['textAlign'] : 'left';
							$certificate_number_font_family_size  = ( isset( $certificate_number_attr['fontSize'] ) && ! empty( $certificate_number_attr['fontSize'] ) ) ? $certificate_number_attr['fontSize'] : 16;
							$certificate_number_font_color        = ( isset( $certificate_number_attr['textColor'] ) && ! empty( $certificate_number_attr['textColor'] ) ) ? $certificate_number_attr['textColor'] : '#000000';
							$certificate_number_prefix            = ( isset( $certificate_number_attr['numberPrefix'] ) && ! empty( $certificate_number_attr['numberPrefix'] ) ) ? $certificate_number_attr['numberPrefix'] : '';
							$certificate_number_length            = ( isset( $certificate_number_attr['numberLength'] ) && ! empty( $certificate_number_attr['numberLength'] ) ) ? $certificate_number_attr['numberLength'] : 3;
							$certificate_number_hide_show         = ( isset( $certificate_number_attr['certificateNumber'] ) && ! empty( $certificate_number_attr['certificateNumber'] ) ) ? $certificate_number_attr['certificateNumber'] : false;

							if ( $certificate_number_hide_show && empty( $certificate_number ) && ! empty( $certificate_access_key ) ) {
								$genarated_number   = self::generateNDigitRandomNumber( $certificate_number_length );
								$certificate_number = $certificate_number_prefix . $genarated_number;

								// Update Generate Number.
								global $wpdb, $wpcwdb;
								$cer_number_updated = $wpdb->query( $wpdb->prepare( "UPDATE $wpcwdb->certificates SET cert_number = %s WHERE cert_access_key = %s", $certificate_number, $certificate_access_key ) );
							}
							$certificate_number_attr_text = ( ! empty( $certificate_number ) ) ? $certificate_number : 'CERTIFICATE NUMBER';

							// Expiry Date
							$expiry_date_attr              = $block_array[6]['innerBlocks'][4]['attrs'];
							$expiry_date_attr_text         = ( isset( $expiry_date_attr['textField'] ) && ! empty( $expiry_date_attr['textField'] ) ) ? $expiry_date_attr['textField'] : '27 Nov 2020';
							$expiry_date_family            = ( isset( $expiry_date_attr['fontFamily'] ) && ! empty( $expiry_date_attr['fontFamily'] ) ) ? $expiry_date_attr['fontFamily'] : 'Lato-Black';
							$expiry_date_font_family_style = ( isset( $expiry_date_attr['fontWeight'] ) && ! empty( $expiry_date_attr['fontWeight'] ) ) ? 'B' : '';
							$expiry_date_font_underline    = ( isset( $expiry_date_attr['textUnderline'] ) && ! empty( $expiry_date_attr['textUnderline'] ) ) ? 'U' : '';
							$expiry_date_font_align        = ( isset( $expiry_date_attr['textAlign'] ) && ! empty( $expiry_date_attr['textAlign'] ) ) ? $expiry_date_attr['textAlign'] : 'left';
							$expiry_date_font_family_size  = ( isset( $expiry_date_attr['fontSize'] ) && ! empty( $expiry_date_attr['fontSize'] ) ) ? $expiry_date_attr['fontSize'] : 16;
							$expiry_date_font_color        = ( isset( $expiry_date_attr['textColor'] ) && ! empty( $expiry_date_attr['textColor'] ) ) ? $expiry_date_attr['textColor'] : '#000000';
							$expiry_date_format            = ( isset( $expiry_date_attr['dateFormat'] ) && ! empty( $expiry_date_attr['dateFormat'] ) ) ? $expiry_date_attr['dateFormat'] : get_option( 'date_format' );

							$date_str     = date_i18n( $expiry_date_format, $completeDate );
							$date_str_len = $this->pdffile->GetStringWidth( $date_str );

						}

						$this->pdffile->Ln( 25 );
						$this->pdffile->SelectColumn( 2 );

						$fontname = TCPDF_FONTS::addTTFfont( WPCW_PDF_FONTS . $text_field_family . '.ttf', 'TrueTypeUnicode', '', 96 );
						$this->pdffile->SetFont( $fontname, $text_field_font_underline, $text_field_font_family_size, '', false );
						list($r, $g, $b) = sscanf( $text_field_font_color, '#%02x%02x%02x' );
						$this->pdffile->SetTextColor( $r, $g, $b );
						if ( 'center' === $text_field_font_align ) {
							$align = 'C';
							$x     = $this->signature_X - 20;
						} elseif ( 'right' === $text_field_font_align ) {
							$align = 'R';
							$x     = $this->signature_X - 35;
						} else {
							$align = 'L';
							$x     = $this->signature_X - 5;
						}
						$this->pdffile->SetX( $x );
						$this->pdffile->Cell( 90, 7, $text_field_text, 0, 1, $align, 0 );

						if ( 'center' === $cumulative_grade_font_align ) {
							$align = 'C';
							$x     = $this->signature_X - 20;
						} elseif ( 'right' === $cumulative_grade_font_align ) {
							$align = 'R';
							$x     = $this->signature_X - 35;
						} else {
							$align = 'L';
							$x     = $this->signature_X - 5;
						}
						$this->pdffile->SetX( $x );
						if ( $cumulative_grade_hide_show ) {
							$fontname = TCPDF_FONTS::addTTFfont( WPCW_PDF_FONTS . $cumulative_grade_family . '.ttf', 'TrueTypeUnicode', '', 96 );
							$this->pdffile->SetFont( $fontname, $cumulative_grade_font_underline, $cumulative_grade_font_family_size, '', false );
							list($r, $g, $b) = sscanf( $cumulative_grade_font_color, '#%02x%02x%02x' );
							$this->pdffile->SetTextColor( $r, $g, $b );
							$this->pdffile->Cell( 90, 7, mb_strtoupper( $cumulative_grade_text ), 0, 1, $align, 0 );
						} else {
							$this->pdffile->Cell( 90, 7, '', 0, 1, $align, 0 );
						}

						$this->pdffile->Line( $this->signature_X - 5, $this->footer_Y + 15, $this->signature_X + $this->footer_line_length - 5, $this->footer_Y + 15 );
						$this->pdffile->Ln( 4 );

						$fontname = TCPDF_FONTS::addTTFfont( WPCW_PDF_FONTS . $certificate_number_family . '.ttf', 'TrueTypeUnicode', '', 96 );
						$this->pdffile->SetFont( $fontname, $certificate_number_font_underline, $certificate_number_font_family_size, '', false );
						list($r, $g, $b) = sscanf( $certificate_number_font_color, '#%02x%02x%02x' );
						$this->pdffile->SetTextColor( $r, $g, $b );
						if ( 'center' === $certificate_number_font_align ) {
							$align = 'C';
							$x     = $this->signature_X - 20;
						} elseif ( 'right' === $certificate_number_font_align ) {
							$align = 'R';
							$x     = $this->signature_X - 32;
						} else {
							$align = 'L';
							$x     = $this->signature_X - 5;
						}
						$this->pdffile->SetX( $x );
						// $this->pdffile->Cell( 90, 5, mb_strtoupper( $certificate_number_attr_text ), 0, 1, $align, 0 );
						if ( $certificate_number_hide_show ) {
							$this->pdffile->Cell( 90, 5, mb_strtoupper( $certificate_number_attr_text ), 0, 1, $align, 0 );
						}

						$fontname = TCPDF_FONTS::addTTFfont( WPCW_PDF_FONTS . $expiry_date_family . '.ttf', 'TrueTypeUnicode', '', 96 );
						$this->pdffile->SetFont( $fontname, $expiry_date_font_underline, $expiry_date_font_family_size, '', false );
						list($r, $g, $b) = sscanf( $expiry_date_font_color, '#%02x%02x%02x' );
						$this->pdffile->SetTextColor( $r, $g, $b );
						if ( 'center' === $expiry_date_font_align ) {
							$align = 'C';
							$x     = $this->signature_X - 20;
						} elseif ( 'right' === $expiry_date_font_align ) {
							$align = 'R';
							$x     = $this->signature_X - 32;
						} else {
							$align = 'L';
							$x     = $this->signature_X - 5;
						}
						$this->pdffile->SetX( $x );
						$this->pdffile->Cell( 90, 5, mb_strtoupper( $date_str ), 0, 1, $align, 0 );
					}
				} elseif ( 'wpcw/pre-template-2' === $block_name ) {

					/**
					 *  Block 1 styles
					 */
					$block1                   = $block_array[0]['attrs'];
					$block1_text              = ( isset( $block1['textField'] ) && ! empty( $block1['textField'] ) ) ? $block1['textField'] : '';
					$block1_family            = ( isset( $block1['fontFamily'] ) && ! empty( $block1['fontFamily'] ) ) ? $block1['fontFamily'] : 'Lato-Black';
					$block1_font_family_style = ( isset( $block1['fontWeight'] ) && ! empty( $block1['fontWeight'] ) ) ? 'B' : '';
					$block1_font_underline    = ( isset( $block1['textUnderline'] ) && ! empty( $block1['textUnderline'] ) ) ? 'U' : '';
					$block1_font_align        = ( isset( $block1['textAlign'] ) && ! empty( $block1['textAlign'] ) ) ? $block1['textAlign'] : 'left';
					$block1_font_family_size  = ( isset( $block1['fontSize'] ) && ! empty( $block1['fontSize'] ) ) ? $block1['fontSize'] : 16;
					$block1_font_color        = ( isset( $block1['textColor'] ) && ! empty( $block1['textColor'] ) ) ? $block1['textColor'] : '#000000';
					list($r, $g, $b)          = sscanf( $block1_font_color, '#%02x%02x%02x' );

					$fontname = TCPDF_FONTS::addTTFfont( WPCW_PDF_FONTS . $block1_family . '.ttf', 'TrueTypeUnicode', '', 96 );
					$this->pdffile->SetFont( $fontname, $block1_font_underline, $block1_font_family_size, '', false );
					$this->pdffile->SetTextColor( $r, $g, $b );
					if ( 'center' === $block1_font_align ) {
						$align = 'C';
					} elseif ( 'right' === $block1_font_align ) {
						$align = 'R';
					} else {
						$align = 'L';
					}
					$this->pdffile->SetXY( $topLinex, $topLineY );
					$this->pdffile->Cell( 210, 0, $block1_text, false, false, $align );

					/**
					 *  Block 2 styles
					 */
					$block2                   = $block_array[1]['attrs'];
					$block2_text              = ( ! empty( $course_name ) ) ? $course_name : __( 'COURSE TITLE', 'wp-courseware' );
					$block2_family            = ( isset( $block2['fontFamily'] ) && ! empty( $block2['fontFamily'] ) ) ? $block2['fontFamily'] : 'Lato-Black';
					$block2_font_family_style = ( isset( $block2['fontWeight'] ) && ! empty( $block2['fontWeight'] ) ) ? 'B' : '';
					$block2_font_underline    = ( isset( $block2['textUnderline'] ) && ! empty( $block2['textUnderline'] ) ) ? 'U' : '';
					$block2_font_align        = ( isset( $block2['textAlign'] ) && ! empty( $block2['textAlign'] ) ) ? $block2['textAlign'] : 'left';
					$block2_font_family_size  = ( isset( $block2['fontSize'] ) && ! empty( $block2['fontSize'] ) ) ? $block2['fontSize'] : 16;
					$block2_font_color        = ( isset( $block2['textColor'] ) && ! empty( $block2['textColor'] ) ) ? $block2['textColor'] : '#000000';
					list($r, $g, $b)          = sscanf( $block2_font_color, '#%02x%02x%02x' );

					$fontname = TCPDF_FONTS::addTTFfont( WPCW_PDF_FONTS . $block2_family . '.ttf', 'TrueTypeUnicode', '', 96 );
					$this->pdffile->SetFont( $fontname, $block2_font_underline, $block2_font_family_size, '', false );
					$this->pdffile->SetTextColor( $r, $g, $b );
					if ( 'center' === $block2_font_align ) {
						$align = 'C';
					} elseif ( 'right' === $block2_font_align ) {
						$align = 'R';
					} else {
						$align = 'L';
					}
					$this->pdffile->SetXY( $topLinex, $topLineY + 12 );
					$this->pdffile->Cell( 210, 0, mb_strtoupper( $block2_text ), false, false, $align );
					/**
					 *  Block 3 styles
					 */
					$block3                   = $block_array[2]['attrs'];
					$block3_text              = ( isset( $block3['textField'] ) && ! empty( $block3['textField'] ) ) ? $block3['textField'] : 'COURSE TITLE';
					$block3_family            = ( isset( $block3['fontFamily'] ) && ! empty( $block3['fontFamily'] ) ) ? $block3['fontFamily'] : 'Lato-Black';
					$block3_font_family_style = ( isset( $block3['fontWeight'] ) && ! empty( $block3['fontWeight'] ) ) ? 'B' : '';
					$block3_font_underline    = ( isset( $block3['textUnderline'] ) && ! empty( $block3['textUnderline'] ) ) ? 'U' : '';
					$block3_font_align        = ( isset( $block3['textAlign'] ) && ! empty( $block3['textAlign'] ) ) ? $block3['textAlign'] : 'left';
					$block3_font_family_size  = ( isset( $block3['fontSize'] ) && ! empty( $block3['fontSize'] ) ) ? $block3['fontSize'] : 16;
					$block3_font_color        = ( isset( $block3['textColor'] ) && ! empty( $block3['textColor'] ) ) ? $block3['textColor'] : '#000000';
					list($r, $g, $b)          = sscanf( $block3_font_color, '#%02x%02x%02x' );

					$fontname = TCPDF_FONTS::addTTFfont( WPCW_PDF_FONTS . $block3_family . '.ttf', 'TrueTypeUnicode', '', 96 );
					$this->pdffile->SetFont( $fontname, $block3_font_underline, $block3_font_family_size, '', false );
					$this->pdffile->SetTextColor( $r, $g, $b );
					if ( 'center' === $block3_font_align ) {
						$align = 'C';
					} elseif ( 'right' === $block3_font_align ) {
						$align = 'R';
					} else {
						$align = 'L';
					}
					$this->pdffile->SetXY( $topLinex, $topLineY + 30 );
					$this->pdffile->Cell( 210, 0, $block3_text, false, false, $align );

					/**
					 *  Block 4 styles
					 */
					$block4                   = $block_array[3]['attrs'];
					$block4_text              = ( ! empty( $student ) ) ? $student : 'Student Name';
					$block4_family            = ( isset( $block4['fontFamily'] ) && ! empty( $block4['fontFamily'] ) ) ? $block4['fontFamily'] : 'Lato-Black';
					$block4_font_family_file  = ( isset( $block4['file'] ) && ! empty( $block4['file'] ) ) ? $block4['file'] : '';
					$block4_font_family_style = ( isset( $block4['fontWeight'] ) && ! empty( $block4['fontWeight'] ) ) ? 'B' : '';
					$block4_font_underline    = ( isset( $block4['textUnderline'] ) && ! empty( $block4['textUnderline'] ) ) ? 'U' : '';
					$block4_font_align        = ( isset( $block4['textAlign'] ) && ! empty( $block4['textAlign'] ) ) ? $block4['textAlign'] : 'left';
					$block4_font_family_size  = ( isset( $block4['fontSize'] ) && ! empty( $block4['fontSize'] ) ) ? $block4['fontSize'] : 16;
					$block4_font_color        = ( isset( $block4['textColor'] ) && ! empty( $block4['textColor'] ) ) ? $block4['textColor'] : '#000000';
					list($r, $g, $b)          = sscanf( $block4_font_color, '#%02x%02x%02x' );

					$fontname = TCPDF_FONTS::addTTFfont( WPCW_PDF_FONTS . $block4_family . '.ttf', 'TrueTypeUnicode', '', 96 );
					$this->pdffile->SetFont( $fontname, $block4_font_underline, $block4_font_family_size, '', false );
					$this->pdffile->SetTextColor( $r, $g, $b );
					if ( 'center' === $block4_font_align ) {
						$align = 'C';
					} elseif ( 'right' === $block4_font_align ) {
						$align = 'R';
					} else {
						$align = 'L';
					}
					$this->pdffile->SetXY( $topLinex, $topLineY + 39 );
					$this->pdffile->Cell( 210, 0, mb_strtoupper( $block4_text ), false, false, $align );

					$this->footer_line_length = 60;
					$this->footer_Y           = 120;
					$date_X                   = 40;
					$this->signature_X        = $this->size_width - 95;

					$y       = 120;
					$columns = array(
						array(
							'w' => 90,
							's' => 2,
							'y' => $y,
						),
						array(
							'w' => 90,
							's' => 1,
							'y' => $y,
						),
						array(
							'w' => 80,
							's' => 1,
							'y' => $y,
						),
					);
					$this->pdffile->SetColumnsArray( $columns );

					/**
					 *  Block 5: Signature styles
					 */
					$block5 = $block_array[4]['attrs'];
					if ( empty( $block5 ) ) {
						if ( ! empty( $block_array[4]['innerBlocks'] ) ) {
							// Signature Image.
							$signature_image = isset( $block_array[4]['innerBlocks'][0]['attrs']['selectedImage'] ) ? $block_array[4]['innerBlocks'][0]['attrs']['selectedImage'] : WPCW_PATH . 'assets/img/certificates/signature.png';

							// Instructor Name.
							$instructor_attr              = $block_array[4]['innerBlocks'][2]['attrs'];
							$instructor_text              = ( ! empty( $instructor_display_name ) ) ? $instructor_display_name : __( 'INSTRUCTOR NAME', 'wp-courseware' );
							$instructor_family            = ( isset( $instructor_attr['fontFamily'] ) && ! empty( $instructor_attr['fontFamily'] ) ) ? $instructor_attr['fontFamily'] : 'Lato-Black';
							$instructor_font_family_style = ( isset( $instructor_attr['fontWeight'] ) && ! empty( $instructor_attr['fontWeight'] ) ) ? 'B' : '';
							$instructor_font_underline    = ( isset( $instructor_attr['textUnderline'] ) && ! empty( $instructor_attr['textUnderline'] ) ) ? 'U' : '';
							$instructor_font_align        = ( isset( $instructor_attr['textAlign'] ) && ! empty( $instructor_attr['textAlign'] ) ) ? $instructor_attr['textAlign'] : 'left';
							$instructor_font_family_size  = ( isset( $instructor_attr['fontSize'] ) && ! empty( $instructor_attr['fontSize'] ) ) ? $instructor_attr['fontSize'] : 16;
							$instructor_font_color        = ( isset( $instructor_attr['textColor'] ) && ! empty( $instructor_attr['textColor'] ) ) ? $instructor_attr['textColor'] : '#000000';
							$instructor_text_title        = ( isset( $instructor_attr['instructorTitle'] ) && ! empty( $instructor_attr['instructorTitle'] ) ) ? $instructor_attr['instructorTitle'] : '';
							list($r, $g, $b)              = sscanf( $instructor_font_color, '#%02x%02x%02x' );

						}

						$this->pdffile->Ln( 30 );
						$this->pdffile->SelectColumn( 0 );
						$this->pdffile->SetFillColor( 255, 0, 0 );
						$this->pdffile->Ln( 4 );
						if ( 'center' === $instructor_font_align ) {
							$align = 'C';
						} elseif ( 'right' === $instructor_font_align ) {
							$align = 'R';
						} else {
							$align = 'L';
						}
						$this->pdffile->Cell( 120, 20, '', 0, 1, $align, 0 );
						$signWidth = self::px2mm( WPCW_CERTIFICATE_SIGNATURE_WIDTH_PX );
						$sigHeight = self::px2mm( WPCW_CERTIFICATE_SIGNATURE_HEIGHT_PX );

						// Make link relative.
						$signatureImg = $this->pdf_link_path( $signature_image );
						$this->pdffile->Image( $signatureImg, 50, $this->footer_Y, 40, 15 );

						$this->pdffile->Line( $date_X, $this->footer_Y + 15, $date_X + $this->footer_line_length, $this->footer_Y + 15 );

						$fontname = TCPDF_FONTS::addTTFfont( WPCW_PDF_FONTS . $instructor_family . '.ttf', 'TrueTypeUnicode', '', 96 );
						$this->pdffile->SetFont( $fontname, $instructor_font_underline, $instructor_font_family_size, '', false );
						$this->pdffile->SetTextColor( $r, $g, $b );
						if ( 'center' === $instructor_font_align ) {
							$align = 'C';
							$x     = 18;
						} elseif ( 'right' === $instructor_font_align ) {
							$align = 'R';
							$x     = 0;
						} else {
							$align = 'L';
							$x     = 40;
						}
						$this->pdffile->SetX( $x );
						$this->pdffile->Cell( 100, 5, mb_strtoupper( $instructor_text ), 0, 1, $align, 0 );
						$this->pdffile->SetX( $x );
						$this->pdffile->Cell( 100, 5, mb_strtoupper( $instructor_text_title ), 0, 1, $align, 0 );
					}

					/**
					 *  Block 6: Certificat details styles
					 */
					$block7 = $block_array[5]['attrs'];
					if ( empty( $block7 ) ) {
						if ( ! empty( $block_array[5]['innerBlocks'] ) ) {
							// Text Entry field.
							$text_field_attr              = $block_array[5]['innerBlocks'][0]['attrs'];
							$text_field_text              = ( isset( $text_field_attr['textField'] ) && ! empty( $text_field_attr['textField'] ) ) ? $text_field_attr['textField'] : 'TEXT ENTRY';
							$text_field_family            = ( isset( $text_field_attr['fontFamily'] ) && ! empty( $text_field_attr['fontFamily'] ) ) ? $text_field_attr['fontFamily'] : 'Lato-Black';
							$text_field_font_family_style = ( isset( $text_field_attr['fontWeight'] ) && ! empty( $text_field_attr['fontWeight'] ) ) ? 'B' : '';
							$text_field_font_underline    = ( isset( $text_field_attr['textUnderline'] ) && ! empty( $text_field_attr['textUnderline'] ) ) ? 'U' : '';
							$text_field_font_align        = ( isset( $text_field_attr['textAlign'] ) && ! empty( $text_field_attr['textAlign'] ) ) ? $text_field_attr['textAlign'] : 'left';
							$text_field_font_family_size  = ( isset( $text_field_attr['fontSize'] ) && ! empty( $text_field_attr['fontSize'] ) ) ? $text_field_attr['fontSize'] : 16;
							$text_field_font_color        = ( isset( $text_field_attr['textColor'] ) && ! empty( $text_field_attr['textColor'] ) ) ? $text_field_attr['textColor'] : '#000000';
							$text_filed_text_align        = ( isset( $text_field_attr['textAlign'] ) && ! empty( $text_field_attr['textAlign'] ) ) ? $text_field_attr['textAlign'] : '';

							// Cumulative Grade.
							$cumulative_grade_attr              = $block_array[5]['innerBlocks'][1]['attrs'];
							$cumulative_grade_text              = ( ! empty( $cumulativeGrade ) ) ? $cumulativeGrade : 'XX%';
							$cumulative_grade_family            = ( isset( $cumulative_grade_attr['fontFamily'] ) && ! empty( $cumulative_grade_attr['fontFamily'] ) ) ? $cumulative_grade_attr['fontFamily'] : 'Lato-Black';
							$cumulative_grade_font_family_style = ( isset( $cumulative_grade_attr['fontWeight'] ) && ! empty( $cumulative_grade_attr['fontWeight'] ) ) ? 'B' : '';
							$cumulative_grade_font_underline    = ( isset( $cumulative_grade_attr['textUnderline'] ) && ! empty( $cumulative_grade_attr['textUnderline'] ) ) ? 'U' : '';
							$cumulative_grade_font_align        = ( isset( $cumulative_grade_attr['textAlign'] ) && ! empty( $cumulative_grade_attr['textAlign'] ) ) ? $cumulative_grade_attr['textAlign'] : 'left';
							$cumulative_grade_font_family_size  = ( isset( $cumulative_grade_attr['fontSize'] ) && ! empty( $cumulative_grade_attr['fontSize'] ) ) ? $cumulative_grade_attr['fontSize'] : 16;
							$cumulative_grade_font_color        = ( isset( $cumulative_grade_attr['textColor'] ) && ! empty( $cumulative_grade_attr['textColor'] ) ) ? $cumulative_grade_attr['textColor'] : '#000000';
							$cumulative_grade_hide_show         = ( isset( $cumulative_grade_attr['cumulativeGrade'] ) && ! empty( $cumulative_grade_attr['cumulativeGrade'] ) ) ? $cumulative_grade_attr['cumulativeGrade'] : false;

							// Certificate Number.
							$certificate_number_attr              = $block_array[5]['innerBlocks'][3]['attrs'];
							$certificate_number_family            = ( isset( $certificate_number_attr['fontFamily'] ) && ! empty( $certificate_number_attr['fontFamily'] ) ) ? $certificate_number_attr['fontFamily'] : 'Lato-Black';
							$certificate_number_font_family_style = ( isset( $certificate_number_attr['fontWeight'] ) && ! empty( $certificate_number_attr['fontWeight'] ) ) ? 'B' : '';
							$certificate_number_font_underline    = ( isset( $certificate_number_attr['textUnderline'] ) && ! empty( $certificate_number_attr['textUnderline'] ) ) ? 'U' : '';
							$certificate_number_font_align        = ( isset( $certificate_number_attr['textAlign'] ) && ! empty( $certificate_number_attr['textAlign'] ) ) ? $certificate_number_attr['textAlign'] : 'left';
							$certificate_number_font_family_size  = ( isset( $certificate_number_attr['fontSize'] ) && ! empty( $certificate_number_attr['fontSize'] ) ) ? $certificate_number_attr['fontSize'] : 16;
							$certificate_number_font_color        = ( isset( $certificate_number_attr['textColor'] ) && ! empty( $certificate_number_attr['textColor'] ) ) ? $certificate_number_attr['textColor'] : '#000000';
							$certificate_number_prefix            = ( isset( $certificate_number_attr['numberPrefix'] ) && ! empty( $certificate_number_attr['numberPrefix'] ) ) ? $certificate_number_attr['numberPrefix'] : '';
							$certificate_number_length            = ( isset( $certificate_number_attr['numberLength'] ) && ! empty( $certificate_number_attr['numberLength'] ) ) ? $certificate_number_attr['numberLength'] : 3;
							$certificate_number_hide_show         = ( isset( $certificate_number_attr['certificateNumber'] ) && ! empty( $certificate_number_attr['certificateNumber'] ) ) ? $certificate_number_attr['certificateNumber'] : false;

							if ( $certificate_number_hide_show && empty( $certificate_number ) && ! empty( $certificate_access_key ) ) {
								$genarated_number   = self::generateNDigitRandomNumber( $certificate_number_length );
								$certificate_number = $certificate_number_prefix . $genarated_number;

								// Update Generate Number.
								global $wpdb, $wpcwdb;
								$cer_number_updated = $wpdb->query( $wpdb->prepare( "UPDATE $wpcwdb->certificates SET cert_number = %s WHERE cert_access_key = %s", $certificate_number, $certificate_access_key ) );
							}
							$certificate_number_attr_text = ( ! empty( $certificate_number ) ) ? $certificate_number : 'CERTIFICATE NUMBER';

							// Expiry Date
							$expiry_date_attr              = $block_array[5]['innerBlocks'][4]['attrs'];
							$expiry_date_attr_text         = ( isset( $expiry_date_attr['textField'] ) && ! empty( $expiry_date_attr['textField'] ) ) ? $expiry_date_attr['textField'] : '27 Nov 2020';
							$expiry_date_family            = ( isset( $expiry_date_attr['fontFamily'] ) && ! empty( $expiry_date_attr['fontFamily'] ) ) ? $expiry_date_attr['fontFamily'] : 'Lato-Black';
							$expiry_date_font_family_style = ( isset( $expiry_date_attr['fontWeight'] ) && ! empty( $expiry_date_attr['fontWeight'] ) ) ? 'B' : '';
							$expiry_date_font_underline    = ( isset( $expiry_date_attr['textUnderline'] ) && ! empty( $expiry_date_attr['textUnderline'] ) ) ? 'U' : '';
							$expiry_date_font_align        = ( isset( $expiry_date_attr['textAlign'] ) && ! empty( $expiry_date_attr['textAlign'] ) ) ? $expiry_date_attr['textAlign'] : 'left';
							$expiry_date_font_family_size  = ( isset( $expiry_date_attr['fontSize'] ) && ! empty( $expiry_date_attr['fontSize'] ) ) ? $expiry_date_attr['fontSize'] : 16;
							$expiry_date_font_color        = ( isset( $expiry_date_attr['textColor'] ) && ! empty( $expiry_date_attr['textColor'] ) ) ? $expiry_date_attr['textColor'] : '#000000';
							$expiry_date_format            = ( isset( $expiry_date_attr['dateFormat'] ) && ! empty( $expiry_date_attr['dateFormat'] ) ) ? $expiry_date_attr['dateFormat'] : get_option( 'date_format' );

							$date_str     = date_i18n( $expiry_date_format, $completeDate );
							$date_str_len = $this->pdffile->GetStringWidth( $date_str );

						}

						$this->pdffile->Ln( 25 );
						$this->pdffile->SelectColumn( 1 );

						$fontname = TCPDF_FONTS::addTTFfont( WPCW_PDF_FONTS . $text_field_family . '.ttf', 'TrueTypeUnicode', '', 96 );
						$this->pdffile->SetFont( $fontname, $text_field_font_underline, $text_field_font_family_size, '', false );
						list($r, $g, $b) = sscanf( $text_field_font_color, '#%02x%02x%02x' );
						$this->pdffile->SetTextColor( $r, $g, $b );
						if ( 'center' === $text_field_font_align ) {
							$align = 'C';
							$x     = $this->size_width - $this->footer_line_length - $this->footer_Y - 15;
						} elseif ( 'right' === $text_field_font_align ) {
							$align = 'R';
							$x     = $this->size_width - $this->footer_line_length - $this->footer_Y - 30;
						} else {
							$align = 'L';
							$x     = $this->size_width - $this->footer_line_length - $this->footer_Y;
						}
						$this->pdffile->SetX( $x );
						$this->pdffile->Cell( 90, 7, $text_field_text, 0, 1, $align, 0 );
						if ( 'center' === $cumulative_grade_font_align ) {
							$align = 'C';
							$x     = $this->size_width - $this->footer_line_length - $this->footer_Y - 15;
						} elseif ( 'right' === $cumulative_grade_font_align ) {
							$align = 'R';
							$x     = $this->size_width - $this->footer_line_length - $this->footer_Y - 30;
						} else {
							$align = 'L';
							$x     = $this->size_width - $this->footer_line_length - $this->footer_Y;
						}
						$this->pdffile->SetX( $x );
						if ( $cumulative_grade_hide_show ) {
							$fontname = TCPDF_FONTS::addTTFfont( WPCW_PDF_FONTS . $cumulative_grade_family . '.ttf', 'TrueTypeUnicode', '', 96 );
							$this->pdffile->SetFont( $fontname, $cumulative_grade_font_underline, $cumulative_grade_font_family_size, '', false );
							list($r, $g, $b) = sscanf( $cumulative_grade_font_color, '#%02x%02x%02x' );
							$this->pdffile->SetTextColor( $r, $g, $b );
							$this->pdffile->Cell( 90, 7, mb_strtoupper( $cumulative_grade_text ), 0, 1, $align, 0 );
						} else {
							$this->pdffile->Cell( 90, 7, '', 0, 1, $align, 0 );
						}
						$this->pdffile->Line( $this->size_width - $this->footer_line_length - $this->footer_Y, $this->footer_Y + 15, $this->size_width - $this->footer_Y, $this->footer_Y + 15 );
						$this->pdffile->Ln( 4 );

						$fontname = TCPDF_FONTS::addTTFfont( WPCW_PDF_FONTS . $certificate_number_family . '.ttf', 'TrueTypeUnicode', '', 96 );
						$this->pdffile->SetFont( $fontname, $certificate_number_font_underline, $certificate_number_font_family_size, '', false );
						list($r, $g, $b) = sscanf( $certificate_number_font_color, '#%02x%02x%02x' );
						$this->pdffile->SetTextColor( $r, $g, $b );
						if ( 'center' === $certificate_number_font_align ) {
							$align = 'C';
							$x     = $this->size_width - $this->footer_line_length - $this->footer_Y - 15;
						} elseif ( 'right' === $certificate_number_font_align ) {
							$align = 'R';
							$x     = $this->size_width - $this->footer_line_length - $this->footer_Y - 30;
						} else {
							$align = 'L';
							$x     = $this->size_width - $this->footer_line_length - $this->footer_Y;
						}
						$this->pdffile->SetX( $x );
						if ( $certificate_number_hide_show ) {
							$this->pdffile->Cell( 90, 5, mb_strtoupper( $certificate_number_attr_text ), 0, 1, $align, 0 );
						}

						// $this->pdffile->Cell( 90, 5, mb_strtoupper( $certificate_number_attr_text ), 0, 1, $align, 0 );

						$fontname = TCPDF_FONTS::addTTFfont( WPCW_PDF_FONTS . $expiry_date_family . '.ttf', 'TrueTypeUnicode', '', 96 );
						$this->pdffile->SetFont( $fontname, $expiry_date_font_underline, $expiry_date_font_family_size, '', false );
						list($r, $g, $b) = sscanf( $expiry_date_font_color, '#%02x%02x%02x' );
						$this->pdffile->SetTextColor( $r, $g, $b );
						if ( 'center' === $expiry_date_font_align ) {
							$align = 'C';
							$x     = $this->size_width - $this->footer_line_length - $this->footer_Y - 15;
						} elseif ( 'right' === $expiry_date_font_align ) {
							$align = 'R';
							$x     = $this->size_width - $this->footer_line_length - $this->footer_Y - 30;
						} else {
							$align = 'L';
							$x     = $this->size_width - $this->footer_line_length - $this->footer_Y;
						}
						$this->pdffile->SetX( $x );
						$this->pdffile->Cell( 90, 5, mb_strtoupper( $date_str ), 0, 1, $align, 0 );
					}

					/**
					 *  Block 7: Image styles
					 */
					$block6     = $block_array[6]['attrs'];
					$logo_image = $block6['selectedImage'] ? $block6['selectedImage'] : WPCW_PATH . 'assets/img/certificates/default-img-1.jpg';
					$this->pdffile->Ln( 25 );
					$this->pdffile->SelectColumn( 2 );
					$logoWidth  = self::px2mm( WPCW_CERTIFICATE_LOGO_WIDTH_PX );
					$logoHeight = self::px2mm( WPCW_CERTIFICATE_LOGO_HEIGHT_PX );
					$logoImg    = $this->pdf_link_path( $logo_image );
					$this->pdffile->Image( $logoImg, $this->size_width - 95, $this->footer_Y - 5, $logoWidth, $logoHeight ); // Only force width

				} elseif ( 'wpcw/raw-template' === $block_name ) {
					foreach ( $block_array as $key => $value ) {
							$block_name   = $value['blockName'];
							$inner_blocks = $value['innerBlocks'];
							$user_details = array(
								'student_name'       => $student,
								'course_name'        => $course_name,
								'instructor_name'    => $instructor_display_name,
								'certificate_number' => $certificate_number,
								'completeDate'       => $completeDate,
								'cumulativeGrade'    => $cumulativeGrade,
							);
							if ( ! empty( $inner_blocks ) ) {
								$inner_block_name   = $inner_blocks[0]['blockName'];
								$block_attr         = $inner_blocks[0]['attrs'];
								$block_inner_blocks = $inner_blocks[0]['innerBlocks'];
								$this->generateDynamicPDF( 'block_' . $key, $inner_block_name, $block_attr, $block_inner_blocks, $user_details );
							}
					}
				}

				if ( ob_get_contents() ) {
					ob_end_clean();
				}

					// Change output based on what's been specified as a parameter.
				if ( 'browser' == $showMode ) {
					$this->pdffile->Output( 'certificate.pdf', 'I' );
				} else {
					$this->pdffile->Output( 'certificate.pdf', 'D' );
				}
			}

		}

		/**
		 * Convert a measurement from pixels to millimetres at 72dpi.
		 *
		 * @param Integer $px Measurement in pixels
		 *
		 * @return Float Millimetres
		 */
		public static function px2mm( $px ) {
			return $px * 25.4 / 72;
		}

		/**
		 * Generate Random Number.
		 *
		 * @param Integer $length Length of the Digit
		 *
		 * @return Integer Number
		 */
		public function generateNDigitRandomNumber( $length ) {
			return mt_rand( pow( 10, ( $length - 1 ) ), pow( 10, $length ) - 1 );
		}
		/**
		 * Convert a measurement from millimetres into pixels at 72dpi.
		 *
		 * @param Integer $mm Measurement in mm.
		 *
		 * @return Float Pixels
		 */
		public static function mm2px( $mm ) {
			return ( $mm * 72 ) / 25.4;
		}

		/**
		 * Renders the logo provided by the user.
		 */
		public function render_handleLogo() {
			$logoShow = WPCW_arrays_getValue( $this->settingsList, 'cert_logo_enabled' );
			$logoImg  = WPCW_arrays_getValue( $this->settingsList, 'cert_logo_url' );

			// No logo to work with, abort.
			if ( 'cert_logo' != $logoShow || ! $logoImg ) {
				return;
			}

			// Image is fetched using URL, and resized to match the space.
			$logoWidth  = self::px2mm( WPCW_CERTIFICATE_LOGO_WIDTH_PX );
			$logoHeight = self::px2mm( WPCW_CERTIFICATE_LOGO_HEIGHT_PX );

			$logoImg = $this->pdf_link_path( $logoImg );

			$this->pdffile->Image( $logoImg, $this->getLeftOfCentre( $logoWidth ), 134, $logoWidth ); // Only force width
		}

		/**
		 * Renders the signature area for the certificate.
		 */
		public function render_handleSignature() {
			// Have we got a text or image signature?
			$signature     = '';
			$signatureType = WPCW_arrays_getValue( $this->settingsList, 'cert_signature_type', 'text' );
			$signatureImg  = WPCW_arrays_getValue( $this->settingsList, 'cert_sig_image_url' );

			// Get the text for the signature
			if ( 'text' == $signatureType ) {
				// Use codepage translation of signature text
				$encoding = WPCW_arrays_getValue( $this->settingsList, 'certificate_encoding', 'ISO-8859-1' );
				// $signature = iconv('UTF-8', $encoding.'//TRANSLIT//IGNORE', WPCW_arrays_getValue($this->settingsList, 'cert_sig_text'));
				$signature = WPCW_arrays_getValue( $this->settingsList, 'cert_sig_text' );
				// Nothing to do, signature is empty
				if ( ! $signature ) {
					return;
				}

				// Create the signature
				$signature_len = $this->pdffile->GetStringWidth( $signature );
				$this->pdffile->SetXY( $this->signature_X + ( ( $this->footer_line_length - $signature_len ) / 2 ), $this->footer_Y );
				$this->pdffile->Cell( 0, 0, $signature, false, false );
			} // Image - see if we have anything to use.
			else {
				// No image to work with
				if ( ! $signatureImg ) {
					return;
				}

				// Image is fetched using URL, and resized to match the space. We're using
				// an image that's twice the size to get it to scale nicely.
				$signWidth = self::px2mm( WPCW_CERTIFICATE_SIGNATURE_WIDTH_PX );
				$sigHeight = self::px2mm( WPCW_CERTIFICATE_SIGNATURE_HEIGHT_PX );

				// Make link relative.
				$signatureImg = $this->pdf_link_path( $signatureImg );

				// Only force width
				$this->pdffile->Image( $signatureImg, $this->signature_X + ( $this->footer_line_length - $signWidth ) / 2, $this->footer_Y - $sigHeight + 6, $signWidth );
			}
		}

		/**
		 * PDF Link Path.
		 *
		 * @param $link
		 *
		 * @return string
		 */
		public function pdf_link_path( $link ) {
			return wpcw_make_url_relative( $link, true );
		}
	}
}

<?php
/**
 * WP Courseware Abstract Page.
 *
 * All admin pages should extend this class.
 *
 * @package WPCW
 * @subpackage Admin\Pages
 * @since 4.3.0
 */
namespace WPCW\Admin\Pages;

use WPCW\Admin\Tables\Table;
use WPCW\Common\Settings_Api;
use WPCW\Core\Admin;

// Exit if accessed directly
defined( 'ABSPATH' ) || exit;

/**
 * Abstract Class Page.
 *
 * @since 4.1.0
 */
abstract class Page extends Settings_Api {

	/**
	 * @var Admin The admin object.
	 * @since 4.1.0
	 */
	protected $admin;

	/**
	 * @var string The page hook.
	 * @since 4.1.0
	 */
	protected $hook;

	/**
	 * @var Table The list table.
	 * @since 4.1.0
	 */
	protected $table;

	/**
	 * @var array The tabs array.
	 * @since 4.3.0
	 */
	protected $tabs = array();

	/**
	 * @var array The current tab.
	 * @since 4.3.0
	 */
	protected $tab = array();

	/**
	 * @var array The sections array.
	 * @since 4.3.0
	 */
	protected $sections = array();

	/**
	 * @var array The current section.
	 * @since 4.3.0
	 */
	protected $section = array();

	/**
	 * @var bool Disable current menu.
	 * @since 4.4.0
	 */
	protected $disable = false;

	/**
	 * Page Registration
	 *
	 * @since 4.1.0
	 *
	 * @return Page The admin page.
	 */
	public static function register() {
		$admin_page = new static();
		$admin_page->setup();
		$admin_page->hooks();

		return $admin_page;
	}

	/**
	 * Page Setup.
	 *
	 * @since 4.1.0
	 */
	protected function setup() { /* Can be overridden in sub class. */ }

	/**
	 * Page Hooks.
	 *
	 * @since 4.3.0
	 */
	protected function hooks() {
		add_action( 'wpcw_admin_menu', array( $this, 'menu' ), 0 );
		add_filter( 'set-screen-option', array( $this, 'set_screen_options' ), 10, 3 );
	}

	/**
	 * Register Menu.
	 *
	 * @since 4.3.0
	 *
	 * @param Admin The admin object.
	 */
	public function menu( Admin $admin ) {
		$this->admin = $admin;

		// Check if disabled.
		if ( $this->disable || ! $this->get_page_title() || ! $this->get_menu_title() || ! $this->get_capability() || ! $this->get_slug() ) {
			return;
		}

		// Register menu.
		$this->hook = add_submenu_page(
			$this->admin->get_slug(),
			$this->get_page_title(),
			$this->get_menu_title(),
			$this->get_capability(),
			$this->get_slug(),
			$this->get_callback()
		);

		add_action( "load-{$this->hook}", array( $this, 'init' ), 0 );
		add_action( "load-{$this->hook}", array( $this, 'tabs' ), 0 );
		add_action( "load-{$this->hook}", array( $this, 'sections' ), 1 );
		add_action( "load-{$this->hook}", array( $this, 'process' ), 2 );
		add_action( "load-{$this->hook}", array( $this, 'screen' ), 3 );
		add_action( "load-{$this->hook}", array( $this, 'actions' ), 4 );
		add_action( "load-{$this->hook}", array( $this, 'load' ), 5 );
		add_action( "load-{$this->hook}", array( $this, 'loaded' ), 6 );

		add_action( 'admin_head', array( $this, 'hide_menu' ) );
		add_action( 'admin_head', array( $this, 'menu_settings' ) );
	}

	/**
	 * Page Menu Title.
	 *
	 * @since 4.4.0
	 *
	 * @return string
	 */
	public function get_menu_title() { return ''; }

	/**
	 * Page Title.
	 *
	 * @since 4.4.0
	 *
	 * @return string
	 */
	public function get_page_title() { return ''; }

	/**
	 * Page Capability.
	 *
	 * @since 4.4.0
	 *
	 * @return string
	 */
	public function get_capability() {
	    return 'view_wpcw_courses';
	}

	/**
	 * Page Slug.
	 *
	 * @since 4.4.0
	 *
	 * @return string
	 */
	public function get_slug() { return ''; }

	/**
	 * Get Page Id.
	 *
	 * @since 4.4.0
	 *
	 * @return string
	 */
	public function get_id() {
		return $this->get_slug();
	}

	/**
	 * Page Init.
	 *
	 * @since 4.3.0
	 */
	public function init() { /* Override when needed in child theme */ }

	/**
	 * Page Tabs.
	 *
	 * @since 4.3.0
	 */
	public function tabs() {
		$this->tabs = $this->get_tabs();
		$this->tab  = $this->get_current_tab();
	}

	/**
	 * Page Tab Sections.
	 *
	 * @since 4.3.0
	 */
	public function sections() {
		if ( ! $this->has_tab_sections() ) {
			return;
		}

		$this->sections = $this->get_tab_sections();
		$this->section  = $this->get_current_tab_section();
	}

	/**
	 * Page Process.
	 *
	 * This allows other plugins to process requests
	 * very early in the process before the menu is created.
	 *
	 * @since 4.3.0
	 */
	public function process() { /* Can be overridden in sub class. */ }

	/**
	 * Page Screen.
	 *
	 * Use this method to register
	 * admin page screen options.
	 *
	 * @since 4.1.0
	 */
	public function screen() {
		$screen_options = $this->get_screen_options();

		if ( empty( $screen_options ) ) {
			return;
		}

		foreach ( $screen_options as $option => $args ) {
			add_screen_option( $option, $args );
		}
	}

	/**
	 * Page Actions.
	 *
	 * Use this method to register/process
	 * admin page actions.
	 *
	 * @since 4.1.0
	 */
	public function actions() { /* Can be overridden in sub class. */ }

	/**
	 * Page Load.
	 *
	 * Use this method to load the
	 * admin page.
	 *
	 * @since 4.1.0
	 */
	public function load() { /* Can be overridden in sub class. */ }

	/**
	 * Page Loaded.
	 *
	 * Add a couple actions to allow third
	 * party developers to load things after
	 * this page has been loaded.
	 *
	 * @since 4.1.0
	 */
	public function loaded() {
		add_filter( 'admin_head', array( $this, 'highlight_submenu' ) );
		add_action( 'wpcw_enqueue_scripts', array( $this, 'scripts' ) );

		do_action( 'wpcw_admin_page_loaded', $this );
		do_action( "wpcw_admin_page_{$this->get_slug()}_loaded", $this );
	}

	/**
	 * Page Scripts.
	 *
	 * @since 4.3.0
	 */
	public function scripts() { /* Can be overridden in a sub class */ }

	/**
	 * Page Views.
	 *
	 * Later on, alot of javascript will be used.
	 * This function will be use to register JS views
	 * for javascript.
	 *
	 * @since 4.1.0
	 */
	public function views() {
		$views      = array();
		$page_views = $this->get_views();
		$tab_fields = $this->get_tab_fields();

		if ( ! empty( $tab_fields ) ) {
			$common_views = $this->get_common_views();

			foreach ( $tab_fields as $field ) {
				if ( isset( $field['component'] ) && $field['component'] && ! empty( $field['views'] ) ) {
					foreach ( $field['views'] as $view ) {
						$views[ $view ] = $view;
					}
				}
			}

			if ( ! empty( $common_views ) ) {
				foreach ( $common_views as $view ) {
					$views[ $view ] = $view;
				}
			}
		}

		if ( ! empty( $page_views ) ) {
			foreach ( $page_views as $view ) {
				$views[ $view ] = $view;
			}
		}

		if ( ! empty( $views ) ) {
			foreach ( $views as $view ) {
				echo $this->get_view( $view );
			}
		}
	}

	/**
	 * Get Common Views.
	 *
	 * @since 4.3.0
	 *
	 * @return array The common views.
	 */
	public function get_common_views() {
		return apply_filters( 'wpcw_page_common_views', array(
			'common/form-table',
			'common/form-row',
			'common/form-field',
			'common/form-field-image-input',
			'common/form-field-page',
			'common/form-field-color-picker',
			'common/notices',
		) );
	}

	/**
	 * Get View.
	 *
	 * @since 4.3.0
	 *
	 * @param string $template The template name.
	 *
	 * @return string The template contents.
	 */
	public function get_view( $view ) {
		if ( ! file_exists( $view ) ) {
			$view = WPCW_ADMIN_PATH . "views/{$view}.php";
			if ( ! file_exists( $view ) ) {
				return '';
			}
		}

		ob_start();

		include $view;

		return ob_get_clean();
	}

	/**
	 * Get Views.
	 *
	 * @since 4.3.0
	 *
	 * @return array The views that need to be included.
	 */
	public function get_views() {
		return array();
	}

	/**
	 * Page - Highlight Submenu.
	 *
	 * @since 4.1.0
	 */
	public function highlight_submenu() {
		global $parent_file, $submenu_file;

		$parent_file  = $this->admin->get_slug();
		$submenu_file = $this->get_slug();
	}

	/**
	 * Page - Hide Menu.
	 *
	 * @since 4.1.0
	 */
	public function hide_menu() {
		if ( $this->is_hidden() ) {
			$this->admin->hide_menu( $this->admin->get_slug(), $this->get_slug() );
		}
	}

	/**
	 * Page Menu - Settings
	 *
	 * @since 4.1.0
	 */
	public function menu_settings() { /* Can be overridden in sub class. */ }

	/**
	 * Page - Set Screen Options.
	 *
	 * @since 4.1.0
	 *
	 * @param stirng $status
	 * @param string $option
	 * @param mixed $value
	 *
	 * @return mixed
	 */
	public function set_screen_options( $status, $option, $value ) {
		$screen_options = $this->get_screen_options();

		if ( empty( $screen_options ) ) {
			return $status;
		}

		foreach ( $screen_options as $option_key => $args ) {
			$screen_option_name = isset( $args['option'] ) ? sanitize_key( $args['option'] ) : '';

			if ( $screen_option_name === $option ) {
				return $value;
			}
		}

		return $status;
	}

	/**
	 * Page - Get Screen Options.
	 *
	 * @since 4.1.0
	 *
	 * @return array
	 */
	protected function get_screen_options() { /* Can be overridden in sub class. */ }

	/**
	 * Page Icon.
	 *
	 * @since 4.1.0
	 *
	 * @return string
	 */
	protected function get_icon() {
		return sprintf(
			'<img id="wpcw-refresh" src="%s" alt="%s" width="40" height="40" class="icon">',
			wpcw_image_file( 'wp-courseware-icon.svg' ),
			wpcw()->get_name()
		);
	}

	/**
	 * Page Callback.
	 *
	 * @since 4.1.0
	 *
	 * @return array
	 */
	protected function get_callback() {
		return array( $this, 'display_callback' );
	}

	/**
	 * Page Action Buttons.
	 *
	 * @since 4.1.0
	 *
	 * @return string
	 */
	protected function get_action_buttons() { /* Can be overridden in sub class. */ }

	/**
	 * Page - Before Display.
	 *
	 * @since 4.1.0
	 *
	 * @return string
	 */
	protected function get_before_display() { /* Can be overridden in sub class. */ }

	/**
	 * Page - After Display.
	 *
	 * @since 4.1.0
	 *
	 * @return string
	 */
	protected function get_after_display() { /* Can be overridden in sub class. */ }

	/**
	 * Page - Display Callback.
	 *
	 * @since 4.1.0
	 *
	 * @return void
	 */
	public function display_callback() {
		$this->views();
		?>
        <div id="<?php echo $this->get_slug(); ?>-admin-page" class="wrap wpcw-admin-page <?php echo $this->get_slug(); ?>-admin-page">
			<?php echo $this->get_before_display(); ?>

            <h1 class="wpcw-admin-page-title">
				<?php echo $this->get_icon(); ?>
				<?php echo $this->get_page_title(); ?>
				<?php echo $this->get_action_buttons(); ?>
            </h1>

            <div class="wpcw-admin-page-inner">
				<?php $this->display(); ?>
            </div>

			<?php echo $this->get_after_display(); ?>
        </div>
		<?php
	}

	/**
	 * Page - Display.
	 *
	 * @since 4.1.0
	 *
	 * @return mixed
	 */
	protected function display() { /* Can be overridden in sub class. */ }

	/**
	 * Page - Hook
	 *
	 * @since 4.1.0
	 *
	 * @return string The page hook.
	 */
	public function get_hook() {
		return $this->hook;
	}

	/**
	 * Page - Url.
	 *
	 * @since 4.1.0
	 *
	 * @return string The admin url.
	 */
	public function get_url() {
		return esc_url( add_query_arg( array( 'page' => $this->get_slug() ), admin_url( 'admin.php' ) ) );
	}

	/**
	 * Page Hidden?
	 *
	 * @since 4.1.0
	 *
	 * @return bool Default is false.
	 */
	public function is_hidden() {
		return false;
	}

	/**
	 * Is Current Page?
	 *
	 * Conditional function to determine if we are on the current page.
	 *
	 * @since 4.3.0
	 */
	protected function is_current_page() {
		return ( $this->get_slug() === wpcw_get_var( 'page' ) );
	}

	/**
	 * Get Tabs.
	 *
	 * @since 4.3.0
	 *
	 * @return mixed|void
	 */
	public function get_tabs() {
		return array();
	}

	/**
	 * Get Tab Sections.
	 *
	 * @since 4.3.0
	 */
	public function get_tab_sections() {
		return ! empty( $this->tab['sections'] ) ? $this->tab['sections'] : array();
	}

	/**
	 * Get Current Tab.
	 *
	 * @since 4.3.0
	 *
	 * @return array $tab The current tab.
	 */
	public function get_current_tab() {
		$tab_slug = isset( $_GET['tab'] ) ? esc_html( $_GET['tab'] ) : $this->get_default_tab();
		$tab      = ! empty ( $this->tabs[ $tab_slug ] ) ? $this->tabs[ $tab_slug ] : array();

		if ( ! empty( $tab ) ) {
			$tab['slug'] = $tab_slug;
		}

		return $tab;
	}

	/**
	 * Get Current Tab Slug.
	 *
	 * @since 4.3.0
	 *
	 * @return string
	 */
	public function get_current_tab_slug() {
		return isset( $this->tab['slug'] ) ? esc_attr( $this->tab['slug'] ) : '';
	}

	/**
	 * Get Current Tab Url.
	 *
	 * @since 4.1.0
	 *
	 * @param string $slug The page slug.
	 *
	 * @return string The tab url.
	 */
	public function get_current_tab_url() {
		return esc_url_raw( add_query_arg( array( 'tab' => $this->get_current_tab_slug() ), $this->get_url() ) );
	}

	/**
	 * Get Default Tab.
	 *
	 * @since 4.3.0
	 *
	 * @return int|string
	 */
	public function get_default_tab() {
		$default = '';

		foreach ( $this->tabs as $slug => $tab ) {
			if ( $this->is_default_tab( $tab ) ) {
				$default = $slug;
				break;
			}
		}

		if ( empty( $default ) ) {
			$tabs = $this->tabs;
			reset( $tabs );
			$default = key( $tabs );
		}

		return $default;
	}

	/**
	 * Get Current Tab Section.
	 *
	 * @since 4.3.0
	 *
	 * @return array $section The current tab section.
	 */
	public function get_current_tab_section() {
		$section_slug = isset( $_GET['section'] ) ? esc_html( $_GET['section'] ) : $this->get_default_tab_section();
		$section      = ! empty ( $this->sections[ $section_slug ] ) ? $this->sections[ $section_slug ] : array();

		if ( ! empty( $section ) ) {
			$section['slug'] = $section_slug;
		}

		return $section;
	}

	/**
	 * Get Current Tab Section Slug.
	 *
	 * @since 4.3.0
	 *
	 * @return string
	 */
	public function get_current_tab_section_slug() {
		return isset( $this->section['slug'] ) ? esc_attr( $this->section['slug'] ) : '';
	}

	/**
	 * Get Current Tab Section Url.
	 *
	 * @since 4.1.0
	 *
	 * @param string $slug The page slug.
	 *
	 * @return string The tab url.
	 */
	public function get_current_tab_section_url() {
		return esc_url_raw( add_query_arg( array( 'tab' => $this->get_current_tab_slug(), 'section' => $this->get_current_tab_section_slug() ), $this->get_url() ) );
	}

	/**
	 * Get Default Tab Section.
	 *
	 * @since 4.3.0
	 *
	 * @return int|string
	 */
	public function get_default_tab_section() {
		$default = '';

		foreach ( $this->tab['sections'] as $slug => $section ) {
			if ( $this->is_default_tab_section( $section ) ) {
				$default = $slug;
				break;
			}
		}

		if ( empty( $default ) ) {
			$sections = $this->tab['sections'];
			reset( $sections );
			$default = key( $sections );
		}

		return $slug;
	}

	/**
	 * Is Default Tab?
	 *
	 * @since 4.3.0
	 *
	 * @return bool The default slug if is default.
	 */
	public function is_default_tab( $tab ) {
		return isset( $tab['default'] ) ? $tab['default'] : false;
	}

	/**
	 * Is Default Tab Section?
	 *
	 * @since 4.3.0
	 *
	 * @return bool The default slug if is default.
	 */
	public function is_default_tab_section( $section ) {
		return isset( $section['default'] ) ? $section['default'] : false;
	}

	/**
	 * Is Active Tab?
	 *
	 * @since 4.3.0
	 *
	 * @param string $slug The current tab slug.
	 *
	 * @return string The active class or blank.
	 */
	public function is_active_tab( $slug ) {
		return isset( $this->tab['slug'] ) && $slug === $this->tab['slug'] ? ' nav-tab-active' : '';
	}

	/**
	 * Is Active Tab Section?
	 *
	 * @since 4.3.0
	 *
	 * @param string $slug The current section slug.
	 *
	 * @return string The active class or blank.
	 */
	public function is_active_tab_section( $slug ) {
		return isset( $this->section['slug'] ) && $slug === $this->section['slug'] ? ' current' : '';
	}

	/**
	 * Does tab have sections?
	 *
	 * @since 4.3.0
	 *
	 * @return bool True if there are sections, false otherwise.
	 */
	public function has_tab_sections() {
		return ! empty( $this->tab['sections'] ) ? true : false;
	}

	/**
	 * Get Tab Url.
	 *
	 * @since 4.1.0
	 *
	 * @param string $slug The page slug.
	 *
	 * @return string The tab url.
	 */
	public function get_tab_url( $slug ) {
		return esc_url_raw( add_query_arg( array( 'tab' => $slug ), $this->get_url() ) );
	}

	/**
	 * Get Tab Section Url.
	 *
	 * @since 4.1.0
	 *
	 * @param string $slug The page slug.
	 *
	 * @return string The tab url.
	 */
	public function get_tab_section_url( $slug ) {
		return esc_url_raw( add_query_arg( array( 'tab' => $this->get_current_tab_slug(), 'section' => $slug ), $this->get_url() ) );
	}

	/**
	 * Get tab label.
	 *
	 * @since 4.1.0
	 *
	 * @param array $tab The current tab.
	 *
	 * @return string The tab label.
	 */
	public function get_tab_label( $tab ) {
		return isset( $tab['label'] ) ? esc_attr( $tab['label'] ) : '';
	}

	/**
	 * Get tab section label.
	 *
	 * @since 4.1.0
	 *
	 * @param array $section The current tab section.
	 *
	 * @return string The tab section label.
	 */
	public function get_tab_section_label( $section ) {
		return isset( $section['label'] ) ? esc_attr( $section['label'] ) : '';
	}

	/**
	 * Get Tab Screen Reader Text.
	 *
	 * @since 4.3.0
	 */
	public function get_tab_screen_reader_text() {
		echo ! empty( $this->tab['label'] ) ? sprintf( '<h2 class="screen-reader-text">%s</h2>', esc_html( $this->tab['label'] ) ) : '';
	}

	/**
	 * Get Tabs Navigation.
	 *
	 * @since 4.3.0
	 *
	 * @param array $tabs The tabs that should be displayed.
	 */
	public function get_tabs_navigation() {
		if ( empty( $this->tabs ) ) {
			return;
		}

		echo '<nav class="nav-tab-wrapper wpcw-nav-tab-wrapper wpcw-tabs-navigation">';

		foreach ( $this->tabs as $tab_slug => $tab ) {
			if ( empty( $tab['sections'] ) && empty( $tab['fields'] ) ) {
				continue;
			}

			printf(
				'<a class="nav-tab%s" href="%s">%s</a>',
				$this->is_active_tab( $tab_slug ),
				$this->get_tab_url( $tab_slug ),
				$this->get_tab_label( $tab )
			);
		}

		echo '</nav>';
	}

	/**
	 * Get Tab Sections Navigation.
	 *
	 * @since 4.3.0
	 */
	public function get_tab_sections_navigation() {
		if ( ! $this->has_tab_sections() ) {
			return;
		}

		$sections = $this->get_tab_sections();

		$section_keys = array_keys( $sections );

		if ( count( $sections ) <= 1 ) {
			return;
		}

		echo '<ul class="subsubsub wpcw-sub-nav">';

		foreach ( $sections as $slug => $section ) {
			printf(
				'<li><a class="section-tab%s" href="%s">%s</a></li>%s',
				$this->is_active_tab_section( $slug ),
				$this->get_tab_section_url( $slug ),
				$this->get_tab_section_label( $section ),
				( end( $section_keys ) == $slug ? '' : '&nbsp;|&nbsp;' )
			);
		}

		echo '</ul>';
	}

	/**
	 * Get Tab Content Start.
	 *
	 * @since 4.3.0
	 */
	public function get_tab_content_start() {
		if ( $this->has_tab_sections() ) {
			echo '<div class="metabox-holder wpcw-page ' . $this->get_slug() . '-page ' . $this->get_slug() . '-page-' . $this->get_current_tab_slug() . ' ' . $this->get_slug() . '-page-' . $this->get_current_tab_slug() . '-section-' . $this->get_current_tab_section_slug() . '">';
		} else {
			echo '<div class="metabox-holder wpcw-page ' . $this->get_slug() . '-page ' . $this->get_slug() . '-page-' . $this->get_current_tab_slug() . '">';
		}
	}

	/**
	 * Get Tab Form Start.
	 *
	 * @since 4.3.0
	 */
	public function get_tab_form_start() {
		$current_tab = $this->get_current_tab_slug();

		if ( $this->has_tab_sections() && ! empty( $this->section['form'] ) ) {
			$current_section = $this->get_current_tab_section_slug();
			echo '<form class="wpcw-form" method="post">';
			echo '<input type="hidden" name="action" value="wpcw-update-' . $current_tab . '-' . $current_section . '">';
			echo '<input type="hidden" name="tab" value="' . $current_tab . '">';
			echo '<input type="hidden" name="section" value="' . $current_section . '">';
			echo '<input type="hidden" name="nonce" value="' . wp_create_nonce( "wpcw-{$current_tab}-{$current_section}-nonce" ) . '">';
		} elseif ( ! empty( $this->tab['form'] ) ) {
			echo '<form class="wpcw-form" method="post">';
			echo '<input type="hidden" name="action" value="wpcw-update-' . $current_tab . '">';
			echo '<input type="hidden" name="tab" value="' . $current_tab . '">';
			echo '<input type="hidden" name="nonce" value="' . wp_create_nonce( "wpcw-{$current_tab}-nonce" ) . '">';
		} else {
			echo '<div class="wpcw-form">';
		}
	}

	/**
	 * Get Tab Fields.
	 *
	 * @since 4.3.0
	 *
	 * @return array|mixed The page fields.
	 */
	public function get_tab_fields() {
		$fields = array();

		if ( $this->has_tab_sections() && ! empty( $this->section['fields'] ) ) {
			$fields = $this->section['fields'];
		} elseif ( ! empty( $this->tab['fields'] ) ) {
			$fields = $this->tab['fields'];
		}

		return $fields;
	}

	/**
	 * Get Fields.
	 *
	 * @since 4.3.0
	 */
	public function get_fields() {
		$fields = $this->get_tab_fields();
		$fields = $this->normalilze_fields( $fields );

		return apply_filters( 'wpcw_page_fields', array_map( array( $this, 'set_defaults' ), $fields ) );
	}

	/**
	 * Get Tab Html.
	 *
	 * @since 4.3.0
	 */
	public function get_tab_html() {
		$this->generate_fields_html( $this->get_tab_fields() );
	}

	/**
	 * Get Tab Form End.
	 *
	 * @since 4.3.0
	 */
	public function get_tab_form_end() {
		if ( $this->has_tab_sections() && ! empty( $this->section['form'] ) ) {
			if ( ! empty( $this->section['submit'] ) ) {
				printf( '<div class="wpcw-form-submit"><button type="submit" name="wpcw-form-submit" class="button button-primary">%s</button></div>', $this->section['submit'] );
			}

			echo '</form>';
		} elseif ( ! empty( $this->tab['form'] ) ) {
			if ( ! empty( $this->tab['submit'] ) ) {
				printf( '<div class="wpcw-form-submit"><button type="submit" name="wpcw-form-submit" class="button button-primary">%s</button></div>', $this->tab['submit'] );
			}

			echo '</form>';
		} else {
			echo '</div>';
		}
	}

	/**
	 * Get Tab Content End.
	 *
	 * @since 4.3.0
	 */
	public function get_tab_content_end() {
		echo '</div>';
	}

	/**
	 * Get Tab Content.
	 *
	 * @since 4.3.0
	 */
	public function get_tab_content() {
		$this->get_tab_screen_reader_text();
		$this->get_tab_sections_navigation();
		$this->get_tab_content_start();
		$this->get_tab_form_start();
		$this->get_tab_html();
		$this->get_tab_form_end();
		$this->get_tab_content_end();
	}

	/**
	 * Can Process Form?
	 *
	 * @since 4.3.0
	 *
	 * @return bool If the current tab form can be processed.
	 */
	public function can_process_form() {
		if ( ! isset( $_POST['wpcw-form-submit'] ) ) {
			return false;
		}

		if ( empty( $_POST['tab'] ) ) {
			return false;
		}

		$current_tab = $this->get_current_tab_slug();

		if ( $current_tab !== esc_attr( $_POST['tab'] ) ) {
			return false;
		}

		if ( $this->has_tab_sections() ) {
			$current_section = $this->get_current_tab_section_slug();
			if ( ! wp_verify_nonce( $_POST['nonce'], "wpcw-{$current_tab}-{$current_section}-nonce" ) ) {
				return false;
			}
		} elseif ( ! wp_verify_nonce( $_POST['nonce'], "wpcw-{$current_tab}-nonce" ) ) {
			return false;
		}

		if ( ! current_user_can( apply_filters( 'wpcw_admin_page_form_process_capability', 'manage_options' ) ) ) {
			return false;
		}

		return apply_filters( 'wpcw_admin_page_form_process_condition', true, $this );
	}
}

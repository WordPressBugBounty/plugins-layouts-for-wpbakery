<?php
/**
 * Plugin Name: Layouts for WPBakery
 * Plugin URI: https://www.techeshta.com/product/layouts-for-wpbakery/
 * Description: Beautifully designed, Free templates, Handcrafted for popular WPBakery page builder.
 * Version: 2.0
 * Requires at least: 5.8
 * Requires PHP: 7.4
 * Tested up to: 7.1
 * Author: Techeshta
 * Author URI: https://www.techeshta.com
 * License: GPLv2 or later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain: layouts-for-wpbakery
 * Domain Path: /languages/
 *
 * @package Layouts_For_WPBakery
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/*
 * Plugin constants.
 */
define( 'LFW_FILE', __FILE__ );
define( 'LFW_DIR', plugin_dir_path( LFW_FILE ) );
define( 'LFW_URL', plugins_url( '/', LFW_FILE ) );
define( 'LFW_TEXTDOMAIN', 'layouts-for-wpbakery' );

/**
 * Main plugin class.
 *
 * Registers the "Layouts" admin screen, its assets, and the WPBakery
 * dependency notices. The remote API client and the importer live in
 * includes/.
 */
class Layouts_For_WPBakery {

	/**
	 * Plugin version, used for asset cache-busting. Keep in sync with the
	 * "Version" header above and readme.txt's "Stable tag".
	 *
	 * @var string
	 */
	const VERSION = '2.0';

	/**
	 * Minimum WPBakery Page Builder version this plugin supports.
	 *
	 * @var string
	 */
	const MINIMUM_WPBAKERY_VERSION = '5.0';

	/**
	 * Nonce action shared by the plugin's AJAX requests.
	 *
	 * Plugin-specific on purpose: the sibling Layouts for Elementor plugin
	 * uses the generic 'ajax-nonce' action.
	 *
	 * @var string
	 */
	const NONCE_ACTION = 'lfw-ajax-nonce';

	/**
	 * Layouts_For_WPBakery constructor.
	 *
	 * Registers the main plugin actions with WordPress.
	 */
	public function __construct() {
		add_action( 'init', array( $this, 'lfw_check_dependencies' ) );
		$this->hooks();
		$this->lfw_include_files();
	}

	/**
	 * Register hooks that do not depend on WPBakery being active.
	 *
	 * @return void
	 */
	public function hooks() {
		add_action( 'admin_enqueue_scripts', array( $this, 'lfw_admin_scripts' ) );
		register_activation_hook( LFW_FILE, array( $this, 'lfw_plugin_activation' ) );
	}

	/**
	 * Load the remote API client and the importer.
	 *
	 * @return void
	 */
	public function lfw_include_files() {
		require_once LFW_DIR . 'includes/api/class-layouts-remote.php';
		require_once LFW_DIR . 'includes/class-layout-importer.php';
	}

	/**
	 * Check that WPBakery Page Builder is active and recent enough.
	 *
	 * Adds the admin menu when it is, otherwise shows an admin notice.
	 *
	 * @return void
	 */
	public function lfw_check_dependencies() {

		if ( ! defined( 'WPB_VC_VERSION' ) ) {
			add_action( 'admin_notices', array( $this, 'lfw_layouts_widget_fail_load' ) );
			return;
		}

		if ( ! version_compare( WPB_VC_VERSION, self::MINIMUM_WPBAKERY_VERSION, '>=' ) ) {
			add_action( 'admin_notices', array( $this, 'lfw_layouts_wpbakery_update_notice' ) );
			return;
		}

		add_action( 'admin_menu', array( $this, 'lfw_menu' ) );
	}

	/**
	 * Admin notice shown when WPBakery is not installed and/or not active.
	 *
	 * @return void
	 */
	public function lfw_layouts_widget_fail_load() {

		$screen = function_exists( 'get_current_screen' ) ? get_current_screen() : null;
		if ( isset( $screen->parent_file ) && 'plugins.php' === $screen->parent_file && 'update' === $screen->id ) {
			return;
		}

		if ( ! function_exists( 'get_plugins' ) ) {
			require_once ABSPATH . 'wp-admin/includes/plugin.php';
		}

		$plugin            = 'js_composer/js_composer.php';
		$installed_plugins = get_plugins();

		if ( isset( $installed_plugins[ $plugin ] ) ) {
			if ( ! current_user_can( 'activate_plugins' ) ) {
				return;
			}
			$activation_url = wp_nonce_url( admin_url( 'plugins.php?action=activate&plugin=' . rawurlencode( $plugin ) . '&plugin_status=all&paged=1' ), 'activate-plugin_' . $plugin );

			$message  = '<p><strong>' . esc_html__( 'Layouts for WPBakery', 'layouts-for-wpbakery' ) . '</strong>' . esc_html__( ' plugin not working because you need to activate the WPBakery plugin.', 'layouts-for-wpbakery' ) . '</p>';
			$message .= '<p>' . sprintf( '<a href="%s" class="button-primary">%s</a>', esc_url( $activation_url ), esc_html__( 'Activate WPBakery Now', 'layouts-for-wpbakery' ) ) . '</p>';
		} else {
			if ( ! current_user_can( 'install_plugins' ) ) {
				return;
			}

			$message  = '<p><strong>' . esc_html__( 'Layouts for WPBakery', 'layouts-for-wpbakery' ) . '</strong>' . esc_html__( ' plugin not working because you need to install the WPBakery plugin', 'layouts-for-wpbakery' ) . '</p>';
			$message .= '<p>' . sprintf( '<a href="%s" class="button-primary" target="_blank" rel="noopener noreferrer">%s</a>', esc_url( 'https://wpbakery.com' ), esc_html__( 'Get WPBakery', 'layouts-for-wpbakery' ) ) . '</p>';
		}

		echo '<div class="notice notice-error">' . wp_kses_post( $message ) . '</div>';
	}

	/**
	 * Admin notice shown when the active WPBakery is older than MINIMUM_WPBAKERY_VERSION.
	 *
	 * @return void
	 */
	public function lfw_layouts_wpbakery_update_notice() {
		if ( ! current_user_can( 'update_plugins' ) ) {
			return;
		}

		$message  = '<p><strong>' . esc_html__( 'Layouts for WPBakery', 'layouts-for-wpbakery' ) . '</strong>' . esc_html__( ' plugin not working because you are using an old version of WPBakery.', 'layouts-for-wpbakery' ) . '</p>';
		$message .= '<p>' . sprintf( '<a href="%s" class="button-primary" target="_blank" rel="noopener noreferrer">%s</a>', esc_url( 'https://wpbakery.com' ), esc_html__( 'Get Latest WPBakery', 'layouts-for-wpbakery' ) ) . '</p>';
		echo '<div class="notice notice-error">' . wp_kses_post( $message ) . '</div>';
	}

	/**
	 * Activation hook: deactivate the premium Layouts Pro for WPBakery plugin.
	 *
	 * The free and premium plugins register the same admin screen and AJAX
	 * handlers, so they must never be active at the same time.
	 *
	 * @return void
	 */
	public function lfw_plugin_activation() {
		deactivate_plugins( 'layouts-pro-for-wpbakery/layouts-pro-for-wpbakery.php' );
	}

	/**
	 * Register admin CSS/JS, and enqueue them on the plugin's own screen only.
	 *
	 * @return void
	 */
	public function lfw_admin_scripts() {
		wp_register_style( 'lfw-admin-stylesheets', LFW_URL . 'assets/css/admin.css', array(), self::VERSION, 'all' );
		wp_register_style( 'lfw-toastify-stylesheets', LFW_URL . 'assets/css/toastify.css', array(), self::VERSION, 'all' );
		wp_register_script( 'lfw-admin-script', LFW_URL . 'assets/js/admin.js', array( 'jquery' ), self::VERSION, true );
		wp_register_script( 'lfw-toastify-script', LFW_URL . 'assets/js/toastify.js', array( 'jquery' ), self::VERSION, true );
		wp_localize_script(
			'lfw-admin-script',
			'lfw_js_object',
			array(
				'lfw_loading'  => esc_html__( 'Importing...', 'layouts-for-wpbakery' ),
				'lfw_tem_msg'  => esc_html__( 'Template is successfully imported!.', 'layouts-for-wpbakery' ),
				'lfw_msg'      => esc_html__( 'Your page is successfully imported!', 'layouts-for-wpbakery' ),
				'lfw_crt_page' => esc_html__( 'Please Enter Page Name.', 'layouts-for-wpbakery' ),
				'lfw_sync'     => esc_html__( 'Syncing...', 'layouts-for-wpbakery' ),
				'lfw_sync_suc' => esc_html__( 'Templates library refreshed', 'layouts-for-wpbakery' ),
				'lfw_sync_fai' => esc_html__( 'Error in library Syncing', 'layouts-for-wpbakery' ),
				'lfw_error'    => esc_html__( 'Something went wrong. Please try again.', 'layouts-for-wpbakery' ),
				'lfw_url'      => LFW_URL,
				'nonce'        => wp_create_nonce( self::NONCE_ACTION ),
			)
		);

		$page = isset( $_GET['page'] ) ? sanitize_key( $_GET['page'] ) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Only used to decide whether to enqueue assets.
		if ( 'lfw_layouts' === $page ) {
			wp_enqueue_style( 'lfw-admin-stylesheets' );
			wp_enqueue_style( 'lfw-toastify-stylesheets' );
			wp_enqueue_script( 'lfw-toastify-script' );
			wp_enqueue_script( 'lfw-admin-script' );
			add_thickbox();
		}
	}

	/**
	 * Add the "Layouts" top-level admin menu.
	 *
	 * @return void
	 */
	public function lfw_menu() {
		add_menu_page(
			esc_html__( 'Layouts', 'layouts-for-wpbakery' ),
			esc_html__( 'Layouts', 'layouts-for-wpbakery' ),
			'manage_options',
			'lfw_layouts',
			array( $this, 'lfw_layouts_page' ),
			LFW_URL . 'assets/images/layouts-for-wpbakery.png'
		);
	}

	/**
	 * Render the "Layouts" admin page.
	 *
	 * @return void
	 */
	public function lfw_layouts_page() {
		include_once LFW_DIR . 'includes/layouts.php';
	}
}

/*
 * Start the plugin.
 */
new Layouts_For_WPBakery();

<?php

/**
 * Plugin Name: Layouts for WPBakery
 * Plugin URI: https://www.techeshta.com/product/layouts-for-wpbakery/
 * Description: Beautifully designed, Free templates, Handcrafted for popular WPBakery page builder.
 * Version: 1.1.2
 * Author: Techeshta
 * Author URI: https://www.techeshta.com
 * License: GPLv2 or later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 *
 * Text Domain: layouts-for-wpbakery
 * Domain Path: /languages/
 */
/*
 * Exit if accessed directly
 */
if (!defined('ABSPATH')) {
    exit;
}

/*
 * Define variables
 */
define('LFW_FILE', __FILE__);
define('LFW_DIR', plugin_dir_path(LFW_FILE));
define('LFW_URL', plugins_url('/', LFW_FILE));
define('LFW_TEXTDOMAIN', 'layouts-for-wpbakery');

/**
 * Main Plugin Layouts_For_WPBakery class.
 */
class Layouts_For_WPBakery {

    /**
     * Layouts_For_WPBakery constructor.
     *
     * The main plugin actions registered for WordPress
     */
    public function __construct() {
        add_action('init', array($this, 'lfw_check_dependencies'));
        $this->hooks();
        $this->lfw_include_files();
    }

    /**
     * Initialize
     */
    public function hooks() {
        add_action('plugins_loaded', array($this, 'lfw_load_language_files'));
        add_action('admin_enqueue_scripts', array($this, 'lfw_admin_scripts',));
        register_activation_hook(LFW_FILE, array($this, 'lfw_plugin_activation'));
    }

    /**
     * Load files
     */
    public function lfw_include_files() {
        include_once( LFW_DIR . 'includes/class-layout-importer.php' );
        include_once( LFW_DIR . 'includes/api/class-layouts-remote.php' );
    }

    /**
     * @return Loads plugin textdomain
     */
    public function lfw_load_language_files() {
        load_plugin_textdomain('layouts-for-wpbakery', false, dirname(plugin_basename(__FILE__)) . '/languages');
    }

    /**
     * Check plugin dependencies
     * Check if WPBakery plugin is installed
     */
    public function lfw_check_dependencies() {

        if (!defined('WPB_VC_VERSION')) {
            add_action('admin_notices', array($this, 'lfw_layouts_widget_fail_load'));
            return;
        } else {
            add_action('admin_menu', array($this, 'lfw_menu'));
        }
        $wpbakery_version_required = '5.0';
        if (!version_compare(WPB_VC_VERSION, $wpbakery_version_required, '>=')) {
            add_action('admin_notices', array($this, 'lfw_layouts_wpbakery_update_notice'));
            return;
        }
    }

    /**
     * This notice will appear if WPBakery is not installed or activated or both
     */
    public function lfw_layouts_widget_fail_load() {

        $screen = get_current_screen();
        if (isset($screen->parent_file) && 'plugins.php' === $screen->parent_file && 'update' === $screen->id) {
            return;
        }

        $plugin = 'js_composer/js_composer.php';
        $file_path = 'js_composer/js_composer.php';
        $installed_plugins = get_plugins();

        if (isset($installed_plugins[$file_path])) { // check if plugin is installed
            if (!current_user_can('activate_plugins')) {
                return;
            }
            $activation_url = wp_nonce_url('plugins.php?action=activate&amp;plugin=' . $plugin . '&amp;plugin_status=all&amp;paged=1&amp;s', 'activate-plugin_' . $plugin);

            $message = '<p><strong>' . esc_html__('Layouts for WPBakery', 'layouts-for-wpbakery') . '</strong>' . esc_html__(' plugin not working because you need to activate the WPBakery plugin.', 'layouts-for-wpbakery') . '</p>';
            $message .= '<p>' . sprintf('<a href="%s" class="button-primary">%s</a>', $activation_url, esc_html__('Activate WPBakery Now', 'layouts-for-wpbakery')) . '</p>';
        } else {
            if (!current_user_can('install_plugins')) {
                return;
            }

            $buy_now_url = esc_url('https://wpbakery.com');

            $message = '<p><strong>' . esc_html__('Layouts for WPBakery', 'layouts-for-wpbakery') . '</strong>' . esc_html__(' plugin not working because you need to install the WPBakery plugin', 'layouts-for-wpbakery') . '</p>';
            $message .= '<p>' . sprintf('<a href="%s" class="button-primary" target="_blank">%s</a>', $buy_now_url, esc_html__('Get WPBakery', 'layouts-for-wpbakery')) . '</p>';
        }

        echo '<div class="error"><p>' . wp_kses_post($message) . '</p></div>';
    }

    /**
     * Display admin notice for WPBakery update if WPBakery version is old
     */
    public function lfw_layouts_wpbakery_update_notice() {
        if (!current_user_can('update_plugins')) {
            return;
        }

        $file_path = 'js_composer/js_composer.php';

        $upgrade_link = esc_url('https://wpbakery.com');
        $message = '<p><strong>' . esc_html__('Layouts for WPBakery', 'layouts-for-wpbakery') . '</strong>' . esc_html__(' plugin not working because you are using an old version of WPBakery.', 'layouts-for-wpbakery') . '</p>';
        $message .= '<p>' . sprintf('<a href="%s" class="button-primary" target="_blank">%s</a>', $upgrade_link, esc_html__('Get Latest WPBakery', 'layouts-for-wpbakery')) . '</p>';
        echo '<div class="error">' . wp_kses_post($message) . '</div>';
    }

    /**
     *
     * @return plugin activate function
     */
    public function lfw_plugin_activation() {
        // Deactivate Layouts for WPBakery (premium) plugin than activate Layouts for WPBakery (free) plugin
        deactivate_plugins('layouts-pro-for-wpbakery/layouts-pro-for-wpbakery.php');
    }

    /**
     *
     * @return Enqueue admin panel required css/js
     */
    public function lfw_admin_scripts() {
        $screen = get_current_screen();

        wp_register_style('lfw-admin-stylesheets', LFW_URL . 'assets/css/admin.css', array(), 1.0, false);
        wp_register_style('lfw-toastify-stylesheets', LFW_URL . 'assets/css/toastify.css', array(), 1.0, false);
        wp_register_script('lfw-admin-script', LFW_URL . 'assets/js/admin.js', array('jquery'), '1.0.0', true);
        wp_register_script('lfw-toastify-script', LFW_URL . 'assets/js/toastify.js', array('jquery'), '1.0.0', true);
        wp_localize_script('lfw-admin-script', 'js_object', array(
            'lfw_loading' => __('Importing...', 'layouts-for-wpbakery'),
            'lfw_tem_msg' => __('Template is successfully imported!.', 'layouts-for-wpbakery'),
            'lfw_msg' => __('Your page is successfully imported!', 'layouts-for-wpbakery'),
            'lfw_crt_page' => __('Please Enter Page Name.', 'layouts-for-wpbakery'),
            'lfw_sync' => __('Syncing...', 'layouts-for-wpbakery'),
            'lfw_sync_suc' => __('Templates library refreshed', 'layouts-for-wpbakery'),
            'lfw_sync_fai' => __('Error in library Syncing', 'layouts-for-wpbakery'),
            'lfw_error' => __('Something went wrong. Please try again.', 'layouts-for-wpbakery'),
            'lfw_url' => LFW_URL,
            'nonce' => wp_create_nonce('ajax-nonce')
        ));

        if ((isset($_GET['page']) && ( $_GET['page'] == 'lfw_layouts' || $_GET['page'] == 'lfw_started'))) {
            wp_enqueue_style('lfw-admin-stylesheets');
            wp_enqueue_style('lfw-toastify-stylesheets');
            wp_enqueue_script('lfw-toastify-script');
            wp_enqueue_script('lfw-admin-script');
            wp_enqueue_script('lfw-admin-live-script');
            add_thickbox();
        }
    }

    /**
     *
     * add menu at admin panel
     */
    public function lfw_menu() {
        add_menu_page(__('Layouts', 'layouts-for-wpbakery'), __('Layouts', 'layouts-for-wpbakery'), 'administrator', 'lfw_layouts', 'lfw_layouts_function', LFW_URL . 'assets/images/layouts-for-wpbakery.png');

        /**
         *
         * @global type $wp_version
         * @return html Display setting options
         */
        function lfw_layouts_function() {
            include_once( 'includes/layouts.php' );
        }

    }

}

/*
 * Starts our plugin class, easy!
 */
new Layouts_For_WPBakery();

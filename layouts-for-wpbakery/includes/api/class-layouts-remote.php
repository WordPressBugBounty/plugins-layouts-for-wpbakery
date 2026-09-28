<?php
/**
 * Client for the remote Layouts for WPBakery template API.
 *
 * @package Layouts_For_WPBakery
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Handle remote API requests to www.layoutsforwpbakery.com.
 *
 * Template and category lists are cached in transients; the "Sync Now"
 * button (AJAX action lfw_handle_sync) forces a refresh.
 */
class Layouts_WPB_Remote {

	/**
	 * Singleton instance.
	 *
	 * @var Layouts_WPB_Remote|null
	 */
	protected static $lfw_instance = null;

	/**
	 * Transient key for the cached templates list.
	 *
	 * @var string
	 */
	const TRANSIENT_TEMPLATE = 'lfw_templates';

	/**
	 * Transient key for the cached categories list.
	 *
	 * @var string
	 */
	const TRANSIENT_CATEGORY = 'lfw_categories';

	/**
	 * API endpoint listing all templates.
	 *
	 * @var string
	 */
	const TEMPLATES = 'https://www.layoutsforwpbakery.com/wp-json/layoutsforwpbakery/v1/templates';

	/**
	 * API endpoint listing all template categories.
	 *
	 * @var string
	 */
	const CATEGORIES = 'https://www.layoutsforwpbakery.com/wp-json/layoutsforwpbakery/v1/categories';

	/**
	 * API template URL (sprintf() format, takes the template ID).
	 *
	 * @var string
	 */
	private static $template_url = 'https://www.layoutsforwpbakery.com/wp-json/layoutsforwpbakery/v1/template/byid/?id=%d';

	/**
	 * API image URL (sprintf() format, takes the remote media ID).
	 *
	 * @var string
	 */
	private static $image_url = 'https://www.layoutsforwpbakery.com/wp-json/layoutsforwpbakery/v1/image/byid/?id=%d';

	/**
	 * Constructor.
	 */
	public function __construct() {
		$this->hooks();
	}

	/**
	 * Access the shared instance.
	 *
	 * @return self
	 */
	public static function lfw_get_instance() {
		if ( null === self::$lfw_instance ) {
			self::$lfw_instance = new self();
		}
		return self::$lfw_instance;
	}

	/**
	 * Register hooks.
	 *
	 * The action name is plugin-prefixed: the sibling Layouts for Elementor
	 * plugin registers the generic wp_ajax_handle_sync, and with both
	 * plugins active the first-loaded handler would answer both plugins'
	 * requests.
	 *
	 * @return void
	 */
	public function hooks() {
		add_action( 'wp_ajax_lfw_handle_sync', array( $this, 'template_sync' ) );
	}

	/**
	 * AJAX handler: force-refresh the cached templates and categories lists.
	 *
	 * Prints "success" or "error".
	 *
	 * @return void
	 */
	public function template_sync() {

		if ( ! isset( $_POST['nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['nonce'] ) ), Layouts_For_WPBakery::NONCE_ACTION ) ) {
			wp_die( 'error', '', array( 'response' => 403 ) );
		}

		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( 'error', '', array( 'response' => 403 ) );
		}

		$templates  = $this->templates_list( true );
		$categories = $this->categories_list( true );

		echo ( is_array( $templates ) && is_array( $categories ) ) ? 'success' : 'error';
		wp_die();
	}

	/**
	 * Get the templates list.
	 *
	 * @param bool $force_update Whether to bypass the transient cache.
	 * @return array|string Decoded API response, or an error message.
	 */
	public function templates_list( $force_update = false ) {
		return $this->cached_request( self::TRANSIENT_TEMPLATE, self::TEMPLATES, 'templates', 12 * HOUR_IN_SECONDS, $force_update );
	}

	/**
	 * Get the template categories.
	 *
	 * @param bool $force_update Whether to bypass the transient cache.
	 * @return array|string Decoded API response, or an error message.
	 */
	public function categories_list( $force_update = false ) {
		return $this->cached_request( self::TRANSIENT_CATEGORY, self::CATEGORIES, 'category', HOUR_IN_SECONDS, $force_update );
	}

	/**
	 * Fetch a list endpoint, caching only well-formed responses.
	 *
	 * A failed request (network error, non-200, invalid JSON, or a payload
	 * missing $required_key) is returned as an error message and is not
	 * cached, so the next page load retries instead of showing an empty
	 * library until the transient expires.
	 *
	 * @param string $transient    Transient key.
	 * @param string $url          Endpoint URL.
	 * @param string $required_key Key a valid response must contain.
	 * @param int    $expiration   Cache lifetime in seconds.
	 * @param bool   $force_update Whether to bypass the transient cache.
	 * @return array|string Decoded API response, or an error message.
	 */
	private function cached_request( $transient, $url, $required_key, $expiration, $force_update ) {
		$cached = get_transient( $transient );
		if ( is_array( $cached ) && ! $force_update ) {
			return $cached;
		}

		$request = wp_remote_get( $url, array( 'timeout' => 30 ) );
		if ( is_wp_error( $request ) ) {
			return $request->get_error_message();
		}

		$response = json_decode( wp_remote_retrieve_body( $request ), true );
		if ( 200 !== (int) wp_remote_retrieve_response_code( $request ) || ! is_array( $response ) || ! isset( $response[ $required_key ] ) ) {
			return __( 'Unable to load the layouts library. Please try again later.', 'layouts-for-wpbakery' );
		}

		set_transient( $transient, $response, $expiration );
		return $response;
	}

	/**
	 * Get a single template's content.
	 *
	 * @param int $template_id Template ID.
	 * @return array|string|\WP_Error Template data, an API message string, or an error.
	 */
	public function get_template_content( $template_id ) {
		$url = sprintf( self::$template_url, (int) $template_id );

		$response = wp_remote_get( $url, array( 'timeout' => 30 ) );

		if ( is_wp_error( $response ) ) {
			return $response;
		}

		$response_code = (int) wp_remote_retrieve_response_code( $response );
		if ( 200 !== $response_code ) {
			/* translators: %d: HTTP status code. */
			return new \WP_Error( 'response_code_error', sprintf( __( 'The request returned with a status code of %d.', 'layouts-for-wpbakery' ), $response_code ) );
		}

		$template_content = json_decode( wp_remote_retrieve_body( $response ), true );
		if ( ! is_array( $template_content ) ) {
			return new \WP_Error( 'template_data_error', __( 'An invalid data was returned.', 'layouts-for-wpbakery' ) );
		}

		if ( ! empty( $template_content['message'] ) ) {
			return (string) $template_content['message'];
		}

		if ( isset( $template_content['error'] ) ) {
			return new \WP_Error( 'response_error', (string) $template_content['error'] );
		}

		if ( empty( $template_content['title'] ) && empty( $template_content['template'] ) ) {
			return new \WP_Error( 'template_data_error', __( 'An invalid data was returned.', 'layouts-for-wpbakery' ) );
		}

		return $template_content;
	}

	/**
	 * Get a single image URL by remote media ID.
	 *
	 * @param int $media_id Remote media ID.
	 * @return string|\WP_Error Image URL, 'Missing Attachment', empty string, or an error.
	 */
	public function get_media_image( $media_id ) {
		$url = sprintf( self::$image_url, (int) $media_id );

		$response = wp_remote_get( $url, array( 'timeout' => 30 ) );

		if ( is_wp_error( $response ) ) {
			return $response;
		}

		$response_code = (int) wp_remote_retrieve_response_code( $response );
		if ( 200 !== $response_code ) {
			/* translators: %d: HTTP status code. */
			return new \WP_Error( 'response_code_error', sprintf( __( 'The request returned with a status code of %d.', 'layouts-for-wpbakery' ), $response_code ) );
		}

		$image_array = json_decode( wp_remote_retrieve_body( $response ), true );
		if ( isset( $image_array['msg'] ) && 'Missing Attachment' === $image_array['msg'] ) {
			return $image_array['msg'];
		}

		if ( ! empty( $image_array['image_url'] ) && is_string( $image_array['image_url'] ) ) {
			return $image_array['image_url'];
		}

		return '';
	}
}

Layouts_WPB_Remote::lfw_get_instance();

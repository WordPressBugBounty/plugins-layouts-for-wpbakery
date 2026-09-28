<?php
/**
 * Imports a remote WPBakery layout as a page or a saved template.
 *
 * @package Layouts_For_WPBakery
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class for importing a template.
 *
 * The remote API returns WPBakery shortcode markup whose images are
 * referenced by remote attachment ID (e.g. image="2698"). Before it is
 * saved, each of those images is sideloaded into the local Media Library
 * and the markup is rewritten to use the local attachment ID.
 */
class Layouts_WPB_Importer {

	/**
	 * Remote-to-local attachment ID map for the current import, so an image
	 * used several times in one layout is only looked up / downloaded once.
	 *
	 * @var array<int, int>
	 */
	private $media_map = array();

	/**
	 * Constructor.
	 */
	public function __construct() {
		$this->hooks();
	}

	/**
	 * Register hooks.
	 *
	 * The action name is plugin-prefixed: the sibling Layouts for Elementor
	 * plugin registers the generic wp_ajax_handle_import, and with both
	 * plugins active the first-loaded handler would answer both plugins'
	 * requests.
	 *
	 * @return void
	 */
	public function hooks() {
		add_action( 'wp_ajax_lfw_handle_import', array( $this, 'handle_import' ) );
	}

	/**
	 * AJAX handler: import a template.
	 *
	 * Prints the new page ID for a page import, "success" for a templates
	 * library import, or an error message on failure.
	 *
	 * @return void
	 */
	public function handle_import() {

		if ( ! isset( $_POST['nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['nonce'] ) ), Layouts_For_WPBakery::NONCE_ACTION ) ) {
			wp_die( esc_html__( 'Security check failed.', 'layouts-for-wpbakery' ), '', array( 'response' => 403 ) );
		}

		// Importing creates pages / saved templates from external content
		// and sideloads media, so require an administrator.
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( 'Permission denied.', 'layouts-for-wpbakery' ), '', array( 'response' => 403 ) );
		}

		$template_id = isset( $_POST['template_id'] ) ? absint( wp_unslash( $_POST['template_id'] ) ) : 0;
		$with_page   = isset( $_POST['with_page'] ) ? sanitize_text_field( wp_unslash( $_POST['with_page'] ) ) : '';

		$template = Layouts_WPB_Remote::lfw_get_instance()->get_template_content( $template_id );

		if ( is_wp_error( $template ) ) {
			echo esc_html( $template->get_error_message() );
			wp_die();
		}

		// A string here is a message from the API, e.g. "Record not found.".
		if ( is_string( $template ) ) {
			echo esc_html( $template );
			wp_die();
		}

		$result = $this->create_page( $template, $with_page );
		if ( is_wp_error( $result ) ) {
			echo esc_html( $result->get_error_message() );
		} elseif ( '' !== $with_page ) {
			echo (int) $result;
		} else {
			echo 'success';
		}
		wp_die();
	}

	/**
	 * Save the template as a new draft page, or append it to WPBakery's
	 * saved templates (the wpb_js_templates option).
	 *
	 * @param array  $template  Template data from the API (title, template).
	 * @param string $with_page Page title for a new page, or empty for a templates library import.
	 * @return int|true|\WP_Error New page ID, true for a library import, or an error.
	 */
	private function create_page( $template, $with_page ) {

		if ( empty( $template['template'] ) || ! is_string( $template['template'] ) ) {
			return new \WP_Error( 'template_data_error', __( 'An invalid data was returned.', 'layouts-for-wpbakery' ) );
		}

		$this->media_map = array();
		$content         = $this->localize_media( $template['template'] );

		if ( '' !== $with_page ) {
			return wp_insert_post(
				array(
					'post_type'    => 'page',
					'post_title'   => sanitize_text_field( $with_page ),
					'post_content' => $content,
					'post_status'  => 'draft',
					'meta_input'   => array(
						'_wpb_vc_js_status' => 'true',
					),
				),
				true
			);
		}

		// Same structure WPBakery itself uses when saving a template from
		// the editor: a unique key mapping to array( name, template ).
		$templates = get_option( 'wpb_js_templates' );
		if ( ! is_array( $templates ) ) {
			$templates = array();
		}

		$title = sanitize_text_field( isset( $template['title'] ) ? $template['title'] : '' );

		$templates[ uniqid( 'Template_' ) ] = array(
			'name'     => $title . ' (' . current_time( 'mysql' ) . ')',
			'template' => $content,
		);

		if ( ! update_option( 'wpb_js_templates', $templates ) ) {
			return new \WP_Error( 'template_save_error', __( 'Error: Templates exists!', 'layouts-for-wpbakery' ) );
		}

		return true;
	}

	/**
	 * Rewrite every remote image ID in the markup to a local attachment ID.
	 *
	 * Matches any attribute ending in "image" (e.g. image, bg_image) whose
	 * value is a numeric attachment ID. An image that cannot be localized
	 * keeps its original value.
	 *
	 * @param string $content WPBakery shortcode markup.
	 * @return string
	 */
	private function localize_media( $content ) {
		return (string) preg_replace_callback(
			'/image="(\d+)"/',
			function ( $matches ) {
				$local_id = $this->lfw_get_local_attachment_id( (int) $matches[1] );
				return 'image="' . ( $local_id ? $local_id : $matches[1] ) . '"';
			},
			$content
		);
	}

	/**
	 * Get the local attachment ID for a remote attachment ID, sideloading it if needed.
	 *
	 * @param int $remote_id Remote attachment ID.
	 * @return int Local attachment ID, or 0 on failure.
	 */
	private function lfw_get_local_attachment_id( $remote_id ) {
		if ( isset( $this->media_map[ $remote_id ] ) ) {
			return $this->media_map[ $remote_id ];
		}

		$local_id   = 0;
		$remote_url = $this->lfw_get_image_url( $remote_id );
		if ( '' !== $remote_url ) {
			$existing_url = $this->lfw_find_existing_attachment_url( $remote_url );
			$local_id     = '' !== $existing_url ? attachment_url_to_postid( $existing_url ) : $this->lfw_insert_media_from_url( $remote_url );
		}

		$this->media_map[ $remote_id ] = $local_id;
		return $local_id;
	}

	/**
	 * Find an already-imported copy of a remote image in the local Media Library.
	 *
	 * Matches by attachment slug (the file name without extension), also
	 * trying the "-1" / "-2" suffixes WordPress adds to duplicate slugs.
	 *
	 * @param string $image_url Remote image URL.
	 * @return string Local attachment URL, or empty string if none.
	 */
	private function lfw_find_existing_attachment_url( $image_url ) {
		$path      = (string) wp_parse_url( $image_url, PHP_URL_PATH );
		$file_name = sanitize_title( pathinfo( $path, PATHINFO_FILENAME ) );
		if ( '' === $file_name ) {
			return '';
		}

		foreach ( array( '', '-1', '-2' ) as $suffix ) {
			$url = $this->lfw_get_attachment_url_by_slug( $file_name . $suffix );
			if ( '' !== $url ) {
				return $url;
			}
		}

		return '';
	}

	/**
	 * Get a remote image URL by remote attachment ID.
	 *
	 * @param int $img_id Remote attachment ID.
	 * @return string Image URL, or empty string if unavailable (including
	 *                the API's "Missing Attachment" response).
	 */
	private function lfw_get_image_url( $img_id ) {
		$image_url = Layouts_WPB_Remote::lfw_get_instance()->get_media_image( $img_id );
		if ( is_string( $image_url ) && 0 === strpos( $image_url, 'http' ) ) {
			return $image_url;
		}
		return '';
	}

	/**
	 * Get a local attachment's URL by its slug.
	 *
	 * @param string $slug Attachment slug.
	 * @return string Attachment URL, or empty string if none.
	 */
	private function lfw_get_attachment_url_by_slug( $slug ) {
		$attachments = get_posts(
			array(
				'post_type'      => 'attachment',
				'name'           => $slug,
				'posts_per_page' => 1,
				'post_status'    => 'inherit',
			)
		);

		if ( empty( $attachments ) ) {
			return '';
		}

		return (string) wp_get_attachment_url( $attachments[0]->ID );
	}

	/**
	 * Download a remote image into the Media Library.
	 *
	 * Uses wp_safe_remote_get() (which refuses private/loopback hosts), and
	 * only accepts files whose extension WordPress maps to an image MIME
	 * type, so markup from the API can't be used to pull arbitrary files
	 * into the uploads directory.
	 *
	 * @param string $image_url Remote image URL.
	 * @return int Attachment ID, or 0 on failure.
	 */
	private function lfw_insert_media_from_url( $image_url ) {
		$filename = sanitize_file_name( wp_basename( (string) wp_parse_url( $image_url, PHP_URL_PATH ) ) );
		$filetype = wp_check_filetype( $filename );
		if ( '' === $filename || empty( $filetype['type'] ) || 0 !== strpos( $filetype['type'], 'image/' ) ) {
			return 0;
		}

		$response = wp_safe_remote_get( $image_url, array( 'timeout' => 30 ) );
		if ( is_wp_error( $response ) || 200 !== (int) wp_remote_retrieve_response_code( $response ) ) {
			return 0;
		}

		$upload_file = wp_upload_bits( $filename, null, wp_remote_retrieve_body( $response ) );
		if ( ! empty( $upload_file['error'] ) ) {
			return 0;
		}

		$attachment_id = wp_insert_attachment(
			array(
				'post_mime_type' => $filetype['type'],
				'post_title'     => preg_replace( '/\.[^.]+$/', '', $filename ),
				'post_content'   => '',
				'post_status'    => 'inherit',
			),
			$upload_file['file'],
			0,
			true
		);

		if ( is_wp_error( $attachment_id ) ) {
			return 0;
		}

		require_once ABSPATH . 'wp-admin/includes/image.php';
		wp_update_attachment_metadata( $attachment_id, wp_generate_attachment_metadata( $attachment_id, $upload_file['file'] ) );

		return $attachment_id;
	}
}

new Layouts_WPB_Importer();

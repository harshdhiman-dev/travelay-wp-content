<?php
/**
 * Class allows to upload third party image by URL to a WP Media Library
 */
// phpcs:ignoreFile
class DS_MediaProcessing {

	public function __construct() {
		// required to call outside theme (cron for example)
		require_once ABSPATH . 'wp-admin/includes/media.php';
		require_once ABSPATH . 'wp-admin/includes/file.php';

		// used for wp_generate_attachment_metadata()
		require_once ABSPATH . 'wp-admin/includes/image.php';
	}

	/**
	 * @required $image_url - external image url 'https://site.com/uploads/image.jpg'
	 * @required $post_id - WP Post id to attach the file
	 * @optional $image_title - optional image name, original filename by default
	 * @optional $featured - image can be used as post/page featured, 'false' by default
	 */
	public function upload( $image_url, $post_id = 0, $image_title = '', $featured = false ) {
		if ( $image_url == '' ) {
			return false;
		}

		$img_name = basename( $image_url );

		$query = array(
			'post_type'  => 'attachment',
			'fields'     => 'ids',
			'meta_query' => array(
				array(
					'key'     => '_wp_attached_file',
					'value'   => $img_name,
					'compare' => 'LIKE',
				),
			),
		);

		// try to find attachment by name
		$attachments = get_posts( $query );
		$attach_id   = $attachments[0];

		// prevent upload image if exists
		if ( empty( $attach_id ) ) {
			$attach_id = media_sideload_image( $image_url, $post_id, null, 'id' );

			// generate the metadata for the attachment, and update the database record.
			$attach_data = wp_generate_attachment_metadata( $attach_id, get_attached_file( $attach_id ) );
			wp_update_attachment_metadata( $attach_id, $attach_data );

			// set default image title
			$args = array(
				'ID'         => $attach_id,
				'post_title' => ( empty( $image_title ) ) ? $img_name : $image_title,
			);
			wp_update_post( $args );
		}

		if ( $post_id != 0 && $featured ) {
			set_post_thumbnail( $post_id, $attach_id );
		}

		return $attach_id;
	}
}

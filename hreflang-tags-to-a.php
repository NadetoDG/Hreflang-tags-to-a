<?php
/**
 * Plugin Name:       Add Hreflang tags to <a>
 * Description:       This is a short description of what the plugin does. It's displayed in the WordPress admin area.
 * Version:           1.0.0
 * Author:            ngeneva
 * License:           GPL-2.0+
 * License URI:       http://www.gnu.org/licenses/gpl-2.0.txt
 * Text Domain:       hreflang-tags-to-a
 * Domain Path:       /languages
 */

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
	die;
}

/**
 * Define plugin variables
 */
define( 'HRFLNG_VERSION', '1.0.0' );
define( 'HRFLNG_PLUGIN_DIR', dirname(__FILE__) . DIRECTORY_SEPARATOR );

/**
 * Add Hreflang meta box
 */
add_action( 'add_meta_boxes', 'hrflng_register_meta_boxes' );
function hrflng_register_meta_boxes() {
	add_meta_box( 'hrflng_tag_meta_box', __( 'Hreflang Information', 'hreflang-tags-to-a' ), 'hrflng_meta_boxe_callback', 'page' );
}

function hrflng_meta_boxe_callback( $post ) {
	include HRFLNG_PLUGIN_DIR . 'admin/meta-boxes/hreflang.php';
}

/**
 * Save Hreflang meta box
 */
add_action( 'save_post', 'hrflng_save_meta_boxes', 1, 10 );
function hrflng_save_meta_boxes( $post_id ) {
	if ( ! empty( $_POST['hrflng-page-hreflang'] ) ) {
		update_post_meta(
			$post_id,
			'_hrflng_page_hreflang',
			$_POST['hrflng-page-hreflang']
		);
	}
}

/**
 * Update page content links hreflang
 */
add_action( 'save_post', 'hrflng_add_content_links_hreflang' );
function hrflng_add_content_links_hreflang( $post_id ) {
	// Check to see if we are autosaving
	if ( defined('DOING_AUTOSAVE') && DOING_AUTOSAVE ) {
		return;
	}

	//Skip if the post is revision
	if ( wp_is_post_revision( $post_id ) ) {
		return;
	}

	//Skip if the post is trashed
	if ( get_post_status( $post_id ) === 'trash' ) {
		return;
	}

	$post_content = get_the_content( $post_id );

	//Get all content links
	preg_match_all( '/<a.*href="[^"]*".*\/a>/', $post_content, $links );

	if ( empty( $links[0] ) ) {
		return;
	}

	$home_url = parse_url( esc_url( home_url( '/' ) ) );

	$new_post_content = $post_content;

	foreach ( $links[0] as $link_html ) {
		preg_match('/<a.*href="([^"]*)".*\/a>/', $link_html, $link );

		$link_url = parse_url( $link[1] );

		// Check if link is internal
		if ( empty( $link_url['host'] ) || $link_url['host'] == $home_url['host'] ) {
			// Is an internal link
			$page_path = str_replace( home_url( '' ), '', $link[1] );

			$_page = get_page_by_path( $page_path, OBJECT, 'page' );

			if ( $_page ) {
				$_page_hrflng = get_post_meta( $_page->ID, '_hrflng_page_hreflang', true );

				$new_link = $link_html;

				//remove current hreflang
				$new_link = preg_replace( '/(hreflang=(\"|\').*?(\"|\'))/', '', $new_link );

				//add hreflang
				$new_link = preg_replace('/<a(.*href="[^"]*")(.*)\/a>/','<a$1 hreflang="' . $_page_hrflng . '" $2/a>', $new_link );

				$new_post_content = str_replace( $link_html, $new_link, $new_post_content );
			}
		}
	}

	// unhook this function so it doesn't loop infinitely
    remove_action( 'save_post', 'hrflng_add_content_links_hreflang' );

	wp_update_post( [
		"ID"           => $post_id,
		"post_content" => $new_post_content,
	] );

	// re-hook this function
    add_action( 'save_post', 'hrflng_add_content_links_hreflang' );
}

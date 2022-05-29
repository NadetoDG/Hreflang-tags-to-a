<?php
/**
 * The core plugin class.
 */
class Hreflang_Tag {
	/**
	 * The unique identifier of this plugin.
	 *
	 * @var      string    $plugin_name    The string used to uniquely identify this plugin.
	 */
	protected $plugin_name;

	/**
	 * The current version of the plugin.
	 *
	 * @var      string    $version    The current version of the plugin.
	 */
	protected $version;

	/**
	 * Define the core functionality of the plugin.
	 *
	 * Set the plugin name and the plugin version that can be used throughout the plugin.
	 * Load the dependencies, define the locale, and set the hooks for the admin area and
	 * the public-facing side of the site.
	 */
	public function __construct() {
		if ( defined( 'HRFLNG_VERSION' ) ) {
			$this->version = HRFLNG_VERSION;
		} else {
			$this->version = '1.0.0';
		}

		$this->plugin_name = 'hreflang-tags-to-a';
	}

	/**
	 * Run the loader to execute all of the hooks with WordPress.
	 */
	public function run() {
		$this->define_admin_hooks();
	}

	/**
	 * Register all of the hooks related to the admin area functionality
	 * of the plugin.
	 */
	private function define_admin_hooks() {
		add_action( 'add_meta_boxes', [ $this, 'register_meta_boxes' ] );
		add_action( 'save_post', [ $this, 'save_meta_boxes' ] );
		add_action( 'save_post', [ $this, 'add_content_links_hreflang' ] );
	}

	/**
	 * Add Hreflang meta box
	 */
	function register_meta_boxes() {
		add_meta_box( 'hrflng_tag_meta_box', __( 'Hreflang Information', 'hreflang-tags-to-a' ), [ $this, 'meta_boxes_callback' ], 'page' );
	}

	function meta_boxes_callback( $post ) {
		include HRFLNG_PLUGIN_DIR . 'admin/meta-boxes/hreflang.php';
	}

	/**
	 * Save Hreflang meta box
	 */
	function save_meta_boxes( $post_id ) {
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
	function add_content_links_hreflang( $post_id ) {
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
		remove_action( 'save_post', [ $this, 'add_content_links_hreflang' ] );

		wp_update_post( [
			"ID"           => $post_id,
			"post_content" => $new_post_content,
		] );

		// re-hook this function
		add_action( 'save_post', [ $this, 'add_content_links_hreflang' ] );
	}

	/**
	 * The name of the plugin used to uniquely identify it within the context of
	 * WordPress and to define internationalization functionality.
	 *
	 * @return    string    The name of the plugin.
	 */
	public function get_plugin_name() {
		return $this->plugin_name;
	}

	/**
	 * Retrieve the version number of the plugin.
	 *
	 * @return    string    The version number of the plugin.
	 */
	public function get_version() {
		return $this->version;
	}
}

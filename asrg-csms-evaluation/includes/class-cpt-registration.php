<?php
/**
 * Register the csms_tool Custom Post Type.
 *
 * Uses native WP post statuses (draft, pending, publish) for the
 * vendor submission workflow instead of a custom status meta field.
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class ASRG_CSMS_CPT_Registration {

    const POST_TYPE = 'csms_tool';

    /**
     * Register hooks.
     */
    public function init(): void {
        add_action( 'init', [ $this, 'register_post_type' ] );
    }

    /**
     * Register the csms_tool custom post type.
     */
    public function register_post_type(): void {
        $labels = [
            'name'                  => __( 'CSMS Tools', 'asrg-csms' ),
            'singular_name'        => __( 'CSMS Tool', 'asrg-csms' ),
            'menu_name'            => __( 'CSMS Tools', 'asrg-csms' ),
            'add_new'              => __( 'Add New Tool', 'asrg-csms' ),
            'add_new_item'         => __( 'Add New CSMS Tool', 'asrg-csms' ),
            'edit_item'            => __( 'Edit CSMS Tool', 'asrg-csms' ),
            'new_item'             => __( 'New CSMS Tool', 'asrg-csms' ),
            'view_item'            => __( 'View CSMS Tool', 'asrg-csms' ),
            'search_items'         => __( 'Search CSMS Tools', 'asrg-csms' ),
            'not_found'            => __( 'No tools found', 'asrg-csms' ),
            'not_found_in_trash'   => __( 'No tools found in Trash', 'asrg-csms' ),
            'all_items'            => __( 'All Tools', 'asrg-csms' ),
        ];

        $args = [
            'labels'              => $labels,
            'public'              => true,
            'publicly_queryable'  => true,
            'show_ui'             => true,
            'show_in_menu'        => true,
            'show_in_rest'        => true, // Gutenberg & REST API support
            'menu_position'       => 25,
            'menu_icon'           => 'dashicons-shield',
            'capability_type'     => 'csms_tool',
            'map_meta_cap'        => true,
            'has_archive'         => true,
            'rewrite'             => [ 'slug' => 'csms-tools', 'with_front' => false ],
            'supports'            => [ 'title', 'editor', 'thumbnail', 'author', 'revisions' ],
        ];

        register_post_type( self::POST_TYPE, $args );
    }

    /**
     * Helper: convert a sub-feature ID from framework JSON (hyphenated)
     * to an underscore-based meta key prefix.
     *
     * Example: 'rm-automated-tara' -> 'rm_automated_tara'
     */
    public static function to_meta_key( string $sub_feature_id ): string {
        return str_replace( '-', '_', $sub_feature_id );
    }
}

<?php
/**
 * One-time migration from custom tables to csms_tool CPT.
 *
 * Migrates data from:
 *   wp_csms_tools       → csms_tool posts + tool-level meta
 *   wp_csms_tool_scores → sub-feature meta fields on posts
 *   wp_csms_feedback_votes → tool_id column updated to new post IDs
 *
 * Run via WP-CLI:   wp eval 'ASRG_CSMS_Migrate_To_CPT::run();'
 * Or via admin page: Tools → CSMS Migration (if admin UI is added)
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class ASRG_CSMS_Migrate_To_CPT {

    /**
     * Run the full migration.
     *
     * @return array Summary of migration results.
     */
    public static function run(): array {
        global $wpdb;

        $results = [
            'tools_migrated' => 0,
            'scores_migrated' => 0,
            'votes_updated' => 0,
            'errors' => [],
        ];

        $tools_table  = $wpdb->prefix . 'csms_tools';
        $scores_table = $wpdb->prefix . 'csms_tool_scores';
        $votes_table  = $wpdb->prefix . 'csms_feedback_votes';

        // Check if old tables exist.
        $tools_exists = $wpdb->get_var( "SHOW TABLES LIKE '{$tools_table}'" );
        if ( ! $tools_exists ) {
            $results['errors'][] = "Old tools table ({$tools_table}) not found. Nothing to migrate.";
            self::log_results( $results );
            return $results;
        }

        // Fetch all tools from old table.
        $tools = $wpdb->get_results( "SELECT * FROM {$tools_table}", ARRAY_A );

        if ( empty( $tools ) ) {
            $results['errors'][] = 'No tools found in old table.';
            self::log_results( $results );
            return $results;
        }

        // Mapping: old tool_id => new post_id.
        $id_map = [];

        foreach ( $tools as $tool ) {
            $old_id = (int) $tool['id'];

            // Map old status to WP post status.
            $status_map = [
                'draft'          => 'draft',
                'pending_review' => 'pending',
                'published'      => 'publish',
                'archived'       => 'draft',
            ];
            $post_status = $status_map[ $tool['status'] ] ?? 'draft';

            // Create the post.
            $post_data = [
                'post_type'    => 'csms_tool',
                'post_title'   => $tool['name'],
                'post_name'    => $tool['slug'],
                'post_content' => $tool['description'] ?? '',
                'post_status'  => $post_status,
                'post_author'  => $tool['submitted_by'] ?: 1,
                'post_date'    => $tool['created_at'] ?: current_time( 'mysql' ),
            ];

            $post_id = wp_insert_post( $post_data, true );

            if ( is_wp_error( $post_id ) ) {
                $results['errors'][] = "Failed to create post for tool '{$tool['name']}': " . $post_id->get_error_message();
                continue;
            }

            $id_map[ $old_id ] = $post_id;

            // Set tool-level meta.
            update_post_meta( $post_id, 'vendor_name', $tool['vendor'] ?? '' );
            update_post_meta( $post_id, 'website_url', $tool['website'] ?? '' );
            update_post_meta( $post_id, 'is_sponsor', (bool) ( $tool['is_sponsor'] ?? 0 ) );
            update_post_meta( $post_id, 'sponsor_tier', $tool['sponsor_tier'] ?? '' );

            // Handle logo: download and set as featured image if URL exists.
            if ( ! empty( $tool['logo_url'] ) ) {
                self::set_featured_image_from_url( $post_id, $tool['logo_url'], $tool['name'] );
            }

            // Migrate scores for this tool.
            $scores = $wpdb->get_results(
                $wpdb->prepare(
                    "SELECT sub_feature_id, rating, rationale, evidence_url FROM {$scores_table} WHERE tool_id = %d",
                    $old_id
                ),
                ARRAY_A
            );

            foreach ( $scores as $score ) {
                $meta_prefix = str_replace( '-', '_', $score['sub_feature_id'] );
                update_post_meta( $post_id, $meta_prefix . '_rating', $score['rating'] );
                update_post_meta( $post_id, $meta_prefix . '_rationale', $score['rationale'] ?? '' );
                update_post_meta( $post_id, $meta_prefix . '_evidence_url', $score['evidence_url'] ?? '' );
                $results['scores_migrated']++;
            }

            // Compute and store scores.
            ASRG_CSMS_Scoring_Engine::compute_and_store( $post_id );

            $results['tools_migrated']++;
        }

        // Update feedback_votes table: replace old tool_id with new post_id.
        foreach ( $id_map as $old_id => $new_id ) {
            $updated = $wpdb->update(
                $votes_table,
                [ 'tool_id' => $new_id ],
                [ 'tool_id' => $old_id ],
                [ '%d' ],
                [ '%d' ]
            );
            $results['votes_updated'] += ( $updated !== false ) ? $updated : 0;
        }

        // Store the ID map for reference.
        update_option( 'asrg_csms_migration_id_map', $id_map );
        update_option( 'asrg_csms_migration_completed', current_time( 'mysql' ) );

        self::log_results( $results );

        return $results;
    }

    /**
     * Download an image from URL and set as post featured image.
     */
    private static function set_featured_image_from_url( int $post_id, string $url, string $title ): void {
        if ( ! function_exists( 'media_sideload_image' ) ) {
            require_once ABSPATH . 'wp-admin/includes/media.php';
            require_once ABSPATH . 'wp-admin/includes/file.php';
            require_once ABSPATH . 'wp-admin/includes/image.php';
        }

        $attachment_id = media_sideload_image( $url, $post_id, $title . ' Logo', 'id' );

        if ( ! is_wp_error( $attachment_id ) ) {
            set_post_thumbnail( $post_id, $attachment_id );
        }
    }

    /**
     * Log migration results.
     */
    private static function log_results( array $results ): void {
        $msg = sprintf(
            '[CSMS Migration] Tools: %d, Scores: %d, Votes updated: %d, Errors: %d',
            $results['tools_migrated'],
            $results['scores_migrated'],
            $results['votes_updated'],
            count( $results['errors'] )
        );

        error_log( $msg );

        if ( ! empty( $results['errors'] ) ) {
            foreach ( $results['errors'] as $err ) {
                error_log( '[CSMS Migration Error] ' . $err );
            }
        }

        // Also output to CLI if running via WP-CLI.
        if ( defined( 'WP_CLI' ) && WP_CLI ) {
            \WP_CLI::success( $msg );
            foreach ( $results['errors'] as $err ) {
                \WP_CLI::warning( $err );
            }
        }
    }

    /**
     * Drop old tables after migration verification.
     * Only call this after confirming migration was successful!
     */
    public static function drop_old_tables(): void {
        global $wpdb;

        $tables = [
            $wpdb->prefix . 'csms_tools',
            $wpdb->prefix . 'csms_tool_scores',
            $wpdb->prefix . 'csms_feedback_comments',
        ];

        foreach ( $tables as $table ) {
            $wpdb->query( "DROP TABLE IF EXISTS {$table}" );
        }

        if ( defined( 'WP_CLI' ) && WP_CLI ) {
            \WP_CLI::success( 'Old CSMS tables dropped.' );
        }
    }
}

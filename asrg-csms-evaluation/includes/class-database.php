<?php
/**
 * Database table creation and management.
 *
 * Only creates the feedback_votes table. The tools and tool_scores
 * tables have been replaced by the csms_tool CPT with JetEngine meta fields.
 * The feedback_comments table has been replaced by JetReviews.
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class ASRG_CSMS_Database {

    /**
     * Create custom tables on plugin activation.
     */
    public static function create_tables(): void {
        global $wpdb;

        $charset_collate = $wpdb->get_charset_collate();

        $sql = [];

        // Feedback votes table — per-sub-feature agree/disagree voting.
        // tool_id references a csms_tool CPT post ID.
        $table_votes = $wpdb->prefix . 'csms_feedback_votes';
        $sql[] = "CREATE TABLE {$table_votes} (
            id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
            user_id bigint(20) unsigned NOT NULL,
            tool_id bigint(20) unsigned NOT NULL,
            sub_feature_id varchar(100) NOT NULL,
            vote_type varchar(10) NOT NULL,
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            UNIQUE KEY unique_user_vote (user_id, tool_id, sub_feature_id),
            KEY tool_feature (tool_id, sub_feature_id)
        ) {$charset_collate};";

        require_once ABSPATH . 'wp-admin/includes/upgrade.php';

        foreach ( $sql as $query ) {
            dbDelta( $query );
        }

        update_option( 'asrg_csms_db_version', ASRG_CSMS_VERSION );
    }

    /**
     * Get table name with prefix.
     */
    public static function table( string $name ): string {
        global $wpdb;
        return $wpdb->prefix . 'csms_' . $name;
    }
}

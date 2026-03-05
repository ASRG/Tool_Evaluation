<?php
/**
 * Database table creation and management.
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

        // Tools table.
        $table_tools = $wpdb->prefix . 'csms_tools';
        $sql[] = "CREATE TABLE {$table_tools} (
            id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
            slug varchar(100) NOT NULL,
            name varchar(255) NOT NULL,
            vendor varchar(255) NOT NULL,
            website varchar(500) DEFAULT '',
            logo_url varchar(500) DEFAULT '',
            description text DEFAULT '',
            is_sponsor tinyint(1) DEFAULT 0,
            sponsor_tier varchar(50) DEFAULT '',
            status varchar(20) DEFAULT 'draft',
            submitted_by bigint(20) unsigned DEFAULT NULL,
            approved_by bigint(20) unsigned DEFAULT NULL,
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            updated_at datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            UNIQUE KEY slug (slug),
            KEY status (status),
            KEY submitted_by (submitted_by)
        ) {$charset_collate};";

        // Tool scores table.
        $table_scores = $wpdb->prefix . 'csms_tool_scores';
        $sql[] = "CREATE TABLE {$table_scores} (
            id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
            tool_id bigint(20) unsigned NOT NULL,
            sub_feature_id varchar(100) NOT NULL,
            rating varchar(30) NOT NULL,
            rationale text DEFAULT '',
            evidence_url varchar(500) DEFAULT '',
            last_reviewed datetime DEFAULT NULL,
            PRIMARY KEY (id),
            UNIQUE KEY unique_tool_feature (tool_id, sub_feature_id),
            KEY tool_id (tool_id)
        ) {$charset_collate};";

        // Feedback votes table.
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

        // Feedback comments table.
        $table_comments = $wpdb->prefix . 'csms_feedback_comments';
        $sql[] = "CREATE TABLE {$table_comments} (
            id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
            user_id bigint(20) unsigned NOT NULL,
            tool_id bigint(20) unsigned NOT NULL,
            sub_feature_id varchar(100) NOT NULL,
            body text NOT NULL,
            parent_id bigint(20) unsigned DEFAULT NULL,
            is_deleted tinyint(1) DEFAULT 0,
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            updated_at datetime DEFAULT NULL,
            PRIMARY KEY (id),
            KEY tool_feature (tool_id, sub_feature_id),
            KEY parent_id (parent_id),
            KEY user_id (user_id)
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

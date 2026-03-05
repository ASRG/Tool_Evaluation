<?php
/**
 * Score computation hooks.
 *
 * Triggers score recalculation on tool save and via WP-Cron
 * for periodic updates (community vote changes).
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class ASRG_CSMS_Score_Hooks {

    const CRON_HOOK     = 'csms_recompute_scores';
    const CRON_INTERVAL = 'csms_fifteen_minutes';

    /**
     * Register hooks.
     */
    public function init(): void {
        // Recompute scores when a csms_tool post is saved.
        add_action( 'save_post_csms_tool', [ $this, 'on_tool_save' ], 20, 2 );

        // Register custom cron interval.
        add_filter( 'cron_schedules', [ $this, 'add_cron_interval' ] );

        // Schedule cron if not already scheduled.
        if ( ! wp_next_scheduled( self::CRON_HOOK ) ) {
            wp_schedule_event( time(), self::CRON_INTERVAL, self::CRON_HOOK );
        }

        // Cron callback.
        add_action( self::CRON_HOOK, [ $this, 'recompute_all_scores' ] );
    }

    /**
     * Recompute scores when a tool post is saved/updated.
     */
    public function on_tool_save( int $post_id, \WP_Post $post ): void {
        // Skip autosaves and revisions.
        if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
            return;
        }

        if ( wp_is_post_revision( $post_id ) ) {
            return;
        }

        // Only compute for published posts (or pending for preview).
        if ( ! in_array( $post->post_status, [ 'publish', 'pending' ], true ) ) {
            return;
        }

        ASRG_CSMS_Scoring_Engine::compute_and_store( $post_id );
    }

    /**
     * Recompute scores for all published csms_tool posts.
     * Called by WP-Cron every 15 minutes to pick up vote changes.
     */
    public function recompute_all_scores(): void {
        $tools = get_posts( [
            'post_type'      => 'csms_tool',
            'post_status'    => 'publish',
            'posts_per_page' => -1,
            'fields'         => 'ids',
        ] );

        foreach ( $tools as $post_id ) {
            ASRG_CSMS_Scoring_Engine::compute_and_store( $post_id );
        }
    }

    /**
     * Add custom cron interval (15 minutes).
     */
    public function add_cron_interval( array $schedules ): array {
        $schedules[ self::CRON_INTERVAL ] = [
            'interval' => 15 * MINUTE_IN_SECONDS,
            'display'  => __( 'Every 15 Minutes', 'asrg-csms' ),
        ];

        return $schedules;
    }

    /**
     * Unschedule cron on plugin deactivation.
     */
    public static function deactivate(): void {
        $timestamp = wp_next_scheduled( self::CRON_HOOK );
        if ( $timestamp ) {
            wp_unschedule_event( $timestamp, self::CRON_HOOK );
        }
    }
}

<?php
/**
 * AJAX handler for per-sub-feature agree/disagree voting.
 *
 * Provides REST API endpoints for the lightweight voting system.
 * JetReviews handles tool-level reviews separately.
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class ASRG_CSMS_Vote_Handler {

    const NAMESPACE = 'csms/v1';

    /**
     * Register hooks.
     */
    public function init(): void {
        add_action( 'rest_api_init', [ $this, 'register_routes' ] );
    }

    /**
     * Register REST routes for voting.
     */
    public function register_routes(): void {
        // Public: get vote counts for a tool x sub-feature.
        register_rest_route( self::NAMESPACE, '/feedback/(?P<tool_id>\d+)/(?P<sub_feature_id>[a-z0-9-]+)', [
            'methods'             => 'GET',
            'callback'            => [ $this, 'get_votes' ],
            'permission_callback' => '__return_true',
            'args'                => [
                'tool_id'        => [ 'required' => true, 'type' => 'integer' ],
                'sub_feature_id' => [ 'required' => true, 'type' => 'string' ],
            ],
        ] );

        // Authenticated: submit or update a vote.
        register_rest_route( self::NAMESPACE, '/feedback/vote', [
            'methods'             => 'POST',
            'callback'            => [ $this, 'submit_vote' ],
            'permission_callback' => [ $this, 'can_vote' ],
            'args'                => [
                'toolId'       => [ 'required' => true, 'type' => 'integer' ],
                'subFeatureId' => [ 'required' => true, 'type' => 'string', 'sanitize_callback' => 'sanitize_text_field' ],
                'voteType'     => [ 'required' => true, 'type' => 'string', 'enum' => [ 'agree', 'disagree' ] ],
            ],
        ] );

        // Authenticated: delete own vote.
        register_rest_route( self::NAMESPACE, '/feedback/vote/(?P<id>\d+)', [
            'methods'             => 'DELETE',
            'callback'            => [ $this, 'delete_vote' ],
            'permission_callback' => [ $this, 'can_vote' ],
        ] );

        // Public: serve the evaluation framework JSON.
        register_rest_route( self::NAMESPACE, '/framework', [
            'methods'             => 'GET',
            'callback'            => [ $this, 'get_framework' ],
            'permission_callback' => '__return_true',
        ] );
    }

    /**
     * GET /feedback/{tool_id}/{sub_feature_id} — vote counts.
     */
    public function get_votes( \WP_REST_Request $request ): \WP_REST_Response {
        global $wpdb;

        $tool_id        = (int) $request->get_param( 'tool_id' );
        $sub_feature_id = sanitize_text_field( $request->get_param( 'sub_feature_id' ) );

        $table = ASRG_CSMS_Database::table( 'feedback_votes' );

        // Vote counts.
        $vote_counts = $wpdb->get_results(
            $wpdb->prepare(
                "SELECT vote_type, COUNT(*) as cnt FROM {$table}
                 WHERE tool_id = %d AND sub_feature_id = %s
                 GROUP BY vote_type",
                $tool_id,
                $sub_feature_id
            ),
            ARRAY_A
        );

        $votes = [ 'agree' => 0, 'disagree' => 0, 'userVote' => null ];
        foreach ( $vote_counts as $row ) {
            $votes[ $row['vote_type'] ] = (int) $row['cnt'];
        }

        // Current user's vote.
        if ( is_user_logged_in() ) {
            $user_vote = $wpdb->get_var(
                $wpdb->prepare(
                    "SELECT vote_type FROM {$table}
                     WHERE user_id = %d AND tool_id = %d AND sub_feature_id = %s",
                    get_current_user_id(),
                    $tool_id,
                    $sub_feature_id
                )
            );
            $votes['userVote'] = $user_vote;
        }

        return new \WP_REST_Response( [ 'votes' => $votes ], 200 );
    }

    /**
     * POST /feedback/vote — submit or update a vote.
     */
    public function submit_vote( \WP_REST_Request $request ): \WP_REST_Response {
        global $wpdb;

        $user_id        = get_current_user_id();
        $tool_id        = (int) $request->get_param( 'toolId' );
        $sub_feature_id = $request->get_param( 'subFeatureId' );
        $vote_type      = $request->get_param( 'voteType' );

        if ( ! in_array( $vote_type, [ 'agree', 'disagree' ], true ) ) {
            return new \WP_REST_Response( [ 'error' => 'Invalid vote type' ], 400 );
        }

        $table = ASRG_CSMS_Database::table( 'feedback_votes' );

        // Upsert: replace existing vote if any.
        $existing = $wpdb->get_var(
            $wpdb->prepare(
                "SELECT id FROM {$table} WHERE user_id = %d AND tool_id = %d AND sub_feature_id = %s",
                $user_id,
                $tool_id,
                $sub_feature_id
            )
        );

        if ( $existing ) {
            $wpdb->update(
                $table,
                [ 'vote_type' => $vote_type, 'created_at' => current_time( 'mysql' ) ],
                [ 'id' => $existing ]
            );
        } else {
            $wpdb->insert( $table, [
                'user_id'        => $user_id,
                'tool_id'        => $tool_id,
                'sub_feature_id' => $sub_feature_id,
                'vote_type'      => $vote_type,
                'created_at'     => current_time( 'mysql' ),
            ] );
        }

        return new \WP_REST_Response( [ 'success' => true ], 200 );
    }

    /**
     * DELETE /feedback/vote/{id} — delete own vote.
     */
    public function delete_vote( \WP_REST_Request $request ): \WP_REST_Response {
        global $wpdb;

        $vote_id = (int) $request->get_param( 'id' );
        $table   = ASRG_CSMS_Database::table( 'feedback_votes' );

        // Verify ownership.
        $owner = $wpdb->get_var(
            $wpdb->prepare( "SELECT user_id FROM {$table} WHERE id = %d", $vote_id )
        );

        if ( ! $owner || (int) $owner !== get_current_user_id() ) {
            return new \WP_REST_Response( [ 'error' => 'Not found or not authorized' ], 403 );
        }

        $wpdb->delete( $table, [ 'id' => $vote_id ] );

        return new \WP_REST_Response( null, 204 );
    }

    /**
     * GET /framework — serve the evaluation framework JSON.
     */
    public function get_framework(): \WP_REST_Response {
        $framework = ASRG_CSMS_Scoring_Engine::get_framework();
        return new \WP_REST_Response( $framework, 200 );
    }

    /**
     * Permission callback: can the current user vote?
     */
    public function can_vote(): bool {
        return current_user_can( 'csms_vote' );
    }
}

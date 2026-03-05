<?php
/**
 * REST API endpoints for computed scores (public read access).
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class ASRG_CSMS_Scores_Endpoint {

    const NAMESPACE = 'csms/v1';

    public function register_routes(): void {
        register_rest_route( self::NAMESPACE, '/scores', [
            'methods'             => 'GET',
            'callback'            => [ $this, 'get_all_scores' ],
            'permission_callback' => '__return_true',
        ] );

        register_rest_route( self::NAMESPACE, '/scores/(?P<slug>[a-z0-9-]+)', [
            'methods'             => 'GET',
            'callback'            => [ $this, 'get_tool_scores' ],
            'permission_callback' => '__return_true',
            'args'                => [
                'slug' => [
                    'required'          => true,
                    'sanitize_callback' => 'sanitize_title',
                ],
            ],
        ] );
    }

    /**
     * GET /scores — computed scores for all published tools.
     */
    public function get_all_scores(): \WP_REST_Response {
        global $wpdb;

        $table = ASRG_CSMS_Database::table( 'tools' );
        $tools = $wpdb->get_results(
            "SELECT id, slug, name FROM {$table} WHERE status = 'published'",
            ARRAY_A
        );

        $results = [];
        foreach ( $tools as $tool ) {
            $computed = ASRG_CSMS_Scoring_Engine::compute_tool_score( (int) $tool['id'] );
            $computed['slug'] = $tool['slug'];
            $computed['name'] = $tool['name'];
            $results[]        = $computed;
        }

        return new \WP_REST_Response( $results, 200 );
    }

    /**
     * GET /scores/{slug} — computed scores for a single tool.
     */
    public function get_tool_scores( \WP_REST_Request $request ): \WP_REST_Response {
        global $wpdb;

        $slug  = $request->get_param( 'slug' );
        $table = ASRG_CSMS_Database::table( 'tools' );

        $tool = $wpdb->get_row(
            $wpdb->prepare(
                "SELECT id, slug, name FROM {$table} WHERE slug = %s AND status = 'published'",
                $slug
            ),
            ARRAY_A
        );

        if ( ! $tool ) {
            return new \WP_REST_Response( [ 'error' => 'Tool not found' ], 404 );
        }

        $computed         = ASRG_CSMS_Scoring_Engine::compute_tool_score( (int) $tool['id'] );
        $computed['slug'] = $tool['slug'];
        $computed['name'] = $tool['name'];

        return new \WP_REST_Response( $computed, 200 );
    }
}

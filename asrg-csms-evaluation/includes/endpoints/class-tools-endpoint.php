<?php
/**
 * REST API endpoints for tools (public read access).
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class ASRG_CSMS_Tools_Endpoint {

    const NAMESPACE = 'csms/v1';

    public function register_routes(): void {
        register_rest_route( self::NAMESPACE, '/framework', [
            'methods'             => 'GET',
            'callback'            => [ $this, 'get_framework' ],
            'permission_callback' => '__return_true',
        ] );

        register_rest_route( self::NAMESPACE, '/tools', [
            'methods'             => 'GET',
            'callback'            => [ $this, 'get_tools' ],
            'permission_callback' => '__return_true',
        ] );

        register_rest_route( self::NAMESPACE, '/tools/(?P<slug>[a-z0-9-]+)', [
            'methods'             => 'GET',
            'callback'            => [ $this, 'get_tool' ],
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
     * GET /framework — return the evaluation framework JSON.
     */
    public function get_framework(): \WP_REST_Response {
        $framework = ASRG_CSMS_Scoring_Engine::get_framework();
        return new \WP_REST_Response( $framework, 200 );
    }

    /**
     * GET /tools — return all published tools with their scores.
     */
    public function get_tools(): \WP_REST_Response {
        global $wpdb;

        $table = ASRG_CSMS_Database::table( 'tools' );

        $tools = $wpdb->get_results(
            "SELECT id, slug, name, vendor, website, logo_url, description,
                    is_sponsor, sponsor_tier, status, created_at, updated_at
             FROM {$table}
             WHERE status = 'published'
             ORDER BY name ASC",
            ARRAY_A
        );

        // Attach scores for each tool.
        $scores_table = ASRG_CSMS_Database::table( 'tool_scores' );

        foreach ( $tools as &$tool ) {
            $tool['id']        = (int) $tool['id'];
            $tool['isSponsor'] = (bool) $tool['is_sponsor'];

            $tool['scores'] = $wpdb->get_results(
                $wpdb->prepare(
                    "SELECT sub_feature_id AS subFeatureId, rating, rationale, evidence_url AS evidenceUrl
                     FROM {$scores_table}
                     WHERE tool_id = %d",
                    $tool['id']
                ),
                ARRAY_A
            );
        }

        return new \WP_REST_Response( $tools, 200 );
    }

    /**
     * GET /tools/{slug} — return a single tool with full data.
     */
    public function get_tool( \WP_REST_Request $request ): \WP_REST_Response {
        global $wpdb;

        $slug  = $request->get_param( 'slug' );
        $table = ASRG_CSMS_Database::table( 'tools' );

        $tool = $wpdb->get_row(
            $wpdb->prepare(
                "SELECT id, slug, name, vendor, website, logo_url, description,
                        is_sponsor, sponsor_tier, status, submitted_by, approved_by,
                        created_at, updated_at
                 FROM {$table}
                 WHERE slug = %s AND status = 'published'",
                $slug
            ),
            ARRAY_A
        );

        if ( ! $tool ) {
            return new \WP_REST_Response( [ 'error' => 'Tool not found' ], 404 );
        }

        $tool['id']        = (int) $tool['id'];
        $tool['isSponsor'] = (bool) $tool['is_sponsor'];

        // Attach scores.
        $scores_table = ASRG_CSMS_Database::table( 'tool_scores' );
        $tool['scores'] = $wpdb->get_results(
            $wpdb->prepare(
                "SELECT sub_feature_id AS subFeatureId, rating, rationale, evidence_url AS evidenceUrl, last_reviewed AS lastReviewed
                 FROM {$scores_table}
                 WHERE tool_id = %d",
                $tool['id']
            ),
            ARRAY_A
        );

        // Attach computed scores.
        $tool['computedScores'] = ASRG_CSMS_Scoring_Engine::compute_tool_score( $tool['id'] );

        return new \WP_REST_Response( $tool, 200 );
    }
}

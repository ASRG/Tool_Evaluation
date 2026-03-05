<?php
/**
 * REST API endpoints for vendor self-service (submit/update tool evaluations).
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class ASRG_CSMS_Vendor_Endpoint {

    const NAMESPACE = 'csms/v1';

    public function register_routes(): void {
        // Vendor: get own tools.
        register_rest_route( self::NAMESPACE, '/vendor/tools/mine', [
            'methods'             => 'GET',
            'callback'            => [ $this, 'get_my_tools' ],
            'permission_callback' => [ $this, 'can_submit' ],
        ] );

        // Vendor: submit new tool.
        register_rest_route( self::NAMESPACE, '/vendor/tools', [
            'methods'             => 'POST',
            'callback'            => [ $this, 'submit_tool' ],
            'permission_callback' => [ $this, 'can_submit' ],
        ] );

        // Vendor: update own tool.
        register_rest_route( self::NAMESPACE, '/vendor/tools/(?P<id>\d+)', [
            'methods'             => 'PUT',
            'callback'            => [ $this, 'update_tool' ],
            'permission_callback' => [ $this, 'can_submit' ],
        ] );

        // Editor: approve tool.
        register_rest_route( self::NAMESPACE, '/editor/tools/(?P<id>\d+)/approve', [
            'methods'             => 'PUT',
            'callback'            => [ $this, 'approve_tool' ],
            'permission_callback' => [ $this, 'can_approve' ],
        ] );

        // Editor: reject tool.
        register_rest_route( self::NAMESPACE, '/editor/tools/(?P<id>\d+)/reject', [
            'methods'             => 'PUT',
            'callback'            => [ $this, 'reject_tool' ],
            'permission_callback' => [ $this, 'can_approve' ],
        ] );

        // Editor: override a score.
        register_rest_route( self::NAMESPACE, '/editor/tools/(?P<id>\d+)/scores', [
            'methods'             => 'PUT',
            'callback'            => [ $this, 'override_scores' ],
            'permission_callback' => [ $this, 'can_override' ],
        ] );
    }

    /**
     * GET /vendor/tools/mine — list tools submitted by the current vendor.
     */
    public function get_my_tools(): \WP_REST_Response {
        global $wpdb;

        $table = ASRG_CSMS_Database::table( 'tools' );
        $tools = $wpdb->get_results(
            $wpdb->prepare(
                "SELECT id, slug, name, vendor, status, created_at, updated_at
                 FROM {$table}
                 WHERE submitted_by = %d
                 ORDER BY updated_at DESC",
                get_current_user_id()
            ),
            ARRAY_A
        );

        foreach ( $tools as &$tool ) {
            $tool['id'] = (int) $tool['id'];
        }

        return new \WP_REST_Response( $tools, 200 );
    }

    /**
     * POST /vendor/tools — submit a new tool for review.
     */
    public function submit_tool( \WP_REST_Request $request ): \WP_REST_Response {
        global $wpdb;

        $data = $request->get_json_params();

        // Validate required fields.
        $required = [ 'name', 'vendor', 'scores' ];
        foreach ( $required as $field ) {
            if ( empty( $data[ $field ] ) ) {
                return new \WP_REST_Response( [ 'error' => "Missing required field: {$field}" ], 400 );
            }
        }

        $slug = sanitize_title( $data['name'] );
        $table = ASRG_CSMS_Database::table( 'tools' );

        // Check for duplicate slug.
        $existing = $wpdb->get_var(
            $wpdb->prepare( "SELECT id FROM {$table} WHERE slug = %s", $slug )
        );

        if ( $existing ) {
            return new \WP_REST_Response( [ 'error' => 'A tool with this name already exists' ], 409 );
        }

        $wpdb->insert( $table, [
            'slug'         => $slug,
            'name'         => sanitize_text_field( $data['name'] ),
            'vendor'       => sanitize_text_field( $data['vendor'] ),
            'website'      => esc_url_raw( $data['website'] ?? '' ),
            'logo_url'     => esc_url_raw( $data['logoUrl'] ?? '' ),
            'description'  => sanitize_textarea_field( $data['description'] ?? '' ),
            'is_sponsor'   => ! empty( $data['isSponsor'] ) ? 1 : 0,
            'sponsor_tier' => sanitize_text_field( $data['sponsorTier'] ?? '' ),
            'status'       => 'pending_review',
            'submitted_by' => get_current_user_id(),
            'created_at'   => current_time( 'mysql' ),
            'updated_at'   => current_time( 'mysql' ),
        ] );

        $tool_id = $wpdb->insert_id;

        // Insert scores.
        $this->save_scores( $tool_id, $data['scores'] );

        return new \WP_REST_Response( [
            'id'     => $tool_id,
            'slug'   => $slug,
            'status' => 'pending_review',
        ], 201 );
    }

    /**
     * PUT /vendor/tools/{id} — update own tool (creates pending version).
     */
    public function update_tool( \WP_REST_Request $request ): \WP_REST_Response {
        global $wpdb;

        $tool_id = (int) $request->get_param( 'id' );
        $data    = $request->get_json_params();
        $table   = ASRG_CSMS_Database::table( 'tools' );

        // Verify ownership.
        $tool = $wpdb->get_row(
            $wpdb->prepare( "SELECT submitted_by, status FROM {$table} WHERE id = %d", $tool_id ),
            ARRAY_A
        );

        if ( ! $tool || (int) $tool['submitted_by'] !== get_current_user_id() ) {
            return new \WP_REST_Response( [ 'error' => 'Not found or not authorized' ], 403 );
        }

        // Update tool metadata.
        $update_data = [ 'updated_at' => current_time( 'mysql' ), 'status' => 'pending_review' ];

        $allowed_fields = [
            'name'   => 'sanitize_text_field',
            'vendor' => 'sanitize_text_field',
        ];

        foreach ( $allowed_fields as $field => $sanitizer ) {
            if ( isset( $data[ $field ] ) ) {
                $update_data[ $field ] = call_user_func( $sanitizer, $data[ $field ] );
            }
        }

        if ( isset( $data['website'] ) ) {
            $update_data['website'] = esc_url_raw( $data['website'] );
        }
        if ( isset( $data['logoUrl'] ) ) {
            $update_data['logo_url'] = esc_url_raw( $data['logoUrl'] );
        }
        if ( isset( $data['description'] ) ) {
            $update_data['description'] = sanitize_textarea_field( $data['description'] );
        }

        $wpdb->update( $table, $update_data, [ 'id' => $tool_id ] );

        // Update scores if provided.
        if ( ! empty( $data['scores'] ) ) {
            $this->save_scores( $tool_id, $data['scores'] );
        }

        return new \WP_REST_Response( [
            'id'     => $tool_id,
            'status' => 'pending_review',
        ], 200 );
    }

    /**
     * PUT /editor/tools/{id}/approve
     */
    public function approve_tool( \WP_REST_Request $request ): \WP_REST_Response {
        global $wpdb;

        $tool_id = (int) $request->get_param( 'id' );
        $table   = ASRG_CSMS_Database::table( 'tools' );

        $result = $wpdb->update( $table, [
            'status'      => 'published',
            'approved_by' => get_current_user_id(),
            'updated_at'  => current_time( 'mysql' ),
        ], [ 'id' => $tool_id ] );

        if ( false === $result ) {
            return new \WP_REST_Response( [ 'error' => 'Failed to approve' ], 500 );
        }

        return new \WP_REST_Response( [ 'id' => $tool_id, 'status' => 'published' ], 200 );
    }

    /**
     * PUT /editor/tools/{id}/reject
     */
    public function reject_tool( \WP_REST_Request $request ): \WP_REST_Response {
        global $wpdb;

        $tool_id = (int) $request->get_param( 'id' );
        $data    = $request->get_json_params();
        $table   = ASRG_CSMS_Database::table( 'tools' );

        $wpdb->update( $table, [
            'status'     => 'draft',
            'updated_at' => current_time( 'mysql' ),
        ], [ 'id' => $tool_id ] );

        return new \WP_REST_Response( [
            'id'       => $tool_id,
            'status'   => 'draft',
            'feedback' => sanitize_textarea_field( $data['feedback'] ?? '' ),
        ], 200 );
    }

    /**
     * PUT /editor/tools/{id}/scores — editor overrides scores.
     */
    public function override_scores( \WP_REST_Request $request ): \WP_REST_Response {
        $tool_id = (int) $request->get_param( 'id' );
        $data    = $request->get_json_params();

        if ( empty( $data['scores'] ) || ! is_array( $data['scores'] ) ) {
            return new \WP_REST_Response( [ 'error' => 'Scores array required' ], 400 );
        }

        $this->save_scores( $tool_id, $data['scores'] );

        return new \WP_REST_Response( [ 'id' => $tool_id, 'success' => true ], 200 );
    }

    /**
     * Save sub-feature scores for a tool (upsert).
     */
    private function save_scores( int $tool_id, array $scores ): void {
        global $wpdb;

        $table         = ASRG_CSMS_Database::table( 'tool_scores' );
        $valid_ratings = [ 'fully_fulfills', 'partially_fulfills', 'does_not_fulfill' ];

        foreach ( $scores as $score ) {
            if ( empty( $score['subFeatureId'] ) || empty( $score['rating'] ) ) {
                continue;
            }

            if ( ! in_array( $score['rating'], $valid_ratings, true ) ) {
                continue;
            }

            $row = [
                'tool_id'        => $tool_id,
                'sub_feature_id' => sanitize_text_field( $score['subFeatureId'] ),
                'rating'         => $score['rating'],
                'rationale'      => sanitize_textarea_field( $score['rationale'] ?? '' ),
                'evidence_url'   => esc_url_raw( $score['evidenceUrl'] ?? '' ),
                'last_reviewed'  => current_time( 'mysql' ),
            ];

            // Upsert via REPLACE.
            $existing = $wpdb->get_var(
                $wpdb->prepare(
                    "SELECT id FROM {$table} WHERE tool_id = %d AND sub_feature_id = %s",
                    $tool_id,
                    $row['sub_feature_id']
                )
            );

            if ( $existing ) {
                $wpdb->update( $table, $row, [ 'id' => $existing ] );
            } else {
                $wpdb->insert( $table, $row );
            }
        }
    }

    public function can_submit(): bool {
        return current_user_can( 'csms_submit_tool' );
    }

    public function can_approve(): bool {
        return current_user_can( 'csms_approve_tool' );
    }

    public function can_override(): bool {
        return current_user_can( 'csms_override_score' );
    }
}

<?php
/**
 * REST API endpoints for community feedback (votes and comments).
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class ASRG_CSMS_Feedback_Endpoint {

    const NAMESPACE = 'csms/v1';

    public function register_routes(): void {
        // Public: get feedback for a tool x sub-feature.
        register_rest_route( self::NAMESPACE, '/feedback/(?P<tool_id>\d+)/(?P<sub_feature_id>[a-z0-9-]+)', [
            'methods'             => 'GET',
            'callback'            => [ $this, 'get_feedback' ],
            'permission_callback' => '__return_true',
            'args'                => [
                'tool_id'        => [ 'required' => true, 'type' => 'integer' ],
                'sub_feature_id' => [ 'required' => true, 'type' => 'string' ],
            ],
        ] );

        // Authenticated: submit a vote.
        register_rest_route( self::NAMESPACE, '/feedback/vote', [
            'methods'             => 'POST',
            'callback'            => [ $this, 'submit_vote' ],
            'permission_callback' => [ $this, 'can_vote' ],
            'args'                => [
                'toolId'        => [ 'required' => true, 'type' => 'integer' ],
                'subFeatureId'  => [ 'required' => true, 'type' => 'string', 'sanitize_callback' => 'sanitize_text_field' ],
                'voteType'      => [ 'required' => true, 'type' => 'string', 'enum' => [ 'agree', 'disagree' ] ],
            ],
        ] );

        // Authenticated: delete own vote.
        register_rest_route( self::NAMESPACE, '/feedback/vote/(?P<id>\d+)', [
            'methods'             => 'DELETE',
            'callback'            => [ $this, 'delete_vote' ],
            'permission_callback' => [ $this, 'can_vote' ],
        ] );

        // Authenticated: submit a comment.
        register_rest_route( self::NAMESPACE, '/feedback/comment', [
            'methods'             => 'POST',
            'callback'            => [ $this, 'submit_comment' ],
            'permission_callback' => [ $this, 'can_comment' ],
            'args'                => [
                'toolId'        => [ 'required' => true, 'type' => 'integer' ],
                'subFeatureId'  => [ 'required' => true, 'type' => 'string', 'sanitize_callback' => 'sanitize_text_field' ],
                'body'          => [ 'required' => true, 'type' => 'string' ],
                'parentId'      => [ 'required' => false, 'type' => 'integer' ],
            ],
        ] );

        // Authenticated: soft-delete own comment.
        register_rest_route( self::NAMESPACE, '/feedback/comment/(?P<id>\d+)', [
            'methods'             => 'DELETE',
            'callback'            => [ $this, 'delete_comment' ],
            'permission_callback' => [ $this, 'can_comment' ],
        ] );
    }

    /**
     * GET /feedback/{tool_id}/{sub_feature_id}
     */
    public function get_feedback( \WP_REST_Request $request ): \WP_REST_Response {
        global $wpdb;

        $tool_id        = (int) $request->get_param( 'tool_id' );
        $sub_feature_id = sanitize_text_field( $request->get_param( 'sub_feature_id' ) );

        // Vote counts.
        $votes_table = ASRG_CSMS_Database::table( 'feedback_votes' );
        $vote_counts = $wpdb->get_results(
            $wpdb->prepare(
                "SELECT vote_type, COUNT(*) as cnt FROM {$votes_table}
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
                    "SELECT vote_type FROM {$votes_table}
                     WHERE user_id = %d AND tool_id = %d AND sub_feature_id = %s",
                    get_current_user_id(),
                    $tool_id,
                    $sub_feature_id
                )
            );
            $votes['userVote'] = $user_vote;
        }

        // Comments.
        $comments_table = ASRG_CSMS_Database::table( 'feedback_comments' );
        $comments = $wpdb->get_results(
            $wpdb->prepare(
                "SELECT c.id, c.user_id AS userId, c.body, c.parent_id AS parentId,
                        c.is_deleted AS isDeleted, c.created_at AS createdAt, c.updated_at AS updatedAt,
                        u.display_name AS userName
                 FROM {$comments_table} c
                 LEFT JOIN {$wpdb->users} u ON c.user_id = u.ID
                 WHERE c.tool_id = %d AND c.sub_feature_id = %s
                 ORDER BY c.created_at ASC",
                $tool_id,
                $sub_feature_id
            ),
            ARRAY_A
        );

        // Add avatar URLs and mask deleted comment bodies.
        foreach ( $comments as &$comment ) {
            $comment['id']        = (int) $comment['id'];
            $comment['userId']    = (int) $comment['userId'];
            $comment['parentId']  = $comment['parentId'] ? (int) $comment['parentId'] : null;
            $comment['isDeleted'] = (bool) $comment['isDeleted'];
            $comment['avatar']    = get_avatar_url( $comment['userId'], [ 'size' => 48 ] );

            if ( $comment['isDeleted'] ) {
                $comment['body']     = '[deleted]';
                $comment['userName'] = '[deleted]';
            }
        }

        return new \WP_REST_Response( [
            'votes'    => $votes,
            'comments' => $comments,
        ], 200 );
    }

    /**
     * POST /feedback/vote
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
     * DELETE /feedback/vote/{id}
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
     * POST /feedback/comment
     */
    public function submit_comment( \WP_REST_Request $request ): \WP_REST_Response {
        global $wpdb;

        $user_id        = get_current_user_id();
        $tool_id        = (int) $request->get_param( 'toolId' );
        $sub_feature_id = $request->get_param( 'subFeatureId' );
        $body           = sanitize_textarea_field( $request->get_param( 'body' ) );
        $parent_id      = $request->get_param( 'parentId' );

        if ( empty( $body ) || mb_strlen( $body ) > 2000 ) {
            return new \WP_REST_Response( [ 'error' => 'Comment must be 1-2000 characters' ], 400 );
        }

        $table = ASRG_CSMS_Database::table( 'feedback_comments' );

        $wpdb->insert( $table, [
            'user_id'        => $user_id,
            'tool_id'        => $tool_id,
            'sub_feature_id' => $sub_feature_id,
            'body'           => $body,
            'parent_id'      => $parent_id ? (int) $parent_id : null,
            'created_at'     => current_time( 'mysql' ),
        ] );

        $comment_id = $wpdb->insert_id;

        $user = wp_get_current_user();

        return new \WP_REST_Response( [
            'id'        => $comment_id,
            'userId'    => $user_id,
            'userName'  => $user->display_name,
            'avatar'    => get_avatar_url( $user_id, [ 'size' => 48 ] ),
            'body'      => $body,
            'parentId'  => $parent_id ? (int) $parent_id : null,
            'createdAt' => current_time( 'mysql' ),
        ], 201 );
    }

    /**
     * DELETE /feedback/comment/{id} — soft delete.
     */
    public function delete_comment( \WP_REST_Request $request ): \WP_REST_Response {
        global $wpdb;

        $comment_id = (int) $request->get_param( 'id' );
        $table      = ASRG_CSMS_Database::table( 'feedback_comments' );

        $comment = $wpdb->get_row(
            $wpdb->prepare( "SELECT user_id FROM {$table} WHERE id = %d", $comment_id ),
            ARRAY_A
        );

        if ( ! $comment ) {
            return new \WP_REST_Response( [ 'error' => 'Not found' ], 404 );
        }

        // Allow owner or moderators.
        $is_owner     = (int) $comment['user_id'] === get_current_user_id();
        $is_moderator = current_user_can( 'csms_moderate_comments' );

        if ( ! $is_owner && ! $is_moderator ) {
            return new \WP_REST_Response( [ 'error' => 'Not authorized' ], 403 );
        }

        $wpdb->update(
            $table,
            [ 'is_deleted' => 1, 'updated_at' => current_time( 'mysql' ) ],
            [ 'id' => $comment_id ]
        );

        return new \WP_REST_Response( null, 204 );
    }

    public function can_vote(): bool {
        return current_user_can( 'csms_vote' );
    }

    public function can_comment(): bool {
        return current_user_can( 'csms_comment' );
    }
}

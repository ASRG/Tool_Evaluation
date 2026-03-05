<?php
/**
 * Scoring engine — computes rollup scores from sub-feature ratings.
 *
 * Reads sub-feature ratings from post meta (JetEngine fields) and
 * writes computed scores back to post meta for display by Elementor.
 *
 * Sub-feature ratings:
 *   fully_fulfills    = 1.0
 *   partially_fulfills = 0.5
 *   does_not_fulfill   = 0.0
 *
 * Category score = weighted sum of sub-feature numeric values * 100 (0-100 range)
 * Overall score  = weighted sum of category scores (0-100 range)
 *
 * Community adjustment: bounded to +/-15%, using Wilson score lower bound.
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class ASRG_CSMS_Scoring_Engine {

    const RATING_VALUES = [
        'fully_fulfills'     => 1.0,
        'partially_fulfills' => 0.5,
        'does_not_fulfill'   => 0.0,
    ];

    const COMMUNITY_MAX_ADJUSTMENT = 0.15;
    const MIN_VOTES_THRESHOLD      = 5;

    /**
     * Load the evaluation framework from JSON.
     */
    public static function get_framework(): array {
        $path = ASRG_CSMS_PLUGIN_DIR . 'data/evaluation-framework.json';

        if ( ! file_exists( $path ) ) {
            return [ 'categories' => [] ];
        }

        return json_decode( file_get_contents( $path ), true );
    }

    /**
     * Compute scores for a single tool (WP post).
     *
     * @param int        $post_id  The csms_tool post ID.
     * @param array|null $feedback Optional pre-fetched feedback data.
     * @return array Computed score tree.
     */
    public static function compute_tool_score( int $post_id, ?array $feedback = null ): array {
        $framework = self::get_framework();

        // Fetch feedback if not provided.
        if ( null === $feedback ) {
            $feedback = self::get_feedback_summary( $post_id );
        }

        $category_scores   = [];
        $overall_score     = 0.0;
        $overall_editorial = 0.0;

        foreach ( $framework['categories'] as $category ) {
            $sub_feature_scores = [];
            $editorial_sum      = 0.0;
            $adjusted_sum       = 0.0;

            foreach ( $category['subFeatures'] as $sf ) {
                $meta_prefix = str_replace( '-', '_', $sf['id'] );

                // Read rating from post meta.
                $rating   = get_post_meta( $post_id, $meta_prefix . '_rating', true );
                $rating   = ( $rating && isset( self::RATING_VALUES[ $rating ] ) ) ? $rating : 'does_not_fulfill';
                $numeric  = self::RATING_VALUES[ $rating ];
                $weight   = (float) $sf['weight'];

                // Read rationale and evidence from post meta.
                $rationale    = get_post_meta( $post_id, $meta_prefix . '_rationale', true ) ?: '';
                $evidence_url = get_post_meta( $post_id, $meta_prefix . '_evidence_url', true ) ?: '';

                // Community confidence.
                $confidence = 0.0;
                if ( isset( $feedback[ $sf['id'] ] ) ) {
                    $confidence = self::compute_community_confidence(
                        $feedback[ $sf['id'] ]['agree'],
                        $feedback[ $sf['id'] ]['disagree']
                    );
                }

                $adjusted = max( 0.0, min( 1.0, $numeric + $confidence * self::COMMUNITY_MAX_ADJUSTMENT ) );

                $sub_feature_scores[] = [
                    'subFeatureId'        => $sf['id'],
                    'rating'              => $rating,
                    'numericRating'       => $numeric,
                    'communityConfidence' => round( $confidence, 4 ),
                    'adjustedRating'      => round( $adjusted, 4 ),
                    'weight'              => $weight,
                    'rationale'           => $rationale,
                    'evidenceUrl'         => $evidence_url,
                ];

                $editorial_sum += $numeric * $weight;
                $adjusted_sum  += $adjusted * $weight;
            }

            $editorial_score = round( $editorial_sum * 100, 1 );
            $adjusted_score  = round( $adjusted_sum * 100, 1 );

            $category_scores[] = [
                'categoryId'             => $category['id'],
                'editorialScore'         => $editorial_score,
                'communityAdjustedScore' => $adjusted_score,
                'subFeatureScores'       => $sub_feature_scores,
            ];

            $overall_score     += $adjusted_score * (float) $category['weight'];
            $overall_editorial += $editorial_score * (float) $category['weight'];
        }

        return [
            'toolId'         => $post_id,
            'overallScore'   => round( $overall_score, 1 ),
            'editorialScore' => round( $overall_editorial, 1 ),
            'categoryScores' => $category_scores,
        ];
    }

    /**
     * Compute and persist scores as post meta.
     *
     * @param int $post_id The csms_tool post ID.
     * @return array The computed score tree.
     */
    public static function compute_and_store( int $post_id ): array {
        $result = self::compute_tool_score( $post_id );

        // Store overall scores.
        update_post_meta( $post_id, '_overall_score', $result['overallScore'] );
        update_post_meta( $post_id, '_overall_editorial_score', $result['editorialScore'] );

        // Store per-category scores.
        foreach ( $result['categoryScores'] as $cs ) {
            $cat_key = str_replace( '-', '_', $cs['categoryId'] );
            update_post_meta( $post_id, '_cat_' . $cat_key . '_score', $cs['communityAdjustedScore'] );
            update_post_meta( $post_id, '_cat_' . $cat_key . '_editorial_score', $cs['editorialScore'] );
        }

        return $result;
    }

    /**
     * Get aggregated feedback vote counts for a tool.
     *
     * @return array Keyed by sub_feature_id => ['agree' => int, 'disagree' => int]
     */
    public static function get_feedback_summary( int $post_id ): array {
        global $wpdb;

        $table = ASRG_CSMS_Database::table( 'feedback_votes' );

        $rows = $wpdb->get_results(
            $wpdb->prepare(
                "SELECT sub_feature_id, vote_type, COUNT(*) as cnt
                 FROM {$table}
                 WHERE tool_id = %d
                 GROUP BY sub_feature_id, vote_type",
                $post_id
            ),
            ARRAY_A
        );

        $summary = [];
        foreach ( $rows as $row ) {
            $sf_id = $row['sub_feature_id'];
            if ( ! isset( $summary[ $sf_id ] ) ) {
                $summary[ $sf_id ] = [ 'agree' => 0, 'disagree' => 0 ];
            }
            $summary[ $sf_id ][ $row['vote_type'] ] = (int) $row['cnt'];
        }

        return $summary;
    }

    /**
     * Compute community confidence using Wilson score lower bound.
     *
     * Returns a value between -1 (strong disagreement) and +1 (strong agreement).
     * Returns 0 if below the minimum vote threshold.
     */
    private static function compute_community_confidence( int $agree, int $disagree ): float {
        $total = $agree + $disagree;

        if ( $total < self::MIN_VOTES_THRESHOLD ) {
            return 0.0;
        }

        $p = $agree / $total;
        $z = 1.96; // 95% confidence interval.

        $denominator = 1 + ( $z * $z ) / $total;
        $center      = $p + ( $z * $z ) / ( 2 * $total );
        $spread      = $z * sqrt( ( $p * ( 1 - $p ) + ( $z * $z ) / ( 4 * $total ) ) / $total );

        $wilson_lower = ( $center - $spread ) / $denominator;

        // Map from [0, 1] to [-1, +1]: 0.5 maps to 0 (neutral).
        return ( $wilson_lower - 0.5 ) * 2;
    }
}

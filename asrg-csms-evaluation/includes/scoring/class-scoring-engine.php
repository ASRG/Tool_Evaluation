<?php
/**
 * Scoring engine — computes rollup scores from sub-feature ratings.
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
     * Compute scores for a single tool.
     *
     * @param int        $tool_id  The tool database ID.
     * @param array|null $feedback Optional pre-fetched feedback data.
     * @return array Computed score tree.
     */
    public static function compute_tool_score( int $tool_id, ?array $feedback = null ): array {
        global $wpdb;

        $framework = self::get_framework();
        $table     = ASRG_CSMS_Database::table( 'tool_scores' );

        // Fetch all scores for this tool.
        $raw_scores = $wpdb->get_results(
            $wpdb->prepare( "SELECT sub_feature_id, rating, rationale, evidence_url FROM {$table} WHERE tool_id = %d", $tool_id ),
            ARRAY_A
        );

        $score_map = [];
        foreach ( $raw_scores as $row ) {
            $score_map[ $row['sub_feature_id'] ] = $row;
        }

        // Fetch feedback if not provided.
        if ( null === $feedback ) {
            $feedback = self::get_feedback_summary( $tool_id );
        }

        $category_scores = [];
        $overall_score   = 0.0;

        foreach ( $framework['categories'] as $category ) {
            $sub_feature_scores = [];
            $editorial_sum      = 0.0;
            $adjusted_sum       = 0.0;

            foreach ( $category['subFeatures'] as $sf ) {
                $score_row     = $score_map[ $sf['id'] ] ?? null;
                $rating        = $score_row ? $score_row['rating'] : 'does_not_fulfill';
                $numeric       = self::RATING_VALUES[ $rating ] ?? 0.0;
                $weight        = (float) $sf['weight'];

                // Community confidence.
                $fb_key     = $sf['id'];
                $confidence = 0.0;

                if ( isset( $feedback[ $fb_key ] ) ) {
                    $confidence = self::compute_community_confidence(
                        $feedback[ $fb_key ]['agree'],
                        $feedback[ $fb_key ]['disagree']
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
                    'rationale'           => $score_row['rationale'] ?? '',
                    'evidenceUrl'         => $score_row['evidence_url'] ?? '',
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

            $overall_score += $adjusted_score * (float) $category['weight'];
        }

        return [
            'toolId'         => $tool_id,
            'overallScore'   => round( $overall_score, 1 ),
            'categoryScores' => $category_scores,
        ];
    }

    /**
     * Get aggregated feedback vote counts for a tool.
     *
     * @return array Keyed by sub_feature_id => ['agree' => int, 'disagree' => int]
     */
    public static function get_feedback_summary( int $tool_id ): array {
        global $wpdb;

        $table = ASRG_CSMS_Database::table( 'feedback_votes' );

        $rows = $wpdb->get_results(
            $wpdb->prepare(
                "SELECT sub_feature_id, vote_type, COUNT(*) as cnt
                 FROM {$table}
                 WHERE tool_id = %d
                 GROUP BY sub_feature_id, vote_type",
                $tool_id
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

<?php
/**
 * Public shortcodes for Elementor templates.
 *
 * [csms_tool_scores]  — Full category/sub-feature scoring grid for the current csms_tool post.
 * [csms_vote]         — Inline agree/disagree vote buttons with counts.
 * [csms_score_bar]    — Styled score progress bar.
 * [csms_methodology]  — Auto-generated framework table from JSON.
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class ASRG_CSMS_Shortcodes {

    /**
     * Register hooks.
     */
    public function init(): void {
        add_action( 'init', [ $this, 'register' ] );
        add_action( 'wp_enqueue_scripts', [ $this, 'enqueue_assets' ] );
    }

    /**
     * Register all shortcodes.
     */
    public function register(): void {
        add_shortcode( 'csms_tool_scores', [ $this, 'render_tool_scores' ] );
        add_shortcode( 'csms_vote', [ $this, 'render_vote_buttons' ] );
        add_shortcode( 'csms_score_bar', [ $this, 'render_score_bar' ] );
        add_shortcode( 'csms_methodology', [ $this, 'render_methodology' ] );
    }

    /**
     * Enqueue public CSS and JS on pages that use csms_tool CPT.
     */
    public function enqueue_assets(): void {
        if ( ! is_singular( 'csms_tool' ) && ! has_shortcode( get_post()->post_content ?? '', 'csms_tool_scores' ) ) {
            // Always enqueue on csms_tool single pages; conditionally elsewhere.
            // We'll enqueue globally for simplicity since shortcodes can appear anywhere.
        }

        wp_enqueue_style(
            'csms-public',
            ASRG_CSMS_PLUGIN_URL . 'assets/css/csms-public.css',
            [],
            ASRG_CSMS_VERSION
        );

        wp_enqueue_style(
            'csms-vote-buttons',
            ASRG_CSMS_PLUGIN_URL . 'assets/css/vote-buttons.css',
            [],
            ASRG_CSMS_VERSION
        );

        wp_enqueue_script(
            'csms-vote-buttons',
            ASRG_CSMS_PLUGIN_URL . 'assets/js/vote-buttons.js',
            [],
            ASRG_CSMS_VERSION,
            true
        );
    }

    // ------------------------------------------------------------------
    // [csms_tool_scores] — Full scoring grid
    // ------------------------------------------------------------------

    /**
     * Render the full category/sub-feature scoring grid for a csms_tool post.
     */
    public function render_tool_scores( $atts ): string {
        $atts = shortcode_atts( [
            'post_id' => 0,
        ], $atts );

        $post_id = (int) $atts['post_id'] ?: get_the_ID();

        if ( ! $post_id || get_post_type( $post_id ) !== 'csms_tool' ) {
            return '<p class="csms-error">Invalid CSMS Tool post.</p>';
        }

        $framework = ASRG_CSMS_Scoring_Engine::get_framework();
        $scores    = ASRG_CSMS_Scoring_Engine::compute_tool_score( $post_id );

        // Build a lookup map for computed category scores.
        $cat_scores = [];
        foreach ( $scores['categoryScores'] as $cs ) {
            $cat_scores[ $cs['categoryId'] ] = $cs;
        }

        ob_start();
        ?>
        <div class="csms-scores-grid">
            <?php foreach ( $framework['categories'] as $category ) :
                $cs = $cat_scores[ $category['id'] ] ?? null;
                $cat_score = $cs ? $cs['communityAdjustedScore'] : 0;
                $cat_weight = $category['weight'] * 100;

                // Build sub-feature score lookup.
                $sf_scores = [];
                if ( $cs ) {
                    foreach ( $cs['subFeatureScores'] as $sfs ) {
                        $sf_scores[ $sfs['subFeatureId'] ] = $sfs;
                    }
                }
            ?>
            <div class="csms-category-section">
                <div class="csms-category-header">
                    <h3 class="csms-category-name"><?php echo esc_html( $category['name'] ); ?></h3>
                    <span class="csms-category-weight">Weight: <?php echo esc_html( $cat_weight ); ?>%</span>
                    <?php echo $this->score_bar_html( $cat_score ); ?>
                </div>

                <?php if ( ! empty( $category['description'] ) ) : ?>
                    <p class="csms-category-desc"><?php echo esc_html( $category['description'] ); ?></p>
                <?php endif; ?>

                <div class="csms-sub-features">
                    <?php foreach ( $category['subFeatures'] as $sf ) :
                        $sfs = $sf_scores[ $sf['id'] ] ?? null;
                        $rating = $sfs ? $sfs['rating'] : 'does_not_fulfill';
                        $rationale = $sfs ? $sfs['rationale'] : '';
                        $evidence = $sfs ? $sfs['evidenceUrl'] : '';
                    ?>
                    <div class="csms-sub-feature">
                        <div class="csms-sf-header">
                            <span class="csms-sf-name"><?php echo esc_html( $sf['name'] ); ?></span>
                            <?php echo $this->rating_badge_html( $rating ); ?>
                            <?php if ( ! empty( $sf['isoReference'] ) ) : ?>
                                <span class="csms-sf-iso"><?php echo esc_html( $sf['isoReference'] ); ?></span>
                            <?php endif; ?>
                        </div>

                        <p class="csms-sf-desc"><?php echo esc_html( $sf['description'] ); ?></p>

                        <?php if ( $rationale ) : ?>
                            <div class="csms-sf-rationale">
                                <strong>Rationale:</strong> <?php echo esc_html( $rationale ); ?>
                            </div>
                        <?php endif; ?>

                        <?php if ( $evidence ) : ?>
                            <div class="csms-sf-evidence">
                                <a href="<?php echo esc_url( $evidence ); ?>" target="_blank" rel="noopener">View Evidence</a>
                            </div>
                        <?php endif; ?>

                        <?php echo do_shortcode( '[csms_vote tool_id="' . $post_id . '" sub_feature_id="' . esc_attr( $sf['id'] ) . '"]' ); ?>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
        <?php
        return ob_get_clean();
    }

    // ------------------------------------------------------------------
    // [csms_vote] — Inline vote buttons
    // ------------------------------------------------------------------

    /**
     * Render agree/disagree vote buttons for a tool x sub-feature pair.
     */
    public function render_vote_buttons( $atts ): string {
        $atts = shortcode_atts( [
            'tool_id'        => 0,
            'sub_feature_id' => '',
        ], $atts );

        $tool_id        = (int) $atts['tool_id'] ?: get_the_ID();
        $sub_feature_id = sanitize_text_field( $atts['sub_feature_id'] );

        if ( ! $tool_id || ! $sub_feature_id ) {
            return '';
        }

        // Get current vote counts.
        global $wpdb;
        $table = ASRG_CSMS_Database::table( 'feedback_votes' );

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

        $agree    = 0;
        $disagree = 0;
        foreach ( $vote_counts as $row ) {
            if ( 'agree' === $row['vote_type'] ) {
                $agree = (int) $row['cnt'];
            } elseif ( 'disagree' === $row['vote_type'] ) {
                $disagree = (int) $row['cnt'];
            }
        }

        // Check current user's vote.
        $user_vote = null;
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
        }

        $api_base = esc_url( rest_url( 'csms/v1' ) );
        $nonce    = wp_create_nonce( 'wp_rest' );

        ob_start();
        ?>
        <div class="csms-vote-buttons"
             data-tool-id="<?php echo esc_attr( $tool_id ); ?>"
             data-sub-feature-id="<?php echo esc_attr( $sub_feature_id ); ?>"
             data-api-base="<?php echo $api_base; ?>"
             data-nonce="<?php echo esc_attr( $nonce ); ?>">

            <?php if ( is_user_logged_in() ) : ?>
                <button type="button"
                        class="csms-vote-btn <?php echo $user_vote === 'agree' ? 'csms-vote-active' : ''; ?>"
                        data-vote-type="agree">
                    <span class="csms-vote-icon">&#x1F44D;</span>
                    <span class="csms-vote-count-agree"><?php echo $agree; ?></span>
                </button>

                <button type="button"
                        class="csms-vote-btn <?php echo $user_vote === 'disagree' ? 'csms-vote-active' : ''; ?>"
                        data-vote-type="disagree">
                    <span class="csms-vote-icon">&#x1F44E;</span>
                    <span class="csms-vote-count-disagree"><?php echo $disagree; ?></span>
                </button>
            <?php else : ?>
                <span class="csms-vote-icon">&#x1F44D;</span>
                <span class="csms-vote-count-agree"><?php echo $agree; ?></span>
                <span class="csms-vote-icon">&#x1F44E;</span>
                <span class="csms-vote-count-disagree"><?php echo $disagree; ?></span>
                <span class="csms-vote-login-msg">
                    <a href="<?php echo esc_url( wp_login_url( get_permalink() ) ); ?>">Log in</a> to vote
                </span>
            <?php endif; ?>
        </div>
        <?php
        return ob_get_clean();
    }

    // ------------------------------------------------------------------
    // [csms_score_bar] — Styled progress bar
    // ------------------------------------------------------------------

    /**
     * Render a score progress bar.
     */
    public function render_score_bar( $atts ): string {
        $atts = shortcode_atts( [
            'score'    => 0,
            'max'      => 100,
            'show_num' => 'yes',
        ], $atts );

        $score = (float) $atts['score'];
        return $this->score_bar_html( $score, $atts['show_num'] === 'yes' );
    }

    // ------------------------------------------------------------------
    // [csms_methodology] — Framework methodology table
    // ------------------------------------------------------------------

    /**
     * Render the evaluation methodology / framework table.
     */
    public function render_methodology( $atts ): string {
        $framework = ASRG_CSMS_Scoring_Engine::get_framework();

        ob_start();
        ?>
        <div class="csms-methodology">
            <h2>Evaluation Framework</h2>
            <p>
                Version <?php echo esc_html( $framework['version'] ?? '1.0.0' ); ?>
                &mdash; Last updated: <?php echo esc_html( $framework['lastUpdated'] ?? '' ); ?>
            </p>

            <h3>Rating System</h3>
            <table class="csms-meth-table">
                <thead>
                    <tr><th>Rating</th><th>Value</th><th>Description</th></tr>
                </thead>
                <tbody>
                    <tr>
                        <td><?php echo $this->rating_badge_html( 'fully_fulfills' ); ?></td>
                        <td>1.0</td>
                        <td>The tool fully meets the requirement with comprehensive functionality.</td>
                    </tr>
                    <tr>
                        <td><?php echo $this->rating_badge_html( 'partially_fulfills' ); ?></td>
                        <td>0.5</td>
                        <td>The tool partially meets the requirement with some limitations.</td>
                    </tr>
                    <tr>
                        <td><?php echo $this->rating_badge_html( 'does_not_fulfill' ); ?></td>
                        <td>0.0</td>
                        <td>The tool does not meet the requirement or the feature is absent.</td>
                    </tr>
                </tbody>
            </table>

            <h3>Evaluation Categories</h3>
            <table class="csms-meth-table">
                <thead>
                    <tr><th>Category</th><th>Weight</th><th>Sub-Features</th><th>Description</th></tr>
                </thead>
                <tbody>
                    <?php foreach ( $framework['categories'] as $cat ) : ?>
                    <tr>
                        <td><strong><?php echo esc_html( $cat['name'] ); ?></strong></td>
                        <td><?php echo esc_html( ( $cat['weight'] * 100 ) . '%' ); ?></td>
                        <td><?php echo count( $cat['subFeatures'] ); ?></td>
                        <td><?php echo esc_html( $cat['description'] ); ?></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>

            <h3>Scoring Formula</h3>
            <ul>
                <li><strong>Sub-Feature Score</strong> = Rating value (0, 0.5, or 1.0) adjusted by community confidence (max &plusmn;15%).</li>
                <li><strong>Category Score</strong> = Weighted sum of sub-feature scores &times; 100 (range 0&ndash;100).</li>
                <li><strong>Overall Score</strong> = Weighted sum of category scores (range 0&ndash;100).</li>
            </ul>

            <h3>Community Influence</h3>
            <p>
                Community members can vote <em>agree</em> or <em>disagree</em> on individual sub-feature ratings.
                A minimum of 5 votes is required before community influence takes effect.
                The Wilson score lower bound (95% confidence interval) is used to compute community confidence,
                which can adjust a sub-feature score by up to &plusmn;15%.
            </p>
        </div>
        <?php
        return ob_get_clean();
    }

    // ------------------------------------------------------------------
    // HTML Helpers
    // ------------------------------------------------------------------

    /**
     * Generate a score progress bar HTML string.
     */
    private function score_bar_html( float $score, bool $show_num = true ): string {
        $pct   = max( 0, min( 100, $score ) );
        $class = 'csms-score-bar';

        if ( $pct >= 75 ) {
            $class .= ' csms-score-high';
        } elseif ( $pct >= 40 ) {
            $class .= ' csms-score-mid';
        } else {
            $class .= ' csms-score-low';
        }

        $html = '<div class="' . esc_attr( $class ) . '">';
        $html .= '<div class="csms-score-bar-fill" style="width:' . esc_attr( $pct ) . '%;"></div>';
        if ( $show_num ) {
            $html .= '<span class="csms-score-bar-label">' . esc_html( round( $pct, 1 ) ) . '</span>';
        }
        $html .= '</div>';

        return $html;
    }

    /**
     * Generate a rating badge HTML string.
     */
    private function rating_badge_html( string $rating ): string {
        $labels = [
            'fully_fulfills'     => 'FF',
            'partially_fulfills' => 'PF',
            'does_not_fulfill'   => 'DNF',
        ];

        $titles = [
            'fully_fulfills'     => 'Fully Fulfills',
            'partially_fulfills' => 'Partially Fulfills',
            'does_not_fulfill'   => 'Does Not Fulfill',
        ];

        $label = $labels[ $rating ] ?? 'N/A';
        $title = $titles[ $rating ] ?? '';
        $class = 'csms-badge csms-badge-' . str_replace( '_', '-', $rating );

        return '<span class="' . esc_attr( $class ) . '" title="' . esc_attr( $title ) . '">' . esc_html( $label ) . '</span>';
    }
}

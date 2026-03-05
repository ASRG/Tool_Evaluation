<?php
/**
 * Programmatic JetEngine meta field registration.
 *
 * Reads data/evaluation-framework.json and registers all meta boxes
 * and fields for the csms_tool CPT via JetEngine's API.
 *
 * Field groups created:
 *   1. "Tool Information" — vendor_name, website_url, is_sponsor, sponsor_tier
 *   2. "Computed Scores"  — _overall_score, _overall_editorial_score, + 9 category pairs
 *   3-11. One group per category (9 total) — 3 fields per sub-feature (rating, rationale, evidence_url)
 *
 * This class is idempotent: safe to re-run if the framework evolves.
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class ASRG_CSMS_Meta_Fields {

    const POST_TYPE = 'csms_tool';

    /**
     * Option key used to track registered meta boxes.
     */
    const OPTION_KEY = 'asrg_csms_jet_meta_boxes';

    /**
     * Register hooks.
     */
    public function init(): void {
        // Register on init, after JetEngine has loaded.
        add_action( 'init', [ $this, 'ensure_meta_fields' ], 99 );
    }

    /**
     * Ensure all meta fields are registered.
     * Only runs the full registration if the framework version has changed.
     */
    public function ensure_meta_fields(): void {
        // Bail if JetEngine is not active.
        if ( ! function_exists( 'jet_engine' ) ) {
            return;
        }

        $framework = $this->load_framework();
        if ( empty( $framework['categories'] ) ) {
            return;
        }

        $current_version = $framework['version'] ?? '0.0.0';
        $stored_version  = get_option( 'asrg_csms_meta_version', '' );

        if ( $stored_version === $current_version ) {
            return; // Already up to date.
        }

        $this->register_all_meta_boxes( $framework );
        update_option( 'asrg_csms_meta_version', $current_version );
    }

    /**
     * Load the evaluation framework JSON.
     */
    private function load_framework(): array {
        $path = ASRG_CSMS_PLUGIN_DIR . 'data/evaluation-framework.json';

        if ( ! file_exists( $path ) ) {
            return [];
        }

        return json_decode( file_get_contents( $path ), true ) ?: [];
    }

    /**
     * Register all JetEngine meta boxes.
     */
    private function register_all_meta_boxes( array $framework ): void {
        // Remove previously registered meta boxes from this plugin.
        $this->cleanup_old_meta_boxes();

        $meta_box_ids = [];

        // 1. Tool Information fields.
        $meta_box_ids[] = $this->register_tool_info_meta_box();

        // 2. Computed Scores fields.
        $meta_box_ids[] = $this->register_computed_scores_meta_box( $framework );

        // 3-11. Per-category sub-feature evaluation fields.
        foreach ( $framework['categories'] as $category ) {
            $meta_box_ids[] = $this->register_category_meta_box( $category );
        }

        // Store registered IDs for cleanup on next run.
        update_option( self::OPTION_KEY, array_filter( $meta_box_ids ) );
    }

    /**
     * Remove previously registered meta boxes created by this plugin.
     */
    private function cleanup_old_meta_boxes(): void {
        $old_ids = get_option( self::OPTION_KEY, [] );

        if ( empty( $old_ids ) || ! is_array( $old_ids ) ) {
            return;
        }

        foreach ( $old_ids as $id ) {
            wp_delete_post( $id, true );
        }

        delete_option( self::OPTION_KEY );
    }

    /**
     * Register "Tool Information" meta box.
     */
    private function register_tool_info_meta_box(): ?int {
        $fields = [
            $this->text_field( 'vendor_name', 'Vendor Name', 'Company or organisation name of the tool vendor.' ),
            $this->text_field( 'website_url', 'Website URL', 'Public website URL for the tool.' ),
            $this->switcher_field( 'is_sponsor', 'ASRG Sponsor', 'Whether this vendor is a current ASRG sponsor.' ),
            $this->select_field( 'sponsor_tier', 'Sponsor Tier', [
                '' => 'None',
                'silver' => 'Silver',
                'gold' => 'Gold',
                'platinum' => 'Platinum',
            ], 'Sponsor tier level (only relevant if Is Sponsor is enabled).' ),
        ];

        return $this->create_jet_meta_box( 'tool-information', 'Tool Information', $fields );
    }

    /**
     * Register "Computed Scores" meta box.
     */
    private function register_computed_scores_meta_box( array $framework ): ?int {
        $fields = [
            $this->number_field( '_overall_score', 'Overall Score', 'Computed overall score (0-100). Auto-calculated.' ),
            $this->number_field( '_overall_editorial_score', 'Overall Editorial Score', 'Editorial score without community adjustment.' ),
        ];

        foreach ( $framework['categories'] as $category ) {
            $cat_key  = str_replace( '-', '_', $category['id'] );
            $cat_name = $category['name'];

            $fields[] = $this->number_field(
                '_cat_' . $cat_key . '_score',
                $cat_name . ' Score',
                "Community-adjusted score for {$cat_name} (0-100)."
            );
            $fields[] = $this->number_field(
                '_cat_' . $cat_key . '_editorial_score',
                $cat_name . ' Editorial Score',
                "Editorial-only score for {$cat_name} (0-100)."
            );
        }

        return $this->create_jet_meta_box( 'computed-scores', 'Computed Scores (Auto-Generated)', $fields );
    }

    /**
     * Register a per-category meta box with sub-feature evaluation fields.
     */
    private function register_category_meta_box( array $category ): ?int {
        $fields = [];

        foreach ( $category['subFeatures'] as $sf ) {
            $key_prefix = str_replace( '-', '_', $sf['id'] );
            $sf_name    = $sf['name'];

            $fields[] = $this->select_field(
                $key_prefix . '_rating',
                $sf_name . ' — Rating',
                [
                    ''                    => '— Not Evaluated —',
                    'fully_fulfills'      => 'Fully Fulfills',
                    'partially_fulfills'  => 'Partially Fulfills',
                    'does_not_fulfill'    => 'Does Not Fulfill',
                ],
                $sf['description']
            );

            $fields[] = $this->textarea_field(
                $key_prefix . '_rationale',
                $sf_name . ' — Rationale',
                'Justification for the rating above.'
            );

            $fields[] = $this->text_field(
                $key_prefix . '_evidence_url',
                $sf_name . ' — Evidence URL',
                'Link to documentation or evidence supporting this rating.'
            );
        }

        $slug = str_replace( '-', '_', $category['id'] ) . '_scores';

        return $this->create_jet_meta_box( $slug, $category['name'] . ' Scores', $fields );
    }

    // ------------------------------------------------------------------
    // JetEngine Meta Box Creation
    // ------------------------------------------------------------------

    /**
     * Create a JetEngine meta box (stored as a jet-engine post).
     *
     * @param string $slug  Unique slug for this meta box.
     * @param string $title Human-readable title.
     * @param array  $fields Array of field definitions.
     * @return int|null  Post ID of the created meta box or null on failure.
     */
    private function create_jet_meta_box( string $slug, string $title, array $fields ): ?int {
        $meta_box_data = [
            'post_type'      => 'jet-engine',
            'post_title'     => $title,
            'post_name'      => 'csms-' . $slug,
            'post_status'    => 'publish',
            'post_content'   => '',
            'comment_status' => 'closed',
            'ping_status'    => 'closed',
        ];

        $post_id = wp_insert_post( $meta_box_data );

        if ( is_wp_error( $post_id ) ) {
            return null;
        }

        // Build args array for JetEngine.
        $args = [
            'allowed_post_type'    => [ self::POST_TYPE ],
            'active'               => true,
            'hide_field_names'     => false,
            'allowed_tax'          => [],
            'allowed_posts'        => [],
            'allowed_pages'        => [],
            'revision_link'        => false,
            'args_source'          => 'post',
        ];

        // Prepare meta fields array for JetEngine format.
        $jet_fields = [];
        foreach ( $fields as $index => $field ) {
            $jet_fields[] = array_merge( $field, [
                'object_type' => 'field',
                'is_required' => false,
            ] );
        }

        update_post_meta( $post_id, '_jet_engine_meta_box_args', $args );
        update_post_meta( $post_id, '_jet_engine_meta_fields', $jet_fields );

        return $post_id;
    }

    // ------------------------------------------------------------------
    // Field Definition Helpers
    // ------------------------------------------------------------------

    private function text_field( string $name, string $title, string $description = '' ): array {
        return [
            'name'        => $name,
            'title'       => $title,
            'type'        => 'text',
            'description' => $description,
        ];
    }

    private function textarea_field( string $name, string $title, string $description = '' ): array {
        return [
            'name'        => $name,
            'title'       => $title,
            'type'        => 'textarea',
            'description' => $description,
        ];
    }

    private function number_field( string $name, string $title, string $description = '' ): array {
        return [
            'name'        => $name,
            'title'       => $title,
            'type'        => 'number',
            'description' => $description,
            'min_value'   => 0,
            'max_value'   => 100,
            'step_value'  => 0.1,
        ];
    }

    private function select_field( string $name, string $title, array $options, string $description = '' ): array {
        $opt_array = [];
        foreach ( $options as $value => $label ) {
            $opt_array[] = [
                'key'   => $value,
                'value' => $label,
            ];
        }

        return [
            'name'        => $name,
            'title'       => $title,
            'type'        => 'select',
            'description' => $description,
            'options'     => $opt_array,
        ];
    }

    private function switcher_field( string $name, string $title, string $description = '' ): array {
        return [
            'name'        => $name,
            'title'       => $title,
            'type'        => 'switcher',
            'description' => $description,
        ];
    }
}

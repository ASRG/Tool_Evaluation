<?php
/**
 * Main plugin class — coordinates all components.
 *
 * Loads: CPT registration, JetEngine meta fields, scoring engine + hooks,
 *        vote handler, and shortcodes.
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class ASRG_CSMS_Plugin {

    public function init(): void {
        $this->load_dependencies();
        $this->init_components();
    }

    private function load_dependencies(): void {
        require_once ASRG_CSMS_PLUGIN_DIR . 'includes/class-database.php';
        require_once ASRG_CSMS_PLUGIN_DIR . 'includes/class-roles.php';
        require_once ASRG_CSMS_PLUGIN_DIR . 'includes/class-cpt-registration.php';
        require_once ASRG_CSMS_PLUGIN_DIR . 'includes/class-meta-fields.php';
        require_once ASRG_CSMS_PLUGIN_DIR . 'includes/scoring/class-scoring-engine.php';
        require_once ASRG_CSMS_PLUGIN_DIR . 'includes/class-score-hooks.php';
        require_once ASRG_CSMS_PLUGIN_DIR . 'includes/class-vote-handler.php';
        require_once ASRG_CSMS_PLUGIN_DIR . 'includes/class-shortcodes.php';
    }

    private function init_components(): void {
        // Register the csms_tool CPT.
        $cpt = new ASRG_CSMS_CPT_Registration();
        $cpt->init();

        // Register JetEngine meta fields (if JetEngine is active).
        $meta = new ASRG_CSMS_Meta_Fields();
        $meta->init();

        // Score computation hooks (save_post + cron).
        $score_hooks = new ASRG_CSMS_Score_Hooks();
        $score_hooks->init();

        // REST API vote handler.
        $vote_handler = new ASRG_CSMS_Vote_Handler();
        $vote_handler->init();

        // Public shortcodes.
        $shortcodes = new ASRG_CSMS_Shortcodes();
        $shortcodes->init();
    }
}

<?php
/**
 * Main plugin class — coordinates all components.
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class ASRG_CSMS_Plugin {

    public function init(): void {
        $this->load_dependencies();

        add_action( 'rest_api_init', [ $this, 'register_rest_routes' ] );
        add_action( 'init', [ $this, 'register_shortcodes' ] );
    }

    private function load_dependencies(): void {
        require_once ASRG_CSMS_PLUGIN_DIR . 'includes/class-database.php';
        require_once ASRG_CSMS_PLUGIN_DIR . 'includes/class-roles.php';
        require_once ASRG_CSMS_PLUGIN_DIR . 'includes/class-shortcode.php';
        require_once ASRG_CSMS_PLUGIN_DIR . 'includes/class-rest-api.php';
        require_once ASRG_CSMS_PLUGIN_DIR . 'includes/scoring/class-scoring-engine.php';
        require_once ASRG_CSMS_PLUGIN_DIR . 'includes/endpoints/class-tools-endpoint.php';
        require_once ASRG_CSMS_PLUGIN_DIR . 'includes/endpoints/class-scores-endpoint.php';
        require_once ASRG_CSMS_PLUGIN_DIR . 'includes/endpoints/class-feedback-endpoint.php';
        require_once ASRG_CSMS_PLUGIN_DIR . 'includes/endpoints/class-vendor-endpoint.php';
    }

    public function register_rest_routes(): void {
        $rest_api = new ASRG_CSMS_REST_API();
        $rest_api->register_routes();
    }

    public function register_shortcodes(): void {
        $shortcode = new ASRG_CSMS_Shortcode();
        $shortcode->register();
    }
}

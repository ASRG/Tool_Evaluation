<?php
/**
 * Plugin Name: ASRG CSMS Tool Evaluation
 * Plugin URI:  https://asrg.io/csms-evaluation
 * Description: Community-driven evaluation framework and comparison table for automotive CSMS tools, anchored in ISO/SAE 21434.
 * Version:     1.0.0
 * Author:      ASRG
 * Author URI:  https://asrg.io
 * License:     GPL-2.0-or-later
 * Text Domain: asrg-csms
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

define( 'ASRG_CSMS_VERSION', '1.0.0' );
define( 'ASRG_CSMS_PLUGIN_DIR', plugin_dir_path( __FILE__ ) );
define( 'ASRG_CSMS_PLUGIN_URL', plugin_dir_url( __FILE__ ) );
define( 'ASRG_CSMS_PLUGIN_FILE', __FILE__ );

require_once ASRG_CSMS_PLUGIN_DIR . 'includes/class-plugin.php';

/**
 * Plugin activation hook.
 */
function asrg_csms_activate() {
    require_once ASRG_CSMS_PLUGIN_DIR . 'includes/class-database.php';
    require_once ASRG_CSMS_PLUGIN_DIR . 'includes/class-roles.php';

    ASRG_CSMS_Database::create_tables();
    ASRG_CSMS_Roles::register();

    flush_rewrite_rules();
}
register_activation_hook( __FILE__, 'asrg_csms_activate' );

/**
 * Plugin deactivation hook.
 */
function asrg_csms_deactivate() {
    require_once ASRG_CSMS_PLUGIN_DIR . 'includes/class-roles.php';

    ASRG_CSMS_Roles::unregister();

    flush_rewrite_rules();
}
register_deactivation_hook( __FILE__, 'asrg_csms_deactivate' );

/**
 * Initialize the plugin.
 */
function asrg_csms_init() {
    $plugin = new ASRG_CSMS_Plugin();
    $plugin->init();
}
add_action( 'plugins_loaded', 'asrg_csms_init' );

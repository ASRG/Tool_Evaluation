<?php
/**
 * REST API route registration — delegates to individual endpoint classes.
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class ASRG_CSMS_REST_API {

    const NAMESPACE = 'csms/v1';

    public function register_routes(): void {
        $tools    = new ASRG_CSMS_Tools_Endpoint();
        $scores   = new ASRG_CSMS_Scores_Endpoint();
        $feedback = new ASRG_CSMS_Feedback_Endpoint();
        $vendor   = new ASRG_CSMS_Vendor_Endpoint();

        $tools->register_routes();
        $scores->register_routes();
        $feedback->register_routes();
        $vendor->register_routes();
    }
}

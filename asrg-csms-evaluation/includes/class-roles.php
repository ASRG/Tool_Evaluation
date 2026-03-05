<?php
/**
 * Custom WordPress roles for CSMS evaluation.
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class ASRG_CSMS_Roles {

    /**
     * Register custom roles on plugin activation.
     */
    public static function register(): void {
        // Vendor role: can submit and update their own tool evaluations.
        add_role( 'csms_vendor', __( 'CSMS Vendor', 'asrg-csms' ), [
            'read'                  => true,
            'csms_submit_tool'      => true,
            'csms_edit_own_tool'    => true,
            'csms_vote'             => true,
            'csms_comment'          => true,
        ] );

        // Editor role: can approve/reject vendor submissions and override scores.
        add_role( 'csms_editor', __( 'CSMS Editor', 'asrg-csms' ), [
            'read'                  => true,
            'csms_submit_tool'      => true,
            'csms_edit_own_tool'    => true,
            'csms_edit_any_tool'    => true,
            'csms_approve_tool'     => true,
            'csms_override_score'   => true,
            'csms_vote'             => true,
            'csms_comment'          => true,
            'csms_moderate_comments' => true,
        ] );

        // Grant feedback capabilities to existing subscriber role.
        $subscriber = get_role( 'subscriber' );
        if ( $subscriber ) {
            $subscriber->add_cap( 'csms_vote' );
            $subscriber->add_cap( 'csms_comment' );
        }

        // Grant all CSMS capabilities to administrators.
        $admin = get_role( 'administrator' );
        if ( $admin ) {
            $admin->add_cap( 'csms_submit_tool' );
            $admin->add_cap( 'csms_edit_own_tool' );
            $admin->add_cap( 'csms_edit_any_tool' );
            $admin->add_cap( 'csms_approve_tool' );
            $admin->add_cap( 'csms_override_score' );
            $admin->add_cap( 'csms_vote' );
            $admin->add_cap( 'csms_comment' );
            $admin->add_cap( 'csms_moderate_comments' );
        }
    }

    /**
     * Remove custom roles on plugin deactivation.
     */
    public static function unregister(): void {
        remove_role( 'csms_vendor' );
        remove_role( 'csms_editor' );

        // Remove capabilities from built-in roles.
        $capabilities = [
            'csms_submit_tool',
            'csms_edit_own_tool',
            'csms_edit_any_tool',
            'csms_approve_tool',
            'csms_override_score',
            'csms_vote',
            'csms_comment',
            'csms_moderate_comments',
        ];

        foreach ( [ 'subscriber', 'administrator' ] as $role_name ) {
            $role = get_role( $role_name );
            if ( $role ) {
                foreach ( $capabilities as $cap ) {
                    $role->remove_cap( $cap );
                }
            }
        }
    }

    /**
     * Get the CSMS-specific role for a user.
     */
    public static function get_csms_role( int $user_id ): string {
        $user = get_userdata( $user_id );
        if ( ! $user ) {
            return 'anonymous';
        }

        if ( in_array( 'administrator', $user->roles, true ) || in_array( 'csms_editor', $user->roles, true ) ) {
            return 'editor';
        }

        if ( in_array( 'csms_vendor', $user->roles, true ) ) {
            return 'vendor';
        }

        return 'community';
    }
}

<?php
/**
 * Custom WordPress roles and CPT capabilities for CSMS evaluation.
 *
 * Uses WordPress post type capabilities (edit_csms_tools, publish_csms_tools, etc.)
 * instead of custom capability strings for proper CPT integration.
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class ASRG_CSMS_Roles {

    /**
     * All CPT capabilities for csms_tool.
     */
    const CPT_CAPS = [
        'edit_csms_tool',
        'read_csms_tool',
        'delete_csms_tool',
        'edit_csms_tools',
        'edit_others_csms_tools',
        'publish_csms_tools',
        'read_private_csms_tools',
        'delete_csms_tools',
        'delete_private_csms_tools',
        'delete_published_csms_tools',
        'delete_others_csms_tools',
        'edit_private_csms_tools',
        'edit_published_csms_tools',
    ];

    /**
     * Custom capabilities for feedback and moderation.
     */
    const FEEDBACK_CAPS = [
        'csms_vote',
        'csms_comment',
        'csms_moderate_comments',
    ];

    /**
     * Register custom roles on plugin activation.
     */
    public static function register(): void {
        // Vendor role: can create and edit own tool evaluations.
        add_role( 'csms_vendor', __( 'CSMS Vendor', 'asrg-csms' ), [
            'read'                        => true,
            'upload_files'                => true,
            'edit_csms_tool'              => true,
            'read_csms_tool'              => true,
            'delete_csms_tool'            => true,
            'edit_csms_tools'             => true,
            'publish_csms_tools'          => true, // Allows setting to 'pending'.
            'delete_csms_tools'           => true,
            'edit_published_csms_tools'   => true,
            'delete_published_csms_tools' => true,
            'csms_vote'                   => true,
            'csms_comment'                => true,
        ] );

        // Editor role: can manage all tool evaluations.
        add_role( 'csms_editor', __( 'CSMS Editor', 'asrg-csms' ), [
            'read'                         => true,
            'upload_files'                 => true,
            'edit_csms_tool'               => true,
            'read_csms_tool'               => true,
            'delete_csms_tool'             => true,
            'edit_csms_tools'              => true,
            'edit_others_csms_tools'       => true,
            'publish_csms_tools'           => true,
            'read_private_csms_tools'      => true,
            'delete_csms_tools'            => true,
            'delete_private_csms_tools'    => true,
            'delete_published_csms_tools'  => true,
            'delete_others_csms_tools'     => true,
            'edit_private_csms_tools'      => true,
            'edit_published_csms_tools'    => true,
            'csms_vote'                    => true,
            'csms_comment'                 => true,
            'csms_moderate_comments'       => true,
        ] );

        // Grant feedback capabilities to subscribers.
        $subscriber = get_role( 'subscriber' );
        if ( $subscriber ) {
            $subscriber->add_cap( 'csms_vote' );
            $subscriber->add_cap( 'csms_comment' );
        }

        // Grant all CSMS capabilities to administrators.
        $admin = get_role( 'administrator' );
        if ( $admin ) {
            foreach ( self::CPT_CAPS as $cap ) {
                $admin->add_cap( $cap );
            }
            foreach ( self::FEEDBACK_CAPS as $cap ) {
                $admin->add_cap( $cap );
            }
        }
    }

    /**
     * Remove custom roles on plugin deactivation.
     */
    public static function unregister(): void {
        remove_role( 'csms_vendor' );
        remove_role( 'csms_editor' );

        $all_caps = array_merge( self::CPT_CAPS, self::FEEDBACK_CAPS );

        foreach ( [ 'subscriber', 'administrator' ] as $role_name ) {
            $role = get_role( $role_name );
            if ( $role ) {
                foreach ( $all_caps as $cap ) {
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

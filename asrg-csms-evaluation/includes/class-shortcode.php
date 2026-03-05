<?php
/**
 * Shortcode handler — loads the React app into a WordPress page.
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class ASRG_CSMS_Shortcode {

    public function register(): void {
        add_shortcode( 'csms_evaluation', [ $this, 'render' ] );
    }

    /**
     * Render the shortcode — outputs the React mount point and enqueues scripts.
     */
    public function render( $atts ): string {
        $this->enqueue_assets();

        return '<div id="csms-evaluation-root"></div>';
    }

    /**
     * Enqueue the React app bundle and pass WordPress context.
     */
    private function enqueue_assets(): void {
        $dist_dir = ASRG_CSMS_PLUGIN_DIR . 'dist/';
        $dist_url = ASRG_CSMS_PLUGIN_URL . 'dist/';

        // Find the hashed asset filenames from the Vite manifest.
        $manifest_path = $dist_dir . '.vite/manifest.json';

        if ( ! file_exists( $manifest_path ) ) {
            // Development fallback: load from Vite dev server.
            if ( defined( 'ASRG_CSMS_DEV' ) && ASRG_CSMS_DEV ) {
                wp_enqueue_script(
                    'csms-evaluation-dev',
                    'http://localhost:5173/src/main.tsx',
                    [],
                    null,
                    true
                );
                $this->localize_script( 'csms-evaluation-dev' );
                return;
            }
            return;
        }

        $manifest = json_decode( file_get_contents( $manifest_path ), true );

        // Enqueue the main JS bundle.
        if ( isset( $manifest['src/main.tsx'] ) ) {
            $entry = $manifest['src/main.tsx'];

            // Enqueue CSS if present.
            if ( ! empty( $entry['css'] ) ) {
                foreach ( $entry['css'] as $index => $css_file ) {
                    wp_enqueue_style(
                        'csms-evaluation-css-' . $index,
                        $dist_url . $css_file,
                        [],
                        ASRG_CSMS_VERSION
                    );
                }
            }

            // Enqueue JS.
            wp_enqueue_script(
                'csms-evaluation-app',
                $dist_url . $entry['file'],
                [],
                ASRG_CSMS_VERSION,
                true
            );

            // Add module type for Vite output.
            add_filter( 'script_loader_tag', function ( $tag, $handle ) {
                if ( 'csms-evaluation-app' === $handle ) {
                    return str_replace( ' src', ' type="module" src', $tag );
                }
                return $tag;
            }, 10, 2 );

            $this->localize_script( 'csms-evaluation-app' );
        }
    }

    /**
     * Pass WordPress context to the React app via wp_localize_script.
     */
    private function localize_script( string $handle ): void {
        $user_data = null;

        if ( is_user_logged_in() ) {
            $user    = wp_get_current_user();
            $user_data = [
                'id'     => $user->ID,
                'name'   => $user->display_name,
                'email'  => $user->user_email,
                'avatar' => get_avatar_url( $user->ID, [ 'size' => 48 ] ),
                'role'   => ASRG_CSMS_Roles::get_csms_role( $user->ID ),
            ];
        }

        wp_localize_script( $handle, 'csmsConfig', [
            'apiBase'    => esc_url_raw( rest_url( 'csms/v1/' ) ),
            'nonce'      => wp_create_nonce( 'wp_rest' ),
            'isLoggedIn' => is_user_logged_in(),
            'loginUrl'   => wp_login_url( get_permalink() ),
            'user'       => $user_data,
            'pluginUrl'  => ASRG_CSMS_PLUGIN_URL,
            'version'    => ASRG_CSMS_VERSION,
        ] );
    }
}

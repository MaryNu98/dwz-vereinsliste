<?php
/**
 * Plugin Name: DWZ Vereinsliste
 * Plugin URI: https://github.com/MaryNu98
 * Description: Zeigt die DWZ-Liste eines Schachvereins via Widget. Die Daten werden von der API des Deutschen Schachbundes abgerufen.
 * Version: 1.0.0
 * Author: Marius Nürenberg
 * Author URI: https://github.com/MaryNu98
 * License: GPL v2 or later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain: dwz-verein-list
 * Domain Path: /languages
 */

// Sicherheit: Direkten Zugriff verhindern
if (!defined('ABSPATH')) {
    exit;
}

// Plugin-Konstanten
define('DWZ_VL_PLUGIN_DIR', plugin_dir_path(__FILE__));
define('DWZ_VL_PLUGIN_URL', plugin_dir_url(__FILE__));
define('DWZ_VL_VERSION', '1.0.0');

// Includes laden
require_once DWZ_VL_PLUGIN_DIR . 'includes/class-dwz-api.php';
require_once DWZ_VL_PLUGIN_DIR . 'includes/class-fide-sqlite.php';
require_once DWZ_VL_PLUGIN_DIR . 'includes/class-dwz-block.php';

/**
 * Plugin-Aktivierung
 */
function dwz_verein_list_activate() {
    FIDE_SQLite::initialize_database();
    dwz_verein_list_schedule_next_monthly_update();
}
register_activation_hook(__FILE__, 'dwz_verein_list_activate');

/**
 * Plugin-Deaktivierung
 */
function dwz_verein_list_deactivate() {
    wp_clear_scheduled_hook( 'dwz_verein_list_monthly_fide_update' );
}
register_deactivation_hook(__FILE__, 'dwz_verein_list_deactivate');

/**
 * Berechne den nächsten 1. des Monats um 08:00 Uhr
 */
function dwz_verein_list_next_first_of_month() {
    $timezone = function_exists( 'wp_timezone_string' ) ? wp_timezone_string() : get_option( 'timezone_string' );
    $timezone = $timezone && is_string( $timezone ) ? $timezone : 'UTC';
    $tz = new DateTimeZone( $timezone );
    $now = new DateTime( 'now', $tz );

    $first_of_month = new DateTime( $now->format( 'Y-m-01 08:00:00' ), $tz );

    if ( $first_of_month <= $now ) {
        $first_of_month->modify( '+1 month' );
    }

    return $first_of_month->getTimestamp();
}

function dwz_verein_list_schedule_next_monthly_update() {
    if ( ! wp_next_scheduled( 'dwz_verein_list_monthly_fide_update' ) ) {
        wp_schedule_single_event( dwz_verein_list_next_first_of_month(), 'dwz_verein_list_monthly_fide_update' );
    }
}

function dwz_verein_list_monthly_fide_update() {
    FIDE_SQLite::initialize_database();
    $result = FIDE_SQLite::update_from_fide();

    if ( is_wp_error( $result ) ) {
        dwz_verein_list_schedule_next_monthly_update();
        return;
    }

    dwz_verein_list_schedule_next_monthly_update();
}
add_action( 'dwz_verein_list_monthly_fide_update', 'dwz_verein_list_monthly_fide_update' );

/**
 * CSS-Dateien laden (Frontend)
 */
function dwz_verein_list_enqueue_styles() {
    wp_enqueue_style(
        'dwz-verein-list-styles',
        DWZ_VL_PLUGIN_URL . 'assets/dwz-styles.css',
        array(),
        DWZ_VL_VERSION
    );
}
add_action('wp_enqueue_scripts', 'dwz_verein_list_enqueue_styles');

/**
 * Frontend-Script laden
 */
function dwz_verein_list_enqueue_scripts() {
    wp_enqueue_script(
        'dwz-verein-list-frontend',
        DWZ_VL_PLUGIN_URL . 'assets/js/frontend.js',
        array(),
        DWZ_VL_VERSION,
        true
    );

    wp_localize_script(
        'dwz-verein-list-frontend',
        'dwzVereinList',
        array(
            'ajaxUrl' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('dwz_verein_list_player_details'),
            'labels' => array(
                'loading' => __('Spielerdaten werden geladen...', 'dwz-verein-list'),
                'error' => __('Die Spielerdaten konnten nicht geladen werden.', 'dwz-verein-list'),
                'eloDevelopment' => __('Elo-Entwicklung', 'dwz-verein-list'),
                'noHistory' => __('Keine Elo-Historie verfügbar.', 'dwz-verein-list'),
            ),
        )
    );
}
add_action('wp_enqueue_scripts', 'dwz_verein_list_enqueue_scripts');

/**
 * Block initialisieren und registrieren
 */
function dwz_verein_list_register_block() {
    if ( ! function_exists( 'register_block_type' ) ) {
        return;
    }

    register_block_type(
        DWZ_VL_PLUGIN_DIR . 'block.json',
        array(
            'render_callback' => 'dwz_verein_list_render_block'
        )
    );
}
add_action( 'init', 'dwz_verein_list_register_block', 10 );

/**
* REST-API-Endpunkt für Vereinsliste
* Umgeht CORS-Probleme im Gutenberg-Editor.
*/
function dwz_verein_list_register_rest_routes() {
    register_rest_route(
    'dwz/v1',
    '/clubs',
    array(
        'methods'  => 'GET',
        'callback' => 'dwz_verein_list_get_clubs',
        'permission_callback' => '__return_true',
        )
    );
}

add_action( 'rest_api_init', 'dwz_verein_list_register_rest_routes' );

/**
 * Admin-Seite für Cache-Verwaltung hinzufügen
 */
function dwz_verein_list_add_admin_page() {
    add_management_page(
        __( 'DWZ Cache verwalten', 'dwz-verein-list' ),
        __( 'DWZ Cache', 'dwz-verein-list' ),
        'manage_options',
        'dwz-verein-list-cache',
        'dwz_verein_list_cache_admin_page'
    );
}
add_action( 'admin_menu', 'dwz_verein_list_add_admin_page' );
add_action( 'admin_enqueue_scripts', 'dwz_verein_list_admin_enqueue_scripts' );
add_action( 'wp_ajax_dwz_vl_manual_fide_update', 'dwz_verein_list_ajax_manual_fide_update' );
add_action( 'wp_ajax_dwz_vl_fide_update_progress', 'dwz_verein_list_ajax_fide_update_progress' );

function dwz_verein_list_admin_enqueue_scripts( $hook ) {
    if ( 'tools_page_dwz-verein-list-cache' !== $hook ) {
        return;
    }

    wp_enqueue_script(
        'dwz-verein-list-admin',
        DWZ_VL_PLUGIN_URL . 'assets/js/admin.js',
        array(),
        DWZ_VL_VERSION,
        true
    );

    wp_localize_script(
        'dwz-verein-list-admin',
        'dwzVereinListAdmin',
        array(
            'ajaxUrl' => admin_url( 'admin-ajax.php' ),
            'nonce' => wp_create_nonce( 'dwz_vl_manual_fide_update' ),
            'labels' => array(
                'startUpdate' => __( 'FIDE-Datei jetzt laden', 'dwz-verein-list' ),
                'updateRunning' => __( 'Aktualisiere FIDE-Datei...', 'dwz-verein-list' ),
                'starting' => __( 'Starte Aktualisierung...', 'dwz-verein-list' ),
                'error' => __( 'Fehler bei der Aktualisierung.', 'dwz-verein-list' ),
                'never' => __( 'Nie', 'dwz-verein-list' ),
            ),
        )
    );
}

function dwz_verein_list_ajax_manual_fide_update() {
    check_ajax_referer( 'dwz_vl_manual_fide_update', 'nonce' );

    if ( ! current_user_can( 'manage_options' ) ) {
        wp_send_json_error( array( 'message' => __( 'Zugriff verweigert.', 'dwz-verein-list' ) ) );
    }

    $progress = FIDE_SQLite::get_import_progress();
    if ( isset( $progress['status'] ) && 'running' === $progress['status'] ) {
        wp_send_json_error( array( 'message' => __( 'Ein Import läuft bereits.', 'dwz-verein-list' ) ) );
    }

    FIDE_SQLite::set_import_progress(
        array(
            'status' => 'running',
            'percent' => 0,
            'message' => __( 'Lade FIDE-Datei herunter...', 'dwz-verein-list' ),
            'processed' => 0,
            'total' => 0,
        )
    );

    FIDE_SQLite::initialize_database();

    $result = FIDE_SQLite::update_from_fide();
    if ( is_wp_error( $result ) ) {
        FIDE_SQLite::set_import_progress(
            array(
                'status' => 'error',
                'percent' => 0,
                'message' => $result->get_error_message(),
                'processed' => 0,
                'total' => 0,
            )
        );
        wp_send_json_error( array( 'message' => $result->get_error_message() ) );
    }

    wp_send_json_success( array( 'message' => __( 'FIDE-Datei wird importiert.', 'dwz-verein-list' ) ) );
}

function dwz_verein_list_ajax_fide_update_progress() {
    check_ajax_referer( 'dwz_vl_manual_fide_update', 'nonce' );

    if ( ! current_user_can( 'manage_options' ) ) {
        wp_send_json_error( array( 'message' => __( 'Zugriff verweigert.', 'dwz-verein-list' ) ) );
    }

    $progress = FIDE_SQLite::get_import_progress();
    if ( 'completed' === $progress['status'] ) {
        $progress['last_update'] = FIDE_SQLite::get_last_import_time();
    }

    wp_send_json_success( $progress );
}

/**
 * Admin-Seite für Cache-Verwaltung rendern
 */
function dwz_verein_list_cache_admin_page() {
    if ( ! current_user_can( 'manage_options' ) ) {
        wp_die( esc_html__( 'Zugriff verweigert.', 'dwz-verein-list' ) );
    }

    $last_import = FIDE_SQLite::get_last_import_time();
    $progress = FIDE_SQLite::get_import_progress();

    $notice = '';

    if ( isset( $_POST['dwz_vl_cache_action'] ) && check_admin_referer( 'dwz_vl_clear_cache' ) ) {
        $action = isset( $_POST['dwz_vl_cache_action'] ) ? sanitize_key( wp_unslash( $_POST['dwz_vl_cache_action'] ) ) : '';

        if ( 'full' === $action ) {
            FIDE_SQLite::clear_cache();
            DWZ_API::clear_cache();
            delete_transient( 'dwz_clubs_list' );
            $notice = __( 'Der komplette Cache wurde gelöscht.', 'dwz-verein-list' );
        } elseif ( 'player' === $action ) {
            $fide_id = isset( $_POST['dwz_vl_fide_id'] ) ? sanitize_text_field( wp_unslash( $_POST['dwz_vl_fide_id'] ) ) : '';

            if ( $fide_id !== '' ) {
                FIDE_SQLite::clear_cache( $fide_id );
                /* translators: %s: FIDE ID */
                $notice = sprintf( esc_html__( 'Der Cache für die FIDE-ID %s wurde gelöscht.', 'dwz-verein-list' ), esc_html( $fide_id ) );
            } else {
                $notice = __( 'Bitte gib eine FIDE-ID ein.', 'dwz-verein-list' );
            }
        } elseif ( 'club' === $action ) {
            $vkz = isset( $_POST['dwz_vl_vkz'] ) ? sanitize_text_field( wp_unslash( $_POST['dwz_vl_vkz'] ) ) : '';

            if ( $vkz !== '' ) {
                DWZ_API::clear_cache( $vkz );
                /* translators: %s: club ID */
                $notice = sprintf( esc_html__( 'Der Cache für die VKZ %s wurde gelöscht.', 'dwz-verein-list' ), esc_html( $vkz ) );
            } else {
                $notice = __( 'Bitte gib eine VKZ ein.', 'dwz-verein-list' );
            }
        }
    }

    echo '<div class="wrap">';
    echo '<h1>' . esc_html__( 'DWZ-Vereinsliste verwalten', 'dwz-verein-list' ) . '</h1>';
    echo '<p>' . esc_html__( 'Hier kannst du den Cache des Plugins komplett oder gezielt für einzelne Vereine löschen.', 'dwz-verein-list' ) . '</p>';

    echo '<h2>' . esc_html__( 'FIDE-Datei aktualisieren', 'dwz-verein-list' ) . '</h2>';
    echo '<p>' . esc_html__( 'Die FIDE-Datei wird automatisch am 1. des Monats geladen. Hier kannst du sie manuell aktualisieren und den Importfortschritt verfolgen.', 'dwz-verein-list' ) . '</p>';
    echo '<p><strong>' . esc_html__( 'Letzte FIDE-Aktualisierung:', 'dwz-verein-list' ) . '</strong> <span id="dwz-vl-fide-last-updated">' . esc_html( $last_import ? date_i18n( get_option( 'date_format' ) . ' ' . get_option( 'time_format' ), strtotime( $last_import ) ) : esc_html__( 'Nie', 'dwz-verein-list' ) ) . '</span></p>';
    echo '<button id="dwz-vl-fide-update-button" class="button button-primary">' . esc_html__( 'FIDE-Datei manuell laden', 'dwz-verein-list' ) . '</button>';
    echo '<div id="dwz-vl-fide-progress-container" style="display:none; margin-top:1em; max-width:640px;">
            <div id="dwz-vl-fide-progress-bar" style="background:#f1f1f1; border:1px solid #ccd0d4; border-radius:3px; height:22px; overflow:hidden;">
                <div id="dwz-vl-fide-progress-fill" style="background:#0073aa; width:0%; height:100%; transition:width .3s ease;"></div>
            </div>
            <p id="dwz-vl-fide-progress-text" style="margin:.5em 0 0 0; font-weight:600;">&nbsp;</p>
        </div>';

    if ( $notice !== '' ) {
        echo '<div class="notice notice-success is-dismissible"><p>' . esc_html( $notice ) . '</p></div>';
    }

    echo '<h2>' . esc_html__( 'Kompletten Cache löschen', 'dwz-verein-list' ) . '</h2>';
    echo '<form method="post">';
    wp_nonce_field( 'dwz_vl_clear_cache' );
    echo '<input type="hidden" name="dwz_vl_cache_action" value="full" />';
    submit_button( __( 'Kompletten Cache löschen', 'dwz-verein-list' ), 'delete' );
    echo '</form>';

    echo '<h2>' . esc_html__( 'Cache für einzelnen Verein löschen', 'dwz-verein-list' ) . '</h2>';
    echo '<form method="post">';
    wp_nonce_field( 'dwz_vl_clear_cache' );
    echo '<input type="hidden" name="dwz_vl_cache_action" value="club" />';
    echo '<table class="form-table"><tbody><tr><th scope="row"><label for="dwz_vl_vkz">' . esc_html__( 'VKZ', 'dwz-verein-list' ) . '</label></th><td><input type="text" id="dwz_vl_vkz" name="dwz_vl_vkz" class="regular-text" value="" /></td></tr></tbody></table>';
    submit_button( __( 'Cache für diese VKZ löschen', 'dwz-verein-list' ), 'secondary' );
    echo '</form>';
    echo '</div>';
}

/**
* Vereinsliste vom DSB abrufen
*/
function dwz_verein_list_get_clubs() {
    $cached = get_transient( 'dwz_clubs_list' );
    if ( false !== $cached ) {
        return rest_ensure_response( $cached );
    }

    $response = wp_remote_get(
        'https://schachde-apps.liga.nu/dsbwertungsportal/rs/dwz/dwzliste/clubs',
        array(
            'timeout' => 20,
            'headers' => array(
                'Accept' => 'application/json',
            ),
        )
    );

    if ( is_wp_error( $response ) ) {
        return new WP_Error(
            'dwz_api_error',
            $response->get_error_message(),
            array( 'status' => 500 )
        );
    }

    $body = wp_remote_retrieve_body( $response );

    $data = json_decode( $body, true );

    if ( json_last_error() !== JSON_ERROR_NONE ) {
        return new WP_Error(
            'dwz_json_error',
            'Fehler beim Verarbeiten der API-Antwort.',
        array( 'status' => 500 )
        );
    }

    set_transient(
        'dwz_clubs_list',
        $data,
        DAY_IN_SECONDS
    );

    return rest_ensure_response( $data );
}


/**
 * Block rendern auf dem Frontend
 */
function dwz_verein_list_render_block( $attributes ) {
    return DWZ_Block::render_block( $attributes );
}

/**
 * Elo-Historie fuer die aufklappbaren Spielerdetails laden
 */
function dwz_verein_list_ajax_player_details() {
    check_ajax_referer('dwz_verein_list_player_details', 'nonce');

    $fide_id = isset($_POST['fideId']) ? absint($_POST['fideId']) : 0;

    if (empty($fide_id)) {
        wp_send_json_success(
            array(
                'hasFideId' => false,
                'fideData' => array(),
            )
        );
    }

    $player = FIDE_SQLite::get_player_by_fide_id($fide_id);
    if (is_wp_error($player)) {
        wp_send_json_error(
            array(
                'message' => $player->get_error_message(),
            )
        );
    }

    if (empty($player)) {
        wp_send_json_success(
            array(
                'hasFideId' => false,
                'fideData' => array(),
            )
        );
    }

    wp_send_json_success(
        array(
            'hasFideId' => true,
            'fideData' => $player,
        )
    );
}
add_action('wp_ajax_dwz_verein_list_player_details', 'dwz_verein_list_ajax_player_details');
add_action('wp_ajax_nopriv_dwz_verein_list_player_details', 'dwz_verein_list_ajax_player_details');


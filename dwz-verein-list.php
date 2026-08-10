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
require_once DWZ_VL_PLUGIN_DIR . 'includes/class-dwz-block.php';

/**
 * Plugin-Aktivierung
 */
function dwz_verein_list_activate() {
}
register_activation_hook(__FILE__, 'dwz_verein_list_activate');

/**
 * Plugin-Deaktivierung
 */
function dwz_verein_list_deactivate() {
}
register_deactivation_hook(__FILE__, 'dwz_verein_list_deactivate');


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
 * Block rendern auf dem Frontend
 */
function dwz_verein_list_render_block( $attributes ) {
    return DWZ_Block::render_block( $attributes );
}

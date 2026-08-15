<?php
/**
 * DWZ API Klasse
 *
 * Verwaltet die Kommunikation mit der API des Deutschen Schachbundes
 */

class DWZ_API {
    
    /**
     * API Base URL
     */
    const API_BASE_URL = 'https://www.schachbund.de/wertungsportal-api/vereinsliste';
    
    /**
     * Cache-Dauer in Sekunden (24 Stunden = 86400 Sekunden)
     */
    const CACHE_DURATION = 86400;
    
public static function get_verein_list($vkz, $apiToken) {

    if (empty($vkz)) {
        return new WP_Error(
            'invalid_vkz',
            __('VKZ-Nummer ist erforderlich', 'dwz-verein-list')
        );
    }

    if (empty($apiToken)) {
        return new WP_Error(
            'invalid_api_token',
            __('API-Token ist erforderlich', 'dwz-verein-list')
        );
    }

    $cache_key = 'dwz_verein_list_' . sanitize_key($vkz);

    $cached_data = get_transient($cache_key);
    if ($cached_data !== false) {
        return $cached_data;
    }

    $url = add_query_arg(
        array(
            'vkz' => sanitize_text_field($vkz),
            'token' => sanitize_text_field($apiToken)
        ),
        self::API_BASE_URL
    );

    $response = wp_remote_get(
        $url,
        array(
            'timeout'   => 10,
            'sslverify' => true,
            'user-agent'=> 'WordPress-DWZ-Vereinsliste/1.0 (VKZ: ' . sanitize_text_field($vkz) . ')'
        )
    );

    if (is_wp_error($response)) {
        return new WP_Error(
            'api_error',
            $response->get_error_message()
        );
    }

    $status_code = wp_remote_retrieve_response_code($response);

    if ($status_code !== 200) {
        return new WP_Error(
            'invalid_response',
            sprintf(
                /* translators: %d: HTTP status code */
                __('API-Fehler: HTTP %d', 'dwz-verein-list'),
                $status_code
            )
        );
    }

    $body = wp_remote_retrieve_body($response);

    $data = json_decode($body, true);

    if (json_last_error() !== JSON_ERROR_NONE) {
        return new WP_Error(
            'parse_error',
            json_last_error_msg()
        );
    }

    if (
        !isset($data['spieler']) ||
        !is_array($data['spieler'])
    ) {
        return new WP_Error(
            'parse_error',
            __('Keine Spielerdaten gefunden', 'dwz-verein-list')
        );
    }

    set_transient(
        $cache_key,
        $data,
        self::CACHE_DURATION
    );

    return $data;
}
    
    /**
     * Cache leeren
     * @param string $vkz Optional: VKZ-Nummer zum gezielten Löschen
     */
    public static function clear_cache($vkz = null) {
        if ($vkz) {
            $cache_key = 'dwz_verein_list_' . sanitize_key($vkz);
            delete_transient($cache_key);
            return true;
        }

        delete_transient( 'dwz_clubs_list' );
        wp_cache_flush();

        return true;
    }
}

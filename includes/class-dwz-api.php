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
    const API_BASE_URL = 'https://schachde-apps.liga.nu/dsbwertungsportal/rs/dwz/dwzliste/persons';
    
    /**
     * Cache-Dauer in Sekunden (24 Stunden = 86400 Sekunden)
     */
    const CACHE_DURATION = 86400;
    
public static function get_verein_list($vkz) {

    if (empty($vkz)) {
        return new WP_Error(
            'invalid_vkz',
            __('VKZ-Nummer ist erforderlich', 'dwz-verein-list')
        );
    }

    $cache_key = 'dwz_verein_list_' . sanitize_key($vkz);

    $cached_data = get_transient($cache_key);
    if ($cached_data !== false) {
        return $cached_data;
    }

    $url = add_query_arg(
        array(
            'vkz' => sanitize_text_field($vkz)
        ),
        self::API_BASE_URL
    );

    $response = wp_remote_get(
        $url,
        array(
            'timeout'   => 15,
            'sslverify' => true,
            'user-agent'=> 'WordPress/DWZ-Verein-List'
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

    $json = json_decode($body, true);

    if (json_last_error() !== JSON_ERROR_NONE) {
        return new WP_Error(
            'parse_error',
            json_last_error_msg()
        );
    }

    if (
        !isset($json['data']) ||
        !is_array($json['data'])
    ) {
        return new WP_Error(
            'parse_error',
            __('Keine Spielerdaten gefunden', 'dwz-verein-list')
        );
    }

    $data = $json['data'];

    usort($data, function ($a, $b) {

        $rating_a = isset($a['rating']) ? (int)$a['rating'] : 0;
        $rating_b = isset($b['rating']) ? (int)$b['rating'] : 0;

        if ($rating_a === $rating_b) {

            $index_a = isset($a['index']) ? (int)$a['index'] : 0;
            $index_b = isset($b['index']) ? (int)$b['index'] : 0;

            return $index_b <=> $index_a;
        }

        return $rating_b <=> $rating_a;
    });

    set_transient(
        $cache_key,
        $data,
        self::CACHE_DURATION
    );

    return $data;
}

    
    /**
     * Cache leeren
     *
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

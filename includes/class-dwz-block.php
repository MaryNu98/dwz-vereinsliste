<?php
/**
 * DWZ Vereinsliste Block
 *
 * Gutenberg Block für die Anzeige der DWZ-Liste
 */

class DWZ_Block {
    
    /**
     * Block initialisieren
     */
    public static function init() {
        register_block_type(
            DWZ_VL_PLUGIN_DIR . 'block.json',
            array(
                'render_callback' => array(self::class, 'render_block'),
            )
        );
    }
    
    /**
     * Block Frontend rendern
     *
     * @param array $attributes Block-Attribute
     * @return string HTML-Ausgabe
     */
    public static function render_block($attributes) {
        $vkz = isset($attributes['vkz']) ? sanitize_text_field($attributes['vkz']) : '';
        $apiToken = isset($attributes['apiToken']) ? sanitize_text_field($attributes['apiToken']) : '';
        $show_elo = isset($attributes['showElo']) ? (bool) $attributes['showElo'] : true;
        $show_rapid = isset($attributes['showRapid']) ? (bool) $attributes['showRapid'] : true;
        $show_blitz = isset($attributes['showBlitz']) ? (bool) $attributes['showBlitz'] : true;
        $show_status = isset($attributes['showStatus']) ? (bool) $attributes['showStatus'] : true;
        $show_nation = isset($attributes['showNation']) ? (bool) $attributes['showNation'] : true;
        $show_title = isset($attributes['showTitle']) ? (bool) $attributes['showTitle'] : true;
        $showIndex = isset($attributes['showIndex']) ? (bool) $attributes['showIndex'] : true;
        $output = '<div class="wp-block-dwz-verein-list-dwz-list">';
        
        // VKZ-Nummer prüfen
        if (empty($vkz)) {
            $output .= '<div class="dwz-block-error">' . 
                       esc_html__('Kein Verein konfiguriert.', 'dwz-verein-list') . 
                       '</div>';
        } else {
            // API-Token prüfen
            if (empty($apiToken)) {
                $output .= '<div class="dwz-block-error">' .
                           esc_html__('Kein API-Token konfiguriert.', 'dwz-verein-list') . 
                           '</div>';
            }
            // DWZ-Liste abrufen
            $data = DWZ_API::get_verein_list($vkz, $apiToken);
            
            if (is_wp_error($data)) {
                $output .= '<div class="dwz-block-error">';
                $output .= '<strong>' . esc_html__('Fehler beim Abrufen der DWZ-Liste:', 'dwz-verein-list') . '</strong><br>';
                $output .= wp_kses_post($data->get_error_message());
                $output .= '</div>';
            } else {
                $output .= self::render_table($vkz, $data, $show_elo, $show_rapid, $show_blitz, $show_status, $show_nation, $show_title, $showIndex);
            }
        }
        
        $output .= '</div>';
        
        return $output;
    }
    
    /**
     * Tabelle der DWZ-Daten rendern
     *
     * @param string $vkz          Vereinskennzeichen
     * @param array  $data         Array mit Spielerdaten
     * @param bool   $show_elo     FIDE-Elo anzeigen und laden
     * @param bool   $show_rapid   Rapid-Elo anzeigen
     * @param bool   $show_blitz   Blitz-Elo anzeigen
     * @param bool   $show_status  Status (passiv/aktiv) anzeigen
     * @param bool   $show_nation  Nationalität anzeigen
     * @param bool   $show_title   Titel anzeigen
     * @param bool   $showIndex    Index anzeigen
     * @return string              HTML-Ausgabe der Tabelle
     */
    private static function render_table($vkz, $data, $show_elo = true, $show_rapid = true, $show_blitz = true, $show_status = true, $show_nation = true, $show_title = true, $showIndex = true) {
        if (!is_array($data) || empty($data)) {
            return '<p class="dwz-no-data">' . esc_html__('Keine Daten verfügbar', 'dwz-verein-list') . '</p>';
        }

        $data_date = isset($data['stand']) ? sanitize_text_field($data['stand']) : '';
        $formatted_data_date = self::format_data_date($data_date);

        $playerList = self::get_player_list($data);


        // Tabelle erzeugen
        $html = '<div class="dwz-container">';
        $html .= '<table class="dwz-table">';
        $html .= '<thead>';
        $html .= '<tr>';
        $html .= '<th class="dwz-col-platz">' . esc_html__('Nr.', 'dwz-verein-list') . '</th>';
        $html .= '<th class="dwz-col-name">' . esc_html__('Name', 'dwz-verein-list') . '</th>';
         
         // Titel-Spalte
        if($show_title){
            $html .= '<th class="dwz-col-title" style="text-align: center;" title="' . esc_attr__('FIDE-Titel', 'dwz-verein-list') . '">' . esc_html__('T', 'dwz-verein-list') . '</th>';
        }
        // Status-Spalte
        if($show_status){
            $html .= '<th class="dwz-col-status" style="text-align: center;" title="' . esc_attr__('Status: P = Passiv', 'dwz-verein-list') . '">' . esc_html__('S', 'dwz-verein-list') . '</th>';
        }
        // Nationalität-Spalte
        if($show_nation){
            $html .= '<th class="dwz-col-nation" style="text-align: center;" title="' . esc_attr__('Nationalität', 'dwz-verein-list') . '">' . esc_html__('Land', 'dwz-verein-list') . '</th>';
        }
       
        if($showIndex){
            $html .= '<th class="dwz-col-dwz" style="text-align: right;">' . esc_html__('DWZ', 'dwz-verein-list') . '</th>';
            $html .= '<th class="dwz-col-dwz" style="text-align: center; width: 20px;">' . esc_html__('-', 'dwz-verein-list') . '</th>';
            $html .= '<th class="dwz-col-dwz" style="text-align: left;">' . esc_html__('Index', 'dwz-verein-list') . '</th>';
        }else{
            $html .= '<th class="dwz-col-dwz" style="text-align: center;">' . esc_html__('DWZ', 'dwz-verein-list') . '</th>';
        }


        if ($show_elo) {
            $html .= '<th class="dwz-col-elo" style="text-align: center;">' . esc_html__('Elo', 'dwz-verein-list') . '</th>';
        }
        
        if ($show_rapid) {
            $html .= '<th class="dwz-col-elo" style="text-align: center;">' . esc_html__('Rapid', 'dwz-verein-list') . '</th>';
        }
        
        if ($show_blitz) {
            $html .= '<th class="dwz-col-elo" style="text-align: center;">' . esc_html__('Blitz', 'dwz-verein-list') . '</th>';
        }
        
        $html .= '</tr>';
        $html .= '</thead>';
        $html .= '<tbody>';
        

        // Für jeden Spieler in den Daten wird eine Tabellenzeile erstellt, die die entsprechenden Informationen enthält. Die FIDE-Daten werden nur dargestellt, wenn die Option aktiviert ist und eine FIDE-ID vorhanden ist.
        $counter = 1;
        foreach ($playerList as $spieler) {
            $spieler_json = esc_attr(wp_json_encode($spieler));
            $nuLigaPersonId = isset($spieler['id']) ? sanitize_text_field($spieler['id']) : '';
            $lastname = isset($spieler['nachname']) ? sanitize_text_field($spieler['nachname']) : '';
            $firstname = isset($spieler['vorname']) ? sanitize_text_field($spieler['vorname']) : '';
            $dwz = isset($spieler['dwz']) ? intval($spieler['dwz']) : 0;
            $dwzindex = isset($spieler['dwzIndex']) ? intval($spieler['dwzIndex']) : 0;
            $fideId = isset($spieler['fideId']) ? intval($spieler['fideId']) : 0;
            $standard_elo = isset($spieler['elo']) ? intval($spieler['elo']) : 0;
            $rapid_elo = isset($spieler['eloSchnell']) ? intval($spieler['eloSchnell']) : 0;
            $blitz_elo = isset($spieler['eloBlitz']) ? intval($spieler['eloBlitz']) : 0;
            $federation = isset($spieler['nation']) ? sanitize_text_field($spieler['nation']) : '';
            $title = isset($spieler['titel']) ? sanitize_text_field($spieler['titel']) : '';
            $status = isset($spieler['status']) ? sanitize_text_field($spieler['status']) : '';

            $dsb_profile_url = '';
            $fide_profile_url = '';

            if (!empty($nuLigaPersonId)) {
                $dsb_profile_url = 'https://www.schachbund.de/dwz-spieler/' . rawurlencode($nuLigaPersonId) . '.html';
            }

            if (!empty($fideId)) {
                $fide_profile_url = 'https://ratings.fide.com/profile/' . rawurlencode((string) $fideId);
            }


            $html .= '<tr>';

            // Platz-Spalte
            $html .= '<td class="dwz-col-platz">' . intval($counter) . '.</td>';
            
            // Name-Spalte mit Titel, Nachname und Vorname
            $html .= '<td class="dwz-col-name">';
            if (!empty($dsb_profile_url)) {
                $html .= '<a href="' . esc_url($dsb_profile_url) . '" target="_blank" rel="noopener noreferrer" style="color:inherit;">';
            } else {
                $html .= '<span>';
            }
            $html .= esc_html($lastname . ', ' . $firstname);
            if (!empty($dsb_profile_url)) {
                $html .= '</a>';
            } else {
                $html .= '</span>';
            }
            $html .= '</td>';
            
            // Titel-Spalte
            if($show_title){
                $html .= '<td class="dwz-col-title" style="text-align: center;" title="' . esc_attr__('Titel: ' . $title, 'dwz-verein-list') . '">' . esc_html($title) . '</td>';
            }

            // Status-Spalte
            if($show_status){
                $status = $status === 'P' ? 'P' : ''; // Nur "P" für Passiv anzeigen, sonst leer
                $html .= '<td class="dwz-col-status" style="text-align: center;" title="' . esc_attr__('Status: ' . ($status === 'P' ? 'Passiv' : 'Aktiv'), 'dwz-verein-list') . '">' . esc_html($status) . '</td>';
            }

            // Nationalität-Spalte
            if($show_nation){
                if (empty($federation)) {
                    $html .= '<td class="dwz-col-nation"></td>';
                } else {
                    $flag_markup = self::get_country_flag_markup($federation);
                    $nation_markup = !empty($fide_profile_url)
                        ? '<a href="' . esc_url($fide_profile_url) . '" target="_blank" rel="noopener noreferrer" style="color:inherit;">' . esc_html($federation) . '</a>'
                        : esc_html($federation);
                    $html .= '<td class="dwz-col-nation" style="text-align:center; width:72px; min-width:72px; white-space:nowrap; overflow:hidden;" title="' . esc_attr__('Nationalität: ' . self::get_country_name_from_code($federation), 'dwz-verein-list') . '">
                        <span style="display:inline-flex; align-items:center; justify-content:center; gap:6px; white-space:nowrap;">
                            ' . $flag_markup . '
                            <span>' . $nation_markup . '</span>
                        </span>
                    </td>';
                }
            } 
            
            // DWZ und Index in drei Spalten
            if ($dwz!=0){
                if($showIndex){
                    $html .= '<td class="dwz-col-dwz" style="text-align: right;"title="' . esc_attr__('DWZ', 'dwz-verein-list') . '">' . intval($dwz) . '</td>';
                    $html .= '<td class="dwz-col-dwz" style="text-align: center; width: 20px;">-</td>';
                    $html .= '<td class="dwz-col-dwz" style="text-align: left;"title="' . esc_attr__('Anzahl der DWZ-Auswertungen (Index)', 'dwz-verein-list') . '">' . intval($dwzindex) . '</td>';
                }else{
                    $html .= '<td class="dwz-col-dwz" style="text-align: center;"title="' . esc_attr__('DWZ', 'dwz-verein-list') . '">' . intval($dwz) . '</td>';
                }
            }else{
                $html .= '<td class="dwz-col-dwz"></td>';
                if($showIndex){
                    $html .= '<td class="dwz-col-dwz"></td>';
                    $html .= '<td class="dwz-col-dwz"></td>';
                }
            }

            // Elo-Spalten anzeigen
            if ($show_elo) {
                if ($standard_elo) {
                    $html .= '<td class="dwz-col-elo" style="text-align: center;"title="' . esc_attr__('Standard-Elo', 'dwz-verein-list') . '">' . intval($standard_elo) . '</td>';
                } else {
                    $html .= '<td class="dwz-col-elo" style="text-align: center;"title="' . esc_attr__('Standard-Elo', 'dwz-verein-list') . '"></td>';
                }
            }
            if ($show_rapid) {
                if ($rapid_elo) {
                    $html .= '<td class="dwz-col-elo" style="text-align: center;"title="' . esc_attr__('Rapid-Elo', 'dwz-verein-list') . '">' . intval($rapid_elo) . '</td>';
                } else {
                    $html .= '<td class="dwz-col-elo" style="text-align: center;"title="' . esc_attr__('Rapid-Elo', 'dwz-verein-list') . '"></td>';
                }
            }
            if ($show_blitz) {
                if ($blitz_elo) {
                    $html .= '<td class="dwz-col-elo" style="text-align: center;"title="' . esc_attr__('Blitz-Elo', 'dwz-verein-list') . '">' . intval($blitz_elo) . '</td>';
                } else {
                    $html .= '<td class="dwz-col-elo" style="text-align: center;"title="' . esc_attr__('Blitz-Elo', 'dwz-verein-list') . '"></td>';
                }
            }
            
            $html .= '</tr>';

            $counter++;
        }
        
        $html .= '</tbody>';
        $html .= '</table>';
        
        // Info-Text        
        $html .= '<p class="dwz-info">';
            $html .= sprintf(
                esc_html__('Daten vom Deutschen Schachbund (Stand: %s).', 'dwz-verein-list'),
                $formatted_data_date
            );
        
        $html .= '<br>';
        $html .= esc_html__('Erstellt mit dem Wordpress-Plugin "DWZ-Vereinsliste" von Marius Nürenberg.', 'dwz-verein-list');
        $html .= '</p>';
        
        $html .= '</div>';
        
        return $html;
    }

    private static function format_data_date($date_string) {
        if (empty($date_string)) {
            return '';
        }

        $timestamp = strtotime($date_string);
        if ($timestamp === false) {
            return sanitize_text_field($date_string);
        }

        return gmdate('d. F Y, H:i \U\h\r', $timestamp);
    }

    private static function get_country_flag_markup($federation) {
        if (empty($federation)) {
            return '';
        }

        $code = strtoupper(trim((string) $federation));
        $base_dir = trailingslashit(DWZ_VL_PLUGIN_DIR) . 'data/flags/svg/';
        $base_url = trailingslashit(DWZ_VL_PLUGIN_URL) . 'data/flags/svg/';
        $candidates = array();

        if ($code !== '') {
            $candidates[] = $base_dir . $code . '.svg';
            $candidates[] = $base_dir . strtolower($code) . '.svg';
        }

        $legacy_name = self::get_country_name_from_code($code);
        if ($legacy_name !== '') {
            $candidates[] = $base_dir . $legacy_name . '.svg';
            $candidates[] = $base_dir . str_replace(' ', '-', $legacy_name) . '.svg';
        }

        foreach ($candidates as $candidate) {
            if (file_exists($candidate)) {
                $url = str_replace(DWZ_VL_PLUGIN_DIR, DWZ_VL_PLUGIN_URL, $candidate);
                return '<img src="' . esc_url($url) . '" alt="' . esc_attr($code) . '" width="24" height="16" style="display:block; width:24px; height:16px; object-fit:cover; flex-shrink:0; vertical-align:middle;">';
            }
        }

        return '';
    }

    private static function get_player_list($data) {
        if (!is_array($data) || empty($data['spieler']) || !is_array($data['spieler'])) {
            return array();
        }

        $playerList = $data['spieler'];

        usort($playerList, function ($a, $b) {
            $dwzA = isset($a['dwz']) ? (int) $a['dwz'] : 0;
            $dwzB = isset($b['dwz']) ? (int) $b['dwz'] : 0;

            if ($dwzA === $dwzB) {
                return 0;
            }

            return ($dwzA < $dwzB) ? 1 : -1;
        });

        return $playerList;
    }

    private static function get_country_name_from_code($code) {
        $map = array(
            'AFG' => 'Afghanistan',
            'ALB' => 'Albanien',
            'ALG' => 'Algerien',
            'ASM' => 'Amerikanisch-Samoa',
            'AND' => 'Andorra',
            'AGO' => 'Angola',
            'AIA' => 'Anguilla',
            'ATA' => 'Antarktis',
            'ATG' => 'Antigua und Barbuda',
            'ARG' => 'Argentinien',
            'ARM' => 'Armenien',
            'ABW' => 'Aruba',
            'AUS' => 'Australien',
            'AUT' => 'Österreich',
            'AZE' => 'Aserbaidschan',
            'BHS' => 'Bahamas',
            'BHR' => 'Bahrain',
            'BGD' => 'Bangladesch',
            'BRB' => 'Barbados',
            'BLR' => 'Belarus',
            'BEL' => 'Belgien',
            'BLZ' => 'Belize',
            'BEN' => 'Benin',
            'BMU' => 'Bermuda',
            'BTN' => 'Bhutan',
            'BOL' => 'Bolivien',
            'BIH' => 'Bosnien und Herzegowina',
            'BWA' => 'Botswana',
            'BRA' => 'Brasilien',
            'BRN' => 'Brunei Darussalam',
            'BUL' => 'Bulgarien',
            'BFA' => 'Burkina Faso',
            'BDI' => 'Burundi',
            'SCO' => 'Schottland',
            'KHM' => 'Kambodscha',
            'CMR' => 'Kamerun',
            'CAN' => 'Kanada',
            'CPV' => 'Kap Verde',
            'CAF' => 'Zentralafrikanische Republik',
            'TCD' => 'Tschad',
            'CHL' => 'Chile',
            'CHN' => 'China',
            'COL' => 'Kolumbien',
            'COM' => 'Komoren',
            'COD' => 'Kongo, Demokratische Republik',
            'COG' => 'Republik Kongo',
            'CRI' => 'Costa Rica',
            'HRV' => 'Kroatien',
            'CUB' => 'Kuba',
            'CYP' => 'Zypern',
            'CZE' => 'Tschechien',
            'CIV' => 'Elfenbeinküste',
            'DEN' => 'Dänemark',
            'DJI' => 'Dschibuti',
            'DMA' => 'Dominica',
            'DOM' => 'Dominikanische Republik',
            'ECU' => 'Ecuador',
            'EGY' => 'Ägypten',
            'SLV' => 'El Salvador',
            'ENG' => 'England',
            'GNQ' => 'Äquatorialguinea',
            'ERI' => 'Eritrea',
            'EST' => 'Estland',
            'ETH' => 'Äthiopien',
            'FID' => 'Fide',
            'FIN' => 'Finnland',
            'FRA' => 'Frankreich',
            'GAB' => 'Gabun',
            'GMB' => 'Gambia',
            'GEO' => 'Georgien',
            'DEU' => 'Deutschland',
            'GER' => 'Deutschland',
            'GHA' => 'Ghana',
            'GRE' => 'Griechenland',
            'GRD' => 'Grenada',
            'GTM' => 'Guatemala',
            'GIN' => 'Guinea',
            'GNB' => 'Guinea-Bissau',
            'GUY' => 'Guyana',
            'HTI' => 'Haiti',
            'HND' => 'Honduras',
            'HKG' => 'Hongkong',
            'HUN' => 'Ungarn',
            'ISL' => 'Island',
            'IND' => 'Indien',
            'IDN' => 'Indonesien',
            'IRN' => 'Iran',
            'IRQ' => 'Irak',
            'IRL' => 'Irland',
            'ISR' => 'Israel',
            'ITA' => 'Italien',
            'JAM' => 'Jamaika',
            'JPN' => 'Japan',
            'JOR' => 'Jordanien',
            'KAZ' => 'Kasachstan',
            'KEN' => 'Kenia',
            'KIR' => 'Kiribati',
            'KOR' => 'Südkorea',
            'PRK' => 'Nordkorea',
            'KWT' => 'Kuwait',
            'KGZ' => 'Kirgisistan',
            'LAO' => 'Laos',
            'LAT' => 'Lettland',
            'LBN' => 'Libanon',
            'LSO' => 'Lesotho',
            'LBR' => 'Liberia',
            'LBY' => 'Libyen',
            'LIE' => 'Liechtenstein',
            'LTU' => 'Litauen',
            'LUX' => 'Luxemburg',
            'MKD' => 'Nordmazedonien',
            'MDG' => 'Madagaskar',
            'MWI' => 'Malawi',
            'MYS' => 'Malaysia',
            'MDV' => 'Malediven',
            'MLI' => 'Mali',
            'MLT' => 'Malta',
            'MHL' => 'Marshallinseln',
            'MRT' => 'Mauretanien',
            'MUS' => 'Mauritius',
            'MEX' => 'Mexiko',
            'FSM' => 'Mikronesien',
            'MDA' => 'Moldawien',
            'MCO' => 'Monaco',
            'MNG' => 'Mongolei',
            'MNE' => 'Montenegro',
            'MAR' => 'Marokko',
            'MOZ' => 'Mosambik',
            'MMR' => 'Myanmar',
            'NAM' => 'Namibia',
            'NRU' => 'Nauru',
            'NPL' => 'Nepal',
            'NED' => 'Niederlande',
            'NZL' => 'Neuseeland',
            'NIC' => 'Nicaragua',
            'NGA' => 'Nigeria',
            'NOR' => 'Norwegen',
            'OMN' => 'Oman',
            'PAK' => 'Pakistan',
            'PLW' => 'Palau',
            'PSE' => 'Palästina',
            'PAN' => 'Panama',
            'PNG' => 'Papua-Neuguinea',
            'PRY' => 'Paraguay',
            'PER' => 'Peru',
            'PHL' => 'Philippinen',
            'POL' => 'Polen',
            'PRT' => 'Portugal',
            'QAT' => 'Katar',
            'ROU' => 'Rumänien',
            'RUS' => 'Russland',
            'RWA' => 'Ruanda',
            'LCA' => 'St. Lucia',
            'WSM' => 'Samoa',
            'SMR' => 'San Marino',
            'STP' => 'São Tomé und Príncipe',
            'SAU' => 'Saudi-Arabien',
            'SEN' => 'Senegal',
            'SRB' => 'Serbien',
            'SYC' => 'Seychellen',
            'SLE' => 'Sierra Leone',
            'SGP' => 'Singapur',
            'SVK' => 'Slowakei',
            'SVN' => 'Slowenien',
            'SLB' => 'Salomonen',
            'SOM' => 'Somalia',
            'ZAF' => 'Südafrika',
            'SSD' => 'Südsudan',
            'ESP' => 'Spanien',
            'LKA' => 'Sri Lanka',
            'SDN' => 'Sudan',
            'SUR' => 'Suriname',
            'SWE' => 'Schweden',
            'SUI' => 'Schweiz',
            'SYR' => 'Syrien',
            'TWN' => 'Taiwan',
            'TJK' => 'Tadschikistan',
            'TZA' => 'Tansania',
            'THA' => 'Thailand',
            'TLS' => 'Timor-Leste',
            'TGO' => 'Togo',
            'TON' => 'Tonga',
            'TTO' => 'Trinidad und Tobago',
            'TUN' => 'Tunesien',
            'TUR' => 'Türkei',
            'TKM' => 'Turkmenistan',
            'UGA' => 'Uganda',
            'UKR' => 'Ukraine',
            'ARE' => 'Vereinigte Arabische Emirate',
            'GBR' => 'Vereinigtes Königreich',
            'USA' => 'Vereinigte Staaten von Amerika',
            'URY' => 'Uruguay',
            'UZB' => 'Usbekistan',
            'VUT' => 'Vanuatu',
            'VEN' => 'Venezuela',
            'VNM' => 'Vietnam',
            'YEM' => 'Jemen',
            'ZMB' => 'Sambia',
            'ZWE' => 'Simbabwe',
        );

        return $map[$code] ?? '';
    }
}

<?php
/**
 * FIDE SQLite Klasse
 *
 * Lädt die FIDE Legacy XML-Datei herunter, importiert die Daten in SQLite und stellt Abfragen bereit.
 */

class FIDE_SQLite {
    const DOWNLOAD_URL = 'https://ratings.fide.com/download/players_list_xml_legacy.zip';
    const IMPORT_PROGRESS_TRANSIENT = 'dwz_vl_fide_import_progress';
    const LAST_IMPORT_OPTION = 'dwz_vl_fide_last_import';
    const UPLOAD_SUBDIR = 'fide-ratings';
    const ZIP_FILENAME = 'players_list_xml_legacy.zip';
    const XML_FILENAME = 'players_list_xml_legacy.xml';
    const BATCH_SIZE = 1000;

    public static function get_db_path() {
        return untrailingslashit(DWZ_VL_PLUGIN_DIR) . '/data/fide.sqlite';
    }

    public static function get_upload_dir() {
        $uploads = wp_upload_dir();
        return trailingslashit($uploads['basedir']) . self::UPLOAD_SUBDIR;
    }

    public static function get_zip_path() {
        return trailingslashit(self::get_upload_dir()) . self::ZIP_FILENAME;
    }

    public static function get_xml_path() {
        return trailingslashit(self::get_upload_dir()) . self::XML_FILENAME;
    }

    public static function initialize_database() {
        $data_dir = dirname(self::get_db_path());
        if ( ! file_exists( $data_dir ) ) {
            wp_mkdir_p( $data_dir );
        }

        $db = self::get_db();
        if ( is_wp_error( $db ) ) {
            return $db;
        }

        return self::create_tables( $db );
    }

    public static function get_db() {
        if ( ! class_exists( 'SQLite3' ) ) {
            return new WP_Error(
                'sqlite_unavailable',
                __( 'SQLite3 ist auf diesem Server nicht verfügbar.', 'dwz-verein-list' )
            );
        }

        $db_path = self::get_db_path();
        $db_dir = dirname( $db_path );
        if ( ! file_exists( $db_dir ) ) {
            wp_mkdir_p( $db_dir );
        }

        $db = new SQLite3( $db_path );
        $db->busyTimeout( 5000 );
        $db->exec( 'PRAGMA journal_mode = WAL' );
        $db->exec( 'PRAGMA synchronous = NORMAL' );

        return $db;
    }

    public static function create_tables( $db ) {
        if ( is_wp_error( $db ) ) {
            return $db;
        }

        $sql = "CREATE TABLE IF NOT EXISTS players (
            fide_id TEXT PRIMARY KEY,
            name TEXT,
            federation TEXT,
            sex TEXT,
            title TEXT,
            standard_rating INTEGER,
            rapid_rating INTEGER,
            blitz_rating INTEGER,
            birth_year INTEGER,
            inactive INTEGER,
            last_update TEXT
        )";

        if ( ! $db->exec( $sql ) ) {
            return new WP_Error(
                'sqlite_table_create_failed',
                __( 'Die SQLite-Datenbank konnte nicht erstellt werden.', 'dwz-verein-list' )
            );
        }

        return true;
    }

    public static function get_last_import_time() {
        $time = get_option( self::LAST_IMPORT_OPTION );
        return $time ? $time : null;
    }

    public static function get_import_progress() {
        $progress = get_transient( self::IMPORT_PROGRESS_TRANSIENT );
        if ( false === $progress || ! is_array( $progress ) ) {
            return array(
                'status' => 'idle',
                'percent' => 0,
                'message' => '',
                'processed' => 0,
                'total' => 0,
            );
        }

        return wp_parse_args(
            $progress,
            array(
                'status' => 'idle',
                'percent' => 0,
                'message' => '',
                'processed' => 0,
                'total' => 0,
            )
        );
    }

    public static function set_import_progress( $data ) {
        $defaults = array(
            'status' => 'running',
            'percent' => 0,
            'message' => '',
            'processed' => 0,
            'total' => 0,
        );
        $progress = wp_parse_args( $data, $defaults );
        set_transient( self::IMPORT_PROGRESS_TRANSIENT, $progress, HOUR_IN_SECONDS );
    }

    public static function clear_import_progress() {
        delete_transient( self::IMPORT_PROGRESS_TRANSIENT );
    }

    public static function get_player_by_fide_id( $fide_id ) {
        if ( empty( $fide_id ) ) {
            return null;
        }

        $db = self::get_db();
        if ( is_wp_error( $db ) ) {
            return $db;
        }

        $stmt = $db->prepare(
            'SELECT fide_id, name, federation, sex, title, standard_rating, rapid_rating, blitz_rating, birth_year, inactive, last_update FROM players WHERE fide_id = :fide_id'
        );
        if ( ! $stmt ) {
            return null;
        }

        $stmt->bindValue( ':fide_id', (string) $fide_id, SQLITE3_TEXT );
        $result = $stmt->execute();
        $row = $result ? $result->fetchArray( SQLITE3_ASSOC ) : false;
        $result && $result->finalize();
        $db->close();

        return $row ?: null;
    }

    public static function clear_cache( $fide_id = null ) {
        $db = self::get_db();
        if ( is_wp_error( $db ) ) {
            return $db;
        }

        if ( $fide_id ) {
            $stmt = $db->prepare( 'DELETE FROM players WHERE fide_id = :fide_id' );
            if ( $stmt ) {
                $stmt->bindValue( ':fide_id', (string) $fide_id, SQLITE3_TEXT );
                $stmt->execute();
                $stmt->close();
            }
        } else {
            $db->exec( 'DELETE FROM players' );
        }

        $db->close();
        delete_option( self::LAST_IMPORT_OPTION );
        return true;
    }

    public static function download_latest_fide_file() {
        $upload_dir = self::get_upload_dir();
        if ( ! file_exists( $upload_dir ) ) {
            wp_mkdir_p( $upload_dir );
        }

        $zip_path = self::get_zip_path();
        if ( file_exists( $zip_path ) ) {
            unlink( $zip_path );
        }

        $response = wp_remote_get(
            self::DOWNLOAD_URL,
            array(
                'timeout' => 300,
                'stream' => true,
                'filename' => $zip_path,
                'sslverify' => true,
                'user-agent' => 'WordPress/DWZ-Verein-List',
            )
        );

        if ( is_wp_error( $response ) ) {
            return new WP_Error(
                'download_failed',
                $response->get_error_message()
            );
        }

        $status_code = wp_remote_retrieve_response_code( $response );
        if ( 200 !== $status_code ) {
            if ( file_exists( $zip_path ) ) {
                unlink( $zip_path );
            }
            return new WP_Error(
                'download_failed',
                sprintf(
                    __( 'Der Download der FIDE-Datei ist fehlgeschlagen: HTTP %d.', 'dwz-verein-list' ),
                    $status_code
                )
            );
        }

        if ( ! file_exists( $zip_path ) || filesize( $zip_path ) === 0 ) {
            return new WP_Error(
                'download_failed',
                __( 'Die heruntergeladene FIDE-ZIP-Datei ist leer oder konnte nicht gespeichert werden.', 'dwz-verein-list' )
            );
        }

        return $zip_path;
    }

    public static function extract_xml_from_zip() {
        $zip_path = self::get_zip_path();
        if ( ! file_exists( $zip_path ) ) {
            return new WP_Error(
                'zip_missing',
                __( 'Die FIDE-ZIP-Datei wurde nicht gefunden.', 'dwz-verein-list' )
            );
        }

        if ( ! class_exists( 'ZipArchive' ) ) {
            return new WP_Error(
                'zip_unavailable',
                __( 'ZipArchive steht auf diesem Server nicht zur Verfügung.', 'dwz-verein-list' )
            );
        }

        $zip = new ZipArchive();
        $opened = $zip->open( $zip_path );
        if ( true !== $opened ) {
            return new WP_Error(
                'zip_open_failed',
                __( 'Die FIDE-ZIP-Datei konnte nicht geöffnet werden.', 'dwz-verein-list' )
            );
        }

        if ( $zip->numFiles < 1 ) {
            $zip->close();
            return new WP_Error(
                'zip_empty',
                __( 'Die FIDE-ZIP-Datei enthält keine XML-Datei.', 'dwz-verein-list' )
            );
        }

        $entry_name = $zip->getNameIndex( 0 );
        $xml_path = self::get_xml_path();
        if ( file_exists( $xml_path ) ) {
            unlink( $xml_path );
        }

        $extracted = $zip->extractTo( dirname( $xml_path ), $entry_name );
        $zip->close();

        if ( ! $extracted ) {
            return new WP_Error(
                'zip_extract_failed',
                __( 'Die FIDE-ZIP-Datei konnte nicht entpackt werden.', 'dwz-verein-list' )
            );
        }

        if ( basename( $entry_name ) !== basename( $xml_path ) ) {
            $extracted_path = trailingslashit( dirname( $xml_path ) ) . basename( $entry_name );
            if ( file_exists( $extracted_path ) ) {
                rename( $extracted_path, $xml_path );
            }
        }

        if ( ! file_exists( $xml_path ) ) {
            return new WP_Error(
                'xml_missing',
                __( 'Die entpackte FIDE-XML-Datei wurde nicht gefunden.', 'dwz-verein-list' )
            );
        }

        return $xml_path;
    }

    public static function count_players_in_xml( $xml_path ) {
        $reader = new XMLReader();
        if ( ! $reader->open( $xml_path, null, LIBXML_NOENT | LIBXML_NONET | LIBXML_NOERROR | LIBXML_NOWARNING ) ) {
            return new WP_Error(
                'xml_open_failed',
                __( 'Die FIDE-XML-Datei konnte nicht geöffnet werden.', 'dwz-verein-list' )
            );
        }

        $count = 0;
        while ( $reader->read() ) {
            if ( $reader->nodeType === XMLReader::ELEMENT && 'player' === strtolower( $reader->name ) ) {
                $count++;
            }
        }

        $reader->close();
        return $count;
    }

    public static function import_latest_fide_file() {
        ini_set( 'memory_limit', '-1' );
        @set_time_limit( 0 );
        ignore_user_abort( true );

        self::set_import_progress(
            array(
                'status' => 'running',
                'percent' => 0,
                'message' => __( 'Starte Import der FIDE-Daten...', 'dwz-verein-list' ),
                'processed' => 0,
                'total' => 0,
            )
        );

        $xml_path = self::get_xml_path();
        if ( ! file_exists( $xml_path ) ) {
            return new WP_Error(
                'xml_missing',
                __( 'Die XML-Datei wurde nicht gefunden. Bitte laden Sie die FIDE-Datei zuerst herunter.', 'dwz-verein-list' )
            );
        }

        $total_players = self::count_players_in_xml( $xml_path );
        if ( is_wp_error( $total_players ) ) {
            self::set_import_progress(
                array(
                    'status' => 'error',
                    'percent' => 0,
                    'message' => $total_players->get_error_message(),
                )
            );
            return $total_players;
        }

        $db = self::get_db();
        if ( is_wp_error( $db ) ) {
            self::set_import_progress(
                array(
                    'status' => 'error',
                    'percent' => 0,
                    'message' => $db->get_error_message(),
                )
            );
            return $db;
        }

        $stmt = $db->prepare(
            'INSERT OR REPLACE INTO players (fide_id, name, federation, sex, title, standard_rating, rapid_rating, blitz_rating, birth_year, inactive, last_update)
            VALUES (:fide_id, :name, :federation, :sex, :title, :standard_rating, :rapid_rating, :blitz_rating, :birth_year, :inactive, :last_update)'
        );
        if ( ! $stmt ) {
            $db->close();
            $error = new WP_Error(
                'sqlite_prepare_failed',
                __( 'Die SQLite-Abfrage konnte nicht vorbereitet werden.', 'dwz-verein-list' )
            );
            self::set_import_progress(
                array(
                    'status' => 'error',
                    'percent' => 0,
                    'message' => $error->get_error_message(),
                )
            );
            return $error;
        }

        $reader = new XMLReader();
        if ( ! $reader->open( $xml_path, null, LIBXML_NOENT | LIBXML_NONET | LIBXML_NOERROR | LIBXML_NOWARNING ) ) {
            $db->close();
            $error = new WP_Error(
                'xml_open_failed',
                __( 'Die FIDE-XML-Datei konnte nicht geöffnet werden.', 'dwz-verein-list' )
            );
            self::set_import_progress(
                array(
                    'status' => 'error',
                    'percent' => 0,
                    'message' => $error->get_error_message(),
                )
            );
            return $error;
        }

        $processed = 0;
        $batch = 0;
        $last_update = current_time( 'mysql' );

        $db->exec( 'BEGIN IMMEDIATE TRANSACTION' );
        while ( $reader->read() ) {
            if ( $reader->nodeType !== XMLReader::ELEMENT || 'player' !== strtolower( $reader->name ) ) {
                continue;
            }

            $player = self::parse_player_node( $reader );
            if ( empty( $player['fide_id'] ) ) {
                continue;
            }

            $stmt->bindValue( ':fide_id', (string) $player['fide_id'], SQLITE3_TEXT );
            $stmt->bindValue( ':name', (string) $player['name'], SQLITE3_TEXT );
            $stmt->bindValue( ':federation', (string) $player['federation'], SQLITE3_TEXT );
            $stmt->bindValue( ':sex', (string) $player['sex'], SQLITE3_TEXT );
            $stmt->bindValue( ':title', (string) $player['title'], SQLITE3_TEXT );
            $stmt->bindValue( ':standard_rating', $player['standard_rating'], SQLITE3_INTEGER );
            $stmt->bindValue( ':rapid_rating', $player['rapid_rating'], SQLITE3_INTEGER );
            $stmt->bindValue( ':blitz_rating', $player['blitz_rating'], SQLITE3_INTEGER );
            $stmt->bindValue( ':birth_year', $player['birth_year'], SQLITE3_INTEGER );
            $stmt->bindValue( ':inactive', $player['inactive'], SQLITE3_INTEGER );
            $stmt->bindValue( ':last_update', $last_update, SQLITE3_TEXT );
            $stmt->execute();

            $processed++;
            $batch++;

            if ( $batch >= self::BATCH_SIZE ) {
                $db->exec( 'COMMIT' );
                $db->exec( 'BEGIN IMMEDIATE TRANSACTION' );
                $batch = 0;
                self::set_import_progress(
                    array(
                        'status' => 'running',
                        'percent' => $total_players > 0 ? min( 100, (int) floor( $processed / $total_players * 100 ) ) : 0,
                        'message' => sprintf( __( 'Importiere Spieler: %d von %d', 'dwz-verein-list' ), $processed, $total_players ),
                        'processed' => $processed,
                        'total' => $total_players,
                    )
                );
            }
        }

        $db->exec( 'COMMIT' );
        $reader->close();
        $stmt->close();
        $db->close();

        self::set_import_progress(
            array(
                'status' => 'completed',
                'percent' => 100,
                'message' => __( 'Import abgeschlossen.', 'dwz-verein-list' ),
                'processed' => $processed,
                'total' => $total_players,
            )
        );

        update_option( self::LAST_IMPORT_OPTION, $last_update );
        self::cleanup_downloaded_files();

        return array(
            'processed' => $processed,
            'total' => $total_players,
            'last_update' => $last_update,
        );
    }

    private static function parse_player_node( $reader ) {
        $player = array(
            'fide_id' => null,
            'name' => '',
            'federation' => '',
            'sex' => '',
            'title' => '',
            'standard_rating' => null,
            'rapid_rating' => null,
            'blitz_rating' => null,
            'birth_year' => null,
            'inactive' => 0,
        );

        $depth = $reader->depth;
        while ( $reader->read() ) {
            if ( $reader->nodeType === XMLReader::END_ELEMENT && 'player' === strtolower( $reader->name ) && $reader->depth === $depth ) {
                break;
            }

            if ( $reader->nodeType !== XMLReader::ELEMENT ) {
                continue;
            }

            $name = strtolower( $reader->name );
            $reader->read();
            $value = $reader->nodeType === XMLReader::TEXT || $reader->nodeType === XMLReader::CDATA ? trim( $reader->value ) : '';

            switch ( $name ) {
                case 'fideid':
                case 'fide_id':
                case 'fideid':
                    $player['fide_id'] = $value;
                    break;
                case 'name':
                    $player['name'] = $value;
                    break;
                case 'country':
                    $player['federation'] = $value;
                    break;
                case 'sex':
                    $player['sex'] = $value;
                    break;
                case 'title':
                case 'w_title':
                case 'o_title':
                    if ( '' === $player['title'] && '' !== $value ) {
                        $player['title'] = $value;
                    }
                    break;
                case 'rating':
                case 'standard_rating':
                case 'elo':
                    $player['standard_rating'] = is_numeric( $value ) ? intval( $value ) : null;
                    break;
                case 'rapid_rating':
                    $player['rapid_rating'] = is_numeric( $value ) ? intval( $value ) : null;
                    break;
                case 'blitz_rating':
                    $player['blitz_rating'] = is_numeric( $value ) ? intval( $value ) : null;
                    break;
                case 'birthday':
                case 'birth_year':
                    $player['birth_year'] = is_numeric( $value ) ? intval( $value ) : null;
                    break;
                case 'inactive':
                    $player['inactive'] = intval( $value );
                    break;
            }
        }

        return $player;
    }

    public static function cleanup_downloaded_files() {
        $xml_path = self::get_xml_path();
        $zip_path = self::get_zip_path();

        if ( file_exists( $xml_path ) ) {
            unlink( $xml_path );
        }

        if ( file_exists( $zip_path ) ) {
            unlink( $zip_path );
        }
    }

    public static function update_from_fide() {
        $download = self::download_latest_fide_file();
        if ( is_wp_error( $download ) ) {
            return $download;
        }

        self::set_import_progress(
            array(
                'status' => 'running',
                'percent' => 10,
                'message' => __( 'FIDE-Datei wurde heruntergeladen.', 'dwz-verein-list' ),
                'processed' => 0,
                'total' => 0,
            )
        );

        $extract = self::extract_xml_from_zip();
        if ( is_wp_error( $extract ) ) {
            return $extract;
        }

        self::set_import_progress(
            array(
                'status' => 'running',
                'percent' => 15,
                'message' => __( 'FIDE-XML-Datei wird entpackt.', 'dwz-verein-list' ),
                'processed' => 0,
                'total' => 0,
            )
        );

        return self::import_latest_fide_file();
    }
}

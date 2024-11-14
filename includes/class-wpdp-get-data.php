<?php

/**
 * Class for importing CSV data into database table
 *
 * @since 1.0.0
 */

class WPDP_Db_Table {
    private $table_name;
    private $csv_file_path;
    private $delimiter;
    private $logger;

    /**
     * Constructor
     *
     * @param string $table_name
     *
     * @since 1.0.0
     */
    public function __construct($table_name,$file_path) {
        global $wpdb;
        $this->table_name    =  $table_name;
        $this->csv_file_path = $file_path;
        $this->delimiter     = ';';
    }


    public function detect_delimiter($file_path) {
        $delimiters = array(
            ',' => 0,
            ';' => 0,
            "\t" => 0,
            '|' => 0
        );
    
        $handle = fopen($file_path, 'r');
    
        if ($handle) {
            $line = fgets($handle);
            fclose($handle);
    
            foreach ($delimiters as $delimiter => &$count) {
                $count = substr_count($line, $delimiter);
            }
        }
    
        return array_search(max($delimiters), $delimiters);
    }
    

    /**
     * Import CSV file into database table
     *
     * @return bool Success status
     *
     * @since 1.0.0
     */
    public function import_csv() {
        if (!$this->create_table()) {
            return;
        }

        if (!file_exists($this->csv_file_path)) {
            var_dump('csv file does not exist');exit;
        }

        $conn = new mysqli(DB_HOST, DB_USER, DB_PASSWORD, DB_NAME);
        mysqli_options($conn, MYSQLI_OPT_LOCAL_INFILE, true);

        global $wpdb;
        $csv_file_path = $this->sanitize_file_path($this->csv_file_path);
        if ($conn->connect_error) {
            die("Connection failed: " . $conn->connect_error);
        }
        $this->delimiter = $this->detect_delimiter($this->csv_file_path);

        // Check if the table has any data
        $table_name = $this->table_name;
        $check_query = "SELECT COUNT(*) FROM {$table_name}";
        $table_has_data = $wpdb->get_var($check_query);

        if ($table_has_data > 0) {
            // Create a temporary table for the new data
            $table_name = $this->table_name . '_temp';
            $this->create_temp_table($table_name);
        }

        // Live server only
        $query = $wpdb->prepare(
            "LOAD DATA LOCAL INFILE %s
                     INTO TABLE {$table_name}
                     FIELDS TERMINATED BY %s
                     ENCLOSED BY '\"'
                     LINES TERMINATED BY '\\n'
                     IGNORE 1 LINES",
            $csv_file_path,
            $this->delimiter
        );
        $result = $conn->query($query);


        // Local host only.
        // $query = $wpdb->prepare(
        //     "LOAD DATA INFILE %s
        //              INTO TABLE {$this->table_name}
        //              FIELDS TERMINATED BY %s
        //              ENCLOSED BY '\"'
        //              LINES TERMINATED BY '\\n'
        //              IGNORE 1 LINES",
        //     $csv_file_path,
        //     $this->delimiter
        // );
        
        // $result = $wpdb->query($query);


        if (false === $result) {
            var_dump('Error importing CSV data - ' . $conn->error);
            exit;
        }

        if($table_has_data > 0){
            // Merge data from temporary table to main table, removing duplicates
            $this->merge_tables($table_name);
            // Drop the temporary table
            $wpdb->query("DROP TABLE IF EXISTS {$table_name}");
        }

        return true;
    }

    private function create_temp_table($temp_table_name) {
        global $wpdb;
        $wpdb->query("CREATE TABLE {$temp_table_name} LIKE {$this->table_name}");
    }

    private function merge_tables($temp_table_name) {
        global $wpdb;
        
        try {
            // Set longer timeout for large datasets
            set_time_limit(300); // 5 minutes
            
            // First, insert new records
            $insert_result = $wpdb->query("
                INSERT INTO {$this->table_name}
                SELECT t.*
                FROM {$temp_table_name} t
                LEFT JOIN {$this->table_name} m ON t.event_id_cnty = m.event_id_cnty
                WHERE m.event_id_cnty IS NULL
            ");

            // Then, update only fatalities, inter1, and inter2 when they've changed
            $update_result = $wpdb->query("
                UPDATE {$this->table_name} m
                INNER JOIN {$temp_table_name} t ON m.event_id_cnty = t.event_id_cnty
                SET 
                    m.fatalities = COALESCE(t.fatalities, 0),
                    m.inter1 = COALESCE(t.inter1, 0),
                    m.inter2 = COALESCE(t.inter2, 0)
                WHERE COALESCE(t.fatalities, 0) != COALESCE(m.fatalities, 0)
                   OR COALESCE(t.inter1, 0) != COALESCE(m.inter1, 0)
                   OR COALESCE(t.inter2, 0) != COALESCE(m.inter2, 0)
            ");

            return [
                'inserted' => $insert_result,
                'updated' => $update_result
            ];
        } catch (Exception $e) {
            error_log("Error merging tables: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Sanitize file path
     *
     * @param string $file_path
     * @return string Sanitized file path
     *
     * @since 1.0.0
     */
    private function sanitize_file_path($file_path) {
        // Sanitize the file path to prevent SQL injection
        return addslashes($file_path);
    }

    /**
     * Create database table
     *
     * @return bool Success status
     *
     * @since 1.0.0
     */
    public function create_table() {
        global $wpdb;
        $table_exists = $wpdb->get_var("SHOW TABLES LIKE '{$this->table_name}'") === $this->table_name;
        if($table_exists){
            return true;
        }

        // Get the column names from the CSV file
        $column_names = $this->get_column_names();
        if (empty($column_names)) {
            // Unable to get column names from the CSV file
            return;
        }

        $definitions = $this->get_column_definitions($column_names);

        // Create the SQL query to create the table
        $sql = "CREATE TABLE {$this->table_name} (
            " . implode(",\n", $definitions) . ',
            INDEX `disorder_type` (`disorder_type`),
            INDEX `region` (`region`),
            INDEX `event_id_cnty` (`event_id_cnty`),
            INDEX `country` (`country`),
            INDEX `admin1` (`admin1`),
            INDEX `admin2` (`admin2`),
            INDEX `admin3` (`admin3`),
            INDEX `location` (`location`),
            INDEX `event_date` (`event_date`)
        )';

        // Execute the query
        require_once ABSPATH . 'wp-admin/includes/upgrade.php';
        $wpdb->query($sql);

        // Check if the table was created successfully
        if ($wpdb->last_error) {
            var_dump($wpdb->last_error);exit;
        }
        return true;
    }

    /**
     * Get column definitions for table creation
     *
     * @param array $column_names Column names
     * @return array Column definitions
     *
     * @since 1.0.0
     */
    public function get_column_definitions(array $column_names) {
        $column_definitions = array();
        foreach ($column_names as $name) {
            switch ($name) {
                case 'event_id_cnty':
                case 'iso':
                    $column_definitions[] = "`$name` VARCHAR(10)";
                    break;
                case 'event_date':
                    $column_definitions[] = "`$name` VARCHAR(25)";
                    break;
                case 'year':
                    $column_definitions[] = "`$name` YEAR(4)";
                    break;
                case 'time_precision':
                case 'inter1':
                case 'inter2':
                case 'interaction':
                case 'geo_precision':
                case 'fatalities':
                    $column_definitions[] = "`$name` INT";
                    break;
                case 'latitude':
                case 'longitude':
                    $column_definitions[] = "`$name` DECIMAL(10,7)";
                    break;
                case 'notes':
                    $column_definitions[] = "`$name` TEXT";
                    break;
                case 'timestamp':
                    $column_definitions[] = "`$name` BIGINT";
                    break;
                default:
                    $column_definitions[] = "`$name` VARCHAR(100)";
                    break;
            }
        }
        return $column_definitions;
    }

    /**
     * Get column names from CSV file
     *
     * @return array Column names
     *
     * @since 1.0.0
     */
    public function get_column_names() {
        if (!file_exists($this->csv_file_path)) {
            return array();
        }

        $handle = fopen($this->csv_file_path, 'r');

        if (!$handle) {
            return array();
        }

        $column_names = array();
        $row = fgetcsv($handle);

        if ($row && is_array($row)) {
            // Get column names from the first row of the CSV file
            $column_names = array_map('trim', $row);

            // Split columns if they are concatenated in one string
            if (count($column_names) == 1 && strpos($column_names[0], ';') !== false) {
                $column_names = array_map('trim', explode(';', $column_names[0]));
            }
        }

        fclose($handle);

        return $column_names;
    }
}
<?php 
/**
 * View class
 *
 * @since 1.0.0
 */

final class WPDP_Tables {

    /**
     * Instance of this class.
     *
     * @since 1.0.0
     *
     * @var WPDP_Tables
     */
    protected static $_instance = null;

    /**
     * Get instance of the class
     *
     * @since 1.0.0
     *
     * @return WPDP_Tables
     */

    public static function get_instance() {

        // If the single instance hasn't been set, set it now.
        if (null === self::$_instance) {
            self::$_instance = new self();
        }

        return self::$_instance;
    }

    /**
     * Constructor
     *
     * @since 1.0.0
     */
    private function __construct() {
        $this->_add_hooks();
    }

    /**
     * Add hooks
     *
     * @since 1.0.0
     */
    private function _add_hooks() {
        add_action('wp_enqueue_scripts', array($this, 'enqueue_scripts'));
        add_action( 'wp_ajax_nopriv_wpdp_datatables_request', array($this,'get_datatables_data') );
        add_action( 'wp_ajax_wpdp_datatables_request', array($this,'get_datatables_data') );


        add_action( 'wp_ajax_nopriv_wpdp_datatables_find_by_id', array($this,'find_by_id') );
        add_action( 'wp_ajax_wpdp_datatables_find_by_id', array($this,'find_by_id') );


    }


    public function find_by_id(){
        $event_id = $_POST['event_id'];

        if($event_id == ''){
            wp_send_json_error([]);
        }

        $posts = get_posts(array(
            'post_type'=>'wp-data-presentation',
            'posts_per_page'=>-1,
            'fields'=>'ids'
        ));

        if(empty($posts)){
            return 'No data';
        }
        global $wpdb;
        $result = '';
        foreach($posts as $post_id){
            $table_name = $wpdb->prefix. 'wpdp_data_'.$post_id;
            $table_exists = $wpdb->get_var("SHOW TABLES LIKE '{$table_name}'") === $table_name;
            if(!$table_exists){
                continue;
            }
            $result = $wpdb->get_row("SELECT event_type, sub_event_type, source, notes, region, country, admin1, admin2, admin3, location, event_id_cnty, timestamp, fatalities FROM {$table_name} WHERE event_id_cnty = '{$event_id}'", ARRAY_A);
            if(!empty($result)){
                break;
            }
        }

        if(empty($result)){
            wp_send_json_error([]);
        }

        wp_send_json_success([$result]);
    }

    public function get_datatables_data(){
        $types = [
            'event_date',
            'disorder_type',
            'country',
            'fatalities',
            'event_id_cnty',
            'event_type',
            'sub_event_type'
        ];


        $filters = [
            'disorder_type' => isset($_REQUEST['type_val']) ? $_REQUEST['type_val'] : null,
            'locations' => isset($_REQUEST['locations_val']) ? $_REQUEST['locations_val'] : null,
            'from' => isset($_REQUEST['from_val']) ? $_REQUEST['from_val'] : null,
            'to' => isset($_REQUEST['to_val']) ? $_REQUEST['to_val'] : null
        ];


        $start = $_REQUEST['start']; // Starting row
        $length = $_REQUEST['length']; // Page length
        $columnIndex = $_REQUEST['order'][0]['column']; // Column index for sorting
        $columnName = $types[$columnIndex]; // Column name for sorting
        $orderDir = $_REQUEST['order'][0]['dir']; // Order direction

        $totalRecords = $this->get_total_records_count(); // Implement a function to get the total number of records

        $data = $this->get_data($filters,$types, $start, $length, $columnName, $orderDir,true);

        $arr = [
            "draw" => intval($_REQUEST['draw']),
            "recordsTotal" => intval($data['count']),
            "recordsFiltered" => intval($data['count']),
            "data" => $data['data'],
        ];

        echo json_encode($arr);
        wp_die();

    }

    function enqueue_scripts() {
    
        wp_register_script(WP_DATA_PRESENTATION_NAME.'datatables', WP_DATA_PRESENTATION_URL.'assets/js/datatables.min.js', array('jquery'), WP_DATA_PRESENTATION_VERSION, true);
        wp_register_script(WP_DATA_PRESENTATION_NAME.'moment', WP_DATA_PRESENTATION_URL.'assets/js/moment.min.js', array('jquery'), WP_DATA_PRESENTATION_VERSION, true);
        wp_register_style(WP_DATA_PRESENTATION_NAME.'datatables', WP_DATA_PRESENTATION_URL.'assets/css/datatables.min.css', [],WP_DATA_PRESENTATION_VERSION );
        
    }
    

    public function get_total_records_count(){
        $posts = get_posts(array(
            'post_type'=>'wp-data-presentation',
            'posts_per_page'=>-1,
            'fields'=>'ids'
        ));

        if(empty($posts)){
            return 'No data';
        }

        $arr_type = ARRAY_A;
        global $wpdb;
        $count = 0;
        foreach($posts as $id){
            $table_name = $wpdb->prefix. 'wpdp_data_'.$id;
            $table_exists = $wpdb->get_var("SHOW TABLES LIKE '{$table_name}'") === $table_name;
            if(!$table_exists){
                continue;
            }
            $count += $wpdb->get_var( "SELECT COUNT(*) FROM {$table_name}" );
        }

        return $count;
    }



    
    public function get_data($filters, $types, $start, $length, $columnName, $orderDir, $values_only = false) {
        global $wpdb;
    
        $posts = get_posts(array(
            'post_type' => 'wp-data-presentation',
            'posts_per_page' => -1,
            'fields' => 'ids'
        ));
    
        if (empty($posts)) {
            return 'No data';
        }
    
        // Ensure event_id_cnty is always in the types array
        if (!in_array('event_id_cnty', $types)) {
            $types[] = 'event_id_cnty';
        }
    
        $arr_type = $values_only ? ARRAY_N : ARRAY_A;
    
        $union_queries = [];
        $queryArgs = [];
    
        foreach ($posts as $id) {
            $table_name = $wpdb->prefix . 'wpdp_data_' . $id;
            if ($wpdb->get_var("SHOW TABLES LIKE '{$table_name}'") !== $table_name) {
                continue;
            }
            $date_sample = $wpdb->get_var("SELECT event_date FROM $table_name LIMIT 1");
            $date_format = WPDP_Shortcode::get_date_format($date_sample);
            $mysql_date_format = $date_format['mysql'];
            $whereSQL = $this->build_where_clause($filters, $queryArgs,$date_format);
            $query = "SELECT " . implode(', ', array_map(function($type) {
                if ($type === 'fatalities') {
                    return "CASE WHEN $type > 0 THEN CONCAT($type, ' from ', event_type) ELSE CAST($type AS CHAR) END AS $type";
                }
                if ($type === 'disorder_type') {
                    return "CONCAT($type, ' / ', event_type, ' / ', sub_event_type)";
                }
                return $type;
            }, $types)) . " FROM {$table_name}";
            $query = str_replace('event_date',"STR_TO_DATE(event_date, '".$mysql_date_format."') AS date_column_standard",$query);
            $query.= " {$whereSQL}";
            $union_queries[] = $query;
        }

        if (empty($union_queries)) {
            return ['data' => [], 'count' => 0];
        }
    
        $union_query = implode(' UNION ALL ', $union_queries);
    
        $order_by = $columnName === 'event_date' ? 'date_column_standard' : $columnName;
    
        $final_query = "
        SELECT DISTINCT t.*
        FROM ({$union_query}) AS t
        ORDER BY {$order_by} {$orderDir}
        LIMIT {$start}, {$length}
        ";

        
        $count_query = "
        SELECT COUNT(DISTINCT event_id_cnty)
        FROM (
            SELECT event_id_cnty
            FROM ({$union_query}) AS sub
            GROUP BY event_id_cnty
        ) AS t
        ";
    
        $data = $wpdb->get_results($wpdb->prepare($final_query, $queryArgs), $arr_type);
        $count = $wpdb->get_var($wpdb->prepare($count_query, $queryArgs));
        return ['data' => $data, 'count' => $count];
    }
    
    private function build_where_clause($filters, &$queryArgs, $date_format) {
        $whereSQL = '';
        if (!empty($filters)) {
            $whereSQL = ' WHERE 1=1';
            foreach ($filters as $key => $filter) {
                if (!empty($filter)) {
                    if (is_array($filter)) {
                        if ($key == "locations") {
                            $whereSQL .= ' AND (';
                            $conditions = [];
                            foreach ($filter as $value) {
                                $sub_conditions = [];
                                $value_parts = explode(' + ', $value);
                                foreach ($value_parts as $part) {
                                    list($val, $col) = explode('__', $part);
                                    $sub_conditions[] = "$col = %s";
                                    $queryArgs[] = $val;
                                }
                                $conditions[] = '(' . implode(' AND ', $sub_conditions) . ')';
                            }
                            $whereSQL .= implode(' OR ', $conditions) . ')';
                        } else {
                            $conditions = [];
                            foreach ($filter as $value) {
                                $value_parts = explode('+', $value);
                                $sub_conditions = [];
                                foreach ($value_parts as $part) {
                                    list($val, $col) = explode('__', $part);
                                    $sub_conditions[] = "$col = %s";
                                    $queryArgs[] = $val;
                                }
                                $conditions[] = '(' . implode(' AND ', $sub_conditions) . ')';
                            }
                            $whereSQL .= " AND (" . implode(' OR ', $conditions) . ")";
                        }
                    } else {
                        
                        $mysql_date_format = $date_format['mysql'];
    
                        if ($key === 'from') {
                            $whereSQL .= " AND STR_TO_DATE(event_date, '$mysql_date_format') >= STR_TO_DATE(%s, '$mysql_date_format')";
                            $queryArgs[] = date($date_format['php'], strtotime($filter));
                        } elseif ($key === 'to') {
                            $whereSQL .= " AND STR_TO_DATE(event_date, '$mysql_date_format') <= STR_TO_DATE(%s, '$mysql_date_format')";
                            $queryArgs[] = date($date_format['php'], strtotime($filter));
                        } else {
                            $whereSQL .= " AND $key = %s";
                            $queryArgs[] = $filter;
                        }
                    }
                }
            }
        }
        return $whereSQL;
    }
    
    

    public static function shortcode_output($atts = []){

        wp_enqueue_script(WP_DATA_PRESENTATION_NAME.'datatables');
        wp_enqueue_script(WP_DATA_PRESENTATION_NAME.'moment');
        wp_enqueue_style(WP_DATA_PRESENTATION_NAME.'datatables');
        
    ?>

        <table id="wpdp_datatable" style="width:100%;">
            <thead>
                <tr>
                    <th>Date</th>
                    <th>Incident Type</th>
                    <th>Location</th>
                    <th>Fatalities.</th>
                    <th> </th>
                </tr>
            </thead>


        </table>

        <style>
            tr.group, tr.group:hover {
                background-color: #ddd !important;
            }
        </style>

    <?php }


}

WPDP_Tables::get_instance();
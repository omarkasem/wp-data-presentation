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
            $table_name = 'wpdp_data_'.$post_id;
            $table_exists = $wpdb->get_var("SHOW TABLES LIKE '{$table_name}'") === $table_name;
            if(!$table_exists){
                continue;
            }
            $result = $wpdb->get_row("SELECT event_type, sub_event_type, source, notes, timestamp FROM {$table_name} WHERE event_id_cnty = '{$event_id}'", ARRAY_A);
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
            'event_id_cnty'
        ];


        $filters = [
            'disorder_type'=>$_REQUEST['type_val'],
            'locations'=>$_REQUEST['locations_val'],
            'from'=>$_REQUEST['from_val'],
            'to'=>$_REQUEST['to_val']
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
            $table_name = 'wpdp_data_'.$id;
            $table_exists = $wpdb->get_var("SHOW TABLES LIKE '{$table_name}'") === $table_name;
            if(!$table_exists){
                continue;
            }
            $count += $wpdb->get_var( "SELECT COUNT(*) FROM {$table_name}" );
        }

        return $count;
    }

    public function get_data($filters, $types, $start, $length, $columnName, $orderDir, $values_only = false) {
        $posts = get_posts(array(
            'post_type' => 'wp-data-presentation',
            'posts_per_page' => -1,
            'fields' => 'ids'
        ));
    
        if(empty($posts)){
            return 'No data';
        }
    
        $all_types = [
            'event_date',
            'disorder_type',
            'event_type',
            'sub_event_type',
            'region',
            'country',
            'admin1',
            'admin2',
            'admin3',
            'location',
            'latitude',
            'longitude',
            'source',
            'notes',
            'fatalities',
            'timestamp',
        ];
    
        if($types == ''){
            $types = $all_types;
        }

        $arr_type = ARRAY_A;
        global $wpdb;
        $data = [];
        $count = 0;
        foreach($posts as $id){
            $table_name = 'wpdp_data_'.$id;
            $table_exists = $wpdb->get_var("SHOW TABLES LIKE '{$table_name}'") === $table_name;
            if(!$table_exists){
                continue;
            }
            if($values_only){
                $arr_type = ARRAY_N;
            }
    
            $whereSQL = '';
            $queryArgs = [];
            
            $columns = array('region', 'country', 'admin1', 'admin2', 'admin3', 'location');
            
            if (!empty($filters)) {
                $whereSQL = ' WHERE 1=1';
                foreach($filters as $key => $filter) {
                    if(!empty($filter)){
                        if(is_array($filter)){
                            if($key == "locations"){
                                $conditions = array();
                                foreach($columns as $column){
                                    foreach($filter as $value){
                                        $conditions[] = "$column = %s";
                                        $queryArgs[] = $value;
                                    }
                                }
                                $whereSQL .= " AND (".implode(' OR ', $conditions).")";
                            }else{
                                $placeholders = array_fill(0, count($filter), '%s');
                                $whereSQL .= " AND {$key} IN (".implode(', ', $placeholders).")";
                                $queryArgs = array_merge($queryArgs, $filter);
                            }
                        }else{

                            if($key === 'from'){
                                $whereSQL .= " AND STR_TO_DATE({$columnName}, '%%d %%M %%Y') >= STR_TO_DATE(%s, '%%d %%M %%Y')";
                                $queryArgs[] = $filter;
                            }elseif($key === 'to'){
                                $whereSQL .= " AND STR_TO_DATE({$columnName}, '%%d %%M %%Y') <= STR_TO_DATE(%s, '%%d %%M %%Y')";
                                $queryArgs[] = $filter;
                            }else{
                                $whereSQL .= " AND {$key} = %s";
                                $queryArgs[] = $filter;
                            }

                        }
                    }
                }
            }

            if($columnName === 'event_date'){
                $query = $wpdb->prepare("SELECT ".implode(', ', $types)." FROM {$table_name} {$whereSQL} ORDER BY STR_TO_DATE({$columnName}, '%%d %%M %%Y') {$orderDir} LIMIT {$start}, {$length}", $queryArgs);
            }else{
                $query = $wpdb->prepare("SELECT ".implode(', ', $types)." FROM {$table_name} {$whereSQL} ORDER BY {$columnName} {$orderDir} LIMIT {$start}, {$length}", $queryArgs);
            }
            

            $query_count = $wpdb->prepare("SELECT COUNT(*) FROM {$table_name} {$whereSQL}", $queryArgs);
            $count += $wpdb->get_var($query_count);
            
            $result = $wpdb->get_results($query, $arr_type);
    
            if($result){
                $data = array_merge($data, $result);
            }
        }
    
        return ['data'=>$data,'count'=>$count];
    }



    public static function shortcode_output(){

        wp_enqueue_script(WP_DATA_PRESENTATION_NAME.'datatables');
        wp_enqueue_script(WP_DATA_PRESENTATION_NAME.'moment');
        wp_enqueue_style(WP_DATA_PRESENTATION_NAME.'datatables');
        
    ?>

        <table id="wpdp_datatable" style="width:100%">
            <thead>
                <tr>
                    <th>Date</th>
                    <th>Type</th>
                    <th>Location</th>
                    <th>Number</th>
                    <th>More Details</th>
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
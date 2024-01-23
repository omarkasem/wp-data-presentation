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

        if(isset($_GET['test2'])){
            var_dump(get_option('test5'));exit;
        }

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
            'country'=>$_REQUEST['locations_val'],
            'from'=>$_REQUEST['from_val'],
            'to'=>$_REQUEST['to_val']
        ];

        $filter_on = false;
        foreach($filters as $filter){
            if(is_array($filter)){
                foreach($filter as $f){
                    if($f != ''){
                        $filter_on = true;
                    }
                }
            }else{
                if($filter != ''){
                    $filter_on = true;
                }
            }
        }

        $start = $_REQUEST['start']; // Starting row
        $length = $_REQUEST['length']; // Page length
        $columnIndex = $_REQUEST['order'][0]['column']; // Column index for sorting
        $columnName = $types[$columnIndex]; // Column name for sorting
        $orderDir = $_REQUEST['order'][0]['dir']; // Order direction

        $totalRecords = WPDP_Shortcode::get_total_records_count(); // Implement a function to get the total number of records

        $data = WPDP_Shortcode::get_data($filters,$types, $start, $length, $columnName, $orderDir,true);
        
        // if($filter_on){

        //     $totalRecords = count($data);
        // }

        $arr = [
            "draw" => intval($_REQUEST['draw']),
            "recordsTotal" => intval($totalRecords),
            "recordsFiltered" => intval($totalRecords),
            "data" => $data,
        ];

        echo json_encode($arr);
        wp_die();

    }

    function enqueue_scripts() {
    
        wp_register_script(WP_DATA_PRESENTATION_NAME.'datatables', WP_DATA_PRESENTATION_URL.'assets/js/datatables.min.js', array('jquery'), WP_DATA_PRESENTATION_VERSION, true);
        wp_register_script(WP_DATA_PRESENTATION_NAME.'moment', WP_DATA_PRESENTATION_URL.'assets/js/moment.min.js', array('jquery'), WP_DATA_PRESENTATION_VERSION, true);
        wp_register_style(WP_DATA_PRESENTATION_NAME.'datatables', WP_DATA_PRESENTATION_URL.'assets/css/datatables.min.css', [],WP_DATA_PRESENTATION_VERSION );
        
    }
    

    public static function shortcode_output(){

        wp_enqueue_script(WP_DATA_PRESENTATION_NAME.'datatables');
        wp_enqueue_script(WP_DATA_PRESENTATION_NAME.'moment');
        wp_enqueue_style(WP_DATA_PRESENTATION_NAME.'datatables');
        $table = 'wpdp_datatable';

        
    ?>

    <table style="display:none;">
        <tbody><tr>
            <td>Minimum date:</td>
            <td><input type="text" id="wpdp_min" name="min"></td>
        </tr>
        <tr>
            <td>Maximum date:</td>
            <td><input type="text" id="wpdp_max" name="max"></td>
        </tr>
    </tbody></table>

        <table id="<?php echo $table; ?>" class="display" style="width:100%">
            <thead>
                <tr>
                    <th>Date</th>
                    <th>Type</th>
                    <th>Location</th>
                    <th>Number</th>
                    <th>More Details</th>
                </tr>
            </thead>

            <tfoot style="display:none;">
                <tr>
                    <th class="date">Date</th>
                    <th class="type">Type</th>
                    <th class="location">Location</th>
                    <th class="number">Number</th>
                </tr>
            </tfoot>

        </table>

        <style>
            tr.group, tr.group:hover {
                background-color: #ddd !important;
            }
        </style>

    <?php }


}

WPDP_Tables::get_instance();
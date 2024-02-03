<?php 
/**
 * View class
 *
 * @since 1.0.0
 */

final class WPDP_Maps {

    /**
     * Instance of this class.
     *
     * @since 1.0.0
     *
     * @var WPDP_Maps
     */
    protected static $_instance = null;

    /**
     * Get instance of the class
     *
     * @since 1.0.0
     *
     * @return WPDP_Maps
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
        
        add_action( 'wp_ajax_nopriv_wpdp_map_request', array($this,'get_map_data') );
        add_action( 'wp_ajax_wpdp_map_request', array($this,'get_map_data') );


        add_action( 'wp_ajax_nopriv_wpdp_get_markers', array($this,'get_markers') );
        add_action( 'wp_ajax_wpdp_get_markers', array($this,'get_markers') );

        add_filter('script_loader_tag', function($tag, $handle) {
            if ( $handle !== WP_DATA_PRESENTATION_NAME.'google-maps-api' )
                return $tag;
            return str_replace(' src', ' async="async" src', $tag);
        }, 10, 2);
        

        if(isset($_GET['test1'])){
            var_dump($this->get_data([
               
            ],[
                'event_date',
                'disorder_type',
                'event_type',
                'sub_event_type',
                'country',
                'location',
                'latitude',
                'longitude',
                'source',
                'notes',
                'timestamp',
            ]));exit;
        }
    }



    function enqueue_scripts() {
        wp_register_script(WP_DATA_PRESENTATION_NAME.'google-maps-api', 'https://maps.googleapis.com/maps/api/js?key='.get_field('google_maps_api_key','option').'&callback=wpdp_maps', array(), null, true);

        
        wp_register_script(WP_DATA_PRESENTATION_NAME.'google-maps-cluster',WP_DATA_PRESENTATION_URL. 'assets/js/markerclustererplus.js', array(), null, true);
        // wp_register_script(WP_DATA_PRESENTATION_NAME.'google-maps-cluster', 'https://unpkg.com/@google/markerclustererplus', array(), null, true);
        // wp_register_script(WP_DATA_PRESENTATION_NAME.'google-maps-cluster', 'https://unpkg.com/@googlemaps/markerclusterer/dist/index.min.js', array(), null, true);


    }

    public function format_dates_to_one_year($filters){
        if(isset($filters['from']) && $filters['from'] != '' && isset($filters['to']) && $filters['to'] != ''){
            $date1 = date_create($filters['from']);
            $date2 = date_create($filters['to']);
            $diff = date_diff($date1, $date2);
            $days = intval($diff->format('%a'));

            if($days > 366){
                date_add($date1, date_interval_create_from_date_string('1 year'));
                $filters['to'] = $date1->format('d F Y');
            }
        }

        if(isset($filters['from']) && $filters['from'] != '' && (!isset($filters['to']) || $filters['to'] == '')){
            $date1 = date_create($filters['from']);
            date_add($date1, date_interval_create_from_date_string('1 year'));
            $filters['to'] = $date1->format('d F Y');
        }

        if((!isset($filters['from']) || $filters['from'] == '') && isset($filters['to']) && $filters['to'] != ''){
            $date1 = date_create($filters['to']);
            date_sub($date1, date_interval_create_from_date_string('1 year'));
            $filters['from'] = $date1->format('d F Y');
        }

        if( (!isset($filters['from']) || $filters['from'] == '') && (!isset($filters['to']) || $filters['to'] == '') ){
            $filters['to'] = date('d F Y');
            $date1 = date_create($filters['to']);
            date_sub($date1, date_interval_create_from_date_string('1 year'));
            $filters['from'] = $date1->format('d F Y');
        }

        return $filters;
    }

    public function get_data($filters,$types){
        $posts = get_posts(array(
            'post_type' => 'wp-data-presentation',
            'posts_per_page' => -1,
            'fields' => 'ids'
        ));
    
        if(empty($posts)){
            return 'No data';
        }

        $filters = $this->format_dates_to_one_year($filters);

        global $wpdb;
        $data = [];

        $whereSQL = ' WHERE 1=1';
        if($filters['from'] != ''){
            $whereSQL .= " AND STR_TO_DATE(event_date, '%d %M %Y') >= STR_TO_DATE('{$filters['from']}', '%d %M %Y')";
        }

        if($filters['to'] != ''){
            $whereSQL .= " AND STR_TO_DATE(event_date, '%d %M %Y') <= STR_TO_DATE('{$filters['to']}', '%d %M %Y')";
        }

        $columns = array('region', 'country', 'admin1', 'admin2', 'admin3', 'location');
        if (!empty($filters)) {
            foreach($filters as $key => $filter) {
                if(!empty($filter)){
                    if(is_array($filter)){
                        if($key == "locations"){
                            $conditions = array();
                            foreach($columns as $column){
                                foreach($filter as $value){
                                    $conditions[] = "$column = '{$value}'";
                                }
                            }
                            $whereSQL .= " AND (".implode(' OR ', $conditions).")";
                        }elseif($key === 'disorder_type'){
                            $whereSQL .= " AND {$key} IN ('" . implode("', '", $filter) . "')";
                        }
                    }
                }
            }
        }


        foreach($posts as $id){
            $table_name = 'wpdp_data_'.$id;
            $table_exists = $wpdb->get_var("SHOW TABLES LIKE '{$table_name}'") === $table_name;
            if(!$table_exists){
                continue;
            }

            $result = $wpdb->get_results("SELECT 
            ".implode(', ', $types)." 
             FROM {$table_name} {$whereSQL} LIMIT 50");
            $data = array_merge($data,$result);
        }

        // var_dump($data);exit;
        return $data;

    }

    public function get_map_data(){
        $filters = [
            'disorder_type'=>$_REQUEST['type_val'],
            'locations'=>$_REQUEST['locations_val'],
            'from'=>$_REQUEST['from_val'],
            'to'=>$_REQUEST['to_val']
        ];

        $types = [
            'event_date',
            'disorder_type',
            'event_type',
            'sub_event_type',
            'country',
            'fatalities',
            'location',
            'latitude',
            'longitude',
            'source',
            'notes',
            'timestamp',
        ];
   

        $data = $this->get_data($filters,$types);

        wp_send_json_success($data);

    }

    public static function shortcode_output($atts){
        wp_enqueue_script(WP_DATA_PRESENTATION_NAME.'google-maps-cluster');
        wp_enqueue_script(WP_DATA_PRESENTATION_NAME.'google-maps-api');
        
    ?>

        <div id="wpdp_map"></div>

    <?php }


}

WPDP_Maps::get_instance();
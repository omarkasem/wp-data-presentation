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


    }



    function enqueue_scripts() {
        wp_register_script(WP_DATA_PRESENTATION_NAME.'google-maps-api', 'https://maps.googleapis.com/maps/api/js?key='.get_field('google_maps_api_key','option').'&callback=wpdp_maps&loading=async&libraries=marker', array(), null, true);

        
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
        $queryArgs = [];
        $union_queries = [];

        foreach($posts as $id){
            $table_name = $wpdb->prefix. 'wpdp_data_'.$id;
            $table_exists = $wpdb->get_var("SHOW TABLES LIKE '{$table_name}'") === $table_name;
            if(!$table_exists){
                continue;
            }

            $date_sample = $wpdb->get_var("SELECT event_date FROM $table_name LIMIT 1");
            $date_format = WPDP_Shortcode::get_date_format($date_sample);
            $column_exists = $wpdb->get_results("SHOW COLUMNS FROM {$table_name} LIKE 'inter2'");
            $whereSQL = $this->build_where_clause($filters, $queryArgs, $date_format, $column_exists);
            if($column_exists){
                $types[] = 'inter2';
                if (($key = array_search('iso', $types)) !== false) {
                    unset($types[$key]);
                }
            }else{
                if (($key = array_search('inter2', $types)) !== false) {
                    unset($types[$key]);
                }
                $types[] = 'iso';
            }
            $query = "SELECT 
            ".implode(', ', $types)." 
             FROM {$table_name} {$whereSQL}";

            $union_queries[] = $query;

        }
        $union_query = implode(' UNION ALL ', $union_queries);

        $final_query = "
        SELECT DISTINCT t.*
        FROM ({$union_query}) AS t
        LIMIT 500
        ";

        $transient_key = md5($final_query); 
        $data = get_transient('wpdp_cache_'.$transient_key);
        if(empty($data)){
            $data = $wpdb->get_results($wpdb->prepare($final_query, $queryArgs), ARRAY_A);
            set_transient('wpdp_cache_'.$transient_key, $data);
        }

        $count = count($data);
        return ['data'=>$data,'count'=>$count];

    }

    private function build_where_clause($filters, &$queryArgs, $date_format, $column_exists) {
        $whereSQL = ' WHERE 1=1 ';

        if(!empty($filters['locations'])){
            $whereSQL .= ' AND (';
            $conditions = [];
            foreach ($filters['locations'] as $value) {
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
        }


        if(!empty($filters['disorder_type'])){
            $conditions = [];
            foreach ($filters['disorder_type'] as $value) {
                $value_parts = explode('+', $value);
                $sub_conditions = [];
                foreach ($value_parts as $part) {
                    list($val, $col) = explode('__', $part);
                    $sub_conditions[] = "$col = %s".(in_array($value,$filters['fatalities']) ? ' AND fatalities > 0' : '');
                    $queryArgs[] = $val;
                }
                $conditions[] = '(' . implode(' AND ', $sub_conditions) . ')';
            }
            $whereSQL .= " AND (" . implode(' OR ', $conditions) . ")";
        }


        if(!empty($filters['actors'])){
            $conditions = [];
            foreach ($filters['actors'] as $value) {
                $value_parts = explode('+', $value);
                foreach ($value_parts as $part) {
                    $conditions[] = "inter1 = %s";
                    $queryArgs[] = $part;
                    if($column_exists){
                        $conditions[] = "inter2 = %s";
                        $queryArgs[] = $part;
                    }
                }
            }
            $whereSQL .= " AND (" . implode(' OR ', $conditions) . ")";
        }
        
        $mysql_date_format = $date_format['mysql'];

        if(!empty($filters['from'])){
            $whereSQL .= " AND STR_TO_DATE(event_date, '{$mysql_date_format}') >= STR_TO_DATE(%s, '{$mysql_date_format}')";
            $queryArgs[] = date($date_format['php'], strtotime($filters['from']));
        }

        if(!empty($filters['to'])){
            $whereSQL .= " AND STR_TO_DATE(event_date, '{$mysql_date_format}') <= STR_TO_DATE(%s, '{$mysql_date_format}')";
            $queryArgs[] = date($date_format['php'], strtotime($filters['to']));
        }
        return $whereSQL;
    }


    public function get_map_data(){
        $filters = [
            'disorder_type' => isset($_REQUEST['type_val']) ? $_REQUEST['type_val'] : [],
            'locations' => isset($_REQUEST['locations_val']) ? $_REQUEST['locations_val'] : [],
            'actors' => isset($_REQUEST['actors_val']) ? $_REQUEST['actors_val'] : [],
            'fatalities' => isset($_REQUEST['fat_val']) ? $_REQUEST['fat_val'] : [],
            'from' => isset($_REQUEST['from_val']) ? $_REQUEST['from_val'] : '',
            'to' => isset($_REQUEST['to_val']) ? $_REQUEST['to_val'] : ''
        ];

        $merged_types = array_unique(array_merge($filters['disorder_type'],$filters['fatalities']));
        $filters['disorder_type'] = $merged_types;

        $types = [
            'event_date',
            'disorder_type',
            'event_type',
            'sub_event_type',
            'event_id_cnty',
            'region',
            'country',
            'fatalities',
            'inter1',
            'location',
            'admin1',
            'admin2',
            'admin3',
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
        <div class="wpdp_filter_content maps">
                <div id="wpdp_map"></div>
        </div>
    <?php }


}

WPDP_Maps::get_instance();
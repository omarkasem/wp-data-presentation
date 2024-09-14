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

        $where_sql = ' WHERE 1=1';
        if (!empty($filters)) {
            foreach($filters as $key => $filter) {
                if(!empty($filter)){
                    if(is_array($filter)){
                        if($key == "locations"){

                            $where_sql .= ' AND ';
                            $loci = 0;
                            foreach($filter as $value){ $loci++;
                                $conditions = array();
                                if(strpos($value,'+') !== false){
                                    $value = explode(' + ',$value);
                                    foreach($value as $v){
                                        $real_v = explode('__',$v);
                                        $column = $real_v[1];
                                        $real_value = $real_v[0];
                                        $conditions[] = "$column = '{$real_value}'";
                                    }
                                }else{
                                    $real_v = explode('__',$value);
                                    $column = $real_v[1];
                                    $real_value = $real_v[0];
                                    $conditions[] = "$column = '{$real_value}'";
                                }
                                $where_sql .= " (".implode(' AND ', $conditions).")";
                                if($loci !== count($filters['locations'])){
                                    $where_sql.= ' OR ';
                                }
                            }
                        }elseif($key === 'disorder_type'){
                            $conditions2 = [];
                            foreach($filter as $inc_v){

                                if(strpos($inc_v,'+') !== false){
                                    $inc_v = explode('+',$inc_v);
                                    $i=0;
                                    foreach($inc_v as $inc_v2){$i++;
                                        $inc_v2 = explode('__',$inc_v2);
                                        $inc_type[$inc_v2[1]][] = $inc_v2[0];
                                    }
                                }else{
                                    $inc_v = explode('__',$inc_v);
                                    $inc_type[$inc_v[1]][] = $inc_v[0];
                                }

                            }

                            foreach($inc_type as $inc_type_k => $inc_type_v){$i++;
                                $conditions2[] = "{$inc_type_k} IN ('" . implode("', '", $inc_type_v) . "')";
                            }

                            $where_sql .= " AND (".implode(' OR ', $conditions2).")";
 
                        }elseif($key === 'fatalities'){
                            $conditions3 = [];
                            foreach($filter as $inc_v){

                                if(strpos($inc_v,'+') !== false){
                                    $inc_v = explode('+',$inc_v);
                                    $i=0;
                                    foreach($inc_v as $inc_v2){$i++;
                                        $inc_v2 = explode('__',$inc_v2);
                                        $inc_type[$inc_v2[1]][] = $inc_v2[0];
                                    }
                                }else{
                                    $inc_v = explode('__',$inc_v);
                                    $inc_type[$inc_v[1]][] = $inc_v[0];
                                }

                            }
                            
                            foreach($inc_type as $inc_type_k => $inc_type_v){$i++;
                                $conditions3[] = "{$inc_type_k} IN ('" . implode("', '", $inc_type_v) . "')";
                            }

                            $where_sql .= " AND (".implode(' OR ', $conditions3).") AND fatalities > 0";
 
                        }
                    }
                }
            }
        }

        $union_queries = [];

        foreach($posts as $id){
            $new_where = $where_sql;
            $table_name = $wpdb->prefix. 'wpdp_data_'.$id;
            $table_exists = $wpdb->get_var("SHOW TABLES LIKE '{$table_name}'") === $table_name;
            if(!$table_exists){
                continue;
            }


            $date_sample = $wpdb->get_var("SELECT event_date FROM $table_name LIMIT 1");
            $date_format = WPDP_Shortcode::get_date_format($date_sample);
            $mysql_date_format = $date_format['mysql'];
            $filter_format_from = date($date_format['php'],strtotime($filters['from']));
            $filter_format_to = date($date_format['php'],strtotime($filters['to']));
            
            if($filters['from'] != ''){
                $new_where .= " AND STR_TO_DATE(event_date, '$mysql_date_format') >= STR_TO_DATE('{$filter_format_from}', '$mysql_date_format')";
            }
    
            if($filters['to'] != ''){
                $new_where .= " AND STR_TO_DATE(event_date, '$mysql_date_format') <= STR_TO_DATE('{$filter_format_to}', '$mysql_date_format')";
            }

            $new_where = str_replace('%%','%',$new_where);

            $query = "SELECT 
            ".implode(', ', $types)." 
             FROM {$table_name} {$new_where}";

            $union_queries[] = $query;

        }

        $union_query = implode(' UNION ALL ', $union_queries);

        $final_query = "
        SELECT DISTINCT t.*
        FROM ({$union_query}) AS t
        LIMIT 500
        ";

        $result = $wpdb->get_results($final_query);
        $count = count($result);
        return ['data'=>$result,'count'=>$count];

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


        // Merge actors and disorder_type and remove any duplicates
        $merged_types = array_unique(array_merge($filters['actors'], $filters['disorder_type']));
        $filters['disorder_type'] = $merged_types;

        foreach ($filters['disorder_type'] as $fatality) {
            if (($key = array_search($fatality, $filters['fatalities'])) !== false) {
                unset($filters['fatalities'][$key]);
            }
        }

        $types = [
            'event_date',
            'disorder_type',
            'event_type',
            'sub_event_type',
            'event_id_cnty',
            'region',
            'country',
            'fatalities',
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

        <div id="wpdp_map"></div>

    <?php }


}

WPDP_Maps::get_instance();
<?php 
/**
 * View class
 *
 * @since 1.0.0
 */

final class WPDP_Graphs {

    /**
     * Instance of this class.
     *
     * @since 1.0.0
     *
     * @var WPDP_Graphs
     */
    protected static $_instance = null;

    /**
     * Get instance of the class
     *
     * @since 1.0.0
     *
     * @return WPDP_Graphs
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

        add_action( 'wp_ajax_nopriv_wpdp_graph_request', array($this,'get_graph_data') );
        add_action( 'wp_ajax_wpdp_graph_request', array($this,'get_graph_data') );


    }

    public function get_graph_data(){
        $types = [
            'country',
            'disorder_type',
            'event_date',
            'fatalities',
        ];

        $filters = [
            'disorder_type' => isset($_REQUEST['type_val']) ? $_REQUEST['type_val'] : [],
            'locations' => isset($_REQUEST['locations_val']) ? $_REQUEST['locations_val'] : [],
            'from' => isset($_REQUEST['from_val']) ? $_REQUEST['from_val'] : '',
            'to' => isset($_REQUEST['to_val']) ? $_REQUEST['to_val'] : '',
            'timeframe' => isset($_REQUEST['timeframe']) ? $_REQUEST['timeframe'] : '',
            'actors' => isset($_REQUEST['actors_val']) ? $_REQUEST['actors_val'] : [],
            'fatalities' => isset($_REQUEST['fat_val']) ? $_REQUEST['fat_val'] : []
        ];

        $merged_types = array_unique(array_merge($filters['actors'], $filters['disorder_type'], $filters['fatalities']));
        $filters['disorder_type'] = $merged_types;

        $data = $this->get_data($filters,$types);

        wp_send_json_success($data);
    }

    
    function enqueue_scripts() {
        
        wp_register_script(WP_DATA_PRESENTATION_NAME.'chartjs', WP_DATA_PRESENTATION_URL.'assets/js/chart.js', array('jquery'), WP_DATA_PRESENTATION_VERSION, true);
        
        wp_register_script(WP_DATA_PRESENTATION_NAME.'chartjs-adapter', WP_DATA_PRESENTATION_URL.'assets/js/chartjs-adapter-date-fns.bundle.min.js', array('jquery'), WP_DATA_PRESENTATION_VERSION, true);
        wp_register_script(WP_DATA_PRESENTATION_NAME.'chartjs-moment', WP_DATA_PRESENTATION_URL.'assets/js/moment.min.js', array('jquery'), WP_DATA_PRESENTATION_VERSION, true);
    }
    

    public function get_data($filters,$types) {
        $posts = get_posts(array(
            'post_type' => 'wp-data-presentation',
            'posts_per_page' => -1,
            'fields' => 'ids'
        ));
    
        if(empty($posts)){
            return 'No data';
        }

        global $wpdb;
        $sql_parts = [];
        
        $sql_type = 'YEAR';
        $sql_type2 = 'YEAR';
        $chart_sql = 'year';
        $all_filters = WPDP_Shortcode::get_filters();

        if($filters['from'] != '' || $filters['to'] != ''){
            $all_dates = $all_filters['years'];
            $filters['from'] = ($filters['from'] ? $filters['from'] : $all_dates[0]);
            $filters['to'] = ($filters['to'] ? $filters['to'] : end($all_dates));

            $date1 = date_create($filters['from']);
            $date2 = date_create($filters['to']);
            $diff = date_diff($date1, $date2);
            $days = intval($diff->format('%a'));

            if($filters['timeframe'] != ''){
                if($filters['timeframe'] == 'monthly'){
                    $sql_type = 'MONTH';
                    $chart_sql = 'month';
                }elseif($filters['timeframe'] == 'weekly'){
                    $sql_type = 'YEARWEEK';
                    $chart_sql = 'week';
                }elseif($filters['timeframe'] == 'daily'){
                    $sql_type = 'DAY';
                    $chart_sql = 'day';
                }
            }else{
                if($days < 40){
                    $sql_type = 'YEARWEEK';
                    $chart_sql = 'week';
                }elseif($days >= 40 && $days < 369){
                    $sql_type = 'MONTH';
                    $chart_sql = 'month';
                }elseif($days >= 370 && $days < 700){
                    $sql_type = 'MONTH';
                    $chart_sql = 'quarter';
                }
            }

        }

   
        foreach($posts as $id){
            $table_name = $wpdb->prefix. 'wpdp_data_'.$id;
            $table_exists = $wpdb->get_var("SHOW TABLES LIKE '{$table_name}'") === $table_name;
            if(!$table_exists){
                continue;
            }
            $whereSQL = ' WHERE 1=1';

            $date_sample = $wpdb->get_var("SELECT event_date FROM $table_name LIMIT 1");
            if($date_sample == ''){
                continue;
            }
            $date_format = WPDP_Shortcode::get_date_format($date_sample);
            $mysql_date_format = $date_format['mysql'];
            $filter_format_from = date($date_format['php'],strtotime($filters['from']));
            $filter_format_to = date($date_format['php'],strtotime($filters['to']));

            if($filters['from'] != ''){
                $whereSQL .= " AND STR_TO_DATE(event_date, '$mysql_date_format') >= STR_TO_DATE('{$filter_format_from}', '$mysql_date_format')";
            }
    
            if($filters['to'] != ''){
                $whereSQL .= " AND STR_TO_DATE(event_date, '$mysql_date_format') <= STR_TO_DATE('{$filter_format_to}', '$mysql_date_format')";
            }


            $sql_parts[] = "SELECT 
                SUM(fatalities) as fatalities_count,
                COUNT(*) as events_count,
                {$sql_type}(STR_TO_DATE(event_date, '$mysql_date_format')) as year_week,
                MIN(STR_TO_DATE(event_date, '$mysql_date_format')) as week_start,
                disorder_type,event_type,sub_event_type
            FROM {$table_name} {$whereSQL} 
            ";

        }

        if(empty($sql_parts)){
            return [];
        }

        $count = 0;
        $data = [];
        foreach($filters['disorder_type'] as $type){
            $new_sql = [];
            $conditions2 = [];
            $new_where = '';

            if(strpos($type,'+') !== false){
                $type = explode('+',$type);
                $text = '';
                $i=0;
                foreach($type as $type_v){$i++;
                    $type_v = explode('__',$type_v);
                    $column = $type_v[1];
                    $text .= $type_v[0];
                    if(count($type) != $i){
                        $text.= ' & ';
                    }
                    $conditions2[] = "{$column} = '{$text}'";
                }
            }else{
                $type = explode('__',$type);
                $column = $type[1];
                $text = $type[0];
                $conditions2[] = "{$column} = '{$text}'";
            }

            $new_where .= " AND (".implode(' OR ', $conditions2).")";

            if(!empty($filters['locations'])){
                $new_where .= ' AND (';
                $loci = 0;
                foreach($filters['locations'] as $value){ $loci++;

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
                    $new_where .= " (".implode(' AND ', $conditions).")";
                    if($loci !== count($filters['locations'])){
                        $new_where.= ' OR ';
                    }
                }
                $new_where .= ')';
            }

            foreach($sql_parts as $sql){
                $new_sql[]= $sql.' '.$new_where  . ' GROUP BY year_week, disorder_type ';
            }


            $query = $wpdb->prepare("
            SELECT *
            FROM (
                " . implode(' UNION ', $new_sql) . "
            ) AS t ORDER BY week_start ASC
            ");
            $res = $wpdb->get_results($query);
            $data[$text] = $res;
            $count += count($res);
        }


        return [
            'data'=>$data,
            'chart_sql'=>$chart_sql,
            'count'=>$count
        ];
    }


    public static function shortcode_output(){
        wp_enqueue_script(WP_DATA_PRESENTATION_NAME.'chartjs');
        wp_enqueue_script(WP_DATA_PRESENTATION_NAME.'chartjs-moment');
        wp_enqueue_script(WP_DATA_PRESENTATION_NAME.'chartjs-adapter');
    ?>
        <canvas id="wpdp_chart" width="800" ></canvas>
        <hr>
        <canvas id="wpdp_chart_fat" width="800" ></canvas>
    <?php }


}

WPDP_Graphs::get_instance();
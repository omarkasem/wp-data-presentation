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

        
        add_action('init',array($this,'clear_cache'));


    }

    public function clear_cache(){
        if(!isset($_REQUEST['wpdp_clear_cache'])){
            return;
        }

        global $wpdb;
        $wpdb->query("DELETE FROM {$wpdb->options} WHERE option_name LIKE '_transient_wpdp_cache_%'");
    }

    private function aggregate_data($data){

        if(empty($data)){
            return [];
        }

        $result = [];

        foreach($data as $key => $values){
            $aggregated = [];

            foreach($values as $value){
                $year_month = date('Y-m', strtotime($value->week_start));
                $week_start = date('Y-m', strtotime($value->week_start));

                if(!isset($aggregated[$year_month])){
                    $aggregated[$year_month] = (object) [
                        'fatalities_count' => 0,
                        'events_count' => 0,
                        'year_week' => $year_month,
                        'week_start' => $week_start
                    ];
                }

                $aggregated[$year_month]->fatalities_count += (int) $value->fatalities_count;
                $aggregated[$year_month]->events_count += (int) $value->events_count;
            }

            $result[$key] = array_values($aggregated);
        }

        return $result;
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

        $merged_types = [];
        $new_disorder_type = [];
        $new_fatalities = [];
        $new_actors = [];


        foreach (array_merge($filters['disorder_type'], $filters['fatalities']) as $value) {
            $parts = explode('+', $value);
            foreach ($parts as $part) {
                if (!in_array($part, $merged_types)) {
                    $merged_types[] = $part;
                }
                if (in_array($value, $filters['disorder_type']) && !in_array($part, $new_disorder_type)) {
                    $new_disorder_type[] = $part;
                }
                if (in_array($value, $filters['fatalities']) && !in_array($part, $new_fatalities)) {
                    $new_fatalities[] = $part;
                }
            }
        }

        $filters['merged_types'] = $merged_types;
        $filters['disorder_type'] = $new_disorder_type;
        $filters['fatalities'] = $new_fatalities;

        if(empty($filters['actors'])){
            $filters['actors'] = [1,2,3,4,5,6,7,8];
        }else{
            foreach($filters['actors'] as $actor){
                $value_parts = explode('+', $actor);
                foreach ($value_parts as $part) {
                    if (!in_array($part, $new_actors)) {
                        $new_actors[] = $part;
                    }
                }
            }

            $filters['actors'] = array_unique($new_actors);
        }

        $data = $this->get_data($filters,$types);

        wp_send_json_success($data);
    }

    
    function enqueue_scripts() {
        
        wp_register_script(WP_DATA_PRESENTATION_NAME.'chartjs', WP_DATA_PRESENTATION_URL.'assets/js/chart.js', array('jquery'), WP_DATA_PRESENTATION_VERSION, true);
        
        wp_register_script(WP_DATA_PRESENTATION_NAME.'chartjs-adapter', WP_DATA_PRESENTATION_URL.'assets/js/chartjs-adapter-date-fns.bundle.min.js', array('jquery'), WP_DATA_PRESENTATION_VERSION, true);
        wp_register_script(WP_DATA_PRESENTATION_NAME.'chartjs-moment', WP_DATA_PRESENTATION_URL.'assets/js/moment.min.js', array('jquery'), WP_DATA_PRESENTATION_VERSION, true);
    }
    
    public function build_where_clause($filters, $date_format, $column_exists,$include_actors = false) {
        $whereSQL = ' WHERE 1=1 ';
        $mysql_date_format = $date_format['mysql'];
        $filter_format_from = date($date_format['php'],strtotime($filters['from']));
        $filter_format_to = date($date_format['php'],strtotime($filters['to']));

        if($filters['from'] != ''){
            $whereSQL .= " AND STR_TO_DATE(event_date, '$mysql_date_format') >= STR_TO_DATE('{$filter_format_from}', '$mysql_date_format')";
        }

        if($filters['to'] != ''){
            $whereSQL .= " AND STR_TO_DATE(event_date, '$mysql_date_format') <= STR_TO_DATE('{$filter_format_to}', '$mysql_date_format')";
        }


        if(!empty($filters['actors']) && $include_actors){
            $actor_values = [];
            foreach ($filters['actors'] as $value) {
                $value_parts = explode('+', $value);
                foreach ($value_parts as $part) {
                    $actor_values[] = $part;
                }
            }
            $actor_values = array_map(function($val) { return "'{$val}'"; }, $actor_values);
            $actor_values_str = implode(',', $actor_values);
            
            if (!empty($column_exists)) {
                $whereSQL .= " AND (inter1 IN ({$actor_values_str}) OR inter2 IN ({$actor_values_str}))";
            } else {
                $whereSQL .= " AND inter1 IN ({$actor_values_str})";
            }
        }

        if(!empty($filters['merged_types']) && !$include_actors){
            $conditions = [];
            foreach ($filters['merged_types'] as $value) {
                $value_parts = explode('+', $value);
                $sub_conditions = [];
                foreach ($value_parts as $part) {
                    list($val, $col) = explode('__', $part);
                    $sub_conditions[] = "$col = '{$val}'";
                }
                $conditions[] = '(' . implode(' AND ', $sub_conditions) . ')';
            }
            $whereSQL .= " AND (" . implode(' OR ', $conditions) . ")";
        }

        if(!empty($filters['locations'])){
            $whereSQL .= ' AND (';
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
                $whereSQL .= " (".implode(' AND ', $conditions).")";
                if($loci !== count($filters['locations'])){
                    $whereSQL.= ' OR ';
                }
            }
            $whereSQL .= ')';
        }

        return $whereSQL;

    }

    private function get_sql_type($filters, $all_filters){
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

        return [
            'sql_type'=>$sql_type,
            'chart_sql'=>$chart_sql
        ];
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
        $sql_parts_actors = [];
        
        $all_filters = WPDP_Shortcode::get_filters();
        $sql_type = $this->get_sql_type($filters, $all_filters);
        $chart_sql = $sql_type['chart_sql'];
        $sql_type = $sql_type['sql_type'];
        $column_exists_arr = [];
   
        foreach($posts as $id){
            $table_name = $wpdb->prefix. 'wpdp_data_'.$id;
            $table_exists = $wpdb->get_var("SHOW TABLES LIKE '{$table_name}'") === $table_name;
            if(!$table_exists){
                continue;
            }

            $date_sample = $wpdb->get_var("SELECT event_date FROM $table_name LIMIT 1");
            if($date_sample == ''){
                continue;
            }
            $date_format = WPDP_Shortcode::get_date_format($date_sample);
            $mysql_date_format = $date_format['mysql'];
            $column_exists = $wpdb->get_results("SHOW COLUMNS FROM {$table_name} LIKE 'inter2'");
            $whereSQL = $this->build_where_clause($filters, $date_format, $column_exists,true);
            $whereSQL_actors = $this->build_where_clause($filters, $date_format, $column_exists);

            $sql = "SELECT 
            SUM(fatalities) as fatalities_count,
            COUNT(*) as events_count,
            {$sql_type}(STR_TO_DATE(event_date, '$mysql_date_format')) as year_week,
            MIN(STR_TO_DATE(event_date, '$mysql_date_format')) as week_start
            FROM {$table_name} ";

            $sql_parts[] = $sql.$whereSQL;
            $sql_parts_actors[] = $sql.$whereSQL_actors;
            $column_exists_arr[] = ($column_exists ? $table_name : 0);
        }

        if(empty($sql_parts) && empty($sql_parts_actors)){
            return [];
        }

        $data = [];
        $data_fat = [];
        foreach($filters['merged_types'] as $type){
            $new_sql = [];
            $text_and_conditions = $this->get_text_and_conditions($type);
            $text = $text_and_conditions['text'];
            $conditions = $text_and_conditions['conditions'];

            $new_where = " AND (".implode(' OR ', $conditions).")";
            
            foreach($sql_parts as $sql){
                $new_sql[]= $sql.' '.$new_where  . ' GROUP BY year_week';
            }
  
            $query = "
            SELECT *
            FROM (
                " . implode(' UNION ', $new_sql) . "
            ) AS t ORDER BY week_start ASC
            ";
            $transient_key = md5($query);
            $res = get_transient('wpdp_cache_'.$transient_key);
            if(empty($res)){
                $res = $wpdb->get_results($wpdb->prepare($query));
                set_transient('wpdp_cache_'.$transient_key, $res);
            }

            foreach($filters['fatalities'] as $fat){
                $fat_text_and_conditions = $this->get_text_and_conditions($fat);
                if($text === $fat_text_and_conditions['text']){
                    $data_fat[$text] = $res;
                }
            }

            foreach($filters['disorder_type'] as $disorder_type){
                $disorder_type_text_and_conditions = $this->get_text_and_conditions($disorder_type);
                if($text === $disorder_type_text_and_conditions['text']){
                    $data[$text] = $res;
                }
            }
        }

        $inter_labels = [
            1 => "State Forces",
            2 => "Rebel Groups",
            3 => "Political Militias",
            4 => "Identity Militias",
            5 => "Rioters",
            6 => "Protesters",
            7 => "Civilians",
            8 => "External/Other Force"
        ];

        $data_actors = [];

        foreach($filters['actors'] as $type){
            $new_sql = [];
            $conditions = [];
  
            
            foreach($sql_parts_actors as $key => $sql){
                $value_parts = explode('+', $type);
                foreach ($value_parts as $part) {
                    $conditions[] = "inter1 = '{$part}'";
                    foreach($column_exists_arr as $column_exists){
                        if(strpos($column_exists,$sql) !== false){
                            $conditions[] = "inter2 = '{$part}'";
                        }
                    }
                }
    
                $new_where = " AND (".implode(' OR ', $conditions).")";

                $new_sql[]= $sql.' '.$new_where  . ' GROUP BY year_week';
            }
            $query = "
            SELECT *
            FROM (
                " . implode(' UNION ', $new_sql) . "
            ) AS t ORDER BY week_start ASC
            ";

            $transient_key = md5($query);
            $res = get_transient('wpdp_cache_'.$transient_key);
            if(empty($res)){
                $res = $wpdb->get_results($wpdb->prepare($query));
                set_transient('wpdp_cache_'.$transient_key, $res);
            }

            $data_actors[$inter_labels[$type]] = $res;
        }


        return [
            'data'=>$this->aggregate_data($data),
            'data_fat'=>$this->aggregate_data($data_fat),
            'data_actors'=>$this->aggregate_data($data_actors),
            'chart_sql'=>$chart_sql
        ];
    }

    public function get_text_and_conditions($type){
        $conditions = [];
        $text = '';
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
                $conditions[] = "{$column} = '{$text}'";
            }
        }else{
            $type = explode('__',$type);
            $column = $type[1];
            $text = $type[0];
            $conditions[] = "{$column} = '{$text}'";
        }

        return [
            'text'=>$text,
            'conditions'=>$conditions
        ];
    }


    public static function shortcode_output(){
        wp_enqueue_script(WP_DATA_PRESENTATION_NAME.'chartjs');
        wp_enqueue_script(WP_DATA_PRESENTATION_NAME.'chartjs-moment');
        wp_enqueue_script(WP_DATA_PRESENTATION_NAME.'chartjs-adapter');
    ?>
    <div class="wpdp_filter_content table-responsive" >
        <canvas id="wpdp_chart"   ></canvas>
        <hr>
        <canvas id="wpdp_chart_fat"  ></canvas>
        <hr>
        <canvas id="wpdp_chart_bar_chart"  ></canvas>
    </div>
    <?php }


}

WPDP_Graphs::get_instance();
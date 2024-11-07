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


        if(isset($_GET['test566'])){
            var_dump(get_option('test566'));exit;
            $agg= [];
            foreach(get_option('test566') as $res){
                if(isset( $agg[$res['disorder_type'] ][$res['sql_date']] )){
                    $agg[$res['disorder_type']][$res['sql_date']]['fatalities_count'] += $res['fatalities_count'];
                    $agg[$res['disorder_type']][$res['sql_date']]['events_count'] += $res['events_count'];
                }else{
                    $agg[$res['disorder_type']][$res['sql_date']] = $res;
                }


                if(isset( $agg[$res['event_type'] ][$res['sql_date']] )){
                    $agg[$res['event_type']][$res['sql_date']]['fatalities_count'] += $res['fatalities_count'];
                    $agg[$res['event_type']][$res['sql_date']]['events_count'] += $res['events_count'];
                }else{
                    $agg[$res['event_type']][$res['sql_date']] = $res;
                }

                if(isset( $agg[$res['sub_event_type'] ][$res['sql_date']] )){
                    $agg[$res['sub_event_type']][$res['sql_date']]['fatalities_count'] += $res['fatalities_count'];
                    $agg[$res['sub_event_type']][$res['sql_date']]['events_count'] += $res['events_count'];
                }else{
                    $agg[$res['sub_event_type']][$res['sql_date']] = $res;
                }

            }

            var_dump($agg);exit;
        }

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
            'fatalities' => isset($_REQUEST['fat_val']) ? $_REQUEST['fat_val'] : [],
            'actor_names' => isset($_REQUEST['actor_names_val']) ? $_REQUEST['actor_names_val'] : '',
            'target_civ' => isset($_REQUEST['target_civ']) ? $_REQUEST['target_civ'] : ''
        ];
        
        if((int) $_REQUEST['all_selected'] === 1 || (empty($filters['disorder_type']) && empty($filters['fatalities']))){
            $filters['disorder_type'] = [];
            $filters['fatalities'] = [];
            $incidents = get_field('incident_type_filter','option');
            $db_columns = array(
                'disorder_type',
                'event_type',
                'sub_event_type'
            );

            foreach($incidents as $incident){
                foreach($incident['filter'] as $filter){
                    if(strpos($filter['hierarchial'],'1') !== false){
                        foreach($db_columns as $column){
                            $filters['disorder_type'] = array_merge($filters['disorder_type'],$filter[$column]);
                        }
                    }
                }
            }
        }
        
        $merged_types = [];
        $new_actors = [];

        foreach (array_merge($filters['disorder_type'], $filters['fatalities']) as $value) {
            $parts = explode('+', $value);
            foreach ($parts as $part) {
                if (!in_array($part, $merged_types)) {
                    $merged_types[] = $part;
                }
            }
        }

        $filters['merged_types'] = $merged_types;

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
    
    public function build_where_clause($filters, $date_format, $column_exists,$actor_column_exists) {
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



        if(!empty($filters['actor_names'])){
            $conditions = [];
            foreach ($filters['actor_names'] as $value) {
                $value_parts = explode('+', $value);
                foreach ($value_parts as $part) {
                    $conditions[] = "actor1 = '" . esc_sql($part) . "'";
                    if($actor_column_exists){
                        $conditions[] = "actor2 = '" . esc_sql($part) . "'";
                    }
                }
            }
            
            $whereSQL .= " AND (" . implode(' OR ', $conditions) . ")";
        }


        if(!empty($filters['target_civ'])){
            if($filters['target_civ'] == 'yes'){
                $whereSQL .= " AND (civilian_targeting != '') ";
            }
        }


        if(!empty($filters['disorder_type']) || !empty($filters['fatalities'])) {
            $whereSQL .= " AND (";
            $conditions_added = false;

            // Disorder type conditions
            if(!empty($filters['disorder_type'])) {
                $values_by_column = [];
                foreach ($filters['disorder_type'] as $value) {
                    $value_parts = explode('+', $value);
                    foreach ($value_parts as $part) {
                        list($val, $col) = explode('__', $part);
                        $values_by_column[$col][] = "'" . esc_sql($val) . "'";
                    }
                }
                
                $conditions = [];
                foreach ($values_by_column as $column => $values) {
                    $conditions[] = "$column IN (" . implode(',', $values) . ")";
                }
                $whereSQL .= "(" . implode(' OR ', $conditions) . ")";
                $conditions_added = true;
            }

            // Fatalities conditions
            if(!empty($filters['fatalities'])) {
                if($conditions_added) {
                    $whereSQL .= " OR ";
                }
                $values_by_column = [];
                foreach ($filters['fatalities'] as $value) {
                    $value_parts = explode('+', $value);
                    foreach ($value_parts as $part) {
                        list($val, $col) = explode('__', $part);
                        $values_by_column[$col][] = "'" . esc_sql($val) . "'";
                    }
                }
                
                $conditions = [];
                foreach ($values_by_column as $column => $values) {
                    $conditions[] = "$column IN (" . implode(',', $values) . ")";
                }
                $whereSQL .= "( fatalities > 0 AND (" . implode(' OR ', $conditions) . "))";
            }

            $whereSQL .= ")";
        }

        if(!empty($filters['actors'])) {
            $actor_values = [];
            foreach ($filters['actors'] as $value) {
                $actor_values = array_merge($actor_values, array_map(function($v) {
                    return "'" . esc_sql($v) . "'";
                }, explode('+', $value)));
            }
            $actor_values = array_unique($actor_values);
            $whereSQL .= " AND (inter1 IN (" . implode(',', $actor_values) . ")";
            
            if($column_exists){
                $whereSQL .= " OR inter2 IN (" . implode(',', $actor_values) . ")";
            }
            $whereSQL .= ")";
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
                // Handle custom timeframe selection
                if($filters['timeframe'] == 'yearly'){
                    $sql_type = 'YEAR';
                    $chart_sql = 'year';
                    $interval = 1;
                }
                elseif($filters['timeframe'] == 'monthly'){
                    $sql_type = 'MONTH';
                    $chart_sql = 'month';
                    $interval = 1;
                }
                elseif($filters['timeframe'] == 'weekly'){
                    $sql_type = 'YEARWEEK';
                    $chart_sql = 'week';
                    $interval = 1;
                }
                elseif($filters['timeframe'] == 'daily'){
                // Auto timeframe based on date range
                if($days <= 30) { // Up to 1 month
                    $sql_type = 'DAY';
                    $chart_sql = 'day';
                    $interval = 1;
                }
                elseif($days <= 60) { // Up to 2 months
                    $sql_type = 'DAY';
                    $chart_sql = 'day';
                    $interval = 2;
                }
                elseif($days <= 90) { // Up to 3 months
                    $sql_type = 'DAY';
                    $chart_sql = 'day';
                    $interval = 3;
                }
                elseif($days <= 150) { // Up to 5 months
                    $sql_type = 'DAY';
                    $chart_sql = 'day';
                    $interval = 5;
                }
                elseif($days <= 180) { // Up to 6 months
                    $sql_type = 'DAY';
                    $chart_sql = 'day';
                    $interval = 6;
                }
                elseif($days <= 210) { // Up to 7 months
                    $sql_type = 'WEEK';
                    $chart_sql = 'week';
                    $interval = 1;
                }
                else { // More than 7 months
                    $sql_type = 'MONTH';
                    $chart_sql = 'month';
                    $interval = 1;
                }
                }
            }else{
                // Auto timeframe based on date range
                if($days <= 30) { // Up to 1 month
                    $sql_type = 'DAY';
                    $chart_sql = 'day';
                    $interval = 1;
                }
                elseif($days <= 60) { // Up to 2 months
                    $sql_type = 'DAY';
                    $chart_sql = 'day';
                    $interval = 2;
                }
                elseif($days <= 90) { // Up to 3 months
                    $sql_type = 'DAY';
                    $chart_sql = 'day';
                    $interval = 3;
                }
                elseif($days <= 150) { // Up to 5 months
                    $sql_type = 'DAY';
                    $chart_sql = 'day';
                    $interval = 5;
                }
                elseif($days <= 180) { // Up to 6 months
                    $sql_type = 'DAY';
                    $chart_sql = 'day';
                    $interval = 6;
                }
                elseif($days <= 210) { // Up to 7 months
                    $sql_type = 'WEEK';
                    $chart_sql = 'week';
                    $interval = 1;
                }
                else { // More than 7 months
                    $sql_type = 'MONTH';
                    $chart_sql = 'month';
                    $interval = 1;
                }
            }
        }

        return array(
            'sql_type' => $sql_type,
            'chart_sql' => $chart_sql,
            'interval' => $interval,
        );
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
        $agg = [];
        $latest_date = null;
        
        $all_filters = WPDP_Shortcode::get_filters();
        $sql_type_info = $this->get_sql_type($filters, $all_filters);
        $chart_sql = $sql_type_info['chart_sql'];
        $sql_type = $sql_type_info['sql_type'];
        $intervals = $sql_type_info['interval'];
        
        $column_exists_arr = [];
   
        $inter_labels = [
            0 => 'No recorded actors',
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
            $date_format = WPDP_Shortcode::get_date_format($date_sample,true);
            $mysql_date_format = $date_format['mysql'];
            $column_exists = $wpdb->get_results("SHOW COLUMNS FROM {$table_name} LIKE 'inter2'");
            $actor_column_exists = $wpdb->get_results("SHOW COLUMNS FROM {$table_name} LIKE 'actor2'");
            $whereSQL = $this->build_where_clause($filters, $date_format, $column_exists,$actor_column_exists);

            $current_latest_date = $wpdb->get_var("
                SELECT MAX(STR_TO_DATE(event_date, '$mysql_date_format')) 
                FROM $table_name
            ");
            
            if ($latest_date === null || strtotime($current_latest_date) > strtotime($latest_date)) {
                $latest_date = $current_latest_date;
            }


            $sql = "SELECT 
            SUM(fatalities) as fatalities_count,
            COUNT(*) as events_count,
            disorder_type, 
            event_type, 
            sub_event_type,
            CASE 
                WHEN '{$sql_type}' = 'YEARWEEK' THEN DATE_FORMAT(STR_TO_DATE(CONCAT(YEARWEEK(STR_TO_DATE(event_date, '$mysql_date_format')), ' Sunday'), '%X%V %W'), '%Y-%m-%d')
                WHEN '{$sql_type}' = 'MONTH' THEN DATE_FORMAT(STR_TO_DATE(event_date, '$mysql_date_format'), '%Y-%m-01')
                WHEN '{$sql_type}' = 'YEAR' THEN DATE_FORMAT(STR_TO_DATE(event_date, '$mysql_date_format'), '%Y-01-01')
                ELSE DATE_FORMAT(STR_TO_DATE(event_date, '$mysql_date_format'), '%Y-%m-%d')
            END as sql_date
            FROM {$table_name} {$whereSQL} 
            GROUP BY sql_date, disorder_type, event_type, sub_event_type
            ";

            $transient_key = md5($sql);
            $res = get_transient('wpdp_cache_'.$transient_key);
            if(empty($res) || WP_DATA_PRESENTATION_DISABLE_CACHE){
                $results = $wpdb->get_results($sql, ARRAY_A);
                set_transient('wpdp_cache_'.$transient_key, $res);
            }

            if(!empty($results)){
                foreach($results as $res){

                    foreach($filters['merged_types'] as $type){
                        $type = explode('__',$type);
                        $value = $type[0];
                        $type = $type[1];
    
                        if($type === 'disorder_type' && $res['disorder_type'] === $value){
    
                            if(isset( $agg[$res['disorder_type'] ][$res['sql_date']] )){
                                $agg[$res['disorder_type']][$res['sql_date']]['fatalities_count'] += $res['fatalities_count'];
                                $agg[$res['disorder_type']][$res['sql_date']]['events_count'] += $res['events_count'];
                            }else{
                                $agg[$res['disorder_type']][$res['sql_date']] = $res;
                            }
    
                        }
    
    
                        if($type === 'event_type' && $res['event_type'] === $value){
    
                            if(isset( $agg[$res['event_type'] ][$res['sql_date']] )){
                                $agg[$res['event_type']][$res['sql_date']]['fatalities_count'] += $res['fatalities_count'];
                                $agg[$res['event_type']][$res['sql_date']]['events_count'] += $res['events_count'];
                            }else{
                                $agg[$res['event_type']][$res['sql_date']] = $res;
                            }
    
                        }
    
                        if($type === 'sub_event_type' && $res['sub_event_type'] === $value){
    
                            if(isset( $agg[$res['sub_event_type'] ][$res['sql_date']] )){
                                $agg[$res['sub_event_type']][$res['sql_date']]['fatalities_count'] += $res['fatalities_count'];
                                $agg[$res['sub_event_type']][$res['sql_date']]['events_count'] += $res['events_count'];
                            }else{
                                $agg[$res['sub_event_type']][$res['sql_date']] = $res;
                            }
    
                        }
    
    
                    }
                }
            }

            $inter_text = 'inter1';
            if($column_exists){
                $inter_text = 'inter1,inter2';
            }

            $sql_actor = "SELECT 
            COUNT(*) as events_count,
            {$inter_text},
            CASE 
                WHEN '{$sql_type}' = 'YEARWEEK' THEN DATE_FORMAT(STR_TO_DATE(CONCAT(YEARWEEK(STR_TO_DATE(event_date, '$mysql_date_format')), ' Sunday'), '%X%V %W'), '%Y-%m-%d')
                WHEN '{$sql_type}' = 'MONTH' THEN DATE_FORMAT(STR_TO_DATE(event_date, '$mysql_date_format'), '%Y-%m-01')
                WHEN '{$sql_type}' = 'YEAR' THEN DATE_FORMAT(STR_TO_DATE(event_date, '$mysql_date_format'), '%Y-01-01')
                ELSE DATE_FORMAT(STR_TO_DATE(event_date, '$mysql_date_format'), '%Y-%m-%d')
            END as sql_date
            FROM {$table_name} {$whereSQL} 
            GROUP BY sql_date, {$inter_text}";


            $transient_key = md5($sql_actor);
            $res = get_transient('wpdp_cache_'.$transient_key);
            if(empty($res) || WP_DATA_PRESENTATION_DISABLE_CACHE){
                $results_actors = $wpdb->get_results($sql_actor, ARRAY_A);
                set_transient('wpdp_cache_'.$transient_key, $res);
            }

            if(!empty($results_actors)){
                foreach($results_actors as $res){

                    foreach($filters['actors'] as $value){
    
                        if( $res['inter1'] === $value || (isset($res['inter2']) && $res['inter2'] === $value) ){
                            $text_value = (isset($inter_labels[$value])) ? $inter_labels[$value] : $value;

                            if(isset( $data_actors[$text_value] [$res['sql_date']] )){
                                $data_actors[$text_value][$res['sql_date']]['events_count'] += $res['events_count'];
                            }else{
                                $data_actors[$text_value][$res['sql_date']] = $res;
                            }
    
                        }
    
    
                    }
                }
            }

        }

        return [
            'data'=>$agg,
            'data_fat'=>$agg,
            'data_actors'=>$data_actors,
            'chart_sql' => $chart_sql,
            'intervals' => $intervals,
            'latest_date'=>date('jS M Y', strtotime($latest_date))
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
        $shortcode = WPDP_Shortcode::get_instance();
    ?>
    <div class="wpdp_filter_content table-responsive">
        <canvas id="wpdp_chart" style="height:400px" class="table"  ></canvas>
        <div class="last_updated_chart chart">Last data entry: <span class="last_updated_chart_date"></span> <?php echo $shortcode::info_icon(''); ?></div>
        <hr>
        <canvas id="wpdp_chart_fat" style="height:400px" class="table"  ></canvas>
        <div class="last_updated_chart chart_fat">Last data entry: <span class="last_updated_chart_date"></span> <?php echo $shortcode::info_icon(''); ?></div>
        <hr>
        <canvas id="wpdp_chart_bar_chart" style="height:400px" class="table"  ></canvas>
        <div class="last_updated_chart chart_bar">Last data entry: <span class="last_updated_chart_date"></span> <?php echo $shortcode::info_icon(''); ?></div>
    </div>
    <?php }


}

WPDP_Graphs::get_instance();
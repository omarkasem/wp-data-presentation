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
            'disorder_type'=>$_REQUEST['type_val'],
            'locations'=>$_REQUEST['locations_val'],
            'from'=>$_REQUEST['from_val'],
            'to'=>$_REQUEST['to_val']
        ];


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
        $whereSQL = ' WHERE 1=1';
        $sql_type = 'YEAR';
        $sql_type2 = 'YEAR';
        $chart_sql = 'year';
        $all_filters = WPDP_Shortcode::get_filters();


        if($filters['from'] != ''){
            $whereSQL .= " AND STR_TO_DATE(event_date, '%%d %%M %%Y') >= STR_TO_DATE('{$filters['from']}', '%%d %%M %%Y')";
        }

        if($filters['to'] != ''){
            $whereSQL .= " AND STR_TO_DATE(event_date, '%%d %%M %%Y') <= STR_TO_DATE('{$filters['to']}', '%%d %%M %%Y')";
        }

        if($filters['from'] != '' || $filters['to'] != ''){
            $all_dates = $all_filters['years'];
            $filters['from'] = ($filters['from'] ? $filters['from'] : $all_dates[0]);
            $filters['to'] = ($filters['to'] ? $filters['to'] : end($all_dates));

            $date1 = date_create($filters['from']);
            $date2 = date_create($filters['to']);
            $diff = date_diff($date1, $date2);
            $days = intval($diff->format('%a'));
            if($days < 40){
                $sql_type = 'WEEK';
                $sql_type2 = 'YEARWEEK';
                $chart_sql = 'week';
            }elseif($days >= 40 && $days < 365){
                $sql_type = 'MONTH';
                $sql_type2 = 'MONTH';
                $chart_sql = 'month';
            }elseif($days >= 365 && $days < 700){
                $sql_type = 'MONTH';
                $sql_type2 = 'MONTH';
                $chart_sql = 'quarter';
            }
        }
        $filters['disorder_type'] = (!empty($filters['disorder_type']) ? $filters['disorder_type'] : $all_filters['types']);

   
        foreach($posts as $id){
            $table_name = 'wpdp_data_'.$id;
            $table_exists = $wpdb->get_var("SHOW TABLES LIKE '{$table_name}'") === $table_name;
            if(!$table_exists){
                continue;
            }

            $sql_parts[] = "SELECT 
                SUM(fatalities) as fatalities_count,
                COUNT(*) as events_count,
                {$sql_type2}(STR_TO_DATE(event_date, '%%d %%M %%Y')) as year_week,
                MIN(STR_TO_DATE(event_date, '%%d %%M %%Y')) as week_start,
                disorder_type
            FROM {$table_name}
            ";

        }
        
        if(empty($sql_parts)){
            return [];
        }

        $columns = array('region', 'country', 'admin1', 'admin2', 'admin3', 'location');

        $data = [];
        foreach($filters['disorder_type'] as $type){
            $new_sql = [];
            $new_where = $whereSQL;
            $new_where .= " AND disorder_type = '{$type}' ";

            if(!empty($filters['locations'])){
                $conditions = array();
                foreach($columns as $column){
                    foreach($filters['locations'] as $value){
                        $conditions[] = "$column = '{$value}'";
                    }
                }
                $new_where .= " AND (".implode(' OR ', $conditions).") ";
            }

            foreach($sql_parts as $k => $sql){
                $new_sql[]= $sql.' '.$new_where;
            }
            
            $query = $wpdb->prepare("
            " . implode(' UNION ALL ', $new_sql) . "
            GROUP BY year_week 
            ORDER BY week_start ASC
            ");
            

            $data[$type] = $wpdb->get_results($query);

        }
        return [
            'data'=>$data,
            'chart_sql'=>$chart_sql
        ];
    }


    public static function shortcode_output(){
        wp_enqueue_script(WP_DATA_PRESENTATION_NAME.'chartjs');
        wp_enqueue_script(WP_DATA_PRESENTATION_NAME.'chartjs-moment');
        wp_enqueue_script(WP_DATA_PRESENTATION_NAME.'chartjs-adapter');
    ?>
        <img style="width: 25px;" id="graph_loader" src="<?php echo admin_url('images/loading.gif'); ?>" alt="">
        <canvas id="wpdp_chart" width="800" ></canvas>
    <?php }


}

WPDP_Graphs::get_instance();
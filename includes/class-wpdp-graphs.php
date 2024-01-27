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
        wp_register_script(WP_DATA_PRESENTATION_NAME.'chartjs-adapter', WP_DATA_PRESENTATION_URL.'assets/js/chartjs-adapter-moment.min.js', array('jquery'), WP_DATA_PRESENTATION_VERSION, true);
        wp_register_script(WP_DATA_PRESENTATION_NAME.'chartjs-moment', WP_DATA_PRESENTATION_URL.'assets/js/moment.min.js', array('jquery'), WP_DATA_PRESENTATION_VERSION, true);
        
        wp_register_script(WP_DATA_PRESENTATION_NAME.'chartjs', WP_DATA_PRESENTATION_URL.'assets/js/chart.js', array('jquery'), WP_DATA_PRESENTATION_VERSION, true);

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
        $data = [];
        $count = 0;
        foreach($posts as $id){
            $table_name = 'wpdp_data_'.$id;
            $table_exists = $wpdb->get_var("SHOW TABLES LIKE '{$table_name}'") === $table_name;
            if(!$table_exists){
                continue;
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

            $query = $wpdb->prepare("SELECT ".implode(', ', $types)." FROM {$table_name} {$whereSQL} ORDER BY STR_TO_DATE(event_date, '%%d %%M %%Y') asc", $queryArgs);

            $result = $wpdb->get_results($query, ARRAY_A);
    
            if($result){
                $data = array_merge($data, $result);
            }
        }

        return $data;
    }


    public static function shortcode_output($result){
        wp_enqueue_script(WP_DATA_PRESENTATION_NAME.'chartjs');
        wp_enqueue_script(WP_DATA_PRESENTATION_NAME.'chartjs-moment');
        wp_enqueue_script(WP_DATA_PRESENTATION_NAME.'chartjs-adapter');
    ?>
        <h2 id="wpdp_chart_title">
            You have to use the filter to show the chart.
        </h2>
        <canvas style="display:none;" id="wpdp_chart" width="800" ></canvas>
    <?php }


}

WPDP_Graphs::get_instance();
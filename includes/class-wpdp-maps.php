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



        if(isset($_GET['test1'])){
            var_dump($this->get_map_data());exit;
        }
    }



    function enqueue_scripts() {
        wp_register_script(WP_DATA_PRESENTATION_NAME.'google-maps-api', 'https://maps.googleapis.com/maps/api/js?key='.get_field('google_maps_api_key','option').'&callback=wpdp_maps', array(), null, true);

    }

    public function get_map_data(){
        $posts = get_posts(array(
            'post_type' => 'wp-data-presentation',
            'posts_per_page' => -1,
            'fields' => 'ids'
        ));
    
        if(empty($posts)){
            return 'No data';
        }

        $types = [
            'event_date',
            'disorder_type',
            'event_type',
            'sub_event_type',
            'country',
            'location',
            'latitude',
            'longitude',
            // 'source',
            // 'notes',
            'timestamp',
        ];
   
        $all_filters = WPDP_Shortcode::get_filters();

        global $wpdb;
        $data = [];
        foreach($posts as $id){
            $table_name = 'wpdp_data_'.$id;
            $table_exists = $wpdb->get_var("SHOW TABLES LIKE '{$table_name}'") === $table_name;
            if(!$table_exists){
                continue;
            }

            $result = $wpdb->get_results("SELECT 
            ".implode(', ', $types).",
            SUM(fatalities) as fatalities_count,
            COUNT(*) as events_count
             FROM {$table_name} GROUP BY disorder_type,country");
            $data = array_merge($data,$result);

        }

        wp_send_json_success($data);

    }


    public static function shortcode_output(){
        wp_enqueue_script(WP_DATA_PRESENTATION_NAME.'google-maps-api');
        
        
    ?>
    
        <div id="wpdp_map"></div>

    <?php }


}

WPDP_Maps::get_instance();
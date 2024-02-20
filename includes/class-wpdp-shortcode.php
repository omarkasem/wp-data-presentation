<?php 
/**
 * View class
 *
 * @since 1.0.0
 */

final class WPDP_Shortcode {

    /**
     * Instance of this class.
     *
     * @since 1.0.0
     *
     * @var WPDP_Shortcode
     */
    protected static $_instance = null;

    public $shortcode_atts = [];

    /**
     * Get instance of the class
     *
     * @since 1.0.0
     *
     * @return WPDP_Shortcode
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
        add_shortcode( 'WP_DATA_PRESENTATION',array($this,'show_shortcode'));
        add_action('wp_enqueue_scripts', array($this, 'enqueue_scripts'));
    
        if(isset($_GET['t'])){
            var_dump($this->get_filters());exit;
        }
        
    }


    public function enqueue_scripts(){
        wp_register_script(WP_DATA_PRESENTATION_NAME.'select2', WP_DATA_PRESENTATION_URL.'assets/js/select2.min.js', array('jquery'), WP_DATA_PRESENTATION_VERSION, true);

        wp_register_style(WP_DATA_PRESENTATION_NAME.'select2', WP_DATA_PRESENTATION_URL.'assets/css/select2.min.css', [],WP_DATA_PRESENTATION_VERSION );

        wp_register_style(WP_DATA_PRESENTATION_NAME.'public', WP_DATA_PRESENTATION_URL.'assets/css/wp-data-presentation-public.css', [],WP_DATA_PRESENTATION_VERSION );


        wp_register_style(WP_DATA_PRESENTATION_NAME.'jquery-ui', 'https://cdnjs.cloudflare.com/ajax/libs/jqueryui/1.13.2/themes/base/jquery-ui.min.css', [],WP_DATA_PRESENTATION_VERSION );

        wp_register_script(WP_DATA_PRESENTATION_NAME.'public', WP_DATA_PRESENTATION_URL.'assets/js/wp-data-presentation-public.js', array('jquery','jquery-ui-datepicker'), WP_DATA_PRESENTATION_VERSION, true);

        wp_localize_script( WP_DATA_PRESENTATION_NAME.'public','wpdp_obj',[
            'url'=>WP_DATA_PRESENTATION_URL,
            'ajax_url' => admin_url('admin-ajax.php'),
        ]);

    }

    public static function get_filters(){
        $atts = self::get_instance()->shortcode_atts;

        $posts = get_posts(array(
            'post_type'=>'wp-data-presentation',
            'posts_per_page'=>-1,
            'fields'=>'ids'
        ));

        if(empty($posts)){
            return 'No data';
        }

        $arr_type = ARRAY_A;
        global $wpdb;
        $types = [];
        $years = [];
        $ordered_locations = [];
        foreach($posts as $id){
            $table_name = 'wpdp_data_'.$id;
            $table_exists = $wpdb->get_var("SHOW TABLES LIKE '{$table_name}'") === $table_name;
            if(!$table_exists){
                continue;
            }

            $db_types = $wpdb->get_col("SELECT DISTINCT disorder_type FROM {$table_name}");
            if(!empty($db_types)){
                $types = array_merge($types,$db_types);
            }
            
            $whereSQL = ' WHERE 1=1 ';
            if(isset($atts['from']) && $atts['from'] != ''){
                $whereSQL .= " AND STR_TO_DATE(event_date, '%d %M %Y') >= STR_TO_DATE('{$atts['from']}', '%d %M %Y')";
            }
    
            if(isset($atts['to']) && $atts['to'] != ''){
                $whereSQL .= " AND STR_TO_DATE(event_date, '%d %M %Y') <= STR_TO_DATE('{$atts['to']}', '%d %M %Y')";
            }
   

            $db_years = $wpdb->get_col("SELECT DISTINCT event_date FROM {$table_name} {$whereSQL}");

            if(!empty($db_years)){
                $years = array_merge($years,$db_years);
            }
            
            $db_locations = $wpdb->get_results("SELECT DISTINCT region,country,admin1,admin2,admin3,location FROM {$table_name}", ARRAY_A);

            foreach ($db_locations as $location) {
                $region = $location['region'];
                $country = $location['country'];
                $admin1 = $location['admin1'];
                $admin2 = $location['admin2'];
                $admin3 = $location['admin3'];
                $location = $location['location'];


                if (!empty($region)) {
                    $locations[$region] = $locations[$region] ?? [];
                    $currentLevel = &$locations[$region];
                
                    if (!empty($country) && $region != $country) {
                        $currentLevel[$country] = $currentLevel[$country] ?? [];
                        $currentLevel = &$currentLevel[$country];
                    }
                
                    if (!empty($admin1) && $country != $admin1) {
                        $currentLevel[$admin1] = $currentLevel[$admin1] ?? [];
                        $currentLevel = &$currentLevel[$admin1];
                    }
                
                    if (!empty($admin2) && $admin1 != $admin2) {
                        $currentLevel[$admin2] = $currentLevel[$admin2] ?? [];
                        $currentLevel = &$currentLevel[$admin2];
                    }
                
                    if (!empty($admin3) && $admin2 != $admin3) {
                        $currentLevel[$admin3] = $currentLevel[$admin3] ?? [];
                        $currentLevel = &$currentLevel[$admin3];
                    }
                
                    if (!empty($location)) {
                        $currentLevel[] = $location;
                    }
                }

                $ordered_locations = $locations;
    
            }
        }

        usort($years, function($a, $b) {
            return strtotime($a) - strtotime($b);
        });
        
        $types = array_unique($types);

        $filters = array(
            'types'=>$types,
            'years'=>$years,
            'locations'=>$ordered_locations
        );

        return $filters;
    }


    function printArrayAsList($locations, $level = 0) {
        echo '<ul>';
        foreach ($locations as $key => $value) {
            if(!is_array($value) && intval($value) === 0){
                continue;
            }

            if(is_array($value) && empty($value)){
                continue;
            }
            
            if (is_array($value)) {
                echo '<li class="expandable">';
                echo '<input type="checkbox" class="wpdp_location" value="' . $key . '">';
                echo '<div class="exp_click"><span for="' . $key . '">' . $key . '</span>';
                echo '<span class="dashicons dashicons-arrow-up-alt2 arrow"></span></div>';
                $this->printArrayAsList($value, $level + 1);
            } else {
                echo '<li>';
                echo $value;
            }

            echo '</li>';
        }

        echo '</ul>';
    }
    
    public function show_shortcode($atts){
        $atts = shortcode_atts( array(
            'type' => '',
            'from' => '',
            'to' => ''
        ), $atts);

        // if(isset($atts['type']) && $atts['type'] === 'map'){
        //     if(isset($atts['from']) && $atts['from'] != '' && isset($atts['to']) && $atts['to'] != ''){
        //         $date1 = date_create($atts['from']);
        //         $date2 = date_create($atts['to']);
        //         $diff = date_diff($date1, $date2);
        //         $days = intval($diff->format('%a'));
        //         if($days > 366){
                    
        //         }
        //     }
        // }
        $this->shortcode_atts = $atts;
    
        ob_start();
        wp_enqueue_script(WP_DATA_PRESENTATION_NAME.'select2');
        wp_enqueue_style(WP_DATA_PRESENTATION_NAME.'select2');
        wp_enqueue_style(WP_DATA_PRESENTATION_NAME.'public');
        wp_enqueue_script(WP_DATA_PRESENTATION_NAME.'public');
        wp_enqueue_style( WP_DATA_PRESENTATION_NAME.'jquery-ui' );
        wp_enqueue_style( 'dashicons' );

        ?>

        <div class="wpdp">
            <?php
                $filters = self::get_filters();
                $this->get_html_filter($filters,$atts);
                if(isset($atts['type']) && $atts['type'] === 'table'){
                    WPDP_Tables::shortcode_output();
                }elseif(isset($atts['type']) && $atts['type'] === 'graph'){
                    WPDP_Graphs::shortcode_output();
                }elseif(isset($atts['type']) && $atts['type'] === 'map'){
                    WPDP_Maps::shortcode_output($atts);
                }else{
                    WPDP_Tables::shortcode_output($atts);
                    echo '<br><hr>';
                    WPDP_Graphs::shortcode_output();
                }
            ?>
        </div>

        <script>
            var wpdp_shortcode_atts = '<?php echo json_encode($atts); ?>';
            var wpdp_filter_dates = <?php echo json_encode($filters['years']); ?>;
        </script>


    <?php 
        $output = ob_get_clean();
        return $output;
    }

    function get_from_date_value($filters,$atts){
        if(isset($this->shortcode_atts['from']) && $this->shortcode_atts['from'] != ''){
            echo $this->shortcode_atts['from'];
        }else{
            if($atts['type'] === 'map'){
                echo date('d F Y',strtotime('-1 year'));
            }else{
                echo date('d F Y',strtotime($filters['years'][0]));
            }
        }
    }

    function get_to_date_value($filters,$atts){
        if(isset($this->shortcode_atts['from']) && $this->shortcode_atts['from'] != ''){
            echo $this->shortcode_atts['from'];
        }else{
            if($atts['type'] === 'map'){
                echo date('d F Y');
            }else{
                echo date('d F Y',strtotime(end($filters['years'])));
            }
        }
    }

    function get_html_filter($filters,$atts){
        ?>
        <div class="filter_data" style="display:none;">
            <a class="filter" href=""><span class="dashicons dashicons-image-filter"></span></a>
            <div class="con">
                <span class="filter_back dashicons dashicons-arrow-left-alt"></span>
                <form id="filter_form" action="" style="margin-top:15px;">

                    <?php if($atts['type'] === 'graph' || $atts['type'] == ''){ ?>
                    <div class="grp active">

                        <div class="title">
                            COUNT TYPE <span class="dashicons dashicons-arrow-up-alt2"></span>
                        </div>
                        <div class="content">
                            <select name="wpdp_type_selector" id="wpdp_type_selector">
                                <option value="fatalities">Fatalities</option>
                                <option value="incident_count">Incident Count</option>
                            </select>
                        </div>
                    </div>
                    <?php } ?>

                    <div class="grp active">

                        <div class="title">
                            INCIDENT TYPE <span class="dashicons dashicons-arrow-up-alt2"></span>
                        </div>
                        <div class="content">
                            <select multiple="multiple" name="wpdp_type" id="wpdp_type">
                                <option></option>
                                <?php foreach($filters['types'] as $type){
                                    echo '<option selected="selected" value="'.$type.'">'.$type.'</option>';
                                } ?>
                            </select>
                        </div>
                    </div>

                    <div class="grp active">
                        <div class="title">
                            LOCATION/REGION <span class="dashicons dashicons-arrow-down-alt2"></span>
                        </div>
                        <div class="content">
                            <?php $this->printArrayAsList($filters['locations']); ?>
                        </div>
                    </div>

                    <div class="grp active">

                        <div class="title">
                            DATE RANGE <span class="dashicons dashicons-arrow-up-alt2"></span>
                        </div>
                        <div class="content <?php echo (isset($atts['type']) && $atts['type'] === 'map' ? 'filter_maps' : ''); ?>">
                            <div class="dates">
                                <label for="wpdp_from">FROM</label>
                                <input value="<?php $this->get_from_date_value($filters,$atts); ?>" type="text" name="wpdp_from" id="wpdp_from">
                            </div>
                            <div class="dates">
                                <label style="margin-right: 23px;" for="wpdp_to">TO</label>
                                <input value="<?php $this->get_to_date_value($filters,$atts); ?>" type="text" name="wpdp_to" id="wpdp_to">
                            </div>
                        </div>

                    </div>
                    
                    <input type="submit" value="Apply Filters">
                    <img id="filter_loader" src="<?php echo admin_url('images/loading.gif'); ?>" alt="">

                </form>
            </div>
        </div>
    <?php }


}

WPDP_Shortcode::get_instance();
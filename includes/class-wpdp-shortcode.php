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
    }

    public function enqueue_scripts(){
        wp_register_script(WP_DATA_PRESENTATION_NAME.'select2', WP_DATA_PRESENTATION_URL.'assets/js/select2.min.js', array('jquery'), WP_DATA_PRESENTATION_VERSION, true);

        wp_register_style(WP_DATA_PRESENTATION_NAME.'select2', WP_DATA_PRESENTATION_URL.'assets/css/select2.min.css', [],WP_DATA_PRESENTATION_VERSION );

        wp_register_style(WP_DATA_PRESENTATION_NAME.'public', WP_DATA_PRESENTATION_URL.'assets/css/wp-data-presentation-public.css', [],WP_DATA_PRESENTATION_VERSION );


        wp_register_style(WP_DATA_PRESENTATION_NAME.'jquery-ui', WP_DATA_PRESENTATION_URL.'assets/css/jquery-ui.min.css', [],WP_DATA_PRESENTATION_VERSION );

        wp_register_script(WP_DATA_PRESENTATION_NAME.'public', WP_DATA_PRESENTATION_URL.'assets/js/wp-data-presentation-public.js', array('jquery'), WP_DATA_PRESENTATION_VERSION, true);

        wp_localize_script( WP_DATA_PRESENTATION_NAME.'public','wpdp_obj',[
            'url'=>WP_DATA_PRESENTATION_URL,
        ]);

    }

    private function get_filters($result){
        $filters = [];
        $i=-1;
        foreach($result as $key => $val){$i++;
            $filters['types'][] = $val['disorder_type'];
            $filters['years'][] = $val['event_date'];
            $filters['locations'][] = $val['country'];
        }
        $filters['types'] = array_unique($filters['types']);
        $filters['locations'] = array_unique($filters['locations']);
        sort($filters['years']);
        sort($filters['types']);
        sort($filters['locations']);
        return $filters;
    }

    public function get_all_data(){
        $posts = get_posts(array(
            'post_type'=>'wp-data-presentation',
            'posts_per_page'=>-1,
            'fields'=>'ids'
        ));

        if(empty($posts)){
            return 'No data';
        }

        $data = [];
        foreach($posts as $id){
            $result = get_post_meta($id,'wpdp_results',true);
            $data = array_merge($data,$result);
        }
        return $data;
        
    }


    public function show_shortcode($atts){
        
        
        // $id = intval($atts['id']);
        // if($id === 0 || get_post_type($id) !== 'wp-data-presentation'){
        //     return 'ID is not correct';
        // }

        ob_start();
        wp_enqueue_script(WP_DATA_PRESENTATION_NAME.'select2');
        wp_enqueue_style(WP_DATA_PRESENTATION_NAME.'select2');
        wp_enqueue_style(WP_DATA_PRESENTATION_NAME.'public');
        wp_enqueue_script(WP_DATA_PRESENTATION_NAME.'public');
        wp_enqueue_style( 'dashicons' );

        $result = $this->get_all_data();
        
        ?>

        <div class="wpdp">
            <?php 
                $filters = $this->get_filters($result);
                $this->get_filter($filters);
                WPDP_Tables::shortcode_output($result);
                echo '<br><hr>';
                WPDP_Graphs::shortcode_output($result);
                echo '<br><hr>';
                WPDP_Maps::shortcode_output($result);
            ?>
        </div>

        <script>
            var wpdp_data = <?php echo json_encode($result); ?>;
        </script>

    <?php 
        $output = ob_get_clean();
        return $output;
    }

    function get_filter($filters){ ?>
        <div class="filter_data">
            <a class="filter" href=""><span class="dashicons dashicons-image-filter"></span></a>
            <div class="con">
                <span class="filter_back dashicons dashicons-arrow-left-alt"></span>
                <form action="">
                    <div class="grp">
                        <label for="wpdp_type">INCIDENT TYPE</label>
                        <select multiple="multiple" name="wpdp_type" id="wpdp_type">
                        <option></option>
                            <?php foreach($filters['types'] as $type){
                                echo '<option value="'.$type.'">'.$type.'</option>';
                            } ?>
                        </select>
                    </div>

                    <div class="grp">
                        <label for="wpdp_location">LOCATION/REGION</label>
                        <select multiple="multiple" name="wpdp_location" id="wpdp_location">
                        <option></option>
                        <?php foreach($filters['locations'] as $location){
                                echo '<option value="'.$location.'">'.$location.'</option>';
                            } ?>
                        </select>
                    </div>

                    <div class="grp">
                        <label for="wpdp_date">DATE RANGE</label>
                        <div class="dates">
                            <label for="wpdp_from">FROM</label>
                            <select data-allow-clear="true" name="wpdp_from" id="wpdp_from">
                            <option></option>
                            <?php 
                            $years = array_unique($filters['years']);
                            sort($years);
                            foreach($years as $year){
                                    echo '<option value="'.$year.'">'.$year.'</option>';
                            } ?>
                            </select>
                        </div>
                        <div class="dates">
                            <label style="margin-right: 23px;" for="wpdp_to">TO</label>
                            <select data-allow-clear="true" name="wpdp_to" id="wpdp_to">
                            <option></option>
                            <?php 
                            $years = array_unique($filters['years']);
                            foreach($years as $year){
                                    echo '<option value="'.$year.'">'.$year.'</option>';
                            } ?>
                            </select>
                        </div>

                    </div>           

                </form>
            </div>
        </div>
    <?php }


}

WPDP_Shortcode::get_instance();
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
        // Start the session if it hasn't been started yet
        if (session_status() == PHP_SESSION_NONE) {
            session_start();
        }
        // Remove all saved session values

        // session_destroy();


    }

    /**
     * Add hooks
     *
     * @since 1.0.0
     */
    private function _add_hooks() {
        add_shortcode('WP_DATA_PRESENTATION', array($this, 'show_shortcode'));
        add_action('wp_enqueue_scripts', array($this, 'enqueue_scripts'));
        add_action('wp_head', array($this, 'add_loader_html'));
        add_action('wp_ajax_save_filter_choices', array($this, 'save_filter_choices'));
        add_action('wp_ajax_nopriv_save_filter_choices', array($this, 'save_filter_choices'));
        add_action('wp_ajax_clear_filter_choices', array($this, 'clear_filter_choices'));
        add_action('wp_ajax_nopriv_clear_filter_choices', array($this, 'clear_filter_choices'));


        add_action('wp_ajax_search_location', array($this, 'search_location'));
        add_action('wp_ajax_nopriv_search_location', array($this, 'search_location'));
    }

    public function search_location(){
        $search = $_REQUEST['search'];
        $locations = $this->get_locations($search);
        echo json_encode($locations);
        die();
    }

    public function get_locations($search){
        global $wpdb;

        $posts = get_posts(array(
            'post_type'      => 'wp-data-presentation',
            'posts_per_page' => -1,
            'fields'         => 'ids',
        ));

        if (empty($posts)) {
            return [];
        }

        $locations = [];
        $unique_locations = [];
        foreach ($posts as $id) {
            $table_name   = $wpdb->prefix. 'wpdp_data_' . $id;
            $table_exists = $wpdb->get_var("SHOW TABLES LIKE '{$table_name}'") === $table_name;
            if (!$table_exists) {
                continue;
            }
            $query = "SELECT DISTINCT country, admin1, admin2, admin3, location FROM {$table_name} 
                      WHERE country LIKE '%".$search."%' 
                      OR admin1 LIKE '%".$search."%' 
                      OR admin2 LIKE '%".$search."%' 
                      OR admin3 LIKE '%".$search."%' 
                      OR location LIKE '%".$search."%'";
            $results = $wpdb->get_results($query, ARRAY_A);
            foreach ($results as $result) {
                $matched = false;
                foreach (['admin1', 'admin2', 'admin3', 'location'] as $field) {
                    if (stripos($result[$field], $search) !== false) {
                        $location_key = $result[$field];
                        if (!isset($unique_locations[$location_key])) {
                            $unique_locations[$location_key] = [
                                'country' => $result['country'],
                                'location' => $result[$field],
                                'id'=>$result['country'].'__country'.' + '.$result[$field].'__'.$field,
                            ];
                            $matched = true;
                        }
                        break;
                    }
                }
                if (!$matched && stripos($result['country'], $search) !== false) {
                    $location_key = $result['country'];
                    if (!isset($unique_locations[$location_key])) {
                        $unique_locations[$location_key] = [
                            'country' => $result['country'],
                            'location' => $result['country'],
                            'id'=>$result['country'].'__country',
                        ];
                    }
                }
            }
        }
        
        return array_values($unique_locations);
    }

    public function enqueue_scripts() {
        wp_register_script(WP_DATA_PRESENTATION_NAME . 'select2', WP_DATA_PRESENTATION_URL . 'assets/js/select2.min.js', array('jquery'), WP_DATA_PRESENTATION_VERSION, true);

        wp_register_style(WP_DATA_PRESENTATION_NAME . 'select2', WP_DATA_PRESENTATION_URL . 'assets/css/select2.min.css', [], WP_DATA_PRESENTATION_VERSION);

        wp_register_style(WP_DATA_PRESENTATION_NAME . 'public', WP_DATA_PRESENTATION_URL . 'assets/css/wp-data-presentation-public.css', [], WP_DATA_PRESENTATION_VERSION);

        wp_register_style(WP_DATA_PRESENTATION_NAME . 'jquery-ui', 'https://cdnjs.cloudflare.com/ajax/libs/jqueryui/1.13.2/themes/base/jquery-ui.min.css', [], WP_DATA_PRESENTATION_VERSION);

        wp_register_script(WP_DATA_PRESENTATION_NAME . 'public', WP_DATA_PRESENTATION_URL . 'assets/js/wp-data-presentation-public.js', array('jquery', 'jquery-ui-datepicker'), WP_DATA_PRESENTATION_VERSION, true);

        wp_localize_script(WP_DATA_PRESENTATION_NAME . 'public', 'wpdp_obj', [
            'url'      => WP_DATA_PRESENTATION_URL,
            'ajax_url' => admin_url('admin-ajax.php'),
        ]);

    }

    public function add_loader_html() {
        ?>
        <!-- Loader HTML -->
        <div id="wpdp-loader" class="wpdp-loader" style="display:none;">
            <div class="loader">
                <div class="inner"></div>
            </div>
            <h1>Loading...</h1>
        </div>

    <?php }



    public static function get_date_format($date_sample) {
        $date_formats = [
            'Y-m-d' => ['regex' => '/^\d{4}-\d{2}-\d{2}$/', 'mysql' => '%%Y-%%m-%%d'],
            'Y/m/d' => ['regex' => '/^\d{4}\/\d{2}\/\d{2}$/', 'mysql' => '%%Y/%%m/%%d'],
            'd-m-Y' => ['regex' => '/^\d{2}-\d{2}-\d{4}$/', 'mysql' => '%%d-%%m-%%Y'],
            'd/m/Y' => ['regex' => '/^\d{2}\/\d{2}\/\d{4}$/', 'mysql' => '%%d/%%m/%%Y'],
            'm-d-Y' => ['regex' => '/^\d{2}-\d{2}-\d{4}$/', 'mysql' => '%%m-%%d-%%Y'],
            'm/d/Y' => ['regex' => '/^\d{2}\/\d{2}\/\d{4}$/', 'mysql' => '%%m/%%d/%%Y'],
            'd F Y' => ['regex' => '/^\d{2} \w{3,9} \d{4}$/', 'mysql' => '%%d %%M %%Y']
        ];
    
    
        foreach ($date_formats as $php_format => $format_info) {
            if (preg_match($format_info['regex'], $date_sample)) {
                return [
                    'mysql'=>$format_info['mysql'],
                    'php'=>$php_format
                ];
            }
        }
    
        return false;
    }
    


    public static function get_filters() {
        $atts = self::get_instance()->shortcode_atts;

        $posts = get_posts(array(
            'post_type'      => 'wp-data-presentation',
            'posts_per_page' => -1,
            'fields'         => 'ids',
        ));

        if (empty($posts)) {
            return 'No data';
        }

        $arr_type = ARRAY_A;
        global $wpdb;
        $years             = [];
        $ordered_locations = [];
        foreach ($posts as $id) {
            $table_name   = $wpdb->prefix. 'wpdp_data_' . $id;
            $table_exists = $wpdb->get_var("SHOW TABLES LIKE '{$table_name}'") === $table_name;
            if (!$table_exists) {
                continue;
            }

            $whereSQL = ' WHERE 1=1 ';
            if (isset($atts['from']) && '' != $atts['from']) {
                $whereSQL .= " AND STR_TO_DATE(event_date, '%d %M %Y') >= STR_TO_DATE('{$atts['from']}', '%d %M %Y')";
            }

            if (isset($atts['to']) && '' != $atts['to']) {
                $whereSQL .= " AND STR_TO_DATE(event_date, '%d %M %Y') <= STR_TO_DATE('{$atts['to']}', '%d %M %Y')";
            }

            $db_years = $wpdb->get_col("SELECT DISTINCT event_date FROM {$table_name} {$whereSQL}");

            if (!empty($db_years)) {
                $years = array_merge($years, $db_years);
            }

            $db_locations = $wpdb->get_results("SELECT DISTINCT country,admin1,admin2,admin3,location FROM {$table_name}", ARRAY_A);

            foreach ($db_locations as $location) {
                $country  = $location['country'].'__country';
                $admin1   = $location['admin1'].'__admin1';
                $admin2   = $location['admin2'].'__admin2';
                $admin3   = $location['admin3'].'__admin3';
                $location = $location['location'].'__location';

                if (!empty($country)) {
                    $locations[$country] = $locations[$country] ?? [];
                    $currentLevel       = &$locations[$country];

                    if (!empty($admin1) && $country != $admin1) {
                        $currentLevel[$admin1] = $currentLevel[$admin1] ?? [];
                        $currentLevel          = &$currentLevel[$admin1];
                    }

                    if (!empty($admin2) && $admin1 != $admin2) {
                        $currentLevel[$admin2] = $currentLevel[$admin2] ?? [];
                        $currentLevel          = &$currentLevel[$admin2];
                    }

                    if (!empty($admin3) && $admin2 != $admin3) {
                        $currentLevel[$admin3] = $currentLevel[$admin3] ?? [];
                        $currentLevel          = &$currentLevel[$admin3];
                    }

                    if (!empty($location)) {
                        $currentLevel[] = $location;
                    }
                }

                $ordered_locations = $locations;

            }
        }

        usort($years, function ($a, $b) {
            return strtotime($a) - strtotime($b);
        });

        $mapping = get_field('incident_type_filter','option');
        $inc_type = [];
        foreach($mapping as $k1 => $value){
            foreach($value as $k2 => $value2){
                foreach($value2 as $k3 => $value3){
                    if($value3['hierarchial'] !== 'Level 1'){
                        continue;
                    }
                    $incident_type = $value3['type'].'_type';


                    if(!empty($value3[$incident_type])){
                        $inc_type[$value3['text']] = $value3[$incident_type];
                    }
                }
            }
        }

        $ordered_locations = self::sort_locations_array($locations);

        $filters = array(
            'types'     => $inc_type,
            'years'     => $years,
            'locations' => $ordered_locations,
        );

        return $filters;
    }


    private static function sort_locations_array($array) {
        ksort($array);
        foreach ($array as &$value) {
            if (is_array($value)) {
                if (isset($value[0]) && is_string($value[0])) {
                    sort($value);
                } else {
                    $value = self::sort_locations_array($value);
                }
            }
        }
        return $array;
    }

    function printArrayAsList($locations, $level = 0, $parent_key = false) {
        echo '<ul>';
        foreach ($locations as $key => $value) {
            if (!is_array($value) && intval($value) === 0) {
                continue;
            }
    
            if (is_array($value) && empty($value)) {
                continue;
            }
    
            if (is_array($value)) {
                $key_val = explode('__', $key);
                $input_val = $key;
                if ($parent_key !== false) {
                    $input_val = $parent_key . ' + ' . $key;
                }
                $checkbox_name = 'wpdp_'.sanitize_title($key_val[0]);
                $is_checked = $this->get_session_value($checkbox_name) === $input_val ? 'checked' : '';
    
                echo '<li class="expandable">';
                echo '<input type="checkbox" class="wpdp_filter_checkbox wpdp_location" name="' . $checkbox_name . '" value="' . $input_val . '" ' . $is_checked . '>';
                echo '<div class="exp_click"><span for="' . $key . '">' . $key_val[0] . '</span>';
                echo '<span class="dashicons dashicons-arrow-up-alt2 arrow"></span></div>';
                $this->printArrayAsList($value, $level + 1, $input_val);
            } else {
                $checkbox_name = 'wpdp_'.sanitize_title($value);
                $is_checked = $this->get_session_value($checkbox_name) === $value ? 'checked' : '';
                echo '<li>';
                echo '<input type="checkbox" class="wpdp_filter_checkbox wpdp_location" name="' . $checkbox_name . '" value="' . $value . '" ' . $is_checked . '>';
                echo $value;
            }
    
            echo '</li>';
        }
        echo '</ul>';
    }
    

    public function show_shortcode($atts) {
        $atts = shortcode_atts(array(
            'type' => '',
            'from' => '',
            'to'   => '',
        ), $atts);

        $this->shortcode_atts = $atts;

        ob_start();
        wp_enqueue_script(WP_DATA_PRESENTATION_NAME . 'select2');
        wp_enqueue_style(WP_DATA_PRESENTATION_NAME . 'select2');
        wp_enqueue_style(WP_DATA_PRESENTATION_NAME . 'public');
        wp_enqueue_script(WP_DATA_PRESENTATION_NAME . 'public');
        wp_enqueue_style(WP_DATA_PRESENTATION_NAME . 'jquery-ui');
        wp_enqueue_style('dashicons');

        ?>

        <div class="wpdp">
            <?php
        $filters = self::get_filters();
        $this->get_html_filter($filters, $atts);
        if (isset($atts['type']) && 'table' === $atts['type']) {
            WPDP_Tables::shortcode_output();
        } elseif (isset($atts['type']) && 'graph' === $atts['type']) {
            WPDP_Graphs::shortcode_output();
        } elseif (isset($atts['type']) && 'map' === $atts['type']) {
            WPDP_Maps::shortcode_output($atts);
        } else {
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

    function get_from_date_value($filters, $atts) {
        if (isset($this->shortcode_atts['from']) && '' != $this->shortcode_atts['from']) {
            return $this->shortcode_atts['from'];
        } else {
            // if ('map' === $atts['type']) {
                return date('d F Y', strtotime(end($filters['years']) . ' -1 year'));
            // } else {
            //     echo date('d F Y', strtotime($filters['years'][0]));
            // }
        }
    }

    function get_to_date_value($filters, $atts) {
        if (isset($this->shortcode_atts['from']) && '' != $this->shortcode_atts['from']) {
            return $this->shortcode_atts['from'];
        } else {
            // if ('map' === $atts['type']) {
            //     echo date('d F Y');
            // } else {
                return date('d F Y', strtotime(end($filters['years'])));
            // }
        }
    }

    private function renderFilters($filters, $hierarchy = 'Level 1', $not_incident = false) {
        echo '<ul class="'.($hierarchy === 'Level 1' ? 'first_one' : '').'">';
        $class = 'wpdp_incident_type';
        if(!empty($filters)){
            foreach ($filters as $filter) {
                if ($not_incident) {
                    $value = [];
                    $types = ['disorder_type', 'event_type', 'sub_event_type'];
                    foreach ($types as $one_type) {
                        if (!empty($filter[$one_type])) {
                            $value = array_merge($value, $filter[$one_type]);
                        }
                    }
                    $class = 'wpdp_actors';
                    if($not_incident === 'fat'){
                        $class = 'wpdp_fat';
                    }
                }else{
                    $type = $filter['type'] ?? '';
                    $value = $filter[$type];
                }
                if ($filter['hierarchial'] === $hierarchy) {
                    echo '<li class="expandable">';
                    echo '<input class="wpdp_filter_checkbox '.$class.'" type="checkbox" value="' . implode('+', $value) . '">';
                    echo '<div class="exp_click">';
                    echo '<span>' . htmlspecialchars($filter['hierarchial']) . '</span>';
                    echo '<span class="dashicons arrow dashicons-arrow-down-alt2"></span>';
                    echo '</div>';
                    if ($hierarchy !== 'Level 4') {
                        $nextHierarchy = 'Level ' . (intval(substr($hierarchy, -1)) + 1);
                        $this->renderFilters($filters, $nextHierarchy, $not_incident);
                    }
                    echo '</li>';
                }
            }
        }
        echo '</ul>';
    }

    public function generateCheckboxes($arr, $actors = false) {
        echo '<ul>';
        foreach ($arr as $section) {
            $this->renderFilters($section['filter'], 'Level 1', $actors);
        }
        echo '</ul>';
    }


    function get_html_filter($filters, $atts) {
        ?>
        <div class="filter_data" style="display:none;">
            <a class="filter" href=""><span class="fas fa-sliders-h"></span></a>
            <div class="con">
                <form id="filter_form" action="" style="margin-top:15px;">


                    <div class="grp inident_type">

                        <div class="title">
                            INCIDENT TYPE <span class="dashicons dashicons-arrow-down-alt2"></span>
                        </div>
                        <div class="content">
                            <?php 
                                $filter = get_field('incident_type_filter','option');
                                foreach($filter as $filt){
                                    $hierarchy = $this->buildHierarchy($filt['filter']);
                                    echo $this->generateHierarchy($hierarchy);
                                }
                            ?>

                        </div>
                    </div>


                    <div class="grp actors">

                        <div class="title">
                            ACTORS <span class="dashicons dashicons-arrow-down-alt2"></span>
                        </div>
                        <div class="content">
                            <?php 
                                $filter = get_field('actor_filter','option');
                                foreach($filter as $filt){
                                    $hierarchy = $this->buildHierarchy($filt['filter']);
                                    echo $this->generateHierarchy($hierarchy,'actors');
                                }
                            ?>

                        </div>
                    </div>



                    <div class="grp actors">

                        <div class="title">
                            FATALITIES <span class="dashicons dashicons-arrow-down-alt2"></span>
                        </div>
                        <div class="content">
                            <?php 
                                $filter = get_field('fatalities_filter','option');
                                foreach($filter as $filt){
                                    $hierarchy = $this->buildHierarchy($filt['filter']);
                                    echo $this->generateHierarchy($hierarchy,'fat');
                                }
                            ?>

                        </div>
                    </div>

                    <div class="grp ">
                        <div class="title">
                            LOCATION <span class="dashicons dashicons-arrow-down-alt2"></span>
                        </div>
                        <div class="content">
                            <div>
                                <select name="wpdp_search_location" id="wpdp_search_location" multiple="multiple">
                                <?php
                                $selected_locations = $this->get_session_value('wpdp_search_location', []);

                                if (!empty($selected_locations)) {
                                    foreach ($selected_locations as $location) {
                                        if(strpos($location,'+') !== false){
                                            $text = explode('+',$location);
                                            $first = explode('__',$text[1]);
                                            $second = explode('__',$text[0]);
                                            $final_text = $first[0].' ('.$second[0].')';
                                        }else{
                                            $text = explode('__',$location);
                                            $final_text = $text[0];
                                        }
                                        echo '<option value="' . esc_attr($location) . '" selected>' . esc_html($final_text) . '</option>';
                                    }
                                }
                                ?>
                                </select>
                            </div>

                          <br>
                            <?php $this->printArrayAsList($filters['locations']);?>
                        </div>
                    </div>

                    <div class="grp">

                        <div class="title">
                            DATE RANGE <span class="dashicons dashicons-arrow-down-alt2"></span>
                        </div>
                        <div class="content <?php echo (isset($atts['type']) && 'map' === $atts['type'] ? 'filter_maps' : ''); ?>">
                            <div class="dates">
                                <label for="wpdp_from">FROM</label>
                                <input value="<?php 
                                    if ('map' === $atts['type'] && empty($this->get_session_value('wpdp_from'))) {
                                        echo date('d F Y', strtotime('-30 days'));
                                    } else {
                                        echo $this->get_session_value('wpdp_from', $this->get_from_date_value($filters, $atts));
                                    }
                                ?>" type="text" name="wpdp_from" id="wpdp_from">
                            </div>
                            <div class="dates">
                                <label style="margin-right: 23px;" for="wpdp_to">TO</label>
                                <input value="<?php 
                                    if ('map' === $atts['type'] && empty($this->get_session_value('wpdp_to'))) {
                                        echo date('d F Y');
                                    } else {
                                        echo $this->get_session_value('wpdp_to', $this->get_to_date_value($filters, $atts));
                                    }
                                ?>" type="text" name="wpdp_to" id="wpdp_to">
                            </div>
                            <?php if ('graph' === $atts['type'] || '' == $atts['type']) {?>
                            <div class="dates">
                                <label for="wpdp_date_timeframe">Timeframe <br>(for graphs)</label>
                                <select name="wpdp_date_timeframe" id="wpdp_date_timeframe">
                                    <option value="">Choose Timeframe</option>
                                    <option value="yearly" <?php selected($this->get_session_value('wpdp_date_timeframe'), 'yearly'); ?>>Yearly</option>
                                    <option value="monthly" <?php selected($this->get_session_value('wpdp_date_timeframe'), 'monthly'); ?>>Monthly</option>
                                    <option value="weekly" <?php selected($this->get_session_value('wpdp_date_timeframe'), 'weekly'); ?>>Weekly</option>
                                    <option value="daily" <?php selected($this->get_session_value('wpdp_date_timeframe'), 'daily'); ?>>Daily</option>
                                </select>
                            </div>
                            <?php } ?>
                        </div>

                    </div>


                    <?php if ('graph' === $atts['type'] || '' == $atts['type']) {?>
                    <!-- <div class="grp ">

                        <div class="title">
                            COUNT TYPE <span class="dashicons dashicons-arrow-down-alt2"></span>
                        </div>
                        <div class="content">
                            <select name="wpdp_type_selector" id="wpdp_type_selector">
                                <option value="fatalities" <?php selected($this->get_session_value('wpdp_type_selector'), 'fatalities'); ?>>Fatalities</option>
                                <option value="incident_count" <?php selected($this->get_session_value('wpdp_type_selector'), 'incident_count'); ?>>Incident Count</option>
                            </select>
                        </div>
                    </div> -->
                    <?php }?>

                    <div class="no_data" style="display:none;">No data found, please adjust filters</div>
                    <input type="submit" value="Apply Filters">
                    <div class="wpdp_clear"><input type="reset" value="Clear Filters"></div>
                </form>
            </div>
        </div>
    <?php

    }

    function get_value_from_incident_type($array){
        if(empty($array)){
            return [];
        }
        $value = [];
        $incidents = get_field('incident_type_filter','option');
        foreach($incidents as $incident){
            foreach($incident['filter'] as $filter){
                $type = $filter['type'];
                $type_value = $filter[$type];

                foreach($array as $ar_value){
                    if($ar_value === $filter['text']){
                        $value = array_merge($value,$type_value);
                    }
                }
            }

        }

        return $value;
    }

    function generateHierarchy($array, $not_incident = false, $first = 1) {
        $html = '<ul '.($first == 1 ? 'class="first_one"' : '').'>';
        $class = 'wpdp_incident_type';
        foreach ($array as $item) { 
            if ($not_incident) {
                $value = $this->get_value_from_incident_type($item['mapping_to_incident']);
                $class = 'wpdp_actors';
                if($not_incident === 'fat'){
                    $class = 'wpdp_fat';
                }
            } else {
                $type = $item['type'] ?? '';
                $value = $item[$type];
            }
    
            $checkbox_name = sanitize_title($item['text']);
            $checkbox_value = implode('+', $value);
            $is_checked = $this->get_session_value($checkbox_name) === $checkbox_value ? 'checked' : '';
    
            $html .= '<li class="expandable">';
            $html .= '<input class="wpdp_filter_checkbox '.$class.'" type="checkbox" name="'.$checkbox_name.'" value="'.$checkbox_value.'" '.$is_checked.'>';
            $html .= '<div class="exp_click"><span>' . $item['text'] . '</span><span class="dashicons arrow dashicons-arrow-down-alt2"></span></div>';
            
            if (isset($item['children']) && !empty($item['children'])) {
                $html .= $this->generateHierarchy($item['children'], $not_incident, 0);
            }
            
            $html .= '</li>';
        }
        $html .= '</ul>';
        return $html;
    }
    
    
    function buildHierarchy($flatArray) {
        $hierarchy = [];
        $levels = [];
        if(is_array($flatArray) && !empty($flatArray)){
            foreach ($flatArray as $item) {
                $level = substr($item['hierarchial'], -1);
                $item['children'] = [];
        
                if ($level == 1) {
                    $hierarchy[] = $item;
                    $levels[1] = &$hierarchy[count($hierarchy) - 1];
                } else {
                    $levels[$level - 1]['children'][] = $item;
                    $levels[$level] = &$levels[$level - 1]['children'][count($levels[$level - 1]['children']) - 1];
                }
            }
        }
    
        return $hierarchy;
    }

    private function get_session_value($key, $default = '') {
        return isset($_SESSION['wpdp_'.$key]) ? $_SESSION['wpdp_'.$key] : $default;
    }

    public function save_filter_choices() {
        if (!isset($_POST['filter_data'])) {
            wp_send_json_error('No filter data received');
        }

        foreach ($_SESSION as $key => $value) {
            if (strpos($key, 'wpdp_') === 0) {
                unset($_SESSION[$key]);
            }
        }


        $filter_data = $_POST['filter_data'];
        foreach ($filter_data as $key => $value) {
            $_SESSION['wpdp_'.$key] = $value;
        }

        wp_send_json_success('Filter choices saved');
    }

}

WPDP_Shortcode::get_instance();
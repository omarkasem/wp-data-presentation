<?php 
/**
 * View class
 *
 * @since 1.0.0
 */

final class WPDP_Metabox {

    /**
     * Instance of this class.
     *
     * @since 1.0.0
     *
     * @var WP_VST_Shortcode_View
     */
    protected static $_instance = null;

    /**
     * Get instance of the class
     *
     * @since 1.0.0
     *
     * @return VST_Shortcode_View
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

    public function _add_hooks(){
        
        add_filter('acf/settings/url', array($this,'my_acf_settings_url'));
        add_filter('acf/settings/show_admin', array($this,'show_admin'));
        add_filter('acf/render_field/key=field_657e4ec5e8971', array($this,'shortcode_box'), 20, 1);
        add_action('admin_enqueue_scripts', array($this, 'enqueue_scripts'));

        add_action('save_post',array($this,'save_presentation'));

        add_filter('acf/load_field/name=countries_to_show', array($this,'countries_field'));
        add_action('acf/save_post', array($this,'save_option_page'), 20);
        
        add_action( 'ok_wpdp_remove_countries_records', array($this,'remove_countries_records'), 10, 3 );
        
        // Mapping
        add_filter('acf/load_field/name=incident_type_filter', array($this,'load_incidents_choices'));

        add_filter('acf/load_value/name=incident_type_filter', array($this,'set_default_repeater_values'), 10, 3);


        add_filter('acf/load_field/key=field_667ed6bc35cf2', array($this,'empty_mapping_categories'));

        if(isset($_GET['test'])){
            var_dump(get_option('test661'));exit;
        }

    }


    function empty_mapping_categories($field){
        $mapping = get_field('incident_type_filter','option');
        if(empty($mapping)){
            return $field;
        }

        $db_columns = array(
            'disorder_type',
            'event_type',
            'sub_event_type'
        );
        $types = [];
        foreach($db_columns as $column){
            $types = array_merge($types,$this->get_db_column($column));
        }

        foreach($mapping as $k1 => $value){
            foreach($value as $k2 => $value2){
                foreach($value2 as $k3 => $value3){

                    $incident_type = $value3['type'];

                    if(!empty($value3[$incident_type])){
                        $result = $this->find_element($types, $value3['text'],true);
                        if($result !== false){
                            unset($types[$result]);
                        }
                    }
                }
            }
        }
        
        if(empty($types)){
            return $field;
        }

        $message = '<div id="empty_cats">';
        $message .= '
            <div>
            <h2>Disorder Types</h2>
            <ul>
        ';
        foreach($types as $type){
            $text = explode('__', $type);
            if($text[1] === 'disorder_type'){
                $message .= '<li>'.$text[0].'</li>';
            }
        }
        $message .= '</div></ul>';

        $message .= '
            <div>
            <h2>Event Types</h2>
            <ul>
        ';
        foreach($types as $type){
            $text = explode('__', $type);
            if($text[1] === 'event_type'){
                $message .= '<li>'.$text[0].'</li>';
            }
        }
        $message .= '</div></ul>';

        $message .= '
            <div>
            <h2>Sub Event Types</h2>
            <ul>
        ';
        foreach($types as $type){
            $text = explode('__', $type);
            if($text[1] === 'sub_event_type'){
                $message .= '<li>'.$text[0].'</li>';
            }
        }
        $message .= '</div></ul>';


        $message .= '</div>';
        
        $field['message'] = $message;
        return $field;
    }


    function set_default_repeater_values($value, $post_id, $field) {
        if(!empty($value)){
            return $value;
        }

        $filePath = WP_DATA_PRESENTATION_PATH . '/lib/acf-json/default_incident_types.json';

        $jsonContent = file_get_contents($filePath);

        return json_decode($jsonContent, true);
    }



    private function get_db_column($column_name){
        $posts = get_posts(array(
            'post_type'=>'wp-data-presentation',
            'posts_per_page'=>-1,
            'fields'=>'ids'
        ));

        if(empty($posts)){
            return [];
        }

        global $wpdb;
        $column = [];
        foreach($posts as $id){
            $table_name = 'wpdp_data_'.$id;
            $table_exists = $wpdb->get_var("SHOW TABLES LIKE '{$table_name}'") === $table_name;
            if(!$table_exists){
                continue;
            }

            $db_column = $wpdb->get_col("SELECT DISTINCT {$column_name} FROM {$table_name}");
            if(!empty($db_column)){
                if($column_name !== 'country'){
                    $db_column = array_map(function($value) use ($column_name) {
                        return $value . '__' . $column_name;
                    }, $db_column);
                }
                $column = array_merge($column, $db_column);
            }


        }

        return $column;
    }

    function load_choices($column,$sub_field) {
        $types= $this->get_db_column($column);
        
        // Initialize choices array
        $sub_field['choices'] = array();
        
        // Populate choices
        if (!empty($types)) {
            foreach ($types as $type) {
                $val_type = explode('_',$type);
                $sub_field['choices'][$type] = $val_type[0];
            }
        }
        
        return $sub_field;
    }

    function load_incidents_choices($field) {
        if (!empty($field['sub_fields'])) {
            // Iterate through each sub-field in the main repeater
            foreach ($field['sub_fields'] as &$sub_field) {
                // If the sub-field is a repeater itself, iterate its sub-fields
                if (isset($sub_field['sub_fields']) && is_array($sub_field['sub_fields'])) {
                    
                    foreach ($sub_field['sub_fields'] as &$inner_sub_field) {
                        // Check each inner sub-field type and load choices accordingly
                        if (isset($inner_sub_field['name']) && $inner_sub_field['name'] == 'disorder_type') {
                            $inner_sub_field = $this->load_choices('disorder_type',$inner_sub_field);
                        }elseif (isset($inner_sub_field['name']) && $inner_sub_field['name'] == 'event_type') {
                            $inner_sub_field = $this->load_choices('event_type',$inner_sub_field);
                        }elseif (isset($inner_sub_field['name']) && $inner_sub_field['name'] == 'sub_event_type') {
                            $inner_sub_field = $this->load_choices('sub_event_type',$inner_sub_field);
                        }
                    }
                }
            }
        }

        // Return the field
        return $field;
    }
        
    
    

    function save_option_page( $post_id ) {
        // Check if it's not an autosave
        if( defined('DOING_AUTOSAVE') && DOING_AUTOSAVE ) {
            return;
        }

        // Check if it's our specific option field
        if( isset($_POST['acf']['field_66747fef0e941']) ) { 
            $new_value = $_POST['acf']['field_66747fef0e941'];
            $this->remove_other_countires_records_cron_job($new_value);
        }
    }


    function remove_countries_records( $table_name, $countries, $post_id ) {
        global $wpdb;
        $countries_placeholders = implode(', ', array_fill(0, count($countries), '%s'));
        $sql = $wpdb->prepare("DELETE FROM {$table_name} WHERE country NOT IN ($countries_placeholders)", ...$countries);
        $result = $wpdb->query($sql);

        if ($result === false) {
            $error = $wpdb->last_error;
            error_log("Database error: " . $error); // Log the error in WordPress error log
            update_post_meta($post_id,'wpdp_countries_updated_error',$error);
        } else {
            // Operation was successful
            update_post_meta($post_id,'wpdp_countries_updated',true);
        }
        
    }
    

    public function remove_other_countires_records_cron_job($countries){

        if(empty($countries)){
            return;
        }

        $posts = get_posts(array(
            'post_type'=>'wp-data-presentation',
            'posts_per_page'=>-1,
            'fields'=>'ids'
        ));

        if(empty($posts)){
            return [];
        }

        global $wpdb;
        foreach($posts as $id){
            $table_name = 'wpdp_data_'.$id;
            $table_exists = $wpdb->get_var("SHOW TABLES LIKE '{$table_name}'") === $table_name;
            if(!$table_exists){
                continue;
            }

            wp_schedule_single_event( time(), 'ok_wpdp_remove_countries_records', array( $table_name, $countries, $id) );

        }
    }
    
    public function countries_field( $field ) {
        $countries = $this->get_db_column('country');
        
        // Initialize choices array
        $field['choices'] = array();
        
        // Populate choices
        if (!empty($countries)) {
            foreach ($countries as $country) {
                $field['choices'][ $country ] = $country;
            }
        }
        
        // Return the field
        return $field;
    }
    


    public function save_presentation($post_id){
        if(get_post_type($post_id) !== 'wp-data-presentation'){
            return;
        }

        if(get_post_status($post_id) !== 'publish'){
            return;
        }
        
        $table_name = 'wpdp_data_'.$post_id;
        if(get_field('override_csv_file') === true){
            if(get_field('import_file') === 'Upload'){
                $file_path = get_attached_file(get_field('upload_excel_file'));
            }

            $import =  new WPDP_Db_Table($table_name,$file_path);
            if (!$import->import_csv()) {
                var_dump('Error in importing');exit;
            }

            delete_post_meta($post_id,'wpdp_countries_updated');

        }

        // Auto select mapping.
        $mapping = get_field('incident_type_filter','option');
        if(empty($mapping)){
            return;
        }

        $db_columns = array(
            'disorder_type',
            'event_type',
            'sub_event_type'
        );
        $types = [];
        foreach($db_columns as $column){
            $types = array_merge($types,$this->get_db_column($column));
        }

        $changed = false;
        foreach($mapping as $k1 => $value){
            foreach($value as $k2 => $value2){
                foreach($value2 as $k3 => $value3){
                    $incident_type = $value3['type'];
                    if(is_array($value3[$incident_type]) && empty(array_filter($value3[$incident_type]))){
                        $result = $this->find_element($types, $value3['text']);

                        if($result === false){
                            return;
                        }

                        $mapping[$k1][$k2][$k3]['type'] = $this->find_type($result);
                        $mapping[$k1][$k2][$k3][$incident_type] = array($result);
                        $changed = true;
                    }
                }
            }
        }

        if($changed === true){
            update_field('incident_type_filter',$mapping,'option');
        }

    }

    function find_type($cat_value) {
        foreach (['sub_event_type', 'disorder_type', 'event_type'] as $type) {
            if (strpos($cat_value, $type) !== false) {
                return $type;
            }
        }
    }

    function find_element($array, $text, $return_key = false) {
        foreach ($array as $key => $element) {
            if (strpos(strtolower($element), strtolower($text)) !== false) {
                return ($return_key ? $key : $element);
            }
        }
        return null;
    }
    

    public function shortcode_box($field){
        echo '<div class="wpdp_shortcode">
        <input type="text" disabled value=" [WP_DATA_PRESENTATION]"> 
        <button class="button button-secondary wpdp_copy">Copy</button>
        </div>';
    }
    

    public function enqueue_scripts() {
        wp_enqueue_script(WP_DATA_PRESENTATION_NAME, WP_DATA_PRESENTATION_URL . 'assets/js/wp-data-presentation-admin.js', array('jquery'), WP_DATA_PRESENTATION_VERSION, false);
        wp_enqueue_style(WP_DATA_PRESENTATION_NAME, WP_DATA_PRESENTATION_URL . 'assets/css/wp-data-presentation-admin.css', false, WP_DATA_PRESENTATION_VERSION, false);

        wp_localize_script(WP_DATA_PRESENTATION_NAME, 'wpdp_obj', array( 'ajax_url' => admin_url('admin-ajax.php')));

    }



    public function my_acf_settings_url( $url ) {
        return WP_DATA_PRESENTATION_ACF_URL;
    }

    
    public function show_admin( $show_admin ) {
        return WP_DATA_PRESENTATION_ACF_SHOW;
    }

    

}

WPDP_Metabox::get_instance();
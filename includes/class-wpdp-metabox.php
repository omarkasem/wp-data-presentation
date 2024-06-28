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
        add_filter('acf/load_field/name=incident_type_filter', array($this,'disorder_type'));
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

    function load_choices($sub_field) {
        $db_columns = array(
            'disorder_type',
            'event_type',
            'sub_event_type'
        );
        $types = [];
        foreach($db_columns as $column){
            $types= array_merge($types,$this->get_db_column($column));
        }
        
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

    function disorder_type($field) {
        if (!empty($field['sub_fields'])) {
            // Iterate through each sub-field in the main repeater
            foreach ($field['sub_fields'] as &$sub_field) {
                // If the sub-field is a repeater itself, iterate its sub-fields
                if (isset($sub_field['sub_fields']) && is_array($sub_field['sub_fields'])) {
                    foreach ($sub_field['sub_fields'] as &$inner_sub_field) {
                        // Check each inner sub-field type and load choices accordingly
                        if (isset($inner_sub_field['name']) && $inner_sub_field['name'] == 'database_db_column') {
                            $inner_sub_field = $this->load_choices($inner_sub_field);
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
            }elseif(get_field('import_file') === 'URL'){
                $file_url = get_field('excel_file_url');
                $file_path = '';
                // Download file..
            }

            $import =  new WPDP_Db_Table($table_name,$file_path);
            if (!$import->import_csv()) {
                var_dump('Error in importing');exit;
            }

            delete_post_meta($post_id,'wpdp_countries_updated');

        }

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
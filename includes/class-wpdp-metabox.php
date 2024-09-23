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
        
        $this->de_acf_free();
        add_filter('acf/settings/url', array($this,'my_acf_settings_url'));
        add_filter('acf/settings/show_admin', array($this,'show_admin'));
        add_filter('acf/render_field/key=field_657e4ec5e8971', array($this,'shortcode_box'), 20, 1);
        add_filter('acf/render_field/key=field_66ad383f1d6af', array($this,'last_updated_field'), 20, 1);
        add_action('admin_enqueue_scripts', array($this, 'enqueue_scripts'));

        add_action('save_post',array($this,'save_presentation'),1);

        add_filter('acf/load_field/name=countries_to_show', array($this,'countries_field'));
        add_action('acf/save_post', array($this,'save_option_page'), 20);
        
        add_action( 'ok_wpdp_remove_countries_records', array($this,'remove_countries_records'), 10, 3 );
        
        // Mapping
        add_filter('acf/load_field/name=fatalities_filter', array($this,'load_fat_choices'));
        // add_filter('acf/load_field/name=actor_filter', array($this,'load_actor_choices'));
        add_filter('acf/load_field/name=incident_type_filter', array($this,'load_incidents_choices'));

        add_filter('acf/load_value/name=incident_type_filter', array($this,'set_default_repeater_values'), 10, 3);


        add_filter('acf/load_field/key=field_667ed6bc35cf2', array($this,'empty_mapping_categories'));

        // Add the cron job hook
        add_action('wpdp_daily_acled_update', array($this, 'update_acled_presentations'));

        // Schedule the cron job if it's not already scheduled
        if (!wp_next_scheduled('wpdp_daily_acled_update')) {
            wp_schedule_event(time(), 'daily', 'wpdp_daily_acled_update');
        }

        // Add new hook for file upload
        add_filter('upload_mimes', array($this, 'add_custom_mime_types'));
      



    }

    function de_acf_free(){
        if ( ! function_exists( 'is_plugin_active' ) ) {
            include_once ABSPATH . 'wp-admin/includes/plugin.php';
        }

        // Check if the ACF free plugin is activated
        if ( is_plugin_active( 'advanced-custom-fields/acf.php' ) ) {
            // Free plugin activated
            // Free plugin activated, show notice
            add_action( 'admin_notices', function () {
                ?>
                <div class="updated" style="border-left: 4px solid #ffba00;">
                    <p>The ACF plugin cannot be activated at the same time as Third-Party Product and has been deactivated. Please keep ACF installed to allow you to use ACF functionality.</p>
                </div>
                <?php
            }, 99 );

            // Disable ACF free plugin
            deactivate_plugins( 'advanced-custom-fields/acf.php' );
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
            $table_name = $wpdb->prefix. 'wpdp_data_'.$id;
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

    function load_incident_to_actors($column,$sub_field) {
        
        // Initialize choices array
        $sub_field['choices'] = array();
        
        $incidents = get_field('incident_type_filter','option');
        foreach($incidents as $incident){
            foreach($incident['filter'] as $filter){
                if(strpos($filter['hierarchial'],'1') !== false){
                    $field = '[1]';
                }elseif(strpos($filter['hierarchial'],'2') !== false){
                    $field = '[2]';
                }elseif(strpos($filter['hierarchial'],'3') !== false){
                    $field = '[3]';
                }else{
                    $field = '[4]';
                }
                $sub_field['choices'][$filter['text']] = $filter['text'].' - '.$field;
            }
        }
        
        return $sub_field;
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

    function load_fat_choices($field) {
        if (!empty($field['sub_fields'])) {
            // Iterate through each sub-field in the main repeater
            foreach ($field['sub_fields'] as &$sub_field) {
                // If the sub-field is a repeater itself, iterate its sub-fields
                if (isset($sub_field['sub_fields']) && is_array($sub_field['sub_fields'])) {
                    
                    foreach ($sub_field['sub_fields'] as &$inner_sub_field) {
                        // Check each inner sub-field type and load choices accordingly
                        if (isset($inner_sub_field['name']) && $inner_sub_field['name'] == 'mapping_to_incident') {
                            $inner_sub_field = $this->load_incident_to_actors('mapping_to_incident',$inner_sub_field);
                        }
                    }
                }
            }
        }

        // Return the field
        return $field;
    }
    
    function load_actor_choices($field) {
        if (!empty($field['sub_fields'])) {
            // Iterate through each sub-field in the main repeater
            foreach ($field['sub_fields'] as &$sub_field) {
                // If the sub-field is a repeater itself, iterate its sub-fields
                if (isset($sub_field['sub_fields']) && is_array($sub_field['sub_fields'])) {
                    
                    foreach ($sub_field['sub_fields'] as &$inner_sub_field) {
                        // Check each inner sub-field type and load choices accordingly
                        if (isset($inner_sub_field['name']) && $inner_sub_field['name'] == 'mapping_to_incident') {
                            $inner_sub_field = $this->load_incident_to_actors('mapping_to_incident',$inner_sub_field);
                        }
                    }
                }
            }
        }

        // Return the field
        return $field;
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
            $table_name = $wpdb->prefix. 'wpdp_data_'.$id;
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
    
    public function create_data_table($post_id, $use_posted_data = true){
        global $wpdb;
        $table_name = $wpdb->prefix. 'wpdp_data_'.$post_id;

        if($use_posted_data){
            $import_file = $_POST['acf']['field_657aa840cb9c5'];
            $acled_url = $_POST['acf']['field_66a2ceaad7f51'];
            $excel_file = $_POST['acf']['field_657aa818cb9c4'];
        }else{
            $import_file = get_field('import_file',$post_id);
            $acled_url = get_field('acled_url',$post_id);
            $excel_file = get_field('upload_excel_file',$post_id);
        }

        if($import_file === 'Upload'){
            $file_path = get_attached_file($excel_file);
        }else{
            $url = $acled_url;
            $file_path = download_url($url);
            if (is_wp_error($file_path)) {
                $error_message = $file_path->get_error_message();
                error_log($error_message);
                wp_die("Error downloading file: $error_message");
            }
        }

        $import =  new WPDP_Db_Table($table_name,$file_path);
        if (!$import->import_csv()) {
            error_log('Error in importing');
            var_dump('Error in importing');exit;
        }

        if($import_file !== 'Upload'){
            $attachment_id = get_post_meta($post_id, 'wpdp_last_file_attach_id', true);
            if ($attachment_id) {
                $old_file_path = get_attached_file($attachment_id);
                if ($old_file_path && file_exists($old_file_path)) {
                    unlink($old_file_path);
                }
                wp_delete_attachment($attachment_id, true);
            }
            
            // Upload file to media library
            $attach_id = $this->download_and_upload_csv($file_path, $post_id);

            update_post_meta($post_id,'wpdp_last_file_attach_id',$attach_id);
        }

        delete_post_meta($post_id,'wpdp_countries_updated');
        update_post_meta($post_id,'wpdp_last_updated_date',time());
        
    }

    public function download_and_upload_csv($temp_file, $post_id) {
    
        // Prepare file data for upload
        $post_title = get_the_title($post_id);
        $file_name = sanitize_file_name($post_title . '.csv');
        $file_array = array(
            'name'     => $file_name,
            'tmp_name' => $temp_file
        );
    
        // Set upload overrides
        $overrides = array(
            'test_form' => false,
            'test_size' => true,
        );
    
        // Upload the file to the media library
        $time = current_time('mysql');
        $file = wp_handle_sideload($file_array, $overrides, $time);
    
        if (isset($file['error'])) {
            @unlink($temp_file);
            return false;
        }
    
        // Prepare attachment data
        $attachment = array(
            'post_mime_type' => $file['type'],
            'post_title'     => preg_replace('/\.[^.]+$/', '', $file_name),
            'post_content'   => '',
            'post_status'    => 'inherit'
        );
    
        // Insert attachment into the media library
        $attach_id = wp_insert_attachment($attachment, $file['file']);
    
        // Generate metadata for the attachment
        $attach_data = wp_generate_attachment_metadata($attach_id, $file['file']);
        wp_update_attachment_metadata($attach_id, $attach_data);

        update_post_meta($post_id,'wpdp_last_file_url',$file['url']);
        return $attach_id;
    }
    


    public function save_presentation($post_id){
        global $wpdb;
        if(get_post_type($post_id) !== 'wp-data-presentation'){
            return;
        }

        if(get_post_status($post_id) !== 'publish'){
            return;
        }
        
        
        if($_POST['acf']['field_657aa840cb9c5'] === 'Acled URL'){
            $old_value = get_field('acled_url');
            $new_value = $_POST['acf']['field_66a2ceaad7f51'];
        }else{
            $old_value = (int)get_field('upload_excel_file');
            $new_value = (int)$_POST['acf']['field_657aa818cb9c4'];
        } 

        if($old_value === $new_value){
            return;
        }

        $this->create_data_table($post_id);

        $this->auto_select_mapping();

    }

    function auto_select_mapping(){
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
    

    public function last_updated_field($field){
        $post_id = get_the_ID();
        if(get_field('import_file',$post_id) == '' || get_field('import_file',$post_id) === 'Upload'){
            return;
        }
        echo '
            <div class="wpdp_last_updated">
                <h3>'.date('d-m-Y H:i:s',get_post_meta($post_id,'wpdp_last_updated_date',true)).'</h3>
                <a href="'.get_post_meta($post_id,'wpdp_last_file_url',true).'" target="_blank" class="button button-primary">Local Server Copy</a>
                <a href="'.get_field('acled_url',$post_id).'" target="_blank" class="button button-secondary">ACLED Copy</a>
            </div>
        ';
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


    public function update_acled_presentations() {
        $args = array(
            'post_type' => 'wp-data-presentation',
            'posts_per_page' => -1,
            'fields' => 'ids',
            'meta_query' => array(
                array(
                    'key' => 'import_file',
                    'value' => 'Acled URL',
                    'compare' => '='
                ),
                array(
                    'key' => 'include_in_cron_job_updates',
                    'value' => '1',
                    'compare' => '='
                )
            )
        );

        $post_ids = get_posts($args);
        if(empty($post_ids)){
            return;
        }
        foreach ($post_ids as $post_id) {
            $this->create_data_table($post_id, false);
            error_log("Updated ACLED presentation: " . $post_id);
        }
    }

    public function add_custom_mime_types($mimes) {
        // Add CSV mime type
        $mimes['csv'] = 'text/csv';
        
        // Add Excel mime types
        $mimes['xlsx'] = 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet';
        $mimes['xls'] = 'application/vnd.ms-excel';
        
        return $mimes;
    }

    public function custom_upload_filter($file) {
        $allowed_extensions = array('csv', 'xlsx', 'xls');
        $file_extension = pathinfo($file['name'], PATHINFO_EXTENSION);

        if (!in_array(strtolower($file_extension), $allowed_extensions)) {
            $file['error'] = 'File type not allowed. Please upload a CSV or Excel file.';
        }

        return $file;
    }

}

WPDP_Metabox::get_instance();
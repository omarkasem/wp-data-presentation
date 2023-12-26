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
        add_filter('acf/render_field/key=field_657aa8a6e7d11', array($this,'override_acf_message_field'), 20, 1);
        add_filter('acf/render_field/key=field_657d3337b74a7', array($this,'mapping_template'), 20, 1);
        add_filter('acf/render_field/key=field_657e4ec5e8971', array($this,'shortcode_box'), 20, 1);
        add_action('admin_enqueue_scripts', array($this, 'enqueue_scripts'));

        add_action('wp_ajax_wpdp_get_data', array($this,'get_data'));

        add_action('save_post',array($this,'save_presentation'));

    }

    public function save_presentation($post_id){
        if(get_post_type($post_id) !== 'wp-data-presentation'){
            return;
        }

        if(isset($_POST['wpdp_validated']) && intval($_POST['wpdp_validated']) === 0){
            return;
        }
        

        if(get_post_status($post_id) !== 'publish'){
            return;
        }

        if(isset($_POST['t_mapping'])){
            update_post_meta($post_id,'t_mapping',$_POST['t_mapping']);
        }

        if(isset($_POST['t_location'])){
            update_post_meta($post_id,'t_location',$_POST['t_location']);
        }

        $result = get_post_meta($post_id,'wpdp_results',true);
        update_post_meta($post_id,'wpdp_results',$result);
    }

    public function shortcode_box($field){
        echo '<div class="wpdp_shortcode">
        <input type="text" disabled value=" [WP_DATA_PRESENTATION]"> 
        <button class="button button-secondary wpdp_copy">Copy</button>
        <input type="hidden" class="wpdp_validated" name="wpdp_validated" value="0">
        </div>';
    }
    
    public function get_data(){
        $file_url = $_POST['file'];
        $post_id = intval(($_POST['post_id']));
        if($post_id === 0){
            wp_send_json_error(['Post ID not found']);
        }

        if($file_url == ''){
            wp_send_json_error(['file path not found']);
        }

        $file = file_get_contents($file_url);
        $inputFileName = 'tempfile.xlsx';
        file_put_contents($inputFileName, $file);

        $parser = new WPDP_Get_Data($inputFileName );
        $preview = $parser->get_preview_elements($inputFileName);
        $result = $parser->parse_excel($inputFileName);
        
        if(empty($preview)){
            wp_send_json_error(['file is not formatted correctly']);
        }

        update_post_meta($post_id,'wpdp_results',$result);
        wp_send_json_success([$preview]);
        wp_die();
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

    function mapping_template(){ ?>
        <table class="wpdp_table" style="display:none;">
            <thead>
                <th>Sample Data</th>
                <th>Cell No</th>
                <th>Mapping</th>
                <th>Location</th>
                <th>Cell Type</th>
            </thead>
            <tbody>
                <tr>
                    <td class="a1"></td>
                    <td>A1</td>
                    <td>
                        <select disabled name="t_mapping[0]" id="t_mapping">
                            <option value="no">No Axis</option>
                            <option value="x">X Axis (Time)</option>
                            <option value="y">Y Axis (Types)</option>
                        </select>
                    </td>
                    <td>
                        <select name="t_location[0]" id="t_location">
                            <option value="yes">Yes</option>
                            <option value="no">No</option>
                        </select>
                    </td>
                    <td>1st Cell (A1)</td>
                </tr>

                <tr>
                    <td class="a2"></td>
                    <td>A2</td>
                    <td>
                        <select name="t_mapping[1]" id="t_mapping">
                            <option value="y">Y Axis (Types)</option>
                            <option value="x">X Axis (Time)</option>
                        </select>
                    </td>
                    <td>
                        <select name="t_location[1]" id="t_location">
                            <option value="no">No</option>
                            <option value="yes">Yes</option>
                        </select>
                    </td>
                    <td>Row Header</td>
                </tr>

                <tr>
                    <td class="b1"></td>
                    <td>B1</td>
                    <td>
                        <select name="t_mapping[2]" id="t_mapping">
                            <option value="x">X Axis (Time)</option>
                            <option value="y">Y Axis (Types)</option>
                        </select>
                    </td>
                    <td>
                        <select name="t_location[2]" id="t_location">
                            <option value="no">No</option>
                            <option value="yes">Yes</option>
                        </select>
                    </td>
                    <td>Column Header</td>
                </tr>


                <tr>
                    <td class="b2"></td>
                    <td>B2</td>
                    <td>
                        <select disabled name="t_mapping[3]" id="t_mapping">
                            <option value="no">No Axis</option>
                            <option value="x">X Axis (Time)</option>
                            <option value="y">Y Axis (Types)</option>
                        </select>
                    </td>
                    <td>
                        <select name="t_location[3]" id="t_location">
                            <option value="no">No</option>
                            <option value="yes">Yes</option>
                        </select>
                    </td>
                    <td>Data Cell</td>
                </tr>

            </tbody>
        </table>
    <?php }
    

    function override_acf_message_field($field) {
        echo '
        <button class="button button-primary wpdp_validate_file">Validate File</button>
        <img class="wpdp_loader" src="'.admin_url('images/loading.gif').'">
        ';
    }
    


}

WPDP_Metabox::get_instance();
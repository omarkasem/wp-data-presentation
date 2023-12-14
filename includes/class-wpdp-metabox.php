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
        add_action('admin_enqueue_scripts', array($this, 'enqueue_scripts'));

        add_action('wp_ajax_wpdp_get_data', array($this,'get_data'));

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
        $result = $parser->parse_excel();
        if(empty($result)){
            wp_send_json_error(['file is not formatted correctly']);
        }

        update_post_meta($post_id,'wpdp_results',$result);
        wp_send_json_success([$result]);
        wp_die();
    }

    public function enqueue_scripts() {
        wp_enqueue_script(WP_DATA_PRESENTATION_NAME, WP_DATA_PRESENTATION_URL . 'assets/js/wp-data-presentation-admin.js', array('jquery'), WP_DATA_PRESENTATION_VERSION, false);

        wp_localize_script(WP_DATA_PRESENTATION_NAME, 'wpdp_obj', array( 'ajax_url' => admin_url('admin-ajax.php')));

    }



    public function my_acf_settings_url( $url ) {
        return WP_DATA_PRESENTATION_ACF_URL;
    }

    
    public function show_admin( $show_admin ) {
        return WP_DATA_PRESENTATION_ACF_SHOW;
    }

    
    

    function override_acf_message_field($field) {
        $success = (isset($_GET['post']) && intval(get_field('validation')) === 1 ? true : false );

        echo '
        <button class="button button-primary wpdp_validate_file">Validate File</button>
        <img class="wpdp_loader" src="'.admin_url('images/loading.gif').'">
        <p class="wpdp_success" '.($success ? 'style="opacity:1;"' : '').'>Success</p>

        <br>
        '.($success ? '<div class="wpdp_shortcode">
        <input type="text" disabled value=" [WP_DATA_PRESENTATION id='.$_GET['post'].']"> 
        <button class="button button-secondary wpdp_copy">Copy</button> 
        </div>' : '').'
        ';
        echo '<style>
            .wpdp_shortcode span{
                position: absolute;
                right: -54px;
                top: 5px;
            }
            .wpdp_shortcode{
                display: flex;
                position:relative;
                width: 39%;
                margin-top: 10px;
            }
            .wpdp_shortcode input{
                font-weight:bold;
            }
            div[data-name="validation"]{
                display:none;
            }
            .wpdp_loader{
                display:none;
            }
            .wpdp_success{
                opacity:0;
                display: inline-block;
                align-items: center;
                background: #1da311;
                padding: 5px 10px;
                color: #fff;
                margin-left: 5px;
                margin: 0;
                margin-left: 10px;
            }
        </style>';
    }
    


}

WPDP_Metabox::get_instance();
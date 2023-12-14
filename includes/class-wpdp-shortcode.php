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
    }


    public function show_shortcode($atts){
        $atts = shortcode_atts( array(
            'id' => '',
        ), $atts, 'WP_DATA_PRESENTATION' );
        
        $id = intval($atts['id']);
        if($id === 0 || get_post_type($id) !== 'wp-data-presentation'){
            return 'ID is not correct';
        }

        ob_start();
        $type = get_field('presentation_type',$id);
        if($type === 'Datatables'){
            WPDP_DataTables::shortcode_output($atts);
        }elseif($type === 'Graphs'){
            WPDP_Graphs::shortcode_output($atts);
        }

    ?>

        
    

    <?php 
        $output = ob_get_clean();
        return $output;
    }


}

WPDP_Shortcode::get_instance();
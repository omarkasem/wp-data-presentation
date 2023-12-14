<?php 
/**
 * View class
 *
 * @since 1.0.0
 */

final class WPDP_Graphs {

    /**
     * Instance of this class.
     *
     * @since 1.0.0
     *
     * @var WPDP_Graphs
     */
    protected static $_instance = null;

    /**
     * Get instance of the class
     *
     * @since 1.0.0
     *
     * @return WPDP_Graphs
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
    }


    function enqueue_scripts() {
    
    }
    

    public static function shortcode_output($atts){
        $id = intval($atts['id']);
        wp_enqueue_script(WP_DATA_PRESENTATION_NAME.'Graphs');
        wp_enqueue_style(WP_DATA_PRESENTATION_NAME.'Graphs');
        wp_enqueue_script(WP_DATA_PRESENTATION_NAME.'public');
    ?>

        <h1>Graphs</h1>
    

    <?php }


}

WPDP_Graphs::get_instance();
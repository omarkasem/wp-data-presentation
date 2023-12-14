<?php 
/**
 * View class
 *
 * @since 1.0.0
 */

final class WPDP_Post_Types {

    /**
     * Instance of this class.
     *
     * @since 1.0.0
     *
     * @var WPDP_Post_Types
     */
    protected static $_instance = null;

    /**
     * Get instance of the class
     *
     * @since 1.0.0
     *
     * @return WPDP_Post_Types
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
        add_action( 'init', array($this,'register_post_type') );
    }


    function register_post_type() {

        /**
         * Post Type: Data Presentations.
         */
    
        $labels = [
            "name" => esc_html__( "Data Presentations", "astra" ),
            "singular_name" => esc_html__( "Data Presentation", "astra" ),
        ];
    
        $args = [
            "label" => esc_html__( "Data Presentations", "astra" ),
            "labels" => $labels,
            "description" => "",
            "public" => false,
            "publicly_queryable" => false,
            "show_ui" => true,
            "show_in_rest" => false,
            "rest_base" => "",
            "rest_controller_class" => "WP_REST_Posts_Controller",
            "rest_namespace" => "wp/v2",
            "has_archive" => false,
            "show_in_menu" => true,
            "show_in_nav_menus" => true,
            "delete_with_user" => false,
            "exclude_from_search" => false,
            "capability_type" => "post",
            "map_meta_cap" => true,
            "hierarchical" => false,
            "can_export" => false,
            "rewrite" => [ "slug" => "wp-data-presentation", "with_front" => false ],
            "query_var" => true,
            "menu_icon" => "dashicons-tickets",
            "supports" => [ "title" ],
            "show_in_graphql" => false,
        ];
    
        register_post_type( "wp-data-presentation", $args );
    }
    

}

WPDP_Post_Types::get_instance();
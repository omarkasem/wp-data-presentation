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
        add_filter( 'acf/settings/save_json', array($this,'my_acf_json_save_point') );
        add_filter( 'acf/settings/load_json', array($this,'my_acf_json_load_point') );
        add_action('acf/init',array($this,'my_acf_op_init'));

        add_filter('manage_wp-data-presentation_posts_columns', array($this,'add_updated_status_column'));

        add_action('manage_wp-data-presentation_posts_custom_column', array($this,'show_updated_status_column'), 10, 2);


    }

    
    function add_updated_status_column($columns) {
        $new_columns = array();
        foreach ($columns as $key => $value) {
            if ($key == 'title') {
                $new_columns[$key] = $value;
                $new_columns['updated_status'] = __('Countries Edited');
            } else {
                $new_columns[$key] = $value;
            }
        }
        return $new_columns;
    }
    
    function show_updated_status_column($column_name, $post_id) {
        if ($column_name == 'updated_status') {
            $updated = get_post_meta($post_id, 'wpdp_countries_updated', true);
            if ($updated) {
                echo '<span style="color: green; font-weight: bold;">Success</span>';
            } else {
                echo '';
            }
        }
    }


    function my_acf_op_init() {
    
        if( function_exists('acf_add_options_sub_page') ) {
            $parent = acf_add_options_page(array(
                'page_title'  => __('Data Presentation'),
                'menu_title'  => __('Data Presentation'),
                'redirect'    => false,
                'parent_slug'=>'options-general.php'
            ));
        }
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
    


    function my_acf_json_load_point( $paths ) {
        // Remove the original path (optional).
        unset($paths[0]);
    
        // Append the new path and return it.
        $paths[] = WP_DATA_PRESENTATION_PATH . 'lib/acf-json';
    
        return $paths;    
    }
    

    function my_acf_json_save_point( $path ) {
        return WP_DATA_PRESENTATION_PATH . 'lib/acf-json';
    }
    


}

WPDP_Post_Types::get_instance();
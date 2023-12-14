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
        $this->add_acf();
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
    

    function add_acf(){
        if( function_exists('acf_add_local_field_group') ):

            acf_add_local_field_group(array(
                'key' => 'group_657aa7f6e7f7f',
                'title' => 'Data Presentation',
                'fields' => array(
                    array(
                        'key' => 'field_657aa7f7cb9c3',
                        'label' => 'Import File',
                        'name' => '',
                        'aria-label' => '',
                        'type' => 'tab',
                        'instructions' => '',
                        'required' => 0,
                        'conditional_logic' => 0,
                        'wrapper' => array(
                            'width' => '',
                            'class' => '',
                            'id' => '',
                        ),
                        'placement' => 'top',
                        'endpoint' => 0,
                    ),
                    array(
                        'key' => 'field_657aa840cb9c5',
                        'label' => 'Import File',
                        'name' => 'import_file',
                        'aria-label' => '',
                        'type' => 'select',
                        'instructions' => '',
                        'required' => 0,
                        'conditional_logic' => 0,
                        'wrapper' => array(
                            'width' => '',
                            'class' => '',
                            'id' => '',
                        ),
                        'choices' => array(
                            'Upload' => 'Upload',
                            'URL' => 'URL',
                        ),
                        'default_value' => false,
                        'return_format' => 'value',
                        'multiple' => 0,
                        'allow_null' => 0,
                        'ui' => 0,
                        'ajax' => 0,
                        'placeholder' => '',
                    ),
                    array(
                        'key' => 'field_657aa818cb9c4',
                        'label' => 'Upload Excel File',
                        'name' => 'upload_excel_file',
                        'aria-label' => '',
                        'type' => 'file',
                        'instructions' => '',
                        'required' => 0,
                        'conditional_logic' => array(
                            array(
                                array(
                                    'field' => 'field_657aa840cb9c5',
                                    'operator' => '==',
                                    'value' => 'Upload',
                                ),
                            ),
                        ),
                        'wrapper' => array(
                            'width' => '',
                            'class' => '',
                            'id' => '',
                        ),
                        'return_format' => 'id',
                        'library' => 'all',
                        'min_size' => '',
                        'max_size' => '',
                        'mime_types' => 'xlsx,csv',
                    ),
                    array(
                        'key' => 'field_657aa859cb9c6',
                        'label' => 'Excel File URL',
                        'name' => 'excel_file_url',
                        'aria-label' => '',
                        'type' => 'url',
                        'instructions' => '',
                        'required' => 0,
                        'conditional_logic' => array(
                            array(
                                array(
                                    'field' => 'field_657aa840cb9c5',
                                    'operator' => '==',
                                    'value' => 'URL',
                                ),
                            ),
                        ),
                        'wrapper' => array(
                            'width' => '',
                            'class' => '',
                            'id' => '',
                        ),
                        'default_value' => '',
                        'placeholder' => '',
                    ),
                    array(
                        'key' => 'field_657aa8a6e7d11',
                        'label' => 'Validate File',
                        'name' => '',
                        'aria-label' => '',
                        'type' => 'message',
                        'instructions' => '',
                        'required' => 0,
                        'conditional_logic' => 0,
                        'wrapper' => array(
                            'width' => '',
                            'class' => '',
                            'id' => '',
                        ),
                        'message' => '',
                        'new_lines' => 'wpautop',
                        'esc_html' => 0,
                    ),
                    array(
                        'key' => 'field_657abd1ddac96',
                        'label' => 'Validation',
                        'name' => 'validation',
                        'aria-label' => '',
                        'type' => 'true_false',
                        'instructions' => '',
                        'required' => 0,
                        'conditional_logic' => 0,
                        'wrapper' => array(
                            'width' => '',
                            'class' => '',
                            'id' => '',
                        ),
                        'message' => '',
                        'default_value' => 0,
                        'ui' => 0,
                        'ui_on_text' => '',
                        'ui_off_text' => '',
                    ),
                    array(
                        'key' => 'field_657ac50fe4b23',
                        'label' => 'Presentation',
                        'name' => '',
                        'aria-label' => '',
                        'type' => 'tab',
                        'instructions' => '',
                        'required' => 0,
                        'conditional_logic' => array(
                            array(
                                array(
                                    'field' => 'field_657abd1ddac96',
                                    'operator' => '==',
                                    'value' => '1',
                                ),
                            ),
                        ),
                        'wrapper' => array(
                            'width' => '',
                            'class' => '',
                            'id' => '',
                        ),
                        'placement' => 'top',
                        'endpoint' => 0,
                    ),
                    array(
                        'key' => 'field_657ac59723f2d',
                        'label' => 'Presentation Type',
                        'name' => 'presentation_type',
                        'aria-label' => '',
                        'type' => 'select',
                        'instructions' => '',
                        'required' => 0,
                        'conditional_logic' => 0,
                        'wrapper' => array(
                            'width' => '',
                            'class' => '',
                            'id' => '',
                        ),
                        'choices' => array(
                            'Datatables' => 'Datatables',
                            'Graphs' => 'Graphs',
                            'Maps' => 'Maps',
                        ),
                        'default_value' => false,
                        'return_format' => 'value',
                        'multiple' => 0,
                        'allow_null' => 0,
                        'ui' => 0,
                        'ajax' => 0,
                        'placeholder' => '',
                    ),
                ),
                'location' => array(
                    array(
                        array(
                            'param' => 'post_type',
                            'operator' => '==',
                            'value' => 'wp-data-presentation',
                        ),
                    ),
                ),
                'menu_order' => 0,
                'position' => 'normal',
                'style' => 'default',
                'label_placement' => 'top',
                'instruction_placement' => 'label',
                'hide_on_screen' => '',
                'active' => true,
                'description' => '',
                'show_in_rest' => 0,
            ));
            
            endif;		
    
    }

}

WPDP_Post_Types::get_instance();
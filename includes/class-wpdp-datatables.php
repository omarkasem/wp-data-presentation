<?php 
/**
 * View class
 *
 * @since 1.0.0
 */

final class WPDP_DataTables {

    /**
     * Instance of this class.
     *
     * @since 1.0.0
     *
     * @var WPDP_DataTables
     */
    protected static $_instance = null;

    /**
     * Get instance of the class
     *
     * @since 1.0.0
     *
     * @return WPDP_DataTables
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
    
        wp_register_script(WP_DATA_PRESENTATION_NAME.'datatables', WP_DATA_PRESENTATION_URL.'assets/js/datatables.min.js', array('jquery'), WP_DATA_PRESENTATION_VERSION, true);
        
        wp_register_style(WP_DATA_PRESENTATION_NAME.'datatables', WP_DATA_PRESENTATION_URL.'assets/css/datatables.min.css', [],WP_DATA_PRESENTATION_VERSION );
        
        wp_register_script(WP_DATA_PRESENTATION_NAME.'public', WP_DATA_PRESENTATION_URL.'assets/js/wp-data-presentation-public.js', array('jquery'), WP_DATA_PRESENTATION_VERSION, true);
    }
    

    public static function shortcode_output($atts){
        $id = intval($atts['id']);
        wp_enqueue_script(WP_DATA_PRESENTATION_NAME.'datatables');
        wp_enqueue_style(WP_DATA_PRESENTATION_NAME.'datatables');
        wp_enqueue_script(WP_DATA_PRESENTATION_NAME.'public');

        $result = get_post_meta($id,'wpdp_results',true);
        if(empty($result)){
            return 'No results found.';
        }

    ?>
        <h2>Location: <b><?php echo $result['location']; ?></b></h2>
        <table id="wpdp_table" class="display" style="width:100%">
            <thead>
                <tr>
                    <th>Country</th>
                    <th>Type</th>
                    <th>Date</th>
                    <th>Number</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach($result['incidents'] as $key => $value){
                    $type = $value[0];
                    unset($value[0]);
                    $value = array_values($value);

                    foreach($value as $k => $v){
                        echo '<tr>
                            <td>'.$result['location'].'</td>
                            <td>'.$type.'</td>
                            <td>'.$result['years'][$k].'</td>
                            <td>'.$v.'</td>
                        </tr>';
                    }

                } ?>

            </tbody>
        </table>

        <style>
            tr.group, tr.group:hover {
                background-color: #ddd !important;
            }
        </style>

    <?php }


}

WPDP_DataTables::get_instance();
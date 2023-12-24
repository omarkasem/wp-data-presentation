<?php 
/**
 * View class
 *
 * @since 1.0.0
 */

final class WPDP_Tables {

    /**
     * Instance of this class.
     *
     * @since 1.0.0
     *
     * @var WPDP_Tables
     */
    protected static $_instance = null;

    /**
     * Get instance of the class
     *
     * @since 1.0.0
     *
     * @return WPDP_Tables
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
        
        wp_register_script(WP_DATA_PRESENTATION_NAME.'exceltables', WP_DATA_PRESENTATION_URL.'assets/js/handsontable.full.min.js', array('jquery'), WP_DATA_PRESENTATION_VERSION, true);
        wp_register_style(WP_DATA_PRESENTATION_NAME.'exceltables', WP_DATA_PRESENTATION_URL.'assets/css/handsontable.full.min.css', [],WP_DATA_PRESENTATION_VERSION );


    }
    

    public static function shortcode_output($atts){
        $id = intval($atts['id']);
        $pres_type = get_field('presentation_type',$id);

        if($pres_type === 'Datatables'){
            wp_enqueue_script(WP_DATA_PRESENTATION_NAME.'datatables');
            wp_enqueue_style(WP_DATA_PRESENTATION_NAME.'datatables');
            $table = 'wpdp_datatable';
        }else{
            wp_enqueue_script(WP_DATA_PRESENTATION_NAME.'exceltables');
            wp_enqueue_style(WP_DATA_PRESENTATION_NAME.'exceltables');
            $table = 'wpdp_exceltables';
        }

        $result = get_post_meta($id,'wpdp_results',true);
        if(empty($result)){
            return 'No results found.';
        }
        
    ?>
        <table id="<?php echo $table; ?>" class="display" style="width:100%">
            <thead>
                <tr>
                    <th>Date</th>
                    <th>Type</th>
                    <th>Location</th>
                    <th>Number</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach($result as $k=> $val){ ?>
                    <tr>
                        <td><?php echo $val['event_date']; ?></td>
                        <td><?php echo $val['disorder_type']; ?></td>
                        <td><?php echo $val['country']; ?></td>
                        <td><?php echo $val['fatalities']; ?></td>
                    </tr>
                <?php } ?>

            </tbody>

            <tfoot>
                <tr>
                    <th>Date</th>
                    <th>Type</th>
                    <th>Location</th>
                    <th>Number</th>
                </tr>
        </tfoot>

        </table>

        <style>
            tr.group, tr.group:hover {
                background-color: #ddd !important;
            }
        </style>

    <?php }


}

WPDP_Tables::get_instance();
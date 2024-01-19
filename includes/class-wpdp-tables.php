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
        add_action( 'wp_ajax_nopriv_wpdp_datatables_request', array($this,'get_datatables_data') );
        add_action( 'wp_ajax_wpdp_datatables_request', array($this,'get_datatables_data') );
        if(isset($_GET['test'])){
            var_dump(get_option('test'));exit;
        }
    }

    public function get_datatables_data(){
        $types = [
            'event_date',
            'disorder_type',
            'country',
            'fatalities',
            'admin1'
        ];
        $data = WPDP_Shortcode::get_all_data($types,true);

        $arr = [
            "draw"=> 1,
            "recordsTotal"=> count($data),
            "recordsFiltered"=> count($data),
            "data"=> $data,
        ];

        echo json_encode($arr);
        wp_die();
    }

    function enqueue_scripts() {
    
        wp_register_script(WP_DATA_PRESENTATION_NAME.'datatables', WP_DATA_PRESENTATION_URL.'assets/js/datatables.min.js', array('jquery'), WP_DATA_PRESENTATION_VERSION, true);
        wp_register_script(WP_DATA_PRESENTATION_NAME.'moment', WP_DATA_PRESENTATION_URL.'assets/js/moment.min.js', array('jquery'), WP_DATA_PRESENTATION_VERSION, true);
        wp_register_style(WP_DATA_PRESENTATION_NAME.'datatables', WP_DATA_PRESENTATION_URL.'assets/css/datatables.min.css', [],WP_DATA_PRESENTATION_VERSION );
        
    }
    

    public static function shortcode_output($result){

        wp_enqueue_script(WP_DATA_PRESENTATION_NAME.'datatables');
        wp_enqueue_script(WP_DATA_PRESENTATION_NAME.'moment');
        wp_enqueue_style(WP_DATA_PRESENTATION_NAME.'datatables');
        $table = 'wpdp_datatable';

        // if(empty($result)){
        //     return 'No results found.';
        // }
        
    ?>

    <table style="display:none;">
        <tbody><tr>
            <td>Minimum date:</td>
            <td><input type="text" id="wpdp_min" name="min"></td>
        </tr>
        <tr>
            <td>Maximum date:</td>
            <td><input type="text" id="wpdp_max" name="max"></td>
        </tr>
    </tbody></table>

        <table id="<?php echo $table; ?>" class="display" style="width:100%">
            <thead>
                <tr>
                    <th>Date</th>
                    <th>Type</th>
                    <th>Location</th>
                    <th>Number</th>
                    <th>More Details</th>
                </tr>
            </thead>

            <tfoot style2="display:none;">
                <tr>
                    <th class="date">Date</th>
                    <th class="type">Type</th>
                    <th class="location">Location</th>
                    <th class="number">Number</th>
                </tr>
            </tfoot>

            <!-- <tbody>
                <?php foreach($result as $k=> $val){
                    $locs = [$val['country'],$val['region'],$val['admin1'],$val['admin2'],$val['admin3'],$val['location']];
                    ?>
                    <tr>
                        <td event_type="<?php echo $val['event_type']; ?>"><?php echo $val['event_date']; ?></td>
                        <td sub_event_type="<?php echo $val['sub_event_type']; ?>"><?php echo $val['disorder_type']; ?></td>
                        <td locs='<?php echo wp_json_encode($locs); ?>' source="<?php echo $val['source']; ?>"><?php echo $val['country']; ?> 
                        <span style="display:none;">
                        <?php unset($locs[0]); foreach(array_filter($locs) as $loc): ?>
                            <?php echo $loc.' '; ?>
                        <?php endforeach; ?>
                        </span>
                    </td>
                        <td notes="<?php echo $val['notes']; ?>"><?php echo $val['fatalities']; ?></td>
                        <td timestamp="<?php echo date('c',$val['timestamp']); ?>"><span style="cursor:pointer;color:#cd0202;font-size:26px;" class="more-info dashicons dashicons-info"></span></td>
                    </tr>
                    
                <?php } ?>

            </tbody> -->

        </table>

        <style>
            tr.group, tr.group:hover {
                background-color: #ddd !important;
            }
        </style>

    <?php }


}

WPDP_Tables::get_instance();
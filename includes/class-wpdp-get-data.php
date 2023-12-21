<?php 
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Style\NumberFormat;

class WPDP_Get_Data{
    private $excelFile;
    private $data;

    public function __construct($excelFile){
        $this->excelFile = $excelFile;
    }

    public function parse_excel(){
        $spreadsheet = IOFactory::load($this->excelFile);
        $sheets = $spreadsheet->getAllSheets();

        return $this->parse_new_data($sheets);
    }

    public function parse_new_data($allSheets){
        $allSheetsData = [];
        foreach ($allSheets as $k => $sheet) {
            $sheetData = $sheet->toArray(null, true, true, true);
        
            $headers = array_shift($sheetData);
        
            foreach ($headers as $header) {
                $allSheetsData[$k][$header] = [];
            }
        
            foreach ($sheetData as $row) {
                foreach ($headers as $column => $header) {
                    $allSheetsData[$k][$header][] = $row[$column];
                }
            }
        }
        return $allSheetsData;
    }

    public function get_preview_elements(){
        $spreadsheet = IOFactory::load($this->excelFile);
        $sheet = $spreadsheet->getActiveSheet();

        $data = [];
        $loc = $sheet->getRowIterator(1)->current();
        $i=0;
        foreach ($loc->getCellIterator() as $cell) { $i++;
            if($i === 1){
                $data['a1'] = $cell->getValue();
            }
            if($i === 2){
                $data['b1'] = $cell->getValue();
            }
        }

        $loc2 = $sheet->getRowIterator(2)->current();
        $m=0;
        foreach ($loc2->getCellIterator() as $cell) { $m++;
            if($m === 1){
                $data['a2'] = $cell->getValue();
            }
            if($m === 2){
                $data['b2'] = $cell->getValue();
            }
        }
        return $data;
    }

}

if(isset($_GET['test2'])){
    $inputFileName = WP_DATA_PRESENTATION_PATH.'new.csv';
    $parser = new WPDP_Get_Data($inputFileName );
    $result = $parser->parse_excel();
    var_dump($result);exit;
}

// add_action('wp_enqueue_scripts', 'enqueue_moment_scripts');
// function enqueue_moment_scripts() {
    
//     wp_register_script('test1', 'https://cdn.jsdelivr.net/npm/chart.js', array('jquery'), 111, true);
//     wp_register_script('test2', plugin_dir_url( __FILE__ ).'/test.js', array('jquery'), 111, true);
    
//     $file = plugin_dir_path( __FILE__ )."test2.xlsx";
//     $parser = new WPDP_Get_Data($file);
//     $result = $parser->parse_excel();

//     wp_localize_script( 'test2', 'ok_obj', $result );

// }



add_shortcode( 'OK_TEST', 'ok_test' );
function ok_test($atts){
    wp_enqueue_script('test1');
    wp_enqueue_script('test2');

    return '
    
    <h2>omar</h2><canvas id="incidentChart" width="800" height="400"></canvas>
    

    <select id="incidentTypeSelector">
        <option value="0">Battles</option>
        <option value="1">Explosions/Remote violence</option>
        <option value="2">Protests</option>
        <option value="3">Riots</option>
        <option value="4">Violence against civilians</option>
        <option value="5">Fatalities</option>
    </select>

    ';

}


// if(isset($_GET['test'])){
//     $file = plugin_dir_path( __FILE__ )."test2.xlsx";
//     $parser = new WPDP_Get_Data($file);
//     $result = $parser->parse_excel();

//     var_dump($result);exit;
// }
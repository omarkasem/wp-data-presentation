<?php 
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Style\NumberFormat;

class WPDP_Get_Data{
    private $excelFile;
    private $data;

    public function __construct($excelFile){
        $this->excelFile = $excelFile;
        $this->data = [];
    }

    public function parse_excel(){
        $spreadsheet = IOFactory::load($this->excelFile);
        $sheet = $spreadsheet->getActiveSheet();

        $this->parse_years($sheet);
        $this->parse_data($sheet);

        return $this->data;
    }

    private function parse_years($sheet){

        $loc = $sheet->getRowIterator(1)->current();
        foreach ($loc->getCellIterator() as $cell) {
            if($cell->getValue() !== 'Location:' && $cell->getValue() != ''){
                $location = $cell->getValue();
            }
        }

        // $this->data['location'] = $location;
    
        $headerRow = $sheet->getRowIterator(2)->current();
        $headerValues = [];

        foreach ($headerRow->getCellIterator() as $cell) {
            // If the cell value is not numeric, format it as a date
            if (!is_numeric($cell->getValue())) {
                $cell->getStyle()->getNumberFormat()->setFormatCode(NumberFormat::FORMAT_DATE_XLSX22);
            }
            $headerValues[] = $cell->getFormattedValue();
        }

        // Remove the "Location" label from the header
        array_shift($headerValues);

        // Set the years as the header
        $this->data['years'] = $headerValues;
    }

    private function parse_data($sheet){
        $rowIterator = $sheet->getRowIterator(3);

        foreach ($rowIterator as $row) {
            $rowData = [];
            $cellIterator = $row->getCellIterator();

            // Get the type of incident (first column)
            $rowData[] = $cellIterator->current()->getValue();
            $cellIterator->next(); // Move to the next cell

            // Iterate over all remaining columns dynamically
            while ($cellIterator->valid()) {
                $rowData[] = $cellIterator->current()->getValue();
                $cellIterator->next();
            }

            $this->data['incidents'][] = $rowData;
        }
    }

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
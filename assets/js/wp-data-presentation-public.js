jQuery(document).ready(function($){
    if ($.fn.DataTable) {
        $('#wpdp_table').DataTable({
            dom: 'Bfrtip',
            buttons: [
                'copyHtml5',
                'excelHtml5',
                'csvHtml5',
                'pdfHtml5'
            ],
            "rowGroup": {
                "dataSrc": 0
            },
            "fixedColumns": {
                "leftColumns": 0
            }
        });
    }

    if (typeof Chart !== 'undefined') {


    // Function to extract data for a specific category from the dataset
    function extractData(dataset, category) {
        const labels = Object.keys(dataset).filter(key => key !== 'location');
        const values = labels.map(year => dataset[year][category]);
        return { labels, values };
      }
  
      // Function to create the line chart
      function createLineChart(dataset, category, color) {
        const { labels, values } = extractData(dataset, category);
  
        const ctx = document.getElementById('lineChart').getContext('2d');
        new Chart(ctx, {
          type: 'line',
          data: {
            labels: labels,
            datasets: [{
              label: `${dataset['location']} - ${category}`,
              borderColor: color,
              data: values,
              fill: false,
            }]
          },
          options: {
            scales: {
              x: {
                type: 'linear',
                position: 'bottom'
              }
            }
          }
        });
      }
  
      // Example: Create line charts for 'Protests' in Zambia and Egypt
      createLineChart(wpdp_data['sheet0'], 'Protests', 'blue');
      createLineChart(wpdp_data['sheet1'], 'Protests', 'red');
  


    }
  
});
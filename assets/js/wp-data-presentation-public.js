( function ( jQuery ) {
  'use strict';

  jQuery( document ).ready( function ( $ ) {
    var self = {};
    var data = wpdp_data;
    var myChart;


    self.init = function(){
      self.dataTables();
      self.excelTables();
      self.filters();
      self.filtersChange();
    },



    self.excelTables = function(){
      if ($('#wpdp_exceltables').length > 0) {
        var data = [];
        $('#wpdp_exceltables tr').each(function(index,el){
            var rowData = [];
            $(el).children().each(function(index,el){
                rowData.push($(el).html());
            });
            data.push(rowData);
        });
        var el = $('#wpdp_exceltables').after('<div id="new_wpdp_exceltables"></div>').next();
        $('#wpdp_exceltables').remove();
        const container = document.querySelector('#new_wpdp_exceltables');
        let headers = data.shift();

        const hot = new Handsontable(container, {
          colHeaders: headers,
          data: data,
          dropdownMenu: true,
          hiddenColumns: {
            indicators: true,
          },
          contextMenu: true,
          multiColumnSorting: true,
          filters: true,
          rowHeaders: true,
          manualRowMove: true,
          autoWrapCol: true,
          autoWrapRow: true,
          licenseKey: 'non-commercial-and-evaluation'
        });
        
      }
    },

    self.dataTables = function(){
      if ($.fn.DataTable && $('#wpdp_datatable').length > 0) {
        $('#wpdp_table').DataTable({
            dom: 'Bfrtip',
            buttons: [
                'copyHtml5',
                'excelHtml5',
                'csvHtml5',
                'pdfHtml5'
            ],
         
        });
      }
    },

    self.filters = function(){
      $('.wpdp .filter').click(function(e){
        e.preventDefault();
        $('.wpdp .con').css('left','0');
      });
    
      $('.wpdp .filter_back').click(function(e){
        e.preventDefault();
        $('.wpdp .con').css('left','-25%');
      });
    
      $('#wpdp_type,#wpdp_location,#wpdp_from,#wpdp_to').select2({
        placeholder:"All",
        width: 'resolve',
      });
    },

    self.filtersChange = function() {
      $('#wpdp_type, #wpdp_location,#wpdp_from,#wpdp_to').on('select2:select select2:unselect',function(e){
        let typeValue = $("#wpdp_type").select2("val");
        let locationValue = $("#wpdp_location").select2("val");
        let fromYear = $("#wpdp_from").select2("val");
        let toYear = $("#wpdp_to").select2("val");


        if(typeValue == ""){
          typeValue = $('#wpdp_type option').toArray().map(item => item.value);
        }
        if(locationValue == ""){
          locationValue = $('#wpdp_location option').toArray().map(item => item.value);
        }
        if(fromYear == ""){
          fromYear = $('#wpdp_from option').toArray().map(item => item.value);
        }
        if(toYear == ""){
          toYear = $('#wpdp_to option').toArray().map(item => item.value);
        }

        if (myChart) {
          myChart.destroy();
        }
        myChart = self.graphChange(typeValue, locationValue,fromYear,toYear);
      });
    },
      
    self.graphChange = function(typeValue, locationValue, fromYear,toYear){
      let chartData = {
        labels: [],
        datasets: []
      };

      function removeDuplicates(data) {
        let uniqueData = [...new Set(data)];
    
        uniqueData.sort(function(a, b) {
            let aParts = a.split('-'),
                bParts = b.split('-');
    
            let aYear = parseInt(aParts[aParts.length - 1]) < 100 ? parseInt(aParts[aParts.length - 1]) + 2000 : parseInt(aParts[aParts.length - 1]);

            let bYear = bParts[bParts.length - 1] < 100 ? parseInt(bParts[bParts.length - 1]) + 2000 : parseInt(bParts[bParts.length - 1]);
    
            let aMonth, bMonth;
    
            if (aParts.length > 1) {
                let aDate = new Date(a);
                aDate.setFullYear(aYear);
                aMonth = aDate.getMonth();
            }
    
            if (bParts.length > 1) {
                let bDate = new Date(b);
                bDate.setFullYear(bYear);
                bMonth = bDate.getMonth();
            }
    
            if (aYear === bYear) {
                return (aMonth - bMonth) || 0        }
    
            return aYear - bYear;
        });
        return uniqueData;
      }
    
      for (let sheet in data) {
        let location = data[sheet].location;
        for (let selectField of typeValue) {
          if(locationValue.length > 0){
            if(!locationValue.includes(location)){
              continue;
            }
          }
          let dataset = {
            label: `${selectField} in ${location}`,
            data: [],
            fill: false,
            borderColor: '#' + (Math.random()*0xFFFFFF<<0).toString(16)
          };
            
          for (let year in data[sheet]) {
            if (year === 'location') continue;
                
            dataset.data.push(data[sheet][year][selectField]);
          }
              
          chartData.labels.push(Object.keys(data[sheet]).filter(key => key !== 'location'));
          chartData.datasets.push(dataset);
        }
      }
    
      chartData.labels = removeDuplicates(chartData.labels.flat());

      let ctx = document.getElementById('myChart').getContext('2d');
          
      return new Chart(ctx, {
        type: 'line',
        data: chartData,
        options: {
            responsive: true,
            plugins: {
                title: {
                    display: true,
                    text: 'Incidents by Type'
                },
            },
            scales: {
                x: {
                    display: true,
                    title: {
                        display: true,
                        text: 'Year'
                    }
                },
                y: {
                    display: true,
                    title: {
                        display: true,
                        text: 'Number of Incidents'
                    },
                    beginAtZero: true
                }
            }
        }
      });
    }
    
 
      


    $( self.init );
  });

  

  if (typeof Chart !== 'undefined') {


    function extractData(dataset, category) {
      const labels = Object.keys(dataset).filter(key => key !== 'location');
      const values = labels.map(year => dataset[year][category]);
      return { labels, values };
    }

    // Function to create the line chart
    function createLineChart(dataset, category, color) {
      const { labels, values } = extractData(dataset, category);
      console.log(labels,values);
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
    // createLineChart(wpdp_data['sheet0'], 'Protests', 'blue');
    // createLineChart(wpdp_data['sheet1'], 'Protests', 'red');



  }

}( jQuery ) );
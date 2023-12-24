( function ( $ ) {
  'use strict';

    var self = {};
    var data = wpdp_data;
    var myChart;
    var global_markers = [];

    self.init = function(){
      self.dataTables();
      self.excelTables();
      self.filters();
      self.filtersChange();
      
    },

    self.maps = function(typeValue = false , locationValue = false,fromYear = false,toYear = false){

      var mapData = [];
      for (let val of data) {
          if(locationValue.length > 0 && !locationValue.includes(val.country)){
            continue;
          }

          if(typeValue.length > 0 && !typeValue.includes(val.event_type)){
            continue;
          }
          
          if(parseInt(val.fatalities) === 0){
            continue;
          }

          mapData.push({
              latitude: val.latitude,
              longitude: val.longitude,
              date: val.year,
              number: val.fatalities,
              type: val.event_type,
              location: val.country
          });
      };


      var startLocation = { lat: parseFloat(mapData[0].latitude), lng: parseFloat(mapData[0].longitude) };
      
      if(!self.main_map){
        
        self.main_map = new google.maps.Map(
          document.getElementById('wpdp_map'),
          {
              zoom: 3, 
              center: startLocation,
              styles: [ // this will make your map color darker
                  {
                      "elementType": "geometry",
                      "stylers": [ { "color": "#242f3e" } ]
                  },
                  {
                      "elementType": "labels.text.stroke",
                      "stylers": [ { "color": "#242f3e" } ]
                  },
                  {
                      "elementType": "labels.text.fill",
                      "stylers": [ { "color": "#746855" } ]
                  },
                  //... more styles if you wish
              ],
              mapTypeControl: false
          }
        );
          
      }

      if(!self.svg_marker){     
        self.svg_marker = {
          path: "M-20,0a20,20 0 1,0 40,0a20,20 0 1,0 -40,0",
          fillColor: '#FF0000',
          fillOpacity: .6,
          anchor: new google.maps.Point(0,0),
          strokeWeight: 0,
          scale: 1
      
        };
      }
        
      var infoWindow = new google.maps.InfoWindow;
      
      mapData.forEach(function(loc) {
          var location = { lat: parseFloat(loc.latitude), lng: parseFloat(loc.longitude) };
          
          var marker = new google.maps.Marker({
              position: location, 
              map: self.main_map,
              icon: self.svg_marker // set custom marker
          });

          global_markers.push(marker);
      
          marker.addListener('click', function() { 
            infoWindow.close(); 
            infoWindow.setContent(`
            <div style="
                color: #333;
                font-size: 16px; 
                padding: 10px;
                line-height: 1.6;
                border: 2px solid #333;
                border-radius: 10px;
                background: #fff;
                ">
                    <h2 style="
                        margin: 0 0 10px;
                        font-size: 20px;
                        border-bottom: 1px solid #333;
                        padding-bottom: 5px;
                    ">${loc.type}</h2>
                    <p style="margin-bottom:0;"><strong>Location:</strong> ${loc.location}</p>
                    <p style="margin-bottom:0;"><strong>Number:</strong> ${loc.number}</p>
                    <p style="margin-bottom:0;"><strong>Date:</strong> ${loc.date}</p>
                </div>
            `);

            infoWindow.open(self.main_map, marker);
          }); 


          // Close the infoWindow when the map is clicked
          self.main_map.addListener('click', function() {
            infoWindow.close();
          });

      });

      window.addEventListener('load', self.maps);
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
        var table =  $('#wpdp_datatable').DataTable({
            dom: 'Bfrtip',
            buttons: [
                'copyHtml5',
                'excelHtml5',
                'csvHtml5',
                'pdfHtml5'
            ],
            
            initComplete: function () {
              this.api()
                  .columns()
                  .every(function () {
                      let column = this;
       
                      // Create select element
                      let select = document.createElement('select');
                      select.add(new Option(''));
                      column.footer().replaceChildren(select);
       
                      // Apply listener for user change in value
                      select.addEventListener('change', function () {
                          var val = DataTable.util.escapeRegex(select.value);
       
                          column
                              .search(val ? '^' + val + '$' : '', true, false)
                              .draw();
                      });
       
                      // Add list of options
                      column
                          .data()
                          .unique()
                          .sort()
                          .each(function (d, j) {
                              select.add(new Option(d));
                          });
                  });
          }
      


        });



      }
    },

    self.filters = function(){
      $('.wpdp .filter').click(function(e){
        e.preventDefault();
        $('.wpdp .con').css('left','-5%');
      });
    
      $('.wpdp .filter_back').click(function(e){
        e.preventDefault();
        $('.wpdp .con').css('left','-35%');
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
        if (typeof Chart !== 'undefined') {
          if (myChart) {
            myChart.destroy();
          }
          myChart = self.graphChange(typeValue, locationValue,fromYear,toYear);
        }

        if (typeof google === 'object' && typeof google.maps === 'object') {
          for(let i=0; i<global_markers.length; i++){
            global_markers[i].setMap(null);
          }
          
          self.maps(typeValue, locationValue,fromYear,toYear);

        }
      });
    },
      
    self.graphChange = function(typeValue, locationValue, fromYear,toYear){
      let chartData = {
        labels: [],
        datasets: []
      };

      let datasetsMap = {};
    
      for (let val of data) {
        if(locationValue.length > 0 && !locationValue.includes(val.country)){
          continue;
        }

        if(typeValue.length > 0 && !typeValue.includes(val.event_type)){
          continue;
        }
        
        if(parseInt(val.fatalities) === 0){
          continue;
        }

        let dataset = {
          label: '',
          data: [],
          fill: false,
          borderColor: '#' + (Math.random()*0xFFFFFF<<0).toString(16)
        };

        // Type
        if (typeValue.length && !datasetsMap[val.event_type]) {
          let label = val.event_type;
          if(locationValue.length){
            label += ' in ' + locationValue;
          }
          dataset.label = label;
    
          datasetsMap[val.event_type] = dataset;
          chartData.datasets.push(dataset);
        }
    
        if(val.event_type in datasetsMap) {
          datasetsMap[val.event_type].data.push(val.fatalities);
        }

        // Location
        if (locationValue.length && !datasetsMap[val.country]) {
          let label = 'Incidents in '+ val.country;
          dataset.label = label;
    
          datasetsMap[val.country] = dataset;
          chartData.datasets.push(dataset);
        }

        if(val.country in datasetsMap) {
          datasetsMap[val.country].data.push(val.fatalities);
        }

        chartData.labels.push(val.year);
      }

      // Fix years.
      chartData.labels = [...new Set(chartData.labels)];
      chartData.labels.sort((a, b) => a - b);
      // let previousYear = (parseInt(chartData.labels[0]) - 1);
      // let nextYear = (parseInt(chartData.labels[chartData.labels.length - 1]) + 1);
      // chartData.labels.unshift(previousYear);
      // chartData.labels.push(nextYear);
      // console.log(chartData.labels);

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
                    type: 'linear',
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


    window.wpdp_maps = self.maps;

}( jQuery ) );
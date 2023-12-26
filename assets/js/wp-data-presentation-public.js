( function ( $ ) {
  'use strict';

    var self = {};
    var data = wpdp_data;
    var myChart;
    var global_markers = [];

    self.init = function(){
      self.dataTables();
      // self.excelTables();
      self.filters();
      self.filtersChange();
      
    },


    self.maps = function(typeValue = false , locationValue = false,fromYear = false,toYear = false){

      var mapData = [];
      for (let val of data) {
          if(locationValue.length > 0 && !locationValue.includes(val.country)){
            continue;
          }

          if(typeValue.length > 0 && !typeValue.includes(val.disorder_type)){
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
              type: val.disorder_type,
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
              { 
                extend: 'copyHtml5',
                exportOptions: {
                    columns: [0,1,2,3]
                }
            },
            {
                extend: 'excelHtml5',
                exportOptions: {
                    columns: [0,1,2,3]
                }
            },
            { 
              extend: 'csvHtml5',
              exportOptions: {
                  columns: [0,1,2,3]
              }
            },
            {
                extend: 'pdfHtml5',
                exportOptions: {
                    columns: [0,1,2,3]
                }
            },
            {
              extend: 'print',
              exportOptions: {
                  columns: [0,1,2,3]
              }
            }
            ],
            "columnDefs": [
              { "orderable": false, "targets": 4 }
            ],
            initComplete: function () {
              let count = 0;
              this.api().columns().every( function () {
                  var title = this.header();
                  //replace spaces with dashes
                  title = $(title).html().replace(/[\W]/g, '-');
                  var column = this;
                  var select = $('<select id="' + title + '" class="select2" ></select>')
                      .appendTo( $(column.footer()).empty() )
                      .on( 'change', function () {
                        //Get the "text" property from each selected data 
                        //regex escape the value and store in array
                        var data = $.map( $(this).select2('data'), function( value, key ) {
                          return value.text ? '^' + $.fn.dataTable.util.escapeRegex(value.text) + '$' : null;
                                   });
                        
                        //if no data selected use ""
                        if (data.length === 0) {
                          data = [""];
                        }
                        
                        //join array into string with regex or (|)
                        var val = data.join('|');
                        
                        //search for the option(s) selected
                        column
                              .search( val ? val : '', true, false )
                              .draw();
                      } );
   
                  column.data().unique().sort().each( function ( d, j ) {
                      select.append( '<option value="'+d+'">'+d+'</option>' );
                  } );
                
                //use column title as selector and placeholder
                $('#' + title).select2({
                  multiple: true,
                  closeOnSelect: false,
                  placeholder: "Select a " + title,
                  width: 'resolve',
                });
                
                //initially clear select otherwise first option is selected
                $('.select2').val(null).trigger('change');
              } );
          }

        });

        $('#wpdp_datatable tbody').on('click', 'button.more-info', function() {
            var tr = $(this).closest('tr');
            var row = table.row( tr );
    
            if ( row.child.isShown() ) {
                row.child.hide();
                tr.removeClass('shown');
            } else {
                row.child( format(row.selector.rows[0] ) ).show();
                tr.addClass('shown');
            }
        } );
    
        function format ( row ) {
          let event_type = $(row).find('td[event_type]').attr('event_type');
          let sub_event_type = $(row).find('td[sub_event_type]').attr('sub_event_type');
          let source = $(row).find('td[source]').attr('source');
          let notes = $(row).find('td[notes]').attr('notes');
          let timestamp = $(row).find('td[timestamp]').attr('timestamp');
          return `
          <ul class="wpdp_more_info">
              <li>
                <b>Event Type:</b>
                `+event_type+`
              </li>
              <li>
                <b>Sub Event Type:</b>
                `+sub_event_type+`
              </li>
              <li>
                <b>Source Type:</b>
                `+source+`
              </li>
              <li>
                <b>Notes:</b>
                `+notes+`
              </li>
              <li>
                <b>Timestamp:</b>
                `+timestamp+`
              </li>

              </ul>`;
        }
          


      }
    },

    self.filters = function(){
      $('.wpdp .filter').click(function(e){
        e.preventDefault();
        e.stopPropagation();
        $('.wpdp .con').css('left','0').addClass('active');
      });
    
      $('.wpdp .filter_back').click(function(e){
        e.preventDefault();
        $('.wpdp .con').css('left','-100%').removeClass('active');
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
          $('#wpdp_chart').show();
          $('#wpdp_chart_title').hide();
        }

        if (typeof google === 'object' && typeof google.maps === 'object') {
          for(let i=0; i<global_markers.length; i++){
            global_markers[i].setMap(null);
          }
          self.maps(typeValue, locationValue,fromYear,toYear);
        }

        if ($.fn.DataTable && $('#wpdp_datatable').length > 0) {
            $('#wpdp_datatable .type select').val(typeValue).trigger('change');
            $('#wpdp_datatable .location select').val(locationValue).trigger('change');
            $('#wpdp_datatable .date select').val(fromYear).trigger('change');
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

        if(typeValue.length > 0 && !typeValue.includes(val.disorder_type)){
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
        if (typeValue.length && !datasetsMap[val.disorder_type]) {
          let label = val.disorder_type;
          if(locationValue.length){
            label += ' in ' + locationValue;
          }
          dataset.label = label;
    
          datasetsMap[val.disorder_type] = dataset;
          chartData.datasets.push(dataset);
        }
    
        if(val.disorder_type in datasetsMap) {
          datasetsMap[val.disorder_type].data.push(val.fatalities);
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

      let ctx = document.getElementById('wpdp_chart').getContext('2d');
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
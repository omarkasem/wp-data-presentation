( function ( $ ) {
  'use strict';

    var self = {};
    var data = wpdp_data;
    var myChart;
    var global_markers = [];
    var markerCluster;

    self.init = function(){
      self.dataTables();
      self.menuFilters();
      self.filtersChange();

      $(document).on('click','.map_more_details button',function(e){
        e.preventDefault();
        let det = $(this).next().html();
        // Create modal elements
        let modal = $('<div>').addClass('wpdp_modal modal');
        let content = $('<div>').addClass('modal-content');
        let close = $('<button>').addClass('close-button').html('&times;');

        // Append elements
        content.append(close);
        content.append(det);
        modal.append(content);
        $('body').append(modal);

        // Show modal
        modal.show();

        // Hide modal when clicking on 'X' or outside modal
        close.click(function() {
            modal.hide();
        });

        $(window).click(function(event) {
            if ($(event.target)[0] == modal[0]) {
                modal.hide();
            }
        });
      });
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
          
          if(fromYear.length > 0){
            let date1 = new Date(fromYear);
            let date2 = new Date(val.event_date);
            if(date2.getTime() < date1.getTime()) {
              continue;
            }
          }

          if(toYear.length > 0){
            let date1 = new Date(toYear);
            let date2 = new Date(val.event_date);
            if(date2.getTime() > date1.getTime()) {
              continue;
            }
          }

          // if(parseInt(val.fatalities) === 0){
          //   continue;
          // }

          mapData.push({
              latitude: val.latitude,
              longitude: val.longitude,
              date: val.event_date,
              number: val.fatalities,
              type: val.disorder_type,
              location: val.country,
              timestamp: val.timestamp,
              event_type: val.event_type,
              sub_event_type: val.sub_event_type,
              source: val.source,
              notes: val.notes,
          });
      };
      
      if(!mapData.length){
        return;
      }
      var startLocation = { lat: parseFloat(mapData[0].latitude), lng: parseFloat(mapData[0].longitude) };
      
      if(!self.main_map){
        
        self.main_map = new google.maps.Map(
          document.getElementById('wpdp_map'),
          {
              zoom: 3, 
              center: startLocation,
              styles: [
                {
                    "featureType": "water",
                    "elementType": "geometry",
                    "stylers": [
                        {
                            "color": "#e9e9e9"
                        },
                        {
                            "lightness": 17
                        }
                    ]
                },
                {
                    "featureType": "landscape",
                    "elementType": "geometry",
                    "stylers": [
                        {
                            "color": "#f5f5f5"
                        },
                        {
                            "lightness": 20
                        }
                    ]
                },
                {
                    "featureType": "road.highway",
                    "elementType": "geometry.fill",
                    "stylers": [
                        {
                            "color": "#ffffff"
                        },
                        {
                            "lightness": 17
                        }
                    ]
                },
                {
                    "featureType": "road.highway",
                    "elementType": "geometry.stroke",
                    "stylers": [
                        {
                            "color": "#ffffff"
                        },
                        {
                            "lightness": 29
                        },
                        {
                            "weight": 0.2
                        }
                    ]
                },
                {
                    "featureType": "road.arterial",
                    "elementType": "geometry",
                    "stylers": [
                        {
                            "color": "#ffffff"
                        },
                        {
                            "lightness": 18
                        }
                    ]
                },
                {
                    "featureType": "road.local",
                    "elementType": "geometry",
                    "stylers": [
                        {
                            "color": "#ffffff"
                        },
                        {
                            "lightness": 16
                        }
                    ]
                },
                {
                    "featureType": "poi",
                    "elementType": "geometry",
                    "stylers": [
                        {
                            "color": "#f5f5f5"
                        },
                        {
                            "lightness": 21
                        }
                    ]
                },
                {
                    "featureType": "poi.park",
                    "elementType": "geometry",
                    "stylers": [
                        {
                            "color": "#dedede"
                        },
                        {
                            "lightness": 21
                        }
                    ]
                },
                {
                    "elementType": "labels.text.stroke",
                    "stylers": [
                        {
                            "visibility": "on"
                        },
                        {
                            "color": "#ffffff"
                        },
                        {
                            "lightness": 16
                        }
                    ]
                },
                {
                    "elementType": "labels.text.fill",
                    "stylers": [
                        {
                            "saturation": 36
                        },
                        {
                            "color": "#333333"
                        },
                        {
                            "lightness": 40
                        }
                    ]
                },
                {
                    "elementType": "labels.icon",
                    "stylers": [
                        {
                            "visibility": "off"
                        }
                    ]
                },
                {
                    "featureType": "transit",
                    "elementType": "geometry",
                    "stylers": [
                        {
                            "color": "#f2f2f2"
                        },
                        {
                            "lightness": 19
                        }
                    ]
                },
                {
                    "featureType": "administrative",
                    "elementType": "geometry.fill",
                    "stylers": [
                        {
                            "color": "#fefefe"
                        },
                        {
                            "lightness": 20
                        }
                    ]
                },
                {
                    "featureType": "administrative",
                    "elementType": "geometry.stroke",
                    "stylers": [
                        {
                            "color": "#fefefe"
                        },
                        {
                            "lightness": 17
                        },
                        {
                            "weight": 1.2
                        }
                    ]
                }
            ],
              mapTypeControl: false
          }
        );
          
      }

      self.originalZoom = self.main_map.getZoom();
      self.originalCenter = self.main_map.getCenter();


      var my_map = self.main_map;

      if(!self.svg_marker){     
        self.svg_marker = {
          path: "M-20,0a20,20 0 1,0 40,0a20,20 0 1,0 -40,0",
          fillColor: '#FF0000',
          fillOpacity: .6,
          anchor: new google.maps.Point(0,0),
          strokeWeight: 0,
          scale: .7
      
        };
      }
        
      var infoWindow = new google.maps.InfoWindow;

      mapData.forEach(function(loc) {
          var location = { lat: parseFloat(loc.latitude), lng: parseFloat(loc.longitude) };
          
          var marker = new google.maps.Marker({
              position: location, 
              map:self.main_map,
              icon: self.svg_marker
          });

          global_markers.push(marker);
          let timestamp = new Date(loc.timestamp * 1000);

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
                    <div class="map_more_details">
                      <button>More Details</button>
                      <div class="det">
                        <ul>
                          <li><b>Event Type:</b> ${loc.event_type}</li>
                          <li><b>Sub Event Type:</b> ${loc.sub_event_type}</li>
                          <li><b>Source:</b> ${loc.source}</li>
                          <li><b>Notes:</b> ${loc.notes}</li>
                          <li><b>Timestamp:</b> ${timestamp.toISOString()}</li>
                        </ul>
                      </div>
                    </div>
                </div>
            `);

            infoWindow.open(self.main_map, marker);
          }); 


          // Close the infoWindow when the map is clicked
          self.main_map.addListener('click', function() {
            infoWindow.close();
          });

      });

      markerCluster = new MarkerClusterer(my_map, global_markers, {
        // imagePath: 'https://developers.google.com/maps/documentation/javascript/examples/markerclusterer/m'
        imagePath: wpdp_obj.url+'assets/images/m'
      });

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
            
                // Get the existing filtering functionality.
                var title = this.header();
                title = $(title).html().replace(/[\W]/g, '-');
                var column = this;
                var select = $('<select id="' + title + '" class="select2" ></select>')
                  .appendTo( $(column.footer()).empty() )
                  .on( 'change', function () {
                    var data = $.map( $(this).select2('data'), function( value, key ) {
                      return value.text ? '^' + $.fn.dataTable.util.escapeRegex(value.text) + '$' : null;
                    });
                    if (data.length === 0) {
                      data = [""];
                    }
                    var val = data.join('|');

                    column.search( val ? val : '', true, false ).draw();
                  } );
            
                column.data().unique().sort().each( function ( d, j ) {
                  select.append( '<option value="'+d+'">'+d+'</option>' );
                } );
            
                $('#' + title).select2({
                  multiple: true,
                  closeOnSelect: false,
                  placeholder: "Select a " + title,
                  width: 'resolve',
                });
                $('.select2').val(null).trigger('change');
            
              });
            }

        });


        let minDate = $('#wpdp_min');
        let maxDate = $('#wpdp_max');
 
        DataTable.ext.search.push(function (settings, data, dataIndex) {
            let min = null;
            let max = null;

            if(minDate.val()){
              min = new Date(minDate.val());
            }
            if(maxDate.val()){
              max = new Date(maxDate.val());
            }

            let date = new Date(data[0]);

            if (
                (min === null && max === null) ||
                (min === null && date <= max) ||
                (min <= date && max === null) ||
                (min <= date && date <= max)
            ) {
                return true;
            }
            return false;
        });

        $('#wpdp_min, #wpdp_max').on('change',function(){
          table.draw();
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

    self.menuFilters = function(){
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
          markerCluster.clearMarkers();
          global_markers = [];

          self.main_map.setZoom(self.originalZoom);
          self.main_map.setCenter(self.originalCenter);

          self.maps(typeValue, locationValue,fromYear,toYear);
        }

        if ($.fn.DataTable && $('#wpdp_datatable').length > 0) {
            $('#wpdp_datatable .type select').val(typeValue).trigger('change');
            $('#wpdp_datatable .location select').val(locationValue).trigger('change');
            $('#wpdp_min').val(fromYear).trigger('change');
            $('#wpdp_max').val(toYear).trigger('change');
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
        
          
        if(fromYear.length > 0){
          let date1 = new Date(fromYear);
          let date2 = new Date(val.event_date);
          if(date2.getTime() < date1.getTime()) {
            continue;
          }
        }

        if(toYear.length > 0){
          let date1 = new Date(toYear);
          let date2 = new Date(val.event_date);
          if(date2.getTime() > date1.getTime()) {
            continue;
          }
        }


        // if(parseInt(val.fatalities) === 0){
        //   continue;
        // }

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

        chartData.labels.push(val.event_date);
      }


      chartData.labels.sort(function(a, b) {
        return new Date(a) - new Date(b);
      });


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
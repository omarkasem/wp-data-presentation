( function ( $ ) {
  'use strict';

    var self = {};
    var data = wpdp_data;
    var myChart;
    var table;
    var global_markers = [];
    var markerCluster;

    self.init = function(){

   

      self.dataTables();
      self.menuFilters();
      self.filtersChange();
      self.expandable();
      self.showMapDetails();
      // self.graphCountSelector();

    },

    self.graphCountSelector = function(){
      document.getElementById('wpdp_type_selector').addEventListener('change', function () {
        let val = this.value;
        let i = -1;
        for(let set of myChart.data.datasets){ i++;
          if(val == 'incident_count'){
            myChart.data.datasets[i].data = [...set.data].fill(1);
          } else {
            myChart.data.datasets[i].data = set.fat;
          }
        }
        myChart.update();
      });
    };

    self.showMapDetails = function(){
      $(document).on('click','.map_more_details span',function(e){
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

    self.expandable = function(){
      $(".wpdp .grp .title").click(function() {
        $(this).parent().toggleClass("active");
        $(this).find(".dashicons").toggleClass("dashicons-arrow-down-alt2 dashicons-arrow-up-alt2");
      });
    },

    self.maps = function(typeValue = false , selectedLocations = [],fromYear = false,toYear = false){
      var mapData = [];
      for (let val of data) {
        let all_locations = [
          val.region,
          val.country,
          val.admin1,
          val.admin2,
          val.admin3,
          val.location,
        ];
        
        if(selectedLocations.length > 0){
          let exist = false;
          for(let loc of selectedLocations){
            if(all_locations.includes(loc)){
              exist = true;
            }
          }
          if(!exist){
            continue;
          }
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
          
      }else{
        self.main_map.setCenter(startLocation);
        self.main_map.setZoom(3);
      }


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
                      <span style="cursor:pointer;color:#cd0202;font-size:25px;margin-top:3px;" class="dashicons dashicons-info"></span>
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
        var selectedLocations = [];
        $('input[type="checkbox"].wpdp_location:checked').each(function() {
            selectedLocations.push($(this).val());
        });
        self.table = $('#wpdp_datatable').DataTable({
            ajax: {
              "url": wpdp_obj.ajax_url,
              "type": "POST",
              "data": function ( d ) {
                d.action = 'wpdp_datatables_request';
                d.type_val = $('#wpdp_type').val();
                d.from_val = $('#wpdp_from').val();
                d.to_val = $('#wpdp_to').val();
                d.locations_val = selectedLocations;
              }
            },
            processing: true,
            serverSide: true,
            deferRender: true,
            pagingType: "full_numbers",
            lengthChange: false,
            pageLength: 20,
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
            "createdRow": function(row, data, dataIndex) {
              $('td:eq(4)', row).html('<span event_id="'+data[4]+'" style="cursor:pointer;color:#cd0202;font-size:26px;" class="more-info dashicons dashicons-info"></span>');
            },



        });


        // let minDate = $('#wpdp_min');
        // let maxDate = $('#wpdp_max');
 
        // DataTable.ext.search.push(function (settings, data, dataIndex) {
        //     let min = null;
        //     let max = null;

        //     if(minDate.val()){
        //       min = new Date(minDate.val());
        //     }
        //     if(maxDate.val()){
        //       max = new Date(maxDate.val());
        //     }

        //     let date = new Date(data[0]);

        //     if (
        //         (min === null && max === null) ||
        //         (min === null && date <= max) ||
        //         (min <= date && max === null) ||
        //         (min <= date && date <= max)
        //     ) {
        //         return true;
        //     }
        //     return false;
        // });

        // $('#wpdp_min, #wpdp_max').on('change',function(){
        //   table.draw();
        // });


      $('#wpdp_datatable tbody').on('click', 'span.more-info', function() {
          var tr = $(this).closest('tr');
          var row = self.table.row(tr);
      
          if (row.child.isShown()) {
              tr.next('tr').find('td').wrapInner('<div style="display: block;"></div>');
              tr.next('tr').find('td > div').slideUp(function() {
                 row.child.hide();
                 tr.removeClass('shown');
              });
          } else {
            format(row.selector.rows[0],row.child,tr)
          }
      });
    
      function format ( row, child, tr ) {
        let event_id = $(row).find('span[event_id]').attr('event_id');
        if(event_id == ''){
          return;
        }

        $.ajax({
            url: wpdp_obj.ajax_url,
            data: {
              action:'wpdp_datatables_find_by_id',
              event_id:event_id
            },
            type: 'POST',
            success: function(response) {
              
              var htmlContent = `
                <ul class="wpdp_more_info">
                    <li>
                      <b>Event Type:</b>
                      `+response.data[0].event_type+`
                    </li>
                    <li>
                      <b>Sub Event Type:</b>
                      `+response.data[0].sub_event_type+`
                    </li>
                    <li>
                      <b>Source Type:</b>
                      `+response.data[0].source+`
                    </li>
                    <li>
                      <b>Notes:</b>
                      `+response.data[0].notes+`
                    </li>
                    <li>
                      <b>Timestamp:</b>
                      `+response.data[0].timestamp+`
                    </li>
        
                  </ul>
              `;
                child(htmlContent).show();
                tr.next('tr').find('td').wrapInner('<div style="display: none;"></div>');
                tr.next('tr').find('td > div').slideDown();
                tr.addClass('shown');
            },
            error: function(errorThrown){
                alert('No data found');
            }
        });
      }
          
      }
    },

    self.menuFilters = function(){

      setTimeout(function() {
          $('.wpdp .filter_data').show();
      }, 1000);


      $('.filter_data li input[type="checkbox"]').on('change', function() {
          $(this).parent().find('li input[type="checkbox"]').prop('checked', this.checked);
      });



      $('.expandable > .exp_click').on('click', function(event) {
        event.stopPropagation();
        $(this).parent().toggleClass('expanded');
      });

      $('.filter_data li:not(:has(li))').find('.dashicons').remove();


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
      $('#wpdp_type, .wpdp_location,#wpdp_from,#wpdp_to').on('change select2:select select2:unselect',function(e){
        let typeValue = $("#wpdp_type").select2("val");
        let fromYear = $("#wpdp_from").select2("val");
        let toYear = $("#wpdp_to").select2("val");
        var selectedLocations = [];

        $('input[type="checkbox"].wpdp_location:checked').each(function() {
            selectedLocations.push($(this).val());
        });

        if (typeof Chart !== 'undefined') {
          if (myChart) {
            myChart.destroy();
          }
          myChart = self.graphChange(typeValue, selectedLocations,fromYear,toYear);
          $('#wpdp_chart').show();
          $('#wpdp_chart_title').hide();
        }

        if (typeof google === 'object' && typeof google.maps === 'object') {
          for(let i=0; i<global_markers.length; i++){
            global_markers[i].setMap(null);
          }
          markerCluster.clearMarkers();
          global_markers = [];

          self.maps(typeValue, selectedLocations,fromYear,toYear);
        }

        if ($.fn.DataTable && $('#wpdp_datatable').length > 0) {
          self.table.draw(false);
        }


      });
    },
      
    self.graphChange = function(typeValue, selectedLocations, fromYear,toYear){
      let chartData = {
        labels: [],
        datasets: []
      };

      let datasetsMap = {};
      data.sort(function(a, b) {
        return new Date(a.event_date) - new Date(b.event_date);
      });


      for (let val of data) {
        let all_locations = [
          val.region,
          val.country,
          val.admin1,
          val.admin2,
          val.admin3,
          val.location,
        ];
        
        if(selectedLocations.length > 0){
          let exist = false;
          for(let loc of selectedLocations){
            if(all_locations.includes(loc)){
              exist = true;
            }
          }
          if(!exist){
            continue;
          }
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


        let dataset = {
          label: '',
          data: [],
          fat:[],
          fill: false,
          borderColor: '#' + (Math.random()*0xFFFFFF<<0).toString(16)
        };

        // Type
        if (typeValue.length && !datasetsMap[val.disorder_type]) {
          let label = val.disorder_type;
          if(selectedLocations.length){
            label += ' in ' + selectedLocations[0];
          }
          dataset.label = label;
    
          datasetsMap[val.disorder_type] = dataset;
          chartData.datasets.push(dataset);
        }
    
        if(val.disorder_type in datasetsMap) {
          if($('#wpdp_type_selector').val() === 'incident_count'){
            datasetsMap[val.disorder_type].data.push(1);
          }else{
            datasetsMap[val.disorder_type].data.push(val.fatalities);
          }

            datasetsMap[val.disorder_type].fat.push(val.fatalities);
        }

        // Location
        if (selectedLocations.length && !datasetsMap[val.country]) {
          let label = 'Incidents in '+ val.country;
          dataset.label = label;
    
          datasetsMap[val.country] = dataset;
          chartData.datasets.push(dataset);
        }

        if(val.country in datasetsMap) {
          if($('#wpdp_type_selector').val() === 'incident_count'){
            datasetsMap[val.country].data.push(1);
          }else{
            datasetsMap[val.country].data.push(val.fatalities);
          }
          
            datasetsMap[val.country].fat.push(val.fatalities);
        }

        chartData.labels.push(val.event_date);
      }


      let ctx = document.getElementById('wpdp_chart').getContext('2d');
      var wpdp_chart =  new Chart(ctx, {
        type: 'line',
        data: chartData,
        options: {
            responsive: true,
            plugins: {
                tooltips: {
                  callbacks: {
                    title: function(tooltipItems, data) {
                      return ''; 
                    }
                  }
                },
                title: {
                    display: true,
                    text: 'Incidents by Type'
                },
            },
            scales: {
                x: {
                  type: 'timeseries',
                  time: {
                    unit: 'month',  
                    tooltipFormat: 'DD MMM YYYY'
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




      return wpdp_chart;
      
    }
    

    $( self.init );


    window.wpdp_maps = self.maps;

}( jQuery ) );
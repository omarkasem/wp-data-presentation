( function ( $ ) {
  'use strict';

    var self = {};
    var myChart;
    var table;
    var global_markers = [];
    var markerCluster;
    var selectedLocations = [];
    var selectedIncidents = [];

    self.init = function(){
      self.graphChange();
      self.dataTables();
      self.menuFilters();
      self.filtersChange();
      self.expandable();
      self.showMapDetails();
      self.graphCountSelector();
      self.datePicker();
      self.actors();
      self.checkbox();
    },

    self.checkfForIndeterminate = function(){
      setTimeout(() => {
        $('ul.first_one > li.expandable').each(function() {
          var $parentCheckbox = $(this).children('input[type="checkbox"]');
          var $childCheckboxes = $(this).find('ul input[type="checkbox"]');
          
          var allChecked = $childCheckboxes.length > 0 && $childCheckboxes.filter(':checked').length === $childCheckboxes.length;
          var someChecked = $childCheckboxes.filter(':checked').length > 0;
  
          if (allChecked) {
              $parentCheckbox.prop('indeterminate', false);
          } else if (someChecked) {
              $parentCheckbox.prop('indeterminate', true);
          } else {
              $parentCheckbox.prop('indeterminate', false);
          }
      });
  
      }, 100);
    }


    self.checkbox = function(){
      $('.filter_data li input[type="checkbox"]').on('change', function() {
        $(this).parent().find('li input[type="checkbox"]').prop('checked', this.checked).change();

        self.checkfForIndeterminate();
      });

      $('#filter_form').on('reset', function() {
        $(this).find('input[type="checkbox"]').prop('indeterminate', false);
      });



    };

    self.actors = function(){
      function updateIncidentTypes(triggerElem, action) {
        var values = $(triggerElem).val().split('+');
        values.forEach(function(value) {
            $('.wpdp_incident_type').each(function() {
                if ($(this).val() == value) {
                    $(this).prop('checked', action);
                }
            });
        });
      }

      $('.wpdp_actors, .wpdp_fat').change(function() {
          var isChecked = $(this).is(':checked');
          updateIncidentTypes(this, isChecked);
          self.checkfForIndeterminate();
      });
    };

    self.datePicker = function(){

      var minDate = new Date(wpdp_filter_dates[0]); 
      var maxDate = new Date(wpdp_filter_dates[wpdp_filter_dates.length - 1]);

      $('#wpdp_from').datepicker({
        minDate: minDate,
        maxDate: maxDate,
        dateFormat: 'dd MM yy',
        defaultDate: minDate,
        showButtonPanel: true,
        closeText: 'Clear',
        changeMonth: true,
        changeYear: true,
        yearRange: "c-100:c+100",
        onSelect: function(selectedDate) {
          if($('.content.filter_maps').length > 0){
            var endDate = new Date(selectedDate);
            endDate.setDate(endDate.getDate() + 1);
            endDate.setFullYear(endDate.getFullYear() + 1);
      
            var currentToDate = $('#wpdp_to').datepicker('getDate');
            if (currentToDate < new Date(selectedDate) || currentToDate > endDate) {
              $('#wpdp_to').datepicker('setDate', endDate);
            }
          }
        },
        beforeShow: function (input, inst) {
          setTimeout(function () {
              var clearButton = $(input)
                  .datepicker("widget")
                  .find(".ui-datepicker-close");
              clearButton.unbind("click").bind("click", function () {
                  $.datepicker._clearDate(input);
              });
          }, 1);
        }

      });
      
      $('#wpdp_to').datepicker({
        minDate: minDate,
        maxDate: maxDate,
        dateFormat: 'dd MM yy',
        defaultDate: maxDate,
        changeMonth: true,
        showButtonPanel: true,
        closeText: 'Clear',
        changeYear: true,
        yearRange: "c-100:c+100",
        onSelect: function(selectedDate) {
          if($('.content.filter_maps').length > 0){
            var startDate = new Date(selectedDate);
            startDate.setDate(startDate.getDate() - 1);
            startDate.setFullYear(startDate.getFullYear() - 1);
      
            var currentFromDate = $('#wpdp_from').datepicker('getDate');
            if (currentFromDate > new Date(selectedDate) || currentFromDate < startDate) {
              $('#wpdp_from').datepicker('setDate', startDate);
            }
          }

        },
        beforeShow: function (input, inst) {
          setTimeout(function () {
              var clearButton = $(input)
                  .datepicker("widget")
                  .find(".ui-datepicker-close");
              clearButton.unbind("click").bind("click", function () {
                  $.datepicker._clearDate(input);
              });
          }, 1);
        }

      });

    },

    self.graphCountSelector = function(){
      if(document.getElementById('wpdp_type_selector')){
        document.getElementById('wpdp_type_selector').addEventListener('change', function () {
          if(self.myChart){
            let val = this.value;
            let i = -1;
            for(let set of self.myChart.data.datasets){ i++;
              if(val == 'incident_count'){
                self.myChart.data.datasets[i].data = self.myChart.data.datasets[i].count;
              } else {
                self.myChart.data.datasets[i].data = self.myChart.data.datasets[i].fat;
              }
            }
            self.myChart.update();
          }

        });
      }

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

    self.maps = function(){

      $('#wpdp-loader').css('display','flex');

      let fromYear = $("#wpdp_from").val();
      let toYear = $("#wpdp_to").val();

      if(fromYear == '' && JSON.parse(wpdp_shortcode_atts).from != ''){
        fromYear = JSON.parse(wpdp_shortcode_atts).from;
      }

      if(toYear == '' && JSON.parse(wpdp_shortcode_atts).to != ''){
        toYear = JSON.parse(wpdp_shortcode_atts).to;
      }

      self.selectedLocations = [];
      $('input[type="checkbox"].wpdp_location:checked').each(function() {
        var isChildChecked = $(this).closest('ul').parent().children('input[type="checkbox"].wpdp_location:checked').length > 0;
        if (!isChildChecked) {
          self.selectedLocations.push($(this).val());
        }
      });


      self.selectedIncidents = [];
      $('input[type="checkbox"].wpdp_incident_type:checked').each(function() {
        self.selectedIncidents.push($(this).val());
      });


      $.ajax({
        url: wpdp_obj.ajax_url,
        data: {
          action:'wpdp_map_request',
          type_val: self.selectedIncidents,
          locations_val: self.selectedLocations,
          from_val: fromYear,
          to_val: toYear
        },
        type: 'POST',
        success: function(response) {
          self.mapInit(response.data);
          $('#wpdp-loader').hide();

          $('.wpdp .con').css('left','-152%').removeClass('active');
          $('.wpdp .filter span').attr('class','fas fa-sliders-h');
        },
        error: function(errorThrown){
            alert('No data found');
        }
      });


    },

    self.mapInit = function(mapData){
      if (!mapData || !Array.isArray(mapData) || mapData.length === 0) {
        console.log('No valid map data available');
        return;
      }

      var startLocation = { lat: parseFloat(mapData[0].latitude), lng: parseFloat(mapData[0].longitude) };
      
      if(!self.main_map){
        
        self.main_map = new google.maps.Map(
          document.getElementById('wpdp_map'),
          {
              zoom: 3.3, 
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
      var global_markers = []; // Ensure global_markers is defined
      var seenLocations = {};
    
      mapData.forEach(function(loc) {
        var originalLocation = loc.latitude + ',' + loc.longitude;
        var offset = 0.0001; // Small offset value
    
        if (seenLocations[originalLocation]) {
          // Apply a small random offset to avoid overlapping
          loc.latitude = parseFloat(loc.latitude) + (Math.random() - 0.5) * offset;
          loc.longitude = parseFloat(loc.longitude) + (Math.random() - 0.5) * offset;
        } else {
          seenLocations[originalLocation] = true;
        }
    
        var location = { lat: parseFloat(loc.latitude), lng: parseFloat(loc.longitude) };
    
        var marker = new google.maps.Marker({
          position: location,
          map: self.main_map,
          icon: self.svg_marker
        });
    
        global_markers.push(marker);
        let timestamp = new Date(loc.timestamp * 1000);
    
        marker.addListener('click', function() {
          infoWindow.close();
          infoWindow.setContent(`
            <div style="color: #333; font-size: 16px; padding: 10px; line-height: 1.6; border: 2px solid #333; border-radius: 10px; background: #fff;">
              <h2 style="margin: 0 0 10px; font-size: 20px; border-bottom: 1px solid #333; padding-bottom: 5px;">
                ${loc.disorder_type}
              </h2>
              <p style="margin-bottom:0;"><strong>Number:</strong> ${loc.fatalities}</p>
              <p style="margin-bottom:0;"><strong>Date:</strong> ${loc.event_date}</p>
              <div class="map_more_details">
                <span style="cursor:pointer;color:#cd0202;font-size:25px;margin-top:3px;" class="dashicons dashicons-info"></span>
                <div class="det">
                  <ul>
                    <li><b>Event ID:</b> ${loc.event_id_cnty}</li>
                    <li><b>Event Type:</b> ${loc.event_type}</li>
                    <li><b>Sub Event Type:</b> ${loc.sub_event_type}</li>
                    <li><b>Source:</b> ${loc.source}</li>
                    <li><b>Full Location:</b> ${loc.region} ${loc.country} ${loc.admin1} ${loc.admin2} ${loc.admin3} ${loc.location}</li>
                    <li><b>Notes:</b> ${loc.notes}</li>
                    <li><b>Timestamp:</b> ${timestamp.toISOString()}</li>
                  </ul>
                </div>
              </div>
            </div>
          `);
          infoWindow.open(self.main_map, marker);
        });
    
        self.main_map.addListener('click', function() {
          infoWindow.close();
        });
      });
    
      markerCluster = new MarkerClusterer(my_map, global_markers, {
        imagePath: 'https://developers.google.com/maps/documentation/javascript/examples/markerclusterer/m'
        // imagePath: wpdp_obj.url+'assets/images/m'
      });

    },

    self.dataTables = function(){
      if ($.fn.DataTable && $('#wpdp_datatable').length > 0) {
        $('#wpdp-loader').css('display','flex');

        self.table = $('#wpdp_datatable').DataTable({
            ajax: {
              "url": wpdp_obj.ajax_url,
              "type": "POST",
              "data": function ( d ) {
                let wpdp_from = $('#wpdp_from').val();
                if(wpdp_from == '' && JSON.parse(wpdp_shortcode_atts).from != ''){
                  wpdp_from = JSON.parse(wpdp_shortcode_atts).from;
                }
        
                let wpdp_to = $('#wpdp_to').val();
                if(wpdp_to == '' && JSON.parse(wpdp_shortcode_atts).to != ''){
                  wpdp_to = JSON.parse(wpdp_shortcode_atts).to;
                }

                d.action = 'wpdp_datatables_request';
                d.type_val = self.selectedIncidents;
                d.from_val = wpdp_from;
                d.to_val = wpdp_to;
                d.locations_val = self.selectedLocations;
              }
            },
            drawCallback: function(settings, json) {
              if($('#wpdp_chart').length <= 0){
                $('#wpdp-loader').hide();
              }
              $('.wpdp .con').css('left','-152%').removeClass('active');
              $('.wpdp .filter span').attr('class','fas fa-sliders-h');
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
                },
				
				customize: function (doc) {
					// Get the chart as a base64 image
					var canvas = document.getElementById('wpdp_chart');
					var chartImage = canvas.toDataURL('image/png');
					
					doc.content.push({
						text: ' ',
						margin: [0, 10] // Adjust margin as needed for spacing
					});
					// Add the image to the PDF after the table
					doc.content.push({
						image: chartImage,
						width: 500 // Adjust the width as needed
					});
				}

			
            },
            {
              extend: 'print',
              exportOptions: {
                  columns: [0,1,2,3]
              },
				
				
				customize: function (win) {
					// Get the chart as a base64 image
					var canvas = document.getElementById('wpdp_chart');
					var chartImage = canvas.toDataURL('image/png');

					// Create an image element for the chart
					var img = $('<img>').attr('src', chartImage).css({
						width: '500px', // Adjust the width as needed
						marginTop: '10px' // Add some space before the chart image
					});

					// Append the chart image after the table
					$(win.document.body).find('table').after(img);
				}

				
				
            },
            {
              text: 'Filter',
              action: function ( e, dt, node, config ) {
                setTimeout(function() {
                    $('.wpdp .filter').trigger('click');
                }, 10);
              }
            },

            ],
            "columnDefs": [
              { "orderable": false, "targets": 4 },
              {
                "targets": 0,
                "render": function(data, type, row) {
                  if (type === 'display' || type === 'filter') {
                    var date = new Date(data);
                    let day = date.getDate().toString().padStart(2, '0');

                    return day + ' ' + date.toLocaleString('default', { month: 'short' }) + ' ' + date.getFullYear();
                  }
                  return data;
                }
              }
            ],
          
            "createdRow": function(row, data, dataIndex) {
              $('td:eq(4)', row).html('<span event_id="'+data[4]+'" style="cursor:pointer;color:#cd0202;font-size:26px;" class="more-info dashicons dashicons-info"></span>');
            },



        });


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
                      <b>Event ID:</b>
                      `+response.data[0].event_id_cnty+`
                    </li>
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
                      <b>Event Full Location:</b>
                      `+response.data[0].region+`
                      `+response.data[0].country+` 
                      `+response.data[0].admin1+` 
                      `+response.data[0].admin2+` 
                      `+response.data[0].admin3+` 
                      `+response.data[0].location+` 
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
      }, 500);




      $('.expandable > .exp_click').on('click', function(event) {
        event.stopPropagation();
        $(this).parent().toggleClass('expanded');
        $(this).find(".dashicons").toggleClass("dashicons-arrow-down-alt2 dashicons-arrow-up-alt2");
      });

      $('.filter_data li:not(:has(li))').find('.dashicons').remove();



      $(document).click(function(e) {
        if ($('#wpdp_from').datepicker('widget').is(':visible')) {
            // Don't do anything if datepicker is visible
            return;
        }
        if (!$(e.target).closest('.wpdp .con').length && 
            !$(e.target).hasClass('hasDatepicker') && 
            !$(e.target).closest('.ui-datepicker').length && 
            !$(e.target).hasClass('select2-selection__choice__remove') &&
            !$(e.target).hasClass('ui-datepicker-trigger')) {
            $('.wpdp .con').css('left','-152%').removeClass('active');
			      $('.wpdp .filter span').attr('class','fas fa-sliders-h');
        }
      });
    

    
      $('.wpdp .filter').click(function(e){
        e.preventDefault();
        e.stopPropagation();

        if($(this).find('span').hasClass('fa-close')){
          $('.wpdp .con').css('left','-152%').removeClass('active');
          $('.wpdp .filter span').attr('class','fas fa-sliders-h');
        }else{
          $('.wpdp .con').css('left','0').addClass('active');
          let that = $(this);
          setTimeout(function () {
            that.find('span').attr('class','fas fa-close');
          },200);
        }
      });
    

    },

    self.filtersChange = function() {
      $('.wpdp #filter_form').on('submit',function(e){
        e.preventDefault();
        $('#wpdp-loader').css('display','flex');
        self.filterAction();

      });
    },

    self.filterAction = function(){
      let fromYear = $("#wpdp_from").val();
      let toYear = $("#wpdp_to").val();
      let timeframe = ($("#wpdp_date_timeframe").val() ? $("#wpdp_date_timeframe").val() : 'yearly');

      if(fromYear == '' && JSON.parse(wpdp_shortcode_atts).from != ''){
        fromYear = JSON.parse(wpdp_shortcode_atts).from;
      }

      if(toYear == '' && JSON.parse(wpdp_shortcode_atts).to != ''){
        toYear = JSON.parse(wpdp_shortcode_atts).to;
      }

      self.selectedLocations = [];
      
      $('input[type="checkbox"].wpdp_location:checked').each(function() {
        var isChildChecked = $(this).closest('ul').parent().children('input[type="checkbox"].wpdp_location:checked').length > 0;
        if (!isChildChecked) {
          self.selectedLocations.push($(this).val());
        }
      });

      
      self.selectedIncidents = [];      
      $('input[type="checkbox"].wpdp_incident_type:checked').each(function() {
        self.selectedIncidents.push($(this).val());
      });


      self.graphChange(self.selectedIncidents, self.selectedLocations,timeframe);

      if (typeof google === 'object' && typeof google.maps === 'object') {
        for(let i=0; i<global_markers.length; i++){
          global_markers[i].setMap(null);
        }
        markerCluster.clearMarkers();
        global_markers = [];
        self.maps();
      }

      if ($.fn.DataTable && $('#wpdp_datatable').length > 0) {
        self.table.draw(false);
      }
    },
      
    self.graphChange = function(selectedIncidents=[], selectedLocations, timeframe){
      
      if (typeof Chart === 'undefined') {
        return;
      }

      $('#wpdp-loader').css('display','flex');

      if(selectedIncidents.length <= 0){
        // Select only parent checkboxes.
        $('ul > ul > li > input[type="checkbox"].wpdp_incident_type').each(function() {
          selectedIncidents.push($(this).val());
        });
      }


      let fromYear = $("#wpdp_from").val();
      let toYear = $("#wpdp_to").val();

      if(fromYear == '' && JSON.parse(wpdp_shortcode_atts).from != ''){
        fromYear = JSON.parse(wpdp_shortcode_atts).from;
      }

      if(toYear == '' && JSON.parse(wpdp_shortcode_atts).to != ''){
        toYear = JSON.parse(wpdp_shortcode_atts).to;
      }



      $.ajax({
        url: wpdp_obj.ajax_url,
        data: {
          action:'wpdp_graph_request',
          type_val: selectedIncidents,
          locations_val: selectedLocations,
          from_val: fromYear,
          to_val: toYear,
          timeframe: timeframe
        },
        type: 'POST',
        success: function(response) {
          self.chartInit(response.data,selectedIncidents,selectedLocations,timeframe);
          $('#wpdp-loader').hide();
          $('.wpdp .con').css('left','-152%').removeClass('active');
          $('.wpdp .filter span').attr('class','fas fa-sliders-h');
        },
        error: function(errorThrown){
            alert('No data found');
        }
      });
    }

    self.chartInit = function(data,typeValue,selectedLocations,timeframe){
      var datasets = [];
      const colors = [
        "#e6194b", "#3cb44b", "#ffe119", "#4363d8", "#f58231", "#911eb4", "#46f0f0", "#f032e6",
        "#bcf60c", "#fabebe", "#008080", "#e6beff", "#9a6324", "#fffac8", "#800000", "#aaffc3",
        "#808000", "#ffd8b1", "#000075", "#808080", "#ffffff", "#000000", "#ff4500", "#00ff00",
        "#ffa500", "#7fffd4", "#8a2be2", "#ff69b4", "#ff1493", "#4b0082", "#00ff7f", "#ff6347"
      ];
    
      var chart_sql = data.chart_sql;
      data = data.data;
      let i =0;
      for(let label in data){ i++;
        let dataset = {
          label:label,
          borderColor: colors[i],
          fill: false,
          data: [],
          count:[],
          fat:[],
        };


        for(let val of data[label]){
          if($('#wpdp_type_selector').val() === 'incident_count'){
            dataset.data.push({x: val.week_start, y: val.events_count});
          }else{
            dataset.data.push({x: val.week_start, y: val.fatalities_count});
          }
          dataset.fat.push({x: val.week_start, y: val.fatalities_count});
          dataset.count.push({x: val.week_start, y: val.events_count});
        }

        datasets.push(dataset);
      }


      if (self.myChart) {
        self.myChart.destroy();
      }
      
      if(!document.getElementById('wpdp_chart')){
		    return;  
	    }
      
	  let ctx = document.getElementById('wpdp_chart').getContext('2d');
      
      self.myChart = new Chart(ctx, {
        type: 'line',
        data: {datasets:datasets},
        options: {
          responsive: true,
          maintainAspectRatio: true,
          plugins: {
            tooltip: {
                callbacks: {
                    title: function(tooltipItems) {
                        var date = new Date(tooltipItems[0].parsed.x);
                        var monthNames = ["January", "February", "March", "April", "May", "June",
                                          "July", "August", "September", "October", "November", "December"];
                        return monthNames[date.getMonth()] + ' ' + date.getFullYear();
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
                type: 'time',
                time: {
                  unit: chart_sql,
                }
              },
              y:{
                type: 'linear',
                display: true,
                beginAtZero: true
              }
          },
        

        }
      });
      

    }
    

    $( self.init );


    window.wpdp_maps = self.maps;

}( jQuery ) );
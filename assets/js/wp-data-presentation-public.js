( function ( $ ) {
  'use strict';

    var self = {};
    var myChart;
    var myChartFat;
    var table;
    var global_markers = [];
    var markerCluster;
    var selectedLocations = [];
    var selectedIncidents = [];
    var selectedActorNames = [];
    var selectedActors = [];
    var selectedFat = [];
    var selectedGraphFat = [];
    var fromYear = '';
    var toYear = '';
    var timeframe = '';
    var targetCiv = '';
    const interLabels = {
      1: "State Forces",
      2: "Rebel Groups",
      3: "Political Militias",
      4: "Identity Militias",
      5: "Rioters",
      6: "Protesters",
      7: "Civilians",
      8: "External/Other Force"
    };

    self.init = function(){
      self.currentMapType = $('input[name="wpdp_search_location_country"]').length > 0 ? 
      ($('input[name="wpdp_search_location_country"]').val() == '' ? 'polygons' : 'points') : 'points';
      

      self.setDefaultFilters();
      self.dataTables();
      self.graphChange();
      self.menuFilters();
      self.filtersChange();
      self.expandable();
      self.showMapDetails();
      self.datePicker();
      self.checkbox();
      self.checkfForIndeterminate();
      self.select2();
      self.tippy();
    },

    self.tippy = function(){
      tippy('.tippy-icon', {
        trigger: 'click',
      });
    },

    self.setDefaultFilters = function(){
      selectedLocations = [];
      selectedIncidents = [];
      selectedActors = [];
      selectedActorNames = [];
      selectedFat = [];
      selectedGraphFat = [];
      fromYear = '';
      toYear = '';
      timeframe = '';
      targetCiv = '';

      fromYear = $("#wpdp_from").val();
      toYear = $("#wpdp_to").val();

      targetCiv = $('input[type="radio"][name="target_civ"]:checked').val();

      if(fromYear == '' && JSON.parse(wpdp_shortcode_atts).from != ''){
        fromYear = JSON.parse(wpdp_shortcode_atts).from;
      }

      if(toYear == '' && JSON.parse(wpdp_shortcode_atts).to != ''){
        toYear = JSON.parse(wpdp_shortcode_atts).to;
      }

      timeframe = $("#wpdp_date_timeframe").val();


      $('input[type="checkbox"].wpdp_location:checked').each(function() {
        var isChildChecked = $(this).closest('ul').parent().children('input[type="checkbox"].wpdp_location:checked').length > 0;

        if (!isChildChecked) {
          selectedLocations.push($(this).val());
        }
      });

      if($('#wpdp_search_location').val() && $('#wpdp_search_location').val().length > 0){
        selectedLocations = selectedLocations.concat($('#wpdp_search_location').val());
      }

      selectedIncidents = self.checkedSelector('wpdp_incident_type');

      selectedActors = self.checkedSelector('wpdp_actors');

      selectedActorNames = $('#wpdp_search_actors').val();

      $('input[type="checkbox"].wpdp_fat:checked').each(function() {
        selectedFat.push($(this).val());
        selectedGraphFat.push($(this).val());
      });


    },

    self.checkedSelector = function(className){
      var totalActors = $('input[type="checkbox"].' + className).length;
      var checkedActors = $('input[type="checkbox"].' + className + ':checked').length;
      var selected = [];

      // if (totalActors === checkedActors) {
      //   selected = [];
      // } else {
      //   $('input[type="checkbox"].' + className + ':checked').each(function() {
      //     selected.push($(this).val());
      //   });
      // }

      $('input[type="checkbox"].' + className + ':checked').each(function() {
        selected.push($(this).val());
      });

      return selected;

    },

    self.select2 = function(){
      $('#wpdp_search_location').select2({
        multiple: true,
        ajax: {
          url: wpdp_obj.ajax_url,
          dataType: 'json',
          delay: 250,
          data: function (params) {
            return {
              search: params.term,
              action: 'search_location',
              country: $('input[name="wpdp_search_location_country"]').length ? $('input[name="wpdp_search_location_country"]').val() : ''
            };
          },
          processResults: function (data) {
            // Sort the results alphabetically by location
            data.sort(function(a, b) {
              var textA = a.location.toUpperCase();
              var textB = b.location.toUpperCase();
              return (textA < textB) ? -1 : (textA > textB) ? 1 : 0;
            });
            return {
              results: $.map(data, function(item) {
                return {
                  id: item.id,
                  text: item.location === item.country ? item.location : item.location + ' (' + item.country + ')'
                };
              })
            };
          },
          cache: true
        },
        minimumInputLength: 3,
        placeholder: 'Search location',
        width: 'element',
        dropdownParent: $('#wpdp_search_location').parent()
      });

      $('#wpdp_search_actors').select2({
        multiple: true,
        minimumInputLength: 3,
        ajax: {
          url: wpdp_obj.ajax_url,
          dataType: 'json',
          delay: 250,
          data: function (params) {
            return {
              search: params.term,
              action: 'search_actor_names'
            };
          },
          processResults: function (data) {
            return {
              results: $.map(data, function(item) {
                return {
                  id: item.id,
                  text: item.text
                };
              })
            };
          },
          cache: true
        },
        placeholder: 'Search Actor Name',
        width: 'element',
        dropdownParent: $('#wpdp_search_actors').parent()
      });

    },

    self.checkfForIndeterminate = function(){
      setTimeout(() => {
        $('ul.first_one li.expandable:has(ul li)').each(function() {
          var $parentCheckbox = $(this).children('input[type="checkbox"]');
          var $childCheckboxes = $(this).find('ul input[type="checkbox"]');
          
          var allChecked = $childCheckboxes.length > 0 && $childCheckboxes.filter(':checked').length === $childCheckboxes.length;
          var someChecked = $childCheckboxes.filter(':checked').length > 0;
  
          if (allChecked) {
              $parentCheckbox.prop('indeterminate', false);
              $parentCheckbox.prop('checked', true);
          } else if (someChecked) {
              $parentCheckbox.prop('indeterminate', true);
          } else {
              $parentCheckbox.prop('indeterminate', false);
          }
      });
  
      }, 100);
    }


    self.checkbox = function(){
      $('.filter_data li input[type="checkbox"]:not(.select_unselect_all)').on('change', function() {
        $('.filter_data .no_data').hide();
        self.checkfForIndeterminate();
      });

      $('.filter_data li input[type="checkbox"]').not('.wpdp_location, .select_unselect_all').on('change', function() {
        $(this).parent().find('li input[type="checkbox"]').prop('checked', this.checked).change();
      });

      // Reset - Updated version
      $('#filter_form').on('reset', function(e) {
        // Prevent the default reset behavior
        e.preventDefault();
        
        // Set default dates based on map existence
        var maxDate = new Date();
        var defaultFromDate = new Date();
        if (typeof google === 'object' && typeof google.maps === 'object') {
          // If maps exist, set from date to 1 month ago
          defaultFromDate.setMonth(defaultFromDate.getMonth() - 1);
        } else {
          // If no maps, set from date to 1 year ago
          defaultFromDate.setFullYear(defaultFromDate.getFullYear() - 1);
        }

        // Set the datepicker values
        $('#wpdp_from').datepicker('setDate', defaultFromDate);
        $('#wpdp_to').datepicker('setDate', maxDate);

        // First uncheck all checkboxes
        $(this).find('input[type="checkbox"]').prop('checked', false);
        
        // Then check only top-level checkboxes (direct children of first_one class)
        $('.first_one > li > input[type="checkbox"]').prop('checked', true);

        
        // Clear select2 fields
        $('#wpdp_search_location').val('').trigger('change');
        $('#wpdp_search_actors').val('').trigger('change');
        
        // Clear regular select fields
        $(this).find('select').val('').trigger('change');
        
        // Hide no data message
        $('.filter_data .no_data').hide();

        
        // Force recheck indeterminate states
        self.checkfForIndeterminate();

        // Trigger form change to update any dependent elements
        $(this).trigger('change');
      });

      // Select/Unselect All
      $(document).on('click', 'li a.select_unselect_all', function(e) {
        e.preventDefault();
        var $content = $(this).closest('.content');
        
        requestAnimationFrame(function() {
          var checkboxes;
          if ($content.closest('.grp').hasClass('locations')) {
            checkboxes = $content.find('li input[type="checkbox"]');
            if(!checkboxes.length){
              checkboxes = $content.find('.checkboxes_locations > ul > li > input[type="checkbox"]');
            }
          } else {
            checkboxes = $content.find('input[type="checkbox"]');
          }

          // Count total and checked checkboxes
          var totalChecked = checkboxes.filter(':checked').length;
          var shouldCheck = totalChecked < checkboxes.length;

          // Set all checkboxes to either checked or unchecked
          checkboxes.each(function(index, checkbox) {
            checkbox.checked = shouldCheck;
            checkbox.indeterminate = false;
          });
        });
      });

      // Selected country hidden field
      $(document).on('change', 'input[name="wpdp_country"]:radio', function() {
        $('input[name="wpdp_search_location_country"]').val($(this).val());
      });

      // View countries button
      $('.view_countries').on('click', function() {
        $('input[name="wpdp_search_location_country"]').val('').parents('form').submit();
      });

    };


    self.datePicker = function(){

      var minDate = new Date(wpdp_filter_dates[0]); 
      var maxDate = new Date();

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
          $('#wpdp_from').addClass('changed');
          $('.filter_data .no_data').hide();
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
          $('#wpdp_to').addClass('changed');
          $('.filter_data .no_data').hide();
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

      var selectedCountry = $('input[name="wpdp_search_location_country"]').length ? $('input[name="wpdp_search_location_country"]').val() : '';

      $('#wpdp-loader').css('display','flex');
      self.setDefaultFilters();
      $.ajax({
        url: wpdp_obj.ajax_url,
        data: {
          action:'wpdp_map_request',
          type_val: selectedIncidents,
          locations_val: selectedLocations,
          actors_val: selectedActors,
          actors_names_val: selectedActorNames,
          fat_val: selectedFat,
          from_val: fromYear,
          to_val: toYear,
          target_civ: targetCiv,
          selected_country: selectedCountry
        },
        type: 'POST',
        success: function(response) {

          self.mapInit(response.data.data);
          $('#wpdp-loader').hide();

          // $('.wpdp .con').css('left','-152%').removeClass('active');
          // $('.wpdp .filter span').attr('class','fas fa-sliders-h');
          if(response.data.count == 0){
            $('.filter_data .no_data').show();
          }
        },
        error: function(errorThrown){
            alert('No data found');
        }
      });


    },


    self.getCenterLocation = function(mapData) {
      let totalLat = 0;
      let totalLng = 0;
      let count = mapData.length;
    
      mapData.forEach(function(loc) {
        totalLat += parseFloat(loc.latitude);
        totalLng += parseFloat(loc.longitude);
      });
      
      var lat = (totalLng / count);

      if($('.wpdp .filter').find('span').hasClass('fa-arrow-left')){
        lat = lat + 12;
      }

      return {
        lat: totalLat / count,
        lng: lat
      };
    },

    self.mapsStyles = function(){
      return [
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
    ];
    }

    self.mapInit = function(mapData){
      if (!mapData || !Array.isArray(mapData) || mapData.length === 0) {
        console.log('No valid map data available');
        return;
      }

      var centerLocation = self.getCenterLocation(mapData);
      var zoom = 3.8;

      if($('input[name="wpdp_search_location_country"]').length){
        zoom = 5;
      }

      if(!self.main_map){
        
        self.main_map = new google.maps.Map(
          document.getElementById('wpdp_map'),
          {
              zoom: zoom, 
              center: centerLocation,
              styles: self.mapsStyles(),
              mapTypeControl: false
          }
        );
          
      }else{
        self.main_map.setCenter(centerLocation);
        self.main_map.setZoom(zoom);
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
        var offset = 0.0009; // Small offset value
    
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
        if (loc.inter1 && interLabels[loc.inter1]) {
          loc.inter1 = interLabels[loc.inter1] + ' ( '+loc.actor1+' )';
        }

        if (loc.inter2 && interLabels[loc.inter2]) {
          loc.inter2 = interLabels[loc.inter2] + ' ( '+loc.actor2+' )';
        }

        marker.addListener('click', function() {
          infoWindow.close();
          let locationString = loc.region+', '+loc.country+', '+loc.admin1+', '+loc.admin2+', '+loc.admin3+', '+loc.location;
          locationString = locationString.replace(', ,', ',');
          infoWindow.setContent(`
            <div style="color: #333; font-size: 16px; padding: 10px; line-height: 1.6; border: 2px solid #333; border-radius: 10px; background: #fff;">
              <h2 style="margin: 0 0 10px; font-size: 20px; border-bottom: 1px solid #333; padding-bottom: 5px;">
                ${loc.disorder_type}
              </h2>
              <p style="margin-bottom:0;"><strong>Fatalities:</strong> ${loc.fatalities}`+(loc.fatalities > 0 ? ' From '+loc.event_type+'' : '')+`</p>
             
              <p style="margin-bottom:0;"><strong>Date:</strong> ${loc.event_date}</p>
              <div class="map_more_details">
                <span style="cursor:pointer;color:#cd0202;font-size:25px;margin-top:3px;" class="dashicons dashicons-info"></span>
                <div class="det">
                  <ul class="wpdp_more_info_map">
                    <li><b>Event ID:</b> <span>${loc.event_id_cnty}</span></li>
                    <li><b>Event Type:</b> <span>${loc.event_type}</span></li>
                    <li><b>Actor 1:</b> <span>${loc.inter1}</span></li>
                    ${loc.inter2 ? `<li><b>Actor 2:</b> <span>${loc.inter2 == '0' ? 'N/A' : loc.inter2}</span></li>` : ''}
                    <li><b>Sub Event Type:</b> <span>${loc.sub_event_type}</span></li>
                    <li><b>Source:</b> <span>${loc.source}</span></li>
                    <li><b>Location:</b> <span>${locationString}</span></li>
                    <li class="notes"><b>Notes:</b> <span>${loc.notes}</span></li>
                    <li><b>Timestamp:</b> <span>${timestamp.toISOString()}</span></li>
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
                d.action = 'wpdp_datatables_request';
                d.type_val = selectedIncidents;
                d.from_val = fromYear;
                d.actors_val = selectedActors;
                d.actor_names_val = selectedActorNames;
                d.fat_val = selectedFat;
                d.to_val = toYear;
                d.locations_val = selectedLocations;
                d.target_civ = targetCiv;
              },
              "dataSrc": function(json) {
                if (!json.data || json.data.length === 0) {
                  $('.filter_data .no_data').show();
                  $('#wpdp-loader').hide();
                  return [];
                }
                return json.data;
              }
            },
            drawCallback: function(settings, json) {
              if($('#wpdp_chart').length <= 0){
                $('#wpdp-loader').hide();
              }
              // $('.wpdp .con').css('left','-152%').removeClass('active');
              // $('.wpdp .filter span').attr('class','fas fa-sliders-h');
            },

            processing: true,
            serverSide: true,
            searching: true,
            language: {
              searchPlaceholder: "Search Event ID",
              search: "" 
            },
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
                },
                action: function (e, dt, button, config) {
                  var selfy = this;
                  self.exportAllData(selfy, dt, button, config);
                }
              },
              {
                extend: 'excelHtml5',
                exportOptions: {
                    columns: [0,1,2,3]
                },
                action: function (e, dt, button, config) {
                  var selfy = this;
                  self.exportAllData(selfy, dt, button, config);
                }
              },
              { 
                extend: 'csvHtml5',
                exportOptions: {
                    columns: [0,1,2,3]
                },
                action: function (e, dt, button, config) {
                  var selfy = this;
                  self.exportAllData(selfy, dt, button, config);
                }
              },
              {
                extend: 'pdfHtml5',
                exportOptions: {
                    columns: [0,1,2,3]
                },
                action: function (e, dt, button, config) {
                  var selfy = this;
                  self.exportAllData(selfy, dt, button, config);
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

        $('#wpdp_datatable_filter label').css('position','relative').append(wpdp_obj.search_info_icon);


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

              if (response.data[0].inter1 && interLabels[response.data[0].inter1]) {
                response.data[0].inter1 = interLabels[response.data[0].inter1] + ' ( '+response.data[0].actor1+' )';
              }

              if (response.data[0].inter2 && interLabels[response.data[0].inter2]) {
                response.data[0].inter2 = interLabels[response.data[0].inter2] + ' ( '+response.data[0].actor2+' )';
              }

              let locationString = response.data[0].region+', '+response.data[0].country+', '+response.data[0].admin1+', '+response.data[0].admin2+', '+response.data[0].admin3+', '+response.data[0].location;
              locationString = locationString.replace(', ,', ',');
    
              
              var htmlContent = `
                <ul class="wpdp_more_info">
                    <li>
                      <b>Event ID:</b>
                      <span>`+response.data[0].event_id_cnty+`</span>
                    </li>
                    <li>
                      <b>Event Type:</b>
                      <span>`+response.data[0].event_type+`</span>
                    </li>
                    <li>
                      <b>Sub Event Type:</b>
                      <span>`+response.data[0].sub_event_type+`</span>
                    </li>
                    <li>
                      <b>Source Type:</b>
                      <span>`+response.data[0].source+`</span>
                    </li>
                    <li>
                      <b>Actor 1:</b>
                      <span>`+response.data[0].inter1+`</span>
                    </li>
                    `+(response.data[0].inter2 ? '<li><b>Actor 2: </b><span>'+(response.data[0].inter2 == '0' ? 'N/A' : response.data[0].inter2)+'</span></li>' : '')+`
                    <li>
                      <b>Fatalities:</b>
                      <span>`+(response.data[0].fatalities > 0 ? response.data[0].fatalities + ' from ' + response.data[0].event_type : response.data[0].fatalities)+`</span>
                    </li>
                    <li>
                      <b>Location:</b>
                      <span>`+locationString+`</span>
                    </li>
                    <li class="notes">
                      <b>Notes:</b>
                      <span>`+response.data[0].notes+`</span>
                    </li>
                    <li>
                      <b>Timestamp:</b>
                      <span>`+response.data[0].timestamp+`</span>
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

    // Add this new method to your self object
    self.exportAllData = function(selfy, dt, button, config) {

      var oldStart = dt.settings()[0]._iDisplayStart;
      var oldOrder = dt.order();  // Store the current order

      
      dt.one('preXhr', function (e, s, data) {
          // Just this once, load all data from the server...
          data.start = 0;
          data.length = 2147483647;
          
          dt.one('preDraw', function (e, settings) {
              // Call the original action function
              if (button[0].className.indexOf('buttons-copy') >= 0) {
                  $.fn.dataTable.ext.buttons.copyHtml5.action.call(selfy, e, dt, button, config);
              } else if (button[0].className.indexOf('buttons-excel') >= 0) {
                  $.fn.dataTable.ext.buttons.excelHtml5.available(dt, config) ?
                      $.fn.dataTable.ext.buttons.excelHtml5.action.call(selfy, e, dt, button, config) :
                      $.fn.dataTable.ext.buttons.excelFlash.action.call(selfy, e, dt, button, config);
              } else if (button[0].className.indexOf('buttons-csv') >= 0) {
                  $.fn.dataTable.ext.buttons.csvHtml5.available(dt, config) ?
                      $.fn.dataTable.ext.buttons.csvHtml5.action.call(selfy, e, dt, button, config) :
                      $.fn.dataTable.ext.buttons.csvFlash.action.call(selfy, e, dt, button, config);
              } else if (button[0].className.indexOf('buttons-pdf') >= 0) {
                  $.fn.dataTable.ext.buttons.pdfHtml5.available(dt, config) ?
                      $.fn.dataTable.ext.buttons.pdfHtml5.action.call(selfy, e, dt, button, config) :
                      $.fn.dataTable.ext.buttons.pdfFlash.action.call(selfy, e, dt, button, config);
              } else if (button[0].className.indexOf('buttons-print') >= 0) {
                  $.fn.dataTable.ext.buttons.print.action(e, dt, button, config);
              }
              dt.one('preXhr', function (e, s, data) {
                  // DataTables thinks the first item displayed is index 0, but we're not drawing that.
                  // Set the property to what it was before exporting.
                  settings._iDisplayStart = oldStart;
                  data.start = oldStart;
              });
              // Reload the grid with the original page. Otherwise, API functions like table.cell(this) don't work properly.
              setTimeout(dt.ajax.reload, 0);
              // Prevent rendering of the full data to the DOM
              return false;
          });
      });
      // Requery the server with the new one-time export settings
      dt.ajax.reload();
    },


    self.menuFilters = function(){

      setTimeout(function() {
          $('.wpdp .filter_data').show();
      }, 500);

      if ($(window).width() <= 1200) {
        $('.wpdp .filter span').removeClass('fa-arrow-left').addClass('fa-sliders-h');
      }

      $('.filter_data .grp:not(.locations) li:not(:has(li))').find('.dashicons').remove();


      $(document).click(function(e) {
        if ($('#wpdp_from').datepicker('widget').is(':visible') ||
            $(e.target).closest('.select2-container').length) {
            // Don't do anything if datepicker or select2 dropdown is visible
            return;
        }
        // if (!$(e.target).closest('.wpdp .con').length && 
        //     !$(e.target).hasClass('hasDatepicker') && 
        //     !$(e.target).closest('.ui-datepicker').length && 
        //     !$(e.target).hasClass('select2-selection__choice__remove') &&
        //     !$(e.target).hasClass('ui-datepicker-trigger') &&
        //     !$(e.target).closest('.select2-selection').length) {
        //     $('.wpdp .con').css('left','-152%').removeClass('active');
        //     $('.wpdp .filter span').attr('class','fas fa-sliders-h');
        // }
      });

    
      $('.wpdp .filter').click(function(e){
        e.preventDefault();
        e.stopPropagation();

        if($(this).find('span').hasClass('fa-arrow-left')){
          // Closing filter menu
          $('.wpdp .con').animate({marginLeft:'-270px'},1).hide().removeClass('active');
          $('.wpdp .filter span').attr('class','fas fa-sliders-h');
          $('.wpdp_filter_content').animate({marginLeft:'0',width:'100%'},200, function() {
            // After animation, shift center point left to compensate for closed menu
            if (self.main_map) {
              var center = self.main_map.getCenter();
              if (center) {
                self.main_map.setCenter(new google.maps.LatLng(
                  center.lat(),
                  center.lng() - 12 // Adjust this value to shift more/less left
                ));
              }
            }


            if (self.poly_map) {
              var center = self.poly_map.getCenter();
              if (center) {
                self.poly_map.setCenter(new google.maps.LatLng(
                  center.lat(),
                  center.lng() - 28 // Adjust this value to shift more/less left
                ));
              }
            }

          });
          $('#map-info-panel').css('right','60px');
        } else {
          // Opening filter menu
          $('.wpdp .con').animate({marginLeft:'0'},200).show().addClass('active');
          $(this).find('span').attr('class','fas fa-arrow-left');
          $('#map-info-panel').css('right','290px');
          if ($('.wpdp_filter_content').hasClass('maps')) {
              $('.wpdp_filter_content').animate({marginLeft:'270px',width:'100%'},200, function() {
                // After animation, shift center point right to compensate for opened menu
                if (self.main_map) {
                  var center = self.main_map.getCenter();
                  if (center) {
                    self.main_map.setCenter(new google.maps.LatLng(
                      center.lat(),
                      center.lng() + 12 // Adjust this value to shift more/less right
                    ));
                  }
                }

                if (self.poly_map) {
                  var center = self.poly_map.getCenter();
                  if (center) {
                    self.poly_map.setCenter(new google.maps.LatLng(
                      center.lat(),
                      center.lng() + 28 // Adjust this value to shift more/less right
                    ));
                  }
                }

              });
          } else {
              $('.wpdp_filter_content').animate({marginLeft:'270px',width:'80%'},200);
          }
        }
      });
    

    },

    self.filtersChange = function() {
      $('.wpdp #filter_form').on('submit',function(e){
        e.preventDefault();
        $('#wpdp-loader').css('display','flex');
        self.filterAction();

        // Session save.
        var filterData = {};
        $(this).find('input, select').each(function() {
            var $this = $(this);
            if ($this.is(':checkbox') || $this.is(':radio')) {
                if ($this.is(':checked')) {
                    filterData[$this.attr('name')] = $this.val();
                }
            } else {
                if ($this.val() !== '') {
                  if($this.attr('name') == 'wpdp_from' && !$('#wpdp_from').hasClass('changed')){
                    return true;
                  }
                  if($this.attr('name') == 'wpdp_to' && !$('#wpdp_to').hasClass('changed')){
                    return true;
                  }
                  filterData[$this.attr('name')] = $this.val();
                }
            }
        });

        $.ajax({
            url: wpdp_obj.ajax_url,
            type: 'POST',
            data: {
                action: 'save_filter_choices',
                filter_data: filterData
            },
            success: function(response) {
                if (response.success) {

                } else {
                    console.error('Failed to save filter choices');
                }
            }
        });
      });
    },

    self.filterAction = function(){

      self.setDefaultFilters();

      if ($.fn.DataTable && $('#wpdp_datatable').length > 0) {
        self.table.draw(false);
      }

      self.graphChange();

      if (typeof google === 'object' && typeof google.maps === 'object') {
        self.mapChange();
      }


    },

    self.mapChange = function(){
      let newMapType = $('input[name="wpdp_search_location_country"]').length > 0 ? 
      ($('input[name="wpdp_search_location_country"]').val() == '' ? 'polygons' : 'points') : 'points';
  

      // Only run AJAX if map type has changed
      if (newMapType !== self.currentMapType) {
        $('.checkboxes_locations').html('Loading Locations...');
        $.ajax({
          url: wpdp_obj.ajax_url,
          type: 'POST',
          data: {
            action: 'get_locations_html',
            'search_location_country': newMapType === 'polygons' ? '' : $('input[name="wpdp_search_location_country"]').val(),
            'atts': wpdp_shortcode_atts
          },
          success: function(response){
            $('.checkboxes_locations').html(response);
          }
        });
        self.currentMapType = newMapType;
      }

      if(newMapType === 'polygons') {
        $('#wpdp_map').hide();
        $('#polygons_map').show();
        $('.wpdp_maps_only').hide();
        self.maps_polygons();
      } else {
        $('.wpdp_maps_only').show().css('display','inline-block');
        $('#polygons_map').hide();
        $('#wpdp_map').show();

        for(let i=0; i<global_markers.length; i++){
          global_markers[i].setMap(null);
        }
        if(markerCluster){
          markerCluster.clearMarkers();
        }
        global_markers = [];
        self.maps();
      }
    },
      
    self.graphChange = function(){
      
      if (typeof Chart === 'undefined') {
        return;
      }

      $('#wpdp-loader').css('display','flex');

      let allIncidentsAndFatSelected = 0;

      if($('.grp.inident_type .content input:checked').not('.select_unselect_all').length === $('.grp.inident_type .content input').not('.select_unselect_all').length && $('.grp.fatalities .content input:checked').not('.select_unselect_all').length === $('.grp.fatalities .content input').not('.select_unselect_all').length){
        allIncidentsAndFatSelected = 1;
      }

      $.ajax({
        url: wpdp_obj.ajax_url,
        data: {
          action:'wpdp_graph_request',
          type_val: selectedIncidents,
          locations_val: selectedLocations,
          actors_val: selectedActors,
          actor_names_val: selectedActorNames,
          fat_val: selectedGraphFat,
          from_val: fromYear,
          to_val: toYear,
          timeframe: timeframe,
          all_selected: allIncidentsAndFatSelected,
          target_civ: targetCiv
        },
        type: 'POST',
        success: function(response) {

          // // Check if all datasets are empty
          // const hasIncidentData = Object.keys(response.data.data).some(key => response.data.data[key].length > 0);
          // const hasFatalityData = Object.keys(response.data.data_fat).some(key => response.data.data_fat[key].length > 0);
          // const hasActorData = Object.keys(response.data.data_actors).some(key => response.data.data_actors[key].length > 0);

          // // Hide loader
          // $('#wpdp-loader').hide();

          // // Handle incidents graph
          // if (!hasIncidentData) {
          //   $('#wpdp_chart').addClass('wpdp_force_hide').removeClass('wpdp_force_show');
          //   $('#wpdp_chart').after('<div class="no-chart-data-message" style="text-align: center; padding: 20px;">No incidents found. Please adjust the filter options to see more data.</div>');
          // } else {
          //   $('#wpdp_chart').removeClass('wpdp_force_hide').addClass('wpdp_force_show');
          //   $('.no-chart-data-message').remove();
          // }

          // // Handle fatalities graph
          // if (!hasFatalityData) {
          //   $('#wpdp_chart_fat').removeClass('wpdp_force_show').addClass('wpdp_force_hide');
          //   $('#wpdp_chart_fat').after('<div class="no-fat-data-message" style="text-align: center; padding: 20px;">No fatalities found. Please adjust the filter options to see more data.</div>');
          // } else {
          //   $('#wpdp_chart_fat').removeClass('wpdp_force_hide').addClass('wpdp_force_show');
          //   $('.no-fat-data-message').remove();
          // }

          // // Handle actors graph
          // if (!hasActorData) {
          //   $('#wpdp_chart_bar_chart').removeClass('wpdp_force_show').addClass('wpdp_force_hide');
          //   $('#wpdp_chart_bar_chart').after('<div class="no-actors-data-message" style="text-align: center; padding: 20px;">No actors found. Please adjust the filter options to see more data.</div>');
          // } else {
          //   $('#wpdp_chart_bar_chart').removeClass('wpdp_force_hide').addClass('wpdp_force_show');
          //   $('.no-actors-data-message').remove();
          // }
          // console.log(response.data);
          self.chartInit(response.data.data,response.data.data_fat,response.data.data_actors,response.data.chart_sql);
          $('#wpdp-loader').hide();

          if(response.data.count == 0){
            $('.filter_data .no_data').show();
          }
        },
        error: function(errorThrown){
            alert('No data found');
        }
      });
    }

    self.chartInit = function(data,data_fat,data_actors,chart_sql){
      var datasets = [];
      var datasets_fat = [];
      var datasets_actors = [];
      const colors = [
        '#4dc9f6',
        '#f67019',
        '#f53794',
        '#537bc4',
        '#acc236',
        '#166a8f',
        '#00a950',
        '#58595b',
        '#8549ba',
        '#ff9f40',
        '#ffcd56',
        '#36a2eb',
        '#9966ff',
        '#c9cbcf',
        '#ff6384',
        '#4bc0c0',
        '#ff9f40',
        '#ffcd56',
        '#36a2eb'
      ];

      let i =0;
      for(let label in data){ i++;
        let dataset = {
          label: label,
          borderColor: colors[i],
          fill: false,
          data: [],
          count: [],
          fat: []
        };

        for(let val in data[label]){
          dataset.data.push({
            x: val, 
            y: data[label][val].events_count
          });
        }
        datasets.push(dataset);
      }

      i =0;
      for(let label in data_actors){i++;
        let dataset_actors = {
          label:label,
          borderColor: colors[i],
          fill: false,
          data: [],
          count:[],
          fat:[],
        };

        for(let val in data_actors[label]){
          dataset_actors.data.push({
            x: val, 
            y: data_actors[label][val].events_count
          });
        }

        datasets_actors.push(dataset_actors);
      }

      i =0;
      for(let label in data_fat){ i++;
        let dataset_fat = {
          label:label,
          borderColor: colors[i],
          fill: false,
          data: [],
          count:[],
          fat:[],
        };

        for(let val in data_fat[label]){
          dataset_fat.data.push({
            x: val, 
            y: data_fat[label][val].events_count
          });
        }

        datasets_fat.push(dataset_fat);
      }


      if (window.myChart) {
        window.myChart.destroy();
      }

      if (window.myChartBarFat) {
        window.myChartBarFat.destroy();
      }

      if (window.myChartBar) {
        window.myChartBar.destroy();
      }
      
      if(!document.getElementById('wpdp_chart')){
        return;  
      }

      let title_text = 'Events in all ICGLR Member States';
      if(selectedLocations.length > 0){
        title_text = 'Events in ';
        let countries = new Set();
        $.each(selectedLocations,function(index,value){
          value = value.split('+');
          $.each(value,function(index,value){
            if(value.indexOf('country') > 0){
              value = value.split('__');
              countries.add(value[0]);
            }
          });
        });
        let countriesArray = Array.from(countries);
        if (countriesArray.length > 1 && countriesArray.length < 12) {
          title_text += countriesArray.slice(0, -1).join(', ') + ' and ' + countriesArray.slice(-1);
        }else if(countriesArray.length >= 12){
          title_text = 'Events in all ICGLR Member States';
        } else {
          title_text += countriesArray[0];
        }
      }
        
      let ctx = document.getElementById('wpdp_chart').getContext('2d');
      let ctx_fat = document.getElementById('wpdp_chart_fat').getContext('2d');
      let ctx_bar = document.getElementById('wpdp_chart_bar_chart').getContext('2d');
      let title_text_fat = title_text.replace('Events', 'Events by Fatalities');
      let title_text_actors = title_text.replace('Events', 'Events by Actors');

      self.graphFun(ctx,datasets,title_text,chart_sql,false);
      self.graphFunBar(ctx_fat,datasets_fat,title_text_fat,chart_sql,true);
      self.graphFunBar(ctx_bar,datasets_actors,title_text_actors,chart_sql,false);
    }

    self.graphFunBar = function(ctx,datasets,title_text,chart_sql,is_fat){
      var chartVar = 'myChartBar';
      if(is_fat){
        chartVar = 'myChartBarFat';
      }
      datasets.forEach(function(dataset) {
        dataset.backgroundColor = dataset.borderColor;
        delete dataset.borderColor;
      });
      window[chartVar] = new Chart(ctx, {
        type: 'bar',
        data: {datasets:datasets},
        options: {
          responsive: true,
            maintainAspectRatio: false, // Changed to false
            height: 400, // Explicit height

          plugins: {
              title: {
                  display: true,
                  text: title_text
              },
          },
          scales: {
              x: {
                type: 'time',
                time: {
                  unit: chart_sql,
                },
                stacked: true,
                bounds: 'ticks',
              },
              y: {
                type: 'linear',
                display: true,
                beginAtZero: true,
                stacked: true,
              }
          },
        }
      });
    }


    self.graphFun = function(ctx,datasets,title_text,chart_sql,is_fat){
      var chartVar = 'myChart';
      if(is_fat){
        chartVar = 'myChartFat';
      }
      window[chartVar] = new Chart(ctx, {
        type: 'line',
        data: {datasets:datasets},
        options: {
          responsive: true,
            maintainAspectRatio: false, // Changed to false
            height: 400, // Explicit height

          plugins: {
              title: {
                  display: true,
                  text: title_text
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

    self.maps_polygons = function() {

      $('#wpdp-loader').css('display','flex');
      var selectedCountries = $('input[name="wpdp_country"]:radio').map(function() {
        return this.value;
      }).get();

      $.ajax({
        url: wpdp_obj.ajax_url,
        data: {
          action: 'wpdp_get_country_polygons_data',
          type_val: selectedIncidents,
          locations_val: selectedCountries,
          actors_val: selectedActors,
          actors_names_val: selectedActorNames,
          fat_val: selectedFat,
          from_val: fromYear,
          to_val: toYear,
          target_civ: targetCiv
        },
        type: 'POST',
        success: function(response) {
          $('#wpdp-loader').hide();
          const eventCounts = response.data.data.map(country => country.events_count);
          const sortedCounts = [...eventCounts].sort((a, b) => b - a);
          
          // Calculate thresholds using quartiles instead of fixed positions
          const highThreshold = sortedCounts[Math.floor(sortedCounts.length * 0.75)]; // 75th percentile
          const mediumThreshold = sortedCounts[Math.floor(sortedCounts.length * 0.25)]; // 25th percentile
          
          const thresholds = {
            LARGE: highThreshold || 0,
            MEDIUM: mediumThreshold || 0
          };


          // Add fixed info panel to map
          const infoPanel = document.createElement('div');
          infoPanel.id = 'map-info-panel';
          infoPanel.style.cssText = `
            position: absolute;
            bottom: 40px;
            right: 310px;
            background: white;
            padding: 15px;
            border-radius: 4px;
            box-shadow: 0 2px 6px rgba(0,0,0,0.3);
            min-width: 200px;
            z-index: 1;
            display: none;
          `;
          
          // Add default content
          infoPanel.innerHTML = '<p class="default-text">Hover over a country to see details</p>';
          
          self.poly_map = new google.maps.Map(document.getElementById('polygons_map'), {
            zoom: 5.5,
            styles: self.mapsStyles(),
            mapTypeControl: false,
          });
          // Change how we add the info panel to the map
          document.getElementById('polygons_map').appendChild(infoPanel);

          // Set default styling for all features
          self.poly_map.data.setStyle({
            fillColor: '#FFEB3B',
            fillOpacity: 0.35,
            strokeColor: '#000000',
            strokeWeight: 1,
            visible: true
          });

          $.getJSON(wpdp_obj.url+'/lib/filtered_countries2.geojson', function(geoJson) {

            self.poly_map.data.addGeoJson(geoJson);

            // After adding GeoJSON, apply specific styling
            self.poly_map.data.setStyle(function(feature) {
              const countryName = feature.getProperty('ADMIN');
              const countryData = response.data.data.find(c => c.country === countryName);
              
              if (!countryData) {
                console.log('No data for country:', countryName); // Debug log
                return {
                  fillColor: '#CCCCCC', // Gray color for no data
                  fillOpacity: 0.35,
                  strokeColor: '#000000',
                  strokeWeight: 1,
                  visible: true // Changed to always be visible
                };
              }

              const color = self.getColorForIntensity(countryData.events_count, thresholds);

              return {
                fillColor: color,
                fillOpacity: 0.35,
                strokeColor: '#000000',
                strokeWeight: 1,
                visible: true
              };
            });

            // Add bounds fitting to ensure polygons are visible
            var bounds = new google.maps.LatLngBounds();
            self.poly_map.data.forEach(function(feature) {
              self.processPoints(feature.getGeometry(), bounds.extend, bounds);
            });
            self.poly_map.fitBounds(bounds);

            // To shift the center after bounds are set, add this:
            google.maps.event.addListenerOnce(self.poly_map, 'bounds_changed', function() {
              var center = self.poly_map.getCenter();
              var lng = center.lng();
              if($('.wpdp .filter').find('span').hasClass('fa-arrow-left')){
                lng = lng + 25;
              }
              self.poly_map.setCenter(new google.maps.LatLng(
                center.lat(),
                lng
              ));
            });

          }).fail(function(jqXHR, textStatus, errorThrown) {
            console.error('Failed to load GeoJSON:', textStatus, errorThrown);
          });

          // Replace infoWindow with info panel updates
          self.poly_map.data.addListener('mouseover', function(event) {
            self.poly_map.data.overrideStyle(event.feature, {
              fillOpacity: 0.7,
              strokeColor: "#FF0000",
              strokeWeight: 2
            });

            const countryName = event.feature.getProperty('ADMIN');
            const countryData = response.data.data.find(c => c.country === countryName);
            
            infoPanel.style.display = 'block';
            if (!countryData) {
              infoPanel.innerHTML = `
                <div class="country-info">
                  <h3 style="margin: 0 0 10px 0; font-size: 16px;">${countryName}</h3>
                  <p style="margin: 5px 0; color: #666;">
                    No events were found in this region,<br> please adjust the filter options to see more data.
                  </p>
                </div>
              `;
            } else {
              const severityLabel = self.getSeverityLabel(countryData.events_count, thresholds);
              const severityColor = self.getColorForIntensity(countryData.events_count, thresholds);
              
              infoPanel.innerHTML = `
                <div class="country-info">
                  <h3 style="margin: 0 0 10px 0; font-size: 16px;">${countryData.country}</h3>
                  <p style="margin: 5px 0;">
                    <span>Severity Level:</span>
                    <strong style="color: ${severityColor}">${severityLabel}</strong>
                  </p>
                  <p style="margin: 5px 0;">
                    <span>Total Incidents:</span>
                    <strong>${countryData.events_count.toLocaleString()}</strong>
                  </p>
                  <p style="margin: 5px 0;">
                    <span>Fatalities:</span>
                    <strong>${countryData.fatalities_count.toLocaleString()}</strong>
                  </p>
                </div>
              `;
            }
          });

          self.poly_map.data.addListener('mouseout', function(event) {
            self.poly_map.data.revertStyle();
            infoPanel.style.display = 'none';
          });

          self.poly_map.data.addListener('click', function(event) {
            const countryName = event.feature.getProperty('ADMIN');
            $('input[name="wpdp_country"][value="' + countryName + '"]:radio').prop('checked', true);
            $('input[name="wpdp_search_location_country"]').val(countryName).parents('form').submit();
          });
        },
        error: function(errorThrown) {
          console.error('Failed to load country data:', errorThrown);
        }
      });
    };

    // Add this helper function to process points for bounds
    self.processPoints = function(geometry, callback, thisArg) {
      if (geometry instanceof google.maps.LatLng) {
        callback.call(thisArg, geometry);
      } else if (geometry instanceof google.maps.Data.Point) {
        callback.call(thisArg, geometry.get());
      } else {
        geometry.getArray().forEach(function(g) {
          self.processPoints(g, callback, thisArg);
        });
      }
    };

    self.getColorForIntensity = function(count, thresholds) {
      const COLORS = {
        LARGE: '#CD6155',    // Red for high count
        MEDIUM: '#F0B27A',   // Orange for medium count
        SMALL: '#F9E79F'     // Yellow for low count
      };

      // Add minimum threshold to prevent very low counts from being marked as high
      const MIN_THRESHOLD = 5; // Adjust this value based on your needs

      if (count < MIN_THRESHOLD) {
        return COLORS.SMALL;
      }

      if (count >= thresholds.LARGE) {
        return COLORS.LARGE;
      } else if (count >= thresholds.MEDIUM) {
        return COLORS.MEDIUM;
      } else {
        return COLORS.SMALL;
      }
    };

    self.getSeverityLabel = function(count, thresholds) {
      if (count >= thresholds.LARGE) {
        return 'High';
      } else if (count >= thresholds.MEDIUM) {
        return 'Medium';
      } else {
        return 'Low';
      }
    };

    self.mapsChoice = function(){
      if($('input[name="wpdp_country"]:radio').length > 0){
        self.maps_polygons();
      }else{
        self.maps();
      }
    }

    // Add this new function to handle lazy loading of location levels
    self.loadLocationLevel = function(parentElement, parentKey) {
      $.ajax({
        url: wpdp_obj.ajax_url,
        type: 'POST',
        data: {
          action: 'get_location_level',
          parent_key: parentKey,
          country: $('input[name="wpdp_search_location_country"]').length ? $('input[name="wpdp_search_location_country"]').val() : ''
        },
        beforeSend: function() {
          $('.checkboxes_locations').css('opacity','0.5').css('pointer-events', 'none');
        },
        success: function(response) {
          if (response.success) {
            parentElement.find('> ul').remove();
            parentElement.append(response.data);
          } else {
            parentElement.find('> ul').html('<li>No data found</li>');
          }
          $('.checkboxes_locations').css('opacity','1').css('pointer-events', 'auto');
        },
        error: function() {
          parentElement.find('> ul').html('<li>Error loading data</li>');
        }
      });
    };

    // Modify the expandable click handler
    $(document).on('click', '.exp_click', function(e) {
      if ($(this).prev().is(':radio')) {
        return;
      }

      var $li = $(this).closest('li');
      var $arrow = $(this).find('.arrow');
      if($(this).parents('.grp').hasClass('locations')){
        if (!$li.hasClass('loaded')) {
          // Get the parent location key
          var parentKey = $li.find('> input').val();
          // Load child locations
          self.loadLocationLevel($li, parentKey);
          
          $li.addClass('loaded');
        }
      }
      
      $li.toggleClass('expanded');
      $arrow.toggleClass('dashicons-arrow-down-alt2 dashicons-arrow-up-alt2');
    });

    $( self.init );


    // window.wpdp_maps = self.maps;
    
    window.wpdp_map = self.mapsChoice;
}( jQuery ) );

( function ( $ ) {
  'use strict';

    var self = {};
    var myChart;
    var table;
    var global_markers = [];
    var markerCluster;
    var selectedLocations = [];

    self.init = function(){


      self.dataTables();
      self.menuFilters();
      self.filtersChange();
      self.expandable();
      self.showMapDetails();
      self.graphCountSelector();

    },

    self.graphCountSelector = function(){
      if(document.getElementById('wpdp_type_selector')){
        document.getElementById('wpdp_type_selector').addEventListener('change', function () {
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

    self.maps = function(fromYear = false,toYear = false){
      if(self.main_map){
        return;
      }
      $.ajax({
        url: wpdp_obj.ajax_url,
        data: {
          action:'wpdp_map_request',
          from_val: fromYear,
          to_val: toYear
        },
        type: 'POST',
        success: function(response) {
          self.mapInit(response.data);
        },
        error: function(errorThrown){
            alert('No data found');
        }
      });


    },

    self.mapInit = function(mapData){
      if(!mapData.length){
        return;
      }


      var typeValue = [];
      $("#wpdp_type").find('option').each(function(){
        typeValue.push($(this).val());
      });

      var startLocation = { lat: parseFloat(mapData[0].latitude), lng: parseFloat(mapData[0].longitude) };

      if(!self.main_map){

        var infoWindow = new google.maps.InfoWindow();

        self.main_map = new google.maps.Map(
          document.getElementById('wpdp_map'),
          {
              mapTypeId: 'terrain',
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

              zoom: 3, 
              center: startLocation,   
              mapTypeControl: false
          }
        );
          
      }else{
        self.main_map.setCenter(startLocation);
        self.main_map.setZoom(3);
      }


      var my_map = self.main_map;

      my_map.data.loadGeoJson('https://raw.githubusercontent.com/johan/world.geo.json/master/countries.geo.json', null, function (features) {
            features.forEach(function (feature) {
                const country = feature.getProperty('name');
                const mapDataForCountry = mapData.filter(d => d.country === country);
                if(mapDataForCountry.length){
                  let count = 0;
                  mapDataForCountry.forEach(dataForCountry => {
                    Object.keys(dataForCountry).forEach(function(key) {
                        if(key === 'fatalities_count'){
                          count+= parseInt(dataForCountry[key]);
                        }
                        feature.setProperty(key, dataForCountry[key]);
                    });
                    feature.setProperty('full_fat',count);
                  });
                }

            });
        });

        my_map.data.setStyle(function(feature) {
            var fatalities = feature.getProperty('full_fat');
            var color = getColor(fatalities);
            const country = feature.getProperty('name');
            if (mapData.find(d => d.country === country)) {
              return {
                  fillColor: color,
                  strokeWeight: 2
              };
            }else{
              return{
                strokeWeight: .3
              }
            }
        });

      my_map.data.addListener('click', function(event) {
        const country = event.feature.getProperty('name');
        
        if (mapData.find(d => d.country === country)) {
          const mapDataForCountry = mapData.filter(d => d.country === country);
          
            my_map.data.revertStyle();
            my_map.data.overrideStyle(event.feature, {strokeWeight: 2});
            var location = event.latLng;

          const table = `
            <table>
                <thead>
                    <tr>
                        <th style="font-size:13px;">${country}</th>
                        `+typeValue.filter(type => type.trim() !== "").map(type => `<th style="font-size:13px;">${type}</th>`).join('')+`
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <td style="font-size:13px;">Incidents</td>
                        `+mapDataForCountry.map(data => `<td style="font-size:13px;">${data.events_count}</td>`).join('')+`
                    </tr>
                    <tr>
                        <td style="font-size:13px;">Fatalities</td>
                        `+mapDataForCountry.map(data => `<td style="font-size:13px;">${data.fatalities_count}</td>`).join('')+`
                    </tr>
                    <tr>
                        <td style="font-size:13px;">Total</td>
                        
                    </tr>
                </tbody>
            </table>
            <div class="map_more_details">
              More Info
              <span style="cursor:pointer;color:#cd0202;font-size:20px;margin-top:-1px;" class="dashicons dashicons-info"></span>
              <div class="det">
                <ul>
                  <li><b>Event Type:</b> ${mapDataForCountry.event_type}</li>
                  <li><b>Sub Event Type:</b> ${mapDataForCountry.sub_event_type}</li>
                  <li><b>Source:</b> ${mapDataForCountry.source}</li>
                  <li><b>Notes:</b> ${mapDataForCountry.notes}</li>
                  <li><b>Timestamp:</b> ${mapDataForCountry.timestamp}</li>
                </ul>
              </div>
            </div>


        `;

        infoWindow.setContent(table);
              infoWindow.setPosition(location);
            infoWindow.open(my_map);
        }else{
          infoWindow.close();
        }
      });

      function calculateTotals(infowindow) {
        let tempDiv = document.createElement('div');
        tempDiv.innerHTML = infowindow.getContent();
        let table = tempDiv.getElementsByTagName('table')[0];
        
        let rows = table.rows;
        let totalRow = rows[rows.length-1]; // The last row is the total row
    
        for (let i = 1; i < rows[0].cells.length; i++) { // Start from 1 to skip the first column
            let total = 0;
            for (let j = 1; j < rows.length - 1; j++) { // Start from 1 to skip the header row, and minus 1 to skip the total row
                total += parseInt(rows[j].cells[i].innerText, 10);
            }
            let newCell = totalRow.insertCell(i);
            newCell.style.fontSize = "14px";
            newCell.innerText = total;
        }
        
        infowindow.setContent(tempDiv.innerHTML);
    }
    
    google.maps.event.addListener(infoWindow, 'domready', function() {
        calculateTotals(infoWindow);
    });

    function getColor(accidents) {
      return accidents > 10000 ? '#800026' :
            accidents > 8000  ? '#BD0026' :
            accidents > 5000  ? '#E31A1C' :
            accidents > 4000  ? '#FC4E2A' :
            accidents > 3000   ? '#FD8D3C' :
            accidents > 500   ? '#FEB24C':
            accidents > 100   ? '#FED976' :
            '#FFEDA0';
    }


    },

    self.dataTables = function(){
      if ($.fn.DataTable && $('#wpdp_datatable').length > 0) {
        let wpdp_from = $('#wpdp_from').val();
        if(wpdp_from == '' && JSON.parse(wpdp_shortcode_atts).from != ''){
          wpdp_from = JSON.parse(wpdp_shortcode_atts).from;
        }

        let wpdp_to = $('#wpdp_to').val();
        if(wpdp_to == '' && JSON.parse(wpdp_shortcode_atts).to != ''){
          wpdp_to = JSON.parse(wpdp_shortcode_atts).to;
        }
        

        self.table = $('#wpdp_datatable').DataTable({
            ajax: {
              "url": wpdp_obj.ajax_url,
              "type": "POST",
              "data": function ( d ) {
                d.action = 'wpdp_datatables_request';
                d.type_val = $('#wpdp_type').val();
                d.from_val = wpdp_from;
                d.to_val = wpdp_to;
                d.locations_val = self.selectedLocations;
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
      $('#wpdp_type,#wpdp_from,#wpdp_to').on('select2:select select2:unselect',function(e){
        self.filterAction();
      });
      $('.wpdp_location').on('change',function(e){
        self.filterAction();
      });
    },

    self.filterAction = function(){
      let typeValue = $("#wpdp_type").select2("val");
      let fromYear = $("#wpdp_from").select2("val");
      let toYear = $("#wpdp_to").select2("val");

      if(fromYear == '' && JSON.parse(wpdp_shortcode_atts).from != ''){
        fromYear = JSON.parse(wpdp_shortcode_atts).from;
      }

      if(toYear == '' && JSON.parse(wpdp_shortcode_atts).to != ''){
        toYear = JSON.parse(wpdp_shortcode_atts).to;
      }

      self.selectedLocations = [];
      
      $('input[type="checkbox"].wpdp_location:checked').each(function() {
          self.selectedLocations.push($(this).val());
      });

      if (typeof Chart !== 'undefined') {

        self.graphChange(typeValue, self.selectedLocations,fromYear,toYear);
        $('#wpdp_chart').show();
        $('#wpdp_chart_title').hide();
      }

      if (typeof google === 'object' && typeof google.maps === 'object') {
        self.maps(fromYear,toYear);
      }

      if ($.fn.DataTable && $('#wpdp_datatable').length > 0) {
        self.table.draw(false);
      }
    },
      
    self.graphChange = function(typeValue, selectedLocations, fromYear,toYear){
      $.ajax({
        url: wpdp_obj.ajax_url,
        data: {
          action:'wpdp_graph_request',
          type_val: typeValue,
          locations_val: selectedLocations,
          from_val: fromYear,
          to_val: toYear
        },
        type: 'POST',
        success: function(response) {
          self.chartInit(response.data,typeValue,selectedLocations);
        },
        error: function(errorThrown){
            alert('No data found');
        }
      });
    }

    self.chartInit = function(data,typeValue,selectedLocations){
      var datasets = [];
      const colors = ["#FF5733", "#FFBD33",  "#75FF33",  "#33FFBD", "#33DBFF", "#3375FF", "#5733FF", "#BD33FF"];
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

      let ctx = document.getElementById('wpdp_chart').getContext('2d');
      self.myChart = new Chart(ctx, {
        type: 'line',
        data: {datasets:datasets},
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
            }
        }
      });
      

    }
    

    $( self.init );


    window.wpdp_maps = self.maps;

}( jQuery ) );
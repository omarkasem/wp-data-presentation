// your_script.js
var result = ok_obj;
document.addEventListener('DOMContentLoaded', function () {
    // Assuming your PHP array is stored in a JavaScript variable named 'result'
    // Example data structure:
    // result = {
    //     years: ['2005', '2006', '2007', ...],
    //     incidents: [
    //         ['Battles', 2, 1, 4, ...],
    //         ['Explosions/Remote violence', 0, 1, 1, ...],
    //         ['Protests', 1, 2, 1, ...],
    //         ['Riots', 0, 0, 1, ...],
    //         ['Violence against civilians', 0, 1, 7, ...],
    //         ['Fatalities', 3, 13, 16, ...]
    //     ]
    // };

    // Select the canvas element
    var ctx = document.getElementById('incidentChart').getContext('2d');

    // Choose the incident type you want to display (e.g., 'Battles')
    var incidentTypeIndex = 0;

    // Create a data structure for Chart.js
    var chartData = {
        labels: result.years,
        datasets: [{
            label: result.incidents[incidentTypeIndex][0],
            data: result.incidents[incidentTypeIndex].slice(1),
            backgroundColor: 'rgba(75, 192, 192, 0.2)', // Adjust the color as needed
            borderColor: 'rgba(75, 192, 192, 1)', // Adjust the color as needed
            borderWidth: 1
        }]
    };

    // Create Chart.js bar chart
    var incidentChart = new Chart(ctx, {
        type: 'bar',
        data: chartData,
        options: {
            scales: {
                x: {
                    stacked: true
                },
                y: {
                    stacked: true
                }
            }
        }
    });

    // Change data when selecting a different incident type
    document.getElementById('incidentTypeSelector').addEventListener('change', function () {
        incidentTypeIndex = this.value;
        incidentChart.data.datasets[0].label = result.incidents[incidentTypeIndex][0];
        incidentChart.data.datasets[0].data = result.incidents[incidentTypeIndex].slice(1);
        incidentChart.update();
    });
});
document.addEventListener('DOMContentLoaded', function () {
    var revCanvas = document.getElementById('revenueChart');
    var movieCanvas = document.getElementById('topMoviesChart');

    if (typeof Chart === 'undefined') return;

    if (revCanvas) {
        var labels = JSON.parse(revCanvas.getAttribute('data-labels') || '[]');
        var values = JSON.parse(revCanvas.getAttribute('data-values') || '[]');
        new Chart(revCanvas, {
            type: 'bar',
            data: {
                labels: labels,
                datasets: [{
                    label: 'Pendapatan (Rp)',
                    data: values,
                    backgroundColor: '#FCA311',
                    borderRadius: 6
                }]
            },
            options: {
                responsive: true,
                plugins: { legend: { display: false } }
            }
        });
    }

    if (movieCanvas) {
        var mLabels = JSON.parse(movieCanvas.getAttribute('data-labels') || '[]');
        var mValues = JSON.parse(movieCanvas.getAttribute('data-values') || '[]');
        new Chart(movieCanvas, {
            type: 'doughnut',
            data: {
                labels: mLabels,
                datasets: [{
                    data: mValues,
                    backgroundColor: ['#FCA311', '#14213D', '#2E9E6B', '#3B82F6', '#D64545']
                }]
            },
            options: {
                responsive: true
            }
        });
    }
});

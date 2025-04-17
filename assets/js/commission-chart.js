// Graphique des commissions
document.addEventListener('DOMContentLoaded', function() {
    const ctx = document.getElementById('commissionsChart').getContext('2d');
    
    // Fonction pour mettre à jour le graphique
    function updateChart() {
        fetch('api/commission_api.php?action=getWeeklyData')
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    const weekData = data.data;
                    const days = ['Dimanche', 'Lundi', 'Mardi', 'Mercredi', 'Jeudi', 'Vendredi', 'Samedi'];
                    
                    // Créer ou mettre à jour le graphique
                    if (window.commissionsChart) {
                        window.commissionsChart.data.datasets[0].data = weekData;
                        window.commissionsChart.update();
                    } else {
                        window.commissionsChart = new Chart(ctx, {
                            type: 'line',
                            data: {
                                labels: days,
                                datasets: [{
                                    label: 'Commissions (€)',
                                    data: weekData,
                                    borderColor: '#6B73FF',
                                    backgroundColor: 'rgba(107, 115, 255, 0.1)',
                                    borderWidth: 2,
                                    fill: true,
                                    tension: 0.4
                                }]
                            },
                            options: {
                                responsive: true,
                                maintainAspectRatio: false,
                                plugins: {
                                    legend: {
                                        display: false
                                    },
                                    tooltip: {
                                        mode: 'index',
                                        intersect: false,
                                        callbacks: {
                                            label: function(context) {
                                                return `${context.parsed.y.toFixed(2)} €`;
                                            }
                                        }
                                    }
                                },
                                scales: {
                                    y: {
                                        beginAtZero: true,
                                        ticks: {
                                            callback: function(value) {
                                                return value.toFixed(2) + ' €';
                                            }
                                        }
                                    }
                                },
                                interaction: {
                                    intersect: false,
                                    mode: 'index'
                                }
                            }
                        });
                    }
                }
            })
            .catch(error => console.error('Erreur lors de la récupération des données:', error));
    }

    // Mettre à jour le graphique toutes les 30 secondes
    updateChart();
    setInterval(updateChart, 30000);
}); 
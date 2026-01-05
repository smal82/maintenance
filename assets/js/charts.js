// assets/js/charts.js

const Charts = {
    defaultColors: [
        '#3498db', '#2ecc71', '#f39c12', '#e74c3c', '#9b59b6',
        '#1abc9c', '#34495e', '#e67e22', '#95a5a6', '#c0392b'
    ],
    
    // Initialize maintenances chart
    initMaintenancesChart: function(data) {
        const ctx = document.getElementById('maintenancesChart');
        if (!ctx) return;
        
        const labels = data.map(item => {
            const [year, month] = item.month.split('-');
            const date = new Date(year, month - 1);
            return date.toLocaleDateString('it-IT', { month: 'short', year: 'numeric' });
        });
        
        new Chart(ctx, {
            type: 'line',
            data: {
                labels: labels,
                datasets: [
                    {
                        label: 'Totali',
                        data: data.map(item => item.total),
                        borderColor: '#3498db',
                        backgroundColor: 'rgba(52, 152, 219, 0.1)',
                        tension: 0.4,
                        fill: true
                    },
                    {
                        label: 'Completate',
                        data: data.map(item => item.completed),
                        borderColor: '#2ecc71',
                        backgroundColor: 'rgba(46, 204, 113, 0.1)',
                        tension: 0.4,
                        fill: true
                    }
                ]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        position: 'bottom'
                    },
                    tooltip: {
                        mode: 'index',
                        intersect: false
                    }
                },
                scales: {
                    y: {
                        beginAtZero: true,
                        ticks: {
                            precision: 0
                        }
                    }
                }
            }
        });
    },
    
    // Initialize assets by category chart
    initAssetsCategoryChart: function(data) {
        const ctx = document.getElementById('assetsCategoryChart');
        if (!ctx) return;
        
        const filteredData = data.filter(item => item.total > 0);
        
        new Chart(ctx, {
            type: 'doughnut',
            data: {
                labels: filteredData.map(item => item.name),
                datasets: [{
                    data: filteredData.map(item => item.total),
                    backgroundColor: filteredData.map((item, index) => {
                        return item.color || this.defaultColors[index % this.defaultColors.length];
                    }),
                    borderWidth: 2,
                    borderColor: '#fff'
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        position: 'bottom'
                    },
                    tooltip: {
                        callbacks: {
                            label: function(context) {
                                const label = context.label || '';
                                const value = context.parsed || 0;
                                const total = context.dataset.data.reduce((a, b) => a + b, 0);
                                const percentage = ((value / total) * 100).toFixed(1);
                                return `${label}: ${value} (${percentage}%)`;
                            }
                        }
                    }
                }
            }
        });
    },
    
    // Initialize priority distribution chart
    initPriorityChart: function(data) {
        const ctx = document.getElementById('priorityChart');
        if (!ctx) return;
        
        new Chart(ctx, {
            type: 'bar',
            data: {
                labels: ['Bassa', 'Media', 'Alta', 'Critica'],
                datasets: [{
                    label: 'Manutenzioni per Priorità',
                    data: [
                        data.low || 0,
                        data.medium || 0,
                        data.high || 0,
                        data.critical || 0
                    ],
                    backgroundColor: [
                        '#95a5a6',
                        '#f39c12',
                        '#e74c3c',
                        '#c0392b'
                    ]
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        display: false
                    }
                },
                scales: {
                    y: {
                        beginAtZero: true,
                        ticks: {
                            precision: 0
                        }
                    }
                }
            }
        });
    },
    
    // Initialize status distribution chart
    initStatusChart: function(data) {
        const ctx = document.getElementById('statusChart');
        if (!ctx) return;
        
        new Chart(ctx, {
            type: 'pie',
            data: {
                labels: ['Programmata', 'In Corso', 'Completata', 'Annullata'],
                datasets: [{
                    data: [
                        data.scheduled || 0,
                        data.in_progress || 0,
                        data.completed || 0,
                        data.cancelled || 0
                    ],
                    backgroundColor: [
                        '#3498db',
                        '#f39c12',
                        '#2ecc71',
                        '#95a5a6'
                    ]
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        position: 'bottom'
                    }
                }
            }
        });
    },
    
    // Initialize cost chart
    initCostChart: function(data) {
        const ctx = document.getElementById('costChart');
        if (!ctx) return;
        
        new Chart(ctx, {
            type: 'bar',
            data: {
                labels: data.map(item => item.month),
                datasets: [{
                    label: 'Costi Manutenzione (€)',
                    data: data.map(item => item.cost),
                    backgroundColor: '#3498db',
                    borderRadius: 6
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
                        callbacks: {
                            label: function(context) {
                                return Utils.formatCurrency(context.parsed.y);
                            }
                        }
                    }
                },
                scales: {
                    y: {
                        beginAtZero: true,
                        ticks: {
                            callback: function(value) {
                                return '€' + value.toLocaleString('it-IT');
                            }
                        }
                    }
                }
            }
        });
    }
};

// Initialize charts on page load
$(document).ready(function() {
    // Check if data is available
    if (typeof maintenancesData !== 'undefined') {
        Charts.initMaintenancesChart(maintenancesData);
    }
    
    if (typeof assetsCategoryData !== 'undefined') {
        Charts.initAssetsCategoryChart(assetsCategoryData);
    }
    
    if (typeof priorityData !== 'undefined') {
        Charts.initPriorityChart(priorityData);
    }
    
    if (typeof statusData !== 'undefined') {
        Charts.initStatusChart(statusData);
    }
    
    if (typeof costData !== 'undefined') {
        Charts.initCostChart(costData);
    }
});
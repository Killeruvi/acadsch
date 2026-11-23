@if($user->isAdmin())
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.1/dist/chart.umd.min.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function () {
    const isDark = document.documentElement.classList.contains('dark');
    const textColor = isDark ? '#cbd5e1' : '#334155';
    const gridColor = isDark ? 'rgba(51, 65, 85, 0.4)' : 'rgba(226, 232, 240, 0.8)';

    // 1. Attendance Trend Chart
    const ctxAtt = document.getElementById('chart-attendance-trend');
    if (ctxAtt) {
        new Chart(ctxAtt, {
            type: 'line',
            data: {
                labels: {!! json_encode($attendanceTrendChart['labels'] ?? []) !!},
                datasets: [
                    {
                        label: 'Present',
                        data: {!! json_encode($attendanceTrendChart['present'] ?? []) !!},
                        borderColor: '#10b981',
                        backgroundColor: 'rgba(16, 185, 129, 0.12)',
                        fill: true,
                        tension: 0.35,
                        borderWidth: 2,
                        pointRadius: 3,
                    },
                    {
                        label: 'Absent',
                        data: {!! json_encode($attendanceTrendChart['absent'] ?? []) !!},
                        borderColor: '#f43f5e',
                        backgroundColor: 'transparent',
                        borderDash: [4, 4],
                        borderWidth: 2,
                        pointRadius: 3,
                    },
                    {
                        label: 'Excused',
                        data: {!! json_encode($attendanceTrendChart['excused'] ?? []) !!},
                        borderColor: '#3b82f6',
                        backgroundColor: 'transparent',
                        borderWidth: 1.5,
                        pointRadius: 2,
                    }
                ]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: { labels: { color: textColor, boxWidth: 10, font: { size: 10 } } }
                },
                scales: {
                    x: { ticks: { color: textColor, font: { size: 9 } }, grid: { display: false } },
                    y: { ticks: { color: textColor, font: { size: 9 } }, grid: { color: gridColor }, beginAtZero: true }
                }
            }
        });
    }

    // Infix 6-Month Income vs Expense Trend Chart
    const ctxIncExp = document.getElementById('chart-income-expense-trend');
    if (ctxIncExp) {
        new Chart(ctxIncExp, {
            type: 'bar',
            data: {
                labels: {!! json_encode($financialTrendChart['labels'] ?? []) !!},
                datasets: [
                    {
                        label: 'Operating Income',
                        data: {!! json_encode($financialTrendChart['income'] ?? []) !!},
                        backgroundColor: '#10b981',
                        borderRadius: 4,
                    },
                    {
                        label: 'Operating Expenses',
                        data: {!! json_encode($financialTrendChart['expenses'] ?? []) !!},
                        backgroundColor: '#f43f5e',
                        borderRadius: 4,
                    },
                    {
                        label: 'Fees Realized',
                        data: {!! json_encode($financialTrendChart['fees'] ?? []) !!},
                        backgroundColor: '#7c32ff',
                        borderRadius: 4,
                    }
                ]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: { position: 'top', labels: { color: textColor, boxWidth: 10, font: { size: 10 } } }
                },
                scales: {
                    x: { ticks: { color: textColor, font: { size: 9 } }, grid: { display: false } },
                    y: { ticks: { color: textColor, font: { size: 9 } }, grid: { color: gridColor }, beginAtZero: true }
                }
            }
        });
    }

    // 2. Fee Distribution Chart
    const ctxFee = document.getElementById('chart-fee-distribution');
    if (ctxFee) {
        new Chart(ctxFee, {
            type: 'doughnut',
            data: {
                labels: {!! json_encode($feeDistributionChart['labels'] ?? []) !!},
                datasets: [{
                    data: {!! json_encode($feeDistributionChart['data'] ?? []) !!},
                    backgroundColor: ['#10b981', '#f59e0b', '#f43f5e'],
                    borderWidth: 0,
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                cutout: '65%',
                plugins: {
                    legend: { position: 'bottom', labels: { color: textColor, boxWidth: 10, font: { size: 10 } } }
                }
            }
        });
    }

    // 3. Admissions Funnel Chart
    const ctxAdm = document.getElementById('chart-admissions-funnel');
    if (ctxAdm) {
        new Chart(ctxAdm, {
            type: 'bar',
            data: {
                labels: {!! json_encode($admissionsChart['labels'] ?? []) !!},
                datasets: [{
                    label: 'Scholars',
                    data: {!! json_encode($admissionsChart['data'] ?? []) !!},
                    backgroundColor: ['#6366f1', '#10b981', '#f43f5e', '#eab308'],
                    borderRadius: 6,
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: { legend: { display: false } },
                scales: {
                    x: { ticks: { color: textColor, font: { size: 9 } }, grid: { display: false } },
                    y: { ticks: { color: textColor, font: { size: 9 } }, grid: { color: gridColor }, beginAtZero: true }
                }
            }
        });
    }

    // 4. Class Capacity Chart
    const ctxCap = document.getElementById('chart-class-capacity');
    if (ctxCap) {
        new Chart(ctxCap, {
            type: 'bar',
            data: {
                labels: {!! json_encode($classCapacityChart['labels'] ?? []) !!},
                datasets: [
                    {
                        label: 'Enrolled',
                        data: {!! json_encode($classCapacityChart['enrolled'] ?? []) !!},
                        backgroundColor: '#0ea5e9',
                        borderRadius: 4,
                    },
                    {
                        label: 'Max Capacity',
                        data: {!! json_encode($classCapacityChart['capacity'] ?? []) !!},
                        backgroundColor: isDark ? '#334155' : '#cbd5e1',
                        borderRadius: 4,
                    }
                ]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: { labels: { color: textColor, boxWidth: 10, font: { size: 10 } } }
                },
                scales: {
                    x: { ticks: { color: textColor, font: { size: 9 } }, grid: { display: false } },
                    y: { ticks: { color: textColor, font: { size: 9 } }, grid: { color: gridColor }, beginAtZero: true }
                }
            }
        });
    }
});
</script>
@endif
@endsection

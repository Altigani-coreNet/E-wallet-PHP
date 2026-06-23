import React, { useState, useEffect } from 'react';
import Chart from 'react-apexcharts';

const DashboardCharts = ({ data, hasRange, loading }) => {
    const [activeTab, setActiveTab] = useState('daily');

    // Get common chart options
    const getChartOptions = (chartData, period) => {
        if (!chartData || !chartData.series || !chartData.labels) {
            return null;
        }

        return {
            series: chartData.series.map(serie => ({
                name: serie.name,
                data: serie.data || []
            })),
            chart: {
                fontFamily: 'inherit',
                type: 'line',
                height: 300,
                toolbar: { show: false }
            },
            plotOptions: {},
            legend: {
                show: true,
                position: 'top',
                labels: {
                    colors: '#808080',
                    useSeriesColors: false
                }
            },
            dataLabels: { enabled: false },
            stroke: {
                curve: 'smooth',
                show: true,
                width: 3,
                colors: ['#009ef7', '#f1416c', '#ffc700']
            },
            xaxis: {
                categories: chartData.labels || [],
                axisBorder: { show: false },
                axisTicks: { show: false },
                tickAmount: 6,
                labels: {
                    style: {
                        colors: '#808080',
                        fontSize: '12px'
                    }
                },
                crosshairs: {
                    position: 'front',
                    stroke: {
                        color: ['#009ef7', '#f1416c', '#ffc700'],
                        width: 1,
                        dashArray: 3
                    }
                }
            },
            yaxis: {
                min: 0,
                tickAmount: 6,
                labels: {
                    style: {
                        colors: '#808080',
                        fontSize: '12px'
                    },
                    formatter: function(val) {
                        return Math.floor(val);
                    }
                }
            },
            colors: ['#009ef7', '#f1416c', '#ffc700'],
            grid: {
                borderColor: '#e4e6ef',
                strokeDashArray: 4,
                yaxis: { lines: { show: true } }
            },
            markers: {
                strokeColors: ['#009ef7', '#f1416c', '#ffc700'],
                strokeWidth: 3
            },
            tooltip: {
                style: {
                    fontSize: '12px'
                },
                y: {
                    formatter: function(val) {
                        return val ? val.toFixed(0) : '0';
                    }
                }
            }
        };
    };

    // Get chart data based on period
    const getChartData = (period) => {
        if (!data?.transactionChartData) return null;
        return data.transactionChartData[period];
    };

    const renderChart = (period) => {
        let chartData = getChartData(period);
        
        // If no data, create empty chart structure with 0 values
        if (!chartData || !chartData.series || !chartData.labels || chartData.labels.length === 0) {
            // Create demo/empty data based on period
            const emptyLabels = period === 'daily' 
                ? Array.from({length: 7}, (_, i) => `Day ${i + 1}`)
                : period === 'weekly' 
                ? Array.from({length: 4}, (_, i) => `Week ${i + 1}`)
                : Array.from({length: 6}, (_, i) => `Month ${i + 1}`);
            
            chartData = {
                labels: emptyLabels,
                series: [
                    { name: 'Approved', data: Array(emptyLabels.length).fill(0) },
                    { name: 'Voided', data: Array(emptyLabels.length).fill(0) },
                    { name: 'Refunded', data: Array(emptyLabels.length).fill(0) }
                ]
            };
        }
        
        const options = getChartOptions(chartData, period);
        
        if (!options) {
            return (
                <div className="d-flex align-items-center justify-content-center" style={{ height: '300px' }}>
                    <p className="text-muted">Unable to render chart</p>
                </div>
            );
        }

        return (
            <div className="ms-n5 me-n3 min-h-auto w-100" style={{ height: '300px' }}>
                <Chart
                    options={options}
                    series={options.series}
                    type="line"
                    height={300}
                />
                {(!data?.transactionChartData || chartData.series[0].data.every(val => val === 0)) && (
                    <div className="text-center mt-2">
                        <small className="text-muted">No transaction data available</small>
                    </div>
                )}
            </div>
        );
    };

    if (loading) {
        return (
            <div className="card card-flush h-xl-100">
                <div className="card-body d-flex align-items-center justify-content-center" style={{ minHeight: '400px' }}>
                    <div className="text-center">
                        <span className="spinner-border spinner-border-lg text-primary"></span>
                        <p className="mt-3 text-muted">Loading charts...</p>
                    </div>
                </div>
            </div>
        );
    }

    return (
        <div className="card card-flush h-xl-100">
            {/* Header */}
            <div className="card-header pt-5">
                <h3 className="card-title align-items-start flex-column">
                    <span className="card-label fw-bold text-gray-900">
                        Transaction Analytics
                    </span>
                    <span className="text-gray-500 mt-1 fw-semibold fs-6">
                        {data?.todayStats ? 
                            `${new Intl.NumberFormat('en-US').format(data.todayStats.count || 0)} transactions & $${new Intl.NumberFormat('en-US').format(data.todayStats.amount || 0)} today` 
                            : 'Loading...'}
                    </span>
                </h3>
                
                {/* Toolbar */}
                <div className="card-toolbar">
                    {hasRange ? (
                        <span className="badge badge-light-primary">Custom range</span>
                    ) : (
                        <ul className="nav" role="tablist">
                            <li className="nav-item" role="presentation">
                                <button
                                    className={`nav-link btn btn-sm btn-color-muted btn-active btn-active-light fw-bold px-4 me-1 ${activeTab === 'daily' ? 'active' : ''}`}
                                    onClick={() => setActiveTab('daily')}
                                    type="button"
                                >
                                    Daily
                                </button>
                            </li>
                            <li className="nav-item" role="presentation">
                                <button
                                    className={`nav-link btn btn-sm btn-color-muted btn-active btn-active-light fw-bold px-4 me-1 ${activeTab === 'weekly' ? 'active' : ''}`}
                                    onClick={() => setActiveTab('weekly')}
                                    type="button"
                                >
                                    Weekly
                                </button>
                            </li>
                            <li className="nav-item" role="presentation">
                                <button
                                    className={`nav-link btn btn-sm btn-color-muted btn-active btn-active-light fw-bold px-4 me-1 ${activeTab === 'monthly' ? 'active' : ''}`}
                                    onClick={() => setActiveTab('monthly')}
                                    type="button"
                                >
                                    Monthly
                                </button>
                            </li>
                        </ul>
                    )}
                </div>
            </div>
            
            {/* Body */}
            <div className="card-body pb-0 pt-4">
                <div className="tab-content">
                    {hasRange ? (
                        <div className="tab-pane fade active show">
                            {renderChart('daily')}
                        </div>
                    ) : (
                        <>
                            <div className={`tab-pane fade ${activeTab === 'daily' ? 'active show' : ''}`}>
                                {activeTab === 'daily' && renderChart('daily')}
                            </div>
                            <div className={`tab-pane fade ${activeTab === 'weekly' ? 'active show' : ''}`}>
                                {activeTab === 'weekly' && renderChart('weekly')}
                            </div>
                            <div className={`tab-pane fade ${activeTab === 'monthly' ? 'active show' : ''}`}>
                                {activeTab === 'monthly' && renderChart('monthly')}
                            </div>
                        </>
                    )}
                </div>
            </div>
        </div>
    );
};

export default DashboardCharts;


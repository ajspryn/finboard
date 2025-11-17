@extends('layouts.app')

@section('title', 'Dashboard Test')

@section('content')
<div class="container-xxl flex-grow-1 container-p-y">
    <h4>Test Dashboard - Simple Chart</h4>

    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <h5>Monthly Trends</h5>
                </div>
                <div class="card-body">
                    <div id="monthlyTrendChart"></div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@section('scripts')
<script>
console.log('=== Test Dashboard Script ===');
console.log('ApexCharts:', typeof ApexCharts);

document.addEventListener('DOMContentLoaded', function() {
    console.log('DOM Ready');

    const chartData = {
        labels: @json($monthlyTrends['labels']),
        funding: @json($monthlyTrends['funding']),
        lending: @json($monthlyTrends['lending'])
    };

    console.log('Chart Data:', chartData);

    const options = {
        series: [{
            name: 'Plafon',
            data: chartData.funding
        }, {
            name: 'Outstanding',
            data: chartData.lending
        }],
        chart: {
            height: 350,
            type: 'line'
        },
        xaxis: {
            categories: chartData.labels
        }
    };

    const chart = new ApexCharts(document.querySelector("#monthlyTrendChart"), options);
    chart.render();
    console.log('Chart rendered');
});
</script>
@endsection

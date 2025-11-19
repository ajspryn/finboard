let combinedTrendChart = null;
const combinedTrendEl = document.querySelector('#combinedTrendChart');
let currentCombinedTrendType = 'nominal';
let currentCombinedTrendView = 'chart';

function createCombinedTrendView(type = 'nominal', view = 'chart') {
    if (combinedTrendChart) {
        combinedTrendChart.destroy();
    }
    console.log('Function works');
}

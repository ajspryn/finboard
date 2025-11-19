// Test syntax for renderCombinedTrendTable
function renderCombinedTrendTable(tableRows, sortedMonths, type) {
    let html = '<div class="table-responsive"><table class="table table-striped table-hover">';
    html += '<thead><tr><th>Bulan</th>';
    
    const firstRow = tableRows[sortedMonths[0]];
    Object.keys(firstRow).forEach(key => {
        if (key !== 'month') {
            html += '<th class="text-end">' + key + '</th>';
        }
    });
    html += '</tr></thead><tbody>';
    
    sortedMonths.forEach(monthKey => {
        const row = tableRows[monthKey];
        html += '<tr>';
        html += '<td><strong>' + row.month + '</strong></td>';
        
        Object.keys(row).forEach(key => {
            if (key !== 'month') {
                const value = row[key];
                let displayValue = '';
                if (type === 'nominal') {
                    displayValue = 'Rp ' + value.toFixed(0);
                } else {
                    displayValue = value.toString();
                }
                html += '<td class="text-end">' + displayValue + '</td>';
            }
        });
        html += '</tr>';
    });
    
    html += '</tbody></table></div>';
    console.log(html);
}

console.log('Function defined successfully');

window.HJSEOCharts = (function() {
  function numberFmt(n) { return Intl.NumberFormat('en-US').format(n); }
  function makeLineChart(ctx, labels, data, label) {
    return new Chart(ctx, {
      type: 'line',
      data: { labels, datasets: [{ label, data, borderColor: '#7fd3da', backgroundColor: 'rgba(127,211,218,.15)', tension: .3 }] },
      options: { scales: { y: { ticks: { callback: v => numberFmt(v) } } } }
    });
  }
  return { makeLineChart };
})();
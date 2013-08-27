$(document).ready(function(){
  var options = {
    chart: {
      type: 'spline',
      renderTo: 'graph_report',
      marginBottom: 50,
      borderWidth: 1,
      borderColor: '#CCCCCC'
    },

    title: {
      text: null
    },

    credits: {
      enabled: false
    },

    xAxis: {
      type: 'datetime',
      tickInterval: 24 * 3600 * 1000,
      tickWidth: 0,
      gridLineWidth: 1,
      labels: {
        align: 'center',
        x: 0,
        y: 20
      }
    },

    yAxis: [{ // left y axis
      min: 0,
      title: {
        text: null
      },

      labels: {
        align: 'left',
        x: 3,
        y: 16,
        formatter: function() {
          return Highcharts.numberFormat(this.value, 0);
        }
      },
      showFirstLabel: false
    }, { // right y axis
      linkedTo: 0,
      gridLineWidth: 0,
      opposite: true,
      title: {
        text: null
      },
      labels: {
        align: 'right',
        x: -3,
        y: 16,
        formatter: function() {
          return Highcharts.numberFormat(this.value, 0);
        }
      },
      showFirstLabel: false
    }],

    legend: {
      align: 'right',
      verticalAlign: 'bottom',
      floating: true,
      borderWidth: 0,
      itemStyle: {
        fontSize: '0.9em'
      }
    },

    tooltip: {
      shared: true,
      crosshairs: true
    }
  };

  var chart = new Highcharts.Chart(options);
  chart.showLoading('グラフを描画しています...');

  var url = '/cpanel/analyzeSQLReportChart.do'
           +'?from=' + $('#from').val()
           +'&to=' + $('#to').val();

  var moduleName = $('#module').val();

  if (moduleName.length) {
    url += '&module=' + moduleName;
  }

  jQuery.get(url, null, function(data, status, xhr) {
    var lines = [], date;
    var statement = {
      select: [], insert: [], update: [], delete: [], other: []
    };
    var csv = data.split(/\n/g);

    jQuery.each(csv, function(i, line) {
      line = line.split(/,/);
      date = Date.parse(line[0].replace('/-/g', '/'));

      var j = 1;

      for (var type in statement) {
        statement[type].push([date, parseInt(line[j])]);
        j++;
      }
    });

    for (var type in statement) {
      chart.addSeries({name: type.toUpperCase(), data: statement[type]});
    }

    chart.hideLoading();
  });
});

<div id="chartdiv_{$chart_id}am_bar" style="width: 100%; height: 550px;"></div>
<script type="text/javascript">
    (function (_, $) {
        $.ceEvent('on', 'ce.tab.show', function(){
            chart = new AmCharts.AmSerialChart();
            chart.categoryField = "title";
            chart.columnSpacing = 90;

            var categoryAxis = chart.categoryAxis;
            categoryAxis.labelRotation = 90;
            categoryAxis.dashLength = 5;
            categoryAxis.gridPosition = "start";

            var valueAxis = new AmCharts.ValueAxis();
            valueAxis.dashLength = 5;
            chart.addValueAxis(valueAxis);

            var graph = new AmCharts.AmGraph();
            graph.valueField = "value";
            graph.colorField = "color";
            graph.descriptionField = "full_descr";
            graph.balloonText = "<span style='font-size:14px'>[[description]]: <b>[[value]]</b></span>";
            graph.type = "column";
            graph.lineAlpha = 0;
            graph.fillAlphas = 1;
            chart.addGraph(graph);

            var chartCursor = new AmCharts.ChartCursor();
            chartCursor.cursorAlpha = 0;
            chartCursor.zoomable = false;
            chartCursor.categoryBalloonEnabled = false;
            chart.addChartCursor(chartCursor);

            chart.dataProvider = {$chart_data|json_encode nofilter};
            // this makes the chart 3D
            chart.depth3D = 15;
            chart.angle = 30;

            chart.write("chartdiv_{$chart_id}am_bar");
        });
    }(Tygh, Tygh.$));
</script>

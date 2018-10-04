if((!isset(userProfils.doNotAutoHideMenu) || userProfils.doNotAutoHideMenu != 1) && !jQuery.support.touch){
	$('#sd_objectList').hide();
	$('#div_graphiqueDisplay').removeClass('col-xs-10').addClass('col-xs-12');

	$('#bt_displayObjectList').on('mouseenter',function(){
	var timer = setTimeout(function(){
	$('#bt_displayObjectList').find('i').hide();
	$('#div_graphiqueDisplay').addClass('col-xs-10').removeClass('col-xs-12');
	$('#sd_objectList').show();
	$(window).resize();
	}, 100);
	$(this).data('timerMouseleave', timer)
	}).on("mouseleave", function(){
	clearTimeout($(this).data('timerMouseleave'));
	});

	$('#sd_objectList').on('mouseleave',function(){
		var timer = setTimeout(function(){
			$('#sd_objectList').hide();
			$('#bt_displayObjectList').find('i').show();
			$('#div_graphiqueDisplay').removeClass('col-xs-10').addClass('col-xs-12');
			setTimeout(function(){
				$(window).resize();
			},100);
			setTimeout(function(){
				$(window).resize();
			},300);
			setTimeout(function(){
				$(window).resize();
			},500);
		}, 300);
		$(this).data('timerMouseleave', timer);
	}).on("mouseenter", function(){
		clearTimeout($(this).data('timerMouseleave'));
	});
}

$(".in_datepicker").datepicker();

$('#bt_validChangeDate').on('click', function () {
	getDatas(object_id, $('#in_startDate').value(), $('#in_endDate').value());
});

setTimeout(function(){ getDatas(object_id); }, 1);

function getDatas(_object_id, _dateStart, _dateEnd) {
	$.ajax({
		type: 'POST',
		url: 'plugins/arrosageAuto/core/ajax/arrosageAuto.ajax.php',
		data: {
			action: 'getData',
			object_id: init(_object_id),
			dateStart: init(_dateStart),
			dateEnd: init(_dateEnd),
		},
		dataType: 'json',
		error: function (request, status, error) {
			handleAjaxError(request, status, error);
		},
		success: function (data) {
			if (data.state != 'ok') {
				$('#div_alert').showAlert({message: data.result, level: 'danger'});
				return;
			}
			if(data.result == null)
				return;
			var series = [];
			var categories = [];			
			for(var i in data.result){
				categories.push(i);
				if (typeof data.result[i].Plui !== 'undefined') { 
					series.push({
						step: true,
						name: i + ' Pluie (mm)',
						data:  data.result[i].Plui,
						type: 'column',
						stack : i,
						stacking : 'normal',
						dataGrouping: {
							approximation: "sum",
							enabled: true,
							forced: true,
							units: [[groupBy,[1]]]
						},
						tooltip: {
							valueDecimals: 2
						},
					});
				}
				if (typeof data.result[i].Pluviometrie !== 'undefined') { 
					series.push({
						step: true,
						name: i + ' Arrosage (mm)',
						data:  data.result[i].Pluviometrie,
						type: 'column',
						stack : i,
						stacking : 'normal',
						dataGrouping: {
							approximation: "sum",
							enabled: true,
							forced: true,
							units: [[groupBy,[1]]]
						},
						tooltip: {
							valueDecimals: 2
						},
					});
				}
			}
			drawSimpleGraph('div_graphPluviometerie',series,categories);

			var series = [];
			var categories = [];			
			for(var i in data.result){
				categories.push(i);	
				series.push({
					step: true,
					name: i + ' Arrosage (mm)',
					data:  data.result[i].ConsomationEau,
					type: 'column',
					stack : i,
					stacking : 'normal',
					dataGrouping: {
						approximation: "sum",
						enabled: true,
						forced: true,
						units: [[groupBy,[1]]]
					},
					tooltip: {
						valueDecimals: 2
					},
				});
			};
			drawSimpleGraph('div_graphConsommationEau',series,categories);
		}
	});
}

function drawSimpleGraph(_el, _serie,_categories) {
	var legend = {
		enabled: true,
		borderColor: 'black',
		borderWidth: 2,
		shadow: true
	};

	new Highcharts.StockChart({
		chart: {
			zoomType: 'x',
			renderTo: _el,
			height: 350,
			spacingTop: 0,
			spacingLeft: 0,
			spacingRight: 0,
			spacingBottom: 0
		},
		credits: {
			text: 'Copyright Jeedom',
			href: 'https://www.jeedom.com',
		},
		navigator: {
			enabled: false
		},
		rangeSelector: {
			buttons: [{
				type: 'week',
				count: 1,
				text: 'S'
			}, {
				type: 'month',
				count: 1,
				text: 'M'
			}, {
				type: 'year',
				count: 1,
				text: 'A'
			}, {
				type: 'all',
				count: 1,
				text: 'Tous'
			}],
			selected: 6,
			inputEnabled: false
		},
		legend: legend,
		tooltip: {
			pointFormat: '<span style="color:{series.color}">{series.name}</span>: <b>{point.y}</b><br/>',
			valueDecimals: 2,
		},
		yAxis: {
			format: '{value}',
			showEmpty: false,
			showLastLabel: true,
			min: 0,
			labels: {
				align: 'right',
				x: -5
			}
		},
		xAxis: {
			categories: _categories,
			type: 'datetime'
		},
		scrollbar: {
			barBackgroundColor: 'gray',
			barBorderRadius: 7,
			barBorderWidth: 0,
			buttonBackgroundColor: 'gray',
			buttonBorderWidth: 0,
			buttonBorderRadius: 7,
			trackBackgroundColor: 'none', trackBorderWidth: 1,
			trackBorderRadius: 8,
			trackBorderColor: '#CCC'
		},
		series: _serie
	});
}

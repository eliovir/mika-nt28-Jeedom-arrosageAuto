<?php
	require_once dirname(__FILE__) . '/../../../core/php/core.inc.php';
	include_file('core', 'authentification', 'php');
	if (!isConnect()) {
		include_file('desktop', '404', 'php');
		die();
	}
?>
<div class="row">
	<div class="col-sm-6">
		<legend>{{Source d'eau}}</legend>
		<form class="form-horizontal">
			<fieldset>
				<div class="form-group">
					<label class="col-lg-5 control-label">{{Débit de l'arrivée d'eau}}</label>
					<div class="col-lg-6">
						<input type="text" class="configKey"  data-l1key="debit" />
					</div>
				</div>
				<div class="form-group">
					<label class="col-lg-5 control-label">{{Pressioni maximal de l'arrivée d'eau (bar)}}</label>
					<div class="col-lg-6">
						<input type="text" class="configKey"  data-l1key="pression" />
					</div>
				</div>
			</fieldset>
		</form>
	</div>
	<div class="col-sm-6">
		<legend>{{Météo}}</legend>
		<form class="form-horizontal">
			<fieldset>
				<div class="form-group">
					<label class="col-lg-5 control-label">{{Maximum de la probabilité de précipitation (%)}}</label>
					<div class="col-lg-6">
						<div class="input-group">
							<input class="configKey form-control input-sm" data-l1key="cmdPrecipProbability"/>
							<span class="input-group-btn">
								<a class="btn btn-success btn-sm listAction">
									<i class="fa fa-list-alt"></i>
								</a>
							</span>
						</div>
						<input type="text" class="configKey"  data-l1key="precipProbability" />
					</div>
				</div>
				<div class="form-group">
					<label class="col-lg-5 control-label">{{Vitesse du vent maximum (km/h)}}</label>
					<div class="col-lg-6">
						<div class="input-group">
							<input class="configKey form-control input-sm" data-l1key="cmdWindSpeed"/>
							<span class="input-group-btn">
								<a class="btn btn-success btn-sm listAction">
									<i class="fa fa-list-alt"></i>
								</a>
							</span>
						</div>
						<input type="text" class="configKey"  data-l1key="windSpeed" />
					</div>
				</div>
				<div class="form-group">
					<label class="col-lg-5 control-label">{{Humidité maximum (%)}}</label>
					<div class="col-lg-6">
						<div class="input-group">
							<input class="configKey form-control input-sm" data-l1key="cmdHumidity"/>
							<span class="input-group-btn">
								<a class="btn btn-success btn-sm listAction">
									<i class="fa fa-list-alt"></i>
								</a>
							</span>
						</div>
						<input type="text" class="configKey"  data-l1key="humidity" />
					</div>
				</div>				
				<div class="form-group">
					<label class="col-lg-5 control-label">{{Précipitation de la veille}}</label>
					<div class="col-lg-6">
						<div class="input-group">
							<input class="configKey form-control input-sm" data-l1key="cmdPrecipitation"/>
							<span class="input-group-btn">
								<a class="btn btn-success btn-sm listAction">
									<i class="fa fa-list-alt"></i>
								</a>
							</span>
						</div>
					</div>
				</div>
			</fieldset>
		</form>
	</div>
	 <div class="col-sm-6">
		<legend>{{Type de plantation}}
			<a class="btn btn-success btn-xs pull-right cursor" id="bt_AddTypePlantation"><i class="fa fa-check"></i> {{Ajouter}}</a>
		</legend>
		<form class="form-horizontal">
			<fieldset>
				<div class="form-group">
					<table id="table_type_plantation" class="table table-bordered table-condensed tablesorter">
						<thead>
							<tr>
								<th>{{Type de plantation}}</th>
								<th>{{Pluviometerie (mm)}}</th>
							</tr>
						</thead>
						<tbody></tbody>
					</table>
				</div>
			</fieldset>
		</form>
	</div>
</div>
<script>
	$("body").on('click', ".listAction", function() {
		var el = $(this).closest('.input-group').find('input');
		jeedom.cmd.getSelectModal({}, function (result) {
			el.value(result.human);
		});
	});
	$.ajax({
		type: "POST",
		timeout:8000,
		url: "core/ajax/config.ajax.php",
		data: {
			action:'getKey',
			key:'{"configuration":""}',
			plugin:'arrosageAuto',
		},
		dataType: 'json',
		error: function(request, status, error) {
			handleAjaxError(request, status, error);
		},
		success: function(data) {
			if (data.state != 'ok') {
				$('#div_alert').showAlert({message: data.result, level: 'danger'});
				return;
			}
			if (data.result['configuration']!=''){
				var TypePlantation= new Object();
				$.each(data.result['configuration'], function(param,valeur){
					switch(typeof(valeur)){
						case 'object':
							$.each(valeur, function(TypePlantationkey,value ){
								if (typeof(TypePlantation[TypePlantationkey]) === 'undefined')
									TypePlantation[TypePlantationkey]= new Object();
								if (typeof(TypePlantation[TypePlantationkey]['configuration']) === 'undefined')
									TypePlantation[TypePlantationkey]['configuration']= new Object();
								TypePlantation[TypePlantationkey]['configuration'][param]=value;
							});
						break;
						case 'string':
							if (typeof(TypePlantation[0]) === 'undefined')
								TypePlantation[0]= new Object();
							if (typeof(TypePlantation[0]['configuration']) === 'undefined')
								TypePlantation[0]['configuration']= new Object();
							TypePlantation[0]['configuration'][param]=valeur;
						break;
					}
				});
				$.each(TypePlantation, function(id,data){
					AddTypePlantation($('#table_type_plantation tbody'),data);
				});
			}
		}
	});
	$('#bt_AddTypePlantation').on('click',function(){
		AddTypePlantation($('#table_type_plantation tbody'),'');
	});
	$('body').on('click','#bt_RemoveTypePlantation',function(){
		$(this).closest('tr').remove();
	});
	function AddTypePlantation(_el,data){
		var tr=$('<tr>')
			.append($('<td>')
				.append($('<div class="input-group">')
					.append($('<span class="input-group-btn">')
						.append($('<a class="btn btn-default btn-sm bt_RemoveTypePlantation">')
							.append($('<i class="fa fa-minus-circle">'))))
					.append($('<input class="configKey form-control input-sm "data-l1key="configuration" data-l2key="type">'))))
			.append($('<td>')
				.append($('<input class="configKey form-control input-sm" data-l1key="configuration" data-l2key="volume">')));

		_el.append(tr);
		_el.find('tr:last').setValues(data, '.configKey');
	}
</script>

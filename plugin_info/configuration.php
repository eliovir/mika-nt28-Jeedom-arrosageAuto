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
		<form>
			<fieldset>
				<div class="form-group">
					<label class="col-lg-4 control-label">{{Météo}}</label>
					<div class="col-lg-4">
						<div class="input-group">
							<input class="configKey form-control input-sm" data-l1key="meteo"/>
							<span class="input-group-btn">
								<a class="btn btn-success btn-sm listEqLogicAction">
									<i class="fa fa-list-alt"></i>
								</a>
							</span>
						</div>
					</div>
				</div>
				<div class="form-group">
					<label class="col-lg-4 control-label">{{Maximum de la probabilité de précipitation (%)}}</label>
					<div class="col-lg-4">
						<input type="text" class="configKey"  data-l1key="precipProbability" />
					</div>
				</div>
				<div class="form-group">
					<label class="col-lg-4 control-label">{{Vitesse du vent maximum (km/h)}}</label>
					<div class="col-lg-4">
						<input type="text" class="configKey"  data-l1key="windSpeed" />
					</div>
				</div>
				<div class="form-group">
					<label class="col-lg-4 control-label">{{Humidité maximum (%)}}</label>
					<div class="col-lg-4">
						<input type="text" class="configKey"  data-l1key="humidity" />
					</div>
				</div>
				<div class="form-group">
					<label class="col-lg-4 control-label">{{Debit de l'arriver d'eau}}</label>
					<div class="col-lg-4">
						<input type="text" class="configKey"  data-l1key="debit" />
					</div>
				</div>
			</fieldset>
		</form>
	</div>
	 <div class="col-sm-6">
		<legend>Type de plantation
			<a class="btn btn-success btn-xs pull-right cursor" id="bt_AddTypePlantation"><i class="fa fa-check"></i> {{Ajouter}}</a>
		</legend>
		<form>
			<fieldset>
				<div class="form-group">
					<table id="table_type_plantation" class="table table-bordered table-condensed tablesorter">
						<thead>
							<tr>
								<th>{{Type de plantation}}</th>
								<th>{{Volume / m²}}</th>
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
	$("body").on('click', ".listEqLogicAction", function() {
		var el = $(this).closest('.input-group').find('input');
		jeedom.eqLogic.getSelectModal({}, function (result) {
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
				switch(typeof(id)){
					case 'object':
						$.each(id, function(TypePlantationkey,value ){
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
						TypePlantation[0]['configuration'][param]=id;
					break;
				}
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

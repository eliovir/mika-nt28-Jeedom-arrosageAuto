<?php
	if (!isConnect('admin')) {
		throw new Exception('401 Unauthorized');
	}
$Branches=array();
foreach(eqLogic::byType('arrosageAuto') as $eqLogic){
  $Branches[]=array('id'=>$eqLogic->getId(),'name'=>$eqLogic->getName());
}
	sendVarToJS('Branches', $Branches);
	sendVarToJS('Programmations', config::byKey('Programmations', 'arrosageAuto'));
?>
<form class="form-horizontal">
	<fieldset>
		<legend>{{Les programmations de la zone :}}
			<sup>
				<i class="fa fa-question-circle tooltips" title="Saisir toutes les programmations pour la zone"></i>
			</sup>
			<a class="btn btn-success btn-xs ProgramationAttr" data-action="add" style="margin-left: 5px;">
				<i class="fa fa-plus-circle"></i>
				{{Ajouter une programmation}}
			</a>
		</legend>
		<table id="table_programation" class="table table-bordered table-condensed">
			<thead>
				<tr>
					<th style="width:30px;"></th>
					<th style="width:600px;">{{Jour actif}}</th>
					<th style="width:100px;">{{Heure}}</th>
					<th style="width:300px;">{{Branche}}</th>
				</tr>
			</thead>
			<tbody></tbody>
		</table>
	</fieldset>
</form>
<script>	
	$('.ProgramationAttr[data-action=add]').off().on('click',function(){
		addProgramation({},$(this).closest('fieldset').find('#table_programation'));
	});
	$.each(Programmations, function (index,_programation) {
		addProgramation(_programation,$('#table_programation'));
	});
	function addProgramation(_programation,  _el) {
		var Heure=$('<select class="expressionAttr form-control" data-l1key="Heure" >');
	    	var Minute=$('<select class="expressionAttr form-control" data-l1key="Minute" >');
		var number = 0;
	    	while (number < 24) {
			Heure.append($('<option value="'+number+'">')
				.text(number));
		number++;
		}
		number = 0;
	    	while (number < 60) {
			Minute.append($('<option value="'+number+'">')
				.text(number));
		number++;
		}
     	var Branche=$('<select class="expressionAttr form-control input-sm cmdAction" data-l1key="evaluation" multiple>');
      	for(var index in Branches) {
			Branche.append($('<option value="'+Branches[index].id+'">')
				.text(Branches[index].name));
		};
		var tr = $('<tr class="ProgramationGroup">')
			.append($('<td>')
				.append($('<span class="input-group-btn">')
					.append($('<a class="btn btn-default ProgramationAttr btn-sm" data-action="remove">')
						.append($('<i class="fa fa-minus-circle">')))))
			.append($('<td>')
				.append($('<label class="checkbox-inline">')
					.append($('<input type="checkbox" class="expressionAttr" data-l1key="1">'))
					.append('{{Lundi}}'))
				.append($('<label class="checkbox-inline">')
					.append($('<input type="checkbox" class="expressionAttr" data-l1key="2">'))
					.append('{{Mardi}}'))
				.append($('<label class="checkbox-inline">')
					.append($('<input type="checkbox" class="expressionAttr" data-l1key="3">'))
					.append('{{Mercredi}}'))
				.append($('<label class="checkbox-inline">')
					.append($('<input type="checkbox" class="expressionAttr" data-l1key="4">'))
					.append('{{Jeudi}}'))
				.append($('<label class="checkbox-inline">')
					.append($('<input type="checkbox" class="expressionAttr" data-l1key="5">'))
					.append('{{Vendredi}}'))
				.append($('<label class="checkbox-inline">')
					.append($('<input type="checkbox" class="expressionAttr" data-l1key="6">'))
					.append('{{Samedi}}'))
				.append($('<label class="checkbox-inline">')
					.append($('<input type="checkbox" class="expressionAttr" data-l1key="0" />'))
					.append('{{Dimanche}}')))
			.append($('<td>')
				.append(Heure)
				.append(Minute))
			.append($('<td>').append(Branche));
		_el.append(tr);
		_el.find('tr:last').setValues(_programation, '.expressionAttr');
		$('.ProgramationAttr[data-action=remove]').off().on('click',function(){
			$(this).closest('tr').remove();
		});
	}
</script>

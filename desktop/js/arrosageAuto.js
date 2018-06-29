$("#table_cmd").sortable({axis: "y", cursor: "move", items: ".cmd", placeholder: "ui-state-highlight", tolerance: "intersect", forcePlaceholderSize: true});
$("#table_arroseur").sortable({axis: "y", cursor: "move", items: ".ArroseurGroup", placeholder: "ui-state-highlight", tolerance: "intersect", forcePlaceholderSize: true});
$("#table_programation").sortable({axis: "y", cursor: "move", items: ".ProgramationGroup", placeholder: "ui-state-highlight", tolerance: "intersect", forcePlaceholderSize: true});
$("#table_condition").sortable({axis: "y", cursor: "move", items: ".ConditionGroup", placeholder: "ui-state-highlight", tolerance: "intersect", forcePlaceholderSize: true});
$("#table_action").sortable({axis: "y", cursor: "move", items: ".ActionGroup", placeholder: "ui-state-highlight", tolerance: "intersect", forcePlaceholderSize: true});
$('.bt_showExpressionTest').off('click').on('click', function () {
  $('#md_modal').dialog({title: "{{Testeur d'expression}}"});
  $("#md_modal").load('index.php?v=d&modal=expression.test').dialog('open');
});
$('#bt_planArrosageAuto').off('click').on('click', function () {
	$('#md_modal').dialog({title: "{{Plan d'arrosage}}"});
	$('#md_modal').load('index.php?v=d&plugin=arrosageAuto&modal=plan').dialog('open');
});
$('#bt_programArrosageAuto').off('click').on('click', function () {
	bootbox.dialog({
		title: "{{Programmation de l'arrosage}}",
		message: $('<div>').load('index.php?v=d&plugin=arrosageAuto&modal=programmation'),
		size: "large",
		buttons: {
			"Annuler": {
				className: "btn-default"
			},
			success: {
				label: "Valider",
				className: "btn-primary",
				callback: function () {
					var ProgramationArray= new Array();
					$('#table_programation .ProgramationGroup').each(function( index ) {
						ProgramationArray.push($(this).getValues('.expressionAttr')[0])
					});
					jeedom.config.save({
						plugin:'arrosageAuto',
						configuration: {'Programmations': ProgramationArray},
						error: function (error) {
							$('#div_alert').showAlert({message: error.message, level: 'danger'});
						},
						success: function () {
							$('#div_alert').showAlert({message: '{{Sauvegarde réussie}}', level: 'success'});
						 }
					});
				}
			},
		}
	});
});
$('#bt_healthArrosageAuto').off('click').on('click', function () {
	$('#md_modal').dialog({title: "{{Santé des zones d'arrosage}}"});
	$('#md_modal').load('index.php?v=d&plugin=arrosageAuto&modal=health').dialog('open');
});
function saveEqLogic(_eqLogic) {
	_eqLogic.configuration.arroseur=new Object();
	_eqLogic.configuration.condition=new Object();
	_eqLogic.configuration.action=new Object();
	var ConditionArray= new Array();
	var ActionArray= new Array();
	var ArroseurArray= new Array();
	$('#arroseurtab .ArroseurGroup').each(function( index ) {
		ArroseurArray.push($(this).getValues('.expressionAttr')[0])
	});
	$('#conditiontab .ConditionGroup').each(function( index ) {
		ConditionArray.push($(this).getValues('.expressionAttr')[0])
	});
	$('#actiontab .ActionGroup').each(function( index ) {
		ActionArray.push($(this).getValues('.expressionAttr')[0])
	});
	_eqLogic.configuration.arroseur=ArroseurArray;
	_eqLogic.configuration.condition=ConditionArray;
	_eqLogic.configuration.action=ActionArray;
   	return _eqLogic;
}
function printEqLogic(_eqLogic) {
	$('.ArroseurGroup').remove();
	$('.ConditionGroup').remove();
	$('.ActionGroup').remove();
	if (typeof(_eqLogic.configuration.arroseur) !== 'undefined') {
		for(var index in _eqLogic.configuration.arroseur) {
			if( (typeof _eqLogic.configuration.arroseur[index] === "object") && (_eqLogic.configuration.arroseur[index] !== null) )
				addArroseur(_eqLogic.configuration.arroseur[index],$('#arroseurtab').find('table tbody'));
		}
	}
	if (typeof(_eqLogic.configuration.condition) !== 'undefined') {
		for(var index in _eqLogic.configuration.condition) {
			if( (typeof _eqLogic.configuration.condition[index] === "object") && (_eqLogic.configuration.condition[index] !== null) )
				addCondition(_eqLogic.configuration.condition[index],$('#conditiontab').find('table tbody'));
		}
	}
	if (typeof(_eqLogic.configuration.action) !== 'undefined') {
		for(var index in _eqLogic.configuration.action) {
			if( (typeof _eqLogic.configuration.action[index] === "object") && (_eqLogic.configuration.action[index] !== null) )
				addAction(_eqLogic.configuration.action[index],$('#actiontab').find('table tbody'));
		}
	}
}
function addArroseur(_arroseur,  _el) {
	var tr = $('<tr class="ArroseurGroup">')
	tr.append($('<td>')
		.append($('<span class="input-group-btn">')
			.append($('<a class="btn btn-default ArroseurAttr btn-sm" data-action="remove">')
				.append($('<i class="fa fa-minus-circle">')))));	
	tr.append($('<td>')
		.append($('<select class="expressionAttr form-control" data-l1key="Type" >')
			.append($('<option value="gouteAgoute">')
				.text('{{Goutte à goutte}}'))
			.append($('<option value="tuyere">')
				.text('{{Tuyère}}'))
			.append($('<option value="turbine">')
				.text('{{Turbine}}'))));
	tr.append($('<td>')
		  .append($('<input class="expressionAttr form-control" data-l1key="Debit" placeholder="Saisir le débit de votre arroseur (mm ou L/H)"/>')));
	tr.append($('<td>')
		  .append($('<input class="expressionAttr form-control" data-l1key="Pression" placeholder="Saisir la pression minimal de votre arroseur"/>')));
	tr.append($('<td>')
		  .append($('<div class="gouteAgoute">')
			.append($('<input class="expressionAttr form-control" data-l1key="EspacementLateral" placeholder="Saisir l\'espacement latéral (cm)"/>'))
			.append($('<input class="expressionAttr form-control" data-l1key="EspacemenGoutteurs" placeholder="Saisir l\'espacement des goutteurs (cm)"/>')))
		  .append($('<div class="tuyere">')
			  .append($('<select class="expressionAttr form-control" data-l1key="Quart" >')
				.append($('<option value="0,25">')
					.text('{{1/4}}'))
				.append($('<option value="0,5">')
					.text('{{2/4}}'))
				.append($('<option value="0,75">')
					.text('{{3/4}}'))
				.append($('<option value="1">')
					.text('{{4/4}}'))))		
		  .append($('<div class="turbine">')
			  .append($('<input class="expressionAttr form-control" data-l1key="Distance" placeholder="{{Saisir la distance d\'arrosage (m)}}"/>'))
			  .append($('<input class="expressionAttr form-control" data-l1key="Angle" placeholder="{{Saisir l\'angle d\'arrosage de votre turbine}}" />'))));
	_el.append(tr);
	_el.find('tr:last .gouteAgoute').hide();
	_el.find('tr:last .tuyere').hide();
	_el.find('tr:last .turbine').hide();
	$('.ArroseurAttr[data-action=remove]').off().on('click',function(){
		$(this).closest('tr').remove();
	});
	$('.ArroseurGroup .expressionAttr[data-l1key=Type]').off().on('change',function(){
		switch($(this).val()){
			case 'gouteAgoute':
				$(this).closest('tr').find('.gouteAgoute').show();
				$(this).closest('tr').find('.tuyere').hide();
				$(this).closest('tr').find('.turbine').hide();
			break;
			case 'tuyere':
				$(this).closest('tr').find('.gouteAgoute').hide();
				$(this).closest('tr').find('.tuyere').show();
				$(this).closest('tr').find('.turbine').hide();
			break;
			case 'turbine':
				$(this).closest('tr').find('.gouteAgoute').hide();
				$(this).closest('tr').find('.tuyere').hide();
				$(this).closest('tr').find('.turbine').show();
			break;
		 }
	});
        _el.find('tr:last').setValues(_arroseur, '.expressionAttr');
}
function addCondition(_condition,_el) {
	var tr = $('<tr class="ConditionGroup">')
		.append($('<td>')
			.append($('<input type="checkbox" class="expressionAttr" data-l1key="enable" checked/>')))
		.append($('<td>')
			.append($('<div class="input-group">')
				.append($('<span class="input-group-btn">')
					.append($('<a class="btn btn-default conditionAttr btn-sm" data-action="remove">')
						.append($('<i class="fa fa-minus-circle">'))))
				.append($('<input class="expressionAttr form-control input-sm cmdCondition" data-l1key="expression"/>'))
				.append($('<span class="input-group-btn">')
					.append($('<a class="btn btn-warning btn-sm listCmdCondition">')
						.append($('<i class="fa fa-list-alt">'))))));

        _el.append(tr);
        _el.find('tr:last').setValues(_condition, '.expressionAttr');
	$('.conditionAttr[data-action=remove]').off().on('click',function(){
		$(this).closest('tr').remove();
	});  
}
function addAction(_action,  _el) {
	var tr = $('<tr class="ActionGroup">')
		.append($('<td>')
			.append($('<input type="checkbox" class="expressionAttr" data-l1key="enable"/>')))
		.append($('<td>')
			.append($('<div class="input-group">')
				.append($('<span class="input-group-btn">')
					.append($('<a class="btn btn-default ActionAttr btn-sm" data-action="remove">')
						.append($('<i class="fa fa-minus-circle">'))))
				.append($('<input class="expressionAttr form-control input-sm cmdAction" data-l1key="cmd"/>'))
				.append($('<span class="input-group-btn">')
					.append($('<a class="btn btn-success btn-sm listAction" title="Sélectionner un mot-clé">')
						.append($('<i class="fa fa-tasks">')))
					.append($('<a class="btn btn-success btn-sm listCmdAction">')
						.append($('<i class="fa fa-list-alt">'))))))
		.append($('<td>')
		       .append($(jeedom.cmd.displayActionOption(init(_action.cmd, ''), _action.options))))
		.append($('<td>')
			.append($('<select class="expressionAttr form-control" data-l1key="Type">')
				.append($('<option value="">')
					.text('{{Tous}}'))
				.append($('<option value="start">')
					.text('{{Start}}'))
				.append($('<option value="stop">')
					.text('{{Stop}}'))));
        _el.append(tr);
        _el.find('tr:last').setValues(_action, '.expressionAttr');
	$('.ActionAttr[data-action=remove]').off().on('click',function(){
		$(this).closest('tr').remove();
	});
 }
$('.ArroseurAttr[data-action=add]').off().on('click',function(){
	addArroseur({},$(this).closest('.tab-pane').find('table'));
});
$('.conditionAttr[data-action=add]').off().on('click',function(){
	addCondition({},$(this).closest('.tab-pane').find('table'));
});
$('body').on('click','.listCmdCondition',function(){
	var el = $(this).closest('tr').find('.expressionAttr[data-l1key=expression]');
	jeedom.cmd.getSelectModal({cmd: {type: 'info'}}, function (result) {
		var message = 'Aucun choix possible';
		if(result.cmd.subType == 'numeric'){
			message = '<div class="row">  ' +
			'<div class="col-md-12"> ' +
			'<form class="form-horizontal" onsubmit="return false;"> ' +
			'<div class="form-group"> ' +
			'<label class="col-xs-5 control-label" >'+result.human+' {{est}}</label>' +
			'             <div class="col-xs-3">' +
			'                <select class="conditionAttr form-control" data-l1key="operator">' +
			'                    <option value="==">{{égal}}</option>' +
			'                  <option value=">">{{supérieur}}</option>' +
			'                  <option value="<">{{inférieur}}</option>' +
			'                 <option value="!=">{{différent}}</option>' +
			'            </select>' +
			'       </div>' +
			'      <div class="col-xs-4">' +
			'         <input type="number" class="conditionAttr form-control" data-l1key="operande" />' +
			'    </div>' +
			'</div>' +
			'<div class="form-group"> ' +
			'<label class="col-xs-5 control-label" >{{Ensuite}}</label>' +
			'             <div class="col-xs-3">' +
			'                <select class="conditionAttr form-control" data-l1key="next">' +
			'                    <option value="">rien</option>' +
			'                  <option value="OU">{{ou}}</option>' +
			'            </select>' +
			'       </div>' +
			'</div>' +
			'</div> </div>' +
			'</form> </div>  </div>';
		}
		if(result.cmd.subType == 'string'){
			message = '<div class="row">  ' +
			'<div class="col-md-12"> ' +
			'<form class="form-horizontal" onsubmit="return false;"> ' +
			'<div class="form-group"> ' +
			'<label class="col-xs-5 control-label" >'+result.human+' {{est}}</label>' +
			'             <div class="col-xs-3">' +
			'                <select class="conditionAttr form-control" data-l1key="operator">' +
			'                    <option value="==">{{égale}}</option>' +
			'                  <option value="matches">{{contient}}</option>' +
			'                 <option value="!=">{{différent}}</option>' +
			'            </select>' +
			'       </div>' +
			'      <div class="col-xs-4">' +
			'         <input class="conditionAttr form-control" data-l1key="operande" />' +
			'    </div>' +
			'</div>' +
			'<div class="form-group"> ' +
			'<label class="col-xs-5 control-label" >{{Ensuite}}</label>' +
			'             <div class="col-xs-3">' +
			'                <select class="conditionAttr form-control" data-l1key="next">' +
			'                    <option value="">{{rien}}</option>' +
			'                  <option value="OU">{{ou}}</option>' +
			'            </select>' +
			'       </div>' +
			'</div>' +
			'</div> </div>' +
			'</form> </div>  </div>';
		}
		if(result.cmd.subType == 'binary'){
			message = '<div class="row">  ' +
			'<div class="col-md-12"> ' +
			'<form class="form-horizontal" onsubmit="return false;"> ' +
			'<div class="form-group"> ' +
			'<label class="col-xs-5 control-label" >'+result.human+' {{est}}</label>' +
			'            <div class="col-xs-7">' +
			'                 <input class="conditionAttr" data-l1key="operator" value="==" style="display : none;" />' +
			'                  <select class="conditionAttr form-control" data-l1key="operande">' +
			'                       <option value="1">{{Ouvert}}</option>' +
			'                       <option value="0">{{Fermé}}</option>' +
			'                       <option value="1">{{Allumé}}</option>' +
			'                       <option value="0">{{Éteint}}</option>' +
			'                       <option value="1">{{Déclenché}}</option>' +
			'                       <option value="0">{{Au repos}}</option>' +
			'                       </select>' +
			'                    </div>' +
			'                 </div>' +
			'<div class="form-group"> ' +
			'<label class="col-xs-5 control-label" >{{Ensuite}}</label>' +
			'             <div class="col-xs-3">' +
			'                <select class="conditionAttr form-control" data-l1key="next">' +
			'                  <option value="">{{rien}}</option>' +
			'                  <option value="OU">{{ou}}</option>' +
			'            </select>' +
			'       </div>' +
			'</div>' +
			'</div> </div>' +
			'</form> </div>  </div>';
		}

		bootbox.dialog({
			title: "{{Ajout d'une nouvelle condition}}",
			message: message,
			buttons: {
				"Ne rien mettre": {
					className: "btn-default",
					callback: function () {
						el.atCaret('insert', result.human);
					}
				},
				success: {
					label: "Valider",
					className: "btn-primary",
					callback: function () {
    						var condition = result.human;
						condition += ' ' + $('.conditionAttr[data-l1key=operator]').value();
						if(result.cmd.subType == 'string'){
							if($('.conditionAttr[data-l1key=operator]').value() == 'matches'){
								condition += ' "/' + $('.conditionAttr[data-l1key=operande]').value()+'/"';
							}else{
								condition += ' "' + $('.conditionAttr[data-l1key=operande]').value()+'"';
							}
						}else{
							condition += ' ' + $('.conditionAttr[data-l1key=operande]').value();
						}
						condition += ' ' + $('.conditionAttr[data-l1key=next]').value()+' ';
						el.atCaret('insert', condition);
						if($('.conditionAttr[data-l1key=next]').value() != ''){
							el.click();
						}
					}
				},
			}
		});
	});
});
$('.ActionAttr[data-action=add]').off().on('click',function(){
	addAction({},$(this).closest('.tab-pane').find('table'));
});
$("body").on('click', ".listAction", function() {
	var el = $(this).closest('.form-group').find('.expressionAttr[data-l1key=cmd]');
	jeedom.getSelectActionModal({}, function (result) {
		el.value(result.human);
		jeedom.cmd.displayActionOption(el.value(), '', function (html) {
			el.closest('.form-group').find('.actionOptions').html(html);
		});
	});
});
$("body").on('click', ".listCmdAction", function() {
	var el = $(this).closest('td').find('.expressionAttr[data-l1key=cmd]');
	jeedom.cmd.getSelectModal({cmd: {type: 'action'}}, function (result) {
		el.value(result.human);
		jeedom.cmd.displayActionOption(el.value(), '', function (html) {
			el.closest('.form-group').find('.actionOptions').html(html);
		});
	});
});
function addCmdToTable(_cmd) {
	var tr =$('<tr class="cmd" data-cmd_id="' + init(_cmd.id) + '">');
	tr.append($('<td>')
		.append($('<input type="hidden" class="cmdAttr form-control input-sm" data-l1key="id">'))
		.append($('<input class="cmdAttr form-control input-sm" data-l1key="name" value="' + init(_cmd.name) + '" placeholder="{{Name}}" title="Name">')));
	tr.append($('<td>')
		  .append($('<div>')
			.append($('<input class="tooltips cmdAttr form-control input-sm" data-l1key="configuration" data-l2key="minValue" placeholder="{{Min}}" title="{{Min}}" >'))
			.append($('<input class="tooltips cmdAttr form-control input-sm" data-l1key="configuration" data-l2key="maxValue" placeholder="{{Max}}" title="{{Max}}" >')))
		  .append($('<div>')
			.append($('<span class="type" type="' + init(_cmd.type) + '">')
				.append(jeedom.cmd.availableType()))
			.append($('<span class="subType" subType="'+init(_cmd.subType)+'">'))));
	
	var parmetre=$('<td>');
	if (is_numeric(_cmd.id)) {
		parmetre.append($('<a class="btn btn-default btn-xs cmdAction" data-action="test">')
			.append($('<i class="fa fa-rss">')
				.text('{{Tester}}')));
	}
	parmetre.append($('<a class="btn btn-default btn-xs cmdAction tooltips" data-action="configure">')
		.append($('<i class="fa fa-cogs">')));
	parmetre.append($('<div>')
		.append($('<span>')
			.append($('<label class="checkbox-inline">')
				.append($('<input type="checkbox" class="cmdAttr checkbox-inline" data-size="mini" data-label-text="{{Historiser}}" data-l1key="isHistorized" checked/>'))
				.append('{{Historiser}}')
				.append($('<sup>')
					.append($('<i class="fa fa-question-circle tooltips" style="font-size : 1em;color:grey;">')
					.attr('title','Souhaitez-vous historiser les changements de valeur ?'))))));
	parmetre.append($('<div>')
		.append($('<span>')
			.append($('<label class="checkbox-inline">')
				.append($('<input type="checkbox" class="cmdAttr checkbox-inline" data-size="mini" data-label-text="{{Afficher}}" data-l1key="isVisible" checked/>'))
				.append('{{Afficher}}')
				.append($('<sup>')
					.append($('<i class="fa fa-question-circle tooltips" style="font-size : 1em;color:grey;">')
					.attr('title','Souhaitez-vous afficher cette commande sur le dashboard ?'))))));
	tr.append(parmetre);
	$('#table_cmd tbody').append(tr);
	$('#table_cmd tbody tr:last').setValues(_cmd, '.cmdAttr');
	jeedom.cmd.changeType($('#table_cmd tbody tr:last'), init(_cmd.subType));
}

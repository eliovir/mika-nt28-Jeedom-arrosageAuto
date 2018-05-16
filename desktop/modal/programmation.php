<?php
  if (!isConnect('admin')) {
    throw new Exception('401 Unauthorized');
  }
  $eqLogics = ChauffeEau::byType('arrosageAuto ');
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
  </fieldset>
</form>
<table id="table_programation" class="table table-bordered table-condensed">
  <thead>
    <tr>
      <th></th>
      <th>{{Jour actif}}</th>
      <th>{{Heure}}</th>
      <th>{{Branche}}</th>
    </tr>
  </thead>
  <tbody>
  	 <?php
foreach ($eqLogics as $eqLogic) {
	echo '<td><span class="label label-info" style="font-size : 1em; cursor : default;">' . $eqLogic->getId() . '</span></td>';
	echo '<td><span class="label label-info" style="font-size : 1em; cursor : default;">' . $eqLogic->getName() . '</span></td>';
	$status = '<span class="label label-success" style="font-size : 1em;cursor:default;">{{OK}}</span>';
	if ($eqLogic->getStatus('state') == 'nok') {
		$status = '<span class="label label-danger" style="font-size : 1em;cursor:default;">{{NOK}}</span>';
	}
	echo '<td>' . $status . '</td>';
	echo '<td><span class="label label-info" style="font-size : 1em;cursor:default;">' . $eqLogic->getStatus('lastCommunication') . '</span></td>';
	echo '<td><span class="label label-info" style="font-size : 1em;cursor:default;">' . $eqLogic->getConfiguration('createtime') . '</span></td></tr>';
}
?>
</tbody>
</table>

?>

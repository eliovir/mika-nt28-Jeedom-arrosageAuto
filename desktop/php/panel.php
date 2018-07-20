<?php
if (!isConnect()) {
	throw new Exception('{{401 - Accès non autorisé}}');
}
if (init('object_id') == '') {
	$_GET['object_id'] = $_SESSION['user']->getOptions('defaultDashboardObject');
}
$object = object::byId(init('object_id'));
if (!is_object($object)) {
	$object = object::rootObject();
}
if (!is_object($object)) {
	throw new Exception('{{Aucun objet racine trouvé}}');
}

sendVarToJs('object_id', $object->getId());
sendVarToJs('groupBy', init('groupBy', 'day'));
if (init('groupBy', 'day') == 'day') {
	$date = array(
		'start' => init('startDate', date('Y-m-d', strtotime('-31 days ' . date('Y-m-d')))),
		'end' => init('endDate', date('Y-m-d')),
	);
}
if (init('groupBy', 'day') == 'month') {
	$date = array(
		'start' => init('startDate', date('Y-m-d', strtotime('-1 year ' . date('Y-m-d')))),
		'end' => init('endDate', date('Y-m-d', strtotime('+1 days' . date('Y-m-d')))),
	);
}
?>
<div style="position : fixed;height:100%;width:15px;top:50px;left:0px;z-index:998;background-color:#f6f6f6;" id="bt_displayObjectList"><i class="fa fa-arrow-circle-o-right" style="color : #b6b6b6;"></i></div>
<div class="row row-overflow" id="div_arrosageAuto">
	<div class="col-xs-2" id="sd_objectList" style="z-index:999">
		<div class="bs-sidebar">
			<ul id="ul_object" class="nav nav-list bs-sidenav">
				<li class="nav-header">{{Liste objets}}</li>
				<li class="filter" style="margin-bottom: 5px;"><input class="filter form-control input-sm" placeholder="{{Rechercher}}" style="width: 100%"/></li>
				<?php
				$allObject = object::buildTree();
				foreach ($allObject as $object_li) {
					if(count($object_li->getEqLogic(true, false, 'arrosageAuto') > 0 && $object_li->getIsVisible() == 1) {
						$margin = 15 * $object_li->parentNumber();
						if ($object_li->getId() == $object->getId()) {
							echo '<li class="cursor li_object active" ><a href="index.php?v=d&m=arrosageAuto&p=panel&object_id=' . $object_li->getId() . '" style="position:relative;left:' . $margin . 'px;">' . $object_li->getHumanName(true) . '</a></li>';
						} else {
							echo '<li class="cursor li_object" ><a href="index.php?v=d&m=arrosageAuto&p=panel&object_id=' . $object_li->getId() . '" style="position:relative;left:' . $margin . 'px;">' . $object_li->getHumanName(true) . '</a></li>';
						}
					}
				}
				?>
			</ul>
		</div>
	</div>
	<div class="col-xs-10" id="div_graphiqueDisplay">
		<legend style="height: 40px;">
			<i class="fa fa-picture-o"></i>  <span class="objectName"></span> {{du}}
			<input class="form-control input-sm in_datepicker" id='in_startDate' style="display : inline-block; width: 150px;" value='<?php echo $date['start']?>'/> {{au}}
			<input class="form-control input-sm in_datepicker" id='in_endDate' style="display : inline-block; width: 150px;" value='<?php echo $date['end']?>'/>
			<a class="btn btn-success btn-sm tooltips" id='bt_validChangeDate' title="{{Attention une trop grande plage de dates peut mettre très longtemps à être calculée ou même ne pas s'afficher}}">{{Ok}}</a>
			<span class="pull-right">
			<?php
			if (init('groupBy', 'day') == 'day') {
				echo '<a class="btn btn-primary btn-sm" href="index.php?v=d&m=arrosageAuto&p=panel&groupBy=day&object_id=' . $object->getId() . '">{{Jour}}</a> ';
			} else {
				echo '<a class="btn btn-default btn-sm" href="index.php?v=d&m=arrosageAuto&p=panel&groupBy=day&object_id=' . $object->getId() . '">{{Jour}}</a> ';
			}
			if (init('groupBy', 'day') == 'month') {
				echo '<a class="btn btn-primary btn-sm" href="index.php?v=d&m=arrosageAuto&p=panel&groupBy=month&object_id=' . $object->getId() . '">{{Mois}}</a> ';
			} else {
				echo '<a class="btn btn-default btn-sm" href="index.php?v=d&m=arrosageAuto&p=panel&groupBy=month&object_id=' . $object->getId() . '">{{Mois}}</a> ';
			}
			?>
		</legend>
		<div class="row">
			<div class="col-lg-6">
				<legend><i class="fa fa-eur"></i>  {{Pluviometerie}}</legend>
				<div id='div_graphPluviometerie'></div>
			</div>
		</div>
	</div>
</div>
<?php include_file('desktop', 'panel', 'js', 'arrosageAuto');?>

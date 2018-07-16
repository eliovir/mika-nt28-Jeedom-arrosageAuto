<?php
try {
	require_once dirname(__FILE__) . '/../../../../core/php/core.inc.php';
	include_file('core', 'authentification', 'php');

	if (!isConnect('admin')) {
		throw new Exception(__('401 - Accès non autorisé', __FILE__));
	}
	
	if (init('action') == 'PlanImg') {
		if (isset($_FILES['PlanImg'])){
			$uploaddir = 'plugins/arrosageAuto/plan/';
			if(file_exists($uploaddir))
				exec('sudo rm -R '.$uploaddir);
			exec('sudo mkdir -p '.$uploaddir);
			exec('sudo chmod 777 -R '.$uploaddir);
			$uploadfile = $uploaddir . basename($_FILES['PlanImg']['name']);
			if (move_uploaded_file($_FILES['PlanImg']['tmp_name'], $uploadfile))
				ajax::success(basename($_FILES['PlanImg']['name']));
			else
				ajax::error('');
		}
		ajax::error('');
	}
	if (init('action') == 'getData') {
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
		$date = array(
			'start' => init('dateStart'),
			'end' => init('dateEnd'),
		);
		if ($date['start'] == '') {
			$date['start'] = date('Y-m-d', strtotime('-1 month'));
			if (init('groupBy', 'day') == 'month') {
				$date['start'] = date('Y-m-d', strtotime('-1 year'));
			}
		}
		if ($date['end'] == '') {
			$date['end'] = date('Y-m-d');
		}
		$arrosageAuto = arrosageAuto::getGraph($date['start'], $date['end'], $object->getId());
		ajax::success(array(
			'datas' => $arrosageAuto,
			'date' => $date,
			'object' => utils::o2a($object)
		));
	}
	throw new Exception(__('Aucune méthode correspondante à : ', __FILE__) . init('action'));
	/*     * *********Catch exeption*************** */
} catch (Exception $e) {
	ajax::error(displayExeption($e), $e->getCode());
}
?>

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
			echo '<pre>';
			if (move_uploaded_file($_FILES['PlanImg']['tmp_name'], $uploadfile))
				ajax::success($uploadfile);
			else
				ajax::error('');
		}
		ajax::error('');
	}
	throw new Exception(__('Aucune méthode correspondante à : ', __FILE__) . init('action'));
	/*     * *********Catch exeption*************** */
} catch (Exception $e) {
	ajax::error(displayExeption($e), $e->getCode());
}
?>

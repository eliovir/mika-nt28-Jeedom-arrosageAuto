<?php
require_once dirname(__FILE__) . '/../../../core/php/core.inc.php';
function arrosageAuto_install(){
}
function arrosageAuto_update(){
	log::add('arrosageAuto','debug','Lancement du script de mise à jour'); 
	foreach(eqLogic::byType('arrosageAuto') as $arrosageAuto){
		//Mise a jour des statistique
		$cache = cache::byKey('arrosageAuto::Statistique::'.$arrosageAuto->getId());
		if(is_object($cache))
			$cache->remove();
		$arrosageAuto->save();
	}
	log::add('arrosageAuto','debug','Fin du script de mise à jour');
}
function arrosageAuto_remove(){
}
?>

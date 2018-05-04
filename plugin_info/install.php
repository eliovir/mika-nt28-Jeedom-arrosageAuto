<?php
require_once dirname(__FILE__) . '/../../../core/php/core.inc.php';
function arrosageAuto_install(){
}
function arrosageAuto_update(){
	log::add('arrosageAuto','debug','Lancement du script de mise a jours'); 
	foreach(eqLogic::byType('arrosageAuto') as $arrosageAuto){
		$arrosageAuto->save();
	}
	log::add('arrosageAuto','debug','Fin du script de mise a jours');
}
function arrosageAuto_remove(){
}
?>

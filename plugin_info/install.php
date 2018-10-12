<?php
require_once dirname(__FILE__) . '/../../../core/php/core.inc.php';
function arrosageAuto_install(){
}
function arrosageAuto_update(){
	log::add('arrosageAuto','debug','Lancement du script de mise a jours'); 
	$ProgramationCenter=config::byKey('Programmations', 'arrosageAuto');
	if($ProgramationCenter == '')
		$ProgramationCenter=array();
	foreach(eqLogic::byType('arrosageAuto') as $arrosageAuto){
		foreach($arrosageAuto->getConfiguration('programation') as $programmation){
			$ProgramationCenter[]=$programmation;
		}
		$arrosageAuto->setConfiguration('programation','');
		$cron = cron::byClassAndFunction('arrosageAuto', 'pull',array('Zone_id' => $arrosageAuto->getId()));
		if (is_object($cron))
			$cron->remove();
		$cron = cron::byClassAndFunction('arrosageAuto', 'pull',array('id' => $arrosageAuto->getId()));
		if (is_object($cron)) 	
			$cron->remove();
		$arrosageAuto->save();
		
		//Mise a jour des statistique
		$cache = cache::byKey('arrosageAuto::Statistique::'.$arrosageAuto->getId()));
		if(is_object($cache))
			$cache->remove()
	}
	config::save('Programmations', $ProgramationCenter,'arrosageAuto');
	log::add('arrosageAuto','debug','Fin du script de mise a jours');
}
function arrosageAuto_remove(){
}
?>

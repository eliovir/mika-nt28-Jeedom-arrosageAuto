<?php
require_once dirname(__FILE__) . '/../../../../core/php/core.inc.php';
class arrosageAuto extends eqLogic {
	public static function deamon_info() {
		$return = array();
		$return['log'] = 'arrosageAuto';
		$return['launchable'] = 'ok';
		$return['state'] = 'nok';
	
		$return['state'] = 'ok';
		return $return;
	}
	public static function deamon_start($_debug = false) {
		log::remove('arrosageAuto');
		self::deamon_stop();
		$deamon_info = self::deamon_info();
		if ($deamon_info['launchable'] != 'ok') 
			return;
		if ($deamon_info['state'] == 'ok') 
			return;
	}
	public static function deamon_stop() {	
		
	}
	public function ExecuteAction($Action) {	
		foreach($Action as $cmd){
			if (isset($cmd['enable']) && $cmd['enable'] == 0)
				continue;
			try {
				$options = array();
				if (isset($cmd['options'])) 
					$options = $cmd['options'];
				scenarioExpression::createAndExec('action', $cmd['cmd'], $options);
			} catch (Exception $e) {
				log::add('arrosageAuto', 'error',$this->getHumanName().' : '. __('Erreur lors de l\'exécution de ', __FILE__) . $action['cmd'] . __('. Détails : ', __FILE__) . $e->getMessage());
			}
			$Commande=cmd::byId(str_replace('#','',$cmd['cmd']));
			if(is_object($Commande)){
				if($this->getConfiguration('isRandom'))
				   sleep(rand(0,$this->getConfiguration('DelaisPresence')));
				log::add('arrosageAuto','debug',$this->getHumanName().' : Exécution de '.$Commande->getHumanName());
				$Commande->event($cmd['options']);
			}
		}
	}
	public function CreateCron($Schedule, $logicalId) {
		$cron =cron::byClassAndFunction('arrosageAuto', $logicalId, array('arrosageAuto_id' => $this->getId()));
			if (!is_object($cron)) {
				$cron = new cron();
				$cron->setClass('arrosageAuto');
				$cron->setFunction($logicalId);
				$cron->setOption(array('arrosageAuto_id' => $this->getId()));
				$cron->setEnable(1);
				$cron->setDeamon(0);
				$cron->setSchedule($Schedule);
				$cron->save();
			}else{
				$cron->setSchedule($Schedule);
				$cron->save();
			}
		return $cron;
	}
	public function EvaluateCondition($Evenement,$Saison,$TypeGestion){
		foreach($this->getConfiguration('condition') as $condition){
			if($condition['evaluation']!=$Evenement && $condition['evaluation']!='all')
				continue;
			if($condition['saison']!=$Saison && $condition['saison']!='all')
				continue;
			if(stripos($condition['TypeGestion'],$TypeGestion) === false && $condition['TypeGestion']!='all')	
				continue;		
			if (isset($condition['enable']) && $condition['enable'] == 0)
				continue;
			$_scenario = null;
			$expression = scenarioExpression::setTags($condition['expression'], $_scenario, true);
			$message = __('Evaluation de la condition : [', __FILE__) . trim($expression) . '] = ';
			$result = evaluate($expression);
			if (is_bool($result)) {
				if ($result) {
					$message .= __('Vrai', __FILE__);
				} else {
					$message .= __('Faux', __FILE__);
				}
			} else {
				$message .= $result;
			}
			log::add('arrosageAuto','info',$this->getHumanName().' : '.$message);
			if(!$result){
				log::add('arrosageAuto','info',$this->getHumanName().' : Les conditions ne sont pas remplies');
				return false;
			}
		}
		return true;
	}
	public static function AddCommande($eqLogic,$Name,$_logicalId,$Type="info", $SubType='binary',$visible,$Template='') {
		$Commande = $eqLogic->getCmd(null,$_logicalId);
		if (!is_object($Commande))
		{
			$Commande = new arrosageAutoCmd();
			$Commande->setId(null);
			$Commande->setName($Name);
			$Commande->setIsVisible($visible);
			$Commande->setLogicalId($_logicalId);
			$Commande->setEqLogic_id($eqLogic->getId());
			$Commande->setType($Type);
			$Commande->setSubType($SubType);
		}
   		$Commande->setTemplate('dashboard',$Template );
		$Commande->setTemplate('mobile', $Template);
		$Commande->save();
		return $Commande;
	}
	public function postSave() {
		$isArmed=self::AddCommande($this,"Etat activation","isArmed","info","binary",false,'lock');
		$isArmed->event(true);
		$isArmed->setCollectDate(date('Y-m-d H:i:s'));
		$isArmed->save();
		$Armed=self::AddCommande($this,"Activer","armed","action","other",true,'lock');
		$Armed->setValue($isArmed->getId());
		$Armed->setConfiguration('state', '1');
		$Armed->setConfiguration('armed', '1');
		$Armed->save();
		$Released=self::AddCommande($this,"Désactiver","released","action","other",true,'lock');
		$Released->setValue($isArmed->getId());
		$Released->save();
		$Released->setConfiguration('state', '0');
		$Released->setConfiguration('armed', '1');
		self::deamon_stop();
	}	
	public function postRemove() {
	}
}
class arrosageAutoCmd extends cmd {
    public function execute($_options = null) {
		
	}
}
?>

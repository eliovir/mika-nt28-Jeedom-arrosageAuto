<?php
require_once dirname(__FILE__) . '/../../../../core/php/core.inc.php';
class arrosageAuto extends eqLogic {
	public static function deamon_info() {
		$return = array();
		$return['log'] = 'arrosageAuto';
		$return['launchable'] = 'ok';
		$return['state'] = 'ok';
		foreach(eqLogic::byType('arrosageAuto') as $zone){
			if($zone->getIsEnable() && $zone->getCmd(null,'isArmed')->execCmd()){
				$cron = cron::byClassAndFunction('arrosageAuto', 'pull'/*,array('id' => $zone->getId())*/);
				if (!is_object($cron)) 	{	
					$return['state'] = 'nok';
					return $return;
				}
			}
		}
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
		foreach(eqLogic::byType('arrosageAuto') as $zone){
			if($zone->getIsEnable() && $zone->getCmd(null,'isArmed')->execCmd())
				$zone->NextStart();
		}
	}
	public static function deamon_stop() {	
		foreach(eqLogic::byType('arrosageAuto') as $zone){
			$cron = cron::byClassAndFunction('arrosageAuto', 'pull',array('id' => $zone->getId()));
			if (is_object($cron)) 	
				$cron->remove();
		}
	}
	public function toHtml($_version = 'dashboard') {
		if ($this->getIsEnable() != 1) {
			return '';
		}
		$version = jeedom::versionAlias($_version);
		if ($this->getDisplay('hideOn' . $version) == 1) {
			return '';
		}
		$vcolor = 'cmdColor';
		if ($version == 'mobile') {
			$vcolor = 'mcmdColor';
		}
		$cmdColor='';
		$Next='';
		$cron = cron::byClassAndFunction('arrosageAuto', 'pull', array('id' => $this->getId()));
		if (is_object($cron)){
			$_option=$cron->getOption();
			$Next=$_option['action'].' : '.$cron->getNextRunDate();
		}
		$cmdColor = ($this->getPrimaryCategory() == '') ? '' : jeedom::getConfiguration('eqLogic:category:' . $this->getPrimaryCategory() . ':' . $vcolor);
		$replace_eqLogic = array(
			'#id#' => $this->getId(),
			'#background_color#' => $this->getBackgroundColor(jeedom::versionAlias($_version)),
			'#humanname#' => $this->getHumanName(),
			'#name#' => $this->getName(),
			'#height#' => $this->getDisplay('height', 'auto'),
			'#width#' => $this->getDisplay('width', 'auto'),
			'#cmdColor#' => $cmdColor,
			'#Next#' => $Next
		);
		foreach ($this->getCmd() as $cmd) {
			if ($cmd->getDisplay('hideOn' . $version) == 1) 
				continue;
			$replace_eqLogic['#'.$cmd->getLogicalId().'#']= $cmd->toHtml($_version, $cmdColor);
		}
		return $this->postToHtml($_version, template_replace($replace_eqLogic, getTemplate('core', jeedom::versionAlias($version), 'eqLogic', 'arrosageAuto')));
	}
	public static $_widgetPossibility = array('custom' => array(
	        'visibility' => true,
	        'displayName' => true,
	        'optionalParameters' => true,
	));
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
		$cron = cron::byClassAndFunction('arrosageAuto', 'pull',array('id' => $this->getId()));
		if (is_object($cron)) 	
			$cron->remove();
	}
	public static function pull($_option){
		$zone=eqLogic::byId($_option['id']);
		if(is_object($zone)){
			if(!$zone->getCmd(null,'isArmed')->execCmd())
				exit;
      			$cron = cron::byClassAndFunction('arrosageAuto', 'pull',array('id' => $zone->getId()));
     		 	if (!is_object($cron))
				exit;
			if(!$zone->EvaluateCondition())
				exit;
			foreach($zone->getConfiguration('action') as $cmd){
				$zone->ExecuteAction($cmd,$_option['action']);
				if($_option['action'] == 'start'){
					$PowerTime=$zone->EvaluateTime();
					log::add('ChauffeEau','info','Estimation du temps d\'activation '.$PowerTime);
					$Schedule= $zone->TimeToShedule($PowerTime);
					$zone->CreateCron($Schedule, array('action' => 'stop'));
				}
				if($_option['action'] == 'stop')
					$zone->NextStart();
			}
		}
	}
	public function TimeToShedule($Time) {
		$Heure=round($Time/3600);
		$Minute=round(($Time-($Heure*3600))/60);
		$Shedule = new DateTime();
		$Shedule->add(new DateInterval('PT'.$Time.'S'));
		return  $Shedule->format("i H d m *");
	} 
	public function EvaluateTime() {
		$DebitGicler=$this->getConfiguration('DebitGicler');
		$TypeArrosage=config::byKey('configuration','arrosageAuto');
		$key=array_search($this->getConfiguration('TypeArrosage'),$TypeArrosage['type']);
		$QtsEau=$TypeArrosage['volume'][$key]; 
		//Ajouter la verification du nombre de start dans la journée pour repartir la quantité 
		//$QtsEau=$QtsEau/count($this->getConfiguration('programation'));
		return round($QtsEau/$DebitGicler);
	} 
	public function ExecuteAction($Action, $Type) {	
		foreach($Action as $cmd){
			if (isset($cmd['enable']) && $cmd['enable'] == 0)
				continue;
			if ($cmd['Type'] != $Type && $Type !='')
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
				log::add('arrosageAuto','debug',$this->getHumanName().' : Exécution de '.$Commande->getHumanName());
				$Commande->event($cmd['options']);
			}
		}
	}
	public function CreateCron($Schedule, $option=array()) {
		$option['id']= $this->getId();
		$cron = cron::byClassAndFunction('arrosageAuto', 'pull',$option);
		if (!is_object($cron)) {
			$cron = new cron();
			$cron->setClass('arrosageAuto');
			$cron->setFunction('pull');
			$cron->setEnable(1);
			$cron->setDeamon(0);
		}
		$cron->setOption($option);
		$cron->setSchedule($Schedule);
		$cron->save();
		return $cron;
	}
	private function EvaluateCondition(){
		foreach($this->getConfiguration('condition') as $condition){	
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
		log::add('arrosageAuto','info',$this->getHumanName().' : Les conditions sont remplies');
		return true;
	}
	private function CheckPompe($nextTime){
		//Chercher toutes les branche active en meme temps
		$DebitGiclers=$this->getConfiguration('DebitGicler');
		foreach(eqLogic::byType('arrosageAuto') as $zone){
		}
		$DebitPmp=config::byKey('debit','arrosageAuto');
		if($DebitPmp<$DebitGiclers)
			return false;
		return true;
	}
	private function NextStart(){
		$nextTime=null;
		foreach($this->getConfiguration('programation') as $ConigSchedule){
			$offset=0;
			for($day=0;$day<7;$day++){
				if($ConigSchedule[date('w')+$day]){
					$offset=$day;
					break;
				}
			}
			while(!$this->CheckPompe($nextTime)){
				while(mktime()>$timestamp){
					$timestamp=mktime ($ConigSchedule["Heure"], $ConigSchedule["Minute"], 0, date("n") , date("j")+$offset , date("Y"));
					$offset++;
				}
				if($nextTime == null)
					$nextTime=$timestamp;
				if($nextTime>$timestamp)
					$nextTime=$timestamp;
			}
		}
		if($nextTime != null){
			$cron=$this->CreateCron(date('i H d m w Y',$nextTime),array('action' => 'start'));
			log::add('arrosageAuto','info',$this->getHumanName().' : Création du prochain arrosage '. $cron->getNextRunDate());
		}
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
}
class arrosageAutoCmd extends cmd {
		public function execute($_options = null) {
		$Listener=cmd::byId(str_replace('#','',$this->getValue()));
		if (is_object($Listener)) {	
			switch($this->getLogicalId()){
				case 'armed':
					$Listener->event(true);
				break;
				case 'released':
					$Listener->event(false);
				break;
			}
			$Listener->setCollectDate(date('Y-m-d H:i:s'));
			$Listener->save();
		}
	}
}
?>

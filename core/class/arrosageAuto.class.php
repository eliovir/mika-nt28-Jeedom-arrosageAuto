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
				$cron = cron::byClassAndFunction('arrosageAuto', 'pull',array('id' => $zone->getId()));
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
			if($zone->getIsEnable() && $zone->getCmd(null,'isArmed')->execCmd()){
				$nextTime=$zone->NextProg();
				$zone->CreateCron(date('i H d m w Y',$nextTime));
			}
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
		$replace = $this->preToHtml($_version);
		if (!is_array($replace)) 
			return $replace;
		$version = jeedom::versionAlias($_version);
		if ($this->getDisplay('hideOn' . $version) == 1)
			return '';
		foreach ($this->getCmd() as $cmd) {
			if ($cmd->getDisplay('hideOn' . $version) == 1)
				continue;
			$replace['#'.$cmd->getLogicalId().'#']= $cmd->toHtml($_version, $cmdColor);
		}
		$replace['#cmdColor#'] = ($this->getPrimaryCategory() == '') ? '' : jeedom::getConfiguration('eqLogic:category:' . $this->getPrimaryCategory() . ':' . $vcolor);
		$NextProg=$this->NextProg();
		$replace['#NextStart#'] = date('d/m/Y H:i',$NextProg);
		if($plui=$this->CheckMeteo() === false)		
			$replace['#NextStop#'] = 'Météo incompatible';
		else{
			$PowerTime=$this->EvaluateTime($plui);	
			$replace['#NextStop#'] = date('d/m/Y H:i',$NextProg+$PowerTime);
		}
		if ($_version == 'dview' || $_version == 'mview') {
			$object = $this->getObject();
			$replace['#name#'] = (is_object($object)) ? $object->getName() . ' - ' . $replace['#name#'] : $replace['#name#'];
		}
      		return $this->postToHtml($_version, template_replace($replace, getTemplate('core', $version, 'eqLogic', 'arrosageAuto')));
  	}
	public static $_widgetPossibility = array('custom' => array(
	        'visibility' => true,
	        'displayName' => true,
	        'displayObjectName' => true,
	        'optionalParameters' => true,
	        'background-color' => true,
	        'text-color' => true,
	        'border' => true,
	        'border-radius' => true
	));
	public function postSave() {
		$isArmed=$this->AddCommande("État activation","isArmed","info","binary",false,'lock');
		$isArmed->event(true);
		$isArmed->setCollectDate(date('Y-m-d H:i:s'));
		$isArmed->save();
		$Armed=$this->AddCommande("Activer","armed","action","other",true,'lock');
		$Armed->setValue($isArmed->getId());
		$Armed->setConfiguration('state', '1');
		$Armed->setConfiguration('armed', '1');
		$Armed->save();
		$Released=$this->AddCommande("Désactiver","released","action","other",true,'lock');
		$Released->setValue($isArmed->getId());
		$Released->setConfiguration('state', '0');
		$Released->setConfiguration('armed', '1');
		$Released->save();
		$Coef=$this->AddCommande("Coefficient","coefficient","info","numeric",false);
		$regCoefficient=$this->AddCommande("Réglage coefficient","regCoefficient","action","slider",true,'coefArros');
		$regCoefficient->setValue($Coef->getId());
		$regCoefficient->save();
		$nextTime=$this->NextProg();
		$this->CreateCron(date('i H d m w Y',$nextTime));
	}
	public function preRemove() {
		$cron = cron::byClassAndFunction('arrosageAuto', 'pull',array('id' => $this->getId()));
		if (is_object($cron)) 	
			$cron->remove();
	}
	public function CreateCron($Schedule) {
		log::add('arrosageAuto','debug','Création du cron  ID = '.$this->getId().' --> '.$Schedule);
		$cron = cron::byClassAndFunction('arrosageAuto', "pull" ,array('id' => $this->getId()));
		if (!is_object($cron)) 
			$cron = new cron();
		$cron->setClass('arrosageAuto');
		$cron->setFunction("pull");
		$options['id']= $this->getId();
		$cron->setOption($options);
		$cron->setEnable(1);
		$cron->setSchedule($Schedule);
		$cron->save();
		return $cron;
	}
	public static function pull($_option){
		$zone=eqLogic::byId($_option['id']);
		if(is_object($zone)){			
			if(!$zone->getCmd(null,'isArmed')->execCmd()){
				log::add('arrosageAuto','info','La zone est desactivée');
				exit;
			}
			if(!$zone->CheckCondition()){
				log::add('arrosageAuto','info','Les conditions ne sont pas evaluées');
				exit;
			}
			if($plui=$zone->CheckMeteo() === false){
				log::add('arrosageAuto','info','La météo n\'est pas idéale pour l\'arrosage');
				exit;
			}
			$zone->ExecuteAction('start');
			$PowerTime=$zone->EvaluateTime($plui);
			log::add('arrosageAuto','info','Estimation du temps d\'activation '.$PowerTime.'s');
			sleep($PowerTime);
			$zone->ExecuteAction('stop');
			$nextTime=$zone->NextProg();
			$zone->CreateCron(date('i H d m w Y',$nextTime));
		}
	}
	public function EvaluateTime($plui=0) {
		$TypeArrosage=config::byKey('configuration','arrosageAuto');
		$key=array_search($this->getConfiguration('TypeArrosage'),$TypeArrosage['type']);
		$QtsEau=$TypeArrosage['volume'][$key];
		//Ajouter la verification du nombre de start dans la journée pour repartir la quantité
		$NbProgramation=0;
		foreach($this->getConfiguration('programation') as $programmation){
			if($programmation[date('w')])
				$NbProgramation++;
		}
		$QtsEau-=$plui;
		$QtsEau=$QtsEau/$NbProgramation;
		$Pluviometrie=$this->CalculPluviometrie();
		/*if($Pluviometrie == 0)
			return $Pluviometrie;*/
		log::add('arrosageAuto','info',$this->getHumanName().' : Nous devons arroser '.$QtsEau.' L/m² avec un pluviometrie de '.$Pluviometrie.'L/s');
		return $this->Ratio((($QtsEau-$plui)*3600/$Pluviometrie)*$this->getConfiguration('superficie'));
	}
	public function Ratio($Value){
		$cmd=$this->getCmd(null, 'coefficient');
		if(!is_object($cmd))
			return $Value;
		$min=$cmd->getConfiguration('minValue');
		$max=$cmd->getConfiguration('maxValue');
		if($min == '' && $max == '')
			return $Value;
		if($min == '')
			$min=0;
		if($max == '')
			$max=300;
		return round(($Value/100)*($max-$min)+$min);
		
	}
	public function ExecuteAction($Type) {
		foreach($this->getConfiguration('action') as $cmd){
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
	public function CheckMeteo(){
		//$precipProbability= jeedom::evaluateExpression(config::byKey('cmdPrecipProbability','arrosageAuto'));
		$result=$this->EvaluateCondition(config::byKey('cmdPrecipProbability','arrosageAuto').' < '. config::byKey('precipProbability','arrosageAuto'));
		if(!$result){
			log::add('arrosageAuto','info',$this->getHumanName().' : La probalité de précipitation est trop important');
			return false;
		}
		//$windSpeed= jeedom::evaluateExpression(config::byKey('cmdWindSpeed','arrosageAuto'));
		$result=$this->EvaluateCondition(config::byKey('cmdWindSpeed','arrosageAuto').' < '. config::byKey('windSpeed','arrosageAuto'));
		if(!$result){
			log::add('arrosageAuto','info',$this->getHumanName().' : Il y a trop de vent pour arroser');
			return false;
		}
		//$humidity= jeedom::evaluateExpression(config::byKey('cmdHumidity','arrosageAuto'));
		$result=$this->EvaluateCondition(config::byKey('cmdHumidity','arrosageAuto').' < '. config::byKey('humidity','arrosageAuto'));
		if(!$result){
			log::add('arrosageAuto','info',$this->getHumanName().' : Il y a suffisament d\'humidié, pas besoin d\'arroser');
			return false;
		}
		$Precipitation= jeedom::evaluateExpression(config::byKey('cmdPrecipitation','arrosageAuto'));
		log::add('arrosageAuto','debug',$this->getHumanName().' : Precipitation '.$Precipitation);
		return $Precipitation;
	}
	public function CheckCondition(){
		foreach($this->getConfiguration('condition') as $Condition){
			if (isset($Condition['enable']) && $Condition['enable'] == 0)
				continue;
			$result=$this->EvaluateCondition($Condition['expression']);
			if(!$result){
				log::add('arrosageAuto','info',$this->getHumanName().' : Les conditions ne sont pas remplies');
				return false;
			}
		}
		log::add('arrosageAuto','info',$this->getHumanName().' : Les conditions sont remplies');
		return true;
	}
	public function boolToText($value){
		if (is_bool($value)) {
			if ($value) 
				return __('Vrai', __FILE__);
			else 
				return __('Faux', __FILE__);
		} else 
			return $value;
	}
	public function EvaluateCondition($Condition){
		$_scenario = null;
		$expression = scenarioExpression::setTags($Condition, $_scenario, true);
		$message = __('Evaluation de la condition : ['.$Condition.'][', __FILE__) . trim($expression) . '] = ';
		$result = evaluate($expression);
		$message .=$this->boolToText($result);
		log::add('arrosageAuto','info',$this->getHumanName().' : '.$message);
		if(!$result)
			return false;		
		return true;
	}
	public function CheckPompe($nextTime){
		/*$DebitGiclers=$this->getConfiguration('DebitGicler');
		foreach(eqLogic::byType('arrosageAuto') as $zone){
			$cron = cron::byClassAndFunction('arrosageAuto', 'pull',array('Zone_id' => $zone->getId()));
			if (is_object($cron)){
				$Time=$zone->EvaluateTime();
				$nextStart=$zone->NextProg();
				$nextStop=$nextStart+$Time;
				if($nextStart>$nextTime && $nextStop<$nextTime)
					$DebitGiclers+=$zone->getConfiguration('DebitGicler');
			}
		}
		$DebitPmp=config::byKey('debit','arrosageAuto');
		if($DebitPmp<$DebitGiclers)
			return false;*/
		return $nextTime;
	}
	public function CalculPluviometrie(){
		switch($this->getConfiguration('TypeGicler')){
			case'gouteAgoute':
				$Debit = 10000 * $this->getConfiguration('DebitGoutteur');
            			$Espacement = $this->getConfiguration('EspacementLateral') * $this->getConfiguration('EspacemenGoutteurs');
           			$Pluviometrie = $Debit / $Espacement;
				log::add('arrosageAuto','info',$this->getHumanName().' : Pluviométrie : '.$Pluviometrie);
			return $Pluviometrie;
			case'turbine':
			return 15;
		 }
	}
	public function NextProg(){
		$nextTime=null;
		foreach($this->getConfiguration('programation') as $ConigSchedule){
			$offset=0;
			if(date('H') > $ConigSchedule["Heure"])
				$offset++;
			if(date('H') == $ConigSchedule["Heure"] && date('i') >= $ConigSchedule["Minute"])	
				$offset++;
			for($day=0;$day<7;$day++){
				if($ConigSchedule[date('w')+$day+$offset]){
					$offset+=$day;
					$timestamp=mktime ($ConigSchedule["Heure"], $ConigSchedule["Minute"], 0, date("n") , date("j") , date("Y"))+ (3600 * 24) * $offset;
					break;
				}
			}
			if($nextTime == null || $nextTime > $timestamp)
				$nextTime = $timestamp;
		}
		return $nextTime;
	}
	public function AddCommande($Name,$_logicalId,$Type="info", $SubType='binary',$visible,$Template='') {
		$Commande = $this->getCmd(null,$_logicalId);
		if (!is_object($Commande))
		{
			$Commande = new arrosageAutoCmd();
			$Commande->setId(null);
			$Commande->setName($Name);
			$Commande->setIsVisible($visible);
			$Commande->setLogicalId($_logicalId);
			$Commande->setEqLogic_id($this->getId());
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
					$nextTime=$this->getEqLogic()->NextProg();
					$this->getEqLogic()->CreateCron(date('i H d m w Y',$nextTime));
				break;
				case 'released':
					$Listener->event(false);
					$cron = cron::byClassAndFunction('arrosageAuto', 'pull', array('Zone_id' => $this->getEqLogic()->getId()));
					if (is_object($cron))
						$cron->remove();
				break;
				case 'regCoefficient':
					$Listener->event($_options['slider']);
				break;
			}
			$Listener->setCollectDate(date('Y-m-d H:i:s'));
			$Listener->save();
		}
	}
}
?>

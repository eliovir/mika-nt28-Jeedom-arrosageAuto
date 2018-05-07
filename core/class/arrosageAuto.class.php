<?php
require_once dirname(__FILE__) . '/../../../../core/php/core.inc.php';
class arrosageAuto extends eqLogic {
	public function toHtml($_version = 'dashboard') {
		$replace = $this->preToHtml($_version);
		if (!is_array($replace)) 
			return $replace;
		$version = jeedom::versionAlias($_version);
		if ($this->getDisplay('hideOn' . $version) == 1)
			return '';
		$Next='';
		foreach ($this->getCmd() as $cmd) {
			if ($cmd->getDisplay('hideOn' . $version) == 1)
				continue;
			$replace['#'.$cmd->getLogicalId().'#']= $cmd->toHtml($_version, $cmdColor);
		}
		$replace['#cmdColor#']($this->getPrimaryCategory() == '') ? '' : jeedom::getConfiguration('eqLogic:category:' . $this->getPrimaryCategory() . ':' . $vcolor);
		$NextProg=$this->NextProg();
		$replace['#NextStart#'] = date('d/m/Y H:i',$NextProg-$PowerTime);
		$replace['#NextStop#'] = date('d/m/Y H:i',$NextProg);
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
		$regCoefficient=$this->AddCommande("Réglage coefficient","regCoefficient","action","slider",true);
		$regCoefficient->setValue($Coef->getId());
		$regCoefficient->save();
	}
	public static function cron() {	
		foreach(eqLogic::byType('arrosageAuto') as $zone){
			if (!$ChauffeEau->getIsEnable()) 
				return
			$NextProg=$zone->NextProg();
			if($NextProg != null){
				$PowerTime=$zone->EvaluatePowerTime();
				if(mktime() > $NextProg-$PowerTime+60){	//Heure actuel > Heure de dispo - Temps de chauffe + Pas d'integration
					if(mktime() > $NextProg){
						log::add('arrosageAuto','debug',$zone->getHumanName().' : Temps supperieur a l\'heure programmée');
						$zone->ExecuteAction('stop');
						break;
					}
					if(!$zone->getCmd(null,'isArmed')->execCmd()){
						log::add('arrosageAuto','info','La zone est desactivée');
						exit;
					}
					if(!$zone->EvaluateCondition()){
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
							
				}else
					$zone->ExecuteAction('stop');
			}		   
		}
	}
	public function TimeToShedule($Time) {
		$Shedule = new DateTime();
		$Shedule->add(new DateInterval('PT'.$Time.'S'));
		return  $Shedule->format("i H d m w Y");
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
		$QtsEau=$QtsEau/$NbProgramation;
		$Pluviometrie=$this->CalculPluviometrie();
		if($Pluviometrie == 0)
			return $Pluviometrie;
		return $this->Ratio(($QtsEau-$plui)*3600/$Pluviometrie);
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
		log::add('arrosageAuto','debug',$this->getHumanName().' : Probabilité de précipitation '.$this->getMeteoParameter('precipProbability').' >'. config::byKey('precipProbability','arrosageAuto').' ?');
		if($this->getMeteoParameter('precipProbability')>config::byKey('precipProbability','arrosageAuto'))
			return false;
		log::add('arrosageAuto','debug',$this->getHumanName().' : Vitesse du vent '.$this->getMeteoParameter('windSpeed').' > '.config::byKey('windSpeed','arrosageAuto').' ?');
		if($this->getMeteoParameter('windSpeed')>config::byKey('windSpeed','arrosageAuto'))
			return false;
		log::add('arrosageAuto','debug',$this->getHumanName().' : Humidité '.$this->getMeteoParameter('humidity').' > '.config::byKey('humidity','arrosageAuto').'?');
		if($this->getMeteoParameter('humidity')>config::byKey('humidity','arrosageAuto'))
			return false;
		return $this->getMeteoParameter('precipIntensity');
	}
	public function getMeteoParameter($search){
      		$meteo=config::byKey('meteo','arrosageAuto');
      		$meteo=str_replace('#','',$meteo);
      		$meteo=str_replace('eqLogic','',$meteo);
		$meteo=eqLogic::byId($meteo);
		if(is_object($meteo)){
			switch($meteo->getEqType_name()){
				case 'darksky':
				case 'forecastio':
					$plugin=array(
						'windBearing' => array(
							'id' =>'windBearing',
							'nom' =>'Direction du vent'),
						'windSpeed' => array(
							'id' =>'windSpeed',
							'nom' =>'Vitesse du vent'),
						'humidity' => array(
							'id' =>'humidity',
							'nom' =>'Humidité'),
						'precipIntensity' => array(
							'id' =>'precipIntensity',
							'nom' =>'Intensité de précipitation'),
						'precipProbability' => array(
							'id' =>'precipProbability',
							'nom' =>'Probabilité de Précipitation')
					);
				break;
				case 'weather':
					$plugin=array(
						'windBearing' => array(
							'id' =>'wind_direction',
							'nom' =>'Direction du vent'),
						'windSpeed' => array(
							'id' =>'wind_speed',
							'nom' =>'Vitesse du vent'),
						'humidity' => array(
							'id' =>'humidity',
							'nom' =>'Humidité')
					);
				break;
				default:
					return 0;
			}
			$logicalId=$plugin[$search]['id'];
			$objet=$meteo->getCmd(null,$logicalId);
			if(is_object($objet))
				return $objet->execCmd();
		}
		return 0;
	}
	public function EvaluateCondition(){
		foreach($this->getConfiguration('condition') as $condition){
			if (isset($condition['enable']) && $condition['enable'] == 0)
				continue;
			$_scenario = null;
			$expression = scenarioExpression::setTags($condition['expression'], $_scenario, true);
			$message = __('Évaluation de la condition : [', __FILE__) . trim($expression) . '] = ';
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
	public function CheckPompe($nextTime){
		/*$DebitGiclers=$this->getConfiguration('DebitGicler');
		foreach(eqLogic::byType('arrosageAuto') as $zone){
			$cron = cron::byClassAndFunction('arrosageAuto', 'pull',array('Zone_id' => $zone->getId()));
			if (is_object($cron)){
				$Time=$zone->EvaluateTime();
				$nextStart=$zone->NextStart();
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
	public function NextStart(){
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
				break;
				case 'released':
					$Listener->event(false);
					$cron = cron::byClassAndFunction('arrosageAuto', 'pull', array('Zone_id' => $this->getEqLogic()->getId()));
					if (is_object($cron))
						$cron->remove();
				break;
			}
			$Listener->setCollectDate(date('Y-m-d H:i:s'));
			$Listener->save();
		}
	}
}
?>

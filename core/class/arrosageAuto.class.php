<?php
require_once dirname(__FILE__) . '/../../../../core/php/core.inc.php';
class arrosageAuto extends eqLogic {	
	public static function deamon_info() {
		$return = array();
		$return['log'] = 'arrosageAuto';
		$return['launchable'] = 'ok';
		$return['state'] = 'nok';
		foreach(eqLogic::byType('arrosageAuto') as $Zone){
			if($Zone->getIsEnable() && $Zone->getCmd(null,'isArmed')->execCmd()){
				if($Zone->getConfiguration('EtatElectrovanne') != ''){
					$listener = listener::byClassAndFunction('arrosageAuto', 'pull', array('Zone_id' => $Zone->getId()));
					if (!is_object($listener))	
						return $return;
				}
			}
		}
		$return['state'] = 'ok';
		return $return;
	}
	public static function deamon_start($_debug = false) {
		//log::remove('arrosageAuto');
		self::deamon_stop();
		$deamon_info = self::deamon_info();
		if ($deamon_info['launchable'] != 'ok') 
			return;
		if ($deamon_info['state'] == 'ok') 
			return;
		foreach(eqLogic::byType('arrosageAuto') as $Zone){
			if (!is_object($Zone))	
				continue;
			if(!$Zone->getIsEnable() || !$Zone->getCmd(null,'isArmed')->execCmd())
				continue;
			$Zone->zoneStart();
		}
	}
	public static function deamon_stop() {	
		foreach(eqLogic::byType('arrosageAuto') as $Zone)
			$Zone->zoneStop();
	}
	public static function cron() {
		foreach(eqLogic::byType('arrosageAuto') as $Zone){
			if(!$Zone->getIsEnable())
				continue;
			$Precipitation= jeedom::evaluateExpression(config::byKey('cmdPrecipitation','arrosageAuto'));
			$Zone->addCacheStatistique(mktime(0,0,0),$Precipitation);
			$Zone->EvaluateTime($Precipitation);
			if(!$Zone->getCmd(null,'isArmed')->execCmd())
				continue;
          		$start=time();
			$startDate = $Zone->getCmd(null,'NextStart');
			if(is_object($startDate))
				$start = strtotime($startDate->execCmd());
          		$stop=time();
            		$Temps = $Zone->getCmd(null,'Temps');
            		if(is_object($Temps))
               			$stop= $start + $Temps->execCmd();
			if(time() >= $start && time() < $stop){
				if($Zone->startArrosage()){
					if(time() - $stop < 30)
						$Zone->stopArrosage($Precipitation);
				}else
					$Zone->stopArrosage($Precipitation);
            		}else
				$Zone->stopArrosage($Precipitation);
		}
	}
	public static function pull($_option) {
		$Zone = eqLogic::byId($_option['Zone_id']);
		if (is_object($Zone) && $Zone->getIsEnable()) {
			$State=cache::byKey('arrosageAuto::isStart::'.$Zone->getId());
			if($_option['value'] != $State->getValue(false)){
				log::add('arrosageAuto','info',$Zone->getHumanName().' : Changement d\'etat de l\'électrovanne non autorisé, la gestion automatique est desactivé');
				$Zone->checkAndUpdateCmd('isArmed',false);
				$Zone->ExecuteAction('stop');
				$cron = cron::byClassAndFunction('arrosageAuto', "Arrosage" ,array('Zone_id' => $Zone->getId()));
				if (is_object($cron)) 	
					$cron->remove();
			}
		}
	}
	public static function devicesParameters($_device = '') {
		$path = dirname(__FILE__) . '/../config/devices';
		if (isset($_device) && $_device != '') {
			$files = ls($path, $_device . '.json', false, array('files', 'quiet'));
			if (count($files) == 1) {
				try {
					$content = file_get_contents($path . '/' . $files[0]);
					if (is_json($content)) {
						$deviceConfiguration = json_decode($content, true);
						return $deviceConfiguration[$_device];
					}
				} catch (Exception $e) {
					return array();
				}
			}
		}
		$files = ls($path, '*.json', false, array('files', 'quiet'));
		$return = array();
		foreach ($files as $file) {
			try {
				$content = file_get_contents($path . '/' . $file);
				if (is_json($content)) {
					$return = array_merge($return, json_decode($content, true));
				}
			} catch (Exception $e) {
			}
		}
		if (isset($_device) && $_device != '') {
			if (isset($return[$_device])) {
				return $return[$_device];
			}
			return array();
		}
		return $return;
	}
	public function zoneStart() {
		cache::set('arrosageAuto::isStart::'.$this->getId(),false, 0);
		if($this->getConfiguration('EtatElectrovanne') != '')
			$this->ListenState();
		$this->NextProg();
	}
	public function zoneStop() {	
		$this->checkAndUpdateCmd('Temps',0);
		$this->checkAndUpdateCmd('NextStart','');
		$cron = cron::byClassAndFunction('arrosageAuto', "Arrosage" ,array('Zone_id' => $this->getId()));
		if (is_object($cron)) 	
			$cron->remove();
		$listener = listener::byClassAndFunction('arrosageAuto', 'pull', array('Zone_id' => $this->getId()));
		if (is_object($listener))
			$listener->remove();
		$isStart=cache::byKey('arrosageAuto::isStart::'.$this->getId());
		if (is_object($isStart)) 	
			$isStart->remove();
		$ActiveTime = cache::byKey('arrosageAuto::ActiveTime::'.$this->getId());
		if (is_object($ActiveTime)) 	
			$ActiveTime->remove();
	}
	public function ListenState() {
		$listener = listener::byClassAndFunction('arrosageAuto', 'pull', array('Zone_id' => $this->getId()));
		if (is_object($listener)) 	
			$listener->remove();
		$listener = listener::byClassAndFunction('arrosageAuto', 'pull', array('Zone_id' => $this->getId()));
		if (!is_object($listener))
		    $listener = new listener();
		$listener->setClass('arrosageAuto');
		$listener->setFunction('pull');
		$listener->setOption(array('Zone_id' => $this->getId()));
		$listener->emptyEvent();				
		$listener->addEvent($this->getConfiguration('EtatElectrovanne'));
		$listener->save();	
	}
	public function startArrosage(){
		if(!$this->CheckCondition()){
			log::add('arrosageAuto','info',$this->getHumanName().' : Les conditions ne sont pas evaluées');
			return false;
		}
		$isStart=cache::byKey('arrosageAuto::isStart::'.$this->getId());
		if (is_object($isStart) && !$isStart->getValue(false)) 	
			$this->ExecuteAction('start');
		cache::set('arrosageAuto::isStart::'.$this->getId(),true, 0);
		return true;
	}
	public function stopArrosage($Precipitation){
		$isStart=cache::byKey('arrosageAuto::isStart::'.$this->getId());
		if (is_object($isStart) && $isStart->getValue(false)){
			$this->ExecuteAction('stop');
			cache::set('arrosageAuto::isStart::'.$this->getId(),false, 0);
			$_parameter['Start']=time();
			$startDate = $this->getCmd(null,'Temps');
			if(is_object($startDate))
				$_parameter['Start'] = strtotime($startDate->execCmd());
			$_parameter['ActiveTime'] = time() - $_parameter['Start'];
			$_parameter['ConsomationEau']=$this->ConsomationEau($_parameter['ActiveTime']);
			$_parameter['Pluviometrie']=$this->CalculPluviometrie();
			$this->addCacheStatistique(mktime(0,0,0),$Precipitation,$_parameter);
			$this->NextProg();
		}
	}
	public function CheckProgActiveBranche($Branches,$NextProg){
		if($NextProg == null){
			log::add('arrosageAuto','debug',$this->getHumanName().' : Aucune programmation');
			return;
		}
		$DebitArroseurs=0;
		$PressionsArroseurs=0;
		$TempsArroseurs=0;
		foreach($Branches as $Branche){
			$Zone=eqLogic::byId(str_replace('#','',$Branche));
			if(!is_object($Zone)){
				log::add('arrosageAuto','debug',$this->getHumanName().' : Zone inconne '.'('.$Branche.')');
				continue;
			}	
			if(!$Zone->getCmd(null,'isArmed')->execCmd()){
				log::add('arrosageAuto','info',$Zone->getHumanName().' : La zone est desactivée');
				continue;
			}
			$startDate = $Zone->getCmd(null,'NextStart');
			if(is_object($startDate) && $startDate->execCmd() != ''){
				$start = strtotime($startDate->execCmd());
				if($start < $NextProg){
					log::add('arrosageAuto','debug',$Zone->getHumanName().' : Une programmation plus tot existe deja');
					continue;
				}
			}
			$DebitArroseurs+=$Zone->CheckDebit();
			$PressionsArroseurs+=$Zone->CheckPression();
			$plui=jeedom::evaluateExpression(config::byKey('cmdPrecipitation','arrosageAuto'));
			$ActiveTime=$Zone->EvaluateTime($plui,date('w',$NextProg));	
			if(!self::CheckPompe($DebitArroseurs,$PressionsArroseurs)){
				$DebitArroseurs=0;
				$PressionsArroseurs=0;
				$TempsArroseurs+=$ActiveTime+config::byKey('temps','arrosageAuto');
			}
			$Zone->checkAndUpdateCmd('NextStart',date('d/m/Y H:i',$NextProg+$TempsArroseurs));
		}
	}
	public function NextProg(){
		$nextTime=null;
		$Branches=null;
		foreach(config::byKey('Programmations', 'arrosageAuto') as $ConigSchedule){
			if(array_search($this->getId(),$ConigSchedule["evaluation"]) === false){
				log::add('arrosageAuto','debug',$this->getHumanName().' : Zone inactive dans cette programmation : '.json_encode($ConigSchedule));
				continue;
			}
			$offset=0;
			if(date('H') > $ConigSchedule["Heure"])
				$offset++;
			if(date('H') == $ConigSchedule["Heure"] && date('i') >= $ConigSchedule["Minute"])	
				$offset++;
			for($day=0;$day<7;$day++){
				$jour=date('w')+$day+$offset;
				if($jour > 6)
					$jour= $jour-7;
				if($ConigSchedule[$jour]){
					$offset+=$day;
					$timestamp=mktime ($ConigSchedule["Heure"], $ConigSchedule["Minute"], 0, date("n") , date("j") , date("Y"))+ (3600 * 24) * $offset;
					break;
				}
			}
			if($nextTime == null || $nextTime > $timestamp){
				$Branches=$ConigSchedule["evaluation"];
				$nextTime = $timestamp;
			}
		}
		$this->CheckProgActiveBranche($Branches,$nextTime);
		return $nextTime;
	}
	public static function CheckPompe($Debit,$Pressions){
		$DebitPmp=config::byKey('debit','arrosageAuto');
		$PressionsPmp=config::byKey('pression','arrosageAuto');
		if($DebitPmp<$Debit)
			return false;
		if($PressionsPmp<$Pressions)
			return false;
		return true;
	}
	public function CheckDebit(){
		$Debit=0;		
		foreach($this->getConfiguration('arroseur') as $Arroseur)
			$Debit += $Arroseur['Debit'];
		return $Debit; 
	}
	public function CheckPression(){
		$Pression=0;		
		foreach($this->getConfiguration('arroseur') as $Arroseur)
			$Pression += $Arroseur['Pression'];
		return $Pression; 
	}
	public function getPression($Pression,$Debit){
		//Équation de Hazen-Williams
		return $Pression-(($Debit*$Longeur)/(0.849*150*$Air*$Rayon));
	}	
	public function preSave() {
		if(!self::CheckPompe($this->CheckDebit() ,$this->CheckPression()))	   
			throw new Exception(__('Le bilan des arroseurs est superieur a la source', __FILE__));
	}
	public function postSave() {
		$this->AddCommande("Début","NextStart","info","string",true);
		$this->AddCommande("Temps","Temps","info","string",true,'','s');
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
		$this->zoneStart();
	}
	public function preRemove() {
		$isStart=cache::byKey('arrosageAuto::isStart::'.$this->getId());
		if (is_object($isStart)) 	
			$isStart->remove();
		$ActiveTime = cache::byKey('arrosageAuto::ActiveTime::'.$this->getId());
		if (is_object($ActiveTime)) 	
			$ActiveTime->remove();
		$cron = cron::byClassAndFunction('arrosageAuto', 'Arrosage',array('Zone_id' => $this->getId()));
		if (is_object($cron)) 	
			$cron->remove();
	}
	public function EvaluateTime($Precipitation=0,$day=null) {
     	 	if($day == null)
        		$day=date('w');
		$TypeArrosage=config::byKey('configuration','arrosageAuto');
		$key=array_search($this->getConfiguration('TypeArrosage'),$TypeArrosage['type']);
		$QtsEau=$TypeArrosage['volume'][$key];
		//Ajouter la verification du nombre de start dans la journée pour repartir la quantité
		$NbProgramation=0;
		foreach(config::byKey('Programmations', 'arrosageAuto') as $programmation){
			if($programmation[$day])
				$NbProgramation++;
		}
		$QtsEau-=$Precipitation;
		$QtsEau=$QtsEau/$NbProgramation;
		$Pluviometrie=$this->CalculPluviometrie();
		if($Pluviometrie == 0)
			return $Pluviometrie;
		log::add('arrosageAuto','info',$this->getHumanName().' : Nous devons arroser '.$QtsEau.' mm/m² avec un pluviometrie de '.$Pluviometrie.'mm/s');
		$Temps=$this->UpdateCoefficient($QtsEau/$Pluviometrie);
		$this->checkAndUpdateCmd('Temps',$Temps);
		return $Temps;
	}
	public function UpdateCoefficient($Value){
     		$cmd=$this->getCmd(null, 'coefficient');
		if(!is_object($cmd))
			return $Value;
		$min=$cmd->getConfiguration('minValue');
		$max=$cmd->getConfiguration('maxValue');
      		$coef=$cmd->execCmd();
		if($min == '')
			$min=0;
		if($max == '')
			$max=300;
		$temps = round($Value*$coef/100);
      		log::add('arrosageAuto','info',$this->getHumanName().' : Temps d\'arrosage de '.$temps.'s avec un coeficient de '.$coef.'%');
		return $temps;
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
		$result=$this->EvaluateCondition(config::byKey('cmdPrecipProbability','arrosageAuto').' < '. config::byKey('precipProbability','arrosageAuto'));
		if(!$result){
			log::add('arrosageAuto','info',$this->getHumanName().' : La probalité de précipitation est trop important');
			return false;
		}
		$result=$this->EvaluateCondition(config::byKey('cmdWindSpeed','arrosageAuto').' < '. config::byKey('windSpeed','arrosageAuto'));
		if(!$result){
			log::add('arrosageAuto','info',$this->getHumanName().' : Il y a trop de vent pour arroser');
			return false;
		}
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
		$message = __('Evaluation de la condition : ['.jeedom::toHumanReadable($Condition).'][', __FILE__) . trim($expression) . '] = ';
		$result = evaluate($expression);
		$message .=$this->boolToText($result);
		log::add('arrosageAuto','info',$this->getHumanName().' : '.$message);
		if(!$result)
			return false;		
		return true;
	}
	public function ConsomationEau($Temps){
		$ConsomationEau=array();
		foreach($this->getConfiguration('arroseur') as $Arroseur)
			$ConsomationEau[] = $Arroseur['Debit'] * $Temps / 3600;
		return array_sum($ConsomationEau);
	}
	public function CalculPluviometrie(){
		$Pluviometrie=array();
		foreach($this->getConfiguration('arroseur') as $Arroseur){
			switch($Arroseur['Type']){
				case'gouteAgoute':
					$Debit = 10000 * $Arroseur['Debit'];
					$Espacement = $Arroseur['EspacementLateral'] * $Arroseur['EspacemenGoutteurs'];
					$Pluviometrie[] = $Debit / $Espacement;
				break;
				case'tuyere':
					$Pluviometrie[] = $Arroseur['Debit'] * $Arroseur['Quart'];
				break;
				case'turbine':
					//$Pluviometrie[] = $Arroseur['Debit'] /($Arroseur['Distance'] * $Arroseur['Angle']);
					$Pluviometrie[] = 15;
				break;
				case'oscillant':
					//$Pluviometrie[] = $Arroseur['Debit'] /($Arroseur['Distance'] * $Arroseur['Angle']);
					$Pluviometrie[] = 15;
				break;
					
			 }
		}
		$Pluviometrie = array_sum($Pluviometrie)/count($Pluviometrie);
		return $Pluviometrie/3600; //Conversion de mm/H en mm/s
	}
	public function addCacheStatistique($_date,$_plui,$_parameter='') {
		$cache = cache::byKey('arrosageAuto::Statistique::'.$this->getId());
		$value = json_decode($cache->getValue('[]'), true);
		$value[$_date]['Plui'] = $_plui;
		if($_parameter != '')
			$value[$_date]['Arrosage'][] = $_parameter;
		if(count($value) >=255){			
			unset($value[0]);
			array_shift($value);
		}
		cache::set('arrosageAuto::Statistique::'.$this->getId(),json_encode($value), 0);
	}
	public function AddCommande($Name,$_logicalId,$Type="info", $SubType='binary',$visible,$Template='',$unite='') {
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
			$Commande->setTemplate('dashboard',$Template );
			$Commande->setTemplate('mobile', $Template);
		}
		$Commande->setUnite($unite);
		$Commande->save();
		return $Commande;
	}
	public static function getGraph($_startTime = null, $_endTime = null, $_object_id) {	
		$object = object::byId($_object_id);	
		if (!is_object($object)) {
			throw new Exception(__('Objet non trouvé. Vérifiez l\'id : ', __FILE__) . $_object_id);	
		}	
		foreach ($object->getEqLogic(true, false, 'arrosageAuto') as $arrosageAuto) {		
			$cache = cache::byKey('arrosageAuto::Statistique::'.$arrosageAuto->getId());
			$Curve['Plui']=array();
			$Curve['Pluviometrie']=array();
			$Curve['ConsomationEau']=array();
			foreach(json_decode($cache->getValue('[]'), true) as $Date => $Statistique){
				$Curve['Plui'][]=array($Date*1000,floatval($Statistique['Plui']));
				foreach($Statistique['Arrosage'] as $Arrosage){
					$Curve['Pluviometrie'][]=array($Arrosage['Start']*1000,floatval($Arrosage['Pluviometrie']));
					$Curve['ConsomationEau'][]=array($Arrosage['Start']*1000,floatval($Arrosage['ConsomationEau']));
				}
			}
			$return[$arrosageAuto->getName()]=$Curve;
		}
		return $return;
	}
}
class arrosageAutoCmd extends cmd {
		public function execute($_options = null) {
		$Listener=cmd::byId(str_replace('#','',$this->getValue()));
		if (is_object($Listener)) {
			switch($this->getLogicalId()){
				case 'armed':
					$Listener->event(true);
					$this->getEqLogic()->zoneStart();
				break;
				case 'released':
					$Listener->event(false);
					$this->getEqLogic()->zoneStop();
				break;
				case 'regCoefficient':
					$Listener->event($_options['slider']);
					$this->getEqLogic()->EvaluateTime();
				break;
			}
			$Listener->setCollectDate(date('Y-m-d H:i:s'));
			$Listener->save();
		}
	}
}
?>

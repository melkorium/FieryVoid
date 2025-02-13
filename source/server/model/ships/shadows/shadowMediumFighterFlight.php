<?php
class ShadowMediumFighterFlight extends FighterFlight{
    
    function __construct($id, $userid, $name,  $slot){
        parent::__construct($id, $userid, $name,  $slot);
        
		$this->pointCost = 150*6;
		$this->faction = "Shadow Association";
		$this->phpclass = "ShadowMediumFighterFlight";
		$this->shipClass = "Medium Fighters";
		$this->imagePath = "img/ships/ShadowFighter.png";
	    
		$this->isd = 'Ancient';
		$this->factionAge = 3; //1 - Young, 2 - Middleborn, 3 - Ancient, 4 - Primordial
        /*
		$this->enhancementOptionsDisabled[] = 'POOR_TRAIN'; //there are no poorly trained Shadow fighters
		$this->enhancementOptionsDisabled[] = 'EXP_MOTIV'; //no crew - and no dropouts anyway ;)
		$this->enhancementOptionsEnabled[] = 'SHAD_CTRL'; //can be deployed as uncontrolled
		*/
		Enhancements::nonstandardEnhancementSet($this, 'ShadowFighter');
		
		$this->notes = "Shadow fighters are integral part of their carriers. For every Shadow fighter included in fleet, appropriate carrier should take a level of Fighter Launched enhancement OR fighter should take Uncontrolled enhancement (the latter for scenarios only).";
		
		$this->forwardDefense = 7;
		$this->sideDefense = 7;
		$this->freethrust = 15;
		$this->offensivebonus = 8;
		$this->jinkinglimit = 8;
		$this->turncost = 0.33;
        
		
	    $this->advancedArmor = true; 
        //$this->gravitic = true;
		$this->critRollMod = -100; //cannot drop out 
		
		$this->iniativebonus = 20 *5;
		$this->populate();
    }


    public function populate(){

        $current = count($this->systems);
        $new = $this->flightSize;
        $toAdd = $new - $current;

        for ($i = 0; $i < $toAdd; $i++){			
			$armour = array(4, 3, 3, 3);
			$fighter = new Fighter("ShadowMediumFighterFlight", $armour, 12, $this->id);
			$fighter->displayName = "Medium Fighter";
			$fighter->imagePath = "img/ships/ShadowFighter.png";
			$fighter->iconPath = "img/ships/ShadowFighter_LARGE.png";
						
			//ramming attack - no room to show it cleanly on Aft, Diffuser and Tendrils take a lot of room...			
			$fighter->addFrontSystem(new RammingAttack(0, 0, 360, $fighter->getRammingFactor(), 0)); //ramming attack
			
			$fighter->addFrontSystem(new ftrPolarityCannon(330, 30, 1));//arcfrom, arcto, numberofguns	
			
			//Advanced Sensors
            $fighter->addFrontSystem(new Fighteradvsensors(0, 1, 0));			
			
			$diffuser = new EnergyDiffuser(0, 1, 1, 0, 360);//($armour, $maxhealth, $dissipation, $startArc, $endArc)
			$tendril=new DiffuserTendrilFtr(3,'L');//absorbtion capacity,side
			$diffuser->addTendril($tendril);
			$fighter->addAftSystem($tendril);
			$fighter->addAftSystem($diffuser);
			$tendril=new DiffuserTendrilFtr(3,'R');//absorbtion capacity,side
			$diffuser->addTendril($tendril);
			$fighter->addAftSystem($tendril);
			
			$this->addSystem($fighter);			
		}	
    }//endof function populate


/*RENAME TO _OLD - actually current state of default hit handling should be able to handle Shadow fighter just as well*/
	/*remaking damage allocation routine - this fighter is special enough (no dropouts, Diffuser) that it should actually have different priorities when handling damage allocation*/
    public function getHitSystem_old($shooter, $fire, $weapon, $gamedata, $location = null)
    {
		return parent::getHitSystem($shooter, $fire, $weapon, $gamedata, $location);/*core routines were modified to handle protected fighters!*/
		//...and nothing below matters due to the above, but I'm leaving the code just in case
        $skipStandard = false;
        $systems = array();
        if ($fire->calledid != -1) {
            $system = $this->getSystemById($fire->calledid);
			//if system is not actual fighter - redirect to fighter it's mounted on!
			if(!$system instanceof Fighter){
				$system = $this->getFighterBySystem($system->id);
			}
			
            if (!$system->isDestroyed()) { //called shot at particular fighter, which is still living
                $systems[] = $system;
                $skipStandard = true;
            }
        }

        if (!$skipStandard) {
            foreach ($this->systems as $system) {
                if (!$system->isDestroyed()) {
                    $systems[] = $system;
                }
            }
        }

        if (sizeof($systems) == 0) return null;
	    
		/* AF fire is normally allocated by player, and it's very important for fighter toughness
		in FV there is no information about actual amount of incoming damage
		but let's try to make an algorithm based on damage _potential_ of incoming shot - won't be as good, but far better than random allocation
		priority of allocation FOR SHADOW FIGHTER SPECIFICALLY:
		 1. no chance of being destroyed
		 2. being LESS damaged already (unless destruction is guaranteed)
		 4. having higher ID (last craft in flight first - if any craft is special, it'll be the first one)
		*/
		//fill data about eligible craft...
		$craftWithData = array();
		foreach ($systems as $craft){
			$dmgPotential = 0;
			$dmgPotential = $weapon->maxDamage; //potential = maximum damage weapon can do	
			$armor = $weapon->getSystemArmourComplete($this, $craft, $gamedata, $fire);
			//modify by Diffuser! 
			$protection=0;
			$diffuser = $this->getSystemProtectingFromDamage($shooter, null, $gamedata->turn, $weapon, $craft,$dmgPotential);//let's find biggest one!
			if($diffuser){ //may be unavailable, eg. already filled
				$protection = $diffuser->doesProtectFromDamage($dmgPotential,$craft);
			}
			$armor += $protection;		
			$dmgPotential = max(0, $dmgPotential-$armor);//never negative damage ;)
		
			/*for linked weapons - multiply by number of shots!*/
			if ($weapon->isLinked){
				$dmgPotential = $dmgPotential*$weapon->shots;
			}
			$remainingHP = $craft->getRemainingHealth()+$protection; //let's count Tendril capacity as remaining hp!
			$minRemainingHP = $remainingHP-$dmgPotential;
			$canBeKilled = false;
			if ($minRemainingHP<1) $canBeKilled = true;
			$singleCraft = array("id"=>$craft->id, "hp"=>$remainingHP, "canDrop"=>false, "canDie"=>$canBeKilled, "fighter"=>$craft);
			$craftWithData[] = $singleCraft;
		}
	    
		//sort by priorities, return first one on list!
		usort($craftWithData, function($a, $b){
			if (($a["canDie"] == true) && ($b["canDie"] == false)){ //prefer craft with no death chance
				return 1;	
			} else if (($b["canDie"] == true) && ($a["canDie"] == false)){ 
				return -1;	
			} else if ($a["hp"] > $b["hp"]){ //prefer LESS damaged craft
				return 1;
			} else if ($b["hp"] > $a["hp"]){ 
				return -1;	
			} else if ($a["id"] < $b["id"]){ //lastly - prefer one that's further in order of IDs
				return 1;
			} else if ($b["id"] < $a["id"]){ 
				return -1;	
			}	
			else return 0; //should never happen, IDs are different!
		});
			
		return $craftWithData[0]["fighter"];

        //return $systems[(Dice::d(sizeof($systems)) - 1)];
    }

}



?>

<?php
class ThorunHeavy extends FighterFlight{
    
    function __construct($id, $userid, $name,  $slot){
        parent::__construct($id, $userid, $name,  $slot);
        
		$this->pointCost = 312;
		$this->faction = "Dilgar Imperium";
        $this->phpclass = "ThorunHeavy";
        $this->shipClass = "Thorun Heavy Dartfighters";
		$this->imagePath = "img/ships/thorun.png";
		$this->isd = 2232;
				
        $this->notes = '+5 Initiative bonu as long as flight leader is alive and uninjured.';


        $this->occurence = "rare";
        //$this->variantOf = "Thorun Dartfighters";
	        $this->variantOf = 'OBSOLETE'; //awaiting all games it's used in, then is to be removed from active ships list

		
		$this->enhancementOptionsEnabled[] = 'NAVIGATOR'; //this flight can take Navigator enhancement option	
        
        $this->forwardDefense = 8;
        $this->sideDefense = 7;
        $this->freethrust = 10;
        $this->offensivebonus = 5;
        $this->jinkinglimit = 6;
        $this->turncost = 0.33;
        
		$this->iniativebonus = 80;
        
        $this->dropOutBonus = -2;
        $this->populate();
    }

    public function populate(){

        $current = count($this->systems);
        $new = $this->flightSize;
        $toAdd = $new - $current;

        for ($i = 0; $i < $toAdd; $i++){
            $armour = array(3, 2, 2, 2);
            
            $fighter = new Fighter("thorun", $armour, 11, $this->id);
            
            $fighter->imagePath = "img/ships/thorun.png";
            $fighter->iconPath = "img/ships/thorun_large.png";
            
            if(count($this->systems) == 0 ){
                //$fighter->displayName = "Thorun Heavy Leader";  
                $this->flightLeader = $fighter;
                //$fighter->iconPath = "img/ships/thorun_leader_large.png";
            } //else {
                $fighter->displayName = "Thorun Heavy";
            //}
            
            $fighter->addFrontSystem(new PairedLightBoltCannon(330, 30, 4));
            $fighter->addFrontSystem(new FighterMissileRack(4, 330, 30));
            
			$fighter->addAftSystem(new RammingAttack(0, 0, 360, $fighter->getRammingFactor(), 0)); //ramming attack
            
            $this->addSystem($fighter);
        }
    }

    public function getInitiativebonus($gamedata){
        $initiativeBonusRet = parent::getInitiativebonus($gamedata);
        
        // Check if flightleader is still uninjured and alive
        if($this->flightLeader!=null
                && !$this->flightLeader->isDisengaged($gamedata->turn)
                && $this->flightLeader->getRemainingHealth() == 11){
            $initiativeBonusRet += 5;
        }
        
        return $initiativeBonusRet;
    }
}

?>

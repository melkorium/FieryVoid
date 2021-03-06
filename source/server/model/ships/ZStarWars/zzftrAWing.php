<?php
class zzftrawing extends FighterFlight{
    /*StarWars A-Wing...*/
    function __construct($id, $userid, $name,  $slot){
        parent::__construct($id, $userid, $name,  $slot);
        
        $this->pointCost = 35*6;
        $this->faction = "ZStarWars";
        $this->phpclass = "zzftrawing";
        $this->shipClass = "A-Wing Interceptors";
        $this->imagePath = "img/starwars/aWing.png";
        
		$this->isd = "18 BBY";
		$this->notes = "Primary users: Rebel Alliance, New Republic.";
		$this->notes .= "<br>Hyperdrive";
	    
        //$this->isd = 2214;
        $this->unofficial = true;
        
        $this->forwardDefense = 4;
        $this->sideDefense = 5;
        $this->freethrust = 14;
        $this->offensivebonus = 5;
        $this->jinkinglimit = 10;
        $this->pivotcost = 2; //SW fighters have higher pivot cost - only elite pilots perform such maneuvers on screen!
		$this->enhancementOptionsEnabled[] = 'ELITE_SW'; //this flight can have Elite Pilot (SW) enhancement option	
        $this->turncost = 0.33;
        
    	$this->iniativebonus = 20 *5;
		
		
		$this->hangarRequired = "Fighter Squadrons"; //SW small craft are handled on squadron basis
		$this->unitSize = 12; //number of craft in squadron
		
        $this->populate();
    }
    public function populate(){
        $current = count($this->systems);
        $new = $this->flightSize;
        $toAdd = $new - $current;
        for ($i = 0; $i < $toAdd; $i++){
		$armour = array(1, 1, 1, 1);
		$fighter = new Fighter("zzftrawing", $armour, 7, $this->id);
		$fighter->displayName = "A-Wing";
            	$fighter->imagePath = "img/starwars/aWing.png";
            	$fighter->iconPath = "img/starwars/aWing_large.png"; 
            
		
            	$frontGun = new SWFighterLaser(300, 60, 2, 2); //front Lasers
            	$fighter->addFrontSystem($frontGun);
            
		
            	//2 forward Concussion Missile Launchers, 3 shots each
            	$ConcussionMissileLauncher = new SWFtrConcMissileLauncher(3, 330, 30, 2);//single dual launcher! like for direct fire
            	$fighter->addFrontSystem($ConcussionMissileLauncher);

             
            	//Ray Shield, 1 points
            	$fighter->addAftSystem(new SWRayShield(0, 1, 0, 1, 0, 360));
            
            
			$fighter->addAftSystem(new RammingAttack(0, 0, 360, $fighter->getRammingFactor(), 0)); //ramming attack
            
            $this->addSystem($fighter);
       }
    }
    
    
}
?>

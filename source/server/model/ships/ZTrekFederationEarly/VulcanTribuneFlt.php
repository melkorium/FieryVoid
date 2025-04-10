<?php
class VulcanTribuneFlt extends FighterFlight{
    
    function __construct($id, $userid, $name,  $slot){
        parent::__construct($id, $userid, $name,  $slot);
        
        $this->pointCost = 90 *6; //for 6; a bit add-on for not requiring carrier
        $this->faction = "ZStarTrek (early) Federation";
        $this->phpclass = "VulcanTribuneFlt";
        $this->shipClass = "Vulcan Tribune flight";
        $this->imagePath = "img/ships/StarTrek/VulcanTribune.png";
		$this->unofficial = true;
		
        $this->isd = 2151;
		$this->notes = "Warp Engine";
		$this->notes .= "<br>No hangar required";
        $this->limited = 10; //Restricted 10% - to go with no hangar requirements; to be decided later whether such a limitation is necessary; "ships per hull" limitation will apply (at flight level)
		$this->hangarRequired = ""; //NO hangar required

        $this->forwardDefense = 9;
        $this->sideDefense = 10;
        $this->freethrust = 9;
        $this->offensivebonus = 4;
        $this->jinkinglimit = 2;
        $this->turncost = 0.33;
		
        $this->pivotcost = 2;

	//$this->unitSize = 3; //number of craft in squadron
    	$this->superheavy = true;
        $this->maxFlightSize = 3;//this is a superheavy fighter originally intended as single unit, limit flight size to 3
        
       	$this->iniativebonus = 14 *5; //default SHF ini
        $this->populate();
    }
    public function populate(){
        $current = count($this->systems);
        $new = $this->flightSize;
        $toAdd = $new - $current;
        for ($i = 0; $i < $toAdd; $i++){   
            $armour = array(3, 2, 3, 3);
            $fighter = new Fighter("VulcanTribuneFlt", $armour, 25, $this->id);
            $fighter->displayName = "Tribune Courier";
			
            $fighter->imagePath = "img/ships/StarTrek/VulcanTribune.png";
            $fighter->iconPath = "img/ships/StarTrek/VulcanTribuneLarge.png";
			
			
			$frontGun1 = new TrekFtrPhaser(210, 30, 2, 1,"Phase Cannon");
            $fighter->addFrontSystem($frontGun1);

            $frontGun = new TrekFtrPhaseCannon(330, 30, 4, 1, 6, "Phase Cannon"); //arc from/to, damage bonus, number of shots, rake size, base weapon name
            $fighter->addFrontSystem($frontGun);

			$frontGun2 = new TrekFtrPhaser(330, 150, 2, 1,"Phase Cannon");
            $fighter->addFrontSystem($frontGun2);

			$fighter->addAftSystem(new TrekShieldFtr(1, 6, 3, 1) ); //armor, health, rating, recharge
			
			$fighter->addAftSystem(new RammingAttack(0, 0, 360, $fighter->getRammingFactor(), 0)); //ramming attack
		
            $this->addSystem($fighter);
        }
    }
}
?>
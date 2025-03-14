<?php
class DeltaV extends FighterFlight{
    
    function __construct($id, $userid, $name,  $slot){
        parent::__construct($id, $userid, $name,  $slot);
        
	$this->pointCost = 192;
	$this->faction = "Raiders";
        $this->phpclass = "DeltaV";
        $this->shipClass = "Delta-V Light Fighters";
	$this->imagePath = "img/ships/deltaV.png";
        
        $this->forwardDefense = 5;
        $this->sideDefense = 5;
        $this->freethrust = 9;
        $this->offensivebonus = 3;
        $this->jinkinglimit = 10;
        $this->turncost = 0.33;
        
		$this->notes = "Generic raider unit.";
		$this->notes .= "<br> ";

		$this->isd = 2190;
        
	$this->iniativebonus = 100;
        $this->populate();
    }

    public function populate(){

        $current = count($this->systems);
        $new = $this->flightSize;
        $toAdd = $new - $current;

        for ($i = 0; $i < $toAdd; $i++){
		$armour = array(2, 0, 1, 1);
		$fighter = new Fighter("deltaV", $armour, 7, $this->id);
		$fighter->displayName = "Delta-V";
		$fighter->imagePath = "img/ships/deltaV.png";
		$fighter->iconPath = "img/ships/deltaV_large.png";
			
		$fighter->addFrontSystem(new PairedParticleGun(330, 30, 2));
			
		$fighter->addAftSystem(new RammingAttack(0, 0, 360, $fighter->getRammingFactor(), 0)); //ramming attack
		
		$this->addSystem($fighter);
	}
    }
}


?>

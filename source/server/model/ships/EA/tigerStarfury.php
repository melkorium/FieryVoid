<?php
class TigerStarfury extends FighterFlight{
    
    function __construct($id, $userid, $name,  $slot){
        parent::__construct($id, $userid, $name,  $slot);
        
        $this->pointCost = 282;
        $this->faction = "Earth Alliance";
        $this->phpclass = "TigerStarfury";
        $this->shipClass = "Starfury: Tiger Heavy flight";
        $this->imagePath = "img/ships/tigerStarfury2.png";
	    $this->isd = 2203;
	    
	    $this->notes = 'Non-atmospheric.';
        
        $this->forwardDefense = 8;
        $this->sideDefense = 6;
        $this->freethrust = 8;
        $this->offensivebonus = 3;
        $this->jinkinglimit = 6;
        $this->turncost = 0.33;
        
	$this->iniativebonus = 80;
        $this->populate();
    }

    public function populate(){

        $current = count($this->systems);
        $new = $this->flightSize;
        $toAdd = $new - $current;

        for ($i = 0; $i < $toAdd; $i++){
            $armour = array(2, 1, 2, 2);
            $fighter = new Fighter("tigerStarfury", $armour, 11, $this->id);
            $fighter->displayName = "Tiger";
            $fighter->imagePath = "img/ships/tigerStarfury2.png";
            $fighter->iconPath = "img/ships/tigerStarfury2_large.png";

            $frontGun = new PairedParticleGun(330, 30, 4);
            $frontGun->displayName = "Uni-Pulse Cannon";
            $fighter->addFrontSystem($frontGun);
			$fighter->addAftSystem(new RammingAttack(0, 0, 360, $fighter->getRammingFactor(), 0)); //ramming attack	
            $this->addSystem($fighter);
	}
    }
}
?>

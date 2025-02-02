<?php
class VelraxResteraxFighter extends FighterFlight{
    
    function __construct($id, $userid, $name,  $slot){
        parent::__construct($id, $userid, $name,  $slot);
        
        $this->pointCost = 44*6;
        $this->faction = "ZNexus Velrax Republic (early)";
        $this->phpclass = "VelraxResteraxFighter";
        $this->shipClass = "Resterax-A Assault flight";
        $this->imagePath = "img/ships/Nexus/velraxResterax.png";
		$this->unofficial = true;

        $this->isd = 2062;
        
        $this->forwardDefense = 7;
        $this->sideDefense = 8;
        $this->freethrust = 9;
        $this->offensivebonus = 4;
        $this->jinkinglimit = 6;
        $this->turncost = 0.33;
		$this->turndelay = 0;
        
        $this->iniativebonus = 80;
        $this->populate();       

    }

    public function populate(){

        $current = count($this->systems);
        $new = $this->flightSize;
        $toAdd = $new - $current;

        for ($i = 0; $i < $toAdd; $i++){            
            $armour = array(3, 1, 1, 1);
            $fighter = new Fighter("VelraxResteraxFighter", $armour, 12, $this->id);
            $fighter->displayName = "Resterax-A";
            $fighter->imagePath = "img/ships/Nexus/velraxResterax.png";
            $fighter->iconPath = "img/ships/Nexus/velraxResterax_large.png";

//			$mauler = new NexusMauler(330, 30, 1);
//			$fighter->addFrontSystem($mauler);
			$fighter->addFrontSystem(new IonBolt(330, 30));
	        $light = new NexusLightIonGun(330, 30, 0, 1); //$startArc, $endArc, $nrOfShots
	        $fighter->addFrontSystem($light);
			$aftLight = new NexusLightIonGun(150, 210, 0, 1);
			
			$fighter->addAftSystem($aftLight);
			$fighter->addAftSystem(new RammingAttack(0, 0, 360, $fighter->getRammingFactor(), 0)); //ramming attack			
            
            $this->addSystem($fighter);
        }
    }
}
?>

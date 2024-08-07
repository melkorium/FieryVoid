<?php
class ChoukaRaiderReclumDFighter extends FighterFlight{
    
    function __construct($id, $userid, $name,  $slot){
        parent::__construct($id, $userid, $name,  $slot);
        
        $this->pointCost = 20*6;
        $this->faction = "ZEscalation Chouka Raider";
        $this->phpclass = "ChoukaRaiderReclumDFighter";
        $this->shipClass = "Reclum-D Light flight (missile)";
			$this->variantOf = "Reclum-A Light flight";
			$this->occurence = "common";
        $this->imagePath = "img/ships/EscalationWars/ChoukaRaiderReclum.png";
		$this->unofficial = true;
		
        $this->isd = 1957;
        
        $this->forwardDefense = 6;
        $this->sideDefense = 6;
        $this->freethrust = 10;
        $this->offensivebonus = 3;
        $this->jinkinglimit = 10;
        $this->turncost = 0.33;
		$this->turndelay = 0;
        
        $this->iniativebonus = 100;
        $this->populate();       

    }

    public function populate(){

        $current = count($this->systems);
        $new = $this->flightSize;
        $toAdd = $new - $current;

        for ($i = 0; $i < $toAdd; $i++){            
            $armour = array(1, 1, 1, 1);
            $fighter = new Fighter("ChoukaRaiderReclumCFighter", $armour, 7, $this->id);
            $fighter->displayName = "Reclum-D";
            $fighter->imagePath = "img/ships/EscalationWars/ChoukaRaiderReclum.png";
            $fighter->iconPath = "img/ships/EscalationWars/ChoukaRaiderReclum_Large.png";

			$gun = new LightParticleBeam(330, 30, 1, 1);
			$gun->displayName = "Ultralight Particle Beam";
			$fighter->addFrontSystem($gun);
            $fighter->addFrontSystem(new FighterMissileRack(2, 330, 30));
		
			$fighter->addAftSystem(new RammingAttack(0, 0, 360, $fighter->getRammingFactor(), 0)); //ramming attack			
            
            $this->addSystem($fighter);
        }
    }
}
?>

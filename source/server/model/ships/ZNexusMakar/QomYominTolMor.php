<?php
class QomYominTolMor extends FighterFlight{
    
    function __construct($id, $userid, $name,  $slot){
        parent::__construct($id, $userid, $name,  $slot);
        
        $this->pointCost = 20*6;
        $this->faction = "ZNexus Makar Federation";
        $this->phpclass = "QomYominTolMor";
        $this->shipClass = "Tol Mor Armed Drone";
        $this->imagePath = "img/ships/Nexus/makar_tolmor.png";
		$this->unofficial = true;

        $this->isd = 1925;
        
        $this->forwardDefense = 7;
        $this->sideDefense = 7;
        $this->freethrust = 10;
        $this->offensivebonus = 3;
        $this->jinkinglimit = 4;
        $this->turncost = 0.33;
		$this->turndelay = 0;
        
        $this->iniativebonus = 90;
        $this->populate();       

        HkControlNode::addHKFlight($this);

    }

    public function populate(){

        $current = count($this->systems);
        $new = $this->flightSize;
        $toAdd = $new - $current;

        for ($i = 0; $i < $toAdd; $i++){            
            $armour = array(1, 1, 0, 0);
            $fighter = new Fighter("QomYominTolMor", $armour, 5, $this->id);
            $fighter->displayName = "Tol Mor";
            $fighter->imagePath = "img/ships/Nexus/makar_tolmor.png";
            $fighter->iconPath = "img/ships/Nexus/makar_tolmor_large.png";

	        $light = new NexusLightDefenseGun(300, 60, 1); //$startArc, $endArc, $nrOfShots
	        $fighter->addFrontSystem($light);
			
			$fighter->addAftSystem(new RammingAttack(0, 0, 360, $fighter->getRammingFactor(), 0)); //ramming attack			
            
            $this->addSystem($fighter);
        }
    }


    public function getInitiativebonus($gamedata){
        $iniBonus = parent::getInitiativebonus($gamedata);
	$iniBonus += HkControlNode::getIniMod($this->userid,$gamedata);
        return $iniBonus;
    }	


}
?>

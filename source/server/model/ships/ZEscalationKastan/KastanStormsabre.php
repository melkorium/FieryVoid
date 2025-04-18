<?php
class KastanStormsabre extends HeavyCombatVessel{
    
    function __construct($id, $userid, $name,  $slot){
        parent::__construct($id, $userid, $name,  $slot);
        
        $this->pointCost = 425;
        $this->faction = "ZEscalation Kastan Monarchy";
        $this->phpclass = "KastanStormsabre";
        $this->imagePath = "img/ships/EscalationWars/KastanSteelsabre.png";
			$this->canvasSize = 125; //img has 200px per side
        $this->shipClass = "Stormsabre Torpedo Destroyer";
			$this->variantOf = "Steelsabre Destroyer";
			$this->occurence = "uncommon";
		$this->unofficial = true;

        $this->isd = 1890;
        
        $this->forwardDefense = 14;
        $this->sideDefense = 15;
        
        $this->turncost = 0.66;
        $this->turndelaycost = 0.66;
        $this->accelcost = 2;
        $this->rollcost = 2;
        $this->pivotcost = 2;
        $this->iniativebonus = 40;
         
        $this->addPrimarySystem(new Reactor(4, 20, 0, 0));
        $this->addPrimarySystem(new CnC(5, 15, 0, 0));
        $this->addPrimarySystem(new Scanner(4, 12, 3, 5));
        $this->addPrimarySystem(new Engine(4, 14, 0, 8, 2));
        $this->addPrimarySystem(new Hangar(3, 2));
        $this->addPrimarySystem(new Thruster(3, 13, 0, 4, 3));
        $this->addPrimarySystem(new Thruster(3, 13, 0, 4, 4));
      
        $this->addFrontSystem(new Thruster(2, 8, 0, 3, 1));
        $this->addFrontSystem(new Thruster(2, 8, 0, 3, 1));
		$this->addFrontSystem(new EWPulseTorpedo(3, 6, 4, 300, 60));
		$this->addFrontSystem(new EWPulseTorpedo(3, 6, 4, 300, 60));
		$this->addFrontSystem(new EWLaserBolt(2, 4, 2, 240, 360));
		$this->addFrontSystem(new EWLaserBolt(2, 4, 2, 240, 360));
		$this->addFrontSystem(new EWLaserBolt(2, 4, 2, 0, 120));
		$this->addFrontSystem(new EWLaserBolt(2, 4, 2, 0, 120));
                
        $this->addAftSystem(new Thruster(3, 11, 0, 4, 2));
        $this->addAftSystem(new Thruster(3, 11, 0, 4, 2));
		$this->addAftSystem(new EWPulseTorpedo(2, 6, 4, 240, 360));
		$this->addAftSystem(new EWPulseTorpedo(2, 6, 4, 0, 120));
        $this->addAftSystem(new EWLaserBolt(2, 4, 2, 180, 300));
        $this->addAftSystem(new EWLaserBolt(2, 4, 2, 60, 180));
        
        //0:primary, 1:front, 2:rear, 3:left, 4:right;
        $this->addFrontSystem(new Structure( 3, 48));
        $this->addAftSystem(new Structure( 3, 42));
        $this->addPrimarySystem(new Structure( 4, 48));
		
        $this->hitChart = array(
            0=> array(
                    8 => "Structure",
                    11 => "Thruster",
                    13 => "Scanner",
                    15 => "Engine",
                    17 => "Hangar",
                    19 => "Reactor",
                    20 => "C&C",
            ),
            1=> array(
                    4 => "Thruster",
                    6 => "Pulse Torpedo",
					9 => "Laser Bolt",
					18 => "Structure",
                    20 => "Primary",
            ),
            2=> array(
                    6 => "Thruster",
                    8 => "Pulse Torpedo",
					10 => "Laser Bolt",
                    18 => "Structure",
                    20 => "Primary",
            ),
        );
    }
}
?>

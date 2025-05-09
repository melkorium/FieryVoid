<?php
class Vasachi extends HeavyCombatVessel{
    
    function __construct($id, $userid, $name,  $slot){
        parent::__construct($id, $userid, $name,  $slot);
        
        $this->pointCost = 650;
        $this->faction = "Centauri Republic";
        $this->phpclass = "Vasachi";
        $this->imagePath = "img/ships/sulust.png";
        $this->shipClass = "Vasachi Escort Destroyer";
        $this->occurence = "rare";
        $this->variantOf = "Sulust Escort Destroyer";
        $this->isd = 2240;
        
        $this->forwardDefense = 14;
        $this->sideDefense = 16;
        
        $this->turncost = 0.50;
        $this->turndelaycost = 0.50;
        $this->accelcost = 2;
        $this->rollcost = 1;
        $this->pivotcost = 2;
        $this->iniativebonus = 30;
        
         
        $this->addPrimarySystem(new Reactor(6, 15, 0, 4));
        $this->addPrimarySystem(new CnC(5, 12, 0, 0));
        $this->addPrimarySystem(new Scanner(5, 15, 5, 8));
        $this->addPrimarySystem(new Engine(5, 15, 0, 10, 2));
        $this->addPrimarySystem(new Hangar(5, 1));
        $this->addPrimarySystem(new Thruster(3, 10, 0, 4, 3));
        $this->addPrimarySystem(new Thruster(3, 10, 0, 4, 4));
        
        
        
        $this->addFrontSystem(new Thruster(4, 10, 0, 3, 1));
        $this->addFrontSystem(new Thruster(4, 10, 0, 3, 1));
       
        $this->addFrontSystem(new HeavyArray(4, 8, 4, 300, 60));
        
        
        $this->addFrontSystem(new TwinArray(4, 6, 2, 180, 60));
        $this->addFrontSystem(new TwinArray(4, 6, 2, 300, 60));
        $this->addFrontSystem(new TwinArray(4, 6, 2, 300, 180));
        $this->addFrontSystem(new GuardianArray(3, 4, 2, 180, 0));
        $this->addFrontSystem(new GuardianArray(3, 4, 2, 0,180));
        
        $this->addAftSystem(new Thruster(4, 14, 0, 5, 2));
        $this->addAftSystem(new Thruster(4, 14, 0, 5, 2));
        $this->addAftSystem(new TwinArray(3, 6, 2, 120, 300));
        $this->addAftSystem(new TwinArray(3, 6, 2, 60, 240));
        
        
        //0:primary, 1:front, 2:rear, 3:left, 4:right;
        $this->addFrontSystem(new Structure( 4, 60));
        $this->addAftSystem(new Structure( 4, 60));
        $this->addPrimarySystem(new Structure( 6, 40));
        
        $this->hitChart = array(
                0=> array(
                    6 => "Structure",
                    10 => "Thruster",
                    13 => "Scanner",
                    16 => "Engine",
                    17 => "Hangar",
                    19 => "Reactor",
                    20 => "C&C",
                ),
                1=> array(
                    5 => "Thruster",
                    6 => "Heavy Array",
                    9 => "Twin Array",
                    11 => "Guardian Array",
                    18 => "Structure",
                    20 => "Primary",
                ),
                2=> array(
                    6 => "Thruster",
                    8 => "Twin Array",
                    18 => "Structure",
                    20 => "Primary",
			),
		); 
        
    }

}



?>

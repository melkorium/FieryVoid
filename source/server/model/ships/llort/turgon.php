<?php
class Turgon extends BaseShip{
    
    function __construct($id, $userid, $name,  $slot){
        parent::__construct($id, $userid, $name,  $slot);
        
        $this->pointCost = 600;
        $this->faction = "Llort";
        $this->phpclass = "Turgon";
        $this->imagePath = "img/ships/LlortTurlisk.png";
        $this->shipClass = "Turgon Bolt Cruiser";
        $this->shipSizeClass = 3;
        $this->fighters = array("normal"=>12);
     
        $this->occurence = "rare";
        $this->variantOf = 'Turlisk Jump Carrier';
        $this->isd = 2237;
   
        $this->forwardDefense = 17;
        $this->sideDefense = 17;
        
        $this->turncost = 1;
        $this->turndelaycost = 1;
        $this->accelcost = 3;
        $this->rollcost = 2;
        $this->pivotcost = 3;
         
        $this->addPrimarySystem(new Reactor(5, 18, 0, 0));
        $this->addPrimarySystem(new CnC(5, 16, 0, 0));
        $this->addPrimarySystem(new Scanner(5, 12, 4, 6));
        $this->addPrimarySystem(new Engine(4, 20, 0, 9, 3));
        $this->addPrimarySystem(new Hangar(4, 14));
        $this->addPrimarySystem(new JumpEngine(4, 15, 5, 24));
  
        $this->addFrontSystem(new Thruster(4, 10, 0, 4, 1));
	$this->addFrontSystem(new Thruster(4, 10, 0, 4, 1));
        $this->addFrontSystem(new MediumBolter(4, 8, 4, 240, 0));
        $this->addFrontSystem(new ScatterGun(3, 8, 3, 270, 90));
        $this->addFrontSystem(new HeavyBolter(4, 10, 6, 300, 60));
        $this->addFrontSystem(new ScatterGun(3, 8, 3, 270, 90));
        $this->addFrontSystem(new MediumBolter(4, 8, 4, 0, 120));

        $this->addAftSystem(new ScatterGun(3, 8, 3, 90, 270));
        $this->addAftSystem(new ScatterGun(3, 8, 3, 90, 270));
        $this->addAftSystem(new Thruster(4, 10, 0, 3, 2));
        $this->addAftSystem(new Thruster(4, 10, 0, 3, 2));
        $this->addAftSystem(new Thruster(4, 10, 0, 3, 2));

        $this->addLeftSystem(new Thruster(4, 13, 0, 4, 3));
        $this->addLeftSystem(new ScatterGun(3, 8, 3, 180, 360));
  
        $this->addRightSystem(new Thruster(4, 13, 0, 4, 4));
        $this->addRightSystem(new ScatterGun(3, 8, 3, 0, 180));
        
        
        //0:primary, 1:front, 2:rear, 3:left, 4:right;
        $this->addFrontSystem(new Structure( 5, 42));
        $this->addAftSystem(new Structure( 5, 38));
        $this->addLeftSystem(new Structure( 5, 56));
        $this->addRightSystem(new Structure( 5, 56));
        $this->addPrimarySystem(new Structure( 5, 48));
        
        $this->hitChart = array(
        		0=> array(
        				8 => "Structure",
        				11 => "Jump Engine",
        				13 => "Scanner",
        				15 => "Engine",
        				17 => "Hangar",
        				19 => "Reactor",
        				20 => "C&C",
        		),
        		1=> array(
        				5 => "Thruster",
        				7 => "Heavy Bolter",
        				9 => "Medium Bolter",
        				11 => "Scattergun",
        				18 => "Structure",
        				20 => "Primary",
        		),
        		2=> array(
        				6 => "Thruster",
        				8 => "Scattegun",
         				18 => "Structure",
        				20 => "Primary",
        		),
        		3=> array(
        				5 => "Thruster",
					7 => "Scattergun",
        				18 => "Structure",
        				20 => "Primary",
        		),
        		4=> array(
        				5 => "Thruster",
					7 => "Scattergun",
        				18 => "Structure",
        				20 => "Primary",
        		),
        );
    }
}

?>

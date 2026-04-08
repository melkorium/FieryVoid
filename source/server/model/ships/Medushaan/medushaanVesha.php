<?php
class medushaanCorlesc extends MediumShip{

    function __construct($id, $userid, $name,  $slot){
        parent::__construct($id, $userid, $name,  $slot);
        
        $this->pointCost = 170;
        $this->faction = "Medushaan";
        $this->phpclass = "medushaanCorlesc";
        $this->imagePath = "img/ships/Nexus/Dalithorn_CommandFrigate2.png";
        $this->shipClass = "Corlesc Freighter";
//			$this->variantOf = "Frigate";
//			$this->occurence = "rare";
		$this->unofficial = true;
        $this->canvasSize = 100;
	    $this->isd = 1857;
        
        $this->forwardDefense = 10;
        $this->sideDefense = 11;
        
        $this->turncost = 0.5;
        $this->turndelaycost = 0.5;
        $this->accelcost = 3;
        $this->rollcost = 2;
        $this->pivotcost = 2;
        $this->iniativebonus = 0;

		$cA = new CargoBay(2, 25);
		$cB = new CargoBay(2, 25);
		$cC = new CargoBay(2, 25);
		$cD = new CargoBay(2, 25);

		$cA->displayName = "Cargo Bay A";
		$cB->displayName = "Cargo Bay B";
		$cC->displayName = "Cargo Bay C";
		$cD->displayName = "Cargo Bay D";
         
        $this->addPrimarySystem(new Reactor(3, 10, 0, 0));
        $this->addPrimarySystem(new CnC(3, 5, 0, 0));
        $this->addPrimarySystem(new Scanner(3, 9, 2, 3));
        $this->addPrimarySystem(new Engine(3, 9, 0, 6, 3));
        $this->addPrimarySystem(new Hangar(2, 2));
		$this->addPrimarySystem(new HeavySlugCannon(1, 3, 2, 0, 360));
		$this->addPrimarySystem(new HeavySlugCannon(1, 3, 2, 0, 360));
        $this->addPrimarySystem(new Thruster(2, 8, 0, 3, 3));
        $this->addPrimarySystem(new Thruster(2, 8, 0, 3, 4));        

        $this->addFrontSystem(new Thruster(2, 10, 0, 3, 1));
		$this->addFrontSystem(new LightRailGun(1, 6, 3, 300, 60));
		$this->addFrontSystem(new LightRailGun(1, 6, 3, 300, 60));
        $this->addFrontSystem($cA);
        $this->addFrontSystem($cB);
        $this->addFrontSystem($cC);
        $this->addFrontSystem($cD);
		
        $this->addAftSystem(new Thruster(2, 10, 0, 3, 2));
        $this->addAftSystem(new Thruster(2, 10, 0, 3, 2));
        $this->addAftSystem($cE);
        $this->addAftSystem($cF);
        $this->addAftSystem($cG);
        $this->addAftSystem($cH);
       
        $this->addPrimarySystem(new Structure(2, 36));

	//d20 hit chart
	$this->hitChart = array(
		
		0=> array(
			10 => "Thruster",
			13 => "Scanner",
			16 => "Engine",
			17 => "Hangar",
			18 => "Heavy Slug Cannon"
			19 => "Reactor",
			20 => "C&C",
		),

		1=> array(
			3 => "Thruster",
 			5 => "Light Railgun",
			7 => "Cargo Bay A",
			9 => "Cargo Bay B",
			17 => "Structure",
			20 => "Primary",
		),

		2=> array(
			6 => "Thruster",
			8 => "Cargo Bay C",
        	10 => "Cargo Bay D",
			17 => "Structure",
			20 => "Primary",
		),

	);

        
        }
    }
?>

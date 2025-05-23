<?php
class VelraxArcCorvetteRefit2 extends MediumShip{

    function __construct($id, $userid, $name,  $slot){
        parent::__construct($id, $userid, $name,  $slot);
        
        $this->pointCost = 290;
        $this->faction = "ZNexus Velrax Republic";
        $this->phpclass = "VelraxArcCorvetteRefit2";
        $this->imagePath = "img/ships/Nexus/velraxMassken.png";
        $this->shipClass = "Massken Arc Corvette (2109)";
			$this->variantOf = "Thristen Corvette (2109)";
			$this->occurence = "common";
		$this->unofficial = true;
        $this->canvasSize = 100;
	    $this->isd = 2109;

	    $this->notes = 'Atmospheric Capable.';
        
        $this->forwardDefense = 12;
        $this->sideDefense = 10;
        
        $this->turncost = 0.5;
        $this->turndelaycost = 0.5;
        $this->accelcost = 2;
        $this->rollcost = 1;
        $this->pivotcost = 1;
        $this->iniativebonus = 60;
         
        $this->addPrimarySystem(new Reactor(4, 8, 0, 0));
        $this->addPrimarySystem(new CnC(4, 8, 0, 0));
        $this->addPrimarySystem(new Scanner(4, 11, 4, 5));
        $this->addPrimarySystem(new Engine(3, 9, 0, 8, 3));
        $this->addPrimarySystem(new Thruster(2, 10, 0, 4, 3));
        $this->addPrimarySystem(new Thruster(2, 10, 0, 4, 4));        
        
		$this->addFrontSystem(new NexusTwinIonGun(1, 4, 4, 180, 60));
		$this->addFrontSystem(new EWPlasmaArc(2, 5, 4, 300, 60));
		$this->addFrontSystem(new EWPlasmaArc(2, 5, 4, 300, 60));
		$this->addFrontSystem(new NexusTwinIonGun(1, 4, 4, 300, 180));
        $this->addFrontSystem(new Thruster(2, 12, 0, 4, 1));
	    
        $this->addAftSystem(new Thruster(2, 9, 0, 4, 2));    
        $this->addAftSystem(new Thruster(2, 9, 0, 4, 2));    
        $this->addAftSystem(new Hangar(1, 2));
		$this->addAftSystem(new NexusIonGun(1, 2, 2, 120, 360));
		$this->addAftSystem(new NexusIonGun(1, 2, 2, 0, 240));
       
        $this->addPrimarySystem(new Structure(3, 40));

	//d20 hit chart
	$this->hitChart = array(
		
		0=> array(
			9 => "Thruster",
			13 => "Scanner",
			16 => "Engine",
			19 => "Reactor",
			20 => "C&C",
		),

		1=> array(
			6 => "Thruster",
			8 => "Twin Ion Gun",
			10 => "Plasma Arc",
			17 => "Structure",
			20 => "Primary",
		),

		2=> array(
			7 => "Thruster",
			9 => "Hangar",
			11 => "Ion Gun",
			17 => "Structure",
			20 => "Primary",
		),

	);

        
        }
    }
?>

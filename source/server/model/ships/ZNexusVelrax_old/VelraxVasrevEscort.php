<?php
class VelraxVasrevEscort extends MediumShip{

    function __construct($id, $userid, $name,  $slot){
        parent::__construct($id, $userid, $name,  $slot);
        
        $this->pointCost = 325;
        $this->faction = "ZNexus Velrax Republic (early)";
        $this->phpclass = "VelraxVasrevEscort";
        $this->imagePath = "img/ships/Nexus/velraxSathrin.png";
        $this->shipClass = "Vasrev Escort Frigate";
			$this->variantOf = "Sathrin Border Frigate";
			$this->occurence = "common";
		$this->unofficial = true;
        $this->agile = true;
        $this->canvasSize = 100;
	    $this->isd = 2063;

	    $this->notes = 'Atmospheric Capable.';

        $this->forwardDefense = 11;
        $this->sideDefense = 13;
        
        $this->turncost = 0.5;
        $this->turndelaycost = 0.33;
        $this->accelcost = 2;
        $this->rollcost = 2;
        $this->pivotcost = 2;
        $this->iniativebonus = 60;
         
        $this->addPrimarySystem(new Reactor(4, 12, 0, 0));
        $this->addPrimarySystem(new CnC(4, 10, 0, 0));
        $this->addPrimarySystem(new Scanner(3, 14, 5, 5));
        $this->addPrimarySystem(new Engine(3, 12, 0, 8, 3));
        $this->addPrimarySystem(new Hangar(1, 2));
        $this->addPrimarySystem(new Thruster(3, 12, 0, 4, 3));
        $this->addPrimarySystem(new Thruster(3, 12, 0, 4, 4));        
        
		$this->addFrontSystem(new EWPlasmaArc(3, 5, 4, 240, 60));
		$this->addFrontSystem(new EWPlasmaArc(3, 5, 4, 300, 120));
		$this->addFrontSystem(new NexusDartInterceptor(2, 4, 1, 270, 90));
		$this->addFrontSystem(new NexusTwinIonGun(3, 4, 4, 270, 90));
        $this->addFrontSystem(new Thruster(2, 12, 0, 4, 1));
	    
        $this->addAftSystem(new Thruster(3, 9, 0, 4, 2));    
        $this->addAftSystem(new Thruster(3, 9, 0, 4, 2));    
		$this->addAftSystem(new NexusTwinIonGun(2, 4, 4, 120, 360));
		$this->addAftSystem(new NexusTwinIonGun(2, 4, 4, 0, 240));
       
        $this->addPrimarySystem(new Structure(4, 50));

	//d20 hit chart
	$this->hitChart = array(
		
		0=> array(
			9 => "Thruster",
			12 => "Scanner",
			15 => "Engine",
			17 => "Hangar",
			19 => "Reactor",
			20 => "C&C",
		),

		1=> array(
			5 => "Thruster",
			7 => "Plasma Arc",
			9 => "Twin Ion Gun",
			11 => "Dart Interceptor",
			17 => "Structure",
			20 => "Primary",
		),

		2=> array(
			7 => "Thruster",
			10 => "Twin Ion Gun",
			17 => "Structure",
			20 => "Primary",
		),

	);

        
        }
    }
?>

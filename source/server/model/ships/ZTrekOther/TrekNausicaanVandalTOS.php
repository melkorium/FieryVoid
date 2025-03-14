<?php
class TrekNausicaanVandalTOS extends MediumShip{

    function __construct($id, $userid, $name,  $slot){
        parent::__construct($id, $userid, $name,  $slot);
        
        $this->pointCost = 450;
        $this->faction = "ZTrek Playtest Other Factions";
        $this->phpclass = "TrekNausicaanVandalTOS";
        $this->imagePath = "img/ships/StarTrek/NausicaanVandal.png";
        $this->shipClass = "Nausicaan Vandal Destroyer (Upgraded)";

	$this->unofficial = true;
        $this->occurence = "common";

        $this->canvasSize = 100;
	$this->isd = 2260;
	$this->fighters = array("heavy"=>6,"Shuttlecraft"=>1);
	$this->customFighter = array("Nausicaan small craft"=>7); //can deploy small craft with Nausicaan crew
        
        $this->forwardDefense = 11;
        $this->sideDefense = 15;
        
        $this->gravitic = true;  
        $this->turncost = 0.5;
        $this->turndelaycost = 0.5;
        $this->accelcost = 2;
        $this->rollcost = 1;
        $this->pivotcost = 2;
        $this->iniativebonus =  12 *5; 

        $this->addPrimarySystem(new Reactor(4, 20, 0, 0));
        $this->addPrimarySystem(new CnC(5, 9, 0, 0));
        $this->addPrimarySystem(new Scanner(4, 10, 4, 4));
        $this->addPrimarySystem(new Hangar(3, 7));
	$impulseDrive = new TrekImpulseDrive(5,20,0,2,3); //Impulse Drive is an engine in its own right, in addition to serving as hub for Nacelle output: $armour, $maxhealth, $powerReq, $output, $boostEfficiency


		$projection = new TrekShieldProjection(3, 15, 7, 270, 90, 'F');//parameters: $armor, $maxhealth, $rating, $arc from/to - F/A/L/R suggests whether to use left or right graphics
			$projector = new TrekShieldProjector(2, 8, 2, 2, 270, 90, 'F'); //parameters: $armor, $maxhealth, $power used, $rating, $arc from/to - F/A/L/R suggests whether to use left or right graphics
			$projection->addProjector($projector);
			$this->addFrontSystem($projector);
			$projector = new TrekShieldProjector(2, 8, 2, 2, 270, 90, 'F'); //parameters: $armor, $maxhealth, $power used, $rating, $arc from/to - F/A/L/R suggests whether to use left or right graphics
			$projection->addProjector($projector);
			$this->addFrontSystem($projector);
		$this->addFrontSystem($projection);
		$this->addFrontSystem(new PlasmaProjector(4, 8, 5, 300, 60));
        	$this->addFrontSystem(new PlasmaWaveTorpedo(4, 7, 4, 300, 60));      	
		$this->addFrontSystem(new EWPointPlasmaGun(2, 3, 2, 240, 90));
		$this->addFrontSystem(new EWPointPlasmaGun(2, 3, 2, 270, 120));


		$this->addAftSystem(new LtPlasmaProjector(2, 6, 3, 90, 270));
        	$this->addAftSystem(new EWPointPlasmaGun(2, 3, 2, 180, 360));
        	$this->addAftSystem(new EWPointPlasmaGun(2, 3, 2, 0, 180));
		$this->addAftSystem(new EWDualRocketLauncher(3, 6, 2, 210, 90));
		$this->addAftSystem(new EWDualRocketLauncher(3, 6, 2, 270, 150));
		$warpNacelle = new TrekWarpDrive(3, 16, 0, 4); //armor, structure, power usage, impulse output
		$impulseDrive->addThruster($warpNacelle);
		$this->addAftSystem($warpNacelle);
		$warpNacelle = new TrekWarpDrive(3, 16, 0, 4); //armor, structure, power usage, impulse output
		$impulseDrive->addThruster($warpNacelle);
		$this->addAftSystem($warpNacelle);


		$projection = new TrekShieldProjection(3, 12, 6, 90, 270, 'A');//parameters: $armor, $maxhealth, $rating, $arc from/to - F/A/L/R suggests whether to use left or right graphics
			$projector = new TrekShieldProjector(2, 6, 2, 2, 90, 270, 'A'); //parameters: $armor, $maxhealth, $power used, $rating, $arc from/to - F/A/L/R suggests whether to use left or right graphics
			$projection->addProjector($projector);
			$this->addAftSystem($projector);
			$projector = new TrekShieldProjector(2, 6, 2, 2, 90, 270, 'A'); //parameters: $armor, $maxhealth, $power used, $rating, $arc from/to - F/A/L/R suggests whether to use left or right graphics
			$projection->addProjector($projector);
			$this->addAftSystem($projector);
		$this->addAftSystem($projection);

		
		//technical thrusters - unlimited, like for LCVs		
		$this->addFrontSystem(new InvulnerableThruster(99, 1, 0, 99, 1)); //unhitable and with unlimited thrust allowance
		$this->addAftSystem(new InvulnerableThruster(99, 1, 0, 99, 2)); //unhitable and with unlimited thrust allowance   
		$this->addPrimarySystem(new InvulnerableThruster(99, 1, 0, 99, 3)); //unhitable and with unlimited thrust allowance
		$this->addPrimarySystem(new InvulnerableThruster(99, 1, 0, 99, 4)); //unhitable and with unlimited thrust allowance
        $this->addPrimarySystem($impulseDrive);

        $this->addPrimarySystem(new Structure(3, 40));

	//d20 hit chart
	$this->hitChart = array(
		
		0=> array(
			4 => "2:Nacelle",
			10 => "Structure",
			12 => "Hangar",			
			14 => "Scanner",
			16 => "Engine",
			18 => "Reactor",
			20 => "C&C",
		),

		1=> array(
		    2 => "Shield Projector",
			4 => "Point Plasma Gun",
			6 => "Plasma Projector",
			7 => "Plasma Wave",
			17 => "Structure",
			20 => "Primary",
		),

		2=> array(
			2 => "Shield Projector",
			7 => "Nacelle",
			9 => "Dual Rocket Launcher",
			11 => "Point Plasma Gun",
			12 => "Light Plasma Projector",
			18 => "Structure",
			20 => "Primary",
		),

	);

        
        }
    }
?>
<?php
class TrekFederationNXWarCruiser extends MediumShip{

    function __construct($id, $userid, $name,  $slot){
        parent::__construct($id, $userid, $name,  $slot);
        
        $this->pointCost = 360;
        $this->faction = "ZStarTrek (early) Federation";
        $this->phpclass = "TrekFederationNXWarCruiser";
        $this->imagePath = "img/ships/StarTrek/EnterpriseNX.png";
        $this->shipClass = "NX War Cruiser";
		/*
			$this->occurence = "common";
			$this->variantOf = "NX Cruiser";
*/
		$this->unofficial = true;
        $this->canvasSize = 100;
	    $this->isd = 2152;

	$this->fighters = array("Shuttlecraft"=>2);
		$this->customFighter = array("Human small craft"=>2); //can deploy small craft with Human crew
        
        $this->forwardDefense = 13;
        $this->sideDefense = 13;
        
        $this->gravitic = true;  
        $this->turncost = 0.5;
        $this->turndelaycost = 0.5;
        $this->accelcost = 2;
        $this->rollcost = 2;
        $this->pivotcost = 2;
        $this->iniativebonus = 10 *5; //deliberately lowered compared to standard MCV

        $this->addPrimarySystem(new Reactor(3, 12, 0, 4));
        $this->addPrimarySystem(new CnC(3, 9, 0, 0));
        $this->addPrimarySystem(new Scanner(3, 9, 4, 4));
        $this->addPrimarySystem(new Hangar(3, 2));
		$grappler = new CustomIndustrialGrappler(2, 5, 0, 0, 360);
			$grappler->displayName = "Magnetic Grappler";
			$this->addPrimarySystem($grappler);
	$impulseDrive = new TrekImpulseDrive(3,20,0,1,3); //Impulse Drive is an engine in its own right, in addition to serving as hub for Nacelle output: $armour, $maxhealth, $powerReq, $output, $boostEfficiency

/*
		$polarizedhullplating = new AbsorbtionShield(2,4,3,1,270,90);  //$armour, $maxhealth, $powerReq, $shieldFactor, $startArc, $endArc
			$polarizedhullplating->displayName = "Polarized Hull Plating";
			$this->addFrontSystem($polarizedhullplating);
			*/
		$projection = new TrekShieldProjection(1, 12, 3, 270, 90, 'F');//parameters: $armor, $maxhealth, $rating, $arc from/to - F/A/L/R suggests whether to use left or right graphics
			$projection->displayName = "Polarized Hull Plating";
		$this->addFrontSystem($projection);
		
		$this->addFrontSystem(new TrekPhaseCannon(3, 6, 4, 240, 60));
		$this->addFrontSystem(new TrekPhaseCannon(3, 6, 4, 270, 90));
		$this->addFrontSystem(new TrekPhaseCannon(3, 6, 4, 300, 120));
		$this->addFrontSystem(new TrekPhotonicTorp(2, 6, 1, 270, 90));
		$this->addFrontSystem(new TrekPhotonicTorp(2, 6, 1, 270, 90));
	    
		$warpNacelle = new TrekWarpDrive(3, 18, 0, 4); //armor, structure, power usage, impulse output
		$impulseDrive->addThruster($warpNacelle);
		$this->addAftSystem($warpNacelle);
		$warpNacelle = new TrekWarpDrive(3, 18, 0, 4); //armor, structure, power usage, impulse output
		$impulseDrive->addThruster($warpNacelle);
		$this->addAftSystem($warpNacelle);

		$this->addAftSystem(new TrekPhaseCannon(2, 6, 4, 90, 270));
		$this->addAftSystem(new TrekPhotonicTorp(2, 6, 1, 120, 240));
		/*
		$polarizedhullplating = new AbsorbtionShield(2,4,3,1,90,270);  //$armour, $maxhealth, $powerReq, $shieldFactor, $startArc, $endArc
			$polarizedhullplating->displayName = "Polarized Hull Plating";
			$this->addAftSystem($polarizedhullplating);*/
			
		$projection = new TrekShieldProjection(1, 12, 3, 90, 270, 'A');//parameters: $armor, $maxhealth, $rating, $arc from/to - F/A/L/R suggests whether to use left or right graphics
			$projection->displayName = "Polarized Hull Plating";
		$this->addAftSystem($projection);
		
		//technical thrusters - unlimited, like for LCVs		
		$this->addPrimarySystem(new InvulnerableThruster(99, 1, 0, 99, 3)); //unhitable and with unlimited thrust allowance
		$this->addPrimarySystem(new InvulnerableThruster(99, 1, 0, 99, 1)); //unhitable and with unlimited thrust allowance
		$this->addPrimarySystem(new InvulnerableThruster(99, 1, 0, 99, 2)); //unhitable and with unlimited thrust allowance
		$this->addPrimarySystem(new InvulnerableThruster(99, 1, 0, 99, 4)); //unhitable and with unlimited thrust allowance   
        $this->addPrimarySystem($impulseDrive);

        $this->addPrimarySystem(new Structure(3, 60));

	//d20 hit chart
	$this->hitChart = array(
		0=> array(
			2 => "2:Nacelle",
			7 => "Structure",
			9 => "Magnetic Grappler",
			12 => "Hangar",			
			14 => "Scanner",
			16 => "Engine",
			18 => "Reactor",
			20 => "C&C",
		),

		1=> array(
			4 => "Phase Cannon",
			7 => "Photonic Torpedo",
			17 => "Structure",
			20 => "Primary",
		),

		2=> array(
		    5 => "Nacelle",
			7 => "Phase Cannon",
			9 => "Photonic Torpedo",
			17 => "Structure",
			20 => "Primary",
		),

	);

        
        }
    }
?>

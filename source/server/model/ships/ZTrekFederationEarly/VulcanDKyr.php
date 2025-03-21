<?php
class VulcanDkyr extends BaseShip{
	
    function __construct($id, $userid, $name,  $slot){
        parent::__construct($id, $userid, $name,  $slot);
        
	$this->pointCost = 625;
	$this->faction = "ZStarTrek (early) Federation";
        $this->phpclass = "VulcanDkyr";
        $this->imagePath = "img/ships/StarTrek/VulcanDKyr.png";
        $this->shipClass = "Vulcan D'Kyr Heavy Cruiser";

	$this->unofficial = true;
	    $this->isd = '2140';


	$this->fighters = array("Shuttlecraft"=>4);
		$this->customFighter = array("Vulcan small craft"=>4); //can deploy small craft with Vulcan crew
        
        $this->forwardDefense = 14;
        $this->sideDefense = 16;
 
        $this->gravitic = true;       
        $this->turncost = 1;
        $this->turndelaycost = 0.5;
        $this->accelcost = 3;
        $this->rollcost = 2;
        $this->pivotcost = 4;
	$this->iniativebonus = 0; 
		
	$this->addPrimarySystem(new CnC(4, 16, 0, 0));
        $this->addPrimarySystem(new Reactor(4, 20, 0, 0));
        $this->addPrimarySystem(new Scanner(4, 12, 6, 6));
	$this->addPrimarySystem(new Hangar(3, 4, 4));
	$this->addPrimarySystem(new SWTractorBeam(2,0,360,1)); 

	$impulseDrive = new TrekImpulseDrive(4,20,0,1,3); //Impulse Drive is an engine in its own right, in addition to serving as hub for Nacelle output: $armour, $maxhealth, $powerReq, $output, $boostEfficiency
		
  
	$projection = new TrekShieldProjection(2, 20, 6, 300, 60, 'F');//parameters: $armor, $maxhealth, $rating, $arc from/to - F/A/L/R suggests whether to use left or right graphics
		$projector = new TrekShieldProjector(1, 6, 2, 2, 300, 60, 'F'); //parameters: $armor, $maxhealth, $power used, $rating, $arc from/to - F/A/L/R suggests whether to use left or right graphics
		$projection->addProjector($projector);
		$this->addFrontSystem($projector);
		$projector = new TrekShieldProjector(1, 6, 2, 2, 300, 60, 'F'); //parameters: $armor, $maxhealth, $power used, $rating, $arc from/to - F/A/L/R suggests whether to use left or right graphics
		$projection->addProjector($projector);
		$this->addFrontSystem($projector);
	$this->addFrontSystem($projection);
	$this->addFrontSystem(new TrekHvyPhaseCannon(3, 8, 6, 270, 30));
	$this->addFrontSystem(new TrekHvyPhaseCannon(3, 8, 6, 330, 90));
	$this->addFrontSystem(new TrekPhaseCannon(3, 6, 4, 240, 0));
	$this->addFrontSystem(new TrekPhaseCannon(3, 6, 4, 0, 120));

	$projection = new TrekShieldProjection(2, 20, 6, 210, 330, 'L');//parameters: $armor, $maxhealth, $rating, $arc from/to - F/A/L/R suggests whether to use left or right graphics
		$projector = new TrekShieldProjector(1, 6, 2, 2, 210, 330, 'L'); //parameters: $armor, $maxhealth, $power used, $rating, $arc from/to - F/A/L/R suggests whether to use left or right graphics
		$projection->addProjector($projector);
		$this->addLeftSystem($projector);
		$projector = new TrekShieldProjector(1, 6, 2, 2, 210, 330, 'L'); //parameters: $armor, $maxhealth, $power used, $rating, $arc from/to - F/A/L/R suggests whether to use left or right graphics
		$projection->addProjector($projector);
		$this->addLeftSystem($projector);
	$this->addLeftSystem($projection);
	$this->addLeftSystem(new TrekPhotonicTorp(2, 6, 1, 240, 0));
	$this->addLeftSystem(new TrekPhotonicTorp(2, 6, 1, 240, 0));
	$this->addLeftSystem(new TrekLtPhaseCannon(2, 4, 2, 180, 60));

	$projection = new TrekShieldProjection(2, 20, 6, 30, 150, 'R');//parameters: $armor, $maxhealth, $rating, $arc from/to - F/A/L/R suggests whether to use left or right graphics
		$projector = new TrekShieldProjector(1, 6, 2, 2, 30, 150, 'R'); //parameters: $armor, $maxhealth, $power used, $rating, $arc from/to - F/A/L/R suggests whether to use left or right graphics
		$projection->addProjector($projector);
		$this->addRightSystem($projector);
		$projector = new TrekShieldProjector(1, 6, 2, 2, 30, 150, 'R'); //parameters: $armor, $maxhealth, $power used, $rating, $arc from/to - F/A/L/R suggests whether to use left or right graphics
		$projection->addProjector($projector);
		$this->addRightSystem($projector);
	$this->addRightSystem($projection);
	$this->addRightSystem(new TrekPhotonicTorp(2, 6, 1, 0, 120));
	$this->addRightSystem(new TrekPhotonicTorp(2, 6, 1, 0, 120));
	$this->addRightSystem(new TrekLtPhaseCannon(2, 4, 2, 300, 180));

	$warpNacelle = new TrekWarpDrive(3, 12, 0, 2); //armor, structure, power usage, impulse output
	$impulseDrive->addThruster($warpNacelle);
	$this->addAftSystem($warpNacelle);
	$warpNacelle = new TrekWarpDrive(3, 12, 0, 2); //armor, structure, power usage, impulse output
	$impulseDrive->addThruster($warpNacelle);
	$this->addAftSystem($warpNacelle);
	$warpNacelle = new TrekWarpDrive(3, 12, 0, 2); //armor, structure, power usage, impulse output
	$impulseDrive->addThruster($warpNacelle);
	$this->addAftSystem($warpNacelle);
	$warpNacelle = new TrekWarpDrive(3, 12, 0, 2); //armor, structure, power usage, impulse output
	$impulseDrive->addThruster($warpNacelle);
	$this->addAftSystem($warpNacelle);		

		
	$projection = new TrekShieldProjection(2, 20, 6, 120, 240, 'A');//parameters: $armor, $maxhealth, $rating, $arc from/to - F/A/L/R suggests whether to use left or right graphics
		$projector = new TrekShieldProjector(1, 6, 2, 2, 120, 240, 'A'); //parameters: $armor, $maxhealth, $power used, $rating, $arc from/to - F/A/L/R suggests whether to use left or right graphics
		$projection->addProjector($projector);
		$this->addAftSystem($projector);
		$projector = new TrekShieldProjector(1, 6, 2, 2, 120, 240, 'A'); //parameters: $armor, $maxhealth, $power used, $rating, $arc from/to - F/A/L/R suggests whether to use left or right graphics
		$projection->addProjector($projector);
		$this->addAftSystem($projector);
	$this->addAftSystem($projection);
	$this->addAftSystem(new TrekLtPhaseCannon(2, 4, 2, 90, 270));
	$this->addAftSystem(new TrekLtPhaseCannon(2, 4, 2, 90, 270));

		
	//technical thrusters - unlimited, like for LCVs		
	$this->addPrimarySystem(new InvulnerableThruster(99, 99, 0, 99, 3)); //unhitable and with unlimited thrust allowance
	$this->addPrimarySystem(new InvulnerableThruster(99, 99, 0, 99, 1)); //unhitable and with unlimited thrust allowance
	$this->addPrimarySystem(new InvulnerableThruster(99, 99, 0, 99, 2)); //unhitable and with unlimited thrust allowance
	$this->addPrimarySystem(new InvulnerableThruster(99, 99, 0, 99, 4)); //unhitable and with unlimited thrust allowance  
        $this->addPrimarySystem($impulseDrive);
        
        //0:primary, 1:front, 2:rear, 3:left, 4:right;
        $this->addFrontSystem(new Structure( 3, 36));
        $this->addAftSystem(new Structure( 3, 32));
        $this->addLeftSystem(new Structure( 3, 40));
        $this->addRightSystem(new Structure( 3, 40));
        $this->addPrimarySystem(new Structure( 4, 36));

	    
        $this->hitChart = array(
            0=> array(
				2 => "2:Nacelle",
				9 => "Structure",
				11 => "Tractor Beam",
				13 => "Hangar",
				15 => "Scanner",
				17 => "Engine",
				19 => "Reactor",
				20 => "C&C",
            ),
            1=> array(
				2 => "Shield Projector",
				6 => "Heavy Phase Cannon",
				9 => "Phase Cannon",
				18 => "Structure",
				20 => "Primary",
            ),
            2=> array(
				2 => "Shield Projector",
				8 => "Nacelle",
				10 => "Light Phase Cannon",
				18 => "Structure",
				20 => "Primary",
            ),
            3=> array(
				2 => "Shield Projector",
				6 => "Photonic Torpedo",
				8 => "Light Phase Cannon",
				18 => "Structure",
				20 => "Primary",
            ),
            4=> array(
				2 => "Shield Projector",
				6 => "Photonic Torpedo",
				8 => "Light Phase Cannon",
				18 => "Structure",
				20 => "Primary",
            ),
       );
	    
	    
    }
}
?>

<?php
class Optine extends BaseShip{
    
    function __construct($id, $userid, $name,  $slot){
        parent::__construct($id, $userid, $name,  $slot);
        
		$this->pointCost = 665;
        $this->faction = "Centauri Republic (WotCR)";
        $this->phpclass = "Optine";
        $this->imagePath = "img/ships/optine.png";
        $this->shipClass = "Optine Battlecruiser";
        $this->shipSizeClass = 3;
	    $this->isd = 1840;

        $this->forwardDefense = 16;
        $this->sideDefense = 18;
        
        $this->turncost = 1;
        $this->turndelaycost = 1;
        $this->accelcost = 4;
        $this->rollcost = 3;
        $this->pivotcost = 4;
       
        $this->addPrimarySystem(new Reactor(6, 25, 0, 0));
        $this->addPrimarySystem(new CnC(6, 16, 0, 0));
        $this->addPrimarySystem(new Scanner(6, 23, 4, 7));
        $this->addPrimarySystem(new Engine(5, 20, 0, 10, 3));
		$this->addPrimarySystem(new Hangar(4, 2));
        
	$this->addFrontSystem(new Thruster(3, 10, 0, 4, 1));
        $this->addFrontSystem(new Thruster(3, 10, 0, 4, 1));
        $this->addFrontSystem(new TacLaser(3, 5, 4, 240, 360));
        $this->addFrontSystem(new ImperialLaser(3, 8, 5, 300, 60));
        $this->addFrontSystem(new ImperialLaser(3, 8, 5, 300, 60));
        $this->addFrontSystem(new TacLaser(3, 5, 4, 0, 120));
        $this->addFrontSystem(new ParticleProjector(3, 6, 1, 270, 90));
		
        $this->addAftSystem(new Thruster(2, 10, 0, 3, 2));
        $this->addAftSystem(new Thruster(3, 12, 0, 4, 2));
        $this->addAftSystem(new Thruster(2, 10, 0, 3, 2));
	$this->addAftSystem(new JumpEngine(4, 25, 3, 20));
	$this->addAftSystem(new TacLaser(3, 5, 4, 180, 300));
	$this->addAftSystem(new TacLaser(3, 5, 4, 60, 180));
	$this->addAftSystem(new ParticleProjector(3, 6, 1, 120, 300));
	$this->addAftSystem(new ParticleProjector(3, 6, 1, 60, 240));
		
	$this->addLeftSystem(new Thruster(4, 14, 0, 5, 3));
        $this->addLeftSystem(new ImperialLaser(3, 8, 5, 300, 360));
	$this->addLeftSystem(new ParticleProjector(3, 6, 1, 240, 360));
	$this->addLeftSystem(new ParticleProjector(3, 6, 1, 180, 300));

	$this->addRightSystem(new Thruster(4, 14, 0, 5, 4));
        $this->addRightSystem(new ImperialLaser(3, 8, 5, 0, 60));
	$this->addRightSystem(new ParticleProjector(3, 2, 1, 0, 120));
	$this->addRightSystem(new ParticleProjector(3, 2, 1, 60, 180));
		
        //0:primary, 1:front, 2:rear, 3:left, 4:right;
        $this->addFrontSystem(new Structure( 5, 38));
        $this->addAftSystem(new Structure( 4, 35));
        $this->addLeftSystem(new Structure( 4, 48));
        $this->addRightSystem(new Structure( 4, 48));
        $this->addPrimarySystem(new Structure( 6, 55));
	    
	//d20 hit chart
	$this->hitChart = array(
		
		0=> array(
			10 => "Structure",
			13 => "Scanner",
			16 => "Engine",
			17 => "Hangar",
			19 => "Reactor",
			20 => "C&C",
		),
		1=> array(
			5 => "Thruster",
			8 => "Imperial Laser",
			10 => "Tactical Laser",
			11 => "Particle Projector",
			18 => "Structure",
			20 => "Primary",
		),
		2=> array(
			5 => "Thruster",
			8 => "Jump Engine",
			10 => "Tactical Laser",
			12 => "Particle Projector",
			18 => "Structure",
			20 => "Primary",
		),
		3=> array(
			4 => "Thruster",
			6 => "Imperial Laser",
			8 => "Particle Projector",
			18 => "Structure",
			20 => "Primary",
		),
		4=> array(
			4 => "Thruster",
			6 => "Imperial Laser",
			8 => "Particle Projector",
			18 => "Structure",
			20 => "Primary",
		),
	);
	    
	    
    }
}
?>

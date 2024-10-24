<?php
class PeregrineCmd extends BaseShipNoAft{
    
    function __construct($id, $userid, $name,  $slot){
        parent::__construct($id, $userid, $name,  $slot);
        
    	$this->pointCost = 625;
        $this->faction = "Drazi Freehold (WotCR)";
        $this->phpclass = "peregrinecmd";
        $this->imagePath = "img/ships/vulture.png";
        $this->shipClass = "Peregrine Command Ship";
        $this->fighters = array("light" => 12);
        $this->limited = 33;
        $this->occurence = "rare";
        $this->variantOf = 'Peregrine Jump Ship';
		$this->isd = 2065;
        $this->canvasSize = 180;

	    $this->unofficial = true;
		$this->notes = 'ALTERNATE UNIVERSE - unit designed for "In ancient times" campaign';
        
        $this->forwardDefense = 15;
        $this->sideDefense = 15;
        
        $this->turncost = 0.66;
        $this->turndelaycost = 0.66;
        $this->accelcost = 3;
        $this->rollcost = 3;
        $this->pivotcost = 3;
        $this->iniativebonus = 10;
        
        $this->addPrimarySystem(new Reactor(5, 20, 0, 0));
        $this->addPrimarySystem(new CnC(6, 20, 0, 0));
        $this->addPrimarySystem(new Scanner(4, 16, 5, 8));
        $this->addPrimarySystem(new Engine(4, 15, 0, 9, 3));
        $this->addPrimarySystem(new Hangar(3, 15));
        $this->addAftSystem(new JumpEngine(4, 12, 3, 38));
        $this->addAftSystem(new Thruster(4, 12, 0, 5, 2));
		$this->addAftSystem(new Thruster(4, 12, 0, 5, 2));
  
        $this->addFrontSystem(new SolarCannon(4, 7, 3, 300, 60));
        $this->addFrontSystem(new StdParticleBeam(3, 4, 1, 240, 120));
        $this->addFrontSystem(new SolarCannon(4, 7, 3, 300, 60));
        $this->addFrontSystem(new Thruster(3, 8, 0, 3, 1));
        $this->addFrontSystem(new Thruster(3, 8, 0, 3, 1));
        
        $this->addLeftSystem(new StdParticleBeam(3, 4, 1, 180, 0));
		$this->addLeftSystem(new StdParticleBeam(3, 4, 1, 180, 0));
        $this->addLeftSystem(new ParticleCannon(3, 8, 7, 240, 60));
        $this->addLeftSystem(new Thruster(4, 16, 0, 5, 3));
        
        $this->addRightSystem(new StdParticleBeam(3, 4, 1, 0, 180));
	$this->addRightSystem(new StdParticleBeam(3, 4, 1, 0, 180));
        $this->addRightSystem(new ParticleCannon(3, 8, 7, 300, 120));
        $this->addRightSystem(new Thruster(4, 16, 0, 5, 4));
        
        //0:primary, 1:front, 2:rear, 3:left, 4:right;
        $this->addPrimarySystem(new Structure(5, 50));
        $this->addLeftSystem(new Structure(4, 44));
        $this->addRightSystem(new Structure(4, 44));
        $this->addFrontSystem(new Structure(4, 45));
    
    
      	$this->hitChart = array(
		0=> array(
				8=> "Structure",
				10=> "2:Thruster",
				12=> "Scanner",
				14=> "2:Jump Engine",
				16=> "Engine",
				18=> "Hangar",
				19=> "Reactor",
				20=> "C&C",
		),
		1=> array(
				5=> "Thruster",
				8=> "Solar Cannon",
				9=> "Standard Particle Beam",
				18=> "Structure",
				20=> "Primary",
		),
		3=> array(
				5=> "Thruster",
				7=> "Standard Particle Beam",
				9=> "Particle Cannon",
				18=> "Structure",
				20=> "Primary",
		),
		4=> array(
				5=> "Thruster",
				7=> "Standard Particle Beam",
				9=> "Particle Cannon",
				18=> "Structure",
				20=> "Primary",
		),
        );
    }
}
?>

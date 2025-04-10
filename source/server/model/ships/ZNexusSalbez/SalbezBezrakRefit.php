<?php
class SalbezBezrakRefit extends SmallStarBaseFourSections{

	function __construct($id, $userid, $name,  $slot){
		parent::__construct($id, $userid, $name,  $slot);

		$this->pointCost = 850;
		$this->base = true;
		$this->smallBase = true;
		$this->faction = "ZNexus Sal-bez Coalition";
		$this->phpclass = "SalbezBezrakRefit";
		$this->shipClass = "Bez'rak Combat Station";
		$this->imagePath = "img/ships/Nexus/salbez_combatBase3.png";
		$this->canvasSize = 140; 
		$this->unofficial = true;
		$this->isd = 2127;

		$this->shipSizeClass = 3; 
		$this->Enormous = false;
		$this->iniativebonus = -200; //no voluntary movement anyway
		$this->turncost = 0;
		$this->turndelaycost = 0;

		$this->fighters = array("normal"=>12);

		$this->forwardDefense = 17;
		$this->sideDefense = 17;

		$this->addPrimarySystem(new Reactor(4, 28, 0, 0));
		$this->addPrimarySystem(new CnC(4, 24, 0, 0));
		$this->addPrimarySystem(new Scanner(4, 18, 7, 7));
		$this->addPrimarySystem(new Hangar(4, 6));
		$this->addPrimarySystem(new NexusImprovedParticleBeam(4, 3, 1, 0, 360));
		$this->addPrimarySystem(new NexusImprovedParticleBeam(4, 3, 1, 0, 360));
		
		$this->addFrontSystem(new MediumLaser(4, 6, 5, 270, 90));
		$this->addFrontSystem(new MediumLaser(4, 6, 5, 270, 90));
		$this->addFrontSystem(new NexusImprovedParticleBeam(4, 3, 1, 270, 90));
		$this->addFrontSystem(new NexusImprovedParticleBeam(4, 3, 1, 270, 90));
		$this->addFrontSystem(new NexusRangedSwarmTorpedo(4, 5, 2, 270, 90));
		$this->addFrontSystem(new Hangar(4, 3));
		$this->addFrontSystem(new CargoBay(4, 10));

		$this->addAftSystem(new MediumLaser(4, 6, 5, 90, 270));
		$this->addAftSystem(new MediumLaser(4, 6, 5, 90, 270));
		$this->addAftSystem(new NexusImprovedParticleBeam(4, 3, 1, 90, 270));
		$this->addAftSystem(new NexusImprovedParticleBeam(4, 3, 1, 90, 270));
		$this->addAftSystem(new NexusRangedSwarmTorpedo(4, 5, 2, 90, 270));
		$this->addAftSystem(new Hangar(4, 3));
		$this->addAftSystem(new CargoBay(4, 10));
			
		$this->addLeftSystem(new MediumLaser(4, 6, 5, 180, 360));
		$this->addLeftSystem(new MediumLaser(4, 6, 5, 180, 360));
		$this->addLeftSystem(new NexusImprovedParticleBeam(4, 3, 1, 180, 360));
		$this->addLeftSystem(new NexusImprovedParticleBeam(4, 3, 1, 180, 360));
		$this->addLeftSystem(new NexusRangedSwarmTorpedo(4, 5, 2, 180, 360));
		$this->addLeftSystem(new Hangar(4, 3));
		$this->addLeftSystem(new CargoBay(4, 10));

		$this->addRightSystem(new MediumLaser(4, 6, 5, 0, 180));
		$this->addRightSystem(new MediumLaser(4, 6, 5, 0, 180));
		$this->addRightSystem(new NexusImprovedParticleBeam(4, 3, 1, 0, 180));
		$this->addRightSystem(new NexusImprovedParticleBeam(4, 3, 1, 0, 180));
		$this->addRightSystem(new NexusRangedSwarmTorpedo(4, 5, 2, 0, 180));
		$this->addRightSystem(new Hangar(4, 3));
		$this->addRightSystem(new CargoBay(4, 10));

		$this->addFrontSystem(new Structure( 4, 60));
		$this->addAftSystem(new Structure( 4, 60));
		$this->addLeftSystem(new Structure( 4, 60));
		$this->addRightSystem(new Structure( 4, 60));
		$this->addPrimarySystem(new Structure( 4, 98));
		
		$this->hitChart = array(			
			0=> array(
				10 => "Structure",
				12 => "Improved Particle Beam",
				14 => "Hangar",
				16 => "Scanner",
				18 => "Reactor",
				20 => "C&C",
			),
			1=> array(
				3 => "Medium Laser",
				5 => "Improved Particle Beam",
				7 => "Ranged Swarm Torpedo",
				10 => "Cargo Bay",
				12 => "Hangar",
				18 => "Structure",
				20 => "Primary",
			),
			2=> array(
				3 => "Medium Laser",
				5 => "Improved Particle Beam",
				7 => "Ranged Swarm Torpedo",
				10 => "Cargo Bay",
				12 => "Hangar",
				18 => "Structure",
				20 => "Primary",
			),	
			3=> array(
				3 => "Medium Laser",
				5 => "Improved Particle Beam",
				7 => "Ranged Swarm Torpedo",
				10 => "Cargo Bay",
				12 => "Hangar",
				18 => "Structure",
				20 => "Primary",
			),
			4=> array(
				3 => "Medium Laser",
				5 => "Improved Particle Beam",
				7 => "Ranged Swarm Torpedo",
				10 => "Cargo Bay",
				12 => "Hangar",
				18 => "Structure",
				20 => "Primary",
			),
		);

    	}
   	}
?>
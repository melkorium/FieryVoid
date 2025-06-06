<?php
class Axor extends MediumShip{
    
    function __construct($id, $userid, $name,  $slot){
        parent::__construct($id, $userid, $name,  $slot);
        
		$this->pointCost = 250;
		$this->faction = "Kor-Lyan Kingdoms";
        $this->phpclass = "Axor";
        $this->imagePath = "img/ships/korlyan_axor2.png";
        $this->shipClass = "Axor Assault Frigate";
        $this->canvasSize = 70;
		$this->fighters = array("assault shuttles"=>4);
	    
	    $this->isd = 2223;

	    $this->notes = 'Atmospheric Capable.';
        
        $this->forwardDefense = 10;
        $this->sideDefense = 12;
        
        $this->turncost = 0.33;
        $this->turndelaycost = 0.33;
        $this->accelcost = 1;
        $this->rollcost = 1;
        $this->pivotcost = 1;
		$this->iniativebonus = 60;

		//ammo magazine itself (AND its options)
		$ammoMagazine = new AmmoMagazine(40); //pass magazine capacity 
	    $this->addPrimarySystem($ammoMagazine); //fit to ship immediately
	    $ammoMagazine->addAmmoEntry(new AmmoMissileI(), 40); //add full load of basic missiles	    
	       
	    $this->enhancementOptionsEnabled[] = 'AMMO_C';//add enhancement options for other missiles - Class-C
	    $this->enhancementOptionsEnabled[] = 'AMMO_A';//add enhancement options for other missiles - Class-A
	    
   		$this->enhancementOptionsEnabled[] = 'ELT_MRN'; //To enable Elite Marines enhancement
		$this->enhancementOptionsEnabled[] = 'EXT_MRN'; //To enable extra Marines enhancement	    	    	    

         
        $this->addPrimarySystem(new Reactor(3, 8, 0, 0));
        $this->addPrimarySystem(new CnC(3, 6, 0, 0));
        $this->addPrimarySystem(new Scanner(3, 8, 3, 4));
        $this->addPrimarySystem(new Engine(3, 9, 0, 4, 1));
		$this->addPrimarySystem(new Hangar(3, 4));
		$this->addPrimarySystem(new Thruster(3, 10, 0, 3, 3));
		$this->addPrimarySystem(new Thruster(3, 10, 0, 3, 4));
		
        $this->addFrontSystem(new Thruster(3, 10, 0, 4, 1));
        $this->addFrontSystem(new StdParticleBeam(1, 4, 1, 240, 60));
		$this->addFrontSystem(new GrapplingClaw(3, 0, 0, 300, 60, 8, false));
        $this->addFrontSystem(new AmmoMissileRackD(2, 0, 0, 270, 90, $ammoMagazine, false));
		$this->addFrontSystem(new GrapplingClaw(3, 0, 0, 300, 60, 8, false));
        $this->addFrontSystem(new StdParticleBeam(1, 4, 1, 300, 120));
		
        $this->addAftSystem(new Thruster(3, 6, 0, 2, 2));
        $this->addAftSystem(new AmmoMissileRackD(2, 0, 0, 90, 270, $ammoMagazine, false));
        $this->addAftSystem(new Thruster(3, 6, 0, 2, 2));
	
        $this->addPrimarySystem(new Structure( 3, 64));
        
		$this->hitChart = array(
                0=> array(
                        8 => "Thruster",
                        11 => "Scanner",
                        14 => "Engine",
						15 => "Hangar",
                        18 => "Reactor",
                        20 => "C&C",
                ),
                1=> array(
                        5 => "Thruster",
						7 => "Standard Particle Beam",
                        9 => "Class-D Missile Rack",
						11 => "Grappling Claw",
                        17 => "Structure",
                        20 => "Primary",
                ),
                2=> array(
                        6 => "Thruster",
                        8 => "Class-D Missile Rack",
                        17 => "Structure",
                        20 => "Primary",
                ),
        );

    }

}



?>

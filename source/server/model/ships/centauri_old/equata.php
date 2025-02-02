<?php
class Equata extends HeavyCombatVessel{
    
    function __construct($id, $userid, $name,  $slot){
        parent::__construct($id, $userid, $name,  $slot);
        
        $this->pointCost = 445;
        $this->faction = "Centauri Republic (WotCR)";
        $this->phpclass = "Equata";
        $this->imagePath = "img/ships/strela.png";
        $this->shipClass = "Equata Intruder";
        $this->fighters = array("heavy"=>6);
        
        $this->variantOf = "Strela Light Jump Ship";
	    $this->isd = 2008;
        $this->occurence = "rare";
        $this->limited = 10;
		
		
        
        $this->forwardDefense = 14;
        $this->sideDefense = 15;
        
        $this->turncost = 0.66;
        $this->turndelaycost = 0.50;
        $this->accelcost = 3;
        $this->rollcost = 2;
        $this->pivotcost = 3;
        $this->iniativebonus = 30;  
         
        $this->addPrimarySystem(new Reactor(6, 17, 0, 0));
        $this->addPrimarySystem(new CnC(6, 12, 0, 0));
        $this->addPrimarySystem(new ElintScanner(5, 20, 5, 7));
        $this->addPrimarySystem(new Engine(5, 15, 0, 8, 3));
        $this->addPrimarySystem(new Hangar(5, 7));
        $this->addPrimarySystem(new Thruster(3, 10, 0, 4, 3));
        $this->addPrimarySystem(new Thruster(3, 10, 0, 4, 4));

        $this->addFrontSystem(new Thruster(4, 8, 0, 3, 1));
        $this->addFrontSystem(new Thruster(4, 8, 0, 3, 1));
        $this->addFrontSystem(new LightParticleBeamShip(2, 2, 1, 240, 60));
        $this->addFrontSystem(new AssaultLaser(4, 6, 4, 300, 60));
        $this->addFrontSystem(new LightParticleBeamShip(2, 2, 1, 300, 120));
        
        $this->addAftSystem(new Thruster(4, 14, 0, 4, 2));
        $this->addAftSystem(new Thruster(4, 14, 0, 4, 2));
        $this->addAftSystem(new JumpEngine(3, 15, 3, 25));
        $this->addAftSystem(new LightParticleBeamShip(2, 2, 1, 180, 360));
        $this->addAftSystem(new LightParticleBeamShip(2, 2, 1, 180, 360));
        $this->addAftSystem(new LightParticleBeamShip(2, 2, 1, 0, 180));
        $this->addAftSystem(new LightParticleBeamShip(2, 2, 1, 0, 180));
        
        //0:primary, 1:front, 2:rear, 3:left, 4:right;
        $this->addFrontSystem(new Structure( 4, 50));
        $this->addAftSystem(new Structure( 4, 50));
        $this->addPrimarySystem(new Structure( 5, 40 ));
        
		
		

        $this->hitChart = array(
            0=> array(
                    6 => "Structure",
                    9 => "Thruster",
                    12 => "ELINT Scanner",
                    15 => "Engine",
                    17 => "Hangar",
                    19 => "Reactor",
                    20 => "C&C",
            ),
            1=> array(
                    5 => "Thruster",
                    7 => "Assault Laser",
                    9 => "Light Particle Beam",
                    18 => "Structure",
                    20 => "Primary",
            ),
            2=> array(
                    5 => "Thruster",
                    7 => "Light Particle Beam",
					9 => "Jump Engine",
                    18 => "Structure",
                    20 => "Primary",
            ),
        );
        
    }

}



?>

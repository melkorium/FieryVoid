<?php

class Deathfalcon2230 extends BaseShipNoAft{

    function __construct($id, $userid, $name,  $slot){
        parent::__construct($id, $userid, $name,  $slot);

        $this->pointCost = 725;
        $this->faction = "Drazi Freehold";
        $this->phpclass = "Deathfalcon2230";
        $this->imagePath = "img/ships/drazi/DraziStormfalcon.png";
        $this->shipClass = "Deathfalcon Heavy Assault Cruiser (2230)";
        $this->fighters = array("assault shuttles"=>12);
        $this->occurence = "rare";
        $this->variantOf = 'Stormfalcon Heavy Cruiser';
        $this->isd = 2234;
        $this->limited = 33;
        $this->canvasSize = 200;
 		$this->unofficial = 'S'; //design released after AoG demise       

        $this->forwardDefense = 15;
        $this->sideDefense = 14;

        $this->turncost = 0.5;
        $this->turndelaycost = 1;
        $this->accelcost = 3;
        $this->rollcost = 5;
        $this->pivotcost = 3;
        
        $this->iniativebonus = 10;

        $this->addPrimarySystem(new Reactor(5, 17, 0, 0));
        $this->addPrimarySystem(new CnC(5, 16, 0, 0));
        $this->addPrimarySystem(new Scanner(5, 14, 5, 7));
        $this->addPrimarySystem(new Engine(5, 15, 0, 8, 3));
        $this->addAftSystem(new JumpEngine(5, 10, 5, 36));
        $this->addPrimarySystem(new Hangar(4, 14, 12));
        $this->addPrimarySystem(new HvyParticleCannon(5, 12, 9, 330, 30));
        $this->addAftSystem(new Thruster(5, 21, 0, 8, 2));

        $this->addFrontSystem(new RepeaterGun(2, 6, 4, 240, 0));    
        $this->addFrontSystem(new RepeaterGun(2, 6, 4, 300, 60));     
        $this->addFrontSystem(new RepeaterGun(2, 6, 4, 0, 120));   
        $this->addFrontSystem(new StdParticleBeam(3, 4, 1, 300, 60));  
        $this->addFrontSystem(new StdParticleBeam(3, 4, 1, 300, 60));  
        $this->addFrontSystem(new Thruster(4, 13, 0, 4, 1));
        $this->addFrontSystem(new Thruster(4, 13, 0, 4, 1));

        $this->addLeftSystem(new StdParticleBeam(3, 4, 1, 240, 0));
        $this->addLeftSystem(new StdParticleBeam(3, 4, 1, 240, 0));
        $this->addLeftSystem(new TwinArray(3, 6, 2, 180, 0));
        $this->addLeftSystem(new Thruster(4, 15, 0, 5, 3));

        $this->addRightSystem(new StdParticleBeam(3, 4, 1, 0, 120));
        $this->addRightSystem(new StdParticleBeam(3, 4, 1, 0, 120));
        $this->addRightSystem(new TwinArray(3, 6, 2, 0, 180));
        $this->addRightSystem(new Thruster(4, 15, 0, 5, 4));

        //0:primary, 1:front, 2:rear, 3:left, 4:right;
        $this->addFrontSystem(new Structure( 4, 48));
        $this->addLeftSystem(new Structure( 4, 44));
        $this->addRightSystem(new Structure( 4, 44));
        $this->addPrimarySystem(new Structure( 5, 44));
        
        $this->hitChart = array(
        		0=> array(
        				8 => "Structure",
        				9 => "2:Jump Engine",
        				11 => "2:Thruster",
        				13 => "Scanner",
        				15 => "Engine",
        				17 => "Hangar",
        				18 => "Heavy Particle Cannon",
        				19 => "Reactor",
        				20 => "C&C",
        		),
        		1=> array(
        				4 => "Thruster",
        				6 => "0:Heavy Particle Cannon",
        				8 => "Repeater Gun",
        				9 => "Standard Particle Beam",
        				18 => "Structure",
        				20 => "Primary",
        		),
        		3=> array(
        				4 => "Thruster",
        				6 => "0:Heavy Particle Cannon",
        				8 => "Standard Particle Beam",
        				9 => "Twin Array",
        				18 => "Structure",
        				20 => "Primary",
        		),
        		4=> array(
        				4 => "Thruster",
        				6 => "0:Heavy Particle Cannon",
        				8 => "Standard Particle Beam",
        				9 => "Twin Array",
        				18 => "Structure",
        				20 => "Primary",
        		),
        );
    }
}
?>

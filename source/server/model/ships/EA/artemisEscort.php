<?php
class ArtemisEscort extends HeavyCombatVessel{
    
    function __construct($id, $userid, $name,  $slot){
        parent::__construct($id, $userid, $name,  $slot);
        
        $this->pointCost = 650;
        $this->faction = "Earth Alliance";
        $this->phpclass = "ArtemisEscort";
        $this->imagePath = "img/ships/artemis.png";
        $this->shipClass = "Artemis Escort Frigate (Zeta)";
        $this->occurence = "uncommon";
        $this->variantOf = "Artemis Heavy Frigate (Beta)";
        $this->isd = 2242;
        
        $this->forwardDefense = 14;
        $this->sideDefense = 15;
        
        $this->turncost = 0.66;
        $this->turndelaycost = 0.66;
        $this->accelcost = 2;
        $this->rollcost = 3;
        $this->pivotcost = 2;
        $this->iniativebonus = 30;
        
         
        $this->addPrimarySystem(new Reactor(6, 20, 0, 3));
        $this->addPrimarySystem(new CnC(6, 12, 0, 0));
        $this->addPrimarySystem(new Scanner(5, 14, 4, 6));
        $this->addPrimarySystem(new Engine(6, 13, 0, 8, 2));
        $this->addPrimarySystem(new Hangar(6, 2));
        $this->addPrimarySystem(new Thruster(5, 13, 0, 4, 3));
        $this->addPrimarySystem(new Thruster(5, 13, 0, 4, 4));       
        $this->addPrimarySystem(new MediumPulse(5, 6, 3, 240, 0));
        $this->addPrimarySystem(new MediumPulse(5, 6, 3, 0, 120));
     
        $this->addFrontSystem(new Thruster(5, 8, 0, 3, 1));
        $this->addFrontSystem(new Thruster(5, 8, 0, 3, 1));       
        $this->addFrontSystem(new MediumPulse(5, 6, 3, 240, 0));
        $this->addFrontSystem(new MediumPulse(5, 6, 3, 0, 120));
        $this->addFrontSystem(new InterceptorMkI(2, 4, 1, 270, 90));      
        
        $this->addAftSystem(new Thruster(5, 12, 0, 4, 2));
        $this->addAftSystem(new Thruster(5, 12, 0, 4, 2));
        $this->addAftSystem(new MediumPulse(5, 6, 3, 180, 300));
        $this->addAftSystem(new MediumPulse(5, 6, 3, 60, 180));
        $this->addAftSystem(new InterceptorMkI(2, 4, 1, 90, 270));        
        $this->addAftSystem(new StdParticleBeam(2, 4, 1, 180, 360));
        $this->addAftSystem(new StdParticleBeam(2, 4, 1, 180, 360));
        $this->addAftSystem(new StdParticleBeam(2, 4, 1, 0, 180));
        $this->addAftSystem(new StdParticleBeam(2, 4, 1, 0, 180));
        
        //0:primary, 1:front, 2:rear, 3:left, 4:right;
        $this->addFrontSystem(new Structure( 6, 40));
        $this->addAftSystem(new Structure( 6, 40));
        $this->addPrimarySystem(new Structure( 6, 45));
        
        $this->hitChart = array(
        		0=> array(
        				7=> "Structure",
        				9=> "Medium Pulse Cannon",
        				11=> "Thruster",
        				13=> "Scanner",
        				15=> "Engine",
        				16=> "Hangar",
        				19=> "Reactor",
        				20=> "C&C",
        		),
        		1=> array(
        				3=> "Thruster",
                        9=> "Medium Pulse Cannon",
        				8=> "Interceptor I",
        				18=> "Structure",
        				20=> "Primary",
        		),
        		2=> array(
        				5=> "Thruster",
        				8=> "Standard Particle Beam",
                        10=> "Medium Pulse Cannon",
                        12=> "Interceptor I",
        				18=> "Structure",
        				20=> "Primary",
        		),
        );
    }
}



?>


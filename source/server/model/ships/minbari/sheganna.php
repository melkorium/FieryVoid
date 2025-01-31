<?php
class Sheganna extends OSAT{
    
    function __construct($id, $userid, $name,  $slot){
        parent::__construct($id, $userid, $name,  $slot);
        
		$this->pointCost = 300;
		$this->faction = "Minbari Federation";
        $this->phpclass = "Sheganna";
        $this->imagePath = "img/ships/sheganna.png";
        $this->shipClass = 'Sheganna OSAT';        
        $this->forwardDefense = 9;
        $this->sideDefense = 9;
        $this->turncost = 0;
        $this->turndelaycost = 0;
        $this->accelcost = 0;
        $this->rollcost = 0;
        $this->pivotcost = 0;	
        $this->iniativebonus = 65; 
        $this->isd = 2070;

        $this->addPrimarySystem(new OSATCnC(0, 1, 0, 0));
        $this->addPrimarySystem(new Reactor(4, 9, 0, 0));
        $this->addPrimarySystem(new Scanner(4, 7, 2, 8)); 
        $this->addPrimarySystem(new Jammer(3, 6, 3));
        $this->addAftSystem(new Thruster(4, 7, 0, 0, 2));
        
        $this->addFrontSystem(new NeutronLaser(2, 5, 4, 270, 90));
        $this->addFrontSystem(new FusionCannon(3, 8, 4, 180, 360));
        $this->addFrontSystem(new FusionCannon(3, 8, 4, 270, 90));
        $this->addFrontSystem(new FusionCannon(3, 8, 4, 270, 90));
        $this->addFrontSystem(new FusionCannon(3, 8, 4, 0, 180));
        
        //0:primary, 1:front, 2:rear, 3:left, 4:right;
        
        $this->addPrimarySystem(new Structure(4, 35));
		
		$this->hitChart = array(
                0=> array(
                        9 => "Structure",
                        12 => "2:Thruster",
                        13 => "1:Neutron Laser",
                        15 => "1:Fusion Cannon",
                        17 => "Scanner",
                        19 => "Reactor",
                        20 => "Jammer",
                ),
        );
        
    }
}

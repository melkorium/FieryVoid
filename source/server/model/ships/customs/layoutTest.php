<?php
class VelraxSivrinGunship extends HeavyCombatVesselLeftRight{
    
    function __construct($id, $userid, $name,  $slot){
        parent::__construct($id, $userid, $name,  $slot);
        
	$this->pointCost = 365;
	$this->faction = "ZNexus Velrax";
        $this->phpclass = "VelraxSivrinGunship";
        $this->imagePath = "img/ships/Nexus/VelraxSivrin.png";
        $this->shipClass = "Sivrin Gunship";
	    $this->isd = 2023;
        $this->canvasSize = 95;

		$this->unofficial = true;

        $this->forwardDefense = 14;
        $this->sideDefense = 14;
        
        $this->turncost = 0.66;
        $this->turndelaycost = 0.66;
        $this->accelcost = 3;
        $this->rollcost = 2;
        $this->pivotcost = 2;
        $this->iniativebonus = 30;

        $this->addPrimarySystem(new Reactor(4, 20, 0, 0));
        $this->addPrimarySystem(new CnC(4, 12, 0, 0));
        $this->addPrimarySystem(new Scanner(4, 12, 6, 6));
        $this->addPrimarySystem(new Engine(4, 13, 0, 8, 4));
        $this->addPrimarySystem(new Hangar(1, 2));
		$this->addPrimarySystem(new MediumPlasma(3, 5, 3, 300, 60));
        $this->addPrimarySystem(new Thruster(3, 15, 0, 4, 1));
        $this->addPrimarySystem(new Thruster(4, 20, 0, 8, 2));

        $this->addLeftSystem(new NexusLaserSpear(3, 5, 3, 240, 360));
        $this->addLeftSystem(new NexusLaserSpear(3, 5, 3, 240, 360));
        $this->addLeftSystem(new NexusTwinIonGun(2, 4, 4, 180, 360));
		$this->addLeftSystem(new MediumPlasma(3, 5, 3, 180, 360));
        $this->addLeftSystem(new Thruster(3, 12, 0, 4, 3));

        $this->addRightSystem(new NexusLaserSpear(3, 5, 3, 0, 120));
        $this->addRightSystem(new NexusLaserSpear(3, 5, 3, 0, 120));
        $this->addRightSystem(new NexusTwinIonGun(2, 4, 4, 0, 180));
		$this->addRightSystem(new MediumPlasma(3, 5, 3, 0, 180));
        $this->addRightSystem(new Thruster(3, 12, 0, 4, 4));

        //0:primary, 1:front, 2:rear, 3:left, 4:right;
        $this->addPrimarySystem(new Structure(3, 50));
        $this->addLeftSystem(new Structure(3, 50));
        $this->addRightSystem(new Structure(3, 50));
    
            $this->hitChart = array(
        		0=> array(
        				6 => "Structure",
						7 => "Medium Plasma Cannon",
        				12 => "Thruster",
						13 => "Hangar",
        				15 => "Scanner",
        				17 => "Engine",
        				19 => "Reactor",
        				20 => "C&C",
        		),
        		3=> array(
        				5 => "Thruster",
        				7 => "Twin Ion Gun",
						10 => "Laser Spear",
						12 => "Medium Plasma Cannon",
        				18 => "Structure",
        				20 => "Primary",
        		),
        		4=> array(
        				5 => "Thruster",
        				7 => "Twin Ion Gun",
        				8 => "Heavy Laser Spear",
						12 => "Medium Plasma Cannon",
        				18 => "Structure",
        				20 => "Primary",
        		),
        );
    
    }
}
?>

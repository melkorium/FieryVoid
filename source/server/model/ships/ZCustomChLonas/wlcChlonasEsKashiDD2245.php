<?php
class wlcChlonasEsKashiDD2245 extends HeavyCombatVesselLeftRight{
    /*Ch'Lonas Es'Kashi destroyer, variant ISD 2245*/
    function __construct($id, $userid, $name,  $slot){
        parent::__construct($id, $userid, $name,  $slot);
        
        $this->pointCost = 450;
        $this->phpclass = "wlcChlonasEsKashiDD2245";
        $this->imagePath = "img/ships/ChlonasEsKashi.png";
        $this->canvasSize = 200;
        $this->shipClass = "Es'Kashi Destroyer (2245)";
        //$this->fighters = array("medium"=>6);
	    
	    
        $this->faction = "Ch'Lonas Cooperative";
        $this->variantOf = "Es'Kashi Destroyer";
	$this->isd = 2245;
	$this->unofficial = true;
        
        $this->forwardDefense = 13;
        $this->sideDefense = 14;
        
        $this->turncost = 0.66;
        $this->turndelaycost = 0.50;
        $this->accelcost = 2;
        $this->rollcost = 2;
        $this->pivotcost = 2;
        $this->iniativebonus = 35;
        
         
        $this->addPrimarySystem(new Reactor(5, 14, 0, 0));
        $this->addPrimarySystem(new CnC(5, 8, 0, 0));
        $this->addPrimarySystem(new Scanner(4, 13, 4, 6));
        $this->addPrimarySystem(new Engine(5, 11, 0, 8, 3));
        $this->addPrimarySystem(new Hangar(4, 2));
        $this->addAftSystem(new Thruster(4, 15, 0, 4, 1));
        $this->addAftSystem(new Thruster(4, 21, 0, 8, 2));
        $this->addFrontSystem(new customHeavyMatterCannon(4, 0, 0, 300, 60));
        
        $this->addLeftSystem(new Thruster(4, 15, 0, 4, 3));
        $this->addLeftSystem(new AssaultLaser(3, 6, 4, 240, 60));
        $this->addLeftSystem(new MatterCannon(3, 7, 4, 240, 360));
        $this->addLeftSystem(new LightParticleBeamShip(2, 2, 1, 240, 360));
        $this->addLeftSystem(new LightParticleBeamShip(2, 2, 1, 180, 300));

        $this->addRightSystem(new Thruster(4, 15, 0, 4, 4));
        $this->addRightSystem(new AssaultLaser(3, 6, 4, 300, 120));
        $this->addRightSystem(new MatterCannon(3, 7, 4, 0, 120));
        $this->addRightSystem(new LightParticleBeamShip(2, 2, 1, 0, 120));
        $this->addRightSystem(new LightParticleBeamShip(2, 2, 1, 60, 180));

        
        
        
        
        //0:primary, 1:front, 2:rear, 3:left, 4:right;
        $this->addLeftSystem(new Structure( 4, 44));
        $this->addRightSystem(new Structure( 4, 44));
        $this->addPrimarySystem(new Structure( 5, 36 ));
        
        
	//d20 hit chart
	$this->hitChart = array(
		
		0=> array(
			5 => "Structure",
			7 => "1:Heavy Matter Cannon",
			11 => "2:Thruster",
			13 => "Scanner",
			15 => "Engine",
			17 => "Hangar",
			19 => "Reactor",
			20 => "C&C",
		),
		3=> array(
			4 => "Thruster",
			7 => "Assault Laser",
			10 => "Matter Cannon",
			12 => "Light Particle Beam",
			18 => "Structure",
			20 => "Primary",
		),
		4=> array(
			4 => "Thruster",
			7 => "Assault Laser",
			10 => "Matter Cannon",
			12 => "Light Particle Beam",
			18 => "Structure",
			20 => "Primary",
		),
	);
    }
}
?>

<?php
class KastanIronbladeRefit2 extends BaseShip{
    
    function __construct($id, $userid, $name,  $slot){
        parent::__construct($id, $userid, $name,  $slot);
        
	$this->pointCost = 550;
	$this->faction = "ZEscalation Kastan Monarchy";
        $this->phpclass = "KastanIronbladeRefit2";
        $this->imagePath = "img/ships/EscalationWars/KastanIronblade.png";
        $this->shipClass = "Ironblade Heavy Cruiser (1934 refit)";
			$this->variantOf = "Ironblade Heavy Cruiser";
			$this->occurence = "common";
        $this->shipSizeClass = 3;
		$this->canvasSize = 175; //img has 200px per side
		$this->unofficial = true;

        $this->fighters = array("normal"=>6);

		$this->isd = 1934;
        
        $this->forwardDefense = 15;
        $this->sideDefense = 16;
        
        $this->turncost = 1.0;
        $this->turndelaycost = 1.0;
        $this->accelcost = 3;
        $this->rollcost = 2;
        $this->pivotcost = 3;
        $this->iniativebonus = 10;
        
        $this->addPrimarySystem(new Reactor(5, 20, 0, 0));
        $this->addPrimarySystem(new CnC(5, 16, 0, 0));
        $this->addPrimarySystem(new Scanner(4, 15, 3, 7));
        $this->addPrimarySystem(new Engine(4, 18, 0, 12, 3));
		$this->addPrimarySystem(new SoMissileRack(4, 6, 0, 0, 360));
		$this->addPrimarySystem(new Hangar(4, 8));
		$this->addPrimarySystem(new JumpEngine(4, 15, 4, 24));
		
        $this->addFrontSystem(new Thruster(3, 13, 0, 4, 1));
        $this->addFrontSystem(new Thruster(3, 13, 0, 4, 1));
		$this->addFrontSystem(new EWDualLaserBolt(2, 6, 4, 240, 60));
		$this->addFrontSystem(new EWSovereignLaser(4, 12, 6, 300, 60));
		$this->addFrontSystem(new EWDualLaserBolt(2, 6, 4, 300, 120));

        $this->addAftSystem(new Thruster(3, 15, 0, 6, 2));
        $this->addAftSystem(new Thruster(3, 15, 0, 6, 2));
        $this->addAftSystem(new EWDualLaserBolt(2, 6, 4, 90, 270));
        $this->addAftSystem(new EWRoyalLaser(2, 6, 4, 90, 270));
        $this->addAftSystem(new EWDualLaserBolt(2, 6, 4, 90, 270));

        $this->addLeftSystem(new ImperialLaser(4, 8, 5, 240, 360));
        $this->addLeftSystem(new EWRoyalLaser(2, 6, 4, 180, 360));
        $this->addLeftSystem(new EWRoyalLaser(2, 6, 4, 180, 360));
        $this->addLeftSystem(new Thruster(3, 15, 0, 5, 3));
		
        $this->addRightSystem(new ImperialLaser(4, 8, 5, 0, 120));
        $this->addRightSystem(new EWRoyalLaser(2, 6, 4, 0, 180));
        $this->addRightSystem(new EWRoyalLaser(2, 6, 4, 0, 180));
        $this->addRightSystem(new Thruster(3, 15, 0, 5, 4));

        //0:primary, 1:front, 2:rear, 3:left, 4:right;
        $this->addFrontSystem(new Structure(4, 48));
        $this->addAftSystem(new Structure(4, 48));
        $this->addLeftSystem(new Structure(4, 44));
        $this->addRightSystem(new Structure(4, 44));
        $this->addPrimarySystem(new Structure(4, 50));
		
		$this->hitChart = array(
			0=> array(
					10 => "Structure",
					11 => "Jump Engine",
                    12 => "Class-SO Missile Rack",
					14 => "Scanner",
					16 => "Engine",
					17 => "Hangar",
					19 => "Reactor",
					20 => "C&C",
			),
			1=> array(
					4 => "Thruster",
					7 => "Sovereign Laser",
					9 => "Dual Laser Bolt",
					18 => "Structure",
					20 => "Primary",
			),
			2=> array(
					6 => "Thruster",
					8 => "Royal Laser",
					10 => "Dual Laser Bolt",
					18 => "Structure",
					20 => "Primary",
			),
			3=> array(
					5 => "Thruster",
					7 => "Imperial Laser",
					10 => "Royal Laser",
					18 => "Structure",
					20 => "Primary",
			),
			4=> array(
					5 => "Thruster",
					7 => "Imperial Laser",
					10 => "Royal Laser",
					18 => "Structure",
					20 => "Primary",
			),
		);
    }
}

?>

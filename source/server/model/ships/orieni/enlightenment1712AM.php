<?php
class Enlightenment1712AM extends BaseShip{
    
    function __construct($id, $userid, $name,  $slot){
        parent::__construct($id, $userid, $name,  $slot);
        
		$this->pointCost = 600;
		$this->faction = "Orieni Imperium";
        $this->phpclass = "Enlightenment1712AM";
        $this->imagePath = "img/ships/enlightenment.png";
        $this->shipClass = "Enlightenment Invader (early)";
        $this->variantOf = "Enlightenment Invader";
        $this->shipSizeClass = 3;
	    $this->isd = 1712;
        $this->fighters = array("light"=>12, "assault shuttles"=>24);
        $this->canvasSize = 200;
		
        $this->forwardDefense = 19;
        $this->sideDefense = 18;
        
        $this->turncost = 1.33;
        $this->turndelaycost = 1;
        $this->accelcost = 5;
        $this->rollcost = 3;
        $this->pivotcost = 3;
        
		
		//ammo magazine itself (AND its missile options)
        $ammoMagazine = new AmmoMagazine(128); //pass magazine capacity - 12 rounds per class-SO rack, 20 most other shipborne racks, 60 class-B rack and 80 Reload Rack
        $this->addPrimarySystem($ammoMagazine); //fit to ship immediately
        $ammoMagazine->addAmmoEntry(new AmmoMissileB(), 128); //add full load of basic missiles
        $this->enhancementOptionsEnabled[] = 'AMMO_A';//add enhancement options for other missiles - Class-A
  //      $this->enhancementOptionsEnabled[] = 'AMMO_C';//add enhancement options for other missiles - Class-C        
        $this->enhancementOptionsEnabled[] = 'AMMO_H';//add enhancement options for other missiles - Class-H
		$this->enhancementOptionsEnabled[] = 'AMMO_KK';               
  //      $this->enhancementOptionsEnabled[] = 'AMMO_L';//add enhancement options for other missiles - Class-L
		//By the Book Orieni should have access to missie types: KK, B, A, H, L, C, but L and C not available until 2005 so disabled on these older ships.
		
		
        $this->addPrimarySystem(new Reactor(5, 30, 0, 0));
        $this->addPrimarySystem(new CnC(5, 25, 0, 0));
        $this->addPrimarySystem(new Scanner(5, 25, 4, 6));
        $this->addPrimarySystem(new Engine(5, 25, 0, 8, 4));
        $this->addPrimarySystem(new Hangar(4, 15));
        $this->addPrimarySystem(new ReloadRack(6, 9));
        $this->addPrimarySystem(new CargoBay(4, 24));
              
        $this->addFrontSystem(new LaserLance(2, 6, 4, 240, 60));
        $this->addFrontSystem(new LaserLance(2, 6, 4, 300, 120));
        $this->addFrontSystem(new OrieniGatlingRG(1, 4, 1, 240, 120));
        $this->addFrontSystem(new OrieniGatlingRG(1, 4, 1, 240, 120));
        $this->addFrontSystem(new OrieniGatlingRG(1, 4, 1, 240, 120));
        $this->addFrontSystem(new Thruster(2, 15, 0, 2, 1));
        $this->addFrontSystem(new Thruster(2, 15, 0, 2, 1));
        $this->addFrontSystem(new Thruster(2, 15, 0, 2, 1));  
	    
        $this->addAftSystem(new OrieniGatlingRG(1, 4, 1, 120, 240));
        $this->addAftSystem(new OrieniGatlingRG(1, 4, 1, 120, 240));
        $this->addAftSystem(new Thruster(2, 12, 0, 2, 2));
        $this->addAftSystem(new Thruster(2, 12, 0, 2, 2));
        $this->addAftSystem(new Thruster(2, 12, 0, 2, 2));
        $this->addAftSystem(new Thruster(2, 12, 0, 2, 2));
		
		$this->addLeftSystem(new AmmoMissileRackSO(3, 0, 0, 180, 300, $ammoMagazine, false)); //$armour, $health (0=auto), $power (0=auto), $startArc, $endArc, $magazine, $base
		$this->addLeftSystem(new AmmoMissileRackSO(3, 0, 0, 240, 360, $ammoMagazine, false)); //$armour, $health (0=auto), $power (0=auto), $startArc, $endArc, $magazine, $base
        $this->addLeftSystem(new OrieniGatlingRG(1, 4, 1, 180, 360));
        $this->addLeftSystem(new OrieniGatlingRG(1, 4, 1, 180, 360));
        $this->addLeftSystem(new Hangar(3, 14));        
        $this->addLeftSystem(new CargoBay(2, 25));    
        $this->addLeftSystem(new Thruster(4, 20, 0, 6, 3));
	    
		$this->addRightSystem(new AmmoMissileRackSO(3, 0, 0, 0, 120, $ammoMagazine, false)); //$armour, $health (0=auto), $power (0=auto), $startArc, $endArc, $magazine, $base
		$this->addRightSystem(new AmmoMissileRackSO(3, 0, 0, 60, 180, $ammoMagazine, false)); //$armour, $health (0=auto), $power (0=auto), $startArc, $endArc, $magazine, $base
        $this->addRightSystem(new OrieniGatlingRG(1, 4, 1, 0, 180));
        $this->addRightSystem(new OrieniGatlingRG(1, 4, 1, 0, 180));
        $this->addRightSystem(new Hangar(3, 14));        
        $this->addRightSystem(new CargoBay(2, 25));    
        $this->addRightSystem(new Thruster(4, 20, 0, 6, 4));
	    
		//structures
        $this->addFrontSystem(new Structure(4, 51));
        $this->addAftSystem(new Structure(4, 54));
        $this->addLeftSystem(new Structure(4, 60));
        $this->addRightSystem(new Structure(4, 60));
        $this->addPrimarySystem(new Structure(5, 48));
        
	    
	//d20 hit chart
	$this->hitChart = array(
		
		//PRIMARY
		0=> array( 
			9 => "Structure",
			11 => "Scanner",
			13 => "Engine",
			15 => "Hangar",
			17 => "Cargo Bay",
			18 => "Reload Rack",			
			19 => "Reactor",
			20 => "C&C",
		),
		//Forward
		1=> array(
			6 => "Thruster",
			8 => "Laser Lance",
			11 => "Gatling Railgun",
			18 => "Structure",
			20 => "Primary",
		),
		//Aft
		2=> array(
			8 => "Thruster",
			10 => "Gatling Railgun",
			18 => "Structure",
			20 => "Primary",
		),
		//Port
		3=> array(
			4 => "Thruster",
			6 => "Class-SO Missile Rack",
			8 => "Gatling Railgun",
			10 => "Hangar",			
			12 => "Cargo Bay",
			18 => "Structure",
			20 => "Primary",
		),
		//Starboard
		4=> array(
			4 => "Thruster",
			6 => "Class-SO Missile Rack",
			8 => "Gatling Railgun",
			10 => "Hangar",			
			12 => "Cargo Bay",
			18 => "Structure",
			20 => "Primary",
		),
	);
	    
	    
    }
}
?>

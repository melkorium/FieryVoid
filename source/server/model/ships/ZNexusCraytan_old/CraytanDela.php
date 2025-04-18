<?php
class CraytanDela extends HeavyCombatVesselLeftRight{
    
    function __construct($id, $userid, $name,  $slot){
        parent::__construct($id, $userid, $name,  $slot);
        
        $this->pointCost = 100;
        $this->faction = "ZNexus Support Units";
        $this->phpclass = "CraytanDela";
        $this->imagePath = "img/ships/Nexus/craytan_dela.png";
		$this->canvasSize = 125; //img has 200px per side
        $this->shipClass = "Craytan Dela Freighter";
		$this->unofficial = true;
        $this->isd = 1898;

        $this->fighters = array("assault shuttles"=>4);
		
        $this->forwardDefense = 14;
        $this->sideDefense = 15;
        
        $this->turncost = 1;
        $this->turndelaycost = 1;
        $this->accelcost = 4;
        $this->rollcost = 4;
        $this->pivotcost = 4;
        $this->iniativebonus = -10;
         
        $this->addPrimarySystem(new Reactor(3, 6, 0, 0));
        $this->addPrimarySystem(new CnC(4, 6, 0, 0));
        $this->addPrimarySystem(new Scanner(2, 8, 2, 2));
        $this->addPrimarySystem(new Engine(2, 6, 0, 4, 4));
		$this->addPrimarySystem(new Magazine(2, 4));
		$this->addPrimarySystem(new CargoBay(2, 12));
        $this->addFrontSystem(new Thruster(2, 10, 0, 4, 1));
      
        $this->addLeftSystem(new Thruster(2, 10, 0, 2, 2));
        $this->addLeftSystem(new Thruster(2, 10, 0, 3, 3));
        $this->addLeftSystem(new Hangar(2, 2));
		$this->addLeftSystem(new NexusCIDS(2, 4, 2, 240, 60));
		$this->addLeftSystem(new NexusCIDS(2, 4, 2, 120, 300));
		$this->addLeftSystem(new CargoBay(2, 40));
                
        $this->addRightSystem(new Thruster(2, 10, 0, 2, 2));
        $this->addRightSystem(new Thruster(2, 10, 0, 3, 4));
        $this->addRightSystem(new Hangar(2, 2));
		$this->addRightSystem(new NexusCIDS(2, 4, 2, 300, 120));
		$this->addRightSystem(new NexusCIDS(2, 4, 2, 60, 240));
		$this->addRightSystem(new CargoBay(2, 40));
        
        //0:primary, 1:front, 2:rear, 3:left, 4:right;
        $this->addPrimarySystem(new Structure(3, 40));
        $this->addLeftSystem(new Structure(2, 40));
        $this->addRightSystem(new Structure(2, 40));
		
        $this->hitChart = array(
            0=> array(
                    8 => "Structure",
					10 => "1:Thruster",
					11 => "Magazine",
					13 => "Cargo",
                    15 => "Scanner",
                    17 => "Engine",
                    19 => "Reactor",
                    20 => "C&C",
            ),
            3=> array(
					2 => "Connection Strut",
                    5 => "Thruster",
					7 => "Close-In Defense System",
					8 => "Hangar",
					12 => "Cargo",
					18 => "Structure",
                    20 => "Primary",
            ),
            4=> array(
					2 => "Connection Strut",
                    5 => "Thruster",
					7 => "Close-In Defense System",
					8 => "Hangar",
					12 => "Cargo",
					18 => "Structure",
                    20 => "Primary",
            ),
        );
    }
}
?>

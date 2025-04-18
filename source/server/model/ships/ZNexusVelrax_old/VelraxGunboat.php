<?php
class VelraxGunboat extends LCV{
    
    function __construct($id, $userid, $name,  $slot){
        parent::__construct($id, $userid, $name,  $slot);
        
        $this->pointCost = 160;
        $this->faction = "ZNexus Velrax Republic (early)";
        $this->phpclass = "VelraxGunboat";
        $this->imagePath = "img/ships/Nexus/velraxArcGunboat.png";
			$this->canvasSize = 70; //img has 200px per side
        $this->shipClass = "Nashran Gunboat";
		$this->unofficial = true;
			$this->isd = 2033;

        $this->hangarRequired = ''; //Nexus LCVs are more independent than their B5 counterparts
	    $this->notes = 'Atmospheric Capable.';
	    $this->notes .= '<br>May deploy independently.';
        
        $this->forwardDefense = 8;
        $this->sideDefense = 13;
        
		$this->agile = true;
        $this->turncost = 0.33;
        $this->turndelaycost = 0.33;
        $this->accelcost = 1;
        $this->rollcost = 1;
        $this->pivotcost = 1;
        $this->iniativebonus = 14*5;
 
		$this->addFrontSystem(new InvulnerableThruster(99, 99, 0, 99, 1)); //unhitable and with unlimited thrust allowance
		$this->addAftSystem(new InvulnerableThruster(99, 99, 0, 99, 3)); //unhitable and with unlimited thrust allowance
		$this->addAftSystem(new InvulnerableThruster(99, 99, 0, 99, 2)); //unhitable and with unlimited thrust allowance
		$this->addAftSystem(new InvulnerableThruster(99, 99, 0, 99, 4)); //unhitable and with unlimited thrust allowance
  
		$this->addPrimarySystem(new Reactor(2, 9, 0, 0));
		$this->addPrimarySystem(new CnC(99, 1, 0, 0)); //C&C should be unhittable anyway
    	$sensors = new Scanner(2, 7, 2, 3);
			$sensors->markLCV();
			$this->addPrimarySystem($sensors);
//        $this->addPrimarySystem(new AntiquatedScanner(2, 7, 2, 3));
		$this->addPrimarySystem(new Engine(3, 15, 0, 5, 2));

		$this->addFrontSystem(new NexusIonGun(2, 2, 2, 240, 60));
		$this->addFrontSystem(new EWPlasmaArc(2, 5, 4, 300, 60));
		$this->addFrontSystem(new NexusIonGun(2, 2, 2, 300, 120));
	    
        $this->addPrimarySystem(new Structure(3, 32));
	    
        $this->hitChart = array(
        		0=> array( 
        				10 => "Structure",
        				13 => "1:Plasma Arc",
        				15 => "1:Ion Gun",
						17 => "0:Engine",
        				19 => "0:Reactor",
        				20 => "0:Scanner",
        		),
        		1=> array( //redirect to PRIMARY
        				10 => "Structure",
        				13 => "1:Plasma Arc",
        				15 => "1:Ion Gun",
						17 => "0:Engine",
        				19 => "0:Reactor",
        				20 => "0:Scanner",
        		),
        		2=> array( //redirect to PRIMARY
        				10 => "Structure",
        				13 => "1:Plasma Arc",
        				15 => "1:Ion Gun",
						17 => "0:Engine",
        				19 => "0:Reactor",
        				20 => "0:Scanner",
        		),        		
        ); //end of hit chart
    }
}
?>

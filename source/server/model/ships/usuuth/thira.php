<?php
class Thira extends LCV{
    
    function __construct($id, $userid, $name,  $slot){
        parent::__construct($id, $userid, $name,  $slot);
        
        $this->pointCost = 90;
        //$this->faction = "Usuuth Coalition";
	    $this->faction = "Civilians";
        $this->phpclass = "Thira";
        $this->shipClass = "Usuuth Thira Freighter";
        $this->imagePath = "img/ships/UsuuthBaroon.png";
        $this->canvasSize = 100;
        $this->agile = true;
        
        $this->forwardDefense = 11;
        $this->sideDefense = 12;
	    $this->isCombatUnit = false; //not a combat unit, it will never be present in a regular battlegroup
		
        
        $this->occurence = "common";
        $this->isd = 1955;
        //$this->variantOf = "Baroon Escort Cutter";
        $this->hangarRequired = ''; //LCV-sized, but designed as cargo ship for interstellar trade - with less raw power, more independence
        
        $this->turncost = 0.5;
        $this->turndelaycost = 0.5;
        $this->accelcost = 2;
        $this->rollcost = 1;
        $this->pivotcost = 1;
        $this->iniativebonus = 6*5;       
        
        $this->addFrontSystem(new InvulnerableThruster(99, 99, 0, 99, 1)); //unhitable and with unlimited thrust allowance
        $this->addAftSystem(new InvulnerableThruster(99, 99, 0, 99, 3)); //unhitable and with unlimited thrust allowance
        $this->addAftSystem(new InvulnerableThruster(99, 99, 0, 99, 2)); //unhitable and with unlimited thrust allowance
        $this->addAftSystem(new InvulnerableThruster(99, 99, 0, 99, 4)); //unhitable and with unlimited thrust allowance
        
        $this->addPrimarySystem(new Reactor(3, 6, 0, 0));
        $this->addPrimarySystem(new CnC(99, 99, 0, 0)); //C&C should be unhittable anyway
	    	$sensors = new Scanner(3, 6, 1, 2);
		    $sensors->markLCV();
		    $this->addPrimarySystem($sensors);
        $this->addPrimarySystem(new Engine(3, 12, 0, 6, 1));
        $this->addPrimarySystem(new CargoBay(2, 8));
        $this->addPrimarySystem(new CargoBay(2, 8));

        $this->addFrontSystem(new LightParticleProjector(2, 3, 1, 180, 360));
        $this->addFrontSystem(new LightParticleProjector(2, 3, 1, 0, 180));
        
        $this->addPrimarySystem(new Structure(4, 24));   
        
        $this->hitChart = array(
            0=> array( //should never happen
                11 => "Structure",
                13 => "0:Cargo",
                15 => "1:Particle Projector",
                17 => "0:Engine",
                19 => "0:Reactor",
                20 => "0:Scanner",
            ),
            1=> array( //PRIMARY hit table, effectively
                11 => "Structure",
                13 => "0:Cargo",
                15 => "1:Particle Projector",
                17 => "0:Engine",
                19 => "0:Reactor",
                20 => "0:Scanner",
            ),
            2=> array( //same as Fwd
                11 => "Structure",
                13 => "0:Cargo",
                15 => "1:Particle Projector",
                17 => "0:Engine",
                19 => "0:Reactor",
                20 => "0:Scanner",
            ),         
        ); //end of hit chart              
    }
}
?>

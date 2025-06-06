<?php
class QomYominCeedrae extends MediumShip{

    function __construct($id, $userid, $name,  $slot){
        parent::__construct($id, $userid, $name,  $slot);
        
        $this->pointCost = 310;
        $this->faction = "ZNexus Makar Federation";
        $this->phpclass = "QomYominCeedrae";
        $this->imagePath = "img/ships/Nexus/makar_ceedrae2.png";
        $this->shipClass = "Ceedrae Escort Frigate";
			$this->variantOf = "Sorol Ma Light Scout (2015)";
			$this->occurence = "common";
		$this->unofficial = true;
        $this->canvasSize = 80;
	    $this->isd = 2107;

	    $this->notes = '<br>Unreliable Ship:';
 	    $this->notes .= '<br> - Sluggish';
		$this->notes .= '<br>Ramming damage is +33% greater due to large quantities of on board water.';
		$this->enhancementOptionsDisabled[] = 'SLUGGISH';
        
        $this->forwardDefense = 13;
        $this->sideDefense = 13;
        
        $this->turncost = 0.66;
        $this->turndelaycost = 0.66;
        $this->accelcost = 3;
        $this->rollcost = 2;
        $this->pivotcost = 2;
        $this->iniativebonus = 53;
         
        $this->addPrimarySystem(new Reactor(3, 13, 0, 6));
        $this->addPrimarySystem(new CnC(4, 9, 0, 0));
        $this->addPrimarySystem(new Scanner(3, 12, 5, 6));
        $this->addPrimarySystem(new Engine(3, 11, 0, 5, 3));
		$this->addPrimarySystem(new NexusWaterCaster(3, 4, 1, 0, 360));
        $this->addPrimarySystem(new Thruster(3, 13, 0, 5, 3));
        $this->addPrimarySystem(new Thruster(3, 13, 0, 5, 4));   

		//Need this to boost the Qom Yomin ramming factor to account for all of the water on board
		//Qom Yomin manned units typically have a +33% to ram
	    $hitBonus = 0; //there is no bonus to hit
	    $rammingAttack = new RammingAttack(0, 0, 360, 60, $hitBonus, true, 0); //actual damage - NOT calculated, but designed (60)
		$this->addPrimarySystem($rammingAttack);
        
		$this->addFrontSystem(new NexusLightBurstBeam(3, 6, 3, 180, 60));
		$this->addFrontSystem(new EWDualRocketLauncher(3, 6, 2, 270, 90));
		$this->addFrontSystem(new NexusLightBurstBeam(3, 6, 3, 300, 180));
        $this->addFrontSystem(new Thruster(3, 7, 0, 3, 1));
        $this->addFrontSystem(new Thruster(3, 7, 0, 3, 1));
	    
		$this->addAftSystem(new NexusLightChargeCannon(3, 4, 1, 180, 60));
		$this->addAftSystem(new NexusLightChargeCannon(3, 4, 1, 0, 360));
		$this->addAftSystem(new NexusLightChargeCannon(3, 4, 1, 300, 180));
		$this->addAftSystem(new Hangar(2, 2));
		$this->addAftSystem(new Thruster(3, 10, 0, 3, 2));    
        $this->addAftSystem(new Thruster(3, 10, 0, 3, 2));    
        
        $this->addPrimarySystem(new Structure(4, 45));

	//d20 hit chart
	$this->hitChart = array(
		
		0=> array(
			8 => "Thruster",
			10 => "Water Caster",
			14 => "Scanner",
			17 => "Engine",
			19 => "Reactor",
			20 => "C&C",
		),

		1=> array(
			6 => "Thruster",
			8 => "Dual Rocket Launcher",
			10 => "Light Burst Beam",
			17 => "Structure",
			20 => "Primary",
		),

		2=> array(
			7 => "Thruster",
			9 => "Hangar",
			11 => "Light Charge Cannon",
			17 => "Structure",
			20 => "Primary",
		),

	);

        
        }
    }
?>

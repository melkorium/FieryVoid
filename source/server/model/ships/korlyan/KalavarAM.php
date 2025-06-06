<?php
class KalavarAM extends OSAT{
    
    function __construct($id, $userid, $name,  $slot){
        parent::__construct($id, $userid, $name,  $slot);
        
		$this->pointCost = 250;
		$this->faction = "Kor-Lyan Kingdoms";
        $this->phpclass = "KalavarAM";
        $this->imagePath = "img/ships/korlyan_kalavar.png";
        $this->shipClass = "Kalavar Orbital Satellite (2240)";
        $this->isd = 2240;
        
        $this->forwardDefense = 10;
        $this->sideDefense = 10;
        
        $this->turncost = 0;
        $this->turndelaycost = 0;
        $this->accelcost = 0;
        $this->rollcost = 0;
        $this->pivotcost = 0;	
        $this->iniativebonus = 60; 

	//ammo magazine itself (AND its missile options)
	$ammoMagazine = new AmmoMagazine(60); //pass magazine capacity 
	    $this->addPrimarySystem($ammoMagazine); //fit to ship immediately
	    $ammoMagazine->addAmmoEntry(new AmmoMissileB(), 40); //add full load of basic missiles 
	    $ammoMagazine->addAmmoEntry(new AmmoMissileI(), 20); //add full load of basic missiles  	      

	    $this->enhancementOptionsEnabled[] = 'AMMO_A';//add enhancement options for other missiles - Class-A
	    $this->enhancementOptionsEnabled[] = 'AMMO_C';//add enhancement options for other missiles - Class-C
	    $this->enhancementOptionsEnabled[] = 'AMMO_F';//add enhancement options for other missiles - Class-F
	    $this->enhancementOptionsEnabled[] = 'AMMO_H';//add enhancement options for other missiles - Class-H    
	    $this->enhancementOptionsEnabled[] = 'AMMO_I';//add enhancement options for other missiles - Class-I
	    $this->enhancementOptionsEnabled[] = 'AMMO_J';//add enhancement options for other missiles - Class-J	     
	    $this->enhancementOptionsEnabled[] = 'AMMO_K';//add enhancement options for other missiles - Class-K   
	    $this->enhancementOptionsEnabled[] = 'AMMO_L';//add enhancement options for other missiles - Class-L
	    $this->enhancementOptionsEnabled[] = 'AMMO_M';//add enhancement options for other missiles - Class-M	    
		$this->enhancementOptionsEnabled[] = 'AMMO_P';//add enhancement options for other missiles - Class-P    	    	    	    
	    $this->enhancementOptionsEnabled[] = 'AMMO_S';//add enhancement options for other missiles - Class-S
	    $this->enhancementOptionsEnabled[] = 'AMMO_X';//add enhancement options for other missiles - Class-X	

        $this->addPrimarySystem(new OSATCnC(0, 1, 0, 0));	    
        $this->addPrimarySystem(new Reactor(3, 5, 0, 0));
        $this->addPrimarySystem(new Scanner(3, 5, 2, 4)); 


//		$TargeterA = new ProximityLaser(0, 1, 0, 180, 360, 'A');
		$TargeterA = new ProximityLaser(0, 1, 0, 0, 360, 'A');		
		$LauncherA = new ProximityLaserLauncher(3, 0, 0, 180, 360, 'A'); 
		$TargeterA->addLauncher($LauncherA);
		$this->addAftSystem($TargeterA);
		$this->addAftSystem($LauncherA);
        $this->addAftSystem(new Thruster(4, 6, 0, 0, 2));

//		$TargeterB = new ProximityLaser(0, 1, 0, 0, 180, 'B');
		$TargeterB = new ProximityLaser(0, 1, 0, 0, 360, 'B');		
		$LauncherB = new ProximityLaserLauncher(3, 0, 0, 0, 180, 'B'); 
		$TargeterB->addLauncher($LauncherB);
		$this->addAftSystem($TargeterB);
		$this->addAftSystem($LauncherB);	
        
        $this->addFrontSystem(new AmmoMissileRackF(3, 0, 0, 270, 90, $ammoMagazine, true));
        $this->addFrontSystem(new AmmoMissileRackD(2, 0, 0, 0, 360, $ammoMagazine, true));
        $this->addFrontSystem(new AmmoMissileRackF(3, 0, 0, 270, 90, $ammoMagazine, true));
        
        //0:primary, 1:front, 2:rear, 3:left, 4:right;
        
        $this->addPrimarySystem(new Structure(4, 30));
        
        	//d20 hit chart
        $this->hitChart = array(
            0=> array(
                    9 => "Structure",
                    11 => "2:Thruster",
		    		13 => "1:Class-F Missile Rack",
		    		15 => "2:Proximity Laser",
                    17 => "Scanner",
                    19 => "Reactor",
                    20 => "1:Class-D Missile Rack",
            )
        );
        
    }
}
?>

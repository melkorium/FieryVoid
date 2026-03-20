<?php
class dewMineTerrainMissile extends Mine{
    
    function __construct($id, $userid, $name,  $slot){
        parent::__construct($id, $userid, $name,  $slot);
        
		$this->pointCost = 30;
		$this->faction = "Terrain";
        $this->phpclass = "dewMineTerrainMissile";
        $this->imagePath = "img/ships/korlyan_mine.png";
        $this->shipClass = "DEW Mine (Missile)";
		$this->occurence = "common";
		$this->variantOf = "DEW Mine (Beam)";
        $this->isd = 2200;
        
        $this->forwardDefense = 12;
        $this->sideDefense = 12;
        $this->signature = 3;
        $this->detectedSignature = 2;           
        
        $this->turncost = 0;
        $this->turndelaycost = 0;
        $this->accelcost = 0;
        $this->rollcost = 0;
        $this->pivotcost = 0;	
        $this->iniativebonus = -200; 
        $this->IFFSystem = true;
       		    	    	    	    
        //Block all enhancements for Mine units when bought
		Enhancements::nonstandardEnhancementSet($this, 'Mines');	
		$this->enhancementOptionsDisabled[] = 'IFF_SYS'; //Already comes equipped with one.
        
	    //ammo magazine itself (AND its missile options)
	    $ammoMagazine = new AmmoMagazine(4); //pass magazine capacity 
	    $this->addPrimarySystem($ammoMagazine); //fit to ship immediately
	    $ammoMagazine->addAmmoEntry(new AmmoMissileB(), 4); //add full load of basic missiles                 


        $this->addPrimarySystem(new OSATCnC(0, 1, 0, 0));
        $this->addPrimarySystem(new MagGravReactorTechnical(0, 1, 0, 1));
        $this->addPrimarySystem(new mineStealth(0, 1, 1));
        $this->addPrimarySystem(new MineControllerDEW(0, 1, 0, 20, 2, true)); //$armour, $maxhealth, $powerReq, $startArc, $endArc, $range/output, $accuracy 
        $this->addPrimarySystem(new AmmoMissileRackS(0, 4, 0, 0, 360, $ammoMagazine, false));
        
        //0:primary, 1:front, 2:rear, 3:left, 4:right;
        $this->addPrimarySystem(new Structure(0, 6));
        
        	//d20 hit chart
        $this->hitChart = array(
            0=> array(
                    20 => "Structure",
            )
        );
        
    }
}
?>

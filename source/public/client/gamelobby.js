'use strict';

window.gamedata = {
    thisplayer: 0,
    slots: null,
    ships: [],
    gameid: 0,
    turn: 0,
    phase: 0,
    activeship: 0,
    waiting: true,
    maxpoints: 0,
    status: "LOBBY",
    selectedSlot: null,
    allShips: null,
 	displayedShip: '',
 	displayedFaction: '',
	lastShipNumber: 0,

    getPowerRating: function getPowerRating(factionName) {
		var powerRating = '';
		switch(factionName) {
		  case 'Abbai Matriarchate':
			powerRating = 'Tier 2';
			break;
		  case 'Abbai Matriarchate (WotCR)':
			powerRating = 'Tier 3';
			break;
		  case 'Alacan Republic':
			powerRating = 'Tier 3';
			break;
		  case 'Balosian Underdwellers':
			powerRating = 'Tier 2';
			break;
		  case 'Belt Alliance':
			powerRating = 'Tier 2';
			break;
		  case 'Brakiri Syndicracy':
			powerRating = 'Tier 2; distinct Corporations can be mixed freely';
			break;
		  case 'Cascor Commonwealth':
			powerRating = 'Tier 3 (official) or Tier 2 (custom re-pointing enhancement)';
			break;
		  case 'Centauri Republic':
			powerRating = 'Tier 1';
			break;
		  case 'Centauri Republic (WotCR)':
			powerRating = 'Tier 3';
			break;
		  case "Ch'Lonas Cooperative":
			powerRating = 'Tier 2, custom faction';
			break;
		  case 'Civilians':
			powerRating = 'Generic civilian designs for scenarios, not a faction';
			break;
		  case 'Corillani Theocracy':
			powerRating = 'Tier 2; distinct sub-factions should not be mixed';
			break;
		  case 'Custom Ships':
			powerRating = "Designs that don't fit anywhere else, not a faction";
			break;
		  case 'Deneth Tribes':
			powerRating = 'Tier 2';
			break;
		  case 'Descari Committees':
			powerRating = 'Tier 2';
			break;
		  case 'Dilgar Imperium':
			powerRating = 'Tier 1';
			break;
		  case 'Drakh':
			powerRating = 'Tier 1, custom faction';
			break;
		  case 'Drazi Freehold':
			powerRating = 'Tier 1';
			break;
		  case 'Drazi Freehold (WotCR)':
			powerRating = 'Tier 2';
			break;
		  case 'Earth Alliance':
			powerRating = 'Tier 1 (Not Balanced with Warlock or Shadow Omega)';
			break;
		  case 'Earth Alliance (custom)':
			powerRating = 'custom and non-combatant designs for EA faction';
			break;
		  case 'Earth Alliance (defenses)':
			powerRating = 'fixed tefenses for EA faction';
			break;
		  case 'Earth Alliance (early)':
			powerRating = 'Tier 3';
			break;
		  case 'Gaim Intelligence':
			powerRating = 'Tier 1';
			break;
		  case 'Grome Autocracy':
			powerRating = 'Tier 3';
			break;
		  case 'Hurr Republic':
			powerRating = 'Tier 3';
			break;
		  case 'Hyach Gerontocracy':
			powerRating = 'Tier 1';
			break;
		  case 'Ipsha Baronies':
			powerRating = 'Tier 3 (distinct Baronies cannot be mixed)';
			break;
		  case 'Kor-Lyan Kingdoms':
			powerRating = 'Tier 1';
			break;
		  case 'Llort': //actually no full name in the sourcebook (RPP1), it's just Llort!
			powerRating = 'Tier 1';
			break;
		  case 'Markab Theocracy':
			powerRating = 'Tier 3';
			break;
		  case 'Minbari Federation':
			powerRating = 'Tier 1 (all-White Star: Not Balanced)';
			break;
		  case 'Minbari Protectorate':
			powerRating = 'Tier 1';
			break;
		  case 'Mindriders':
			powerRating = 'Tier Ancients';
			break;			
		  case 'Narn Regime':
			powerRating = 'Tier 1 (Not Balanced with >6 EMine launchers)';
			break;
		  case 'Orieni Imperium':
			powerRating = 'Tier 1';
			break;
		  case 'Orieni Imperium (defenses)':
			powerRating = 'fixed tefenses for Orieni faction';
			break;
		  case "Pak'ma'ra Confederacy":
			powerRating = 'Tier 2';
			break;
		  case 'Raiders':
			powerRating = 'Tier 2 (directory contains distinct Raider factions in addition to generic Raiders!)';
			break;
		  case 'Rogolon Dynasty':
			powerRating = 'Tier 3';
			break;
		  case 'Shadow Association':
			powerRating = 'Tier Ancients';
			break;
		  case 'Small Races':
			powerRating = 'varies by faction, see Tiers file';
			break;
		  case 'Streib':
			powerRating = 'Not balanced';
			break;
		  case 'Thirdspace':
			powerRating = 'Ancients, custom faction';
			break;
		  case 'Torata Regency':
			powerRating = 'Tier 1';
			break;
		  case 'Usuuth Coalition':
			powerRating = 'Tier 3';
			break;
		  case 'Vorlon Empire':
			powerRating = 'Tier Ancients';
			break;
		  case 'Vree Conglomerate':
			powerRating = 'Tier 1';
			break;
		  case 'Yolu Confederation':
			powerRating = 'Not Balanced (official) or Tier 1 (custom re-pointing enhancement)';
			break;
		  case 'ZBSG Colonials':
			powerRating = 'Tier 2, custom faction';
			break;
		  case 'ZEscalation Blood Sword Raiders':
			powerRating = 'Tier 3, custom faction';
			break;
		  case 'ZEscalation Civilian':
			powerRating = 'Generic civilian designs for scenarios, not a faction, custom';
			break;
		  case 'ZEscalation Chouka Raider':
			powerRating = 'Not balanced, custom faction';
			break;
		  case 'ZEscalation Chouka Theocracy':
			powerRating = 'Tier 2, custom faction';
			break;
		  case 'ZEscalation Circasian Empire':
			powerRating = 'Tier 2, custom faction';
			break;
		  case 'ZEscalation Kastan Monarchy':
			powerRating = 'Tier 2, custom faction';
			break;
		  case "ZEscalation Sshel'ath Alliance":
			powerRating = 'Tier 2, custom faction';
			break;
		  case 'ZNexus Brixadii Clans (early)':
			powerRating = 'Tier 3, custom faction';
			break;
		  case 'ZNexus Brixadii Clans':
			powerRating = 'Tier 2, custom faction';
			break;
		  case 'ZNexus Support Units':
			powerRating = 'Designs for scenarios, not a faction, custom';
			break;
		  case 'ZNexus Craytan Union (early)':
			powerRating = 'Tier 3, custom faction';
			break;
		  case 'ZNexus Craytan Union':
			powerRating = 'Tier 2, custom faction';
			break;
		  case 'ZNexus Dalithorn Commonwealth (early)':
			powerRating = 'Tier 3, custom faction';
			break;
		  case 'ZNexus Dalithorn Commonwealth':
			powerRating = 'Tier 2, custom faction';
			break;
		  case 'ZNexus Makar Federation (early)':
			powerRating = 'Tier 3, custom faction, playtest';
			break;
		  case 'ZNexus Makar Federation':
			powerRating = 'Tier 2, custom faction, playtest';
			break;
		  case 'ZNexus Sal-bez Coalition (early)':
			powerRating = 'Tier 3, custom faction';
			break;
		  case 'ZNexus Sal-bez Coalition':
			powerRating = 'Tier 2, custom faction';
			break;
		  case 'ZNexus Velrax Republic (early)':
			powerRating = 'Tier 3, custom faction';
			break;
		  case 'ZNexus Velrax Republic':
			powerRating = 'Tier 2, custom faction';
			break;
		  case 'ZStarTrek (TOS) Federation':
			powerRating = 'Tier 2, custom faction';
			break;
		  case 'ZStarTrek (early) Federation':
			powerRating = 'Tier 3, custom faction';
			break;
		  case 'ZStarTrek (early) Suliban':
			powerRating = 'Tier 3, custom faction';
			break;
		  case 'ZStarWars':
			powerRating = 'Tier 2 (directory contains multiple overlapping factions), custom faction';
			break;
		  default:
			powerRating = 'NOT ASSIGNED';
		}
			//...disclaimer proved too long to be practical
		//powerRating = 'Estimated CUSTOM combat effectiveness rating: ' + powerRating;
		return powerRating;
    },
	
    canAfford: function canAfford(ship) {

        var slotid = gamedata.selectedSlot;
        var selectedSlot = playerManager.getSlotById(slotid);

        var points = 0;
        for (var i in gamedata.ships) {
            var lship = gamedata.ships[i];
            if (lship.slot != slotid) continue;
            points += lship.pointCost;
        }

        points += ship.pointCost;
        if (points > selectedSlot.points) return false;

        return true;
    },

    updateFleet: function updateFleet(ship) {
        var a = 0;
        for (var i in gamedata.ships) {
            a = i;
        }
        a++;
        ship.id = a;
        ship.slot = gamedata.selectedSlot;
        gamedata.ships[a] = ship;
        var h = $('<div class="ship bought slotid_' + ship.slot + ' shipid_' + ship.id + '" data-shipindex="' + a + '"><span class="shiptype">' + ship.shipClass + '</span><span class="shipname name">' + ship.name + '</span><span class="pointcost">' + ship.pointCost + 'p</span><span class="remove clickable">remove</span></div>');
        $(".remove", h).bind("click", function () {
            delete gamedata.ships[a];
            h.remove();
            gamedata.calculateFleet();
        });
        h.appendTo("#fleet");
        gamedata.calculateFleet();
    },
	
	/*returns ship variant as a single letter*/
	variantLetter: function(ship){
		var vLetter = '';		
		switch(ship.occurence) {
		    case 'unique':
			vLetter = 'Q';
			break;
		    case 'rare':
			vLetter = 'R';
			break;
		    case 'uncommon':
			vLetter = 'U';
			break;
		    case 'common':
			vLetter = 'C';
			break;
		    default: //assume something atypical
			vLetter = 'X'; 
		}	
		return(vLetter);
	},
	
	/*checks fleet composition and displays alert with result*/
    checkChoices: function(){

		//block if player already has confirmed fleet (in any slot)
		for (var i in gamedata.slots)  { //check all slots
			var checkSlot = gamedata.slots[i];
			if (checkSlot.lastphase == "-2") { //this slot has ready fleet
				var player = playerManager.getPlayerInSlot(checkSlot);
				if (player.id == gamedata.thisplayer){
					window.confirm.error("You have already confirmed Your fleet for this game!", function () {});
					return;
				}
			}
		}

		var warningText = ""
	    var checkResult = "";
	    var problemFound = false;
	    var warningFound = false;
	    var slotid = gamedata.selectedSlot;
            var selectedSlot = playerManager.getSlotById(slotid);
	    
	    var units10 = 0;
	    var units33 = 0;
	    var points10 = 0;
	    var points33 = 0;
	    var totalU = 0;
	    var totalR = 0;
	    var jumpDrivePresent = false;
	    var capitalShips = 0;
	    var totalShips = 0;
	    var customShipPresent = false;
	    var enhancementPresent = false;
	    var uniqueShipPresent = false;
		var ancientUnitPresent = false;
		var specialVariantPresent = false;
	    var staticPresent = false;
	    var nonCombatPresent = false;
	    var shipTable = []; 
	    var noSmallFlights = 0;
	    
		var specialFighters = [];
		var specialHangars = [];
		var specialFtrAmt = 0;
		var specialFtrName = '';
		var specialHgrAmt = 0;
		var specialHgrName = '';
	    var totalHangarH = 0; //hangarspace for heavy fighters
	    var totalHangarM = 0; //hangarspace for medium fighters
	    var totalHangarL = 0; //hangarspace for light fighters
	    var totalHangarXL = 0; //hangarspace for ultralight fighters
		var totalHangarAS = 0;//total Assault Shuttle/Breaching pod slots		
	    var totalHangarOther = new Array( ); //other hangarspace
	    var totalFtrH = 0;//total heavy fighters
	    var totalFtrM = 0;//total medium fighters
	    var totalFtrL = 0;//total light fighters
	    var totalFtrXL = 0;//total ultralight fighters
		var totalFtrAS = 0;//total Assault Shuttle/Breaching pods
		var hangarConversionsF = 0; //How many converted hangar slots TO fighter slots.
		var hangarConversionsAS = 0; //How many converted hangar slots TO Assault Shuttle slots.		
	    var totalFtrOther = new Array( );//total other small craft
		var smallCraftUsed = new Array( );//small craft sizes that happen to be present, whether as hangar space or actual craft
		
		var totalEnhancementsValue = 0;

	    for (var i in gamedata.ships){
			var lship = gamedata.ships[i];
			if (lship.slot != slotid) continue;

			if (lship.limited==10){
				points10 += lship.pointCost;
				units10 += 1;
			}
			if (lship.limited==33){
				points33 += lship.pointCost;
				units33 += 1;
			}		
			totalEnhancementsValue += lship.pointCostEnh;
			var vLetter = gamedata.variantLetter(lship);
			var hull = lship.variantOf; 
			var hullFound;
			hullFound = false;
			if (hull == "") hull = lship.shipClass; //ship is either base itself, or base is indicated in variantOf variable
			for (var j in  shipTable){
				var oHull = shipTable[j];
				if (oHull.name == hull){
					hullFound = true;
					oHull.Total++;
					if(lship.hangarRequired!=''){ //let's require sticking to hull limit if ANY ship of this hull requires it
						oHull.hangarRequired = true;
					}
					switch(vLetter) {
						case 'Q':
						oHull.Q++;
						totalR++;
						uniqueShipPresent = true;
						break;
						case 'R':
						oHull.R++;
						totalR++;
						break;
						case 'U':
						oHull.U++;
						totalU++;
						break;
						case 'C':
						oHull.C++;
						break;
						default:
						nHull.X++;
						}
				}
			}
			if (hullFound == false){
				var nHull = {name:hull, Total: 1, Q:0, R: 0, U: 0, C: 0, X: 0, isFtr:false, hangarRequired:false };
					if(lship.flight){
						nHull.isFtr = lship.flight;
					}
					if(lship.hangarRequired!=''){
						nHull.hangarRequired = true;
					}
				switch(vLetter) {
					case 'Q':
					nHull.Q++;
					totalR++; //Unique is treated more or less the same as Rare
					uniqueShipPresent = true;
					break;
					case 'R':
					nHull.R++;
					totalR++;
					break;
					case 'U':
					nHull.U++;
					totalU++;
					break;
					case 'C':
					nHull.C++;
					break;
					default:
					nHull.X++;
					specialVariantPresent = true;
				}
				shipTable.push(nHull);
			}
			if (lship.factionAge > 2){
				ancientUnitPresent = true;
			}
			if(!lship.flight){
					totalShips++;
					
				// Check if ship has converted Assault Shuttle Hangar Space to Fighters before calculating total hangar space
				for (var enh in lship.enhancementOptions) { 
					if (lship.enhancementOptions[enh][6]) { // Hangar conversion is an option, ignore others.
						if (lship.enhancementOptions[enh][0] === "HANG_CON_F") {
							hangarConversionsF += lship.enhancementOptions[enh][2]; //Record number of slots converted from Assault Shuttle to Fighters.
						}	
					}
				}

				// Check if ship has converted Fighter Hangar Space to Assault Shuttles before calculating total hangar space
				for (var enh in lship.enhancementOptions) { 
					if (lship.enhancementOptions[enh][6]) { // Hangar conversion is an option, ignore others.
						if (lship.enhancementOptions[enh][0] === "HANG_CON_AS") {
							hangarConversionsAS += lship.enhancementOptions[enh][2]; //Record number of slots converted from Fighter to Assault Shuttles.
							if (lship.customFighter && Object.keys(lship.customFighter).length > 0) {
								var shipFighters = 0;
								for (let g in lship.fighters) {
									shipFighters += lship.fighters[g];
								}
								var customFighters = 0;
								for (let h in lship.customFighter) {
									customFighters += lship.customFighter[h];
								}								
								if((shipFighters-lship.enhancementOptions[enh][2]) <= customFighters){
									for (var i in lship.customFighter){								
										lship.customFighter[i] = (shipFighters-lship.enhancementOptions[enh][2]);
										break; //Let's just amend the first entry, should usually work...										
									}
								}	
							}
						}
					}
				}

				//check for custom hangars
				if(lship.customFighter){
					for (var h in lship.customFighter){
						specialHgrName = h;
						specialHgrAmt = lship.customFighter[h];
						specialHangars.push([specialHgrName, specialHgrAmt]);
					}				
					//console.table(specialHangars);
				}



				//check hangar space available...	
				for(var h in lship.fighters){
					var amount = lship.fighters[h];
					if(h == "normal" || h =="heavy"){
						totalHangarH += amount;
					}else if(h=="medium"){ 
						totalHangarM += amount;
					}else if(h=="light"){ 
						totalHangarL += amount;
					}else if(h=="ultralight"){ 
						totalHangarXL += amount;
					}else if(h=="assault shuttles"){ 
						totalHangarAS += amount;
					}else{ //something other than fighters
						var found = false;
						for(var nh in totalHangarOther){ 					
							if (totalHangarOther[nh][0] == h){//this is small craft type we're looking for!
								found = true;
								totalHangarOther[nh][1] += amount;
							}
						}
						if (found != true){ //such craft wasn't encountered yet
							totalHangarOther.push(new Array(h,amount));
							smallCraftUsed.push(h);
						}
					}
				}
				//ship may actually require hangar, too! but this must be specified directly
				if (lship.hangarRequired != '') { //classify based on explicit info from craft
					var found = false;
					for(var nh in totalFtrOther){ 					
						if (totalFtrOther[nh][0] == lship.hangarRequired){//this is small craft type we're looking for!
							found = true;
							totalFtrOther[nh][1] += 1/lship.unitSize; //always 1 craft in this case!
						}
					}
					if (found != true){ //such craft wasn't encountered yet
						totalFtrOther.push(new Array(lship.hangarRequired,1/lship.unitSize));
						smallCraftUsed.push(lship.hangarRequired);
					}
				}
			}else{//note presence of fighters
				totalShips++; //well, total units anyway... rules say "one other unit present" and indicate that unit may be a fighter flight as well
				
				//check for presence of small flights: if for something flight size of 6 is allowed, then anything less counts as small flight
				if ((lship.flightSize<6)&&(lship.maxFlightSize>=6)) noSmallFlights++;
				
				var smallCraftSize = '';			
				if (lship.hangarRequired != 'fighters' ) { //classify based on explicit info from craft
					smallCraftSize = lship.hangarRequired;
				}else{//classify depending on jinking limit...
					if (lship.jinkinglimit>=99){ //ultralight jinking limit is unlimited
						smallCraftSize = 'ultralight';
					}else if (lship.jinkinglimit>=10){
						smallCraftSize = 'light';
					}else if (lship.jinkinglimit>=8){
						smallCraftSize = 'medium';
					}else if (lship.jinkinglimit>=6){
						smallCraftSize = 'heavy';
					}else{
						smallCraftSize = 'NOT RECOGNIZED';
					}
				}
				//now translate size into hangar space used...
				if(smallCraftSize !=''){				
					if(lship.customFtrName){
						specialFtrAmt = lship.flightSize/lship.unitSize;
						specialFtrName = lship.customFtrName;
						specialFighters.push([specialFtrName,specialFtrAmt]);
					}
					
					if(smallCraftSize =="heavy"){
						totalFtrH += lship.flightSize/lship.unitSize;
					}else if(smallCraftSize=="medium"){ 
						totalFtrM += lship.flightSize/lship.unitSize;
					}else if(smallCraftSize=="light"){ 
						totalFtrL += lship.flightSize/lship.unitSize;
					}else if(smallCraftSize=="ultralight"){ 
						totalFtrXL += lship.flightSize/lship.unitSize;
					}else if(smallCraftSize=="assault shuttles"){ 
						totalFtrAS += lship.flightSize/lship.unitSize;
					}else{ //something other than standard fighters
						var found = false;
						for(var nh in totalFtrOther){ 					
							if (totalFtrOther[nh][0] == smallCraftSize){//this is small craft type we're looking for!
								found = true;
								totalFtrOther[nh][1] += lship.flightSize/lship.unitSize;
							}
						}
						if (found != true){ //such craft wasn't encountered yet
							totalFtrOther.push(new Array(smallCraftSize,lship.flightSize/lship.unitSize));
							smallCraftUsed.push(smallCraftSize);
						}
					}
				}
			}
			if (jumpDrivePresent == false){ //if already found there's no point
				for (var a in lship.systems){
					var sSystem = lship.systems[a];
					if (sSystem.name=='jumpEngine') jumpDrivePresent = true;
				}
			}
			if (lship.shipSizeClass >= 3) capitalShips++;
			if (lship.unofficial == true){ //as opposed to eg. 'S'
				customShipPresent = true;
				warningFound = true;
			}
			if ((lship.base == true) || (lship.osat == true)) staticPresent = true;	
			if (lship.isCombatUnit != true)  nonCombatPresent = true;	
				//check for presence of enhancements
				if (!enhancementPresent){ //if already found - no point in checking
					for (var enhNo in lship.enhancementOptions) if (!lship.enhancementOptions[enhNo][6]){ //only if enhancement isn't really an option
						if (lship.enhancementOptions[enhNo][2] > 0){
								enhancementPresent = true;
						}						
					}
				}
	    } //end of loop at ships preparing data
	    
	    

	    checkResult = "Total fleet limit: " + selectedSlot.points + "<br><br>";
	    
	    //check: overall fleet traits
	    checkResult += "Jump engine: "; //Jump Engine present?
	    if (jumpDrivePresent){
		    checkResult += " present";
	    }else{		    
		    checkResult += " NOT present! (at least one is required)";
		    problemFound = true;
	    }
	    checkResult += "<br>";
	    
	    checkResult += "Capital ships: " + capitalShips + ": "; //Capital Ship present?
	    //var capsRequired = Math.floor(selectedSlot.points/3000);//1 per 3000, round down; so 1 at 3000, 2 at 6000, 3 at 9000, 10 at 30000
	    //let's decrease the requirement at larger battles: 1 per 4000, round up, with first 2499 not counted; so 1 at 2500, 2 at 6500, 3 at 10500, 10 at 42500
	    var capsRequired = 0;	    
		if (!ancientUnitPresent){ //regular limit: one per 5000 points, starting at 3000
			if (selectedSlot.points >= 3000){
				//capsRequired = Math.ceil((selectedSlot.points-2499)/4000); //previous: one per 4000 points above 2499
				capsRequired = Math.ceil(selectedSlot.points/5000);
			}
		}else{ //Ancient-level limit: one per 15000 points, starting at 5000			
			if (selectedSlot.points >= 5000){
				capsRequired = Math.ceil(selectedSlot.points/15000);
			}
		}
	    
	    checkResult += " (min. " + capsRequired +")";
	    if (capitalShips >= capsRequired){ //tournament rules: at least 1; changed for scalability
			checkResult += " <span style='color: #33cc33;'>OK</span>";
	    }else{		    
		    checkResult += " <b><span style='color: red;'>FAILED!</span></b>";
		    problemFound = true;
	    }
	    checkResult += "<br>";
	    
	    //Ancient units present?
	    if (ancientUnitPresent){
			warningText += "<br> - Ancient unit(s) present! Seek opponent's permission first. Fleet restrictions adjusted to Ancients."; 	
			warningFound = true;
	    }
	    //Custom units present?
	    if (customShipPresent){
			warningText += "<br> - Custom unit(s) present! Seek opponent's permission first."; 	
			warningFound = true;
	    }
	    //enhanced units present?
	    if (enhancementPresent){
			warningText += "<br> - Enhancement(s) present! Seek opponent's permission first. Total value: "+totalEnhancementsValue;
			warningFound = true;
	    }
	    //unique units present?
	    if (uniqueShipPresent){
			warningText += "<br> - Unique unit(s) present! Seek opponent's permission first."; 	
			warningFound = true;
	    }
	    //unchecked variant present?
	    if (specialVariantPresent){
			warningText += "<br> - Special deployment unit(s) present! See particular unit description."; 	
			warningFound = true;
	    }
	    
	    //Static structures present?
	    if (staticPresent){
		   checkResult += "Static structures present! They're not allowed in pickup battle.<br>"; 
		   problemFound = true;
	    }
	    	    
	    //non-combat units present?
	    if (nonCombatPresent){
		   checkResult += "Non-Combat units present! They're not allowed in pickup battle.<br>"; 
		   problemFound = true;
	    }
	    
	    checkResult += "<br>";
	    
	    
	    var limit10 = Math.floor(selectedSlot.points*0.1);
	    var limit33 = Math.floor(selectedSlot.points*0.33);
	    var oneOverAllowed = false;	    
	    checkResult += "<br><u><b>Deployment restrictions:</b></u><br><br>";
	    checkResult += " - 10% bracket: " + points10 +"/" + limit10 + ": ";
	    if (points10<=limit10){
			checkResult += " <span style='color: #33cc33;'>OK</span>";
	    }else{		
		    if(units10==1 && oneOverAllowed == false){ //only 1 unit, and this exception wasn't used yet
			//oneOverAllowed = true; //re-checked rules, Restricted and Limited pools should be checked separately
			checkResult += "<span style='color: #33cc33;'>OK</span> (one single ship is allowed to break limit)";
		    }else{
			checkResult += "<b><span style='color: red;'>FAILED!</span></b> (too many points in this deployment bracket)";
		    	problemFound = true;
		    }
	    }
	    checkResult += "<br>";
	    checkResult += " - 33% bracket: " + points33 +"/" + limit33 + ": ";
	    if (points33<=limit33){
			checkResult += " <span style='color: #33cc33;'>OK</span>";
	    }else{		
		    if(units33==1 && oneOverAllowed == false){ //only 1 unit, and this exception wasn't used yet
			//oneOverAllowed = true;//re-checked rules, Restricted and Limited pools should be checked separately
			checkResult += "<span style='color: #33cc33;'>OK</span> (one single ship is allowed to break limit)";
		    }else{
			checkResult += "<b><span style='color: red;'>FAILED!</span></b> (too many points in this deployment bracket)";
		    	problemFound = true;
		    }
	    }
	    if(points10>0 && totalShips<2){
			checkResult += "<br>Restricted (10%) ship present without escort! Such a rare ship needs to be accompanied by at least one other unit, unless it's Dargan or a Minbari ship.";
			problemFound = true;
	    }	    
	    checkResult += "<br><br>";
	    
	    //variant restrictions
	    checkResult += "<br><u><b>Variant restrictions:</b></u><br><br>";
	    var limitPerHull = Math.floor(selectedSlot.points/1100); //turnament rules: 3, but it's for 3500 points
		if (ancientUnitPresent){ //Ancients have way fewer total units...
			limitPerHull = Math.floor(selectedSlot.points/3000);
		}
	    limitPerHull = Math.max(limitPerHull,2); //always allow at least 2!
	    var currRlimit = 0;
	    var currUlimit = 0;
	    var sumVar = 0;
	    for (var j in  shipTable){
		var currHull = shipTable[j];
		checkResult += " <i>" + currHull.name + "</i><br>";			
		checkResult +=  " - Total: " + currHull.Total;
		//if ((!currHull.isFtr) && (!currHull.hangarRequired)){ //fighter total is not limited; also, let's not limit units requiring hangar slots! (this isn't in the rules but I think LCV logic demands it)
		if (!currHull.hangarRequired){ //actually there MAY be hangarless fighters - they should be limited per hull (well, per flight) just like ships!
		    	checkResult +=  " (allowed " +limitPerHull+ ")";
			if (currHull.Total>limitPerHull ){
				checkResult += " <b><span style='color: red;'>TOO MANY!</span></b>";
				problemFound = true;
			} else {
				checkResult += " <span style='color: #33cc33;'>OK</span>";
			}
		}
		checkResult += "<br>";
		currRlimit = Math.ceil(currHull.Total/9);
		currUlimit = Math.ceil(currHull.Total/3);
		sumVar = currHull.R + currHull.Q + currHull.U;
		if (sumVar > 0){
			checkResult += " - Uncommon/Rare/Unique: " + sumVar + " (allowed " +currUlimit+ ")";
			if (sumVar>currUlimit){
				checkResult += " <b><span style='color: red;'>TOO MANY!</span></b>";
				problemFound = true;
			} else {
				checkResult += " <span style='color: #33cc33;'>OK</span>";
			}
			checkResult += "<br>";
		}
		sumVar = currHull.R + currHull.Q;
		if (sumVar > 0){
			checkResult += " - Rare/Unique: " + sumVar + " (allowed " +currRlimit+ ")";
			if (sumVar>currRlimit){
				checkResult += " <b><span style='color: red;'>TOO MANY!</span></b>";
				problemFound = true;
			} else {
				checkResult += " <span style='color: #33cc33;'>OK</span>";
			}
			checkResult += "<br>";
		}
		sumVar = currHull.X;
		if (sumVar > 0){
			checkResult += " - Special: "+sumVar;
			checkResult += " CORRECTNESS NOT CHECKED!";
			warningFound = true;
			checkResult += "<br>";
		}  		
	        checkResult += "<br>";
	    }
	    checkResult += "<br>";
	    
	    //total Uncommon/Rare units in fleet	    
	    var limitUTotal =  0;
	    var limitRTotal =  0;
		
		if (ancientUnitPresent){ //Ancients have way fewer total units...
			limitUTotal = Math.floor(selectedSlot.points/4000);
		} else if((selectedSlot.points-1500) > 0){
	    	limitUTotal = Math.floor((selectedSlot.points-1500)/1000); //limit Uncommon units per fleet; turnament rules: 2, but it's for 3500 points
	    }
	    limitUTotal = Math.max(limitUTotal,2); //always allow at least 2! 
	    limitRTotal = Math.floor(limitUTotal/2); //limit Rare units per fleet; turnament rules: 1, but it's for 3500 points    
		var limitUTotalResult = "<span style='color: #33cc33;'>OK</span>";
		var limitRTotalResult = "<span style='color: #33cc33;'>OK</span>";
	    if (totalU>limitUTotal){
			limitUTotalResult = " <b><span style='color: red;'>TOO MANY!</span></b>";
			//checkResult += "FAILED: You have " + totalU + " Uncommon units, out of " + limitUTotal + " allowed for fleet.<br><br>" ;
			problemFound = true;
	    }
	    if (totalR>limitRTotal){
			limitRTotalResult = " <b><span style='color: red;'>TOO MANY!</span></b>";
			//checkResult += "FAILED: You have " + totalR + " Rare/Unique units, out of " + limitRTotal + " allowed for fleet.<br><br>" ;
			problemFound = true;
	    }
		checkResult += 'Total Uncommon units: ' + totalU + ' (allowed ' + limitUTotal +') ' + limitUTotalResult +'<br>';
		checkResult += 'Total Rare/Unique units: ' + totalR + ' (allowed ' + limitRTotal +') ' + limitRTotalResult +'<br><br>';
		
	    
	    //fighters!
		//ultralights count as half a fighter when accounting for hangar space used - IF packed into something other than ultralight hangars...
		var hangarConversionNet = hangarConversionsF-hangarConversionsAS; //Positive is more fighter slots, negative if more AS.
		var totalHangarAvailable = totalHangarH+totalHangarM+totalHangarL+(totalHangarXL/2)+hangarConversionNet;
	    var minFtrRequired = Math.ceil(totalHangarAvailable/2);
	    var totalFtrPresent = totalFtrH+totalFtrM+totalFtrL+(totalFtrXL/2);
	    var totalFtrCurr = 0;
	    var totalHangarCurr = 0;

	    checkResult += "<br><b><u>Fighters:</u></b><br>";
		checkResult +=  "<br> Total Fighters: " + totalFtrPresent;
	    checkResult +=  " (select between " +minFtrRequired+ " and " + totalHangarAvailable + ")";
		if((totalFtrXL>0) || (totalHangarXL>0)){ //add disclaimer because sums will not add up straight
			checkResult += " <i>[Note - Ultralights only use half a hangar slot]</i>";
		}
		if (totalFtrPresent > totalHangarAvailable || totalFtrPresent < minFtrRequired){ //fighter total is not within limits
			checkResult += " <b><span style='color: red;'>FAILURE!</span></b>";
			problemFound = true;
		}else{
			checkResult += " <span style='color: #33cc33;'>OK</span>";
		}
		checkResult += "<br>";	    

		totalFtrCurr = totalFtrXL;
		totalHangarCurr = (totalHangarH+totalHangarM+totalHangarL+hangarConversionNet)*2 + totalHangarXL;		
		if (totalFtrCurr > 0 || totalHangarCurr > 0){ //do not show if there are no fighters/hangars in this segment
//			totalHangarCurr = (totalHangarH+totalHangarM+totalHangarL+hangarConversionNet)*2 + totalHangarXL;
			checkResult +=  " - Ultralight Fighters: " + totalFtrCurr;
			checkResult +=  " (allowed up to " + totalHangarCurr + ")";
			if((totalFtrXL>0) || (totalHangarXL>0)){ //add disclaimer because sums will not add up straight.
				checkResult += " <i>[Ultralights only require half a normal hangar slot]</i>";
			}			
			if (totalFtrCurr > totalHangarCurr){ //fighter total is not within limits
				checkResult += " <b><span style='color: red;'>TOO MANY!</span></b>";
				problemFound = true;
			}else{
				checkResult += " <span style='color: #33cc33;'>OK</span>";
			}
			checkResult += "<br>";
		}
	    
		totalFtrCurr = totalFtrL;
		totalHangarCurr = totalHangarH+totalHangarM+totalHangarL+hangarConversionNet;		
		if (totalFtrCurr > 0 || totalHangarCurr > 0){ //do not show if there are no fighters/hangars in this segment
//			totalHangarCurr = totalHangarH+totalHangarM+totalHangarL+hangarConversionNet;
			checkResult +=  " - Light Fighters: " + totalFtrCurr;
			checkResult +=  " (allowed up to " + totalHangarCurr + ")";
			if (totalFtrCurr > totalHangarCurr){ //fighter total is not within limits
				checkResult += " <b><span style='color: red;'>TOO MANY!</span></b>";
				problemFound = true;
			}else{
				checkResult += " <span style='color: #33cc33;'>OK</span>";
			}
			checkResult += "<br>";
		}
		
		totalFtrCurr = totalFtrM;
		totalHangarCurr = totalHangarH+totalHangarM+hangarConversionNet;		
		if (totalFtrCurr > 0 || totalHangarCurr > 0){ //do not show if there are no fighters/hangars in this segment
//			totalHangarCurr = totalHangarH+totalHangarM+hangarConversionNet;
			checkResult +=  " - Medium Fighters: " + totalFtrCurr;
			checkResult +=  " (allowed up to " + totalHangarCurr + ")";
			if (totalFtrCurr > totalHangarCurr){ //fighter total is not within limits
				checkResult += " <b><span style='color: red;'>TOO MANY!</span></b>";
				problemFound = true;
			}else{
				checkResult += " <span style='color: #33cc33;'>OK</span>";
			}
			checkResult += "<br>";
		}
	    
		totalFtrCurr = totalFtrH;
		totalHangarCurr = totalHangarH+hangarConversionNet;		
		if (totalFtrCurr > 0 || totalHangarCurr > 0){ //do not show if there are no fighters/hangars in this segment
//			totalHangarCurr = totalHangarH+hangarConversionNet;				
			checkResult +=  " - Heavy Fighters: " + totalFtrCurr;
				checkResult +=  " (allowed up to " + totalHangarCurr + ")";
			if (totalFtrCurr > totalHangarCurr){ //fighter total is not within limits
				checkResult += " <b><span style='color: red;'>TOO MANY!</span></b>";
				problemFound = true;
			}else{
				checkResult += " <span style='color: #33cc33;'>OK</span>";
			}
			checkResult += "<br>";
		}
		

/* //Old method of calculating Fighter slots, in case you hate the new one above - DK :)
 var totalHangarAvailable = totalHangarH+totalHangarM+totalHangarL+(totalHangarXL/2)+hangarConversionsF;
	    var minFtrRequired = Math.ceil(totalHangarAvailable/2);
	    var totalFtrPresent = totalFtrH+totalFtrM+totalFtrL+(totalFtrXL/2);
	    var totalFtrCurr = 0;
	    var totalHangarCurr = 0;
	    checkResult += "<br><b><u>Fighters:</u></b><br>";
		checkResult +=  " - Total Fighters: " + totalFtrPresent;
	    checkResult +=  " (allowed between " +minFtrRequired+ " and " + totalHangarAvailable + ")";
		if((totalFtrXL>0) || (totalHangarXL>0)){ //add disclaimer because sums will not add up straight
			checkResult += " <i>(Ultralights counted as half)</i>";
		}
		if (totalFtrPresent > totalHangarAvailable || totalFtrPresent < minFtrRequired){ //fighter total is not within limits
			checkResult += " FAILURE!";
			problemFound = true;
		}else{
			checkResult += " OK";
		}
		checkResult += "<br>";	    

		totalFtrCurr = (totalFtrXL/2)+totalFtrL+totalFtrM+totalFtrH;
		if (totalFtrCurr > 0){ //do not show if there are no fighters in this segment
			totalHangarCurr = totalHangarH+totalHangarM+totalHangarL + (totalHangarXL/2)+hangarConversionsF;
			checkResult +=  " - Ultralight / Light / Medium / Heavy Fighters: " + totalFtrCurr;
			checkResult +=  " (allowed up to " + totalHangarCurr + ")";
			if((totalFtrXL>0) || (totalHangarXL>0)){ //add disclaimer because sums will not add up straight
				checkResult += " <i>(Ultralights counted as half)</i>";
			}			
			if (totalFtrCurr > totalHangarCurr){ //fighter total is not within limits
				checkResult += " TOO MANY!";
				problemFound = true;
			}else{
				checkResult += " OK";
			}
			checkResult += "<br>";
		}
	    
		totalFtrCurr = totalFtrL+totalFtrM+totalFtrH;
		if (totalFtrCurr > 0){ //do not show if there are no fighters in this segment
			totalHangarCurr = totalHangarH+totalHangarM+totalHangarL+hangarConversionsF;
			checkResult +=  " - Light / Medium / Heavy Fighters: " + totalFtrCurr;
			checkResult +=  " (allowed up to " + totalHangarCurr + ")";
			if (totalFtrCurr > totalHangarCurr){ //fighter total is not within limits
				checkResult += " TOO MANY!";
				problemFound = true;
			}else{
				checkResult += " OK";
			}
			checkResult += "<br>";
		}
		
		totalFtrCurr = totalFtrM+totalFtrH;
		if (totalFtrCurr > 0){ //do not show if there are no fighters in this segment
			totalHangarCurr = totalHangarH+totalHangarM+hangarConversionsF;
			checkResult +=  " - Medium / Heavy Fighters: " + totalFtrCurr;
			checkResult +=  " (allowed up to " + totalHangarCurr + ")";
			if (totalFtrCurr > totalHangarCurr){ //fighter total is not within limits
				checkResult += " TOO MANY!";
				problemFound = true;
			}else{
				checkResult += " OK";
			}
			checkResult += "<br>";
		}
	    
		totalFtrCurr = totalFtrH;
		if (totalFtrCurr > 0){ //do not show if there are no fighters in this segment
			totalHangarCurr = totalHangarH+hangarConversionsF;
			checkResult +=  " - Heavy Fighters: " + totalFtrCurr;
				checkResult +=  " (allowed up to " + totalHangarCurr + ")";
			if (totalFtrCurr > totalHangarCurr){ //fighter total is not within limits
				checkResult += " FAILURE!";
				problemFound = true;
			}else{
				checkResult += " OK";
			}
			checkResult += "<br>";
		}
	
		//Lets just check Asssault shuttle/Breaching Pod capacity separately using their own variables.
		totalHangarAS = totalHangarAS+totalHangarH+totalHangarM-hangarConversionsF; //Deduct any Hangar conversions here.
		if (totalFtrAS > 0 || totalHangarAS > 0){ //do not show if there are no Assault Shuttle hangars in this segment
//			var hangarOnlyAS = totalHangarAS-hangarConversionsF;
//			var minASRequired = Math.ceil(hangarOnlyAS/2); //Commented out alternative code here that could be used to set 50% required for Assault Shuttle ships
			checkResult +=  " - Total Assault Shuttles / Breaching Pods: " + totalFtrAS;
//			checkResult +=  " (allowed between " +minASRequired+ " and " + totalHangarAS + ")";
			checkResult +=  " (allowed up to " + totalHangarAS + ")";			
//			if (totalFtrAS > totalHangarAS || totalFtrAS < minASRequired){ //Assault shuttle total is not within limits
			if (totalFtrAS > totalHangarAS){ //Asssault Shuttle total is not within limits
				checkResult += " FAILURE!";
				problemFound = true;
			}else{
				checkResult += " OK";
			}
			checkResult += "<br>";
		}		
*/ 
		//small flights (do not show if there aren't any!)
		if (noSmallFlights > 0){
			checkResult +=  " - Small Flights (< 6 craft): " + noSmallFlights;
			if (noSmallFlights>1){ //fighter total is not within limits
				checkResult += " <b><span style='color: red;'>TOO MANY!</span></b> (up to 1 allowed)";
				problemFound = true;
			}else{
				checkResult += " <span style='color: #33cc33;'>OK</span>";
			}
			checkResult += "<br>";
		}


		if ( specialFighters.length > 0 ){ //do not show if there are no fighters that require special hangars
			/*let's show details even if there are no hangars at all
			if (specialHangars.length == 0){
				checkResult += "No special hangars for special fighters. FAILURE!";
				checkResult += "<br>";
				problemFound = true;
			}else*/{ //calculate total amount and type of special fighters
				var totalSpecialFighters = [];
				specialFighters.sort();
				var idx = 0;
				while (specialFighters.length > 0) {
					if (totalSpecialFighters.length == 0) {
						totalSpecialFighters.push([specialFighters[0][0], specialFighters[0][1]]);
						specialFighters.shift();
					} else {
						if ( totalSpecialFighters[idx][0] == specialFighters[0][0] ){
							var totalFighterName = totalSpecialFighters[idx][0];
							var totalAmountToAdd = totalSpecialFighters[idx][1];
							totalAmountToAdd += specialFighters[0][1];
							totalSpecialFighters.pop();
							totalSpecialFighters.push([totalFighterName, totalAmountToAdd]);
							specialFighters.shift();
						} else {
							totalSpecialFighters.push([specialFighters[0][0],specialFighters[0][1]]);
							specialFighters.shift();
							idx++;
						}
					}
				}
				//calculate total amount and type of special hangars
				var totalSpecialHangars = [];
				specialHangars.sort();
				idx = 0;
				while (specialHangars.length > 0) {
					if (totalSpecialHangars.length == 0) {
						totalSpecialHangars.push([specialHangars[0][0], specialHangars[0][1]]);
						specialHangars.shift();
					} else {
						if ( totalSpecialHangars[idx][0] == specialHangars[0][0] ){
							var totalFighterName = totalSpecialHangars[idx][0];
							var totalAmountToAdd = totalSpecialHangars[idx][1];
							totalAmountToAdd += specialHangars[0][1];
							totalSpecialHangars.pop();
							totalSpecialHangars.push([totalFighterName, totalAmountToAdd]);
							specialHangars.shift();
						} else {
							totalSpecialHangars.push([specialHangars[0][0],specialHangars[0][1]]);
							specialHangars.shift();
							idx++;
						}
					}
				}
				/*
				console.log('Total Fighters');
				console.table(totalSpecialFighters);
				console.log('Total Hangars');
				console.table(totalSpecialHangars);
				*/
				//determine if there is enough special hangars for each type of special fighter
				for (i=0;i<totalSpecialFighters.length;i++) {
					var match = false;
					for (j=0;j<totalSpecialHangars.length;j++) {
						if (totalSpecialFighters[i][0] == totalSpecialHangars[j][0]) {
							checkResult +=  " - " + totalSpecialFighters[i][0] + ": " + totalSpecialFighters[i][1];
							checkResult +=  " (allowed up to " + totalSpecialHangars[j][1] + ")";
							if (totalSpecialFighters[i][1] > totalSpecialHangars[j][1]){ //fighter total is not within limits
								checkResult += " <b><span style='color: red;'>FAILURE!</span></b>";
								problemFound = true;
							}else{
								checkResult += " <span style='color: #33cc33;'>OK</span>";
							}
							checkResult += "<br>";
							match = true;
						} 
					}
					if (match == false) {
						checkResult +=  " - " + totalSpecialFighters[i][0] + ": " + totalSpecialFighters[i][1];
						checkResult +=  " (allowed up to 0) <b><span style='color: red;'>FAILURE!</span></b><br>";
						problemFound = true;
					}
				}
			}	
		}
		
		//make list of small craft in fleet contain only unique values...
		var smallCraftUsedUnique = smallCraftUsed.filter(function(item, pos) {
			return smallCraftUsed.indexOf(item) == pos;
		})
		
		//list each small craft size used separately!
		for(var sc in smallCraftUsedUnique){
			var scSize = smallCraftUsedUnique[sc];
			totalFtrCurr = 0;
			totalHangarCurr = 0;
			for(var nh in totalFtrOther){ 					
				if (totalFtrOther[nh][0] == scSize){//this is small craft type we're looking for!
					totalFtrCurr = totalFtrOther[nh][1] ;
				}
			}
			for(var nh in totalHangarOther){ 					
				if (totalHangarOther[nh][0] == scSize){//this is small craft type we're looking for!
					totalHangarCurr = totalHangarOther[nh][1] ;
				}
			}	
			checkResult +=  " - " + scSize + ": " + totalFtrCurr;
			if (scSize != 'Fighter Squadrons' ){ //standard
				checkResult +=  " (allowed up to " + totalHangarCurr + ")";
			}else{ //Fighter Squadrons get treated as fighters - eg. half are required
				var halfH = totalHangarCurr/2;
				checkResult +=  " (allowed between " + halfH + " and " + totalHangarCurr + ")";
			}
			if (totalFtrCurr > totalHangarCurr){ //small craft total is not within limits
				checkResult += " <b><span style='color: red;'>TOO MANY!</span></b>";
				problemFound = true;
			}else if ((scSize == 'Fighter Squadrons') && (totalFtrCurr < totalHangarCurr/2)){
				checkResult += " <b><span style='color: red;'>FAILURE!</span></b>";
				problemFound = true;
			}else{
				checkResult += " <span style='color: #33cc33;'>OK</span>";
			}
			checkResult += "<br>";				
		}
		checkResult += "<br>";		

		//Lets just check Assault shuttle/Breaching Pod capacity separately using their own variables.
		totalHangarAS = totalHangarAS-hangarConversionNet; //Deduct any Hangar conversions here.
		if (totalFtrAS > 0 || totalHangarAS > 0){ //do not show if there are no Assault Shuttles/hangars in this segment
			checkResult +=  " Total Assault Shuttles / Breaching Pods: " + totalFtrAS;
			checkResult +=  " (allowed up to " + totalHangarAS + ")";			
			if (totalFtrAS > totalHangarAS){ //Asssault Shuttle total is not within limits
				checkResult += " <b><span style='color: red;'>FAILURE!</span></b>";
				problemFound = true;
			}else{
				checkResult += " <span style='color: #33cc33;'>OK</span>";
			}
			checkResult += "<br>";
		}		
	    
	    if (warningFound){
		    checkResult = "<u>Unchecked or non-canon elements found - check text for details.</u>"+warningText+"<br><br>"+checkResult;
	    }
	    
		if (problemFound) {
			checkResult = "Overall: <b><span style='color: red; font-weight: 850;'>FAILED!</span></b><br><br>" + checkResult;
		} else {
			checkResult = "Overall: <b><span style='color: #33cc33;'>OK!</span></b><br><br>" + checkResult;
		}
	    
	    checkResult = "<b>FLEET CORRECTNESS REPORT</b><br><i>(Based on tournament rules, modified for scalability)<i><br><br>"+checkResult;   
	    
	    //alert(checkResult); //alert will be truncated by browser
	    var targetDiv = document.getElementById("fleetcheck");
	    targetDiv.style.display = "block";
	    var targetSpan = document.getElementById("fleetchecktxt");
	    targetSpan.innerHTML = checkResult;	    
	    
	    alert("Fleet check updated!");
    }, //endof function checkChoices
	

    constructFleetList: function constructFleetList() {
        var slotid = gamedata.selectedSlot;
        var selectedSlot = playerManager.getSlotById(slotid);

        $(".ship.bought").remove();
        for (var i in gamedata.ships) {
            // Reset ship ids to avoid ending up with elements with the same id
            // a number of times after you have removed a ship.
            gamedata.ships[i].id = i;

            var ship = gamedata.ships[i];
            if (ship.slot != slotid) continue;

            var h = $('<div class="ship bought slotid_' + ship.slot + ' shipid_' + ship.id + '" data-shipindex="' + ship.id + '"><span class="shiptype">' + ship.shipClass + '</span><span class="shipname name">' + ship.name + '</span><span class="pointcost">' + ship.pointCost + 'p</span><span class="remove clickable">remove</span></div>');
            h.appendTo("#fleet");
        }
        $(".ship.bought .remove").bind("click", function (e) {
            var id = $(this).parent().data('shipindex');

            for (var i in gamedata.ships) {
                if (gamedata.ships[i].id == id) {
                    gamedata.ships.splice(i, 1);
                    break;
                }
            }
            $('.ship.bought.shipid_' + id).remove();
            gamedata.calculateFleet();
            // This is done to update it immediately and more importantly,
            // to assign new id's to all fleet entries
            gamedata.constructFleetList();
        });

        gamedata.calculateFleet();
    },

    calculateFleet: function calculateFleet() {
        var slotid = gamedata.selectedSlot;
        if (!slotid) return;

        var selectedSlot = playerManager.getSlotById(slotid);
        var points = 0;
        for (var i in gamedata.ships) {
            if (gamedata.ships[i].slot != slotid) continue;

            points += gamedata.ships[i].pointCost;
        }

	    var maxPoints = selectedSlot.points;
	    var remainingPoints = maxPoints - points;

        $('.max').html(selectedSlot.points);
        $('.current').html(points);
        $('.remaining').html(remainingPoints);        
        return points;
    },


    isMyShip: function isMyShip(ship) {
        return ship.userid == gamedata.thisplayer;
    },

    orderShipListOnName: function orderShipListOnName(shipList) {
        var swapped = true;

        for (var x = 1; x < shipList.length && swapped; x++) {
            swapped = false;

            for (var y = 0; y < shipList.length - x; y++) {
                if (shipList[y + 1].shipClass < shipList[y].shipClass) {
                    var temp = shipList[y];
                    shipList[y] = shipList[y + 1];
                    shipList[y + 1] = temp;
                    swapped = true;
                }
            }
        }
    },

    /*alternate sorting method - by point value*/
    orderShipListOnPV: function orderShipListOnPV(shipList) {
        var swapped = true;

        for (var x = 1; x < shipList.length && swapped; x++) {
            swapped = false;

            for (var y = 0; y < shipList.length - x; y++) {
                if (shipList[y + 1].pointCost > shipList[y].pointCost) {
                    //top-down
                    var temp = shipList[y];
                    shipList[y] = shipList[y + 1];
                    shipList[y + 1] = temp;
                    swapped = true;
                }
            }
        }
    },

    orderStringList: function orderStringList(stringList) {
        var swapped = true;

        for (var x = 1; x < stringList.length && swapped; x++) {
            swapped = false;

            for (var y = 0; y < stringList.length - x; y++) {
                if (stringList[y + 1] < stringList[y]) {
                    var temp = stringList[y];
                    stringList[y] = stringList[y + 1];
                    stringList[y + 1] = temp;
                    swapped = true;
                }
            }
        }
    },

    parseFactions: function parseFactions(jsonFactions) {
        this.orderStringList(jsonFactions);
        var factionList = new Array();

        for (var i in jsonFactions) {
            var faction = jsonFactions[i];

            factionList[faction] = new Array();

	//August 2023: I think everyone knows You need to click to expandd - I'm putting power rating to mouseover instead! 
            //var group = $('<div id="' + faction + '" class="' + faction + ' faction shipshidden listempty" data-faction="' + faction + '"><div class="factionname name"><span>' + faction + '</span><span class="tooltip">(click to expand)</span></div>').appendTo("#store");
	var group = $('<div id="' + faction + '" class="' + faction + ' faction shipshidden listempty" data-faction="' + faction + '"><div class="factionname name"><span>' + faction + '</span><span class="tooltip">'+gamedata.getPowerRating(faction)+'</span></div>').appendTo("#store");

            group.find('.factionname').on("click", this.expandFaction);
        }

        gamedata.allShips = factionList;
    },
    
	/*old, simple version*/
	/*
	parseShips: function(jsonShips){
		for (var faction in jsonShips){
			var shipList = jsonShips[faction];
			
			this.orderShipListOnName(shipList);
			gamedata.setShipsFromFaction(faction, shipList);

			for (var index = 0; index < jsonShips[faction].length; index++){
				var ship = shipList[index];
				var targetNode = document.getElementById(ship.faction);

				var h = $('<div oncontextmenu="gamedata.onShipContextMenu(this);return false;" class="ship" data-id="'+ship.id+'" data-faction="'+ faction +'" data-shipclass="'+ship.phpclass+'"><span class="shiptype">'+ship.shipClass+'</span><span class="pointcost">'+ship.pointCost+'p</span><span class="addship clickable">Add to fleet</span></div>');
				    h.appendTo(targetNode);
			}
	
			$(".addship").bind("click", this.buyShip);
		}
	},*/
	
	/*prepares ship class name for display - will contain lots of information besides class name itself!*/
	prepareClassName: function(ship){
		//name: actualname (limited variant custom)
		//italics if actual variant!
		var displayName = ship.shipClass;
		var addOn = '';
		
		switch(ship.occurence) {
		    case 'unique':
			addOn = 'Q';
			break;
		    case 'rare':
			addOn = 'R';
			break;
		    case 'uncommon':
			addOn = 'U';
			break;
		    case 'common':
			addOn = 'C';
			break;
		    default: //assume something atypical
			addOn = 'X';
		}		
		if((ship.limited>0) && (ship.limited < 100)){ //else no such info necessary
			addOn = addOn +' '+ ship.limited + '%';
		}	
		if (ship.unofficial == 'S') {
			addOn = addOn +' '+'SEMI-CUSTOM';
		} else if(ship.unofficial == true){
			addOn = addOn +' '+'CUSTOM';
		}
		
		displayName=displayName+' ('+addOn+')';
		if(ship.variantOf !=''){
			displayName = '&nbsp;&nbsp;&nbsp;<i>'+displayName+'</i>';
		}else{
			displayName = '<b>'+displayName+'</b>';
		}
		
		return displayName;
	}, //endof prepareClassName
	
	/*prepares fleet list for purchases for display*/
	parseShips: function(jsonShips){
		for (var faction in jsonShips){
			var targetNode = document.getElementById(faction);
			var h;
			var ship;
			var shipV;
			var shipDisplayName;
			var shipList = jsonShips[faction];
			var pointCostFull = '';
			
			//this.orderShipListOnName(shipList); //alphabetical sort
			this.orderShipListOnPV(shipList); //perhaps more appropriate here, as alphabetical order will be shot to hell anyway
			
			gamedata.setShipsFromFaction(faction, shipList);
			
			//show separately: immobile objects (bases/OSATs), every ship size, fighters
			var sizeClassHeaders = ['Fighters','Medium Ships','Heavy Ships', 'Capital Ships', 'Immobile Structures'];
			for(var desiredSize=4; desiredSize>=0;desiredSize--){
				//display header
				h = $('<div class="shipsizehdr" data-faction="'+ faction +'"><span class="shiptype">'+sizeClassHeaders[desiredSize]+'</span></div>');
                    		h.appendTo(targetNode);
				for (var index = 0; index < jsonShips[faction].length; index++){
					ship = shipList[index];
					if(desiredSize==4){ //bases and OSATs, size does not matter
						if((ship.base != true) && (ship.osat != true)) continue; //check if it's a base or OSAT
				        }else if(desiredSize>0){ //ships (check actual size)
						if(ship.shipSizeClass!=desiredSize) continue;//check if it's of correct size
						if((ship.base == true) || (ship.osat == true)) continue; //check if it's not a base or OSAT
					}else{ //fighters! check max size - they should be -1, but 0 isn't used...
						if(ship.shipSizeClass>0) continue;//check if it's of correct size
						if((ship.base == true) || (ship.osat == true)) continue; //check if it's not a base or OSAT
					}
					if(ship.variantOf!='') continue;//check if it's not a variant, we're looking only for base designs here...
					//ok, display...
					shipDisplayName = this.prepareClassName(ship);
					pointCostFull = ship.pointCost;
					if (ship.flight && (ship.maxFlightSize != 1)) pointCostFull = pointCostFull + ' (' + pointCostFull/6 + ' ea.)';//for fighters: display price per craft, too!
					h = $('<div oncontextmenu="return false;" class="ship"><span class="shiptype">'+shipDisplayName+'</span><span class="pointcost">'+pointCostFull+'</span> -<span class="addship clickable">Add to fleet</span> -<span class="showship clickable">Show details</span></div>');
                    $(".addship", h).on("click", this.buyShip.bind(this, ship.phpclass));
                    $(".showship", h).on("click", gamedata.onShipContextMenu.bind(this, ship.phpclass, faction));
                        
                    h.appendTo(targetNode);
					//search for variants of the base design above...
					for (var indexV = 0; indexV < jsonShips[faction].length; indexV++){
						shipV = shipList[indexV];
						if(shipV.variantOf != ship.shipClass) continue;//that's not a variant of current base ship
						shipDisplayName = this.prepareClassName(shipV);
						pointCostFull = shipV.pointCost;
						if (shipV.flight && (shipV.maxFlightSize != 1)) pointCostFull = pointCostFull + ' (' + pointCostFull/6 + ' ea.)';//for fighters: display price per craft, too!
						h = $('<div oncontextmenu="return false;" class="ship"><span class="shiptype">'+shipDisplayName+'</span><span class="pointcost">'+pointCostFull+'</span> -<span class="addship clickable">Add to fleet</span> -<span class="showship clickable">Show details</span></div>');
                        $(".addship", h).on("click",  this.buyShip.bind(this, shipV.phpclass));
                        $(".showship", h).on("click", gamedata.onShipContextMenu.bind(this, shipV.phpclass, faction));
                        
                        h.appendTo(targetNode);
					} //end of variant
				} //end of base design
			} //end of size
			
            
		} //end of faction
	}, //endof parseShips
	

    expandFaction: function expandFaction(event) {
        var clickedElement = $(this);
        var faction = clickedElement.parent().data("faction");

        if (clickedElement.parent().hasClass("shipshidden")) {
            if (clickedElement.parent().hasClass("listempty")) {
                window.ajaxInterface.getShipsForFaction(faction, function (factionShips) {
                    gamedata.parseShips(factionShips);
                });

                clickedElement.parent().removeClass("listempty");
            }
        }

        clickedElement.parent().toggleClass("shipshidden");
    },

    goToWaiting: function goToWaiting() {},

    parseServerData: function parseServerData(serverdata) {
        if (serverdata == null) {
            window.location = "games.php";
            return;
        }

        if (!serverdata.id) return;

        gamedata.turn = serverdata.turn;
        gamedata.gamephase = serverdata.phase;
        gamedata.activeship = serverdata.activeship;
        gamedata.gameid = serverdata.id;
        gamedata.slots = serverdata.slots;
        //gamedata.ships = serverdata.ships;
        gamedata.thisplayer = serverdata.forPlayer;
        gamedata.maxpoints = serverdata.points;
        gamedata.status = serverdata.status;

        if (gamedata.status == "ACTIVE") {
            window.location = "game.php?gameid=" + gamedata.gameid;
        }

        this.createSlots();
        this.enableBuy();
        this.constructFleetList();
    },

    createNewSlot: function createNewSlot(data) {
        var template = $("#slottemplatecontainer .slot");
        var target = $("#team" + data.team + ".slotcontainer");
        var actual = template.clone(true).appendTo(target);

        actual.data("slotid", data.slot);
        actual.addClass("slotid_" + data.slot);
        gamedata.setSlotData(data);
    },

    createSlots: function createSlots() {
        var selectedSlot = playerManager.getSlotById(gamedata.selectedSlot);
        if (selectedSlot && selectedSlot.playerid != gamedata.thisplayer) {
            $('.slot.slotid_' + selectedSlot.slot).removeClass("selected");
            gamedata.selectedSlot = null;
        }

        for (var i in gamedata.slots) {
            var slot = gamedata.slots[i];
            var slotElement = $('.slot.slotid_' + slot.slot);

            if (!slotElement.length) {
                gamedata.createNewSlot(slot);
            }

            slotElement = $('.slot.slotid_' + slot.slot);
            var data = slotElement.data();
            if (playerManager.isOccupiedSlot(slot)) {
                var player = playerManager.getPlayerInSlot(slot);
                slotElement.data("playerid", player.id);
                slotElement.addClass("taken");
                $(".playername", slotElement).html(player.name);

                if (slot.lastphase == "-2") {
                    slotElement.addClass("ready");
                }

                if (player.id == gamedata.thisplayer) {
                    if (gamedata.selectedSlot == null) gamedata.selectedSlot = slot.slot;
                    $(".leaveslot", slotElement).show();
                } else $(".leaveslot", slotElement).hide();
            } else {
                $(".leaveslot", slotElement).hide();

                slotElement.attr("data-playerid", "");
                slotElement.removeClass("taken");
                $(".playername", slotElement).html("");

                slotElement.removeClass("ready");
            }

            if (gamedata.selectedSlot == slot.slot) {
                gamedata.selectSlot(slot);
            }
        }
    },

    setSlotData: function setSlotData(data) {
        var slot = $(".slot.slotid_" + data.slot);
        $(".name", slot).html(data.name);
        $(".points", slot).html(data.points);

        $(".depx", slot).html(data.depx);
        $(".depy", slot).html(data.depy);
        $(".deptype", slot).html(data.deptype);
        $(".depwidth", slot).html(data.depwidth);
        $(".depheight", slot).html(data.depheight);
        $(".depavailable", slot).html(data.depavailable);
    },

    clickTakeslot: function clickTakeslot() {
        var slot = $(".slot").has($(this));
        var slotid = slot.data("slotid");
	    
	//block if player already has confirmed fleet (in any slot)
	for (var i in gamedata.slots)  { //check all slots
		var checkSlot = gamedata.slots[i];
		if (checkSlot.lastphase == "-2") { //this slot has ready fleet
			var player = playerManager.getPlayerInSlot(checkSlot);
			if (player.id == gamedata.thisplayer){
				window.confirm.error("You have already confirmed Your fleet for this game!", function () {});
				return;
			}
		}
	}
	    
        ajaxInterface.submitSlotAction("takeslot", slotid);
    },

    onLeaveSlotClicked: function onLeaveSlotClicked() {
        var slot = $(".slot").has($(this));
        var slotid = slot.data("slotid");
	    
	//block if player already has confirmed fleet (in this slot)
	if (slot.lastphase == "-2") { 
		window.confirm.error("You have already confirmed Your fleet for this game!", function () {});
		return;
	}
	    
	ajaxInterface.submitSlotAction("leaveslot", slotid);
	window.location = "games.php";
    },

    enableBuy: function enableBuy() {
        var selectedSlot = playerManager.getSlotById(gamedata.selectedSlot);
        if (selectedSlot && selectedSlot.playerid == gamedata.thisplayer) {
            $(".buy").show();
        } else {
            $(".buy").hide();
        }
    },

    buyShip: function buyShip(shipclass) {
        var ship = gamedata.getShipByType(shipclass);

        var slotid = gamedata.selectedSlot;
        var selectedSlot = playerManager.getSlotById(slotid);
        if (selectedSlot.lastphase == "-2") {
            window.confirm.error("This slot has already bought a fleet!", function () {});
            return false;
        }

        $(".confirm").remove();

        //		if (gamedata.canAfford(ship)){
        window.confirm.showShipBuy(ship, gamedata.doBuyShip);
        //		}else{
        //			window.confirm.error("You cannot afford that ship!", function(){});
        //		}
    },

    doBuyShip: function doBuyShip() {
        var shipclass = $(this).data().shipclass;
        var ship = gamedata.getShipByType(shipclass);

        var name = $(".confirm input").val();
        ship.name = name;
        ship.userid = gamedata.thisplayer;

        if ($(".confirm .totalUnitCostAmount").length > 0) {
            ship.pointCost = $(".confirm .totalUnitCostAmount").data("value");			
        }

        if (!gamedata.canAfford(ship)) {
            $(".confirm").remove();
            window.confirm.error("You cannot afford that ship!", function () {});
            return;
        }

        if (ship.flight) {
            var flightSize = $(".fighterAmount").html();
            if (!flightSize) {
                flightSize = 1;
            }
            ship.flightSize = Math.floor(flightSize);
        }
	    
		//do note enhancements bought (if any)
		var enhNo = 0;
		var noTaken = 0;
		var target = $(".selectAmount.shpenh" + enhNo);
		while(typeof target.data("enhPrice") != 'undefined'){ //as long as there are enhancements defined...
			noTaken = target.data("count");
			if(noTaken > 0){ //enhancement picked - note!
				ship.enhancementOptions[enhNo][2] = noTaken;
				if(!ship.enhancementOptions[enhNo][6]){ //this is an actual enhancement (as opposed to option) - note value!
					if (ship.flight){
						ship.pointCostEnh += target.data("enhCost") * flightSize;
					} else {
						ship.pointCostEnh += target.data("enhCost");
					}
				}else{ //this is an option - still note value, just separately!
					if (ship.flight){
						ship.pointCostEnh2 += target.data("enhCost") * flightSize;
					} else {
						ship.pointCostEnh2 += target.data("enhCost");
					}
				}
			}
			//go to next enhancement
			enhNo++;
			target = $(".selectAmount.shpenh" + enhNo);
		}	    

        if ($(".confirm .selectAmount").length > 0) {
            if (ship.flight) {

                // and get the amount of launchers on a fighter
                var nrOfLaunchers = 0;

                for (var j in ship.systems[1].systems) {
                    var fighterSystem = ship.systems[1].systems[j];

                    if (!mathlib.arrayIsEmpty(fighterSystem.firingModes) && fighterSystem.missileArray != null) {
                        nrOfLaunchers++;
                    }
                }

                // get all selections of missiles
                var missileOptions = $(".confirm .selectAmount");

                for (var k = 0; k < missileOptions.length; k++) {
                    var firingMode = $(missileOptions[k]).data("firingMode");

                    // divide the bought missiles over the missileArrays
                    var boughtAmount = $(".confirm .selectAmount." + firingMode).data("value");

                    // perLauncher should always get you an integer as result. The UI handles
                    // buying of missiles that way.
                    var perLauncher = boughtAmount;

                    for (var i in ship.systems) {
                        var fighter = ship.systems[i];

                        for (var j in fighter.systems) {
                            var fighterSystem = fighter.systems[j];

                            if (!mathlib.arrayIsEmpty(fighterSystem.firingModes) && fighterSystem.missileArray != null) {
                                // find the correct index, depending on the firingMode
                                for (var index in fighterSystem.firingModes) {
                                    if (fighterSystem.firingModes[index] == firingMode) {
                                        fighterSystem.missileArray[index].amount = perLauncher;
                                    }
                                }
                            }
                        }
                    }
                }
            } else {}
        }

        $(".confirm").remove();
        gamedata.updateFleet(ship);
    },

    //        arrayIsEmpty: function(array){
    //            for(var i in array){
    //                return false;
    //            }
    //
    //            return true;
    //        },

    getShipByType: function getShipByType(type) {

        for (var race in gamedata.allShips) {
            for (var i in gamedata.allShips[race]) {
                var ship = gamedata.allShips[race][i];

                if (ship.phpclass == type) {
                    var shipRet = jQuery.extend(true, {}, ship);

                    // to avoid two different flights pointing to the
                    // same fighter object, also extend each fighter
                    // individually. (This solves the bug of setting
                    // missile amounts, that suddenly are set for all
                    // the fighters of the same type.)
                    for (var i in shipRet.systems) {
                        shipRet.systems[i] = jQuery.extend(true, {}, ship.systems[i]);

                        if (shipRet.flight) {
                            // in case of a flight, also do the systems of the fighters
                            for (var j in shipRet.systems[i].systems) {
                                shipRet.systems[i].systems[j] = jQuery.extend(true, {}, ship.systems[i].systems[j]);
                            }
                        } else {
                            // to avoid problems with ammo and normal ships, also do the
                            // ship systems

                        }
                    }

                    return shipRet;
                }
            }
        }

        return null;
    },

	onReadyClicked: function onReadyClicked() {
	    var points = gamedata.calculateFleet();

		//block if player already has confirmed fleet (in any slot)
		for (var i in gamedata.slots)  { //check all slots
			var checkSlot = gamedata.slots[i];
			if (checkSlot.lastphase == "-2") { //this slot has ready fleet
				var player = playerManager.getPlayerInSlot(checkSlot);
				if (player.id == gamedata.thisplayer){
					window.confirm.error("You have already confirmed Your fleet for this game!", function () {});
					return;
				}
			}
		}

	    if (points == 0) {
	        window.confirm.error("You have to buy at least one ship!", function () {});
	        return;
	    }
	    // Pass the submission function as a callback, not invoke it immediately
	    confirm.confirm("Are you sure you wish to ready your fleet?", function () {
	        ajaxInterface.submitGamedata();
	    });
	},

    onLeaveClicked: function onLeaveClicked() {
        window.location = "gamelobby.php?gameid=" + gamedata.gameid + "&leave=true";
    },

    onSelectSlotClicked: function onSelectSlotClicked(e) {
        var slotElement = $(".slot").has($(this));
        var slotid = slotElement.data("slotid");
        var slot = playerManager.getSlotById(slotid);

        if (slot.playerid == gamedata.thisplayer) gamedata.selectSlot(slot);
    },

    selectSlot: function selectSlot(slot) {
        $(".slot").removeClass("selected");

        $(".slot.slotid_" + slot.slot).addClass("selected");
        gamedata.selectedSlot = slot.slot;
        this.constructFleetList();
    },

    onShipContextMenu: function onShipContextMenu(phpclass, faction) {

        var ship = gamedata.getShip(phpclass, faction);

        if (!ship.shipStatusWindow) {
            if (ship.flight) {
                ship.shipStatusWindow = flightWindowManager.createShipWindow(ship);
            } else {
                ship.shipStatusWindow = shipWindowManager.createShipWindow(ship);
            }

            shipWindowManager.setData(ship);
        }

        shipWindowManager.open(ship);
        return false;
    },

    getShip: function getShip(phpclass, faction) {
    	var actPhpclass;
    	var actFaction;
    	if (faction != null ){ //faction provided
			actPhpclass = phpclass;
			actFaction = faction;
			gamedata.displayedShip = phpclass;
			gamedata.displayedFaction = faction;
    	}else{ //recall last opened!
 			actPhpclass = gamedata.displayedShip;
			actFaction =  gamedata.displayedFaction;
    	}
	    
        if (! gamedata.allShips[actFaction]) {
            throw new Error("Unable to find faction " + actFaction)
        }

        return gamedata.allShips[actFaction].find(ship => ship.phpclass == actPhpclass);
    },

    setShipsFromFaction: function setShipsFromFaction(faction, jsonShips) {
        gamedata.allShips[faction] = Object.keys(window.staticShips[faction]).map(function(shipClass) {
            return new Ship(window.staticShips[faction][shipClass]);
        })
    }

};

window.animation = {
    animateWaiting: function animateWaiting() {}
};

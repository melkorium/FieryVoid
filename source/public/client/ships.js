"use strict";

window.shipManager = {
/*
    shipImages: Array(),
    initiated: 0,
    
    initShips: function initShips() {
        if (window.webglScene) {
            return;
        }

        shipManager.initiated = 1;
        for (var i in gamedata.ships) {
            shipManager.createHexShipDiv(gamedata.ships[i]);
        }
        shipManager.initiated = 2;
        shipManager.drawShips();
    },

    createHexShipDiv: function createHexShipDiv(ship) {

        if (ship.htmlContainer) return;

        var e = $("#pagecontainer #hexship_" + ship.id + ".hexship");

        if (!e.length) {

            e = $("#templatecontainer .hexship");
            e.attr("id", "hexship_" + ship.id);
            var s = shipManager.getShipCanvasSize(ship);
            var w = s;
            var h = s;
            $("canvas.hexshipcanvas", e).attr("id", "shipcanvas_" + ship.id).attr("width", w).attr("height", h);
            var n = e.clone(true).appendTo("#pagecontainer");
            n.data("ship", ship.id);
            ship.htmlContainer = $("#pagecontainer #hexship_" + ship.id);
            ship.shipclickableContainer = $('<div oncontextmenu="shipManager.onShipContextMenu(this);return false;" class="shipclickable ship_' + ship.id + '"></div>').appendTo("#pagecontainer");
            ship.shipclickableContainer.data("id", ship.id);
            ship.shipclickableContainer.on("dblclick", shipManager.onShipDblClick);
            ship.shipclickableContainer.on("click", shipManager.onShipClick);
            ship.shipclickableContainer.on("mouseover", shipClickable.shipclickableMouseOver);
            ship.shipclickableContainer.on("mouseout", shipClickable.shipclickableMouseOut);
            if (ship.flight) {
                ship.shipStatusWindow = flightWindowManager.createShipWindow(ship);
            } else {
                ship.shipStatusWindow = shipWindowManager.createShipWindow(ship);
            }

            shipWindowManager.setData(ship);
            $("canvas.hexshipcanvas", e).attr("id", "shipcanvas_");
            e.attr("id", "hexship_");
            var img = new Image();
            img.src = ship.imagePath;
            shipManager.shipImages[ship.id] = {
                orginal: img,
                modified: null,
                rolled: null,
                drawData: Array()
            };
            $(shipManager.shipImages[ship.id].orginal).on("load", function () {
                shipManager.shipImages[ship.id].orginal.loaded = true;
            });
        } else {
            ship.htmlContainer = e;
            ship.shipclickableContainer = $(".shipclickable.ship_" + ship.id);
            ship.shipStatusWindow = $(".shipwindow.ship_" + ship.id);
            shipWindowManager.setData(ship);
        }

        if (shipManager.isDestroyed(ship)) ship.dontDraw = true;
    },

    drawShips: function drawShips() {

        if (shipManager.initiated == 0) {
            shipManager.initShips();
            return;
        }

        if (shipManager.initiated == 1) return;

        for (var i in gamedata.ships) {
            shipManager.drawShip(gamedata.ships[i]);
        }
    },

    /*
    drawShip: function(ship){
         if (shipManager.initiated == 0){
            shipManager.initShips();
            return;
        }
         if (shipManager.initiated == 1)
            return;
         if (gamedata.gamephase != -1){
            if (ship.dontDraw || ship.unavailable){
                ship.shipclickableContainer.css("z-index", "1");
                ship.htmlContainer.hide();
                return;
            }   
        }
        //graphics.clearCanvas("shipcanvas_" + ship.id);
        var canvas = window.graphics.getCanvas("shipcanvas_" + ship.id);
         canvas.fillStyle    = hexgrid.hexlinecolor;;
        canvas.font         = 'italic 12px sans-serif';
        canvas.textBaseline = 'top';
         var pos = shipManager.getShipPositionForDrawing(ship);
           var s = shipManager.getShipCanvasSize(ship);
        var h = Math.round(s/2)
        var hexShipZ = 1000; //+ship.id;
        var scZ = 4500;//+ship.id;
        if (gamedata.mouseOverShipId == ship.id){
            hexShipZ+=500;
            scZ+=500;
        }
         if (gamedata.activeship == ship.id || gamedata.isSelected(ship)){
            hexShipZ+=250;
            scZ+=250;
        }
        ship.htmlContainer.css("top", pos.y -h + "px").css("left", pos.x -h + "px").css("z-index", hexShipZ);
        ship.htmlContainer.show();
         var img = damageDrawer.getShipImage(ship);
         var sc = ship.shipclickableContainer;
        scSize = s*0.15*gamedata.zoom;
        sc.css("width", scSize+"px");
        sc.css("height", scSize+"px");
        sc.css("left", ((pos.x) - (scSize*0.5))+"px");
        sc.css("top", ((pos.y) - (scSize*0.5))+"px");
        sc.css("z-index", scZ);
           //console.log("gamedata.gamephase: " + gamedata.gamephase + " gamedata.activeship: " + gamedata.activeship + " ship.id: " + ship.id);
        if (gamedata.gamephase == 2 && gamedata.activeship == ship.id && gamedata.animating == false && gamedata.waiting == false && gamedata.isMyShip(ship))
            UI.shipMovement.drawShipMovementUI(ship);
         if (gamedata.gamephase == -1 && gamedata.isMyShip(ship) && gamedata.isSelected(ship))
            UI.shipMovement.drawShipMovementUI(ship);
         if (gamedata.gamephase == 3 && ship.flight && gamedata.isSelected(ship))
            UI.shipMovement.drawShipMovementUI(ship);
         if (img.loaded){
            shipManager.doDrawShip(canvas, s, ship, img);
        }else{
            $(img).on("load", function(){
                img = damageDrawer.getShipImage(ship);
                if (img.loaded){
                    shipManager.doDrawShip(canvas, s, ship, img);
                }else{
                    $(img).on("load", function(){
                        img = damageDrawer.getShipImage(ship);
                        shipManager.doDrawShip(canvas, s, ship, img);
                    });
                }
            });
        }
     },
     doDrawShip: function(canvas, s, ship, img){
        var dew = ew.getDefensiveEW(ship);
        if (ship.flight)
    dew = shipManager.movement.getJinking(ship);
         var ccew = ew.getCCEW(ship);
         var shipdrawangle = shipManager.getShipHeadingAngleForDrawing(ship);
        var selected = gamedata.isSelected(ship);
        var mouseover = (gamedata.mouseOverShipId == ship.id);
         if (ship.drawn && shipdrawangle == ship.shipdrawangle && ship.drawnzoom == gamedata.zoom
    && ship.drawmouseover == mouseover && ship.drawselected == selected && ship.drawDamage == false
    && ship.drawDEW == dew && ship.drawCCEW == ccew)
    {
    return;
        }
          var myship = gamedata.isMyShip(ship);
        //console.log("draw");
        canvas.clearRect(0, 0, s, s);
    if ((selected && myship && gamedata.gamephase == 1) || (mouseover && gamedata.gamephase > 1) || (mouseover && myship)){
    if (gamedata.zoom > 0){
    	if (dew > 0){
    	dew = Math.ceil(( dew )*gamedata.zoom*0.5);
    	canvas.strokeStyle = "rgba(144,185,208,0.40)";
    	graphics.drawCircle(canvas, s/2, s/2, s*0.18*gamedata.zoom, dew);
    }
    	if (ccew > 0){
    	ccew = Math.ceil(( ccew )*gamedata.zoom*0.5);
                    if (myship)
                    {
                        canvas.strokeStyle = "rgba(20,80,128,0.50)";
                    }
                    else
                    {
                        canvas.strokeStyle = "rgba(179,65,25,0.50)";
                    }
    		graphics.drawCircle(canvas, s/2, s/2, ((s*0.18*gamedata.zoom)+(dew*0.5) + (ccew*0.5) + 2), ccew);
    }
    }
    }
    if (selected && !mouseover && !(gamedata.gamephase == 2 && ship.id == gamedata.activeship)) {
    canvas.strokeStyle = "rgba(144,185,208,0.40)";
    canvas.fillStyle = "rgba(255,255,255,0.18)";
    graphics.drawCircleAndFill(canvas, s/2, s/2, s*0.15*gamedata.zoom+1, 1);
    }else if ( mouseover ){
    if (gamedata.isMyShip(ship)){
    canvas.strokeStyle = "rgba(86,200,45,0.60)";
    canvas.fillStyle = "rgba(50,122,24,0.50)";
    }else{
    canvas.strokeStyle = "rgba(229,87,38,0.60)";
    canvas.fillStyle = "rgba(179,65,25,0.50)";
    }
    graphics.drawCircleAndFill(canvas, s/2, s/2, s*0.15*gamedata.zoom+1, 1);
    }
    if (gamedata.isTargeted(ship)) {
    canvas.strokeStyle = "rgba(144,185,208,0.40)";
    canvas.fillStyle = "rgba(255,255,255,0.18)";
    graphics.drawCircleAndFill(canvas, s/2, s/2, s*0.15*gamedata.zoom+1, 1);
    }
         var rolled = shipManager.movement.isRolled(ship);
    graphics.drawAndRotate(canvas, s, s, s*gamedata.zoom, s*gamedata.zoom, shipdrawangle, img, rolled);
         if (mouseover
            && (!gamedata.isMyShip(ship) || gamedata.gamephase != 2 || gamedata.activeship != ship.id)){
             canvas.strokeStyle = "rgba(86,200,45,0.90)";
            canvas.fillStyle = "rgba(50,122,24,0.70)";
             var c = Math.floor(s/2);
            var a = shipManager.getShipDoMAngle(ship);
            var r = s*0.18*gamedata.zoom;
            var p = mathlib.getPointInDirection(r, a , c, c);
            //graphics.drawCircleAndFill(canvas, p.x, p.y, 5*gamedata.zoom, 2);
            graphics.drawArrow(canvas, p.x, p.y, a, 30, 1);
        }
    ship.shipdrawangle = shipdrawangle;
    ship.drawn = true;
    ship.drawnzoom = gamedata.zoom;
    ship.drawselected = selected;
    ship.drawmouseover = mouseover;
    ship.drawDamage = false;
    ship.drawDEW = dew;
    ship.drawCCEW = ccew;
      },
    
    getShipCanvasSize: function getShipCanvasSize(ship) {
        return ship.canvasSize;
    },

    hasAnimationsDone: function hasAnimationsDone(ship) {

        for (var i in ship.movement) {
            movement = ship.movement[i];
            if (movement.animated == false || movement.commit == false) {
                return false;
            }
        }

        return true;
    },
*/
    getShipDoMAngle: function getShipDoMAngle(ship) {
        var d = shipManager.movement.getLastCommitedMove(ship).heading;
        if (d == 0) {
            return 0;
        }
        if (d == 1) {
            return 60;
        }
        if (d == 2) {
            return 120;
        }
        if (d == 3) {
            return 180;
        }
        if (d == 4) {
            return 240;
        }
        if (d == 5) {
            return 300;
        }
    },

    getShipHeadingAngle: function getShipHeadingAngle(ship) {

        var d = shipManager.movement.getLastCommitedMove(ship).facing;
        if (d == 0) {
            return 0;
        }
        if (d == 1) {
            return 60;
        }
        if (d == 2) {
            return 120;
        }
        if (d == 3) {
            return 180;
        }
        if (d == 4) {
            return 240;
        }
        if (d == 5) {
            return 300;
        }
    },

    /*
    getShipHeadingAngleForDrawing: function(ship){
         var movement = null;
        for (var i in ship.movement){
            movement = ship.movement[i];
            if (movement.animated == true)
                continue;
             if (movement.type=="turnleft" || movement.type=="turnright"){
                var last = ship.movement[i-1];
                if (!last)
                    return shipManager.getShipHeadingAngle(ship);
                 var lastheading = mathlib.hexFacingToAngle(last.facing);
                var destination = mathlib.hexFacingToAngle(movement.facing);
                var perc = movement.animationtics / animation.turningspeed;
                 var right = (movement.type=="turnright");
                 return mathlib.getFacingBetween(lastheading, destination, perc, right);
             }
             if (movement.type=="pivotleft" || movement.type=="pivotright"){
                var last = ship.movement[i-1];
                if (!last)
                    return shipManager.getShipHeadingAngle(ship);
                 var lastheading = mathlib.hexFacingToAngle(last.facing);
                var destination = mathlib.hexFacingToAngle(movement.facing);
                var perc = movement.animationtics / animation.turningspeed;
                 var right = (movement.type=="pivotright");
                 return mathlib.getFacingBetween(lastheading, destination, perc, right);
             }
             break;
         }
          return shipManager.getShipHeadingAngle(ship);
     },
    */

    /*
    getShipPositionInTurn: function getShipPositionInTurn(ship, turn) {

        if (turn <= 0) turn = 1;

        var movement = null;

        for (var i in ship.movement) {
            if (ship.movement[i].turn === turn) {
                movement = ship.movement[i];
            }
        }

        if (movement === null && ship.movement.length > 0) {
            movement = ship.movement[ship.movement.length - 1];
        }

        var x = movement.x;
        var y = movement.y;
        var xO = movement.xOffset;
        var yO = movement.yOffset;
        return { x: x, y: y, xO: xO, yO: yO };
    },
    */

    getShipPosition: function getShipPosition(ship) {
        var movement = shipManager.movement.getLastCommitedMove(ship);
        return new hexagon.Offset(movement.position);
    },

    getShipPositionForDrawing: function getShipPositionForDrawing(ship) {
        var movement = null;
        for (var i in ship.movement) {
            if (ship.movement[i].commit == false) break;

            movement = ship.movement[i];

            if (movement.animated == true) continue;

            if (movement.type == "move" || movement.type == "slipright" || movement.type == "slipleft") {
                var last = ship.movement[i - 1];

                if (!last) {
                    break;
                }
                var lastpos = hexgrid.hexCoToPixel(last.x, last.y);
                lastpos.x = lastpos.x + last.xOffset * gamedata.zoom;
                lastpos.y = lastpos.y + last.yOffset * gamedata.zoom;
                var destination = hexgrid.hexCoToPixel(movement.x, movement.y);
                destination.x = destination.x + movement.xOffset * gamedata.zoom;
                destination.y = destination.y + movement.yOffset * gamedata.zoom;
                var perc = movement.animationtics / animation.movementspeed;

                return mathlib.getPointBetween(lastpos, destination, perc);
            }

            break;
        }

        var x = movement.x;
        var y = movement.y;

        var lastpos = hexgrid.hexCoToPixel(x, y);
        lastpos.x = Math.floor(lastpos.x + movement.xOffset * gamedata.zoom);
        lastpos.y = Math.floor(lastpos.y + movement.yOffset * gamedata.zoom);
        return lastpos;
    },

    onShipContextMenu: function onShipContextMenu(e) {
        var id = $(e).data("id");
        var ship = gamedata.getShip(id);

        if (shipSelectList.haveToShowList(ship, e)) {
            shipSelectList.showList(ship);
        } else {
            shipManager.doShipContextMenu(ship);
        }
    },

    doShipContextMenu: function doShipContextMenu(ship) {

        shipSelectList.remove();

        if (shipManager.isDestroyed(ship)) return;

        if (ship.userid == gamedata.thisplayer && (gamedata.gamephase == 1 || gamedata.gamephase > 2)) {
            shipWindowManager.open(ship);
            gamedata.selectShip(ship, false);
            gamedata.shipStatusChanged(ship);
            drawEntities();
        } else {
            shipWindowManager.open(ship);
        }
        return false;
    },

    onShipDblClick: function onShipDblClick(e) {},

    onShipClick: function onShipClick(e) {
        //console.log("click on ship");

        if (!e || e.which !== 1) return;

        e.stopPropagation();
        var id = $(this).data("id");
        var ship = gamedata.getShip(id);

        if (shipSelectList.haveToShowList(ship, e)) {
            shipSelectList.showList(ship);
        } else {
            shipManager.doShipClick(ship);
        }
    },

    doShipClick: function doShipClick(ship) {

        shipSelectList.remove();

        if (ship == null) {
            return;
        }

        if (gamedata.thisplayer == -1) return;

        if (shipManager.isDestroyed(ship)) return;

        if (gamedata.gamephase == 2) return;

        if (gamedata.waiting) return;

        if (ship.userid == gamedata.thisplayer) {
            gamedata.selectShip(ship, false);
        }

        if (ship.userid != gamedata.thisplayer && gamedata.gamephase == 3) {
            weaponManager.targetShip(ship, false);
        }

        if (gamedata.gamephase == 1 && ship.userid != gamedata.thisplayer) {
            if (gamedata.selectedSystems.length > 0) {
                weaponManager.targetShip(ship, false);
            } else if (!ship.flight) {
                ew.AssignOEW(ship);
            }
        }
        gamedata.shipStatusChanged(ship);
        drawEntities();
        //scrolling.scrollToShip(ship);
    },

    getPrimaryCnC: function getPrimaryCnC(ship) {
        var cncs = [];

        for (var system in ship.systems) {
            if (ship.systems[system].displayName == "C&C") {
                cncs.push(ship.systems[system]);
            }
        }

        cncs.sort(function (a, b) {
            if (shipManager.systems.getRemainingHealth(a) > shipManager.systems.getRemainingHealth(b)) {
                return 1;
            } else {
                return -1;
            }
        });

        var primary = cncs[0];

        return primary;
    },

    isDisabled: function isDisabled(ship) {
        if (ship.base) {
            var primary = shipManager.getPrimaryCnC(ship);

            if (!shipManager.criticals.hasCriticalOnTurn(primary, "ShipDisabledOneTurn", gamedata.turn - 1) || !shipManager.criticals.hasCriticalOnTurn(primary, "ShipDisabled", gamedata.turn - 1)) {
                return false;
            }
        } else {
            for (var i = 0; i < ship.systems.length; i++) {
                if (ship.systems[i].displayName == "C&C") {
                    if (shipManager.criticals.hasCriticalOnTurn(ship.systems[i], "ShipDisabledOneTurn", gamedata.turn - 1) || shipManager.criticals.hasCriticalOnTurn(ship.systems[i], "ShipDisabled", gamedata.turn - 1)) {
                        return true;
                    }
                }
            }
        }
        return false;
    },

	//Used by RelayAnimationStrategy to check if ship has jumped, if so different destroyed sprite
	hasJumpedNotDestroyed: function (ship) {
		var hasJumped = false;
	    // Check if the ship has a jump engine
	    const jumpEngine = shipManager.systems.getSystemByName(ship, "jumpEngine");
	    
	    // If no jump engine, ship cannot jump
	    if (!jumpEngine) {
	        return false;
	    }
	    
	    // Check if the jump engine is boosted
	    var boostedJump = shipManager.power.isBoosted(ship, jumpEngine);
	    if(!boostedJump) return false; //Hasn't boosted jump engine, it cannot have jumped.
	    	
		//Check damage entries, and remove Hyperspace jump entry, to see if ship was 'destroyed' by jumping not actual damage.	    
		var struct = shipManager.systems.getStructureSystem(ship, 0);
            var maxHealth = struct.maxhealth;
            var totalDamage = 0;
            var thisDamage = null;
            for (var i in struct.damage) {
                thisDamage = struct.damage[i];
                if (thisDamage.damageclass !== 'HyperspaceJump') totalDamage += thisDamage.damage; //Only count non-jump damage, as jumping destroys ship anyway.
            }
            
        if(totalDamage < maxHealth) return true; //The other damage sustained has not destroyed this ship, jumping has.
        
        return hasJumped;
	},
	
		
    isDestroyed: function isDestroyed(ship) {

        if (ship == null) {
            return;
        }

        if (ship.flight) {
            for (var i in ship.systems) {
                var fighter = ship.systems[i];
                if (!shipManager.systems.isDestroyed(ship, fighter)) {
                    return false;
                }
            }
            return true;
        } else {
            if (!ship.base) {
                var stru = shipManager.systems.getStructureSystem(ship, 0);
                if (shipManager.systems.isDestroyed(ship, stru)) {
                    return true;
                }

                var react = shipManager.systems.getSystemByName(ship, "reactor");
                if (shipManager.systems.isDestroyed(ship, react)) {
                    return true;
                }
            } else {
                var stru = shipManager.systems.getStructureSystem(ship, 0);
                if (shipManager.systems.isDestroyed(ship, stru)) {
                    return true;
                }

                var mainReactor = shipManager.systems.getSystemByNameInLoc(ship, "reactor", 0);
                if (shipManager.systems.isDestroyed(ship, mainReactor)) {
                    return true;
                }
            }
        }

        return false;
    },

    getStructuresDestroyedThisTurn: function getStructuresDestroyedThisTurn(ship) {

        var array = [];

        for (var j = 0; j < ship.systems.length; j++) {
            system = ship.systems[j];
            if (system.displayName == "Structure" && system.location != 0) {
                if (system.destroyed) {
                    for (var k = 0; k < system.damage.length; k++) {
                        var dmg = system.damage[k];
                        if (dmg.destroyed) {
                            if (gamedata.turn == dmg.turn) {
                                array.push(system);
                                break;
                            }
                        }
                    }
                }
            }
        }

        if (array.length > 0) {
            return array;
        } else return null;
    },

    getOuterReactorDestroyedThisTurn: function getOuterReactorDestroyedThisTurn(ship) {

        var array = [];

        for (var j = 0; j < ship.systems.length; j++) {
            system = ship.systems[j];
            if (system.displayName == "Reactor" && system.location != 0) {
                if (system.destroyed) {
                    for (var k = 0; k < system.damage.length; k++) {
                        var dmg = system.damage[k];
                        if (dmg.destroyed) {
                            if (gamedata.turn == dmg.turn) {
                                array.push(system);
                                break;
                            }
                        }
                    }
                }
            }
        }

        if (array.length > 0) {
            return array;
        } else return null;
    },

    isAdrift: function isAdrift(ship) {
        if (ship.flight || ship.osat || ship.base) return false;

        if (shipManager.criticals.hasCriticalInAnySystem(ship, "ShipDisabledOneTurn") || shipManager.criticals.hasCriticalInAnySystem(ship, "ShipDisabled")) return true;

        if (shipManager.systems.isDestroyed(ship, shipManager.systems.getSystemByName(ship, "cnC"))) {
            return true;
        }
        
        /*ship without power (power deficit or Reactor shutdown critical) is adrift as well*/
	/*...after consulting rulebook - it isn't!*/
	/*
        //isPowerless already checks for appropriate critical, actually
        if (shipManager.power.isPowerless(ship)) return true;
        */
	    
        return false;
    },

    isEngineless: function isEngineless(ship) {
        var engines = [];
        for (var sys in ship.systems) {
            if (ship.systems[sys].displayName == "Engine") {
                engines.push(ship.systems[sys]);
            }
        }

        for (var i = 0; i < engines.length; i++) {
            if (engines[i].destroyed == false) {
                return false;
            }
        }

        return true;
    },

    getTurnDestroyed: function getTurnDestroyed(ship) {
        var turn = null;
		if (!shipManager.isDestroyed(ship)) return null; //if ship is not destroyed then it's not destroyed :)
        if (ship.flight) {

            var fightersSurviving = ship.systems.some(function (fighter) {
                return damageManager.getTurnDestroyed(ship, fighter) === null;
            });

            if (fightersSurviving) {
                return null;
            }

            ship.systems.forEach(function (fighter) {
                var dturn = damageManager.getTurnDestroyed(ship, fighter);
                if (dturn > turn) turn = dturn;
            });
        } else {

            var react = shipManager.systems.getSystemByName(ship, "reactor");
            var rturn = damageManager.getTurnDestroyed(ship, react);
            var stru = shipManager.systems.getStructureSystem(ship, 0);
            var sturn = damageManager.getTurnDestroyed(ship, stru);

            if (rturn != null && (rturn < sturn || sturn == null)) turn = rturn;else turn = sturn;
        }

        return turn;
    },
/*
    getIniativeOrder: function getIniativeOrder(ship) {
        var previousInitiative = -100000; //same Ini move together now!
        var order = 0;  

        for (var i in gamedata.ships) {
            if (shipManager.isDestroyed(gamedata.ships[i])) continue;
            if (gamedata.ships[i].iniative > previousInitiative){ //new Ini higher than previous!         
                order++;
                previousInitiative = gamedata.ships[i].iniative;
            }
            if (gamedata.ships[i].id == ship.id) return order;
        }

        return 0; //should not happen
    },
*/

    //New getInitiativeOrder function to accommodate terrain units like asteroids.
    getIniativeOrder: function getIniativeOrder(ship) {
        var previousInitiative = -100000; // same Ini move together now!
        var order = 0;

        // Filter out destroyed ships and those with shipSizeClass === 5 e.g. terrain
        var validShips = gamedata.ships.filter(function(s) {
            return !shipManager.isDestroyed(s) && !gamedata.isTerrain(s.shipSizeClass, s.userid) && !(shipManager.getTurnDeployed(s) > gamedata.turn);
        });

        for (var i in validShips) {
            if (validShips[i].iniative > previousInitiative) { // new Ini higher than previous!
                order++;
                previousInitiative = validShips[i].iniative;
            }
            if (validShips[i].id === ship.id) return order;
        }

        return 0; // should not happen
    },


    hasBetterInitive: function hasBetterInitive(a, b) {
        //console.log(a.name);
        if (a.iniative > b.iniative) return true;

        if (a.iniative < b.iniative) return false;

/*
        if (a.unmodifiedIniative != null && b.unmodifiedIniative != null) {
            if (a.unmodifiedIniative > b.unmodifiedIniative)
                return 1;
        
            if (a.unmodifiedIniative < b.unmodifiedIniative)
                return -1;
        }
*/
        //if (a.iniative == b.iniative) {
            if (a.iniativebonus > b.iniativebonus) return true;

            if (b.iniativebonus > a.iniativebonus) return false;

	    return (a.id>b.id); //lower ID wins, if all else fails
	    
	    /*
            for (var i in gamedata.ships) {
                if (gamedata.ships[i] == a) return false;

                if (gamedata.ships[i] == b) return true;
            }
	    */
        //}

        return 0; //shouldn't get here
    },
	
	hasWorseInitiveSort: function hasWorseInitiveSort(a, b) {
		var hasBetterIni = shipManager.hasBetterInitive(a, b);
		if(hasBetterIni) return -1; //reverse
		return 1;
    },

    //Only used for Deployment checks to prevent ships deploying on same hex (or now allow for later Deployments) - DK
    getShipsInSameHex: function getShipsInSameHex(ship, pos1) {

        if (!pos1) var pos1 = shipManager.getShipPosition(ship);

        var shipsInHex = Array();
        for (var i in gamedata.ships) {
            var ship2 = gamedata.ships[i];

            if (shipManager.isDestroyed(ship2)) continue;

            //Let's allow ships that deploy on later turns to deploy on same hex as existing units - DK
            var depTurn = shipManager.getTurnDeployed(ship2);            
            if (depTurn !== gamedata.turn && !ship2.Enormous) continue;

            var pos2 = shipManager.getShipPosition(ship2);

            //But never let ships Deployment on unoccupied parts of Huge terrain.
            if(ship2.Huge > 0 && ship2.Huge < 3){ //Between 1 and 2, Moons basically - DK
                //var s2pos = shipManager.getShipPosition(ship2);
                var distance = pos1.distanceTo(pos2);
                if(distance > 0 && distance <= ship2.Huge){ 
                    shipsInHex.push(ship2);
                    confirm.error("You cannot deploy on terrain.");                                         
                }    
            }

            //if (ship.id = ship2.d)
            //  continue;

            if (pos1.equals(pos2)) {
                if(gamedata.isTerrain(ship2.shipSizeClass, ship2.userid)){
                    shipsInHex.push(ship2);
                    confirm.error("You cannot deploy on terrain.");  
                }else{
                    shipsInHex.push(ship2);
                }    
            }
        }

        return shipsInHex;
    },

    getFighterPosition: function getFighterPosition(pos, angle, zoom) {

        var dir = 0;
        if (pos == 0) {
            dir = mathlib.addToDirection(0, angle);
            return mathlib.getPointInDirection(19 * zoom, dir, 0, 0);
        } else if (pos == 1) {
            dir = mathlib.addToDirection(300, angle);
            return mathlib.getPointInDirection(13 * zoom, dir, 0, 0);
        } else if (pos == 2) {
            dir = mathlib.addToDirection(60, angle);
            return mathlib.getPointInDirection(13 * zoom, dir, 0, 0);
        } else if (pos == 3) {
            dir = mathlib.addToDirection(180, angle);
            return mathlib.getPointInDirection(12 * zoom, dir, 0, 0);
        } else if (pos == 4) {
            dir = mathlib.addToDirection(250, angle);
            return mathlib.getPointInDirection(21 * zoom, dir, 0, 0);
        } else if (pos == 5) {
            dir = mathlib.addToDirection(110, angle);
            return mathlib.getPointInDirection(21 * zoom, dir, 0, 0);
        } else if (pos == 6) {
            dir = mathlib.addToDirection(180, angle);
            return mathlib.getPointInDirection(29 * zoom, dir, 0, 0);
        } else if (pos == 7) {
            dir = mathlib.addToDirection(230, angle);
            return mathlib.getPointInDirection(32 * zoom, dir, 0, 0);
        } else if (pos == 8) {
            dir = mathlib.addToDirection(130, angle);
            return mathlib.getPointInDirection(32 * zoom, dir, 0, 0);
        } else if (pos == 9) {
            dir = mathlib.addToDirection(0, angle);
            return mathlib.getPointInDirection(35 * zoom, dir, 0, 0);
        } else if (pos == 10) {
            dir = mathlib.addToDirection(295, angle);
            return mathlib.getPointInDirection(28 * zoom, dir, 0, 0);
        } else if (pos == 11) {
            dir = mathlib.addToDirection(65, angle);
            return mathlib.getPointInDirection(28 * zoom, dir, 0, 0);
        }

        return { x: 0, y: 0 };
    },

    getSpecialAbilitySystem: function getSpecialAbilitySystem(ship, ability) {
        for (var i in ship.systems) {
            var system = ship.systems[i];

            if (shipManager.systems.isDestroyed(ship, system)) continue;

            if (shipManager.power.isOffline(ship, system)) continue;

            for (var a in system.specialAbilities) {
                if (system.specialAbilities[a] == ability) return system;
            }
        }

        return false;
    },

    hasSpecialAbility: function hasSpecialAbility(ship, ability) {
        if (shipManager.getSpecialAbilitySystem(ship, ability)) return true;

        return false;
    },

    isElint: function isElint(ship) {
        if (shipManager.hasSpecialAbility(ship, "ELINT")) {
            return true;
        }

        return false;
    },

    checkConstantPivot: function checkConstantPivot() {
		var pivotShips = [];
		var counter = 0;
		for (var i in gamedata.ships) {
			var ship = gamedata.ships[i];
			if (!ship.mustPivot) continue;//Ignore everything but ships that HAVE to pivot.
			if (ship.unavailable) continue;
			if (ship.userid != gamedata.thisplayer) continue;					
			if (shipManager.isDestroyed(ship)) continue;
            var deployTurn = shipManager.getTurnDeployed(ship);
			if(deployTurn > gamedata.turn) continue;  //Don't bother checking for ships that haven't deployed yet.

			var left = 0;
			var right = 0;				
	
	        const currentTurnMovements = ship.movement.filter(movement => movement.turn == gamedata.turn);
	        currentTurnMovements.forEach(movement => {
	            if (movement.type == "pivotleft" && !movement.preturn) {
	                left++;
	            }
	            if (movement.type == "pivotright" && !movement.preturn) {
	                right++;
	            }
	        });

			if(left == right){				
				pivotShips[counter] = ship;
				counter++;		
			}					
		}
		
		return pivotShips;
    },


    isEscorting: function isEscorting(ship, target) {
        if (!ship.flight) return false;
        //var ships = shipManager.getShipsInSameHex(ship);
        //for (var i in ships) {
            //var othership = ships[i];
        if (gamedata.turn == 1) return true; //on turn 1 all friendly ships can be protected!
        
        for (var i in gamedata.ships) { //doesn't need to be on the same hex NOW... only at the start and end of move :)
            var othership = gamedata.ships[i];
            if (shipManager.isDestroyed(othership)) continue; //no need to list ships already destroyed
            if (othership.flight === true) continue; //can escort only ships
            if (othership.id == ship.id) continue;

            if (gamedata.isEnemy(ship, othership)) continue;

            var oPos = shipManager.movement.getPositionAtStartOfTurn(othership);
            var tPos = shipManager.movement.getPositionAtStartOfTurn(ship);

            if (!target || target.id == othership.id) {
                if (oPos.equals(tPos)) return true;
            }
        }
        return false;
    },
    
    /*list of names of escorted ships*/
    listEscorting: function listEscorting(ship) {
        var resultTxt = '';
        if (!ship.flight) return resultTxt;
    
        if (gamedata.turn == 1) return 'All'; //turn 1: all ships can be escorted

        for (var i in gamedata.ships) {
            var othership = gamedata.ships[i];
            /*
            if (othership.flight === true) continue; //can escort only ships
            if (othership.id == ship.id) continue; //self
            if (gamedata.isEnemy(ship, othership)) continue; //no escorting opponent

            var oPos = shipManager.movement.getPositionAtStartOfTurn(othership);
            var tPos = shipManager.movement.getPositionAtStartOfTurn(ship);

            if (oPos.equals(tPos)){
            */
            if (shipManager.isEscorting(ship,othership)){
                if(resultTxt != '') resultTxt += ', ';
                resultTxt += othership.name;
            }
        }

        return resultTxt;
    },

    //Generic function called from various front end functions.  Checks if ships should be shown/interactable or not.
    shouldBeHidden: function(ship) {    
        if(shipManager.getTurnDeployed(ship) > gamedata.turn) return true; //Not deployed yet.
        if(!gamedata.isMyorMyTeamShip(ship) && shipManager.isStealthShip(ship) && !shipManager.isDetected(ship)) return true; //Enemy, stealth ship and not currently detected
        return false;
    },
    

    getTurnDeployed: function getTurnDeployed(ship) {
          
        if(ship.osat || ship.base || gamedata.isTerrain(ship.shipSizeClass, ship.userid)) {
            return 1; //Bases and OSATs never 'jump in', returns Turn 1.
        }/*else if (gamedata.gamephase == -1 && slot.depavailable == gamedata.turn){
            if(!ajaxInterface.submiting){
                return gamedata.turn;
            }else{
                return slot.depavailable;                 
            } 
        }*/else{    
           //return Math.max(ship.deploysOnTurn, slot.depavailable);
            var slot = playerManager.getSlotById(ship.slot);   
            return slot.depavailable;                       
        }     
    },    


    //True or false function, e.g. for possible use in Deployment Phase to show commit button in case needed.
    playerHasDeployedShips: function playerHasDeployedShips(playerid) {
       for (const ship of gamedata.ships) {
            if(ship.userid !== playerid) continue;
    
            var deploys = shipManager.getTurnDeployed(ship);
            if(deploys <= gamedata.turn) return true;
        }
        return false;         
    },


    //Called in various places to identify a ship as having stealth ability.
    isStealthShip: function(ship) {
        if(shipManager.hasSpecialAbility(ship, "Stealth") && (!ship.flight)) return true;
        return false;
    },
  

    markAsDetected: function(ship) {
        var stealthSystem = shipManager.systems.getSystemByName(ship, "stealth");
        if(stealthSystem) stealthSystem.detected = true;
    }, 


    //Main Front End check on whether a stealth ship is detected or not, called in various places.
    isDetected: function(ship) {
        if(gamedata.gamephase == -1) return true;  //Do not hide in Deployment Phase.        
        var stealthSystem = shipManager.systems.getSystemByName(ship, "stealth");
        if(stealthSystem && stealthSystem.detected) return true; //Already detected.

        // If the ship used offensive or ELINT EW, it is revealed
        const usedEW = ew.getAllEWExceptDEW(ship); //Has used any EW abilities except DEW?
        if (usedEW > 0){
            return true; //If so, revealed.
        }            

        if(gamedata.gamephase != 3) return false;  //Cannot only try to detect at start of Firing Phase

        // Check all enemy ships to see if any can detect this ship
        for (const otherShip of gamedata.ships) {
            if (otherShip.team === ship.team) continue; // Skip friendly ships
            if (gamedata.isTerrain(otherShip.shipSizeClass, otherShip.userid)) continue; //Skip Terrain 
            if(shipManager.isDestroyed(otherShip)) continue; //Skip destroyed

            let totalDetection = 0;

            if (!otherShip.flight) {
                if(shipManager.isDisabled(otherShip)) continue; //Skip disabled ships               
                // Not a fighter — use scanner systems for detection
                const standardScanners = shipManager.systems.getSystemListByName(otherShip, "scanner");
                const elintScanners = shipManager.systems.getSystemListByName(otherShip, "elintScanner");
                const scanners = [...standardScanners, ...elintScanners];

                for (const scanner of scanners) {
                    if (!shipManager.systems.isDestroyed(otherShip, scanner) && !shipManager.power.isOfflineOnTurn(otherShip, scanner, gamedata.turn)) {
                        totalDetection += scanner.output;
                    }
                }

                // Apply detection multiplier based on ship type
                if (otherShip.base) {
                    totalDetection *= 5;
                } else if (shipManager.hasSpecialAbility(otherShip, "ELINT")) {
                    totalDetection *= 3;
                    //Then add any Detect Stealth bonus here.
                    var bonusDSEW = ew.getEWByType("Detect Stealth", otherShip);
                    totalDetection += bonusDSEW * 2;
                } else {
                    totalDetection *= 2;
                }
            } else {
                // Fighter unit — use offensive bonus
                if(otherShip.offensivebonus) totalDetection = otherShip.offensivebonus;
            }

            // Get distance to the stealth ship and check line of sight
            const distance = parseFloat(mathlib.getDistanceBetweenShipsInHex(ship, otherShip));
            var loSBlocked = false;
            var blockedLosHex = weaponManager.getBlockedHexes(); //Check if there are any hexes that block LoS
            var shipPos= shipManager.getShipPosition(ship);
            var otherShipPos= shipManager.getShipPosition(otherShip);                   
            loSBlocked = mathlib.checkLineOfSight(shipPos, otherShipPos, blockedLosHex); // Defaults to false (LoS NOT blocked)            

            // If within detection range, the ship is revealed
            if (totalDetection >= distance && !loSBlocked) { //In range and LoS not blocked.
                shipManager.markAsDetected(ship);
                return true; //Just return, if one ship can see the stealthed ship then all can.
            }
        }

        // No one detected the ship
        return false;
    }

};

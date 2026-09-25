<?php
// Load global config and classes
require_once 'global.php';
session_write_close(); // Prevent session locking for concurrent loads

if (!isset($_SESSION["user"]) || $_SESSION["user"] == false){
    header('Location: index.php');
    exit;
}

// Never cache this HTML document — it inlines a player-specific, point-in-time
// lobby snapshot ($gamelobbydataJSON below). Without this the browser can
// disk-cache the page and replay a stale copy on session restore (reopening tabs
// after a browser or computer restart), with no server round-trip. no-store
// forces a fresh fetch every time.
header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');

if (isset($_GET["leave"]) && isset($_GET["gameid"])){
    Manager::leaveLobbySlot($_SESSION["user"], $_GET["gameid"]);
    header('Location: games.php');
    exit;
}
	
	
	$gameid = null;
	
	if (isset($_GET["gameid"])){
		$gameid = $_GET["gameid"];
	}
	
  // Use cached JSON to reduce server load
  $gamelobbydataJSON = Manager::getGameLobbyDataJSON( $_SESSION["user"], $gameid);
  $gamelobbydata = json_decode($gamelobbydataJSON);
  
    // STAMPEDE PROTECTION: If server is generating data, tell client to wait 1s
    if (isset($gamelobbydata->status) && $gamelobbydata->status == "GENERATING") {
        echo '<html><head><meta http-equiv="refresh" content="1"></head>
        <body style="background:#000; color:red; display:flex; justify-content:center; align-items:center; height:100vh; font-family:sans-serif; font-size:24px;">
        Loading...
        </body></html>';
        exit;
    }

    if (isset($gamelobbydata->error)) {
        echo '<html><body style="background:#000; color:red; padding: 20px; font-family:sans-serif;">
        <h2>Backend Error Generating Lobby</h2>
        <pre>' . htmlspecialchars($gamelobbydata->error) . '</pre>
        <p>File: ' . htmlspecialchars($gamelobbydata->file) . ' on line ' . htmlspecialchars($gamelobbydata->line) . '</p>
        <p>Log ID: ' . htmlspecialchars($gamelobbydata->logid) . '</p>
        <a href="games.php" style="color:#aaf;">Back to Games</a>
        </body></html>';
        exit;
    }

    if (!is_object($gamelobbydata) || !isset($gamelobbydata->status) || $gamelobbydata->status != "LOBBY") {
        header('Location: games.php');
        exit;
    }
  
    // $gamelobbydataJSON is already set/cached, no need to encode again

	// Fleet Builder (FleetTestRule): a one-slot lobby with no teams, map or scenario.
	$isFleetTest = isset($gamelobbydata->rules->fleetTest);

	// Scenario Description (CREATE_GAME_GAMELOBBY_REDESIGN_PLAN.md Stage 4): the structured
	// scenario as its stored JSON TEXT, handed to the page's JS as a string for scenarioCard to
	// parse - or null for a game created before it existed, whose description is parsed below
	// instead. The JSON_HEX_* flags keep the text inert inside the inline <script>.
	$scenarioText = $isFleetTest ? null : Manager::getGameScenario($gamelobbydata->id);
	$scenarioJS = json_encode($scenarioText, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT);
	if ($scenarioJS === false) $scenarioJS = 'null';

	// Getting all ships in one go causes memory overload on the server.
	// Get the factions first. When a faction is opened to buy ships,
	// go bother the server for the ships of that faction only.
	
	$factions = json_encode(Manager::getAllFactions(), JSON_NUMERIC_CHECK);

	// Cache-busting versions for per-faction static ship JSON.
	// The lobby fetches ships on demand from gamelobbyloader.php, which serves
	// static/json/<faction>.json (regenerated on every patch). Relying on the
	// ETag/Last-Modified revalidation alone proved unreliable on mobile/BFCache
	// (browsers skip the revalidation round-trip and serve stale ships). We emit
	// each faction file's mtime so the client can append ?v=<mtime>, making the
	// request URL change whenever the ship data changes — same approach as
	// AssetLoader::getAssetUrl() uses for the JS bundles.
	$factionVersions = [];
	$jsonDir = __DIR__ . '/static/json';
	foreach (glob($jsonDir . '/*.json') as $jsonFile) {
		$factionVersions[basename($jsonFile, '.json')] = filemtime($jsonFile);
	}
	$factionVersionsJSON = json_encode($factionVersions, JSON_UNESCAPED_UNICODE);

	$ships = [];


?>

<!DOCTYPE HTML>
<html>
	<head>
		<title>Fiery Void - Gamelobby</title>
		<meta name="viewport" content="width=device-width, initial-scale=1.0"> <!--To try and ix mobile scroll juddering caused by viewport recalculation repaints -->
		<meta http-equiv="Content-Type" content="text/html; charset=utf-8">

		<!-- Preload critical bundle to parallelize download with large inline JSON payloads below -->
		<?php $debug = (isset($_GET['debug']) || isset($_GET['DEBUG'])); ?>
		<?php if (!$debug): ?>
		<link rel="preload" href="<?php echo AssetLoader::getAssetUrl('client/gamelobby.legacy.bundle.js'); ?>" as="script">
		<?php endif; ?>
		<link rel="preload" href="<?php echo AssetLoader::getAssetUrl('client/UI/reactJs/UI.bundle.js'); ?>" as="script">

		<!-- Shared fv design tokens (roadmap item 6): MUST load before every other stylesheet. -->
		<link href="<?php echo AssetLoader::getAssetUrl('styles/tokens.css'); ?>" rel="stylesheet" type="text/css">
		<link href="<?php echo AssetLoader::getAssetUrl('styles/base.css'); ?>" rel="stylesheet" type="text/css">
		<link href="<?php echo AssetLoader::getAssetUrl('styles/lobby.css'); ?>" rel="stylesheet" type="text/css">
		<link href="<?php echo AssetLoader::getAssetUrl('styles/confirm.css'); ?>" rel="stylesheet" type="text/css">
        <link href="<?php echo AssetLoader::getAssetUrl('styles/gamesNew.css'); ?>" rel="stylesheet" type="text/css">
        <link href="<?php echo AssetLoader::getAssetUrl('styles/scenarioCard.css'); ?>" rel="stylesheet" type="text/css">
        <!-- The redesigned top of the page (Stage 4). After lobby.css and gamesNew.css, which it overrides. -->
        <link href="<?php echo AssetLoader::getAssetUrl('styles/gameLobby.css'); ?>" rel="stylesheet" type="text/css">
        <!-- jQuery + jQuery-UI self-hosted (same-origin HTTP/2 + cache-control, no 3rd-party
             TLS). Both kept SYNCHRONOUS: the lobby's synchronous client/*.js scripts run
             during parse and expect $.fn.draggable present, so jQuery-UI must not defer
             here. assetManager.js has no jQuery dep, so it defers. -->
        <script src="<?php echo AssetLoader::getAssetUrl('client/lib/jquery-4.0.0.min.js'); ?>"></script>
        <!-- Deploy-version cache-buster for images (see AssetManager.appendVersion). Plain
             inline <script> runs at parse time, before the deferred assetManager.js, so the
             global is guaranteed set first. Changes each deploy -> stale same-name art refetched. -->
        <script>window.assetVersion = "<?php echo AssetLoader::getDeployVersion(); ?>";</script>
        <script defer src="<?php echo AssetLoader::getAssetUrl('client/assetManager.js'); ?>"></script>
        <script src="<?php echo AssetLoader::getAssetUrl('client/lib/jquery-ui-1.14.2.min.js'); ?>"></script>
        <!-- React UI bundle (ship window + system info) — ship-window redesign Stage 3.
             AssetLoader tag, so bundle-legacy.js skips it (same as game.php). -->
        <script defer src="<?php echo AssetLoader::getAssetUrl('client/UI/reactJs/UI.bundle.js'); ?>"></script>
        <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
		
		<!-- replaced by php include below
        <script src="static/ships.js"></script>
		-->
<?php		
	//include 'static/ships.php'; //Changed how staticships are loaded to help with HTTP Protocol errors - DK Dec 2025

?>
		
<!--		<script src="client/helper.js"></script>-->
    <!--		<script src="client/helper.js"></script>-->
    <?php if ($debug): ?>
    <script src="client/gamelobby.js"></script>
		<script src="client/ajaxInterface.js"></script>
		<script src="client/lobbyEnhancements.js"></script>
		<script src="client/uiEventRelay.js"></script>
		<script src="client/renderer/shipWindowManager.js"></script>
		<script src="client/player.js"></script>
        <script src="client/ships.js"></script>
        <script src="client/criticals.js"></script>
        <script src="client/damage.js"></script>
        <script src="client/systems.js"></script>
        <script src="client/battleDamage.js"></script>
        <script src="client/systemEnhancements.js"></script>
        <script src="client/savedFleets.js"></script>
        <script src="client/power.js"></script>
        <script src="client/movement.js"></script>
        <script src="client/mathlib.js"></script>
		<script src="client/hangarShared.js"></script>
		<script src="client/UI/confirm.js"></script>
        <script src="client/UI/fleetList.js"></script>
        <script src="client/UI/gameInfo.js"></script>
        <script src="client/UI/scenarioCard.js"></script>
        <script src="client/UI/mapPreview.js"></script>
        <script src="client/model/ship.js"></script>
        <script src="client/model/shipSystem.js"></script>
        <script src="client/model/systemFactory.js"></script>
        <script src="client/model/system/baseSystems.js"></script>
        <script src="client/model/system/defensive.js"></script>
        <script src="client/model/weapon/ammo.js"></script>
    	<script src="client/model/weapon/ammoWeapons.js"></script>         
        <script src="client/model/weapon/laser.js"></script>
        <script src="client/model/weapon/particle.js"></script>
        <script src="client/model/weapon/matter.js"></script>
        <script src="client/model/weapon/plasma.js"></script>
        <script src="client/model/weapon/special.js"></script>
        <script src="client/model/weapon/supportWeapons.js"></script>        
        <script src="client/model/weapon/torpedo.js"></script>
        <script src="client/model/weapon/pulse.js"></script>
        <script src="client/model/weapon/electromagnetic.js"></script>
        <script src="client/model/weapon/aoe.js"></script>
        <script src="client/model/weapon/molecular.js"></script>
        <script src="client/model/weapon/antimatter.js"></script>
        <script src="client/model/weapon/gravitic.js"></script>
        <script src="client/model/weapon/missile.js"></script>
        <script src="client/model/weapon/ion.js"></script>
    	<script src="client/model/weapon/customs.js"></script>
		<script src="client/model/weapon/customSW.js"></script>
        <script src="client/model/weapon/customNexus.js"></script>
        <script src="client/model/weapon/customDevelopment.js"></script>
        <script src="client/model/weapon/customEscalation.js"></script>		
        <script src="client/model/weapon/customBSG.js"></script>		
        <script src="client/model/weapon/customTrek.js"></script>		
        <script src="client/model/weapon/customCW.js"></script>
    <?php else: ?>
    <script defer src="<?php echo AssetLoader::getAssetUrl('client/gamelobby.legacy.bundle.js'); ?>"></script>
    <?php endif; ?>		
		<script>
			
            /* Lobby weaponManager stub: predicate/data functions the React
               SystemIcon + SystemInfo render paths call. The lobby is read-only,
               so everything reads as idle/unloaded-order and the action functions
               are no-ops (SystemIcon's action branches are additionally gated off
               by gamephase -2 / gamedata.waiting).

               ⚠ THIS LIST DRIFTS. gamelobby.php does NOT bundle weaponManager.js -
               this object is the whole of `weaponManager` on this page. Any NEW
               `weaponManager.x()` call added to reactJs/system/* that is not behind
               a `gamedata.gamephase === -2` early return has to be added here too,
               or the lobby ship window dies with "x is not a function" and React
               reports "Ship window render failed for <ship>".

               SystemInfoButtons is safe by construction: canDoAnything, hasStyledMenu
               and render all return at gamephase -2 before reading any of this.
               SystemIcon is NOT - it calls straight through on every render. */
            window.weaponManager =
            {
                hasFiringOrder: function(){return false},
                isLoaded: function(){return true},
                isLoadedAlternate: function(){return false},
                isSelectedWeapon: function(){return false},
                getFiringOrder: function(){return null},
                getCalledShotInfo: function(){return null},
                selectAllWeapons: function(){}, //right-click select-all: nothing to select pre-game
                //Manual interception (MANUAL_INTERCEPTION_PLAN.md). Both are read by
                //SystemIcon with NO phase guard in front of them:
                //  isInterceptOnly       - the green "committed to defence" icon state,
                //                          evaluated for every system on every render (§11.3).
                //  canManuallyInterceptWith - the last clause of the weapon-select gate, and
                //                          `||` reaches it once the three phase clauses ahead
                //                          of it are false, which at gamephase -2 they always
                //                          are (Stage 4, §4.7a).
                //Nothing is declared pre-game, so both are flatly false.
                isInterceptOnly: function(){return false},
                canManuallyInterceptWith: function(){return false},
                getWeaponCurrentLoading: function(weapon)
                {
                    /* Weapons enter the game fully loaded, so the icon load counter
                       shows the ready state ("1/1", or "2/2" for normalload weapons).
                       Plain normalload (the old stub) read "0/1" on standard weapons. */
                    return weapon.normalload > 0 ? weapon.normalload : weapon.loadingtime;
                },
            }

            $(function(){
                window.shipManager.movement.isRolled = function(ship)
                {
                    return false;
                }
            });


            $(function(){
                /* Lobby: your own purchased mines are always identified — there is
                   no reveal mechanic pre-game, and blueprint ships have no .team so
                   the game-side check would render every mine as an unknown "?". */
                if (window.MineStealth) {
                    MineStealth.prototype.isMineRevealed = function () { return true; };
                }
            });

        
        jQuery(function($){            
            var lobbyData = <?php print($gamelobbydataJSON); ?>;
            // Per-faction static-ship versions (file mtimes) for cache-busting
            // the gamelobbyloader.php fetch. See $factionVersions in gamelobby.php.
            window.factionVersions = <?php print($factionVersionsJSON); ?>;
            gamedata.parseServerData(lobbyData);
            // The structured Scenario Description (tac_game.scenario) as its raw JSON text, or null.
            gamedata.renderScenarioPanel(<?php print($scenarioJS); ?>);
            gamedata.parseFactions(<?php print($factions); ?>);
            
            var customWarningShown = false; 
            var customFactionWarningShown = false;
            var customShipWarningShown = false;

            $('.readybutton').on("click", gamedata.onReadyClicked);
            $('.savebutton').on("click", gamedata.onSaveClicked)            		
            $('.checkbutton').on("click", gamedata.checkChoices); //fleet correctness check
            $('.leave').on("click", gamedata.onLeaveClicked);
            $('.leaveslot').on("click", gamedata.onLeaveSlotClicked);
            $('.selectslot').on("click", gamedata.onSelectSlotClicked);
            $('.takeslot').on("click", gamedata.clickTakeslot);

            // Start polling for updates
            ajaxInterface.startPollingGamedata();

            // ✅ Unified filter logic for factions based on Tier and Custom
            window.updateTierFilter = function() {   // ✅ Now global
                const selectedTiers = $('.tier-filter:checked').map(function () {
                    return $(this).data('tier');
                }).get();

                const showCustom = $('#toggleCustom').is(':checked');
                const customMode = $('#customSelect').val();

                $('.faction').each(function () {
                    const tier = $(this).data('tier');
                    const isCustom = $(this).data('custom') === true || $(this).data('custom') === "true";

                    let isVisible = false;

                    if (selectedTiers.includes(tier)) {
                        if (showCustom) {
                            if (customMode === 'showOnlyCustom') {
                                isVisible = isCustom;
                            } else {
                                isVisible = true; // show both custom and non-custom
                            }
                        } else {
                            isVisible = !isCustom; // hide custom if toggle unchecked
                        }
                    }

                    $(this).toggle(isVisible);
                });

                // Group headers visibility
                $('.factiongroup-header').each(function () {
                    let header = $(this);
                    let hasVisibleFaction = false;
                    let next = header.next();

                    if (next.hasClass('faction-group-container')) {
                        // Check if any faction inside the container is NOT hidden by the filter
                        // We avoid :visible because it checks parent visibility (which might be collapsed)
                        next.find('.faction').each(function() {
                            if ($(this).css('display') !== 'none') {
                                hasVisibleFaction = true;
                                return false; // break
                            }
                        });
                    } else {
                        while (next.length && !next.hasClass('factiongroup-header')) {
                            if (next.hasClass('faction') && next.is(':visible')) {
                                hasVisibleFaction = true;
                                break;
                            }
                            next = next.next();
                        }
                    }
                    
                    header.toggle(hasVisibleFaction);
                    if (next.hasClass('faction-group-container')) {
                        if (!hasVisibleFaction) {
                            next.hide();
                        } else {
                            // If it has visible factions and it's NOT collapsed, it should be visible
                            var icon = header.find('.faction-toggle-icon');
                            if (icon.text() === '[-]') {
                                next.show();
                            }
                        }
                    }
                });
            }

            // ✅ Listen to Tier and Custom Faction checkboxes
            $('.tier-filter').on('change', updateTierFilter);

            // Combined listener for toggle and dropdown
            $('#toggleCustom, #customSelect').on('change', function () {
                var showCustom = $('#toggleCustom').is(':checked');
                // var mode = $('#customSelect').val(); // Mode no longer needed for specific warnings

                if (showCustom) {
                    $('#customDropdown').show();
                    
                    var description = lobbyData.description || "";
                    // Check if explicit permission is missing (i.e. it does NOT say "Allowed")
                    var allowed = description.match(/CUSTOM FACTIONS \/ UNITS:\s*Allowed/i);

                    if (!allowed && !customWarningShown && gamedata.rules && gamedata.rules.fleetTest !== 1) {
                         window.confirm.warning("Custom Factions and/or Units not allowed in this match. <br>Please check Scenario Description");
                         customWarningShown = true;
                    }
                } else {
                    $('#customDropdown').hide();
                }
                updateTierFilter();
                gamedata.applyCustomShipFilter();
            });

            $('#customSelect').on('change', function () {
                updateTierFilter();
                gamedata.applyCustomShipFilter();
            });


            // ✅ Default the "Show Custom" checkbox on if customs are explicitly
            // allowed in the scenario description, or this is a fleet-test lobby.
            (function () {
                var description = lobbyData.description || "";
                var customsAllowed = /CUSTOM FACTIONS \/ UNITS:\s*Allowed/i.test(description)
                var isFleetTest = !!(gamedata.rules && gamedata.rules.fleetTest);
                if (customsAllowed || isFleetTest) {
                    // Set checked + show the dropdown without triggering the change
                    // handler, so the "not allowed" warning never fires on load.
                    $('#toggleCustom').prop('checked', true);
                    $('#customDropdown').show();
                }
            })();

            // ✅ Initial call
            updateTierFilter();


            // ✅ Select All / None Tier checkboxes + toggle customs
            // Every text filter, so the three reset paths below cannot forget one.
            var shipFilterFields = "#isdFilter, #nameFilter, #costFilter";

            $('.tier-select-all').on('click', function () {
                $('.tier-filter').prop('checked', true);
                $('#toggleCustom').prop('checked', true).trigger('change');
                $('#customSelect').val('showCustom'); // ✅ reset custom dropdown to Show Customs
                $(shipFilterFields).val('');
                gamedata.applyCustomShipFilter();
                updateTierFilter();
            });

            $('.tier-select-none').on('click', function () {
                $('.tier-filter').prop('checked', false);
                $('#toggleCustom').prop('checked', false).trigger('change');
                $(shipFilterFields).val('');
                gamedata.applyCustomShipFilter();
                updateTierFilter();
            });

            // Sanitize input on each keystroke, but don't apply filter yet
            $("#isdFilter").on("input", function () {
                let val = $(this).val().replace(/\D/g, ''); // remove non-digits
                if (val.length > 4) val = val.slice(0, 4); // limit to 4 digits
                $(this).val(val);
            });

            // Same treatment for Cost - digits only, 5 is plenty for any point value
            $("#costFilter").on("input", function () {
                let val = $(this).val().replace(/\D/g, '');
                if (val.length > 5) val = val.slice(0, 5);
                $(this).val(val);
            });

            // Apply filter only when Enter key is pressed
            $("#isdFilter, #nameFilter, #costFilter").on("keypress", function (e) {
                if (e.which === 13) {
                    gamedata.applyCustomShipFilter();
                }
            });

            // Reset filters when clicking "Reset Filters"
            $(".resetFilters").on("click", function () {
                $(shipFilterFields).val('');
                gamedata.applyCustomShipFilter();
            });

            /* Fleet Builder points cap (rendered only in a fleetTest lobby, so both
               selectors come back empty and these binds are no-ops elsewhere).
               gamedata.builderMaxPoints is the ONE piece of state: null = unlimited.
               Everything downstream reads it through gamedata.getMaxPoints(). */
            $('#unlimitedPointsToggle').on('change', function () {
                if ($(this).is(':checked')) {
                    gamedata.builderMaxPoints = null;
                } else {
                    var typed = parseInt($('#maxPointsInput').val(), 10);
                    if (isNaN(typed) || typed < 0) {
                        typed = 3500;
                        $('#maxPointsInput').val(typed);
                    }
                    gamedata.builderMaxPoints = typed;
                }
                gamedata.calculateFleet();
            });

            $('#maxPointsInput').on('input', function () {
                var typed = parseInt($(this).val(), 10);
                //A blank/part-typed field keeps the last good cap rather than briefly
                //reading as 0 and turning the whole fleet unaffordable.
                if (isNaN(typed) || typed < 0) return;
                gamedata.builderMaxPoints = typed;
                gamedata.calculateFleet();
            });

            $('#maxPointsInput').on('blur', function () {
                if (gamedata.builderMaxPoints === null) return;
                $(this).val(gamedata.builderMaxPoints);
            });

            // Optional: initialize custom ship visibility
            $("#toggleCustom").trigger("change");
        });


		</script>
	</head>
	<body <?php
		// The background filename arrives from the client at game creation and is
		// stored unescaped. Emit it only if it still looks like a plain image
		// filename — that closes attribute breakout, CSS url() injection and path
		// traversal in one check.
		if ($gamelobbydata && !empty($gamelobbydata->background)
			&& preg_match('/^[A-Za-z0-9._-]+\.(jpg|jpeg|png|gif|webp)$/i', $gamelobbydata->background)) {
			echo 'style="background-image:url(img/maps/' . htmlspecialchars($gamelobbydata->background, ENT_QUOTES) . ')"';
		}
	?>>

  <header class="pageheader">
    <img src="img/logo.png" alt="Fiery Void Logo" class="logo">
    <div class="top-right-row">
      <a href="games.php">Back to Lobby</a>        
      <a href="logout.php" class="btn btn-primary">Logout</a>
    </div>
  </header>
<!--        <div class="helphide" style="float:right" onclick="window.helper.onClickHelpHide()">
        <img id="helphideimg" src="img/greyvir.jpg" height="30" width="30">	
        </div>-->
<main class="container"></main>        
		<?php
		/* ── Top of the page (CREATE_GAME_GAMELOBBY_REDESIGN_PLAN.md §4.1 / §4.2 / §11.3, Stage 4) ──
		   The game's name, then one row: Map Preview | Scenario Description | Teams. Teams and the
		   Map Preview are filled by gamelobby.js on every poll; the rule chips (under the map) and the
		   Scenario Description's structured facts once, on load (gamedata.renderScenarioPanel).
		   A Fleet Builder lobby has no teams, no map and no scenario - only the reference links. */

		/* The reference pages - the same three as game.php's USEFUL LINKS - and, until the lobby's
		   own faction randomiser replaces them (plan §4.5, Stage 7), the three random-faction wheels. */
		$lobbyLinks = '
		<div class="lb-links">
		  <div class="lb-links-row">
		    <span class="lb-links-label">Useful links</span>
		    <a class="lb-link" href="./faq.php" target="_blank" rel="noopener noreferrer"
		       title="Aide-memoire of specific rules and differences from Babylon 5 Wars">FAQ</a>
		    <a class="lb-link" href="./factions-tiers.php" target="_blank" rel="noopener noreferrer"
		       title="Overview of Fiery Void factions and their approximate strengths">Factions &amp; Tiers</a>
		    <a class="lb-link" href="./ammo-options-enhancements.php" target="_blank" rel="noopener noreferrer"
		       title="Details of all the extras available to Fiery Void units, e.g. missiles">Ammo &amp; Options</a>
		  </div>
		  <div class="lb-links-row">
		    <span class="lb-links-label">Random faction</span>
		    <a class="lb-link lb-link--quiet" href="https://old.wheelofnames.com/fx3-uje" target="_blank" rel="noopener noreferrer">Tier 1</a>
		    <a class="lb-link lb-link--quiet" href="https://old.wheelofnames.com/rmq-7ds" target="_blank" rel="noopener noreferrer">Tier 2</a>
		    <a class="lb-link lb-link--quiet" href="https://old.wheelofnames.com/sgd-5zq" target="_blank" rel="noopener noreferrer">Tier 3</a>
		  </div>
		</div>';

		/* A game created before tac_game.scenario existed has only its free-text description:
		   "LABEL: value" lines, recovered by splitting each on its first colon - the old parser,
		   unchanged - and written out in scenarioCard.js's own fact-grid markup, so an old game reads
		   like a new one (plan §4.1). A line with no colon continues the fact above it (Additional
		   Info's extra lines). Everything is escaped: it is the creator's text. */
		function lobbyLegacyScenarioFacts($description) {
			$desc = str_replace(array('<br>', '<br/>', '<br />'), "\n", (string)$description);
			$desc = preg_replace('/^\*{3}.*\*{3}\s*/m', '', $desc); //the old "*** ... ***" header line

			$facts = array();
			foreach (preg_split("/\r\n|\n|\r/", trim($desc)) as $line) {
				$line = trim($line);
				if ($line === '') continue;

				$pos = strpos($line, ':');
				if ($pos !== false) {
					$facts[] = array('label' => trim(substr($line, 0, $pos)), 'value' => trim(substr($line, $pos + 1)));
				} else if (!empty($facts)) {
					$last = count($facts) - 1;
					$facts[$last]['value'] .= ($facts[$last]['value'] === '' ? '' : "\n") . $line;
				} else {
					$facts[] = array('label' => '', 'value' => $line);
				}
			}

			$html = '';
			foreach ($facts as $fact) {
				if ($fact['value'] === '') continue; //as in the structured grid: an empty fact is left out
				$isInfo = preg_match('/^ADDITIONAL INFO(RMATION)?$/i', $fact['label']) === 1;
				$html .= '<div class="fv-scn-fact' . ($isInfo ? ' fv-scn-fact--wide' : '') . '">'
					. '<dt class="fv-scn-label">' . htmlspecialchars($fact['label']) . '</dt>'
					. '<dd class="fv-scn-value' . ($isInfo ? ' fv-scn-value--multiline' : '') . '">' . htmlspecialchars($fact['value']) . '</dd>'
					. '</div>';
			}
			return $html === '' ? '' : '<dl class="fv-scn-grid">' . $html . '</dl>';
		}

		//A structured scenario is rendered by the page's JS; only a game without one is parsed here.
		$scenarioFactsHtml = ($scenarioText === null) ? lobbyLegacyScenarioFacts($gamelobbydata->description) : '';

		$mapSizeText = 'No boundaries';
		if (preg_match('/^(\d+)x(\d+)$/', (string)$gamelobbydata->gamespace, $mapSizeMatch)) {
			$mapSizeText = $mapSizeMatch[1] . ' &times; ' . $mapSizeMatch[2];
		}
		?>
		<div class="lb-top<?php if ($isFleetTest) echo ' lb-top--builder'; ?>">
			<div class="lb-titlebar">
				<!-- The game name is player-supplied and stored unescaped, so it must be escaped here. -->
				<h1 class="lb-title"><?php print($isFleetTest ? 'Fleet Builder' : htmlspecialchars($gamelobbydata->name)); ?></h1>
				<button type="button" class="lb-btn lb-btn--leave leave">Leave Game</button>
			</div>

		<?php if ($isFleetTest): ?>
			<section class="lb-panel lb-builder" aria-labelledby="lbBuilderHead">
				<h2 class="lb-panel-head" id="lbBuilderHead"><span>Rules &amp; Info</span></h2>
				<?php print($lobbyLinks); ?>
			</section>
		<?php else: ?>
			<div class="lb-row">
				<!-- Every panel keeps its own height. The Map Preview, usually the tallest, leads; stacked
				     on a narrower screen, Scenario Description comes before it (gameLobby.css). -->
				<div class="lb-pair">
					<section class="lb-panel lb-map" aria-labelledby="lbMapHead">
						<h2 class="lb-panel-head" id="lbMapHead"><span>Map Preview</span><span class="lb-panel-meta"><?php print($mapSizeText); ?></span></h2>
						<div class="lb-panel-body">
							<div class="lb-map-frame"><canvas id="mapPreview" width="545" height="390" aria-hidden="true"></canvas></div>
							<div class="lb-legend" id="lbMapLegend"></div>
						</div>
						<div class="lb-rules">
							<span class="lb-rules-label" id="lbRulesLabel">Game rules</span>
							<ul class="fv-rule-chips" id="lbRuleChips" aria-labelledby="lbRulesLabel"></ul>
						</div>
					</section>

					<section class="lb-panel lb-scenario" aria-labelledby="lbScenarioHead">
						<h2 class="lb-panel-head" id="lbScenarioHead"><span>Scenario Description</span></h2>
						<div class="lb-panel-body" id="lbScenarioFacts"><?php
							print($scenarioFactsHtml !== '' ? $scenarioFactsHtml : '<p class="lb-empty">No scenario details.</p>');
						?></div>
						<?php print($lobbyLinks); ?>
					</section>
				</div>

				<section class="lb-panel lb-teams" aria-labelledby="lbTeamsHead">
					<h2 class="lb-panel-head" id="lbTeamsHead"><span>Teams</span><span class="lb-panel-meta" id="lbTeamsMeta"></span></h2>
					<div class="lb-panel-body">
						<div class="lb-teams-grid" id="lobbyTeamsContainer">
							<!-- Teams injected by JS (gamedata.createSlots) -->
						</div>
					</div>
				</section>
			</div>
		<?php endif; ?>
		</div>

<div class="panel large lobby buy buy-panel-container">


    <div class="buy-header-flex">
        <div>
            <span class="panelheader buy-header-title-style">PURCHASE YOUR FLEET</span>
        </div> 
                <div>
                    <span class="remaining-points-container">
                        <!--<span class="panelsmall points-bracket-style">(</span>-->
                        <span class="panelsmall remaining">0</span><span class="panelsmall remaining-points-units">pts left</span>
                        <!--<span class="panelsmall points-bracket-style">)</span>-->
                    </span>
                </div>             
    </div>

            <div class="filter-container-style">
                <div>
                    <span class="clickable tier-select-all all-filters-link">All Filters</span>
                    <span class="filter-pipe-separator">|</span>          
                    <span class="clickable tier-select-none no-filters-link">No Filters</span>
                    <span class="filter-pipe-separator">|</span>  

                    <span class="filter-by-text">Filter by:</span>

                    <label class="name-filter-label-style">
                        <span class="filter-by-name-text">Name</span>
                        <input type="text" id="nameFilter" value="" class="name-input-style">
                    </label>

                    <!-- Cost filter: hides anything costing MORE than the figure typed. -->
                    <label class="cost-filter-label-style">
                        <span class="filter-by-cost-text">Cost</span>
                        <input type="text" id="costFilter" value="" class="cost-input-style"
                               inputmode="numeric" pattern="[0-9]*">
                    </label>

                    <label class="isd-filter-label-style">
                        <span class="filter-by-isd-text">ISD</span>
                        <input type="text" id="isdFilter" value="" class="isd-input-style"
                               inputmode="numeric" pattern="[0-9]*">
                    </label>

                    <!-- Outside the ISD <label> on purpose: a span inside a label is part of
                         that label's hit area, so clicking Reset also focused the ISD box. -->
                    <span class="clickable resetFilters reset-filters-link-style">Reset Filters</span>
                </div>
                <!-- points-readout is a flex row: these five pieces are different font
                     sizes (and the checkbox carries base.css's global 2px nudge), so they
                     are centred on the row rather than left to find a common baseline. -->
                <div class="points-readout">
                    <!--<span class="remaining-points-container">
                        <span class="panelsmall points-bracket-style">(</span>
                        <span class="panelsmall remaining">0</span><span class="panelsmall remaining-points-units">pts left</span>
                        <span class="panelsmall">) </span>
                    </span>-->
                    <span class="panelsubheader current">0</span>
                    <span class="panelsubheader">/</span>
                    <span class="panelsubheader max">0</span><?php if ($isFleetTest): ?>
                    <!-- Fleet Builder only. The slot itself is always unlimited server-side,
                         so this optional cap is purely a client-side yardstick: it drives the
                         points readout, the affordability checks and the Fleet Checker's
                         bracket/hull limits (gamedata.getMaxPoints). It sits exactly where
                         the word "Unlimited" does, and calculateFleet swaps the two - the
                         value is NEVER written into .max, which is rewritten on every
                         recalculation and would eat the field mid-keystroke. -->
                    <input type="number" id="maxPointsInput" class="max-points-input" value="3500"
                           min="0" step="50" style="display:none" aria-label="Maximum fleet points"><?php endif; ?><span class="panelsubheader max-points-units">pts</span><?php if ($isFleetTest): ?>
                    <input type="checkbox" id="unlimitedPointsToggle" class="yellow-tick unlimited-points-toggle"
                           checked title="Unlimited points - untick to build against a fixed limit"
                           aria-label="Unlimited points">
                    <?php endif; ?>
                </div>
            </div>


    <div class="tier-filters-row">
        <label class="tier-label-style">Tier 1 <input type="checkbox" class="tier-filter" data-tier="Tier 1" checked></label>
        <label class="tier-label-style">Tier 2 <input type="checkbox" class="tier-filter" data-tier="Tier 2" checked></label>
        <label class="tier-label-style">Tier 3 <input type="checkbox" class="tier-filter" data-tier="Tier 3" checked></label>
        <label class="tier-label-style">Ancients <input type="checkbox" class="tier-filter" data-tier="Tier Ancients" checked></label>
        <label class="tier-label-style">Other <input type="checkbox" class="tier-filter" data-tier="Tier Other" checked></label>

        <span class="tier-pipe-separator">|</span>

        <label class="tier-label-style">Show Custom<input type="checkbox" id="toggleCustom" class="yellow-tick"></label>

        <!-- ⚠️ DIRECTLY AFTER ITS OWN CHECKBOX, and it has to stay there. This dropdown is shown
             and hidden by #toggleCustom (it is the "which customs?" half of that one control), so
             the two read as one thing only while they are adjacent. The reinforcement label below
             was inserted between them when it was added, which pushed the dropdown to the far side
             of an unrelated control the moment Show Custom was ticked (user report 2026-08-28). -->
        <span id="customDropdown" class="custom-dropdown-style">
            <select id="customSelect" name="customFilterMode">
                <option value="showCustom">Show Customs</option>
                <option value="showOnlyCustom">Show Only Customs</option>
            </select>
        </span>

        <span class="tier-pipe-separator">|</span>

        <!-- REINFORCEMENTS_PLAN.md 2.1 - a BUY MODE, not a per-unit control: everything added to
             the fleet while it is ticked is bought as a reinforcement and waits in hyperspace.
             Hidden entirely unless the game was created with Allow Reinforcements
             (gamedata.applyReinforcementRule), so a game without the rule looks exactly as it
             did. An already-bought row is re-flagged from its own Reinforce/Main Fleet link.

             ⭐ THIS CHECKBOX IS THE STATE THE WHOLE FEATURE READS. The MAIN FLEET /
             REINFORCEMENTS headers in the fleet list are a second, more obvious control for the
             same thing (user request 2026-08-28) - gamedata.setBuyTarget writes this box and
             gamedata.applyFleetGrouping reads it back to decide which header lights up - so the
             two can never disagree, and buyingReinforcement() still has exactly one thing to
             ask. -->
        <label class="tier-label-style reinforcement-mode-label" style="display:none"
               title="Units bought while this is ticked wait in hyperspace and arrive through a jump point">Buy
            as Reinforcement<input type="checkbox" id="reinforcementModeToggle" class="cyan-tick"></label>


        <div class="fleet-loading-container">
            <!-- A bare <input> gave phone keyboards a "Next" action key, because the page has
                 more focusable fields after it (the chat panel), so pressing it moved focus
                 there instead of firing the keydown handler and the fleet never loaded.
                 The <form> is what actually fixes it: an input inside its OWN single-field
                 form gets implicit submission, so the action key becomes Go/Enter rather than
                 Next — no submit button is needed for that (HTML implicit submission), and
                 there deliberately isn't one. enterkeyhint labels the key, inputmode/pattern
                 bring up the numeric pad. -->
            <form class="fleet-id-form" id="fleetIdForm" action="#" onsubmit="return false;">
                <label class="fleet-id-label-container">
                    <span class="Load-Fleet-by-ID">Load Fleet by #ID:</span>
                    <input type="text" id="fleetIdInput" value="" class="fleetIdInput"
                           inputmode="numeric" pattern="[0-9]*" enterkeyhint="go"
                           autocomplete="off" aria-label="Load fleet by ID">
                </label>
            </form>

            <!-- Custom Saved Fleet Dropdown -->
            <div class="saved-fleet-wrapper">
                <div id="fleetDropdownButton" class="fleet-dropdown-btn">
                    LOAD A FLEET
                </div>
                <div id="fleetDropdownList" class="fleet-dropdown-list">
                    <!-- populated dynamically -->
                </div>
            </div>

            <!-- Second SAVE FLEET, beside the loader (the other one is at the bottom of
                 the buy panel, a long scroll away on a phone). Same .savebutton hook, so
                 the single handler bound at the top of this file drives both.
                 ⚠️ NO `btn` class, deliberately — .readybutton-top beside it has none
                 either. gamesNew.css is linked AFTER lobby.css, and its `.btn` rule
                 (border: none; display: inline-block) has the same specificity as
                 `.savebutton-top`, so it WON: this button lost its border and its
                 inline-flex centring while its twin at the bottom of the buy panel, which
                 pairs `.btn` with the later `.btn-primary-lobby`, kept both. -->
            <span class="savebutton savebutton-top">SAVE FLEET</span>

            <?php if(!$isFleetTest): ?>
            <span class="readybutton readybutton-top">READY</span>
            <?php endif; ?>
        </div>



    </div>

    <script>
        let cachedFleets = [];
        let fleetsLoaded = false;
        // References
        const fleetDropdownButton = document.getElementById('fleetDropdownButton');
        const fleetDropdownList = document.getElementById('fleetDropdownList');

        // Toggle dropdown visibility
        fleetDropdownButton.addEventListener('click', () => {
            if (fleetDropdownList.style.display === 'block') {
                fleetDropdownList.style.display = 'none';
            } else {
                fleetDropdownList.style.display = 'block';
                
                if (!fleetsLoaded) {
                     // Show loading state
                     fleetDropdownList.innerHTML = '<div style="text-align:center; padding:10px; color:#555;">Loading fleets...</div>';
                     
                     ajaxInterface.getSavedFleets(function(fleets) {
                        cachedFleets = fleets;
                        fleetsLoaded = true;
                        gamedata.populateFleetDropdown();
                    });
                }
            }
        });

        // Close dropdown if clicked outside
        document.addEventListener('click', (e) => {
            if (!fleetDropdownButton.contains(e.target) && !fleetDropdownList.contains(e.target)) {
                fleetDropdownList.style.display = 'none';
            }
        });

        const fleetInput = document.getElementById("fleetIdInput");
        const fleetIdForm = document.getElementById("fleetIdForm");

        // Sanitize input on each keystroke: allow only digits
        fleetInput.addEventListener("input", function() {
            // Remove any non-digit characters
            this.value = this.value.replace(/\D/g, "");
        });

        function submitFleetId() {
            const fleetId = fleetInput.value.trim();
            if (fleetId === "" || isNaN(fleetId)) {
                window.confirm.fleetNotice("Enter the numeric ID of the fleet you want to load.");
                return;
            }
            fleetInput.blur();   // dismiss the on-screen keyboard before the dialog opens
            gamedata.loadSavedFleetById(parseInt(fleetId, 10));
        }

        /* The form submit is the path that phone keyboards actually take (their GO key
           submits the form rather than emitting an Enter keydown), and it is also what the
           Load button fires. The keydown handler stays for desktop Enter and for any
           keyboard that emits Enter without submitting - submitFleetId is idempotent, and
           preventDefault stops the two firing twice for one press. */
        fleetIdForm.addEventListener("submit", function(event) {
            event.preventDefault();
            submitFleetId();
        });

        fleetInput.addEventListener("keydown", function(event) {
            if (event.key === "Enter") {
                event.preventDefault();
                submitFleetId();
            }
        });

    </script>

        <!-- Fleet selection area -->
        <table class="store store-layout-table">
            <tr>
                <td class="store-left-col">
                    <div id="store" class="subpanel"></div>
                </td>            
                <td class="store-right-col">
                    <div id="fleet" class="subpanel fleet-panel-style"></div>
                </td>
            </tr>
        </table>

			
        <div class="action-buttons-row">
            <a href="./fleetchecker.php" title="Details of fleet composition rules" target="_blank" class="fleet-checker-link-style">Fleet Checker rules</a>
            &nbsp;            
            <span class="btn btn-primary-lobby checkbutton">CHECK</span>
            &nbsp;&nbsp;
            <span class="btn btn-primary-lobby savebutton">SAVE FLEET</span>
            &nbsp;&nbsp;            
            <?php if(!$isFleetTest): ?>
            <span class="btn btn-success-lobby readybutton">READY</span>
            <?php endif; ?>
        </div>

    </div> <!-- Final closing of the .buy panel -->

        <!-- ✅ Your inserted fleetcheck panel -->
        <div id="fleetcheck" class="panel large lobby fleet-check-panel-container"><p id="fleetchecktxt" class="fleet-check-text-style"><span></div>

        <?php
        // A PANEL WRAPPER around #globalchat rather than the same element, matching
        // games.php and creategame.php: the head bar has to sit outside the scrolling
        // body or it scrolls away with the message log. .fv-chat-panel (chat.css) takes
        // over the padding and the flow; .panel.large.lobby keeps the lobby's own width,
        // border and fill.
        ?>
        <section class="panel large lobby global-chat-wrapper fv-chat-panel">
        <div id="globalchat">
        <?php
            $chatgameid = 0;
            $chatelement = "#globalchat";
            $chattitle = "Global Chat";
            $chatmeta = "All players";
            include("chat.php")
        ?>
        </div>
        </section>

<!--        <div id="globalhelp" class="helppanel">
        <?php
//        	$messagelocation='gamelobby.php';
//        	$ingame=false;
//        	include("helper.php")
        ?>
        </div>-->
                    
    <!-- React mounts (ship-window redesign Stage 3). Fixed full-viewport wrappers so
         the absolutely-positioned windows/tooltips anchor to the VIEWPORT on this
         scrolling page (the legacy equivalent was lobby.css forcing .shipwindow to
         position: fixed). pointer-events is re-enabled inside the components
         (ShipWindowContainer); the info tooltip stays click-through by design. -->
    <div id="shipWindowsReact" style="position:fixed; inset:0; pointer-events:none; z-index:10001;"></div>
    <div id="systemInfoReact" style="position:fixed; inset:0; pointer-events:none; z-index:20000;"></div>

                    
    <!-- Cloned by gamedata.createNewSlot, one per team; paintLobbyTeams sets its --rail colour. -->
    <div id="lobbyTeamTemplate" style="display:none;">
        <div class="team-section lb-team" data-team-id="">
             <h3 class="lb-team-head">Team <span class="team-number"></span></h3>
             <div class="slotcontainer lb-slots"></div>
        </div>
    </div>

    <!-- Cloned WITH its events (clone(true)) by gamedata.createNewSlot: the Take / Select / Leave
         handlers are bound to these template buttons at load, so the classes must stay. Which of
         them shows is the .taken / .ready / .selected classes plus createSlots' show() / hide(). -->
    <div id="slottemplatecontainer" class="hidden-template-container">
        <div class="slot lb-slot">
            <div class="lb-slot-main">
                <div class="lb-slot-player">
                    <span class="playername"></span><span class="lb-slot-open">[Open slot]</Open></span>
                </div>
                <div class="lb-slot-meta">
                    <span class="value name"></span>
                    <span class="lb-slot-sep" aria-hidden="true">&middot;</span>
                    <span class="value points"></span>
                    <span class="lb-slot-late"><span class="lb-slot-sep" aria-hidden="true">&middot;</span> deploys T<span class="value depavailable"></span></span>
                </div>
            </div>
            <div class="lb-slot-actions">
                <span class="status">Ready</span>
                <button type="button" class="takeslot lb-pill lb-pill--take">Take Slot</button>
                <button type="button" class="selectslot lb-pill">Select</button>
                <button type="button" class="leaveslot lb-pill lb-pill--leave">Leave Slot</button>
            </div>
        </div>
    </div>

    <div id="systemtemplatecontainer" class="hidden-template-container">

        <div class="structure system">
            <div class="name"><span class="namevalue">STRUCTURE</span></div>
            <div class="systemcontainer">

                <div class="health systembarcontainer">
                    <div class="healthbar bar health-bar-initial"></div>
                    <div class="valuecontainer"><span class="healthvalue value"></span></div>
                </div>
            </div>
        </div>

        <div class="fightersystem">
            <div class="icon">
                <span class="efficiency value"></span>
                <div class="iconmask"></div>
            </div>
        </div>

        <div class="system regular">
            <div class="systemcontainer">
                <div class="icon">
                    <div class="efficiency value"></div>
                    <div class="iconmask"></div>
                    <div class="UI">
                        <div class="button stopoverload"></div>
                        <div class="button overload"></div>
                        <div class="button plus"></div>
                        <div class="button minus"></div>
                        <div class="button off"></div>
                        <div class="button on"></div>
                        <div class="button holdfire"></div>
                        <div class="button mode"></div>
                    </div>
                </div>

                <div class="health systembarcontainer">
                    <div class="healthbar bar" style="width:40px;"></div>
                </div>
                <div class="critical systembarcontainer">
                    <div class="valuecontainer"><span class="criticalvalue value">CRITICAL<span></div>
                </div>

            </div>
        </div>

        <div class="fighter">
            <div class="destroyedtext"><span>DESTROYED</span></div>
            <div class="disengagedtext"><span>DISENGAGED</span></div>
            <div class="dockedtext"><span>DOCKED</span></div>
            <div class="systemcontainer">
                <div class="icon">
                    <table class="fightersystemcontainer 1"><tr></tr></table>
                    <div style="height:60px;"></div>
                    <table class="fightersystemcontainer 2"><tr></tr></table>
                </div>

                <div class="health systembarcontainer">
                    <div class="healthbar bar" style="width:90px;"></div>
                    <div class="valuecontainer"><span class="healthvalue value"></span></div>
                </div>
            </div>
        </div>

        <div class="heavyfighter">
            <div class="systemcontainer">
                <div class="icon">
                    <table class="fightersystemcontainer 1"><tr></tr></table>
                    <div style="height:60px;"></div>
                    <table class="fightersystemcontainer 2"><tr></tr></table>
                </div>

                <div class="health systembarcontainer">
                    <div class="healthbar bar" style="width:90px;"></div>
                    <div class="valuecontainer"><span class="healthvalue value"></span></div>
                </div>
            </div>
        </div>
        
    </div>

    <div class="missileSelectItem" style="display:none">
        <span>
            <span class="selectText"></span>
            <span class="selectAmount"></span>
            <span class="selectButtons">
                <table>
                    <tr>
                        <td><span class="plusButton"></span></td>
                    </tr>
                    <tr>
                        <td><span class="minusButton"></span></td>
                    </tr>
                </table>
            </span>
        </span>
    </div>
        
    <div class="totalUnitCost" style="display: none">
        <span>
            <span class="totalUnitCostText"></span>
            <span class="totalUnitCostAmount"></span>
        </span>
    </div>   
    </main>	
    
<div id="global-blocking-overlay" class="blocking-overlay" style="display:none;">
    <span>
        TRANSMITTING ORDERS...<br>
        <span class="blocking-warning">Do not close window</span>
    </span>

</div>    

<footer class="site-disclaimer">
  <p>
DISCLAIMER — Fiery Void is an unofficial, fan-created work based on concepts from Agents of Gaming’s Babylon 5 Wars. 
It is not affiliated with, endorsed by, or sponsored by any official rights holders. 
All trademarks and copyrights remain the property of their respective owners.
  </p>
</footer>


	</body>
</html>

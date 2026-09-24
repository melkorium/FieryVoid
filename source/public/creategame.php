<?php
    include_once 'global.php';

	if (!isset($_SESSION["user"]) || $_SESSION["user"] == false){
		header('Location: index.php');
	}
	if (!Manager::canCreateGame($_SESSION["user"])){
		header('Location: games.php');
	}

	$maps = Manager::getMapBackgrounds();
	$defaultGameName = 'GAME NAME' . $_SESSION["user"];
	$playerName = Manager::getPlayerName($_SESSION["user"]);
	//if ($playerName != '')
		$defaultGameName = ucfirst($playerName) . "'s Game";

	if (isset($_POST["docreate"]) && isset($_POST["data"])){
		$id = Manager::createGame($_SESSION["user"], $_POST["data"]);
		if ($id){
			header("Location: gamelobby.php?gameid=$id");
		}

	}

	natsort($maps); // Natural sort: sorts "1", "2", ..., "10", "11"

	//"12.PlanetOrange.jpg" -> "PlanetOrange"
	function cgMapDisplayName($file) {
		return preg_replace(array('/^\d+\./', '/\.[^.]+$/'), '', $file);
	}

	//<option>s 0..$max for a short Terrain Features count (the moon rows).
	function cgCountOptions($max) {
		$html = '';
		for ($i = 0; $i <= $max; $i++) {
			$html .= "<option value=\"$i\">$i</option>";
		}
		return $html;
	}
	$fieldPresets = array(0 => 'None', 3 => 'Few', 6 => 'Several', 12 => 'Pack', 18 => 'Lots', 24 => 'Horde', 36 => 'Swarm', 48 => 'Zounds');

	//A count that takes ANY value 0..$max - typed, or stepped with the mouse wheel while focused -
	//and also offers a short list of named presets behind its ▾ (createGame.initCountCombos).
	//The old page's asteroid box worked this way; this is that control rebuilt accessibly.
	function cgCountCombo($id, $label, $max, $presets) {
		$list = '';
		foreach ($presets as $value => $name) {
			$list .= "<li role=\"option\" id=\"{$id}_opt{$value}\" class=\"cg-combo-option\" data-value=\"$value\">$name ($value)</li>";
		}
		return "<div class=\"cg-combo\">"
			. "<input type=\"text\" id=\"$id\" class=\"cg-input cg-combo-input cg-terrain-count\" value=\"0\""
			. " inputmode=\"numeric\" pattern=\"[0-9]*\" maxlength=\"" . strlen((string)$max) . "\" min=\"0\" max=\"$max\" step=\"1\""
			. " autocomplete=\"off\" role=\"combobox\" aria-autocomplete=\"none\" aria-expanded=\"false\" aria-controls=\"{$id}_list\">"
			. "<button type=\"button\" class=\"cg-combo-toggle\" tabindex=\"-1\" aria-label=\"$label presets\"><span class=\"cg-chevron\" aria-hidden=\"true\"></span></button>"
			. "<ul class=\"cg-combo-list\" id=\"{$id}_list\" role=\"listbox\" aria-label=\"$label presets\" hidden>$list</ul>"
			. "</div>";
	}

?>

<!DOCTYPE HTML>
<html>
	<head>
		<title>Fiery Void - Create game</title>
		<meta http-equiv="Content-Type" content="text/html; charset=utf-8">
		<!-- Without this a phone lays the page out ~980px wide and scales it down, and none of the
		     responsive rules in createGame.css ever apply. -->
		<meta name="viewport" content="width=device-width, initial-scale=1">
		<!-- Shared fv design tokens (roadmap item 6): MUST load before every other stylesheet. -->
		<link href="<?php echo AssetLoader::getAssetUrl('styles/tokens.css'); ?>" rel="stylesheet" type="text/css">
		<link href="<?php echo AssetLoader::getAssetUrl('styles/base.css'); ?>" rel="stylesheet" type="text/css">
        <link href="<?php echo AssetLoader::getAssetUrl('styles/confirm.css'); ?>" rel="stylesheet" type="text/css">
        <link href="<?php echo AssetLoader::getAssetUrl('styles/ladder.css'); ?>" rel="stylesheet" type="text/css">
        <link href="<?php echo AssetLoader::getAssetUrl('styles/lobby.css'); ?>" rel="stylesheet" type="text/css">
        <link href="<?php echo AssetLoader::getAssetUrl('styles/gamesNew.css'); ?>" rel="stylesheet" type="text/css">
        <link href="<?php echo AssetLoader::getAssetUrl('styles/createGame.css'); ?>" rel="stylesheet" type="text/css">
        <link href="<?php echo AssetLoader::getAssetUrl('styles/scenarioCard.css'); ?>" rel="stylesheet" type="text/css">
        <script src="<?php echo AssetLoader::getAssetUrl('client/lib/jquery-4.0.0.min.js'); ?>"></script>
<!--		<script src="client/helper.js"></script>-->
        <script src="client/mathlib.js"></script>
        <script src="client/UI/confirm.js"></script>
        <script src="<?php echo AssetLoader::getAssetUrl('client/UI/scenarioCard.js'); ?>"></script>
        <!-- Versioned: this script and the markup below change together, and a browser holding
             yesterday's copy against today's page would find none of its ids. -->
        <script src="<?php echo AssetLoader::getAssetUrl('client/UI/createGame.js'); ?>"></script>
        <script src="client/ajaxInterface.js"></script>
        <script src="client/ladder.js"></script>
	</head>
	<body class="creategame">
  <header class="pageheader">
    <img src="img/logo.png" alt="Fiery Void Logo" class="logo">
    <div class="top-right-row">
      <a href="games.php">Back to Game Lobby</a>
      <a href="logout.php" class="btn btn-primary">Logout</a>
    </div>
  </header>

  <main class="container">
    <form id="createGameForm" method="post" class="cg-page">

      <h1 class="cg-title">Create Game</h1>

      <!-- ═══ GAME OPTIONS ═══ -->
      <section class="cg-section" aria-labelledby="cgOptionsHead">
        <h2 class="cg-section-head" id="cgOptionsHead">Game Options</h2>
        <div class="cg-section-body">

          <div class="cg-field cg-name-field">
            <label for="gamename" class="cg-label">Game Name</label>
            <input id="gamename" class="gamename cg-input" type="text" name="gamename" value="<?php print(htmlspecialchars($defaultGameName)); ?>">
          </div>

          <!-- Background: the thumbnails ARE the choice. A radio group, so it is one Tab stop
               with arrow-key selection, exactly like the <select> it replaces. -->
          <fieldset class="cg-card cg-bg-card">
            <legend class="cg-card-label">Background</legend>
            <div class="cg-bg-strip">
              <?php
                $first = true;
                foreach ($maps as $name) {
                    $file = htmlspecialchars($name);
                    $display = htmlspecialchars(cgMapDisplayName($name));
                    print('<label class="cg-bg-tile" title="' . $display . '">'
                        . '<input type="radio" name="background" value="' . $file . '"' . ($first ? ' checked' : '') . '>'
                        . '<img src="img/maps/' . $file . '" alt="" loading="lazy" decoding="async">'
                        . '<span class="cg-sr">' . $display . '</span>'
                        . '</label>');
                    $first = false;
                }
              ?>
            </div>
            <div class="cg-caption">Selected: <span id="bgSelectedName"></span></div>
          </fieldset>

          <div class="cg-options-grid">

            <div class="cg-card">
              <h3 class="cg-card-label">Rules &amp; Options</h3>

              <div class="cg-check-row">
                <input id="laddercheck" type="checkbox" name="laddercheck">
                <div class="cg-check-body">
                  <div class="cg-check-line">
                    <label for="laddercheck" class="cg-check-label">Ladder Game</label>
                    <button type="button" class="btn-ladder cg-link">View Ladder</button>
                  </div>
                  <div class="cg-caption">Counts toward the ladder standings. Limits the game to two teams of one slot each.</div>
                </div>
              </div>

              <div class="cg-check-row">
                <input id="movementcheck" type="checkbox" name="movementcheck">
                <div class="cg-check-body">
                  <div class="cg-check-line">
                    <label for="movementcheck" class="cg-check-label">Simultaneous Movement</label>
                    <span id="movementDropdown" class="cg-dep">
                      <label for="initiativeSelect" class="cg-sr">Number of brackets</label>
                      <select id="initiativeSelect" name="initiativeCategories" class="cg-input cg-input--inline">
                        <?php
                        for ($i = 1; $i <= 12; $i++) {
                            $selected = ($i == SimultaneousMovementRule::$defaultNoOfCategories) ? ' selected' : '';
                            print("<option value=\"$i\"$selected>$i " . ($i == 1 ? 'Bracket' : 'Brackets') . "</option>");
                        }
                        ?>
                      </select>
                    </span>
                  </div>
                  <div class="cg-caption">Units move in initiative brackets, a whole bracket at a time, instead of one unit at a time.</div>
                </div>
              </div>

              <div class="cg-check-row">
                <input id="allowMinesCheck" type="checkbox" name="allowMinesCheck">
                <div class="cg-check-body">
                  <label for="allowMinesCheck" class="cg-check-label">Mines Allowed</label>
                  <div class="cg-caption">Mine units may be bought in the lobby.</div>
                </div>
              </div>

              <!-- REINFORCEMENTS_PLAN.md 2.1: off means the whole feature does not exist -
                   the lobby sells no reinforcements, so nothing is ever flagged and no jump
                   point exit can be declared. -->
              <div class="cg-check-row">
                <input id="allowReinforcementsCheck" type="checkbox" name="allowReinforcementsCheck">
                <div class="cg-check-body">
                  <label for="allowReinforcementsCheck" class="cg-check-label">Reinforcements Allowed</label>
                  <div class="cg-caption">Units may be bought as reinforcements, arriving later through a jump point.</div>
                </div>
              </div>

              <div class="cg-check-row">
                <input id="desperatecheck" type="checkbox" name="desperatecheck">
                <div class="cg-check-body">
                  <div class="cg-check-line">
                    <label for="desperatecheck" class="cg-check-label">Desperate Scenario</label>
                    <span id="desperateDropdown" class="cg-dep">
                      <label for="desperateSelect" class="cg-sr">Apply Desperate rules to</label>
                      <select id="desperateSelect" name="desperateCategories" class="cg-input cg-input--inline">
                        <option value="-1">Both teams</option>
                        <option value="1">Team 1</option>
                        <option value="2">Team 2</option>
                      </select>
                    </span>
                  </div>
                  <div class="cg-caption">Desperate rules: ramming is allowed, and a ship may jump out on a damaged jump drive.</div>
                </div>
              </div>

              <div class="cg-check-row">
                <input id="friendlyFireCheck" type="checkbox" name="friendlyFireCheck">
                <div class="cg-check-body">
                  <label for="friendlyFireCheck" class="cg-check-label">Friendly Fire</label>
                  <div class="cg-caption">Weapons can hit units on their own side.</div>
                </div>
              </div>

              <div class="cg-check-row">
                <input id="unlimitedPointsCheck" type="checkbox" name="unlimitedPointsCheck">
                <div class="cg-check-body">
                  <label for="unlimitedPointsCheck" class="cg-check-label">Unlimited Points</label>
                  <div class="cg-caption">No fleet points cap for any slot.</div>
                </div>
              </div>
            </div>

            <!-- Terrain Features: one fixed row per type (plan §8.4). Zero = none of that type;
                 createGame.readTerrain() turns the rows into rules. -->
            <div class="cg-card">
              <h3 class="cg-card-label">Terrain Features</h3>
              <div class="cg-terrain-row"><label for="asteroidsSelect">Asteroids</label><?php print(cgCountCombo('asteroidsSelect', 'Asteroids', 48, $fieldPresets)); ?></div>
              <div class="cg-terrain-row"><label for="moonsSmallSelect">Moons (Small)</label><select id="moonsSmallSelect" class="cg-input cg-terrain-count"><?php print(cgCountOptions(5)); ?></select></div>
              <div class="cg-terrain-row"><label for="moonsMediumSelect">Moons (Medium)</label><select id="moonsMediumSelect" class="cg-input cg-terrain-count"><?php print(cgCountOptions(4)); ?></select></div>
              <div class="cg-terrain-row"><label for="moonsLargeSelect">Moons (Large)</label><select id="moonsLargeSelect" class="cg-input cg-terrain-count"><?php print(cgCountOptions(2)); ?></select></div>
              <div class="cg-terrain-row"><label for="dustSelect">Dust Field</label><?php print(cgCountCombo('dustSelect', 'Dust', DustAndMeteorsRule::$maxCount, $fieldPresets)); ?></div>
              <div class="cg-terrain-row"><label for="meteorsSelect">Meteor Swarms</label><?php print(cgCountCombo('meteorsSelect', 'Meteor Swarms', DustAndMeteorsRule::$maxCount, $fieldPresets)); ?></div>
              <div class="cg-caption">Placed at random when the game starts. Dust and Meteor Swarms are single hexes that may sit right next to other terrain.</div>
            </div>

          </div>
        </div>
      </section>

      <!-- ═══ SCENARIO DESCRIPTION ═══ built by createGame.renderScenarioFields() from
           scenarioCard.FIELDS, which is also what the stored JSON is checked against. -->
      <section class="cg-section" aria-labelledby="cgScenarioHead">
        <h2 class="cg-section-head" id="cgScenarioHead">Scenario Description</h2>
        <div class="cg-section-body">
          <p class="cg-intro">Every player sees this in the game lobby before choosing a fleet.</p>
          <div id="scenarioFields" class="cg-scn-grid"></div>
        </div>
      </section>

      <!-- ═══ TEAMS & MAP ═══ -->
      <section class="cg-section" aria-labelledby="cgTeamsHead">
        <h2 class="cg-section-head" id="cgTeamsHead">Teams &amp; Map</h2>
        <div class="cg-section-body">

          <div id="gamespace" class="cg-map-controls">
            <div class="cg-field cg-template-field">
              <label for="mapDimensionsSelect" class="cg-label">Map Template</label>
              <select id="mapDimensionsSelect" name="mapdimensions" class="mapSelect cg-input">
                <option value="custom">Custom</option>
                <option value="small">Small (30x24)</option>
                <option value="standard" selected>Standard (42x30)</option>
                <option value="large">Large (60x40)</option>
                <option value="2v2">2v2 (42x40)</option>
                <option value="ambush">Ambush (42x40)</option>
                <option value="baseAssault">Base Assault (60x40)</option>
                <option value="convoyRaid">Convoy Raid (42x30)</option>
                <option value="northvsouth">North Vs South (60x40)</option>
                <option value="3teams">Three Teams (42x30)</option>
                <option value="4teams">Four Teams (42x30)</option>
                <option value="unlimited">No Boundaries</option>
              </select>
            </div>
            <div class="gamespacedefinition">
              <span class="unlimitedspace cg-field">
                <span class="cg-label">Map Size</span>
                <span class="cg-static">No Boundaries</span>
              </span>
              <span class="limitedspace cg-size-fields invisible">
                <span class="cg-field">
                  <label for="spacex" class="cg-label">Width</label>
                  <input id="spacex" class="spacex cg-input" data-validation="^-{0,1}[0-9]+$" data-default="0" type="number" name="spacex" value="0">
                </span>
                <span class="cg-field">
                  <label for="spacey" class="cg-label">Height</label>
                  <input id="spacey" class="spacey cg-input" data-validation="^-{0,1}[0-9]+$" data-default="0" type="number" name="spacey" value="0">
                </span>
              </span>
            </div>
          </div>

          <div class="cg-teams-map">
            <div class="cg-card cg-map-card">
              <h3 class="cg-card-label">Map Preview</h3>
              <div id="mapPreviewContainer" class="cg-map-frame">
                <canvas id="mapPreview" width="545" height="390"></canvas>
              </div>
              <div id="mapLegend" class="cg-legend"></div>
            </div>

            <div class="cg-teams-col">
              <div id="teamsContainer">
                <!-- Teams will be injected here -->
              </div>
              <button type="button" id="addTeamBtn" class="cg-btn">+ Add Team</button>
            </div>
          </div>

        </div>
      </section>

      <input type="hidden" name="docreate" value="true">
      <input id="createGameData" type="hidden" name="data" value="">

      <div class="cg-actions">
        <button type="submit" class="cg-btn cg-btn--create create-game-btn">Create Game</button>
      </div>

      <!-- Template for Team (Hidden). renderTeams() clones it and paints --rail per team. -->
      <div id="teamtemplatecontainer" hidden>
        <div class="team-section cg-card cg-team" data-team-id="">
          <div class="cg-team-head">
            <h3 class="cg-team-name">Team <span class="team-number"></span></h3>
            <button type="button" class="btn-remove-team remove-team-btn cg-link">Remove Team</button>
          </div>
          <div class="slotcontainer"></div>
          <div class="add-slot-wrapper"><button type="button" class="addslotbutton btn-add-slot cg-btn cg-btn--small">+ Add Slot</button></div>
        </div>
      </div>

      <!-- Template for Slots (Hidden). Input NAMES are the slot model's keys (inputChange). -->
      <div id="slottemplatecontainer" hidden>
        <div class="slot-card slot">
          <div class="cg-slot-head">
            <label class="cg-slot-field cg-slot-name">
              <span class="cg-mini-label">Slot Name</span>
              <input class="name cg-input" type="text" name="name" value="BLUE">
            </label>
            <button type="button" class="close remove-btn cg-link">Remove Slot</button>
          </div>
          <div class="cg-slot-grid">
            <label class="cg-slot-field">
              <span class="cg-mini-label">Points</span>
              <input class="points cg-input" type="text" inputmode="numeric" data-validation="^[0-9]+$" name="points" value="0">
              <span class="unlimited-label cg-static" style="display:none;">Unlimited</span>
            </label>
            <label class="cg-slot-field">
              <span class="cg-mini-label">Deploys Turn</span>
              <input class="depavailable cg-input" type="number" name="depavailable" value="1" min="1">
            </label>
            <label class="cg-slot-field">
              <span class="cg-mini-label">Deploy X</span>
              <input class="depx cg-input" data-validation="^-{0,1}[0-9]+$" data-default="0" type="number" name="depx" value="0">
            </label>
            <label class="cg-slot-field">
              <span class="cg-mini-label">Deploy Y</span>
              <input class="depy cg-input" type="number" name="depy" value="1">
            </label>
            <label class="cg-slot-field">
              <span class="cg-mini-label">Width</span>
              <input class="depwidth cg-input" type="number" name="depwidth" value="0">
            </label>
            <label class="cg-slot-field">
              <span class="cg-mini-label">Height</span>
              <input class="depheight cg-input" type="number" name="depheight" value="0">
            </label>
          </div>
        </div>
      </div>

    </form>

        <?php
        // The chat is now a PANEL WRAPPER around #globalchat rather than the same element,
        // matching games.php: the head bar has to sit outside the scrolling body, or it
        // would scroll away with the message log. .fv-chat-panel (chat.css) takes over the
        // padding and the flow; .panel.large.create keeps the page's own border and fill.
        ?>
        <section class="panel large create fv-chat-panel" style="height:230px; margin-top: 15px;">
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

  </main>

<footer class="site-disclaimer">
  <p>
DISCLAIMER — Fiery Void is an unofficial, fan-created work based on concepts from Agents of Gaming’s Babylon 5 Wars.
It is not affiliated with, endorsed by, or sponsored by any official rights holders.
All trademarks and copyrights remain the property of their respective owners.
  </p>
</footer>

<?php include("ladder.php"); ?>
</body>
</html>

"use strict";

jQuery(function ($) {
    $("input[name='background']").on("change", createGame.mapSelect);

    // Bind global Add Team button
    $("#addTeamBtn").on("click", function () {
        createGame.addTeam();
    });

    createGame.mapSelect();

    $("body").on("change", "input", createGame.inputChange);
    $("body").on("change", "select", createGame.inputChange);
    $("body").on("focus", "input", createGame.inputFocus);

    // Mousewheel support for number inputs
    // We use a non-passive listener on the document to ensure we can preventDefault() the scroll
    document.addEventListener("wheel", function (e) {
        // Support standard number inputs AND our special .points text input
        const isNumberInput = (e.target.tagName === 'INPUT' && e.target.type === 'number');
        const isPointsInput = (e.target.tagName === 'INPUT' && $(e.target).hasClass('points'));
        const isComboInput = (e.target.tagName === 'INPUT' && $(e.target).hasClass('cg-combo-input'));

        if (!isNumberInput && !isPointsInput && !isComboInput) return;
        if (document.activeElement !== e.target) return; // Only if focused

        e.preventDefault();

        // Determine direction
        const delta = e.deltaY;
        const input = $(e.target);
        const step = parseFloat(input.attr("step")) || 1;
        let val = parseFloat(input.val()) || 0;

        if (delta > 0) { // Scrolling down -> decrement
            val -= step;
        } else { // Scrolling up -> increment
            val += step;
        }

        // Respect min/max if present
        const min = input.attr("min");
        const max = input.attr("max");

        if (min !== undefined && val < parseFloat(min)) val = parseFloat(min);
        if (max !== undefined && val > parseFloat(max)) val = parseFloat(max);

        input.val(val);
        input.trigger("change"); // Update model

    }, { passive: false });

    // Scenario Description: built from scenarioCard.FIELDS, so it can never offer an option the
    // stored JSON's contract doesn't know. Binds its own reveal/help handlers.
    createGame.renderScenarioFields();

    // Terrain Features: the rules follow the rows, no master checkbox. The combos bind their own
    // clamp-on-change FIRST, so readTerrain always sees a value already inside 0..max.
    createGame.initCountCombos();
    $(".cg-terrain-count").on("change", createGame.readTerrain);
    createGame.readTerrain();

    // UNLIMITED POINTS LOGIC
    $("#unlimitedPointsCheck").on("change", function () {
        const isUnlimited = $(this).is(":checked");

        if (isUnlimited) {
            $(".points").hide();
            $(".unlimited-label").show(); // Show our custom label

            // Force update data model for all slots
            createGame.slots.forEach(slot => {
                slot.points = -1;
            });

        } else {
            $(".points").show();
            $(".unlimited-label").hide();

            // Reset visual and model
            $(".points").each(function () {
                $(this).val("3000"); // Standard default visual
                $(this).trigger("change"); // Trigger change to update model
            });
        }
    });

    // Use body delegation for dynamic elements if needed, though structure suggests static buttons for adding slots
    $(".addslotbutton").on("click", createGame.createNewSlot);
    // Delegate close button click since slots are dynamic
    // Delegate close button click to a static parent since slots and their containers are dynamic
    $("#teamsContainer").on("click", ".close", createGame.removeSlot);
    // Delegate remove team button
    $("#teamsContainer").on("click", ".remove-team-btn", function () {
        console.log("Remove Team Clicked");
        const rawId = $(this).closest(".team-section").data("team-id");
        const teamId = parseInt(rawId);
        console.log("Target Team ID:", teamId);
        createGame.removeTeam(teamId);
    });

    let allowSubmit = false;

    // Only set allowSubmit on real mouse or touch interaction
    $("#createGameForm button[type='submit']").on("mousedown touchstart", function () {
        allowSubmit = true;
    });

    $("#createGameForm").on("submit", function (e) {
        if (!allowSubmit) {
            e.preventDefault(); // Block submission from pressing Enter
            return false;
        }

        // Call your original setData function before submitting
        createGame.setData();

        allowSubmit = false; // Reset flag after submission
    });

    // Bind checkbox events
    $("#mapDimensionsSelect").on("change", createGame.onMapDimensionsChange);

    $("#movementcheck").on("click", createGame.doMovementCheck);
    $("#desperatecheck").on("click", createGame.doDesperateCheck);
    $("#allowMinesCheck").on("click", createGame.doAllowMinesCheck);
    $("#allowReinforcementsCheck").on("click", createGame.doAllowReinforcementsCheck);
    $("#friendlyFireCheck").on("click", createGame.doFriendlyFireCheck);
    $("#laddercheck").on("click", createGame.doLadderCheck);

    // A browser restoring this form (Back from the lobby, or a failed POST) re-ticks the boxes
    // WITHOUT firing click, which left a ticked box whose rule was never set. Read them once.
    createGame.doMovementCheck();
    createGame.doDesperateCheck();
    createGame.doAllowMinesCheck();
    createGame.doAllowReinforcementsCheck();
    createGame.doFriendlyFireCheck();
    if ($("#laddercheck").is(":checked")) createGame.doLadderCheck();
    if ($("#unlimitedPointsCheck").is(":checked")) $("#unlimitedPointsCheck").trigger("change");

    createGame.refreshSlotsUI();
    createGame.onMapDimensionsChange(); // Run on load
    createGame.drawMapPreview();
});

window.createGame = {
    gamespace_data: { width: 42, height: 30 },
    rules: {},
    slots: [
        { id: 1, team: 1, name: "Team 1", points: 3500, depx: -21, depy: 0, deptype: "box", depwidth: 10, depheight: 30, depavailable: 1 },
        { id: 2, team: 2, name: "Team 2", points: 3500, depx: 21, depy: 0, deptype: "box", depwidth: 10, depheight: 30, depavailable: 1 }
    ],
    slotid: 2,

    //The chosen background doubles as the page's own backdrop - a full-size preview for free.
    mapSelect: function mapSelect() {
        const checked = $("input[name='background']:checked");
        const val = checked.val();
        if (!val) return;

        $(".cg-bg-tile").removeClass("is-selected");
        checked.closest(".cg-bg-tile").addClass("is-selected");
        $("#bgSelectedName").text(checked.closest(".cg-bg-tile").attr("title") || val);
        $("body").css("background-image", "url(img/maps/" + val + ")");
    },

    inputFocus: function inputFocus(e) {
        const input = $(this);
        const value = input.val();
        input.data("oldvalue", value);
    },

    inputChange: function inputChange(e) {

        const input = $(this);
        const value = input.val();
        const inputname = input.attr("name");

        if (input.data("validation")) {
            const patt = new RegExp(input.data("validation"));
            if (value.length == 0 || !patt.test(value)) {
                input.val(input.data("oldvalue"));
                return;
            }
        }

        if (inputname == "spacex") {
            createGame.gamespace_data.width = parseInt(value);
            $("#mapDimensionsSelect").val("custom");
            createGame.drawMapPreview();
            return;
        }

        if (inputname == "spacey") {
            createGame.gamespace_data.height = parseInt(value);
            $("#mapDimensionsSelect").val("custom");
            createGame.drawMapPreview();
            return;
        }

        // Find parent slot
        const slot = input.closest(".slot");
        if (slot.length === 0) return; // Not inside a slot

        const slotId = slot.data("slotid");
        const data = createGame.getSlotData(slotId);

        if (!data) return;

        data[inputname] = value;

        if (inputname == "deptype") {
            // Logic for deptype if we re-enable it later
        }

        createGame.drawMapPreview();
    },

    drawMapPreview: function drawMapPreview() {
        const canvas = document.getElementById("mapPreview");
        if (!canvas) return;
        const ctx = canvas.getContext("2d");
        if (!ctx) return;

        const isLimited = $("#mapDimensionsSelect").val() !== "unlimited";

        // Use fixed width/height if unlimited is selected
        const mapWidth = isLimited ? (createGame.gamespace_data.width || 1) : 84;
        const mapHeight = isLimited ? (createGame.gamespace_data.height || 1) : 60;

        // Clear canvas
        ctx.clearRect(0, 0, canvas.width, canvas.height);

        // Background
        ctx.fillStyle = "#000000";
        ctx.fillRect(0, 0, canvas.width, canvas.height);

        // Margins and scale
        const margin = 10;
        const scaleX = (canvas.width - margin * 2) / mapWidth;
        const scaleY = (canvas.height - margin * 2) / mapHeight;
        const scale = Math.min(scaleX, scaleY); // Uniform scale

        // Calculate offset to center the map in the canvas
        const offsetX = (canvas.width - mapWidth * scale) / 2;
        const offsetY = (canvas.height - mapHeight * scale) / 2;

        // Draw Map Boundary
        ctx.fillStyle = "#050a10";
        ctx.fillRect(offsetX, offsetY, mapWidth * scale, mapHeight * scale);

        ctx.strokeStyle = "#deebffaf";
        ctx.lineWidth = 2;
        ctx.strokeRect(offsetX, offsetY, mapWidth * scale, mapHeight * scale);

        // Grid lines / Center lines
        ctx.save();
        ctx.globalAlpha = 0.4;
        ctx.strokeStyle = "#496791";
        ctx.lineWidth = 1;
        ctx.setLineDash([4, 4]);

        const centerX = offsetX + 6 + (mapWidth / 2) * scale;
        const centerY = offsetY + (mapHeight / 2) * scale;

        // Vertical Center Line
        ctx.beginPath();
        ctx.moveTo(centerX, offsetY);
        ctx.lineTo(centerX, offsetY + mapHeight * scale);
        ctx.stroke();

        // Horizontal Center Line
        ctx.beginPath();
        ctx.moveTo(offsetX, centerY);
        ctx.lineTo(offsetX + mapWidth * scale, centerY);
        ctx.stroke();

        ctx.restore();

        // Draw deployment zones
        // Iterate data model directly to ensure we catch all teams even if DOM is lagging
        const teamCount = createGame.getTeamIds().length;
        createGame.slots.forEach(function (slot) {
            const data = slot;
            const team = data.team;

            const x = parseInt(data.depx) || 0;
            const y = parseInt(data.depy) || 0;
            const w = parseInt(data.depwidth) || 0;
            const h = parseInt(data.depheight) || 0;

            const rgb = createGame.teamColor(team, teamCount);
            ctx.fillStyle = "rgba(" + rgb.join(",") + ", 0.35)";
            ctx.strokeStyle = "rgb(" + rgb.join(",") + ")";
            ctx.lineWidth = 1;

            // Adjust position to treat (x, y) as center
            // coordinate system: center of map is (0,0)
            // canvas origin is topleft

            const drawX = offsetX + (x - w / 2 + mapWidth / 2) * scale;
            const drawY = offsetY + ((mapHeight / 2) - y - (h / 2)) * scale;

            ctx.fillRect(drawX + 6, drawY, w * scale, h * scale);
            ctx.strokeRect(drawX + 6, drawY, w * scale, h * scale);

            // Draw slot number/TeamID
            ctx.save();
            ctx.fillStyle = "white";
            ctx.font = "bold 14px Arial";
            ctx.textAlign = "center";
            ctx.textBaseline = "middle";
            // Center of the box
            ctx.fillText(team, drawX + 6 + (w * scale) / 2, drawY + (h * scale) / 2);
            ctx.restore();
        });

        createGame.renderLegend(teamCount);
    },

    getTeamIds: function getTeamIds() {
        return [...new Set(createGame.slots.map(s => s.team))].sort((a, b) => a - b);
    },

    /* One colour per team for the map zones, the team cards' rails and the legend, so the three
       always agree - and agree with the colours the game itself will use. Copied from
       gamedata.js (not loaded on this page), plan §11.4:
         2 teams  - RELATIVE, as seen by the creator (who takes slot 1 on Team 1): own green,
                    enemy red. teamBaseColors[0] / [1].
         3+ teams - ABSOLUTE, by team number: teamBaseColorsMultiTeam.
       ⚠️ Keep in step with gamedata.teamBaseColors / teamBaseColorsMultiTeam. */
    teamColorsMultiTeam: [
        [50, 205, 50], [255, 150, 40], [40, 230, 230], [170, 90, 230],
        [240, 230, 60], [51, 173, 255], [230, 40, 230], [255, 80, 80]
    ],

    teamColor: function teamColor(team, teamCount) {
        if (teamCount <= 2) return (team === 1) ? [50, 205, 50] : [255, 80, 80];
        const palette = createGame.teamColorsMultiTeam;
        return palette[(team - 1) % palette.length];
    },

    renderLegend: function renderLegend(teamCount) {
        const html = createGame.getTeamIds().map(function (team) {
            const rgb = createGame.teamColor(team, teamCount);
            return '<span class="cg-legend-item"><span class="cg-swatch" style="background:rgb(' + rgb.join(",") + ')"></span>Team ' + team + "</span>";
        }).join("");
        $("#mapLegend").html(html);
    },

    doFlightCheck: function doFlightCheck(data) {
        var checkval = $("#flightSizeCheck:checked").val();
        if (checkval == "on") {
            createGame.variableFlights = 1;
        } else createGame.variableFlights = 0;
    },


    doDesperateCheck: function doDesperateCheck(data) {
        var checkval = $("#desperatecheck:checked").val();

        if (checkval == "on") {
            $("#desperateDropdown").show();
            var selectedValue = $("#desperateSelect").val();
            createGame.rules.desperate = parseInt(selectedValue, 10);

            $("#desperateSelect").off('change').on('change', function () {
                createGame.rules.desperate = parseInt($(this).val(), 10);
            });
        } else {
            $("#desperateDropdown").hide();
            delete createGame.rules.desperate;
        }
    },


    /* Count combos (creategame.php cgCountCombo): Asteroids, Dust, Meteor Swarms. Best of both, as
       the old asteroid box was - ANY value 0..max can be typed or stepped with the mouse wheel (the
       page's shared wheel handler), and the ▾ offers only the named presets.
       An ARIA combobox: the input keeps focus throughout and the highlighted preset is announced
       through aria-activedescendant. ArrowDown / Alt+ArrowDown opens, arrows move, Enter picks,
       Escape closes. The list opens on a click or tap of the field too, like the original, but
       NOT on Tab focus - a keyboard user tabbing past should not get a list in their way. */
    initCountCombos: function initCountCombos() {
        $(".cg-combo").each(function () {
            const combo = $(this);
            const input = combo.find(".cg-combo-input");
            const max = parseInt(input.attr("max"), 10);

            //Typed text: digits only as they type, then clamped once they are done.
            input.on("input", function () {
                const digits = this.value.replace(/[^0-9]/g, "");
                if (digits !== this.value) this.value = digits;
            });
            input.on("change", function () {
                let val = parseInt(this.value, 10);
                if (isNaN(val) || val < 0) val = 0;
                if (val > max) val = max;
                this.value = val;
            });

            input.on("click", function () { createGame.openCombo(combo); });

            //Focus the field after a MOUSE press on the ▾ (so the arrow keys and wheel work next),
            //but not after a tap: that would raise a phone's keyboard over the list just opened.
            let lastPointer = "mouse";
            combo.find(".cg-combo-toggle").on("pointerdown", function (e) {
                lastPointer = e.pointerType || "mouse";
            }).on("click", function () {
                if (combo.hasClass("is-open")) createGame.closeCombo(combo);
                else createGame.openCombo(combo);
                if (lastPointer === "mouse") input.trigger("focus");
            });

            //mousedown, not click: picking must not first blur the input.
            combo.on("mousedown", ".cg-combo-option", function (e) {
                e.preventDefault();
                createGame.pickComboValue(combo, $(this).data("value"));
            });

            input.on("keydown", function (e) {
                const open = combo.hasClass("is-open");
                const options = combo.find(".cg-combo-option");
                let active = options.index(options.filter(".is-active"));

                if (e.key === "ArrowDown" || e.key === "ArrowUp") {
                    e.preventDefault();
                    if (!open) { createGame.openCombo(combo); return; }
                    active = (e.key === "ArrowDown") ? Math.min(active + 1, options.length - 1) : Math.max(active - 1, 0);
                    createGame.setComboActive(combo, options.eq(active));
                } else if (e.key === "Enter" && open && active >= 0) {
                    e.preventDefault(); //never submit the form from here
                    createGame.pickComboValue(combo, options.eq(active).data("value"));
                } else if (e.key === "Escape" && open) {
                    e.preventDefault();
                    createGame.closeCombo(combo);
                } else if (e.key === "Tab" && open) {
                    createGame.closeCombo(combo);
                }
            });
        });

        //Any click or tap outside a combo closes whichever one is open.
        $(document).on("mousedown touchstart", function (e) {
            if ($(e.target).closest(".cg-combo").length) return;
            $(".cg-combo.is-open").each(function () { createGame.closeCombo($(this)); });
        });
    },

    openCombo: function openCombo(combo) {
        $(".cg-combo.is-open").not(combo).each(function () { createGame.closeCombo($(this)); });
        const input = combo.find(".cg-combo-input");
        combo.addClass("is-open");
        combo.find(".cg-combo-list").prop("hidden", false);
        input.attr("aria-expanded", "true");

        //Start on the preset matching the current value, if there is one.
        const current = String(parseInt(input.val(), 10) || 0);
        const options = combo.find(".cg-combo-option");
        options.attr("aria-selected", "false");
        const match = options.filter(function () { return String($(this).data("value")) === current; });
        match.attr("aria-selected", "true");
        createGame.setComboActive(combo, match.length ? match : options.first());
    },

    closeCombo: function closeCombo(combo) {
        combo.removeClass("is-open");
        combo.find(".cg-combo-list").prop("hidden", true);
        combo.find(".cg-combo-option").removeClass("is-active");
        combo.find(".cg-combo-input").attr("aria-expanded", "false").removeAttr("aria-activedescendant");
    },

    setComboActive: function setComboActive(combo, option) {
        combo.find(".cg-combo-option").removeClass("is-active");
        if (!option.length) return;
        option.addClass("is-active");
        combo.find(".cg-combo-input").attr("aria-activedescendant", option.attr("id"));
        option[0].scrollIntoView({ block: "nearest" });
    },

    //No refocus here: a mouse pick never lost focus (the mousedown is cancelled), and a tap on a
    //phone should not bring the keyboard up after the choice is already made.
    pickComboValue: function pickComboValue(combo, value) {
        combo.find(".cg-combo-input").val(value).trigger("change");
        createGame.closeCombo(combo);
    },

    /* Terrain Features rows -> rules. A type is in the rules only when its count is above zero,
       so a game with no terrain carries no terrain keys at all (it used to carry a zero asteroid
       count and an all-zero moons object whenever "Add Terrain" had been ticked). Server-side,
       each key is a GameRules entry: asteroids -> AsteroidsRule, moons -> MoonsRule,
       dustAndMeteors -> DustAndMeteorsRule. */
    readTerrain: function readTerrain() {
        const count = function (id) {
            const el = $("#" + id);
            const val = parseInt(el.val(), 10);
            const max = parseInt(el.attr("max"), 10);
            if (isNaN(val) || val < 0) return 0;
            return isNaN(max) ? val : Math.min(val, max);
        };

        const asteroids = count("asteroidsSelect");
        if (asteroids > 0) createGame.rules.asteroids = asteroids;
        else delete createGame.rules.asteroids;

        const moons = { small: count("moonsSmallSelect"), medium: count("moonsMediumSelect"), large: count("moonsLargeSelect") };
        if (moons.small + moons.medium + moons.large > 0) createGame.rules.moons = moons;
        else delete createGame.rules.moons;

        const fields = { dust: count("dustSelect"), meteors: count("meteorsSelect") };
        if (fields.dust + fields.meteors > 0) createGame.rules.dustAndMeteors = fields;
        else delete createGame.rules.dustAndMeteors;
    },

    doMovementCheck: function doMovementCheck(data) {
        var checkval = $("#movementcheck:checked").val();

        if (checkval == "on") {
            $("#movementDropdown").show();
            var selectedValue = $("#initiativeSelect").val();
            createGame.rules.initiativeCategories = parseInt(selectedValue, 10);

            $("#initiativeSelect").off('change').on('change', function () {
                createGame.rules.initiativeCategories = parseInt($(this).val(), 10);
            });
        } else {
            $("#movementDropdown").hide();
            delete createGame.rules.initiativeCategories;
        }
    },

    doAllowMinesCheck: function doAllowMinesCheck(data) {
        var checkval = $("#allowMinesCheck:checked").val();

        if (checkval == "on") {
            createGame.rules.allowMines = 1;
        } else {
            delete createGame.rules.allowMines;
        }
    },

    doAllowReinforcementsCheck: function doAllowReinforcementsCheck(data) {
        var checkval = $("#allowReinforcementsCheck:checked").val();

        if (checkval == "on") {
            createGame.rules.allowReinforcements = 1;
        } else {
            delete createGame.rules.allowReinforcements;
        }
    },

    doFriendlyFireCheck: function doFriendlyFireCheck(data) {
        var checkval = $("#friendlyFireCheck:checked").val();

        if (checkval == "on") {
            createGame.rules.friendlyFire = 1;
        } else {
            delete createGame.rules.friendlyFire;
        }
    },

    doLadderCheck: function doLadderCheck(data) {
        var checkval = $("#laddercheck:checked").val();
        var mapSelect = $("#mapDimensionsSelect");

        if (checkval == "on") {
            createGame.rules.ladder = 1;

            // Ladder requires strictly 1 slot per team.
            // Prune any extras.
            var team1 = createGame.slots.find(function (s) { return s.team === 1; });
            var team2 = createGame.slots.find(function (s) { return s.team === 2; });

            var newSlots = [];
            if (team1) newSlots.push(team1);
            if (team2) newSlots.push(team2);

            createGame.slots = newSlots;
            createGame.refreshSlotsUI();

            // Grey out forbidden maps
            var currentMap = mapSelect.val();
            createGame.forbiddenLadderMaps.forEach(function (mapVal) {
                var option = mapSelect.find('option[value="' + mapVal + '"]');
                option.prop('disabled', true);
                // Visual feedback (optional, but good for clarity)
                option.css('color', '#999');
            });

            // If current map is forbidden, switch to standard
            if (createGame.forbiddenLadderMaps.includes(currentMap)) {
                mapSelect.val("standard").trigger("change");
            }

            createGame.drawMapPreview();

        } else {
            delete createGame.rules.ladder;

            // Re-enable all maps
            createGame.forbiddenLadderMaps.forEach(function (mapVal) {
                var option = mapSelect.find('option[value="' + mapVal + '"]');
                option.prop('disabled', false);
                option.css('color', '');
            });

            createGame.refreshSlotsUI();
        }
    },

    forbiddenLadderMaps: ["2v2", "ambush", "baseAssault", "convoyRaid", "3teams", "4teams"],


    mapData: {
        "custom": {
            width: null, height: null,
            slotsRequired: { 1: 1, 2: 1 },
            teams: [
                { name: "Team 1", id: 1, depx: -19, depy: 0, depwidth: 5, depheight: 30, depavailable: 1 },
                { name: "Team 2", id: 2, depx: 18, depy: 0, depwidth: 5, depheight: 30, depavailable: 1 }
            ]
        },
        "small": {
            width: 30, height: 24,
            slotsRequired: { 1: 1, 2: 1 },
            teams: [
                { name: "Team 1", id: 1, depx: -12, depy: 0, depwidth: 7, depheight: 24, depavailable: 1 },
                { name: "Team 2", id: 2, depx: 11, depy: 0, depwidth: 7, depheight: 24, depavailable: 1 }
            ]
        },
        "standard": {
            width: 42, height: 30,
            slotsRequired: { 1: 1, 2: 1 },
            teams: [
                { name: "Team 1", id: 1, depx: -19, depy: 0, depwidth: 5, depheight: 30, depavailable: 1 },
                { name: "Team 2", id: 2, depx: 18, depy: 0, depwidth: 5, depheight: 30, depavailable: 1 }
            ]
        },
        "large": {
            width: 60, height: 40,
            slotsRequired: { 1: 1, 2: 1 },
            teams: [
                { name: "Team 1", id: 1, depx: -28, depy: 0, depwidth: 5, depheight: 40, depavailable: 1 },
                { name: "Team 2", id: 2, depx: 27, depy: 0, depwidth: 5, depheight: 40, depavailable: 1 }
            ]
        },
        "2v2": {
            width: 42, height: 40,
            // Enforce strictly 2 slots per team
            slotsRequired: { 1: 2, 2: 2 },
            teams: [
                {
                    id: 1,
                    depx: -19, depy: 0, depwidth: 5, depheight: 40,
                    slots: [
                        { name: "Team 1 (North)", depx: -19, depy: 10, depwidth: 5, depheight: 20, depavailable: 1 },
                        { name: "Team 1 (South)", depx: -19, depy: -10, depwidth: 5, depheight: 20, depavailable: 1 }
                    ]
                },
                {
                    id: 2,
                    depx: 18, depy: 0, depwidth: 5, depheight: 40,
                    slots: [
                        { name: "Team 2 (North)", depx: 18, depy: 10, depwidth: 5, depheight: 20, depavailable: 1 },
                        { name: "Team 2 (South)", depx: 18, depy: -10, depwidth: 5, depheight: 20, depavailable: 1 }
                    ]
                }
            ]
        },
        "ambush": {
            width: 42, height: 30,
            // Enforce strictly 2 slots per team
            slotsRequired: { 1: 1, 2: 2 },
            scenario: {
                //other: "The defender has been caught in an ambush! They must survive for 8 turns.",
            },
            teams: [
                {
                    id: 1,
                    depx: 0, depy: 0, depwidth: 12, depheight: 6,
                    slots: [
                        { name: "Ambushed", points: 3000, depx: 0, depy: 0, depwidth: 12, depheight: 6, depavailable: 1 }
                    ]
                },
                {
                    id: 2,
                    depx: 0, depy: 0, depwidth: 30, depheight: 5,
                    slots: [
                        { name: "Ambusher (North)", points: 2000, depx: 0, depy: 12, depwidth: 30, depheight: 5, depavailable: 1 },
                        { name: "Ambusher (South)", points: 2000, depx: 0, depy: -12, depwidth: 30, depheight: 5, depavailable: 1 }
                    ]
                }
            ]
        },
        "baseAssault": {
            width: 60, height: 40,
            // Enforce strictly 2 slots per team
            slotsRequired: { 1: 2, 2: 1 },
            teams: [
                {
                    id: 1,
                    depx: -19, depy: 0, depwidth: 5, depheight: 40,
                    slots: [
                        { points: 5000, name: "Fixed Defences", depx: -19, depy: 0, depwidth: 5, depheight: 20, depavailable: 1 },
                        { points: 5000, name: "Reinforcements", depx: -28, depy: 0, depwidth: 5, depheight: 40, depavailable: 3 }
                    ]
                },
                {
                    id: 2,
                    depx: 26, depy: 0, depwidth: 6, depheight: 40,
                    slots: [
                        { points: 10000, name: "Attackers", depx: 26, depy: 0, depwidth: 6, depheight: 40, depavailable: 1 }
                    ]
                }
            ]
        },
        "convoyRaid": {
            width: 42, height: 30,
            // Enforce strictly 2 slots per team
            slotsRequired: { 1: 3, 2: 1 },
            teams: [
                {
                    id: 1,
                    depx: -19, depy: 5, depwidth: 5, depheight: 10,
                    slots: [
                        { name: "Defenders", depx: -19, depy: 5, depwidth: 5, depheight: 10, depavailable: 2 },
                        { name: "Freighters", depx: -15, depy: -12, depwidth: 10, depheight: 3, depavailable: 1 },
                        { name: "Jumpgate", points: 1000, depx: 15, depy: 12, depwidth: 2, depheight: 2, depavailable: 1 }
                    ]
                },
                {
                    id: 2,
                    depx: 18, depy: 7, depwidth: 5, depheight: 16,
                    slots: [
                        { name: "Attackers", depx: 18, depy: 7, depwidth: 5, depheight: 16, depavailable: 1 }
                    ]
                }
            ]
        },
        "northvsouth": {
            width: 60, height: 40,
            // Enforce strictly 2 slots per team
            slotsRequired: { 1: 1, 2: 1 },
            teams: [
                { name: "North", id: 1, depx: 0, depy: 17, depwidth: 59, depheight: 5, depavailable: 1 },
                { name: "South", id: 2, depx: 0, depy: -17, depwidth: 59, depheight: 5, depavailable: 1 }
            ]
        },
        "3teams": {
            width: 42, height: 30,
            slotsRequired: { 1: 1, 2: 1, 3: 1 },
            teams: [
                { name: "Team 1", id: 1, depx: -19, depy: -7, depwidth: 5, depheight: 15, depavailable: 1 },
                { name: "Team 2", id: 2, depx: 18, depy: -7, depwidth: 5, depheight: 15, depavailable: 1 },
                { name: "Team 3", id: 3, depx: 0, depy: 12, depwidth: 15, depheight: 5, depavailable: 1 }
            ]
        },
        "4teams": {
            width: 42, height: 30,
            slotsRequired: { 1: 1, 2: 1, 3: 1, 4: 1 },
            teams: [
                { name: "Team 1", id: 1, depx: -19, depy: 0, depwidth: 5, depheight: 15, depavailable: 1 },
                { name: "Team 2", id: 2, depx: 18, depy: 0, depwidth: 5, depheight: 15, depavailable: 1 },
                { name: "Team 3", id: 3, depx: 0, depy: 12, depwidth: 15, depheight: 5, depavailable: 1 },
                { name: "Team 4", id: 4, depx: 0, depy: -12, depwidth: 15, depheight: 5, depavailable: 1 }
            ]
        },
        "unlimited": {
            width: null, height: null,
            slotsRequired: { 1: 1, 2: 1 },
            teams: [
                { name: "Team 1", id: 1, depx: -28, depy: 0, depwidth: 5, depheight: 40, depavailable: 1 },
                { name: "Team 2", id: 2, depx: 27, depy: 0, depwidth: 5, depheight: 40, depavailable: 1 }
            ]
        },
    },

    onMapDimensionsChange: function () {
        const val = $("#mapDimensionsSelect").val();
        const mapConfig = createGame.mapData[val];

        if (val === "unlimited") {
            $(".gamespacedefinition .unlimitedspace").removeClass("invisible");
            $(".gamespacedefinition .limitedspace").addClass("invisible");
        } else {
            $(".gamespacedefinition .unlimitedspace").addClass("invisible");
            $(".gamespacedefinition .limitedspace").removeClass("invisible");
        }

        if (mapConfig) {
            createGame.applyMapConfig(mapConfig);
        }

        createGame.drawMapPreview();
    },

    applyMapConfig: function (config) {
        if (config.width && config.height) {
            createGame.gamespace_data.width = config.width;
            createGame.gamespace_data.height = config.height;
            $(".spacex").val(config.width);
            $(".spacey").val(config.height);
        }

        // Handle slots
        // Handle slots
        if (config.slotsRequired) {
            // Enforce specific number of slots
            const teamIds = Object.keys(config.slotsRequired).map(Number);

            // First, remove teams that are not in the requirements (if we are being strict, but maybe better to just ensure the ones we need exist)
            // For now, let's just ensure the required ones exist.

            // NEW: Remove teams that are NOT in the required list
            // This allows switching from "4 Teams" back to "Standard" to cleanup
            const currentTeams = [...new Set(createGame.slots.map(s => s.team))];
            currentTeams.forEach(teamId => {
                if (!teamIds.includes(teamId)) {
                    // Remove all slots for this team
                    // createGame.removeTeam(teamId); // Suppressed to avoid confirmation

                    // Note: createGame.removeTeam usually asks for confirmation...
                    // But here we might want to force it?
                    // The removeTeam function:
                    // window.confirm.confirm("Are you sure you want to remove Team " + teamId + "?", function () { ... });

                    // We can't easily bypass the confirm in the current `removeTeam` implementation without modifying it.
                    // Instead, let's manually remove the slots for this team.

                    createGame.slots = createGame.slots.filter(s => s.team !== teamId);
                }
            });

            // Re-fetch slots after removal
            // Then ensure required teams exist
            teamIds.forEach(teamId => {
                const required = config.slotsRequired[teamId] || 0;
                if (required > 0) {
                    const defaults = config.teams ? config.teams.find(t => t.id === teamId) : null;
                    createGame.ensureTeamSlots(teamId, required, defaults);
                }
            });

        } else if (config.teams) {
            // Just update existing slots with defaults (Legacy behavior)
            for (let slot of createGame.slots) {
                const defaults = config.teams.find(t => t.id === slot.team);
                if (defaults) {
                    // Only copy specific properties to avoid overwriting everything
                    Object.assign(slot, {
                        depx: defaults.depx,
                        depy: defaults.depy,
                        depwidth: defaults.depwidth,
                        depheight: defaults.depheight
                    });
                }
            }
        }

        // Populating Aditional Info when you select a map is supported, but it's quite tricky to know when to clear it.  So for the moment it's not used.
        if (config.scenario) {
            for (const [key, value] of Object.entries(config.scenario)) {
                // If it's a dropdown that needs 'Other' to show custom input
                if (["req", "tier", "victory", "enhancements"].includes(key)) {
                    // Check if value is one of the options
                    const select = $(`#${key}`);
                    const optionExists = select.find(`option[value="${value}"]`).length > 0;

                    if (optionExists) {
                        select.val(value).trigger("change");
                    } else {
                        // Assume custom input
                        const triggerVal = {
                            "req": "Other",
                            "tier": "Other",
                            "victory": "Other",
                            "enhancements": "Up to X points"
                        }[key];

                        if (triggerVal) {
                            select.val(triggerVal).trigger("change");
                            $(`#${key}_custom`).val(value);
                        }
                    }
                } else {
                    // Standard input/textarea
                    $(`#${key}`).val(value);
                }
            }
        }

        createGame.refreshSlotsUI();
    },

    ensureTeamSlots: function (team, count, defaults) {
        // Find current slots for this team
        let teamSlots = createGame.slots.filter(s => s.team === team);

        // Add slots if needed
        while (teamSlots.length < count) {
            createGame.createNewSlot.call({ className: "team" + team }, null, defaults);
            // Refresh list
            teamSlots = createGame.slots.filter(s => s.team === team);
        }

        // Remove slots if too many
        while (teamSlots.length > count) {
            const slotToRemove = teamSlots[teamSlots.length - 1];
            createGame.removeSlotData(slotToRemove.id);
            // Also remove from DOM immediately to keep UI in sync before full refresh
            $(".slot.slotid_" + slotToRemove.id).remove();
            teamSlots = createGame.slots.filter(s => s.team === team);
        }

        // Update all slots with defaults or specific slot overrides
        if (defaults) {
            let i = 0;
            for (let slot of teamSlots) {
                let config = defaults;
                // Check for per-slot override
                if (defaults.slots && defaults.slots[i]) {
                    // Create a merged config where slot override takes precedence
                    config = $.extend({}, defaults, defaults.slots[i]);
                }

                Object.assign(slot, {
                    depx: config.depx,
                    depy: config.depy,
                    depwidth: config.depwidth,
                    depheight: config.depheight
                });

                if (config.points !== undefined) {
                    slot.points = config.points;
                }
                if (config.name !== undefined) {
                    if (!createGame.rules.ladder || !slot.isLadderPopulated) {
                        slot.name = config.name;
                    }
                }
                if (config.depavailable !== undefined) {
                    slot.depavailable = config.depavailable;
                }
                i++;
            }
        }

        createGame.refreshSlotsUI();
    },

    refreshSlotsUI: function () {
        createGame.renderTeams();
        $(".slotcontainer").empty();
        createGame.createSlotsFromArray();
        createGame.drawMapPreview(); // Ensure map updates

        if (createGame.rules.ladder) {
            $(".addslotbutton").hide();
            $(".slot .remove-btn").hide();
            $("#addTeamBtn").hide();
            $(".remove-team-btn").hide();
        } else {
            // Only show Add Team if map supports it (or is custom/unlimited)
            const mapType = $("#mapDimensionsSelect").val();
            // Allow adding teams on all maps for now as per user request/workflow
            const allowAddTeam = true; // ["custom", "unlimited"].includes(mapType);

            if (allowAddTeam) {
                $("#addTeamBtn").show();
                $(".remove-team-btn").show();
            } else {
                $("#addTeamBtn").hide();
                $(".remove-team-btn").hide();
            }

            $(".addslotbutton").show();
            createGame.updateSlotButtons();
        }
    },

    updateSlotButtons: function () {
        // Reset display first (or ensure we show/hide correctly)
        // $(".slot .remove-btn").css("display", ""); // Optional if we explicitly show/hide below

        const teams = [...new Set(createGame.slots.map(s => s.team))];
        teams.forEach(teamId => {
            const teamSlots = createGame.slots.filter(s => s.team === teamId);
            if (teamSlots.length <= 1) {
                $(`#team${teamId} .slot .remove-btn`).hide();
            } else {
                $(`#team${teamId} .slot .remove-btn`).show();
            }
        });
    },


    renderTeams: function () {
        // Identify all unique teams
        const teams = [...new Set(createGame.slots.map(s => s.team))].sort((a, b) => a - b);
        const container = $("#teamsContainer");

        // Remove teams that no longer exist
        container.find(".team-section").each(function () {
            const id = parseInt($(this).data("team-id"));
            if (!teams.includes(id)) {
                $(this).remove();
            }
        });

        // Add missing teams
        teams.forEach(teamId => {
            if (container.find(`.team-section[data-team-id="${teamId}"]`).length === 0) {
                const template = $("#teamtemplatecontainer .team-section").clone();
                template.attr("data-team-id", teamId);
                template.find(".team-number").text(teamId);

                // Add specific ID for slot container targeting
                template.find(".slotcontainer").attr("id", "team" + teamId);

                // Update Add Slot button
                template.find(".addslotbutton").addClass("team" + teamId).data("team", teamId);

                // Bind remove team
                // Show/Hide remove team button based on team ID
                if (teamId > 2) {
                    template.find(".remove-team-btn").show();
                } else {
                    template.find(".remove-team-btn").hide();
                }

                // Bind add slot
                template.find(".addslotbutton").on("click", createGame.createNewSlot);

                container.append(template);
            }
        });

        // Repaint EVERY team, not just new ones: going from 2 teams to 3 switches the palette
        // (Team 2 goes from enemy red to its absolute orange), see teamColor().
        container.find(".team-section").each(function () {
            const rgb = "rgb(" + createGame.teamColor(parseInt($(this).data("team-id")), teams.length).join(",") + ")";
            this.style.setProperty("--rail", rgb);
            $(this).find(".cg-team-name").css("color", rgb);
        });
    },

    addTeam: function () {
        const teams = [...new Set(createGame.slots.map(s => s.team))];
        const nextTeamId = (teams.length > 0 ? Math.max(...teams) : 0) + 1;

        // Determine default deployment based on Odd/Even
        let defaults = {};
        if (nextTeamId % 2 !== 0) {
            // Odd -> Mimic Team 1
            const t1 = createGame.slots.find(s => s.team === 1);
            if (t1) {
                defaults = { depx: t1.depx, depy: t1.depy, depwidth: t1.depwidth, depheight: t1.depheight };
            } else {
                defaults = { depx: -19, depy: 0, depwidth: 5, depheight: 30 };
            }
        } else {
            // Even -> Mimic Team 2
            const t2 = createGame.slots.find(s => s.team === 2);
            if (t2) {
                defaults = { depx: t2.depx, depy: t2.depy, depwidth: t2.depwidth, depheight: t2.depheight };
            } else {
                defaults = { depx: 18, depy: 0, depwidth: 5, depheight: 30 };
            }
        }

        // Find a new slot ID
        let maxSlot = 0;
        createGame.slots.forEach(s => maxSlot = Math.max(maxSlot, s.id));
        createGame.slotid = maxSlot + 1;

        createGame.slots.push({
            id: createGame.slotid,
            team: nextTeamId,
            name: "Team " + nextTeamId,
            points: 3500,
            depx: parseInt(defaults.depx) || 0,
            depy: parseInt(defaults.depy) || 0,
            depwidth: parseInt(defaults.depwidth) || 5,
            depheight: parseInt(defaults.depheight) || 5,
            depavailable: 1
        });

        createGame.refreshSlotsUI();
    },

    removeTeam: function (teamId) {
        console.log("Executing removeTeam for:", teamId);
        window.confirm.confirm("Are you sure you want to remove Team " + teamId + "?", function () {
            createGame.slots = createGame.slots.filter(s => s.team !== teamId);
            createGame.refreshSlotsUI();
        });
    },



    createSlotsFromArray: function createSlotsFromArray() {
        for (var i in createGame.slots) {
            createGame.createSlot(createGame.slots[i]);
        }
    },

    createSlot: function createSlot(data) {
        var template = $("#slottemplatecontainer .slot").clone();
        var target = $("#team" + data.team + ".slotcontainer");

        if (target.length === 0) {
            console.error("Target container for team " + data.team + " not found!");
            return;
        }

        template.addClass("slotid_" + data.id);
        template.data("slotid", data.id);
        template.data("team", data.team);

        // ... (rest of slot population)
        var actual = template.clone(true).appendTo(target);

        actual.data("slotid", data.id);
        actual.addClass("slotid_" + data.id);

        // Disable points input if unlimited
        if (data.points == -1) {
            actual.find("[name='points']").hide();
            // Ensure label exists if not in template (it might be added to template later, but safe to add if missing)
            if (actual.find(".unlimited-label").length === 0) {
                actual.find("[name='points']").after('<span class="unlimited-label" style="display:inline-block; font-weight:bold; color:#DEEBFF; margin-left:5px;">Unlimited</span>');
            } else {
                actual.find(".unlimited-label").show();
            }
        } else {
            // For standard slots, ensure label is hidden if it exists
            if (actual.find(".unlimited-label").length === 0) {
                actual.find("[name='points']").after('<span class="unlimited-label" style="display:none; font-weight:bold; color:#DEEBFF; margin-left:5px;">Unlimited</span>');
            }
        }

        createGame.setSlotData(data);
    },

    setSlotData: function setSlotData(data) {
        var slot = $(".slot.slotid_" + data.id);
        // Note: We used to just use class selectors, but now we have Inputs with Names.
        // We can use [name='...']
        slot.find("[name='name']").val(data.name);

        // Only update points if NOT unlimited
        if (data.points != -1) {
            slot.find("[name='points']").val(data.points);
        }

        slot.find("[name='depx']").val(data.depx);
        slot.find("[name='depy']").val(data.depy);
        slot.find("[name='depwidth']").val(data.depwidth);
        slot.find("[name='depheight']").val(data.depheight);
        slot.find("[name='depavailable']").val(data.depavailable);
    },

    createNewSlot: function createNewSlot(e, explicitDefaults) {
        if (this.id === "addTeamBtn") return; // Prevent accidental trigger if class matches
        var team;
        // Check if 'this' is a DOM-like object or jQuery object
        // Use data-team attribute if available (Best practice)
        if ($(this).data("team")) {
            team = parseInt($(this).data("team"));
        } else {
            // Fallback for ensureTeamSlots which uses .call({className...}) or legacy class names
            const className = this.className || "";
            const match = className.match(/team(\d+)/);
            if (match) {
                team = parseInt(match[1]);
            } else {
                team = 1; // Default fallback
            }
        }

        createGame.slotid++;

        // Default to copying the LAST slot of that team, or standard defaults if none
        let lastSlotOfTeam = null;
        for (let i = createGame.slots.length - 1; i >= 0; i--) {
            if (createGame.slots[i].team === team) {
                lastSlotOfTeam = createGame.slots[i];
                break;
            }
        }

        let newData = {
            id: createGame.slotid,
            team: team,
            name: "Team " + team,
            points: $("#unlimitedPointsCheck").is(":checked") ? -1 : 3500,
            depx: 0,
            depy: 0,
            deptype: "box",
            depwidth: 5,
            depheight: 5,
            depavailable: 1
        };

        if (explicitDefaults) {
            Object.assign(newData, {
                depx: explicitDefaults.depx,
                depy: explicitDefaults.depy,
                depwidth: explicitDefaults.depwidth,
                depheight: explicitDefaults.depheight
            });
            if (explicitDefaults.points !== undefined) {
                newData.points = explicitDefaults.points;
            }
            if (explicitDefaults.name !== undefined) {
                newData.name = explicitDefaults.name;
            }
            if (explicitDefaults.depavailable !== undefined) {
                newData.depavailable = explicitDefaults.depavailable;
            }
        } else if (lastSlotOfTeam) {
            // Copy relevant deployment data
            newData.depx = lastSlotOfTeam.depx;
            newData.depy = lastSlotOfTeam.depy;
            newData.depwidth = lastSlotOfTeam.depwidth;
            newData.depheight = lastSlotOfTeam.depheight;
            newData.depavailable = lastSlotOfTeam.depavailable;
            newData.points = lastSlotOfTeam.points;
        } else {
            // Fallback if no slots exist for team (shouldn't happen often)
            if (team === 1) { newData.depx = -19; newData.depheight = 30; }
            if (team === 2) { newData.depx = 18; newData.depheight = 30; }
        }

        createGame.slots.push(newData);
        createGame.createSlot(newData);
        createGame.drawMapPreview();
        createGame.updateSlotButtons();
    },

    getSlotData: function getSlotData(id) {
        for (var i in createGame.slots) {
            var slot = createGame.slots[i];
            if (slot.id == id) return slot;
        }
    },

    removeSlotData: function removeSlotData(id) {
        for (var i = createGame.slots.length - 1; i >= 0; i--) {
            if (createGame.slots[i].id === id) {
                createGame.slots.splice(i, 1);
            }
        }
    },

    removeSlot: function removeSlot(e) {
        const removeBtn = $(this);
        const slot = removeBtn.closest(".slot");
        const slotId = slot.data("slotid");
        const data = createGame.getSlotData(slotId);

        if (!data) return;

        // Check if it's the last slot of a team
        const slotsOfTeam = createGame.slots.filter(s => s.team === data.team);
        if (slotsOfTeam.length <= 1) {
            window.confirm.error("You cannot delete the last slot of a team!", function () { });
            return false;
        }

        // Confirmation is generally annoying for simple removes, but if desired:
        // window.confirm.show("Are you sure?", ...)
        // For now, let's just do it to be zippy.

        createGame.removeSlotData(data.id);
        createGame.drawMapPreview();
        slot.remove();
        createGame.updateSlotButtons();
    },

    /* Create Game's half of each scenario field; scenarioCard.FIELDS is the shared half.
       `id` keeps the element ids the form has always had (applyMapConfig's config.scenario
       support addresses fields by them), `value` is the preselected option, and `legacy` is the
       line label in the old free-text description - see buildLegacyDescription(). */
    scenarioUI: {
        tier:              { id: "tier", value: "Tier 1", placeholder: "Enter power level...", legacy: "EXPECTED POWER LEVEL" },
        fleetRequirements: { id: "req", placeholder: "Enter requirements...", legacy: "REQUIREMENTS" },
        customFactions:    { id: "customfactions", value: "Not allowed", legacy: "CUSTOM FACTIONS / UNITS" },
        forbiddenFactions: { id: "forbidden", value: "None", legacy: "FORBIDDEN FACTIONS" },
        enhancements:      { id: "enhancements", placeholder: "Enter points...", legacy: "ENHANCEMENTS" },
        mapBorders:        { id: "borders", legacy: "MAP BORDERS" },
        victoryConditions: { id: "victory", placeholder: "Enter victory conditions...", legacy: "VICTORY CONDITIONS" },
        additionalInfo:    { id: "other", legacy: "ADDITIONAL INFO" }
    },

    //The old description's line order - NOT FIELDS order. Called Shots is gone (plan §3.3).
    legacyDescriptionOrder: ["fleetRequirements", "customFactions", "enhancements", "tier",
        "forbiddenFactions", "mapBorders", "victoryConditions", "additionalInfo"],

    renderScenarioFields: function renderScenarioFields() {
        const esc = scenarioCard.escapeHtml;
        let html = "";

        scenarioCard.FIELDS.forEach(function (field) {
            const ui = createGame.scenarioUI[field.key];
            const id = ui.id;
            let control;

            if (field.options) {
                control = '<select id="' + id + '" class="cg-input">' + field.options.map(function (opt) {
                    return '<option value="' + esc(opt) + '"' + (opt === ui.value ? " selected" : "") + ">" + esc(opt) + "</option>";
                }).join("") + "</select>";
            } else if (field.multiline) {
                control = '<textarea id="' + id + '" class="cg-input" rows="3" maxlength="4000"></textarea>';
            } else {
                control = '<input type="text" id="' + id + '" class="cg-input" maxlength="200" value="' + esc(ui.value || "") + '">';
            }

            //"Other" / "Up to X points" reveal a second input, collapsed until chosen.
            let reveal = "";
            if (field.otherKey || field.pointsKey) {
                const extra = field.pointsKey
                    ? '<input type="text" id="' + id + '_custom" class="cg-input cg-input--points" inputmode="numeric" pattern="[0-9]*" maxlength="6" placeholder="' + esc(ui.placeholder) + '">'
                    : '<input type="text" id="' + id + '_custom" class="cg-input" maxlength="200" placeholder="' + esc(ui.placeholder) + '">';
                reveal = '<div class="cg-reveal" id="' + id + '_reveal"><div class="cg-reveal-inner">' + extra + "</div></div>";
            }

            //Inline help rather than a floating bubble: a tap target that expands in place works
            //the same on a phone as with a mouse, and never has to be positioned.
            let helpButton = "", helpText = "";
            if (field.help) {
                helpButton = '<button type="button" class="cg-help" aria-expanded="false" aria-controls="' + id + '_help">'
                    + '<span aria-hidden="true">?</span><span class="cg-sr">About ' + esc(field.label) + "</span></button>";
                helpText = '<p class="cg-help-text" id="' + id + '_help" hidden>' + esc(field.help) + "</p>";
            }

            const optional = field.multiline ? ' <span class="cg-optional">(optional)</span>' : "";
            html += '<div class="cg-scn-card' + (field.wide ? " cg-scn-card--wide" : "") + '">'
                + '<div class="cg-scn-head"><label for="' + id + '" class="cg-card-label">' + esc(field.label) + optional + "</label>" + helpButton + "</div>"
                + helpText + control + reveal
                + "</div>";
        });

        $("#scenarioFields").html(html);

        scenarioCard.FIELDS.forEach(function (field) {
            if (!field.otherKey && !field.pointsKey) return;
            const id = createGame.scenarioUI[field.key].id;
            const trigger = field.pointsKey ? "Up to X points" : "Other";

            $("#" + id).on("change", function (e) {
                const open = $(this).val() === trigger;
                $("#" + id + "_reveal").toggleClass("is-open", open);
                //Focus only when a PERSON chose it - applyMapConfig triggers this too.
                if (open && e.originalEvent) $("#" + id + "_custom").trigger("focus");
            }).trigger("change");
        });

        //Digits only, typed or pasted - a points cap.
        $("#enhancements_custom").on("input", function () {
            const digits = this.value.replace(/[^0-9]/g, "");
            if (digits !== this.value) this.value = digits;
        });

        $("#scenarioFields").on("click", ".cg-help", function () {
            const open = $(this).attr("aria-expanded") !== "true";
            $(this).attr("aria-expanded", open ? "true" : "false");
            $("#" + $(this).attr("aria-controls")).prop("hidden", !open);
        });
    },

    //The form -> the scenario object stored in tac_game.scenario (via scenarioCard.normalise).
    //An "Other"/"Up to X points" text is only read while that choice is selected.
    readScenario: function readScenario() {
        const raw = {};
        scenarioCard.FIELDS.forEach(function (field) {
            const id = createGame.scenarioUI[field.key].id;
            const value = String($("#" + id).val() || "");
            raw[field.key] = value;

            if (field.otherKey && value === "Other") raw[field.otherKey] = $("#" + id + "_custom").val();
            if (field.pointsKey && value === "Up to X points") raw[field.pointsKey] = $("#" + id + "_custom").val();
        });
        return scenarioCard.normalise(raw);
    },

    /* The old free-text tac_game.description, still written beside the JSON: gamelobby.php
       parses it (until the structured render replaces that, plan Stage 4) and its inline JS
       regex-matches "CUSTOM FACTIONS / UNITS: Allowed". Same labels, same line order, same
       "Up to Npts allowed" wording as the code this replaced - only the Called Shots line is gone. */
    buildLegacyDescription: function buildLegacyDescription(scenario) {
        let result = "*** SCENARIO DESCRIPTION ***\n";
        createGame.legacyDescriptionOrder.forEach(function (key) {
            const field = scenarioCard.FIELDS.find(f => f.key === key);
            let value = scenario[key] || "";
            if (field.pointsKey && value === "Up to X points" && scenario[field.pointsKey]) {
                value = "Up to " + scenario[field.pointsKey] + "pts allowed";
            } else {
                value = scenarioCard.factValue(field, scenario);
            }
            result += createGame.scenarioUI[key].legacy + ": " + value + "\n";
        });
        return result;
    },

    submitFleetTest: function () {
        createGame.isFleetTest = true;
        $("#createGameForm button[type='submit']").trigger("mousedown"); // Trigger validation/allowSubmit flag if needed
        $("#createGameForm").submit();
    },

    setData: function setData() {
        var gamename = $("#gamename").val();
        var background = $("input[name='background']:checked").val();
        var scenario = createGame.readScenario();
        var description = createGame.buildLegacyDescription(scenario);
        var gamespace = "-1x-1";
        var flight = "";

        if ($("#mapDimensionsSelect").val() !== "unlimited") {
            gamespace = "" + createGame.gamespace_data.width + "x" + createGame.gamespace_data.height;
        }

        if ($("#flightSizeCheck:checked").val() == "on") {
            flight = 1;
        }

        // Add Fleet Test rule if flag is set
        if (createGame.isFleetTest) {
            createGame.rules.fleetTest = 1;
        }

        var data = { gamename: gamename, background: background, slots: createGame.slots, gamespace: gamespace, flight: flight, rules: createGame.rules, description: description, scenario: scenario };
        data = JSON.stringify(data);
        $("#createGameData").val(data);
    }
};

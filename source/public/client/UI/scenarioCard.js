"use strict";

/*
 * scenarioCard.js - a game's structured Scenario Description: the definition of its JSON, and the
 * read-only fact grid it renders as. CREATE_GAME_GAMELOBBY_REDESIGN_PLAN.md §3.3 / §4.1.
 *
 * Loaded by BOTH creategame.php (the wizard's Summary step is a preview of exactly what the
 * opponent will see) and gamelobby.php (its Scenario Description panel). One renderer, so the two
 * screens cannot drift apart. Styles: styles/scenarioCard.css.
 *
 * WHY: the scenario used to travel only as one "LABEL: value\n" string in tac_game.description,
 * which gamelobby.php recovers by splitting each line on its first colon - so a value containing
 * a colon misparses, and neither screen can show more structure than that string carries. It now
 * travels as JSON in tac_game.scenario (db/createGameRedesign.sql) as well.
 *
 * ⚠️ FIELDS IS A STORAGE CONTRACT. Its keys (key / otherKey / pointsKey) are what gets written to
 * tac_game.scenario and kept for the life of the game - rename one and every stored game loses
 * that fact. The `options` strings are copied VERBATIM from creategame.php's <option value>s and
 * stored as-is, so they are compared, not just displayed: do not paraphrase them either.
 * Called Shots is deliberately absent (dropped from the redesign, plan §3.3).
 *
 * STAGE 0: nothing calls this yet. Stage 1 writes the JSON, Stages 2 and 4 render it.
 */
window.scenarioCard = {

    //Bump only when an existing key changes MEANING. Adding a field needs no bump: a game stored
    //before the field existed simply has no value for it, and render() skips empty facts.
    VERSION: 1,

    FIELDS: [
        {
            key: "tier", label: "Expected Power Level", factLabel: "Tier",
            options: ["Any", "Tier 1", "Tier 2", "Tier 3", "Ancient", "Other"],
            otherKey: "tierCustom"
        },
        {
            key: "fleetRequirements", label: "Fleet Requirements", factLabel: "Fleet Requirements",
            options: ["Pass the fleet checker", "Other"],
            otherKey: "fleetRequirementsCustom"
        },
        {
            key: "customFactions", label: "Custom Factions / Units", factLabel: "Custom Factions",
            options: ["Allowed", "Custom factions allowed", "Custom ships in official factions allowed", "Not allowed"]
        },
        {
            //free text, "None" by default - a fact that says nothing is left out of the grid
            key: "forbiddenFactions", label: "Forbidden Factions", factLabel: "Forbidden Factions",
            omitNone: true
        },
        {
            key: "enhancements", label: "Enhancements", factLabel: "Enhancements",
            options: ["Allowed", "Up to X points", "Not allowed"],
            pointsKey: "enhancementsPoints"
        },
        {
            key: "mapBorders", label: "Map Borders", factLabel: "Map Borders",
            options: ["Unit ending movement out of map is destroyed", "Unit leaving map is destroyed"]
        },
        {
            key: "victoryConditions", label: "Victory Conditions", factLabel: "Victory Conditions",
            options: ["More forces remaining after Turn 12", "Last unit on map", "Last ship on map", "Other"],
            otherKey: "victoryCustom"
        },
        {
            key: "additionalInfo", label: "Additional Info", factLabel: "Additional Info",
            multiline: true, wide: true
        }
    ],

    //Every key a stored scenario may carry, in FIELDS order.
    storedKeys: function storedKeys() {
        var keys = [];
        scenarioCard.FIELDS.forEach(function (field) {
            keys.push(field.key);
            if (field.otherKey) keys.push(field.otherKey);
            if (field.pointsKey) keys.push(field.pointsKey);
        });
        return keys;
    },

    /*
     * Raw scenario (a JSON string, a parsed object, or nothing) -> a clean object holding only
     * known keys, every value a trimmed string; or null when there is no usable scenario.
     *
     * Every value goes through String() because the lobby payload is encoded with
     * JSON_NUMERIC_CHECK, which hands a numeric-looking string back as a NUMBER - an
     * enhancementsPoints of "20" arrives as 20. (That flag can also CHANGE a value - "0012"
     * becomes 12 - which no amount of client-side care undoes. The server side's answer is to
     * publish the column as raw JSON text; see db/createGameRedesign.sql.)
     */
    normalise: function normalise(raw) {
        var data = raw;
        if (typeof data === "string") {
            if (data.trim() === "") return null;
            try {
                data = JSON.parse(data);
            } catch (e) {
                return null;
            }
        }
        if (!data || typeof data !== "object" || Array.isArray(data)) return null;

        var clean = { v: Number(data.v) || scenarioCard.VERSION };
        scenarioCard.storedKeys().forEach(function (key) {
            if (data[key] != null) clean[key] = String(data[key]).trim();
        });
        return clean;
    },

    //The text a fact card shows for one field: an "Other" or "Up to X points" choice is replaced
    //by what the creator typed for it, and left as the bare option when they typed nothing.
    factValue: function factValue(field, data) {
        var value = data[field.key] || "";
        if (field.otherKey && value === "Other" && data[field.otherKey]) {
            return data[field.otherKey];
        }
        if (field.pointsKey && value === "Up to X points" && data[field.pointsKey]) {
            return "Up to " + data[field.pointsKey] + " pts";
        }
        return value;
    },

    /*
     * The fact grid as an HTML string, or "" when there is nothing to show - the caller owns the
     * panel/card around it and its heading. Every value is escaped: the free-text fields are
     * player-typed and this output goes straight into .html().
     *
     * options.plain: drop each fact's own card chrome, for a grid that already sits INSIDE a card
     * (the wizard's Summary step). The Gamelobby panel uses the default carded grid.
     */
    render: function render(raw, options) {
        var data = scenarioCard.normalise(raw);
        if (!data) return "";

        var facts = "";
        scenarioCard.FIELDS.forEach(function (field) {
            var value = scenarioCard.factValue(field, data);
            if (value === "" || (field.omitNone && /^none$/i.test(value))) return;

            facts += '<div class="fv-scn-fact' + (field.wide ? " fv-scn-fact--wide" : "") + '">' +
                '<dt class="fv-scn-label">' + scenarioCard.escapeHtml(field.factLabel) + "</dt>" +
                '<dd class="fv-scn-value' + (field.multiline ? " fv-scn-value--multiline" : "") + '">' +
                scenarioCard.escapeHtml(value) + "</dd>" +
                "</div>";
        });
        if (facts === "") return "";

        var plain = options && options.plain;
        return '<dl class="fv-scn-grid' + (plain ? " fv-scn-grid--plain" : "") + '">' + facts + "</dl>";
    },

    //Same as games.js's escapeHtml - that file is not loaded on either page that uses this one.
    escapeHtml: function escapeHtml(value) {
        return String(value == null ? "" : value).replace(/[&<>"']/g, function (c) {
            return { "&": "&amp;", "<": "&lt;", ">": "&gt;", '"': "&quot;", "'": "&#39;" }[c];
        });
    }
};

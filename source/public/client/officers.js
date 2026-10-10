'use strict';

/* OFFICERS (OFFICERS_PLAN.md, repo root) - the client half: the labels and one-line effects, the post
   a lobby officer would take, and the ONE list every insignia, tooltip line and ship-level listing
   reads (§7, "two tracks drift").

   WHERE THE LIST COMES FROM:
     - in GAME, and for anything the server built: ship.officers, sent by Officers::addForJson to the
       owner's team only (and to everybody once the game is over). An enemy's ship simply has none, so
       nothing on this side ever compares userids - the isRevealedToCurrentViewer pattern.
     - in the LOBBY, before the server has seen the fleet: derived from the bought OFF_* rows, the way
       Officers::pickPosts derives it. Only the buyer's own browser holds those counts; server payloads
       never carry enhancementOptions, and a static blueprint's rows are all 0.
   Out of action is decided SERVER-SIDE from the full damage history and the kill notes (§4.4); the
   client only reads out/how. The one exception is the lobby's pre-battle damage preview: a post the
   player destroys there leaves its officer disabled from the start (§4.9), as the server will rule. */
window.officers = {

	ID_PREFIX: 'OFF_',

	/* ⚠️ MIRROR of Officers::$registry (PHP): ids, labels and posts, in the SAME order - the order every
	   list here is shown in. `summary` is the effect line the System Info tooltip prints (D12);
	   `initiative` is what the officer adds to iniativebonus while he serves (Officers::apply). */
	REGISTRY: [
		{ id: 'OFF_HELM', label: 'Expert Helmsman', post: 'cnc', initiative: 5,
			summary: '+5 initiative, wins ties, -1 on meteor chart' }
	],

	isOfficerId: function isOfficerId(enhID) {
		return typeof enhID === 'string' && enhID.indexOf(this.ID_PREFIX) === 0;
	},

	get: function get(id) {
		for (var i = 0; i < this.REGISTRY.length; i++) {
			if (this.REGISTRY[i].id === id) return this.REGISTRY[i];
		}
		return null;
	},

	label: function label(id) {
		var officer = this.get(id);
		return officer ? officer.label : String(id);
	},

	/* The system an officer of this post would live in, or null.
	   ⚠️ MIRROR PAIR with Officers::findPost (PHP). Duck-typed, never instanceof: a bought lobby ship is a
	   jQuery.extend clone with no prototype chain ([[arch_lobby_ship_objects]]). A Flag Bridge is a
	   "cnC" carrying the FlagBridge special ability - the class property every FlagBridge declares.
	     cnc  the first C&C that is not a Flag Bridge; failing that, the first one of any kind */
	findPost: function findPost(ship, post) {
		if (!ship || !ship.systems) return null;
		var systems = Array.isArray(ship.systems) ? ship.systems : Object.values(ship.systems);
		if (post === 'cnc') {
			var any = null;
			for (var i = 0; i < systems.length; i++) {
				var system = systems[i];
				if (!system || system.name !== 'cnC') continue;
				var isBridge = Array.isArray(system.specialAbilities) && system.specialAbilities.indexOf('FlagBridge') !== -1;
				if (!isBridge) return system;
				if (any === null) any = system;
			}
			return any;
		}
		return null;
	},

	/* Every officer aboard, as array({id, post, out?, how?}) in registry order. See the header for
	   which of the two sources answers. Flights carry none before the fighter framework (Stage 4). */
	listFor: function listFor(ship) {
		if (!ship || ship.flight) return [];
		if (Array.isArray(ship.officers)) return ship.officers;
		if (!Array.isArray(ship.enhancementOptions)) return [];

		var list = [];
		for (var i = 0; i < this.REGISTRY.length; i++) {
			var officer = this.REGISTRY[i];
			if (!this.isBought(ship, officer.id)) continue;
			var post = this.findPost(ship, officer.post);
			if (!post) continue;
			var entry = { id: officer.id, post: post.id };
			if (this.isPostDestroyed(ship, post)) {
				entry.out = 0; //pre-battle damage: he never serves (Officers::serves)
				entry.how = 'disabled';
			}
			list.push(entry);
		}
		return list;
	},

	//true when the list is the lobby's own derivation rather than something the server sent
	isDerived: function isDerived(ship) {
		return Boolean(ship) && !ship.flight && !Array.isArray(ship.officers);
	},

	isBought: function isBought(ship, id) {
		for (var i = 0; i < ship.enhancementOptions.length; i++) {
			var row = ship.enhancementOptions[i];
			if (row && row[0] === id && (parseInt(row[2], 10) || 0) > 0) return true;
		}
		return false;
	},

	isPostDestroyed: function isPostDestroyed(ship, post) {
		if (window.shipManager && shipManager.systems && typeof shipManager.systems.isDestroyed === 'function') {
			return Boolean(shipManager.systems.isDestroyed(ship, post));
		}
		return Boolean(post.destroyed);
	},

	//the officers posted on one system
	onPost: function onPost(ship, systemId) {
		return this.listFor(ship).filter(function (entry) { return String(entry.post) === String(systemId); });
	},

	/* Out on turn N = serves N, gone from N + 1; out at turn 0 (pre-battle damage) never serves.
	   ⚠️ MIRROR PAIR with Officers::serves (PHP). */
	serves: function serves(entry, turn) {
		if (entry.out === undefined || entry.out === null) return true;
		var out = parseInt(entry.out, 10);
		return out >= 1 && out >= (parseInt(turn, 10) || 0);
	},

	currentTurn: function currentTurn() {
		return (window.gamedata && gamedata.turn !== undefined) ? gamedata.turn : 0;
	},

	/* 'serving' | 'disabled' | 'killed' - what the insignia shows for one post: the BEST state of the
	   officers posted there, so any serving officer keeps it gold (D11). null with nobody posted. */
	postState: function postState(ship, systemId) {
		var entries = this.onPost(ship, systemId);
		if (entries.length === 0) return null;
		var turn = this.currentTurn();
		var state = 'killed';
		for (var i = 0; i < entries.length; i++) {
			if (this.serves(entries[i], turn)) return 'serving';
			if (entries[i].how !== 'killed') state = 'disabled';
		}
		return state;
	},

	//"(disabled, turn 3)", "(killed, turn 3)", the pre-battle wording, or '' while he serves
	stateText: function stateText(entry) {
		if (entry.out === undefined || entry.out === null) return '';
		if (parseInt(entry.out, 10) < 1) return '(post destroyed before battle - he will not serve)';
		return '(' + (entry.how === 'killed' ? 'killed' : 'disabled') + ', turn ' + entry.out + ')';
	},

	//"Expert Helmsman (disabled, turn 3)" - the insignia's title, one officer per line
	describePost: function describePost(ship, systemId) {
		var self = this;
		return this.onPost(ship, systemId).map(function (entry) {
			var state = self.stateText(entry);
			return self.label(entry.id) + (state ? ' ' + state : '');
		}).join('\n');
	},

	/* The initiative bonus a window shows. In game the server has already put a serving officer's share
	   into iniativebonus (sent to every viewer - user ruling 2026-10-10, so the ship tooltip's "base" is the
	   ship's real figure); in the lobby nothing has, so it is added HERE, at read time -
	   never written into the ship, so a C&C destroyed in the pre-battle editor takes the +5 away on the
	   next render, and an edit cannot compound it.
	   ⚠️ MIRROR PAIR with Officers::apply's stat changes (PHP) - enhancementsDifferential.js compares the
	   two through this function. */
	displayIniativeBonus: function displayIniativeBonus(ship) {
		var bonus = Number(ship && ship.iniativebonus) || 0;
		if (!this.isDerived(ship)) return bonus;
		var turn = this.currentTurn();
		var list = this.listFor(ship);
		for (var i = 0; i < list.length; i++) {
			var officer = this.get(list[i].id);
			if (officer && officer.initiative && this.serves(list[i], turn)) bonus += officer.initiative;
		}
		return bonus;
	}
};

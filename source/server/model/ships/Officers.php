<?php
/* OFFICERS (OFFICERS_PLAN.md, repo root) - expert officers carried by a unit.

   An officer is an enhancement with a POST: the one system on the unit he lives in. While the post
   stands, his bonus applies. Two ways out of action, both permanent and both effective from the
   NEXT turn (D3):
     - DISABLED: the post was destroyed, by its own boxes or by its Structure block. Re-derived from
       the complete damage history at every load (the server loads every turn's damage rows), so
       nothing is stored. Self Repair reviving the post changes nothing: "undestroyed" is ignored.
     - KILLED: the post took damage through its armour and its critical roll came to 20 or more,
       modifiers included - the roll the post makes anyway, or one made for him when the post has
       just been destroyed. A dice result, so it IS stored: one OffKilled individual note per death,
       intercepted in DBManager's bulk note loop and never handed to any system (§4.4).

   THE ONE RUNTIME LIST. $ship->getOfficers() holds array('id', 'post', 'out', 'how') per officer:
   'post' is the post's system id, 'out' the turn he left action (null while he has not), 'how'
   'disabled' or 'killed'. He serves on turn T while out === null or out >= T. Every effect, the
   payload and (Stage 2) the insignia read that list and nothing else (§7, "two tracks drift").

   ⚠️ POST-side ships (Manager::getShipsFromJSON) never run onConstructed, so their list is empty:
   nothing in a phase's process() may ask about officers ([[arch_post_side_ship_reconstruction]]).

   Track 1 only so far (fixed-post ship officers, ship-level OFF_* rows). Placed officers (track 2,
   Stage 8) and fighter officers (Stage 4) are not built yet. */
class Officers{

	const ID_PREFIX = 'OFF_';
	const KILL_NOTE = 'OffKilled'; //tac_individual_notes.notekey, varchar(40)
	const KILL_ROLL = 20;          //"a '20' or greater (including any modifications)"

	/* One literal per officer (D6), walked in THIS order by apply() (D5).
	     label  the name in the buy dialog, the stored enhname and the kill note's notekey_human
	     post   a key into findPost()
	     pct    price in percent of the hull's blueprint pointCost, rounded up (D8)
	     apply  the static method that applies his stat changes while he serves, or null when every
	            rule he carries is asked for elsewhere (isActive). */
	private static $registry = array(
		/* Expert Helmsman: +5 initiative (FV units, QX1), wins initiative ties, and -1 on the die he
		   rolls on a meteor swarm's impact chart (user, 2026-10-10). The tie-break is
		   TacGamedata::sortShips; the chart is RammingAttack::resolveMeteors. */
		'OFF_HELM' => array('label' => 'Expert Helmsman', 'post' => 'cnc', 'pct' => 5, 'apply' => 'applyHelmsman'),
	);

	public static function isOfficerId($enhID){
		return strpos((string)$enhID, self::ID_PREFIX) === 0;
	}

	public static function getRegistry(){
		return self::$registry;
	}

	public static function getLabel($id){
		return isset(self::$registry[$id]) ? self::$registry[$id]['label'] : (string)$id;
	}

	/* D9: never on Ancient hulls, OSATs (mines extend OSAT) or terrain - one shared test in front of
	   every officer, not something each entry repeats. Bases, LCVs and the rest only where the post
	   exists, which findPost() decides. Flights wait for the fighter framework (Stage 4). */
	public static function mayCarryOfficers($ship){
		if ($ship instanceof FighterFlight) return false;
		if ((int)$ship->factionAge >= 3) return false;
		if ($ship instanceof OSAT) return false;
		if (!empty($ship->mine)) return false;
		if ($ship->isTerrain()) return false;
		return true;
	}

	/* ------------------------------------------------------------------ offers */

	/* Called at the end of Enhancements::setEnhancementOptionsShip, so the offers land in the static
	   blueprints (fvbuild -Statics follows any change here). Limit 1: "one of each type" (D9). */
	public static function addOffers($ship){
		if (!self::mayCarryOfficers($ship)) return;
		foreach (self::$registry as $id => $officer){
			if (in_array($id, $ship->enhancementOptionsDisabled)) continue;
			if (self::findPost($ship, $officer['post']) === null) continue;
			$price = self::price($ship->pointCost, $officer['pct']);
			$ship->enhancementOptions[] = array($id, $officer['label'], 0, 1, $price, 0, false);
		}
	}

	/* ceil(pct% of the blueprint cost), in whole numbers: 0.07 x 100 is 7.000000000000001 in floats,
	   and ceil() would charge 8 for it (SHIP_ENHANCEMENTS_PLAN.md Stage 5 hit the same trap). */
	public static function price($pointCost, $pct){
		$hundredths = max(0, (int)round($pointCost * 100));
		return intdiv($hundredths * (int)$pct + 9999, 10000);
	}

	/* ------------------------------------------------------------------ posts */

	/* The system an officer lives in, or null when the hull has none (so he is not offered).
	   ⚠️ MIRROR PAIR with officers.js pickPosts (Stage 2).
	     cnc  the first C&C that is not a Flag Bridge; failing that, the first C&C of any kind.
	          SecondaryCnC is not a CnC subclass, so it is never picked. */
	private static function findPost($ship, $post){
		switch ($post){
			case 'cnc':
				$any = null;
				foreach ($ship->systems as $system){
					if (!($system instanceof CnC)) continue;
					if (!($system instanceof FlagBridge)) return $system;
					if ($any === null) $any = $system;
				}
				return $any;
		}
		return null;
	}

	/* D2: posts are picked at the top of BaseShip::onConstructed, BEFORE any enhancement moves an
	   output, from the bought rows. A count above 1 still means one officer (D9), and a row whose
	   post the hull lacks buys nothing - the server trusts the client's rows, so a doctored payload
	   is answered here. */
	public static function pickPosts($ship){
		$list = array();
		if (self::hasOfficerRow($ship) && self::mayCarryOfficers($ship)){
			foreach (self::$registry as $id => $officer){
				if (!self::isBought($ship, $id)) continue;
				$post = self::findPost($ship, $officer['post']);
				if ($post === null) continue;
				$list[] = array('id' => $id, 'post' => (int)$post->id, 'out' => null, 'how' => null);
			}
		}
		$ship->setOfficers($list);
	}

	//the cheap test that keeps every ordinary ship (a single 'NONE' row) out of the resolver
	private static function hasOfficerRow($ship){
		foreach ($ship->enhancementOptions as $entry){
			if ((int)$entry[2] > 0 && self::isOfficerId($entry[0])) return true;
		}
		return false;
	}

	private static function isBought($ship, $id){
		foreach ($ship->enhancementOptions as $entry){
			if ($entry[0] === $id && (int)$entry[2] > 0) return true;
		}
		return false;
	}

	/* ------------------------------------------------------------------ out of action */

	/* After the per-system onConstructed loop (structureSystem is filled there) and before
	   iniativeadded is computed (D5). Derives each officer's state, then applies the stat changes of
	   every officer serving on $turn, in registry order. */
	public static function apply($ship, $turn){
		$list = $ship->getOfficers();
		if (empty($list)) return;
		$kills = $ship->getOfficerKills();
		foreach ($list as $i => $entry){
			$post = $ship->getSystemById($entry['post']);
			$disabled = ($post !== null) ? self::disabledTurn($ship, $post) : 0; //a vanished post never served
			$killed = isset($kills[$entry['id']]) ? (int)$kills[$entry['id']] : null;
			if ($killed !== null && ($disabled === null || $killed <= $disabled)){
				$list[$i]['out'] = $killed;
				$list[$i]['how'] = 'killed'; //both in one turn reads as killed (§4.4)
			} else if ($disabled !== null){
				$list[$i]['out'] = $disabled;
				$list[$i]['how'] = 'disabled';
			}
		}
		$ship->setOfficers($list);

		foreach ($list as $entry){
			if (!self::serves($entry, $turn)) continue;
			$method = self::$registry[$entry['id']]['apply'];
			if ($method !== null) self::$method($ship);
		}
	}

	/* Out on turn N = serves N, gone from N + 1 (D3: the support-system convention, and it keeps
	   simultaneous fire simultaneous). Out at turn 0 is pre-battle damage: he never serves (§4.9). */
	private static function serves($entry, $turn){
		if ($entry['out'] === null) return true;
		return ($entry['out'] >= 1) && ($entry['out'] >= (int)$turn);
	}

	/* Is this officer aboard and serving on $turn (default: the turn this gamedata was loaded at)?
	   On a hot path, ask behind TacGamedata::$officersPresent. */
	public static function isActive($ship, $id, $turn = null){
		if ($turn === null) $turn = TacGamedata::$currentTurn;
		foreach ($ship->getOfficers() as $entry){
			if ($entry['id'] === $id) return self::serves($entry, $turn);
		}
		return false;
	}

	/* The earliest turn the post was destroyed: by a damage row of its own, or by its Structure block
	   (for a post spanning several locations, the turn the LAST of its blocks fell). null = never.
	   ⚠️ Mirrors ShipSystem::isDestroyed's cascade, but without "undestroyed": a revival by Self Repair
	   does not bring an officer back. The block's fall turn is the officer's out turn, which is right:
	   isDestroyed() lets a system fall off one turn AFTER its block, so he serves the turn it falls. */
	private static function disabledTurn($ship, $post){
		$turn = self::firstDestroyedTurn($post);
		if (!($post instanceof Structure) && !$post->getSurvivesStructureDestruction()){
			$blocks = $post->getStructureSystem();
			$cascade = null;
			if (is_array($blocks)){
				$all = true;
				foreach ($blocks as $block){
					if (!$block) continue; //isDestroyed() treats a missing block as fallen too
					$fell = self::structureFallTurn($ship, $block);
					if ($fell === null){ $all = false; break; }
					$cascade = ($cascade === null) ? $fell : max($cascade, $fell);
				}
				if (!$all) $cascade = null;
			} else if ($blocks){
				$cascade = self::structureFallTurn($ship, $blocks);
			}
			$turn = self::earlier($turn, $cascade);
		}
		return $turn;
	}

	//a section's block also falls with the PRIMARY Structure, as in ShipSystem::isDestroyed
	private static function structureFallTurn($ship, $block){
		$turn = self::firstDestroyedTurn($block);
		if ((int)$block->location !== 0){
			$primary = $ship->getStructureSystem(0);
			if ($primary && $primary !== $block) $turn = self::earlier($turn, self::firstDestroyedTurn($primary));
		}
		return $turn;
	}

	private static function firstDestroyedTurn($system){
		$first = null;
		foreach ($system->damage as $damage){
			if ($damage->destroyed) $first = self::earlier($first, (int)$damage->turn);
		}
		return $first;
	}

	private static function earlier($a, $b){
		if ($a === null) return $b;
		if ($b === null) return $a;
		return min($a, $b);
	}

	/* ------------------------------------------------------------------ the kill roll */

	/* Criticals::setCriticals, right after Pass 1 (which made the posts' own rolls) and before Pass 2's
	   Self Repair, behind TacGamedata::$officersPresent. Every post holding an officer still in action
	   - or put out of it THIS turn, since "make a roll even if the system is totally destroyed" - that
	   took damage through its armour this turn (isDamagedOnTurn, Pass 1's own trigger) is tested once,
	   and 20 or more kills every officer posted there.
	   The roll is the post's own from Pass 1 when it made one this turn; otherwise (Pass 1 skips a
	   destroyed system, and a missile launcher whose magazine exploded never reached the parent) the
	   same formula is rolled here.
	   ⚠️ Writes straight through Manager::insertIndividualNote - a sweep inside advance()
	   ([[arch_individual_notes_and_phase_hooks]]) - and updates the in-memory list, so anything later
	   in this request reads him as killed. */
	public static function rollKills($ships, $gamedata){
		$turn = (int)$gamedata->turn;
		foreach ($ships as $ship){
			$list = $ship->getOfficers();
			if (empty($list)) continue;
			$byPost = array();
			foreach ($list as $i => $entry){
				if ($entry['how'] === 'killed') continue;
				if ($entry['out'] !== null && $entry['out'] < $turn) continue; //out on an earlier turn
				$byPost[$entry['post']][] = $i;
			}
			$changed = false;
			foreach ($byPost as $postId => $indexes){
				$post = $ship->getSystemById($postId);
				if ($post === null || !$post->isDamagedOnTurn($turn)) continue;
				$roll = $post->getCritRollOnTurn($turn);
				if ($roll === null) $roll = self::rollFor($ship, $post);
				if ($roll < self::KILL_ROLL) continue;
				foreach ($indexes as $i){
					$id = $list[$i]['id'];
					$list[$i]['out'] = $turn;
					$list[$i]['how'] = 'killed';
					$changed = true;
					//notekey_human is varchar(40) and an overflow aborts the whole advance: the longest
					//planned label, "Expert Software Engineer killed", is 31
					Manager::insertIndividualNote(new IndividualNote(-1, $gamedata->id, $turn, $gamedata->phase,
						$ship->id, $post->id, self::KILL_NOTE, substr(self::getLabel($id) . ' killed', 0, 40), $id));
				}
			}
			if ($changed) $ship->setOfficers($list);
		}
	}

	/* ShipSystem::testCritical's formula for a post that made no roll this turn: d20 + floor(total
	   damage) + the system's and the ship's critRollMod.
	   ⚠️ Known gap: a Hyach scanner's "damage halved for critical rolls" lives in Scanner::testCritical
	   and is not repeated here, so it reaches a CAPTURED roll but not this one. No Stage 1 post is a
	   scanner; the Scanner officers' stage decides it. */
	private static function rollFor($ship, $post){
		return Dice::d(20) + floor($post->getTotalDamage()) + $post->critRollMod + $ship->critRollMod;
	}

	//DBManager's bulk note loop hands every OffKilled note here instead of to its system (§4.4)
	public static function isKillNote($note){
		return $note->notekey === self::KILL_NOTE;
	}

	/* ------------------------------------------------------------------ effects */

	private static function applyHelmsman($ship){
		$ship->iniativebonus += 5;
	}

	//Expert Helmsman: "wins ties" - TacGamedata::sortShips, behind $officersPresent
	public static function winsInitiativeTies($ship){
		return self::isActive($ship, 'OFF_HELM');
	}

	//Expert Helmsman: -1 on the meteor impact chart's die - RammingAttack::resolveMeteors
	public static function meteorChartModifier($ship){
		return self::isActive($ship, 'OFF_HELM') ? -1 : 0;
	}

	//the initiative serving officers add to iniativebonus
	private static function initiativeShare($ship){
		return self::isActive($ship, 'OFF_HELM') ? 5 : 0;
	}

	/* ------------------------------------------------------------------ payload */

	/* BaseShip::stripForJson, after the enhancement fields.
	   - The LIST goes to the owner's team, and to everybody once the game is over (D4), with out/how
	     only once he has left action.
	   - The INITIATIVE BONUS goes to EVERY viewer whenever a serving officer has moved it (user ruling
	     2026-10-10): the ship tooltip's "base" figure has to be the ship's real one, because a hidden +5
	     misleads more than it hides. So an enemy reads the number but not who is behind it.
	   ⚠️ Cosmetic secrecy, like the refit star - §4.6 lists the numbers that reach the enemy. */
	public static function addForJson($ship, $strippedShip){
		$list = $ship->getOfficers();
		if (empty($list)) return;
		if (self::initiativeShare($ship) !== 0) $strippedShip->iniativebonus = $ship->iniativebonus;
		if (!TacGamedata::$currentGameFinished && !$ship->isRevealedToCurrentViewer()) return;
		$out = array();
		foreach ($list as $entry){
			$row = array('id' => $entry['id'], 'post' => $entry['post']);
			if ($entry['out'] !== null){
				$row['out'] = $entry['out'];
				$row['how'] = $entry['how'];
			}
			$out[] = $row;
		}
		$strippedShip->officers = $out;
	}
}
?>

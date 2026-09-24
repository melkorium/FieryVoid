# Create Game & Gamelobby Redesign Plan

**Build status: Stages 0-1 BUILT 2026-09-23, Stage 2 BUILT 2026-09-24 (see §12); Stages 3-10 not
started.** Covers two pages:
`source/public/creategame.php` (+ `client/UI/createGame.js`) and `source/public/gamelobby.php`
(+ `client/gamelobby.js`, `client/lobbyEnhancements.js`).

Also covers the ship **buy / edit / bulk-buy confirm dialogs** (`client/UI/confirm.js`,
`styles/confirm.css`) reached from Gamelobby's purchase panel — added 2026-09-22, see §10.

**Read §11 before implementing anything.** A Design-canvas mockup pass (2026-09-22) validated
and corrected a number of decisions in this plan — most significantly the faction-picker
architecture (§4.3), several real-code facts about how the Store ship list and team colours
actually render that weren't in the original audit, and a few UI decisions this plan guessed
wrong the first time. Where §11 and an earlier section disagree, §11 is the corrected version;
the earlier sections have been patched in place where practical, but §11 is the complete record.

Companion to `GAMES_PAGE_REDESIGN_PLAN.md` and `VISUAL_UNIFICATION_PLAN.md` — this plan
**reuses** the visual language those two established (the `--fv-*` tokens in
`styles/tokens.css`, the "contact readout" card grammar in `styles/gamesPanel.css`) rather than
inventing a third look. Nothing here touches combat rules, movement, or firing.

Every numbered item from the brief is addressed below; each place I made a judgment call is
marked **Decision** with its reasoning, and §8 lists them together for a quick yes/no pass.

---

## 1. What exists today (audit)

### 1.1 Create Game (`creategame.php`, `client/UI/createGame.js`)

Single long form inside one `<panel class="panel large create">`, two-column split (Game
Options | Scenario Description), then a second panel for Map Layout & Teams, then chat. One
`<form id="createGameForm" method="post">`, one POST at submit
(`creategame.php:354-362` — `docreate=true` + a hidden `data` field createGame.js fills with
JSON before submit).

Findings:

- **Scenario Description is 9 raw `<select>`/`<input>` rows** (creategame.php:216-294) with no
  visual hierarchy beyond a shared label style; "Additional Info" is a plain 3-row `<textarea>`.
  These get client-concatenated into **one free-text string** (§1.1.1) that gamelobby.php later
  has to reverse-engineer with regex.
- **Terrain controls are hand-built, not reusable.** The asteroids/moons dropdowns
  (creategame.php:126-174) are custom divs with literal `style="width:140px;background-color:
  #0c1a28;..."` inline on every option row — doesn't touch `tokens.css`, and adding a third
  terrain type today means copy-pasting this whole block again.
- **Background picker is a blind `<select>`** of 26 numbered filenames (creategame.php:74-86,
  fed by `Manager::getMapBackgrounds()` at Manager.php:415) — zero visual preview for a choice
  that's purely visual (the hex-grid backdrop).
- **No password/private-game concept anywhere** — grepped `DBManager.php` for
  `password|private`, zero hits outside player auth. This is new schema, not a UI gap.
- **No "save these settings" anywhere** — every game starts from the same
  `$defaultGameName` + all-blank options (creategame.php:12-15).
- **Team/slot "Add" is already a clone-based mechanism** — `createGame.js:962` and `:1049`
  `.clone()` a hidden template div and rewrite its ids. "Copy Slot/Team" is a small variant of
  a mechanism that already exists, not a new one.
- **The map preview canvas already works well** — `#mapPreview` (545×390) already redraws live
  as team/slot fields change. This is the one piece of the page that already behaves like a
  modern tool; leave the mechanism alone, just give it a legend (§3.4).

#### 1.1.1 The free-text scenario round-trip — the core fragility

`createGame.js` builds `description` as one formatted string (e.g.
`"EXPECTED POWER LEVEL: Tier 1\nFLEET REQUIREMENTS: ...\n"`). `Manager::createGame`
(Manager.php:364-396, specifically line 372) stores it verbatim in `tac_game.description`
(TEXT). `gamelobby.php:690-741` then reconstructs structure by: stripping `<br>` tags,
stripping a `***...***` header line, splitting on newlines, then splitting **each line on its
first colon** to recover a label/value pair — with a hand-coded special case for "Additional
Info" (lines 716-725) because it can span multiple lines.

This is a text→text round trip standing in for structured data. It's the reason Scenario
Description looks "basic" on **both** ends: Create Game only offers flat rows because the
field is one string, and Gamelobby only offers flat label:value lines because that's all it
can reliably recover from that string (a free-typed victory condition containing a colon, e.g.
a time, would silently misparse).

**Decision:** move scenario data to a structured JSON column at creation
(new `tac_game.scenario`), alongside the existing free-text `description` (kept as-is for
anything else that reads it raw). Gamelobby then renders straight from JSON with no parsing.
This one schema change is what unlocks the readability upgrade on both screens at once — see
§6.

### 1.2 Gamelobby (`gamelobby.php`, `gamelobby.js`, `lobbyEnhancements.js`)

1244-line PHP file: header → name panel → scenario/map split → teams panel → buy panel
(filters, tier checkboxes, faction list, ship list) → chat.

Findings:

- **Scenario description renders via the fragile parser above**, as a wall of
  `<span class="scenariolabel">X:</span> <span class="scenariovalue">Y</span><br>` pairs
  (gamelobby.php:724-737) — no grouping, no visual distinction between "who can play" facts and
  "how the match ends" facts.
- **"OPTIONS SELECTED" is one comma-joined PHP string** (gamelobby.php:745, built at
  527-658) — `', Ladder Game, Simultaneous Movement (Brackets: 4), Mines Allowed, ...'` —
  unreadable past about four active options and impossible to scan for one flag.
- **Map preview is 400×300, unlabelled** (gamelobby.php:755-757) — no legend for deployment
  zones or terrain markers.
- **The faction list is already grouped** (`gamelobby.js:2448` `parseFactions`) into six
  buckets — Major/League of Non-Aligned Worlds/Minor/Ancients/Other/Custom — with a hand-rolled
  `[+]`/`[-]` text-toggle per group (line 2497). Functional, but: no search box, "Custom
  Factions" is one flat bucket (nowhere for the user's Nexus/Escalation-Wars/Other-Universe
  split to go), and a plain text glyph as the only tap target is a poor fit for touch.
- **The three faction "wheels" leave the site** — `gamelobby.php:674-680` links to
  `old.wheelofnames.com` for Tier 1/2/3 random picks, with no tie-in to this game's actual
  tier/custom filters (a wheel spin can hand you a faction the scenario forbids).
- **The ISD per-ship filter already exists and already works** — `#isdFilter`
  (gamelobby.php:813-817) is a manual numeric box, and it works because `ship.isd` is already a
  populated field on every ship class (`ShipClasses.php:66`, `public $isd = 0`). A **game-level**
  ISD cutoff is therefore a "seed and lock an existing control" problem, not a new-data problem
  — see §4.4.

---

## 2. Design language to reuse

Nothing new needs inventing — reuse the games.php "contact readout" grammar wholesale:

- `.fv-panel-head` bar per section, `--fv-card` / `--fv-card-hover` fills, `--fv-line-scs`
  borders, square `--fv-radius-chrome: 0`, Consolas for numerals.
- `.fv-well` / `.fv-card` for anything that is a *list of chips or rows* (scenario facts,
  faction groups, saved presets) instead of a new card component.
- `.fv-btn` family (from `gamesPanel.css`) for Next/Back/Confirm, replacing the legacy
  `.btn-create-submit` one-off styling.
- **New signature element for this pair of pages:** a left-edge accent rail per section, keyed
  to *topic*. **Final rail assignment, settled across the mockup rounds (supersedes the initial
  guess in this bullet):** the wizard's STEP-INDICATOR tab bar is simplest — Steps 1-3 always
  `--fv-accent` blue, Confirm always `--fv-own` green, regardless of which step's content you're
  looking at (this is the one place "urgency"-style colour, not topic colour, won this argument).
  Inside each step, content cards use: Game Options = `--fv-accent` blue; Scenario Description =
  `--fv-purple` (a new token, not urgency-coded — kept deliberately distinct from Gamelobby's own
  Scenario cards, which stayed blue); Teams & Map = `--fv-own` green for Team A's card/map zone,
  `--fv-enemy` red for Team B's (NOT `--fv-ally` blue — see §11.4, this was wrong for a full
  round before the real team-colour semantics were confirmed). On the CONFIRM step's summary
  cards specifically, all three cards (Game Options/Scenario Description/Teams & Map) are
  `--fv-accent` blue, uniformly — an explicit later correction, not amber/green as an earlier
  mockup pass had them. See §11 for the rest of what the mockup settled and corrected.

---

## 3. CREATE GAME redesign

### 3.1 Structure: a 4-step wizard, one page, one POST

Steps, matching the brief's own grouping:

1. **Game Options** — name, background (visual picker), ladder, movement, terrain, mines,
   reinforcements, desperate, friendly fire, points, **new:** private/password, **new:**
   In-Service Date.
2. **Scenario Description** — tier/power level, fleet requirements, custom factions, forbidden
   factions, enhancements, map borders, called shots, victory conditions, additional info —
   same fields, restructured presentation (§3.3).
3. **Teams & Map** — map template, map size, teams/slots with Copy Slot/Team, live map preview.
4. **Summary & Confirm** — read-only recap of all three steps, grouped exactly like the
   eventual gamelobby layout (so this screen doubles as "what your opponent is about to see"),
   Back jumps to the exact step, Confirm fires the one existing POST unchanged.

**Decision:** one page, one `<form>`, one POST — the four "steps" are `<section>`s toggled by a
small state machine in `createGame.js`, not four separate page loads. This keeps the entire
existing data contract (`Manager::createGame`, `GameRules`, `PlayerSlotFromJSON`) untouched —
the wizard is a client-side presentation change, not a new endpoint. Lower risk, no
half-created game sitting server-side if someone abandons mid-wizard, and the chat/ladder
`include()`s at the bottom of the page keep working exactly as now.

Progress indicator: four labelled steps in the panel head
(`GAME OPTIONS — SCENARIO — TEAMS & MAP — CONFIRM`), current step lit with `--fv-accent`,
**completed** steps stay clickable to jump back directly (not just a Back button). Validation
that blocks moving forward (e.g. a team needs ≥1 slot) shows inline using the existing
`.confirm`/warning styling — never a native `alert()`.

### 3.2 Step 1 — Game Options

- **Background picker → visual grid.** Replace the 26-item `<select>` with a scrollable strip
  of thumbnails (crop the existing 26 JPGs via CSS `background-position` — no new asset
  pipeline needed for v1), selection styled like an `.fv-card` pick (accent border + check
  glyph on the selected tile).
  *"Refresh existing backgrounds"* is a separate **content** task, not architecture —
  recommend re-cropping/upscaling the visually muddiest few (eyeball
  `8.Moon-AndreasSchantl.jpg`, `1.Default.jpg`) once the picker exists, not before; there's no
  point re-shooting art for a `<select>` nobody could see anyway.
  *"Add new map backgrounds"* needs **zero code** beyond the picker itself —
  `Manager::getMapBackgrounds()` already lists everything in `img/maps/` at request time.

- **Terrain options, made extensible.** Today: two hardcoded custom dropdowns (asteroid count,
  three moon-size counts). New ask: add "Dust and Meteorites" plus "a menu for adding more in
  future."
  **Decision:** generalise into one repeatable **Terrain Features** list — a
  "+ Add Terrain Feature" row where each entry is `{type: <select>, value: <number>}`, carried
  in the submitted JSON as `terrainFeatures: [{type, value}, ...]` instead of one flag per
  feature. Server-side, extend `GameRules.php` (source/server/model/GameRules.php) exactly the
  way `AsteroidsRule`/`MoonsRule` already do it — a private `getXRules($rules)` method plus one
  more `array_push`, and a new `DustAndMeteoritesRule` class matching the shape of the existing
  rule classes (lines 111-117 and 142-165 are the templates). Adding a fourth terrain type after
  that is one new `<option>` + one new Rule class — no UI rework. This is what "build menu with
  adding more in future" is actually asking for.

- **Existing checkboxes** (Ladder / Simultaneous Movement / Mines / Reinforcements / Desperate /
  Friendly Fire / Unlimited Points) — keep as checkboxes, add a one-line grey helper caption
  under each label (most of this is jargon to a new player — ties into
  [[project_dev_roadmap]] Tier-2 onboarding item). Group under the `.split-header` bar
  convention already in the file (creategame.php:64/217/300) — apply it consistently; today only
  two of the three columns actually get one.

- **New: In-Service Date.** A single year input next to the Tier/Custom-Factions notes (B5W
  eras are year-based, a bare year is enough precision). Stored as
  `tac_game.in_service_date` (nullable INT — blank = no cutoff, so every existing game's
  behaviour is unchanged). Feeds the gamelobby ISD filter directly (§4.4).

- **New: Private / password-protected game.** A "Private Game" checkbox reveals a password
  field. New `tac_game.password_hash` column (nullable — null = public). Reuse the
  `password_hash(PASSWORD_DEFAULT)` convention already established for player accounts
  ([[arch_auth_password_hash_migration]]) rather than inventing a second hashing scheme. The
  Join flow (games.php's Join Games list, and the actual slot-take action) needs a
  password-prompt gate — flagged as backend-touching, held to its own stage (§7, Stage 8).

- **New: Save & reuse settings.** "Save these settings" / "Load saved settings" next to the
  step header.
  **Decision, v1:** localStorage only (`fv.createGamePreset.<name>`), the same pattern
  `Settings.js` already uses for persisted player prefs (project map §4). Zero backend work,
  ships in the same stage as everything else in §3.2. A DB-backed, cross-device preset (a
  `tac_saved_game_settings` table shaped like the saved-fleet tables) is a natural v2 if presets
  ever need to survive a browser wipe or be shared — not built now since nobody asked for
  cross-device sharing yet ([[feedback_fv_workflow]] scope discipline).

### 3.3 Step 2 — Scenario Description, restructured

Every existing field stays (tier, requirements, custom factions, forbidden factions,
enhancements, borders, victory conditions, additional info) — the ask is presentation, not
content. **Called Shots is dropped** (user decision, confirmed against the mockup 2026-09-22) —
do not carry that field into the structured `scenario` JSON below.

- Lay out as a **card grid** (2 columns desktop, 1 column mobile): each field gets its own
  small card — an eyebrow label plus its control — instead of a flat list of label/select rows.
  This is the SAME shape Gamelobby will render each field as (§4.1), so building one shared
  renderer (a small `client/UI/scenarioCard.js`, loaded by both pages) covers both without
  writing the layout twice.
- The existing "select one of a few + reveal a text box on Other" pattern stays exactly as-is
  functionally, just restyle the reveal as a short slide/height transition instead of an
  instant `display:none`→`block` swap — one deliberate motion moment here, not scattered
  everywhere else on the page.
- **Submit as structured JSON**, not a formatted string:
  ```
  scenario: {
    v, tier, tierCustom, fleetRequirements, fleetRequirementsCustom, customFactions,
    forbiddenFactions, enhancements, enhancementsPoints, mapBorders,
    victoryConditions, victoryCustom, additionalInfo
  }
  ```
  This is the field that removes gamelobby's regex parser entirely (§4.1). **Corrected at
  Stage 0:** the original list here missed `fleetRequirementsCustom` (the real form's
  Fleet Requirements select has an "Other" + `#req_custom` reveal, same as Tier and Victory),
  and gained a `v` schema version. The authoritative key list is now
  `scenarioCard.FIELDS` in `client/UI/scenarioCard.js` — §12.1.
- **Backward compatibility:** existing lobbies only have the old free-text `description`.
  Gamelobby's renderer needs both paths — `scenario` JSON present → structured render; absent
  (a legacy game) → today's parser, unchanged. No backfill migration; old games simply age out.

### 3.4 Step 3 — Teams & Map

- Map template `<select>` and size inputs are already fine — unchanged.
- **Copy Slot / Copy Team** buttons sit next to "Add Slot"/"Add Team": clone the **source**
  row (not the blank template) using the exact `.clone()` mechanism already used for template
  cloning (`createGame.js:962`, `:1049`), copying every field's current `.val()` across and
  re-indexing ids/names the same way the existing Add flow already does. Additive to an
  existing function, not a new mechanism.
- The live preview canvas is untouched technically — it gains a legend strip underneath
  (your deployment zone / other team's / terrain) using the `--fv-own`/`--fv-enemy`/
  `--fv-neutral` tokens that already exist for exactly this purpose (tokens.css:220-229) but
  aren't used on this page yet.

### 3.5 Step 4 — Summary & Confirm

Read-only recap in the same three-card layout Gamelobby will use (§4.1) — this screen is
literally a preview of the opponent's view. Each card carries an "Edit" link back to its own
step, not a generic Back. Confirm fires the existing single POST (`docreate=true`) — no server
contract change from the wizard itself.

---

## 4. GAMELOBBY redesign

### 4.1 Scenario Description → structured render

- **New games** (with `scenario` JSON present): render as a **fact-card grid**, one small card
  per field — eyebrow label, value beneath — using `.fv-card`/`.fv-well`. This is the same
  component the Create Game summary step renders (§3.5); one shared `scenarioCard.js` renderer
  used by both pages avoids writing the layout twice.
- "OPTIONS SELECTED" (today's one comma-joined sentence) becomes a **row of chips** — one small
  pill per active rule ("Ladder", "Sim. Movement ×4", "Mines", "Asteroids (12)", "Friendly
  Fire") — reusing the `.fv-count-badge` pill markup already established in `gamesPanel.css`,
  instead of a run-on sentence that stops being scannable past four items.
- **Legacy games** (free-text `description` only): keep today's regex parser as the fallback,
  visually upgraded into the SAME card/chip shells where the data allows it (it still has
  labels/values, just recovered by regex instead of read from JSON) — so old and new games
  don't look jarringly different side-by-side.

### 4.2 Map Preview

**Reversed from this section's original guess, per the mockup (§11.3):** the top of Gamelobby
ended up as a three-column row — **Teams | Scenario Description | Map Preview** — rather than a
two-column Scenario/Map row with Teams full-width below. In three columns Map Preview is the
*narrowest* of the three (its own share, with Scenario Description and Map Preview stretched to
match each other's height, while Teams sits at its own natural height beside them) — so the
canvas actually gets SMALLER than today's 400×300, not bigger. Still gets the legend (deployment
zones + terrain markers) below the map. Team colours for the deployment-zone labels/overlays are
**not** `gamedata.getTeamColorVars`/`getMutedTeamColorRGB` (no such function was found this
session) — see §11.4 for the real, verified team-colour functions and which one applies to a
2-team vs. 3+-team game.

### 4.3 Faction picker overhaul

Current state: flat six-group outline list, `[+]`/`[-]` text toggles, no search
(`gamelobby.js:2448-2541`), with the ship list (all categories, all variants) rendered inline
underneath whichever faction is expanded — see §11.2 for exactly how deep that nesting really
goes (base hulls + every variant, not just base hulls as an earlier mockup pass wrongly assumed).

**Architecture revised from this section's original shape, validated across 3 mockup rounds —
this is the single biggest structural change to come out of the mockup pass:** picking a
faction and browsing that faction's ships are now two SEPARATE steps, on both desktop and
mobile — not "inline accordion on desktop, combobox sheet on mobile" as first planned here.

- **The Store/purchase panel shows ONLY the currently-active faction's ships**, scoped, never
  the full group→faction tree. A compact "[Faction name] · Switch Faction ▾" context bar sits
  above the ship list; the ship list itself is flat (category → base hull → nested variants),
  no faction-level nesting at all once a faction is picked.
- **A dedicated Faction Picker — same shape on desktop and mobile** — is where the six-group
  tree actually lives: search box pinned at top, the six groups (Custom Factions still split
  into its Nexus/Escalation Wars/Other Universe sub-groups, unchanged from the original ask),
  each faction a row, **stopping at faction level** — no ship/category nesting inside the
  picker at all. Desktop opens it as a centred modal; mobile opens it as a full-height sheet.
  Picking a row closes the picker and scopes the Store panel to that faction.
- **Tier/Custom filters and the faction randomiser (§4.5) live IN the picker**, not above the
  ship list — they scope which FACTIONS are selectable, not which ships show, so they belong
  where faction-picking actually happens.
- **Add a search box** inside the picker ("Filter factions…"), wired into the same filter
  pipeline the tier/custom checkboxes already drive (gamelobby.php:852-861).
- **Split "Custom Factions" into sub-groups** (Nexus, Escalation Wars, Other Universe) as asked
  — unchanged from the original plan. The data already carries this (per-faction directory names
  under `model/ships/` are literally `Nexus*`, `Escalation*`, `BSG*`,
  `ZStarWars`/`ZTrek*`/`StarWarsCloneWars`, etc., project map §3) — add a lookup table beside the
  existing `forceCustomGroup` override list (`gamelobby.js:2465`), applied only inside the
  Custom bucket. **Custom Factions gets a distinct yellow accent** (matches the mockup's
  `--fv-warn` treatment) in the picker; every other (non-custom, non-selected) faction row
  shares one neutral rail colour — no more one-colour-per-faction, simplified after mockup
  feedback ("for now," may return as a per-faction thing later).
- Group headers get a real disclosure triangle + a larger tap target (today: a plain `[+]`/
  `[-]` text glyph, `gamelobby.js:2497`) — unchanged from the original ask.
- **Ship-level "Show Custom" is a SEPARATE toggle from the picker's faction-level "Show
  Custom."** `applyCustomShipFilter` (gamelobby.js ~2966, `#toggleCustom`) hides any
  fully-CUSTOM ship (`ship.unofficial === true`, not `'S'`/SEMI-CUSTOM) inside an otherwise
  official faction's ship list, independent of which factions are selectable. The mockup's
  Store panel does not yet have a control for this — flagged as a gap to design, not built.

### 4.4 In-Service Date filter

- When a game has `in_service_date` set (§3.2), Gamelobby **pre-fills and locks** the existing
  `#isdFilter` box (gamelobby.php:813-817) to that value, swapping its label to something like
  "In-Service Date: 2258 (fixed by scenario)" — reusing the exact filtering logic already wired
  to that input, since `ship.isd` is already populated per ship. Zero new filtering code; this
  is "seed and lock an existing control."
- When no cutoff is set (legacy games, or a creator who left it blank), the box behaves exactly
  as it does today — fully backward compatible.

### 4.5 FV's own faction randomiser

Replace the three external Wheel-of-Names links (gamelobby.php:674-680) with an in-page
**"Randomise My Faction"** control:

- A button plus a small toggle ("Restrict to: current tier filters / all allowed factions")
  sitting **inside the Faction Picker** (§4.3's dedicated modal/sheet), next to the tier
  checkboxes it reads from — not next to the ship-browsing panel, since picking now happens in
  the picker, not the Store view.
- Fully client-side: the faction list is already loaded and already tier/custom-tagged
  (`gamelobby.js:2448-2541`) — the randomiser picks a random entry from whatever the CURRENT
  filter state resolves to, so it can never suggest a faction the scenario forbids, then opens
  and highlights that faction's group. No server round-trip, no new data.
- The "custom toggle" the brief asks for **is** the existing tier/custom filter state, reused
  rather than duplicated as a second setting.
- This drops the external-site dependency entirely — nothing on this flow should send a player
  off `fieryvoid.eu` to pick a faction.

---

## 5. Shared UI principles (applied per the Game UI Frontend skill guidance)

Both pages are pre-game **configuration** screens, not the live playfield — the skill's
"protect the playfield" rule translates here as "protect the decision the player is mid-way
through":

- One primary action visible at a time (Next/Confirm on Create Game; Ready/Save Fleet on
  Gamelobby) — never buried below a long scroll, which is today's Gamelobby buy-panel problem
  on a phone.
- Secondary/rarely-touched settings (Additional Info free text, forbidden-factions free text,
  saved-preset management) sit behind a clearly labelled disclosure, not open by default.
- Reserve real motion for actual state changes (the wizard step transition, the faction sheet
  sliding up on mobile); keep hover/focus states cheap and instant everywhere else, and respect
  `prefers-reduced-motion` — already a pattern in `gamesPanel.css:1149`, reuse it rather than
  re-deriving it.
- Design mobile-first for the two genuinely hard mobile surfaces named in the brief: the
  faction picker (§4.3) and the wizard navigation (§3.1) — both get an explicit mobile layout
  in this plan, not a media-query afterthought bolted on later.
- **Every stage must be mobile-friendly, not just those two surfaces** (user requirement,
  2026-09-23: "the new page design should be mobile friendly too", applying throughout the
  project). Each stage's own markup/CSS must work at 390px before it counts as done — §9's
  phone-width screenshot pass is a gate, not a nice-to-have. Prefer layouts keyed to their
  CONTAINER's width (see §12.1's fact grid) over viewport media queries: the same component
  sits in a third-width lobby column on desktop and a full-width phone screen.

---

## 6. Data model / schema changes

**Flagged separately — none of this should be built without a sign-off pass, since it's the
one part of this plan that touches `DBManager.php` / `db/emptyDatabase.sql` rather than pure
front-end.**

| Change | Column | Nullability | Risk |
|---|---|---|---|
| Structured scenario | `tac_game.scenario` (JSON/TEXT) | nullable, additive | Low — old rows are NULL, fallback parser (§3.3) handles it |
| In-Service Date cutoff | `tac_game.in_service_date` (INT) | nullable, additive | Low |
| Private game | `tac_game.password_hash` (VARCHAR) | nullable, additive | Medium — touches the JOIN flow (games.php's Join Games list + the slot-take action), needs its own review pass; same rollback caution as [[arch_auth_password_hash_migration]] applies to whatever hashing helper is reused |
| New terrain rule(s) | none — lives in the existing `rules` JSON blob | n/a | Low — follows the existing `GameRules.php` extension pattern exactly |
| Saved presets (v1) | none — localStorage | n/a | None |

All four DB-touching changes are additive-nullable columns on `tac_game` — no existing row
needs a backfill, no existing query breaks. Per repo convention, each ships as its own named
patch file (e.g. `db/addGameScenarioJson.sql`, `db/addGameInServiceDate.sql`,
`db/addGamePassword.sql`) plus the matching block in `db/emptyDatabase.sql`, exactly like
`addGameRules.sql` did for the existing `rules` column.

---

## 7. Staged build-out

Each stage is independently shippable and testable on its own. **Stages 1-3 (Create Game) have
a user-approved visual reference** — the mockup canvas's 4 wizard-step artboards (§11) — build
against those directly rather than re-deriving layout from this section's prose description.

- **Stage 0 — Shared groundwork. ✅ BUILT 2026-09-23 — as-built record and traps in §12.1.**
  No user-visible change: add the three additive columns (§6, minus the password join-flow
  work), stub the shared `scenarioCard.js` renderer. Lets every later stage build on real
  columns instead of a guessed shape.
- **Stage 1 — Create Game visual pass, still single-page. ✅ BUILT 2026-09-23 — §12.2.**
  Restyle in place: background
  picker grid, Terrain Features list, consistently grouped headers, structured scenario submit
  (§3.2/§3.3) — without the wizard yet. Biggest readability win, fastest, and the safest place
  to prove the new `scenario` JSON round-trips correctly before building navigation on top of
  it.
- **Stage 2 — Create Game wizard shell. ✅ BUILT 2026-09-24 — §12.3.** Wrap Stage 1's sections
  into the 4-step navigator + summary screen (§3.1/§3.5). Grew three user additions: moon counts
  as count combos, **Maps with Terrain** (templates with pre-placed terrain — a new server rule),
  and faint white hexes in game for Dust / Meteor Swarms.
- **Stage 3 — Copy Slot/Team + save/reuse presets (localStorage).** Small, isolated, no schema
  dependency.
- **Stage 4 — Gamelobby scenario/map rendering.** Structured-JSON render + legacy fallback
  (§4.1), map preview legend (§4.2). Depends on Stage 0's `scenario` column existing.
- **Stage 5 — Gamelobby faction picker overhaul.** Search, custom sub-groups, mobile sheet
  (§4.3). Independent of every other stage — could ship first if the mobile complaint is the
  most urgent one; ordered here only because it's the largest single chunk of new JS.
- **Stage 6 — In-Service Date end-to-end.** Create Game field + Gamelobby locked filter
  (§3.2/§4.4). Small, once Stage 0's column exists.
- **Stage 7 — FV faction randomiser.** Replaces the Wheel links (§4.5). Purely additive, no
  dependency on other stages.
- **Stage 8 — Private/password games.** Held to last deliberately — the only change touching
  the JOIN flow and therefore auth-adjacent code (games.php's Join Games list, the slot-take
  path). Wants its own focused review pass rather than riding along with a UI stage.
- **Stage 9 — Buy/Edit/Bulk-Buy dialog restructure** (§10.2). Accordion sections + sticky
  total-cost bar, built once against `confirm.showShipBuy`/`showBuyBulk` since both dialogs
  share the same row-building code. Independent of every Create Game/Gamelobby-page stage
  above — could ship any time, including before Stage 1, since it touches a different file
  entirely (`confirm.js`/`confirm.css`, not `gamelobby.php`/`createGame.php`).
- **Stage 10 — Filter box inside the buy dialog** (§10.2). Small, rides after Stage 9 since it
  assumes the accordion sections exist to filter within.
- **"Dust and Meteorites"** rides with Stage 1 (the UI slot for it) plus a small
  `GameRules.php` addition any time before Stage 1 ships — content, not its own stage.
- **Map background refresh** is art curation, not a coding stage — flagged for review once
  Stage 1's picker exists and the muddy ones are actually visible.

---

## 8. Open decisions — defaults I picked, flag any you want changed

1. **Wizard shape:** one page/one POST with client-side steps, vs. genuinely separate PHP
   pages per step. Picked: one page (§3.1) — lower risk, no partial-game server state.
2. **Saved presets:** localStorage-only v1 vs. DB-backed from day one. Picked: localStorage
   (§3.2) — matches the `Settings.js` precedent, no schema needed, upgrade path stays open.
3. **Scenario storage:** a new `tac_game.scenario` column vs. folding it into the existing
   `rules` JSON. Picked: separate column (§6) — `rules` has an established `GameRules.php`
   contract; mixing presentation-only fields into it risks an unrelated rule-parsing
   regression later.
4. **Terrain extensibility:** a generic repeatable "Terrain Features" list vs. one more
   hardcoded row per type. **Reversed after the mockup (see §11.6):** the mockup built FIXED
   rows — one row per known terrain type (Asteroids, Moons Small/Medium/Large, Dust Clouds,
   Meteorites), no "+ Add Terrain Feature" affordance — and the user approved that shape across
   two rounds without asking for the generic add-row UI back. This still maps cleanly onto
   `GameRules.php`'s real extension pattern (one new Rule class per terrain type, e.g.
   `DustAndMeteoritesRule`) — "extensible" turns out to mean "cheap to add a new fixed row +
   Rule class when a type ships," not "player-facing generic add-a-row control." Build the fixed
   list, not the generic repeater.

---

## 9. Testing / verification

No automated coverage exists for either page today — the replay harness
([[project_replay_harness]]) is server-simulation only and doesn't touch page rendering.
Verification is manual, per stage:

- Desktop 1920×1080 and a real phone width (390px) screenshot pass, before/after, each stage.
- Per [[feedback_fv_workflow]]'s testing convention: create a **fresh** test game per test
  (never `safeGameID`), covering both a legacy-shaped game (created with today's code, then
  viewed under the new Gamelobby renderer, to prove the fallback path) and a new-shaped game
  (created and viewed fully through the new wizard).
- Confirm `fvbuild.ps1 -Check` still passes after any DB/autoload-touching stage (Stage 0,
  Stage 8) — no ship-data or replay regression is expected, but it's the standing pre-deploy
  gate.

---

## 10. Buy / Edit / Bulk-Buy Ship Dialogs (added 2026-09-22)

Reached from Gamelobby's purchase panel (`.addship` click → `gamelobby.buyShip`/`buyBulk`/
`editShip`, `gamelobby.js:3538`/`3228`/`3889`). Not a separate page — a `.confirm` modal built
entirely in JS, shared by all four pages via `confirm.css`. In scope because the number of
Enhancements/Options a ship can carry has grown past what the dialog's layout was designed for.

### 10.1 What exists today (audit)

- **One flat, unbounded list.** `confirm.showShipBuy` (`confirm.js:1242`, the single-ship buy
  dialog) and `confirm.showBuyBulk` (`:1487`, shared by bulk-buy AND edit — `existing` is the
  only switch between them) both loop over `ship.enhancementOptions` and `.prependTo()` one
  `.missileSelectItem` spinner row per entry into the same scrolling box. There is no grouping,
  no pagination, no collapse — a ship with a long enhancement/option/ammo list is just a long
  scroll.
- **Category signal already exists in the data, but only drives text colour.** Each row already
  knows what it is: `enhIsOption` (enhancement vs. option) and an ammo-type substring match
  (`HEAVY AMMO`/`MEDIUM AMMO`/`LIGHT AMMO`/`AMMO`, `confirm.js:1328` and `:1567`) — today these
  only prepend a coloured `<span>` to the row's own label (amber for options, cyan for ammo).
  Nothing structural reads these flags; they're the ready-made grouping key.
- **The Total Cost readout isn't pinned, and ends up buried.** `showShipBuy` inserts the
  `.totalUnitCost` row once, *before* the enhancement loop (`confirm.js:1250-1251`) — but every
  row added afterwards also calls `.prependTo(e)`, which inserts at the top of the container
  each time. By the time the loop and the missile-options/fighter-size rows that follow are all
  added, the total-cost row has been pushed down past all of them. On a ship with many rows the
  running total scrolls out of view during exactly the interaction (adding more things) where a
  player most needs to see it.
- **The dialog shell is a fixed 540px box that just grows and scrolls**
  (`confirm.css:21-42` — `max-height:100%; overflow-y:auto`, no internal sectioning). This is
  the literal container for every `multi-value-confirm` variant in the file (hangar
  dock/launch/recover dialogs use the same shell) — whatever layout mechanism is added should
  stay compatible with that shared shell rather than forking it.
- **Dead code found in passing:** `confirm.showShipBuy` is defined **twice** in the same object
  literal (`confirm.js:653` and `:1242`) — the second silently wins, matching the
  `Manager::advanceGameState` duplicate-definition pattern already known elsewhere in this
  codebase. Whoever builds this stage should delete the first copy rather than edit it by
  mistake.

### 10.2 Design direction: accordion sections + a sticky summary, not tabs

**Decision:** group rows into collapsible **accordion sections**, not tabs. Tabs hide whatever
is selected in a category the instant you leave it, which is the wrong tradeoff for a screen
whose entire job is tracking a running cost across everything you've picked — this dialog
already has one real bug in that direction (the buried total, above), and tabs would add a
second, worse one. An accordion keeps every section reachable without a full page of open rows,
while never fully hiding a category's selected state:

- **Sections, built from data that already exists** (no new server-side modelling needed):
  **Ammo & Ordnance** (the existing ammo-type tags + missile options), **Enhancements**
  (`enhIsOption === false`), **Options** (`enhIsOption === true`), and a **reserved fourth
  section for Officers** if/when that feature is designed — this plan only reserves the slot in
  the layout, it does not design what an Officer is or does; that's a game-rules feature with
  its own scope, not a UI arrangement question.
- **Each collapsed section header shows a count + subtotal badge** — e.g. "Enhancements · 3
  selected · 45pts" — reusing the `.fv-count-badge` pill pattern from `gamesPanel.css` so a
  collapsed section never fully hides what's chosen inside it.
- **A sticky total-cost bar** (`position: sticky`, pinned to the top or bottom of the `.confirm`
  box) replaces the current prepend-and-get-buried row — this is the direct fix for the bug in
  §10.1, independent of whether accordion sections ship in the same pass.
- Sections default to **open when the ship has few rows total** (today's experience,
  unchanged for a lightly-equipped ship) and **collapsed by category once the row count passes
  a threshold** (say ~8), so the dialog only gets more structured exactly when it's currently
  straining, not always.
- Multiple sections may be open at once — this isn't a radio-tab switch, just a way to hide bulk
  you aren't currently adjusting.

**Also add a filter box** at the top of the dialog ("Filter…") for ships with a lot of rows —
grouping helps browsing; a text filter is the actual fix for "I know the name of the
enhancement I want," which a B5W player very often does. Filters within whichever sections are
open; an empty section (everything filtered out) collapses itself.

Both `showShipBuy` and `showBuyBulk` should keep building from the **same** row-construction
code (as they already mostly do) so the accordion/sticky-bar/filter logic is written once and
shared — do not fork into two copies the way the dead `showShipBuy` duplicate shows this file
has drifted before.

### 10.3 Schema / backend impact

**None.** Every row already carries the flags needed to group it (`enhIsOption`, ammo-type
string). This is a pure `confirm.js` + `confirm.css` restructure — no `tac_game`/`tac_ship`
column, no `Manager.php`/`DBManager.php` change, no `GameRules.php` involvement. The eventual
Officers feature, whenever it's designed, is the one part of this that WOULD need new schema —
tracked as a future item, not part of this UI stage.

### 10.4 Open decision

**Collapse threshold** — I picked "~8 rows in a section" as the point where it defaults
collapsed rather than open; this is a guess pending your eye on how the sections actually fill
up once built. Easy to retune as a single constant once it's in front of real ship data (a
heavily-loaded capital ship vs. a bare fighter will want different defaults, which is exactly
why it's a per-section count check rather than a global one).

### 10.5 Refinements from the mockup pass

- **A "Base Hull" line item** sits above the accordion sections, showing the ship's own point
  cost, so the sticky total is arithmetically transparent (base + every section's subtotal =
  the total shown) rather than an unexplained number.
- **Ammo & Ordnance rows are NOT colour-coded by ammo type.** Today's dialog prepends a
  coloured `<span>` per ammo tier (amber/cyan per §10.1's audit) — drop this in the redesign.
  Every action link and every row label in this dialog family reads in one consistent colour;
  colour-per-category was tried in the mockup and explicitly rejected.

---

## 11. Mockup findings (Design-canvas pass, 2026-09-22) — read before implementing

Canvas: https://claude.ai/artifact/4z8DSdV35K2AxZ5VmUkG1w — a Design-canvas mockup covering all
4 Create Game wizard steps, Gamelobby's main screen, both faction pickers (desktop modal +
mobile sheet), the Buy Ship accordion dialog, a with/without-Reinforcements bought-fleet
comparison, and a 4-team panel variant. **Create Game (all 4 steps) is user-approved and closed
as of this pass** ("I am content for now with Create Game design"); Gamelobby is still open to
further iteration. Read the canvas directly for exact layout/copy before implementing — this
section is the durable facts extracted from it, not a substitute for looking at it.

This section supersedes anything above it in this document where the two disagree (several
earlier assumptions — faction-picker shape, map-preview sizing direction, terrain UI shape, rail
colours, team colours — turned out wrong or were revised after real-code verification; the
corrections are already folded into §2/§3.3/§4.2/§4.3/§4.5/§8 above, this section is the
supporting detail and the parts that don't have an obvious home elsewhere in the doc).

### 11.1 Real category order and open/closed defaults for the Store ship list

The 7 size-class categories render in this order top-to-bottom — **Mines, Immobile Structures,
Capital Ships, Heavy Combat Vessels, Medium Ships, Light Combat Vessels, Fighters** (ending with
Fighters) — the reverse of the array order `sizeClassHeaders` is declared in
(`gamelobby.js:2783`), because the render loop walks it backwards. Default open/closed: Capital
Ships, Heavy Combat Vessels, Medium Ships and Fighters start open; Light Combat Vessels,
Immobile Structures and Mines start closed (a category's own ship rows still sort correctly
whether or not it starts collapsed). Within an open category, base hulls sort by cost
DESCENDING, except Mines, which sorts alphabetically
(`orderShipListOnPV` vs `orderShipListOnName`, gamelobby.js:2412/2824).

### 11.2 Variants are real, independently-purchasable rows — get this right the first time

**This was gotten wrong twice during the mockup pass before a user screenshot of the live game
settled it — do not re-derive, this is verified against primary source.** A ship variant
(`ship.variantOf != ''`, an alternate loadout of a base hull) is NOT hidden inside a "pick your
loadout" step of a Buy dialog. It is its own row, indented directly under its base hull, with
its own independent "Add to fleet"/"Show details" links — exactly like a base hull, just styled
differently. The real render function is **`parseShips`, `gamelobby.js:2764-2930`** — there is a
dead, never-invoked "old, simple version" of the same function name at lines 2679-2698 that
caused the first misread; confirm which `parseShips` is actually called before trusting a
reading of this area again. Nesting mechanism: an outer loop walks cost-sorted base hulls only
(`if (ship.variantOf != '') continue;` at line 2858 — this only stops a variant from being used
as an outer anchor, it does not hide it); a separate inner loop
(`gamelobby.js:2885-2914`) re-scans the full ship list for every variant of that base hull and
appends each one immediately beneath it, in the base hull's own category regardless of the
variant's own `shipSizeClass`.

**Visual treatment** (`prepareClassName`, gamelobby.js:2701-2740, cross-checked against a
live-game screenshot): base hull name → **bold**, colour `#90b1ee`. Variant name → **italic +
indented**, colour `#578bec` — two distinct blue shades, this is the actual distinction, not a
single shared "dim" colour. Category header text: `#d0dbec` (`.categoryType`, `lobby.css:234`).
Action links ("Add to fleet"/"Show details"): `#DEEBFF` (`.store .ship .clickable`,
`lobby.css:364`).

**Every ship name carries a real "(TAG)" suffix**, built from three ship-object fields (no
lookup table): rarity letter from `ship.occurence` (misspelled in the codebase, not
"occurrence") — common→C, uncommon→U, rare→R, unique→Q; an optional `NN%` from `ship.limited`
shown only when `0 < limited < 100`; and `SEMI-CUSTOM` (`ship.unofficial === 'S'`) or `CUSTOM`
(`ship.unofficial === true`, boolean). A fully-CUSTOM (not semi-custom) variant is hidden by
default — see §4.3's ship-level "Show Custom" note. Fighters additionally carry a
`[H]`/`[M]`/`[L]`/`[U]`/`[SHF]` size badge (`getFighterSizeTag`, gamelobby.js:2745-2761) and a
per-unit cost note for squadron purchases, e.g. "348p (58 ea.)".

### 11.3 Gamelobby top-of-page: three columns, Teams first

Final layout, left to right: **Teams | Scenario Description | Map Preview**. Teams sits at its
own natural height; Scenario Description and Map Preview are the two that visually match height
(nested in their own sub-row) — Teams is deliberately NOT forced to match, since its height
varies with team/slot count. With 3+ teams, the Teams panel grids into 2 columns (same pattern
as the separate 4-team artboard) instead of stacking indefinitely — not demonstrated live in the
2-team mockup artboard, since that would require actually showing a 3+-team scenario there.
Scenario Description's own internal order: the "Game Rules" chip row (Ladder/Sim.
Movement/Mines/etc., one pill per active rule) sits ABOVE the fact-card grid, not below it — an
explicit reorder request. A subtlety worth remembering for any stretched/flex-matched card
layout: if a panel gets force-stretched taller than its own content, `justify-content:
space-between` on its content does NOT close the resulting gap — it just relocates the gap to
BETWEEN the panel's own children. The actual fix is to not force-stretch panels whose content
heights are allowed to differ (`align-items: flex-start` on the row, not `stretch`).

### 11.4 Team colours — verified against `gamedata.js`, do not invent

**2-team games** use the RELATIVE mine/ally/enemy scheme already established everywhere else in
the app (combat log, ship icons, `getFleetHeaderColorRGB`): green = your own team, red = the
OTHER team (enemy), blue = "ally" — which specifically means *same team, different player slot*,
never "the other team." A viewing participant's own team is always green; the other of the two
teams is always red — never blue, however tempting "give team B its own colour" looks. Tokens:
`--fv-own`/`--fv-own-signal` (green) and `--fv-enemy`/`--fv-enemy-signal` (red), already in
`tokens.css`.

**3+ team games use a completely separate, ABSOLUTE per-team-index palette**
(`teamBaseColorsMultiTeam`, `gamedata.js`): 1 Green / 2 Orange / 3 Cyan / 4 Purple / 5 Yellow /
6 Blue / 7 Magenta / 8 Red — do not reuse the 2-team own/ally/enemy tokens for a 3+-team
context; they mean something different there (there's no single unambiguous "ally"/"enemy" once
more than 2 teams exist). `tokens.css` has no ready-made tokens for team-index 2-8; the mockup
added local `--fv-team2`/`--fv-team3`/`--fv-team4` (`#ff9628`/`#28e6e6`/`#aa5ae6`) for its
4-team demo artboard — worth promoting to real `tokens.css` entries if this palette gets used
more than once in the real build.

**Reinforcements' real colour is `#00b8e6` cyan** — found in `gamedata.js` comments ("FV's 'not
here yet' cyan, the same value as the blue Jump Point marker and the fleet list's hyperspace
rows"), not an invented colour. "Main Fleet" (the non-Reinforcements bought-ship group header)
is not this colour and isn't green either — settled on the page's own chrome blue (`--fv-accent`)
specifically so it's visually distinct from the more saturated Reinforcements cyan while still
"fitting the page's colour scheme."

### 11.5 Bought-ship rows: name vs. shipClass, and the action-link colour rule

**A bought ship shows its player-given name AND its hull class as two separate pieces of text**,
per the real template (`gamelobby.js:943-949`, classes `.shipname`/`.shiptype`): `ship.name`
(defaults to `ship.shipClass` until the player renames it) rendered bold, `ship.shipClass` (the
hull's display name, e.g. "G'Quan Heavy Cruiser") rendered normal-weight, same base text colour,
`padding-left:3px`, in a fixed `min-width:150px` name column so classes align down the list
(`lobby.css:286-308`). This is NOT the same axis as the Store's size-CATEGORY grouping
(Fighters/Medium/Heavy/Capital/etc) — conflating the two was an error caught mid-mockup.

**Every clickable action link in the Purchase Fleet panel — Show Details, Add to Fleet, Details,
Edit, Copy, Reinforcement⇄Main Fleet, and Remove — shares ONE colour.** An earlier mockup pass
gave "Remove" its own red, which the user explicitly rejected as clashing; there is no
destructive-action colour distinction in this part of the redesign, unlike normal web-app
convention.

### 11.6 Small, settled UI decisions worth carrying into the build

- No checkmark (✓) glyphs on completed wizard-step tabs — tried, explicitly removed.
- No "new" tags/badges next to newly-added fields (Private Game, In-Service Date, Dust
  Clouds/Meteorites) — tried, explicitly removed; the fields just look like any other field.
- **A checkbox that reveals a dependent control** (Simultaneous Movement's bracket-count
  dropdown, default 8; Desperate Measures' which-teams-affected dropdown, default "Both Teams")
  is INLINE, appended right after the row's own label text, one line tall — same established
  pattern as "Ladder Game *View Ladder*" already uses in the same card, not a separate flex
  column beside the checkbox (that shape was tried, looked bad — the dropdown visually
  stretched to the row's full label+caption height as a flex sibling of the caption block, and
  was dropped after a screenshot showed exactly why). The dependent control is only present in
  the markup at all when its checkbox is checked in that mockup's state — an unchecked row (e.g.
  Desperate Measures) shows no dropdown, with the caption text itself naming what ticking would
  reveal and its default, since a static mock can't show a live show/hide toggle. **In-Service
  Date**, which has no boolean checkbox (blank = off), instead puts its year input as the FIRST
  element in the row, left-aligned to the same x-position every other row's checkbox starts at —
  not indented behind a blank spacer div (tried, didn't actually align), and not floated to the
  right edge of the container (the original placement, also wrong).
- Terrain Features card: single column of rows (label + count dropdown each), not the two-column
  grid an earlier pass tried — the two-column split stopped being necessary once the card moved
  beside Rules & Options instead of spanning the step's full width.

---

## 12. Build log

### 12.1 Stage 0 — shared groundwork (built 2026-09-23)

**What landed** (no user-visible change; nothing calls the renderer or touches the columns yet):

- `db/createGameRedesign.sql` — ONE migration, not the three files §6 suggested (matches the
  more recent `reinforcements.sql` precedent: one feature, one file). Adds `tac_game.scenario`
  (TEXT), `in_service_date` (INT), `password_hash` (VARCHAR(255)), all `DEFAULT NULL`, after
  `description`. Uses `ADD COLUMN IF NOT EXISTS`, so re-running it is a no-op (verified by
  running it twice against a throwaway `CREATE TABLE … LIKE tac_game` copy). Matching block in
  `db/emptyDatabase.sql`. **Safe to apply ahead of any code** — every existing `tac_game` query
  was audited: the one INSERT (`DBManager::createGame`) already names its columns, and every
  `SELECT *` maps its row field-by-field into `TacGamedata`.
- `client/UI/scenarioCard.js` — `window.scenarioCard`. `FIELDS` is **the storage contract** for
  the scenario JSON (keys + the option strings, the latter verified verbatim against
  creategame.php's `<option value>`s by script); `normalise(raw)` accepts a JSON string, an
  object or nothing and returns known keys only, all trimmed strings; `render(raw, {plain})`
  returns an escaped `<dl>` fact grid (or `""`). Display rules taken from the mockup: an
  "Other"/"Up to X points" choice shows what was typed ("Up to 20 pts"); Forbidden Factions is
  left out when it says "None"; empty facts are left out (so an older, shorter scenario renders
  cleanly); Additional Info spans the full width and keeps its line breaks via CSS
  `white-space: pre-line`, never `<br>` markup. Each field has a form `label` (creategame.php's
  wording) and a shorter `factLabel` (the mockup's lobby wording — "Tier", "Custom Factions").
- `styles/scenarioCard.css` — `.fv-scn-*`. NOT gamesPanel.css's `.fv-card` (that is games.php's
  clickable `<a>` card and the file isn't linked on either page). **Column count follows the
  grid's own width**: at most two columns, one once a column would be under 11rem — so it is
  two-up in a third-width lobby column and one-up on a phone without a media query. `plain`
  modifier drops each fact's card chrome for use inside the Summary step's card.
- Wired on both pages: CSS via `AssetLoader`; JS via `AssetLoader` on creategame.php, but as a
  PLAIN tag inside gamelobby.php's `$debug` block (bundle-legacy.js skips AssetLoader tags —
  a versioned tag there would never enter the lobby bundle).

**Traps found — read before Stages 1, 4, 6 and 8:**

1. **Lobby payload uses `JSON_NUMERIC_CHECK`** (`Manager::getGameLobbyDataJSON`). Any
   numeric-looking STRING nested anywhere in the payload becomes a number, and some change value
   on the way ("0012" → 12, "1e5" → 100000). Stage 4 must publish `scenario` as its **raw JSON
   text** (a string at the top level — never numeric) and let `scenarioCard.normalise` parse it
   client-side; publishing a decoded object would let this flag rewrite player free text.
2. **The DB connection is `utf8` (3-byte)** (`mysqli_set_charset(…, 'utf8')`). A 4-byte
   character (emoji) in a free-text field can't be stored. Stage 1: re-encode the validated
   scenario server-side with `json_encode`'s DEFAULT flags (never `JSON_UNESCAPED_UNICODE`) — the
   stored text is then pure ASCII `\uXXXX` and immune. (`description` already has this latent
   problem today; not ours to fix here.)
3. **`description` must KEEP being written** alongside `scenario`. Besides the PHP parser,
   gamelobby.php's inline JS regex-matches it directly — `/CUSTOM FACTIONS \/ UNITS:\s*Allowed/i`
   at ~lines 361 and 384 (the "Show Custom" default and its warning). Stage 4 can switch those
   two to `scenario.customFactions` with the regex as the legacy fallback.
4. **`TacGamedata`'s constructor rewrites `\n` → `<br>` in `description`.** Do not route
   `scenario` through that path, or through anything else that does.
5. **`password_hash` must never enter `TacGamedata`.** `stripForJson` builds its object by hand,
   so a property it doesn't name stays server-side — but that payload is inlined into
   gamelobby.php and polled by game.php, so one careless `$strippedGamedata->… = $this->…` would
   hand a private game's hash to everyone who opens it. Stage 8: give the join path its own
   narrow query.
6. **creategame.php has NO `<meta name="viewport">`** (gamelobby.php does). Without it a phone
   lays the page out at ~980px and scales it down, so NO responsive CSS on that page can ever
   take effect. Not added at Stage 0 because it changes how today's fixed-width page renders on
   phones (user-visible); **Stage 1 must add it**, since the restyled page is designed for it.
7. **Mockup leftover:** `CreateGame_Step4_Summary` still shows a "Called Shots: Allowed" fact
   and paraphrased values ("Hard edge"). Called Shots is dropped (§3.3) and values are always
   the real option text — the renderer already does both.
8. **Verifying at 390px with headless Chrome on Windows:** the browser enforces a minimum
   window width (~500px) and just crops the screenshot, so `--window-size=390,…` lays out
   wider than it shows and looks broken/overflowing. Test a component inside a 358px-wide box
   (390 minus 16px gutters) on a wider page instead, or use real device emulation.
9. **Local DB:** the migration was only validated against a scratch copy — apply
   `db/createGameRedesign.sql` to the local DB (and later test/live) before Stage 1's code runs.
   The legacy watcher wasn't running at build time; the lobby bundle was rebuilt by hand with
   `FV_NO_MINIFY=1 node scripts/bundle-legacy.js` (what the watcher runs).

### 12.2 Stage 1 — Create Game restyle, single page (built 2026-09-23)

**⚠️⚠️ DEPLOY ORDER: `db/createGameRedesign.sql` MUST be applied to a database BEFORE this
stage's code runs against it** (local, test and live). `DBManager::createGame` now names the
`scenario` column, so without the migration EVERY game creation fails — the Fleet Builder included.

**What landed:**

- **creategame.php** rebuilt as three `.cg-section` panels — Game Options / Scenario
  Description / Teams & Map — then one Create Game button. Still one form, one POST; no wizard
  (Stage 2). Added `<meta name="viewport">` (§12.1 trap 6). `createGame.js` is now versioned
  through `AssetLoader`: it and the markup change together, and a cached copy against the new
  page would find none of its ids.
- **Background picker:** a horizontally scrolling strip of lazy-loaded thumbnails (the 26
  backgrounds total 2.3 MB). A radio group named `background`, so it is still one Tab stop with
  arrow keys; the choice still repaints the page backdrop as a full-size preview.
- **Rules & Options:** the mockup's check rows with a one-line caption each. Brackets and
  Desperate teams are the inline dependent selects of §11.6, shown only while ticked. Real option
  values kept (brackets 1-12, default 8 from `SimultaneousMovementRule`; Desperate
  Both/Team 1/Team 2). **Labels follow the mockup except "Desperate Scenario"**, which keeps the
  real wording (the mockup's "Desperate Measures" is not a term used anywhere else).
  All element ids are unchanged, and the page now re-reads every ticked box on load — a browser
  restoring the form (Back, failed POST) used to leave a ticked box whose rule was never set.
- **Terrain Features:** six fixed count rows, no master "Add Terrain" checkbox; a type enters
  the rules only when its count is above zero (`readTerrain`). Moons (0-5 / 0-4 / 0-2) are plain
  `<select>`s (Stage 2 made them count combos too, §12.3). **Asteroids, Dust and Meteor Swarms are a COUNT COMBO** (`cgCountCombo` +
  `createGame.initCountCombos`), keeping the old asteroid box's best-of-both-worlds behaviour at
  the user's explicit request: ANY value 0-48 can be typed (digits only, clamped on change) or
  stepped with the mouse wheel while focused (the page's shared wheel handler), and the ▾ lists
  ONLY the named presets — None/Few/Several/Pack/Lots/Horde/Swarm/Zounds. A first cut that listed
  every number 0-48 in a `<select>` was rejected: it lost free typing and the wheel, and buried
  the presets. The combo is an ARIA combobox (input keeps focus, `aria-activedescendant`;
  ArrowDown opens, arrows move, Enter picks without submitting, Escape/Tab/outside click
  close; one open at a time). On touch, the ▾ opens the list WITHOUT focusing the field, so the
  phone keyboard doesn't cover it. ⚠️ `.cg-section` deliberately has NO `overflow: hidden`: on a
  phone, Meteor Swarms is the last row of its panel and its list must drop past the panel edge.
- **Dust / Meteor Swarms** (user rulings 2026-09-23): each count is that many **single-hex** units,
  0-48 with the asteroid presets, labelled "Dust" and "Meteor Swarms". New `DustAndMeteorsRule`
  (rules key `dustAndMeteors: {dust, meteors}`, clamped 0-48, absent when both are 0).
  `BuyingGamePhase` creates `spawnDustField` / `spawnMeteoroid` — the Triad Asteroid Salvo's own
  classes, so collision damage and not blocking line of sight come for free; their map names stay
  "Dust Field" / "Meteoroid Field". **Placement ruling:** they may sit adjacent to ANY other
  terrain, moons included (no moon buffer), but never share a hex; they are placed last. Every
  other terrain type keeps its old spacing. Also shown in the lobby's Options Selected line and
  counted by the games list's TERRAIN chip.
- **Scenario Description:** built from `scenarioCard.FIELDS` (which gained `help` text), 8 purple-
  rail cards in a 2→1 container-width grid. "?" expanded the help INLINE rather than as a floating
  bubble — **superseded at the Stage 2 review (§12.3): it is the mockup's floating window now.** "Other" / "Up to X points" reveal
  their input with the plan's one deliberate animation (grid-row 0fr→1fr, off under
  `prefers-reduced-motion`). Submits BOTH `scenario` (JSON) and the legacy `description`, whose
  format is byte-compatible with the old one minus the Called Shots line.
- **Server:** `Manager::cleanScenario` keeps known keys only (its `$scenarioKeys` mirrors
  `FIELDS` — change both together), trims and caps them (4000 chars for Additional Info, 200
  otherwise, digits only for points), stamps `v: 1`, and encodes with DEFAULT flags so the stored
  text is pure ASCII (§12.1 trap 2). Option values are deliberately not checked against the lists.
- **Teams & Map:** map preview + legend beside team cards. One `teamColor()` drives the canvas
  zones, the team rails and the legend, matching the game's own palettes (§11.4): 2 teams
  relative (Team 1 green, Team 2 red); 3+ teams absolute (`teamBaseColorsMultiTeam`), repainted on
  every refresh because adding a third team turns Team 2 from red to orange. The legend names teams
  ("Team 1", "Team 2"…) rather than the mockup's "Your / Other team's deployment": nothing on this
  canvas is per-viewer, and no terrain is drawn on it. The canvas keeps its 545×390 drawing surface
  and is only CSS-scaled (nothing on it is clickable).
- **Mobile:** everything collapses to one column (grids by their own width where possible,
  900px where they split the page); form controls go to 16px under 600px (iOS zooms the page on
  focusing anything smaller) and 44px tall on touch screens; `lobby.css`'s
  `#mapPreview { min-width: 480px }` and base.css's fixed 1000px chat panel are overridden here.

**Deliberately NOT in Stage 1** (they appear in the Step 1 artboard but belong to later stages):
In-Service Date (Stage 6), Private Game (Stage 8), Save/Load Settings and Copy Slot/Team (Stage 3),
the step tabs and Next/Back (Stage 2).

**Verified:** PHP lint on all seven files; `cleanScenario` + rule round-trip + the REAL
`BuyingGamePhase::advance()` placement pass run against a stub DBManager — 40 randomised runs,
2480 units placed, zero shared hexes, dust/meteors landing adjacent to other terrain 664 times,
asteroid/moon spacing unchanged; creategame.php rendered through PHP CLI and driven in headless
Chrome (the posted `data` checked field by field); screenshots at desktop and 390px (the latter
inside an iframe, which IS its own viewport — see §12.1 trap 8). `fvbuild -Check`: autoload map
up to date, ship validator clean, replay 130/131 — the one, **game 4251** (ship 6's system
`output` values each 1 lower), fails identically with every Stage 1 server change stashed, so it
is pre-existing drift awaiting a re-record, not this stage.
**Count combo, verified 2026-09-24 with Docker down** (not restarted): creategame.php syntax-
checked with the `php-parser` npm package (negative-controlled — it rejects a planted error); the
combo driven in headless Chrome on the previous CLI render with the three rows swapped for a
line-for-line mirror of `cgCountCombo`'s output — typing, clamping, wheel, ▾, keyboard, mouse pick,
outside-close, Escape and the posted rules all as intended; screenshots of an open list on desktop
and of the LAST row's list on a phone, unclipped. ⚠️ A real PHP lint + CLI render of the final
creategame.php is still owed once Docker is back up.

**Found, not fixed:** `lobby.css` still carries `#asteroidsDropdown` / `#moonsDropdown` rules for
elements that no longer exist anywhere (dead, harmless).
**Owed item closed at Stage 2:** creategame.php now lints clean under the real `php -l` and
CLI-renders with no PHP errors (Docker was up on 2026-09-24).

### 12.3 Stage 2 — Create Game wizard + Maps with Terrain (built 2026-09-24)

**⚠️ One schema change, added at review: `tac_game.rules` varchar(400) → text** (appended to
`db/createGameRedesign.sql` + `emptyDatabase.sql`). A terrain map's `terrainLayout` is 0.9-1.9 KB of
rules JSON, so without it creating a game on any "Maps with Terrain" entry fails with "Data too
long for column 'rules'". Apply before this stage's code runs (local, test, live); the statement
is a no-op on re-run and keeps every stored value and the `'{}'` default (checked on a scratch copy
of `tac_game`). **One new server class** (`TerrainLayoutRule`, autoload map regenerated) and
`game.legacy.bundle.js` needs rebuilding for the renderer change — both happen in a normal deploy
build.

**The wizard (plan §3.1 / §3.5, mockup Step 1-4 artboards):**

- creategame.php's three `.cg-section`s are now steps 1-3 (`#cgStep1..3`, `data-step`), plus a new
  read-only **Step 4 Summary & Confirm** (`#cgStep4`). Still one form, one POST, no endpoint change.
  One shown at a time via the `hidden` attribute — `.cg-page [hidden] { display:none !important }`
  because `.cg-btn` and friends set their own display, which beats the browser's `[hidden]` rule.
- **Step bar** (`.cg-steps`): four `<button>`s, `aria-current="step"` on the current one. Rails per
  §2: steps 1-3 blue, Confirm green, always; progress shows as fill/brightness (current tinted,
  passed full strength, ahead faded). No ✓ glyphs (§11.6). Every tab is clickable.
- **Navigation rule:** going FORWARD (Next, or any later tab) runs `createGame.validateStep()` on
  every step being passed and stops on the first that is not ready, with the reason in the bar and
  the field flagged (`aria-invalid`) and focused. Going back never checks. Kept deliberately short —
  only what would reach the lobby blank or broken: a game name; an "Other" / "Up to X points" with
  nothing typed; a limited map with no size; a slot with no name.
- **Sticky nav bar** (`.cg-nav`, `position: sticky; bottom`): Cancel (step 1, → games.php) /
  Back, then Next ("Next: <next step>", just "Next" on a phone) or, on Step 4, **Confirm & Create
  Game** ("Create Game" on a phone). The step error lives INSIDE the bar — at the foot of a long
  step it could be scrolled out of view.
- **Submit guard replaced.** The old mousedown/touchstart `allowSubmit` flag also refused the
  keyboard's own Enter/Space on the button. Now: the form submits only from Step 4 (Enter in a
  field on steps 1-3 is an implicit submit through Confirm and is refused), every step is
  re-checked on the way out, and Confirm disables itself after the first press (two presses = two
  games) — re-enabled on `pageshow` for a bfcache Back. `isFleetTest` keeps its exemption.
- **Summary** (`renderSummary`, rebuilt each time Step 4 opens): Game Options card (background
  thumbnail, name, one chip per active rule — the lobby's "Options Selected" itemised; "No
  optional rules" when none), Scenario card (`scenarioCard.render(…, {plain:true})` — the renderer
  Stage 0 built for exactly this), Teams & Map card (a second canvas painted by the same
  `paintMap()`, template/size/terrain line, one block per team in the §11.4 colours). Each card
  has Edit → its step.
- On a step change: short entry animation (off under reduced motion), the step bar scrolled into
  view, focus to the step's heading (`tabindex="-1"`, no ring) so a screen reader announces it.
- Mobile: step bar shows "1 / 4" + short names; nav labels shorten; Summary cards stack at 900px.
  Checked at 390px in an iframe (§12.1 trap 8) on steps 1, 3 and 4.

**Moons use the count combo** (user addition 1): the three moon rows are `cgCountCombo`s like
Asteroids/Dust/Meteor Swarms — typed, wheel, ▾ presets. Ranges UNCHANGED (0-5 / 0-4 / 0-2); the
presets are every count ("None (0)", "1", "2"…) — `cgCountCombo` now prints a bare number without
the "(n)" gloss and skips presets above the row's max.

**Maps with Terrain** (user addition 2) — Map Template entries with PRE-PLACED terrain, the same
every game:

- **Server:** new `TerrainLayoutRule` (rules key `terrainLayout: {name, units:[{type,q,r,h}]}`).
  `type` is a short key (`asteroidS/M/L`, `asteroid2`, `asteroid3`, `moonS/M/L`, `dust`,
  `meteors`) mapped to the phpclass in `TerrainLayoutRule::$types` — the rules blob is published to
  every client, the class is the server's business. Checked for SHAPE only (known type, ints,
  facing wrapped 0-5, ≤150 units, name ≤60 chars); balance/overlap is the creator's choice, like a
  deployment zone. `getUnitsOnMap($gamespace)` drops units whose centre is off the map (same bounds
  as `ReinforcementEntry.onMap`), and BOTH halves of BuyingGamePhase read through it, so the unit
  created and the hex it is placed on always come from the same list.
- `BuyingGamePhase::process` (slot 1): `addTerrainLayout()` creates one unit per entry FIRST, named
  in the same series as the random terrain ("Asteroids #n", "Moon #n", "Dust #n", "Meteor Swarm
  #n"); the random adders gained a name offset so they number on after it — no duplicate names.
- `BuyingGamePhase::advance`: layout units are placed BEFORE the random pass — each entry takes the
  lowest-id unplaced unit of its phpclass (units of a class are interchangeable, and any extra of
  the same class from a random count is left for the random pass). Their hexes and moon centres
  are registered exactly as a random unit's would be, so random terrain on top keeps its usual
  spacing from them. The footprint maths moved into `getTerrainHexes()` (used by both passes).
- **Random counts still work on top** of a terrain map; Step 1's Terrain card says so while a
  terrain map is picked. Typing a Width/Height turns the template to Custom and **drops** the
  layout (it was laid out for the template's size).
- Lobby: the Options Selected line gains "Terrain Map: <name> (N features)" (name HTML-escaped —
  it came from a POST); the games list's TERRAIN chip counts layouts too. The lobby MAP PREVIEW
  markers are Stage 4's job — the data is already in `gamedata.rules.terrainLayout`.
- **Client:** the maps live in `createGame.mapData` as `{base, name, blurb, terrain}` entries,
  resolved onto their base template by `getMapConfig()`. Preview (and the Summary copy) draws white
  markers of real size — a disc of radius Huge+½ for a moon, a dot per hex for the rest, irregular
  asteroids turned by `h` with `createGame.rotatedHex` (a port of `Mathlib::getRotatedHex`,
  **verified identical to the server on all 32 irregular units**). Dust/Meteor markers are fainter:
  `TERRAIN_ALPHA` 0.9 / `FIELD_TERRAIN_ALPHA` 0.45, legend swatches follow them.
- **Seven maps**, one per size family: Close Quarters (Small 30x24), Asteroid Belt / Twin Moons
  (Standard 42x30), Crossroads (Four Teams 42x30), Fractured Front (2v2 42x40), Shattered Moon /
  Meteor Storm (Large 60x40). Each is authored as one quadrant/half and mirrored about the map's
  TRUE centre — half a hex left of hex 0,0 on an even width (the map box is drawn centred there, and
  the standard zones at -19/+18 are symmetric about it): mirror = `(q,r) → (-1-q+(r&1), r)`,
  flip = `(q,-r)`, and an irregular asteroid's mirrored facing is found by matching footprints.
  Every map keeps two hexes clear of each deployment zone, never overlaps, keeps non-field terrain
  one hex apart and moons 7 apart. Crossroads and Fractured Front are added to
  `forbiddenLadderMaps` (not two teams of one slot); the other five are allowed in ladder games.

**In-game Dust / Meteor hexes** (user addition 3): `BallisticIconContainer.generateTerrainHexes`
now draws them with the same white `hexWhite` region as asteroids and moons, at
**`FIELD_TERRAIN_DIM` = 0.5** (a multiplier on the region's fill/rim opacity — the user asked for a
value to tune by hand). Matched by phpclass (`spawnDustField` / `spawnMeteoroid`) as well as the old
Enormous + size-class-5 test, so they are drawn whether or not a blueprint carries those fields —
which also covers the Triad Asteroid Salvo's spawned dust/meteors.

**Traps found:**

1. **jQuery 4 has no `$.trim`** (creategame.php loads jQuery 4.0) — it threw on the first Next.
   Use `String(x || "").trim()`.
2. **jQuery 4 runs ready-handlers ASYNCHRONOUSLY.** A test driver on `window.load` can run BEFORE
   createGame's ready handler, whose `initWizard()` then resets to Step 1 — looks like a broken
   step jump. Drive tests from `jQuery(fn)` + `setTimeout`.
3. **gamesNew.css's `* { font-family: Arial }`** hits every nested span: the step bar's and the
   buttons' inner spans need their face named (or `inherit`) explicitly.
4. **Git Bash `sed -i` and `grep -c $'\r$'` both lie about CRLF** — sed strips CRs, grep hides
   them. Check line endings with a byte count in node or `git ls-files --eol`.

**Verified:** real `php -l` on all six server files; creategame.php CLI-rendered with no PHP
errors; a scratch run of the REAL path — `GameRules` round trip → `addTerrainLayout` + all three
random adders with offsets → `BuyingGamePhase::advance()` against a stub DBManager — for all seven
maps with random terrain on top: every layout unit on its exact hex with its facing, zero shared
hexes, zero duplicate names; malformed layouts dropped or trimmed as intended. Wizard driven in
headless Chrome (validation, forward-jump stop, Back, terrain map set/posted/dropped on a typed
size, moon clamp 9→5, submit refused before Step 4); screenshots at desktop and at 390px of steps
1, 3 (Asteroid Belt, Meteor Storm) and 4 (Shattered Moon, Crossroads) plus the error state.
**Not verified in a live game:** the in-game dust/meteor hexes and a real terrain-map game start —
create a game on a terrain map, buy, and check the terrain lands where the preview showed.

**Review refinements (user, 2026-09-24):**

- **Scenario "?" = the mockup's floating window**, not an inline row: the "?" sits right after the
  label; a click/tap opens a purple-bordered bubble (`.cg-help-bubble`) just under it with an arrow
  pointing at the "?". `createGame.positionHelp()` measures from the card's PADDING box (what
  `left`/`top` resolve against) and slides the bubble left to stay inside the card — on a phone the
  "?" of "Custom Factions / Units" sits nearer the edge than the bubble is wide. One open at a time;
  a click elsewhere or Escape closes it (Escape returns focus to the "?"); a step change closes it.
  No hover-to-open — deliberate, so mouse and touch behave the same.
- **Additional Info** is one grid cell like the rest, which in FIELDS order puts it under Map
  Borders beside Victory Conditions (the mockup). FIELDS' `wide` is untouched — it still spans the
  read-only fact grid (Summary, lobby).
- **Slot numbers on one line:** a flex row of fixed 3.75rem centred inputs, each field as wide as
  that or its label (~422px for all six). Too narrow for that — a phone, or the two-column layout
  below ~1200px viewport — and it becomes two rows of three in EQUAL columns so the numbers still
  line up, via `@container (max-width: 430px)` on `.slot` (the page's first container query:
  it has to follow the slot's width, which a viewport query cannot know).
- **Confirm step = two columns:** Game Options above Scenario Description (`.cg-sum-col`) | Teams &
  Map. Stacks in that order under 900px.

Verified in headless Chrome: the help windows' open/switch/toggle/inside-click/outside-click/
Escape+focus/step-change states, arrow landing on the "?" centre; Additional Info's position; slot
row one line at 1440/1280, 3+3 aligned at 1024/390, with Unlimited Points too; screenshots of steps
2-4 at desktop and 390px (iframe). `php -l` clean.

**Second review round (user, 2026-09-24):**

- **Dust / Meteor Swarms are now their own terrain classes** (user's change): `DustField` and
  `MeteorSwarm` in `ships/terrain/`, mirroring the Triad's `spawnDustField` / `spawnMeteoroid`,
  which stay for the Asteroid Salvo. Everything that names a class had to follow:
  `TerrainLayoutRule::$types`, BuyingGamePhase's placement weight, `BallisticIconContainer`'s
  `FIELD_TERRAIN_CLASSES` (all four listed). ⚠️ `RammingAttack`'s terrain gate now reads
  `isDustField` / `isMeteoroid`, which only those classes declare, and warnings throw here — so
  `!empty()`, or a jump gate / shipyard / jump point (non-Enormous terrain, auto-given a
  RammingAttack) fatals the pre-firing step. The "Class MeteoriteSwarm not found" lobby fatal was
  a game created while the class's `phpclass` string still read "MeteoriteSwarm" (the container's
  rsync copy had not picked up the rename); that game's rows were repaired by hand.
- **Terrain names are unnumbered now** (user): the random adders and `addTerrainLayout` both name
  a unit by its class alone ("Asteroids", "Asteroid" for the irregulars, "Small Moon" / "Moon" /
  "Large Moon", "Dust Field", "Meteor Swarm") - so the "zero duplicate names" check above no longer
  applies. The numbered versions are kept commented out, and the name offsets are still passed, so
  numbering can be switched back on without touching `process()`.
- **Map Preview restyled to the mockup's Teams & Map artboard** (`createGame.paintMap`, both Step 3
  and the Summary): the map is a dark well (`--fv-well`) under a faint grid every few hexes (~24px,
  anchored on brighter centre lines); each zone a 0.14 wash of its team colour with a DASHED edge
  on its inner sides only (a side on the rim is left to the rim); a mono "TEAM n" label in the
  zone's corner nearest the rim; terrain grey `#5a6a76` discs, those ≥ 6px with the mockup's halo
  drawn INSIDE the true footprint; dust/meteors fainter with no halo. The canvas now takes the
  map's proportions (height/width clamped 0.45-1.0, letterboxed beyond) so the map fills the
  frame, and is drawn at 2x its logical 545px width for sharp lines once CSS scales it. The old
  fixed 6px x-nudge is now exactly half a hex (`toX`), the true-centre offset it approximated.
  The legend keeps team names (not "Your / Other Team's Deployment" - nothing here is per-viewer).

import * as React from "react";
import styled from "styled-components";
import theme from "../styled/theme";
import buildComplement from "../helpers/buildComplement";

/*Lobby datasheet (SHIPWINDOW_REDESIGN_PLAN.md Stage 3b) — the always-visible
  replacement for the legacy window's overlap-prone `.notes` column. Two pieces:

  - ManoeuvreStats: the manoeuvre block (TC/TD, Acc/Pivot/Roll, Profile, Ini),
    rendered in the window's ctrl column directly beneath the Hit Chart button
    (user layout decision 2026-07-17: stats left, complement/notes right).
  - ShipNotesPanel (default export): hangar capacity (fighters + default
    shuttles), ship notes, metadata (limited/variant/ISD/custom flags) and
    purchased enhancements. In the grid window it occupies the `ew` grid area —
    the exact place the Electronic Warfare panel has in game.php (feedback
    2026-07-17) — so it reads as the same chrome and can never be obscured by
    ship icon elements. Flight windows render it as a side rail, compact
    windows (mines) as a full-width block.

  Both read the ship object AFTER lobbyEnhancements.apply() has mutated it, so
  enhancement-driven stat changes (Elite Crew initiative, Improved Thrust, ...)
  show live. Rendering is read-only — enhancement mutations stay in
  lobbyEnhancements (blueprints share static data, arch_client_system_shared_reference).*/

/*Transparent stack container - each Block inside is its own dotted-bordered panel
  (feedback 2026-07-17 round 2: Notes gets its own bordered box with a gap below
  Hangar Capacity, exactly like Ship Stats sits below the Hit Chart button; the 4px
  gap matches ControlsArea's).
  $grid: occupies the `ew` grid cell (150px). $full: full-width (compact mine
  windows). Default: 200px side rail (flights).*/
const Rail = styled.div`
    box-sizing: border-box;
    display: flex;
    flex-direction: column;
    gap: 4px;
    font-size: 10px;
    line-height: 1.4;
    color: ${theme.colors.textAccent};
    ${props => {
        if (props.$grid) {
            return `
    grid-area: ew;
    justify-self: center;
    align-self: start;
    position: relative; /*above the watermark + ship-click underlay*/
    z-index: 1;
    width: 150px;`;
        }
        if (props.$full) {
            return `
    width: 100%;
    padding: 4px;`;
        }
        return `
    flex: 0 0 auto;
    width: 200px;
    padding: 4px;`;
    }}
`;

const Block = styled.div`
    background-color: ${theme.colors.panelBgGlass};
    /*$gold: the Enhancements block matches its bronze header border (user request
      2026-07-18) so the whole panel reads as the gold-accented one*/
    border: 1px dotted ${props => props.$gold ? '#8a6d3b' : theme.colors.line};
    padding: 0 8px 3px;
`;

const BlockTitle = styled.div`
    font-size: 8px;
    letter-spacing: 0.8px;
    text-transform: uppercase;
    white-space: nowrap;
    overflow: hidden;
    color: ${props => props.$gold ? '#e8cf93' : theme.colors.text};
    /*shaded header-bar blue (same as the hit chart section names) so the block
      headers stand out against the glass panels (feedback 2026-07-17).
      $gold: muted bronze variant for the Enhancements blocks (user request
      2026-07-18) - stands out from the blue chrome without going garish.*/
    background-color: ${props => props.$gold ? 'rgba(169, 128, 56, 0.30)' : 'rgba(73, 103, 145, 0.25)'};
    border-bottom: 1px solid ${props => props.$gold ? '#8a6d3b' : theme.colors.line};
    margin: 0 -8px 3px;
    padding: 2px 8px;
`;

const Row = styled.div`
    padding: 1px 0;
`;

const CustomFlag = styled.div`
    padding: 1px 0;
    font-weight: bold;
    font-style: italic;
    color: ${theme.colors.warning};
`;

/*stat rows read like the Notes lines (feedback round 3): 10px, sentence-case
  labels in the notes blue, values in white*/
const StatRow = styled.div`
    display: flex;
    align-items: baseline;
    justify-content: space-between;
    gap: 4px;
    padding-top: 1px;
`;

const StatLabel = styled.span`
    font-size: 10px;
    color: ${theme.colors.textAccent};
    white-space: nowrap;
    margin-left: 5px;    
`;

const StatValue = styled.span`
    font-family: ${theme.fonts.mono};
    font-size: 10px;
    color: ${theme.colors.text};
    margin-right: 5px;
`;

/*Manoeuvre block, styled as a sibling of the ctrl buttons and the datasheet panels
  opposite - same 150px width (feedback 2026-07-17: chrome columns symmetric).*/
const StatsPanel = styled.div`
    width: 150px;
    box-sizing: border-box;
    background-color: ${theme.colors.panelBgGlass};
    border: 1px dotted ${theme.colors.line};
    padding: 2px 4px 3px;
`;

const StatsTitle = styled.div`
    font-size: 8px;
    letter-spacing: 0.5px;
    text-transform: uppercase;
    white-space: nowrap;
    overflow: hidden;
    color: ${theme.colors.text};
    /*shaded header-bar blue, matching BlockTitle / the ctrl buttons*/
    background-color: rgba(73, 103, 145, 0.25);
    margin: -2px -4px 2px;
    padding: 2px 4px;
    border-bottom: 1px solid ${theme.colors.line};
`;

const AgileRow = styled.div`
    text-align: center;
    font-size: 10px;
    color: ${theme.colors.warning};
    padding-top: 2px;
`;

/*Enhancements as a standalone bottom-right grid panel (feedback round 3): keeping it
  out of the `ew` datasheet stack stops long enhancement lists inflating grid row 1
  and pushing Forward away from Primary.*/
const EnhArea = styled.div`
    grid-area: enh;
    justify-self: center;
    align-self: start; /*top of its cell - starts directly below the Starboard section (feedback round 5)*/
    position: relative; /*above the watermark + ship-click underlay*/
    z-index: 1;
    width: 150px;
    box-sizing: border-box;
    font-size: 10px;
    line-height: 1.4;
    color: ${theme.colors.textAccent};
`;

//legacy parity: TC/TD to two decimals, Profile as defense * 5, Ini raw
const fix2 = (value) => (typeof value === 'number' ? value.toFixed(2) : value);

//Bases don't manoeuvre: only their Profile is relevant (feedback 2026-07-17),
//so the movement rows are dropped for them.
export const ManoeuvreStats = ({ ship }) => {
    const mobile = !ship.base;
    return (
        <StatsPanel>
            <StatsTitle>Ship Stats</StatsTitle>
            {mobile && <StatRow><StatLabel>Turn cost</StatLabel><StatValue>{fix2(ship.turncost)}</StatValue></StatRow>}
            {mobile && <StatRow><StatLabel>Turn delay</StatLabel><StatValue>{fix2(ship.turndelaycost)}</StatValue></StatRow>}
            {mobile && <StatRow><StatLabel>Accel/decel</StatLabel><StatValue>{ship.accelcost}</StatValue></StatRow>}
            {mobile && <StatRow><StatLabel>Pivot</StatLabel><StatValue>{ship.pivotcost}</StatValue></StatRow>}
            {mobile && <StatRow><StatLabel>Roll</StatLabel><StatValue>{ship.rollcost}</StatValue></StatRow>}
            <StatRow><StatLabel>Profile F/S</StatLabel><StatValue>{ship.forwardDefense * 5}/{ship.sideDefense * 5}</StatValue></StatRow>
            {mobile && <StatRow><StatLabel>Initiative</StatLabel><StatValue>{ship.iniativebonus}</StatValue></StatRow>}
            {Boolean(ship.agile) && <AgileRow>Agile ship</AgileRow>}
        </StatsPanel>
    );
};

//standalone bottom-right Enhancements panel for the lobby grid window; the rail
//keeps its inline block for flight/mine variants (no grid there)
export const EnhancementsPanel = ({ ship }) => {
    const enhLines = splitHtmlLines(ship.enhancementTooltip);
    if (enhLines.length === 0) return null;
    return (
        <EnhArea>
            <Block $gold>
                <BlockTitle $gold>Enhancements</BlockTitle>
                {enhLines.map((line, i) => <Row key={`enh-${i}`}>{line}</Row>)}
            </Block>
        </EnhArea>
    );
};

const splitHtmlLines = (text) =>
    (text || '')
        .split(/<br\s*\/?>/i)
        .map(line => line.replace(/<[^>]*>/g, '').replace(/&nbsp;/g, ' ').trim())
        .filter(Boolean);

class ShipNotesPanel extends React.Component {

    render() {
        //hideEnhancements: the lobby grid window shows enhancements in the standalone
        //bottom-right EnhancementsPanel instead (feedback round 3)
        const { ship, full, grid, hideEnhancements } = this.props;

        const complement = buildComplement(ship);
        const noteLines = splitHtmlLines(ship.notes);
        const enhLines = hideEnhancements ? [] : splitHtmlLines(ship.enhancementTooltip);

        const meta = [];
        if (ship.limited && ship.limited != 0) meta.push("Limited: " + ship.limited + "%");
        if (ship.variantOf) {
            const occ = ship.occurence
                ? ship.occurence.charAt(0).toUpperCase() + ship.occurence.slice(1) + " "
                : "";
            meta.push(occ + "variant of " + ship.variantOf);
        }
        if (ship.isd) meta.push("In-Service (ISD): " + ship.isd);

        let customFlag = null;
        if (ship.unofficial === 'S') customFlag = "Semi-Custom";
        else if (ship.unofficial) customFlag = "Custom";

        const hasNotesBlock = noteLines.length > 0 || meta.length > 0 || customFlag;

        return (
            <Rail $full={full} $grid={grid}>
                {ship.flight && (
                    <Block>
                        <BlockTitle>Flight Stats</BlockTitle>
                        <StatRow><StatLabel>Armor F/S/A</StatLabel><StatValue>{shipManager.systems.getFlightArmour(ship)}</StatValue></StatRow>
                        <StatRow><StatLabel>Off. bonus</StatLabel><StatValue>{ship.offensivebonus * 5}</StatValue></StatRow>
                        <StatRow><StatLabel>Thrust</StatLabel><StatValue>{ship.freethrust}</StatValue></StatRow>
                        <StatRow><StatLabel>Initiative</StatLabel><StatValue>{ship.iniativebonus}</StatValue></StatRow>
                    </Block>
                )}

                {complement.length > 0 && (
                    <Block>
                        <BlockTitle>Hangar Capacity</BlockTitle>
                        {complement.map((line, i) => <Row key={`comp-${i}`}>{line}</Row>)}
                    </Block>
                )}

                {hasNotesBlock && (
                    <Block>
                        <BlockTitle>Notes</BlockTitle>
                        {noteLines.map((line, i) => <Row key={`note-${i}`}>{line}</Row>)}
                        {meta.map((line, i) => <Row key={`meta-${i}`}>{line}</Row>)}
                        {customFlag && <CustomFlag>{customFlag}</CustomFlag>}
                    </Block>
                )}

                {enhLines.length > 0 && (
                    <Block $gold>
                        <BlockTitle $gold>Enhancements</BlockTitle>
                        {enhLines.map((line, i) => <Row key={`enh-${i}`}>{line}</Row>)}
                    </Block>
                )}
            </Rail>
        );
    }
}

export default ShipNotesPanel;

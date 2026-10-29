import React, { Component } from 'react';
import styled from 'styled-components';

/* OFFICERS_PLAN.md STAGE 34 - THE WAR LEADER MENU (user request 2026-10-29).
 *
 * In Initial Orders, the C&C an Expert War Leader serves in shows this panel. "Once only once during the scenario, the war leader can do each
 * of the following, but can only do one on any turn": the two given now - TAUNT and ANTICIPATE - are buttons, one at most, and a given one
 * may be taken back until the commit; the two of the Firing phase - WRATH and CENTER OF GLORY, from the War Leader icon there - are listed
 * to say whether they are still his. Each row says when it was used.
 *
 * The order waits on the C&C (officers.setWarLeaderOrder) until the commit carries it (CnC.doIndividualNotesTransfer); the server checks it
 * against its own rule before writing it (Officers::saveWarLeaderOrder). SystemInfoButtons gates it on officers.canWarLeaderOrder.
 *
 * In the shape of the Officers menu beside it (OfficersMenu.js), but in Narn red and gold rather than the Officers blue (user, game 4523:
 * "less generic blue") - the gold is the War Leader icon's yellow (UI.shipMovement.warLeaderColour).
 */

/* Shaped like a SECTION_INK entry (menuControls.js), plus the panel's own fill and text. Solid colours, never alphas: the tooltip
   it sits in is translucent already (the compounding note on SECTION_INK). */
const INK = {
    panel: 'linear-gradient(180deg, #3b0d13, #25070b)',
    rail: '#8e2a31',              //frame and the rules between rows - a dull blood red
    bar: '#e0b440',               //the header's stripe, a given order's button and every button on hover - gold
    wash: 'linear-gradient(180deg, #701a22, #4b0f16)',
    title: '#f2c230',             //UI.shipMovement.warLeaderColour
    btnBg: '#4c1118',
    btnLine: '#b8892f',           //a button's border - the gold, dimmed
    btnText: '#f5d77a',
    btnOnText: '#2a0709',         //on the gold
    text: '#fff4ea',
    dim: '#7f5c58',               //an ability once used: its name...
    noteDim: '#6c4d4a',           //...and its note - all but its "Used, Turn N" (user, 2026-10-29)
    note: '#dcbcae',
    state: '#cfa65c',             //"used, turn N" / "Firing phase" / "one a turn"
};

//What each of his four does - the Note under its row
const ABILITIES = [
    { id: 'taunt', name: 'Taunt', order: true,
        note: 'This turn every enemy gains +5% to hit his ship and -5% to hit the other ships of his fleet.' },
    { id: 'anticipate', name: 'Anticipate', order: true,
        note: 'This turn his ship gains +60 initiative and may hold back all its EW to the Firing phase.' },
    { id: 'wrath', name: 'Wrath',
        note: 'Firing phase (War Leader icon): one enemy unit - his fleet\'s ships +5% to hit it this turn.' },
    { id: 'glory', name: 'Center of Glory',
        note: 'Firing phase (War Leader icon): one unit of his fleet - +10% to hit with all its weapons this turn.' }
];

/* Asks for 190px and no more, then stretches to the menu: `contain: inline-size` keeps its text out of the
   shrink-to-fit tooltip's width sum (JumpEngineMenu's note on WIDTH). */
const Panel = styled.div`
    display: flex;
    flex-direction: column;
    width: 100%;
    min-width: 190px;
    contain: inline-size;
    background: ${INK.panel};
    border: 1px solid ${INK.rail};
`;

const Header = styled.div`
    padding: 3px;
    background: ${INK.wash};
    border-left: 3px solid ${INK.bar};
    border-bottom: 1px solid ${INK.btnLine};
    color: ${INK.title};
    text-align: center;
    font-size: 12px;
    font-weight: bold;
    letter-spacing: 0.5px;
    margin-bottom: 2px;
`;

const Row = styled.div`
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 6px;
    padding: 3px 8px;
    font-size: 11px;
    color: ${props => props.$dim ? INK.dim : INK.text};
`;

const Note = styled.div`
    padding: 0 8px 5px 8px;
    font-size: 10px;
    line-height: 1.35;
    color: ${props => props.$dim ? INK.noteDim : INK.note};
    border-bottom: 1px solid ${INK.rail};

    &:last-child {
        border-bottom: none;
    }
`;

const State = styled.span`
    font-size: 10px;
    color: ${INK.state};
    white-space: nowrap;
`;

const Button = styled.div`
    padding: 0 6px;
    height: 18px;
    box-sizing: border-box;
    background: ${props => props.$on ? INK.bar : INK.btnBg};
    border: 1px solid ${INK.btnLine};
    color: ${props => props.$on ? INK.btnOnText : INK.btnText};
    cursor: pointer;
    display: flex;
    align-items: center;
    font-size: 10px;
    white-space: nowrap;
    user-select: none;

    &:hover {
        background: ${INK.bar};
        color: ${INK.btnOnText};
    }
`;

class WarLeaderMenu extends Component {

    order(e, ability) {
        e.stopPropagation(); e.preventDefault();
        const { ship, system } = this.props;
        if (officers.setWarLeaderOrder(ship, ability)) officers.repaint(ship, system);
    }

    //the row's right-hand side: when it was used, the order given (to take back), a button to give it, or why it cannot be given now
    renderControl(ability, entry, ordered) {
        const used = officers.spentTurn(entry, ability.id);
        if (used !== null) return <State>{`Used, Turn ${used}`}</State>;
        if (!ability.order) return <State>Firing phase</State>;
        if (ordered === ability.id) {
            return <Button $on title="Take the order back" onClick={e => this.order(e, null)}>{`${ability.name}!`}</Button>;
        }
        if (ordered) return <State>one a turn</State>;
        return <Button title={`${ability.name} this turn - once per battle`} onClick={e => this.order(e, ability.id)}>{ability.name}</Button>;
    }

    render() {
        const { ship } = this.props;
        const entry = officers.warLeaderEntry(ship);
        if (!entry) return null;
        const ordered = officers.warLeaderOrder(ship);
        return (
            <Panel>
                <Header>Expert War Leader</Header>
                {ABILITIES.map(ability => {
                    const used = officers.spentTurn(entry, ability.id) !== null;
                    return (
                        <React.Fragment key={ability.id}>
                            <Row $dim={used}>
                                <span>{ability.name}</span>
                                {this.renderControl(ability, entry, ordered)}
                            </Row>
                            <Note $dim={used}>{ability.note}</Note>
                        </React.Fragment>
                    );
                })}
            </Panel>
        );
    }
}

export default WarLeaderMenu;

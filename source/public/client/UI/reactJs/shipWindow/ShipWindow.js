import * as React from "react";
import styled from "styled-components"

import { Clickable } from "../styled";
import theme from "../styled/theme";
import ShipSection from "./ShipSection";
import ShipWindowEw from "./ShipWindowEw";
import FighterList from "./FighterList";
import HitChartPanel from "./HitChartPanel";
import ShipInfo from "../system/ShipInfo";

/*"Digital SCS" ship window (SHIPWINDOW_REDESIGN_PLAN.md Stage 1): sections arranged
  geographically on a CSS grid like the paper Ship Control Sheets - Forward top, Port
  left, Starboard right, Aft bottom, Primary centre - over a monochrome watermark of
  the ship's own art. Six-sided ships and big bases get their four quarter sections at
  side-column height with Primary spanning the middle rows (the layout fix the old
  flex rows could never do: Primary only centres between Forward and Aft if it shares
  one grid band with ALL side sections).

  The grid's top row is chrome: "ctrl fwd ew" - Hit Chart / Notes buttons top-left
  (click opens a popup, clicking anywhere outside closes it), Forward section centre,
  vertical EW panel top-right (2026-07-16 feedback round; replaces both the old
  hover-pin ⊕ in the header and the footer EW strip).

  Rolled ships (user request 2026-07-16): port/starboard grid columns swap while the
  ship is rolled - the window then matches what the enemy actually faces - with an
  amber ROLLED banner at the bottom of the window confirming the mirroring. Section
  headers keep their true names (the port section stays "PORT", it is just drawn on
  the right).*/

const ShipWindowContainer = styled.div`
    display: flex;
    flex-direction: column;
    position: absolute;
    ${props => {
        if (props.$isMyTeam) {
            return "left: 50px; \n top: 50px;"
        } else {
            return "right: 50px; \n top: 50px;"
        }
    }}
    width: ${props => {
        if (props.$variant === 'terrain') return '250px';
        if (props.$variant === 'flight') return 'auto';
        return 'fit-content';
    }};
    max-width: ${props => props.$variant === 'flight' ? '400px' : 'unset'};
    height: auto;
    border: 1px solid ${theme.colors.line};
    background-color: ${theme.colors.windowBg};
    opacity: 0.95;
    z-index: 10001;
    overflow: visible; /*lets the Hit Chart / Notes popup extend past the window; the watermark is clipped by the body instead*/
    box-shadow: 5px 5px 10px black;
    font-size: 10px;
    color: ${theme.colors.text};
    font-family: ${theme.fonts.body};

    /* Prevent text selection and callouts on mobile */
    -webkit-user-select: none;
    -webkit-touch-callout: none;
    -moz-user-select: none;
    -ms-user-select: none;
    user-select: none;

    @media (max-width: 1024px) {
        ${props => {
        if (props.$isMyTeam) {
            return "left: 0; \n top: 0; \n right: unset;"
        } else {
            return "right: 0; \n top: 0; \n left: unset;"
        }
    }}
        max-width: 100vw;
        max-height: 100vh;
        /*auto, not scroll: scroll pins a permanent (usually inert) scrollbar to
          the window on classic-scrollbar platforms even when nothing overflows.
          When it does engage, it wears the site-standard scrollbar (same as
          PopupHolder / #gameinfo / the log panel).*/
        overflow-y: auto;

        scrollbar-width: thin;
        scrollbar-color: #3c5574 #0d1620;

        &::-webkit-scrollbar {
            width: 10px;
        }
        &::-webkit-scrollbar-track {
            background: #0d1620;
        }
        &::-webkit-scrollbar-thumb {
            background: #3c5574;
        }
        &::-webkit-scrollbar-thumb:hover {
            background: #5a7ea8;
        }
    }
`;

const Header = styled.div`
    position: relative;
    background-color: ${theme.colors.panelBg};
    border-bottom: 1px solid ${theme.colors.line};
    height: 26px;
    display: flex;
    align-items: baseline; /*name + class share a text baseline (different font sizes)*/
    gap: 6px;
    padding: 0 26px 0 5px; /*right padding clears the ✕ button*/
    width: 100%;
    box-sizing: border-box;
    flex-shrink: 0;
    cursor: move;
`;

const HeaderName = styled.span`
    font-size: 11px;
    line-height: 26px; /*centres the shared baseline within the 26px header bar*/
    text-transform: uppercase;
    letter-spacing: 0.5px;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
    min-width: 0;
    flex-shrink: 1; /*long flight names ellipsise instead of pushing past the ✕*/
    color: ${props => props.$tint || theme.colors.text};
`;

const HeaderClass = styled.span`
    font-size: 9px;
    letter-spacing: 0.5px;
    text-transform: uppercase;
    color: ${theme.colors.textAccent};
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
    min-width: 0; /*allow flex shrink so the ellipsis can engage*/
    flex-shrink: 3; /*the class gives way before the ship name does*/
`;

const CloseButton = styled.div`
    width: 25px;
    height: 25px;
    position: absolute;
    right: 0;
    top: 0;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 22px;
    padding-left: 5px;
    margin-top: -2px;
    color: ${theme.colors.line};
    ${Clickable}
`;

/*Hit Chart / Notes button stack, top-left of the window body (the empty corner cell
  of the SCS grid), centred within its column. Click opens the matching popup;
  clicking anywhere outside closes it (document pointerdown listener). Compact
  windows (mines/terrain) render it as a full-width row above the sections instead
  ($row).*/
const ControlsArea = styled.div`
    grid-area: ctrl;
    justify-self: center;
    align-self: start;
    position: relative; /*above the watermark + ship-click underlay*/
    z-index: 2;
    display: flex;
    flex-direction: column;
    ${props => props.$compact ? 'width: 100%; align-items: center; margin-bottom: 5px;' : 'align-items: stretch;'}
    gap: 4px;
`;

const CtrlButton = styled.div`
    display: flex;
    align-items: center;
    gap: 4px;
    padding: 2px 6px 2px 4px;
    box-sizing: border-box;
    min-width: 120px; /*match the EW panel opposite; also equalises the two buttons*/
    border: 1px solid ${theme.colors.line};
    background-color: ${props => props.$active ? 'rgba(198, 226, 255, 0.12)' : theme.colors.panelBg};
    color: ${props => props.$active ? theme.colors.text : theme.colors.textAccent};
    font-size: 8px;
    letter-spacing: 0.8px;
    text-transform: uppercase;
    white-space: nowrap;
    ${Clickable}
`;

const CtrlIcon = styled.span`
    font-size: 12px;
    line-height: 1;
    color: inherit;
`;

/*Shared popup box for the Hit Chart / Notes panels: anchored below the control
  buttons, spanning the window body. A direct child of the window container so it
  can extend BELOW the window (overflow: visible); capped at 70vh with the site's
  standard scrollbar (matches #gameinfo / the log panel).*/
const PopupHolder = styled.div`
    position: absolute;
    top: ${props => props.$top || 78}px;
    left: 6px;
    /*$fit (Notes): size to content instead of spanning the window*/
    ${props => props.$fit
        ? 'right: auto; width: fit-content; max-width: calc(100% - 12px);'
        : 'right: 6px;'}
    z-index: 20;
    max-height: 70vh;
    overflow-y: auto;
    box-sizing: border-box;
    background-color: ${theme.colors.panelBg};
    border: 1px solid ${theme.colors.line};
    box-shadow: 4px 4px 10px rgba(0, 0, 0, 0.7);
    padding: 5px 5px 10px; /*extra bottom padding so the last chart rows never look clipped*/
    cursor: default;

    scrollbar-width: thin;
    scrollbar-color: #3c5574 #0d1620;

    &::-webkit-scrollbar {
        width: 10px;
    }
    &::-webkit-scrollbar-track {
        background: #0d1620;
    }
    &::-webkit-scrollbar-thumb {
        background: #3c5574;
    }
    &::-webkit-scrollbar-thumb:hover {
        background: #5a7ea8;
    }
`;

/*The geographic grid. The side tracks are 1fr each so they always resolve to the SAME
  width (the wider of the two) - this keeps the centre column, and therefore the
  sections, aligned with the window's midline and the watermark even when the chrome
  row is asymmetric (small button stack left, 120px EW panel right). Section widths
  (120/156px, set in ShipSection) keep icon rows at the 3-wide / 4-wide lengths the
  symmetric ordering expects.*/
const SectionGrid = styled.div`
    position: relative;
    display: grid;
    grid-template-columns: 1fr auto 1fr;
    grid-template-areas: ${props => props.$areas};
    justify-content: center;
    gap: 8px;
    padding: 8px;
    box-sizing: border-box;
    width: 100%;
    overflow: hidden; /*clips the watermark now that the window itself is overflow: visible*/
`;

/*Monochrome hull art behind the sections, nose-up like the old thumbnail. Purely
  decorative: pointer-events pass through to the ship hit underlay below. Sized as a
  square from the body's HEIGHT (clamped by width) so short windows never crop the art
  vertically - a centre-cropped slice is what made satellites look "stretched".*/
const WatermarkLayer = styled.div`
    position: absolute;
    top: 50%;
    left: 50%;
    height: 80%;
    width: auto;
    aspect-ratio: 1 / 1;
    max-width: 80%;
    max-height: 380px;
    transform: translate(-50%, -50%) rotate(-90deg);
    background-image: ${props => `url(${props.$img})`};
    background-size: contain;
    background-repeat: no-repeat;
    background-position: center;
    filter: grayscale(1) brightness(1.9);
    opacity: 0.55;
    pointer-events: none;
    z-index: 0;
`;

/*Ship-level click surface (replaces the old corner thumbnail): covers the grid
  background - the gaps between sections and the watermark - while the sections
  themselves sit above it on z-index 1, so system icons keep their own events.
  Hover deliberately does nothing (2026-07-16 feedback): the old ship tooltip's
  content now lives in the Hit Chart / Notes popups.*/
const ShipHitArea = styled.div`
    position: absolute;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    z-index: 0;
`;

const RolledBanner = styled.div`
    width: 100%;
    box-sizing: border-box;
    padding: 2px 6px 3px;
    text-align: center;
    font-size: 9px;
    letter-spacing: 2px;
    text-transform: uppercase;
    color: ${theme.colors.warning};
    background-color: rgba(225, 176, 0, 0.10);
    border-top: 1px solid ${theme.colors.line};
    flex-shrink: 0;
`;

const CompactBody = styled.div`
    position: relative; /*watermark anchor for the compact variant*/
    width: 100%;
    min-height: 120px; /*room for the watermark art in sparse windows (mines)*/
    display: flex;
    flex-wrap: wrap;
    justify-content: space-around;
    align-items: flex-start;
    padding: 2px;
    box-sizing: border-box;
    overflow: hidden; /*clips the watermark now that the window itself is overflow: visible*/
`;

const UnknownSystemIcon = styled.div`
    position: relative;
    box-sizing: border-box;
    width: 50px;
    height: 50px;
    margin: auto;
    border: 1px solid ${theme.colors.line};
    background-color: black;
    color: #e3c182;
    font-family: ${theme.fonts.body};
    font-size: 24px;
    display: flex;
    align-items: center;
    justify-content: center;
    cursor: pointer;
    -webkit-user-select: none;
    -webkit-touch-callout: none;
    -moz-user-select: none;
    -ms-user-select: none;
    user-select: none;
`;

//location → grid-area name; rolled ships swap the port/starboard columns
const GRID_AREAS = { 1: 'fwd', 2: 'aft', 0: 'prim', 3: 'left', 4: 'right', 31: 'lfwd', 41: 'rfwd', 32: 'laft', 42: 'raft' };
const MIRRORED_LOCATION = { 3: 4, 4: 3, 31: 41, 41: 31, 32: 42, 42: 32 };
//vertical alignment inside the grid band, per area (the Stage 1 lesson: Primary only
//centres between Forward and Aft because it shares the middle band with all sides)
const GRID_VALIGN = { fwd: 'center', aft: 'center', prim: 'center', left: 'center', right: 'center', lfwd: 'start', rfwd: 'start', laft: 'end', raft: 'end' };
//render order is irrelevant for grid placement; kept geographic for DOM readability
const GRID_LOCATIONS = [1, 3, 31, 32, 0, 4, 41, 42, 2];
const COMPACT_LOCATIONS = [1, 3, 31, 0, 4, 41, 32, 2, 42];
const QUARTER_LOCATIONS = [31, 32, 41, 42];

class ShipWindow extends React.Component {
    constructor(props) {
        super(props);
        this.elementRef = React.createRef();
        this.controlsRef = React.createRef();
        this.popupRef = React.createRef();
        //openPanel: clicked open (sticky until click-outside); hoverNotes: desktop
        //hover peek on the Notes button/popup, hides shortly after mouse-out
        this.state = { openPanel: null, hoverNotes: false }; //openPanel: null | 'hitchart' | 'notes'
        this.notesHoverTimer = null;
        this.onDocumentPointerDown = this.onDocumentPointerDown.bind(this);
    }

    onShipClick(event) {
        event.stopPropagation();
        let { ship } = this.props;

        if (this.ignoreNextClick) {
            this.ignoreNextClick = false;
            return;
        }

        window.uiEvents.relay('SystemClicked', {
            ship: ship,
            system: ship,
            element: event.target
        });
    }

    onShipTouchMove(event) {
        if (!this.longPressTimer) return;

        const touch = event.touches[0];
        const dx = touch.clientX - this.touchStartX;
        const dy = touch.clientY - this.touchStartY;

        // Cancel if they move more than 10 pixels
        if (Math.abs(dx) > 10 || Math.abs(dy) > 10) {
            clearTimeout(this.longPressTimer);
            this.longPressTimer = null;
        }
    }

    onShipTouchCancel(event) {
        if (this.longPressTimer) {
            clearTimeout(this.longPressTimer);
            this.longPressTimer = null;
        }
        this.touchActive = false;
        window.uiEvents.relay('SystemMouseOut');
    }

    onShipTouchEnd(event) {
        if (this.longPressTimer) {
            // Timer didn't pop, meaning this was a short tap
            clearTimeout(this.longPressTimer);
            this.longPressTimer = null;
        } else {
            // Timer already fired (long press). Hide info on release.
            window.uiEvents.relay('SystemMouseOut');
        }

        setTimeout(() => {
            this.touchActive = false;
        }, 300); // Clear touch active after giving click events time to fire/be ignored
    }

    onUnknownMouseOver(event) {
        if (this.touchActive) return;
        if (window.lastTouchActiveTime && Date.now() - window.lastTouchActiveTime < 1000) return;

        let { ship } = this.props;
        let stealthSystem = shipManager.systems.getSystemByName(ship, "mineStealth");

        window.uiEvents.relay('SystemMouseOver', {
            ship: ship,
            system: stealthSystem ? stealthSystem : ship.systems[0],
            element: event.currentTarget,
            showInfo: true
        });
    }

    onUnknownMouseOut() {
        if (this.touchActive) return;
        if (window.lastTouchActiveTime && Date.now() - window.lastTouchActiveTime < 1000) return;

        window.uiEvents.relay('SystemMouseOut');
    }

    onUnknownTouchStart(event) {
        this.touchActive = true;
        this.ignoreNextClick = false;
        window.lastTouchActiveTime = Date.now();

        if (this.longPressTimer) {
            clearTimeout(this.longPressTimer);
        }

        const target = event.currentTarget;
        const touch = event.touches[0];
        this.touchStartX = touch.clientX;
        this.touchStartY = touch.clientY;

        this.longPressTimer = setTimeout(() => {
            this.ignoreNextClick = true; // Prevent click from firing after long press
            let { ship } = this.props;
            let stealthSystem = shipManager.systems.getSystemByName(ship, "mineStealth");

            // Long Press -> generic tooltip
            window.uiEvents.relay('SystemMouseOver', {
                ship: ship,
                system: stealthSystem ? stealthSystem : ship.systems[0],
                element: target,
                showInfo: true
            });

            this.longPressTimer = null;
        }, 400); // 400ms hold required for info window
    }


    componentDidMount() {
        const element = jQuery(this.elementRef.current);
        //drag by the header bar only (plan §6) - body drags would fight the ship-click
        //underlay and system icon interactions; the header ✕ stays click-only
        element.draggable({ handle: ".shipwindow-drag-handle", cancel: ".shipwindow-nodrag" });

        //close the Hit Chart / Notes popup on any press outside it (2026-07-16 feedback)
        document.addEventListener('pointerdown', this.onDocumentPointerDown);
    }

    componentWillUnmount() {
        document.removeEventListener('pointerdown', this.onDocumentPointerDown);
        if (this.notesHoverTimer) clearTimeout(this.notesHoverTimer);
    }

    onNotesHoverStart() {
        if (this.notesHoverTimer) {
            clearTimeout(this.notesHoverTimer);
            this.notesHoverTimer = null;
        }
        if (!this.state.hoverNotes) this.setState({ hoverNotes: true });
    }

    //short grace period so the pointer can cross from the button into the popup
    onNotesHoverEnd() {
        if (this.notesHoverTimer) clearTimeout(this.notesHoverTimer);
        this.notesHoverTimer = setTimeout(() => {
            this.notesHoverTimer = null;
            this.setState({ hoverNotes: false });
        }, 150);
    }

    onDocumentPointerDown(event) {
        if (!this.state.openPanel) return;

        const controls = this.controlsRef.current;
        const popup = this.popupRef.current;
        if (controls && controls.contains(event.target)) return; //button clicks toggle themselves
        if (popup && popup.contains(event.target)) return; //clicks inside the popup keep it open

        this.setState({ openPanel: null });
    }

    close() {
        window.uiEvents.relay('CloseShipWindow', { ship: this.props.ship });
    }

    togglePanel(name, event) {
        event.stopPropagation();
        this.setState(state => ({ openPanel: state.openPanel === name ? null : name }));
    }

    renderHeader(shipName, unitName, tint) {
        return (
            <Header className="shipwindow-drag-handle">
                <HeaderName $tint={tint} title={shipName}>{shipName}</HeaderName>
                <HeaderClass title={unitName}>{unitName}</HeaderClass>
                <CloseButton className="shipwindow-nodrag" onClick={this.close.bind(this)}>✕</CloseButton>
            </Header>
        );
    }

    renderControls(withHitChart, withNotes, compact) {
        if (!withHitChart && !withNotes) return null;

        const { openPanel } = this.state;

        return (
            <ControlsArea ref={this.controlsRef} $compact={compact}>
                {withHitChart && (
                    <CtrlButton $active={openPanel === 'hitchart'} onClick={this.togglePanel.bind(this, 'hitchart')}>
                        <CtrlIcon>⊕</CtrlIcon>Hit Chart
                    </CtrlButton>
                )}
                {withNotes && (
                    <CtrlButton
                        $active={openPanel === 'notes'}
                        onClick={this.togglePanel.bind(this, 'notes')}
                        onMouseEnter={this.onNotesHoverStart.bind(this)}
                        onMouseLeave={this.onNotesHoverEnd.bind(this)}
                    >
                        <CtrlIcon>✎</CtrlIcon>Notes
                    </CtrlButton>
                )}
            </ControlsArea>
        );
    }

    renderPopup(withHitChart, withNotes, top) {
        const { ship } = this.props;
        const { openPanel, hoverNotes } = this.state;

        //clicked panel wins; otherwise a desktop hover on the Notes button peeks the notes
        const shown = openPanel || (hoverNotes && withNotes ? 'notes' : null);

        if (shown === 'hitchart' && withHitChart) {
            return <PopupHolder ref={this.popupRef} $top={top}><HitChartPanel ship={ship} /></PopupHolder>;
        }
        if (shown === 'notes' && withNotes) {
            return (
                <PopupHolder
                    ref={this.popupRef}
                    $top={top}
                    $fit
                    onMouseEnter={this.onNotesHoverStart.bind(this)}
                    onMouseLeave={this.onNotesHoverEnd.bind(this)}
                >
                    <ShipInfo ship={ship} hideHitChart />
                </PopupHolder>
            );
        }
        return null;
    }

    render() {
        const { ship } = this.props;
        const isMyTeam = ship.team === window.gamedata.getPlayerTeam();

        var unitName = ship.shipClass;
        var shipName = ship.name;
        let isUnrevealedMine = false;

        if (ship.mine) {
            var stealthSystem = shipManager.systems.getSystemByName(ship, "mineStealth");
            if (stealthSystem && !stealthSystem.isMineRevealed(ship)) {
                unitName = "Mine";
                shipName = "Mine";
                isUnrevealedMine = true;
            }
        }

        const withHitChart = !isUnrevealedMine && hasHitChart(ship);
        const withNotes = !isUnrevealedMine && hasNotes(ship);

        if (ship.flight) {
            return (
                <ShipWindowContainer ref={this.elementRef} onClick={shipWindowClicked} onContextMenu={e => { e.preventDefault(); e.stopPropagation(); }} $isMyTeam={isMyTeam} $variant="flight">
                    {this.renderHeader(shipName, unitName, getHeaderTint(ship))}
                    <FighterList ship={ship} />
                </ShipWindowContainer>
            )
        }

        if (isUnrevealedMine) {
            return (<ShipWindowContainer ref={this.elementRef} onClick={shipWindowClicked} onContextMenu={e => { e.preventDefault(); e.stopPropagation(); }} $isMyTeam={isMyTeam} $variant="terrain">
                {this.renderHeader(shipName, unitName, null)}
                <CompactBody>
                    <ShipHitArea onClick={this.onShipClick.bind(this)} />
                    <WatermarkLayer $img={window.AssetManager.getSmartImagePath(ship.imagePath)} />
                    <UnknownSystemIcon onMouseOver={this.onUnknownMouseOver.bind(this)} onMouseOut={this.onUnknownMouseOut.bind(this)} onTouchStart={this.onUnknownTouchStart.bind(this)} onTouchMove={this.onShipTouchMove.bind(this)} onTouchEnd={this.onShipTouchEnd.bind(this)} onTouchCancel={this.onShipTouchCancel.bind(this)}>?</UnknownSystemIcon>
                </CompactBody>
            </ShipWindowContainer>)
        }

        const systemsByLocation = sortIntoLocations(ship);
        const isTerrain = window.gamedata.isTerrain(ship.shipSizeClass, ship.userid) || ship.mine;

        //terrain + revealed mines keep the compact thumbnail variant
        if (isTerrain) {
            return (<ShipWindowContainer ref={this.elementRef} onClick={shipWindowClicked} onContextMenu={e => { e.preventDefault(); e.stopPropagation(); }} $isMyTeam={isMyTeam} $variant="terrain">
                {this.renderHeader(shipName, unitName, null)}
                <CompactBody>
                    <ShipHitArea onClick={this.onShipClick.bind(this)} />
                    <WatermarkLayer $img={window.AssetManager.getSmartImagePath(ship.imagePath)} />
                    {/*compact windows: vertical button list above the sections*/}
                    {this.renderControls(withHitChart, withNotes, true)}
                    {COMPACT_LOCATIONS.map(location => systemsByLocation[location].length > 0 && (
                        <ShipSection key={`section-${ship.id}-${location}`} location={location} ship={ship} systems={systemsByLocation[location]} isTerrain />
                    ))}
                </CompactBody>
                {this.renderPopup(withHitChart, withNotes, 72)}
            </ShipWindowContainer>)
        }

        //full "digital SCS" grid window
        const rolled = shipManager.movement.isRolled(ship);
        const areas = buildTemplateAreas(systemsByLocation);
        const isBigBase = ship.base && !ship.smallBase;

        return (<ShipWindowContainer ref={this.elementRef} onClick={shipWindowClicked} onContextMenu={e => { e.preventDefault(); e.stopPropagation(); }} $isMyTeam={isMyTeam} $variant="ship">
            {this.renderHeader(shipName, unitName, getHeaderTint(ship))}
            <SectionGrid $areas={areas}>
                <ShipHitArea onClick={this.onShipClick.bind(this)} />
                <WatermarkLayer $img={window.AssetManager.getSmartImagePath(ship.imagePath)} />
                {this.renderControls(withHitChart, withNotes)}
                <ShipWindowEw ship={ship} />
                {GRID_LOCATIONS.map(location => {
                    if (systemsByLocation[location].length === 0) return null;

                    const displayLocation = rolled && MIRRORED_LOCATION[location] !== undefined ? MIRRORED_LOCATION[location] : location;
                    const area = GRID_AREAS[displayLocation];
                    //big-base quarter sections spread 4-wide so their many systems don't
                    //stack into a metre-tall window; sparse six-sided quarters get a
                    //minimum height so small hulls (Xill) keep some presence
                    const wide = location === 0 || location === 1 || location === 2
                        || (isBigBase && QUARTER_LOCATIONS.includes(location));
                    const minHeight = ship.SixSidedShip && QUARTER_LOCATIONS.includes(location) ? 84 : undefined;

                    return (
                        <ShipSection
                            key={`section-${ship.id}-${location}`}
                            location={location}
                            displayLocation={displayLocation}
                            area={area}
                            valign={GRID_VALIGN[area]}
                            wide={wide}
                            minHeight={minHeight}
                            ship={ship}
                            systems={systemsByLocation[location]}
                        />
                    );
                })}
            </SectionGrid>
            {rolled && <RolledBanner>⟲ Rolled — port / starboard reversed</RolledBanner>}
            {this.renderPopup(withHitChart, withNotes)}
        </ShipWindowContainer>)
    }

}

const shipWindowClicked = () => window.uiEvents.relay('CloseSystemInfo');

const hasHitChart = (ship) => Boolean(ship.hitChart) && Object.keys(ship.hitChart).length > 0;

const hasNotes = (ship) => Boolean(ship.notes)
    || Boolean(ship.enhancementTooltip)
    || Boolean(ship.hasAttached && Object.keys(ship.hasAttached).length > 0);

/*Faction/team tint for the header name. Reuses the fleet list's canonical palette call
  (gamedata.getFleetHeaderColorRGB carries the team-colour gate internally) rather than
  adding another gate site - see arch_team_colour_logic.*/
const getHeaderTint = (ship) => {
    /*if (typeof playerManager === 'undefined' || !window.gamedata) return null;
    if (typeof gamedata.getFleetHeaderColorRGB !== 'function') return null;

    const slot = playerManager.getSlotById(ship.slot);
    if (!slot) return null;

    return gamedata.getFleetHeaderColorRGB(slot);*/
    return new THREE.Color(224 / 255, 231 / 255, 239 / 255).convertSRGBToLinear(); //Keep off-white for now   
};

const sortIntoLocations = (ship) => {

    const locations = { 0: [], 1: [], 2: [], 3: [], 4: [], 5: [], 41: [], 42: [], 31: [], 32: [] };

    ship.systems.forEach((system) => {
        //hideInShipWindow: technical-only systems (e.g. InvulnerableThruster) are omitted from the
        //icon grid. Purely cosmetic - thrust mechanics iterate ship.systems directly, not this list.
        if (system.hideInShipWindow) return;
        locations[system.location].push(system);
    });

    return locations;
}

/*Grid template rows, built from whichever locations exist so absent sections collapse:
      "ctrl fwd   ew"      always - window chrome (buttons / Forward / EW panel)
      "left prim  right"   ships with Port/Starboard (3/4)
      "lfwd prim  rfwd"    six-sided ships / big bases (31/41)
      "laft prim  raft"    six-sided ships / big bases (32/42)
      ".    aft   ."       always when Aft exists
  Primary spans every middle row present (a contiguous rectangle, as grid requires).*/
const buildTemplateAreas = (locations) => {
    const rows = ['"ctrl  fwd   ew"'];
    let middleRows = 0;

    if (locations[3].length || locations[4].length) { rows.push('"left  prim  right"'); middleRows++; }
    if (locations[31].length || locations[41].length) { rows.push('"lfwd  prim  rfwd"'); middleRows++; }
    if (locations[32].length || locations[42].length) { rows.push('"laft  prim  raft"'); middleRows++; }
    if (middleRows === 0 && locations[0].length) rows.push('".    prim  ."');
    if (locations[2].length) rows.push('".    aft   ."');

    return rows.join(' ');
}


export default ShipWindow;

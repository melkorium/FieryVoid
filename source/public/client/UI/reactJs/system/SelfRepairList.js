import * as React from "react";
import styled from "styled-components";
import { Clickable } from "../styled";

const Container = styled.div`
    display: flex;
    flex-direction: column;
    margin-top: 5px;
    width: 100%;
    min-width: 200px;
`;

const Header = styled.div`
    padding: 3px;
    background-color: #2b3e51;
    border: 1px solid #496791;
    color: #f2f2f2;
    text-align: center;
    font-size: 12px;
    margin-bottom: 2px;
    font-weight: normal;
`;

const ListContainer = styled.div`
    background-color: rgba(0, 0, 0, 0.9);
    border: 1px solid #496791;
    max-height: 200px;
    overflow-y: auto;
    display: block;
`;

const ListItem = styled.div`
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 3px 5px;
    border-bottom: 1px solid #2b3e51;
    font-size: 11px;
    color: #e6e6e6;

    &:last-child {
        border-bottom: none;
    }
`;

const ItemInfo = styled.div`
    flex-grow: 1;
    display: flex;
    flex-direction: column;
`;

const ItemName = styled.span`
    font-weight: bold;
`;

const ItemStatus = styled.span`
    font-size: 9px;
    color: #a3badc;
    margin-top: 2px;
    margin-left: 1px;
`;

const CriticalItemName = styled(ItemName)`
    color: #ffb833;
    font-weight: normal;    
`;

const DestroyedItemName = styled(ItemName)`
    color: #ff1a1a;
    font-weight: bold;    
`;

const ActionButtons = styled.div`
    display: flex;
    gap: 2px;
`;

const ActionButton = styled.div`
    width: 16px;
    height: 16px;
    background-image: url(${props => props.img});
    background-size: cover;
    cursor: pointer;
    opacity: 0.9;
    margin-left: 3px;
    &:hover {
        opacity: 1;
    }
    
     ${Clickable}
`;

const CenteredListItem = styled(ListItem)`
    justify-content: center;
    font-style: italic;
    opacity: 0.7;
`;

class SelfRepairList extends React.Component {
    constructor(props) {
        super(props);
    }

    getRepairableSystems() {
        const { ship, system } = this.props;
        const systems = [];
        const criticals = [];

        // DEBUG: Trace start of calculation
        // console.log("SelfRepairList: Calculating systems...");

        // Ensure systems is iterable (handle Object vs Array)
        const shipSystems = Array.isArray(ship.systems) ? ship.systems : Object.values(ship.systems);

        for (const sys of shipSystems) {

            if (sys.repairPriority === 0) continue; // Priority 0 cannot be repaired

            //if Structure - skip if destroyed (can't be repaired anyway)
            if ((sys.name == 'structure') && (shipManager.systems.isDestroyed(sys.ship, sys))) continue;
            //if fitted to destroyed Structure - skip (can't be repaired anyway)
            if ((sys.name != 'structure') && (sys.location != 0)) {
                var stru = shipManager.systems.getStructureSystem(sys.ship, sys.location);
                if (stru && shipManager.systems.isDestroyed(sys.ship, stru)) continue;
            }

            // Calculate effective priority for the SYSTEM
            // Start with base priority
            let basePriority = sys.repairPriority;

            // Apply override if it exists
            let isOverridden = false;
            if (system.priorityChanges && (sys.id in system.priorityChanges) && system.priorityChanges[sys.id] >= 0) {
                basePriority = system.priorityChanges[sys.id];
                isOverridden = true;
            }

            // Apply implicit +10 for destroyed systems (ONLY if not overridden)
            if (!isOverridden && shipManager.systems.isDestroyed(ship, sys) && basePriority <= 10) {
                basePriority += 10;
            }

            // --- CRITICALS ---
            if (!shipManager.systems.isDestroyed(ship, sys) && sys.criticals) {
                //Ensure criticals is iterable
                const sysCriticals = Array.isArray(sys.criticals) ? sys.criticals : Object.values(sys.criticals);

                for (const crit of sysCriticals) {

                    if (crit.repairPriority === 0) continue; // Cannot be repaired
                    if (crit.turn >= gamedata.turn) {
                        continue; // Caused this turn
                    }
                    if (crit.oneturn || (crit.turnend > 0)) {
                        continue; // Temporary or already fixed
                    }

                    // START CRITICAL PRIORITY LOGIC
                    let critPriority = crit.repairPriority || 0;
                    const compositeKey = sys.id + '-' + crit.id;
                    let critOverridden = false;

                    // Check for Critical Override
                    if (system.priorityChanges && (compositeKey in system.priorityChanges) && system.priorityChanges[compositeKey] >= 0) {
                        critPriority = system.priorityChanges[compositeKey];
                        critOverridden = true;
                        // console.log(`SelfRepairList: Found override for ${compositeKey}: ${critPriority}`);
                    } else {
                        // Standard Logic (if not overridden): Add NATIVE System Priority if < 10
                        if (critPriority < 10) {
                            critPriority += sys.repairPriority;
                        }
                    }

                    criticals.push({
                        type: 'critical',
                        sys: sys,
                        crit: crit,
                        priority: critPriority,
                        cost: crit.repairCost,
                        id: sys.id, // For stable sort,
                        keyId: compositeKey // For action handlers
                    });
                }
            }


            // --- SYSTEM DAMAGE ---
            // Only damaged systems
            const damage = shipManager.systems.getTotalDamage(sys);
            if (damage > 0) {
                systems.push({
                    type: 'system',
                    sys: sys,
                    priority: basePriority,
                    damage: damage,
                    maxHealth: sys.maxhealth,
                    keyId: sys.id // For action handlers
                });
            }
        }

        // Concatenate first
        const allItems = [...criticals, ...systems];

        // Sort Unified List: Priority DESC, then Criticals over Systems, then ID ASC
        allItems.sort((a, b) => {
            if (a.priority !== b.priority) return b.priority - a.priority; // Higher priority first

            // If priorities equal, Criticals come first (legacy behavior/tie-breaker)
            if (a.type !== b.type) {
                return a.type === 'critical' ? -1 : 1;
            }

            // Secondary sorts from previous logic
            if (a.type === 'critical') {
                if (a.cost !== b.cost) return b.cost - a.cost; // Costlier crits first
            } else {
                if (a.maxHealth !== b.maxHealth) return a.maxHealth - b.maxHealth; // Smaller systems first
            }

            return a.id - b.id;
        });

        return allItems;
    }

    handleTop(e, targetId) {
        e.stopPropagation();

        const systems = this.getRepairableSystems();
        if (systems.length === 0) return;

        const maxPrio = systems[0].priority;
        const myItem = systems.find(s => s.keyId === targetId); // Use keyId

        if (!myItem) return;

        // If I am already at the top priority, do nothing
        if (myItem.priority === maxPrio) {
            return;
        }

        let newPrio = maxPrio + 1;

        this.setPriority(targetId, newPrio);
    }

    handleUp(e, targetId, currentPriority) {
        e.stopPropagation();

        let newPrio = currentPriority + 1;

        if (newPrio !== currentPriority) {
            this.setPriority(targetId, newPrio);
        }
    }

    handleDown(e, targetId, currentPriority) {
        e.stopPropagation();

        if (currentPriority <= 1) return; // Min priority
        this.setPriority(targetId, currentPriority - 1);
    }

    handleReset(e, targetId) {
        e.stopPropagation();
        // Resetting simply means removing the override. 
        // We can pass -1 or handle it in setPriority.
        // The previous implementation looked up default priority.
        // For Criticals, default is implied. For Systems, default is on the object.
        // Simplest way: set override to -1 to trigger "unset" logic in server/client.

        this.setPriority(targetId, -1);
    }

    setPriority(targetId, newPriority) {
        const { ship, system } = this.props;

        system.setOverride(targetId, newPriority);

        // Propagate
        for (const sys of ship.systems) {
            if (sys.name === 'SelfRepair' && sys.id !== system.id) {
                sys.setOverride(targetId, newPriority);
            }
        }

        webglScene.customEvent('SystemDataChanged', { ship: ship, system: system });
    }

    render() {
        const { ship } = this.props;
        const repairableSystems = this.getRepairableSystems();

        return (
            <Container>
                <Header>
                    Manage Repair Queue
                </Header>

                <ListContainer>
                    {repairableSystems.length === 0 && <CenteredListItem>No damaged systems</CenteredListItem>}
                    {repairableSystems.map(item => (
                        <ListItem key={`${item.type}-${item.sys.id}${item.crit ? '-' + item.crit.id : ''}`}>
                            <ItemInfo>
                                {item.type === 'critical' ? (
                                    <>
                                        <CriticalItemName>{item.sys.displayName} ({item.crit.description || item.crit.phpclass})</CriticalItemName>
                                        <ItemStatus>
                                            Cost: {item.cost}  |  Id: {item.sys.id}  |  Prio: {item.priority}
                                        </ItemStatus>
                                    </>
                                ) : (
                                    <>
                                        {shipManager.systems.isDestroyed(ship, item.sys) ? (
                                            <DestroyedItemName>{item.sys.displayName}</DestroyedItemName>
                                        ) : (
                                            <ItemName>{item.sys.displayName}</ItemName>
                                        )}
                                        <ItemStatus>
                                            HP: {shipManager.systems.getRemainingHealth(item.sys)} / {item.sys.maxhealth}  |  Prio: {item.priority}
                                        </ItemStatus>
                                    </>
                                )}
                            </ItemInfo>
                            <ActionButtons>
                                <ActionButton title="Reset Default" onClick={(e) => this.handleReset(e, item.keyId)} img="./img/iconSRCancel.png" />
                                <ActionButton title="Decrease Priority" onClick={(e) => this.handleDown(e, item.keyId, item.priority)} img="./img/systemicons/AAclasses/iconMinus.png" />
                                <ActionButton title="Increase Priority" onClick={(e) => this.handleUp(e, item.keyId, item.priority)} img="./img/systemicons/AAclasses/iconPlus.png" />
                                <ActionButton title="Move to Top" onClick={(e) => this.handleTop(e, item.keyId)} img="./img/iconSRHigh.png" />
                            </ActionButtons>
                        </ListItem>
                    ))}
                </ListContainer>
            </Container>
        );
    }
}

export default SelfRepairList;


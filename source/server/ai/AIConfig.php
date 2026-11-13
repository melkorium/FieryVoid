<?php
/**
 * CPU opponent - every number and list the AI is tuned by, in one place (TACTICAL_AI_PLAN.md
 * section 5). Stage T0 needs only the driver's limits; weights and thresholds join as the
 * deciders are built.
 */
class AIConfig
{
    //The feature flag (Features.php). Off = none of source/server/ai/ is reachable.
    const FLAG = 'cpuOpponent';

    //Seconds one request may spend driving bots. A longer CPU turn carries on at the next poll.
    const BUDGET_SECONDS = 4.0;

    //Hard stop on submissions in one run, whatever the clock says (the self-play harness has no clock).
    const MAX_SUBMISSIONS_PER_RUN = 200;

    //A bot refused this many times at the same point of a game gives the game up (plan section 4).
    const STALL_LIMIT = 3;

    //Rows of tac_ai_log kept per game (DBManager::aiLog).
    const LOG_CAP = 2000;

    //How long "this game still has a bot waiting to play" is remembered between polls (seconds).
    const PENDING_TTL = 120;

    //What a seat may be set to. Levels and personalities do nothing before stage T5 / T8; the
    //seat stores them from the start so that games created now keep their setting.
    const LEVELS = array('easy', 'normal', 'hard');
    const DEFAULT_LEVEL = 'normal';
    const PERSONALITIES = array('balanced');
    const DEFAULT_PERSONALITY = 'balanced';
}

import { parseInput } from './parser.js';
import { includesMatch } from './util.js';
import { logLines, logText, formatList } from './ui.js';
import { loadWorld, loadRoom, loadNpc, loadDialog, loadAscii, resetCache } from './loader.js';
import { runEvents } from './events.js';

const STORAGE_KEY = 'adventure.save';

export const state = {
  adventureId: 'demo',
  started: false,
  room: null,
  inventory: [],
  flags: {},
  dialog: { active: false, npcId: null, nodeId: null },
};

let currentWorld = null;

function persistState() {
  try {
    localStorage.setItem(STORAGE_KEY, JSON.stringify(state));
  } catch (e) {
    // ignore
  }
}

function restoreState() {
  try {
    const raw = localStorage.getItem(STORAGE_KEY);
    if (raw) {
      const saved = JSON.parse(raw);
      Object.assign(state, saved);
      if (!state.dialog) state.dialog = { active: false, npcId: null, nodeId: null };
    }
  } catch (e) {
    // ignore
  }
}

export async function start() {
  state.started = true;
  state.room = null;
  state.inventory = [];
  state.flags = {};
  state.dialog = { active: false, npcId: null, nodeId: null };
  resetCache();
  await ensureWorld();
  state.room = currentWorld.start_room;
  persistState();
  await showRoom();
}

export async function continueGame() {
  restoreState();
  await ensureWorld();
  if (!state.started) {
    state.started = true;
    state.room = currentWorld.start_room;
  }
  if (state.dialog && state.dialog.active) {
    logLines(['(Dialog fortgesetzt)']);
    await showDialogNode();
  } else {
    await showRoom();
  }
}

export async function reset() {
  localStorage.removeItem(STORAGE_KEY);
  state.started = false;
  state.room = null;
  state.inventory = [];
  state.flags = {};
  state.dialog = { active: false, npcId: null, nodeId: null };
  resetCache();
  logLines(['Abenteuer zurückgesetzt. Tippe "adv start".']);
}

async function ensureWorld() {
  if (!currentWorld) {
    currentWorld = await loadWorld(state.adventureId);
  }
}

export async function handleInput(raw) {
  if (!raw) return;
  if (state.dialog && state.dialog.active) {
    const trimmed = raw.trim();
    if (/^(abbrechen|exit|bye)$/i.test(trimmed)) {
      endDialog();
      return;
    }
    const num = parseInt(trimmed, 10);
    if (!isNaN(num)) {
      await handleDialogChoice(num);
      return;
    }
    logLines(['Bitte eine Zahl wählen oder "abbrechen".']);
    return;
  }

  const action = parseInput(raw);
  if (!action) return;

  switch (action.verb) {
    case 'start':
      await start();
      break;
    case 'continue':
      await continueGame();
      break;
    case 'reset':
      await reset();
      break;
    case 'help':
      logLines(['Befehle: look, go <richtung>, take <item>, drop <item>, talk <person>, inventory']);
      break;
    case 'look':
      await showRoom();
      break;
    case 'inventory':
      logLines(['Inventar: ' + formatList(state.inventory)]);
      break;
    case 'talk':
      await performTalk(action.target);
      break;
    default:
      logLines(['Unbekannter Befehl.']);
      break;
  }
}

export async function showRoom() {
  await ensureWorld();
  if (!state.room) {
    state.room = currentWorld.start_room;
  }
  const room = await loadRoom(state.adventureId, state.room);
  const lines = [room.name || state.room, room.description || '', ''];

  const exits = room.exits || {};
  const unlocked = Object.keys(exits).filter((dir) => exits[dir] && exits[dir].unlocked !== false);
  if (unlocked.length) lines.push('Ausgänge: ' + unlocked.join(', '));

  if (room.items && room.items.length) {
    lines.push('Gegenstände: ' + formatList(room.items));
  }

  const npcs = await listNpcsInRoom(state.room);
  if (npcs.length) {
    lines.push('Personen: ' + npcs.map((n) => n.name || n.id).join(', '));
  }

  logLines(lines);
}

async function listAllNpcs() {
  await ensureWorld();
  const ids = currentWorld.npcs || [];
  const list = [];
  for (const id of ids) {
    try {
      const npc = await loadNpc(state.adventureId, id);
      list.push(npc);
    } catch (e) {
      // ignore missing npc
    }
  }
  return list;
}

export async function listNpcsInRoom(roomId) {
  const all = await listAllNpcs();
  return all.filter((npc) => {
    if (npc.room && npc.room !== roomId) return false;
    if (npc.hidden_if_flag) {
      const { key, equals } = npc.hidden_if_flag;
      if (state.flags && state.flags[key] === equals) return false;
    }
    if (npc.only_if_flag) {
      const { key, equals } = npc.only_if_flag;
      if (state.flags && state.flags[key] !== equals) return false;
    }
    return true;
  });
}

async function performTalk(target) {
  const npcs = await listNpcsInRoom(state.room);
  if (!npcs.length) {
    logLines(['Niemand ist hier.']);
    return;
  }
  if (!target) {
    logLines(['Mit wem möchtest du sprechen?']);
    return;
  }
  const npc = npcs.find((n) => includesMatch(target, n.name || n.id));
  if (!npc) {
    logLines(['Niemand antwortet.']);
    return;
  }
  await startDialog(npc.id, npc.dialog_start || null);
}

export async function startDialog(npcId, node) {
  const dialog = await loadDialog(state.adventureId, npcId);
  const nodeId = node || dialog.start || 'start';
  state.dialog = { active: true, npcId, nodeId };
  persistState();
  await showDialogNode();
}

export function endDialog() {
  state.dialog = { active: false, npcId: null, nodeId: null };
  persistState();
  logLines(['(Dialog beendet)']);
  showRoom();
}

async function resolveDialogChoices(node) {
  const visible = [];
  for (const choice of node.choices || []) {
    if (choice.hidden_if) {
      if (choice.hidden_if.inventory && choice.hidden_if.inventory.every((id) => state.inventory.includes(id))) {
        continue;
      }
      if (
        choice.hidden_if.flag &&
        state.flags &&
        state.flags[choice.hidden_if.flag.key] === choice.hidden_if.flag.equals
      ) {
        continue;
      }
    }
    visible.push(choice);
  }
  return visible;
}

function meetsRequirements(choice) {
  if (!choice.requires) return true;
  if (choice.requires.inventory) {
    const ok = choice.requires.inventory.every((id) => state.inventory.includes(id));
    if (!ok) return false;
  }
  if (choice.requires.flag) {
    const { key, equals } = choice.requires.flag;
    if (state.flags[key] !== equals) return false;
  }
  return true;
}

export async function showDialogNode() {
  const dialog = await loadDialog(state.adventureId, state.dialog.npcId);
  const node = dialog.nodes[state.dialog.nodeId];
  if (!node) {
    endDialog();
    return;
  }
  const npc = await loadNpc(state.adventureId, dialog.npc);
  const lines = [];
  lines.push(npc.name || npc.id);
  lines.push(node.text || '');

  const ascii = await loadAscii(state.adventureId, node.ascii);
  if (ascii) {
    lines.push('```');
    lines.push(ascii);
    lines.push('```');
  }

  const choices = await resolveDialogChoices(node);
  choices.forEach((choice, idx) => {
    const available = meetsRequirements(choice);
    let label = `${idx + 1}) ${choice.text}`;
    if (!available) label += ' [X]';
    lines.push(label);
    if (!available && choice.note) {
      lines.push(`   ${choice.note}`);
    }
  });
  lines.push('');
  lines.push(`Wähle 1-${choices.length} oder tippe 'abbrechen'.`);
  logLines(lines);
}

export async function handleDialogChoice(index) {
  const dialog = await loadDialog(state.adventureId, state.dialog.npcId);
  const node = dialog.nodes[state.dialog.nodeId];
  if (!node) {
    endDialog();
    return;
  }
  const choices = await resolveDialogChoices(node);
  const choice = choices[index - 1];
  if (!choice) {
    logLines(['Ungültige Auswahl.']);
    return;
  }
  if (!meetsRequirements(choice)) {
    logLines(['Das kannst du gerade nicht.']);
    return;
  }
  if (choice.events) {
    await runEvents(choice.events, state, dialogEventContext());
  }
  if (!choice.next || choice.next === 'end' || !dialog.nodes[choice.next]) {
    endDialog();
    return;
  }
  state.dialog.nodeId = choice.next;
  persistState();
  await showDialogNode();
}

function dialogEventContext() {
  return {
    startDialog: (npc, node) => startDialog(npc, node),
    endDialog: () => endDialog(),
    gotoDialogNode: (node) => gotoDialogNode(node),
    changeRoom: (room) => changeRoom(room),
    unlockExit: (room, dir) => unlockExit(room, dir),
    lockExit: (room, dir) => lockExit(room, dir),
  };
}

async function gotoDialogNode(node) {
  state.dialog.nodeId = node;
  persistState();
  await showDialogNode();
}

async function changeRoom(roomId) {
  state.room = roomId;
  persistState();
  await showRoom();
}

export function unlockExit(roomId, direction) {
  // optional room unlock handling placeholder
  logText(`Ausgang ${direction} in ${roomId} entsperrt.`);
}

export function lockExit(roomId, direction) {
  logText(`Ausgang ${direction} in ${roomId} gesperrt.`);
}

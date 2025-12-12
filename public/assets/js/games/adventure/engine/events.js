import { logLines, logText } from './ui.js';
import { loadAscii } from './loader.js';

export async function runEvents(events = [], state, ctx) {
  if (!events || !events.length) return;
  for (const evt of events) {
    await handleEvent(evt, state, ctx);
  }
}

async function handleEvent(event, state, ctx = {}) {
  const type = event.type;
  switch (type) {
    case 'message':
      logLines([event.text]);
      break;
    case 'ascii': {
      const art = await loadAscii(state.adventureId, event.file || event.ascii || event.path || event);
      if (art) {
        logLines(['```', art, '```']);
      }
      break;
    }
    case 'flag_set':
      if (!state.flags) state.flags = {};
      state.flags[event.key] = event.value;
      break;
    case 'flag_if': {
      const matches = state.flags && state.flags[event.key] === event.equals;
      if (matches && Array.isArray(event.then)) {
        await runEvents(event.then, state, ctx);
      } else if (!matches && Array.isArray(event.else)) {
        await runEvents(event.else, state, ctx);
      }
      break;
    }
    case 'add_item':
      if (!state.inventory.includes(event.id)) {
        state.inventory.push(event.id);
      }
      break;
    case 'remove_item':
      state.inventory = state.inventory.filter((i) => i !== event.id);
      break;
    case 'unlock_exit':
      ctx.unlockExit && ctx.unlockExit(event.room, event.direction);
      break;
    case 'lock_exit':
      ctx.lockExit && ctx.lockExit(event.room, event.direction);
      break;
    case 'transition':
      ctx.changeRoom && (await ctx.changeRoom(event.to));
      break;
    case 'trigger_fight':
      logText('[Kampf wird ausgelöst – noch nicht implementiert]');
      break;
    case 'start_dialog':
      ctx.startDialog && (await ctx.startDialog(event.npc, event.node));
      break;
    case 'end_dialog':
      ctx.endDialog && ctx.endDialog();
      break;
    case 'goto_dialog_node':
      ctx.gotoDialogNode && (await ctx.gotoDialogNode(event.node));
      break;
    default:
      logText(`[Unbekanntes Event: ${type}]`);
      break;
  }
}

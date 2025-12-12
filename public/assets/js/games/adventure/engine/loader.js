import { logLines } from './ui.js';

const DEFAULT_ROOT = '/assets/js/games/adventure/adventures/';
export const ADVENTURE_ROOT = (typeof window !== 'undefined' && window.ADVENTURE_ROOT) || DEFAULT_ROOT;

const cache = {
  adventures: {},
  rooms: {},
  items: {},
  objects: {},
  npcs: {},
  dialogs: {},
  ascii: {},
};

async function fetchJson(path) {
  const response = await fetch(path);
  if (!response.ok) {
    throw new Error(`Failed to load ${path}`);
  }
  return response.json();
}

async function fetchText(path) {
  const response = await fetch(path);
  if (!response.ok) {
    throw new Error(`Failed to load ${path}`);
  }
  return response.text();
}

export async function loadWorld(adventureId) {
  if (!cache.adventures[adventureId]) {
    cache.adventures[adventureId] = await fetchJson(`${ADVENTURE_ROOT}${adventureId}/world.json`);
  }
  return cache.adventures[adventureId];
}

export async function loadRoom(adventureId, roomId) {
  const key = `${adventureId}:${roomId}`;
  if (!cache.rooms[key]) {
    cache.rooms[key] = await fetchJson(`${ADVENTURE_ROOT}${adventureId}/rooms/${roomId}.json`);
  }
  return cache.rooms[key];
}

export async function loadItem(adventureId, itemId) {
  const key = `${adventureId}:${itemId}`;
  if (!cache.items[key]) {
    cache.items[key] = await fetchJson(`${ADVENTURE_ROOT}${adventureId}/items/${itemId}.json`);
  }
  return cache.items[key];
}

export async function loadObject(adventureId, objectId) {
  const key = `${adventureId}:${objectId}`;
  if (!cache.objects[key]) {
    cache.objects[key] = await fetchJson(`${ADVENTURE_ROOT}${adventureId}/objects/${objectId}.json`);
  }
  return cache.objects[key];
}

export async function loadNpc(adventureId, npcId) {
  const key = `${adventureId}:${npcId}`;
  if (!cache.npcs[key]) {
    cache.npcs[key] = await fetchJson(`${ADVENTURE_ROOT}${adventureId}/npcs/${npcId}.json`);
  }
  return cache.npcs[key];
}

export async function loadDialog(adventureId, npcId) {
  const key = `${adventureId}:${npcId}`;
  if (!cache.dialogs[key]) {
    cache.dialogs[key] = await fetchJson(`${ADVENTURE_ROOT}${adventureId}/dialogs/${npcId}.json`);
  }
  return cache.dialogs[key];
}

export async function loadAscii(adventureId, ascii) {
  if (!ascii) return null;
  const key = typeof ascii === 'string' ? ascii : ascii.file;
  if (!key) return null;
  if (!cache.ascii[key]) {
    try {
      const file = typeof ascii === 'string' ? ascii : ascii.file;
      cache.ascii[key] = await fetchText(`${ADVENTURE_ROOT}${adventureId}/${file}`);
    } catch (e) {
      logLines([`[ASCII konnte nicht geladen werden: ${key}]`]);
      return null;
    }
  }
  return cache.ascii[key];
}

export function resetCache() {
  Object.keys(cache).forEach((k) => (cache[k] = {}));
}

import { normalize } from './util.js';

const verbs = {
  look: ['look', 'umschauen', 'umsehen', 'schau', 'schau dich um'],
  inventory: ['inventory', 'inventar', 'rucksack', 'tasche', 'i', 'inv'],
  go: ['go', 'gehe', 'geh', 'gehe zu', 'gehe nach'],
  take: ['take', 'nimm', 'aufheben', 'einsammeln'],
  drop: ['drop', 'lasse', 'lass fallen', 'werfe'],
  talk: ['talk', 'rede', 'rede mit', 'sprich', 'sprich mit', 'dialog', 'spreche'],
  start: ['start'],
  continue: ['continue', 'fortsetzen', 'weiter'],
  reset: ['reset', 'neu', 'neustart'],
  help: ['help', 'hilfe'],
};

const verbIndex = {};
Object.entries(verbs).forEach(([verb, list]) => {
  list.forEach((alias) => {
    verbIndex[normalize(alias)] = verb;
  });
});

export function parseInput(input) {
  const raw = input.trim();
  if (!raw) return null;
  const normalizedRaw = normalize(raw);
  const words = normalizedRaw.split(/\s+/);

  let match = null;
  let matchLength = 0;
  Object.keys(verbIndex).forEach((alias) => {
    const aliasWords = alias.split(' ');
    if (aliasWords.length > words.length) return;
    const candidate = words.slice(0, aliasWords.length).join(' ');
    if (candidate === alias && aliasWords.length > matchLength) {
      match = verbIndex[alias];
      matchLength = aliasWords.length;
    }
  });

  if (!match) {
    return { verb: 'unknown', raw, normalized: normalizedRaw };
  }

  const rawWords = raw.split(/\s+/);
  const target = rawWords.slice(matchLength).join(' ');

  return {
    verb: match,
    raw,
    normalized: normalizedRaw,
    target: target.trim(),
  };
}

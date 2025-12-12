export function normalize(value) {
  return value
    .toString()
    .toLowerCase()
    .replace(/ä/g, 'ae')
    .replace(/ö/g, 'oe')
    .replace(/ü/g, 'ue')
    .replace(/ß/g, 'ss')
    .trim();
}

export function includesMatch(target, candidate) {
  const normTarget = normalize(target);
  const normCandidate = normalize(candidate);
  return normCandidate.includes(normTarget) || normTarget.includes(normCandidate);
}

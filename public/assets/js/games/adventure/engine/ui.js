const output = (...args) => {
  if (typeof advLog === 'function') {
    advLog(...args);
  } else if (typeof printLines === 'function') {
    printLines(...args);
  } else {
    console.log(...args);
  }
};

export function logLines(lines) {
  const normalized = Array.isArray(lines) ? lines : [String(lines)];
  output(normalized);
}

export function logText(text) {
  output([text]);
}

export function formatList(list) {
  return list && list.length ? list.join(', ') : '-';
}

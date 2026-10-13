// Old HTML embed snippets load dist/js/public.js. The real bundle is
// public.min.js, so this file only loads that one.
const FILE = 'public.js';
const MIN_FILE = 'public.min.js';

function splitUrl(src: string): [string, string] {
  const marks = [src.indexOf('?'), src.indexOf('#')].filter((i) => i !== -1);
  if (!marks.length) {
    return [src, ''];
  }
  const cut = Math.min(...marks);
  return [src.slice(0, cut), src.slice(cut)];
}

function hasPathEnding(src: string, file: string): boolean {
  return splitUrl(src)[0].endsWith(`/${file}`);
}

const OWN_PATH = '/assets/dist/js/public.js';

function findScript(
  doc: Document,
  matches: (path: string) => boolean,
): HTMLScriptElement | null {
  const scripts = doc.getElementsByTagName('script');
  for (let i = scripts.length - 1; i >= 0; i -= 1) {
    if (matches(splitUrl(scripts[i].getAttribute('src') || '')[0])) {
      return scripts[i];
    }
  }
  return null;
}

function findPublicScript(doc: Document): HTMLScriptElement | null {
  return (
    findScript(doc, (path) => path.endsWith(OWN_PATH)) ||
    findScript(doc, (path) => hasPathEnding(path, FILE))
  );
}

export function loadPublicBundle(
  doc: Document,
  current: HTMLScriptElement | null = doc.currentScript as HTMLScriptElement | null,
): void {
  const currentSrc = current && current.getAttribute('src');
  const self =
    current && currentSrc && hasPathEnding(currentSrc, FILE)
      ? current
      : findPublicScript(doc);
  const src = self && self.getAttribute('src');
  if (!self || !src) {
    return;
  }
  const [path, rest] = splitUrl(src);
  const minPath = `${path.slice(0, -FILE.length)}${MIN_FILE}`;
  if (findScript(doc, (candidate) => candidate === minPath)) {
    return;
  }
  const script = doc.createElement('script');
  script.async = false;
  script.src = `${minPath}${rest}`;
  const nonce = self.nonce || self.getAttribute('nonce');
  if (nonce) {
    script.setAttribute('nonce', nonce);
  }
  if (self.parentNode) {
    self.parentNode.insertBefore(script, self.nextSibling);
  } else {
    doc.head.appendChild(script);
  }
}

if (typeof document !== 'undefined') {
  loadPublicBundle(document);
}

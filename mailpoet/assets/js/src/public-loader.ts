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
  const path = splitUrl(src)[0];
  return path.slice(-(file.length + 1)) === `/${file}`;
}

function findScript(doc: Document, file: string): HTMLScriptElement | null {
  const scripts = doc.getElementsByTagName('script');
  for (let i = scripts.length - 1; i >= 0; i -= 1) {
    if (hasPathEnding(scripts[i].getAttribute('src') || '', file)) {
      return scripts[i];
    }
  }
  return null;
}

export function loadPublicBundle(
  doc: Document,
  current: HTMLScriptElement | null = doc.currentScript as HTMLScriptElement | null,
): void {
  if (findScript(doc, MIN_FILE)) {
    return;
  }
  const self = current || findScript(doc, FILE);
  const src = self && self.getAttribute('src');
  if (!self || !src) {
    return;
  }
  const [path, rest] = splitUrl(src);
  const script = doc.createElement('script');
  script.async = false;
  script.src = `${path.slice(0, -FILE.length)}${MIN_FILE}${rest}`;
  if (self.parentNode) {
    self.parentNode.insertBefore(script, self.nextSibling);
  } else {
    doc.head.appendChild(script);
  }
}

if (typeof document !== 'undefined') {
  loadPublicBundle(document);
}

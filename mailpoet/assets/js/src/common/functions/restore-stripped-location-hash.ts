/**
 * Some plugins rewrite the URL early on wp-admin pages and drop the hash
 * fragment before our routers boot (e.g. Tourfic Pro removes any "#/..."
 * hash on every admin page via history.replaceState). The navigation timing
 * entry keeps the URL the document was requested with, so a lost route can
 * be recovered from it. Call before mounting a HashRouter.
 *
 * One-shot by design: it undoes a strip that happened before our bundle
 * runs, not one that happens later, and a back/forward navigation to an
 * already rewritten history entry leaves nothing to restore.
 */
export function restoreStrippedLocationHash(): void {
  if (window.location.hash) {
    return;
  }
  try {
    const [entry] = window.performance.getEntriesByType('navigation');
    const url = entry?.name ?? '';
    const hashIndex = url.indexOf('#');
    if (hashIndex === -1) {
      return;
    }
    const hash = url.slice(hashIndex);
    if (hash.startsWith('#/')) {
      window.history.replaceState(
        window.history.state,
        '',
        window.location.pathname + window.location.search + hash,
      );
    }
  } catch {
    // performance API unavailable — nothing to restore from
  }
}

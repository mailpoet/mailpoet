import { useCallback, useEffect, useRef } from '@wordpress/element';

const IGNORE_CLICKS_AFTER_OPEN_MS = 500;

/**
 * Wraps the send panel’s send action so that one click sends one email.
 *
 * The panel’s Send button opens where the “Review & send” button was, so the
 * second click of a double-click on “Review & send” lands on Send. Clicks
 * right after the panel opens, and clicks while a send is still running, are
 * ignored.
 */
export function useGuardedSend(
  isOpen: boolean,
  send: () => Promise<void>,
): () => Promise<void> {
  const openedAtRef = useRef(0);
  const isSendingRef = useRef(false);

  useEffect(() => {
    if (isOpen) {
      openedAtRef.current = performance.now();
    }
  }, [isOpen]);

  return useCallback(async () => {
    const isTooSoon =
      performance.now() - openedAtRef.current < IGNORE_CLICKS_AFTER_OPEN_MS;
    if (isTooSoon || isSendingRef.current) {
      return;
    }
    isSendingRef.current = true;
    try {
      await send();
    } finally {
      isSendingRef.current = false;
    }
  }, [send]);
}

import {
  useCallback,
  useLayoutEffect,
  useRef,
  useState,
} from '@wordpress/element';

const IGNORE_CLICKS_AFTER_OPEN_MS = 500;

type GuardedSend = {
  send: (event: { detail: number }) => Promise<void>;
  isSending: boolean;
};

/**
 * Wraps the send panel’s send action so that one click sends one email.
 *
 * The panel’s Send button opens where the “Review & send” button was, so the
 * second click of a double-click on “Review & send” lands on Send. That click
 * is ignored: it comes right after the panel opens, or the browser counts it
 * as the second click of a double-click (`detail` is 2 or more), which also
 * covers a slow double-click speed set in the OS. Clicks while a send is
 * running are ignored too.
 */
export function useGuardedSend(
  isOpen: boolean,
  send: () => Promise<void>,
): GuardedSend {
  const openedAtRef = useRef(0);
  // The ref blocks a click that arrives before React re-renders with the
  // new state; the state shows the button as busy.
  const isSendingRef = useRef(false);
  const [isSending, setIsSending] = useState(false);

  // Before paint, so the Send button can't be clicked before the time is set.
  useLayoutEffect(() => {
    if (isOpen) {
      openedAtRef.current = performance.now();
    }
  }, [isOpen]);

  const guardedSend = useCallback(
    async (event: { detail: number }) => {
      const isTooSoon =
        performance.now() - openedAtRef.current < IGNORE_CLICKS_AFTER_OPEN_MS;
      if (isTooSoon || event.detail > 1 || isSendingRef.current) {
        return;
      }
      isSendingRef.current = true;
      setIsSending(true);
      try {
        await send();
      } finally {
        isSendingRef.current = false;
        setIsSending(false);
      }
    },
    [send],
  );

  return { send: guardedSend, isSending };
}

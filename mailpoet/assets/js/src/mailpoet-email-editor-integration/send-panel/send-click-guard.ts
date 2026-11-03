const SEND_CLICK_GUARD_MS = 500;

/**
 * The send panel's Send button opens where the "Review & send" button was,
 * so the second click of a double-click on "Review & send" would send the
 * email. Clicks this soon after the panel opens are ignored.
 */
export function isSendClickTooSoon(openedAt: number, now: number): boolean {
  return now - openedAt < SEND_CLICK_GUARD_MS;
}

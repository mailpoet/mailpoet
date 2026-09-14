/**
 * The denominator for open and click rates, worked out from the total shown
 * next to it so the rate stays right while a campaign is still sending.
 */
export function getTrackedSent(totalSent: number, notTracked: number): number {
  return Math.max(0, totalSent - notTracked);
}

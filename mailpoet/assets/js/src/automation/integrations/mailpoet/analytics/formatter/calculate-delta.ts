import { CurrentAndPrevious } from '../store';

export function calculateDelta(current: number, previous: number): number {
  if (!previous) {
    return 0;
  }
  return ((current - previous) / previous) * 100;
}

/**
 * Coverage is reported as 100% for a period that sent nothing, so without a previous period
 * that sent something there is nothing to compare and the card should say so itself.
 */
export function calculateCoverageDelta(
  coverage: CurrentAndPrevious | undefined,
  sent: CurrentAndPrevious | undefined,
): number | undefined {
  if (!coverage || !sent?.previous) {
    return undefined;
  }
  return calculateDelta(coverage.current, coverage.previous);
}

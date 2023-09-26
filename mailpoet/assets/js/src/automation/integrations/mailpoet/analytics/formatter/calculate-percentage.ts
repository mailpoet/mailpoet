export function calculatePercentage(
  value: number,
  base: number,
  canBeNegative = false,
): number {
  if (base === 0) {
    return 0;
  }
  const percentage = (value * 100) / base;
  return canBeNegative ? percentage - 100 : percentage;
}

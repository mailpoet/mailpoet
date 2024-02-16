export const validateInTheLastParam = (
  args: Record<string, unknown>,
): boolean => {
  if (args.params === undefined) {
    return true;
  }

  if (typeof args.params !== 'object' || !('in_the_last' in args.params)) {
    return false;
  }

  const inTheLast = args.params.in_the_last;
  return (
    typeof inTheLast === 'object' &&
    'number' in inTheLast &&
    'unit' in inTheLast &&
    typeof inTheLast.number === 'number' &&
    inTheLast.number > 0 &&
    inTheLast.unit === 'days'
  );
};

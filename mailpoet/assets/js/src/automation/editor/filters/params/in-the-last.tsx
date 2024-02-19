import { __, sprintf } from '@wordpress/i18n';

type Param = {
  number: number;
  unit: 'days';
};

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

export const formatInTheLastParam = (
  args: Record<string, unknown>,
): string | undefined => {
  if (args.params === undefined) {
    return undefined;
  }

  const isValid = validateInTheLastParam(args);
  if (!isValid) {
    return undefined;
  }

  const params = args.params as { in_the_last: Param };
  return sprintf(
    // translators: %d is a number and %s is a unit of time (days, weeks, months, years)
    __('in the last %d %s', 'mailpoet'),
    params.in_the_last.number,
    __('days', 'mailpoet'),
  );
};

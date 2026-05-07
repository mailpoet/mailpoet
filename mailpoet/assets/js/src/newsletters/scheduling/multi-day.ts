type DayLabels = Record<string, string>;
type DayValue = string | number | null | undefined;

// Monday for weekDayValues (0..6), 1st for monthDayValues (1..28).
// Used as the fallback when no site-specific day is available, no day is
// selected, or the stored value is invalid.
export const DEFAULT_DAY = '1';
const WEEK_DAY_KEYS = ['0', '1', '2', '3', '4', '5', '6'];

// Numeric-string sort: every entry must parse to a finite number
// (weekDayValues 0..6 and monthDayValues 1..28). Non-numeric inputs
// like nthWeekDayValues' 'L' coerce to NaN and produce an unstable
// order, so this helper must not be used for those.
const sortNumericKeys = (values: string[]): string[] =>
  [...values].sort(
    (firstValue, secondValue) => Number(firstValue) - Number(secondValue),
  );

const normalizeValue = (value: DayValue): string =>
  value === undefined || value === null ? '' : String(value).trim();

export const getDefaultWeekDay = (weekStartsOn: DayValue): string => {
  const normalizedWeekStartsOn = normalizeValue(weekStartsOn);

  return WEEK_DAY_KEYS.includes(normalizedWeekStartsOn)
    ? normalizedWeekStartsOn
    : DEFAULT_DAY;
};

export const getOrderedWeekDayKeys = (weekStartsOn: DayValue): string[] => {
  const defaultWeekDay = getDefaultWeekDay(weekStartsOn);
  const defaultWeekDayIndex = WEEK_DAY_KEYS.indexOf(defaultWeekDay);

  return [
    ...WEEK_DAY_KEYS.slice(defaultWeekDayIndex),
    ...WEEK_DAY_KEYS.slice(0, defaultWeekDayIndex),
  ];
};

const sortByValueOrder = (values: string[], valueOrder: string[]): string[] =>
  [...values].sort((firstValue, secondValue) => {
    const firstIndex = valueOrder.indexOf(firstValue);
    const secondIndex = valueOrder.indexOf(secondValue);

    if (firstIndex === -1 || secondIndex === -1) {
      return Number(firstValue) - Number(secondValue);
    }

    return firstIndex - secondIndex;
  });

export const parseSelectedValues = (
  value: DayValue,
  defaultValue: string,
  availableValues: DayLabels,
): string[] => {
  const availableValueKeys = Object.keys(availableValues);
  const selectedValues = normalizeValue(value)
    .split(',')
    .map((selectedValue) => selectedValue.trim())
    .filter((selectedValue) => availableValueKeys.includes(selectedValue));

  if (selectedValues.length === 0) {
    return [defaultValue];
  }

  return sortNumericKeys([...new Set(selectedValues)]);
};

export const serializeSelectedValues = (values: string[]): string =>
  sortNumericKeys(values).join(',');

export const formatSelectedValues = (
  value: DayValue,
  labels: DayLabels,
  defaultValue: string,
  valueOrder: string[] = sortNumericKeys(Object.keys(labels)),
): string =>
  sortByValueOrder(parseSelectedValues(value, defaultValue, labels), valueOrder)
    .map((key) => labels[key])
    // English-style ", " joiner. wp.i18n has no locale-aware list
    // formatter, so locales like Japanese (e.g. "、") render with the
    // Latin separator until that gap is filled.
    .join(', ');

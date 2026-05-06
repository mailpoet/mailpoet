type DayLabels = Record<string, string>;
type DayValue = string | number | null | undefined;

// Monday for weekDayValues (0..6), 1st for monthDayValues (1..28).
// Used as the fallback when no day is selected or the stored value
// is invalid.
export const DEFAULT_DAY = '1';

// Numeric-string sort: every entry must parse to a finite number
// (weekDayValues 0..6 and monthDayValues 1..28). Non-numeric inputs
// like nthWeekDayValues' 'L' coerce to NaN and produce an unstable
// order, so this helper must not be used for those.
const sortNumericKeys = (values: string[]): string[] =>
  [...values].sort(
    (firstValue, secondValue) => Number(firstValue) - Number(secondValue),
  );

export const parseSelectedValues = (
  value: DayValue,
  defaultValue: string,
  availableValues: DayLabels,
): string[] => {
  const availableValueKeys = Object.keys(availableValues);
  const normalizedValue = value === undefined || value === null ? '' : value;
  const selectedValues = String(normalizedValue)
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
): string =>
  parseSelectedValues(value, defaultValue, labels)
    .map((key) => labels[key])
    // English-style ", " joiner. wp.i18n has no locale-aware list
    // formatter, so locales like Japanese (e.g. "、") render with the
    // Latin separator until that gap is filled.
    .join(', ');

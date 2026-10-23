import { isValid, parseISO } from 'date-fns';
import { formatPickedDate } from 'common/datepicker/format-picked-date';

export const convertDateToString = (
  value: Date | [Date, Date] | null,
): string | undefined => {
  if (value === null) {
    return undefined;
  }
  if (Array.isArray(value)) {
    throw new Error(
      'convertDateToString can process only single date array given',
    );
  }
  return formatPickedDate(value);
};

export const parseDate = (value: string): Date | undefined => {
  if (!value) return undefined;
  const date = parseISO(value);
  if (!isValid(date)) return undefined;
  return date;
};

import { isValid, parseISO } from 'date-fns';
import { MailPoet } from 'mailpoet';

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
  return MailPoet.Date.format(value, { format: 'Y-m-d' });
};

export const parseDate = (value: string): Date | undefined => {
  if (!value) return undefined;
  const date = parseISO(value);
  if (!isValid(date)) return undefined;
  return date;
};

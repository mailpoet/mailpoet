import { formatPickedDate } from 'common/datepicker/format-picked-date';

type ShortcodeAttributes = {
  name: string;
  segments: unknown[];
  startDate: Date | null;
  endDate: Date | null;
  lastNDays: string;
  subjectContains: string;
  limit: string;
};

export const buildShortcode = ({
  name,
  segments,
  startDate,
  endDate,
  lastNDays,
  subjectContains,
  limit,
}: ShortcodeAttributes): string => {
  let shortcode = `[${name}`;

  if (segments.length) {
    shortcode += ` segments="${segments.join(',')}"`;
  }

  if (startDate) {
    shortcode += ` start_date="${formatPickedDate(startDate)}"`;
  }

  if (endDate) {
    shortcode += ` end_date="${formatPickedDate(endDate)}"`;
  }

  if (lastNDays && parseInt(lastNDays, 10) > 0) {
    shortcode += ` in_the_last_days="${lastNDays}"`;
  }

  if (subjectContains && subjectContains.length > 0) {
    shortcode += ` subject_contains="${subjectContains}"`;
  }

  if (limit && parseInt(limit, 10) > 0) {
    shortcode += ` limit="${limit}"`;
  }

  return `${shortcode}]`;
};

import { dateI18n, getDate, getSettings } from '@wordpress/date';

/**
 * Formats a scheduled_at value (a timezone-less `Y-m-d\TH:i:s` string in the
 * site's timezone) for display. Uses `getDate()` to parse the string *in the
 * site zone* rather than converting a browser-local instant to it — the two
 * only agree when the browser and site share a timezone.
 */
export function formatScheduledDate(scheduledDate: string): string {
  const settings = getSettings();
  return dateI18n(
    settings.formats.datetime,
    getDate(scheduledDate),
    settings.timezone.string,
  );
}

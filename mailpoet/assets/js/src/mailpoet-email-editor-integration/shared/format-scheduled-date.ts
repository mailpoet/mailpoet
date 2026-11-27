import { dateI18n, getDate, getSettings, gmdateI18n } from '@wordpress/date';

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

/**
 * Formats a local date and time that has no time zone of its own, such as a
 * send time in each subscriber's time zone. Formatting in UTC keeps the wall
 * clock as picked, even when it falls in a clock-change gap of the site zone.
 */
export function formatLocalScheduledDate(date: string, time: string): string {
  return gmdateI18n(getSettings().formats.datetime, `${date}T${time}Z`);
}

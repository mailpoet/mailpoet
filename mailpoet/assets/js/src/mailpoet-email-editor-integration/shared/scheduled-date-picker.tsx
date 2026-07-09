import { DateTimePicker } from '@wordpress/components';
import { _x } from '@wordpress/i18n';
import { getSettings } from '@wordpress/date';
import {
  DEFAULT_SCHEDULED_LOCAL_TIME,
  SCHEDULE_MODE_SUBSCRIBER_TIMEZONE,
  getTomorrowLocalDate,
  snapLocalDateTimeToQuarterHour,
} from 'common/newsletter-schedule-mode';
import { useScheduledDate } from './use-scheduled-date';

/**
 * The date & time picker used to choose when an email is sent. Shared between
 * the scheduled sidebar row and the review & send panel. In subscriber
 * timezone mode it edits the local wall-clock date and time instead of the
 * website datetime, snapping the time to the 15-minute steps the server
 * validates.
 */
export function ScheduledDatePicker() {
  const {
    scheduledDate,
    setScheduledDate,
    scheduleMode,
    scheduledLocalDate,
    scheduledLocalTime,
    setScheduledLocalDateTime,
  } = useScheduledDate();
  const settings = getSettings();
  const isSubscriberTimezoneMode =
    scheduleMode === SCHEDULE_MODE_SUBSCRIBER_TIMEZONE;

  const is12HourTime = /a(?!\\)/i.test(
    settings.formats.time
      .toLowerCase() // Test only the lower case a.
      .replace(/\\\\/g, '') // Replace "//" with empty strings.
      .split('')
      .reverse()
      .join(''), // Reverse the string and test for "a" not followed by a slash.
  );
  // Used for comparing today with DateTimePicker dates to determine validity.
  // We set the hours to 0:00:00 to match the time format of DateTimePicker dates.
  const today = new Date().setHours(0, 0, 0, 0);

  const currentDate = isSubscriberTimezoneMode
    ? `${scheduledLocalDate || getTomorrowLocalDate()}T${
        scheduledLocalTime || DEFAULT_SCHEDULED_LOCAL_TIME
      }`
    : scheduledDate;

  const handleChange = (newDate: string | null) => {
    if (!isSubscriberTimezoneMode) {
      setScheduledDate(newDate);
      return;
    }
    const snapped = newDate ? snapLocalDateTimeToQuarterHour(newDate) : null;
    if (snapped) {
      setScheduledLocalDateTime(snapped.localDate, snapped.localTime);
    }
  };

  return (
    <DateTimePicker
      currentDate={currentDate}
      onChange={handleChange}
      dateOrder={
        /* translators: Order of day, month, and year. Available formats are 'dmy', 'mdy', and 'ymd'. */
        _x('dmy', 'date order', 'mailpoet') as 'dmy' | 'mdy' | 'ymd'
      }
      is12Hour={is12HourTime}
      isInvalidDate={(date) => date.getTime() < today}
    />
  );
}

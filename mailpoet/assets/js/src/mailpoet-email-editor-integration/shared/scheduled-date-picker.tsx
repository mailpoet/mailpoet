import { DateTimePicker } from '@wordpress/components';
import { _x } from '@wordpress/i18n';
import { getSettings } from '@wordpress/date';
import { useScheduledDate } from './use-scheduled-date';

/**
 * The date & time picker used to choose when an email is sent. Shared between
 * the scheduled sidebar row and the review & send panel.
 */
export function ScheduledDatePicker() {
  const { scheduledDate, setScheduledDate } = useScheduledDate();
  const settings = getSettings();

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

  return (
    <DateTimePicker
      currentDate={scheduledDate}
      onChange={(newDate) => setScheduledDate(newDate)}
      dateOrder={
        /* translators: Order of day, month, and year. Available formats are 'dmy', 'mdy', and 'ymd'. */
        _x('dmy', 'date order', 'mailpoet') as 'dmy' | 'mdy' | 'ymd'
      }
      is12Hour={is12HourTime}
      isInvalidDate={(date) => date.getTime() < today}
    />
  );
}

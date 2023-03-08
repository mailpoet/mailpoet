import _ from 'underscore';
import { __, _x } from '@wordpress/i18n';
import { MailPoet } from 'mailpoet';

const timeFormat = window.mailpoet_time_format || 'H:i';

// welcome emails
const timeDelayValues = {
  immediate: __('immediately', 'mailpoet'),
  minutes: __('minute(s) later', 'mailpoet'),
  hours: __('hour(s) later', 'mailpoet'),
  days: __('day(s) later', 'mailpoet'),
  weeks: __('week(s) later', 'mailpoet'),
};

const intervalValues = {
  daily: __('Once a day at...', 'mailpoet'),
  weekly: __('Weekly on...', 'mailpoet'),
  monthly: __('Monthly on the...', 'mailpoet'),
  nthWeekDay: __('Monthly every...', 'mailpoet'),
  immediately: __('Immediately', 'mailpoet'),
};

// notification emails
const SECONDS_IN_DAY = 86400;
const TIME_STEP_SECONDS = 900; // 15 minutes
const numberOfTimeSteps = SECONDS_IN_DAY / TIME_STEP_SECONDS;

const timeOfDayValues = _.object(
  _.map(
    _.times(numberOfTimeSteps, (step) => step * TIME_STEP_SECONDS),
    (seconds) => {
      const date = new Date(null);
      date.setSeconds(seconds);
      const timeLabel = MailPoet.Date.format(date, {
        format: timeFormat,
        offset: 0,
      });
      return [seconds, timeLabel];
    },
  ),
);

const weekDayValues = {
  0: __('Sunday', 'mailpoet'),
  1: __('Monday', 'mailpoet'),
  2: __('Tuesday', 'mailpoet'),
  3: __('Wednesday', 'mailpoet'),
  4: __('Thursday', 'mailpoet'),
  5: __('Friday', 'mailpoet'),
  6: __('Saturday', 'mailpoet'),
};

const NUMBER_OF_DAYS_IN_MONTH = 28;
const monthDayValues = _.object(
  _.map(
    _.times(NUMBER_OF_DAYS_IN_MONTH, (day) => day),
    (day) => {
      const labels = {
        0: __('1st', 'mailpoet'),
        1: __('2nd', 'mailpoet'),
        2: __('3rd', 'mailpoet'),
      };
      let label;
      if (labels[day] !== undefined) {
        label = labels[day];
      } else {
        label = __('%1$dth', 'mailpoet').replace('%1$d', day + 1);
      }
      return [day + 1, label];
    },
  ),
);

const nthWeekDayValues = {
  1: __('1st', 'mailpoet'),
  2: __('2nd', 'mailpoet'),
  3: __('3rd', 'mailpoet'),
  L: _x('last', 'e.g. monthly every last Monday', 'mailpoet'),
};

export { timeDelayValues };
export { intervalValues };
export { timeOfDayValues };
export { weekDayValues };
export { monthDayValues };
export { nthWeekDayValues };

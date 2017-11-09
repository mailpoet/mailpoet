import _ from 'underscore';
import MailPoet from 'mailpoet';

const timeFormat = window.mailpoet_time_format || 'H:i';

// welcome emails
const timeDelayValues = {
  immediate: MailPoet.I18n.t('delayImmediately'),
  hours: MailPoet.I18n.t('delayHoursAfter'),
  days: MailPoet.I18n.t('delayDaysAfter'),
  weeks: MailPoet.I18n.t('delayWeeksAfter'),
};

const intervalValues = {
  daily: MailPoet.I18n.t('daily'),
  weekly: MailPoet.I18n.t('weekly'),
  monthly: MailPoet.I18n.t('monthly'),
  nthWeekDay: MailPoet.I18n.t('monthlyEvery'),
  immediately: MailPoet.I18n.t('immediately'),
};

// notification emails
const SECONDS_IN_DAY = 86400;
const TIME_STEP_SECONDS = 3600;
const numberOfTimeSteps = SECONDS_IN_DAY / TIME_STEP_SECONDS;

const timeOfDayValues = _.object(_.map(
  _.times(numberOfTimeSteps, (step) => {
    return step * TIME_STEP_SECONDS;
  }), (seconds) => {
  const date = new Date(null);
  date.setSeconds(seconds);
  const timeLabel = MailPoet.Date.format(date, { format: timeFormat, offset: 0 });
  return [seconds, timeLabel];
})
);

const weekDayValues = {
  0: MailPoet.I18n.t('sunday'),
  1: MailPoet.I18n.t('monday'),
  2: MailPoet.I18n.t('tuesday'),
  3: MailPoet.I18n.t('wednesday'),
  4: MailPoet.I18n.t('thursday'),
  5: MailPoet.I18n.t('friday'),
  6: MailPoet.I18n.t('saturday'),
};

const NUMBER_OF_DAYS_IN_MONTH = 28;
const monthDayValues = _.object(
  _.map(
    _.times(NUMBER_OF_DAYS_IN_MONTH, (day) => {
      return day;
    }), (day) => {
    const labels = {
      0: MailPoet.I18n.t('first'),
      1: MailPoet.I18n.t('second'),
      2: MailPoet.I18n.t('third'),
    };
    let label;
    if (labels[day] !== undefined) {
      label = labels[day];
    } else {
      label = MailPoet.I18n.t('nth').replace('%$1d', day + 1);
    }
    return [day + 1, label];
  }
  )
);

const nthWeekDayValues = {
  1: MailPoet.I18n.t('first'),
  2: MailPoet.I18n.t('second'),
  3: MailPoet.I18n.t('third'),
  L: MailPoet.I18n.t('last'),
};

export { timeDelayValues };
export { intervalValues };
export { timeOfDayValues };
export { weekDayValues };
export { monthDayValues };
export { nthWeekDayValues };

import { __, _x } from '@wordpress/i18n';
import { registerLocale } from 'react-datepicker';
import type { Day } from 'date-fns';
import { enUS } from 'date-fns/locale/en-US';
import { MailPoet } from 'mailpoet';

/**
 * This function is a copy of the buildLocalizeFn function from date-fns (date-fns/locale/_lib/buildLocalizeFn)
 * After 3.0.0 the package contains exports which prevents us from including it via import form a file.
 */
function buildLocalizeFn(args) {
  return (value, options) => {
    const context = options?.context ? String(options.context) : 'standalone';

    let valuesArray;
    if (context === 'formatting' && args.formattingValues) {
      const defaultWidth = args.defaultFormattingWidth || args.defaultWidth;
      const width = options?.width ? String(options.width) : defaultWidth;

      valuesArray =
        args.formattingValues[width] || args.formattingValues[defaultWidth];
    } else {
      const defaultWidth = args.defaultWidth;
      const width = options?.width ? String(options.width) : args.defaultWidth;

      valuesArray = args.values[width] || args.values[defaultWidth];
    }
    const index = args.argumentCallback ? args.argumentCallback(value) : value;

    // eslint-disable-next-line @typescript-eslint/no-unsafe-return
    return valuesArray[index];
  };
}

const monthValues = {
  abbreviated: [
    __('Jan', 'mailpoet'),
    __('Feb', 'mailpoet'),
    __('Mar', 'mailpoet'),
    __('Apr', 'mailpoet'),
    __('May', 'mailpoet'),
    __('Jun', 'mailpoet'),
    __('Jul', 'mailpoet'),
    __('Aug', 'mailpoet'),
    __('Sep', 'mailpoet'),
    __('Oct', 'mailpoet'),
    __('Nov', 'mailpoet'),
    __('Dec', 'mailpoet'),
  ],
  wide: [
    __('January', 'mailpoet'),
    __('February', 'mailpoet'),
    __('March', 'mailpoet'),
    __('April', 'mailpoet'),
    __('May', 'mailpoet'),
    __('June', 'mailpoet'),
    __('July', 'mailpoet'),
    __('August', 'mailpoet'),
    __('September', 'mailpoet'),
    __('October', 'mailpoet'),
    __('November', 'mailpoet'),
    __('December', 'mailpoet'),
  ],
};

const dayValues = {
  narrow: [
    _x('S', 'Sunday - one letter abbreviation', 'mailpoet'),
    _x('M', 'Monday - one letter abbreviation', 'mailpoet'),
    _x('T', 'Tuesday - one letter abbreviation', 'mailpoet'),
    _x('W', 'Wednesday - one letter abbreviation', 'mailpoet'),
    _x('T', 'Thursday - one letter abbreviation', 'mailpoet'),
    _x('F', 'Friday - one letter abbreviation', 'mailpoet'),
    _x('S', 'Saturday - one letter abbreviation', 'mailpoet'),
  ],
  abbreviated: [
    __('Sun', 'mailpoet'),
    __('Mon', 'mailpoet'),
    __('Tue', 'mailpoet'),
    __('Wed', 'mailpoet'),
    __('Thu', 'mailpoet'),
    __('Fri', 'mailpoet'),
    __('Sat', 'mailpoet'),
  ],
  wide: [
    __('Sunday', 'mailpoet'),
    __('Monday', 'mailpoet'),
    __('Tuesday', 'mailpoet'),
    __('Wednesday', 'mailpoet'),
    __('Thursday', 'mailpoet'),
    __('Friday', 'mailpoet'),
    __('Saturday', 'mailpoet'),
  ],
};

const weekStartsOn: Day =
  typeof MailPoet.wpWeekStartsOn !== 'undefined'
    ? (Number(MailPoet.wpWeekStartsOn) as Day)
    : 1;

registerLocale('mailpoet', {
  ...enUS,
  localize: {
    ...enUS.localize,
    month: buildLocalizeFn({ values: monthValues, defaultWidth: 'wide' }),
    day: buildLocalizeFn({ values: dayValues, defaultWidth: 'wide' }),
  },
  options: { ...enUS.options, weekStartsOn },
});

export { weekStartsOn };

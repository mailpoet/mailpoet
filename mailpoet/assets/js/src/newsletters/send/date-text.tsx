import { Component, SyntheticEvent } from 'react';
import { __, _x } from '@wordpress/i18n';
import { registerLocale } from 'react-datepicker';
import locale from 'date-fns/locale/en-US';
import { Datepicker } from 'common/datepicker/datepicker';
import { MailPoet } from 'mailpoet';
import { DateOptions } from 'date';

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

locale.localize.month = buildLocalizeFn({
  values: monthValues,
  defaultWidth: 'wide',
});
locale.localize.day = buildLocalizeFn({
  values: dayValues,
  defaultWidth: 'wide',
});
locale.options.weekStartsOn =
  typeof MailPoet.wpWeekStartsOn !== 'undefined' ? MailPoet.wpWeekStartsOn : 1;

registerLocale('mailpoet', locale);

type DateTextEvent = SyntheticEvent<HTMLInputElement> & {
  target: EventTarget & {
    name?: string;
    value?: string;
  };
};

type DateTextProps = {
  displayFormat: string;
  onChange: (date: DateTextEvent) => void;
  storageFormat: string;
  value: string;
  disabled: boolean;
  validation: {
    'data-parsley-required': boolean;
    'data-parsley-required-message': string;
    'data-parsley-errors-container': string;
  };
  maxDate: Date;
  name?: string;
};

class DateText extends Component<DateTextProps> {
  onChange = (value: Date, event) => {
    const changeEvent: DateTextEvent = event;
    // Swap display format to storage format
    const storageDate = this.getStorageDate(value);

    changeEvent.target.name = this.getFieldName();
    changeEvent.target.value = storageDate;
    this.props.onChange(changeEvent);
  };

  getFieldName = () => this.props.name || 'date';

  getDisplayDateFormat = (format: string) => {
    const convertedFormat = MailPoet.Date.convertFormat(format);
    // Convert moment format to date-fns, see: https://git.io/fxCyr
    return convertedFormat
      .replace(/D/g, 'd')
      .replace(/Y/g, 'y')
      .replace(/A/g, 'a')
      .replace(/o/g, 'Y') // MailPoet.Date.convertFormat converts 'S' to 'o'
      .replace(/\[/g, '')
      .replace(/\]/g, '');
  };

  getDate = (date: string) => {
    const formatting = {
      parseFormat: this.props.storageFormat,
    } as DateOptions;
    return MailPoet.Date.toDate(date, formatting);
  };

  getStorageDate = (date: Date) => {
    const formatting = {
      format: this.props.storageFormat,
    };
    return MailPoet.Date.formatFromGmt(date, formatting);
  };

  render() {
    return (
      <Datepicker
        name={this.getFieldName()}
        selected={this.getDate(this.props.value)}
        dateFormat={this.getDisplayDateFormat(this.props.displayFormat)}
        disabled={this.props.disabled}
        onChange={this.onChange}
        minDate={this.getDate(window.mailpoet_current_date)}
        maxDate={this.props.maxDate}
        locale="mailpoet"
        {...this.props.validation}
      />
    );
  }
}

export { DateText };

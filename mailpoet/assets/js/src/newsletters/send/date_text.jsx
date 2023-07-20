import { Component } from 'react';
import { __, _x } from '@wordpress/i18n';
import PropTypes from 'prop-types';
import { registerLocale } from 'react-datepicker';
import locale from 'date-fns/locale/en-US';
import buildLocalizeFn from 'date-fns/locale/_lib/buildLocalizeFn';

import { Datepicker } from 'common/datepicker/datepicker.tsx';
import { MailPoet } from 'mailpoet';

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

class DateText extends Component {
  onChange = (value, event) => {
    const changeEvent = event;
    // Swap display format to storage format
    const storageDate = this.getStorageDate(value);

    changeEvent.target.name = this.getFieldName();
    changeEvent.target.value = storageDate;
    this.props.onChange(changeEvent);
  };

  getFieldName = () => this.props.name || 'date';

  getDisplayDateFormat = (format) => {
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

  getDate = (date) => {
    const formatting = {
      parseFormat: this.props.storageFormat,
    };
    return MailPoet.Date.toDate(date, formatting);
  };

  getStorageDate = (date) => {
    const formatting = {
      format: this.props.storageFormat,
    };
    return MailPoet.Date.format(date, formatting);
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

DateText.propTypes = {
  displayFormat: PropTypes.string.isRequired,
  onChange: PropTypes.func.isRequired,
  name: PropTypes.string,
  storageFormat: PropTypes.string.isRequired,
  value: PropTypes.string.isRequired,
  disabled: PropTypes.bool.isRequired,
  validation: PropTypes.shape({
    'data-parsley-required': PropTypes.bool,
    'data-parsley-required-message': PropTypes.string,
    'data-parsley-type': PropTypes.string,
    'data-parsley-errors-container': PropTypes.string,
    maxLength: PropTypes.number,
  }).isRequired,
  maxDate: PropTypes.instanceOf(Date),
};

DateText.defaultProps = {
  name: 'date',
  maxDate: null,
};
DateText.displayName = 'DateText';
export { DateText };

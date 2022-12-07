import { Component } from 'react';
import PropTypes from 'prop-types';
import { registerLocale } from 'react-datepicker';
import locale from 'date-fns/locale/en-US';
import buildLocalizeFn from 'date-fns/locale/_lib/buildLocalizeFn';

import { Datepicker } from 'common/datepicker/datepicker.tsx';
import { MailPoet } from 'mailpoet';

const monthValues = {
  abbreviated: [
    MailPoet.I18n.t('januaryShort'),
    MailPoet.I18n.t('februaryShort'),
    MailPoet.I18n.t('marchShort'),
    MailPoet.I18n.t('aprilShort'),
    MailPoet.I18n.t('mayShort'),
    MailPoet.I18n.t('juneShort'),
    MailPoet.I18n.t('julyShort'),
    MailPoet.I18n.t('augustShort'),
    MailPoet.I18n.t('septemberShort'),
    MailPoet.I18n.t('octoberShort'),
    MailPoet.I18n.t('novemberShort'),
    MailPoet.I18n.t('decemberShort'),
  ],
  wide: [
    MailPoet.I18n.t('january'),
    MailPoet.I18n.t('february'),
    MailPoet.I18n.t('march'),
    MailPoet.I18n.t('april'),
    MailPoet.I18n.t('may'),
    MailPoet.I18n.t('june'),
    MailPoet.I18n.t('july'),
    MailPoet.I18n.t('august'),
    MailPoet.I18n.t('september'),
    MailPoet.I18n.t('october'),
    MailPoet.I18n.t('november'),
    MailPoet.I18n.t('december'),
  ],
};

const dayValues = {
  narrow: [
    MailPoet.I18n.t('sundayMin'),
    MailPoet.I18n.t('mondayMin'),
    MailPoet.I18n.t('tuesdayMin'),
    MailPoet.I18n.t('wednesdayMin'),
    MailPoet.I18n.t('thursdayMin'),
    MailPoet.I18n.t('fridayMin'),
    MailPoet.I18n.t('saturdayMin'),
  ],
  abbreviated: [
    MailPoet.I18n.t('sundayShort'),
    MailPoet.I18n.t('mondayShort'),
    MailPoet.I18n.t('tuesdayShort'),
    MailPoet.I18n.t('wednesdayShort'),
    MailPoet.I18n.t('thursdayShort'),
    MailPoet.I18n.t('fridayShort'),
    MailPoet.I18n.t('saturdayShort'),
  ],
  wide: [
    MailPoet.I18n.t('sunday'),
    MailPoet.I18n.t('monday'),
    MailPoet.I18n.t('tuesday'),
    MailPoet.I18n.t('wednesday'),
    MailPoet.I18n.t('thursday'),
    MailPoet.I18n.t('friday'),
    MailPoet.I18n.t('saturday'),
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

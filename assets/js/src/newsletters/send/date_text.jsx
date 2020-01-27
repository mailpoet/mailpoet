import React from 'react';
import jQuery from 'jquery';
import _ from 'underscore';
import MailPoet from 'mailpoet';
import PropTypes from 'prop-types';

const datepickerTranslations = {
  closeText: MailPoet.I18n.t('close'),
  currentText: MailPoet.I18n.t('today'),
  nextText: MailPoet.I18n.t('next'),
  prevText: MailPoet.I18n.t('previous'),
  monthNames: [
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
  monthNamesShort: [
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
  dayNames: [
    MailPoet.I18n.t('sunday'),
    MailPoet.I18n.t('monday'),
    MailPoet.I18n.t('tuesday'),
    MailPoet.I18n.t('wednesday'),
    MailPoet.I18n.t('thursday'),
    MailPoet.I18n.t('friday'),
    MailPoet.I18n.t('saturday'),
  ],
  dayNamesShort: [
    MailPoet.I18n.t('sundayShort'),
    MailPoet.I18n.t('mondayShort'),
    MailPoet.I18n.t('tuesdayShort'),
    MailPoet.I18n.t('wednesdayShort'),
    MailPoet.I18n.t('thursdayShort'),
    MailPoet.I18n.t('fridayShort'),
    MailPoet.I18n.t('saturdayShort'),
  ],
  dayNamesMin: [
    MailPoet.I18n.t('sundayMin'),
    MailPoet.I18n.t('mondayMin'),
    MailPoet.I18n.t('tuesdayMin'),
    MailPoet.I18n.t('wednesdayMin'),
    MailPoet.I18n.t('thursdayMin'),
    MailPoet.I18n.t('fridayMin'),
    MailPoet.I18n.t('saturdayMin'),
  ],
};

class DateText extends React.Component {
  constructor(props) {
    super(props);
    this.dateInput = React.createRef();
  }

  componentDidMount() {
    const $element = jQuery(this.dateInput.current);
    const that = this;
    if ($element.datepicker) {
      // Override jQuery UI datepicker Date parsing and formatting
      jQuery.datepicker.parseDate = function parseDate(format, value) {
        // Transform string format to Date object
        return MailPoet.Date.toDate(value, {
          parseFormat: this.props.displayFormat,
          format,
        });
      };
      jQuery.datepicker.formatDate = function formatDate(format, value) {
        // Transform Date object to string format
        const newValue = MailPoet.Date.format(value, {
          format,
        });
        return newValue;
      };

      $element.datepicker(_.extend({
        dateFormat: this.props.displayFormat,
        firstDay: window.mailpoet_start_of_week,
        isRTL: false,
        onSelect: function onSelect(value) {
          that.onChange({
            target: {
              name: that.getFieldName(),
              value,
            },
          });
        },
      }, datepickerTranslations));

      this.datepickerInitialized = true;
    }
  }

  componentWillUnmount() {
    if (this.datepickerInitialized) {
      jQuery(this.dateInput.current).datepicker('destroy');
    }
  }

  onChange = (event) => {
    const changeEvent = event;
    // Swap display format to storage format
    const displayDate = changeEvent.target.value;
    const storageDate = this.getStorageDate(displayDate);

    changeEvent.target.value = storageDate;
    this.props.onChange(changeEvent);
  };

  getFieldName = () => this.props.name || 'date';

  getDisplayDate = (date) => {
    const formatting = {
      parseFormat: this.props.storageFormat,
      format: this.props.displayFormat,
    };
    return MailPoet.Date.format(date, formatting);
  };

  getStorageDate = (date) => {
    const formatting = {
      parseFormat: this.props.displayFormat,
      format: this.props.storageFormat,
    };
    return MailPoet.Date.format(date, formatting);
  };

  render() {
    return (
      <input
        type="text"
        size="30"
        name={this.getFieldName()}
        value={this.getDisplayDate(this.props.value)}
        readOnly
        disabled={this.props.disabled}
        onChange={this.onChange}
        ref={this.dateInput}
        {...this.props.validation} // eslint-disable-line react/jsx-props-no-spreading
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
  validation: PropTypes.object.isRequired, // eslint-disable-line react/forbid-prop-types
};

DateText.defaultProps = {
  name: 'date',
};

export default DateText;

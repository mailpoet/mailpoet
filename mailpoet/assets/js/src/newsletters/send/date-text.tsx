import { Component, SyntheticEvent } from 'react';
import Moment from 'moment';
import { Datepicker } from 'common/datepicker/datepicker';
import { MailPoet } from 'mailpoet';
import { momentToDateFnsFormat } from './date-fns-format';

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
  onChange = (value: Date | null) => {
    // Clearing the input gives null; keep the last valid date
    if (!value) return;
    // Swap display format to storage format
    this.props.onChange({
      target: {
        name: this.getFieldName(),
        value: this.getAsStringInFormat(value),
      },
    } as DateTextEvent);
  };

  getFieldName = () => this.props.name || 'date';

  getDisplayDateFormat = (format: string) =>
    momentToDateFnsFormat(MailPoet.Date.convertFormat(format));

  getDate = (date: string) => Moment(date).toDate();

  getAsStringInFormat = (date: Date) =>
    Moment(date).format(MailPoet.Date.convertFormat(this.props.storageFormat));

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
        {...this.props.validation}
      />
    );
  }
}

export { DateText };

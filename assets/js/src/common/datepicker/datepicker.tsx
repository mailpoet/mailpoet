import React from 'react';
import classnames from 'classnames';
import ReactDatePicker, { ReactDatePickerProps } from 'react-datepicker';

type Props = ReactDatePickerProps & {
};

const DatePicker = ({
  ...props
}: Props) => (
  <div
    className={
      classnames(
        'mailpoet-datepicker mailpoet-form-input',
        {
          'mailpoet-disabled': props.disabled,
        }
      )
    }
  >
    <ReactDatePicker
      useWeekdaysShort
      {...props} // eslint-disable-line react/jsx-props-no-spreading
    />
  </div>
);

export default DatePicker;

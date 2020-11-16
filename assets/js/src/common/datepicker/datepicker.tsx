import React from 'react';
import classnames from 'classnames';
import ReactDatePicker, { ReactDatePickerProps } from 'react-datepicker';

type Props = ReactDatePickerProps & {
  dimension?: 'small';
  isFullWidth?: boolean;
  iconStart?: JSX.Element;
  iconEnd?: JSX.Element;
};

const Datepicker = ({
  dimension,
  isFullWidth,
  iconStart,
  iconEnd,
  ...props
}: Props) => (
  <div
    className={
      classnames(
        'mailpoet-datepicker mailpoet-form-input',
        {
          [`mailpoet-form-input-${dimension}`]: dimension,
          'mailpoet-disabled': props.disabled,
          'mailpoet-full-width': isFullWidth,
        }
      )
    }
  >
    {iconStart}
    <ReactDatePicker
      useWeekdaysShort
      {...props} // eslint-disable-line react/jsx-props-no-spreading
    />
    {iconEnd}
  </div>
);

export default Datepicker;

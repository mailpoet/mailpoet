import classnames from 'classnames';
import ReactDatePicker, { ReactDatePickerProps } from 'react-datepicker';
import { MailPoet } from 'mailpoet';
import { withBoundary } from '../error_boundary';

type Props = ReactDatePickerProps & {
  dimension?: 'small';
  isFullWidth?: boolean;
  iconStart?: JSX.Element;
  iconEnd?: JSX.Element;
};

function Datepicker({
  dimension,
  isFullWidth,
  iconStart,
  iconEnd,
  ...props
}: Props) {
  return (
    <div
      className={classnames('mailpoet-datepicker mailpoet-form-input', {
        [`mailpoet-form-input-${dimension}`]: dimension,
        'mailpoet-disabled': props.disabled,
        'mailpoet-full-width': isFullWidth,
      })}
    >
      {iconStart}
      <ReactDatePicker
        useWeekdaysShort
        calendarStartDay={props.calendarStartDay ?? MailPoet.wpWeekStartsOn}
        {...props}
      />
      {iconEnd}
    </div>
  );
}

Datepicker.displayName = 'Datepicker';
const DatepickerWithBoundary = withBoundary(Datepicker);

export { DatepickerWithBoundary as Datepicker };

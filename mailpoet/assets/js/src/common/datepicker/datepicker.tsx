import classnames from 'classnames';
import ReactDatePicker, { DatePickerProps } from 'react-datepicker';
import { withBoundary } from '../error-boundary';
import { weekStartsOn } from './locale';

type Props = Extract<
  DatePickerProps,
  { selectsRange?: false; selectsMultiple?: false }
> & {
  className?: string;
  dimension?: 'small';
  isFullWidth?: boolean;
  iconStart?: JSX.Element;
  iconEnd?: JSX.Element;
};

function Datepicker({
  className,
  dimension,
  isFullWidth,
  iconStart,
  iconEnd,
  ...props
}: Props) {
  return (
    <div
      className={classnames(
        className,
        'mailpoet-datepicker mailpoet-form-input',
        {
          [`mailpoet-form-input-${dimension}`]: dimension,
          'mailpoet-disabled': props.disabled,
          'mailpoet-full-width': isFullWidth,
        },
      )}
    >
      {iconStart}
      <ReactDatePicker
        useWeekdaysShort
        locale="mailpoet"
        calendarStartDay={props.calendarStartDay ?? weekStartsOn}
        {...props}
      />
      {iconEnd}
    </div>
  );
}

Datepicker.displayName = 'Datepicker';
const DatepickerWithBoundary = withBoundary(Datepicker);

export { DatepickerWithBoundary as Datepicker };

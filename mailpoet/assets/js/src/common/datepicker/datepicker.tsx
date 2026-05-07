import classnames from 'classnames';
import { forwardRef, type ComponentProps } from 'react';
import ReactDatePicker, { ReactDatePickerProps } from 'react-datepicker';
import { dateI18n, getSettings } from '@wordpress/date';
import { MailPoet } from 'mailpoet';
import { withBoundary } from '../error-boundary';

type Props = ReactDatePickerProps & {
  className?: string;
  dimension?: 'small';
  formatWithWordPressSettings?: boolean;
  isFullWidth?: boolean;
  iconStart?: JSX.Element;
  iconEnd?: JSX.Element;
};

type WordPressFormattedInputProps = ComponentProps<'input'> & {
  selectedDate?: Date | null;
};

const WordPressFormattedInput = forwardRef<
  HTMLInputElement,
  WordPressFormattedInputProps
>(({ selectedDate, value: _value, ...props }, ref) => {
  const settings = getSettings();
  const formattedValue = selectedDate
    ? dateI18n(
        settings.formats.date,
        selectedDate,
        settings.timezone.string || settings.timezone.offset,
      )
    : '';

  return <input {...props} ref={ref} value={formattedValue} readOnly />;
});
WordPressFormattedInput.displayName = 'WordPressFormattedInput';

function Datepicker({
  className,
  dimension,
  formatWithWordPressSettings,
  isFullWidth,
  iconStart,
  iconEnd,
  ...props
}: Props) {
  const selectedDate =
    props.selected instanceof Date ? props.selected : undefined;
  const datePickerProps = formatWithWordPressSettings
    ? {
        ...props,
        customInput: <WordPressFormattedInput selectedDate={selectedDate} />,
      }
    : props;

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
        calendarStartDay={props.calendarStartDay ?? MailPoet.wpWeekStartsOn}
        {...datePickerProps}
      />
      {iconEnd}
    </div>
  );
}

Datepicker.displayName = 'Datepicker';
const DatepickerWithBoundary = withBoundary(Datepicker);

export { DatepickerWithBoundary as Datepicker };

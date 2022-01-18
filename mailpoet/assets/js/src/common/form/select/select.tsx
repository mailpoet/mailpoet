import React, { SelectHTMLAttributes } from 'react';
import classnames from 'classnames';

type Props = SelectHTMLAttributes<HTMLSelectElement> & {
  children?: React.ReactNode;
  dimension?: 'small';
  isFullWidth?: boolean;
  isMinWidth?: boolean;
  iconStart?: JSX.Element;
  automationId?: string;
};

const Select = React.forwardRef(({
  children,
  dimension,
  isFullWidth,
  isMinWidth,
  iconStart,
  automationId,
  ...attributes
}: Props, ref?: React.Ref<HTMLSelectElement>) => (
  <div
    className={
      classnames(
        'mailpoet-form-input',
        'mailpoet-form-select',
        {
          [`mailpoet-form-input-${dimension}`]: dimension,
          'mailpoet-disabled': attributes.disabled,
          'mailpoet-full-width': isFullWidth,
          'mailpoet-min-width': isMinWidth,
        }
      )
    }
  >
    {iconStart}
    <select {...attributes} ref={ref} data-automation-id={automationId}>
      {children}
    </select>
  </div>
));

export default Select;

import React, { InputHTMLAttributes } from 'react';
import classnames from 'classnames';

type Props = InputHTMLAttributes<HTMLInputElement> & {
  onCheck: (isChecked: boolean) => void,
  children?: React.ReactNode,
  isFullWidth?: boolean,
  automationId?: string,
};

const Checkbox = ({
  children,
  isFullWidth,
  onCheck,
  automationId,
  ...attributes
}: Props) => (
  <label
    className={
      classnames({
        'mailpoet-form-checkbox': true,
        'mailpoet-disabled': attributes.disabled,
        'mailpoet-full-width': isFullWidth,
      })
    }
    data-automation-id={automationId}
  >
    <input
      type="checkbox"
      onChange={(e) => onCheck(e.target.checked)}
      {...attributes}
    />
    <span className="mailpoet-form-checkbox-control" />
    {children}
  </label>
);

export default Checkbox;

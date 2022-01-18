import React, { ChangeEvent, InputHTMLAttributes } from 'react';
import classnames from 'classnames';

type Props = InputHTMLAttributes<HTMLInputElement> & {
  dimension?: 'small';
  onCheck: (isChecked: boolean, event: ChangeEvent) => void;
  automationId?: string;
};

const Toggle = ({
  dimension,
  onCheck,
  automationId,
  className,
  ...attributes
}: Props) => (
  <label
    className={
      classnames({
        [className]: className,
        'mailpoet-form-toggle': true,
        [`mailpoet-form-toggle-${dimension}`]: dimension,
        'mailpoet-disabled': attributes.disabled,
      })
    }
    data-automation-id={automationId}
  >
    <input
      type="checkbox"
      onChange={(e) => onCheck(e.target.checked, e)}
      {...attributes}
    />
    <span className="mailpoet-form-toggle-control" />
  </label>
);

export default Toggle;

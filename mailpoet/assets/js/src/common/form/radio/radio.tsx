import { ChangeEvent, InputHTMLAttributes, ReactNode } from 'react';
import classnames from 'classnames';

type Props = InputHTMLAttributes<HTMLInputElement> & {
  onCheck: (value: string, event: ChangeEvent) => void;
  children?: ReactNode;
  isFullWidth?: boolean;
  automationId?: string;
};

function Radio({
  children,
  isFullWidth,
  onCheck,
  automationId,
  ...attributes
}: Props) {
  return (
    <label
      className={classnames({
        'mailpoet-form-radio': true,
        'mailpoet-disabled': attributes.disabled,
        'mailpoet-full-width': isFullWidth,
      })}
      data-automation-id={automationId}
    >
      <input
        type="radio"
        onChange={(e) => onCheck(e.target.value, e)}
        {...attributes}
      />
      <span className="mailpoet-form-radio-control" />
      {children}
    </label>
  );
}

export default Radio;

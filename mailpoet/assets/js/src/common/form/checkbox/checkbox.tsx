import { ChangeEvent, InputHTMLAttributes, ReactNode } from 'react';
import classnames from 'classnames';

type Props = InputHTMLAttributes<HTMLInputElement> & {
  onCheck: (isChecked: boolean, event: ChangeEvent) => void;
  children?: ReactNode;
  isFullWidth?: boolean;
  automationId?: string;
};

function Checkbox({
  children,
  isFullWidth,
  onCheck,
  automationId,
  ...attributes
}: Props) {
  return (
    <label
      className={classnames({
        'mailpoet-form-checkbox': true,
        'mailpoet-disabled': attributes.disabled,
        'mailpoet-full-width': isFullWidth,
      })}
      data-automation-id={automationId}
    >
      <input
        type="checkbox"
        onChange={(e) => onCheck(e.target.checked, e)}
        {...attributes}
      />
      <span className="mailpoet-form-checkbox-control" />
      {children}
    </label>
  );
}

export default Checkbox;

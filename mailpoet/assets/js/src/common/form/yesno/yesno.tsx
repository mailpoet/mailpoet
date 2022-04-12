import { InputHTMLAttributes } from 'react';
import classnames from 'classnames';
import { yesIcon } from './icons/yes';
import { noIcon } from './icons/no';

type Props = InputHTMLAttributes<HTMLInputElement> & {
  name: string;
  checked?: boolean;
  onCheck: (isChecked: boolean) => void;
  showError?: boolean;
  automationId?: string;
};

export function YesNo({
  onCheck,
  showError,
  checked,
  automationId,
  ...attributes
}: Props) {
  return (
    <div
      className={classnames({
        'mailpoet-form-yesno': true,
        'mailpoet-form-yesno-error': showError,
        'mailpoet-disabled': attributes.disabled,
      })}
      data-automation-id={automationId}
    >
      <label>
        <input
          type="radio"
          checked={checked === true}
          onChange={() => onCheck(true)}
          {...attributes}
        />
        <span className="mailpoet-form-yesno-control mailpoet-form-yesno-yes">
          {yesIcon}
        </span>
      </label>
      <label>
        <input
          type="radio"
          checked={checked === false}
          onChange={() => onCheck(false)}
          {...attributes}
        />
        <span className="mailpoet-form-yesno-control mailpoet-form-yesno-no">
          {noIcon}
        </span>
      </label>
    </div>
  );
}

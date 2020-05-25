import React, { InputHTMLAttributes } from 'react';
import classnames from 'classnames';
import iconYes from './icons/yes';
import iconNo from './icons/no';

type Props = InputHTMLAttributes<HTMLInputElement> & {
  name: string,
  onCheck: (isChecked: boolean) => void,
  showError?: boolean,
};

const YesNo = ({
  onCheck,
  showError,
  ...attributes
}: Props) => (
  <div
    className={
      classnames({
        'mailpoet-form-yesno': true,
        'mailpoet-form-yesno-error': showError,
        'mailpoet-disabled': attributes.disabled,
      })
    }
  >
    <label>
      <input
        type="radio"
        onChange={(e) => onCheck(true)}
        {...attributes}
      />
      <span className="mailpoet-form-yesno-control mailpoet-form-yesno-yes">
        {iconYes}
      </span>
    </label>
    <label>
      <input
        type="radio"
        onChange={(e) => onCheck(false)}
        {...attributes}
      />
      <span className="mailpoet-form-yesno-control mailpoet-form-yesno-no">
        {iconNo}
      </span>
    </label>
  </div>
);

export default YesNo;

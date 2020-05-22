import React, { InputHTMLAttributes } from 'react';
import classnames from 'classnames';

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
      <span className="mailpoet-form-yesno-control mailpoet-form-yesno-yes" />
    </label>
    <label>
      <input
        type="radio"
        onChange={(e) => onCheck(false)}
        {...attributes}
      />
      <span className="mailpoet-form-yesno-control mailpoet-form-yesno-no" />
    </label>
  </div>
);

export default YesNo;

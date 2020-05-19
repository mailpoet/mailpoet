import React, { InputHTMLAttributes } from 'react';
import classnames from 'classnames';

type Props = InputHTMLAttributes<HTMLInputElement> & {
  dimension?: 'small',
  onCheck: (isChecked: boolean) => void,
};

const Toggle = ({
  dimension,
  onCheck,
  ...attributes
}: Props) => (
  <div
    className={
      classnames({
        'mailpoet-form-toggle': true,
        [`mailpoet-form-toggle-${dimension}`]: dimension,
      })
    }
  >
    <input
      type="checkbox"
      onChange={(e) => onCheck(e.target.checked)}
      {...attributes}
    />
    <span className="mailpoet-form-toggle-control" />
  </div>
);

export default Toggle;

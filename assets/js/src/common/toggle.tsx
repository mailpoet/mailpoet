import React from 'react';

type Props = {
  checked: boolean,
  onCheck: (checked: boolean) => void,
  name?: string,
};

const Toggle = ({ checked, onCheck, name }: Props) => (
  <>
    <input
      className="mailpoet-toggle mailpoet-toggle-light"
      id={`mailpoet-toggle-${name}`}
      type="checkbox"
      checked={checked}
      onChange={(event) => onCheck(event.target.checked)}
      key={`toggle-input-${name}`}
    />
    {/* eslint-disable-next-line jsx-a11y/label-has-associated-control */}
    <label
      className="mailpoet-toggle-button"
      htmlFor={`mailpoet-toggle-${name}`}
      key={`toggle-label-${name}`}
    />
  </>
);

export default Toggle;

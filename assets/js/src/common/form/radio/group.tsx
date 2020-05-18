import React, { InputHTMLAttributes, useState } from 'react';
import Radio from './radio';

type RadioValueType = string | string[] | number;

type RadioProps = InputHTMLAttributes<HTMLInputElement> & {
  label: string,
}

type Props = {
  name: string,
  options: RadioProps[],
  defaultValue?: RadioValueType,
  isFullWidth?: boolean,
  onChange?: (RadioValueType) => void,
};

const RadioGroup = ({
  name,
  options,
  defaultValue,
  isFullWidth,
  onChange,
}: Props) => {
  const [currentValue, setCurrentValue] = useState(defaultValue);

  const handleChange = (value: RadioValueType) => {
    setCurrentValue(value);
    onChange(value);
  };

  return (
    <div>
      {options.map(({ label, value, ...attributes }: RadioProps) => (
        <Radio
          checked={currentValue === value}
          key={label}
          name={name}
          value={value}
          onChange={() => handleChange(value)}
          isFullWidth={isFullWidth}
          {...attributes} // eslint-disable-line react/jsx-props-no-spreading
        >
          {label}
        </Radio>
      ))}
    </div>
  );
};

export default RadioGroup;

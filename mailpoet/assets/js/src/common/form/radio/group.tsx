import { InputHTMLAttributes, useState } from 'react';
import Radio from './radio';

type RadioValueType = string | string[] | number;

type RadioProps = InputHTMLAttributes<HTMLInputElement> & {
  label: string;
};

type Props = {
  name: string;
  options: RadioProps[];
  defaultValue?: RadioValueType;
  isFullWidth?: boolean;
  onChange?: (value: RadioValueType) => void;
};

function RadioGroup({
  name,
  options,
  defaultValue,
  isFullWidth,
  onChange,
}: Props) {
  const [currentValue, setCurrentValue] = useState(defaultValue);

  const handleChange = (value: RadioValueType) => {
    setCurrentValue(value);
    onChange(value);
  };

  return (
    <div>
      {options.map((props: RadioProps) => {
        const { label, ...attributes } = props;
        const value = props.value as RadioValueType;
        return (
          <Radio
            checked={currentValue === value}
            key={label}
            name={name}
            value={value}
            onCheck={() => handleChange(value)}
            isFullWidth={isFullWidth}
            {...attributes}
          >
            {label}
          </Radio>
        );
      })}
    </div>
  );
}

export default RadioGroup;

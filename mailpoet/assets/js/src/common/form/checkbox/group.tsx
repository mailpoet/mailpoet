import { InputHTMLAttributes, useState } from 'react';
import Checkbox from './checkbox';

type CheckboxValueType = string | string[] | number;

type CheckboxProps = InputHTMLAttributes<HTMLInputElement> & {
  label: string;
};

type Props = {
  name: string;
  options: CheckboxProps[];
  defaultValue?: CheckboxValueType[];
  isFullWidth?: boolean;
  onChange?: (values: CheckboxValueType[]) => void;
};

function CheckboxGroup({
  name,
  options,
  defaultValue,
  isFullWidth,
  onChange,
}: Props) {
  const [values, setValues] = useState(defaultValue || []);

  const handleChange = (value: CheckboxValueType, isChecked: boolean) => {
    const index = values.indexOf(value);
    let newValues: CheckboxValueType[] = [];
    if (isChecked && index === -1) {
      newValues = values.concat([value]);
    }
    if (!isChecked && index !== -1) {
      newValues = values.filter((val: CheckboxValueType) => val !== value);
    }
    newValues.sort();
    setValues(newValues);
    onChange(newValues);
  };

  return (
    <div>
      {options.map((props: CheckboxProps) => {
        const { label, ...attributes } = props;
        const value = props.value as CheckboxValueType;
        return (
          <Checkbox
            checked={values.includes(value)}
            key={label}
            name={name}
            value={value}
            onCheck={(isChecked) => handleChange(value, isChecked)}
            isFullWidth={isFullWidth}
            {...attributes}
          >
            {label}
          </Checkbox>
        );
      })}
    </div>
  );
}

export default CheckboxGroup;

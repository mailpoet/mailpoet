import React, { useState, useEffect } from 'react';
import { RangeControl, RadioControl } from '@wordpress/components';

export type SizeDefinition = {
  value: number|undefined;
  unit: 'percent'|'pixel';
};

type Props = {
  label: string;
  minPercents?: number;
  maxPercents?: number;
  minPixels?: number;
  maxPixels?: number;
  value: SizeDefinition|undefined;
  defaultPercentValue?: number;
  defaultPixelValue?: number;
  onChange: (value: SizeDefinition) => void;
}

export const SizeSettings: React.FunctionComponent<Props> = ({
  label,
  minPercents = 0,
  maxPercents = 100,
  minPixels = 10,
  maxPixels = 1000,
  value,
  defaultPercentValue = 50,
  defaultPixelValue = 200,
  onChange,
}: Props) => {
  const [localValue, setLocalValue] = useState(value ?? { unit: 'pixel', value: undefined });

  useEffect(() => {
    setLocalValue(value);
  }, [value]);

  return (
    <div className="mailpoet-size-settings-control">
      <h3 className="mailpoet-styles-settings-heading">{label}</h3>
      <RadioControl
        selected={localValue.unit || 'pixel'}
        options={[
          { label: 'px', value: 'pixel' },
          { label: '%', value: 'percent' },
        ]}
        onChange={(unit): void => {
          const newValue = {
            value: unit === 'pixel' ? defaultPixelValue : defaultPercentValue,
            unit,
          };
          setLocalValue(newValue);
          onChange(newValue);
        }}
      />
      <RangeControl
        value={localValue.value ?? (localValue.unit === 'pixel' ? defaultPixelValue : defaultPercentValue)}
        min={localValue.unit === 'pixel' ? minPixels : minPercents}
        max={localValue.unit === 'pixel' ? maxPixels : maxPercents}
        onChange={(val): void => {
          const newValue: SizeDefinition = {
            unit: localValue.unit === 'pixel' ? 'pixel' : 'percent',
            value: val,
          };
          setLocalValue(newValue);
          onChange(newValue);
        }}
      />
    </div>
  );
};

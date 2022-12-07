import { useEffect, useState } from 'react';
import { BaseControl, RadioControl, RangeControl } from '@wordpress/components';
import { withBoundary } from 'common';

export type SizeDefinition = {
  value: number | undefined;
  unit: 'percent' | 'pixel';
};

type Props = {
  label: string;
  minPercents?: number;
  maxPercents?: number;
  minPixels?: number;
  maxPixels?: number;
  value: SizeDefinition | undefined;
  defaultPercentValue?: number;
  defaultPixelValue?: number;
  onChange: (value: SizeDefinition) => void;
};

function SizeSettings({
  label,
  minPercents = 0,
  maxPercents = 100,
  minPixels = 10,
  maxPixels = 1000,
  value,
  defaultPercentValue = 50,
  defaultPixelValue = 200,
  onChange,
}: Props): JSX.Element {
  const [localValue, setLocalValue] = useState(
    value ?? { unit: 'pixel', value: undefined },
  );

  useEffect(() => {
    setLocalValue(value);
  }, [value]);

  return (
    <div className="mailpoet-size-settings-control">
      <BaseControl.VisualLabel>{label}</BaseControl.VisualLabel>
      <RadioControl
        selected={localValue.unit || 'pixel'}
        options={[
          { label: 'px', value: 'pixel' },
          { label: '%', value: 'percent' },
        ]}
        onChange={(unit: SizeDefinition['unit']): void => {
          const newValue = {
            value: unit === 'pixel' ? defaultPixelValue : defaultPercentValue,
            unit,
          };
          setLocalValue(newValue);
          onChange(newValue);
        }}
      />
      <RangeControl
        value={
          localValue.value ??
          (localValue.unit === 'pixel'
            ? defaultPixelValue
            : defaultPercentValue)
        }
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
}

SizeSettings.displayName = 'FormEditorSizeSettings';
const SizeSettingsWithBoundary = withBoundary(SizeSettings);
export { SizeSettingsWithBoundary as SizeSettings };

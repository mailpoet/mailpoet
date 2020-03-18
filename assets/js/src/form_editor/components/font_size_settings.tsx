import React from 'react';
import {
  FontSizePicker,
} from '@wordpress/components';
import { useSelect } from '@wordpress/data';

type Props = {
  name: string,
  value: number|undefined
  onChange: (value: string|undefined) => any
}

const FontSizeSettings = ({
  name,
  value,
  onChange,
}: Props) => {
  const fontSizes = useSelect(
    (select) => {
      const { getSettings } = select('core/block-editor');
      return getSettings().fontSizes;
    },
    []
  );
  return (
    <div>
      <h3 className="mailpoet-styles-settings-heading">
        {name}
      </h3>
      <FontSizePicker
        value={value}
        onChange={onChange}
        fontSizes={fontSizes}
      />
    </div>
  );
};

export default FontSizeSettings;

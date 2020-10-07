import React from 'react';
import {
  FontSizePicker,
} from '@wordpress/components';
import { __experimentalUseEditorFeature } from '@wordpress/block-editor';

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
  const fontSizes = __experimentalUseEditorFeature('typography.fontSizes');
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

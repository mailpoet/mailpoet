import React from 'react';
import {
  FontSizePicker,
} from '@wordpress/components';
import { __experimentalUseEditorFeature } from '@wordpress/block-editor';

type Props = {
  value: number|undefined;
  onChange: (value: string|undefined) => void;
}

const FontSizeSettings: React.FunctionComponent<Props> = ({
  value,
  onChange,
}: Props) => {
  const fontSizes = __experimentalUseEditorFeature('typography.fontSizes');
  return (
    <FontSizePicker
      value={value}
      onChange={onChange}
      fontSizes={fontSizes}
    />
  );
};

export default FontSizeSettings;

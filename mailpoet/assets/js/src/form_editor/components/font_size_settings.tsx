import { FontSizePicker } from '@wordpress/components';
import { useSetting } from '@wordpress/block-editor';

type Props = Pick<FontSizePicker.Props, 'value' | 'onChange'>;

export function FontSizeSettings({ value, onChange }: Props): JSX.Element {
  const fontSizes = useSetting('typography.fontSizes');
  return (
    <FontSizePicker value={value} onChange={onChange} fontSizes={fontSizes} />
  );
}

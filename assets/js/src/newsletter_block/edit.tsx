/**
 * External dependencies
 */
/* eslint-disable react/react-in-jsx-scope */
import { useBlockProps, RichText } from '@wordpress/block-editor';
/* eslint-disable import/no-unresolved */
import { CheckboxControl } from '@woocommerce/blocks-checkout';

/**
 * Internal dependencies
 */
import './editor.scss';

export const Edit = ({
  attributes: { text },
  setAttributes,
}: {
  attributes: { text: string; checkbox: boolean };
  setAttributes: (attributes: Record<string, unknown>) => void;
}): JSX.Element => {
  const blockProps = useBlockProps();
  const defaultText = 'Hello I am default checkbox text';
  const currentText = text || defaultText;
  return (
    <div {...blockProps}>
      <div className="wc-block-checkout__newsletter">
        <CheckboxControl id="subscribe-to-newsletter" checked={false} />
        <RichText
          value={currentText}
          onChange={(value) => setAttributes({ text: value })}
        />
      </div>
    </div>
  );
};

export const Save = (): JSX.Element => (<div {...useBlockProps.save()} />);

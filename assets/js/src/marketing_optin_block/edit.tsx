/* eslint-disable react/react-in-jsx-scope */
/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import { useBlockProps, RichText } from '@wordpress/block-editor';
import { CheckboxControl } from '@woocommerce/blocks-checkout';
import { getSetting } from '@woocommerce/settings';
import { Placeholder, Button } from '@wordpress/components';
import { Icon, megaphone } from '@wordpress/icons';

/**
 * Internal dependencies
 */
import './editor.scss';

const adminUrl = getSetting('adminUrl');
const { optinEnabled, defaultText } = getSetting('mailpoet_data');

const EmptyState = (): JSX.Element => (
  <Placeholder
    icon={<Icon icon={megaphone} />}
    label={__('marketing-opt-in-label', 'mailpoet')}
    className="wp-block-mailpoet-newsletter-block-placeholder"
  >
    <span className="wp-block-mailpoet-newsletter-block-placeholder__description">
      {__('marketing-opt-in-not-shown', 'mailpoet')}
    </span>
    <Button
      isPrimary
      href={`${adminUrl}admin.php?page=mailpoet-settings#/woocommerce`}
      target="_blank"
      rel="noopener noreferrer"
      className="wp-block-mailpoet-newsletter-block-placeholder__button"
    >
      {__('marketing-opt-in-enable', 'mailpoet')}
    </Button>
  </Placeholder>
);

export const Edit = ({
  attributes: { text },
  setAttributes,
}: {
  attributes: { text: string; checkbox: boolean };
  setAttributes: (attributes: Record<string, unknown>) => void;
}): JSX.Element => {
  const blockProps = useBlockProps();
  const currentText = text || defaultText;
  return (
    <div {...blockProps}>
      {optinEnabled ? (
        <>
          <div className="wc-block-checkout__marketing">
            <CheckboxControl id="mailpoet-marketing-optin" checked={false} />
            <RichText
              value={currentText}
              onChange={(value) => setAttributes({ text: value })}
            />
          </div>
        </>
      ) : (
        <EmptyState />
      )}
    </div>
  );
};

export const Save = (): JSX.Element => (<div {...useBlockProps.save()} />);

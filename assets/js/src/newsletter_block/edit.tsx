/* eslint-disable react/react-in-jsx-scope */
/* eslint-disable import/no-unresolved */

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
const { newsletterEnabled, newsletterDefaultText } = getSetting('mailpoet_data');

const EmptyState = () => {
  return (
    <Placeholder
      icon={<Icon icon={megaphone} />}
      label={__('Marketing opt-in', 'automatewoo')}
      className="wp-block-mailpoet-newsletter-block-placeholder"
    >
      <span className="wp-block-mailpoet-newsletter-block-placeholder__description">
        {__(
          'MailPoet marketing opt in would be shown here, you can enable from AutomateWoo settings page.',
          'mailpoet'
        )}
      </span>
      <Button
        isPrimary
        href={`${adminUrl}admin.php?page=mailpoet-settings#/woocommerce`}
        target="_blank"
        rel="noopener noreferrer"
        className="wp-block-mailpoet-newsletter-block-placeholder__button"
      >
        {__('Enable opt-in for Checkout', 'automatewoo')}
      </Button>
    </Placeholder>
  );
};

export const Edit = ({
  attributes: { text },
  setAttributes,
}: {
  attributes: { text: string; checkbox: boolean };
  setAttributes: (attributes: Record<string, unknown>) => void;
}): JSX.Element => {
  const blockProps = useBlockProps();
  const currentText = text || newsletterDefaultText;
  return (
    <div {...blockProps}>
      {newsletterEnabled ? (
        <>
          <div className="wc-block-checkout__newsletter">
            <CheckboxControl id="subscribe-to-newsletter" checked={false} />
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

import { registerBlockType } from '@wordpress/blocks';
import { __ } from '@wordpress/i18n';
import { useBlockProps, InspectorControls } from '@wordpress/block-editor';
import { PanelBody, RadioControl, Icon } from '@wordpress/components';
import { useState } from 'react';
import metadata from './block.json';
import MailPoetIcon from './mailpoet-icon';
import { PremiumModal } from '../../common/premium-modal';
import './style.scss';

const getCdnUrl = () => window.mailpoet_cdn_url;
const getPremiumPluginStatus = () => window.mailpoet_premium_active;

function LogoImage({
  logoSrc,
  style = {},
}: {
  logoSrc: string;
  style?: React.CSSProperties;
}): JSX.Element {
  const [isModalOpened, setIsModalOpened] = useState(false);

  return (
    <>
      <button
        type="button"
        className="mailpoet-email-footer-credit"
        onClick={() => setIsModalOpened(true)}
      >
        <img
          src={logoSrc}
          style={style}
          alt="Powered by MailPoet"
          width="100px"
        />
      </button>
      {!!isModalOpened && (
        <PremiumModal onRequestClose={() => setIsModalOpened(false)}>
          {__(
            'A MailPoet logo will appear in the footer of all emails sent with the free version of MailPoet.',
            'mailpoet',
          )}
        </PremiumModal>
      )}
    </>
  );
}

function Edit({
  attributes,
  setAttributes,
}: {
  attributes: { logo: string };
  setAttributes: (value: { logo: string }) => void;
}): JSX.Element {
  const blockProps = useBlockProps();

  const cdnUrl = getCdnUrl();
  const isPremiumPluginActive = getPremiumPluginStatus();

  if (isPremiumPluginActive) {
    return null;
  }
  const selectedLogo = attributes?.logo ?? 'default';
  return (
    <div {...blockProps}>
      <div
        className="mailpoet-email-footer-credit"
        style={{ textAlign: 'center' }}
      >
        <LogoImage logoSrc={`${cdnUrl}email-editor/logo-${selectedLogo}.png`} />
      </div>
      <InspectorControls>
        <PanelBody title={__('Settings', 'mailpoet')}>
          <RadioControl
            className="wc-block-editor-mini-cart__cart-icon-toggle"
            label={__('Image', 'mailpoet')}
            selected={selectedLogo}
            options={[
              {
                label: (
                  <LogoImage
                    logoSrc={`${cdnUrl}email-editor/logo-default.png`}
                  />
                ) as unknown as string,
                value: 'default',
              },
              {
                label: (
                  <LogoImage logoSrc={`${cdnUrl}email-editor/logo-light.png`} />
                ) as unknown as string,
                value: 'light',
              },
            ]}
            onChange={(value) => {
              setAttributes({
                logo: value,
              });
            }}
          />
        </PanelBody>
      </InspectorControls>
    </div>
  );
}

// @ts-expect-error TS2322 Different types
registerBlockType(metadata, {
  icon: {
    src: <Icon icon={MailPoetIcon} />,
  },
  edit: Edit,
});

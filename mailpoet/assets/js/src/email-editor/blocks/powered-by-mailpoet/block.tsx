import { registerBlockType } from '@wordpress/blocks';
import { __ } from '@wordpress/i18n';
import { useBlockProps, InspectorControls } from '@wordpress/block-editor';
import { PanelBody, RadioControl, Icon } from '@wordpress/components';
import { useSelect } from '@wordpress/data';
import metadata from './block.json';
import { storeName } from '../../engine/store/constants';
import MailPoetIcon from './mailpoet-icon';

function LogoImage({
  logoSrc,
  style = {},
}: {
  logoSrc: string;
  style?: React.CSSProperties;
}): JSX.Element {
  return (
    <img src={logoSrc} style={style} alt="Powered by MailPoet" width="100px" />
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
  const { cdnUrl, isPremiumPluginActive } = useSelect(
    (select) => ({
      cdnUrl: select(storeName).getCdnUrl(),
      isPremiumPluginActive: select(storeName).isPremiumPluginActive(),
    }),
    [],
  );
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
              {
                label: (
                  <LogoImage
                    logoSrc={`${cdnUrl}email-editor/logo-dark.png`}
                    style={{ background: '#000000' }}
                  />
                ) as unknown as string,
                value: 'dark',
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

// eslint-disable-next-line @typescript-eslint/ban-ts-comment
// @ts-ignore
registerBlockType(metadata, {
  icon: {
    src: <Icon icon={MailPoetIcon} />,
  },
  edit: Edit,
});

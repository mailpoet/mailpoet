import { CSSProperties } from 'react';
import classnames from 'classnames';
import { MailPoet } from 'mailpoet';
import { InspectorControls } from '@wordpress/block-editor';
import { ColorGradientSettings } from 'form_editor/components/color_gradient_settings';
import {
  Panel,
  PanelBody,
  RangeControl,
  SelectControl,
  ToggleControl,
} from '@wordpress/components';
import { Attributes, defaultAttributes, Style, Types } from './divider_types';

type Props = {
  attributes: Attributes;
  setAttributes: (attribute) => void;
};

export function DividerEdit({ attributes, setAttributes }: Props): JSX.Element {
  const attributeDividerHeight =
    attributes.dividerHeight ?? defaultAttributes.dividerHeight;
  const attributeDividerWidth =
    attributes.dividerWidth ?? defaultAttributes.dividerWidth;
  const attributeHeight = attributes.height ?? defaultAttributes.height;

  const dividerSettings = (
    <>
      <SelectControl
        label={MailPoet.I18n.t('blockDividerStyle')}
        data-automation-id="settings_divider_style"
        value={attributes.style}
        onChange={(style): void => setAttributes({ style })}
        options={[
          {
            value: Style.Solid,
            label: MailPoet.I18n.t('blockDividerStyleSolid'),
          },
          {
            value: Style.Dashed,
            label: MailPoet.I18n.t('blockDividerStyleDashed'),
          },
          {
            value: Style.Dotted,
            label: MailPoet.I18n.t('blockDividerStyleDotted'),
          },
        ]}
      />
      <RangeControl
        label={MailPoet.I18n.t('blockDividerDividerHeight')}
        className="mailpoet-automation-styles-divider-height"
        value={attributeDividerHeight}
        min={1}
        max={40}
        allowReset
        onChange={(dividerHeight: number): void => {
          let newHeight = attributeHeight;
          if (dividerHeight !== undefined) {
            newHeight = Math.max(dividerHeight, attributeHeight);
          }
          setAttributes({
            dividerHeight,
            height: newHeight,
          });
        }}
      />
      <RangeControl
        label={MailPoet.I18n.t('blockDividerDividerWidth')}
        className="mailpoet-automation-styles-divider-width"
        value={attributeDividerWidth}
        min={1}
        max={100}
        allowReset
        onChange={(dividerWidth): void => setAttributes({ dividerWidth })}
      />
      <ColorGradientSettings
        title={MailPoet.I18n.t('formSettingsColor')}
        settings={[
          {
            label: MailPoet.I18n.t('blockDividerBackground'),
            colorValue: attributes.color,
            onColorChange: (color): void => setAttributes({ color }),
          },
        ]}
      />
    </>
  );

  const dividerStyles = {} as CSSProperties;
  if (attributes.type === Types.Divider) {
    dividerStyles.borderTopStyle = attributes.style;
    dividerStyles.borderTopWidth = attributeDividerHeight;
    dividerStyles.borderTopColor = attributes.color;
    dividerStyles.height = attributeDividerHeight;
    dividerStyles.width = `${attributeDividerWidth}%`;
  }

  return (
    <>
      <InspectorControls>
        <Panel>
          <PanelBody title={MailPoet.I18n.t('formSettingsStyles')} initialOpen>
            <RangeControl
              label={MailPoet.I18n.t('blockSpacerHeight')}
              className="mailpoet-automation-spacer-height-size"
              value={attributeHeight}
              min={1}
              max={400}
              allowReset
              onChange={(height: number): void => {
                let newDividerHeightHeight = attributeDividerHeight;
                if (height !== undefined) {
                  newDividerHeightHeight = Math.min(
                    height,
                    attributeDividerHeight,
                  );
                } else {
                  newDividerHeightHeight = 1;
                }
                setAttributes({
                  height,
                  dividerHeight: newDividerHeightHeight,
                });
              }}
            />
            <ToggleControl
              label={MailPoet.I18n.t('blockSpacerEnableDivider')}
              className="mailpoet-automation-divider-togle-enable"
              checked={attributes.type === Types.Divider}
              onChange={(checked): void =>
                setAttributes({
                  type: checked ? Types.Divider : Types.Spacer,
                })
              }
            />
            {attributes.type === Types.Divider && dividerSettings}
          </PanelBody>
        </Panel>
      </InspectorControls>

      <div
        className={classnames('mailpoet_spacer', attributes.className)}
        data-automation-id="editor_spacer_block"
        style={{
          height: attributeHeight,
          display: 'flex',
          flexDirection: 'column',
          alignItems: 'center',
          width: '100%',
          justifyContent: 'center',
        }}
      >
        <div
          className="mailpoet_divider"
          data-automation-id="editor_divider_block"
          style={dividerStyles}
        />
      </div>
    </>
  );
}

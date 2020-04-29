import React from 'react';
import classnames from 'classnames';
import MailPoet from 'mailpoet';
import { InspectorControls } from '@wordpress/block-editor';
import ColorSettings from 'form_editor/components/color_settings';
import {
  Panel,
  PanelBody,
  RangeControl,
  SelectControl,
  ToggleControl,
} from '@wordpress/components';
import {
  Attributes,
  Style,
  Types,
  defaultAttributes,
} from './divider';

type Props = {
  attributes: Attributes,
  setAttributes: (attribute) => void,
};

const DividerEdit = ({ attributes, setAttributes }: Props) => {
  const attributeStyle = attributes.style ?? defaultAttributes.style;
  const attributeDividerHeight = attributes.dividerHeight ?? defaultAttributes.dividerHeight;
  const attributeDividerWidth = attributes.dividerWidth ?? defaultAttributes.dividerWidth;
  const attributeHeight = attributes.height ?? defaultAttributes.height;
  const attributeType = attributes.type ?? defaultAttributes.type;
  const attributeColor = attributes.color ?? defaultAttributes.color;

  const dividerSettings = (
    <>
      <SelectControl
        label={MailPoet.I18n.t('blockDividerStyle')}
        value={attributeStyle}
        onChange={(style) => (setAttributes({ style }))}
        options={[
          { value: Style.Solid, label: MailPoet.I18n.t('blockDividerStyleSolid') },
          { value: Style.Dashed, label: MailPoet.I18n.t('blockDividerStyleDashed') },
          { value: Style.Dotted, label: MailPoet.I18n.t('blockDividerStyleDotted') },
        ]}
      />
      <RangeControl
        label={MailPoet.I18n.t('blockDividerDividerHeight')}
        value={attributeDividerHeight}
        min={1}
        max={40}
        allowReset
        onChange={(dividerHeight) => {
          setAttributes({
            dividerHeight,
            height: Math.max(dividerHeight, attributeHeight),
          });
        }}
      />
      <RangeControl
        label={MailPoet.I18n.t('blockDividerDividerWidth')}
        value={attributeDividerWidth}
        min={1}
        max={100}
        allowReset
        onChange={(dividerWidth) => (setAttributes({ dividerWidth }))}
      />
      <ColorSettings
        name={MailPoet.I18n.t('blockDividerColor')}
        value={attributeColor}
        onChange={(color) => (setAttributes({ color }))}
      />
    </>
  );

  const dividerStyles = {} as React.CSSProperties;
  if (attributeType === Types.Divider) {
    dividerStyles.borderTopStyle = attributeStyle;
    dividerStyles.borderTopWidth = attributeDividerHeight;
    dividerStyles.borderTopColor = attributeColor;
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
              value={attributeHeight}
              min={1}
              max={400}
              allowReset
              onChange={(height) => (setAttributes({ height }))}
            />
            <ToggleControl
              label={MailPoet.I18n.t('blockSpacerEnableDivider')}
              checked={attributeType === Types.Divider}
              onChange={(checked) => setAttributes({
                type: checked ? Types.Divider : Types.Spacer,
              })}
            />
            {(
              (attributeType === Types.Divider) && (dividerSettings)
            )}
          </PanelBody>
        </Panel>
      </InspectorControls>

      <div
        className={classnames('mailpoet_spacer', attributes.className)}
        style={{
          height: attributeHeight,
          display: 'flex',
          flexDirection: 'column',
          alignItems: 'center',
          width: '100%',
          justifyContent: 'center',
        }}
      >
        <div className="mailpoet_divider" style={dividerStyles} />
      </div>
    </>
  );
};
export default DividerEdit;

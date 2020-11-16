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
} from './divider_types';

type Props = {
  attributes: Attributes;
  setAttributes: (attribute) => void;
};

const DividerEdit = ({ attributes, setAttributes }: Props) => {
  const attributeDividerHeight = attributes.dividerHeight ?? defaultAttributes.dividerHeight;
  const attributeDividerWidth = attributes.dividerWidth ?? defaultAttributes.dividerWidth;
  const attributeHeight = attributes.height ?? defaultAttributes.height;

  const dividerSettings = (
    <>
      <SelectControl
        label={MailPoet.I18n.t('blockDividerStyle')}
        value={attributes.style}
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
        value={attributeDividerWidth}
        min={1}
        max={100}
        allowReset
        onChange={(dividerWidth) => (setAttributes({ dividerWidth }))}
      />
      <ColorSettings
        name={MailPoet.I18n.t('blockDividerColor')}
        value={attributes.color}
        onChange={(color) => (setAttributes({ color }))}
      />
    </>
  );

  const dividerStyles = {} as React.CSSProperties;
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
              value={attributeHeight}
              min={1}
              max={400}
              allowReset
              onChange={(height) => {
                let newDividerHeightHeight = attributeDividerHeight;
                if (height !== undefined) {
                  newDividerHeightHeight = Math.min(height, attributeDividerHeight);
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
              checked={attributes.type === Types.Divider}
              onChange={(checked) => setAttributes({
                type: checked ? Types.Divider : Types.Spacer,
              })}
            />
            {(
              (attributes.type === Types.Divider) && (dividerSettings)
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

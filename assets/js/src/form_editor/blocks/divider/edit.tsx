import React from 'react';
import classnames from 'classnames';
import MailPoet from 'mailpoet';
import { InspectorControls } from '@wordpress/block-editor';
import {
  Panel,
  PanelBody,
  RangeControl,
  SelectControl,
  ToggleControl,
} from '@wordpress/components';
import { Attributes, Style, Types } from './divider';

type Props = {
  attributes: Attributes,
  setAttributes: (attribute) => void,
};

const DividerEdit = ({ attributes, setAttributes }: Props) => {
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
        value={attributes.dividerHeight}
        min={1}
        max={40}
        allowReset
        onChange={(dividerHeight) => {
          setAttributes({
            dividerHeight,
            height: Math.max(dividerHeight, attributes.height),
          });
        }}
      />
      <RangeControl
        label={MailPoet.I18n.t('blockDividerDividerWidth')}
        value={attributes.dividerWidth}
        min={1}
        max={100}
        allowReset
        onChange={(dividerWidth) => (setAttributes({ dividerWidth }))}
      />
    </>
  );

  const dividerStyles = {} as React.CSSProperties;
  if (attributes.type === Types.Divider) {
    dividerStyles.borderTopStyle = attributes.style;
    dividerStyles.borderTopWidth = attributes.dividerHeight;
    dividerStyles.height = attributes.dividerHeight;
    dividerStyles.width = `${attributes.dividerWidth}%`;
  }

  return (
    <>
      <InspectorControls>
        <Panel>
          <PanelBody title={MailPoet.I18n.t('formSettingsStyles')} initialOpen>
            <RangeControl
              label={MailPoet.I18n.t('blockSpacerHeight')}
              value={attributes.height}
              min={1}
              max={400}
              allowReset
              onChange={(height) => (setAttributes({ height }))}
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
          height: attributes.height,
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

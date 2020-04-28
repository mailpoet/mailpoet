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
import { Style, Types } from './divider';

const DividerEdit = ({ attributes, setAttributes }) => {
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
    </>
  );

  const dividerStyles = {};
  if (attributes.type === Types.Divider) {
    dividerStyles.borderTopStyle = attributes.style;
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
        }}
      >
        <div className="mailpoet_divider" style={dividerStyles} />
      </div>
    </>
  );
};
export default DividerEdit;

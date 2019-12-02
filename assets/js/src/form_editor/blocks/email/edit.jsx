import React from 'react';
import {
  Panel,
  PanelBody,
  TextControl,
  ToggleControl,
} from '@wordpress/components';
import { InspectorControls } from '@wordpress/block-editor';
import PropTypes from 'prop-types';
import MailPoet from 'mailpoet';

const EmailEdit = ({ attributes, setAttributes }) => {
  const inspectorControls = (
    <InspectorControls>
      <Panel>
        <PanelBody title={MailPoet.I18n.t('formSettings')} initialOpen>
          <TextControl
            label={MailPoet.I18n.t('label')}
            value={attributes.label}
            onChange={(label) => (setAttributes({ label }))}
          />
          <ToggleControl
            label={MailPoet.I18n.t('displayLabelWithinInput')}
            checked={attributes.labelWithinInput}
            onChange={(labelWithinInput) => (setAttributes({ labelWithinInput }))}
          />
        </PanelBody>
      </Panel>

    </InspectorControls>
  );
  return (
    <>
      {inspectorControls}
      {attributes.labelWithinInput ? (
        <input className="mailpoet_text" type="email" name="name" placeholder={attributes.label} />
      ) : (
        <label className="mailpoet_text_label">
          {attributes.label}
          <br />
          <input className="mailpoet_text" type="email" name="email" placeholder="" />
        </label>
      )}
    </>
  );
};

EmailEdit.propTypes = {
  attributes: PropTypes.shape({
    label: PropTypes.string.isRequired,
    labelWithinInput: PropTypes.bool.isRequired,
  }).isRequired,
  setAttributes: PropTypes.func.isRequired,
};

export default EmailEdit;

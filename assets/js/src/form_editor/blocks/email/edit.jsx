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

import ParagraphEdit from '../paragraph_edit.jsx';
import { InputStylesSettings, inputStylesPropTypes } from '../input_styles_settings.jsx';

const EmailEdit = ({ attributes, setAttributes }) => {
  const inspectorControls = (
    <InspectorControls>
      <Panel>
        <PanelBody title={MailPoet.I18n.t('formSettings')} initialOpen>
          <TextControl
            label={MailPoet.I18n.t('label')}
            value={attributes.label}
            data-automation-id="settings_email_label_input"
            onChange={(label) => (setAttributes({ label }))}
          />
          <ToggleControl
            label={MailPoet.I18n.t('displayLabelWithinInput')}
            checked={attributes.labelWithinInput}
            onChange={(labelWithinInput) => (setAttributes({ labelWithinInput }))}
          />
        </PanelBody>
      </Panel>
      <InputStylesSettings
        styles={attributes.styles}
        onChange={(styles) => (setAttributes({ styles }))}
      />
    </InspectorControls>
  );

  const getTextInput = (placeholder) => (
    <input
      id="email"
      className="mailpoet_text"
      type="email"
      name="email"
      placeholder={placeholder}
      data-automation-id="editor_email_input"
    />
  );

  return (
    <ParagraphEdit>
      {inspectorControls}
      {!attributes.labelWithinInput ? (
        <label className="mailpoet_text_label" data-automation-id="editor_email_label" htmlFor="email">
          {`${attributes.label} *`}
        </label>
      ) : null}
      {getTextInput(attributes.labelWithinInput ? `${attributes.label} *` : '')}
    </ParagraphEdit>
  );
};

EmailEdit.propTypes = {
  attributes: PropTypes.shape({
    label: PropTypes.string.isRequired,
    labelWithinInput: PropTypes.bool.isRequired,
    styles: inputStylesPropTypes.isRequired,
  }).isRequired,
  setAttributes: PropTypes.func.isRequired,
};

export default EmailEdit;

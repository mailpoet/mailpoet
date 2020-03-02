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
import formatLabel from '../label_formatter.jsx';
import InputStylesSettings from '../input_styles_settings.jsx';

const LastNameEdit = ({ attributes, setAttributes }) => {
  const inspectorControls = (
    <InspectorControls>
      <Panel>
        <PanelBody title={MailPoet.I18n.t('formSettings')} initialOpen>
          <TextControl
            label={MailPoet.I18n.t('label')}
            value={attributes.label}
            data-automation-id="settings_last_name_label_input"
            onChange={(label) => (setAttributes({ label }))}
          />
          <ToggleControl
            label={MailPoet.I18n.t('displayLabelWithinInput')}
            checked={attributes.labelWithinInput}
            onChange={(labelWithinInput) => (setAttributes({ labelWithinInput }))}
          />
          <ToggleControl
            label={MailPoet.I18n.t('blockMandatory')}
            checked={attributes.mandatory}
            onChange={(mandatory) => (setAttributes({ mandatory }))}
          />
        </PanelBody>
      </Panel>
      <InputStylesSettings />
    </InspectorControls>
  );

  const getTextInput = (placeholder) => (
    <input
      id="last_name"
      className="mailpoet_text"
      type="text"
      name="last_name"
      placeholder={placeholder}
      data-automation-id="editor_last_name_input"
    />
  );

  return (
    <ParagraphEdit>
      {inspectorControls}
      {!attributes.labelWithinInput ? (
        <label className="mailpoet_text_label" data-automation-id="editor_last_name_label" htmlFor="last_name">
          {formatLabel(attributes)}
        </label>
      ) : null}
      {getTextInput(attributes.labelWithinInput ? formatLabel(attributes) : '')}
    </ParagraphEdit>
  );
};

LastNameEdit.propTypes = {
  attributes: PropTypes.shape({
    label: PropTypes.string.isRequired,
    labelWithinInput: PropTypes.bool.isRequired,
    mandatory: PropTypes.bool.isRequired,
  }).isRequired,
  setAttributes: PropTypes.func.isRequired,
};

export default LastNameEdit;

import React from 'react';
import {
  Panel,
  PanelBody, SelectControl,
  TextControl,
  ToggleControl,
} from '@wordpress/components';
import { InspectorControls } from '@wordpress/block-editor';
import PropTypes from 'prop-types';
import MailPoet from 'mailpoet';

const FirstNameEdit = ({ attributes, setAttributes }) => {
  const inspectorControls = (
    <InspectorControls>
      <Panel>
        <PanelBody title={MailPoet.I18n.t('formSettings')} initialOpen>
          <TextControl
            label={MailPoet.I18n.t('label')}
            value={attributes.label}
            data-automation-id="settings_custom_text_label_input"
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
          <SelectControl
            label={`${MailPoet.I18n.t('customFieldValidateFor')}:`}
            options={[
              {
                label: MailPoet.I18n.t('customFieldValidateNothing'),
                value: '',
              },
              {
                label: MailPoet.I18n.t('customFieldValidateNumbersOnly'),
                value: 'alphanum',
              },
              {
                label: MailPoet.I18n.t('customFieldValidateAlphanumerical'),
                value: 'number',
              },
              {
                label: MailPoet.I18n.t('customFieldValidatePhoneNumber'),
                value: 'phone',
              },
            ]}
            onChange={(validate) => (setAttributes({ validate }))}
          />
        </PanelBody>
      </Panel>

    </InspectorControls>
  );

  const getTextInput = (placeholder) => (
    <input
      id="custom_text"
      className="mailpoet_text"
      type="text"
      name="custom_text"
      disabled
      placeholder={placeholder}
      data-automation-id="editor_custom_text_input"
    />
  );

  const getLabel = () => {
    if (attributes.mandatory) {
      return `${attributes.label} *`;
    }
    return attributes.label;
  };

  return (
    <>
      {inspectorControls}
      {attributes.labelWithinInput ? (getTextInput(getLabel())
      ) : (
        <label className="mailpoet_text_label" data-automation-id="editor_custom_text_label" htmlFor="custom_text">
          {getLabel()}
          <br />
          {getTextInput('')}
        </label>
      )}
    </>
  );
};

FirstNameEdit.propTypes = {
  attributes: PropTypes.shape({
    label: PropTypes.string.isRequired,
    labelWithinInput: PropTypes.bool.isRequired,
    mandatory: PropTypes.bool.isRequired,
  }).isRequired,
  setAttributes: PropTypes.func.isRequired,
};

export default FirstNameEdit;

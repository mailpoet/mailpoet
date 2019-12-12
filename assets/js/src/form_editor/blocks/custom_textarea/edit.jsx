import React from 'react';
import {
  Panel,
  PanelBody,
  SelectControl,
  TextControl,
  ToggleControl,
} from '@wordpress/components';
import { InspectorControls } from '@wordpress/block-editor';
import PropTypes from 'prop-types';
import MailPoet from 'mailpoet';

const CustomTextAreaEdit = ({ attributes, setAttributes }) => {
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
          <SelectControl
            label={`${MailPoet.I18n.t('customFieldNumberOfLines')}:`}
            value={attributes.lines}
            options={[
              {
                label: MailPoet.I18n.t('customField1Line'),
                value: '1',
              },
              {
                label: MailPoet.I18n.t('customField2Lines'),
                value: '2',
              },
              {
                label: MailPoet.I18n.t('customField3Lines'),
                value: '3',
              },
              {
                label: MailPoet.I18n.t('customField4Lines'),
                value: '4',
              },
              {
                label: MailPoet.I18n.t('customField5Lines'),
                value: '5',
              },
            ]}
            onChange={(lines) => (setAttributes({ lines }))}
          />
        </PanelBody>
      </Panel>

    </InspectorControls>
  );

  const getTextArea = (placeholder) => (
    <textarea
      id="custom_text"
      className="mailpoet_text"
      name="custom_text"
      disabled
      data-automation-id="editor_custom_text_input"
      defaultValue={placeholder}
      rows={attributes.lines}
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
      {attributes.labelWithinInput ? (getTextArea(getLabel())
      ) : (
        <label className="mailpoet_text_label" data-automation-id="editor_custom_text_label" htmlFor="custom_text">
          {getLabel()}
          <br />
          {getTextArea('')}
        </label>
      )}
    </>
  );
};

CustomTextAreaEdit.propTypes = {
  attributes: PropTypes.shape({
    label: PropTypes.string.isRequired,
    labelWithinInput: PropTypes.bool.isRequired,
    mandatory: PropTypes.bool.isRequired,
  }).isRequired,
  setAttributes: PropTypes.func.isRequired,
};

export default CustomTextAreaEdit;

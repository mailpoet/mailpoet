import {
  Panel,
  PanelBody,
  TextControl,
  ToggleControl,
} from '@wordpress/components';
import { InspectorControls } from '@wordpress/block-editor';
import PropTypes from 'prop-types';

import { MailPoet } from 'mailpoet';
import {
  inputStylesPropTypes,
  InputStylesSettings,
} from '../input_styles_settings';
import { TextInputEdit } from '../text_input_edit.jsx';

function EmailEdit({ attributes, setAttributes }) {
  const inspectorControls = (
    <InspectorControls>
      <Panel>
        <PanelBody title={MailPoet.I18n.t('formSettings')} initialOpen>
          <TextControl
            label={MailPoet.I18n.t('label')}
            value={attributes.label}
            data-automation-id="settings_email_label_input"
            onChange={(label) => setAttributes({ label })}
          />
          <ToggleControl
            label={MailPoet.I18n.t('displayLabelWithinInput')}
            checked={attributes.labelWithinInput}
            onChange={(labelWithinInput) => setAttributes({ labelWithinInput })}
          />
        </PanelBody>
      </Panel>
      <InputStylesSettings
        styles={attributes.styles}
        onChange={(styles) => setAttributes({ styles })}
      />
    </InspectorControls>
  );

  return (
    <>
      {inspectorControls}
      <TextInputEdit
        className={attributes.className}
        name="email"
        label={attributes.label}
        labelWithinInput={!!attributes.labelWithinInput}
        mandatory
        styles={attributes.styles}
      />
    </>
  );
}

EmailEdit.propTypes = {
  attributes: PropTypes.shape({
    label: PropTypes.string.isRequired,
    labelWithinInput: PropTypes.bool.isRequired,
    className: PropTypes.string,
    styles: inputStylesPropTypes.isRequired,
  }).isRequired,
  setAttributes: PropTypes.func.isRequired,
};

export { EmailEdit };

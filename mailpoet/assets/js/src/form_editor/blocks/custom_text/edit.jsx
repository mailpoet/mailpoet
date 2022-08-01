import { Panel, PanelBody, ToggleControl } from '@wordpress/components';
import { InspectorControls } from '@wordpress/block-editor';
import PropTypes from 'prop-types';
import { MailPoet } from 'mailpoet';
import { useDispatch, useSelect } from '@wordpress/data';

import { CustomFieldSettings } from './custom_field_settings.jsx';
import { mapCustomFieldFormData } from '../map_custom_field_form_data.jsx';
import {
  InputStylesSettings,
  inputStylesPropTypes,
} from '../input_styles_settings';
import { TextInputEdit } from '../text_input_edit.jsx';

function CustomTextEdit({ attributes, setAttributes, clientId }) {
  const isSaving = useSelect(
    (sel) => sel('mailpoet-form-editor').getIsCustomFieldSaving(),
    [],
  );
  const isDeleting = useSelect(
    (sel) => sel('mailpoet-form-editor').getIsCustomFieldDeleting(),
    [],
  );

  const { saveCustomField, deleteCustomField, customFieldEdited } = useDispatch(
    'mailpoet-form-editor',
  );

  const inspectorControls = (
    <InspectorControls>
      <Panel>
        <PanelBody title={MailPoet.I18n.t('customFieldSettings')} initialOpen>
          <CustomFieldSettings
            updateAttributes={(attrs) => setAttributes(attrs)}
            customFieldId={attributes.customFieldId}
            label={attributes.label}
            mandatory={attributes.mandatory}
            validate={attributes.validate}
            isSaving={isSaving}
            onSave={(params) => {
              saveCustomField({
                customFieldId: attributes.customFieldId,
                data: {
                  params: mapCustomFieldFormData('text', params),
                },
                onFinish: () =>
                  setAttributes({
                    mandatory: params.mandatory,
                    validate: params.validate,
                    label: params.label,
                  }),
              });
            }}
            onCustomFieldDelete={() =>
              deleteCustomField(attributes.customFieldId, clientId)
            }
            isDeleting={isDeleting}
            onChange={(data, hasUnsavedChanges) =>
              hasUnsavedChanges && customFieldEdited()
            }
          />
        </PanelBody>
      </Panel>
      <Panel>
        <PanelBody
          title={MailPoet.I18n.t('customFieldsFormSettings')}
          initialOpen
        >
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
        name="custom_text"
        mandatory={attributes.mandatory}
        labelWithinInput={attributes.labelWithinInput}
        label={attributes.label}
        styles={attributes.styles}
      />
    </>
  );
}

CustomTextEdit.propTypes = {
  attributes: PropTypes.shape({
    label: PropTypes.string.isRequired,
    validate: PropTypes.string,
    labelWithinInput: PropTypes.bool.isRequired,
    mandatory: PropTypes.bool.isRequired,
    customFieldId: PropTypes.number.isRequired,
    styles: inputStylesPropTypes.isRequired,
    className: PropTypes.string,
  }).isRequired,
  setAttributes: PropTypes.func.isRequired,
  clientId: PropTypes.string.isRequired,
};

export { CustomTextEdit };

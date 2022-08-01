import { useRef, useState } from 'react';
import {
  Panel,
  PanelBody,
  SelectControl,
  ToggleControl,
} from '@wordpress/components';
import { InspectorControls } from '@wordpress/block-editor';
import PropTypes from 'prop-types';
import { useDispatch, useSelect } from '@wordpress/data';

import { MailPoet } from 'mailpoet';
import { convertAlignmentToMargin } from '../convert_alignment_to_margin';
import { CustomFieldSettings } from '../custom_text/custom_field_settings.jsx';
import { formatLabel } from '../label_formatter.jsx';
import {
  inputStylesPropTypes,
  InputStylesSettings,
} from '../input_styles_settings';
import { mapCustomFieldFormData } from '../map_custom_field_form_data.jsx';
import { ParagraphEdit } from '../paragraph_edit.jsx';

function CustomTextAreaEdit({ name, attributes, setAttributes, clientId }) {
  const id = `${name.replace(/[^a-zA-Z]/g, '')}_${Math.random()
    .toString(36)
    .substring(2, 15)}`;
  const settings = useSelect(
    (select) => select('mailpoet-form-editor').getFormSettings(),
    [],
  );
  const isSaving = useSelect(
    (sel) => sel('mailpoet-form-editor').getIsCustomFieldSaving(),
    [],
  );
  const isDeleting = useSelect(
    (sel) => sel('mailpoet-form-editor').getIsCustomFieldDeleting(),
    [],
  );

  const [inputValue, setInputValue] = useState('');

  const { saveCustomField, deleteCustomField, customFieldEdited } = useDispatch(
    'mailpoet-form-editor',
  );
  const inspectorControls = (
    <InspectorControls>
      <Panel>
        <PanelBody title={MailPoet.I18n.t('customFieldSettings')} initialOpen>
          <CustomFieldSettings
            label={attributes.label}
            mandatory={attributes.mandatory}
            validate={attributes.validate}
            isSaving={isSaving}
            onSave={(params) =>
              saveCustomField({
                customFieldId: attributes.customFieldId,
                data: {
                  params: mapCustomFieldFormData('textarea', params),
                },
                onFinish: () =>
                  setAttributes({
                    mandatory: params.mandatory,
                    validate: params.validate,
                    lines: params.lines,
                    label: params.label,
                  }),
              })
            }
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
        <PanelBody title={MailPoet.I18n.t('formSettings')} initialOpen>
          <ToggleControl
            label={MailPoet.I18n.t('displayLabelWithinInput')}
            checked={attributes.labelWithinInput}
            onChange={(labelWithinInput) => setAttributes({ labelWithinInput })}
          />
          <SelectControl
            label={`${MailPoet.I18n.t('customFieldNumberOfLines')}:`}
            data-automation-id="settings_custom_text_area_number_of_lines"
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
            onChange={(lines) => setAttributes({ lines })}
          />
        </PanelBody>
      </Panel>
      <InputStylesSettings
        styles={attributes.styles}
        onChange={(styles) => setAttributes({ styles })}
      />
    </InspectorControls>
  );

  const labelStyles = !attributes.styles.inheritFromTheme
    ? {
        fontWeight: attributes.styles.bold ? 'bold' : 'inherit',
      }
    : {};

  const inputStyles = !attributes.styles.inheritFromTheme
    ? {
        borderRadius: attributes.styles.borderRadius
          ? `${attributes.styles.borderRadius}px`
          : 0,
        borderWidth:
          attributes.styles.borderSize !== undefined
            ? `${attributes.styles.borderSize}px`
            : '1px',
        borderColor: attributes.styles.borderColor || 'initial',
        borderStyle: 'solid',
      }
    : {};

  if (attributes.styles.fullWidth) {
    inputStyles.width = '100%';
  }

  if (
    attributes.styles.backgroundColor &&
    !attributes.styles.inheritFromTheme
  ) {
    inputStyles.backgroundColor = attributes.styles.backgroundColor;
  }

  if (settings.inputPadding !== undefined) {
    inputStyles.padding = settings.inputPadding;
  }

  if (settings.alignment !== undefined) {
    inputStyles.textAlign = settings.alignment;
    inputStyles.margin = convertAlignmentToMargin(inputStyles.textAlign);
  }

  inputStyles.resize = 'none';

  const placeholderStyle = {};

  if (attributes.styles.fontColor && !attributes.styles.inheritFromTheme) {
    inputStyles.color = attributes.styles.fontColor;
    if (attributes.labelWithinInput) {
      placeholderStyle.color = attributes.styles.fontColor;
    }
  }

  const textarea = useRef(null);
  const getTextArea = (placeholder) => {
    let style = `#${id}::placeholder {`;
    if (placeholderStyle.color !== undefined) {
      style += `color: ${placeholderStyle.color};`;
    }
    if (settings.fontFamily) {
      style += `font-family: ${settings.fontFamily};`;
    }
    style += '}';
    return (
      <>
        <style>{style}</style>
        <textarea
          id={id}
          ref={textarea}
          className="mailpoet_textarea"
          name="custom_text"
          data-automation-id="editor_custom_textarea_input"
          rows={attributes.lines}
          style={inputStyles}
          onChange={() => setInputValue('')}
          placeholder={placeholder}
          value={inputValue}
        />
      </>
    );
  };

  return (
    <ParagraphEdit className={attributes.className}>
      {inspectorControls}
      {attributes.labelWithinInput ? (
        getTextArea(formatLabel(attributes))
      ) : (
        <>
          <label
            className="mailpoet_textarea_label"
            data-automation-id="editor_custom_text_label"
            htmlFor={id}
            style={labelStyles}
          >
            {formatLabel(attributes)}
          </label>
          {getTextArea('')}
        </>
      )}
    </ParagraphEdit>
  );
}

CustomTextAreaEdit.propTypes = {
  attributes: PropTypes.shape({
    label: PropTypes.string.isRequired,
    customFieldId: PropTypes.number.isRequired,
    validate: PropTypes.string,
    labelWithinInput: PropTypes.bool.isRequired,
    mandatory: PropTypes.bool.isRequired,
    lines: PropTypes.string,
    styles: inputStylesPropTypes.isRequired,
    className: PropTypes.string,
  }).isRequired,
  setAttributes: PropTypes.func.isRequired,
  clientId: PropTypes.string.isRequired,
  name: PropTypes.string.isRequired,
};

export { CustomTextAreaEdit };

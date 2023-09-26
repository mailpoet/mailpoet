import { Panel, PanelBody, ToggleControl } from '@wordpress/components';
import { InspectorControls } from '@wordpress/block-editor';
import PropTypes from 'prop-types';
import { useDispatch, useSelect } from '@wordpress/data';

import { MailPoet } from 'mailpoet';
import { CustomFieldSettings } from './custom-field-settings.jsx';
import { formatLabel } from '../label-formatter.jsx';
import { ParagraphEdit } from '../paragraph-edit.jsx';
import { mapCustomFieldFormData } from '../map-custom-field-form-data.jsx';
import { storeName } from '../../store/constants';

function CustomRadioEdit({ attributes, setAttributes, clientId }) {
  const isSaving = useSelect(
    (sel) => sel(storeName).getIsCustomFieldSaving(),
    [],
  );
  const isDeleting = useSelect(
    (sel) => sel(storeName).getIsCustomFieldDeleting(),
    [],
  );
  const { saveCustomField, deleteCustomField, customFieldEdited } =
    useDispatch(storeName);

  const inspectorControls = (
    <InspectorControls>
      <Panel>
        <PanelBody title={MailPoet.I18n.t('customFieldSettings')} initialOpen>
          <CustomFieldSettings
            label={attributes.label}
            mandatory={attributes.mandatory}
            values={attributes.values}
            isSaving={isSaving}
            onSave={(params) =>
              saveCustomField({
                customFieldId: attributes.customFieldId,
                data: {
                  params: mapCustomFieldFormData('radio', params),
                },
                onFinish: () =>
                  setAttributes({
                    mandatory: params.mandatory,
                    values: params.values,
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
            label={MailPoet.I18n.t('displayLabel')}
            checked={!attributes.hideLabel}
            onChange={(hideLabel) => setAttributes({ hideLabel: !hideLabel })}
          />
        </PanelBody>
      </Panel>
    </InspectorControls>
  );

  const getLabel = () => {
    if (attributes.hideLabel) return null;
    return formatLabel(attributes);
  };

  return (
    <ParagraphEdit className={attributes.className}>
      {inspectorControls}
      <span
        className="mailpoet_radio_label"
        data-automation-id="editor_custom_field_radio_buttons_block"
      >
        {getLabel()}
      </span>
      {Array.isArray(attributes.values) &&
        attributes.values.map((value) => (
          <div key={value.id}>
            <label>
              <input
                type="radio"
                disabled
                checked={value.isChecked || false}
                className="mailpoet_radio"
              />
              {value.name}
            </label>
          </div>
        ))}
    </ParagraphEdit>
  );
}

CustomRadioEdit.propTypes = {
  attributes: PropTypes.shape({
    label: PropTypes.string.isRequired,
    customFieldId: PropTypes.number.isRequired,
    values: PropTypes.arrayOf(
      PropTypes.shape({
        name: PropTypes.string.isRequired,
        id: PropTypes.string.isRequired,
        isChecked: PropTypes.bool,
      }),
    ),
    mandatory: PropTypes.bool.isRequired,
    hideLabel: PropTypes.bool,
    className: PropTypes.string,
  }).isRequired,
  setAttributes: PropTypes.func.isRequired,
  clientId: PropTypes.string.isRequired,
};

export { CustomRadioEdit };

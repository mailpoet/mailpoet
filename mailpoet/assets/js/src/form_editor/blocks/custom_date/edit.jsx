import moment from 'moment';
import { Panel, PanelBody } from '@wordpress/components';
import { InspectorControls } from '@wordpress/block-editor';
import PropTypes from 'prop-types';
import MailPoet from 'mailpoet';
import { useDispatch, useSelect } from '@wordpress/data';

import ParagraphEdit from '../paragraph_edit.jsx';
import CustomFieldSettings from './custom_field_settings.jsx';
import FormFieldDate from './date.jsx';
import formatLabel from '../label_formatter.jsx';
import mapCustomFieldFormData from '../map_custom_field_form_data.jsx';

function CustomDateEdit({ attributes, setAttributes, clientId }) {
  const isSaving = useSelect(
    (sel) => sel('mailpoet-form-editor').getIsCustomFieldSaving(),
    [],
  );
  const dateSettings = useSelect(
    (sel) => sel('mailpoet-form-editor').getDateSettingsData(),
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
            label={attributes.label}
            mandatory={attributes.mandatory}
            dateSettings={dateSettings}
            defaultToday={attributes.defaultToday}
            dateFormat={attributes.dateFormat}
            dateType={attributes.dateType}
            isSaving={isSaving}
            onSave={(params) =>
              saveCustomField({
                customFieldId: attributes.customFieldId,
                data: {
                  params: mapCustomFieldFormData('date', params),
                },
                onFinish: () =>
                  setAttributes({
                    mandatory: params.mandatory,
                    dateType: params.dateType,
                    dateFormat: params.dateFormat,
                    defaultToday: params.defaultToday,
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
    </InspectorControls>
  );

  return (
    <ParagraphEdit className={attributes.className}>
      <div className="mailpoet_custom_date">
        {inspectorControls}
        <label
          className="mailpoet_date_label"
          data-automation-id="editor_custom_date_label"
          htmlFor={clientId}
        >
          {formatLabel(attributes)}
        </label>
        <FormFieldDate
          field={{
            name: clientId,
            day_placeholder: MailPoet.I18n.t('customFieldDay'),
            month_placeholder: MailPoet.I18n.t('customFieldMonth'),
            year_placeholder: MailPoet.I18n.t('customFieldYear'),
            params: {
              date_type: attributes.dateType,
              date_format: attributes.dateFormat,
            },
          }}
          item={{
            [clientId]: attributes.defaultToday
              ? moment().format('YYYY-MM-DD')
              : '',
          }}
          addDefaultClasses
          onValueChange={() => {}}
        />
      </div>
    </ParagraphEdit>
  );
}

CustomDateEdit.propTypes = {
  attributes: PropTypes.shape({
    label: PropTypes.string.isRequired,
    dateFormat: PropTypes.string.isRequired,
    dateType: PropTypes.string.isRequired,
    defaultToday: PropTypes.bool,
    mandatory: PropTypes.bool.isRequired,
    customFieldId: PropTypes.number.isRequired,
    className: PropTypes.string,
  }).isRequired,
  clientId: PropTypes.string.isRequired,
  setAttributes: PropTypes.func.isRequired,
};

export default CustomDateEdit;

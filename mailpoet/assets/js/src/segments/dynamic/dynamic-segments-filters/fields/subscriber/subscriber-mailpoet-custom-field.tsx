import { find } from 'lodash/fp';
import { useSelect, useDispatch } from '@wordpress/data';

import { MailPoet } from 'mailpoet';
import { ReactSelect } from 'common/form/react-select/react-select';

import { Text, validateText } from './custom-fields/text';
import { RadioSelect, validateRadioSelect } from './custom-fields/select';
import { Checkbox, validateCheckbox } from './custom-fields/checkbox';
import { CustomFieldDate, validateDate } from './custom-fields/date';

import {
  WordpressRoleFormItem,
  SelectOption,
  WindowCustomFields,
  FilterProps,
} from '../../../types';
import { storeName } from '../../../store';

enum CustomFieldsTypes {
  DATE = 'date',
  TEXT = 'text',
  TEXTAREA = 'textarea',
  RADIO = 'radio',
  CHECKBOX = 'checkbox',
  SELECT = 'select',
}

const validationMap = {
  [CustomFieldsTypes.TEXT]: validateText,
  [CustomFieldsTypes.TEXTAREA]: validateText,
  [CustomFieldsTypes.RADIO]: validateRadioSelect,
  [CustomFieldsTypes.SELECT]: validateRadioSelect,
  [CustomFieldsTypes.CHECKBOX]: validateCheckbox,
  [CustomFieldsTypes.DATE]: validateDate,
};

export function validateMailPoetCustomField(
  formItems: WordpressRoleFormItem,
): boolean {
  const validator: (WordpressRoleFormItem) => boolean =
    validationMap[formItems.custom_field_type];
  if (!validator) return false;

  return validator(formItems);
}

const componentsMap = {
  [CustomFieldsTypes.TEXT]: Text,
  [CustomFieldsTypes.TEXTAREA]: Text,
  [CustomFieldsTypes.RADIO]: RadioSelect,
  [CustomFieldsTypes.SELECT]: RadioSelect,
  [CustomFieldsTypes.CHECKBOX]: Checkbox,
  [CustomFieldsTypes.DATE]: CustomFieldDate,
};

export function MailPoetCustomFields({
  filterIndex,
}: FilterProps): JSX.Element {
  const segment: WordpressRoleFormItem = useSelect(
    (select) => select(storeName).getSegmentFilter(filterIndex),
    [filterIndex],
  );

  const { updateSegmentFilter } = useDispatch(storeName);

  const customFieldsList: WindowCustomFields = useSelect(
    (select) => select(storeName).getCustomFieldsList(),
    [],
  );
  const selectedCustomField = find(
    { id: Number(segment.custom_field_id) },
    customFieldsList,
  );
  const options = customFieldsList.map((currentValue) => ({
    value: currentValue.id.toString(),
    label: currentValue.name,
  }));

  const TypeComponent = componentsMap[segment.custom_field_type];

  return (
    <>
      <div>
        <ReactSelect
          dimension="small"
          isFullWidth
          placeholder={MailPoet.I18n.t('selectCustomFieldPlaceholder')}
          options={options}
          automationId="select-custom-field"
          value={find((option) => {
            if (!segment.custom_field_id) return undefined;
            return segment.custom_field_id === option.value;
          }, options)}
          onChange={(option: SelectOption): void => {
            const customField = find(
              { id: Number(option.value) },
              customFieldsList,
            );
            if (!customField) return;
            void updateSegmentFilter(
              {
                custom_field_id: option.value,
                custom_field_type: customField.type,
                operator: undefined,
                value: undefined,
              },
              filterIndex,
            );
          }}
        />
      </div>
      <div>
        {TypeComponent && (
          <TypeComponent
            customField={selectedCustomField}
            filterIndex={filterIndex}
          />
        )}
      </div>
    </>
  );
}

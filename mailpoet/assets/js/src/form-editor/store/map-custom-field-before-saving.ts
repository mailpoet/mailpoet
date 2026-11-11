import { CustomField } from './form-data-types';

// Don't deep-merge here: lodash merge combines arrays by index, so options
// removed in the sidebar would come back from the stored params.values.
export function mapCustomFieldBeforeSaving(
  customField: CustomField,
  data: Partial<CustomField>,
): CustomField {
  return { ...customField, ...data };
}

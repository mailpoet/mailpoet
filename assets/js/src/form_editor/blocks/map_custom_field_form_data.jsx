function mapFormDataToParams(fieldType, data) {
  switch (fieldType) {
    case 'checkbox':
      return {
        required: data.mandatory ? '1' : undefined,
        values: [{
          is_checked: data.isChecked ? '1' : undefined,
          value: data.checkboxLabel,
        }],
      };
    case 'date':
      return {
        required: data.mandatory ? '1' : undefined,
        date_type: data.dateType,
        date_format: data.dateFormat,
        is_default_today: data.defaultToday ? '1' : undefined,
      };
    case 'radio':
    case 'select':
      return {
        required: data.mandatory ? '1' : undefined,
        values: data.values.map((value) => ({ value: value.name })),
      };
    case 'text':
      return {
        required: data.mandatory ? '1' : undefined,
        validate: data.validate,
      };
    case 'textarea':
      return {
        required: data.mandatory ? '1' : undefined,
        validate: data.validate,
        lines: data.lines ? data.lines : '1',
      };
    default:
      throw new Error(`Invalid custom field type ${fieldType}!`);
  }
}

export default mapFormDataToParams;

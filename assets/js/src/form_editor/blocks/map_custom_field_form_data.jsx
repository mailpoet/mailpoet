function mapFormDataToParams(fieldType, data) {
  switch (fieldType) {
    case 'checkbox':
      return {
        required: data.mandatory ? '1' : '',
        values: [{
          is_checked: data.isChecked ? '1' : '',
          value: data.checkboxLabel,
        }],
      };
    case 'date':
      return {
        required: data.mandatory ? '1' : '',
        date_type: data.dateType,
        date_format: data.dateFormat,
        is_default_today: data.defaultToday ? '1' : '',
      };
    case 'radio':
    case 'select':
      return {
        required: data.mandatory ? '1' : '',
        values: data.values.map((value) => {
          const mapped = { value: value.name };
          if (value.isChecked) {
            mapped.is_checked = '1';
          }
          return mapped;
        }),
      };
    case 'text':
      return {
        required: data.mandatory ? '1' : '',
        validate: data.validate,
      };
    case 'textarea':
      return {
        required: data.mandatory ? '1' : '',
        validate: data.validate,
        lines: data.lines ? data.lines : '1',
      };
    default:
      throw new Error(`Invalid custom field type ${fieldType}!`);
  }
}

export default mapFormDataToParams;

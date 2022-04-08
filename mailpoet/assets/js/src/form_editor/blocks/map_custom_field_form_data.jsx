function mapFormDataToParams(fieldType, data) {
  switch (fieldType) {
    case 'checkbox':
      return {
        label: data.label,
        required: data.mandatory ? '1' : '',
        values: [
          {
            is_checked: data.isChecked ? '1' : '',
            value: data.checkboxLabel,
          },
        ],
      };
    case 'date':
      return {
        label: data.label,
        required: data.mandatory ? '1' : '',
        date_type: data.dateType,
        date_format: data.dateFormat,
        is_default_today: data.defaultToday ? '1' : '',
      };
    case 'radio':
    case 'select':
      return {
        required: data.mandatory ? '1' : '',
        label: data.label,
        values: data.values.map((value) => {
          const mapped = { value: value.name };
          if (value.isChecked) {
            mapped.is_checked = '1';
          } else {
            mapped.is_checked = '';
          }
          return mapped;
        }),
      };
    case 'text':
      return {
        required: data.mandatory ? '1' : '',
        validate: data.validate,
        label: data.label,
      };
    case 'textarea':
      return {
        required: data.mandatory ? '1' : '',
        validate: data.validate,
        lines: data.lines ? data.lines : '1',
        label: data.label,
      };
    default:
      throw new Error(`Invalid custom field type ${fieldType}!`);
  }
}

export default mapFormDataToParams;

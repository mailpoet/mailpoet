import * as customDate from './custom_date/custom_date.jsx';
import * as customText from './custom_text/custom_text.jsx';
import * as customTextArea from './custom_textarea/custom_textarea.jsx';
import * as customRadio from './custom_radio/custom_radio.jsx';
import * as customCheckbox from './custom_checkbox/custom_checkbox.jsx';
import * as customSelect from './custom_select/custom_select.jsx';

export function getCustomFieldBlockSettings(customField) {
  return {
    date: {
      name: customDate.name,
      settings: customDate.getSettings(customField),
    },
    text: {
      name: customText.name,
      settings: customText.getSettings(customField),
    },
    textarea: {
      name: customTextArea.name,
      settings: customTextArea.getSettings(customField),
    },
    radio: {
      name: customRadio.name,
      settings: customRadio.getSettings(customField),
    },
    checkbox: {
      name: customCheckbox.name,
      settings: customCheckbox.getSettings(customField),
    },
    select: {
      name: customSelect.name,
      settings: customSelect.getSettings(customField),
    },
  };
}

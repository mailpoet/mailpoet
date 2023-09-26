import * as customDate from './custom-date/custom-date.jsx';
import * as customText from './custom-text/custom-text.jsx';
import * as customTextArea from './custom-textarea/custom-textarea.jsx';
import * as customRadio from './custom-radio/custom-radio.jsx';
import * as customCheckbox from './custom-checkbox/custom-checkbox.jsx';
import * as customSelect from './custom-select/custom-select.jsx';

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

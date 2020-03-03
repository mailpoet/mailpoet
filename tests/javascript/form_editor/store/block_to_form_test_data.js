export const emailBlock = {
  clientId: 'email',
  isValid: true,
  innerBlocks: [],
  name: 'mailpoet-form/email-input',
  attributes: {
    label: 'Email Address',
    labelWithinInput: false,
  },
};

export const submitBlock = {
  clientId: 'submit',
  isValid: true,
  innerBlocks: [],
  name: 'mailpoet-form/submit-button',
  attributes: {
    label: 'Subscribe!',
  },
};

export const segmentsBlock = {
  clientId: 'segments',
  isValid: true,
  innerBlocks: [],
  name: 'mailpoet-form/segment-select',
  attributes: {
    labelWithinInput: false,
    mandatory: false,
    label: 'Select list(s):',
    values: [
      { id: '6', name: 'Unicorn Truthers' },
      { id: '24', name: 'Carrots are lit', isChecked: true },
      { id: '29', name: 'Daily' },
    ],
  },
};

export const firstNameBlock = {
  clientId: 'first_name',
  isValid: true,
  innerBlocks: [],
  name: 'mailpoet-form/first-name-input',
  attributes: {
    label: 'First Name',
    labelWithinInput: false,
    mandatory: false,
  },
};

export const lastNameBlock = {
  clientId: 'last_name',
  isValid: true,
  innerBlocks: [],
  name: 'mailpoet-form/last-name-input',
  attributes: {
    label: 'Last Name',
    labelWithinInput: false,
    mandatory: false,
  },
};

export const customTextBlock = {
  clientId: '2',
  isValid: true,
  innerBlocks: [],
  name: 'mailpoet-form/custom-text',
  attributes: {
    label: 'Name of the street',
    labelWithinInput: false,
    mandatory: false,
    validate: 'alphanum',
    customFieldId: 1,
  },
};

export const customRadioBlock = {
  clientId: '4',
  isValid: true,
  innerBlocks: [],
  name: 'mailpoet-form/custom-radio',
  attributes: {
    label: 'Options',
    hideLabel: true,
    mandatory: true,
    customFieldId: 2,
    values: [
      { name: 'option 1' },
      { name: 'option 2' },
    ],
  },
};

export const customCheckBox = {
  clientId: '5',
  isValid: true,
  innerBlocks: [],
  name: 'mailpoet-form/custom-checkbox',
  attributes: {
    label: 'Checkbox',
    hideLabel: false,
    mandatory: false,
    customFieldId: 3,
    values: [
      {
        name: 'Check this',
        isChecked: true,
      },
    ],
  },
};

export const customSelectBlock = {
  clientId: '5',
  isValid: true,
  innerBlocks: [],
  name: 'mailpoet-form/custom-select',
  attributes: {
    label: 'Select',
    labelWithinInput: false,
    mandatory: false,
    customFieldId: 6,
    values: [
      { name: 'option 1' },
      { name: 'option 2' },
    ],
  },
};

export const customDateBlock = {
  clientId: '5',
  isValid: true,
  innerBlocks: [],
  name: 'mailpoet-form/custom-date',
  attributes: {
    label: 'Date',
    mandatory: false,
    customFieldId: 6,
    dateType: 'month_year',
    dateFormat: 'MM/YYYY',
    defaultToday: true,
  },
};

export const dividerBlock = {
  clientId: 'some_random_123',
  isValid: true,
  innerBlocks: [],
  name: 'mailpoet-form/divider',
  attributes: {},
};

export const customHtmlBlock = {
  clientId: 'some_random_321',
  isValid: true,
  innerBlocks: [],
  name: 'mailpoet-form/html',
  attributes: {
    content: 'HTML content',
    nl2br: true,
  },
};

export const nestedColumns = {
  clientId: 'columns-1',
  name: 'core/columns',
  isValid: true,
  attributes: {},
  innerBlocks: [
    {
      clientId: 'column-1-1',
      name: 'core/column',
      isValid: true,
      attributes: {
        width: 66.66,
        verticalAlignment: 'center',
      },
      innerBlocks: [
        {
          clientId: 'columns-1-1',
          name: 'core/columns',
          isValid: true,
          attributes: {},
          innerBlocks: [
            {
              clientId: 'column-1-1-1',
              name: 'core/column',
              isValid: true,
              attributes: {},
              innerBlocks: [firstNameBlock],
            },
            {
              clientId: 'columns-1-1-2',
              name: 'core/column',
              isValid: true,
              attributes: {},
              innerBlocks: [],
            },
          ],
        },
        dividerBlock,
      ],
    },
    {
      clientId: 'column-1-2',
      name: 'core/column',
      isValid: true,
      attributes: {
        width: 33.33,
      },
      innerBlocks: [submitBlock],
    },
  ],
};

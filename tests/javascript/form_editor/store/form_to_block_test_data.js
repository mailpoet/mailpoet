export const emailInput = {
  type: 'text',
  name: 'Email',
  id: 'email',
  unique: '0',
  static: '1',
  params: {
    label: 'Email',
  },
  position: null,
};
export const firstNameInput = {
  type: 'text',
  name: 'First name',
  id: 'first_name',
  unique: '1',
  static: '0',
  params: {
    label: 'First Name',
  },
  position: null,
};
export const lastNameInput = {
  type: 'text',
  name: 'Last name',
  id: 'last_name',
  unique: '1',
  static: '0',
  params: {
    label: 'Last Name',
  },
  position: null,
};
export const segmentsInput = {
  type: 'segment',
  name: 'List selection',
  id: 'segments',
  unique: '1',
  static: '0',
  params: {
    label: 'Select list(s):',
    values: [
      {
        id: '6',
        name: 'Unicorn Truthers',
      },
      {
        id: '24',
        is_checked: '1',
        name: 'Carrots are lit',
      },
      {
        id: '29',
        name: 'Daily',
      },
    ],
  },
  position: null,
};
export const submitInput = {
  type: 'submit',
  name: 'Submit',
  id: 'submit',
  unique: '0',
  static: '1',
  params: {
    label: 'Subscribe!',
  },
  position: null,
};
export const customTextInput = {
  type: 'text',
  name: 'Street name',
  id: '1',
  unique: '1',
  static: '0',
  params: {
    required: '',
    validate: 'alphanum',
    label: 'Name of the street',
    label_within: '1',
  },
  position: null,
};
export const customRadioInput = {
  type: 'radio',
  name: 'Options',
  id: '3',
  unique: '1',
  static: '0',
  params: {
    required: '',
    label: 'Options',
    hide_label: '1',
    values: [
      {
        value: 'option 1',
      },
    ],
  },
  position: null,
};
export const customSelectInput = {
  type: 'select',
  name: 'Custom select',
  id: '5',
  unique: '1',
  static: '0',
  params: {
    required: '',
    label: 'Select',
    label_within: '1',
    values: [
      {
        value: 'option 1',
      },
    ],
  },
  position: null,
};
export const customCheckboxInput = {
  type: 'checkbox',
  name: 'Custom check',
  id: '4',
  unique: '1',
  static: '0',
  params: {
    required: '',
    label: 'Check this',
    hide_label: '',
    values: [
      {
        value: 'Check',
        is_checked: '1',
      },
    ],
  },
  position: null,
};
export const customDateInput = {
  type: 'date',
  name: 'Custom date',
  id: '6',
  unique: '1',
  static: '0',
  params: {
    required: '1',
    label: 'Date',
    date_type: 'month_year',
    date_format: 'MM/YYYY',
    is_default_today: true,
  },
  position: null,
};
export const divider = {
  type: 'divider',
  name: 'Divider',
  id: 'divider',
  unique: '0',
  static: '0',
  params: '',
  position: null,
};

export const customHtml = {
  type: 'html,',
  name: 'Custom text or HTML',
  id: 'html',
  unique: '0',
  static: '0',
  params: {
    text: 'test',
    nl2br: '1',
  },
  position: null,
};

export const nestedColumns = {
  position: '2',
  type: 'columns',
  params: {
    vertical_alignment: 'center',
  },
  body: [
    {
      position: '1',
      type: 'column',
      params: {
        width: 66.66,
        vertical_alignment: 'center',
      },
      body: [
        {
          position: '1',
          type: 'columns',
          body: [
            {
              position: '1',
              type: 'column',
              params: {
                width: 50,
              },
              body: [firstNameInput],
            },
            {
              position: '2',
              type: 'column',
              params: {
                width: 50,
              },
              body: [],
            },
          ],
        },
        divider,
      ],
    },
    {
      position: '2',
      type: 'column',
      params: {
        width: 33.33,
      },
      body: [submitInput],
    },
  ],
};

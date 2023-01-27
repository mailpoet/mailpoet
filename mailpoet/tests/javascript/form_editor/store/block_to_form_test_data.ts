export const emailBlock = {
  clientId: 'email',
  isValid: true,
  innerBlocks: [],
  name: 'mailpoet-form/email-input',
  attributes: {
    label: 'Email Address',
    labelWithinInput: false,
    styles: {
      fullWidth: false,
      inheritFromTheme: true,
    },
  },
};

export const submitBlock = {
  clientId: 'submit',
  isValid: true,
  innerBlocks: [],
  name: 'mailpoet-form/submit-button',
  attributes: {
    label: 'Subscribe!',
    styles: {
      fullWidth: false,
      inheritFromTheme: true,
    },
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
    values: [{ id: '6' }, { id: '24', isChecked: true }, { id: '29' }],
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
    styles: {
      fullWidth: false,
      inheritFromTheme: true,
    },
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
    styles: {
      fullWidth: false,
      inheritFromTheme: true,
    },
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
    styles: {
      fullWidth: false,
      inheritFromTheme: true,
    },
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
    values: [{ name: 'option 1' }, { name: 'option 2' }],
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
    values: [{ name: 'option 1' }, { name: 'option 2' }],
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
  attributes: {
    className: null,
    height: 23,
    type: 'divider',
    style: 'solid',
    dividerHeight: 34,
    dividerWidth: 65,
    color: 'red',
  },
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

export const headingBlock = {
  clientId: 'd9dd2b88-d01f-4a5e-80a4-afaa74de1b00',
  name: 'core/heading',
  isValid: true,
  attributes: {
    content: '',
    level: 2,
  },
  innerBlocks: [],
};

export const paragraphBlock = {
  clientId: '895d5bfd-9fef-4b58-83be-7259a7375785',
  name: 'core/paragraph',
  isValid: true,
  attributes: {
    content: 'content',
    dropCap: true,
    align: 'center',
  },
  innerBlocks: [],
};

export const imageBlock = {
  clientId: '895d5bfd-9fef-4b58-83be-7259a7375786',
  name: 'core/image',
  isValid: true,
  attributes: {
    className: 'my-class',
    align: 'center',
    url: 'http://example.com/image.jpg',
    alt: 'Alt text',
    title: 'Title',
    caption: 'Caption',
    linkDestination: 'none',
    link: 'http://example.com',
    href: 'http://example.com/link',
    linkClass: 'link-class',
    rel: 'linkRel',
    linkTarget: '_blank',
    id: 123,
    sizeSlug: 'medium',
    width: 100,
    height: 200,
  },
  innerBlocks: [],
};

export const nestedColumns = {
  clientId: 'columns-1',
  name: 'core/columns',
  isValid: true,
  attributes: {
    verticalAlignment: 'center',
    isStackedOnMobile: false,
    style: {
      spacing: {
        padding: {
          top: '1em',
          right: '2em',
          bottom: '3em',
          left: '4em',
        },
      },
    },
  },
  innerBlocks: [
    {
      clientId: 'column-1-1',
      name: 'core/column',
      isValid: true,
      attributes: {
        width: '200px',
        verticalAlignment: 'center',
        style: {
          spacing: {
            padding: {
              top: '10px',
              right: '20px',
              bottom: '30px',
              left: '40px',
            },
          },
        },
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
              attributes: {
                width: '40px',
              },
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
        width: '33%',
      },
      innerBlocks: [submitBlock],
    },
  ],
};

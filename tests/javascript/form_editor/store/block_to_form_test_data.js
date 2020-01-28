// eslint-disable-next-line import/prefer-default-export
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
              innerBlocks: [
                {
                  clientId: 'first-name-1-1-1',
                  name: 'mailpoet-form/last-name-input',
                  isValid: true,
                  attributes: {
                    label: 'Last name',
                    labelWithinInput: true,
                    mandatory: true,
                  },
                  innerBlocks: [],
                },
              ],
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
        {
          clientId: 'divider-1-1-2',
          name: 'mailpoet-form/divider',
          isValid: true,
          attributes: {},
          innerBlocks: [],
        },
      ],
    },
    {
      clientId: 'column-1-2',
      name: 'core/column',
      isValid: true,
      attributes: {
        width: 33.33,
      },
      innerBlocks: [
        {
          clientId: 'submit-1-2',
          isValid: true,
          innerBlocks: [],
          name: 'mailpoet-form/submit-button',
          attributes: {
            label: 'Subscribe!',
          },
        },
      ],
    },
  ],
};

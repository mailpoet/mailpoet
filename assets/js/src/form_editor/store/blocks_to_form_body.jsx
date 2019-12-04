/**
 * Transforms blocks to form.body data structure.
 * @param blocks - blocks representation taken from @wordpress/block-editor
 */
export default (blocks) => {
  if (!Array.isArray(blocks)) {
    throw new Error('Mapper expects blocks to be an array.');
  }
  return blocks.map((block, index) => {
    const mapped = {
      type: 'text',
      unique: '0',
      static: '1',
      position: (index + 1).toString(),
      params: {
        label: block.attributes.label,
      },
    };
    if (block.attributes.mandatory) {
      mapped.params.required = '1';
    }
    if (block.attributes.labelWithinInput) {
      mapped.params.label_within = '1';
    }

    switch (block.name) {
      case 'mailpoet-form/email-input':
        return {
          id: 'email',
          name: 'Email',
          required: '1',
          ...mapped,
        };
      case 'mailpoet-form/first-name-input':
        return {
          id: 'first_name',
          unique: '1',
          static: '0',
          name: 'First name',
          ...mapped,
        };
      case 'mailpoet-form/last-name-input':
        return {
          id: 'last_name',
          unique: '1',
          static: '0',
          name: 'Last name',
          ...mapped,
        };
      case 'mailpoet-form/submit-button':
        return {
          id: 'submit',
          type: 'submit',
          name: 'Submit',
          ...mapped,
        };
      default:
        return null;
    }
  }).filter(Boolean);
};

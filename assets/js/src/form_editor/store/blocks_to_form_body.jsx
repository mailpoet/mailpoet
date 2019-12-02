export default (blocks) => {
  let position = 1;
  return blocks.map((block) => {
    const mapped = {
      params: {},
    };
    switch (block.name) {
      case 'mailpoet-form/email-input':
        mapped.id = 'email';
        mapped.type = 'text';
        mapped.unique = '0';
        mapped.static = '1';
        mapped.name = 'Email';
        mapped.params.label = block.attributes.label;
        mapped.params.required = '1';
        if (block.attributes.labelWithinInput) {
          mapped.params.label_within = '1';
        }
        break;
      case 'mailpoet-form/submit-button':
        mapped.id = 'submit';
        mapped.type = 'submit';
        mapped.name = 'Submit';
        mapped.unique = '0';
        mapped.static = '1';
        mapped.params.label = block.attributes.label;
        break;
      default:
        return null;
    }
    mapped.position = position.toString();
    position += 1;
    return mapped;
  }).filter(Boolean);
};

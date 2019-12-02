export default (formData, formBlocks) => {
  const errors = [];
  if (!formData.settings.segments || formData.settings.segments.length === 0) {
    errors.push('missing-lists');
  }
  const emailInput = formBlocks.filter((block) => (block.attributes.id === 'email'));
  const submit = formBlocks.filter((block) => (block.attributes.id === 'submit'));
  if (!emailInput) {
    errors.push('missing-email-input');
  }
  if (!submit) {
    errors.push('missing-submit');
  }
  return errors;
};

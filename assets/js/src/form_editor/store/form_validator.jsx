import findBlock from './find_block.jsx';

export default (formData, formBlocks) => {
  if (!formData || !formData.settings || !Array.isArray(formData.settings.segments)) {
    throw new Error('formData.settings.segments are expected to be an array.');
  }
  if (!Array.isArray(formBlocks)) {
    throw new Error('formBlocks are expected to be an array.');
  }
  const errors = [];
  if (!formData.settings.segments || formData.settings.segments.length === 0) {
    errors.push('missing-lists');
  }
  const emailInput = findBlock(formBlocks, 'mailpoet-form/email-input');
  const submit = findBlock(formBlocks, 'mailpoet-form/submit-button');
  if (!emailInput) {
    errors.push('missing-email-input');
  }
  if (!submit) {
    errors.push('missing-submit');
  }
  return errors;
};

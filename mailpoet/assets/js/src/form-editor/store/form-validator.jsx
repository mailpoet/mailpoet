import { findBlock } from './find_block';

export const validateForm = (formData, formBlocks) => {
  if (
    !formData ||
    !formData.settings ||
    !Array.isArray(formData.settings.segments)
  ) {
    throw new Error('formData.settings.segments are expected to be an array.');
  }
  if (!Array.isArray(formBlocks)) {
    throw new Error('formBlocks are expected to be an array.');
  }
  const customSegmentsBlock = findBlock(
    formBlocks,
    'mailpoet-form/segment-select',
  );
  const errors = [];
  if (
    (!customSegmentsBlock ||
      customSegmentsBlock.attributes.values.length === 0) &&
    (!formData.settings.segments || formData.settings.segments.length === 0)
  ) {
    errors.push('missing-lists');
  }
  if (
    customSegmentsBlock &&
    customSegmentsBlock.attributes.values.length === 0
  ) {
    errors.push('missing-lists-in-custom-segments-block');
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

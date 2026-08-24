import { findBlock } from './find-block';

export const validateForm = (
  formData,
  formBlocks,
  isTrackingConsentCaptureEnabled = false,
) => {
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
  // "Required" here means the checkbox is on the form, not that the subscriber
  // has to tick it. The block is still rendered unticked and optional.
  if (isTrackingConsentCaptureEnabled) {
    const trackingConsent = findBlock(
      formBlocks,
      'mailpoet-form/tracking-consent',
    );
    if (!trackingConsent) {
      errors.push('missing-tracking-consent');
    }
  }
  return errors;
};

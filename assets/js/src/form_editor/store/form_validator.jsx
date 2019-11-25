export default (formData) => {
  const errors = [];
  if (!formData.settings.segments || formData.settings.segments.length === 0) {
    errors.push('missing-lists');
  }
  return errors;
};

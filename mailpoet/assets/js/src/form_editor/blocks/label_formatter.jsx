export const formatLabel = (attributes) => {
  const labelText = attributes.label ? attributes.label : '';
  if (attributes.mandatory) {
    return `${labelText} *`;
  }
  return labelText;
};

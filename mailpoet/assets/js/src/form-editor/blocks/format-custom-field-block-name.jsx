import slugify from 'slugify';

export function formatCustomFieldBlockName(blockName, customField) {
  const name = slugify(customField.name, { lower: true })
    .replace(/[^a-z0-9]+/g, '')
    .replace(/-$/, '');
  return `${blockName}-${name}`;
}

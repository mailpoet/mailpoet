import slugify from 'slugify';

export function formatCustomFieldBlockName(blockName, customField) {
  let name = slugify(customField.name, { lower: true })
    .replace(/[^a-z0-9]+/g, '')
    .replace(/-$/, '');

  // Ensure unique block names by appending ID if the slug is empty or too short
  // (which can happen with certain character sets)
  if (!name || name.length < 2) {
    name = `field${customField.id}`;
  }

  return `${blockName}-${name}`;
}

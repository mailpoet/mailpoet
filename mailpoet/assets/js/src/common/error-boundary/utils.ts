export const getComponentDisplayName = (component): string =>
  component.displayName || component.name || 'Unknown application/component';

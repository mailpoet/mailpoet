import { ComponentType } from 'react';

export const getComponentDisplayName = (component: ComponentType): string =>
  component.displayName || component.name || 'Unknown application/component';

import { orders } from './orders';
import { SectionData } from '../types';

export const getSampleData = (sectionId: string): SectionData | undefined => {
  switch (sectionId) {
    case 'orders':
      return orders;
    default:
      return undefined;
  }
};

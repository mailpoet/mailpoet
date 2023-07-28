import { orders } from './orders';
import { subscribers } from './subscribers';
import { SectionData } from '../types';

export const getSampleData = (sectionId: string): SectionData | undefined => {
  switch (sectionId) {
    case 'orders':
      return orders;
    case 'subscribers':
      return subscribers;
    default:
      return undefined;
  }
};

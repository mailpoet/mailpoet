import { Item } from './item';

export type Group = {
  type: string;
  title?: string;
  label: string;
  items: Item[];
};

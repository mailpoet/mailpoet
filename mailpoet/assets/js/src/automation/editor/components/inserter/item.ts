import { ComponentType } from 'react';

export type Item = {
  key: string;
  title: string;
  description: string;
  icon: ComponentType | JSX.Element;
  isDisabled?: boolean;
};

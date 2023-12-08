import { DropdownOption } from '@wordpress/components/build-types/dropdown-menu/types';

export type Item = {
  key: string;
  control: DropdownOption;
  slot?: JSX.Element;
};

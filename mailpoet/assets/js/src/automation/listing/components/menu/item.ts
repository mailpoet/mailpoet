import { DropdownMenu } from '@wordpress/components';

import Control = DropdownMenu.Control;

export type Item = {
  key: string;
  control: Control;
  slot?: JSX.Element;
};

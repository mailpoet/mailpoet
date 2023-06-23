import { select } from '@wordpress/data';
import { FormTokenItem } from '../../editor/components';
import { storeName } from '../../editor/store';

type Segment = FormTokenItem & {
  type: string;
};

export type Context = {
  segments?: Segment[];
  userRoles?: FormTokenItem[];
};

export const getContext = (): Context =>
  select(storeName).getContext('mailpoet') as Context;

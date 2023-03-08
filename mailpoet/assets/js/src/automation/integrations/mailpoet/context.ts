import { select } from '@wordpress/data';
import { FormTokenItem } from '../../editor/components';
import { store } from '../../editor/store/store';

type Segment = FormTokenItem & {
  type: string;
};

export type Context = {
  segments?: Segment[];
};

export const getContext = (): Context =>
  select(store).getContext('mailpoet') as Context;

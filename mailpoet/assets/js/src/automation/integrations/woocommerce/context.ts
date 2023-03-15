import { select } from '@wordpress/data';
import { storeName } from '../../editor/store';

export type Context = {
  order_statuses?: Record<string, string> | undefined;
};

export const getContext = (): Context =>
  select(storeName).getContext('woocommerce') as Context;

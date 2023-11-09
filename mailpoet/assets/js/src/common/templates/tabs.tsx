import { ComponentType, ReactNode } from 'react';
import { TabPanel as WpTabPanel } from '@wordpress/components';
import { Badge } from '@woocommerce/components';

// Tab['title'] is typed as string but supports React Nodes
export const TabPanel = WpTabPanel as ComponentType<
  Omit<WpTabPanel.Props, 'tabs'> & {
    tabs: readonly (Omit<WpTabPanel.Props['tabs'][number], 'title'> & {
      title: ReactNode;
    })[];
  }
>;

type Props = {
  title: string;
  count: number;
};

export function TabTitle({ title, count }: Props): JSX.Element {
  return (
    <>
      <span>{title}</span>
      <Badge count={count} />
    </>
  );
}

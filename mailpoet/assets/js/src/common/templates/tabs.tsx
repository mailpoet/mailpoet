import { ComponentProps, ComponentType, ReactNode } from 'react';
import { TabPanel as WpTabPanel } from '@wordpress/components';
import { Badge } from '@woocommerce/components';
import { TabPanelProps } from '@wordpress/components/build-types/tab-panel/types';

// Tab['title'] is typed as string but supports React Nodes
const FixedTabPanel = WpTabPanel as ComponentType<
  Omit<TabPanelProps, 'tabs'> & {
    tabs: readonly (Omit<TabPanelProps['tabs'][number], 'title'> & {
      title: ReactNode;
    })[];
  }
>;

export function TabPanel(
  props: ComponentProps<typeof FixedTabPanel>,
): JSX.Element {
  return <FixedTabPanel className="mailpoet-templates-tab-panel" {...props} />;
}

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

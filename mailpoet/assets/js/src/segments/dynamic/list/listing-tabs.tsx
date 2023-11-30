import { useSelect } from '@wordpress/data';
import { useCallback, useMemo } from 'react';
import { useLocation } from 'react-router-dom';
import { TabPanel } from '@wordpress/components';
import { __, _x } from '@wordpress/i18n';
import { storeName } from '../store';
import { ListingTabContent } from './listing-tab-content';
import {
  getTabFromLocation,
  updateDynamicQuery,
  updateDynamicQueryFromLocation,
} from './listing-helpers';

const tabConfig = [
  {
    name: 'all',
    title: __('All', 'mailpoet'),
    className: 'mailpoet-tab-all',
  },
  {
    name: 'trash',
    title: _x('Trash', 'noun', 'mailpoet'),
    className: 'mailpoet-tab-trash',
  },
] as const;

export function ListingTabs(): JSX.Element {
  const location = useLocation();
  const { dynamicSegmentQuery, dynamicSegmentsGroups } = useSelect((s) => ({
    dynamicSegments: s(storeName).getDynamicSegments(),
    dynamicSegmentQuery: s(storeName).getDynamicSegmentsQuery(),
    dynamicSegmentsGroups: s(storeName).getDynamicSegmentsGroups(),
  }));

  const tabs = useMemo(
    () =>
      tabConfig.map((tab) => {
        const currentGroup = dynamicSegmentsGroups?.find(
          (group) => tab.name === group.name,
        );
        const count = currentGroup?.count ?? 0;
        return {
          name: tab.name,
          title: (
            <>
              <span>{tab.title}</span>
              {count > 0 && <span className="count">{count}</span>}
            </>
          ) as any, // eslint-disable-line @typescript-eslint/no-explicit-any -- typed as string but supports JSX
          className: tab.className,
        };
      }),
    [dynamicSegmentsGroups],
  );

  const renderTabs = useCallback((tab) => <ListingTabContent tab={tab} />, []);

  return (
    <TabPanel
      className="mailpoet-filter-tab-panel"
      tabs={tabs}
      initialTabName={getTabFromLocation(location.pathname)}
      onSelect={(tab) => {
        if (dynamicSegmentQuery === null) {
          updateDynamicQueryFromLocation(location.pathname);
          return;
        }
        if (dynamicSegmentQuery.group === tab) {
          return;
        }
        updateDynamicQuery({ group: tab, offset: 0 });
      }}
    >
      {renderTabs}
    </TabPanel>
  );
}

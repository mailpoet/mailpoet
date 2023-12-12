import { dispatch, select } from '@wordpress/data';
import { CurrentView, storeName } from '../store';

type ValidTabs = 'automation-flow' | 'emails' | 'orders' | 'subscribers';
export function openTab(tab: ValidTabs, currentView?: CurrentView): void {
  if (currentView) {
    const section = select(storeName).getSection(tab);
    const payload = {
      ...section,
      customQuery: {
        ...section.customQuery,
        filter: {
          ...currentView.filters,
        },
      },
      currentView,
    };
    void dispatch(storeName).updateSection(payload);
  }

  const classMap: Record<ValidTabs, string> = {
    'automation-flow': 'mailpoet-analytics-tab-flow',
    emails: 'mailpoet-analytics-tab-emails',
    orders: 'mailpoet-analytics-tab-orders',
    subscribers: 'mailpoet-analytics-tab-subscribers',
  };
  const tabElement: HTMLButtonElement | null = document.querySelector(
    `.${classMap[tab]}`,
  );
  tabElement?.click();
}

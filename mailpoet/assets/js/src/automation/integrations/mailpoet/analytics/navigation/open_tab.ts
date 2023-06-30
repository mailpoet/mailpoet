type ValidTabs = 'automation-flow' | 'emails' | 'orders' | 'subscribers';
export function openTab(tab: ValidTabs): void {
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

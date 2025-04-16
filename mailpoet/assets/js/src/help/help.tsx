import { createRoot } from 'react-dom/client';
import { MailPoet } from 'mailpoet';
import { __ } from '@wordpress/i18n';
import { KnowledgeBase } from 'help/knowledge-base';
import { SystemInfo } from 'help/system-info';
import { SystemStatus } from 'help/system-status';
import { YourPrivacy } from 'help/your-privacy';
import { GlobalContext, useGlobalContextValue } from 'context';
import { GlobalNotices } from 'notices/global-notices';
import { MssAccessNotices } from 'notices/mss-access-notices';
import { Notices } from 'notices/notices';
import { RoutedTabs } from '../common/tabs/routed-tabs';
import { registerTranslations, Tab } from '../common';
import { PageHeader } from '../common/page-header/page-header';

function App(): JSX.Element {
  return (
    <GlobalContext.Provider value={useGlobalContextValue(window)}>
      <GlobalNotices />
      <Notices />
      <PageHeader heading={__('Help', 'mailpoet')} />
      <MssAccessNotices />
      <RoutedTabs activeKey="knowledgeBase">
        <Tab
          key="knowledgeBase"
          title={MailPoet.I18n.t('tabKnowledgeBaseTitle')}
        >
          <KnowledgeBase />
        </Tab>
        <Tab key="systemStatus" title={MailPoet.I18n.t('tabSystemStatusTitle')}>
          <SystemStatus />
        </Tab>
        <Tab key="systemInfo" title={MailPoet.I18n.t('tabSystemInfoTitle')}>
          <SystemInfo />
        </Tab>
        <Tab key="yourPrivacy" title={MailPoet.I18n.t('tabYourPrivacyTitle')}>
          <YourPrivacy />
        </Tab>
      </RoutedTabs>
    </GlobalContext.Provider>
  );
}

const container = document.getElementById('help_container');

if (container) {
  registerTranslations();
  const root = createRoot(container);
  root.render(<App />);
}

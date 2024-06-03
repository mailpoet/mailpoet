import { createRoot } from 'react-dom/client';
import { MailPoet } from 'mailpoet';
import { KnowledgeBase } from 'help/knowledge-base.tsx';
import { SystemInfo } from 'help/system-info.tsx';
import { SystemStatus } from 'help/system-status.jsx';
import { YourPrivacy } from 'help/your-privacy.jsx';
import { GlobalContext, useGlobalContextValue } from 'context';
import { GlobalNotices } from 'notices/global-notices.jsx';
import { MssAccessNotices } from 'notices/mss-access-notices';
import { Notices } from 'notices/notices.jsx';
import { RoutedTabs } from '../common/tabs/routed-tabs';
import { registerTranslations, Tab } from '../common';
import { TopBar } from '../common/top-bar/top-bar';

function App() {
  return (
    <GlobalContext.Provider value={useGlobalContextValue(window)}>
      <TopBar />
      <GlobalNotices />
      <Notices />
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

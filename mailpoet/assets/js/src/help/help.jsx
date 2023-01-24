import ReactDOM from 'react-dom';
import { MailPoet } from 'mailpoet';
import { KnowledgeBase } from 'help/knowledge_base.jsx';
import { SystemInfo } from 'help/system_info.tsx';
import { SystemStatus } from 'help/system_status.jsx';
import { YourPrivacy } from 'help/your_privacy.jsx';
import { GlobalContext, useGlobalContextValue } from 'context/index.jsx';
import { Notices } from 'notices/notices.jsx';
import { RoutedTabs } from '../common/tabs/routed_tabs';
import { Tab } from '../common';
import { TopBar } from '../common/top_bar/top_bar';

function App() {
  return (
    <GlobalContext.Provider value={useGlobalContextValue(window)}>
      <TopBar />
      <Notices />
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
  ReactDOM.render(<App />, container);
}

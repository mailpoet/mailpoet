import { GlobalNotices } from 'notices/global-notices';
import { MssAccessNotices } from 'notices/mss-access-notices';
import { Notices } from 'notices/notices.jsx';
import { Loading } from 'common/loading';
import { t } from 'common/functions';
import { RoutedTabs } from 'common/tabs/routed-tabs';
import { Tab } from 'common/tabs/tab';
import { TopBar } from 'common/top-bar/top-bar';
import { UnsavedChangesNotice } from 'common/notices/unsaved-changes-notice';
import {
  Advanced,
  Basics,
  KeyActivation,
  SendWith,
  SignupConfirmation,
  WooCommerce,
} from './pages';
import { useSelector } from './store/hooks';

export function Settings() {
  const isSaving = useSelector('isSaving')();
  const hasWooCommerce = useSelector('hasWooCommerce')();
  return (
    <>
      <TopBar />
      {isSaving && <Loading />}
      <GlobalNotices />
      <Notices />
      <MssAccessNotices />
      <UnsavedChangesNotice storeName="mailpoet-settings" />
      <RoutedTabs activeKey="basics">
        <Tab
          key="basics"
          route="basics/:showModal?"
          title={t('basicsTab')}
          automationId="basic_settings_tab"
        >
          <Basics />
        </Tab>
        <Tab
          key="signup"
          title={t('signupConfirmationTab')}
          automationId="signup_settings_tab"
        >
          <SignupConfirmation />
        </Tab>
        <Tab
          key="mta"
          route="mta/:subPage?"
          title={t('sendWithTab')}
          automationId="send_with_settings_tab"
        >
          <SendWith />
        </Tab>
        {hasWooCommerce && (
          <Tab
            key="woocommerce"
            title={t('wooCommerceTab')}
            automationId="woocommerce_settings_tab"
          >
            <WooCommerce />
          </Tab>
        )}
        <Tab
          key="advanced"
          title={t('advancedTab')}
          automationId="settings-advanced-tab"
        >
          <Advanced />
        </Tab>
        <Tab
          key="premium"
          title={t('keyActivationTab')}
          automationId="activation_settings_tab"
        >
          <KeyActivation subscribersCount={window.mailpoet_subscribers_count} />
        </Tab>
      </RoutedTabs>
    </>
  );
}

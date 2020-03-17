import React from 'react';
import { useLocation } from 'react-router-dom';
import { t } from 'common/functions';
import { useSelector } from 'settings/store/hooks';
import TabLink from './tab_link';

export default () => {
  const { pathname } = useLocation();
  const [, current] = pathname.split('/');
  const hasWooCommerce = useSelector('hasWooCommerce')();

  return (
    <h2 className="nav-tab-wrapper">
      <TabLink
        name="basics"
        current={current}
        automationId="basic_settings_tab"
      >
        {t('basicsTab')}
      </TabLink>
      <TabLink
        name="signup"
        current={current}
        automationId="signup_settings_tab"
      >
        {t('signupConfirmationTab')}
      </TabLink>
      <a
        className="nav-tab"
        href="?page=mailpoet-settings#mta"
        data-automation-id="send_with_settings_tab"
      >
        {t('sendWithTab')}
      </a>
      {hasWooCommerce && (
        <TabLink
          name="woocommerce"
          current={current}
          automationId="woocommerce_settings_tab"
        >
          {t('wooCommerceTab')}
        </TabLink>
      )}
      <a
        className="nav-tab"
        href="?page=mailpoet-settings#advanced"
        data-automation-id="settings-advanced-tab"
      >
        {t('advancedTab')}
      </a>
      <TabLink
        name="premium"
        current={current}
        automationId="activation_settings_tab"
      >
        {t('keyActivationTab')}
      </TabLink>
    </h2>
  );
};

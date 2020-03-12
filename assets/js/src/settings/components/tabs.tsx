import React from 'react';
import { useLocation } from 'react-router-dom';
import { useSelector } from 'settings/store/hooks';
import { t } from 'settings/utils';
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
        {t`basicsTab`}
      </TabLink>
      <a
        className="nav-tab"
        href="?page=mailpoet-settings#signup"
        data-automation-id="signup_settings_tab"
      >
        {t`signupConfirmationTab`}
      </a>
      <a
        className="nav-tab"
        href="?page=mailpoet-settings#mta"
        data-automation-id="send_with_settings_tab"
      >
        {t`sendWithTab`}
      </a>
      {hasWooCommerce && (
        <a
          className="nav-tab"
          href="?page=mailpoet-settings#woocommerce"
          data-automation-id="woocommerce_settings_tab"
        >
          {t`wooCommerceTab`}
        </a>
      )}
      <a
        className="nav-tab"
        href="?page=mailpoet-settings#advanced"
        data-automation-id="settings-advanced-tab"
      >
        {t`advancedTab`}
      </a>
      <a
        className="nav-tab"
        href="?page=mailpoet-settings#premium"
        data-automation-id="activation_settings_tab"
      >
        {t`keyActivationTab`}
      </a>
    </h2>
  );
};

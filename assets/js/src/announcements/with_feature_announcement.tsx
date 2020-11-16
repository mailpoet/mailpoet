import React from 'react';
import MailPoet from 'mailpoet';
import ReactStringReplace from 'react-string-replace';
import jQuery from 'jquery';
import { noop } from 'lodash';

interface FeatureAnnouncementWindow extends Window {
  Beamer: {
    show: () => void;
  };
  mailpoet_feature_announcement_has_news: boolean;
  mailpoet_update_available: boolean;
  beamer_config: {
    product_id: string;
    selector: string;
    language: string;
    callback: () => void;
    filter?: string;
  };
  mailpoet_user_locale: string;
}

declare let window: FeatureAnnouncementWindow;

export const withFeatureAnnouncement = <P extends object>(
  Component: React.ComponentType<P>
): React.FC<Omit<P, 'hasNews'|'onBeamerClick'>> => {
  const isBeamerInitialized = () => typeof window.Beamer !== 'undefined';
  let showDot = window.mailpoet_feature_announcement_has_news;
  let beamerCallback;

  function showPluginUpdateNotice() {
    if (!window.mailpoet_update_available || document.getElementById('mailpoet_update_notice')) {
      return;
    }
    const updateMailPoetNotice = ReactStringReplace(
      MailPoet.I18n.t('updateMailPoetNotice'),
      /\[link\](.*?)\[\/link\]/,
      (match) => `<a href="update-core.php">${match}</a>`
    ).join('');
    jQuery('#beamerOverlay').append(
      `<p id="mailpoet_update_notice" class="mailpoet_in_beamer_update_notice">${updateMailPoetNotice}</p>`
    );
  }

  function updateLastAnnouncementSeenValue() {
    // eslint-disable-next-line @typescript-eslint/camelcase
    const data = { last_announcement_seen: Math.floor(Date.now() / 1000) };
    MailPoet.Ajax.post({
      api_version: MailPoet.apiVersion,
      endpoint: 'user_flags',
      action: 'set',
      data,
    });
  }

  function loadBeamer() {
    // eslint-disable-next-line @typescript-eslint/camelcase
    window.beamer_config = {
      product_id: 'VvHbhYWy7118',
      selector: '#beamer-empty-element',
      language: window.mailpoet_user_locale,
      callback: beamerCallback,
    };
    if (MailPoet.isWoocommerceActive) {
      window.beamer_config.filter = 'woocommerce';
    }
    MailPoet.Modal.loading(true);
    // eslint-disable-next-line @typescript-eslint/camelcase
    window.mailpoet_feature_announcement_has_news = false;
    const s = document.createElement('script');
    s.type = 'text/javascript';
    s.src = 'https://app.getbeamer.com/js/beamer-embed.js';
    document.getElementsByTagName('body')[0].appendChild(s);
  }

  function showBeamer(event = null) {
    if (event) {
      event.preventDefault();
    }
    if (!isBeamerInitialized()) {
      loadBeamer();
      return;
    }
    showDot = false;
    beamerCallback = noop; // We show Beamer panel only on first callback after initialization
    MailPoet.Modal.loading(false);
    window.Beamer.show();
    updateLastAnnouncementSeenValue();
    showPluginUpdateNotice();
  }

  beamerCallback = () => {
    if (!isBeamerInitialized()) {
      return;
    }
    showBeamer();
  };

  return ({
    ...props
  }: Omit<P, 'hasNews'|'onBeamerClick'>) => (
    <Component
      {...props as P}
      onBeamerClick={showBeamer}
      hasNews={showDot}
    />
  );
};

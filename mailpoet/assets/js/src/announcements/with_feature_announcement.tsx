import { ComponentType, FC } from 'react';
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

export const withFeatureAnnouncement = <P extends Record<string, unknown>>(
  Component: ComponentType<P>,
): FC<Omit<P, 'hasNews' | 'onBeamerClick'>> => {
  const isBeamerInitialized = () => typeof window.Beamer !== 'undefined';
  let showDot = window.mailpoet_feature_announcement_has_news;
  let beamerCallback;

  function showPluginUpdateNotice() {
    if (
      !window.mailpoet_update_available ||
      document.getElementById('mailpoet_update_notice')
    ) {
      return;
    }
    const updateMailPoetNotice = ReactStringReplace(
      MailPoet.I18n.t('updateMailPoetNotice'),
      /\[link\](.*?)\[\/link\]/,
      (match) => `<a href="update-core.php">${match}</a>`,
    ).join('');
    jQuery('#beamerOverlay').append(
      `<p id="mailpoet_update_notice" class="mailpoet_in_beamer_update_notice">${updateMailPoetNotice}</p>`,
    );
  }

  function updateLastAnnouncementSeenValue() {
    const data = { last_announcement_seen: Math.floor(Date.now() / 1000) };
    void MailPoet.Ajax.post({
      api_version: MailPoet.apiVersion,
      endpoint: 'user_flags',
      action: 'set',
      data,
    });
  }

  function loadBeamer() {
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

  return function withFeatureAnnouncementRenderer({
    ...props
  }: Omit<P, 'hasNews' | 'onBeamerClick'>) {
    return (
      <Component
        {...(props as P)}
        onBeamerClick={(e) => showBeamer(e)}
        hasNews={showDot}
      />
    );
  };
};

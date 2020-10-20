import React from 'react';
import MailPoet from 'mailpoet';
import ReactStringReplace from 'react-string-replace';
import jQuery from 'jquery';

export const withFeatureAnnouncement = <P extends object>(
  Component: React.ComponentType<P>
): React.FC<Omit<P, 'hasNews'|'onBeamerClick'>> => {
  const isBeamerInitialized = () => typeof (window as any).Beamer !== 'undefined';
  let showDot = (window as any).mailpoet_feature_announcement_has_news;

  let beamerCallback = () => {
    if (!isBeamerInitialized()) {
      return;
    }
    showBeamer();
  };

  function updateLastAnnouncementSeenValue() {
    const data = { last_announcement_seen: Math.floor(Date.now() / 1000) };
    MailPoet.Ajax.post({
      api_version: (window as any).mailpoet_api_version,
      endpoint: 'user_flags',
      action: 'set',
      data,
    });
  }

  function showPluginUpdateNotice() {
    if (!(window as any).mailpoet_update_available || document.getElementById('mailpoet_update_notice')) {
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

  function loadBeamer() {
    (window as any).beamer_config = {
      product_id: 'VvHbhYWy7118',
      selector: '#beamer-empty-element',
      language: (window as any).mailpoet_user_locale,
      callback: beamerCallback,
    };
    if ((window as any).mailpoet_woocommerce_active) {
      (window as any).beamer_config.filter = 'woocommerce';
    }
    MailPoet.Modal.loading(true);
    (window as any).mailpoet_feature_announcement_has_news = false;
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
    beamerCallback = () => {}; // We show Beamer panel only on first callback after initialization
    MailPoet.Modal.loading(false);
    (window as any).Beamer.show();
    updateLastAnnouncementSeenValue();
    showPluginUpdateNotice();
  }

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

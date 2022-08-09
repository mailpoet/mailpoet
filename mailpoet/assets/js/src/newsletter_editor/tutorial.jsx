import { MailPoet } from 'mailpoet';
import moment from 'moment';

export const displayTutorial = () => {
  if (window.config.dragDemoUrlSettings) {
    return;
  }
  if (
    moment(window.config.installedAt).isBefore(moment().subtract(7, 'days'))
  ) {
    return;
  }
  MailPoet.Modal.popup({
    title: MailPoet.I18n.t('tutorialVideoTitle'),
    template: `<div class="mailpoet_drag_and_drop_tutorial"><video style="height:640px;" src="${window.config.dragDemoUrl}" controls autoplay></video></div>`,
    onInit: () => {
      MailPoet.Ajax.post({
        api_version: window.mailpoet_api_version,
        endpoint: 'user_flags',
        action: 'set',
        data: { editor_tutorial_seen: 1 },
      });
    },
  });
};

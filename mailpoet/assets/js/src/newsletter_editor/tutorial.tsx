import { MailPoet } from 'mailpoet';
import moment from 'moment';
import { __ } from '@wordpress/i18n';

export const displayTutorial = (onInit?) => {
  MailPoet.Modal.popup({
    title: __(
      'Before you start, this is how you drag and drop in MailPoet',
      'mailpoet',
    ),
    template: `<div class="mailpoet_drag_and_drop_tutorial"><video style="height:640px;" src="${MailPoet.emailEditorTutorialUrl}" controls autoplay></video></div>`,
    onInit,
  });
};

export const initTutorial = () => {
  if (MailPoet.emailEditorTutorialSeen) {
    return;
  }
  if (moment(MailPoet.installedAt).isBefore(moment().subtract(7, 'days'))) {
    return;
  }
  const onInit = () => {
    void MailPoet.Ajax.post({
      api_version: window.mailpoet_api_version,
      endpoint: 'user_flags',
      action: 'set',
      data: { editor_tutorial_seen: 1 },
    });
  };
  displayTutorial(onInit);
};

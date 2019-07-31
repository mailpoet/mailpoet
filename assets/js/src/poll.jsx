import MailPoet from 'mailpoet';

const getSettingsKey = (pollType) => `show_poll_success_delivery_${pollType}`;

const initTypeformScript = () => {
  if (!document.getElementById('typef_orm')) {
    const js = document.createElement('script');
    js.id = 'typef_orm';
    js.src = 'https://embed.typeform.com/embed.js';
    const q = document.getElementsByTagName('script')[0];
    q.parentNode.insertBefore(js, q);
  }
};

const setPollShown = (pollType) => {
  const data = {};
  data[getSettingsKey(pollType)] = '0';
  MailPoet.Ajax.post({
    api_version: window.mailpoet_api_version,
    endpoint: 'settings',
    action: 'set',
    data,
  });
  // since poll trigger can be called multiple times on single page load
  // it must be also locally changed in settings
  window.mailpoet_polls_visibility[getSettingsKey(pollType)] = '0';
};

const Poll = {
  successDelivery: {
    canShow: (pollType, skipMtaMethod) => (
      window.mailpoet_locale === 'en'
      && window.mailpoet_polls_visibility[getSettingsKey(pollType)] === '1'
      && (skipMtaMethod || window.mailpoet_polls_data.mta_method === 'PHPMail')
    ),
    initTypeformScript,
    setPollShown,
    showModal: (pollType, typeformId) => {
      MailPoet.Modal.popup({
        onInit: initTypeformScript,
        template: `
          <div class="typeform-widget"
            data-url="https://mailpoet.typeform.com/to/${typeformId}"
            data-transparency="100"
            data-hide-headers="true"
            data-hide-footer="true"
            style="width: 500px; height: 500px; max-width: 100%; max-height: 100%;"
          ></div>
        `,
      });
    },
  },
};

MailPoet.Poll = Poll;

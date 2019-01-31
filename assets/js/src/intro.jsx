import introJs from 'intro.js';
import MailPoet from 'mailpoet';

const introSteps = [
  {
    element: document.querySelector('#toplevel_page_mailpoet-newsletters > ul > li > a[href*=mailpoet-segments]').parentNode,
    intro: MailPoet.I18n.t('introLists'),
  },
  {
    element: document.querySelector('#toplevel_page_mailpoet-newsletters > ul > li > a[href*=mailpoet-forms]').parentNode,
    intro: MailPoet.I18n.t('introForms'),
  },
  {
    element: '#hs-beacon:not(.hs-beacon-hidden) iframe, .olark-launch-button',
    intro: MailPoet.I18n.t('introChat'),
  },
  {
    element: '#mailpoet-new-email',
    intro: MailPoet.I18n.t('introEmails'),
  },
];

let introActive = false;

function Intro() {
  if (introActive) {
    return;
  }

  // don't show on small screens
  if (window.innerWidth <= 960) {
    return;
  }

  const intro = introJs();
  intro.setOptions({
    steps: introSteps,
    nextLabel: `${MailPoet.I18n.t('introNext')} →`,
    prevLabel: `← ${MailPoet.I18n.t('introBack')}`,
    skipLabel: MailPoet.I18n.t('introSkip'),
    doneLabel: MailPoet.I18n.t('introDone'),
    positionPrecedence: ['right', 'left', 'bottom', 'top'],
    buttonClass: 'button',
    hidePrev: true,
    hideNext: true,
    helperElementPadding: 12,
    scrollToElement: false,
    showStepNumbers: false,
    tooltipPosition: 'auto',
  });

  intro.onbeforechange(() => {
    // evaluate step selector again since DOM might have changed (HelpScout -> Olark)
    const step = intro._currentStep; // eslint-disable-line no-underscore-dangle
    const element = introSteps[step].element;
    intro._introItems[step].element = typeof element === 'string' ? document.querySelector(element) : element; // eslint-disable-line no-underscore-dangle
  });

  intro.onafterchange((targetElement) => {
    // fix for intro.js positioning bug on 'position: fixed' elements
    if (getComputedStyle(targetElement).getPropertyValue('position') === 'fixed') {
      const helperLayer = document.querySelector('.introjs-helperLayer');
      const referenceLayer = document.querySelector('.introjs-tooltipReferenceLayer');
      referenceLayer.style.top = `${parseInt(referenceLayer.style.top, 10) - window.pageYOffset}px`;
      helperLayer.style.top = `${parseInt(helperLayer.style.top, 10) - window.pageYOffset}px`;
    }
  });

  intro.onexit(() => {
    introActive = false;
    document.body.classList.remove('mailpoet-intro-active');
  });

  intro.onskip(() => {
    // this is called also when "Done" button used
    MailPoet.Ajax.post({
      api_version: window.mailpoet_api_version,
      endpoint: 'settings',
      action: 'set',
      data: {
        show_intro: 0,
      },
    });
  });

  intro.start();
  introActive = true;
  document.body.classList.add('mailpoet-intro-active');
}

MailPoet.showIntro = Intro;

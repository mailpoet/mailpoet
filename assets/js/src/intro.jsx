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
    element: '.mailpoet-chat',
    intro: MailPoet.I18n.t('introChat'),
  },
  {
    element: '#mailpoet-new-email',
    intro: MailPoet.I18n.t('introEmails'),
  },
];

function Intro() {
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

  intro.onafterchange((targetElement) => {
    document.body.classList.add('mailpoet-intro-active');

    // fix for intro.js positioning bug on 'position: fixed' elements
    if (getComputedStyle(targetElement).getPropertyValue('position') === 'fixed') {
      const helperLayer = document.querySelector('.introjs-helperLayer');
      const referenceLayer = document.querySelector('.introjs-tooltipReferenceLayer');
      referenceLayer.style.top = `${parseInt(referenceLayer.style.top, 10) - pageYOffset}px`;
      helperLayer.style.top = `${parseInt(helperLayer.style.top, 10) - pageYOffset}px`;
    }
  });

  intro.onexit(() => {
    document.body.classList.remove('mailpoet-intro-active');
  });

  intro.start();
}

MailPoet.showIntro = Intro;

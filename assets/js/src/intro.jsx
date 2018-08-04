import introJs from 'intro.js';
import MailPoet from 'mailpoet';

const introSteps = [
  {
    element: document.querySelector('#toplevel_page_mailpoet-newsletters > ul > li > a[href*=mailpoet-segments]').parentNode,
    intro: 'Create your lists here. Subscribers can be added to one or many of lists.',
  },
  {
    element: document.querySelector('#toplevel_page_mailpoet-newsletters > ul > li > a[href*=mailpoet-forms]').parentNode,
    intro: 'Create a form and add it to your website so your visitors can subscribe to your list.',
  },
  {
    element: '.mailpoet-chat',
    intro: 'You have a question? Start a chat or send a message to get an answer from our support team.',
  },
  {
    element: '#mailpoet-new-email',
    intro: 'We suggest you begin by creating a newsletter, a welcome email or a post notification. Enjoy!',
  },
];

function Intro() {
  const intro = introJs();
  intro.setOptions({
    steps: introSteps,
    nextLabel: 'Next' + ' &rarr;',
    prevLabel: '&larr; ' + 'Back',
    skipLabel: 'Skip',
    doneLabel: 'Done',
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

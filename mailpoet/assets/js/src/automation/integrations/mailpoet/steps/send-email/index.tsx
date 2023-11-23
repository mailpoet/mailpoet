import { __ } from '@wordpress/i18n';
import { Hooks } from 'wp-js-hooks';
import ReactStringReplace from 'react-string-replace';
import { Edit } from './edit';
import { State, StepType } from '../../../../editor/store';
import { Step } from '../../../../editor/components/automation/types';
import { isTransactional } from './helper/is-transactional';
import { SendMailIcon } from './icons/send-mail';
import { TransactionalIcon } from './icons/transactional';
import { MarketingIcon } from './icons/marketing';

const keywords = [
  // translators: noun, used as a search keyword for "Send email" automation action
  __('email', 'mailpoet'),
  // translators: used as a search keyword for "Send email" automation action
  __('send email', 'mailpoet'),
  // translators: verb, used as a search keyword for "Send email" automation action
  __('send', 'mailpoet'),
];
export const step: StepType = {
  key: 'mailpoet:send-email',
  group: 'actions',
  title: (data, context) => {
    if (context !== 'automation') {
      return __('Send email', 'mailpoet');
    }
    const Icon =
      data && isTransactional(data) ? TransactionalIcon : MarketingIcon;
    return (
      <>
        {__('Send email', 'mailpoet')} <Icon />
      </>
    );
  },
  description: (data) => {
    const text = (
      <span className="mailpoet-sendemail-description-main">
        {__('An email will be sent to subscriber.', 'mailpoet')}
      </span>
    );
    if (isTransactional(data)) {
      const transactionalText = ReactStringReplace(
        __(
          "This is a transactional email. This type of email doesn't require marketing consent. Read more about [link]transactional emails[/link].",
          'mailpoet',
        ),
        /\[link\](.*?)\[\/link\]/g,
        (match, i) => (
          <a
            key={i}
            rel="noreferrer"
            href="https://kb.mailpoet.com/article/397-how-to-set-up-an-automation"
            target="_blank"
          >
            {match}
          </a>
        ),
      );
      return (
        <span className="mailpoet-sendmail-description">
          {text}
          <TransactionalIcon />
          <span className="mailpoet-sendmail-description-type">
            {transactionalText}
          </span>
        </span>
      );
    }
    const marketingText = ReactStringReplace(
      __(
        'This is a marketing email. This type of email does require marketing consent. Read more about [link]marketing emails[/link].',
        'mailpoet',
      ),
      /\[link\](.*?)\[\/link\]/g,
      (match, i) => (
        <a
          key={i}
          rel="noreferrer"
          href="https://kb.mailpoet.com/article/397-how-to-set-up-an-automation"
          target="_blank"
        >
          {match}
        </a>
      ),
    );
    return (
      <span className="mailpoet-sendmail-description">
        {text}
        <MarketingIcon />
        <span className="mailpoet-sendmail-description-type">
          {marketingText}
        </span>
      </span>
    );
  },
  keywords,
  subtitle: (data) =>
    (data.args.name as string) ?? __('Send email', 'mailpoet'),
  foreground: '#996800',
  background: '#FCF9E8',
  icon: SendMailIcon,
  edit: Edit,
  createStep: (stepData: Step, state: State) =>
    Hooks.applyFilters(
      'mailpoet.automation.send_email.create_step',
      stepData,
      state.automationData.id,
    ),
} as const;

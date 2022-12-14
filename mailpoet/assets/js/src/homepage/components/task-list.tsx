import { MailPoet } from 'mailpoet';

export function TaskList(): JSX.Element {
  return (
    <>
      <h1>{MailPoet.I18n.t('welcomeToMailPoet')}</h1>
      <h2>{MailPoet.I18n.t('beginByCompletingSetup')}</h2>
    </>
  );
}

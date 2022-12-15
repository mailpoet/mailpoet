import { MailPoet } from 'mailpoet';
import { DropdownMenu } from '@wordpress/components';
import { moreVertical } from '@wordpress/icons';

export function TaskList(): JSX.Element {
  return (
    <>
      <h1>{MailPoet.I18n.t('welcomeToMailPoet')}</h1>
      <h2>{MailPoet.I18n.t('beginByCompletingSetup')}</h2>
      <DropdownMenu
        label={MailPoet.I18n.t('hideList')}
        icon={moreVertical}
        controls={[
          {
            title: MailPoet.I18n.t('hideList'),
            onClick: () => {},
            icon: null,
          },
        ]}
      />
    </>
  );
}

import { Button } from 'common/button/button';
import { MailPoet } from 'mailpoet';

type Props = {
  onProceed?: () => void;
};

function CleanList({ onProceed }: Props): JSX.Element {
  return (
    <div className="mailpoet-clean-list-step-container">
      <p>{MailPoet.I18n.t('cleanListText1')}</p>
      <p>{MailPoet.I18n.t('cleanListText2')}</p>
      <p>
        {onProceed && (
          <Button onClick={onProceed} variant="tertiary">
            {MailPoet.I18n.t('listCleaningGotIt')}
          </Button>
        )}
        <Button
          target="_blank"
          href="https://kb.mailpoet.com/article/287-list-cleaning-services"
        >
          {MailPoet.I18n.t('tryListCleaning')}
        </Button>
      </p>
    </div>
  );
}

CleanList.displayName = 'CleanList';
export { CleanList };

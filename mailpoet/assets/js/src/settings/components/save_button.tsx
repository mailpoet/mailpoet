import { useState, useContext, useEffect } from 'react';
import MailPoet from 'mailpoet';
import Button from 'common/button/button';
import { useAction, useSelector } from 'settings/store/hooks';
import { GlobalContext } from 'context';
import ReactStringReplace from 'react-string-replace';

const showReEngagementNotice = (action, showError, showSuccess) => {
  if (action === 'deactivate') {
    showError(<p>{MailPoet.I18n.t('re-engagementDisabledNotice')}</p>, {
      scroll: true,
    });
    return;
  }
  if (action === 'reactivate') {
    const reEngagementReactivatedNotice = ReactStringReplace(
      MailPoet.I18n.t('re-engagementReactivatedNotice'),
      /\[link\](.*?)\[\/link\]/g,
      (match) => (
        <a
          key="reEngagementEmailsTabLink"
          href="?page=mailpoet-newsletters#/re_engagement"
          rel="noopener noreferrer"
        >
          {match}
        </a>
      ),
    );
    showSuccess(<p>{reEngagementReactivatedNotice}</p>, { scroll: true });
  }
};

export default function SaveButton() {
  const [clicked, setClicked] = useState(false);
  const isSaving = useSelector('isSaving')();
  const hasError = useSelector('hasErrorFlag')();
  const error = useSelector('getSavingError')();
  const hasReEngagementNotice = useSelector('hasReEngagementNotice')();
  const reEngagementAction = useSelector('getReEngagementAction')();
  const save = useAction('saveSettings');
  // eslint-disable-next-line @typescript-eslint/no-explicit-any
  const { notices } = useContext<any>(GlobalContext);
  const showError = notices.error;
  const showSuccess = notices.success;
  useEffect(() => {
    if (clicked && !isSaving) {
      if (error)
        showError(
          error.map((err) => <p>{err}</p>),
          { scroll: true },
        );
      else {
        showSuccess(<p>{MailPoet.I18n.t('settingsSaved')}</p>, {
          scroll: true,
        });
        if (hasReEngagementNotice) {
          showReEngagementNotice(reEngagementAction, showError, showSuccess);
        }
      }
    }
  }, [
    clicked,
    error,
    isSaving,
    showError,
    showSuccess,
    hasReEngagementNotice,
    reEngagementAction,
  ]);
  const onClick = () => {
    setClicked(true);
    save();
  };
  return (
    <div className="mailpoet-settings-save">
      <Button
        type="button"
        automationId="settings-submit-button"
        isDisabled={isSaving || hasError}
        onClick={onClick}
      >
        {MailPoet.I18n.t('saveSettings')}
      </Button>
    </div>
  );
}

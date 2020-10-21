import React from 'react';
import MailPoet from 'mailpoet';
import Button from 'common/button/button';
import { useAction, useSelector } from 'settings/store/hooks';
import { GlobalContext } from 'context';

export default () => {
  const [clicked, setClicked] = React.useState(false);
  const isSaving = useSelector('isSaving')();
  const hasError = useSelector('hasErrorFlag')();
  const error = useSelector('getSavingError')();
  const save = useAction('saveSettings');
  const { notices } = React.useContext<any>(GlobalContext);
  const showError = notices.error;
  const showSuccess = notices.success;
  React.useEffect(() => {
    if (clicked && !isSaving) {
      if (error) showError(error.map((err) => <p>{err}</p>), { scroll: true });
      else showSuccess(<p>{MailPoet.I18n.t('settingsSaved')}</p>, { scroll: true });
    }
  }, [clicked, error, isSaving, showError, showSuccess]);
  const onClick = () => {
    setClicked(true);
    save();
  };
  return (
    <div className="mailpoet-settings-save">
      <Button
        type="button"
        data-automation-id="settings-submit-button"
        isDisabled={isSaving || hasError}
        onClick={onClick}
      >
        {MailPoet.I18n.t('saveSettings')}
      </Button>
    </div>
  );
};

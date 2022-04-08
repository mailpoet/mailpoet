import { t, onChange } from 'common/functions';
import Input from 'common/form/input/input';
import { Label, Inputs } from 'settings/components';
import { useSetting } from 'settings/store/hooks';

export default function EmailSubject() {
  const [enabled] = useSetting('signup_confirmation', 'enabled');
  const [subject, setSubject] = useSetting('signup_confirmation', 'subject');

  if (!enabled) return null;
  return (
    <>
      <Label title={t('emailSubject')} htmlFor="signup_confirmation-subject" />
      <Inputs>
        <Input
          dimension="small"
          type="text"
          size={50}
          id="signup_confirmation-subject"
          data-automation-id="signup_confirmation_email_subject"
          value={subject}
          onChange={onChange(setSubject)}
        />
      </Inputs>
    </>
  );
}

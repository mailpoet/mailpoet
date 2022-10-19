import { t, onChange } from 'common/functions';
import { Textarea } from 'common/form/textarea/textarea';
import { Label, Inputs } from 'settings/components';
import { useSetting } from 'settings/store/hooks';

export function EmailContent() {
  const [enabled] = useSetting('signup_confirmation', 'enabled');
  const [body, setBody] = useSetting('signup_confirmation', 'body');

  if (!enabled) return null;
  const descriptionLines = t('emailContentDescription')
    .replace('[current_site_title]', window.mailpoet_current_site_title || '')
    .split('<br />')
    .filter((x) => x);
  return (
    <>
      <Label
        title={t('emailContent')}
        description={descriptionLines.map((line) => (
          <span key={line}>
            {line}
            <br />
            <br />
          </span>
        ))}
        htmlFor="signup_confirmation-body"
      />
      <Inputs>
        <Textarea
          id="signup_confirmation-body"
          cols={50}
          rows={15}
          data-automation-id="signup_confirmation_email_body"
          value={body}
          onChange={onChange(setBody)}
        />
      </Inputs>
    </>
  );
}

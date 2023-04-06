import { __ } from '@wordpress/i18n';
import { Notice } from './notice';

type Props = {
  newsletters: Array<{
    id: string;
    subject: string;
  }>;
};

function CorruptEmailNotice({ newsletters }: Props) {
  return (
    <Notice type="error" timeout={false} closable={false} renderInPlace>
      <h3>{__('Paused emails', 'mailpoet')}</h3>
      <p>
        {__(
          'There was problem sending the following email(s), please fix the issues described for each email and resume.',
          'mailpoet',
        )}
      </p>
      <ul>
        {newsletters.map(({ id, subject }) => (
          <li key={id}>{subject}</li>
        ))}
      </ul>
    </Notice>
  );
}

CorruptEmailNotice.displayName = 'CorruptEmailNotice';
export { CorruptEmailNotice };

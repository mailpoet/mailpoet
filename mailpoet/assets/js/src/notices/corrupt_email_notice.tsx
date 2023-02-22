import { Notice } from './notice';
import { MailPoet } from '../mailpoet';

type Props = {
  newsletters: Array<{
    id: string;
    subject: string;
  }>;
};

function CorruptEmailNotice({ newsletters }: Props) {
  return (
    <Notice type="error" timeout={false} closable={false} renderInPlace>
      <h3>{MailPoet.I18n.t('pausedEmails')}</h3>
      <p>{MailPoet.I18n.t('problemRenderingNewsletter')}</p>
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

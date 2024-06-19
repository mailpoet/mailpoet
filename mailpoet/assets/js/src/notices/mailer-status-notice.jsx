import PropTypes from 'prop-types';
import { Notice } from 'notices/notice.tsx';

function MailerStatusNotice({ error = null }) {
  if (!error || error.operation !== 'authorization') return null;
  return (
    <Notice type="error" timeout={false} closable={false}>
      <p>{error.error_message}</p>
    </Notice>
  );
}
MailerStatusNotice.propTypes = {
  error: PropTypes.shape({
    operation: PropTypes.string,
    error_message: PropTypes.string,
  }),
};

export { MailerStatusNotice };

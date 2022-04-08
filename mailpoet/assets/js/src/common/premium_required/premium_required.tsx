import { ReactNode } from 'react';
import Badge from 'common/badge/badge';
import Heading from 'common/typography/heading/heading';

type Props = {
  title: string;
  message: ReactNode;
  actionButton: ReactNode;
};

function PremiumRequired({ title, message, actionButton }: Props) {
  return (
    <div className="mailpoet-premium-required">
      <div className="mailpoet-premium-required-message">
        <Heading level={5}>
          <Badge title="Premium" /> {title}
        </Heading>
        {message}
      </div>
      <div className="mailpoet-premium-required-button">{actionButton}</div>
    </div>
  );
}

export default PremiumRequired;

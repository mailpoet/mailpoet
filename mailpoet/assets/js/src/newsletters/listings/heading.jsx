import { Link } from 'react-router-dom';
import MailPoet from 'mailpoet';
import { TopBarWithBeamer } from 'common/top_bar/top_bar';
import plusIcon from 'common/button/icon/plus';

function ListingHeading() {
  return (
    <TopBarWithBeamer>
      <Link
        id="mailpoet-new-email"
        className="mailpoet-button button-secondary"
        to="/new"
        onClick={() => MailPoet.trackEvent('Emails > Add New')}
        data-automation-id="new_email"
      >
        {plusIcon}
        <span>{MailPoet.I18n.t('new')}</span>
      </Link>
    </TopBarWithBeamer>
  );
}

export default ListingHeading;

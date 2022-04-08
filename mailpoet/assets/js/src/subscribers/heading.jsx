import PropTypes from 'prop-types';
import { Link, withRouter } from 'react-router-dom';
import MailPoet from 'mailpoet';
import { TopBarWithBeamer } from 'common/top_bar/top_bar';
import plusIcon from 'common/button/icon/plus';

function SubscribersHeading({ location }) {
  return (
    <TopBarWithBeamer>
      <Link
        className="mailpoet-button button-secondary"
        to={{
          pathname: '/new',
          state: {
            backUrl: location?.pathname,
          },
        }}
      >
        {plusIcon}
        <span data-automation-id="add-new-subscribers-button">
          {MailPoet.I18n.t('new')}
        </span>
      </Link>
      <a
        className="mailpoet-button not-small-screen button-secondary"
        href="?page=mailpoet-import"
        data-automation-id="import-subscribers-button"
      >
        {MailPoet.I18n.t('import')}
      </a>
      <a
        id="mailpoet_export_button"
        className="mailpoet-button not-small-screen button-secondary"
        href="?page=mailpoet-export"
      >
        {MailPoet.I18n.t('export')}
      </a>
    </TopBarWithBeamer>
  );
}

SubscribersHeading.propTypes = {
  location: PropTypes.object.isRequired, // eslint-disable-line react/forbid-prop-types
};

export default withRouter(SubscribersHeading);

import PropTypes from 'prop-types';
import MailPoet from 'mailpoet';
import Loader from 'common/loader/loader';
import Heading from 'common/typography/heading/heading';

function Loading(props) {
  return (
    <div className="mailpoet-congratulate-loading">
      {props.showRichLoadingScreen && (
        <div>
          <Heading level={0}>
            {MailPoet.I18n.t('congratulationsLoadingHeader')}
          </Heading>
          <Heading level={3}>
            {MailPoet.I18n.t('congratulationsLoadingBody')}
          </Heading>
          <div className="mailpoet-gap-large" />
          <div className="mailpoet-gap-large" />
          <Loader size={64} />
        </div>
      )}
    </div>
  );
}

Loading.propTypes = {
  showRichLoadingScreen: PropTypes.bool.isRequired,
};

export default Loading;

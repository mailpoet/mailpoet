import PropTypes from 'prop-types';
import { __ } from '@wordpress/i18n';

import { Loader } from 'common/loader/loader';
import { Heading } from 'common/typography/heading/heading';

function Loading(props) {
  return (
    <div className="mailpoet-congratulate-loading">
      {props.showRichLoadingScreen && (
        <div>
          <Heading level={0}>{__('Verification', 'mailpoet')}</Heading>
          <Heading level={3}>
            {__(
              'Congrats, you’re sending your first newsletter! We’re doing a quick verification to make sure everything works fine.',
              'mailpoet',
            )}
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

export { Loading };

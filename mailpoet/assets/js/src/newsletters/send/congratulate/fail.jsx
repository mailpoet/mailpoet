import { useState } from 'react';
import PropTypes from 'prop-types';
import ReactStringReplace from 'react-string-replace';
import { __ } from '@wordpress/i18n';

import { Button } from 'common';
import { Heading } from 'common/typography/heading/heading';

function Fail(props) {
  const [isClosing, setIsClosing] = useState(false);
  return (
    <div>
      <Heading level={1}>
        {__('Oops! We can’t send your newsletter', 'mailpoet')}
      </Heading>
      <Heading level={3}>
        {ReactStringReplace(
          __(
            'Rest assured, this is fairly common and is usually fixed quickly. [link]See our quick guide[/link] to help you solve this and get your website sending.',
            'mailpoet',
          ),
          /\[link\](.*?)\[\/link\]/g,
          (match, i) => (
            <a
              key={i}
              target="_blank"
              rel="noopener noreferrer"
              href="https://kb.mailpoet.com/article/231-sending-does-not-work"
              data-beacon-article="5a0257ac2c7d3a272c0d7ad6"
            >
              {match}
            </a>
          ),
        )}
      </Heading>
      <div className="mailpoet-gap-large" />
      <div className="mailpoet-gap-large" />
      <img
        src={window.mailpoet_congratulations_error_image}
        alt=""
        width="500"
      />
      <div className="mailpoet-gap-large" />
      <Button
        dimension="small"
        type="button"
        onClick={() => {
          props.failClicked();
          setIsClosing(true);
        }}
        withSpinner={isClosing}
      >
        {__('Close', 'mailpoet')}
      </Button>
    </div>
  );
}

Fail.propTypes = {
  failClicked: PropTypes.func.isRequired,
};

export { Fail };

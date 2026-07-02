import React from 'react';
import { renderToStaticMarkup } from 'react-dom/server';

import { SenderDomainAuthenticatedStatus } from '../../../assets/js/src/settings/pages/basics/sender-domain-authenticated-status';

describe('SenderDomainAuthenticatedStatus', () => {
  it('renders the authenticated sender domain', () => {
    const html = renderToStaticMarkup(
      React.createElement(SenderDomainAuthenticatedStatus, {
        senderDomain: 'example.com',
      }),
    );

    expect(html).to.contain(
      'data-automation-id="sender-domain-authenticated-status"',
    );
    expect(html).to.contain('Sender domain');
    expect(html).to.contain('<strong>example.com</strong>');
    expect(html).to.contain('is authenticated.');
  });
});

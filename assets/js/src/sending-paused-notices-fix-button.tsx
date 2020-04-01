import jQuery from 'jquery';
import React, { useState } from 'react';
import ReactDOM from 'react-dom';
import SetFromAddressModal from 'common/set_from_address_modal';
import { GlobalContext, useGlobalContextValue } from 'context/index.jsx';
import Notices from 'notices/notices.jsx';

const App = () => {
  const [showModal, setShowModal] = useState(false);

  // use jQuery since some of the targeted notices are added to the DOM using the old
  // jQuery-based notice implementation which doesn't trigger pure-JS added listeners
  jQuery(($) => {
    $(document).on('click', '.notice .mailpoet-js-button-fix-this', () => {
      setShowModal(true);
    });
  });

  return (
    <GlobalContext.Provider value={useGlobalContextValue(window)}>
      <Notices />
      { showModal && (
        <SetFromAddressModal
          onRequestClose={() => setShowModal(false)}
        />
      )}
    </GlobalContext.Provider>
  );
};

// nothing is actually rendered to the container because the <Modal> component uses
// ReactDOM.createPortal() but we need an element as a React root on all pages
const container = document.getElementById('mailpoet_set_from_address_modal');
if (container) {
  ReactDOM.render(<App />, container);
}

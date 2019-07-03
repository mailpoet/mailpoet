import React, { useLayoutEffect, useContext } from 'react';
import MailPoet from 'mailpoet';
import PropTypes from 'prop-types';

import ImportContext from '../context.jsx';

import { createSelection } from './generate_segment_selection.jsx';

function SelectSegment({ setSelectedSegments }) {
  const { segments: originalSegments } = useContext(ImportContext);

  useLayoutEffect(() => {
    createSelection(originalSegments, (segments) => {
      setSelectedSegments(segments);
    });
  });

  return (
    <>
      <label htmlFor="mailpoet_segments_select">
        {MailPoet.I18n.t('pickLists')}
        <p className="description">
          {MailPoet.I18n.t('pickListsDescription')}
        </p>
        <select
          id="mailpoet_segments_select"
          data-placeholder={MailPoet.I18n.t('select')}
          multiple="multiple"
        >
          <option />
        </select>
      </label>
      <a className="mailpoet_create_segment">{MailPoet.I18n.t('createANewList')}</a>
    </>
  );
}

SelectSegment.propTypes = {
  setSelectedSegments: PropTypes.func.isRequired,
};

export default SelectSegment;

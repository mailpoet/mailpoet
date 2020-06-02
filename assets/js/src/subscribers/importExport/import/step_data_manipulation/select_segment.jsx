import React, { useLayoutEffect, useContext, useState } from 'react';
import MailPoet from 'mailpoet';
import PropTypes from 'prop-types';

import { GlobalContext } from 'context/index.jsx';

import { createSelection, destroySelection } from './generate_segment_selection.jsx';
import createNewSegment from './create_new_segment.jsx';

function SelectSegment({ setSelectedSegments }) {
  const { segments: segmentsContext } = useContext(GlobalContext);
  const { all: originalSegments, updateAll: updateContextSegments } = segmentsContext;
  const [selectionSegments, setSelectionSegments] = useState(originalSegments);

  useLayoutEffect(() => {
    createSelection(selectionSegments, (segments) => {
      setSelectedSegments(segments);
    });
  }, [selectionSegments, setSelectedSegments]);

  const onCreateNewSegment = (segment) => {
    destroySelection();
    setSelectedSegments([]);
    setSelectionSegments([...selectionSegments, segment]);
    updateContextSegments([...selectionSegments, segment]);
  };

  return (
    <div className="mailpoet_import_select_segment">
      <label htmlFor="mailpoet_segments_select">
        <div className="mailpoet_label_description">
          <b>{MailPoet.I18n.t('pickLists')}</b>
          <p className="description">
            {MailPoet.I18n.t('pickListsDescription')}
          </p>
        </div>
        <select
          id="mailpoet_segments_select"
          data-placeholder={MailPoet.I18n.t('select')}
          multiple="multiple"
        >
          {/* eslint-disable-next-line jsx-a11y/control-has-associated-label */}
          <option />
        </select>
      </label>
      <a
        className="mailpoet_create_segment"
        onClick={() => createNewSegment(onCreateNewSegment)}
        role="button"
        tabIndex={0}
        onKeyDown={(event) => {
          if ((['keydown', 'keypress'].includes(event.type) && ['Enter', ' '].includes(event.key))) {
            event.preventDefault();
            createNewSegment(onCreateNewSegment);
          }
        }}
      >
        {MailPoet.I18n.t('createANewList')}
      </a>
    </div>
  );
}

SelectSegment.propTypes = {
  setSelectedSegments: PropTypes.func.isRequired,
};

export default SelectSegment;

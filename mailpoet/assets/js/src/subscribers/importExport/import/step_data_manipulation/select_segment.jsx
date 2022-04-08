import { useLayoutEffect, useContext, useState } from 'react';
import MailPoet from 'mailpoet';
import PropTypes from 'prop-types';

import { GlobalContext } from 'context/index.jsx';

import Button from 'common/button/button';
import {
  createSelection,
  destroySelection,
} from './generate_segment_selection.jsx';
import createNewSegment from './create_new_segment.jsx';

function SelectSegment({ setSelectedSegments }) {
  const { segments: segmentsContext } = useContext(GlobalContext);
  const { all: originalSegments, updateAll: updateContextSegments } =
    segmentsContext;
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
    <>
      <div className="mailpoet-settings-label">
        <label htmlFor="mailpoet_segments_select">
          {MailPoet.I18n.t('pickLists')}
          <p className="description">
            {MailPoet.I18n.t('pickListsDescription')}
          </p>
        </label>
      </div>
      <div className="mailpoet-settings-inputs">
        <div className="mailpoet-settings-inputs-row mailpoet-settings-inputs-row-centered">
          <div className="mailpoet-form-select mailpoet-form-input">
            <select
              id="mailpoet_segments_select"
              data-placeholder={MailPoet.I18n.t('select')}
              multiple="multiple"
            >
              {/* eslint-disable-next-line jsx-a11y/control-has-associated-label */}
              <option />
            </select>
          </div>
          <Button
            variant="tertiary"
            onClick={() => createNewSegment(onCreateNewSegment)}
          >
            {MailPoet.I18n.t('createANewList')}
          </Button>
        </div>
      </div>
    </>
  );
}

SelectSegment.propTypes = {
  setSelectedSegments: PropTypes.func.isRequired,
};

export default SelectSegment;

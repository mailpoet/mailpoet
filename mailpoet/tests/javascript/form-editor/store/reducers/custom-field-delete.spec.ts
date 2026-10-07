import {
  customFieldDeleteFailed,
  customFieldDeleteStart,
} from '../../../../../assets/js/src/form-editor/store/reducers/custom-field-delete.jsx';
import { createStateMock } from '../mocks/partial-mocks';

describe('Custom Field Delete Reducers', () => {
  it('Should clear the previous custom field notice when deleting starts', () => {
    const initialState = createStateMock({
      notices: [
        {
          id: 'custom-field',
          content: 'Old error',
          isDismissible: true,
          status: 'error',
        },
        {
          id: 'other',
          content: 'Other notice',
          isDismissible: true,
          status: 'info',
        },
      ],
      isCustomFieldDeleting: false,
    });
    const finalState = customFieldDeleteStart(initialState);
    expect(finalState.isCustomFieldDeleting).to.equal(true);
    expect(finalState.notices.map(({ id }) => id)).to.deep.equal(['other']);
  });

  it('Should stop deleting and show the error when deleting fails', () => {
    const initialState = createStateMock({
      notices: [],
      isCustomFieldDeleting: true,
    });
    const finalState = customFieldDeleteFailed(initialState, {
      message: 'Field is used in a segment',
    });
    expect(finalState.isCustomFieldDeleting).to.equal(false);
    expect(finalState.notices.length).to.equal(1);
    expect(finalState.notices[0].content).to.equal(
      'Field is used in a segment',
    );
  });
});

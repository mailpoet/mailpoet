import { expect } from 'chai';
import { identity } from 'lodash';
import reducerFactory from '../../../../../assets/js/src/form_editor/store/reducers/save_form_started.jsx';

const MailPoetStub = {
  I18n: {
    t: identity,
  },
};
const reducer = reducerFactory(MailPoetStub);

describe('Save Form Started Reducer', () => {
  let initialState = null;
  beforeEach(() => {
    initialState = {
      notices: [],
      formErrors: [],
      isFormSaving: false,
      sidebar: {
        activeTab: 'block',
        openedPanels: [],
      },
    };
  });

  it('Should set isFormSaving when there are no errors', () => {
    const action = {
      type: 'SAVE_FORM_STARTED',
    };
    const finalState = reducer(initialState, action);
    expect(finalState.isFormSaving).to.equal(true);
  });

  it('Should clean all form related notices', () => {
    const action = {
      type: 'SAVE_FORM_STARTED',
    };
    const state = {
      ...initialState,
      notices: [
        {
          id: 'missing-lists',
          content: 'message',
          status: 'error',
        },
        {
          id: 'save-form',
          content: 'message',
          status: 'error',
        },
        {
          id: 'missing-block',
          content: 'message',
          status: 'error',
        },
        {
          id: 'some-notice',
          content: 'message',
          status: 'error',
        },
      ],
    };
    const finalState = reducer(state, action);
    expect(finalState.notices.length).to.equal(1);
    expect(finalState.notices[0].id).to.equal('some-notice');
  });

  it('Should set proper state for missing-lists error', () => {
    const action = {
      type: 'SAVE_FORM_STARTED',
    };
    const state = {
      ...initialState,
      formErrors: ['missing-lists'],
    };
    const finalState = reducer(state, action);
    expect(finalState.sidebar.activeTab).to.equal('form');
    expect(finalState.sidebar.openedPanels).to.contain('basic-settings');
    const listsNotice = finalState.notices.find(
      (notice) => notice.id === 'missing-lists',
    );
    expect(listsNotice).to.not.equal(null);
    expect(listsNotice.status).to.equal('error');
    expect(listsNotice.isDismissible).to.equal(true);
  });

  it('Should set proper state for missing email input error', () => {
    const action = {
      type: 'SAVE_FORM_STARTED',
    };
    const state = {
      ...initialState,
      formErrors: ['missing-email-input'],
    };
    const finalState = reducer(state, action);
    const listsNotice = finalState.notices.find(
      (notice) => notice.id === 'missing-block',
    );
    expect(listsNotice).to.not.equal(null);
    expect(listsNotice.status).to.equal('error');
    expect(listsNotice.isDismissible).to.equal(true);
  });
});

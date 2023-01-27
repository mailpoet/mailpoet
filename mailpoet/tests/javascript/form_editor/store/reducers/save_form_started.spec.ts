import { expect } from 'chai';
import { identity } from 'lodash';
import { saveFormStartedFactory } from '../../../../../assets/js/src/form_editor/store/reducers/save_form_started';
import { createStateMock } from '../mocks/partialMocks';

const MailPoetStub = {
  I18n: {
    t: identity,
  },
};
const reducer = saveFormStartedFactory(MailPoetStub);

describe('Save Form Started Reducer', () => {
  let initialState = createStateMock(null);
  beforeEach(() => {
    initialState = createStateMock({
      notices: [],
      formErrors: [],
      isFormSaving: false,
      sidebar: {
        activeSidebar: 'default',
        activeTab: 'block',
        openedPanels: [],
      },
    });
  });

  it('Should set isFormSaving when there are no errors', () => {
    const finalState = reducer(initialState);
    expect(finalState.isFormSaving).to.equal(true);
  });

  it('Should clean all form related notices', () => {
    const state = {
      ...initialState,
      notices: [
        {
          id: 'missing-lists',
          content: 'message',
          isDismissible: true,
          status: 'error',
        },
        {
          id: 'save-form',
          content: 'message',
          isDismissible: true,
          status: 'error',
        },
        {
          id: 'missing-block',
          content: 'message',
          isDismissible: true,
          status: 'error',
        },
        {
          id: 'some-notice',
          content: 'message',
          isDismissible: true,
          status: 'error',
        },
      ],
    };
    const finalState = reducer(state);
    expect(finalState.notices.length).to.equal(1);
    expect(finalState.notices[0].id).to.equal('some-notice');
  });

  it('Should set proper state for missing-lists error', () => {
    const state = {
      ...initialState,
      formErrors: ['missing-lists'],
    };
    const finalState = reducer(state);
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
    const state = {
      ...initialState,
      formErrors: ['missing-email-input'],
    };
    const finalState = reducer(state);
    const listsNotice = finalState.notices.find(
      (notice) => notice.id === 'missing-block',
    );
    expect(listsNotice).to.not.equal(null);
    expect(listsNotice.status).to.equal('error');
    expect(listsNotice.isDismissible).to.equal(true);
  });
});

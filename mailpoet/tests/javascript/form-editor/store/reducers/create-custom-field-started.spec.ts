import { createCustomFieldStartedFactory } from '../../../../../assets/js/src/form-editor/store/reducers/create-custom-field-started';
import { CustomFieldStartedAction } from '../../../../../assets/js/src/form-editor/store/actions-types';
import { createCustomFieldMock, createStateMock } from '../mocks/partial-mocks';

const MailPoetStub = {
  I18n: {
    t: () => 'Error [name]!',
  },
};
const reducer = createCustomFieldStartedFactory(MailPoetStub);

const dummyCustomField = createCustomFieldMock({
  name: 'My custom field',
});

describe('Create Custom Field Started Reducer', () => {
  let initialState = createStateMock(null);
  beforeEach(() => {
    initialState = createStateMock({
      notices: [],
      isCustomFieldCreating: false,
      customFields: [dummyCustomField],
    });
  });

  it('Should set isCustomFieldCreating when there are no errors', () => {
    const customField = { ...dummyCustomField, name: 'Unique custom field' };
    const action: CustomFieldStartedAction = {
      type: 'CREATE_CUSTOM_FIELD_STARTED',
      customField,
    };
    const finalState = reducer(initialState, action);
    expect(finalState.isCustomFieldCreating).to.equal(true);
  });

  it('Should create error notice and stop creation process', () => {
    const customField = { ...dummyCustomField };
    const action: CustomFieldStartedAction = {
      type: 'CREATE_CUSTOM_FIELD_STARTED',
      customField,
    };
    const finalState = reducer(initialState, action);
    expect(finalState.isCustomFieldCreating).to.equal(false);
    expect(finalState.notices.length).to.equal(1);
    expect(finalState.notices[0].content).to.equal('Error My custom field!');
  });
});

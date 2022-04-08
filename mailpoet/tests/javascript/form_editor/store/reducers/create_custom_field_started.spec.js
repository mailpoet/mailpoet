import { expect } from 'chai';
import reducerFactory from '../../../../../assets/js/src/form_editor/store/reducers/create_custom_field_started.jsx';

const MailPoetStub = {
  I18n: {
    t: () => 'Error [name]!',
  },
};
const reducer = reducerFactory(MailPoetStub);

const dummyCustomField = {
  name: 'My custom field',
};

describe('Create Custom Field Started Reducer', () => {
  let initialState = null;
  beforeEach(() => {
    initialState = {
      notices: [],
      isCustomFieldCreating: false,
      customFields: [dummyCustomField],
    };
  });

  it('Should set isCustomFieldCreating when there are no errors', () => {
    const customField = { ...dummyCustomField, name: 'Unique custom field' };
    const action = {
      type: 'CREATE_CUSTOM_FIELD_STARTED',
      customField,
    };
    const finalState = reducer(initialState, action);
    expect(finalState.isCustomFieldCreating).to.equal(true);
  });

  it('Should create error notice and stop creation process', () => {
    const customField = { ...dummyCustomField };
    const action = {
      type: 'CREATE_CUSTOM_FIELD_STARTED',
      customField,
    };
    const finalState = reducer(initialState, action);
    expect(finalState.isCustomFieldCreating).to.equal(false);
    expect(finalState.notices.length).to.equal(1);
    expect(finalState.notices[0].content).to.equal('Error My custom field!');
  });
});

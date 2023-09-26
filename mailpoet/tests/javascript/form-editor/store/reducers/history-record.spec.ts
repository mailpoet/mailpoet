import { expect } from 'chai';
import {
  createHistoryRecord,
  historyRedo,
  historyUndo,
} from '../../../../../assets/js/src/form_editor/store/reducers/history_record';
import {
  createBlocksMock,
  createFormDataMock,
  createFormSettingsMock,
  createStateMock,
} from '../mocks/partialMocks';

describe('History Record Reducer', () => {
  let initialState = createStateMock(null);
  beforeEach(() => {
    initialState = createStateMock({
      editorHistory: [],
      editorHistoryOffset: 0,
      formBlocks: createBlocksMock([{ name: 'Name of block' }]),
      formData: createFormDataMock({
        settings: createFormSettingsMock({ backgroundColor: 'red' }),
      }),
    });
  });

  it('Should add history record into history', () => {
    const finalState = createHistoryRecord(initialState);

    const history = finalState.editorHistory;
    expect(history.length).to.equal(1);
    const historyRecord = history[0];
    expect(historyRecord.blocks[0].name).to.equal('Name of block');
    expect(historyRecord.data.settings.backgroundColor).to.equal('red');
  });

  it('Should restore history corectly after undo', () => {
    const name = 'Name after undo';
    const backgroundColor = 'blue';
    const state = {
      ...initialState,
      editorHistory: [
        {
          time: 0,
          blocks: createBlocksMock([{ name }]),
          data: createFormDataMock({
            settings: createFormSettingsMock({ backgroundColor }),
          }),
        },
      ],
    };

    const finalState = historyUndo(state);
    expect(finalState.editorHistoryOffset).to.equal(1);
    expect(finalState.formBlocks[0].name).to.equal(name);
    expect(finalState.formData.settings.backgroundColor).to.equal('blue');
  });

  it('Should restore history corectly after redo', () => {
    const name = 'Name after redo';
    const backgroundColor = 'blue';
    const state = {
      ...initialState,
      editorHistoryOffset: 1,
      editorHistory: [
        {
          time: 0,
          blocks: createBlocksMock([{ name }]),
          data: createFormDataMock({
            settings: createFormSettingsMock({ backgroundColor }),
          }),
        },
      ],
    };

    const finalState = historyRedo(state);
    expect(finalState.editorHistoryOffset).to.equal(0);
    expect(finalState.formBlocks[0].name).to.equal(name);
    expect(finalState.formData.settings.backgroundColor).to.equal(
      backgroundColor,
    );
  });

  it('Should create record at end of the history when offset is zero', () => {
    const state = {
      ...initialState,
      editorHistoryOffset: 0,
      editorHistory: [
        {
          time: 0,
          blocks: createBlocksMock([{ name: 'name' }]),
          data: createFormDataMock({
            settings: createFormSettingsMock({ backgroundColor: 'green' }),
          }),
        },
      ],
    };

    const finalState = historyUndo(state);
    expect(finalState.editorHistory.length).to.equal(2);
    expect(finalState.editorHistoryOffset).to.equal(1);
  });
});

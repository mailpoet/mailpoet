import { isEqual } from 'lodash';

export const createHistoryRecord = (state) => {
  let editorHistory: any[] = state.editorHistory;
  let editorHistoryOffset = state.editorHistoryOffset;
  const lastHistoryRecord = editorHistory[editorHistory.length - 1];

  let newHistoryRecord;
  const time = Date.now();

  if (
    lastHistoryRecord !== undefined
    && (time - lastHistoryRecord.time) < 1000
    && editorHistory.length > 1
  ) {
    editorHistory.pop();

    newHistoryRecord = {
      blocks: state.formBlocks,
      data: state.formData,
      time: lastHistoryRecord.time,
    };
  } else {
    newHistoryRecord = {
      blocks: state.formBlocks,
      data: state.formData,
      time,
    };

    if (
      lastHistoryRecord !== undefined
      && isEqual(lastHistoryRecord.data, newHistoryRecord.data)
      && isEqual(lastHistoryRecord.blocks, newHistoryRecord.blocks)
    ) {
      return state;
    }
  }

  if (state.editorHistoryOffset !== 0) {
    const offset = state.editorHistory.length - (state.editorHistoryOffset + 1);
    editorHistoryOffset = 0;
    editorHistory = editorHistory.slice(0, offset);
  }

  if (editorHistory.length >= 100) {
    editorHistory.shift();
  }

  editorHistory.push(newHistoryRecord);
  return {
    ...state,
    editorHistory,
    editorHistoryOffset,
  };
};

const historyMove = (state, increment: number) => {
  let offset = state.editorHistoryOffset;

  // When we move undo, then we need save current state as last record in history
  if (offset === 0) {
    createHistoryRecord(state);
  }

  offset += increment;

  const index = state.editorHistory.length - (offset + 1);
  let formBlocksHistory;
  let formDataHistory;
  if (state.editorHistory[index] !== undefined) {
    const historyRecord = state.editorHistory[index];
    formBlocksHistory = historyRecord.blocks;
    formDataHistory = historyRecord.data;
  } else {
    return state;
  }

  return {
    ...state,
    editorHistoryOffset: offset,
    formBlocks: formBlocksHistory,
    formData: formDataHistory,
  };
};


export const historyUndo = (state) => (historyMove(state, 1));

export const historyRedo = (state) => (historyMove(state, -1));

import { isEqual } from 'lodash';

const HISTORY_LENGTH = 100;
const HISTORY_DEBOUNCE = 1000; // 1 second

type HistoryRecord = {
  blocks: unknown[];
  data: unknown[];
  time: number;
};

const createRecord = (editorHistory: HistoryRecord[], state): HistoryRecord => {
  const lastHistoryRecord = editorHistory[editorHistory.length - 1];
  const time = Date.now();

  // When we would create two almost same records in a row in very short time,
  // then we overwrite remove the last record
  if (
    lastHistoryRecord !== undefined &&
    time - lastHistoryRecord.time < HISTORY_DEBOUNCE &&
    editorHistory.length > 1
  ) {
    editorHistory.pop();

    return {
      blocks: state.formBlocks,
      data: state.formData,
      time: lastHistoryRecord.time,
    };
  }

  const newHistoryRecord = {
    blocks: state.formBlocks,
    data: state.formData,
    time,
  };

  // We won't store two same records in a row, then we retur null
  if (
    lastHistoryRecord !== undefined &&
    isEqual(lastHistoryRecord.data, newHistoryRecord.data) &&
    isEqual(lastHistoryRecord.blocks, newHistoryRecord.blocks)
  ) {
    return null;
  }

  return newHistoryRecord;
};

export const createHistoryRecord = (state) => {
  let editorHistory: HistoryRecord[] = state.editorHistory;
  let editorHistoryOffset: number = state.editorHistoryOffset;

  const newHistoryRecord = createRecord(editorHistory, state);
  if (newHistoryRecord === null) {
    return state;
  }

  // When we want to create a history record, and we aren't at the end,
  // then we have to drop the rest of the history stack
  if (state.editorHistoryOffset !== 0) {
    const offset =
      state.editorHistory.length - ((state.editorHistoryOffset as number) + 1);
    editorHistoryOffset = 0;
    editorHistory = editorHistory.slice(0, offset);
  }

  // We can't store history for whole time of editing
  if (editorHistory.length >= HISTORY_LENGTH) {
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
  let offset: number = state.editorHistoryOffset;

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

export const historyUndo = (state) => historyMove(state, 1);

export const historyRedo = (state) => historyMove(state, -1);

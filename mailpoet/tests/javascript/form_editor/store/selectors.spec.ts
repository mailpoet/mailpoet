import { expect } from 'chai';
import { selectors } from '../../../../assets/js/src/form_editor/store/selectors';
import {
  createBlocksMock,
  createFormDataMock,
  createStateMock,
} from './mocks/partialMocks';

describe('Selectors', () => {
  describe('getClosestParentAttribute', () => {
    it('Should return null for empty blocks', () => {
      const state = createStateMock({
        formBlocks: [],
      });

      expect(selectors.getClosestParentAttribute(state, 'id', 'attr')).to.equal(
        null,
      );
    });

    it('Should return null if block is found on top level', () => {
      const formBlocks = createBlocksMock([{ clientId: 'id' }]);
      const state = createStateMock({ formBlocks });
      expect(selectors.getClosestParentAttribute(state, 'id', 'attr')).to.equal(
        null,
      );
    });

    it('Should return parent attribute of the closest parent with the attribute', () => {
      const formBlocks = createBlocksMock([
        {
          clientId: 'parent1',
          attributes: {
            attr: 'Hello',
          },
          innerBlocks: [
            {
              clientId: 'parent2',
              innerBlocks: [{ clientId: 'id' }],
            },
          ],
        },
      ]);
      const state = createStateMock({ formBlocks });
      const result = selectors.getClosestParentAttribute(state, 'id', 'attr');
      expect(result).to.equal('Hello');
    });

    it('Should return closest parent attribute', () => {
      const formBlocks = createBlocksMock([
        {
          clientId: 'parent1',
          attributes: {
            attr: 'Hello',
          },
          innerBlocks: [
            {
              clientId: 'parent2',
              attributes: {
                attr: 'Hello 2',
              },
              innerBlocks: [{ clientId: 'id' }],
            },
          ],
        },
      ]);
      const state = createStateMock({ formBlocks });
      const result = selectors.getClosestParentAttribute(state, 'id', 'attr');
      expect(result).to.equal('Hello 2');
    });
  });
  describe('undo and redo', () => {
    const historyRecord = {
      data: createFormDataMock({ styles: 'some styles 1' }),
      blocks: [],
      time: 0,
    };
    const historyRecord2 = {
      data: createFormDataMock({ styles: 'some styles 2' }),
      blocks: [],
      time: 0,
    };
    const historyRecord3 = {
      data: createFormDataMock({ styles: 'some styles 3' }),
      blocks: [],
      time: 0,
    };

    describe('hasEditorUndo', () => {
      it('Should return false for empty history', () => {
        const state = createStateMock({
          editorHistory: [],
          editorHistoryOffset: 0,
        });
        expect(selectors.hasEditorUndo(state)).to.equal(false);
      });

      it('Should return true for one record in history', () => {
        const state = createStateMock({
          editorHistory: [historyRecord],
          editorHistoryOffset: 0,
        });
        expect(selectors.hasEditorUndo(state)).to.equal(true);
      });

      it('Should return false for two records in history on offset one', () => {
        const state = createStateMock({
          editorHistory: [historyRecord, historyRecord2],
          editorHistoryOffset: 1,
        });
        expect(selectors.hasEditorUndo(state)).to.equal(false);
      });

      it('Should return true for three records in history on offset one', () => {
        const state = createStateMock({
          editorHistory: [historyRecord, historyRecord2, historyRecord3],
          editorHistoryOffset: 1,
        });
        expect(selectors.hasEditorUndo(state)).to.equal(true);
      });
    });

    describe('hasEditorRedo', () => {
      it('Should return false for empty history', () => {
        const state = createStateMock({
          editorHistory: [],
          editorHistoryOffset: 0,
        });
        expect(selectors.hasEditorRedo(state)).to.equal(false);
      });

      it('Should return true for two records in history on offset one', () => {
        const state = createStateMock({
          editorHistory: [historyRecord, historyRecord2],
          editorHistoryOffset: 1,
        });
        expect(selectors.hasEditorRedo(state)).to.equal(true);
      });
    });
  });
});

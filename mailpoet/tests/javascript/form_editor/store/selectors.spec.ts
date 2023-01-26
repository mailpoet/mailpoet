import { expect } from 'chai';
import { selectors } from '../../../../assets/js/src/form_editor/store/selectors';
import { State } from '../../../../assets/js/src/form_editor/store/state_types';

describe('Selectors', () => {
  describe('getClosestParentAttribute', () => {
    it('Should return null for empty blocks', () => {
      const state = {
        formBlocks: [],
      } as State;

      expect(selectors.getClosestParentAttribute(state, 'id', 'attr')).to.equal(
        null,
      );
    });

    it('Should return null if block is found on top level', () => {
      const formBlocks = [{ clientId: 'id' }];
      const state = { formBlocks } as State;
      expect(selectors.getClosestParentAttribute(state, 'id', 'attr')).to.equal(
        null,
      );
    });

    it('Should return parent attribute of the closest parent with the attribute', () => {
      const formBlocks = [
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
      ];
      const state = { formBlocks } as unknown as State;
      const result = selectors.getClosestParentAttribute(state, 'id', 'attr');
      expect(result).to.equal('Hello');
    });

    it('Should return closest parent attribute', () => {
      const formBlocks = [
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
      ];
      const state = { formBlocks } as unknown as State;
      const result = selectors.getClosestParentAttribute(state, 'id', 'attr');
      expect(result).to.equal('Hello 2');
    });
  });

  describe('hasEditorUndo', () => {
    it('Should return false for empty history', () => {
      const state = {
        editorHistory: [],
        editorHistoryOffset: 0,
      } as State;
      expect(selectors.hasEditorUndo(state)).to.equal(false);
    });

    it('Should return true for one record in history', () => {
      const state = {
        editorHistory: [{ data: 'some data' }],
        editorHistoryOffset: 0,
      } as State;
      expect(selectors.hasEditorUndo(state)).to.equal(true);
    });

    it('Should return false for two records in history on offset one', () => {
      const state = {
        editorHistory: [{ data: 'some data 1' }, { data: 'some data 2' }],
        editorHistoryOffset: 1,
      } as State;
      expect(selectors.hasEditorUndo(state)).to.equal(false);
    });

    it('Should return true for three records in history on offset one', () => {
      const state = {
        editorHistory: [
          { data: 'some data 1' },
          { data: 'some data 2' },
          { data: 'some data 3' },
        ],
        editorHistoryOffset: 1,
      } as State;
      expect(selectors.hasEditorUndo(state)).to.equal(true);
    });
  });

  describe('hasEditorRedo', () => {
    it('Should return false for empty history', () => {
      const state = {
        editorHistory: [],
        editorHistoryOffset: 0,
      } as State;
      expect(selectors.hasEditorRedo(state)).to.equal(false);
    });

    it('Should return true for two records in history on offset one', () => {
      const state = {
        editorHistory: [{ data: 'some data 1' }, { data: 'some data 2' }],
        editorHistoryOffset: 1,
      } as State;
      expect(selectors.hasEditorRedo(state)).to.equal(true);
    });
  });
});

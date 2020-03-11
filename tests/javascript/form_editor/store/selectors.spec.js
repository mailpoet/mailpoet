import { expect } from 'chai';
import selectors from '../../../../assets/js/src/form_editor/store/selectors.jsx';

describe('Selectors', () => {
  describe('getClosestParentAttribute', () => {
    it('Should return null for empty blocks', () => {
      const state = {
        formBlocks: [],
      };
      expect(selectors.getClosestParentAttribute(state, 'id', 'attr')).to.equal(null);
    });

    it('Should return null if block is found on top level', () => {
      const formBlocks = [{ clientId: 'id' }];
      const state = { formBlocks };
      expect(selectors.getClosestParentAttribute(state, 'id', 'attr')).to.equal(null);
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
      const state = { formBlocks };
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
      const state = { formBlocks };
      const result = selectors.getClosestParentAttribute(state, 'id', 'attr');
      expect(result).to.equal('Hello 2');
    });
  });
});

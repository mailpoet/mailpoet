import { changeFormBlocks as reducer } from '../../../../../assets/js/src/form-editor/store/reducers/change-form-blocks';
import {
  createBlockMock,
  createFormDataMock,
  createFormSettingsMock,
  createStateMock,
} from '../mocks/partial-mocks';

describe('Change Form Blocks Reducer', () => {
  it('adds px to unitless padding values from the block editor', () => {
    const state = createStateMock({
      formData: createFormDataMock({
        settings: createFormSettingsMock({
          segments: ['1'],
        }),
      }),
    });
    const action = {
      type: 'BLOCKS_CHANGED_IN_BLOCK_EDITOR',
      blocks: [
        createBlockMock({
          name: 'core/paragraph',
          attributes: {
            style: {
              spacing: {
                padding: {
                  top: '153',
                  right: '153',
                  bottom: '153',
                  left: '153',
                },
              },
            },
          },
        }),
      ],
    };

    const finalState = reducer(state, action);

    expect(
      finalState.formBlocks[0].attributes.style.spacing.padding,
    ).to.deep.equal({
      top: '153px',
      right: '153px',
      bottom: '153px',
      left: '153px',
    });
  });

  it('adds px to unitless padding values in nested blocks', () => {
    const state = createStateMock({
      formData: createFormDataMock({
        settings: createFormSettingsMock({
          segments: ['1'],
        }),
      }),
    });
    const action = {
      type: 'BLOCKS_CHANGED_IN_BLOCK_EDITOR',
      blocks: [
        createBlockMock({
          name: 'core/group',
          innerBlocks: [
            createBlockMock({
              name: 'core/heading',
              attributes: {
                style: {
                  spacing: {
                    padding: {
                      top: '24',
                      right: '24',
                      bottom: '24',
                      left: '24',
                    },
                  },
                },
              },
            }),
          ],
        }),
      ],
    };

    const finalState = reducer(state, action);

    expect(
      finalState.formBlocks[0].innerBlocks[0].attributes.style.spacing.padding,
    ).to.deep.equal({
      top: '24px',
      right: '24px',
      bottom: '24px',
      left: '24px',
    });
  });

  // Referential stability keeps the value BlockEditorProvider receives equal to
  // what its onChange emitted, so useBlockSync skips resetBlocks() and the
  // selected block stays selected on every edit.
  it('returns the same block array when nothing needs normalizing', () => {
    const state = createStateMock({
      formData: createFormDataMock({
        settings: createFormSettingsMock({ segments: ['1'] }),
      }),
    });
    const blocks = [
      createBlockMock({ name: 'mailpoet/first-name-input', attributes: {} }),
      createBlockMock({ name: 'mailpoet/email-input', attributes: {} }),
    ];
    const action = { type: 'BLOCKS_CHANGED_IN_BLOCK_EDITOR', blocks };

    const finalState = reducer(state, action);

    expect(finalState.formBlocks).to.equal(blocks);
    expect(finalState.formBlocks[0]).to.equal(blocks[0]);
    expect(finalState.formBlocks[1]).to.equal(blocks[1]);
  });

  it('keeps references for already-normalized padding', () => {
    const state = createStateMock({
      formData: createFormDataMock({
        settings: createFormSettingsMock({ segments: ['1'] }),
      }),
    });
    const blocks = [
      createBlockMock({
        name: 'core/paragraph',
        attributes: {
          style: {
            spacing: {
              padding: {
                top: '10px',
                right: '10px',
                bottom: '10px',
                left: '10px',
              },
            },
          },
        },
      }),
    ];
    const action = { type: 'BLOCKS_CHANGED_IN_BLOCK_EDITOR', blocks };

    const finalState = reducer(state, action);

    expect(finalState.formBlocks).to.equal(blocks);
    expect(finalState.formBlocks[0]).to.equal(blocks[0]);
  });

  it('only clones the block whose padding changed, keeping siblings stable', () => {
    const state = createStateMock({
      formData: createFormDataMock({
        settings: createFormSettingsMock({ segments: ['1'] }),
      }),
    });
    const unchanged = createBlockMock({
      name: 'mailpoet/email-input',
      attributes: {},
    });
    const needsNormalizing = createBlockMock({
      name: 'core/paragraph',
      attributes: {
        style: {
          spacing: {
            padding: { top: '24', right: '24', bottom: '24', left: '24' },
          },
        },
      },
    });
    const blocks = [unchanged, needsNormalizing];
    const action = { type: 'BLOCKS_CHANGED_IN_BLOCK_EDITOR', blocks };

    const finalState = reducer(state, action);

    expect(finalState.formBlocks).to.not.equal(blocks);
    expect(finalState.formBlocks[0]).to.equal(unchanged);
    expect(finalState.formBlocks[1]).to.not.equal(needsNormalizing);
    expect(
      finalState.formBlocks[1].attributes.style.spacing.padding,
    ).to.deep.equal({
      top: '24px',
      right: '24px',
      bottom: '24px',
      left: '24px',
    });
  });
});

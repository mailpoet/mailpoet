import { getEditorExperimentalFeatures } from '../../../../assets/js/src/form-editor/components/editor-settings';

describe('Form editor settings', () => {
  it('enables block text alignment controls', () => {
    const features = getEditorExperimentalFeatures([], [], []);

    expect(features.typography.textAlign).to.equal(true);
  });
});

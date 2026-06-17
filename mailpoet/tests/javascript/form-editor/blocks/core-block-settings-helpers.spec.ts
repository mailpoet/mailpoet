import {
  getCoreBlockFromList,
  isTypographyTextAlignSupported,
} from '../../../../assets/js/src/form-editor/blocks/core-block-settings-helpers';

describe('Core block settings helpers', () => {
  describe('getCoreBlockFromList', () => {
    it('returns a matching core block', () => {
      const block = {
        name: 'core/heading',
        metadata: {},
        settings: {},
      };

      expect(getCoreBlockFromList([block], 'core/heading')).to.equal(block);
    });

    it('throws when the core block is missing', () => {
      expect(() => getCoreBlockFromList([], 'core/heading')).to.throw(
        'Missing WordPress core block: core/heading',
      );
    });
  });

  describe('isTypographyTextAlignSupported', () => {
    it('detects typography text alignment support', () => {
      expect(
        isTypographyTextAlignSupported({
          supports: {
            typography: {
              textAlign: true,
            },
          },
        }),
      ).to.equal(true);
    });

    it('requires explicit typography text alignment support', () => {
      expect(
        isTypographyTextAlignSupported({
          supports: {
            typography: {
              textAlign: false,
            },
          },
        }),
      ).to.equal(false);
      expect(
        isTypographyTextAlignSupported({
          supports: {
            typography: true,
          },
        }),
      ).to.equal(false);
      expect(isTypographyTextAlignSupported({ supports: true })).to.equal(
        false,
      );
      expect(isTypographyTextAlignSupported({})).to.equal(false);
    });
  });
});

import {
  ARCHIVE_HIDDEN_OPTION_VALUE,
  ARCHIVE_VISIBLE_OPTION_VALUE,
  getExcludeFromArchiveOptionValue,
  isNewsletterShownInArchive,
  isNewsletterShownInArchiveFromEditorValue,
} from '../../../../assets/js/src/common/newsletter-archive-visibility';

describe('newsletter archive visibility helpers', () => {
  describe('isNewsletterShownInArchive', () => {
    it('defaults missing and false-like excludeFromArchive values to visible', () => {
      expect(isNewsletterShownInArchive(undefined)).to.equal(true);
      expect(isNewsletterShownInArchive(null)).to.equal(true);
      expect(isNewsletterShownInArchive('')).to.equal(true);
      expect(isNewsletterShownInArchive('0')).to.equal(true);
      expect(isNewsletterShownInArchive(0)).to.equal(true);
      expect(isNewsletterShownInArchive(false)).to.equal(true);
    });

    it('treats canonical hidden values as not visible', () => {
      expect(isNewsletterShownInArchive('1')).to.equal(false);
      expect(isNewsletterShownInArchive(true)).to.equal(false);
    });
  });

  describe('getExcludeFromArchiveOptionValue', () => {
    it('maps visible UI state to the visible option value', () => {
      expect(getExcludeFromArchiveOptionValue(true)).to.equal(
        ARCHIVE_VISIBLE_OPTION_VALUE,
      );
    });

    it('maps hidden UI state to the hidden option value', () => {
      expect(getExcludeFromArchiveOptionValue(false)).to.equal(
        ARCHIVE_HIDDEN_OPTION_VALUE,
      );
    });
  });

  describe('isNewsletterShownInArchiveFromEditorValue', () => {
    it('defaults missing editor values to visible', () => {
      expect(isNewsletterShownInArchiveFromEditorValue(undefined)).to.equal(
        true,
      );
    });

    it('uses the editor positive boolean value directly', () => {
      expect(isNewsletterShownInArchiveFromEditorValue(true)).to.equal(true);
      expect(isNewsletterShownInArchiveFromEditorValue(false)).to.equal(false);
    });
  });
});

import {
  getCreatedAutomationEmail,
  isCreatedEmailPersisted,
  parsePositiveInteger,
} from '../../../../../../../../assets/js/src/automation/integrations/mailpoet/steps/send-email/edit/email-editor-choice';

describe('automation send email editor choice helpers', () => {
  describe('parsePositiveInteger', () => {
    it('accepts positive integer numbers and numeric strings', () => {
      expect(parsePositiveInteger(10)).to.equal(10);
      expect(parsePositiveInteger('20')).to.equal(20);
    });

    it('rejects missing, non-numeric, partial, and non-positive values', () => {
      expect(parsePositiveInteger(undefined)).to.equal(undefined);
      expect(parsePositiveInteger('20abc')).to.equal(undefined);
      expect(parsePositiveInteger('0')).to.equal(undefined);
      expect(parsePositiveInteger(-1)).to.equal(undefined);
    });
  });

  describe('getCreatedAutomationEmail', () => {
    it('requires email and WP post IDs for the new editor path', () => {
      expect(
        getCreatedAutomationEmail(
          { data: { id: '12', wp_post_id: '34' } },
          'new',
        ),
      ).to.deep.equal({
        emailId: 12,
        emailWpPostId: 34,
      });

      expect(getCreatedAutomationEmail({ data: { id: '12' } }, 'new')).to.equal(
        undefined,
      );
    });

    it('requires only an email ID for the classic editor path', () => {
      expect(
        getCreatedAutomationEmail(
          { data: { id: '12', wp_post_id: '34' } },
          'classic',
        ),
      ).to.deep.equal({
        emailId: 12,
      });
    });
  });

  describe('isCreatedEmailPersisted', () => {
    it('confirms new-editor save results with matching email and WP post IDs', () => {
      expect(
        isCreatedEmailPersisted(
          {
            saved: true,
            automation: {
              steps: {
                step1: {
                  args: {
                    email_id: '12',
                    email_wp_post_id: '34',
                  },
                },
              },
            },
          },
          'step1',
          { emailId: 12, emailWpPostId: 34 },
          'new',
        ),
      ).to.equal(true);
    });

    it('confirms classic save results only when email_wp_post_id is absent', () => {
      expect(
        isCreatedEmailPersisted(
          {
            saved: true,
            automation: {
              steps: {
                step1: {
                  args: {
                    email_id: '12',
                  },
                },
              },
            },
          },
          'step1',
          { emailId: 12 },
          'classic',
        ),
      ).to.equal(true);

      expect(
        isCreatedEmailPersisted(
          {
            saved: true,
            automation: {
              steps: {
                step1: {
                  args: {
                    email_id: '12',
                    email_wp_post_id: '34',
                  },
                },
              },
            },
          },
          'step1',
          { emailId: 12 },
          'classic',
        ),
      ).to.equal(false);
    });

    it('rejects unconfirmed save results', () => {
      expect(
        isCreatedEmailPersisted(
          {
            saved: false,
            automation: {
              steps: {
                step1: {
                  args: {
                    email_id: '12',
                    email_wp_post_id: '34',
                  },
                },
              },
            },
          },
          'step1',
          { emailId: 12, emailWpPostId: 34 },
          'new',
        ),
      ).to.equal(false);
    });
  });
});

import {
  automationHasTriggerList,
  disableSendLatestNewsletterWhenMissingTriggerList,
  sendLatestNewsletterStepKey,
  stepProvidesTriggerList,
} from '../../../../../../../assets/js/src/automation/integrations/mailpoet/steps/send-latest-newsletter/helper';
import type { Automation } from '../../../../../../../assets/js/src/automation/editor/components/automation/types';
import type { Item } from '../../../../../../../assets/js/src/automation/editor/components/inserter/item';
import type { Registry } from '../../../../../../../assets/js/src/automation/editor/store/types';

const baseAutomation = {
  id: 1,
  name: 'Automation',
  status: 'draft',
  created_at: '',
  updated_at: '',
  activated_at: '',
  author: {
    id: 1,
    name: 'Admin',
  },
  stats: {
    has_values: false,
    totals: {
      entered: 0,
      in_progress: 0,
      exited: 0,
    },
  },
  meta: {},
};

const registryWithStep = (key: string, subjectKeys: string[]): Registry => ({
  steps: {
    [key]: {
      key,
      name: key,
      subject_keys: subjectKeys,
      args_schema: {
        type: 'object',
        properties: {},
      },
    },
  },
  subjects: {},
  fields: {},
  filters: {},
});

describe('send latest newsletter automation helpers', () => {
  describe('stepProvidesTriggerList', () => {
    it('detects steps that provide subscriber and segment subjects from registry data', () => {
      const registry = registryWithStep('trigger-key', [
        'mailpoet:subscriber',
        'mailpoet:segment',
      ]);

      expect(stepProvidesTriggerList('trigger-key', registry)).to.equal(true);
    });

    it('rejects steps without segment subject context', () => {
      const registry = registryWithStep('trigger-key', ['mailpoet:subscriber']);

      expect(stepProvidesTriggerList('trigger-key', registry)).to.equal(false);
    });

    it('falls back to known MailPoet trigger-list steps when registry data is unavailable', () => {
      expect(stepProvidesTriggerList('mailpoet:someone-subscribes')).to.equal(
        true,
      );
      expect(stepProvidesTriggerList('mailpoet:someone-unsubscribes')).to.equal(
        false,
      );
    });
  });

  describe('automationHasTriggerList', () => {
    it('detects trigger list context in automation steps', () => {
      const automation = {
        ...baseAutomation,
        steps: {
          root: {
            id: 'root',
            type: 'root',
            key: 'core:root',
            args: {},
            next_steps: [{ id: 'trigger' }],
          },
          trigger: {
            id: 'trigger',
            type: 'trigger',
            key: 'mailpoet:someone-subscribes',
            args: {},
            next_steps: [],
          },
        },
      };

      expect(automationHasTriggerList(automation as Automation)).to.equal(true);
    });
  });

  describe('disableSendLatestNewsletterWhenMissingTriggerList', () => {
    it('disables the send latest newsletter inserter item when trigger list context is missing', () => {
      const disabledReason = 'Needs trigger list';
      const items = [
        {
          key: sendLatestNewsletterStepKey,
          title: () => 'Send latest newsletter',
          description: () => '',
          keywords: [],
          icon: () => null,
        },
        {
          key: 'mailpoet:send-email',
          title: () => 'Send email',
          description: () => '',
          keywords: [],
          icon: () => null,
        },
      ];
      const automation = {
        ...baseAutomation,
        steps: {
          root: {
            id: 'root',
            type: 'root',
            key: 'core:root',
            args: {},
            next_steps: [],
          },
        },
      };

      expect(
        disableSendLatestNewsletterWhenMissingTriggerList(
          items as Item[],
          automation as Automation,
          undefined,
          disabledReason,
        ),
      ).to.deep.equal([
        {
          ...items[0],
          isDisabled: true,
          disabledReason,
        },
        items[1],
      ]);
    });

    it('keeps the inserter item enabled when trigger list context exists', () => {
      const items = [
        {
          key: sendLatestNewsletterStepKey,
          title: () => 'Send latest newsletter',
          description: () => '',
          keywords: [],
          icon: () => null,
        },
      ];
      const automation = {
        ...baseAutomation,
        steps: {
          root: {
            id: 'root',
            type: 'root',
            key: 'core:root',
            args: {},
            next_steps: [{ id: 'trigger' }],
          },
          trigger: {
            id: 'trigger',
            type: 'trigger',
            key: 'mailpoet:someone-subscribes',
            args: {},
            next_steps: [],
          },
        },
      };

      expect(
        disableSendLatestNewsletterWhenMissingTriggerList(
          items as Item[],
          automation as Automation,
          undefined,
          'Needs trigger list',
        ),
      ).to.deep.equal(items);
    });
  });
});

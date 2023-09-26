const es5Config = require('@mailpoet/eslint-config/eslint-es5.config');
const es6Config = require('@mailpoet/eslint-config/eslint-es6.config');
const esTsConfig = require('@mailpoet/eslint-config/eslint-ts.config');
const globals = require('@mailpoet/eslint-config/globals');

module.exports = [
  {
    ignores: [
      'assets/js/src/vendor/**',
      'tests/javascript-newsletter-editor/testBundles/**',
    ],
  },
  ...es5Config.map((config) => ({
    ...config,
    files: [
      'assets/js/src/**/*.js',
      'tests/javascript-newsletter-editor/**/*.js',
    ],
  })),
  ...es6Config.map((config) => ({
    ...config,
    files: ['assets/js/src/**/*.jsx', 'tests/javascript/**/*.js'],
  })),
  ...esTsConfig.map((config) => ({
    ...config,
    files: ['assets/js/src/**/*.{ts,tsx}'],
  })),

  // ES5 config overrides
  {
    files: ['tests/javascript-newsletter-editor/**/*.js'],
    languageOptions: {
      globals: {
        ...globals.mocha,
      },
    },
    rules: {
      'func-names': 0,
    },
  },

  // ES6 config overrides
  {
    files: ['assets/js/src/**/*.jsx', 'tests/javascript/**/*.js'],
    rules: {
      'no-script-url': 0,
      'react/destructuring-assignment': 0, // that would be too many changes to fix this one
      'prefer-destructuring': 0, // that would be too many changes to fix this one
      'jsx-a11y/label-has-for': [
        2,
        {
          required: { some: ['nesting', 'id'] }, // some of our labels are hidden and we cannot nest those
        },
      ],
      'jsx-a11y/anchor-is-valid': 0, // cannot fix this one, it would break wordpress themes
      'jsx-a11y/label-has-associated-control': [
        2,
        {
          either: 'either', // control has to be either nested or associated via htmlFor
        },
      ],
    },
  },

  // TS config overrides
  {
    files: ['assets/js/src/**/*.{ts,tsx}'],
    rules: {
      'react/no-unstable-nested-components': ['error', { allowAsProps: true }],
      'react/jsx-no-useless-fragment': ['error', { allowExpressions: true }],
    },
  },

  // File-specific overrides
  // (These are existing violations that we should fix at some point.)
  {
    files: [
      'assets/js/src/common/premium-key/key-activation-button.tsx',
      'assets/js/src/settings/pages/advanced/reinstall.tsx',
      'assets/js/src/settings/pages/advanced/recalculate-subscriber-score.tsx',
      'assets/js/src/settings/pages/send-with/other/activate-or-cancel.tsx',
      'assets/js/src/settings/pages/send-with/send-with-choice.tsx',
    ],
    rules: {
      '@typescript-eslint/await-thenable': 0,
    },
  },
  {
    files: [
      'assets/js/src/common/tabs/routed-tabs.tsx',
      'assets/js/src/common/thumbnail.ts',
      'assets/js/src/form-editor/components/form-settings/form-placement-options/settings-panels/placement-settings.tsx',
      'assets/js/src/form-editor/form-preview.ts',
      'assets/js/src/newsletters/campaign-stats/newsletter-general-stats.tsx',
      'assets/js/src/newsletters/types.tsx',
      'assets/js/src/settings/store/normalize-settings.ts',
      'assets/js/src/subscribers/import-export/export.ts',
    ],
    rules: {
      '@typescript-eslint/restrict-template-expressions': 0,
    },
  },
  {
    files: [
      'assets/js/src/ajax.ts',
      'assets/js/src/automation/editor/components/automation/index.tsx',
      'assets/js/src/automation/editor/components/automation/separator.tsx',
      'assets/js/src/automation/editor/components/header/index.tsx',
      'assets/js/src/automation/editor/components/header/inserter-toggle.tsx',
      'assets/js/src/automation/editor/components/sidebar/header.tsx',
      'assets/js/src/automation/editor/index.tsx',
      'assets/js/src/automation/editor/store/actions.ts',
      'assets/js/src/automation/integrations/core/steps/delay/edit.tsx',
      'assets/js/src/automation/integrations/mailpoet/steps/send-email/edit/edit-newsletter.tsx',
      'assets/js/src/automation/integrations/mailpoet/steps/send-email/edit/email-panel.tsx',
      'assets/js/src/automation/integrations/mailpoet/steps/send-email/edit/reply-to-panel.tsx',
      'assets/js/src/automation/integrations/mailpoet/steps/send-email/index.tsx',
      'assets/js/src/automation/listing/store/reducer.ts',
      'assets/js/src/common/error-boundary/utils.ts',
      'assets/js/src/common/functions/parsley-helper-functions.ts',
      'assets/js/src/common/listings/newsletter-stats/stats.tsx',
      'assets/js/src/common/top-bar/mailpoet-logo-responsive.tsx',
      'assets/js/src/date.ts',
      'assets/js/src/form/fields/token-field.tsx',
      'assets/js/src/form-editor/components/form-settings/form-placement-options/settings-panels/placement-settings.tsx',
      'assets/js/src/form-editor/form-preview.ts',
      'assets/js/src/form-editor/store/blocks-to-form-body.ts',
      'assets/js/src/form-editor/store/controls.tsx',
      'assets/js/src/form-editor/store/reducers/change-active-sidebar.ts',
      'assets/js/src/form-editor/store/reducers/toggle-form.ts',
      'assets/js/src/form-editor/store/reducers/toggle-fullscreen.ts',
      'assets/js/src/form-editor/store/reducers/toggle-sidebar.ts',
      'assets/js/src/form-editor/store/reducers/tutorial-dismiss.ts',
      'assets/js/src/form-editor/store/selectors.ts',
      'assets/js/src/homepage/components/product-discovery.tsx',
      'assets/js/src/homepage/components/task-list.tsx',
      'assets/js/src/marketing-optin-block/frontend.ts',
      'assets/js/src/newsletter-editor/behaviors/text-editor-behavior.ts',
      'assets/js/src/newsletter-editor/blocks/coupon.tsx',
      'assets/js/src/newsletter-editor/blocks/coupon/settings-header.tsx',
      'assets/js/src/newsletters/automatic-emails/events/event-options.tsx',
      'assets/js/src/newsletters/listings/heading-steps.tsx',
      'assets/js/src/newsletters/send.tsx',
      'assets/js/src/newsletters/send/congratulate/success-pitch-mss.tsx',
      'assets/js/src/newsletters/send/ga-tracking.tsx',
      'assets/js/src/newsletters/send/re-engagement.tsx',
      'assets/js/src/newsletters/send/standard.tsx',
      'assets/js/src/newsletters/types.tsx',
      'assets/js/src/notices/email-volume-limit-notice.tsx',
      'assets/js/src/segments/dynamic/dynamic-segments-filters/fields/subscriber/subscriber-tag.tsx',
      'assets/js/src/segments/dynamic/dynamic-segments-filters/woocommerce.tsx',
      'assets/js/src/segments/dynamic/subscribers-counter.tsx',
      'assets/js/src/segments/dynamic/validator.ts',
      'assets/js/src/sending-paused-notices-resume-button.ts',
      'assets/js/src/settings/pages/basics/stats-notifications.tsx',
      'assets/js/src/settings/pages/basics/subscribe-on.tsx',
      'assets/js/src/settings/pages/signup-confirmation/confirmation-email-customizer.tsx',
      'assets/js/src/settings/pages/woo-commerce/checkout-optin.tsx',
      'assets/js/src/settings/pages/woo-commerce/email-customizer.tsx',
      'assets/js/src/settings/pages/woo-commerce/subscribe-old-customers.tsx',
      'assets/js/src/settings/store/actions/settings.ts',
      'assets/js/src/settings/store/hooks/use-selector.ts',
      'assets/js/src/settings/store/hooks/use-setting.ts',
      'assets/js/src/settings/store/normalize-settings.ts',
      'assets/js/src/subscribers/import-export/export.ts',
      'assets/js/src/wizard/welcome-wizard-controller.tsx',
    ],
    rules: {
      '@typescript-eslint/no-unsafe-return': 0,
    },
  },
];

const FlatCompat = require('@eslint/eslintrc').FlatCompat;
const tsPlugin = require('@typescript-eslint/eslint-plugin');
const tsParser = require('@typescript-eslint/parser');
const airbnbConfig = require('eslint-config-airbnb');
const airbnbTsConfig = require('eslint-config-airbnb-typescript');
const prettierConfig = require('eslint-config-prettier');
const webpackResolver = require('eslint-import-resolver-webpack');
const noOnlyTestsPlugin = require('eslint-plugin-no-only-tests');
const reactJsxRuntimeConfig = require('eslint-plugin-react/configs/jsx-runtime');
const reactHooksPlugin = require('eslint-plugin-react-hooks');
const globals = require('globals');

// fix @typescript-eslint/eslint-plugin configs to work with the new ESLint config format
const tsConfigsPath = `${__dirname}/node_modules/@typescript-eslint/eslint-plugin/dist/configs`;
const tsConfigs = Object.fromEntries(
  Object.entries(tsPlugin.configs).map(([name, config]) => [
    name,
    {
      ...config,
      extends: (config.extends ?? []).map((path) =>
        path.replace('./configs', tsConfigsPath),
      ),
    },
  ]),
);

// compat configs
const compat = new FlatCompat({ baseDirectory: __dirname });
const tsRecommendedCompatConfig = compat.config(tsConfigs.recommended);
const tsRequiringTypeCheckingCompatConfig = compat.config(
  tsConfigs['recommended-requiring-type-checking'],
);
const airbnbCompatConfig = compat.config(airbnbConfig);
const airbnbTsCompatConfig = compat.config(airbnbTsConfig);
const prettierCompatConfig = compat.config(prettierConfig);

// React plugin is already defined by airbnb config. This fixes:
//   TypeError: Key "plugins": Cannot redefine plugin "react"
delete reactJsxRuntimeConfig.plugins.react;

module.exports = [
  ...tsRecommendedCompatConfig,
  ...tsRequiringTypeCheckingCompatConfig,
  ...airbnbCompatConfig,
  ...airbnbTsCompatConfig,
  reactJsxRuntimeConfig,
  ...prettierCompatConfig,
  {
    languageOptions: {
      parser: tsParser,
      parserOptions: {
        tsconfigRootDir: '.',
        project: ['./tsconfig.json'],
        ecmaVersion: 6,
        ecmaFeatures: {
          jsx: true,
        },
      },
      globals: {
        ...globals.browser,
      },
    },
    settings: {
      'import/resolver': { webpack: webpackResolver },
      'import/parsers': {
        // prevent "parserPath is required" error with the new ESLint config format
        '@typescript-eslint/parser': ['.ts', '.tsx', '.js', '.jsx'],
      },
    },
    plugins: {
      'react-hooks': reactHooksPlugin,
      'no-only-tests': noOnlyTestsPlugin,
    },
    rules: {
      'react/no-unstable-nested-components': ['error', { allowAsProps: true }],
      // PropTypes
      'react/prop-types': 0,
      'react/jsx-props-no-spreading': 0,
      'react/require-default-props': 0,
      // Hooks
      'react-hooks/rules-of-hooks': 'error',
      'react-hooks/exhaustive-deps': 'warn',
      // Exceptions
      '@typescript-eslint/no-explicit-any': 'error', // make it an error instead of warning - we treat them the same, this is more visible
      'no-void': 0, // can conflict with @typescript-eslint/no-floating-promises
      'react/jsx-no-useless-fragment': [
        'error',
        {
          allowExpressions: true,
        },
      ],
      'react/jsx-filename-extension': 0,
      'class-methods-use-this': 0,
      '@typescript-eslint/no-unsafe-return': 2, // we need to disable it for wordpress select :(
      '@typescript-eslint/no-unsafe-member-access': 0, // this needs to be off until we have typed assignments :(
      '@typescript-eslint/no-unsafe-call': 0, // this needs to be off until we have typed assignments :(
      '@typescript-eslint/no-unsafe-assignment': 0, // this needs to be off until we have typed assignments :(
      'import/extensions': 0, // we wouldn't be able to import jQuery without this line
      'import/no-named-as-default': 0, // we use named default exports at the moment
      'import/prefer-default-export': 0, // we want to stop using default exports and start using named exports
      'react/destructuring-assignment': 0, // that would be too many changes to fix this one
      'prefer-destructuring': 0, // that would be too many changes to fix this one
      'jsx-a11y/label-has-for': [
        2,
        {
          required: { some: ['nesting', 'id'] }, // some of our labels are hidden and we cannot nest those
        },
      ],
      'jsx-a11y/anchor-is-valid': 0, // cannot fix this one, it would break wprdpress themes
      'jsx-a11y/label-has-associated-control': [
        2,
        {
          either: 'either', // control has to be either nested or associated via htmlFor
        },
      ],
      'import/no-default-export': 1, // no default exports
      '@typescript-eslint/no-misused-promises': [
        'error',
        {
          checksVoidReturn: {
            attributes: false, // it is OK to pass an async function to JSX attributes
          },
        },
      ],
    },
  },

  // allow Storybook stories to use dev dependencies and default exports
  {
    files: ['**/_stories/*.tsx'],
    rules: {
      'import/no-extraneous-dependencies': ['error', { devDependencies: true }],
      'import/no-default-export': 0,
    },
  },

  // existing violations that we should fix at some point
  {
    files: [
      'assets/js/src/common/premium_key/key_activation_button.tsx',
      'assets/js/src/settings/pages/advanced/reinstall.tsx',
      'assets/js/src/settings/pages/advanced/recalculate_subscriber_score.tsx',
      'assets/js/src/settings/pages/send_with/other/activate_or_cancel.tsx',
      'assets/js/src/settings/pages/send_with/send_with_choice.tsx',
    ],
    rules: {
      '@typescript-eslint/await-thenable': 0,
    },
  },
  {
    files: [
      'assets/js/src/common/tabs/routed_tabs.tsx',
      'assets/js/src/common/thumbnail.ts',
      'assets/js/src/form_editor/components/form_settings/form_placement_options/settings_panels/placement_settings.tsx',
      'assets/js/src/form_editor/form_preview.ts',
      'assets/js/src/newsletters/campaign_stats/newsletter_general_stats.tsx',
      'assets/js/src/newsletters/types.tsx',
      'assets/js/src/settings/store/normalize_settings.ts',
      'assets/js/src/subscribers/importExport/export.ts',
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
      'assets/js/src/automation/editor/components/header/inserter_toggle.tsx',
      'assets/js/src/automation/editor/components/sidebar/header.tsx',
      'assets/js/src/automation/editor/index.tsx',
      'assets/js/src/automation/editor/store/actions.ts',
      'assets/js/src/automation/integrations/core/steps/delay/edit.tsx',
      'assets/js/src/automation/integrations/mailpoet/steps/send_email/edit/edit_newsletter.tsx',
      'assets/js/src/automation/integrations/mailpoet/steps/send_email/edit/email_panel.tsx',
      'assets/js/src/automation/integrations/mailpoet/steps/send_email/edit/reply_to_panel.tsx',
      'assets/js/src/automation/integrations/mailpoet/steps/send_email/index.tsx',
      'assets/js/src/automation/listing/store/reducer.ts',
      'assets/js/src/common/error_boundary/utils.ts',
      'assets/js/src/common/functions/parsley_helper_functions.ts',
      'assets/js/src/common/listings/newsletter_stats/stats.tsx',
      'assets/js/src/common/top_bar/mailpoet_logo_responsive.tsx',
      'assets/js/src/date.ts',
      'assets/js/src/form/fields/tokenField.tsx',
      'assets/js/src/form_editor/components/form_settings/form_placement_options/settings_panels/placement_settings.tsx',
      'assets/js/src/form_editor/form_preview.ts',
      'assets/js/src/form_editor/store/blocks_to_form_body.ts',
      'assets/js/src/form_editor/store/controls.tsx',
      'assets/js/src/form_editor/store/reducers/change_active_sidebar.ts',
      'assets/js/src/form_editor/store/reducers/toggle_form.ts',
      'assets/js/src/form_editor/store/reducers/toggle_fullscreen.ts',
      'assets/js/src/form_editor/store/reducers/toggle_sidebar.ts',
      'assets/js/src/form_editor/store/reducers/tutorial_dismiss.ts',
      'assets/js/src/form_editor/store/selectors.ts',
      'assets/js/src/homepage/components/product-discovery.tsx',
      'assets/js/src/homepage/components/task-list.tsx',
      'assets/js/src/marketing_optin_block/frontend.ts',
      'assets/js/src/newsletter_editor/behaviors/TextEditorBehavior.ts',
      'assets/js/src/newsletter_editor/blocks/coupon.tsx',
      'assets/js/src/newsletter_editor/blocks/coupon/settings_header.tsx',
      'assets/js/src/newsletters/automatic_emails/events/event_options.tsx',
      'assets/js/src/newsletters/listings/heading_steps.tsx',
      'assets/js/src/newsletters/send.tsx',
      'assets/js/src/newsletters/send/congratulate/success_pitch_mss.tsx',
      'assets/js/src/newsletters/send/ga_tracking.tsx',
      'assets/js/src/newsletters/send/re_engagement.tsx',
      'assets/js/src/newsletters/send/standard.tsx',
      'assets/js/src/newsletters/types.tsx',
      'assets/js/src/notices/email_volume_limit_notice.tsx',
      'assets/js/src/segments/dynamic/dynamic_segments_filters/subscriber_tag.tsx',
      'assets/js/src/segments/dynamic/dynamic_segments_filters/woocommerce.tsx',
      'assets/js/src/segments/dynamic/subscribers_counter.tsx',
      'assets/js/src/segments/dynamic/validator.ts',
      'assets/js/src/sending-paused-notices-resume-button.ts',
      'assets/js/src/settings/pages/basics/stats_notifications.tsx',
      'assets/js/src/settings/pages/basics/subscribe_on.tsx',
      'assets/js/src/settings/pages/signup_confirmation/confirmation_email_customizer.tsx',
      'assets/js/src/settings/pages/woo_commerce/checkout_optin.tsx',
      'assets/js/src/settings/pages/woo_commerce/email_customizer.tsx',
      'assets/js/src/settings/pages/woo_commerce/subscribe_old_customers.tsx',
      'assets/js/src/settings/store/actions/settings.ts',
      'assets/js/src/settings/store/hooks/useSelector.ts',
      'assets/js/src/settings/store/hooks/useSetting.ts',
      'assets/js/src/settings/store/normalize_settings.ts',
      'assets/js/src/subscribers/importExport/export.ts',
      'assets/js/src/wizard/welcome_wizard_controller.tsx',
    ],
    rules: {
      '@typescript-eslint/no-unsafe-return': 0,
    },
  },
];

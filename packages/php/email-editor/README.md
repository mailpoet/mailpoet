# MailPoet Email Editor

This folder contains the code for the MailPoet Email Editor.
We aim to extract the engine as an independent library, so it can be used in other projects.
As we are still in an exploration phase we keep it together with the MailPoet codebase.

## Development

Both **PHP** **and** JS codes are divided into `engine` and `integrations` subdirectories.
Anything **MailPoet** specific is in the `integrations/MailPoet` folder.

For the core stuff that goes to the engine folder, avoid using other MailPoet-specific services and modules. The code in the Engine folder should work only with WP code or other stuff from the engine.

## Workflow Commands
We use `composer` run scripts to run the commands. You can run them using `composer run <command>`.
If you don't have `composer` installed globally, you can use the `composer.phar` file in `../mailpoet/tools/vendor`.

```bash
composer run unit-test                             # runs all the unit tests
composer run unit-test -- [path_to_tests]          # runs a single unit test or a directory of tests
composer run integration-test                      # runs all the integrations tests
composer run integration-test -- [path_to_tests]   # run a single integration test or a directory of tests
composer code-style                                # checks the code style
```
Example:
```bash
# To run test cases defined in tests/integration/Engine/Theme_Controller_Test.php run
composer run integration-test -- tests/integration/Engine/Theme_Controller_Test.php
```

## Known rendering issues

- In some (not all) Outlook versions the width of columns is not respected. The columns will be rendered with the full width.

## Actions and Filters

These actions and filters are currently Work-in-progress.
We may add, update and delete any of them.

Please use with caution.

### Actions

| Name                                   | Argument         | Description                                                                                        |
|----------------------------------------|------------------|----------------------------------------------------------------------------------------------------|
| `mailpoet_email_editor_initialized`    | `null`           | Called when the Email Editor is initialized                                                        |
| `mailpoet_blocks_renderer_initialized` | `BlocksRegistry` | Called when the block content renderer is initialized. You may use this to add a new BlockRenderer |

### Filters

| Name                                       | Argument                                  | Return                                                       | Description                                                                                                                                                         |
|--------------------------------------------|-------------------------------------------|--------------------------------------------------------------|---------------------------------------------------------------------------------------------------------------------------------------------------------------------|
| `mailpoet_email_editor_post_types`         | `Array` $postTypes                        | `Array` EmailPostType                                        | Applied to the list of post types used by the `getPostTypes` method                                                                                                 |
| `mailpoet_email_editor_theme_json`         | `WP_Theme_JSON` $coreThemeData            | `WP_Theme_JSON` $themeJson                                   | Applied to the theme json data. This theme json data is created from the merging of the `WP_Theme_JSON_Resolver::get_core_data` and MailPoet owns `theme.json` file |
| `mailpoet_email_renderer_styles`           | `string` $templateStyles, `WP_Post` $post | `string` $templateStyles                                     | Applied to the email editor template styles.                                                                                                                        |
| `mailpoet_blocks_renderer_parsed_blocks`   | `WP_Block_Parser_Block[]` $output         | `WP_Block_Parser_Block[]` $output                            | Applied to the result of parsed blocks created by the BlocksParser.                                                                                                 |
| `mailpoet_email_content_renderer_styles`   | `string` $contentStyles, `WP_Post` $post  | `string` $contentStyles                                      | Applied to the inline content styles prior to use by the CSS Inliner.                                                                                               |
| `mailpoet_is_email_editor_page`            | `boolean` $isEditorPage                   | `boolean`                                                    | Check current page is the email editor page                                                                                                                         |
| `mailpoet_email_editor_send_preview_email` | `Array` $postData                         | `boolean` Result of processing. Was email sent successfully? | Allows override of the send preview mail function. Folks may choose to use custom implementation                                                                    |

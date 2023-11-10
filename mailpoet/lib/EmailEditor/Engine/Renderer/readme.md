# MailPoet Email Renderer

The renderer is WIP and so is the API for adding support email rendering for new blocks.

## Adding support for a core block

1. Add block into `ALLOWED_BLOCK_TYPES` in `mailpoet/lib/EmailEditor/Engine/Renderer/AllowedBlocks.php`.
2. Make sure the block is registered in the editor. Currently all core blocks are registered in the editor.
3. Add BlockRender class (e.g. Heading) into `mailpoet/lib/EmailEditor/Integration/Core/Renderer/Blocks` folder. <br />

```php
<?php declare(strict_types = 1);

namespace MailPoet\EmailEditor\Integrations\Core\Renderer\Blocks;

use MailPoet\EmailEditor\Engine\Renderer\BlockRenderer;
use MailPoet\EmailEditor\Engine\SettingsController;

class Heading implements BlockRenderer {
  public function render($blockContent, array $parsedBlock, SettingsController $settingsController): string {
    // here comes your rendering logic;
    return 'HEADING_BLOCK';
  }
}
```

4. Register the renderer

```php
<?php declare(strict_types = 1);

namespace MailPoet\EmailEditor\Integrations\Core;

use MailPoet\EmailEditor\Engine\Renderer\BlocksRegistry;

class Initializer {
  public function initialize(): void {
    add_action('mailpoet_blocks_renderer_initialized', [$this, 'registerCoreBlocksRenderers'], 10, 1);
    add_action('mailpoet_blocks_renderer_uninitialized', [$this, 'unregisterCoreBlocksRenderers'], 10, 1);
  }

  /**
   * Register core blocks email renderers when the blocks renderer is initialized.
   */
  public function registerCoreBlocksRenderers(BlocksRegistry $blocksRegistry): void {
    $blocksRegistry->addBlockRenderer('core/heading', new Renderer\Blocks\Heading());
  }

  public function unregisterCoreBlocksRenderers(BlocksRegistry $blocksRegistry): void {
    $blocksRegistry->removeBlockRenderer('core/heading');
  }
}
```

5. Implement the rendering logic in the renderer class.

## Tips for adding support for block

- You can take inspiration on block rendering from MJML in the https://mjml.io/try-it-live
- Test the block in different clients [Litmus](https://litmus.com/)

## TODO

- add universal/fallback renderer for rendering blocks that are not covered by specialized renderers
- add support for all core blocks
- move the renderer to separate package

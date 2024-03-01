# MailPoet Renderer Renderer Library

## Usage

```php
require_once __DIR__ . ' ./mailpoet-renderer/mailpoet_renderer.php';

mp_renderer_init();
$cr = mp_renderer_get_content_renderer();
$post = \WP_Post::get_instance(35);
$output = $cr->render($post);


```

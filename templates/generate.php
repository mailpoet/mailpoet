<?php
require __DIR__.'/vendor/autoload.php';

use Tarsana\Functional as F;

function main(object $config) : void {
  $generate = F\curry('generate');
  F\each(
    $generate($config),
    loadTemplates($config->templatesDir)
  );
}

function loadTemplates(string $templatesDir) : array {
  return F\s(glob("{$templatesDir}/*.json"))
    ->map(F\pipe('file_get_contents', function($content) {
      return json_decode($content, true);
    }))
    ->result();
}

function generate(object $config, array $template) : void {
  $templateDir = makeTemplateDirectory($config->assetsDir, $template);
  $classTemplate = file_get_contents($config->classTemplatePath);
  F\s($template)
    ->saveThumbnail($templateDir)
    ->normalizeBody($templateDir)
    ->renderBody()
    ->renderTemplate($classTemplate)
    ->saveTemplate($config->classesDir)
    ->result();
  echo $template['name'], ' generated!', PHP_EOL;
}

function makeTemplateDirectory(string $assetsDir, array $template) : string {
  $path = $assetsDir. '/' . getDirectoryName($template);
  if (!is_dir($path) && !mkdir($path)) 
    throw new \Exception("Unable to create directory '{$path}'!");
  return $path;
}

function saveThumbnail(string $templateDir, array $template) : array {
  $thumbnail = substr($template['thumbnail'], 23); // 'data:image/jpeg;base64,' is 23 chars
  file_put_contents($templateDir.'/thumbnail.jpg', base64_decode($thumbnail));
  return $template;
}
F\Stream::operation('saveThumbnail', 'String -> Array -> Array');

function normalizeBody(string $templateDir, array $template) : array {
  $template['body'] = F\s($template['body'])
    ->removeContainerDefaults()
    ->downloadAndReplaceImages($templateDir)
    ->replaceSocialIconURLs()
    ->result();
  return $template;
}
F\Stream::operation('normalizeBody', 'String -> Array -> Array');

function renderBody(array $template) : array {
  $template['body'] = F\s($template['body'])
    ->then('export')
    ->render([
      'image_url' => '\' . $this->template_image_url . \'',
      'icon_url' => '\' . $this->social_icon_url . \''
    ])
    ->replace("'' . ", '')
    ->split("\n")
    ->map(F\prepend("    "))
    ->join("\n")
    ->then('trim')
    ->result();

  return $template;
}
F\Stream::operation('renderBody', 'Array -> Array');

function renderTemplate(string $classTemplate, array $template) : array {
  $template['body'] = render([
    'class_name' => getClassName($template),
    'dir_name' => getDirectoryName($template),
    'template_name' => $template['name'],
    'category' => getCategory($template),
    'body' => $template['body']
  ], $classTemplate);

  return $template;
}
F\Stream::operation('renderTemplate', 'String -> Array -> Array');

function saveTemplate(string $classesDir, array $template) : void {
  file_put_contents($classesDir . '/' . getClassName($template) . '.php', $template['body']);
}
F\Stream::operation('saveTemplate', 'String -> Array -> Null');

function removeContainerDefaults(array $body) : array {
  unset($body['blockDefaults']['container']);
  return $body;
}
F\Stream::operation('removeContainerDefaults', 'Array -> Array');

function downloadAndReplaceImages(string $templateDir, array $block) : array {
  if (!empty($block['content'])) {
    $block['content'] = downloadAndReplaceImages($templateDir, $block['content']);
  }

  if ($block['type'] == 'image' && !empty($block['src'])) {
    $block['src'] = downloadImage($templateDir, $block['src']);
  }

  if (!empty($block['image']) && !empty($block['image']['src'])) {
    $block['image']['src'] = downloadImage($templateDir, $block['image']['src']);
  }

  if (!empty($block['blocks'])) {
    foreach ($block['blocks'] as $i => $innerBlock) {
      $block['blocks'][$i] = downloadAndReplaceImages($templateDir, $innerBlock);
    }
  }

  return $block;
}
F\Stream::operation('downloadAndReplaceImages', 'String -> Array -> Array');

function replaceSocialIconURLs(array $block) : array {
  if (!empty($block['content'])) {
    $block['content'] = replaceSocialIconURLs($block['content']);
  }
  
  if (!empty($block['blockDefaults']) && !empty($block['blockDefaults']['social'])) {
    $block['blockDefaults']['social'] = replaceSocialIconURLs($block['blockDefaults']['social']);
  }

  if ($block['type'] == 'socialIcon' && !empty($block['image'])) {
    $block['image'] = F\s($block['image'])
      ->split('?')
      ->head()
      ->split('/')
      ->take(-2)
      ->prepend('{{icon_url}}')
      ->join('/')
      ->result();
  }

  if (!empty($block['icons'])) {
    foreach ($block['icons'] as $i => $icon) {
      $block['icons'][$i] = replaceSocialIconURLs($icon);
    }
  }

  if (!empty($block['blocks'])) {
    foreach ($block['blocks'] as $i => $innerBlock) {
      $block['blocks'][$i] = replaceSocialIconURLs($innerBlock);
    }
  }

  return $block;
}
F\Stream::operation('replaceSocialIconURLs', 'Array -> Array');

function render(array $data, string $text) : string {
  foreach ($data as $name => $value) {
    $text = str_replace('{{'.$name.'}}', $value, $text);
  }
  return $text;
}
F\Stream::operation('render', 'Array -> String -> String');


function getDirectoryName(array $template) : string {
  return F\snakeCase('-', $template['name']);
}

function getClassName(array $template) : string {
  return ucfirst(F\camelCase($template['name']));
}

function getCategory(array $template) : string {
  $categories = json_decode($template['categories']);
  return $categories[0] == 'saved' ? $categories[1] : $categories[0];
}

function downloadImage($templateDir, $src) {
  $name = F\s($src)
    ->split('/')
    ->last()
    ->split('?')
    ->head()
    ->result();
  file_put_contents($templateDir . '/' . $name, file_get_contents($src));
  return '{{image_url}}/' . $name;
}

function export($variable) : string {
  return var_export($variable, true);
}

main(require __DIR__.'/config.php');

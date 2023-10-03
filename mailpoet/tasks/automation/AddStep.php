<?php declare(strict_types = 1);

namespace MailPoetTasks\Automation;

class AddStep {

  private $type;
  private $isPremium;
  private $vendor;
  private $id;
  private $name;
  private $description;
  private $subtitle;
  private $keywords;
  private $premiumNotice;

  public function __construct(
    string $type,
    bool $isPremium,
    string $vendor,
    string $id,
    string $name,
    string $description,
    string $subtitle,
    array $keywords,
    string $premiumNotice
  ) {
    $this->type = strtolower($type);
    $this->isPremium = $isPremium;
    $this->vendor = strtolower($vendor);
    $this->id = $id;
    $this->name = $name;
    $this->description = $description;
    $this->subtitle = $subtitle;
    $this->keywords = $keywords;
    $this->premiumNotice = $premiumNotice;
  }

  public function create() {
    if ($this->doesStepExist()) {
      throw new \RuntimeException(ucfirst("{$this->type} exists already."));
    }

    $this->createFreeJsFile();
    if ($this->isPremium) {
      $this->createPremiumJsFile();
    }
    $this->createPhpFile();
  }

  private function createFreeJsFile() {
    $template = file_get_contents(__DIR__ . '/templates/frontend-free.tsx.tmpl');

    $imports = "import { StepType } from '../../../../editor/store';";
    if ($this->isPremium) {
      $imports .= PHP_EOL . "import { LockedBadge } from '../../../../../common/premium-modal/locked-badge';";
      $imports .= PHP_EOL . "import { PremiumModalForStepEdit } from '../../../../../common/premium-modal';";
    }
    $keywords = "'" . implode("'," . PHP_EOL . "  '", $this->keywords) . "',";
    $template = str_replace('%imports%', $imports, $template);
    $template = str_replace('%keywords%', $keywords, $template);
    $template = str_replace('%key%', $this->key(), $template);
    $template = str_replace('%group%', $this->group(), $template);
    $template = str_replace('%name%', $this->name, $template);
    $template = str_replace('%description%', $this->description, $template);
    $template = str_replace('%subtitle%', $this->isPremium ? "<LockedBadge text={__('Premium', 'mailpoet')} />" : "__('" . $this->subtitle . "', 'mailpoet')", $template);
    $template = str_replace('%foreground%', $this->type === 'trigger' ? '#2271b1' : '#996800', $template);
    $template = str_replace('%background%', $this->type === 'trigger' ? '#f0f6fc' : '#FCF9E8', $template);
    $template = str_replace('%edit%', $this->freeEditMethod(), $template);
    mkdir($this->freeJsPath(), 0777, true);
    file_put_contents($this->freeJsPath() . '/index.tsx', $template);

    $registrationFile = dirname(__DIR__, 2) . '/assets/js/src/automation/integrations/' . $this->vendor . '/index.tsx';
    $register = file_get_contents($registrationFile);
    $importPlaceholder = "// Insert new imports here" . PHP_EOL;
    $name = $this->classname();
    $import = "import { step as " . $name . " } from './steps/" . $this->jsDirectoryName() . "';" . PHP_EOL;
    $register = str_replace($importPlaceholder, $import . $importPlaceholder, $register);
    $codePlaceholder = "// Insert new steps here" . PHP_EOL;
    $code = "  registerStepType($name);" . PHP_EOL;
    $register = str_replace($codePlaceholder, $code . $codePlaceholder, $register);
    file_put_contents($registrationFile, $register);
  }

  private function createPremiumJsFile() {
    $template = file_get_contents(__DIR__ . '/templates/frontend-free.tsx.tmpl');

    $imports = "import { StepType } from '@mailpoet/automation/editor/store';";
    $keywords = "'" . implode("'," . PHP_EOL . "  '", $this->keywords) . "',";
    $template = str_replace('%imports%', $imports, $template);
    $template = str_replace('%keywords%', $keywords, $template);
    $template = str_replace('%key%', $this->key(), $template);
    $template = str_replace('%group%', $this->group(), $template);
    $template = str_replace('%name%', $this->name, $template);
    $template = str_replace('%description%', $this->description, $template);
    $template = str_replace('%subtitle%', ($this->type === 'trigger') ? "__('Trigger', 'mailpoet-premium')" : "__('" . $this->subtitle . "', 'mailpoet')", $template);
    $template = str_replace('%foreground%', $this->type === 'trigger' ? '#2271b1' : '#996800', $template);
    $template = str_replace('%background%', $this->type === 'trigger' ? '#f0f6fc' : '#FCF9E8', $template);
    $template = str_replace('%edit%', '<></>', $template);
    mkdir($this->premiumJsPath(), 0777, true);
    file_put_contents($this->premiumJsPath() . '/index.tsx', $template);

    $vendor = $this->vendor === 'mailpoet' ? 'mailpoet-premium' : $this->vendor;
    $registrationFile = dirname(__DIR__, 3) . '/mailpoet-premium/assets/js/src/automation/integrations/' . $vendor . '/index.tsx';
    $register = file_get_contents($registrationFile);
    $importPlaceholder = "// Insert new imports here" . PHP_EOL;
    $name = $this->classname();
    $import = "import { step as " . $name . " } from './steps/" . $this->jsDirectoryName() . "';" . PHP_EOL;
    $register = str_replace($importPlaceholder, $import . $importPlaceholder, $register);
    $codePlaceholder = "// Insert new steps here" . PHP_EOL;
    $code = "      if (step.key === '" . $this->key() . "') {
        return " . $name . ";
      }" . PHP_EOL;
    $register = str_replace($codePlaceholder, $code . $codePlaceholder, $register);
    file_put_contents($registrationFile, $register);
  }

  private function createPhpFile() {
    $template = $this->type === 'trigger' ? file_get_contents(__DIR__ . '/templates/trigger.php.tmpl') : '';

    $vendorNamespace = $this->vendorNamespace();
    $premiumSpace = $this->isPremium ? "\Premium" : "";
    $namespace = "MailPoet$premiumSpace\Automation\Integrations\\$vendorNamespace\Triggers";
    $template = str_replace('%namespace%', $namespace, $template);
    $template = str_replace('%classname%', $this->classname(), $template);
    $template = str_replace('%key%', $this->key(), $template);
    $template = str_replace('%name%', "__('" . $this->name . "', 'mailpoet')", $template);

    $path = $this->isPremium ? $this->premiumPhpPath() : $this->freePhpPath();
    if (!is_dir($path)) {
      mkdir($path, 0777, true);
    }
    $fileName = $this->classname() . '.php';
    file_put_contents($path . '/' . $fileName, $template);
  }

  private function vendorNamespace(): string {
    switch ($this->vendor) {
      case "woocommerce":
        $vendorNamespace = 'WooCommerce';
        break;
      case "mailpoet":
        $vendorNamespace = $this->isPremium ? 'MailPoetPremium' : 'MailPoet';
        break;
      case "wordpress":
        $vendorNamespace = 'WordPress';
        break;
      default:
        $vendorNamespace = ucfirst($this->vendor);
    }

    return $vendorNamespace;
  }

  private function classname(): string {
    return implode("", array_map(function(string $part): string { return ucfirst($part);
    }, explode('-', $this->id))) .
      ($this->type === 'trigger' ? 'Trigger' : 'Action');
  }

  private function freeEditMethod(): string {
    if (!$this->isPremium) {
      return '(<></>)';
    }

    return "(
    <PremiumModalForStepEdit
      tracking={{
      utm_medium: 'upsell_modal',
        utm_campaign: 'create_automation_editor_" . $this->jsDirectoryName() . "',
      }}
    >
      {__('" . $this->premiumNotice . "', 'mailpoet')}
    </PremiumModalForStepEdit>
  )";
  }

  private function doesStepExist(): bool {
    if ($this->isPremium && is_dir($this->premiumJsPath())) {
      return true;
    }
    if (is_dir($this->freeJsPath())) {
      return true;
    }

    return false;
  }

  private function key(): string {
    return sprintf('%s:%s', $this->vendor, $this->id);
  }

  private function group(): string {
    return $this->type . 's';
  }

  private function jsDirectoryName(): string {
    return str_replace('-', '_', $this->id);
  }

  private function freeJsPath(): string {
    return dirname(__DIR__, 2) . '/assets/js/src/automation/integrations/' . $this->vendor . '/steps/' . $this->jsDirectoryName();
  }

  private function freePhpPath(): string {
    return dirname(__DIR__, 2) . '/lib/Automation/Integrations/' . $this->vendorNamespace() . '/' . ($this->type === 'trigger' ? 'Triggers' : 'Actions');
  }

  private function premiumPhpPath(): string {
    return dirname(__DIR__, 3) . '/mailpoet-premium/lib/Automation/Integrations/' . $this->vendorNamespace() . '/' . ($this->type === 'trigger' ? 'Triggers' : 'Actions');
  }

  private function premiumJsPath(): string {
    $vendorFolder = $this->vendor === 'mailpoet' ? 'mailpoet-premium' : $this->vendor;
    return dirname(__DIR__, 3) . '/mailpoet-premium/assets/js/src/automation/integrations/' . $vendorFolder . '/steps/' . $this->jsDirectoryName();
  }
}

<?php // phpcs:ignore SlevomatCodingStandard.TypeHints.DeclareStrictTypes.DeclareStrictTypesMissing

namespace MailPoet\Tracy\DIPanel;

use MailPoet\DI\ContainerWrapper;
use MailPoetVendor\Symfony\Component\DependencyInjection\Reference;
use MailPoetVendor\Symfony\Component\DependencyInjection\TypedReference;
use Tracy\Debugger;
use Tracy\IBarPanel;

class DIPanel implements IBarPanel {

  /** @var array<int, int|string> */
  private $freeServices = [];

  /** @var array<int, int|string> */
  private $premiumServices = [];

  /** @var \MailPoetVendor\Symfony\Component\DependencyInjection\Definition[] */
  private $definitions = [];

  /** @var string[][] */
  private $argumentsFlattened = [];

  public function getTab() {
    $this->loadServices();
    $img = '<img
      src="data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAAB4AAAAeCAYAAAA7MK6iAAAAAXNSR0IArs4c6QAAAAlwSFlzAAAN1wAADdcBQiibeAAABCRpVFh0WE1MOmNvbS5hZG9iZS54bXAAAAAAADx4OnhtcG1ldGEgeG1sbnM6eD0iYWRvYmU6bnM6bWV0YS8iIHg6eG1wdGs9IlhNUCBDb3JlIDUuNC4wIj4KICAgPHJkZjpSREYgeG1sbnM6cmRmPSJodHRwOi8vd3d3LnczLm9yZy8xOTk5LzAyLzIyLXJkZi1zeW50YXgtbnMjIj4KICAgICAgPHJkZjpEZXNjcmlwdGlvbiByZGY6YWJvdXQ9IiIKICAgICAgICAgICAgeG1sbnM6dGlmZj0iaHR0cDovL25zLmFkb2JlLmNvbS90aWZmLzEuMC8iCiAgICAgICAgICAgIHhtbG5zOmV4aWY9Imh0dHA6Ly9ucy5hZG9iZS5jb20vZXhpZi8xLjAvIgogICAgICAgICAgICB4bWxuczpkYz0iaHR0cDovL3B1cmwub3JnL2RjL2VsZW1lbnRzLzEuMS8iCiAgICAgICAgICAgIHhtbG5zOnhtcD0iaHR0cDovL25zLmFkb2JlLmNvbS94YXAvMS4wLyI+CiAgICAgICAgIDx0aWZmOlJlc29sdXRpb25Vbml0PjI8L3RpZmY6UmVzb2x1dGlvblVuaXQ+CiAgICAgICAgIDx0aWZmOkNvbXByZXNzaW9uPjA8L3RpZmY6Q29tcHJlc3Npb24+CiAgICAgICAgIDx0aWZmOlhSZXNvbHV0aW9uPjkwPC90aWZmOlhSZXNvbHV0aW9uPgogICAgICAgICA8dGlmZjpPcmllbnRhdGlvbj4xPC90aWZmOk9yaWVudGF0aW9uPgogICAgICAgICA8dGlmZjpZUmVzb2x1dGlvbj45MDwvdGlmZjpZUmVzb2x1dGlvbj4KICAgICAgICAgPGV4aWY6UGl4ZWxYRGltZW5zaW9uPjMwPC9leGlmOlBpeGVsWERpbWVuc2lvbj4KICAgICAgICAgPGV4aWY6Q29sb3JTcGFjZT4xPC9leGlmOkNvbG9yU3BhY2U+CiAgICAgICAgIDxleGlmOlBpeGVsWURpbWVuc2lvbj4zMDwvZXhpZjpQaXhlbFlEaW1lbnNpb24+CiAgICAgICAgIDxkYzpzdWJqZWN0PgogICAgICAgICAgICA8cmRmOlNlcS8+CiAgICAgICAgIDwvZGM6c3ViamVjdD4KICAgICAgICAgPHhtcDpNb2RpZnlEYXRlPjIwMTk6MDk6MDMgMTM6MDk6MDU8L3htcDpNb2RpZnlEYXRlPgogICAgICAgICA8eG1wOkNyZWF0b3JUb29sPlBpeGVsbWF0b3IgMy44LjU8L3htcDpDcmVhdG9yVG9vbD4KICAgICAgPC9yZGY6RGVzY3JpcHRpb24+CiAgIDwvcmRmOlJERj4KPC94OnhtcG1ldGE+Ci2FV4MAAAUQSURBVEgNtZZ/TJR1HMc/z/3i7uCOH7ETOI4foY4EEUW0McnYLJKkjOamf5i/UvMHqUmFRlt/VZuZpsgGtKitcIpYqzlDy2y2tdzcTE095afoKQoI7OC8e+55Pr2/Ku5iEwiP9/a65/ne8/1+3s/3x+f7fbQ0ftIg9GLwImgG/WDcJcEh3xoiXQCs0VAZyhHj7gqDUHBsSaaBv1lk5ulxWkb3Pww0147TW8iIa+rxqGnzUw3hBal66dwtJa/DzR78fxZ4n9Q4DEEyQDfwg0Cd6fOS5/hV/5y8FL2pcKKWLtxU8u7e4wGFyfmkxrFwqnnodh5XJcDZjPtsr5+STzT59c/ZJHNmlES/u9Q8r0IdAfXGdCuMT5tMph5cVwLDwyiiQ1vAP2ADWBNjkpoXpWjZYqCLKC8BY1YkWn6WnZ3tLi0tZbvd7kL5DSCGfwEQI/AOCAFCq8HPQKTXmJWAlocXLlzILS0tLNTQ0MA5OTliro+Bk0CYi1wOlFjtY1YcWh7Mz89Xmpqa7puKH1mWubi4mPFM0AD0IGiKQaT9hYWFstPpfGSqKAqXl5fztIwM3rCxmKdnzexEvWVAFwxnYVoLfLXf1T4yVVWVKyoqOMHh4N17y/l6dw/XHKjnmAdzvgL1nyhzxOrdn1kQ7Xt+VRynp6fzqVN/3DevrKzk+Hg7V1ZX8x23j6/c6ubWLjev27yVQx6s9nVoawL/0WjeRiykXZnzo4tmFNr0YgYVcy/VVR0nl8tF1dVVVPLe+5Q5K4da29sp3pFILY1XqXrfHo+rvd2JtlPBGSBW/agVKUlUP/3laP/qqjSOnxLKc5fb+fMrufzCBgdbLKG8bXsZ33b3c0nZRzxlagbvqa7hBa+9rup0uh1wEdOTCESKjVo2jU6zc1bRBHVlRRo70sI4d2kc77iYy/tu5HF521yeszaOUyanct2R43y+1cWLly5jjVbrlySpHi4izx+rxw11rE6v2Z48w7ppxis26URVOyVnWamobCIZLWiCXblT9tGlJDc1trXT6W//pKeibHT+3Fm50Xn5e7htBcNui+LcDJQo2/Umzbakadb189Y66LevrpPtaTMteDeZWGUyW3TUzT46dLKVXH0DFPaMgW7v7iXPUZ84kQ4DsVuNOJ9DexyBvaYsMcOyce4KO7mcA4ReU/6mROrv9lHdtkbysEJ/RXRT06891F/jIU24lvxXFVm+phyCodifb4IRNTTBjVpJirfaDL6BXr+ht8NL895ykNGopagYI4VNMtCBTxvJutxEkQWhpF5UqPPjXnEiiTkVPb01ouPDCkN77GamvztaBjJZpaSCLUkUnWAkZYDptuqltlQP9XTdo/4jXtKESuRt9qv+a8rg8I6qp4MvNtRYlGNNFt0cr9s/+e51L02aHUk9Zpl+PHqNrlzoJct8E8ktCvXV9pPcrPyA+mJ4bwwGHOs1DqnwS1ahjT84mc0zV8ZwyksRnFYfy9G7IzgkXceWV81smKITB8FOMGzKDPcSQ3usYnFFa7VSrn6qXncnQabLX3ZSX6OPrPOMJL6Y3D95yO9S9yLoJ6BruOD/55lIp7eBbEzW+4yzDawxaA5imz9hetaghqTqGfcVeC727qBJHNrrwSWwGZQAsVongVRQB74A8SBoEj1dC1qBOE2MQHys2cCgJuAmfLAQrOubCNQG1oCQYAUdKY5Ih1awCgT1cwXxHiuxc4nU2AW+BoHfxSiOn/4FlYT31T4avjkAAAAASUVORK5CYII="
      style="height: 15px"
    />';
    return $img . '<span class="tracy-label" >' . count($this->freeServices) . ' + ' . count($this->premiumServices) . ' services</span>';
  }

  public function getPanel() {
    ob_start();
    require __DIR__ . '/di-panel.phtml';
    return ob_get_clean();
  }

  public static function init() {
    Debugger::getBar()->addPanel(new DIPanel());
  }

  private function loadServices() {
    list($services, $definitions) = $this->getServicesFromContainer('freeContainer');
    $this->freeServices = array_keys($services);
    sort($this->freeServices);
    $this->definitions = $definitions;
    $this->argumentsFlattened = $this->flattenArguments($this->freeServices);
    if (array_key_exists('premium_container', $services)) {
      list($services, $definitions) = $this->getServicesFromContainer('premiumContainer');
      $this->premiumServices = array_keys($services);
      sort($this->premiumServices);
      $this->definitions = array_merge($this->definitions, $definitions);
      $this->argumentsFlattened = array_merge($this->flattenArguments($this->premiumServices), $this->argumentsFlattened);
    }
  }

  private function getServicesFromContainer($name) {
    $containerWrapper = ContainerWrapper::getInstance();
    $reflection = new \ReflectionProperty(ContainerWrapper::class, $name);
    $reflection->setAccessible(true);
    $container = $reflection->getValue($containerWrapper);
    $reflection = new \ReflectionProperty(get_class($container), 'services');
    $reflection->setAccessible(true);
    return [$reflection->getValue($container), $container->getDefinitions()];
  }

  /**
   * For each service finds all of its arguments recursively and makes them an array
   * @param array<int, int|string> $services
   * @return array
   */
  private function flattenArguments($services) {
    $result = [];
    foreach ($services as $service) {
      $result[$service] = [];
      $this->getAllArguments($service, $result[$service]);
    }
    return $result;
  }

  /**
   * Find all argument of each service and adds them to $results array, repeats recursively
   * @param int|string $service
   * @param string[] $results
   */
  private function getAllArguments($service, &$results) {
    if (array_key_exists($service, $this->definitions)) {
      $arguments = $this->definitions[$service]->getArguments();
      if (!empty($arguments)) {
        foreach ($arguments as $argument) {
          if (is_null($argument)) continue;
          if ($argument instanceof TypedReference) {
            $argumentName = $argument->getType();
          } elseif ($argument instanceof Reference) {
            $argumentName = (string)$argument;
          } else {
            continue;
          }
          $results[$argumentName] = $argumentName;
          if ($argumentName !== 'MailPoet\DI\ContainerWrapper') {
            $this->getAllArguments($argumentName, $results);
          }
        }
      }
    }
  }

  /**
   * @param string $item
   */
  public function printItem($item) {
    echo esc_html($item);
    if (array_key_exists($item, $this->definitions)) {
      $arguments = $this->definitions[$item]->getArguments();
      if (!empty($arguments)) {
        echo '<span class="tracy-toggle tracy-collapsed">...</span>';
        echo '<div class="tracy-collapsed" style="padding-left: 10px">';
        foreach ($arguments as $argument) {
          if (is_null($argument)) {
            echo 'NULL <br>';
          } elseif ($argument instanceof TypedReference) {
            $this->printItem($argument->getType());
            echo '<br>';
          } elseif ($argument instanceof Reference) {
            $this->printItem((string)$argument);
            echo '<br>';
          } elseif (is_string($argument)) {
            echo esc_html($argument);
            echo '<br>';
          }
        }
        echo '</div>';
      }
    }
  }

  /**
   * @param string $item
   */
  public function printUsages($item) {
    $usedIn = [];
    foreach ($this->argumentsFlattened as $service => $arguments) {
      if (array_key_exists($item, $arguments)) {
        $usedIn[] = $service;
      }
    }

    if (count($usedIn)) {
      $label = 'Used in ' . count($usedIn) . ' services';
      echo '<span class="tracy-toggle tracy-collapsed">' . esc_html($label) . '...</span>';
      echo '<div class="tracy-collapsed" style="padding-left: 10px">';
      echo wp_kses_post(join('<br>', $usedIn));
      echo '</div>';
    }
  }
}

<?php declare(strict_types = 1);

namespace MailPoet\Automation\Integrations\WordPress;

use MailPoet\Automation\Engine\WordPress;

class ContextFactory {
  private const ELEVATED_CAPABILITIES = [
    'activate_plugins',
    'create_users',
    'delete_plugins',
    'delete_themes',
    'delete_users',
    'edit_files',
    'edit_plugins',
    'edit_themes',
    'edit_users',
    'install_plugins',
    'install_themes',
    'manage_network',
    'manage_network_options',
    'manage_network_plugins',
    'manage_network_themes',
    'manage_network_users',
    'manage_options',
    'manage_sites',
    'manage_woocommerce',
    'mailpoet_manage_settings',
    'promote_users',
    'remove_users',
    'switch_themes',
    'unfiltered_html',
    'update_core',
    'update_plugins',
    'update_themes',
    'upgrade_network',
  ];

  /** @var WordPress  */
  private $wp;

  public function __construct(
    WordPress $wp
  ) {
    $this->wp = $wp;
  }

  /** @return mixed[] */
  public function getContextData(): array {
    return [
      'comment_statuses' => $this->getCommentStatuses(),
      'editable_roles' => $this->getEditableRoles(),
      'post_types' => $this->getPostTypes(),
      'taxonomies' => $this->getTaxonomies(),
    ];
  }

  /**
   * @return string[][]
   */
  private function getCommentStatuses(): array {
    $statiMap = $this->wp->getCommentStatuses();
    $stati = [];
    foreach ($statiMap as $id => $name) {
      $stati[] = [
        'id' => $id,
        'name' => $name,
      ];
    }
    return $stati;
  }

  /**
   * @return string[][]
   */
  private function getEditableRoles(): array {
    $roles = [];
    foreach ($this->wp->getEditableRoles() as $id => $role) {
      $roleId = (string)$id;
      if ($this->isElevatedRole($roleId, $role)) {
        continue;
      }
      $roles[] = [
        'id' => $roleId,
        'name' => (string)($role['name'] ?? $roleId),
      ];
    }
    return $roles;
  }

  /**
   * @param string $id
   * @param array{name?: string, capabilities?: array<string, bool>} $role
   */
  private function isElevatedRole(string $id, array $role): bool {
    if ($id === 'administrator') {
      return true;
    }
    $capabilities = $role['capabilities'] ?? [];
    foreach (self::ELEVATED_CAPABILITIES as $capability) {
      if (!empty($capabilities[$capability])) {
        return true;
      }
    }
    return false;
  }

  /**
   * @return array<int, array<string, array<string, bool>|bool|string>>
   */
  private function getPostTypes(): array {
    /** @var \WP_Post_Type[] $postTypes */
    $postTypes = $this->wp->getPostTypes([], 'objects');
    return array_values(array_map(function(\WP_Post_Type $type): array {

      $supports = ['comments' => false];
      foreach (array_keys($supports) as $key) {
        $supports[$key] = $this->wp->postTypeSupports($type->name, $key);
      }

      return [
        'name' => $type->name,
        'label' => $type->label,
        'supports' => $supports,
        //phpcs:ignore Squiz.NamingConventions.ValidVariableName.MemberNotCamelCaps
        'show_in_rest' => $type->show_in_rest,
        //phpcs:ignore Squiz.NamingConventions.ValidVariableName.MemberNotCamelCaps
        'rest_base' => $type->rest_base,
        //phpcs:ignore Squiz.NamingConventions.ValidVariableName.MemberNotCamelCaps
        'rest_namespace' => $type->rest_namespace,
        'public' => $type->public,
      ];
    },
    $postTypes));
  }

  /**
   * @return array<int, array<string, string[]|bool|string>>
   */
  private function getTaxonomies(): array {
    /** @var \WP_Taxonomy[] $taxonomies */
    $taxonomies = array_filter(
      $this->wp->getTaxonomies([], 'objects'),
      function($object): bool {
        return $object instanceof \WP_Taxonomy;
      }
    );
    return array_values(array_map(
      function(\WP_Taxonomy $taxonomy): array {
        return [
          'name' => $taxonomy->name,
          'label' => $taxonomy->label,
          //phpcs:ignore Squiz.NamingConventions.ValidVariableName.MemberNotCamelCaps
          'show_in_rest' => $taxonomy->show_in_rest,
          'public' => $taxonomy->public,
          //phpcs:ignore Squiz.NamingConventions.ValidVariableName.MemberNotCamelCaps
          'rest_base' => $taxonomy->rest_base,
          //phpcs:ignore Squiz.NamingConventions.ValidVariableName.MemberNotCamelCaps
          'rest_namespace' => $taxonomy->rest_namespace,
          //phpcs:ignore Squiz.NamingConventions.ValidVariableName.MemberNotCamelCaps
          'object_type' => (array)$taxonomy->object_type,
        ];
      },
      $taxonomies
    ));
  }
}

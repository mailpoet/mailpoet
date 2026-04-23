<?php
/**
 * Plugin Name: MailPoet Dev Companion
 * Description: Dev-only REST endpoints under /wp-json/mailpoet-dev/v1/ for the MailPoet MCP server. Activates only when a shared secret file is present and WP_DEBUG is on.
 *
 * Auth: shared secret file at wp-content/mu-plugins/mailpoet-dev-companion-secret (mapped from host via .wp-env.json). Requests must send X-MailPoet-Dev-Secret matching the file contents.
 *
 * Safety: this file is a wp-env mu-plugin mapping only — it is NOT shipped with the MailPoet plugin. It silently no-ops when the secret file is missing or WP_DEBUG is false.
 */

if (!defined('WP_DEBUG') || !WP_DEBUG) {
    return;
}

$mailpoetDevCompanionSecretPath = WPMU_PLUGIN_DIR . '/mailpoet-dev-companion-secret';
if (!is_readable($mailpoetDevCompanionSecretPath)) {
    return;
}

$mailpoetDevCompanionSecret = trim((string) @file_get_contents($mailpoetDevCompanionSecretPath));
if (strlen($mailpoetDevCompanionSecret) < 32) {
    return;
}

add_action('rest_api_init', function () use ($mailpoetDevCompanionSecret) {
    $namespace = 'mailpoet-dev/v1';

    $permission = function (WP_REST_Request $request) use ($mailpoetDevCompanionSecret) {
        $provided = (string) $request->get_header('x_mailpoet_dev_secret');
        if ($provided === '' || !hash_equals($mailpoetDevCompanionSecret, $provided)) {
            return new WP_Error('mailpoet_dev_forbidden', 'Invalid or missing dev companion secret.', ['status' => 403]);
        }
        return true;
    };

    register_rest_route($namespace, '/ping', [
        'methods' => 'GET',
        'permission_callback' => $permission,
        'callback' => 'mailpoet_dev_companion_ping',
    ]);

    register_rest_route($namespace, '/subscribers', [
        'methods' => 'GET',
        'permission_callback' => $permission,
        'callback' => 'mailpoet_dev_companion_list_subscribers',
        'args' => [
            'email_contains' => ['type' => 'string', 'required' => false],
            'status' => ['type' => 'string', 'required' => false],
            'segment_id' => ['type' => 'string', 'required' => false],
            'limit' => ['type' => 'integer', 'required' => false, 'default' => 50],
            'offset' => ['type' => 'integer', 'required' => false, 'default' => 0],
        ],
    ]);

    register_rest_route($namespace, '/subscribers', [
        'methods' => 'POST',
        'permission_callback' => $permission,
        'callback' => 'mailpoet_dev_companion_create_subscriber',
        'args' => [
            'email' => ['type' => 'string', 'required' => true],
            'first_name' => ['type' => 'string', 'required' => false],
            'last_name' => ['type' => 'string', 'required' => false],
            'status' => ['type' => 'string', 'required' => false],
            'source' => ['type' => 'string', 'required' => false],
            'segment_ids' => ['type' => 'array', 'required' => false, 'items' => ['type' => 'integer']],
            'upsert' => ['type' => 'boolean', 'required' => false, 'default' => false],
        ],
    ]);

    register_rest_route($namespace, '/subscribers/(?P<id>[0-9]+)', [
        'methods' => 'GET',
        'permission_callback' => $permission,
        'callback' => 'mailpoet_dev_companion_get_subscriber',
        'args' => ['id' => ['type' => 'integer', 'required' => true]],
    ]);

    register_rest_route($namespace, '/segments', [
        'methods' => 'GET',
        'permission_callback' => $permission,
        'callback' => 'mailpoet_dev_companion_list_segments',
        'args' => [
            'type' => ['type' => 'string', 'required' => false],
            'include_counts' => ['type' => 'boolean', 'required' => false, 'default' => false],
        ],
    ]);

    register_rest_route($namespace, '/feature-flags', [
        'methods' => 'GET',
        'permission_callback' => $permission,
        'callback' => 'mailpoet_dev_companion_list_feature_flags',
    ]);

    register_rest_route($namespace, '/feature-flags/(?P<name>[A-Za-z0-9_-]+)', [
        'methods' => 'POST',
        'permission_callback' => $permission,
        'callback' => 'mailpoet_dev_companion_set_feature_flag',
        'args' => [
            'name' => ['type' => 'string', 'required' => true],
            'value' => ['type' => 'boolean', 'required' => true],
        ],
    ]);

    register_rest_route($namespace, '/migrations', [
        'methods' => 'GET',
        'permission_callback' => $permission,
        'callback' => 'mailpoet_dev_companion_migrations_status',
    ]);

    register_rest_route($namespace, '/scheduler', [
        'methods' => 'GET',
        'permission_callback' => $permission,
        'callback' => 'mailpoet_dev_companion_scheduler_list',
        'args' => [
            'status' => ['type' => 'string', 'required' => false],
            'hook_contains' => ['type' => 'string', 'required' => false],
            'group' => ['type' => 'string', 'required' => false],
            'limit' => ['type' => 'integer', 'required' => false, 'default' => 50],
        ],
    ]);
});

function mailpoet_dev_companion_container() {
    if (!class_exists(\MailPoet\DI\ContainerWrapper::class)) {
        return new WP_Error('mailpoet_not_loaded', 'MailPoet plugin is not loaded.', ['status' => 503]);
    }
    try {
        return \MailPoet\DI\ContainerWrapper::getInstance();
    } catch (\Throwable $e) {
        return new WP_Error('mailpoet_container_error', $e->getMessage(), ['status' => 500]);
    }
}

function mailpoet_dev_companion_ping() {
    $pluginVersion = defined('MAILPOET_VERSION') ? MAILPOET_VERSION : null;
    $premiumActive = defined('MAILPOET_PREMIUM_VERSION')
        || (function_exists('is_plugin_active') && is_plugin_active('mailpoet-premium/mailpoet-premium.php'));
    return rest_ensure_response([
        'ok' => true,
        'plugin_version' => $pluginVersion,
        'wp_version' => get_bloginfo('version'),
        'php_version' => PHP_VERSION,
        'premium_active' => (bool) $premiumActive,
        'time' => gmdate('c'),
    ]);
}

function mailpoet_dev_companion_serialize_subscriber(\MailPoet\Entities\SubscriberEntity $s): array {
    $segments = [];
    foreach ($s->getSegments() as $seg) {
        $segments[] = ['id' => (string) $seg->getId(), 'name' => $seg->getName()];
    }
    return [
        'id' => (string) $s->getId(),
        'email' => $s->getEmail(),
        'first_name' => $s->getFirstName() ?: null,
        'last_name' => $s->getLastName() ?: null,
        'status' => $s->getStatus(),
        'created_at' => $s->getCreatedAt() ? $s->getCreatedAt()->format('c') : null,
        'updated_at' => $s->getUpdatedAt() ? $s->getUpdatedAt()->format('c') : null,
        'source' => $s->getSource() ?: null,
        'segments' => $segments,
    ];
}

function mailpoet_dev_companion_list_subscribers(WP_REST_Request $request) {
    $container = mailpoet_dev_companion_container();
    if ($container instanceof WP_Error) return $container;

    /** @var \MailPoetVendor\Doctrine\ORM\EntityManager $em */
    $em = $container->get(\MailPoetVendor\Doctrine\ORM\EntityManager::class);

    $qb = $em->createQueryBuilder()
        ->select('s')
        ->from(\MailPoet\Entities\SubscriberEntity::class, 's')
        ->where('s.deletedAt IS NULL')
        ->orderBy('s.id', 'DESC');

    $emailContains = $request->get_param('email_contains');
    if (is_string($emailContains) && $emailContains !== '') {
        $qb->andWhere('LOWER(s.email) LIKE :email')
            ->setParameter('email', '%' . strtolower($emailContains) . '%');
    }

    $status = $request->get_param('status');
    if (is_string($status) && $status !== '') {
        $qb->andWhere('s.status = :status')->setParameter('status', $status);
    }

    $segmentId = $request->get_param('segment_id');
    if (is_string($segmentId) && $segmentId !== '') {
        $qb->innerJoin(\MailPoet\Entities\SubscriberSegmentEntity::class, 'ss', 'WITH', 'ss.subscriber = s AND ss.segment = :segmentId')
            ->setParameter('segmentId', (int) $segmentId);
    }

    $countQb = clone $qb;
    $countQb->select('COUNT(s.id)');
    $total = (int) $countQb->getQuery()->getSingleScalarResult();

    $limit = max(1, min(500, (int) $request->get_param('limit') ?: 50));
    $offset = max(0, (int) $request->get_param('offset') ?: 0);

    $qb->setMaxResults($limit)->setFirstResult($offset);

    /** @var \MailPoet\Entities\SubscriberEntity[] $rows */
    $rows = $qb->getQuery()->getResult();

    $items = array_map('mailpoet_dev_companion_serialize_subscriber', $rows);

    return rest_ensure_response([
        'items' => $items,
        'total' => $total,
        'limit' => $limit,
        'offset' => $offset,
    ]);
}

function mailpoet_dev_companion_get_subscriber(WP_REST_Request $request) {
    $container = mailpoet_dev_companion_container();
    if ($container instanceof WP_Error) return $container;

    /** @var \MailPoet\Subscribers\SubscribersRepository $subscribersRepository */
    $subscribersRepository = $container->get(\MailPoet\Subscribers\SubscribersRepository::class);
    $id = (int) $request->get_param('id');
    $subscriber = $subscribersRepository->findOneById($id);
    if (!$subscriber) {
        return new WP_Error('not_found', "Subscriber $id not found.", ['status' => 404]);
    }
    return rest_ensure_response(mailpoet_dev_companion_serialize_subscriber($subscriber));
}

function mailpoet_dev_companion_create_subscriber(WP_REST_Request $request) {
    $container = mailpoet_dev_companion_container();
    if ($container instanceof WP_Error) return $container;

    $email = strtolower(trim((string) $request->get_param('email')));
    if ($email === '' || !is_email($email)) {
        return new WP_Error('invalid_email', "Invalid email: '$email'.", ['status' => 400]);
    }

    $validStatuses = [
        \MailPoet\Entities\SubscriberEntity::STATUS_SUBSCRIBED,
        \MailPoet\Entities\SubscriberEntity::STATUS_UNSUBSCRIBED,
        \MailPoet\Entities\SubscriberEntity::STATUS_UNCONFIRMED,
        \MailPoet\Entities\SubscriberEntity::STATUS_BOUNCED,
        \MailPoet\Entities\SubscriberEntity::STATUS_INACTIVE,
    ];
    $status = $request->get_param('status');
    if ($status !== null && !in_array($status, $validStatuses, true)) {
        return new WP_Error('invalid_status', "Invalid status '$status'. Allowed: " . implode(', ', $validStatuses), ['status' => 400]);
    }

    try {
        /** @var \MailPoetVendor\Doctrine\ORM\EntityManager $em */
        $em = $container->get(\MailPoetVendor\Doctrine\ORM\EntityManager::class);
        /** @var \MailPoet\Subscribers\SubscribersRepository $subscribersRepository */
        $subscribersRepository = $container->get(\MailPoet\Subscribers\SubscribersRepository::class);
        /** @var \MailPoet\Subscribers\SubscriberSegmentRepository $subscriberSegmentRepository */
        $subscriberSegmentRepository = $container->get(\MailPoet\Subscribers\SubscriberSegmentRepository::class);
        /** @var \MailPoet\Segments\SegmentsRepository $segmentsRepository */
        $segmentsRepository = $container->get(\MailPoet\Segments\SegmentsRepository::class);
    } catch (\Throwable $e) {
        return new WP_Error('mailpoet_container_error', $e->getMessage(), ['status' => 500]);
    }

    $existing = $subscribersRepository->findOneBy(['email' => $email]);
    $upsert = (bool) $request->get_param('upsert');

    if ($existing && !$upsert) {
        return new WP_Error(
            'subscriber_exists',
            "Subscriber '$email' already exists (id {$existing->getId()}). Pass upsert=true to update.",
            ['status' => 409, 'subscriber' => mailpoet_dev_companion_serialize_subscriber($existing)]
        );
    }

    $subscriber = $existing ?: new \MailPoet\Entities\SubscriberEntity();
    $subscriber->setEmail($email);

    $firstName = $request->get_param('first_name');
    if (is_string($firstName)) $subscriber->setFirstName($firstName);

    $lastName = $request->get_param('last_name');
    if (is_string($lastName)) $subscriber->setLastName($lastName);

    if (is_string($status) && $status !== '') {
        $subscriber->setStatus($status);
    } elseif (!$existing) {
        $subscriber->setStatus(\MailPoet\Entities\SubscriberEntity::STATUS_SUBSCRIBED);
    }

    $source = $request->get_param('source');
    $allowedSources = ['api', 'form', 'unknown', 'imported', 'administrator', 'wordpress_user', 'woocommerce_user', 'woocommerce_checkout'];
    if (is_string($source) && $source !== '' && !in_array($source, $allowedSources, true)) {
        return new WP_Error('invalid_source', "Invalid source '$source'. Allowed: " . implode(', ', $allowedSources), ['status' => 400]);
    }
    $subscriber->setSource(is_string($source) && $source !== '' ? $source : 'api');

    if ($subscriber->getStatus() === \MailPoet\Entities\SubscriberEntity::STATUS_SUBSCRIBED && !$subscriber->getLastSubscribedAt()) {
        $subscriber->setLastSubscribedAt(new \DateTimeImmutable());
    }

    if (!$subscriber->getUnsubscribeToken()) {
        do {
            $token = \MailPoet\Util\Security::generateRandomString(\MailPoet\Util\Security::UNSUBSCRIBE_TOKEN_LENGTH);
            $collision = $subscribersRepository->findBy(['unsubscribeToken' => $token]);
        } while (count($collision) > 0);
        $subscriber->setUnsubscribeToken($token);
    }
    if (!$subscriber->getLinkToken()) {
        $subscriber->setLinkToken(\MailPoet\Util\Security::generateHash(\MailPoet\Entities\SubscriberEntity::LINK_TOKEN_LENGTH));
    }

    try {
        $em->persist($subscriber);
        $em->flush();
    } catch (\Throwable $e) {
        return new WP_Error('persist_failed', $e->getMessage(), ['status' => 500]);
    }

    $segmentIds = $request->get_param('segment_ids');
    if (is_array($segmentIds) && $segmentIds !== []) {
        $segmentIdsInt = array_values(array_filter(array_map('intval', $segmentIds), static fn ($id) => $id > 0));
        if ($segmentIdsInt !== []) {
            $segments = $segmentsRepository->findBy(['id' => $segmentIdsInt]);
            $foundIds = array_map(static fn ($s) => $s->getId(), $segments);
            $missing = array_values(array_diff($segmentIdsInt, $foundIds));
            if ($missing !== []) {
                return new WP_Error(
                    'segments_not_found',
                    'Some segment ids do not exist: ' . implode(', ', $missing),
                    ['status' => 400, 'subscriber' => mailpoet_dev_companion_serialize_subscriber($subscriber)]
                );
            }
            $subscriberSegmentRepository->subscribeToSegments($subscriber, $segments, true);
        }
    }

    $em->refresh($subscriber);

    return rest_ensure_response([
        'subscriber' => mailpoet_dev_companion_serialize_subscriber($subscriber),
        'created' => $existing === null,
        'upserted' => $existing !== null,
    ]);
}

function mailpoet_dev_companion_list_segments(WP_REST_Request $request) {
    $container = mailpoet_dev_companion_container();
    if ($container instanceof WP_Error) return $container;

    /** @var \MailPoetVendor\Doctrine\ORM\EntityManager $em */
    $em = $container->get(\MailPoetVendor\Doctrine\ORM\EntityManager::class);

    $qb = $em->createQueryBuilder()
        ->select('seg')
        ->from(\MailPoet\Entities\SegmentEntity::class, 'seg')
        ->where('seg.deletedAt IS NULL')
        ->orderBy('seg.id', 'ASC');

    $type = $request->get_param('type');
    if (is_string($type) && $type !== '') {
        $qb->andWhere('seg.type = :type')->setParameter('type', $type);
    }

    /** @var \MailPoet\Entities\SegmentEntity[] $segments */
    $segments = $qb->getQuery()->getResult();

    $includeCounts = (bool) $request->get_param('include_counts');
    $items = [];
    foreach ($segments as $seg) {
        $row = [
            'id' => (string) $seg->getId(),
            'name' => $seg->getName(),
            'type' => $seg->getType(),
            'description' => $seg->getDescription() ?: null,
            'created_at' => $seg->getCreatedAt() ? $seg->getCreatedAt()->format('c') : null,
            'updated_at' => $seg->getUpdatedAt() ? $seg->getUpdatedAt()->format('c') : null,
        ];
        if ($includeCounts) {
            $count = (int) $em->createQueryBuilder()
                ->select('COUNT(ss.id)')
                ->from(\MailPoet\Entities\SubscriberSegmentEntity::class, 'ss')
                ->where('ss.segment = :seg AND ss.status = :status')
                ->setParameter('seg', $seg)
                ->setParameter('status', \MailPoet\Entities\SubscriberEntity::STATUS_SUBSCRIBED)
                ->getQuery()
                ->getSingleScalarResult();
            $row['subscribed_count'] = $count;
        }
        $items[] = $row;
    }

    return rest_ensure_response(['items' => $items, 'total' => count($items)]);
}

function mailpoet_dev_companion_list_feature_flags() {
    $container = mailpoet_dev_companion_container();
    if ($container instanceof WP_Error) return $container;

    try {
        /** @var \MailPoet\Features\FeaturesController $controller */
        $controller = $container->get(\MailPoet\Features\FeaturesController::class);
    } catch (\Throwable $e) {
        return new WP_Error('mailpoet_container_error', $e->getMessage(), ['status' => 500]);
    }

    $defaults = $controller->getDefaults();
    $current = $controller->getAllFlags();

    $items = [];
    foreach ($defaults as $name => $default) {
        $items[] = [
            'name' => $name,
            'enabled' => (bool) ($current[$name] ?? $default),
            'default' => (bool) $default,
        ];
    }

    return rest_ensure_response(['items' => $items, 'total' => count($items)]);
}

function mailpoet_dev_companion_set_feature_flag(WP_REST_Request $request) {
    $container = mailpoet_dev_companion_container();
    if ($container instanceof WP_Error) return $container;

    $name = (string) $request->get_param('name');
    $value = $request->get_param('value');

    try {
        /** @var \MailPoet\Features\FeaturesController $controller */
        $controller = $container->get(\MailPoet\Features\FeaturesController::class);
        /** @var \MailPoet\Features\FeatureFlagsRepository $repo */
        $repo = $container->get(\MailPoet\Features\FeatureFlagsRepository::class);
    } catch (\Throwable $e) {
        return new WP_Error('mailpoet_container_error', $e->getMessage(), ['status' => 500]);
    }

    if (!$controller->exists($name)) {
        return new WP_Error('unknown_flag', "Unknown feature flag '$name'. Known flags: " . implode(', ', array_keys($controller->getDefaults())), ['status' => 404]);
    }

    try {
        $repo->createOrUpdate(['name' => $name, 'value' => (bool) $value]);
        $controller->resetCache();
    } catch (\Throwable $e) {
        return new WP_Error('save_failed', $e->getMessage(), ['status' => 500]);
    }

    return rest_ensure_response([
        'name' => $name,
        'enabled' => (bool) $value,
        'default' => (bool) ($controller->getDefaults()[$name] ?? false),
    ]);
}

function mailpoet_dev_companion_migrations_status() {
    $container = mailpoet_dev_companion_container();
    if ($container instanceof WP_Error) return $container;

    try {
        /** @var \MailPoet\Migrator\Migrator $migrator */
        $migrator = $container->get(\MailPoet\Migrator\Migrator::class);
        $status = $migrator->getStatus();
    } catch (\Throwable $e) {
        return new WP_Error('migrator_error', $e->getMessage(), ['status' => 500]);
    }

    $items = [];
    $counts = ['new' => 0, 'started' => 0, 'completed' => 0, 'failed' => 0, 'unknown' => 0];
    foreach ($status as $m) {
        if (!empty($m['unknown'])) {
            $counts['unknown']++;
        } elseif (isset($counts[$m['status']])) {
            $counts[$m['status']]++;
        }
        $items[] = [
            'name' => $m['name'],
            'level' => $m['level'],
            'status' => $m['status'],
            'started_at' => $m['started_at'],
            'completed_at' => $m['completed_at'],
            'retries' => $m['retries'],
            'error' => $m['error'],
            'unknown' => (bool) $m['unknown'],
        ];
    }

    return rest_ensure_response([
        'items' => $items,
        'counts' => $counts,
        'total' => count($items),
    ]);
}

function mailpoet_dev_companion_scheduler_list(WP_REST_Request $request) {
    if (!function_exists('as_get_scheduled_actions') || !class_exists(\ActionScheduler_Store::class)) {
        return new WP_Error('action_scheduler_missing', 'Action Scheduler is not loaded.', ['status' => 503]);
    }

    $args = ['per_page' => max(1, min(500, (int) $request->get_param('limit') ?: 50))];

    $status = $request->get_param('status');
    $allowedStatuses = [
        \ActionScheduler_Store::STATUS_PENDING,
        \ActionScheduler_Store::STATUS_RUNNING,
        \ActionScheduler_Store::STATUS_COMPLETE,
        \ActionScheduler_Store::STATUS_FAILED,
        \ActionScheduler_Store::STATUS_CANCELED,
    ];
    if (is_string($status) && $status !== '') {
        if (!in_array($status, $allowedStatuses, true)) {
            return new WP_Error('invalid_status', "Invalid status '$status'. Allowed: " . implode(', ', $allowedStatuses), ['status' => 400]);
        }
        $args['status'] = $status;
    }

    $group = $request->get_param('group');
    if (is_string($group) && $group !== '') {
        $args['group'] = $group;
    }

    $hookContains = $request->get_param('hook_contains');
    if (is_string($hookContains) && $hookContains !== '') {
        $args['search'] = $hookContains;
    }

    try {
        $actionIds = as_get_scheduled_actions($args, 'ids');
        $store = \ActionScheduler_Store::instance();
    } catch (\Throwable $e) {
        return new WP_Error('scheduler_error', $e->getMessage(), ['status' => 500]);
    }

    $items = [];
    foreach ($actionIds as $id) {
        try {
            $action = $store->fetch_action($id);
            $scheduled = $action->get_schedule()->get_date();
            $items[] = [
                'id' => (string) $id,
                'hook' => $action->get_hook(),
                'group' => $action->get_group(),
                'status' => $store->get_status($id),
                'scheduled_at' => $scheduled ? $scheduled->format('c') : null,
                'args' => $action->get_args(),
            ];
        } catch (\Throwable $e) {
            $items[] = ['id' => (string) $id, 'error' => $e->getMessage()];
        }
    }

    return rest_ensure_response(['items' => $items, 'total' => count($items)]);
}

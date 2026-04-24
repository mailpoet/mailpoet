<?php declare(strict_types = 1);

namespace MailPoet\EmailEditor\Integrations\MailPoet\Endpoints;

use Automattic\WooCommerce\EmailEditor\Email_Editor_Container;
use Automattic\WooCommerce\EmailEditor\Engine\PersonalizationTags\Personalization_Tags_Registry;
use MailPoet\API\REST\Endpoint;
use MailPoet\API\REST\ErrorResponse;
use MailPoet\API\REST\Request;
use MailPoet\API\REST\Response;
use MailPoet\EmailEditor\Integrations\MailPoet\EmailEditor;
use MailPoet\Logging\LoggerFactory;
use MailPoet\Validator\Builder;
use MailPoet\Validator\Schema;
use MailPoet\WP\Functions as WPFunctions;

class GenerateSubjectSuggestionsEndpoint extends Endpoint {
  private WPFunctions $wp;

  private const TAG_CATEGORIES_FOR_SUBJECT = ['Subscriber', 'Site', 'Customer', 'Order'];

  private const SUBJECT_MAX_LENGTH = 60;
  private const PREHEADER_MAX_LENGTH = 150;

  public function __construct(
    WPFunctions $wp
  ) {
    $this->wp = $wp;
  }

  public function handle(Request $request): Response {
    /** @var int $postId validated by schema */
    $postId = $request->getParam('post_id');

    if (!function_exists('wp_ai_client_prompt')) {
      return new ErrorResponse(
        503,
        __('AI text generation is not available.', 'mailpoet'),
        'mailpoet_ai_unavailable'
      );
    }

    $post = $this->wp->getPost($postId);
    if (!$post instanceof \WP_Post || $post->post_type !== 'mailpoet_email') { // phpcs:ignore Squiz.NamingConventions.ValidVariableName.MemberNotCamelCaps
      return new ErrorResponse(
        404,
        __('Email not found.', 'mailpoet'),
        'mailpoet_ai_email_not_found'
      );
    }

    if (!current_user_can('edit_post', $postId)) {
      return new ErrorResponse(
        403,
        __('You are not allowed to generate suggestions for this email.', 'mailpoet'),
        'mailpoet_ai_forbidden'
      );
    }

    $html = do_blocks($post->post_content); // phpcs:ignore Squiz.NamingConventions.ValidVariableName.MemberNotCamelCaps
    $bodyText = $this->wp->wpStripAllTags($html);
    $bodyText = (string)preg_replace('/\s+/', ' ', trim($bodyText));

    if ($bodyText === '') {
      return new ErrorResponse(
        400,
        __('Email body is empty. Add content before generating suggestions.', 'mailpoet'),
        'mailpoet_ai_empty_content'
      );
    }

    $personalizationTags = $this->getAvailablePersonalizationTags();
    $tagsInstruction = '';
    if (!empty($personalizationTags)) {
      $tagsList = implode(', ', array_map(function ($tag) {
        return $tag['token'] . ' (' . $tag['name'] . ')';
      }, $personalizationTags));
      $tagsInstruction = 'You may use these personalization tags in the subject lines and preview text to make them more personal: '
        . $tagsList . '. Use them sparingly — not every suggestion needs a tag. Insert the tag token exactly as shown (e.g. [mailpoet/subscriber-firstname]). ';
    }

    $systemInstruction = 'You are an expert email marketer. Generate subject lines and preview text for marketing emails. '
      . 'Match the tone and style of the email body — if it\'s a business email, keep it professional; '
      . 'if it\'s fun or casual, feel free to use emojis. '
      . 'IMPORTANT: Generate suggestions in the same language as the email body content. '
      . $tagsInstruction
      . 'Subject lines must be under 60 characters. Preview text must be under 150 characters and complement the subject line. '
      . 'Respond ONLY with valid JSON (no markdown, no code fences) in this exact format: '
      . '{"suggestions":[{"subject":"...","preheader":"..."},{"subject":"...","preheader":"..."},{"subject":"...","preheader":"..."},{"subject":"...","preheader":"..."}]}';

    $prompt = "Based on the following email body content, generate 4 different subject line and preview text pairs:\n\n" . $bodyText;

    $promptBuilder = wp_ai_client_prompt($prompt)
      ->using_system_instruction($systemInstruction)
      ->using_model_preference(
        ['anthropic', 'claude-sonnet-4-6'],
        ['google', 'gemini-3-flash-preview'],
        ['google', 'gemini-2.5-flash'],
        ['openai', 'gpt-5.4-mini'],
        ['openai', 'gpt-4.1-mini']
      )
      ->as_json_response();

    $logger = LoggerFactory::getInstance()->getLogger(LoggerFactory::TOPIC_EMAIL_EDITOR);

    if (!$promptBuilder->is_supported_for_text_generation()) {
      return new ErrorResponse(
        503,
        __('AI text generation is not available. Please check your AI provider configuration.', 'mailpoet'),
        'mailpoet_ai_unavailable'
      );
    }

    $result = $promptBuilder->generate_text();

    if (is_wp_error($result)) {
      $logger->error('AI subject generation failed', [
        'error_code' => $result->get_error_code(),
        'error_message' => $result->get_error_message(),
        'post_id' => $postId,
      ]);
      return new ErrorResponse(
        502,
        __('Failed to generate suggestions. Please check your AI provider configuration and try again.', 'mailpoet'),
        'mailpoet_ai_generation_failed'
      );
    }

    $decoded = $this->parseAiResponse($result);
    if ($decoded === null) {
      $logger->error('AI subject generation returned invalid JSON', [
        'response_type' => gettype($result),
        'response_length' => is_string($result) ? mb_strlen($result) : null,
        'response_preview' => is_string($result) ? mb_substr($result, 0, 200) : null,
        'post_id' => $postId,
      ]);
      return new ErrorResponse(
        502,
        __('AI returned an unexpected response.', 'mailpoet'),
        'mailpoet_ai_invalid_response'
      );
    }

    $validSuggestions = [];
    foreach ($decoded['suggestions'] as $suggestion) {
      if (
        !is_array($suggestion)
        || !isset($suggestion['subject'], $suggestion['preheader'])
        || !is_string($suggestion['subject'])
        || !is_string($suggestion['preheader'])
        || mb_strlen($suggestion['subject']) > self::SUBJECT_MAX_LENGTH
        || mb_strlen($suggestion['preheader']) > self::PREHEADER_MAX_LENGTH
      ) {
        $logger->info('AI subject suggestion filtered out', [
          'suggestion_type' => gettype($suggestion),
          'suggestion_keys' => is_array($suggestion) ? array_keys($suggestion) : [],
          'subject_length' => is_array($suggestion) && isset($suggestion['subject']) && is_string($suggestion['subject']) ? mb_strlen($suggestion['subject']) : null,
          'preheader_length' => is_array($suggestion) && isset($suggestion['preheader']) && is_string($suggestion['preheader']) ? mb_strlen($suggestion['preheader']) : null,
          'post_id' => $postId,
        ]);
        continue;
      }
      $validSuggestions[] = [
        'subject' => $suggestion['subject'],
        'preheader' => $suggestion['preheader'],
      ];
    }

    if (empty($validSuggestions)) {
      $logger->error('AI subject generation returned no valid suggestions', [
        'suggestion_count' => count($decoded['suggestions']),
        'post_id' => $postId,
      ]);
      return new ErrorResponse(
        502,
        __('AI did not return any valid suggestions. Please try again.', 'mailpoet'),
        'mailpoet_ai_no_valid_suggestions'
      );
    }

    return new Response(['suggestions' => $validSuggestions]);
  }

  /**
   * @return array{suggestions: array<int, mixed>}|null
   */
  private function parseAiResponse(string $result): ?array {
    $json = preg_replace('/^```(?:json)?\s*|```\s*$/i', '', trim($result)) ?? trim($result);
    $json = trim($json);

    $decoded = json_decode($json, true);

    if (is_array($decoded) && isset($decoded['suggestions']) && is_array($decoded['suggestions'])) {
      return $decoded;
    }

    if (is_array($decoded) && !isset($decoded['suggestions']) && isset($decoded[0])) {
      return ['suggestions' => $decoded];
    }

    return null;
  }

  /**
   * @return array<int, array{name: string, token: string}>
   */
  private function getAvailablePersonalizationTags(): array {
    if (!class_exists(Email_Editor_Container::class)) {
      return [];
    }
    $registry = Email_Editor_Container::container()->get(Personalization_Tags_Registry::class);
    $tags = [];
    $urlKeywords = ['url', 'link'];
    foreach ($registry->get_all() as $tag) {
      $postTypes = $tag->get_post_types();
      if (!empty($postTypes) && !in_array(EmailEditor::MAILPOET_EMAIL_POST_TYPE, $postTypes, true)) {
        continue;
      }
      if (!in_array($tag->get_category(), self::TAG_CATEGORIES_FOR_SUBJECT, true)) {
        continue;
      }
      $token = $tag->get_token();
      $tokenLower = strtolower($token);
      $isUrl = false;
      foreach ($urlKeywords as $keyword) {
        if (strpos($tokenLower, $keyword) !== false) {
          $isUrl = true;
          break;
        }
      }
      if ($isUrl) {
        continue;
      }
      $tags[] = [
        'name' => $tag->get_name(),
        'token' => $token,
      ];
    }
    return $tags;
  }

  public function checkPermissions(): bool {
    return current_user_can('edit_posts');
  }

  /** @return array<string, Schema> */
  public static function getRequestSchema(): array {
    return [
      'post_id' => Builder::integer()->required(),
    ];
  }
}

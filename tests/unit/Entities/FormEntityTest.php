<?php

namespace MailPoet\Entities;

class FormEntityTest extends \MailPoetUnitTest {

  private $body = [
    [
      'type' => 'columns',
      'body' => [
        [
          'type' => 'column',
          'body' => [
            [
              'type' => 'paragraph',
              'params' => [
                'content' => 'Paragraph 1',
              ],
            ],
            [
              'type' => 'divider',
              'params' => [],
            ],
            [
              'type' => 'paragraph',
              'params' => [
                'content' => 'Paragraph 2',
              ],
            ],
          ],
        ],
        [
          'type' => 'column',
          'body' => [
            [
              'type' => 'paragraph',
              'params' => [
                'content' => 'Paragraph 3',
              ],
            ],
            [
              'type' => 'heading',
              'params' => [
                'level' => 1,
                'content' => 'Heading 1',
              ],
            ],
          ],
        ],
      ],
    ],
  ];

  public function testGetBlocksByType() {
    $formEntity = new FormEntity('Test' );
    $formEntity->setBody($this->body);
    $paragraphs = $formEntity->getBlocksByType(FormEntity::PARAGRAPH_BLOCK_TYPE);
    expect($paragraphs)->count(3);
    expect($paragraphs[0]['params']['content'])->equals('Paragraph 1');
    expect($paragraphs[1]['params']['content'])->equals('Paragraph 2');
    expect($paragraphs[2]['params']['content'])->equals('Paragraph 3');

    $headings = $formEntity->getBlocksByType(FormEntity::HEADING_BLOCK_TYPE);
    expect($headings)->count(1);
    expect($headings[0]['params']['content'])->equals('Heading 1');

    $columns = $formEntity->getBlocksByType(FormEntity::COLUMNS_BLOCK_TYPE);
    expect($columns)->count(1);
    expect($columns[0]['body'])->count(2);
  }
}

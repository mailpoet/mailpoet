<?php declare(strict_types = 1);

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

  public function testGetBlocksByTypes(): void {
    $formEntity = new FormEntity('Test' );
    $formEntity->setBody($this->body);
    $paragraphs = $formEntity->getBlocksByTypes([FormEntity::PARAGRAPH_BLOCK_TYPE]);
    verify($paragraphs)->arrayCount(3);
    verify($paragraphs[0]['params']['content'])->equals('Paragraph 1');
    verify($paragraphs[1]['params']['content'])->equals('Paragraph 2');
    verify($paragraphs[2]['params']['content'])->equals('Paragraph 3');

    $headings = $formEntity->getBlocksByTypes([FormEntity::HEADING_BLOCK_TYPE]);
    verify($headings)->arrayCount(1);
    verify($headings[0]['params']['content'])->equals('Heading 1');

    $columns = $formEntity->getBlocksByTypes([FormEntity::COLUMNS_BLOCK_TYPE]);
    verify($columns)->arrayCount(1);
    verify($columns[0]['body'])->arrayCount(2);
  }

  public function testGetSegmentSelectionSegmentIds() {
    $formEntity = new FormEntity('Test' );
    $formEntity->setBody($this->body);
    $segmentIds = $formEntity->getSegmentBlocksSegmentIds();
    verify($segmentIds)->empty();

    // Add segment selection block to second columns
    $body = $this->body;
    $body[0]['body'][1]['body'][] = [
      'type' => 'segment',
      'params' => [
        'values' => [['id' => 1], ['id' => 3]],
      ],
    ];
    $formEntity->setBody($body);
    $segmentIds = $formEntity->getSegmentBlocksSegmentIds();
    verify($segmentIds)->equals([1, 3]);
  }
}

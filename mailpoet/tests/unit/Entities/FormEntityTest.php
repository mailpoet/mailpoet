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
    expect($paragraphs)->count(3);
    expect($paragraphs[0]['params']['content'])->equals('Paragraph 1');
    expect($paragraphs[1]['params']['content'])->equals('Paragraph 2');
    expect($paragraphs[2]['params']['content'])->equals('Paragraph 3');

    $headings = $formEntity->getBlocksByTypes([FormEntity::HEADING_BLOCK_TYPE]);
    expect($headings)->count(1);
    expect($headings[0]['params']['content'])->equals('Heading 1');

    $columns = $formEntity->getBlocksByTypes([FormEntity::COLUMNS_BLOCK_TYPE]);
    expect($columns)->count(1);
    expect($columns[0]['body'])->count(2);
  }

  public function testGetSegmentSelectionSegmentIds() {
    $formEntity = new FormEntity('Test' );
    $formEntity->setBody($this->body);
    $segmentIds = $formEntity->getSegmentBlocksSegmentIds();
    expect($segmentIds)->isEmpty();

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
    expect($segmentIds)->equals([1, 3]);
  }
}

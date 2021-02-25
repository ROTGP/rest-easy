<?php
use ROTGP\RestEasy\Test\IntegrationTestCase;

class EventsTest extends IntegrationTestCase
{  
    public function testBasicGetEvents()
    {
        $events = [];
        Event::listen('resteasy.modelevent', function ($value) use (&$events) {
            $events[] = $value;
        });

        $id = 5;
        $query = 'artists/' . $id;
        $this->get($query);

        $this->assertEquals('retrieved Artist with id 5', implode(',', $events));
    }

    public function testBasicUpdateEvents()
    {
        $events = [];
        Event::listen('resteasy.modelevent', function ($value) use (&$events) {
            $events[] = $value;
        });

        $id = 5;
        $query = 'artists/' . $id;
        $this->json('PUT', $query, [
            'biography' => 'foo',
            'record_label_id' => 1
            ]);
        $this->assertEquals('saving Artist with id 5, updating Artist with id 5, updated Artist with id 5, saved Artist with id 5', implode(', ', $events));
    }

    public function testBasicCreateEvents()
    {
        $events = [];
        Event::listen('resteasy.modelevent', function ($value) use (&$events) {
            $events[] = $value;
        });

        $query = 'artists';
        $this->json('POST', $query, [
            'name' => 'fooName',
            'biography' => 'barBiography',
            'record_label_id' => 3,
            ]);

        $this->assertEquals('saving Artist with id ?, creating Artist with id ?, created Artist with id 12, saved Artist with id 12', implode(', ', $events));
    }

    public function testBasicDeleteEvents()
    {
        $events = [];
        Event::listen('resteasy.modelevent', function ($value) use (&$events) {
            $events[] = $value;
        });

        $id = 5;
        $query = 'artists/' . $id;
        $this->json('DELETE', $query);

        $this->assertEquals('deleting Artist with id 5, deleted Artist with id 5', implode(', ', $events));
    }

    public function testBatchGetEvents()
    {
        $events = [];
        Event::listen('resteasy.modelevent', function ($value) use (&$events) {
            $events[] = $value;
        });

        $ids = '4,2';
        $query = 'artists/' . $ids . '?with=record_label';
        $response = $this->asUser(1)->get($query);

        $expected = [
            'retrieved Artist with id 4',
            'retrieved Artist with id 2',
            'retrieved RecordLabel with id 7',
            'retrieved RecordLabel with id 1'
        ];
        $this->assertEquals($expected, $events);
    }

    public function testBatchUpdateEvents()
    {
        $events = [];
        Event::listen('resteasy.modelevent', function ($value) use (&$events) {
            $events[] = $value;
        });

        $ids = '5,8,10';
        $query = 'artists/' . $ids;
        $response = $this->json('PUT', $query, 
            [
                [
                    'id' => 5,
                    'biography' => 'bio1',
                    'record_label_id' => 1
                ],
                [
                    'id' => 8,
                    'biography' => 'bio2',
                    'record_label_id' => 1
                ],
                [
                    'id' => 10,
                    'biography' => 'bio3',
                    'record_label_id' => 1
                ]
            ]
        );

        $expected = [
            'saving Artist with id 5',
            'updating Artist with id 5',
            'updated Artist with id 5',
            'saved Artist with id 5',
            'saving Artist with id 8',
            'updating Artist with id 8',
            'updated Artist with id 8',
            'saved Artist with id 8',
            'saving Artist with id 10',
            'updating Artist with id 10',
            'updated Artist with id 10',
            'saved Artist with id 10'
        ];
        $this->assertEquals($expected, $events);
    }

    public function testBatchCreateEvents()
    {
        $events = [];
        Event::listen('resteasy.modelevent', function ($value) use (&$events) {
            $events[] = $value;
        });

        $query = 'artists?with=record_label';
        $response = $this->asUser(1)->json('POST', $query, [
            [
                'name' => 'fooName1',
                'biography' => 'bio1',
                'record_label_id' => 3
            ],
            [
                'name' => 'fooName2',
                'biography' => 'bio2',
                'record_label_id' => 2
            ],
            [
                'name' => 'fooName3',
                'biography' => 'bio3',
                'record_label_id' => 1
            ]
        ]);

        $expected = [
            'saving Artist with id ?',
            'creating Artist with id ?',
            'created Artist with id 12',
            'saved Artist with id 12',
            'retrieved RecordLabel with id 3',
            'saving Artist with id ?',
            'creating Artist with id ?',
            'created Artist with id 13',
            'saved Artist with id 13',
            'retrieved RecordLabel with id 2',
            'saving Artist with id ?',
            'creating Artist with id ?',
            'created Artist with id 14',
            'saved Artist with id 14',
            'retrieved RecordLabel with id 1'
        ];
        $this->assertEquals($expected, $events);
    }

    public function testBatchDeleteEvents()
    {
        $events = [];
        Event::listen('resteasy.modelevent', function ($value) use (&$events) {
            $events[] = $value;
        });

        $idsToDelete = '2,5,9';
        $response = $this->json('DELETE', 'artists/' . $idsToDelete);
        $expected = [
            'deleting Artist with id 2',
            'deleted Artist with id 2',
            'deleting Artist with id 5',
            'deleted Artist with id 5',
            'deleting Artist with id 9',
            'deleted Artist with id 9'
        ];
        $this->assertEquals($expected, $events);
    }

    public function testBatchCreateManyEvents()
    {
        $events = [];
        Event::listen('resteasy.modelevent', function ($value) use (&$events) {
            $events[] = $value;
        });

        $query = 'artists?with=record_label';
        $payload = [];
        $expected = [];
        for ($i = 0; $i < 100; $i++) {
            $payload[] = [
                'name' => 'fooName' . $i,
                'biography' => 'bio' . $i,
                'record_label_id' => 1
            ];
            $expected[] = 'saving Artist with id ?';
            $expected[] = 'creating Artist with id ?';
            $expected[] = 'created Artist with id ' . ($i + 12);
            $expected[] = 'saved Artist with id ' . ($i + 12);
            $expected[] = 'retrieved RecordLabel with id 1';
        }
        $response = $this->asUser(1)->json('POST', $query, $payload);
        $this->assertEquals($expected, $events);
    }
}

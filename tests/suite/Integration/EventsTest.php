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

        $expected = [
            'Unspecified auth user retrieved Artist with id 5'
        ];

        $this->assertEquals($expected, $events);
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
    
        $expected = [
            'Unspecified auth user saving Artist with id 5',
            'Unspecified auth user updating Artist with id 5',
            'Unspecified auth user updated Artist with id 5',
            'Unspecified auth user saved Artist with id 5'
        ];

        $this->assertEquals($expected, $events);
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

        $expected = [
            'Unspecified auth user saving Artist with id pending',
            'Unspecified auth user creating Artist with id pending',
            'Unspecified auth user created Artist with id 12',
            'Unspecified auth user saved Artist with id 12'
        ];

        $this->assertEquals($expected, $events);
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

        $expected = [
            'Unspecified auth user deleting Artist with id 5',
            'Unspecified auth user deleted Artist with id 5'
        ];

        $this->assertEquals($expected, $events);
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
            'Auth user 1 retrieved Artist with id 4',
            'Auth user 1 retrieved Artist with id 2',
            'Auth user 1 retrieved RecordLabel with id 7',
            'Auth user 1 retrieved RecordLabel with id 1'
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
            'Unspecified auth user saving Artist with id 5',
            'Unspecified auth user updating Artist with id 5',
            'Unspecified auth user updated Artist with id 5',
            'Unspecified auth user saved Artist with id 5',
            'Unspecified auth user saving Artist with id 8',
            'Unspecified auth user updating Artist with id 8',
            'Unspecified auth user updated Artist with id 8',
            'Unspecified auth user saved Artist with id 8',
            'Unspecified auth user saving Artist with id 10',
            'Unspecified auth user updating Artist with id 10',
            'Unspecified auth user updated Artist with id 10',
            'Unspecified auth user saved Artist with id 10'
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
            'Auth user 1 saving Artist with id pending',
            'Auth user 1 creating Artist with id pending',
            'Auth user 1 created Artist with id 12',
            'Auth user 1 saved Artist with id 12',
            'Auth user 1 retrieved RecordLabel with id 3',
            'Auth user 1 saving Artist with id pending',
            'Auth user 1 creating Artist with id pending',
            'Auth user 1 created Artist with id 13',
            'Auth user 1 saved Artist with id 13',
            'Auth user 1 retrieved RecordLabel with id 2',
            'Auth user 1 saving Artist with id pending',
            'Auth user 1 creating Artist with id pending',
            'Auth user 1 created Artist with id 14',
            'Auth user 1 saved Artist with id 14',
            'Auth user 1 retrieved RecordLabel with id 1'
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
            'Unspecified auth user deleting Artist with id 2',
            'Unspecified auth user deleted Artist with id 2',
            'Unspecified auth user deleting Artist with id 5',
            'Unspecified auth user deleted Artist with id 5',
            'Unspecified auth user deleting Artist with id 9',
            'Unspecified auth user deleted Artist with id 9'
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
            $expected[] = 'Auth user 1 saving Artist with id pending';
            $expected[] = 'Auth user 1 creating Artist with id pending';
            $expected[] = 'Auth user 1 created Artist with id ' . ($i + 12);
            $expected[] = 'Auth user 1 saved Artist with id ' . ($i + 12);
            $expected[] = 'Auth user 1 retrieved RecordLabel with id 1';
        }
        $response = $this->asUser(1)->json('POST', $query, $payload);
        $this->assertEquals($expected, $events);
    }

    public function testNoEventsWhenEventsAreNotEnabled()
    {
        $events = [];
        Event::listen('resteasy.modelevent', function ($value) use (&$events) {
            $events[] = $value;
        });

        $ids = '4,2';
        $query = 'artists/' . $ids . '?with=record_label';
        $response = $this->asUser(8)->get($query);

        $expected = [];
        $this->assertEquals($expected, $events);
    }

    public function testEventsWhenPermissionsAreDisabled()
    {
        $events = [];
        Event::listen('resteasy.modelevent', function ($value) use (&$events) {
            $events[] = $value;
        });

        $ids = '4,2';
        $query = 'artists/' . $ids;

        /**
         * user 5 may not read artists, but user 5 also 
         * disables the eloquent guard, so finally the 
         * artists are readable. Events should fire even
         * though the eloquent guard is disabled.
         */ 
        $response = $this->asUser(5)->get($query);

        $expected = [
            'Auth user 5 retrieved Artist with id 4',
            'Auth user 5 retrieved Artist with id 2'
        ];
        $this->assertEquals($expected, $events);
    }

    public function testNoEventsWhenEventsAreDisabledButPermissionsAreEnabled()
    {
        $events = [];
        Event::listen('resteasy.modelevent', function ($value) use (&$events) {
            $events[] = $value;
        });

        $id = 4;
        $query = 'artists/' . $id;

        /**
         * user 8 may read artists, and  the eloquent 
         * guard will be enabled. Events should be 
         * disabled and thus should not fire.
         */ 
        $response = $this->asUser(8)->get($query);
        $response->assertStatus(200);

        $expected = [];
        $this->assertEquals($expected, $events);
    }
}

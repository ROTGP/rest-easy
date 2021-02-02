<?php

use ROTGP\RestEasy\Test\IntegrationTestCase;

class HooksTest extends IntegrationTestCase
{
    public function testWillListHook()
    {
        $result = '';
        Event::listen('hook.artist.will', function ($value) use (&$result) {
            $result = $value;
        });

        $recordLabelId = 4;
        $this->asUser(1)->get('artists')
            ->assertJsonCount(11)
            ->assertStatus(200);

        $this->assertEquals('willListHook', $result);
    }

    public function testWillGetHook()
    {
        $result = '';
        Event::listen('hook.artist.will', function ($value) use (&$result) {
            $result = $value;
        });

        $id = 5;
        $query = 'artists/' . $id;
        $this->get($query)->assertStatus(200);

        $this->assertEquals('willGetHook', $result);
    }

    public function testWillUpdateHook()
    {
        $result = '';
        Event::listen('hook.artist.will', function ($value) use (&$result) {
            $result = $value;
        });

        $id = 5;
        $query = 'artists/' . $id;
        $this->json('PUT', $query, [
            'biography' => 'foo',
            'record_label_id' => 1
            ])->assertStatus(200);

        $this->assertEquals('willUpdateHook', $result);
    }

    public function testWillCreateHook()
    {
        $result = '';
        Event::listen('hook.artist.will', function ($value) use (&$result) {
            $result = $value;
        });

        $query = 'artists';
        $this->json('POST', $query, [
            'name' => 'fooName',
            'biography' => 'barBiography',
            'record_label_id' => 3,
            ])->assertStatus(201);

        $this->assertEquals('willCreateHook', $result);
    }

    public function testWillDeleteHook()
    {
        $result = '';
        Event::listen('hook.artist.will', function ($value) use (&$result) {
            $result = $value;
        });

        $id = 5;
        $query = 'artists/' . $id;
        $this->json('DELETE', $query)
            ->assertStatus(204);

        $this->assertEquals('willDeleteHook', $result);
    }

    public function testDidGetHook()
    {
        $result = '';
        $model = null;
        Event::listen('hook.artist.did', function (...$value) use (&$result, &$model) {
            $result = $value[0];
            $model = $value[1];
        });

        $id = 5;
        $query = 'artists/' . $id;
        $this->get($query)->assertStatus(200);

        $this->assertEquals('didGetHook', $result);
        $this->assertInstanceOf('ROTGP\RestEasy\Test\Models\Artist', $model);
        $this->assertEquals($id, $model->id);
    }

    public function testDidGetManyHook()
    {
        $result = '';
        $collection = null;
        Event::listen('hook.artist.did', function (...$value) use (&$result, &$collection) {
            $result = $value[0];
            $collection = $value[1];
        });

        $ids = '1,5,7,10,11';
        $idsAsArr = array_map('intval', explode(',', $ids));

        $query = 'artists/' . $ids;
        $this->get($query)->assertStatus(200);

        $this->assertEquals('didGetManyHook', $result);
        $this->assertInstanceOf('Illuminate\Support\Collection', $collection);
        $this->assertEquals(
            $idsAsArr,
            array_values($collection->pluck('id')->toArray())
        );
    }

    public function testDidUpdateHook()
    {
        $result = '';
        $model = null;
        Event::listen('hook.artist.did', function (...$value) use (&$result, &$model) {
            $result = $value[0];
            $model = $value[1];
        });

        $id = 5;
        $query = 'artists/' . $id;
        $this->json('PUT', $query, [
            'biography' => 'foo',
            'record_label_id' => 1
            ])->assertStatus(200);

        $this->assertEquals('didUpdateHook', $result);
        $this->assertInstanceOf('ROTGP\RestEasy\Test\Models\Artist', $model);
        $this->assertEquals($id, $model->id);
        $this->assertEquals('foo', $model->biography);
    }

    public function testDidUpdateManyHook()
    {
        $result = '';
        $collection = null;
        Event::listen('hook.artist.did', function (...$value) use (&$result, &$collection) {
            $result = $value[0];
            $collection = $value[1];
        });

        $id = '5,8,10';
        $query = 'artists/' . $id;
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

        $response->assertStatus(200);
        $json = $this->decodeResponse($response);
        $this->assertCount(3, $json);
        $this->assertEquals(5, $json[0]['id']);
        $this->assertEquals(8, $json[1]['id']);
        $this->assertEquals(10, $json[2]['id']);
        $this->assertEquals('bio1', $json[0]['biography']);
        $this->assertEquals('bio2', $json[1]['biography']);
        $this->assertEquals('bio3', $json[2]['biography']);

        $this->assertEquals('didUpdateManyHook', $result);
        $this->assertInstanceOf('Illuminate\Support\Collection', $collection);
        $this->assertCount(3, $collection);
        $this->assertEquals(5, $collection[0]->id);
        $this->assertEquals(8, $collection[1]->id);
        $this->assertEquals(10, $collection[2]->id);
        $this->assertEquals('bio1', $collection[0]->biography);
        $this->assertEquals('bio2', $collection[1]->biography);
        $this->assertEquals('bio3', $collection[2]->biography);
    }

    public function testDidCreateHook()
    {
        $result = '';
        $model = null;
        Event::listen('hook.artist.did', function (...$value) use (&$result, &$model) {
            $result = $value[0];
            $model = $value[1];
        });

        $query = 'artists';
        $this->json('POST', $query, [
            'name' => 'fooName',
            'biography' => 'barBiography',
            'record_label_id' => 3,
            ])->assertStatus(201);

        $this->assertEquals('didCreateHook', $result);
        $this->assertInstanceOf('ROTGP\RestEasy\Test\Models\Artist', $model);
        $this->assertEquals(12, $model->id);
        $this->assertEquals('barBiography', $model->biography);
    }

    public function testDidCreateManyHook()
    {
        $result = '';
        $collection = null;
        Event::listen('hook.artist.did', function (...$value) use (&$result, &$collection) {
            $result = $value[0];
            $collection = $value[1];
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
        $response->assertStatus(201);
        $json = $this->decodeResponse($response);
        $this->assertCount(3, $json);
        $this->assertEquals(12, $json[0]['id']);
        $this->assertEquals(13, $json[1]['id']);
        $this->assertEquals(14, $json[2]['id']);
        $this->assertEquals('bio1', $json[0]['biography']);
        $this->assertEquals('bio2', $json[1]['biography']);
        $this->assertEquals('bio3', $json[2]['biography']);

        $this->assertEquals('didCreateManyHook', $result);
        $this->assertInstanceOf('Illuminate\Support\Collection', $collection);
        $this->assertCount(3, $collection);
        $this->assertEquals(12, $collection[0]->id);
        $this->assertEquals(13, $collection[1]->id);
        $this->assertEquals(14, $collection[2]->id);
        $this->assertEquals('bio1', $collection[0]->biography);
        $this->assertEquals('bio2', $collection[1]->biography);
        $this->assertEquals('bio3', $collection[2]->biography);

        $this->assertEquals(3, $collection[0]->recordLabel->id);
        $this->assertEquals('aftermath', $collection[0]->recordLabel->name);
    }

    public function testDidDeleteHook()
    {
        $result = '';
        $model = null;
        Event::listen('hook.artist.did', function (...$value) use (&$result, &$model) {
            $result = $value[0];
            $model = $value[1];
        });

        $id = 5;
        $query = 'artists/' . $id;
        $this->json('DELETE', $query)->assertStatus(204);

        $this->assertEquals('didDeleteHook', $result);
        $this->assertInstanceOf('ROTGP\RestEasy\Test\Models\Artist', $model);
        $this->assertEquals($id, $model->id);
    }

    public function testDidDeleteManyHook()
    {
        $result = '';
        $collection = null;
        Event::listen('hook.artist.did', function (...$value) use (&$result, &$collection) {
            $result = $value[0];
            $collection = $value[1];
        });

        $idsToDelete = '2,5,9,1';
        $idsToDeleteArr = array_map('intval', explode(',', $idsToDelete));
        $response = $this->json('DELETE', 'artists/' . $idsToDelete)
           ->assertStatus(204);

        $this->assertEquals('didDeleteManyHook', $result);
        $this->assertInstanceOf('Illuminate\Support\Collection', $collection);
        $this->assertEquals(
            $idsToDeleteArr,
            array_values($collection->pluck('id')->toArray())
        );
    }

    // AFTER

    public function testDidGetAfterHook()
    {
        $result = '';
        $model = null;
        Event::listen('hook.artist.did', function (...$value) use (&$result, &$model) {
            $result = $value[0];
            $model = $value[1];
        });

        $id = 5;
        $query = 'artists/' . $id;
        $this->get($query)->assertStatus(200);

        $this->assertEquals('didGetHook', $result);
        $this->assertInstanceOf('ROTGP\RestEasy\Test\Models\Artist', $model);
        $this->assertEquals($id, $model->id);
    }

    // public function testDidGetManyAfterHook()
    // {
    //     $result = '';
    //     $collection = null;
    //     Event::listen('hook.artist.did', function (...$value) use (&$result, &$collection) {
    //         $result = $value[0];
    //         $collection = $value[1];
    //     });

    //     $ids = '1,5,7,10,11';
    //     $idsAsArr = array_map('intval', explode(',', $ids));

    //     $query = 'artists/' . $ids;
    //     $this->get($query)->assertStatus(200);

    //     $this->assertEquals('didGetManyHook', $result);
    //     $this->assertInstanceOf('Illuminate\Support\Collection', $collection);
    //     $this->assertEquals(
    //         $idsAsArr,
    //         array_values($collection->pluck('id')->toArray())
    //     );
    // }

    // public function testDidUpdateAfterHook()
    // {
    //     $result = '';
    //     $model = null;
    //     Event::listen('hook.artist.did', function (...$value) use (&$result, &$model) {
    //         $result = $value[0];
    //         $model = $value[1];
    //     });

    //     $id = 5;
    //     $query = 'artists/' . $id;
    //     $this->json('PUT', $query, [
    //         'biography' => 'foo',
    //         'record_label_id' => 1
    //         ])->assertStatus(200);

    //     $this->assertEquals('didUpdateHook', $result);
    //     $this->assertInstanceOf('ROTGP\RestEasy\Test\Models\Artist', $model);
    //     $this->assertEquals($id, $model->id);
    //     $this->assertEquals('foo', $model->biography);
    // }

    // public function testDidUpdateManyAfterHook()
    // {
    //     $result = '';
    //     $collection = null;
    //     Event::listen('hook.artist.did', function (...$value) use (&$result, &$collection) {
    //         $result = $value[0];
    //         $collection = $value[1];
    //     });

    //     $id = '5,8,10';
    //     $query = 'artists/' . $id;
    //     $response = $this->json('PUT', $query, 
    //         [
    //             [
    //                 'id' => 5,
    //                 'biography' => 'bio1',
    //                 'record_label_id' => 1
    //             ],
    //             [
    //                 'id' => 8,
    //                 'biography' => 'bio2',
    //                 'record_label_id' => 1
    //             ],
    //             [
    //                 'id' => 10,
    //                 'biography' => 'bio3',
    //                 'record_label_id' => 1
    //             ]
    //         ]
    //     );

    //     $response->assertStatus(200);
    //     $json = $this->decodeResponse($response);
    //     $this->assertCount(3, $json);
    //     $this->assertEquals(5, $json[0]['id']);
    //     $this->assertEquals(8, $json[1]['id']);
    //     $this->assertEquals(10, $json[2]['id']);
    //     $this->assertEquals('bio1', $json[0]['biography']);
    //     $this->assertEquals('bio2', $json[1]['biography']);
    //     $this->assertEquals('bio3', $json[2]['biography']);

    //     $this->assertEquals('didUpdateManyHook', $result);
    //     $this->assertInstanceOf('Illuminate\Support\Collection', $collection);
    //     $this->assertCount(3, $collection);
    //     $this->assertEquals(5, $collection[0]->id);
    //     $this->assertEquals(8, $collection[1]->id);
    //     $this->assertEquals(10, $collection[2]->id);
    //     $this->assertEquals('bio1', $collection[0]->biography);
    //     $this->assertEquals('bio2', $collection[1]->biography);
    //     $this->assertEquals('bio3', $collection[2]->biography);
    // }

    // public function testDidCreateAfterHook()
    // {
    //     $result = '';
    //     $model = null;
    //     Event::listen('hook.artist.did', function (...$value) use (&$result, &$model) {
    //         $result = $value[0];
    //         $model = $value[1];
    //     });

    //     $query = 'artists';
    //     $this->json('POST', $query, [
    //         'name' => 'fooName',
    //         'biography' => 'barBiography',
    //         'record_label_id' => 3,
    //         ])->assertStatus(201);

    //     $this->assertEquals('didCreateHook', $result);
    //     $this->assertInstanceOf('ROTGP\RestEasy\Test\Models\Artist', $model);
    //     $this->assertEquals(12, $model->id);
    //     $this->assertEquals('barBiography', $model->biography);
    // }

    // public function testDidCreateManyAfterHook()
    // {
    //     $result = '';
    //     $collection = null;
    //     Event::listen('hook.artist.did', function (...$value) use (&$result, &$collection) {
    //         $result = $value[0];
    //         $collection = $value[1];
    //     });

    //     $query = 'artists?with=record_label';
    //     $response = $this->asUser(1)->json('POST', $query, [
    //         [
    //             'name' => 'fooName1',
    //             'biography' => 'bio1',
    //             'record_label_id' => 3
    //         ],
    //         [
    //             'name' => 'fooName2',
    //             'biography' => 'bio2',
    //             'record_label_id' => 2
    //         ],
    //         [
    //             'name' => 'fooName3',
    //             'biography' => 'bio3',
    //             'record_label_id' => 1
    //         ]
    //     ]);
    //     $response->assertStatus(201);
    //     $json = $this->decodeResponse($response);
    //     $this->assertCount(3, $json);
    //     $this->assertEquals(12, $json[0]['id']);
    //     $this->assertEquals(13, $json[1]['id']);
    //     $this->assertEquals(14, $json[2]['id']);
    //     $this->assertEquals('bio1', $json[0]['biography']);
    //     $this->assertEquals('bio2', $json[1]['biography']);
    //     $this->assertEquals('bio3', $json[2]['biography']);

    //     $this->assertEquals('didCreateManyHook', $result);
    //     $this->assertInstanceOf('Illuminate\Support\Collection', $collection);
    //     $this->assertCount(3, $collection);
    //     $this->assertEquals(12, $collection[0]->id);
    //     $this->assertEquals(13, $collection[1]->id);
    //     $this->assertEquals(14, $collection[2]->id);
    //     $this->assertEquals('bio1', $collection[0]->biography);
    //     $this->assertEquals('bio2', $collection[1]->biography);
    //     $this->assertEquals('bio3', $collection[2]->biography);

    //     $this->assertEquals(3, $collection[0]->recordLabel->id);
    //     $this->assertEquals('aftermath', $collection[0]->recordLabel->name);
    // }

    // public function testDidDeleteAfterHook()
    // {
    //     $result = '';
    //     $model = null;
    //     Event::listen('hook.artist.did', function (...$value) use (&$result, &$model) {
    //         $result = $value[0];
    //         $model = $value[1];
    //     });

    //     $id = 5;
    //     $query = 'artists/' . $id;
    //     $this->json('DELETE', $query)->assertStatus(204);

    //     $this->assertEquals('didDeleteHook', $result);
    //     $this->assertInstanceOf('ROTGP\RestEasy\Test\Models\Artist', $model);
    //     $this->assertEquals($id, $model->id);
    // }

    // public function testDidDeleteManyAfterHook()
    // {
    //     $result = '';
    //     $collection = null;
    //     Event::listen('hook.artist.did', function (...$value) use (&$result, &$collection) {
    //         $result = $value[0];
    //         $collection = $value[1];
    //     });

    //     $idsToDelete = '2,5,9,1';
    //     $idsToDeleteArr = array_map('intval', explode(',', $idsToDelete));
    //     $response = $this->json('DELETE', 'artists/' . $idsToDelete)
    //        ->assertStatus(204);

    //     $this->assertEquals('didDeleteManyHook', $result);
    //     $this->assertInstanceOf('Illuminate\Support\Collection', $collection);
    //     $this->assertEquals(
    //         $idsToDeleteArr,
    //         array_values($collection->pluck('id')->toArray())
    //     );
    // }
}

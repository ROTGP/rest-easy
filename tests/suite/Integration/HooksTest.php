<?php

use ROTGP\RestEasy\Test\IntegrationTestCase;
use ROTGP\RestEasy\Test\Models\Artist;

class HooksTest extends IntegrationTestCase
{
    public function testListHooks()
    {
        $eventNames = [];
        $query = '';
        $didCollection;
        $afterCollection;
        Event::listen('resteasy.artist.list.will', function (...$value) use (&$eventNames, &$query) {
            $eventNames[] = $value[0];
            $query = $value[1];
        });

        Event::listen('resteasy.artist.list.did', function (...$value) use (&$eventNames, &$didCollection) {
            $eventNames[] = $value[0];
            $didCollection = $value[1];
        });

        Event::listen('resteasy.artist.list.after', function (...$value) use (&$eventNames, &$afterCollection) {
            $eventNames[] = $value[0];
            $afterCollection = $value[1];
        });

        $response = $this->asUser(1)->get('artists')
            ->assertJsonCount(11)
            ->assertStatus(200);

        $json = $this->decodeResponse($response);

        $ids = [1,2,3,4,5,6,7,8,9,10,11];
        $this->assertEquals(
            $ids,
            array_column($json, 'id')
        );

        $this->assertEquals(
            $ids,
            $didCollection->pluck('id')->toArray()
        );

        $this->assertEquals(
            $didCollection->pluck('id')->toArray(),
            $afterCollection->pluck('id')->toArray()
        );
        
        $this->assertEquals('willList', $eventNames[0]);
        $this->assertEquals('didList', $eventNames[1]);
        $this->assertEquals('afterList', $eventNames[2]);
        $this->assertEquals('select * from "artists"', $query);
    }

    public function testGetHooks()
    {
        $id = 5;

        $artist = Artist::find($id);
        $this->assertEquals('', $artist->history);

        $query = 'artists/' . $id;
        $response = $this->get($query)->assertStatus(200);
        $json = $this->decodeResponse($response);

        $this->assertEquals('willGet.didGet', $json['history']);
        
        $artist->refresh();
        $this->assertEquals('willGet.didGet.afterGet', $artist->history);
    }

    public function testGetBatchHooks()
    {
        $ids = '5,7,9';
        $query = 'artists/' . $ids;
        $response = $this->get($query)->assertStatus(200);
        $json = $this->decodeResponse($response);

        $this->assertIndexedArray($json);
        $this->assertCount(3, $json);
        $this->assertEquals(5, $json[0]['id']);
        $this->assertEquals(7, $json[1]['id']);
        $this->assertEquals(9, $json[2]['id']);

        $this->assertEquals('willGetBatch.didGetBatch', $json[0]['history']);
        $this->assertEquals('willGetBatch.didGetBatch', $json[1]['history']);
        $this->assertEquals('willGetBatch.didGetBatch', $json[2]['history']);

        $ids = explode(',', $ids);
        foreach ($ids as $id)
            $this->assertEquals('willGetBatch.didGetBatch.afterGetBatch', Artist::find($id)->history);
    }

    public function testUpdateHooks()
    {
        $id = 5;

        $artist = Artist::find($id);
        $this->assertEquals('', $artist->history);

        $query = 'artists/' . $id;
        $response = $this->json('PUT', $query, [
            'biography' => 'foo',
            'record_label_id' => 1
            ])->assertStatus(200);
        
        $json = $this->decodeResponse($response);
        $this->assertEquals('willUpdate.didUpdate', $json['history']);
        
        $artist->refresh();
        $this->assertEquals('willUpdate.didUpdate.afterUpdate', $artist->history);
    }

    public function testUpdateBatchHooks()
    {
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
        $response->assertStatus(200);
        $json = $this->decodeResponse($response);

        $this->assertIndexedArray($json);
        $this->assertCount(3, $json);
        
        $this->assertEquals(5, $json[0]['id']);
        $this->assertEquals(8, $json[1]['id']);
        $this->assertEquals(10, $json[2]['id']);

        $this->assertEquals('bio1', $json[0]['biography']);
        $this->assertEquals('bio2', $json[1]['biography']);
        $this->assertEquals('bio3', $json[2]['biography']);

        $this->assertEquals('willUpdateBatch.didUpdateBatch', $json[0]['history']);
        $this->assertEquals('willUpdateBatch.didUpdateBatch', $json[1]['history']);
        $this->assertEquals('willUpdateBatch.didUpdateBatch', $json[2]['history']);

        $ids = explode(',', $ids);
        foreach ($ids as $id)
            $this->assertEquals('willUpdateBatch.didUpdateBatch.afterUpdateBatch', Artist::find($id)->history);
    }

    public function testCreateHooks()
    {
        $query = 'artists';
        $response = $this->json('POST', $query, [
            'name' => 'fooName',
            'biography' => 'barBiography',
            'record_label_id' => 3,
            ])->assertStatus(201);
        $json = $this->decodeResponse($response);

        $this->assertEquals('willCreate.didCreate', $json['history']);

        $artist = Artist::find($json['id']);
        $this->assertEquals('willCreate.didCreate.afterCreate', $artist->history);
    }

    public function testCreateBatchHooks()
    {
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
        
        $this->assertIndexedArray($json);
        $this->assertCount(3, $json);
        
        $this->assertEquals(12, $json[0]['id']);
        $this->assertEquals(13, $json[1]['id']);
        $this->assertEquals(14, $json[2]['id']);

        $this->assertEquals('bio1', $json[0]['biography']);
        $this->assertEquals('bio2', $json[1]['biography']);
        $this->assertEquals('bio3', $json[2]['biography']);

        $this->assertEquals('willCreateBatch.didCreateBatch', $json[0]['history']);
        $this->assertEquals('willCreateBatch.didCreateBatch', $json[1]['history']);
        $this->assertEquals('willCreateBatch.didCreateBatch', $json[2]['history']);

        foreach ($json as $model)
            $this->assertEquals('willCreateBatch.didCreateBatch.afterCreateBatch', Artist::find($model['id'])->history);
    }

    public function testDeleteHooks()
    {
        $eventNames = [];
        $models = [];
        Event::listen('resteasy.artist.delete', function (...$value) use (&$eventNames, &$models) {
            $eventNames[] = $value[0];
            $models[] = $value[1];
        });

        $id = 5;
        $query = 'artists/' . $id;
        $this->json('DELETE', $query)
            ->assertStatus(204);

        $this->assertEquals('willDelete', $eventNames[0]);
        $this->assertEquals('didDelete', $eventNames[1]);
        $this->assertEquals('afterDelete', $eventNames[2]);
        $this->assertInstanceOf(Artist::class, $models[0]);
        $this->assertInstanceOf(Artist::class, $models[1]);
        $this->assertInstanceOf(Artist::class, $models[2]);
        $this->assertEquals($id, $models[0]->id);
        $this->assertEquals($id, $models[1]->id);
        $this->assertEquals($id, $models[2]->id);
    }

    public function testDeletBatchHooks()
    {
        $eventNames = [];
        $collections = [];

        Event::listen('resteasy.artist.delete', function (...$value) use (&$eventNames, &$collections) {
            $eventNames[] = $value[0];
            $collections[] = $value[1];
        });

        $idsToDelete = '2,5,9';
        $idsToDeleteArr = array_map('intval', explode(',', $idsToDelete));
        $response = $this->json('DELETE', 'artists/' . $idsToDelete)
           ->assertStatus(204);
        $json = $this->decodeResponse($response);
        $this->assertNull($json);

        $this->assertEquals('willDeleteBatch', $eventNames[0]);
        $this->assertEquals('didDeleteBatch', $eventNames[1]);
        $this->assertEquals('afterDeleteBatch', $eventNames[2]);

        $ids = explode(',', $idsToDelete);

        $this->assertEquals(
            $ids,
            $collections[0]->pluck('id')->toArray()
        );

        $this->assertEquals(
            $ids,
            $collections[1]->pluck('id')->toArray()
        );

        $this->assertEquals(
            $ids,
            $collections[2]->pluck('id')->toArray()
        );
    }

    public function testThatNoAfterHooksAreCalledWhenNotRequested()
    {
        $id = 5;

        $artist = Artist::find($id);
        $this->assertEquals('', $artist->history);

        $query = 'artists/' . $id;

        // using user 9 will indicate not to use after events 
        $response = $this->asUser(9)->get($query)->assertStatus(200);
        $json = $this->decodeResponse($response);

        $this->assertEquals('willGet.didGet', $json['history']);
        
        $artist->refresh();
        $this->assertEquals('willGet.didGet', $artist->history);
        $this->assertNotEquals('willGet.didGet.afterGet', $artist->history);

        $response = $this->asUser(1)->get($query)->assertStatus(200);
        $json = $this->decodeResponse($response);

        $this->assertEquals('willGet.didGet.willGet.didGet', $json['history']);
        
        $artist->refresh();
        $this->assertEquals('willGet.didGet.willGet.didGet.afterGet', $artist->history);

        $response = $this->asUser(9)->get($query)->assertStatus(200);
        $json = $this->decodeResponse($response);

        $this->assertEquals('willGet.didGet.willGet.didGet.afterGet.willGet.didGet', $json['history']);
        
        $artist->refresh();
        $this->assertEquals('willGet.didGet.willGet.didGet.afterGet.willGet.didGet', $artist->history);
        $this->assertNotEquals('willGet.didGet.willGet.didGet.afterGet.willGet.didGet.afterGet', $artist->history);
    }
}

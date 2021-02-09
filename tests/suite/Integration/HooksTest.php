<?php

use ROTGP\RestEasy\Test\IntegrationTestCase;
use ROTGP\RestEasy\Test\Models\Artist;

class HooksTest extends IntegrationTestCase
{
    public function testWillListHook()
    {
        $eventName = '';
        $query = '';
        Event::listen('resteasy.artist.list', function (...$value) use (&$eventName, &$query) {
            $eventName = $value[0];
            $query = $value[1];
        });

        $this->asUser(1)->get('artists')
            ->assertJsonCount(11)
            ->assertStatus(200);

        $this->assertEquals('willList', $eventName);
        $this->assertEquals('select * from "artists"', $query);
    }

    public function testWillGetHook()
    {
        $id = 5;

        $artist = Artist::find($id);
        $this->assertEquals('', $artist->history);

        $query = 'artists/' . $id;
        $response = $this->get($query)->assertStatus(200);
        $json = $this->decodeResponse($response);

        $this->assertEquals('willGet.didGet', $json['history']);
        
        $artist->refresh();
        $this->assertEquals('willGet.didGet.didGetAfter', $artist->history);
    }

    public function testWillUpdateHook()
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
        $this->assertEquals('willUpdate.didUpdate.didUpdateAfter', $artist->history);
    }

    public function testWillCreateHook()
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
        $this->assertEquals('willCreate.didCreate.didCreateAfter', $artist->history);
    }

    public function testWillDeleteHook()
    {
        $eventNames = [];
        $model = null;
        Event::listen('resteasy.artist.delete', function (...$value) use (&$eventNames, &$model) {
            $eventNames[] = $value[0];
            $model = $value[1];
        });

        $id = 5;
        $query = 'artists/' . $id;
        $this->json('DELETE', $query)
            ->assertStatus(204);

        $this->assertEquals('willDelete', $eventNames[0]);
        $this->assertEquals('didDelete', $eventNames[1]);
        $this->assertEquals('didDeleteAfter', $eventNames[2]);
        $this->assertInstanceOf(Artist::class, $model);
        $this->assertEquals($id, $model->id);
    }
}

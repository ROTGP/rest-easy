<?php

use ROTGP\RestEasy\Test\IntegrationTestCase;
use ROTGP\RestEasy\Test\Models\Artist;

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
        $this->assertInstanceOf(Artist::class, $model);
        $this->assertEquals($id, $model->id);
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
        $response = $this->json('PUT', $query, [
            'biography' => 'foo',
            'record_label_id' => 1
            ])->assertStatus(200);

        $json = $this->decodeResponse($response);
        $this->assertEquals($id, $json['id']);
        $this->assertEquals('foo', $json['biography']);

        $this->assertEquals('didUpdateHook', $result);
        $this->assertInstanceOf(Artist::class, $model);
        $this->assertEquals($id, $model->id);
        $this->assertEquals('foo1', $model->biography);
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
        $response = $this->json('POST', $query, [
            'name' => 'fooName',
            'biography' => 'barBiography',
            'record_label_id' => 3,
            ])->assertStatus(201);

        $json = $this->decodeResponse($response);

        $this->assertEquals(12, $json['id']);
        $this->assertEquals('barBiography', $json['biography']);
        
        $this->assertEquals('didCreateHook', $result);
        $this->assertInstanceOf(Artist::class, $model);
        $this->assertEquals(12, $model->id);
        $this->assertEquals('barBiography1', $model->biography);
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
        $this->assertInstanceOf(Artist::class, $model);
        $this->assertEquals($id, $model->id);
    }

    // AFTER
    public function testDidGetAfterHook()
    {
        $id = 5;

        $artist = Artist::find($id);
        $artist->biography = 'foo';
        $artist->save();

        $query = 'artists/' . $id;
        $response = $this->get($query)->assertStatus(200);
        $json = $this->decodeResponse($response);
        
        $this->assertEquals($id, $json['id']);
        $this->assertEquals('foo', $json['biography']);

        $artist->refresh();

        $this->assertEquals($id, $artist->id);
        $this->assertEquals('foo1', $artist->biography);

        $artist->refresh();

        $this->assertEquals($id, $artist->id);
        $this->assertEquals('foo1', $artist->biography);

        $response = $this->get($query)->assertStatus(200);
        $json = $this->decodeResponse($response);
        
        $this->assertEquals($id, $json['id']);
        $this->assertEquals('foo1', $json['biography']);

        $artist->refresh();

        $this->assertEquals($id, $artist->id);
        $this->assertEquals('foo2', $artist->biography);

        $response = $this->get($query)->assertStatus(200);
        $response = $this->get($query)->assertStatus(200);
        $response = $this->get($query)->assertStatus(200);
        $response = $this->get($query)->assertStatus(200);
        $response = $this->get($query)->assertStatus(200);

        $json = $this->decodeResponse($response);

        $this->assertEquals($id, $json['id']);
        $this->assertEquals('foo6', $json['biography']);

        $artist->refresh();

        $this->assertEquals($id, $artist->id);
        $this->assertEquals('foo7', $artist->biography);
    }

    // public function testDidUpdateAfterHook()
    // {
    //     $id = 5;

    //     $artist = Artist::find($id);
        
    //     $query = 'artists/' . $id;
        
    //     $response = $this->json('PUT', $query, [
    //         'biography' => 'foobar',
    //         'record_label_id' => 1
    //         ])->assertStatus(200);

    //     $json = $this->decodeResponse($response);
    //     $this->assertEquals($id, $json['id']);
    //     $this->assertEquals('foo', $json['biography']);
    // }
}

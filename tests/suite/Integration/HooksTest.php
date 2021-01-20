<?php

use ROTGP\RestEasy\Test\IntegrationTestCase;
use Event;

class HooksTest extends IntegrationTestCase
{
    public function testListHook()
    {
        $result = '';
        Event::listen('hook.artist', function ($value) use (&$result) {
            $result = $value;
        });

        $recordLabelId = 4;
        $this->asUser(1)->get('artists')
            ->assertJsonCount(11)
            ->assertStatus(200);

        $this->assertEquals('listHook', $result);
    }

    public function testBasicGet()
    {
        $result = '';
        Event::listen('hook.artist', function ($value) use (&$result) {
            $result = $value;
        });

        $id = 5;
        $query = 'artists/' . $id;
        $this->get($query)
            ->assertJsonStructure([
                'id',
                'name',
                'biography',
                'record_label_id',
                'fan_mail_address'
            ])
            ->assertJsonFragment([
                'id' => $id,
                'name' => 'Katelin Bosco',
                'biography' => 'Corrupti nihil consectetur aut repellendus nulla. Voluptatibus quibusdam delectus magnam inventore numquam. Sit pariatur voluptas tempora sed laudantium aliquam. Excepturi velit corporis quia.',
                'record_label_id' => 4,
                'fan_mail_address' => "31333 Ethelyn Tunnel\nCeceliaburgh, CA 10259",
            ])
            ->assertStatus(200);

        $this->assertEquals('getHook', $result);
    }

    public function testBasicUpdate()
    {
        $result = '';
        Event::listen('hook.artist', function ($value) use (&$result) {
            $result = $value;
        });

        $id = 5;
        $query = 'artists/' . $id;
        $this->json('PUT', $query, [
            'biography' => 'foo',
            'record_label_id' => 1
            ])
            ->assertJsonStructure([
                'id',
                'name',
                'biography',
                'record_label_id',
                'fan_mail_address'
            ])
            ->assertJsonFragment([
                'id' => $id,
                'name' => 'Katelin Bosco',
                'biography' => 'foo',
                'record_label_id' => 1
            ])
            ->assertStatus(200);

        $this->assertEquals('updateHook', $result);
    }

    public function testBasicCreate()
    {
        $result = '';
        Event::listen('hook.artist', function ($value) use (&$result) {
            $result = $value;
        });

        $query = 'artists';
        $this->json('POST', $query, [
            'name' => 'fooName',
            'biography' => 'barBiography',
            'record_label_id' => 3,
            ])
            ->assertJsonStructure([
                'id',
                'name',
                'biography',
                'record_label_id',
                'fan_mail_address'
            ])
            ->assertJsonFragment([
                'id' => 12,
                'name' => 'fooName',
                'biography' => 'barBiography',
                'record_label_id' => 3,
            ])
            ->assertStatus(201);

        $this->assertEquals('createHook', $result);
    }

    public function testBasicDelete()
    {
        $result = '';
        Event::listen('hook.artist', function ($value) use (&$result) {
            $result = $value;
        });

        $id = 5;
        $query = 'artists/' . $id;
        $this->json('DELETE', $query)
            ->assertStatus(204);

        $this->get('artists/' . $id)
            ->assertJsonStructure([
                'http_status_code',
                'http_status_message'
            ])
            ->assertJsonFragment([
                'http_status_code' => 404,
                'http_status_message' => 'Not Found'
            ])
            ->assertStatus(404);

        $this->assertEquals('deleteHook', $result);
    }
}

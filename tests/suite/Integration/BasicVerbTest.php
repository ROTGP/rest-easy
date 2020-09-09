<?php
use ROTGP\RestEasy\Test\IntegrationTestCase;

class BasicVerbTest extends IntegrationTestCase
{
    // https://laravel.com/docs/7.x/http-tests#assert-json
    public function testBasicList()
    {
        $this->asUser(1)->get('artists')
            ->assertJsonCount(11)
            ->assertStatus(200);
    }

    public function testBasicGet()
    {
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
    }

    public function testBasicUpdate()
    {
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
    }

    public function testBasicCreate()
    {
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
    }

    public function testBasicDelete()
    {
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
    }
}

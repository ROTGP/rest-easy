<?php
use ROTGP\RestEasy\Test\IntegrationTestCase;
use ROTGP\RestEasy\Test\Models\User;

class BasicVerbTest extends IntegrationTestCase
{
    // https://laravel.com/docs/7.x/http-tests#assert-json
    public function testBasicList()
    {
        $this->actingAs(User::find(1))->get('artists')
            ->assertJsonCount(11)
            ->assertStatus(200);
    }

    public function testBasicGet()
    {
        // $id = 5;
        // $response = $this->get('artists/' . $id);
        // $json = $response->decodeResponseJson();
        // $this->assertEquals($id, $json['id']);
        // $this->assertEquals('Marta Christiansen', $json['name']);
        // $response->assertStatus(200);

        $id = 5;
        $this->get('artists/' . $id)
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
        $this->json('PUT', 'artists/' . $id, [
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
        $this->json('POST', 'artists', [
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
        $this->json('DELETE', 'artists/' . $id)
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

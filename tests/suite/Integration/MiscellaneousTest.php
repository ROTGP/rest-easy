<?php
use ROTGP\RestEasy\Test\IntegrationTestCase;

class MiscellaneousTest extends IntegrationTestCase
{
    public function testThatImmutableFieldsAreNotUpdated()
    {
        $id = 5;
        $query = 'artists/' . $id;
        $this->json('PUT', $query, [
            'name' => 'Jimbo',
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

    public function testOverridingControllerMethod()
    {
        $query = 'songs';
        $response = $this->json('POST', $query, [
            'name' => 'fooName',
            'album_id' => 1,
            'length_seconds' => 30,
            ]);

        $json = $this->decodeResponse($response);
        $this->assertEquals(137, $json['id']);
        $this->assertEquals('bar', $json['foo']);
    }
}

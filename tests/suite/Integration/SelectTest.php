<?php
use ROTGP\RestEasy\Test\IntegrationTestCase;
use ROTGP\RestEasy\Test\Models\User;

class SelectTest extends IntegrationTestCase
{
    public function testGetSelect()
    {
        $query = 'artists/1';
        $response = $this->get($query);
        $json = $this->decodeResponse($response);
        $this->assertArrayHasKey('name', $json);
        $this->assertArrayHasKey('biography', $json);
        $this->assertArrayHasKey('record_label_id', $json);
        $this->assertEquals('Ellis Wyman', $json['name']);
        $this->assertEquals(
            'Error aliquam fugiat quas quam omnis aut. Impedit ut quod quo voluptatem. Nulla voluptatem voluptatem ipsa amet. Suscipit quos necessitatibus vel.',
            $json['biography']
        );
        $this->assertEquals(7, $json['record_label_id']);

        // @TODO why does this return id when it has not been requested?
        $query = 'artists/1?select=name,record_label_id';
        $response = $this->get($query);
        $json = $this->decodeResponse($response);
        $this->assertArrayHasKey('name', $json);
        $this->assertArrayNotHasKey('biography', $json);
        $this->assertArrayHasKey('record_label_id', $json);
        $this->assertEquals('Ellis Wyman', $json['name']);
        $this->assertEquals(7, $json['record_label_id']);
    }

    public function testListSelect()
    {
        $query = 'artists';
        $this->get($query)
            ->assertJsonStructure([[
                'id',
                'name',
                'biography',
                'record_label_id',
                'fan_mail_address'
            ]]
        )->assertStatus(200);

        $query = 'artists?select=name,record_label_id';
        $this->get($query)
            ->assertJsonStructure([[
                'id',
                'name',
                'record_label_id'
            ]]
        )->assertStatus(200);
    }
}

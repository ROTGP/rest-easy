<?php
use ROTGP\RestEasy\Test\IntegrationTestCase;
use ROTGP\RestEasy\Test\Models\User;

class WithRelationshipsTest extends IntegrationTestCase
{
    public function testThatListWithRelationshipsAndNoAuthUserReturnsNoRelationships()
    {
        $response = $this->get('artists?with=record_label,users,albums,foo');
        $json = $response->decodeResponseJson();
        $response->assertJsonCount(11);
        $response->assertStatus(200);
        $artist = $json[0];
        $this->assertArrayNotHasKey('record_label', $artist);
        $this->assertArrayNotHasKey('users', $artist);
        $this->assertArrayNotHasKey('albums', $artist);
        $this->assertArrayNotHasKey('foo', $artist);
    }

    public function testThatListWithRelationshipsAndAuthUserOneReturnsRelationships()
    {
        $response = $this->actingAs(User::find(1))->get('artists?with=record_label,users,albums,foo');
        $json = $response->decodeResponseJson();
        $artist = $json[0];
        $this->assertArrayHasKey('record_label', $artist);
        $this->assertArrayHasKey('users', $artist);
        $this->assertArrayHasKey('albums', $artist);
        $this->assertCount(3, $artist['users']);
        $this->assertCount(3, $artist['albums']);
        $this->assertEquals('cash_money_billionaire_records', $artist['record_label']['name']);
    }

    public function testThatListWithRelationshipsAndAuthUserTwoReturnsRelationships()
    {
        $response = $this->actingAs(User::find(2))->get('artists?with=record_label,users,albums,foo');
        $json = $response->decodeResponseJson();
        $artist = $json[0];
        $this->assertArrayHasKey('record_label', $artist);
        $this->assertArrayNotHasKey('users', $artist);
        $this->assertArrayHasKey('albums', $artist);
        $this->assertCount(3, $artist['albums']);
        $this->assertEquals('cash_money_billionaire_records', $artist['record_label']['name']);
    }

    public function testThatNotRequestingRelationshipsReturnsNoRelationships()
    {
        $response = $this->actingAs(User::find(1))->get('artists');
        $json = $response->decodeResponseJson();
        $artist = $json[0];
        $this->assertArrayNotHasKey('record_label', $artist);
        $this->assertArrayNotHasKey('users', $artist);
        $this->assertArrayNotHasKey('albums', $artist);
    }
        

    // public function testWithRelationshipsWithGet()
    // {
    //     $response = $this->get('users/4?with=albums,songs,role');
    //     $json = $response->decodeResponseJson();
    //     $response->assertStatus(200);
    //     $this->assertArrayHasKey('albums', $json);
    //     $this->assertArrayHasKey('songs', $json);
    //     $this->assertArrayNotHasKey('role', $json);
    //     $this->assertCount(4, $json['albums']);
    //     $this->assertCount(7, $json['songs']);
    // }

    // public function testWithRelationshipsWithUpdate()
    // {
    //     $response = $this->json('PUT', 'users/' . $id, [
    //         'biography' => 'foo',
    //         'record_label_id' => 1
    //         ]);

    //     $response
    //         ->assertJsonStructure([
    //             'id',
    //             'name',
    //             'biography',
    //             'record_label_id',
    //             'fan_mail_address'
    //         ])
    //         ->assertJsonFragment([
    //             'id' => $id,
    //             'name' => 'Katelin Bosco',
    //             'biography' => 'foo',
    //             'record_label_id' => 1
    //         ])
    //         ->assertStatus(200);
    // }
}

<?php
use ROTGP\RestEasy\Test\IntegrationTestCase;
use ROTGP\RestEasy\Test\Models\User;

class WithRelationshipsTest extends IntegrationTestCase
{
    public function testThatListWithRelationshipsAndNoAuthUserReturnsNoRelationships()
    {
        $query = 'artists?with=record_label,users,albums,foo';
        $response = $this->get($query);
        $json = $this->decodeResponse($response);
        $response->assertJsonCount(11);
        $response->assertStatus(200);
        $entity = $json[0];
        $this->assertArrayNotHasKey('record_label', $entity);
        $this->assertArrayNotHasKey('users', $entity);
        $this->assertArrayNotHasKey('albums', $entity);
        $this->assertArrayNotHasKey('foo', $entity);
    }

    public function testThatListWithRelationshipsAndAuthUserOneReturnsRelationships()
    {
        $query = 'artists?with=record_label,users,albums,foo';
        $response = $this->asUser(1)->get($query);
        $json = $this->decodeResponse($response);
        $entity = $json[0];
        $this->assertArrayHasKey('record_label', $entity);
        $this->assertArrayHasKey('users', $entity);
        $this->assertArrayHasKey('albums', $entity);
        $this->assertCount(3, $entity['users']);
        $this->assertCount(3, $entity['albums']);
        $this->assertEquals('cash_money_billionaire_records', $entity['record_label']['name']);
    }

    public function testThatListWithRelationshipsAndAuthUserTwoReturnsRelationships()
    {
        $query = 'artists?with=record_label,users,albums,foo';
        $response = $this->asUser(2)->get($query);
        $json = $this->decodeResponse($response);
        $artist = $json[0];
        $this->assertArrayHasKey('record_label', $artist);
        $this->assertArrayNotHasKey('users', $artist);
        $this->assertArrayHasKey('albums', $artist);
        $this->assertCount(3, $artist['albums']);
        $this->assertEquals('cash_money_billionaire_records', $artist['record_label']['name']);
    }

    public function testThatListNotRequestingRelationshipsReturnsNoRelationships()
    {
        $query = 'artists';
        $response = $this->asUser(1)->get($query);
        $json = $this->decodeResponse($response);
        $artist = $json[0];
        $this->assertArrayNotHasKey('record_label', $artist);
        $this->assertArrayNotHasKey('users', $artist);
        $this->assertArrayNotHasKey('albums', $artist);
    }

    public function testThatGetWithRelationshipsAndNoAuthUserReturnsNoRelationships()
    {
        $query = 'artists/5?with=record_label,users,albums,foo';
        $response = $this->get($query);
        $json = $this->decodeResponse($response);
        $response->assertJsonCount(8);
        $response->assertStatus(200);
        $this->assertArrayNotHasKey('record_label', $json);
        $this->assertArrayNotHasKey('users', $json);
        $this->assertArrayNotHasKey('albums', $json);
        $this->assertArrayNotHasKey('foo', $json);
    }

    public function testThatGetWithRelationshipsAndAuthUserOneReturnsRelationships()
    {
        $query = 'artists/5?with=record_label,users,albums,foo';
        $response = $this->asUser(1)->get($query);
        $json = $this->decodeResponse($response);
        $this->assertArrayHasKey('record_label', $json);
        $this->assertArrayHasKey('users', $json);
        $this->assertArrayHasKey('albums', $json);
        $this->assertCount(3, $json['albums']);
        $this->assertEquals('epic', $json['record_label']['name']);
    }

    public function testThatGetWithNestedRelationshipsAndAuthUserOneReturnsNestedRelationship()
    {
        $id = 5;
        $query = 'albums/' . $id . '?with=artist.record_label'; 
        $response = $this->asUser(1)->json('GET', $query);
        $json = $this->decodeResponse($response);
        $this->assertArrayHasKey('artist', $json);
        $this->assertArrayHasKey('record_label', $json['artist']);
        $this->assertEquals(1, $json['artist']['record_label']['id']);
    }

    public function testThatGetWithRelationshipsAndAuthUserTwoReturnsRelationships()
    {
        $query = 'artists/5?with=record_label,users,albums,foo';
        $response = $this->asUser(2)->get($query);
        $json = $this->decodeResponse($response);
        $this->assertArrayHasKey('record_label', $json);
        $this->assertArrayNotHasKey('users', $json);
        $this->assertArrayHasKey('albums', $json);
        $this->assertCount(3, $json['albums']);
        $this->assertEquals('epic', $json['record_label']['name']);
    }

    public function testThatGetNotRequestingRelationshipsReturnsNoRelationships()
    {
        $query = 'artists/5';
        $response = $this->asUser(1)->get($query);
        $json = $this->decodeResponse($response);
        $this->assertArrayNotHasKey('record_label', $json);
        $this->assertArrayNotHasKey('users', $json);
        $this->assertArrayNotHasKey('albums', $json);
    }

    public function testThatListRequestingRelationshipsOnModelWithNoSafeRelationshipsMethodDefinedReturnsNoRelationships()
    {
        $query = 'plays?with=song,user,foo';
        $response = $this->get($query);
        $json = $this->decodeResponse($response);
        $entity = $json[0];
        $this->assertArrayNotHasKey('song', $entity);
        $this->assertArrayNotHasKey('user', $entity);
        $this->assertArrayNotHasKey('foo', $entity);
    }

    public function testThatGetRequestingRelationshipsOnModelWithNoSafeRelationshipsMethodDefinedReturnsNoRelationships()
    {
        $query = 'plays/5?with=song,user,foo';
        $response = $this->get($query);
        $entity = $response->decodeResponseJson();
        $this->assertArrayNotHasKey('song', $entity);
        $this->assertArrayNotHasKey('user', $entity);
        $this->assertArrayNotHasKey('foo', $entity);
    }

    public function testThatUpdateWithRelationshipsReturnsRelationships()
    {
        $id = 5;
        $query = 'artists/' . $id . '?with=record_label,users,albums,foo';
        $response = $this->asUser(1)->json('PUT', $query, [
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
        $json = $response->decodeResponseJson();

        $this->assertArrayHasKey('record_label', $json);
        $this->assertArrayHasKey('users', $json);
        $this->assertArrayHasKey('albums', $json);
        $this->assertArrayNotHasKey('foo', $json);
    }

    public function testThatCreateWithRelationshipsReturnsRelationships()
    {
        $query = 'artists?with=record_label,users,albums,foo';
        $response = $this->asUser(1)->json('POST', $query, [
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
        $json = $response->decodeResponseJson();
        $this->assertArrayHasKey('record_label', $json);
        $this->assertArrayHasKey('users', $json);
        $this->assertArrayHasKey('albums', $json);
        $this->assertArrayNotHasKey('foo', $json);
        $this->assertEquals('aftermath', $json['record_label']['name']);
    }

    public function testWithCount()
    {
        $query = 'artists/6?with_count=record_label,users,albums&with=users';
        $response = $this->asUser(1)->get($query);
        $json = $this->decodeResponse($response);
        $this->assertArrayHasKey('record_label_count', $json);
        $this->assertEquals(1, $json['record_label_count']);
        $this->assertArrayHasKey('users_count', $json);
        $this->assertEquals(3, $json['users_count']);
        $this->assertArrayHasKey('albums_count', $json);
        $this->assertEquals(2, $json['albums_count']);
        $this->assertCount(3, $json['users']);
    }
}

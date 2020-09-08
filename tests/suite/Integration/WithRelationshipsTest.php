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
        $entity = $json[0];
        $this->assertArrayNotHasKey('record_label', $entity);
        $this->assertArrayNotHasKey('users', $entity);
        $this->assertArrayNotHasKey('albums', $entity);
        $this->assertArrayNotHasKey('foo', $entity);
    }

    public function testThatListWithRelationshipsAndAuthUserOneReturnsRelationships()
    {
        $response = $this->actingAs(User::find(1))->get('artists?with=record_label,users,albums,foo');
        $json = $response->decodeResponseJson();
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
        $response = $this->actingAs(User::find(2))->get('artists?with=record_label,users,albums,foo');
        $json = $response->decodeResponseJson();
        $artist = $json[0];
        $this->assertArrayHasKey('record_label', $artist);
        $this->assertArrayNotHasKey('users', $artist);
        $this->assertArrayHasKey('albums', $artist);
        $this->assertCount(3, $artist['albums']);
        $this->assertEquals('cash_money_billionaire_records', $artist['record_label']['name']);
    }

    public function testThatListNotRequestingRelationshipsReturnsNoRelationships()
    {
        $response = $this->actingAs(User::find(1))->get('artists');
        $json = $response->decodeResponseJson();
        $artist = $json[0];
        $this->assertArrayNotHasKey('record_label', $artist);
        $this->assertArrayNotHasKey('users', $artist);
        $this->assertArrayNotHasKey('albums', $artist);
    }

    public function testThatGetWithRelationshipsAndNoAuthUserReturnsNoRelationships()
    {
        $response = $this->get('artists/5?with=record_label,users,albums,foo');
        $json = $response->decodeResponseJson();
        $response->assertJsonCount(7);
        $response->assertStatus(200);
        $this->assertArrayNotHasKey('record_label', $json);
        $this->assertArrayNotHasKey('users', $json);
        $this->assertArrayNotHasKey('albums', $json);
        $this->assertArrayNotHasKey('foo', $json);
    }

    public function testThatGetWithRelationshipsAndAuthUserOneReturnsRelationships()
    {
        $response = $this->actingAs(User::find(1))->get('artists/5?with=record_label,users,albums,foo');
        $json = $response->decodeResponseJson();
        $this->assertArrayHasKey('record_label', $json);
        $this->assertArrayHasKey('users', $json);
        $this->assertArrayHasKey('albums', $json);
        $this->assertCount(3, $json['users']);
        $this->assertCount(3, $json['albums']);
        $this->assertEquals('epic', $json['record_label']['name']);
    }

    public function testThatGetWithRelationshipsAndAuthUserTwoReturnsRelationships()
    {
        $response = $this->actingAs(User::find(2))->get('artists/5?with=record_label,users,albums,foo');
        $json = $response->decodeResponseJson();
        $this->assertArrayHasKey('record_label', $json);
        $this->assertArrayNotHasKey('users', $json);
        $this->assertArrayHasKey('albums', $json);
        $this->assertCount(3, $json['albums']);
        $this->assertEquals('epic', $json['record_label']['name']);
    }

    public function testThatGetNotRequestingRelationshipsReturnsNoRelationships()
    {
        $response = $this->actingAs(User::find(1))->get('artists/5');
        $json = $response->decodeResponseJson();
        $this->assertArrayNotHasKey('record_label', $json);
        $this->assertArrayNotHasKey('users', $json);
        $this->assertArrayNotHasKey('albums', $json);
    }

    public function testThatListRequestingRelationshipsOnModelWithNoSafeRelationshipsMethodDefinedReturnsNoRelationships()
    {
        $response = $this->get('plays?with=song,user,foo');
        $json = $response->decodeResponseJson();
        $entity = $json[0];
        $this->assertArrayNotHasKey('song', $entity);
        $this->assertArrayNotHasKey('user', $entity);
        $this->assertArrayNotHasKey('foo', $entity);
    }

    public function testThatGetRequestingRelationshipsOnModelWithNoSafeRelationshipsMethodDefinedReturnsNoRelationships()
    {
        $response = $this->get('plays/5?with=song,user,foo');
        $entity = $response->decodeResponseJson();
        $this->assertArrayNotHasKey('song', $entity);
        $this->assertArrayNotHasKey('user', $entity);
        $this->assertArrayNotHasKey('foo', $entity);
    }

    public function testThatUpdateWithRelationshipsReturnsRelationships()
    {
        $id = 5;
        $response = $this->actingAs(User::find(1))->json('PUT', 'artists/' . $id . '?with=record_label,users,albums,foo', [
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
        $entity = $response->decodeResponseJson();
        $this->assertArrayHasKey('record_label', $entity);
        $this->assertArrayHasKey('users', $entity);
        $this->assertArrayHasKey('albums', $entity);
        $this->assertArrayNotHasKey('foo', $entity);
    }

    public function testThatCreateWithRelationshipsReturnsRelationships()
    {
        $response = $this->actingAs(User::find(1))->json('POST', 'artists?with=record_label,users,albums,foo', [
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
        $entity = $response->decodeResponseJson();
        $this->assertArrayHasKey('record_label', $entity);
        $this->assertArrayHasKey('users', $entity);
        $this->assertArrayHasKey('albums', $entity);
        $this->assertArrayNotHasKey('foo', $entity);
        $this->assertEquals('aftermath', $entity['record_label']['name']);
    }
}

<?php
use ROTGP\RestEasy\Test\IntegrationTestCase;
use ROTGP\RestEasy\Test\Models\User;
use Carbon\Carbon;

class PermissionsTest extends IntegrationTestCase
{
    // user 1 has all permissions for albums
    public function testCanReadWithExplicitPermission()
    {
        $id = 1;
        $query = 'albums/' . $id;
        $response = $this->asUser(1)->json('GET', $query);
        $response->assertStatus(200);
        $json = $this->decodeResponse($response);
        $this->assertEquals($id, $json['id']);
    }

    public function testCanCreateWithExplicitPermission()
    {
        $query = 'albums';
        $response = $this->asUser(1)->json('POST', $query, [
            'name' => 'Foo',
            'artist_id' => 1,
            'genre_id' => 1,
            'release_date' => '10-11-2020',
            'price' => 9.99,
            ]
        );
        $response->assertStatus(201);
        $json = $this->decodeResponse($response);
        $this->assertEquals(29, $json['id']);
    }

    public function testCanUpdateWithExplicitPermission()
    {
        $id = 4;
        $query = 'albums/' . $id;
        $response = $this->asUser(1)->get($query);
        $response->assertStatus(200);
        $originalJson = $this->decodeResponse($response);

        $json = $originalJson;
        $json['name'] = 'Foo';
        $response = $this->asUser(1)->json('PUT', $query, $json)
            ->assertStatus(200);
        $json = $this->decodeResponse($response);
        $this->assertEquals($id, $json['id']);
        $this->assertEquals('Foo', $json['name']);
    }

    public function testCanDeleteWithExplicitPermission()
    {
        $id = 5;
        $query = 'albums/' . $id;
        $response = $this->asUser(1)->json('GET', $query);
        $response->assertStatus(200);
        $json = $this->decodeResponse($response);
        $this->assertEquals($id, $json['id']);

        $this->json('DELETE', $query)
            ->assertStatus(204);

        $this->get($query)
            ->assertJsonStructure([
                'http_status_code',
                'http_status_message',
                'resource_id'
            ])
            ->assertJsonFragment([
                'http_status_code' => 404,
                'http_status_message' => 'Not Found',
                'resource_id' => $id
            ])
            ->assertStatus(404);
    }

    public function testCanAttachWithExplicitPermission()
    {
        $query = 'users/1?attach_albums=' . implode(',', [1,2,3]);
        $response = $this->asUser(1)->get($query)
            ->assertStatus(200);
    }

    public function testCanSyncWithExplicitPermission()
    {
        $query = 'users/1?sync_albums=' . implode(',', [1,2,3]);
        $response = $this->asUser(1)->get($query)
            ->assertStatus(200);
    }

    public function testCanDetachWithExplicitPermission()
    {
        $query = 'users/1?detach_albums=' . implode(',', [1,2,3]);
        $response = $this->asUser(1)->get($query)
            ->assertStatus(200);
    }

    // user 3 has no permissions for albums
    public function testCannotReadWithoutExplicitPermission()
    {
        $id = 1;
        $query = 'albums/' . $id;
        $response = $this->asUser(3)->json('GET', $query);
        $this->assertForbidden($response);
    }

    public function testCannotCreateWithoutExplicitPermission()
    {
        $query = 'albums';
        $response = $this->asUser(3)->json('POST', $query, [
            'name' => 'Foo',
            'artist_id' => 1,
            'genre_id' => 1,
            'release_date' => '10-11-2020',
            'price' => 9.99,
            ]
        );
        $this->assertForbidden($response);
    }

    public function testCannotUpdateWithoutExplicitPermission()
    {
        $id = 4;
        $query = 'albums/' . $id;
        $response = $this->asUser(1)->get($query);
        $response->assertStatus(200);
        $originalJson = $this->decodeResponse($response);

        $json = $originalJson;
        $json['name'] = 'Foo';
        $response = $this->asUser(3)->json('PUT', $query, $json);
        $this->assertForbidden($response);
    }

    public function testCannotDeleteWithoutExplicitPermission()
    {
        $query = 'albums/5';
        $response = $this->asUser(3)->json('DELETE', $query);
        $this->assertForbidden($response);
    }

    public function testCannotAttachWithExplicitPermission()
    {
        $query = 'users/1?attach_albums=' . implode(',', [1,2,3]);
        $response = $this->asUser(3)->get($query);
        $this->assertForbidden($response);
    }

    public function testCannotSyncWithExplicitPermission()
    {
        $query = 'users/1?sync_albums=' . implode(',', [1,2,3]);
        $response = $this->asUser(3)->get($query);
        $this->assertForbidden($response);
    }

    public function testCannotDetachWithExplicitPermission()
    {
        $query = 'users/1?detach_albums=' . implode(',', [12, 16]);
        $response = $this->asUser(3)->get($query);
        $this->assertForbidden($response);
    }

    public function testCanReadWithoutExplicitPermissionWhenGuardModelsIsFalse()
    {
        $id = 1;
        $query = 'plays/' . $id;
        $response = $this->asUser(1)->json('GET', $query);
        $response->assertStatus(200);
        $json = $this->decodeResponse($response);
        $this->assertEquals(1, $json['id']);
        $this->assertEquals(70, $json['song_id']);
    }

    public function testCanReadWithoutExplicitPermissionWhenGuardModelsIsTrueAndExplicitPermissionsIsFalse()
    {
        $id = 1;
        $query = 'streaming_services/' . $id;
        $response = $this->asUser(1)->json('GET', $query);
        $response->assertStatus(200);
        $json = $this->decodeResponse($response);
        $this->assertEquals(1, $json['id']);
        $this->assertEquals('youtube', $json['name']);
    }

    public function testCannotCreateWithoutExplicitPermissionWhenGuardModelsIsTrueAndExplicitPermissionsIsFalseButPermissionIsNotGranted()
    {
        $query = 'streaming_services';
        $response = $this->asUser(1)->json('POST', $query, ['Foo' => 'bar']);
        $this->assertForbidden($response);
    }

    public function testPermissionIsDeniedWhenListingAndNoModelsAreAccessedAndPermissionIsNotGranted()
    {
        $ids = '1,2,3,4,5,6,7,8,9,10,11';
        $query = 'artists/' . $ids;
        $response = $this->json('DELETE', $query);
        $response->assertStatus(204);
        $json = $this->decodeResponse($response);
        $this->assertNull($json);
        
        $response = $this->asUser(3)->json('GET', 'artists');
        $json = $this->decodeResponse($response);
        $this->assertForbidden($response);

        $response = $this->asUser(2)->json('GET', 'artists');
        $response->assertStatus(200);
        $json = $this->decodeResponse($response);
        $this->assertCount(0, $json);
    }
}

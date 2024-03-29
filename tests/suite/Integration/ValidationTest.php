<?php
use ROTGP\RestEasy\Test\IntegrationTestCase;

class ValidationTest extends IntegrationTestCase
{
    public function testBasicCreateValidation()
    {
        $query = 'albums';
        $response = $this->asUser(1)->json('POST', $query, [
            'name' => 'Diamonds',
            'artist_id' => 1,
            'genre_id' => 1,
            'release_date' => '10-11-2020',
            'price' => 9.99,
            ]
        );
        $response->assertStatus(201);
    }

    public function testArtistExistsValidation()
    {
        $query = 'albums';
        $response = $this->asUser(1)->json('POST', $query, [
            'name' => 'Diamonds',
            'artist_id' => 1000,
            'genre_id' => 1,
            'release_date' => '10-11-2020',
            'price' => 9.99,
            ]
        );
        $response->assertStatus(400);
        $json = $this->decodeResponse($response);

        $this->assertArrayHasKey('http_status_code', $json);
        $this->assertArrayHasKey('http_status_message', $json);
        $this->assertArrayHasKey('validation_errors', $json);

        $this->assertEquals(400, $json['http_status_code']);
        $this->assertEquals('Bad Request', $json['http_status_message']);
        $this->assertCount(1, $json['validation_errors']);
        $this->assertCount(1, $json['validation_errors']['artist_id']);
        $this->assertEquals(
            'The selected artist id is invalid.',
            $json['validation_errors']['artist_id'][0]
        );
    }

    public function testUniqueNameValidation()
    {
        $query = 'albums';
        $response = $this->asUser(1)->json('POST', $query, [
            'name' => 'Harum similique eum doloribus saepe doloremque unde.',
            'artist_id' => 1,
            'genre_id' => 1,
            'release_date' => '10-11-2020',
            'price' => 9.99,
            ]
        );
        $response->assertStatus(400);
        $json = $this->decodeResponse($response);

        $this->assertArrayHasKey('http_status_code', $json);
        $this->assertArrayHasKey('http_status_message', $json);
        $this->assertArrayHasKey('validation_errors', $json);

        $this->assertEquals(400, $json['http_status_code']);
        $this->assertEquals('Bad Request', $json['http_status_message']);
        $this->assertCount(1, $json['validation_errors']);
        $this->assertCount(1, $json['validation_errors']['name']);
        $this->assertEquals(
            'The name has already been taken.',
            $json['validation_errors']['name'][0]
        );
    }

    public function testCustomValidation()
    {
        $query = 'albums';
        $response = $this->asUser(1)->json('POST', $query, [
            'name' => 'rocky goes to hollywood',
            'artist_id' => 1,
            'genre_id' => 1,
            'release_date' => '10-11-2020',
            'price' => 9.99,
            ]
        );
        $response->assertStatus(400);
        $json = $this->decodeResponse($response);

        $this->assertArrayHasKey('http_status_code', $json);
        $this->assertArrayHasKey('http_status_message', $json);
        $this->assertArrayHasKey('validation_errors', $json);

        $this->assertEquals(400, $json['http_status_code']);
        $this->assertEquals('Bad Request', $json['http_status_message']);
        $this->assertCount(1, $json['validation_errors']);
        $this->assertCount(1, $json['validation_errors']['name']);
        $this->assertEquals(
            'The album name must not contain the name of the genre.',
            $json['validation_errors']['name'][0]
        );
    }

    public function testModelValidation()
    {
        $query = 'albums';
        $response = $this->asUser(1)->json('POST', $query, [
            'name' => 'Diamonds',
            'artist_id' => 1,
            'genre_id' => 1,
            'release_date' => '10-11-2020',
            'price' => 9.99,
            ]
        )->assertStatus(201);

        $response = $this->json('POST', $query, [
            'name' => 'Diamonds and pearls',
            'artist_id' => 1,
            'genre_id' => 1,
            'release_date' => '10-11-2020',
            'price' => 9.99,
            ]
        )->assertStatus(201);

        $response = $this->json('POST', $query, [
            'name' => 'Album #6',
            'artist_id' => 1,
            'genre_id' => 2,
            'release_date' => '10-11-2020',
            'price' => 9.99,
            ]
        )->assertStatus(400);

        $json = $this->decodeResponse($response);

        $this->assertArrayHasKey('http_status_code', $json);
        $this->assertArrayHasKey('http_status_message', $json);
        $this->assertArrayHasKey('validation_errors', $json);

        $this->assertEquals(400, $json['http_status_code']);
        $this->assertEquals('Bad Request', $json['http_status_message']);
        $this->assertCount(1, $json['validation_errors']);
        $this->assertCount(2, $json['validation_errors']['model']);
        $this->assertEquals(
            'An artist may only have up to 5 albums.',
            $json['validation_errors']['model'][0]
        );
        $this->assertEquals(
            'An artist may not have albums belonging to more than 4 genres.',
            $json['validation_errors']['model'][1]
        );
    }

    public function testUpdateValidation()
    {
        $id = 4;
        $query = 'albums/' . $id;
        $response = $this->asUser(1)->get($query);
        $response->assertStatus(200);
        
        $originalJson = $this->decodeResponse($response);
        $json = $originalJson;
        $json['name'] = 'Harum similique eum doloribus saepe doloremque unde.';
        $response = $this->asUser(1)->json('PUT', $query, $json)
            ->assertStatus(400);

        $json = $this->decodeResponse($response);

        $this->assertArrayHasKey('http_status_code', $json);
        $this->assertArrayHasKey('http_status_message', $json);
        $this->assertArrayHasKey('validation_errors', $json);

        $this->assertEquals(400, $json['http_status_code']);
        $this->assertEquals('Bad Request', $json['http_status_message']);
        $this->assertCount(1, $json['validation_errors']);
        $this->assertCount(1, $json['validation_errors']['name']);
        $this->assertEquals(
            'The name has already been taken.',
            $json['validation_errors']['name'][0]
        );

        // artist_id exists id won't fire because the field is immutable
        $json = $originalJson;
        $json['artist_id'] = 1000;
        $json['name'] = 'foo';
        $json['purchases'] = 12345;
        $response = $this->asUser(1)->json('PUT', $query, $json)
            ->assertStatus(200);

        $json = $this->decodeResponse($response);
        $this->assertEquals('foo', $json['name']);
        $this->assertEquals(2, $json['artist_id']);
        $this->assertEquals(12345, $json['purchases']);
    }
}

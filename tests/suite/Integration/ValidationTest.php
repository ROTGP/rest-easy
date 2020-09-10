<?php
use ROTGP\RestEasy\Test\IntegrationTestCase;

class ValidationTest extends IntegrationTestCase
{
    public function testBasicCreateValidation()
    {
        $query = 'albums';
        $response = $this->json('POST', $query, [
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
        $response = $this->json('POST', $query, [
            'name' => 'Diamonds',
            'artist_id' => 1000,
            'genre_id' => 1,
            'release_date' => '10-11-2020',
            'price' => 9.99,
            ]
        );
        $response->assertStatus(400);
        $json = $response->decodeResponseJson();

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
        $response = $this->json('POST', $query, [
            'name' => 'Harum similique eum doloribus saepe doloremque unde.',
            'artist_id' => 1,
            'genre_id' => 1,
            'release_date' => '10-11-2020',
            'price' => 9.99,
            ]
        );
        $response->assertStatus(400);
        $json = $response->decodeResponseJson();

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
        $response = $this->json('POST', $query, [
            'name' => 'rocky goes to hollywood',
            'artist_id' => 1,
            'genre_id' => 1,
            'release_date' => '10-11-2020',
            'price' => 9.99,
            ]
        );
        $response->assertStatus(400);
        $json = $response->decodeResponseJson();

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
        $response = $this->json('POST', $query, [
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

        $json = $response->decodeResponseJson();

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
}

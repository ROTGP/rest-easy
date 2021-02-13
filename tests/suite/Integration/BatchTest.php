<?php
use ROTGP\RestEasy\Test\IntegrationTestCase;

class BatchTest extends IntegrationTestCase
{    
    public function testBatchGet()
    {
        $ids = '33,2';
        $query = 'songs/' . $ids;
        $response = $this->get($query);
        $json = $this->decodeResponse($response);
        $this->assertIndexedArray($json);
        $this->assertCount(2, $json);
        $this->assertEquals(33, $json[0]['id']);
        $this->assertEquals(2, $json[1]['id']);
    }

    public function testBatchUpdate()
    {
        $id = '5,8,10';
        $query = 'artists/' . $id;
        $response = $this->json('PUT', $query, 
            [
                [
                    'id' => 5,
                    'biography' => 'bio1',
                    'record_label_id' => 1
                ],
                [
                    'id' => 8,
                    'biography' => 'bio2',
                    'record_label_id' => 1
                ],
                [
                    'id' => 10,
                    'biography' => 'bio3',
                    'record_label_id' => 1
                ]
            ]
        );
        $response->assertStatus(200);
        $json = $this->decodeResponse($response);

        $this->assertIndexedArray($json);
        $this->assertCount(3, $json);
        $this->assertEquals(5, $json[0]['id']);
        $this->assertEquals(8, $json[1]['id']);
        $this->assertEquals(10, $json[2]['id']);
        $this->assertEquals('bio1', $json[0]['biography']);
        $this->assertEquals('bio2', $json[1]['biography']);
        $this->assertEquals('bio3', $json[2]['biography']);
    }

    public function testBatchUpdateWithSingleElement()
    {
        $id = '5,8,10';
        $query = 'artists/' . $id;
        $response = $this->json('PUT', $query, 
            [
                [
                    'id' => 5,
                    'biography' => 'bio1',
                    'record_label_id' => 1
                ]
            ]
        );
        $response->assertStatus(200);
        $json = $this->decodeResponse($response);

        $this->assertIndexedArray($json);
        $this->assertCount(1, $json);
        $this->assertEquals(5, $json[0]['id']);
        $this->assertEquals('bio1', $json[0]['biography']);
    }

    public function testBatchCreate()
    {
        $query = 'artists?with=record_label';
        $response = $this->asUser(1)->json('POST', $query, [
            [
                'name' => 'fooName1',
                'biography' => 'bio1',
                'record_label_id' => 3
            ],
            [
                'name' => 'fooName2',
                'biography' => 'bio2',
                'record_label_id' => 2
            ],
            [
                'name' => 'fooName3',
                'biography' => 'bio3',
                'record_label_id' => 1
            ]
        ]);
        $response->assertStatus(201);
        $json = $this->decodeResponse($response);
        $this->assertIndexedArray($json);
        $this->assertCount(3, $json);
        $this->assertEquals(12, $json[0]['id']);
        $this->assertEquals(13, $json[1]['id']);
        $this->assertEquals(14, $json[2]['id']);
        $this->assertEquals('bio1', $json[0]['biography']);
        $this->assertEquals('bio2', $json[1]['biography']);
        $this->assertEquals('bio3', $json[2]['biography']);
    }

    public function testBatchCreateWithSingleElement()
    {
        $query = 'artists?with=record_label';
        $response = $this->asUser(1)->json('POST', $query, [
            [
                'name' => 'fooName1',
                'biography' => 'bio1',
                'record_label_id' => 3
            ]
        ]);
        $response->assertStatus(201);
        $json = $this->decodeResponse($response);
        $this->assertIndexedArray($json);
        $this->assertCount(1, $json);
        $this->assertEquals(12, $json[0]['id']);
        $this->assertEquals('bio1', $json[0]['biography']);
    }

    public function testBatchDelete()
    {
        $ids = '1,2,3,4,5,6,7,8,9,10,11';
        $idsArr = array_map('intval', explode(',', $ids));
        $query = 'artists/' . $ids;
        $response = $this->get($query);
        $json = $this->decodeResponse($response);
        
        $this->assertEquals(
            explode(',', $ids),
            array_column($json, 'id')
        );

        $idsToDelete = '2,5,9';
        $idsToDeleteArr = array_map('intval', explode(',', $idsToDelete));
        $response = $this->json('DELETE', 'artists/' . $idsToDelete)
           ->assertStatus(204);
        $json = $this->decodeResponse($response);
        $this->assertNull($json);

        $response = $this->get($query);
        $json = $this->decodeResponse($response);
        
        $response
            ->assertJsonStructure([
                'http_status_code',
                'http_status_message',
                'resource_id'
            ])
            ->assertJsonFragment([
                'http_status_code' => 404,
                'http_status_message' => 'Not Found',
                'resource_id' => 2
            ])
            ->assertStatus(404);

        $response = $this->get('artists');
        $json = $this->decodeResponse($response);
        $this->assertEquals(
            array_column($json, 'id'),
            array_values(array_diff($idsArr, $idsToDeleteArr))
        );
    }

    public function testBatchCreateMany()
    {
        $query = 'artists?with=record_label';
        $payload = [];
        for ($i = 0; $i < 100; $i++) {
            $payload[] = [
                'name' => 'fooName' . $i,
                'biography' => 'bio' . $i,
                'record_label_id' => 1
            ];
        }
        $payload[99]['record_label_id'] = 999;
        $response = $this->asUser(1)->json('POST', $query, $payload);
        $response->assertStatus(400);

        $payload[99]['record_label_id'] = 2;
        $response = $this->asUser(1)->json('POST', $query, $payload);
        $response->assertStatus(201);
        $json = $this->decodeResponse($response);
        $this->assertIndexedArray($json);
        $this->assertCount(100, $json);
        $this->assertEquals(111, $json[99]['id']);
        $this->assertEquals('bio99', $json[99]['biography']);
        $this->assertEquals('island_def_jam', $json[99]['record_label']['name']);
    }

    public function testBatchCreateWithErrorAndTheTransactionRollsBack()
    {
        $query = 'artists?with=record_label';
        $response = $this->asUser(1)->json('POST', $query, [
            [
                'name' => 'fooName1',
                'biography' => 'bio1',
                'record_label_id' => 3
            ],
            [
                'name' => 'fooName2',
                'biography' => 'bio2',
                'record_label_id' => 200 // trigger validation error with bad id
            ],
            [
                'name' => 'fooName3',
                'biography' => 'bio3',
                'record_label_id' => 1
            ]
        ]);
        $response->assertStatus(400);
        $json = $this->decodeResponse($response);

        $id = 12;
        $query = 'artists/' . $id;
        $response = $this->get($query)
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
        
        $query = 'artists?with=record_label';
        $response = $this->asUser(1)->json('POST', $query, [
            [
                'name' => 'fooName1',
                'biography' => 'bio1',
                'record_label_id' => 3
            ]]);
        $json = $this->decodeResponse($response);

        $this->assertCount(1, $json);
        $this->assertEquals(12, $json[0]['id']);
        $this->assertEquals('bio1', $json[0]['biography']);

        $response = $this->asUser(1)->json('POST', $query, [
            'name' => 'fooName2',
            'biography' => 'bio2',
            'record_label_id' => 3
        ]);
        $json = $this->decodeResponse($response);

        $this->assertEquals(13, $json['id']);
        $this->assertEquals('bio2', $json['biography']);
    }

    public function testBatchCreateWithErrorReturnsTmpKey()
    {
        $query = 'artists?with=record_label';
        $response = $this->asUser(1)->json('POST', $query, [
            [
                'name' => 'fooName1',
                'biography' => 'bio1',
                'record_label_id' => 3,
                'tmp_key' => 'foo'
            ],
            [
                'name' => 'fooName2',
                'biography' => 'bio1',
                'record_label_id' => 200, // trigger validation error with bad id
                'tmp_key' => 'baz'
            ],
            [
                'name' => 'fooName3',
                'biography' => 'bio3',
                'record_label_id' => 1,
                'tmp_key' => 'bar'
            ]
        ]);
        $json = $this->decodeResponse($response);
        $errors = $json['validation_errors'];

        $this->assertAssociativeArray($errors);
        $this->assertCount(3, $errors);
        $this->assertArrayHasKey('foo', $errors);
        $this->assertArrayHasKey('baz', $errors);
        $this->assertArrayHasKey('bar', $errors);

        $this->assertEmpty($errors['foo']);
        $this->assertEmpty($errors['bar']);

        $this->assertArrayHasKey('biography', $errors['baz']);
        $this->assertCount(2, $errors['baz']);
        $this->assertCount(1, $errors['baz']['biography']);
        $this->assertEquals('The biography has already been taken.', $errors['baz']['biography'][0]);
        $this->assertCount(1, $errors['baz']['record_label_id']);
        $this->assertEquals('The selected record label id is invalid.', $errors['baz']['record_label_id'][0]);
    }

    public function testBatchCreateWithErrorReturnsNoTmpKeyWhenUseBatchKeysIsFalse()
    {
        $query = 'artists?with=record_label';
        $response = $this->asUser(2)->json('POST', $query, [
            [
                'name' => 'fooName1',
                'biography' => 'bio1',
                'record_label_id' => 3,
                'tmp_key' => 'foo'
            ],
            [
                'name' => 'fooName2',
                'biography' => 'bio1',
                'record_label_id' => 200, // trigger validation error with bad id
                'tmp_key' => 'baz'
            ],
            [
                'name' => 'fooName3',
                'biography' => 'bio3',
                'record_label_id' => 1,
                'tmp_key' => 'bar'
            ]
        ]);
        $json = $this->decodeResponse($response);
        $errors = $json['validation_errors'];
        
        
        $this->assertIndexedArray($errors);
        $this->assertCount(3, $errors);

        $this->assertEmpty($errors[0]);
        $this->assertEmpty($errors[2]);

        $this->assertArrayHasKey('biography', $errors[1]);
        $this->assertCount(2, $errors[1]);
        $this->assertCount(1, $errors[1]['biography']);
        $this->assertEquals('The biography has already been taken.', $errors[1]['biography'][0]);
        $this->assertCount(1, $errors[1]['record_label_id']);
        $this->assertEquals('The selected record label id is invalid.', $errors[1]['record_label_id'][0]);
    }

    public function testBatchUpdateWithErrorReturnsIdAsKey()
    {
        $query = 'artists?with=record_label';
        $id = '5,8,10';
        $query = 'artists/' . $id;
        $response = $this->asUser(1)->json('PUT', $query, 
            [
                [
                    'id' => 5,
                    'biography' => 'bio1',
                    'record_label_id' => 12
                ],
                [
                    'id' => 8,
                    'biography' => 'bio2',
                    'record_label_id' => 100
                ],
                [
                    'id' => 10,
                    'biography' => 'bio3',
                    'record_label_id' => 1
                ]
            ]
        );
        $json = $this->decodeResponse($response);
        $errors = $json['validation_errors'];

        $this->assertAssociativeArray($errors);
        $this->assertCount(3, $errors);
        $this->assertArrayHasKey('5', $errors);
        $this->assertArrayHasKey('8', $errors);
        $this->assertArrayHasKey('10', $errors);

        $this->assertEmpty($errors['10']);
        
        $this->assertArrayHasKey('record_label_id', $errors['5']);
        $this->assertCount(1, $errors['5']['record_label_id']);
        $this->assertEquals('The selected record label id is invalid.', $errors['5']['record_label_id'][0]);
    }

    public function testBatchUpdateWithErrorReturnsNoIdAsKeyWhenUseBatchKeysIsFalse()
    {
        $query = 'artists?with=record_label';
        $id = '5,8,10';
        $query = 'artists/' . $id;
        $response = $this->asUser(2)->json('PUT', $query, 
            [
                [
                    'id' => 5,
                    'biography' => 'bio1',
                    'record_label_id' => 12
                ],
                [
                    'id' => 8,
                    'biography' => 'bio2',
                    'record_label_id' => 100
                ],
                [
                    'id' => 10,
                    'biography' => 'bio3',
                    'record_label_id' => 1
                ]
            ]
        );

        $json = $this->decodeResponse($response);
        $errors = $json['validation_errors'];
        
        $this->assertIndexedArray($errors);
        $this->assertCount(3, $errors);
        $this->assertArrayNotHasKey('5', $errors);
        $this->assertArrayNotHasKey('8', $errors);
        $this->assertArrayNotHasKey('10', $errors);

        $this->assertEmpty($errors[2]);
        
        $this->assertArrayHasKey('record_label_id', $errors[0]);
        $this->assertCount(1, $errors[0]['record_label_id']);
        $this->assertEquals('The selected record label id is invalid.', $errors[0]['record_label_id'][0]);
    }
}

<?php
use ROTGP\RestEasy\Test\IntegrationTestCase;
use ROTGP\RestEasy\Test\Models\User;
use Carbon\Carbon;

class GroupByTest extends IntegrationTestCase
{
    public function testWithoutGroupBy()
    {
        $query = 'songs';
        $response = $this->get($query);
        $json = $this->decodeResponse($response);
        $this->assertCount(136, $json);
        $this->assertEquals(
            range(1, 28),
            array_values(array_unique(array_column($json, 'album_id')))
        );
    }

    public function testSimpleGroupBy()
    {
        $query = 'songs?group_by=album_id';
        $response = $this->get($query);
        $json = $this->decodeResponse($response);
        $this->assertCount(28, $json);
        $this->assertEquals(range(1, 28), array_column($json, 'album_id'));
    }

    public function testMultipleGroupBy()
    {
        $query = 'songs?group_by=length_seconds&count=length_seconds&order_by=length_seconds,asc';
        $response = $this->get($query);
        $json = $this->decodeResponse($response);
        $this->assertCount(110, $json);
        $this->assertArrayHasKey('length_seconds', $json[0]);
        $this->assertEquals(4, $json[0]['length_seconds']);
        $this->assertArrayHasKey('length_seconds_count', $json[0]);
        $this->assertEquals(1, $json[0]['length_seconds_count']);

        $response = $this->json('PUT', 'songs/52', ['length_seconds' => 10]);
        $json = $this->decodeResponse($response);
        
        $response = $this->get($query);
        $json = $this->decodeResponse($response);
        $this->assertCount(109, $json);
        $this->assertArrayHasKey('length_seconds', $json[0]);
        $this->assertEquals(10, $json[0]['length_seconds']);
        $this->assertArrayHasKey('length_seconds_count', $json[0]);
        $this->assertEquals(2, $json[0]['length_seconds_count']);
    }

    public function testSimpleGroupByWithOrderBy()
    {
        $query = 'songs?group_by=album_id&order_by=album_id,desc';
        $response = $this->get($query);
        $json = $this->decodeResponse($response);
        $this->assertCount(28, $json);
        $this->assertEquals(range(28,1), array_column($json, 'album_id'));
    }
}

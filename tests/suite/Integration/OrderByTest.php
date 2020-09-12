<?php
use ROTGP\RestEasy\Test\IntegrationTestCase;
use ROTGP\RestEasy\Test\Models\User;

class OrderByTest extends IntegrationTestCase
{
    public function testWithoutOrderBy()
    {
        $query = 'songs';
        $response = $this->get($query);
        $json = $response->decodeResponseJson();
        $this->assertCount(136, $json);
        $this->assertEquals(
            range(1, 28),
            array_values(array_unique(array_column($json, 'album_id')))
        );
    }

    public function testSimpleOrderBy()
    {
        $query = 'songs?order_by=album_id';
        $response = $this->get($query);
        $json = $response->decodeResponseJson();
        $this->assertCount(136, $json);
        $this->assertEquals(
            range(1, 28),
            array_values(array_unique(array_column($json, 'album_id')))
        );
    }

    public function testSimpleOrderByAsc()
    {
        $query = 'songs?order_by=album_id,asc';
        $response = $this->get($query);
        $json = $response->decodeResponseJson();
        $this->assertCount(136, $json);
        $this->assertEquals(
            range(1, 28),
            array_values(array_unique(array_column($json, 'album_id')))
        );
    }

    public function testSimpleOrderByDesc()
    {
        $query = 'songs?order_by=album_id,desc';
        $response = $this->get($query);
        $json = $response->decodeResponseJson();
        $this->assertCount(136, $json);
        $this->assertEquals(
            range(28, 1),
            array_values(array_unique(array_column($json, 'album_id')))
        );
    }

    public function testMultipleOrderBy()
    {
        $query = 'songs?order_by=album_id,length_seconds';
        $response = $this->get($query);
        $json = $response->decodeResponseJson();
        $albumId1 = array_slice($json, 0, 4);
        $this->assertCount(4, $albumId1);
        $this->assertEquals(2, $albumId1[0]['id']);
        $this->assertEquals(3, $albumId1[1]['id']);
        $this->assertEquals(1, $albumId1[2]['id']);
        $this->assertEquals(4, $albumId1[3]['id']);

        $this->assertEquals(1, $albumId1[0]['album_id']);
        $this->assertEquals(1, $albumId1[1]['album_id']);
        $this->assertEquals(1, $albumId1[2]['album_id']);
        $this->assertEquals(1, $albumId1[3]['album_id']);

        $this->assertEquals(189, $albumId1[0]['length_seconds']);
        $this->assertEquals(194, $albumId1[1]['length_seconds']);
        $this->assertEquals(202, $albumId1[2]['length_seconds']);
        $this->assertEquals(205, $albumId1[3]['length_seconds']);        
    }

    public function testMultipleOrderByDesc()
    {
        $query = 'songs?order_by=album_id,length_seconds,desc';
        $response = $this->get($query);
        $json = $response->decodeResponseJson();
        $albumId28 = array_slice($json, 0, 7);
        $this->assertCount(7, $albumId28);
        $this->assertEquals(135, $albumId28[0]['id']);
        $this->assertEquals(136, $albumId28[1]['id']);
        $this->assertEquals(132, $albumId28[2]['id']);
        $this->assertEquals(130, $albumId28[3]['id']);
        $this->assertEquals(131, $albumId28[4]['id']);
        $this->assertEquals(133, $albumId28[5]['id']);
        $this->assertEquals(134, $albumId28[6]['id']);

        $this->assertEquals(28, $albumId28[0]['album_id']);
        $this->assertEquals(28, $albumId28[1]['album_id']);
        $this->assertEquals(28, $albumId28[2]['album_id']);
        $this->assertEquals(28, $albumId28[3]['album_id']);
        $this->assertEquals(28, $albumId28[4]['album_id']);
        $this->assertEquals(28, $albumId28[5]['album_id']);
        $this->assertEquals(28, $albumId28[6]['album_id']);

        $this->assertEquals(288, $albumId28[0]['length_seconds']);
        $this->assertEquals(268, $albumId28[1]['length_seconds']);
        $this->assertEquals(179, $albumId28[2]['length_seconds']);
        $this->assertEquals(169, $albumId28[3]['length_seconds']);
        $this->assertEquals(154, $albumId28[4]['length_seconds']);
        $this->assertEquals(90, $albumId28[5]['length_seconds']);
        $this->assertEquals(66, $albumId28[6]['length_seconds']);        
    }
}

<?php
use ROTGP\RestEasy\Test\IntegrationTestCase;
use ROTGP\RestEasy\Test\Models\User;
use Carbon\Carbon;

class AggregationTest extends IntegrationTestCase
{
    public function testWithoutAggregation()
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

    public function testAggregationAverage()
    {
        $query = 'songs?avg=length_seconds';
        $response = $this->get($query);
        $json = $response->decodeResponseJson();
        $this->assertCount(1, $json);
        $this->assertEquals(160.941176470588, $json[0]['length_seconds_avg']);
    }

    public function testAggregationAverageWithGroupBy()
    {
        $query = 'songs?avg=length_seconds&group_by=album_id';
        $response = $this->get($query);
        $json = $response->decodeResponseJson();
        $this->assertCount(28, $json);
        $this->assertEquals(1, $json[0]['album_id']);
        $this->assertEquals(197.5, $json[0]['length_seconds_avg']);
        $this->assertEquals(28, $json[27]['album_id']);
        $this->assertEquals(173.428571428571, $json[27]['length_seconds_avg']);
    }

    public function testAggregationMax()
    {
        $query = 'songs?max=length_seconds';
        $response = $this->get($query);
        $json = $response->decodeResponseJson();
        $this->assertCount(1, $json);
        $this->assertEquals(298, $json[0]['length_seconds_max']);
    }

    public function testAggregationMaxWithGroupBy()
    {
        $query = 'songs?max=length_seconds&group_by=album_id';
        $response = $this->get($query);
        $json = $response->decodeResponseJson();
        $this->assertCount(28, $json);
        $this->assertEquals(1, $json[0]['album_id']);
        $this->assertEquals(205, $json[0]['length_seconds_max']);
        $this->assertEquals(28, $json[27]['album_id']);
        $this->assertEquals(288, $json[27]['length_seconds_max']);
    }

    public function testAggregationMin()
    {
        $query = 'songs?min=length_seconds';
        $response = $this->get($query);
        $json = $response->decodeResponseJson();
        $this->assertCount(1, $json);
        $this->assertEquals(4, $json[0]['length_seconds_min']);
    }

    public function testAggregationMinWithGroupBy()
    {
        $query = 'songs?min=length_seconds&group_by=album_id';
        $response = $this->get($query);
        $json = $response->decodeResponseJson();
        $this->assertCount(28, $json);
        $this->assertEquals(1, $json[0]['album_id']);
        $this->assertEquals(189, $json[0]['length_seconds_min']);
        $this->assertEquals(28, $json[27]['album_id']);
        $this->assertEquals(66, $json[27]['length_seconds_min']);
    }

    public function testAggregationCount()
    {
        $query = 'songs?count=id';
        $response = $this->get($query);
        $json = $response->decodeResponseJson();
        $this->assertCount(1, $json);
        $this->assertEquals(136, $json[0]['id_count']);
    }

    public function testAggregationCountWithGroupBy()
    {
        $query = 'songs?count=id&group_by=album_id';
        $response = $this->get($query);
        $json = $response->decodeResponseJson();
        $this->assertCount(28, $json);
        $this->assertEquals(1, $json[0]['album_id']);
        $this->assertEquals(4, $json[0]['id_count']);
        $this->assertEquals(28, $json[27]['album_id']);
        $this->assertEquals(7, $json[27]['id_count']);
    }

    public function testAggregationSum()
    {
        $query = 'songs?sum=length_seconds';
        $response = $this->get($query);
        $json = $response->decodeResponseJson();
        $this->assertCount(1, $json);
        $this->assertEquals(21888, $json[0]['length_seconds_sum']);
    }

    public function testAggregationSumWithGroupBy()
    {
        $query = 'songs?sum=length_seconds&group_by=album_id';
        $response = $this->get($query);
        $json = $response->decodeResponseJson();
        $this->assertCount(28, $json);
        $this->assertEquals(1, $json[0]['album_id']);
        $this->assertEquals(790, $json[0]['length_seconds_sum']);
        $this->assertEquals(28, $json[27]['album_id']);
        $this->assertEquals(1214, $json[27]['length_seconds_sum']);
    }

    public function testAggregationSumWithGroupByAndOrderByDesc()
    {
        $query = 'songs?sum=length_seconds&group_by=album_id&order_by=length_seconds_sum,desc';
        $response = $this->get($query);
        $json = $response->decodeResponseJson();
        $this->assertCount(28, $json);
        $this->assertEquals(23, $json[0]['album_id']);
        $this->assertEquals(1299, $json[0]['length_seconds_sum']);
        $this->assertEquals(13, $json[27]['album_id']);
        $this->assertEquals(127, $json[27]['length_seconds_sum']);
    }

    public function testAggregationSumWithGroupByAndScopeAndOrderByDesc()
    {
        $query = 'songs?sum=length_seconds&group_by=album_id&order_by=length_seconds_sum,desc&longer_than=200';
        $response = $this->get($query);
        $json = $response->decodeResponseJson();
        $this->assertCount(24, $json);
        $this->assertEquals(10, $json[0]['album_id']);
        $this->assertEquals(1090, $json[0]['length_seconds_sum']);
        $this->assertEquals(12, $json[23]['album_id']);
        $this->assertEquals(215, $json[23]['length_seconds_sum']);
    }

    public function testAggregationSumCountMinMaxAvg()
    {
        $query = 'songs?sum=id&min=length_seconds&max=length_seconds&avg=length_seconds&count=album_id';
        $response = $this->get($query);
        $json = $response->decodeResponseJson();
        $this->assertEquals(136, $json[0]['album_id_count']);
        $this->assertEquals(9316, $json[0]['id_sum']);
        $this->assertEquals(160.941176470588, $json[0]['length_seconds_avg']);
        $this->assertEquals(4, $json[0]['length_seconds_min']);
        $this->assertEquals(298, $json[0]['length_seconds_max']);
    }
}

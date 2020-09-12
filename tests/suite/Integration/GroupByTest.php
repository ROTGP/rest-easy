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
        $json = $response->decodeResponseJson();
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
        $json = $response->decodeResponseJson();
        $this->assertCount(28, $json);
        $this->assertEquals(range(1, 28), array_column($json, 'album_id'));
    }

    public function testMultipleGroupBy()
    {
        $query = 'songs?group_by=updated_at';
        $response = $this->get($query);
        $json = $response->decodeResponseJson();
        $this->assertCount(1, $json);
        $this->assertArrayHasKey('updated_at', $json[0]);
        $updatedAt1 = Carbon::parse($json[0]['updated_at']);
        sleep(1);
        $response = $this->json('PUT', 'songs/10', ['name' => 'foo']);
        $json = $response->decodeResponseJson();
        $updatedAt2 = Carbon::parse($json['updated_at']);
        $this->assertEquals(1, $updatedAt1->diffInSeconds($updatedAt2));

        $query = 'songs?group_by=album_id,updated_at';
        $response = $this->get($query);
        $json = $response->decodeResponseJson();
        $this->assertCount(29, $json);
        $this->assertArrayHasKey('updated_at', $json[2]);
        $this->assertArrayHasKey('updated_at', $json[3]);
        $this->assertEquals(
            1, 
            Carbon::parse($json[2]['updated_at'])
                ->diffInSeconds(
                    Carbon::parse($json[3]['updated_at']
                )
            )
        );
    }

    public function testSimpleGroupByWithOrderBy()
    {
        $query = 'songs?group_by=album_id&order_by=album_id,desc';
        $response = $this->get($query);
        $json = $response->decodeResponseJson();
        $this->assertCount(28, $json);
        $this->assertEquals(range(28,1), array_column($json, 'album_id'));
    }
}

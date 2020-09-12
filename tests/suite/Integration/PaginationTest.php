<?php
use ROTGP\RestEasy\Test\IntegrationTestCase;
use ROTGP\RestEasy\Test\Models\User;

class PaginationTest extends IntegrationTestCase
{
    public function testWithoutPagination()
    {
        $query = 'songs';
        $response = $this->get($query);
        $json = $response->decodeResponseJson();
        $this->assertCount(136, $json);
    }

    public function testWithSimplePagination()
    {
        $query = 'songs?page=1&page_size=10';
        $response = $this->get($query);
        $response->assertJsonStructure([
            'page',
            'page_size',
            'total_results',
            'total_pages',
            'data' => [
              '*' => [
                'id',
                'name',
                'album_id',
                'length_seconds',
                'created_at',
                'updated_at'
              ]
            ]
          ]);

        $json = $response->decodeResponseJson();
        
        $this->assertCount(10, $json['data']);
        $this->assertEquals(10, $json['page_size']);
        $this->assertEquals(136, $json['total_results']);
        $this->assertEquals(14, $json['total_pages']);
        $this->assertEquals(1, $json['data'][0]['id']);
        $this->assertEquals(10, $json['data'][9]['id']);
    }

    public function testWithSimplePagination2()
    {
        $query = 'songs?page_size=20&page=2';
        $response = $this->get($query);
        $json = $response->decodeResponseJson();
        $this->assertCount(20, $json['data']);
        $this->assertEquals(20, $json['page_size']);
        $this->assertEquals(136, $json['total_results']);
        $this->assertEquals(7, $json['total_pages']);
        $this->assertEquals(21, $json['data'][0]['id']);
        $this->assertEquals(40, $json['data'][19]['id']);
    }

    public function testWithSimplePagination3()
    {
        $query = 'songs?page_size=20&page=2';
        $response = $this->get($query);
        $json = $response->decodeResponseJson();
        $this->assertCount(20, $json['data']);
        $this->assertEquals(20, $json['page_size']);
        $this->assertEquals(136, $json['total_results']);
        $this->assertEquals(7, $json['total_pages']);
        $this->assertEquals(21, $json['data'][0]['id']);
        $this->assertEquals(40, $json['data'][19]['id']);
    }

    public function testWithPaginationExceedingMaximumPage()
    {
        $query = 'songs?page_size=10&page=200';
        $response = $this->get($query);
        $json = $response->decodeResponseJson();
        $this->assertCount(6, $json['data']);
        $this->assertEquals(10, $json['page_size']);
        $this->assertEquals(136, $json['total_results']);
        $this->assertEquals(14, $json['total_pages']);
        $this->assertEquals(131, $json['data'][0]['id']);
        $this->assertEquals(136, $json['data'][5]['id']);
    }
}

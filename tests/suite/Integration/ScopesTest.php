<?php
use ROTGP\RestEasy\Test\IntegrationTestCase;
use ROTGP\RestEasy\Test\Models\User;
use ROTGP\RestEasy\Test\Models\Artist;

class ScopesTest extends IntegrationTestCase
{
    public function testSimpleScope()
    {
        $query = 'artists?name_like=osco';
        $response = $this->get($query);
        $response->assertJsonCount(2);
        $json = $this->decodeResponse($response);
        $this->assertEquals('Dr. Halle Bosco PhD', $json[0]['name']);
        $this->assertEquals('Katelin Bosco', $json[1]['name']);
    }

    public function testMultipleScopes()
    {
        $recordLabelId = 4;
        $query = 'artists?name_like=osco&record_labels=' . $recordLabelId;
        $response = $this->get($query);
        $response->assertJsonCount(1);
        $json = $this->decodeResponse($response);
        $entity = $json[0];
        $this->assertEquals('Katelin Bosco', $entity['name']);
        $this->assertEquals($recordLabelId, $entity['record_label_id']);
    }
    
    public function testMultipleScopesWithRelationships()
    {
        $query = 'artists?name_like=osco&record_labels=4&with=record_label,users,albums';
        $response = $this->asUser(1)->get($query);
        $response->assertJsonCount(1);
        $json = $this->decodeResponse($response);
        $entity = $json[0];
        $this->assertEquals('Katelin Bosco', $entity['name']);
        $this->assertEquals(4, $entity['record_label_id']);
        $this->assertEquals('epic', $entity['record_label']['name']);
        $this->assertCount(3, $entity['users']);
        $this->assertCount(3, $entity['albums']);
    }

    public function testImplicitScope()
    {
        $query = 'songs?with=users';
        $response = $this->get($query);
        $response->assertJsonCount(136);
        $json = $this->decodeResponse($response);
        
        $userId = 1;
        $query = 'songs?with=users';
        $response = $this->asUser($userId)->get($query);
        $response->assertJsonCount(9);
        $json = $this->decodeResponse($response);
        for ($i = 0; $i < sizeof($json); $i++) {
            $this->assertArrayHasKey('users', $json[$i]);
            $this->assertContains($userId, array_column($json[$i]['users'], 'id'));
        }
    }
}

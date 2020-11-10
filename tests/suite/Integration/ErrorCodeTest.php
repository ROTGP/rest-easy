<?php
use ROTGP\RestEasy\Test\IntegrationTestCase;
use ROTGP\RestEasy\Test\Models\User;
use Carbon\Carbon;

use ROTGP\RestEasy\Test\ErrorCodes;

class ErrorCodeTest extends IntegrationTestCase
{
    // user 3 has no permissions for albums
    public function testCannotReadWithoutExplicitPermissionReturnsExpectedErrorCode()
    {
        $id = 1;
        $query = 'albums/' . $id;
        $response = $this->asUser(3)->json('GET', $query);
        $json = $this->decodeResponse($response);

        $this->assertArrayHasKey('http_status_code', $json);
        $this->assertArrayHasKey('http_status_message', $json);
        $this->assertArrayHasKey('error_code', $json);
        $this->assertArrayHasKey('error_message', $json);
        $this->assertEquals(403, $json['http_status_code']);
        $this->assertEquals('Forbidden', $json['http_status_message']);
        $this->assertEquals(1, $json['error_code']);
        $this->assertEquals('User not authorized to access album', $json['error_message']);
    }

    // @TODO test that when a message is returned from a perm method, that it is displayed
}

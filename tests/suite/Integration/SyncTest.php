<?php
use ROTGP\RestEasy\Test\IntegrationTestCase;

class SyncTest extends IntegrationTestCase
{
    public function testSimpleSync()
    {
        $userId = 1;
        $query = 'users/' . $userId . '?with=albums,songs';
        $response = $this->asUser(1)->get($query);
        $entity = $response->decodeResponseJson();
        $albumIds = array_column($entity['albums'], 'id');
        $songsIds = array_column($entity['songs'], 'id');
        $userIds = array_column(array_column($entity['albums'], 'pivot'), 'user_id');
        $this->assertEquals($userId, $entity['id']);
        $this->assertEquals([], array_diff($albumIds, [12, 15, 16, 20, 25]));
        $this->assertEquals([], array_diff($songsIds, [25, 30, 117, 74, 36, 115, 123, 35, 107]));
        $this->assertEquals($userIds, [1, 1, 1, 1, 1]);

        $albumsIdsToSync = [10, 15, 20];
        $query = 'users/' . $userId . '?with=albums,songs&sync_albums=' . implode(',', $albumsIdsToSync);
        $response = $this->asUser($userId)->get($query);
        $entity = $response->decodeResponseJson();
        $albumIds = array_column($entity['albums'], 'id');
        $userIds = array_column(array_column($entity['albums'], 'pivot'), 'user_id');
        $this->assertEquals($userId, $entity['id']);
        $this->assertEquals([], array_diff($albumIds, $albumsIdsToSync));
        $this->assertEquals($userIds, [1, 1, 1]);

        $albumsIdsToAttach = [12, 14];
        $songIdsToSync = [1, 2, 3, 4, 5];
        $expectedAlbumIds = array_merge($albumsIdsToSync, $albumsIdsToAttach);
        $query = 'users/' . $userId . '?with=albums,songs';
        $query .= '&attach_albums=' . implode(',', $albumsIdsToAttach);
        $query .= '&sync_songs=' . implode(',', $songIdsToSync);
        $response = $this->asUser($userId)->get($query);
        $entity = $response->decodeResponseJson();
        $albumIds = array_column($entity['albums'], 'id');
        $songsIds = array_column($entity['songs'], 'id');
        $userIds = array_column(array_column($entity['albums'], 'pivot'), 'user_id');
        $this->assertEquals($userId, $entity['id']);
        $this->assertEquals([], array_diff($albumIds, $expectedAlbumIds));
        $this->assertEquals([], array_diff($songsIds, $songIdsToSync));
        $this->assertEquals($userIds, [1, 1, 1, 1, 1]);

        $albumsIdsToDetach = [20, 14];
        $songsIdsToDetach = [2, 5];
        $expectedAlbumIds = array_diff($expectedAlbumIds, $albumsIdsToDetach);
        $query = 'users/' . $userId . '?with=albums,songs';
        $query .= '&detach_albums=' . implode(',', $albumsIdsToDetach);
        $query .= '&detach_songs=' . implode(',', $songsIdsToDetach);
        $response = $this->asUser($userId)->get($query);
        $entity = $response->decodeResponseJson();
        $albumIds = array_column($entity['albums'], 'id');
        $songsIds = array_column($entity['songs'], 'id');
        $userIds = array_column(array_column($entity['albums'], 'pivot'), 'user_id');
        $this->assertEquals($userId, $entity['id']);
        $this->assertEquals([], array_diff($albumIds, $expectedAlbumIds));
        $this->assertEquals([], array_diff($songsIds, [1, 3, 4]));
        $this->assertEquals($userIds, [1, 1, 1]);
    }
}

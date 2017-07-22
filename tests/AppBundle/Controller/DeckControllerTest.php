<?php

declare(strict_types=1);

namespace Tests\AppBundle\Controller;

use AppBundle\Controller\DeckLineageController;
use AppBundle\Controller\PrivateDeckController;
use AppBundle\Controller\PublicDeckController;
use Tests\AppBundle\Controller\BaseApiControllerTest;
use Tests\AppBundle\DeckDataTrait;

/**
 * Test class for all the API endpoints that read/write decks
 *
 * @package Tests\AppBundle\Controller
 * @author Alsciende <alsciende@icloud.com>
 */
class DeckControllerTest extends BaseApiControllerTest
{
    use DeckDataTrait;

    /**
     * @covers PrivateDeckController::postAction()
     */
    public function testPrivateDeckControllerPostAction ()
    {
        $client = $this->getAuthenticatedClient();
        $this->sendJsonRequest(
            $client,
            'POST',
            "/private-decks",
            $this->getDeckData()
        );
        $deck = $this->assertStandardGetOne($client);
        $this->assertEquals(
            'PHPUnit Test Deck',
            $deck['name']
        );
        $this->assertEquals(
            33,
            count($deck['cards'])
        );
        $this->assertEquals(
            '0.1',
            $deck['version']
        );
        $this->assertArrayHasKey(
            'id',
            $deck
        );
        return $deck;
    }

    /**
     * @covers  DeckCopyController::postAction()
     * @depends testPrivateDeckControllerPostAction
     */
    public function testDeckCopyControllerPostAction ($deck)
    {
        $client = $this->getAuthenticatedClient();

        $id = $deck['id'];

        $this->sendJsonRequest(
            $client,
            'POST',
            "/private-decks/$id/copy"
        );
        $response = $client->getResponse();
        $this->assertEquals(
            200,
            $response->getStatusCode()
        );
        $record = $this->assertStandardGetOne($client);
        $this->assertEquals(
            'PHPUnit Test Deck',
            $record['name']
        );
        $this->assertEquals(
            33,
            count($record['cards'])
        );
        $this->assertEquals(
            '0.1',
            $record['version']
        );
        $this->assertNotEquals(
            $record['id'],
            $deck['id']
        );
        $this->assertNotEquals(
            $record['lineage'],
            $deck['lineage']
        );
        $this->assertEquals(
            $record['genus'],
            $deck['genus']
        );
        return $record;
    }

    /**
     * @covers  DeckLineageController::postAction()
     * @depends testPrivateDeckControllerPostAction
     */
    public function testDeckLineageControllerPostAction ($deck)
    {
        $client = $this->getAuthenticatedClient();

        $id = $deck['id'];

        $this->sendJsonRequest(
            $client,
            'POST',
            "/private-decks/$id/lineage",
            $deck
        );
        $record = $this->assertStandardGetOne($client);
        $this->assertEquals(
            'PHPUnit Test Deck',
            $record['name']
        );
        $this->assertEquals(
            33,
            count($record['cards'])
        );
        $this->assertEquals(
            '0.2',
            $record['version']
        );
        $this->assertNotEquals(
            $record['id'],
            $deck['id']
        );
        $this->assertEquals(
            $record['lineage'],
            $deck['lineage']
        );
        $this->assertEquals(
            $record['genus'],
            $deck['genus']
        );
        return $record;
    }

    /**
     * @covers  DeckLineageController::listAction()
     * @depends testDeckLineageControllerPostAction
     */
    public function testDeckLineageControllerListAction ($deck)
    {
        $client = $this->getAuthenticatedClient();

        $id = $deck['id'];

        $this->sendJsonRequest(
            $client,
            'GET',
            "/private-decks/$id/lineage"
        );
        $response = $client->getResponse();
        $records = $this->assertStandardGetMany($client);
        $this->assertEquals(
            2,
            count($records)
        );
        return $records;
    }

    /**
     * @covers  DeckLineageController::deleteAction()
     * @depends testDeckLineageControllerListAction
     */
    public function testDeckLineageControllerDeleteAction ($decks)
    {
        $client = $this->getAuthenticatedClient();

        $id = $decks[0]['id'];

        $this->sendJsonRequest(
            $client,
            'DELETE',
            "/private-decks/$id/lineage"
        );
        $this->assertStandardGetNone($client);
    }

    /**
     * @covers  DeckPublishController::postAction()
     * @depends testDeckCopyControllerPostAction
     */
    public function testDeckPublishControllerPostAction ($deck)
    {
        $client = $this->getAuthenticatedClient();

        $id = $deck['id'];

        $this->sendJsonRequest(
            $client,
            'POST',
            "/private-decks/$id/publish", [
                'name' => 'Published name',
                'description' => 'Published description'
            ]
        );
        $record = $this->assertStandardGetOne($client);
        $this->assertEquals(
            '1.0',
            $record['version']
        );
        $this->assertEquals(
            'Published name',
            $record['name']
        );
        $this->assertEquals(
            'Published description',
            $record['description']
        );
        return $record;
    }

    /**
     * @covers  PublicDeckController::listAction()
     * @depends testDeckPublishControllerPostAction
     */
    public function testPublicDeckControllerListAction ($deck)
    {
        $client = $this->getAuthenticatedClient();

        $this->sendJsonRequest(
            $client,
            'GET',
            "/public-decks"
        );
        $record = $this->assertStandardGetMany($client);
    }

    /**
     * @covers  PublicDeckController::getAction()
     * @depends testDeckPublishControllerPostAction
     */
    public function testPublicDeckControllerGetAction ($deck)
    {
        $client = $this->getAuthenticatedClient();

        $id = $deck['id'];

        $this->sendJsonRequest(
            $client,
            'GET',
            "/public-decks/$id"
        );
        $record = $this->assertStandardGetOne($client);
        $this->assertEquals(
            $record['id'],
            $id
        );
    }

    /**
     * @covers  PublicDeckController::patchAction()
     * @depends testDeckPublishControllerPostAction
     */
    public function testPublicDeckControllerPatchAction ($deck)
    {
        $client = $this->getAuthenticatedClient();

        $id = $deck['id'];

        $this->sendJsonRequest(
            $client,
            'PATCH',
            "/public-decks/$id",
            [
                'name' => 'Updated Name',
                'description' => 'Updated description'
            ]
        );
        $record = $this->assertStandardGetOne($client);
        $this->assertEquals(
            'Updated Name',
            $record['name']
        );
        $this->assertEquals(
            'Updated description',
            $record['description']
        );
    }

    /**
     * @covers  DeckLikeController::postAction()
     * @depends testDeckPublishControllerPostAction
     */
    public function testDeckLikeControllerPostAction ($deck)
    {
        $client = $this->getAuthenticatedClient();

        $id = $deck['id'];

        $this->sendJsonRequest(
            $client,
            'POST',
            "/public-decks/$id/like"
        );
        $record = $this->assertStandardGetOne($client);
        $this->assertEquals(
            1,
            $record
        );
        return $deck;
    }

    /**
     * @covers  DeckLikeController::deleteAction()
     * @depends testDeckLikeControllerPostAction
     */
    public function testDeckLikeControllerDeleteAction ($deck)
    {
        $client = $this->getAuthenticatedClient();

        $id = $deck['id'];

        $this->sendJsonRequest(
            $client,
            'DELETE',
            "/public-decks/$id/like"
        );
        $this->assertStandardGetNone($client);
    }

    /**
     * @covers  DeckCommentController::postAction()
     * @depends testDeckPublishControllerPostAction
     */
    public function testDeckCommentControllerPostAction ($deck)
    {
        $client = $this->getAuthenticatedClient();

        $id = $deck['id'];

        $this->sendJsonRequest(
            $client,
            'POST',
            "/public-decks/$id/comments",
            [
                'text' => 'Test comment text'
            ]
        );
        $record = $this->assertStandardGetOne($client);
        $this->assertArrayHasKey(
            'id',
            $record
        );
        $this->assertEquals(
            'Test comment text',
            $record['text']
        );
        $this->assertArrayHasKey(
            'deck_id',
            $record
        );
        $this->assertArrayHasKey(
            'user_id',
            $record
        );
        return $record;
    }

    /**
     * @covers  DeckCommentController::listAction()
     * @depends testDeckCommentControllerPostAction
     */
    public function testDeckCommentControllerListAction ($comment)
    {
        $client = $this->getAuthenticatedClient();

        $id = $comment['deck_id'];

        $this->sendJsonRequest(
            $client,
            'GET',
            "/public-decks/$id/comments"
        );
        $records = $this->assertStandardGetMany($client);
        $this->assertEquals(
            1,
            count($records)
        );
        return $records;
    }

    /**
     * @covers  DeckCommentController::getAction()
     * @depends testDeckCommentControllerPostAction
     */
    public function testDeckCommentControllerGetAction ($comment)
    {
        $client = $this->getAuthenticatedClient();

        $deckId = $comment['deck_id'];
        $id = $comment['id'];

        $this->sendJsonRequest(
            $client,
            'GET',
            "/public-decks/$deckId/comments/$id"
        );
        $record = $this->assertStandardGetOne($client);
        $this->assertEquals(
            $id,
            $record['id']
        );
        $this->assertEquals(
            true,
            $record['visible']
        );
        return $record;
    }

    /**
     * @covers  DeckCommentController::patchAction()
     * @depends testDeckCommentControllerPostAction
     */
    public function testDeckCommentControllerPatchAction ($comment)
    {
        $client = $this->getAuthenticatedClient();

        $deckId = $comment['deck_id'];
        $id = $comment['id'];

        $this->sendJsonRequest(
            $client,
            'PATCH',
            "/public-decks/$deckId/comments/$id",
            [
                'text' => 'Updated text',
                'visible' => false
            ]
        );
        $record = $this->assertStandardGetOne($client);
        $this->assertEquals(
            'Updated text',
            $record['text']
        );
        $this->assertEquals(
            false,
            $record['visible']
        );
        $this->assertEquals(
            $comment['id'],
            $record['id']
        );
        $this->assertEquals(
            $comment['deck_id'],
            $record['deck_id']
        );
        $this->assertEquals(
            $comment['user_id'],
            $record['user_id']
        );
        return $record;
    }

    /**
     * @covers  PublicDeckController::deleteAction()
     * @depends testDeckPublishControllerPostAction
     */
    public function testPublicDeckControllerDeleteAction ($deck)
    {
        $client = $this->getAuthenticatedClient();

        $id = $deck['id'];

        $this->sendJsonRequest(
            $client,
            'DELETE',
            "/public-decks/$id"
        );
        $this->assertStandardGetNone($client);
    }
}
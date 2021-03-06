<?php

namespace AppBundle\Controller;

use AppBundle\Behavior\Service\GetRepositoryTrait;
use AppBundle\Entity\Deck;
use AppBundle\Entity\Strain;
use AppBundle\Form\Type\DeckSearchType;
use AppBundle\Form\Type\PublicDeckType;
use AppBundle\Repository\DeckRepository;
use AppBundle\Search\DeckSearch;
use AppBundle\Service\DeckManager;
use AppBundle\Service\DeckSearchService;
use Doctrine\ORM\EntityManagerInterface;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;
use Symfony\Component\HttpFoundation\Request;

/**
 * Description of PublicDeckController
 *
 * @Route("/decks", name="decks_")
 *
 * @author Alsciende <alsciende@icloud.com>
 */
class PublicDeckController extends AbstractApiController
{
    use GetRepositoryTrait;

    /**
     * Get all public decks
     *
     * @Route("", name="list")
     * @Method("GET")
     */
    public function listAction (Request $request, DeckSearchService $deckSearchService)
    {
        $this->setPublic($request);

        $search = new DeckSearch();
        $form = $this->createForm(DeckSearchType::class, $search);
        $form->submit($request->query->all(), false);

        if ($form->isSubmitted() && $form->isValid()) {
            $deckSearchService->search($search);

            return $this->success($search, [
                'Public',
                'User',
                'user' => [
                    'Default',
                ],
            ]);
        }

        return $this->failure('validation_error', $this->formatValidationErrors($form->getErrors(true)));
    }

    /**
     * Get a public deck
     *
     * @Route("/{id}", name="get")
     * @Method("GET")
     */
    public function getAction (Request $request, Deck $deck)
    {
        $this->setPublic($request);

        if (!$deck->isPublished()) {
            throw $this->createNotFoundException();
        }

        return $this->success($deck, [
            'Public',
            'Description',
            'Cards',
            'User',
            'user'     => [
                'Default',
            ],
            'Comments',
            'comments' => [
                'Default',
                'User',
            ],
        ]);
    }

    /**
     * Get all versions of a public deck
     *
     * @Route("/{id}/versions", name="versions")
     * @Method("GET")
     */
    public function getVersionsAction (Request $request, Deck $deck, EntityManagerInterface $entityManager)
    {
        $this->setPublic($request);

        if (!$deck->isPublished()) {
            throw $this->createNotFoundException();
        }

        /** @var DeckRepository $repository */
        $repository = $this->getRepository($entityManager, Deck::class);

        return $this->success(
            $repository->findAllPublicVersions($deck),
            [
                'Public',
                'Cards',
            ]
        );
    }

    /**
     * Update a public deck - only name and description can be updated
     *
     * @Route("/{id}", name="patch")
     * @Method("PATCH")
     *
     * @Security("has_role('ROLE_USER')")
     */
    public function patchAction (Request $request, Deck $deck, EntityManagerInterface $entityManager)
    {
        if ($deck->isPublished() === false) {
            throw $this->createNotFoundException();
        }
        if ($deck->getUser() !== $this->getUser()) {
            throw $this->createAccessDeniedException();
        }

        $form = $this->createForm(PublicDeckType::class, $deck);
        $form->submit(json_decode($request->getContent(), true), false);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->flush();

            return $this->success($deck, [
                'Public',
                'Description',
                'Cards',
            ]);
        }

        return $this->failure('validation_error', $this->formatValidationErrors($form->getErrors(true)));
    }

    /**
     * Delete a public deck
     *
     * @Route("/{id}", name="delete")
     * @Method("DELETE")
     *
     * @Security("has_role('ROLE_USER')")
     */
    public function deleteAction (Deck $deck, DeckManager $deckManager, EntityManagerInterface $entityManager)
    {
        if ($deck->isPublished() === false) {
            throw $this->createNotFoundException();
        }
        if ($deck->getUser() !== $this->getUser()) {
            throw $this->createAccessDeniedException();
        }
        if ($deck->getComments()->count() > 0) {
            return $this->failure('error', 'This deck has comments.');
        }

        try {
            if ($deck->getStrain() instanceof Strain) {
                $deck->setPublished(false);
                $deck->setPublishedAt(null);
            } else {
                $deckManager->deleteDeck($deck);
            }
            $entityManager->flush();
        } catch (\Exception $ex) {
            return $this->failure($ex->getMessage());
        }

        return $this->success();
    }
}

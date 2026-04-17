<?php

namespace App\Controller;

use App\Entity\Card;
use App\Entity\User;
use App\Entity\UserCard;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class CardController extends AbstractController
{
    public function __construct(protected EntityManagerInterface $entityManager)
    {
    }

    #[Route('/api/getCard', name: 'get_rarity', methods: ['GET'])]
    public function getRarityCard(Request $request): Response
    {
        if (!$this->isAllowedPull()) throw $this->createAccessDeniedException();

        $user = $this->getUser();
        if ($user) {
            $currentUser = $this->entityManager->getRepository(User::class)->findOneByUsername($user->getUsername());
            $rarity = $currentUser->getCountToSSR() >= 25 ? 'SSR' : $this->getRarity();
            $currentUser->setCountToSSR($rarity === 'SSR' ? 0 : $currentUser->getCountToSSR() + 1);
            $this->entityManager->flush();
        } else {
            $forcedRarity = $request->query->get('rarity');
            $rarity = $forcedRarity ?? $this->getRarity();
        }

        $cards = $this->entityManager->getRepository(Card::class)->findByRarity($rarity);
        $card = $cards[array_rand($cards)];

        return $this->json($card);
    }

    #[Route('/api/addCollection', name: 'user_collection', methods: ['POST'])]
    public function addToCollection(Request $request): Response
    {
        $user = $this->getUser();
        $data = json_decode($request->getContent(), true);

        $card = $this->entityManager->getRepository(Card::class)->findOneByName($data['card']['name']);
        if (!$card) {
            return new JsonResponse(['error' => 'Carta no encontrada.'], Response::HTTP_NOT_FOUND);
        }
        $isNewCard = $this->entityManager->getRepository(UserCard::class)->findOneBy(['user' => $user, 'card' => $card]);

        if (!$isNewCard) {
            $userCard = new UserCard();
            $userCard->setUser($user);
            $userCard->setCard($card);

            $this->entityManager->persist($userCard);
            $this->entityManager->flush();
        }

        return $this->json(['status' => 'ok']);
    }

    #[Route('/api/collection', name: 'get_collection', methods: ['GET'])]
    public function getCollectionCards(): Response
    {
        $user = $this->getUser();
        $cards = $this->entityManager->getRepository(UserCard::class)->findByUser($user);
        return $this->json($cards);
    }

    #[Route('/api/enemyCard', name: 'enemy_card', methods: ['GET'])]
    public function getEnemyCard(): Response
    {
        $day = (new \DateTime())->format('N');
        $enemyCard = $this->entityManager->getRepository(Card::class)->findOneById($day);
        return $this->json($enemyCard);
    }

    #[Route('/api/pullsRemaining', name: 'pulls_remaining', methods: ['GET'])]
    public function getPullsRemaining(): Response
    {
        $user = $this->entityManager->getRepository(User::class)->findOneByUsername($this->getUser()->getUsername());
        $maxPulls = gmdate('N') === '7' ? 10 : 5;
        $used = $user->getLastPull() != gmdate('Y-m-d') ? 0 : $user->getPullsUsedToday();

        return $this->json(['remaining' => ($maxPulls - $used), 'max' => $maxPulls, 'count' => $user->getCountToSSR(), 'maxCount' => 25]);
    }
    private function getRarity(): string
    {
        $number = rand(0, 100);
        return match (true) {
            $number < 10 => 'SSR',
            $number < 40 => 'SR',
            $number >= 40 => 'R',
        };
    }

    private function isAllowedPull(): bool
    {
        if ($this->getUser() === null) return true;
        $lastUserPull = $this->entityManager->getRepository(User::class)->findOneByUsername($this->getUser()->getUsername());
        $maxPullsByDay = gmdate('N') === "7" ? 10 : 5;

        if ($lastUserPull->getLastPull() === gmdate('Y-m-d') && $lastUserPull->getPullsUsedToday() >= $maxPullsByDay) {
            return false;
        } elseif ($lastUserPull->getLastPull() !== gmdate('Y-m-d')) {
            $lastUserPull->setPullsUsedToday(0);
            $lastUserPull->setLastPull(gmdate('Y-m-d'));
        }
        $lastUserPull->setPullsUsedToday($lastUserPull->getPullsUsedToday() + 1);

        return true;
    }
}

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
    public function getRarityCard(): Response
    {
        if (!$this->isAllowedPull()) return throw $this->createAccessDeniedException();

        $number = rand(0, 100);
        $rarity = match (true) {
            $number < 10 => 'SSR' ,
            $number < 40 => 'SR',
            $number >= 40 => 'R',
        };
        $card = $this->entityManager->getRepository(Card::class)->findOneByRarity($rarity);

        return $this->json($card);
    }

    #[Route('/api/addCollection', name: 'user_collection', methods: ['POST'])]
    public function addToCollection(Request $request): Response
    {
        $user = $this->getUser();
        $data = json_decode($request->getContent(), true);
        if (!is_array($data) || !isset($data['card']['name'])) {
            return new JsonResponse(['error' => 'Datos de carta incompletos.'], Response::HTTP_BAD_REQUEST);
        }

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
        $today = gmdate('Y-m-d');
        $maxPulls = gmdate('N') === '7' ? 10 : 5;

        if ($user->getLastPull() !== $today) {
            $used = 0;
        } else {
            $used = $user->getPullsUsedToday();
        }

        return $this->json(['remaining' => ($maxPulls - $used), 'max' => $maxPulls]);
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
        $this->entityManager->flush();

        return true;
    }

}

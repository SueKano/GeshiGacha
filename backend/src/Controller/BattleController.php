<?php

namespace App\Controller;

use App\Entity\BattleHistory;
use App\Entity\Card;
use App\Entity\Environment;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class BattleController extends AbstractController
{
    public function __construct(protected EntityManagerInterface $entityManager)
    {
    }

    #[Route('/api/randomBattle', name: 'random_battle', methods: ['GET'])]
    public function doRandomBattle(): Response
    {
        $allCards = $this->entityManager->getRepository(Card::class)->findAll();
        $randomIds = array_rand($allCards, 2);
        $playerCard = $allCards[$randomIds[0]];
        $enemyCard = $allCards[$randomIds[1]];

        $playerHealth = $playerCard->getHealth();
        $enemyHealth = $enemyCard->getHealth();
        $playerShield = $this->calculateShield($playerCard->getDefense(), $playerCard->getRarity());
        $enemyShield = $this->calculateShield($enemyCard->getDefense(), $enemyCard->getRarity());

        $response = [
            'playerHealth' => $playerHealth,
            'enemyHealth'  => $enemyHealth,
            'playerShield' => $playerShield,
            'enemyShield'  => $enemyShield,
            'enemyCard' => $enemyCard,
            'playerCard' => $playerCard,
        ];

        $turns = [];

        while ($playerHealth > 0 && $enemyHealth > 0) {
            $playerAttack = rand(0, $playerCard->getAttack());
            [$enemyShield, $enemyHealth] = $this->applyDamage($enemyShield, $enemyHealth, $playerAttack);

            $enemyAttack = rand(0, $enemyCard->getAttack());
            [$playerShield, $playerHealth] = $this->applyDamage($playerShield, $playerHealth, $enemyAttack);

            $turns[] = [
                'playerAttack'  => $playerAttack,
                'enemyAttack'   => $enemyAttack,
                'playerHealth'  => $playerHealth,
                'enemyHealth'   => $enemyHealth,
                'playerShield'  => $playerShield,
                'enemyShield'   => $enemyShield,
            ];
        }

        $response['winner'] = $playerHealth > 0 ? $playerCard : $enemyCard;
        $response['turns']  = $turns;

        return $this->json($response);
    }

    #[Route('/api/battle', name: 'battle', methods: ['POST'])]
    public function doBattle(Request $request): Response
    {
        $data = json_decode($request->getContent(), true);

        $playerCard = $data['playerCard'];
        $enemyCard = $data['enemyCard'];
        $env = $data['environment'];
        $env['turns'] = $env['turns'] ?? 0;

        $playerHealth = ($playerCard['attack'] + $playerCard['defense']) * 10;
        $enemyHealth  = ($enemyCard['attack'] + $enemyCard['defense']) * 10;
        $playerShield = $this->calculateShield($playerCard['defense'], $playerCard['rarity']);
        $enemyShield  = $this->calculateShield($enemyCard['defense'], $enemyCard['rarity']);

        $playerHealth = $this->applyPermanentModifier($playerHealth, $playerCard, $env, 'health');
        $enemyHealth = $this->applyPermanentModifier($enemyHealth, $enemyCard, $env, 'health');
        $playerCard['attack'] = $this->applyPermanentModifier($playerCard['attack'], $playerCard, $env, 'attack');
        $enemyCard['attack'] = $this->applyPermanentModifier($enemyCard['attack'], $enemyCard, $env, 'attack');

        $response = [
            'playerHealth' => $playerHealth,
            'enemyHealth'  => $enemyHealth,
            'playerShield' => $playerShield,
            'enemyShield'  => $enemyShield,
        ];

        $turns = [];
        for ($t = 0; $playerHealth > 0 && $enemyHealth > 0; $t++) {
            $playerAttack = $this->calculateAttack($playerCard, $env, $t);
            $playerHeal = $this->calculateHealing($playerCard, $env, $playerAttack);
            [$enemyShield, $enemyHealth] = $this->applyDamage($enemyShield, $enemyHealth, $playerAttack, $playerHeal);

            $enemyAttack = $this->calculateAttack($enemyCard, $env, $t);
            $enemyHeal = $this->calculateHealing($enemyCard, $env, $enemyAttack);
            [$playerShield, $playerHealth] = $this->applyDamage($playerShield, $playerHealth, $enemyAttack, $enemyHeal);

            $turns[] = [
                'playerAttack'  => $playerAttack,
                'enemyAttack'   => $enemyAttack,
                'playerHealth'  => $playerHealth,
                'enemyHealth'   => $enemyHealth,
                'playerShield'  => $playerShield,
                'enemyShield'   => $enemyShield,
                'playerHeal'    => $playerHeal,
                'enemyHeal'     => $enemyHeal,
            ];
        }

        $response['winner'] = $playerHealth > 0 ? $playerCard : $enemyCard;
        $response['turns']  = $turns;

        return $this->json($response);
    }

    #[Route('/api/addBattleHistory', name: 'add_battle_history', methods: ['POST'])]
    public function addBattleHistory(Request $request): Response
    {
        $data = json_decode($request->getContent(), true);
        $enemyCard = $this->entityManager->getRepository(Card::class)->findOneByName($data['enemyCard']);
        $playerCard = $this->entityManager->getRepository(Card::class)->findOneByName($data['playerCard']);

        if (!$enemyCard || !$playerCard) {
            return new JsonResponse(['error' => 'Carta no encontrada.'], Response::HTTP_NOT_FOUND);
        }

        $battle = new BattleHistory();
        $battle->setEnemyCard($enemyCard);
        $battle->setIsPlayerWon($data['result']);
        $battle->setUserCard($playerCard);
        if ($this->getUser()) {
            $battle->setUser($this->getUser());
        }

        $this->entityManager->persist($battle);
        $this->entityManager->flush();

        return $this->json(['status' => '200']);
    }

    #[Route('/api/battleHistory', name: 'get_battle_history', methods: ['GET'])]
    public function getBattleHistory(): Response
    {
        $isUserActive = $this->getUser() ? ['user' => $this->getUser()] : [];
        $battles = $this->entityManager->getRepository(BattleHistory::class)->findBy($isUserActive, ['createdAt' => 'DESC'], 5);

        return $this->json($battles);
    }

    #[Route('/api/getEnvironment', name: 'get_environment', methods: ['GET'])]
    public function getEnvironment(): Response
    {
        $day = (new \DateTime())->format('N');
        $environment = $this->entityManager->getRepository(Environment::class)->findOneById($day);
        return $this->json($environment);
    }

    private function applyDamage(int $shield, int $health, int $damage, ?int $heal = null): array
    {
        $heal = $heal ?? 0;
        if ($shield > 0) {
            $absorbed = min($shield, $damage);
            $shield -= $absorbed;
            $health = max(0, $health - ($damage - $absorbed));
        } else {
            $health = max(0, ($health + $heal)  - $damage);
        }
        return [$shield, $health];
    }

    private function calculateShield(int $defense, string $rarity): int
    {
        $multiplier = match ($rarity) {
            'SSR' => 5,
            'SR' => 3,
            default => 1,
        };
        return $defense * $multiplier;
    }

    private function matchesEnvironment(array $card, array $env): bool
    {
        return $card['type'] === $env['affectedType'];
    }
    private function applyPermanentModifier(int $stat, array $card, array $env, string $statType): int
    {
        if ($env['turns'] !== 0 || !$this->matchesEnvironment($card, $env) || $env['benefitType'] !== $statType) {
            return $stat;
        }
        $sign = $env['isUpAffectedType'] ? 1 : -1;

        return $stat * (1 + $sign * $env['quantity']);
    }

    private function calculateAttack(array $card, array $env, int $turn): int
    {
        $maxAttack = $card['attack'];
        if ($this->matchesEnvironment($card, $env) && $env['benefitType'] === 'attack' && $env['turns'] > 1 && $turn < $env['turns']) {
            $maxAttack = (int)($maxAttack * $env['quantity']);
        }

        $attack = rand(0, $maxAttack);
        if ($env['turns'] === 1 && $turn === 0 && $this->matchesEnvironment($card, $env)) {
            $attack *= 2;
        }

        return $attack;
    }
    private function calculateHealing(array $card, array $env, int $attack): int
    {
        $healing = 0;
        if ($this->matchesEnvironment($card, $env) && $env['benefitType'] === 'health') {
            $healing = $attack * $env['quantity'];
        }

        return $healing;
    }
}

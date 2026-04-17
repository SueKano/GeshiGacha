<?php

namespace App\Controller;

use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\RateLimiter\RateLimiterFactoryInterface;
use Symfony\Component\Routing\Attribute\Route;

class UserController extends AbstractController
{
    #[Route('/api/createUser', name: 'new_user', methods: ['POST'])]
    public function createUser(Request $request, UserPasswordHasherInterface $hasher,
                               EntityManagerInterface $entityManager, RateLimiterFactoryInterface $registrationLimiter): Response
    {
        $limiter = $registrationLimiter->create($request->getClientIp());
        if (!$limiter->consume()->isAccepted()) {
            return new JsonResponse(['error' => 'Demasiados intentos. Inténtalo más tarde.'], Response::HTTP_TOO_MANY_REQUESTS);
        }

        $data = json_decode($request->getContent(), true);
        $email = trim($data['email']);
        $plainPassword = $data['password'] ?? '';

        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            return new JsonResponse(['error' => 'Email no válido.'], Response::HTTP_BAD_REQUEST);
        }

        if (mb_strlen($plainPassword) < 8) {
            return new JsonResponse(['error' => 'La contraseña debe tener al menos 8 caracteres.'], Response::HTTP_BAD_REQUEST);
        }

        $existingUser = $entityManager->getRepository(User::class)->findOneByUsername($email);
        if ($existingUser) {
            return new JsonResponse(['error' => 'Ya existe una cuenta con ese email.'], Response::HTTP_CONFLICT);
        }

        $user = new User();
        $user->setUsername($email);
        $hashedPassword = $hasher->hashPassword($user, $plainPassword);
        $user->setPassword($hashedPassword);

        $entityManager->persist($user);
        $entityManager->flush();

        return $this->json(['status' => 'ok']);
    }

    #[Route('/api/login', name: 'api_login', methods: ['POST'])]
    public function login(): Response
    {
        throw new \LogicException('Este endpoint lo maneja el firewall de Symfony.');
    }
}

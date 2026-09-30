<?php

namespace App\Controller;

use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Attribute\Route;

final class AuthController extends AbstractController
{
    #[Route('/api/register', name: 'api_register', methods: ['POST'])]

    public function register(
        Request $request,
        UserPasswordHasherInterface $passwordHasher,
        EntityManagerInterface $entityManager
    ): JsonResponse {
        $data = json_decode($request->getContent(), true);

        if (!$data) {
            return $this->json([
                'message' => 'Invalid JSON data.'
            ], 400);
        }

        // Check required fields
        $requiredFields = [
            'email',
            'password',
            'firstName',
            'lastName',
            'field',
            'year'
        ];

        foreach ($requiredFields as $field) {
            if (empty($data[$field])) {
                return $this->json([
                    'message' => "Missing field: $field"
                ], 400);
            }
        }

        // Check if email already exists
        $existingUser = $entityManager
            ->getRepository(User::class)
            ->findOneBy(['email' => $data['email']]);

        if ($existingUser) {
            return $this->json([
                'message' => 'Email is already registered.'
            ], 409);
        }

        // Create user
        $user = new User();

        $user->setEmail($data['email']);
        $user->setFirstName($data['firstName']);
        $user->setLastName($data['lastName']);
        $user->setField($data['field']);
        $user->setYear($data['year']);

        // New users are students by default
        $user->setRoles(['ROLE_USER']);

        // Hash password
        $hashedPassword = $passwordHasher->hashPassword(
            $user,
            $data['password']
        );

        $user->setPassword($hashedPassword);

        // Set creation date
        $user->setCreatedAt(new \DateTimeImmutable());

        $entityManager->persist($user);
        $entityManager->flush();

        return $this->json([
            'message' => 'User registered successfully.',
            'user' => [
                'id' => $user->getId(),
                'email' => $user->getUserIdentifier(),
                'firstName' => $user->getFirstName(),
                'lastName' => $user->getLastName(),
                'field' => $user->getField(),
                'year' => $user->getYear(),
                'roles' => $user->getRoles()
            ]
        ], 201);
    }


    #[Route('/api/login', name: 'api_login', methods: ['POST'])]
    public function login(): never
    {
    throw new \LogicException('This method should never be called directly.');
    }
    /*
    #[Route('/api/me', name: 'api_me', methods: ['GET'])]
    public function me(): JsonResponse
    {
    $user = $this->getUser();
    
    return $this->json([
        'id' => $user->getId(),
        'email' => $user->getUserIdentifier(),
        'firstName' => $user->getFirstName(),
        'lastName' => $user->getLastName(),
        'field' => $user->getField(),
        'year' => $user->getYear(),
        'roles' => $user->getRoles(),
    ]);
    
    }   
    */







}
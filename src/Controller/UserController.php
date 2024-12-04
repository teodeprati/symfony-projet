<?php

namespace App\Controller;

use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

class UserController extends AbstractController
{
    private $entityManager;

    public function __construct(EntityManagerInterface $entityManager)
    {
        $this->entityManager = $entityManager;
    }

    public function listUsers(): JsonResponse
    {
        $users = $this->entityManager->getRepository(User::class)->findAll();
        return $this->json($users);
    }

    public function findUserById($id): JsonResponse
    {
        $user = $this->entityManager->getRepository(User::class)->find($id);

        if (!$user) {
            return $this->json(['error' => 'User not found'], 404);
        }

        return $this->json($user);
    }


    public function createUser(Request $request, UserPasswordHasherInterface $passwordHasher): JsonResponse
    {
        $data = json_decode($request->getContent(), true);

        if (!$data || !isset($data['username'], $data['email'], $data['password'])) {
            return $this->json([
                'error' => 'Invalid data. Required: username, email, and password.',
            ], 400);
        }

        // Vérification si un utilisateur avec cet email existe déjà
        $existingUser = $this->entityManager
            ->getRepository(User::class)
            ->findOneBy(['email' => $data['email']]);

        if ($existingUser) {
            return $this->json([
                'error' => 'A user with this email already exists.',
            ], 409);
        }

        // Création de l'utilisateur
        $user = new User();
        $user->setUsername($data['username']);
        $user->setEmail($data['email']);
        $hashedPassword = $passwordHasher->hashPassword($user, $data['password']);
        $user->setPassword($hashedPassword);

        // Sauvegarde dans la base de données
        $this->entityManager->persist($user);
        $this->entityManager->flush();

        return $this->json([
            'message' => 'User successfully created!',
            'user' => [
                'id' => $user->getId(),
                'username' => $user->getUsername(),
                'email' => $user->getEmail(),
            ],
        ], 201);
    }

    public function updateUser(Request $request): JsonResponse
    {
        $data = json_decode($request->getContent(), true);

        if (!$data || !isset($data['id'], $data['username'])) {
            return $this->json([
                'error' => 'Invalid data. Required: id and username.',
            ], 400);
        }

        // Recherche de l'utilisateur par ID
        $user = $this->entityManager->getRepository(User::class)->find($data['id']);

        if (!$user) {
            return $this->json(['error' => 'User not found.'], 404);
        }

        // Mise à jour de l'utilisateur
        $user->setUsername($data['username']);
        $this->entityManager->flush();

        return $this->json([
            'message' => 'User updated successfully.',
            'user' => [
                'id' => $user->getId(),
                'username' => $user->getUsername(),
            ],
        ], 200);
    }

    public function deleteUser(Request $request): JsonResponse
    {
        $data = json_decode($request->getContent(), true);

        if (!$data || !isset($data['id'])) {
            return $this->json([
                'error' => 'Invalid data. Required: id.',
            ], 400);
        }

        // Recherche de l'utilisateur par ID
        $user = $this->entityManager->getRepository(User::class)->find($data['id']);

        if (!$user) {
            return $this->json(['error' => 'User not found.'], 404);
        }

        // Suppression de l'utilisateur
        $this->entityManager->remove($user);
        $this->entityManager->flush();

        return $this->json([
            'message' => 'User deleted successfully.',
        ], 200);
    }
}

<?php
// src/Controller/Api/UserController.php
namespace App\Controller\Api;

use App\Entity\User;
use App\Repository\UserRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Component\Serializer\SerializerInterface;

#[Route('/api/users', name: 'api_users_')]
class UserController extends AbstractController
{
    public function __construct(private SerializerInterface $serializer) {}

    /**
     * GET /api/users/me
     * @groups user:read
     */
    #[Route('/me', name: 'me', methods: ['GET'])]
    #[IsGranted('IS_AUTHENTICATED_FULLY')]
    public function me(): JsonResponse
    {
        /** @var User $user */
        $user = $this->getUser();

        // On ne renvoie que les champs nécessaires
        $data = [
            'id'        => $user->getId(),
            'firstName' => $user->getFirstName(),
            'lastName'  => $user->getLastName(),
            'avatarPath'=> $user->getAvatarPath(),
        ];

        return $this->json(
            ['data' => $data],
            JsonResponse::HTTP_OK
        );
    }

    #[Route('', name: 'list', methods: ['GET'])]
    public function list(UserRepository $repo): JsonResponse
    {
        $users = $repo->findAll();
        // On ne conserve que les champs souhaités
    $data = array_map(function(User $u) {
        return [
            'id'          => $u->getId(),
            'firstName'   => $u->getFirstName(),
            'lastName'    => $u->getLastName(),
            'avatarPath'  => $u->getAvatarPath(),
        ];
    }, $users);

        return $this->json(
            ['data' => $data],
            JsonResponse::HTTP_OK,
            [],
            ['groups' => ['user:read']]
        );
    }

    /**
     * GET /api/users/{id}
     * @groups user:read
     */
    #[Route('/{user}', name: 'show', methods: ['GET'])]
    public function show(User $user): JsonResponse
    {
        return $this->json(
            ['data' => $user],
            JsonResponse::HTTP_OK,
            [],
            ['groups' => ['user:read']]
        );
    }
}

<?php

namespace App\Controller\Api;

use App\Entity\Conversation;
use App\Repository\ConversationRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Serializer\SerializerInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;

#[Route('/api/conversations', name: 'api_')]
class ConversationController extends AbstractController
{
    public function __construct(
        private EntityManagerInterface $em,
        private SerializerInterface $serializer,
        private ValidatorInterface $validator,
        private ConversationRepository $repo
    ){}

    #[Route('', name: 'conv_list', methods: ['GET'])]
    public function list(): JsonResponse
    {
        $convs = $this->repo->findAll();
        $data  = $this->serializer->serialize($convs, 'json', ['groups'=>['conv:read']]);
        return new JsonResponse($data, 200, [], true);
    }

    #[Route('/{id}', name: 'conv_show', methods: ['GET'])]
    public function show(Conversation $conversation): JsonResponse
    {
        $data = $this->serializer->serialize($conversation, 'json', ['groups'=>['conv:read']]);
        return new JsonResponse($data, 200, [], true);
    }

    #[Route('', name: 'conv_create', methods: ['POST'])]
    public function create(Request $req): JsonResponse
    {
        $conv = $this->serializer->deserialize($req->getContent(), Conversation::class, 'json', ['groups'=>['conv:write']]);
        $errors = $this->validator->validate($conv);
        if (count($errors) > 0) {
            return $this->json(['errors'=>$errors], 400);
        }
        $this->em->persist($conv);
        $this->em->flush();
        $data = $this->serializer->serialize($conv, 'json', ['groups'=>['conv:read']]);
        return new JsonResponse($data, 201, [], true);
    }

    #[Route('/{id}', name: 'conv_update', methods: ['PUT','PATCH'])]
    public function update(Conversation $conv, Request $req): JsonResponse
    {
        // on réutilise le serializer pour hydrater
        $this->serializer->deserialize($req->getContent(), Conversation::class, 'json', [
            'object_to_populate' => $conv,
            'groups' => ['conv:write']
        ]);
        $errors = $this->validator->validate($conv);
        if (count($errors) > 0) {
            return $this->json(['errors'=>$errors], 400);
        }
        $this->em->flush();
        $data = $this->serializer->serialize($conv, 'json', ['groups'=>['conv:read']]);
        return new JsonResponse($data, 200, [], true);
    }

    #[Route('/{conv}/status', name: 'conv_status', methods: ['PATCH'])]
    public function patchStatus(Conversation $conv, Request $req): JsonResponse
    {
        $payload = json_decode($req->getContent(), true);
        if (isset($payload['status'])) {
            $conv->setMessageStatus($payload['status']);
        }
        if (isset($payload['unreadCount'])) {
            $conv->setUnreadCount($payload['unreadCount']);
        }
        $this->em->flush();
        return $this->json(['data'=>$conv], 200, context: ['groups'=>['conv:read']]);
    }


    #[Route('/{id}', name: 'conv_delete', methods: ['DELETE'])]
    public function delete(Conversation $conv): JsonResponse
    {
        $this->em->remove($conv);
        $this->em->flush();
        return new JsonResponse(null, 204);
    }
}

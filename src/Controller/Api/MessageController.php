<?php
// src/Controller/Api/MessageController.php
namespace App\Controller\Api;

use App\Entity\Conversation;
use App\Entity\Message;
use App\Repository\MessageRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Validator\Validator\ValidatorInterface;

#[Route('/api/conversations/{conversation}/messages', name: 'api_conv_msgs_')]
class MessageController extends AbstractController
{
    public function __construct(
        private EntityManagerInterface   $em,
        private MessageRepository        $repo,
        private ValidatorInterface $validator
    ) {}

    /**
     * GET /api/conversations/{conversation}/messages
     */
    #[Route('', name: 'list', methods: ['GET'])]
    public function list(Conversation $conversation): JsonResponse
    {
        $msgs = $this->repo->findBy(
            ['conversation' => $conversation],
            ['sentAt' => 'ASC']
        );

        return $this->json(
            ['data' => $msgs],
            JsonResponse::HTTP_OK,
            [],
            ['groups' => ['msg:read']]
        );
    }

    /**
     * POST /api/conversations/{conversation}/messages
     * Body JSON attendu : { "content": "…le texte…" }
     */
    #[Route('', name: 'create', methods: ['POST'])]
    public function create(Conversation $conversation, Request $request): JsonResponse
    {
        $data = json_decode($request->getContent(), true);
        if (!isset($data['content']) || !is_string($data['content'])) {
            return $this->json(
                ['error' => 'Le champ "content" est obligatoire.'],
                JsonResponse::HTTP_BAD_REQUEST
            );
        }

        $msg = new Message();
        $msg
            ->setConversation($conversation)
            ->setEnvoyeurId((string) $this->getUser()->getId())
            ->setContent(trim($data['content']))
            ->setSentAt(new \DateTimeImmutable())
        ;

        $errors = $this->validator->validate($msg);
        if (count($errors) > 0) {
            return $this->json(['errors' => $errors], JsonResponse::HTTP_BAD_REQUEST);
        }

        $this->em->persist($msg);
        $this->em->flush();

        return $this->json(
            ['data' => $msg],
            JsonResponse::HTTP_CREATED,
            [],
            ['groups' => ['msg:read']]
        );
    }

    /**
     * DELETE /api/conversations/{conversation}/messages/{message}
     */
    #[Route('/{message}', name: 'delete', methods: ['DELETE'])]
    public function delete(Message $message): JsonResponse
    {
        $this->em->remove($message);
        $this->em->flush();

        return new JsonResponse(null, JsonResponse::HTTP_NO_CONTENT);
    }
}

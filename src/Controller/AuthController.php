<?php
// src/Controller/AuthController.php

namespace App\Controller;

use App\Entity\Matricule;
use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Gesdinet\JWTRefreshTokenBundle\Model\RefreshTokenManagerInterface;
use Gesdinet\JWTRefreshTokenBundle\Entity\RefreshToken;
use Lexik\Bundle\JWTAuthenticationBundle\Exception\JWTEncodeFailureException;
use Lexik\Bundle\JWTAuthenticationBundle\Services\JWTTokenManagerInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\Validator\Validator\ValidatorInterface;
use Symfony\Component\HttpFoundation\Response;

#[Route('/api', name: 'api_')]
class AuthController extends AbstractController
{
    public function __construct(
        private EntityManagerInterface      $em,
        private UserPasswordHasherInterface $passwordHasher,
        private JWTTokenManagerInterface    $jwtManager,
        private ValidatorInterface          $validator,
        private Security                    $security,
        private LoggerInterface             $logger,
        private RefreshTokenManagerInterface $refreshTokenManager
    ) {}



    #[Route('/register', name: 'register', methods: ['POST'])]
    public function register(Request $request): JsonResponse
    {
        $data     = json_decode($request->getContent(), true);
        $first    = trim($data['firstName']   ?? '');
        $last     = trim($data['lastName']    ?? '');
        $matric   = strtoupper(trim($data['matricule'] ?? ''));
        $email    = trim($data['email']       ?? '');
        $password = $data['password']         ?? '';

        // 1) Champs obligatoires
        if (empty($first) || empty($last) || empty($matric) || empty($email) || empty($password)) {
            return $this->json(['message' => 'Tous les champs sont requis.'], Response::HTTP_BAD_REQUEST);
        }

        // 2) Vérifier l’existence du matricule
        $matRepo = $this->em->getRepository(Matricule::class);
        /** @var Matricule|null $mat */
        $mat = $matRepo->findOneBy(['code' => $matric]);
        if (null === $mat) {
            return $this->json(['message' => 'Matricule non reconnu.'], Response::HTTP_BAD_REQUEST);
        }

        // 3) Email unique
        $userRepo = $this->em->getRepository(User::class);
        if ($userRepo->findOneBy(['email' => $email])) {
            return $this->json(['message' => 'Cet email est déjà utilisé.'], Response::HTTP_CONFLICT);
        }

        // 4) Vérifier si les initiales correspondent à celles du matricule
        //    (matricule: AJEUYYYYII###, II = initiales stockées)
        $initialNom    = strtoupper($last[0]);
        $initialPrenom = strtoupper($first[0]);
        $initialesSaisies = $initialNom . $initialPrenom;

        $codeExistant      = $mat->getCode();
        $annee             = substr($codeExistant, 4, 4);    // YYYY
        $initialesBDD      = substr($codeExistant, 8, 2);    // II

        if ($initialesSaisies !== $initialesBDD) {
            // On génère un nouveau code unique basé sur les bonnes initiales
            do {
                $nouveauCode = sprintf(
                    'AJEU%s%s%03d',
                    $annee,
                    $initialesSaisies,
                    random_int(0, 999)
                );
                $exists = (bool) $matRepo->findOneBy(['code' => $nouveauCode]);
            } while ($exists);

            // On met à jour l'entité Matricule
            $mat->setCode($nouveauCode);
            $mat->setNom($last);
            $mat->setPrenom($first);
            $mat->setEmail($email);
            $this->em->persist($mat);
            $this->em->flush();  // on persiste tout de suite pour garantir l'unicité
        } else {
            $mat->setNom($last);
            $mat->setPrenom($first);
            $mat->setEmail($email);
            $this->em->persist($mat);
            $this->em->flush();
        }

        



        // 5) Création de l’utilisateur
        $user = new User();
        $user->setFirstName($first)
            ->setLastName($last)
            ->setMatricule($mat)  // on associe l'objet Matricule
            ->setEmail($email)
            ->setPassword($this->passwordHasher->hashPassword($user, $password))
            ->setRoles(['ROLE_MEMBER']);

        // 6) Validation
        $errors = $this->validator->validate($user);
        if (count($errors) > 0) {
            return $this->json(
                ['message' => 'Erreur de validation des données.'],
                Response::HTTP_UNPROCESSABLE_ENTITY
            );
        }

        // 7) Persistance
        $this->em->persist($user);
        $this->em->flush();

        // 8) Génération du JWT
        try {
            $token = $this->jwtManager->create($user);
        } catch (JWTEncodeFailureException $e) {
            $this->logger->error('JWT encode failed: ' . $e->getMessage());
            return $this->json(
                ['message' => 'Erreur interne : impossible de générer le token JWT.'],
                Response::HTTP_INTERNAL_SERVER_ERROR
            );
        }

        // 9) Réponse
        return $this->json([
            'message'   => 'Inscription réussie!',
            'token'     => $token,
            'user'      => [
                'id'        => $user->getId(),
                'email'     => $user->getEmail(),
                'roles'     => $user->getRoles(),
                'firstName' => $user->getFirstName(),
                'lastName'  => $user->getLastName(),
                // on renvoie le code à jour du matricule
                'matricule' => $mat->getCode(),
            ],
        ], Response::HTTP_CREATED);
    }


    #[Route('/login', name: 'login', methods: ['POST'])]
    public function login(
        Request $request,
        RefreshTokenManagerInterface $refreshTokenManager
    ): JsonResponse {
        $data = json_decode($request->getContent(), true);
        $email = $data['email'] ?? null;
        $password = $data['password'] ?? null;

        if (!$email || !$password) {
            return $this->json(['message' => 'Email et mot de passe requis.'], 400);
        }

        $user = $this->em->getRepository(User::class)->findOneBy(['email' => $email]);

        if (!$user || !$this->passwordHasher->isPasswordValid($user, $password)) {
            return $this->json(['message' => 'Email ou le mot de passe incorrect.'], 401);
        }

        try {
            $token = $this->jwtManager->create($user);
        } catch (\Exception $e) {
            return $this->json([
                'message' => 'Erreur interne : impossible de générer le token JWT.'
            ], 500);
        }

        // Création du refresh token
        $refreshToken = new RefreshToken();
        $refreshToken->setRefreshToken(bin2hex(random_bytes(64))); // Génère un token sécurisé
        $refreshToken->setUsername($user->getEmail());
        $refreshToken->setValid((new \DateTime())->modify('+30 days'));

        $refreshTokenManager->save($refreshToken);

        // Récupérer les cotisations de l’utilisateur
        $cots = [];
        foreach ($user->getCotisations() as $cot) {
            $cots[] = [
                'id'      => $cot->getId(),
                'montant' => $cot->getMontant(),
                'annee'   => $cot->getAnnee(),
                'cotised' => $cot->isCotised(),
            ];
        }

        return $this->json([
            'token' => $token,
            'refresh_token' => $refreshToken->getRefreshToken(),
            'user' => [
                'id' => $user->getId(),
                'email' => $user->getEmail(),
                'roles' => $user->getRoles(),
                'lastName'  => $user->getLastName(),
                'firstName' => $user->getFirstName(),
                'avatarPath' => $user->getAvatarPath(),
                'matricule' => $user->getMatricule()?->getCode(),
                'cotisations' => $cots,
            ]
        ]);
    }


    #[Route('/me', name: 'me', methods: ['GET'])]
    public function me(): JsonResponse
    {
        $user = $this->security->getUser();

        if (!$user instanceof User) {
            return $this->json(
                ['error' => 'Non authentifié.'],
                Response::HTTP_UNAUTHORIZED
            );
        }

        return $this->json(
            [
                'id'    => $user->getId(),
                'email' => $user->getEmail(),
                'roles' => $user->getRoles(),
                'firstName' => $user->getFirstName(),
                'lastName'  => $user->getLastName(),
                'avatarPath' => $user->getAvatarPath(),
                'matricule' => $user->getMatricule()?->getCode(),
            ],
            Response::HTTP_OK
        );
    }

    #[Route('/logout', name: 'logout', methods: ['POST'])]
    public function logout(): JsonResponse
    {
        return $this->json([
            'message' => 'Déconnexion réussie. Supprimez le token côté client.'
        ]);
    }

    // #[Route('/profile', name: 'profile', methods: ['GET'])]
    // public function profile(): JsonResponse
    // {
    //     /** @var \App\Entity\User $user */
    //     $user = $this->getUser();
    //     $cotisations = [];
    //     foreach ($user->getCotisations() as $cot) {
    //         $cotisations[] = [
    //             'id'         => $cot->getId(),
    //             'montant'    => $cot->getMontant(),
    //             'annee'      => $cot->getAnnee(),
    //             'cotised'    => $cot->isCotised(),
    //             'createdAt'  => $cot->getCreatedAt()->format('Y-m-d H:i:s'),
    //         ];
    //     }
    //     return $this->json([
    //         'email' => $user->getEmail(),
    //         'roles' => $user->getRoles(),
    //         'firstName' => $user->getFirstName(),
    //         'lastName'  => $user->getLastName(),
    //         'matricule' => $user->getMatricule()?->getCode(),
    //         'cotisations' => $cotisations,
    //     ]);
    // }
}

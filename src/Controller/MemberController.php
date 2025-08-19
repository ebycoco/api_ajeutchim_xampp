<?php
// src/Controller/MemberController.php
namespace App\Controller;

use App\Entity\User;
use App\Entity\Matricule;
use App\Repository\CotisationRepository;
use App\Repository\MatriculeRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\File\Exception\FileException;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/api', name: 'api_')]
class MemberController extends AbstractController
{
    public function __construct(
        private EntityManagerInterface $em,
        private MatriculeRepository   $repo,
        private CotisationRepository $cotRepo,
        private string $uploadsDir,
        private ParameterBagInterface $params,
    ) {}



    #[Route('/members', name: 'members_list', methods: ['GET'])]
    public function listAll(Request $request): JsonResponse
    {
         // 1) On récupère le paramètre 'year' (si fourni)
        $year = $request->query->getInt('year', 0);

        // 2) On prépare la requête
        $repo = $this->em->getRepository(Matricule::class);
        $matricules = $year > 0
        ? $repo->findBy(['anneeAdhesion' => $year])
        : $repo->findAll();
        $mat = $repo->findAll();
        
    // 3) On mappe chaque Matricule en tableau
        $data = array_map(function (Matricule $m) {
            // --- 1) on construit le tableau des cotisations détaillées ---
            $cots = [];
            foreach ($m->getCotisations() as $cot) {
                $cots[] = [
                    'id'        => $cot->getId(),
                    'montant'   => $cot->getMontant(),
                    'annee'     => $cot->getAnnee(),
                    'cotised'   => $cot->isCotised(),
                    'createdAt' => $cot->getCreatedAt()->format('Y-m-d'),
                ];
            }

            // --- 2) on décide de la valeur de 'cotised' globale pour l'année d'adhésion ---
            // ici, on cherche s'il existe une cotisation pour l’année d’adhésion
            $year      = $m->getAnneeAdhesion();
            $cotForYear = array_filter($cots, fn($c) => (int)$c['annee'] === $year);
            $cotised    = !empty($cotForYear) && reset($cotForYear)['cotised'];

            // --- 3) on renvoie tout le membre enrichi ---
            return [
                'id'              => $m->getId(),
                'code'            => $m->getCode(),
                'nom'             => $m->getNom(),
                'prenom'          => $m->getPrenom(),
                'montantAdhesion' => $m->getMontantAdhesion(),
                'anneeAdhesion'   => $m->getAnneeAdhesion(),
                'email'           => $m->getEmail(),
                'phone'           => $m->getPhone(),
                'commune'         => $m->getCommune(),
                'quartier'        => $m->getQuartier(),
                'avatarPath'      => $m->getAvatarPath(),
                'createdAt'       => $m->getCreatedAt()->format('Y-m-d'), 
                'cotised'         => $cotised,

                // on garde aussi le détail complet
                'cotisations'     => $cots,
            ];
        }, $matricules);

        return $this->json([
        'total' => count($mat),
        'data'  => $data,
    ], JsonResponse::HTTP_OK);
    }


    // Bon : tu obtiens /api/profile
    #[Route('/profile', name: 'profile', methods: ['GET'])]
    public function profile(): JsonResponse
    {
        /** @var User|null $user */
        $user = $this->getUser();
        if (null === $user) {
            return $this->json(['message' => 'Token invalide'], Response::HTTP_UNAUTHORIZED);
        }

        // 1) On prépare la liste des cotisations liées
        $cots = [];
        if (null !== $matricule = $user->getMatricule()) {
            foreach ($matricule->getCotisations() as $cot) {
                $cots[] = [
                    'id'        => $cot->getId(),
                    'montant'   => $cot->getMontant(),
                    'annee'     => $cot->getAnnee(),
                    'cotised'   => $cot->isCotised(),
                    'createdAt' => $cot->getCreatedAt()->format('Y-m-d'),
                ];
            }
        }

        // 2) Construction du profil
        $profile = [
            'id'          => $user->getId(),
            'firstName'   => $user->getFirstName(),
            'lastName'    => $user->getLastName(),
            'email'       => $user->getEmail(),
            'roles'       => $user->getRoles(),
            'commune'     => $user->getCommune(),
            'quartier'    => $user->getQuartier(),
            'phone'       => $user->getPhone(),
            'avatarPath'  => $user->getAvatarPath(),
            'matricule'   => $matricule?->getCode(),
            'cotisations' => $cots,
        ];

        // 3) Statistiques globales
        $all = $this->cotRepo->findAll();
        $total  = count($all);
        $paid   = count(array_filter($all, fn($c) => $c->isCotised()));
        $unpaid = $total - $paid;

        // 4) Réponse
        return $this->json([
            'profile' => $profile,
            'stats'   => [
                'totalMembers' => $total,
                'paidCount'    => $paid,
                'noPaidCount'  => $unpaid,
            ],
        ]);
    }


    #[Route('/profile', name: 'profile_update', methods: ['PUT'])]
    public function update(Request $request): JsonResponse
    {
        /** @var User|null $user */
        $user = $this->getUser();
        if (!$user instanceof User) {
            // 1) Sécurité : pas d'utilisateur connecté
            return $this->json(['message' => 'Non authentifié'], Response::HTTP_UNAUTHORIZED);
        }

        // 2) Récupération des données JSON envoyées
        $data = json_decode($request->getContent(), true);

        // 3) Validation basique des champs obligatoires
        if (empty(trim($data['firstName'] ?? '')) || empty(trim($data['lastName'] ?? ''))) {
            return $this->json(
                ['message' => 'Prénom et nom sont obligatoires'],
                Response::HTTP_BAD_REQUEST
            );
        }

        // 4) Récupération du Matricule lié à l'utilisateur
        $matricule = $user->getMatricule();

        // 5) Calcul des initiales saisies vs initiales actuelles
        $initialNom      = strtoupper(substr(trim($data['lastName']), 0, 1));
        $initialPrenom   = strtoupper(substr(trim($data['firstName']), 0, 1));
        $initialesSaisies = $initialNom . $initialPrenom;

        // 6) Si le matricule existe, on peut extraire son code et l’année
        if ($matricule instanceof Matricule) {
            $codeExistant = $matricule->getCode();
            // AJEUYYYYIICC… => on extrait YYYY puis II (initiales BDD)
            $annee        = substr($codeExistant, 4, 4);
            $initialesBDD = substr($codeExistant, 8, 2);
        } else {
            // Aucun matricule lié : on force la mise à jour simple de l'utilisateur
            $initialesBDD = $initialesSaisies;
            $annee        = date('Y');
        }

        // 7) Si les initiales ont changé, on doit regénérer un code unique
        if ($initialesSaisies !== $initialesBDD && $matricule instanceof Matricule) {
            $repo = $this->em->getRepository(Matricule::class);
            do {
                $nouveauCode = sprintf(
                    'AJEU%s%s%03d',
                    $annee,
                    $initialesSaisies,
                    random_int(0, 999)
                );
                $exists = (bool) $repo->findOneBy(['code' => $nouveauCode]);
            } while ($exists);

            // Mise à jour du code et des coordonnées sur le Matricule
            $matricule
                ->setCode($nouveauCode)
                ->setNom(trim($data['lastName']))
                ->setPrenom(trim($data['firstName']))
                ->setPhone(trim($data['phone']   ?? $user->getPhone()))
                ->setCommune(trim($data['commune'] ?? $user->getCommune()))
                ->setQuartier(trim($data['quartier'] ?? $user->getQuartier()))
            ;
        }

        // 8) Mise à jour des champs de l'utilisateur
        $user
            ->setFirstName(trim($data['firstName']))
            ->setLastName(trim($data['lastName']))
            ->setPhone(trim($data['phone']   ?? $user->getPhone()))
            ->setCommune(trim($data['commune'] ?? $user->getCommune()))
            ->setQuartier(trim($data['quartier'] ?? $user->getQuartier()))
        ;

        // 9) Si pas de changement d'initiales, on synchronise quand même le Matricule
        if ($initialesSaisies === $initialesBDD && $matricule instanceof Matricule) {
            $matricule
                ->setPhone($user->getPhone())
                ->setCommune($user->getCommune())
                ->setQuartier($user->getQuartier())
            ;
        }

        // 10) Flush unique pour persister User + Matricule
        $this->em->flush();

        // 11) Réponse JSON
        return $this->json([
            'message' => 'Profil mis à jour',
            'user'    => [
                'firstName'  => $user->getFirstName(),
                'lastName'   => $user->getLastName(),
                'email'      => $user->getEmail(),
                'phone'      => $user->getPhone(),
                'commune'    => $user->getCommune(),
                'quartier'   => $user->getQuartier(),
                'avatarPath' => $user->getAvatarPath(),
            ],
        ], Response::HTTP_OK);
    }

    #[Route('/profile/avatar', name: 'profile_avatar', methods: ['POST'])]
    #[IsGranted('IS_AUTHENTICATED_FULLY')]
    public function uploadAvatar(Request $request): JsonResponse
    {
        /** @var \App\Entity\User $user */
        $user = $this->getUser();

        $file = $request->files->get('avatar');
        if (!$file) {
            return $this->json(['message' => 'Fichier manquant'], 400);
        }

        // Vérification du type MIME (optionnel)
        if (!in_array($file->getMimeType(), ['image/jpeg', 'image/png'])) {
            return $this->json(['message' => 'Type de fichier non pris en charge'], 415);
        }

        $uploadsDir = $this->getParameter('kernel.project_dir') . '/public/uploads/avatars';
        $newFilename = sprintf('%s.%s', uniqid('avatar_'), $file->guessExtension());

        try {
            $file->move($uploadsDir, $newFilename);
        } catch (FileException $e) {
            return $this->json(['message' => 'Erreur lors de l’upload'], 500);
        }

        // Met à jour l’utilisateur
        $user->setAvatarPath('/uploads/avatars/' . $newFilename);
        $matricule = $user->getMatricule();
        if (null !== $matricule) {
            $matricule->setAvatarPath('/uploads/avatars/' . $newFilename);
        }
        $this->em->flush();

        return $this->json([
            'avatarUrl' => $this->getParameter('app.base_url') . $user->getAvatarPath()
        ], 200);
    }
}

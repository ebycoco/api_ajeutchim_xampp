<?php
namespace App\Controller\Api;

use App\Entity\Cotisation;
use App\Entity\Matricule;
use App\Repository\MatriculeRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpFoundation\Response;

#[Route('/api/matricules', name: 'api_matricules_')]
class MatriculeController extends AbstractController
{
    public function __construct(
        private EntityManagerInterface $em,
        private MatriculeRepository   $repo
    ){}

    #[Route('', name: 'list', methods: ['GET'])]
    public function list(): JsonResponse
    {
        $data = array_map(fn(Matricule $m)=>[
            'id'             => $m->getId(),
            'code'           => $m->getCode(),
            'nom'            => $m->getNom(),
            'prenom'         => $m->getPrenom(),
            'montantAdhesion'=> $m->getMontantAdhesion(),
            'anneeAdhesion'  => $m->getAnneeAdhesion(),
        ], $this->repo->findAll());

        return $this->json($data);
    }

   #[Route('/create', name: 'matricule_create', methods: ['POST'])]
    public function create(Request $request): JsonResponse
    {
        $d = json_decode($request->getContent(), true);

        // 1) Validation rapide des champs
        foreach (['nom','prenom','montantAdhesion','anneeAdhesion'] as $f) {
            if (empty($d[$f])) {
                return $this->json(['message' => sprintf('%s requis', $f)], Response::HTTP_BAD_REQUEST);
            }
        }

        // 2) Instanciation du Matricule
        $mat = new Matricule();
        $mat
            ->setNom(mb_strtoupper(trim($d['nom'])))
            ->setPrenom(mb_strtoupper(trim($d['prenom'])))
            ->setMontantAdhesion((float) $d['montantAdhesion'])
            ->setAnneeAdhesion((int) $d['anneeAdhesion'])
        ;

        // 3) Génération d’un code unique
        $initialNom    = strtoupper($d['nom'][0]);
        $initialPrenom = strtoupper($d['prenom'][0]);

        do {
            $code = sprintf(
                'AJEU%d%s%s%03d',
                $d['anneeAdhesion'],
                $initialNom,
                $initialPrenom,
                random_int(0, 999)
            );

            $exists = (bool) $this->em
                ->getRepository(Matricule::class)
                ->findOneBy(['code' => $code])
            ;
        } while ($exists);

        $mat->setCode($code);
        $mat->setCreatedAt(new \DateTimeImmutable());
        $this->em->persist($mat);

        // 4) Création de la cotisation liée
        $cot = new Cotisation();
        $cot
            ->setMatricule($mat)                  // <-- liaison ManyToOne
            ->setMontant(0)                       // montant initial
            ->setAnnee((int) $d['anneeAdhesion'])
            ->setCotised(false)
            ->setCreatedAt(new \DateTimeImmutable())
        ;

        $this->em->persist($cot);

        // 5) Flush en base
        $this->em->flush();

        return $this->json([
            'message'     => 'Matricule et cotisation créés',
            'matricule'   => [
                'id'   => $mat->getId(),
                'code' => $mat->getCode(),
            ],
            'cotisation'  => [
                'id'       => $cot->getId(),
                // On n’envoie pas de code de cotisation, on récupère via $cot->getMatricule()->getCode()
            ],
        ], Response::HTTP_CREATED);
    }

    #[Route('/update/{id}', name: 'update', methods: ['PUT'])]
    public function update(int $id, Request $request): JsonResponse
    {
        $m = $this->repo->find($id);
        if (!$m) {
            return $this->json(['message'=>'Non trouvé'], 404);
        }
        $d = json_decode($request->getContent(), true);
        $m->setNom($d['nom'] ?? $m->getNom())
          ->setPrenom($d['prenom'] ?? $m->getPrenom())
          ->setMontantAdhesion($d['montantAdhesion'] ?? $m->getMontantAdhesion())
          ->setAnneeAdhesion($d['anneeAdhesion'] ?? $m->getAnneeAdhesion());
        $this->em->flush();

         $data = array_map(fn(Matricule $m)=>[
            'id'             => $m->getId(),
            'code'           => $m->getCode(),
            'nom'            => $m->getNom(),
            'prenom'         => $m->getPrenom(),
            'montantAdhesion'=> $m->getMontantAdhesion(),
            'anneeAdhesion'  => $m->getAnneeAdhesion(),
        ], $this->repo->findAll());

        return $this->json($data);

    }

    #[Route('/delete/{id}', name: 'delete', methods: ['DELETE'])]
public function delete(int $id): JsonResponse
{
    $m = $this->repo->find($id);
    if ($m) {
        // 1) Supprimer toutes les cotisations associées
        foreach ($m->getCotisations() as $cot) {
            $this->em->remove($cot);
        }

        // 2) Supprimer le matricule
        $this->em->remove($m);
        $this->em->flush();
    }

    // 3) Retourner la liste mise à jour (ou tu peux renvoyer un 204 No Content si tu préfères)
    $data = array_map(fn(Matricule $mat) => [
        'id'             => $mat->getId(),
        'code'           => $mat->getCode(),
        'nom'            => $mat->getNom(),
        'prenom'         => $mat->getPrenom(),
        'montantAdhesion'=> $mat->getMontantAdhesion(),
        'anneeAdhesion'  => $mat->getAnneeAdhesion(),
    ], $this->repo->findAll());

    return $this->json($data);
}

}

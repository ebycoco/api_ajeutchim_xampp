<?php
// src/DataFixtures/MatriculeFixtures.php

namespace App\DataFixtures;

use App\Entity\Matricule;
use App\Entity\Cotisation;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;

class MatriculeFixtures extends Fixture
{
    public function load(ObjectManager $manager): void
    {
        // On crée par exemple 1 matricule
        for ($i = 1; $i <= 1; $i++) {
            $nom     = 'BROU';
            $prenom  = 'YAO ERIC';
            $annee   = 2020;
            $montant = 500;

            // Génération d’un code unique
            $initialNom    = strtoupper($nom[0]);
            $initialPrenom = strtoupper($prenom[0]);
            $code          = sprintf(
                'AJEU%d%s%s%03d',
                $annee,
                $initialNom,
                $initialPrenom,
                random_int(0, 999)
            );

            // Création du Matricule
            $mat = (new Matricule())
                ->setCode($code)
                ->setNom($nom)
                ->setPrenom($prenom)
                ->setMontantAdhesion($montant)
                ->setAnneeAdhesion($annee)
                // on initialise explicitement createdAt
                ->setCreatedAt(new \DateTimeImmutable())
            ;
            $manager->persist($mat);

            // Création de la Cotisation associée
            $cot = (new Cotisation())
                ->setMatricule($mat)          // relation ManyToOne / OneToOne
                ->setMontant(0)               // montant initial
                ->setAnnee($annee)
                ->setCotised(false)
                 // et on initialise son createdAt aussi si nécessaire
                ->setCreatedAt(new \DateTimeImmutable())
            ;
            $manager->persist($cot);
        }

        $manager->flush();
    }
}

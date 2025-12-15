<?php

namespace App\Controller;

use App\Entity\Person;
use App\Entity\Address;
use App\Entity\BankAccount;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class Ex09Controller extends AbstractController
{
    #[Route('/ex09', name: 'ex09_home')]
    public function index(): Response
    {
        return $this->render('ex09/index.html.twig');
    }

    #[Route('/ex09/form', name: 'ex09_form', methods: ['GET', 'POST'])]
    public function form(Request $request, EntityManagerInterface $em): Response
    {
        $errors = [];
        $message = null;

        if ($request->isMethod('POST')) {
            $person = new Person();
            $person->setUsername($request->get('username', ''));
            $person->setName($request->get('name', ''));
            $person->setEmail($request->get('email', ''));
            $person->setEnable((bool)$request->get('enable'));
            $person->setMaritalStatus($request->get('marital_status', ''));

            // Birthdate
            if ($request->get('birthdate')) {
                try {
                    $person->setBirthdate(new \DateTime($request->get('birthdate')));
                } catch (\Exception $e) {
                    $errors[] = "Date invalide pour la naissance.";
                }
            }

            // Addresses
            $addresses = $request->get('addresses', []);
            if (is_array($addresses)) {
                foreach ($addresses as $addrText) {
                    $addrText = trim($addrText);
                    if ($addrText !== '') {
                        $address = new Address();
                        $address->setAddress($addrText);
                        $person->addAddress($address);
                    }
                }
            }

            // Bank account
            $iban = $request->get('iban');
            if ($iban) {
                $bankAccount = new BankAccount();
                $bankAccount->setIban($iban);
                $person->setBankAccount($bankAccount);
            }

            // Persist
            $em->persist($person);
            $em->flush();

            $message = "✅ Données enregistrées via ORM Doctrine.";
        }

        return $this->render('ex09/form.html.twig', [
            'message' => $message,
            'errors' => $errors,
        ]);
    }

    #[Route('/ex09/view', name: 'ex09_view')]
    public function view(\Doctrine\DBAL\Connection $connection): Response
    {
        // Récupérer toutes les tables
        $allTables = $connection->executeQuery('SHOW TABLES')->fetchAllAssociative();
        $allTables = array_map(fn($t) => array_values($t)[0], $allTables);

        // Exclure certaines tables
        $tables = array_filter($allTables, fn($t) => !in_array($t, ['messenger_messages', 'doctrine_migration_versions']));

        $structure = [];
        $data = [];
        $foreignKeys = [];

        foreach ($tables as $table) {
            // Structure de la table
            $structure[$table] = $connection->executeQuery("DESCRIBE `$table`")->fetchAllAssociative();

            // Données de la table
            $data[$table] = $connection->executeQuery("SELECT * FROM `$table`")->fetchAllAssociative();

            // Clés étrangères
            $foreignKeys[$table] = $connection->executeQuery("
                SELECT COLUMN_NAME, REFERENCED_TABLE_NAME, REFERENCED_COLUMN_NAME
                FROM INFORMATION_SCHEMA.KEY_COLUMN_USAGE
                WHERE TABLE_SCHEMA = DATABASE()
                AND TABLE_NAME = '$table'
                AND REFERENCED_TABLE_NAME IS NOT NULL
            ")->fetchAllAssociative();
        }

        return $this->render('ex09/view.html.twig', [
            'tables' => $tables,
            'structure' => $structure,
            'data' => $data,
            'foreignKeys' => $foreignKeys,
        ]);
    }
}
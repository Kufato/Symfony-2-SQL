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
            $username = trim($request->get('username', ''));
            $name = trim($request->get('name', ''));
            $email = trim($request->get('email', ''));
            $enable = (bool)$request->get('enable');
            $birthdateInput = $request->get('birthdate');
            $maritalStatus = $request->get('marital_status', 'single');
            $addressesInput = $request->get('addresses', []);
            $iban = trim($request->get('iban', ''));

            if (!$username) $errors[] = "Le username est requis.";
            if (!$name) $errors[] = "Le nom est requis.";
            if (!$email) $errors[] = "L'email est requis.";

            $repo = $em->getRepository(Person::class);
            if ($username && $repo->findOneBy(['username' => $username])) {
                $errors[] = "Le username existe déjà.";
            }
            if ($name && $repo->findOneBy(['name' => $name])) {
                $errors[] = "Le nom existe déjà.";
            }
            if ($email && $repo->findOneBy(['email' => $email])) {
                $errors[] = "L'email existe déjà.";
            }

            $birthdate = null;
            if ($birthdateInput) {
                try {
                    $birthdate = new \DateTime($birthdateInput);
                    if ($birthdate > new \DateTime('today')) {
                        $errors[] = "La date de naissance doit être antérieure ou égale à aujourd'hui.";
                    }
                } catch (\Exception $e) {
                    $errors[] = "Date de naissance invalide.";
                }
            }

            if ($iban) {
                if (strlen($iban) < 27) {
                    $errors[] = "L'IBAN doit faire au moins 27 caractères.";
                }
                if (substr($iban, 0, 2) !== 'FR') {
                    $errors[] = "L'IBAN doit commencer par 'FR'.";
                }
            }

            if (empty($addressesInput) || !is_array($addressesInput) || trim($addressesInput[0]) === '') {
                $errors[] = "Vous devez ajouter au moins une adresse.";
            }

            if (!empty($errors)) {
                return $this->render('ex09/form.html.twig', [
                    'message' => null,
                    'errors' => $errors,
                    'old' => $request->request->all(),
                ]);
            }

            $person = new Person();
            $person->setUsername($username);
            $person->setName($name);
            $person->setEmail($email);
            $person->setEnable($enable);
            $person->setMaritalStatus($maritalStatus);
            if ($birthdate) $person->setBirthdate($birthdate);

            foreach ($addressesInput as $addrText) {
                $addrText = trim($addrText);
                if ($addrText === '') continue;
                $address = new Address();
                $address->setAddress($addrText);
                $person->addAddress($address);
            }

            if ($iban) {
                $bankAccount = new BankAccount();
                $bankAccount->setIban($iban);
                $person->setBankAccount($bankAccount);
            }

            $em->persist($person);
            $em->flush();

            $message = "✅ Données enregistrées via ORM Doctrine.";
        }

        return $this->render('ex09/form.html.twig', [
            'message' => $message,
            'errors' => $errors,
            'old' => $request->request->all()
        ]);
    }

    #[Route('/ex09/view', name: 'ex09_view')]
    public function view(\Doctrine\DBAL\Connection $connection): Response
    {
        $allTables = $connection->executeQuery('SHOW TABLES')->fetchAllAssociative();
        $allTables = array_map(fn($t) => array_values($t)[0], $allTables);

        $tables = array_filter($allTables, fn($t) => !in_array($t, ['messenger_messages', 'doctrine_migration_versions']));

        $structure = [];
        $data = [];
        $foreignKeys = [];

        foreach ($tables as $table) {
            $structure[$table] = $connection->executeQuery("DESCRIBE `$table`")->fetchAllAssociative();

            $data[$table] = $connection->executeQuery("SELECT * FROM `$table`")->fetchAllAssociative();

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
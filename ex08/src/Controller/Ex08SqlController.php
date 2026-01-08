<?php

namespace App\Controller;

use Doctrine\DBAL\Connection;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class Ex08SqlController extends AbstractController
{
    #[Route('/ex08', name: 'ex08_home')]
    public function index(Connection $connection): Response
    {
        $message = null;

        if (isset($_GET['create_persons'])) {
            try {
                $connection->executeStatement("
                    CREATE TABLE IF NOT EXISTS persons (
                        id INT AUTO_INCREMENT PRIMARY KEY,
                        username VARCHAR(255) NOT NULL,
                        name VARCHAR(255) DEFAULT NULL,
                        email VARCHAR(255) DEFAULT NULL,
                        enable BOOLEAN NOT NULL,
                        birthdate DATETIME DEFAULT NULL,
                        UNIQUE INDEX UNIQ_persons_username (username),
                        UNIQUE INDEX UNIQ_persons_email (email)
                    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
                ");
                $message = "✅ Table persons created";
            } catch (\Exception $e) {
                $message = "❌ Error: " . $e->getMessage();
            }
        }

        if (isset($_GET['add_marital_status'])) {
            try {
                $exists = $connection->fetchOne("
                    SELECT COUNT(*) FROM information_schema.COLUMNS
                    WHERE TABLE_SCHEMA = DATABASE()
                    AND TABLE_NAME = 'persons'
                    AND COLUMN_NAME = 'marital_status'
                ");

                if ($exists == 0) {
                    $connection->executeStatement("
                        ALTER TABLE persons
                        ADD COLUMN marital_status ENUM('single','married','divorced','widowed') DEFAULT 'single'
                    ");
                    $message = "✅ Column marital_status added";
                } else {
                    $message = "ℹ️ Column marital_status already exists";
                }
            } catch (\Exception $e) {
                $message = "❌ Error: " . $e->getMessage();
            }
        }

        if (isset($_GET['create_tables'])) {
            $errors = [];

            try {
                $connection->executeStatement("
                    CREATE TABLE IF NOT EXISTS addresses (
                        id INT AUTO_INCREMENT PRIMARY KEY,
                        person_id INT DEFAULT NULL,
                        address LONGTEXT,
                        INDEX IDX_addresses_person (person_id)
                    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
                ");
            } catch (\Exception $e) {
                $errors[] = $e->getMessage();
            }

            try {
                $connection->executeStatement("
                    CREATE TABLE IF NOT EXISTS bank_accounts (
                        id INT AUTO_INCREMENT PRIMARY KEY,
                        person_id INT DEFAULT NULL,
                        iban VARCHAR(255),
                        INDEX IDX_bank_accounts_person (person_id)
                    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
                ");
            } catch (\Exception $e) {
                $errors[] = $e->getMessage();
            }

            $message = empty($errors)
                ? "✅ Tables addresses & bank_accounts created"
                : "❌ Errors: " . implode(" | ", $errors);
        }

        if (isset($_GET['create_relations'])) {
            $errors = [];

            try {
                $fkExists = $connection->fetchOne("
                    SELECT COUNT(*) FROM information_schema.REFERENTIAL_CONSTRAINTS
                    WHERE CONSTRAINT_SCHEMA = DATABASE()
                    AND CONSTRAINT_NAME = 'FK_addresses_person'
                ");

                if ($fkExists == 0) {
                    $connection->executeStatement("
                        ALTER TABLE addresses
                        ADD CONSTRAINT FK_addresses_person
                        FOREIGN KEY (person_id) REFERENCES persons(id)
                        ON DELETE SET NULL
                    ");
                }
            } catch (\Exception $e) {
                $errors[] = $e->getMessage();
            }

            try {
                $fkExists = $connection->fetchOne("
                    SELECT COUNT(*) FROM information_schema.REFERENTIAL_CONSTRAINTS
                    WHERE CONSTRAINT_SCHEMA = DATABASE()
                    AND CONSTRAINT_NAME = 'FK_bank_accounts_person'
                ");

                if ($fkExists == 0) {
                    $connection->executeStatement("
                        ALTER TABLE bank_accounts
                        ADD CONSTRAINT FK_bank_accounts_person
                        FOREIGN KEY (person_id) REFERENCES persons(id)
                        ON DELETE SET NULL
                    ");
                }
            } catch (\Exception $e) {
                $errors[] = $e->getMessage();
            }

            try {
                $uniqueExists = $connection->fetchOne("
                    SELECT COUNT(*) FROM information_schema.TABLE_CONSTRAINTS
                    WHERE TABLE_SCHEMA = DATABASE()
                    AND TABLE_NAME = 'bank_accounts'
                    AND CONSTRAINT_NAME = 'UNIQ_bank_accounts_person'
                ");

                if ($uniqueExists == 0) {
                    $connection->executeStatement("
                        ALTER TABLE bank_accounts
                        ADD CONSTRAINT UNIQ_bank_accounts_person UNIQUE (person_id)
                    ");
                }
            } catch (\Exception $e) {
                $errors[] = $e->getMessage();
            }

            $message = empty($errors)
                ? "✅ Relations created (1-N for addresses, 1-1 for bank_accounts)"
                : "❌ Errors: " . implode(" | ", $errors);
        }


        return $this->render('index.html.twig', [
            'message' => $message
        ]);
    }

   #[Route('/ex08/form', name: 'ex08_form', methods: ['GET', 'POST'])]
    public function form(Request $request, Connection $connection): Response
    {
        $message = null;
        $errors = [];
        $old = [];

        if ($request->isMethod('POST')) {
            $username = trim($request->request->get('username'));
            $name = trim($request->request->get('name'));
            $email = trim($request->request->get('email'));
            $enable = $request->request->get('enable') ? 1 : 0;
            $birthdate = $request->request->get('birthdate');
            $marital = $request->request->get('marital_status', 'single');
            $iban = trim($request->request->get('iban'));
            $addresses = $request->request->all('addresses');
            $old = $request->request->all();

            if (empty($addresses) || trim($addresses[0]) === '') {
                $errors[] = "Vous devez ajouter au moins une adresse.";
            }

            if ($connection->fetchOne("SELECT COUNT(*) FROM persons WHERE username = ?", [$username]) > 0) {
                $errors[] = "Le username existe déjà.";
            }

            if ($connection->fetchOne("SELECT COUNT(*) FROM persons WHERE name = ?", [$name]) > 0) {
                $errors[] = "Le nom existe déjà.";
            }

            if ($connection->fetchOne("SELECT COUNT(*) FROM persons WHERE email = ?", [$email]) > 0) {
                $errors[] = "L'email existe déjà.";
            }

            if (!empty($birthdate)) {
                $birthDateObj = \DateTime::createFromFormat('Y-m-d', $birthdate);
                if (!$birthDateObj) {
                    $errors[] = "La date de naissance est invalide.";
                } elseif ($birthDateObj > new \DateTime('today')) {
                    $errors[] = "La date de naissance doit être antérieure ou égale à aujourd'hui.";
                }
            }

            if ($iban) {
                if (strlen($iban) < 27) {
                    $errors[] = "L'IBAN doit faire au moins 27 caractères.";
                }
                if (substr($iban, 0, 2) !== "FR") {
                    $errors[] = "L'IBAN doit commencer par 'FR'.";
                }
                if ($connection->fetchOne("SELECT COUNT(*) FROM bank_accounts WHERE iban = ?", [$iban]) > 0) {
                    $errors[] = "Cet IBAN est déjà utilisé.";
                }
            }

            if (!empty($errors)) {
                return $this->render('form.html.twig', [
                    'errors' => $errors,
                    'old' => $old
                ]);
            }

            $connection->executeStatement("
                INSERT INTO persons (username, name, email, enable, birthdate, marital_status)
                VALUES (?, ?, ?, ?, ?, ?)
            ", [$username, $name, $email, $enable, $birthdate, $marital]);
            $personId = $connection->lastInsertId();

            foreach ($addresses as $addr) {
                if (trim($addr) === '') continue;

                $connection->executeStatement("
                    INSERT INTO addresses (person_id, address)
                    VALUES (?, ?)
                ", [$personId, $addr]);
            }

            if ($iban) {
                $connection->executeStatement("
                    INSERT INTO bank_accounts (person_id, iban)
                    VALUES (?, ?)
                ", [$personId, $iban]);
            }

            $message = "✅ Données insérées avec succès.";
            $old = [];
        }

        return $this->render('form.html.twig', [
            'message' => $message,
            'errors' => $errors,
            'old' => $old
        ]);
    }

    #[Route('/ex08/view', name: 'ex08_view')]
    public function view(Connection $connection): Response
    {
        $tables = $connection->fetchFirstColumn("SHOW TABLES");
        $database = $connection->fetchOne("SELECT DATABASE()");

        $structure = [];
        $foreignKeys = [];
        $data = [];

        foreach ($tables as $table) {
            $structure[$table] = $connection->fetchAllAssociative("SHOW COLUMNS FROM `$table`");
            $foreignKeys[$table] = $connection->fetchAllAssociative("
                SELECT COLUMN_NAME, REFERENCED_TABLE_NAME, REFERENCED_COLUMN_NAME
                FROM information_schema.KEY_COLUMN_USAGE
                WHERE TABLE_SCHEMA = ?
                AND TABLE_NAME = ?
                AND REFERENCED_TABLE_NAME IS NOT NULL
            ", [$database, $table]);
            $data[$table] = $connection->fetchAllAssociative("SELECT * FROM `$table`");
        }

        return $this->render('view.html.twig', [
            'tables' => $tables,
            'structure' => $structure,
            'foreignKeys' => $foreignKeys,
            'data' => $data
        ]);
    }
}
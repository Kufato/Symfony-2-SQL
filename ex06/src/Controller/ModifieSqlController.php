<?php

namespace App\Controller;

use Doctrine\DBAL\Connection;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class ModifieSqlController extends AbstractController
{
    #[Route('/ex06', name: 'ex06_home')]
    public function index(Connection $connection): Response
    {
        $message = null;

        if (isset($_GET['create'])) {
            try {
                $connection->executeStatement('
                    CREATE TABLE IF NOT EXISTS user (
                        id INT AUTO_INCREMENT PRIMARY KEY,
                        name VARCHAR(100) NOT NULL,
                        email VARCHAR(150) NOT NULL
                    )
                ');
                $message = "✅ Table 'user' créée (ou déjà existante)";
            } catch (\Exception $e) {
                $message = "❌ Erreur : " . $e->getMessage();
            }
        }

        return $this->render('index.html.twig', [
            'message' => $message
        ]);
    }

    #[Route('/ex06/form', name: 'ex06_form', methods: ['GET', 'POST'])]
    public function add(Request $request, Connection $connection): Response
    {
        $message = null;

        if ($request->isMethod('POST')) {
            $name = trim($request->request->get('name'));
            $email = trim($request->request->get('email'));

            if ($name && $email) {
                $existing = $connection->fetchOne('SELECT COUNT(*) FROM user WHERE name = ? OR email = ?', [$name, $email]);
                if ($existing > 0) {
                    $message = "⚠️ Nom ou email déjà utilisé.";
                } else {
                    $connection->executeStatement('INSERT INTO user (name, email) VALUES (?, ?)', [$name, $email]);
                    $message = "✅ Utilisateur ajouté.";
                }
            } else {
                $message = "⚠️ Remplis tous les champs.";
            }
        }

        return $this->render('form.html.twig', [
            'message' => $message
        ]);
    }

    #[Route('/ex06/list', name: 'ex06_list')]
    public function list(Connection $connection): Response
    {
        $users = $connection->fetchAllAssociative('SELECT * FROM user ORDER BY id DESC');
        return $this->render('list.html.twig', [
            'users' => $users,
            'message' => $_GET['msg'] ?? null
        ]);
    }

    #[Route('/ex06/edit/{id}', name: 'ex06_edit', methods: ['GET', 'POST'])]
    public function edit(int $id, Request $request, Connection $connection): Response
    {
        $user = $connection->fetchAssociative('SELECT * FROM user WHERE id = ?', [$id]);

        if (!$user) {
            return $this->redirectToRoute('ex06_list', ['msg' => '❌ Utilisateur introuvable.']);
        }

        $message = null;

        if ($request->isMethod('POST')) {
            $newName = trim($request->request->get('name'));
            $newEmail = trim($request->request->get('email'));

            if (empty($newName) || strlen($newName) > 100) {
                $message = "⚠️ Le nom est invalide.";
            } elseif (empty($newEmail) || strlen($newEmail) > 150 || !filter_var($newEmail, FILTER_VALIDATE_EMAIL)) {
                $message = "⚠️ L'email est invalide.";
            } else {
                try {
                    $exists = $connection->fetchOne('SELECT COUNT(*) FROM user WHERE (name = ? OR email = ?) AND id != ?', [$newName, $newEmail, $id]);
                    if ($exists > 0) {
                        $message = "⚠️ Ce nom ou email est déjà pris.";
                    } else {
                        $connection->executeStatement('UPDATE user SET name = ?, email = ? WHERE id = ?', [$newName, $newEmail, $id]);
                        return $this->redirectToRoute('ex06_list', ['msg' => '✅ Utilisateur mis à jour.']);
                    }
                } catch (\Exception $e) {
                    $message = "❌ Erreur : " . $e->getMessage();
                }
            }
        }

        return $this->render('edit.html.twig', [
            'user' => $user,
            'message' => $message
        ]);
    }
}

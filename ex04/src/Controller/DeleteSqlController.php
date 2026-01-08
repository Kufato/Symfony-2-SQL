<?php
namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Doctrine\DBAL\Connection;

class DeleteSqlController extends AbstractController
{
    #[Route('/ex04', name: 'ex04_home')]
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

    #[Route('/ex04/form', name: 'ex04_form', methods: ['GET', 'POST'])]
    public function add(Request $request, Connection $connection): Response
    {
        $message = null;

        if ($request->isMethod('POST')) {
            $name = $request->request->get('name');
            $email = $request->request->get('email');

            if ($name && $email) {

                $exists = $connection->fetchOne(
                    'SELECT COUNT(*) FROM user WHERE name = ? OR email = ?',
                    [$name, $email]
                );

                if ($exists) {
                    $message = "⚠️ Le nom ou l'email existe déjà, veuillez en choisir un autre.";
                } else {
                    try {
                        $connection->executeStatement(
                            'INSERT INTO user (name, email) VALUES (?, ?)',
                            [$name, $email]
                        );
                        $message = "✅ Utilisateur ajouté avec succès.";
                    } catch (\Exception $e) {
                        $message = "❌ Erreur : " . $e->getMessage();
                    }
                }

            } else {
                $message = "⚠️ Merci de remplir tous les champs.";
            }
        }

        return $this->render('form.html.twig', [
            'message' => $message
        ]);
    }

    #[Route('/ex04/list', name: 'ex04_list')]
    public function list(Connection $connection): Response
    {
        try {
            $users = $connection->fetchAllAssociative('SELECT * FROM user');
        } catch (\Exception $e) {
            $users = [];
            $error = "❌ Erreur : " . $e->getMessage();
        }

        return $this->render('list.html.twig', [
            'users' => $users ?? [],
            'error' => $error ?? null,
        ]);
    }

    #[Route('/ex04/delete/{id}', name: 'ex04_delete')]
    public function delete(string $id, Connection $connection): Response
    {
        if (!ctype_digit($id)) {
            $this->addFlash('error', "⚠️ L'identifiant doit être un nombre entier.");
            return $this->redirectToRoute('ex04_list');
        }

        $id = (int) $id;
        $message = null;

        try {
            $user = $connection->fetchAssociative('SELECT * FROM user WHERE id = ?', [$id]);

            if (!$user) {
                $message = "⚠️ L'utilisateur avec l'ID $id n'existe pas.";
            } else {
                $connection->executeStatement('DELETE FROM user WHERE id = ?', [$id]);
                $message = "✅ Utilisateur ID $id supprimé avec succès.";
            }
        } catch (\Exception $e) {
            $message = "❌ Erreur : " . $e->getMessage();
        }

        $this->addFlash('result', $message);
        return $this->redirectToRoute('ex04_list');
    }
}

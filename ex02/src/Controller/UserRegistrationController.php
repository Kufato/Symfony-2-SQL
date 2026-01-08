<?php

namespace App\Controller;

use Doctrine\DBAL\Connection;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use App\Form\UserType;

class UserRegistrationController extends AbstractController
{
    #[Route('/ex02', name: 'create_table')]
    public function create_table(Request $request, Connection $connection): Response
    {
        $message = null;

        if ($request->isMethod('POST') && $request->request->has('create')) {
            $sql = "
                CREATE TABLE IF NOT EXISTS user (
                    id INT AUTO_INCREMENT PRIMARY KEY,
                    username VARCHAR(255) UNIQUE NOT NULL,
                    name VARCHAR(255) NOT NULL,
                    email VARCHAR(255) UNIQUE NOT NULL,
                    enable BOOLEAN NOT NULL,
                    birthdate DATETIME NOT NULL,
                    address LONGTEXT NOT NULL
                )
            ";
            try {
                $connection->executeStatement($sql);
                $message = "✅ Table 'user' créée avec succès (ou déjà existante).";
            } catch (\Exception $e) {
                $message = "❌ Erreur : " . $e->getMessage();
            }
        }

        return $this->render('create_table_sql.html.twig', [
            'message' => $message,
        ]);
    }

    #[Route('/ex02/form', name: 'user_form')]
    public function user_form(Request $request, Connection $connection): Response
    {
        $form = $this->createForm(UserType::class);
        $form->handleRequest($request);
        $message = null;

        if ($form->isSubmitted() && $form->isValid()) {
            $data = $form->getData();

            $exists = $connection->fetchOne(
                'SELECT COUNT(*) FROM user WHERE username = ? OR email = ?',
                [$data['username'], $data['email']]
            );

            if ($exists) {
                $message = "⚠️ Cet utilisateur existe déjà (username ou email déjà utilisé).";
            } else {
                try {
                    $connection->insert('user', [
                        'username' => $data['username'],
                        'name' => $data['name'],
                        'email' => $data['email'],
                        'enable' => $data['enable'] ? 1 : 0,
                        'birthdate' => $data['birthdate']->format('Y-m-d H:i:s'),
                        'address' => $data['address'],
                    ]);

                    $message = "✅ Utilisateur ajouté avec succès.";

                    $form = $this->createForm(UserType::class);

                } catch (\Exception $e) {
                    $message = "❌ Erreur lors de l'insertion : " . $e->getMessage();
                }
            }
        }

        return $this->render('user_form.html.twig', [
            'form' => $form->createView(),
            'message' => $message,
        ]);
    }

    #[Route('/ex02/list', name: 'user_list')]
    public function user_list(Connection $connection): Response
    {
        try {
            $users = $connection->fetchAllAssociative('SELECT * FROM user');
        } catch (\Exception $e) {
            $users = [];
        }

        return $this->render('user_list.html.twig', [
            'users' => $users,
        ]);
    }
}

<?php

namespace App\Controller;

use Doctrine\DBAL\Connection;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class CreateTableSqlController extends AbstractController
{
    #[Route('/ex00', name: 'create_table')]
    public function create_table(Request $request, Connection $connection): Response
    {
        $message = null;

        if ($request->isMethod('POST')) {

            if ($request->request->has('create')) {
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
                    $message = "❌ Erreur lors de la création de la table : " . $e->getMessage();
                }
            }

            if ($request->request->has('delete')) {
                $sql = "DROP TABLE IF EXISTS user";
                try {
                    $connection->executeStatement($sql);
                    $message = "🗑️ Table 'user' supprimée avec succès.";
                } catch (\Exception $e) {
                    $message = "❌ Erreur lors de la suppression de la table : " . $e->getMessage();
                }
            }
        }

        return $this->render('create_table_sql.html.twig', [
            'message' => $message,
        ]);
    }
}
<?php

namespace App\Controller;

use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Tools\SchemaTool;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class CreateTableOrmController extends AbstractController
{
    #[Route('/ex01', name: 'create_table_orm')]
    public function create_table(Request $request, EntityManagerInterface $em): Response
    {
        $message = null;

        if ($request->isMethod('POST')) {

            $connection = $em->getConnection();
            $schemaManager = $connection->createSchemaManager();
            $tableName = 'user';

            $schemaTool = new SchemaTool($em);
            $metadata = [$em->getClassMetadata(User::class)];

            if ($request->request->has('create')) {

                if ($schemaManager->tablesExist([$tableName])) {
                    $message = "ℹ️ La table '$tableName' existe déjà.";
                } else {
                    try {
                        $schemaTool->createSchema($metadata);
                        $message = "✅ Table '$tableName' créée avec succès.";
                    } catch (\Exception $e) {
                        $message = "❌ Erreur : " . $e->getMessage();
                    }
                }
            }

            if ($request->request->has('delete')) {

                if (!$schemaManager->tablesExist([$tableName])) {
                    $message = "ℹ️ La table '$tableName' n'existe pas.";
                } else {
                    try {
                        $schemaTool->dropSchema($metadata);
                        $message = "🗑️ Table '$tableName' supprimée avec succès.";
                    } catch (\Exception $e) {
                        $message = "❌ Erreur : " . $e->getMessage();
                    }
                }
            }
        }

        return $this->render('create_table_orm.html.twig', [
            'message' => $message,
        ]);
    }
}

<?php

namespace App\Controller;

use App\Entity\User;
use Doctrine\ORM\Tools\SchemaTool;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class CreateTableOrmController extends AbstractController
{
    #[Route('/ex01', name: 'create_table')]
    public function create_table(Request $request, EntityManagerInterface $em): Response
    {
        $message = null;

        if ($request->isMethod('POST'))
        {
            $schemaTool = new SchemaTool($em);
            $metadata = $em->getClassMetadata(User::class);

            if ($request->request->has('create'))
            {
                try {
                    $schemaTool->createSchema([$metadata]);
                    $message = "✅ Table 'user' créée avec succès (ou déjà existante).";
                } catch (\Exception $e) {
                    $message = "❌ Erreur : " . $e->getMessage();
                }
            }

            if ($request->request->has('delete'))
            {
                try {
                    $schemaTool->dropSchema([$metadata]);
                    $message = "🗑️ Table 'user' supprimée avec succès.";
                } catch (\Exception $e) {
                    $message = "❌ Erreur : " . $e->getMessage();
                }
            }
        }

        return $this->render('create_table_orm.html.twig', [
            'message' => $message,
        ]);
    }
}

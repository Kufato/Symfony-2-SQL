<?php

namespace App\Controller;

use App\Entity\User;
use App\Form\UserType;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Tools\SchemaTool;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class UserOrmController extends AbstractController
{
    #[Route('/ex03', name: 'create_table_orm')]
    public function create_table(Request $request, EntityManagerInterface $em): Response
    {
        $message = null;
        $schemaTool = new SchemaTool($em);
        $metadata = $em->getClassMetadata(User::class);

        $tableName = $metadata->getTableName();

        $connection = $em->getConnection();
        $schemaManager = $connection->createSchemaManager();
        $tables = $schemaManager->listTableNames();

        if ($request->isMethod('POST') && $request->request->has('create')) {
            if (in_array($tableName, $tables)) {
                $message = "ℹ️ La table '$tableName' existe déjà — aucune action nécessaire.";
            } else {
                try {
                    $schemaTool->createSchema([$metadata]);
                    $message = "✅ Table '$tableName' créée avec succès.";
                } catch (\Exception $e) {
                    $message = "❌ Erreur lors de la création : " . $e->getMessage();
                }
            }
        }

        return $this->render('create_table_orm.html.twig', [
            'message' => $message,
        ]);
    }

    #[Route('/ex03/form', name: 'user_form')]
    public function form(Request $request, EntityManagerInterface $em, UserRepository $repo): Response
    {
        $user = new User();
        $form = $this->createForm(UserType::class, $user);
        $form->handleRequest($request);

        $message = null;

        if ($form->isSubmitted() && $form->isValid()) {
            $exists = $repo->findOneBy(['username' => $user->getUsername()])
                ?? $repo->findOneBy(['email' => $user->getEmail()]);

            if ($exists) {
                $message = "⚠️ Cet utilisateur existe déjà (username ou email déjà utilisé).";
            } else {
                try {
                    $em->persist($user);
                    $em->flush();
                    $message = "✅ Utilisateur ajouté avec succès.";
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

    #[Route('/ex03/list', name: 'user_list')]
    public function list(UserRepository $repo): Response
    {
        $users = $repo->findAll();
        return $this->render('user_list.html.twig', ['users' => $users]);
    }
}
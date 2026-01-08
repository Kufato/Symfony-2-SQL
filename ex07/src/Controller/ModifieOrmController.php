<?php

namespace App\Controller;

use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class ModifieOrmController extends AbstractController
{
    #[Route('/ex07', name: 'ex07_home')]
    public function index(): Response
    {
        return $this->render('index.html.twig');
    }

    #[Route('/ex07/form', name: 'ex07_form', methods: ['GET', 'POST'])]
    public function add(Request $request, EntityManagerInterface $em): Response
    {
        $message = null;

        if ($request->isMethod('POST')) {
            $name = trim($request->request->get('name'));
            $email = trim($request->request->get('email'));

            if ($name && $email) {
                $repo = $em->getRepository(User::class);
                $existing = $repo->findOneBy(['name' => $name]) ?? $repo->findOneBy(['email' => $email]);

                if ($existing) {
                    $message = "⚠️ Nom ou email déjà utilisé.";
                } else {
                    try {
                        $user = new User();
                        $user->setName($name);
                        $user->setEmail($email);
                        $em->persist($user);
                        $em->flush();

                        $this->addFlash('result', '✅ Utilisateur ajouté.');
                        return $this->redirectToRoute('ex07_form');
                    } catch (\Exception $e) {
                        $message = "❌ Erreur : " . $e->getMessage();
                    }
                }
            } else {
                $message = "⚠️ Remplis tous les champs.";
            }
        }

        return $this->render('form.html.twig', [
            'message' => $message
        ]);
    }

    #[Route('/ex07/list', name: 'ex07_list')]
    public function list(EntityManagerInterface $em): Response
    {
        $users = $em->getRepository(User::class)->findBy([], ['id' => 'DESC']);
        return $this->render('list.html.twig', [
            'users' => $users
        ]);
    }

    #[Route('/ex07/edit/{id}', name: 'ex07_edit', methods: ['GET', 'POST'])]
    public function edit(string $id, Request $request, EntityManagerInterface $em): Response
    {
        if (!ctype_digit($id)) {
            $this->addFlash('error', "⚠️ L'identifiant doit être un nombre entier.");
            return $this->redirectToRoute('ex07_list');
        }
        $id = (int) $id;

        $user = $em->getRepository(User::class)->find($id);

        if (!$user) {
            $this->addFlash('error', '❌ Utilisateur introuvable.');
            return $this->redirectToRoute('ex07_list');
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
                $repo = $em->getRepository(User::class);

                $qb = $repo->createQueryBuilder('u')
                    ->select('COUNT(u.id)')
                    ->where('(u.name = :name OR u.email = :email)')
                    ->andWhere('u.id != :id')
                    ->setParameter('name', $newName)
                    ->setParameter('email', $newEmail)
                    ->setParameter('id', $id);

                $exists = $qb->getQuery()->getSingleScalarResult();

                if ($exists > 0) {
                    $message = "⚠️ Ce nom ou email est déjà pris.";
                } else {
                    try {
                        $user->setName($newName);
                        $user->setEmail($newEmail);
                        $em->flush();

                        $this->addFlash('result', '✅ Utilisateur mis à jour.');
                        return $this->redirectToRoute('ex07_list');
                    } catch (\Exception $e) {
                        $message = "❌ Erreur : " . $e->getMessage();
                    }
                }
            }
        }

        return $this->render('edit.html.twig', [
            'user' => $user,
            'message' => $message
        ]);
    }
}
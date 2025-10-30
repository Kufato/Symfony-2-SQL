<?php
namespace App\Controller;

use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class DeleteOrmController extends AbstractController
{
    #[Route('/ex05', name: 'ex05_home')]
    public function index(): Response
    {
        return $this->render('index.html.twig');
    }

    #[Route('/ex05/form', name: 'ex05_form', methods: ['GET','POST'])]
    public function add(Request $request, EntityManagerInterface $em): Response
    {
        $message = null;

        if ($request->isMethod('POST')) {
            $name = $request->request->get('name');
            $email = $request->request->get('email');

            if ($name && $email) {
                try {
                    $existing = $em->getRepository(User::class)->findOneBy(['name' => $name]);
                    $existingEmail = $em->getRepository(User::class)->findOneBy(['email' => $email]);

                    if ($existing && $existingEmail) {
                        $message = "⚠️ L'utilisateur existe déjà.";
                    } elseif ($existingEmail) {
                        $message = "⚠️ L'email '$email' existe déjà.";
                    } elseif ($existing) {
                        $message = "⚠️ Le nom '$name' existe déjà.";
                    } else {
                        $user = new User();
                        $user->setName($name);
                        $user->setEmail($email);
                        $em->persist($user);
                        $em->flush();

                        $message = "✅ Utilisateur ajouté avec succès.";
                    }
                } catch (\Exception $e) {
                    $message = "❌ Erreur : " . $e->getMessage();
                }
            } else {
                $message = "⚠️ Merci de remplir tous les champs.";
            }
        }

        return $this->render('form.html.twig', ['message' => $message]);
    }

    #[Route('/ex05/list', name: 'ex05_list')]
    public function list(EntityManagerInterface $em): Response
    {
        $users = $em->getRepository(User::class)->findAll();

        return $this->render('list.html.twig', [
            'users' => $users
        ]);
    }

    #[Route('/ex05/delete/{id}', name: 'ex05_delete')]
    public function delete(int $id, EntityManagerInterface $em): Response
    {
        $user = $em->getRepository(User::class)->find($id);

        if (!$user) {
            $this->addFlash('result', "⚠️ L'utilisateur avec l'ID $id n'existe pas.");
        } else {
            try {
                $em->remove($user);
                $em->flush();
                $this->addFlash('result', "✅ Utilisateur ID $id supprimé avec succès.");
            } catch (\Exception $e) {
                $this->addFlash('result', "❌ Erreur : ".$e->getMessage());
            }
        }

        return $this->redirectToRoute('ex05_list');
    }
}

<?php

namespace App\Controller;

use App\Entity\Employee;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class Ex13Controller extends AbstractController
{
    #[Route('/ex13', name: 'ex13_home')]
    public function index(EntityManagerInterface $em): Response
    {
        $employees = $em->getRepository(Employee::class)->findAll();

        return $this->render('ex13/index.html.twig', [
            'employees' => $employees
        ]);
    }

   #[Route('/ex13/add', name: 'ex13_add')]
    public function add(Request $request, EntityManagerInterface $em): Response
    {
        $message = '';

        $employeeRepo = $em->getRepository(Employee::class);
        $employees = $employeeRepo->findAll();
        $ceo = $employeeRepo->findOneBy(['position' => 'ceo']);

        $isFirstEmployee = count($employees) === 0;

        if ($request->isMethod('POST')) {
            try {
                $position = $request->request->get('position');

                // ✅ Premier employé = CEO obligatoire
                if ($isFirstEmployee && $position !== 'ceo') {
                    throw new \Exception('Le premier employé doit être le CEO');
                }

                // ❌ Un seul CEO autorisé
                if (!$isFirstEmployee && $position === 'ceo') {
                    throw new \Exception('Il ne peut y avoir qu’un seul CEO');
                }

                $employee = new Employee();
                $employee->setFirstname($request->request->get('firstname'));
                $employee->setLastname($request->request->get('lastname'));
                $employee->setEmail($request->request->get('email'));
                $employee->setBirthdate(new \DateTime($request->request->get('birthdate')));
                $employee->setEmployedSince(new \DateTime($request->request->get('employed_since')));
                $employee->setEmployedUntil(
                    $request->request->get('employed_until')
                        ? new \DateTime($request->request->get('employed_until'))
                        : null
                );
                $employee->setHours($request->request->get('hours'));
                $employee->setSalary((int)$request->request->get('salary'));
                $employee->setPosition($position);
                $employee->setActive($request->request->get('active') === '1');

                // 👑 CEO sans manager
                if ($position === 'ceo') {
                    $employee->setManager(null);
                } else {
                    $managerId = $request->request->get('manager_id');
                    $manager = $employeeRepo->find($managerId);

                    if (!$manager) {
                        throw new \Exception('Un manager valide est obligatoire');
                    }

                    $employee->setManager($manager);
                }

                $em->persist($employee);
                $em->flush();

                $message = '✅ Employé ajouté avec succès';

            } catch (\Exception $e) {
                $message = '❌ ' . $e->getMessage();
            }
        }

        return $this->render('ex13/add.html.twig', [
            'employees' => $employees,
            'isFirstEmployee' => $isFirstEmployee,
            'message' => $message
        ]);
    }

    #[Route('/ex13/edit/{id}', name: 'ex13_edit')]
    public function edit(
        ?Employee $employee,
        Request $request,
        EntityManagerInterface $em
    ): Response {
        if (!$employee) {
            $this->addFlash('error', 'Employé introuvable');
            return $this->redirectToRoute('ex13_home');
        }

        $message = '';

        $repo = $em->getRepository(Employee::class);
        $employees = $repo->findAll();

        if ($request->isMethod('POST')) {
            try {
                $position = $request->request->get('position');

                // 🔒 Le CEO reste CEO
                if ($employee->getPosition() === 'ceo' && $position !== 'ceo') {
                    throw new \Exception('Le CEO ne peut pas changer de poste');
                }

                // 🔒 Un seul CEO
                if ($employee->getPosition() !== 'ceo' && $position === 'ceo') {
                    throw new \Exception('Il existe déjà un CEO');
                }

                $employee
                    ->setFirstname($request->request->get('firstname'))
                    ->setLastname($request->request->get('lastname'))
                    ->setEmail($request->request->get('email'))
                    ->setBirthdate(new \DateTime($request->request->get('birthdate')))
                    ->setEmployedSince(new \DateTime($request->request->get('employed_since')))
                    ->setEmployedUntil(
                        $request->request->get('employed_until')
                            ? new \DateTime($request->request->get('employed_until'))
                            : null
                    )
                    ->setHours($request->request->get('hours'))
                    ->setSalary((int)$request->request->get('salary'))
                    ->setActive($request->request->get('active') === '1');

                // 👑 Cas CEO
                if ($employee->getPosition() === 'ceo') {
                    $employee->setPosition('ceo');
                    $employee->setManager(null);
                } else {
                    $employee->setPosition($position);

                    $managerId = $request->request->get('manager_id');
                    $manager = $repo->find($managerId);

                    if (!$manager) {
                        throw new \Exception('Manager invalide');
                    }

                    if ($manager->getId() === $employee->getId()) {
                        throw new \Exception('Un employé ne peut pas être son propre manager');
                    }

                    $employee->setManager($manager);
                }

                $em->flush();
                $message = '✅ Employé modifié avec succès';

            } catch (\Exception $e) {
                $message = '❌ ' . $e->getMessage();
            }
        }

        return $this->render('ex13/edit.html.twig', [
            'employee' => $employee,
            'employees' => $employees,
            'message' => $message
        ]);
    }


    #[Route('/ex13/delete/{id}', name: 'ex13_delete')]
    public function delete(?Employee $employee, EntityManagerInterface $em): Response
    {
        if (!$employee) {
            $this->addFlash('error', 'Employé introuvable');
            return $this->redirectToRoute('ex13_home');
        }

        $em->remove($employee);
        $em->flush();

        $this->addFlash('success', 'Employé supprimé');
        return $this->redirectToRoute('ex13_home');
    }
}
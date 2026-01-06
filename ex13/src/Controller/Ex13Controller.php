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
                $firstname = $request->request->get('firstname');
                $lastname = $request->request->get('lastname');
                $position = $request->request->get('position');
                $birthdate = new \DateTime($request->request->get('birthdate'));
                $employedSince = new \DateTime($request->request->get('employed_since'));
                $employedUntil = $request->request->get('employed_until')
                    ? new \DateTime($request->request->get('employed_until'))
                    : null;

                $today = new \DateTime('today');

                // 🔒 Vérification unicité prénom + nom
                $existing = $employeeRepo->findOneBy(['firstname' => $firstname, 'lastname' => $lastname]);
                if ($existing) {
                    throw new \Exception('Un employé avec ce prénom et ce nom existe déjà');
                }

                // 🔒 Premier employé = CEO obligatoire
                if ($isFirstEmployee && $position !== 'ceo') {
                    throw new \Exception('Le premier employé doit être le CEO');
                }

                // 🔒 Un seul CEO
                if (!$isFirstEmployee && $position === 'ceo') {
                    throw new \Exception('Il ne peut y avoir qu’un seul CEO');
                }

                // 🔒 Dates cohérentes
                if ($birthdate > $today) {
                    throw new \Exception('La date de naissance ne peut pas être dans le futur');
                }
                if ($birthdate >= $employedSince) {
                    throw new \Exception('La date de naissance doit être antérieure à la date d’embauche');
                }
                if ($employedUntil && $employedUntil < $employedSince) {
                    throw new \Exception('La date de fin doit être postérieure ou égale à la date de début');
                }

                // Création de l'employé
                $employee = new Employee();
                $employee
                    ->setFirstname($firstname)
                    ->setLastname($lastname)
                    ->setEmail($request->request->get('email'))
                    ->setBirthdate($birthdate)
                    ->setEmployedSince($employedSince)
                    ->setEmployedUntil($employedUntil)
                    ->setHours($request->request->get('hours'))
                    ->setSalary((int)$request->request->get('salary'))
                    ->setPosition($position)
                    ->setActive($request->request->get('active') === '1');

                // Gestion du manager
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

                // 🔄 Recharge pour mise à jour du template
                $employees = $employeeRepo->findAll();
                $ceo = $employeeRepo->findOneBy(['position' => 'ceo']);
                $isFirstEmployee = count($employees) === 1;

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
    public function edit(?Employee $employee, Request $request, EntityManagerInterface $em): Response
    {
        if (!$employee) {
            $this->addFlash('error', 'Employé introuvable');
            return $this->redirectToRoute('ex13_home');
        }

        $message = '';
        $repo = $em->getRepository(Employee::class);
        $employees = $repo->findAll();

        if ($request->isMethod('POST')) {
            try {
                $firstname = $request->request->get('firstname');
                $lastname = $request->request->get('lastname');
                $position = $request->request->get('position');
                $employedSince = new \DateTime($request->request->get('employed_since'));
                $employedUntil = $request->request->get('employed_until')
                    ? new \DateTime($request->request->get('employed_until'))
                    : null;

                $today = new \DateTime('today');
                $birthdate = $employee->getBirthdate();

                // 🔒 Unicité prénom + nom (sauf si c'est le même employé)
                $existing = $repo->findOneBy(['firstname' => $firstname, 'lastname' => $lastname]);
                if ($existing && $existing->getId() !== $employee->getId()) {
                    throw new \Exception('Un employé avec ce prénom et ce nom existe déjà');
                }

                // 🔒 Birthdate et dates cohérentes
                if ($birthdate > $today) {
                    throw new \Exception('La date de naissance est invalide');
                }
                if ($birthdate >= $employedSince) {
                    throw new \Exception('La date de naissance doit être antérieure à la date d’embauche');
                }
                if ($employedUntil && $employedUntil < $employedSince) {
                    throw new \Exception('La date de fin doit être postérieure ou égale à la date de début');
                }

                // 🔒 Le CEO reste CEO
                if ($employee->getPosition() === 'ceo' && $position !== 'ceo') {
                    throw new \Exception('Le CEO ne peut pas changer de poste');
                }
                if ($employee->getPosition() !== 'ceo' && $position === 'ceo') {
                    throw new \Exception('Il existe déjà un CEO');
                }

                // Mise à jour
                $employee
                    ->setFirstname($firstname)
                    ->setLastname($lastname)
                    ->setEmail($request->request->get('email'))
                    ->setEmployedSince($employedSince)
                    ->setEmployedUntil($employedUntil)
                    ->setHours($request->request->get('hours'))
                    ->setSalary((int)$request->request->get('salary'))
                    ->setActive($request->request->get('active') === '1');

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

        if ($employee->getPosition() === 'ceo') {
            $this->addFlash('error', 'Impossible de supprimer le CEO');
            return $this->redirectToRoute('ex13_home');
        }

        $em->remove($employee);
        $em->flush();

        $this->addFlash('success', 'Employé supprimé');
        return $this->redirectToRoute('ex13_home');
    }
}
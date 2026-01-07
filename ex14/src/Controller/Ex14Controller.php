<?php


namespace App\Controller;


use Doctrine\DBAL\Connection;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;


class Ex14Controller extends AbstractController
{
    #[Route('/ex14', name: 'ex14_home')]
    public function index(Connection $conn): Response
    {
        $tableExists = false;
        $tables = $conn->fetchAllAssociative('SHOW TABLES LIKE "users_vulnerable"');
        if (count($tables) > 0) {
            $tableExists = true;
        }

        $users = [];
        if ($tableExists) {
            $users = $conn->fetchAllAssociative('SELECT * FROM users_vulnerable');
        }

        return $this->render('ex14/index.html.twig', [
        'tableExists' => $tableExists,
        'users' => $users
        ]);
    }


    #[Route('/ex14/create-table', name: 'ex14_create_table')]
    public function createTable(Connection $conn): Response
    {
        $sql = "
        CREATE TABLE IF NOT EXISTS users_vulnerable (
        id INT AUTO_INCREMENT PRIMARY KEY,
        username VARCHAR(255),
        password VARCHAR(255)
        )
        ";

        $conn->executeStatement($sql);

        return $this->redirectToRoute('ex14_home');
    }


    #[Route('/ex14/add', name: 'ex14_add', methods: ['POST'])]
    public function add(Request $request, Connection $conn): Response
    {
        $username = $request->request->get('username');
        $password = $request->request->get('password');

        $sql = "INSERT INTO users_vulnerable (username, password)
        VALUES ('$username', '$password')";

        $conn->executeStatement($sql);

        return $this->redirectToRoute('ex14_home');
    }
}
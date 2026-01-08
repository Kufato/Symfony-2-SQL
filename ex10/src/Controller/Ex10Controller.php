<?php

namespace App\Controller;

use Doctrine\DBAL\Connection;
use Doctrine\ORM\EntityManagerInterface;
use App\Entity\OrmFileData;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class Ex10Controller extends AbstractController
{
    #[Route('/ex10', name: 'ex10_home')]
    public function index(): Response
    {
        return $this->render('ex10/index.html.twig');
    }

    #[Route('/ex10/create-table', name: 'ex10_create_table')]
    public function createTable(Connection $conn): Response
    {
        $sql = <<<SQL
        CREATE TABLE IF NOT EXISTS sql_file_data (
            id INT AUTO_INCREMENT PRIMARY KEY,
            filename VARCHAR(255) NOT NULL,
            content LONGTEXT NOT NULL,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
        SQL;

        $conn->executeStatement($sql);

        return $this->render('ex10/index.html.twig', [
            'message' => '✅ Table SQL créée ou déjà existante.'
        ]);
    }

    #[Route('/ex10/import', name: 'ex10_import')]
    public function import(EntityManagerInterface $em, Connection $conn): Response
    {
        $filePath = __DIR__ . '/../../public/data/input.txt';

        if (!file_exists($filePath)) {
            return new Response("❌ Fichier introuvable : $filePath");
        }

        $content = file_get_contents($filePath);

        $conn->insert('sql_file_data', [
            'filename' => 'input.txt',
            'content'  => $content,
            'created_at' => (new \DateTime())->format('Y-m-d H:i:s'),
        ]);

        $ormData = new OrmFileData();
        $ormData->setFilename('input.txt');
        $ormData->setContent($content);
        $ormData->setCreatedAt(new \DateTime());
        $em->persist($ormData);
        $em->flush();

        return $this->render('ex10/index.html.twig', [
            'message' => '✅ Données importées avec succès dans SQL et ORM.'
        ]);
    }

    #[Route('/ex10/view', name: 'ex10_view')]
    public function view(EntityManagerInterface $em, Connection $conn): Response
    {
        $sqlData = $conn->fetchAllAssociative('SELECT * FROM sql_file_data ORDER BY id ASC');

        $ormData = $em->getRepository(OrmFileData::class)->findBy([], ['id' => 'ASC']);

        return $this->render('ex10/view.html.twig', [
            'sqlData' => $sqlData,
            'ormData' => $ormData,
        ]);
    }
}
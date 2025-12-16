<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
    use Symfony\Component\HttpFoundation\Response;
    use Symfony\Component\HttpFoundation\Request;
    use Symfony\Component\Routing\Attribute\Route;
    use Doctrine\DBAL\Connection;

    final class Ex11Controller extends AbstractController
    {
        #[Route('/ex11', name: 'ex11_home')]
        public function index(Request $request, Connection $conn): Response
        {
            $message = '';

            // Création table category + insertion catégories
            if ($request->query->get('create_category')) {
                try {
                    $conn->executeStatement("
                        CREATE TABLE IF NOT EXISTS category (
                            id INT AUTO_INCREMENT PRIMARY KEY,
                            name VARCHAR(100) NOT NULL
                        )
                    ");

                    $count = $conn->fetchOne("SELECT COUNT(*) FROM category");
                    if ($count == 0) {
                        $categories = ['Frais', 'Pas frais', 'electronique'];
                        foreach ($categories as $name) {
                            $conn->insert('category', ['name' => $name]);
                        }
                    }

                    $message .= "✅ Table 'category' créée avec données.<br>";
                } catch (\Exception $e) {
                    $message .= "❌ Erreur catégorie : " . $e->getMessage() . "<br>";
                }
            }

            // Création table product
        if ($request->query->get('create_product')) {
            try {
                $conn->executeStatement("
                    CREATE TABLE IF NOT EXISTS product (
                        id INT AUTO_INCREMENT PRIMARY KEY,
                        name VARCHAR(100) NOT NULL,
                        price DOUBLE NOT NULL,
                        expiry_date DATE NOT NULL,
                        category_id INT NOT NULL,
                        FOREIGN KEY (category_id) REFERENCES category(id)
                    )
                ");
                $message .= "✅ Table 'product' créée.<br>";
            } catch (\Exception $e) {
                $message .= "❌ Erreur product : " . $e->getMessage() . "<br>";
            }
        }

        return $this->render('ex11/index.html.twig', [
            'message' => $message
        ]);
    }

    #[Route('/ex11/add', name: 'ex11_add')]
    public function add(Request $request, Connection $conn): Response
    {
        $message = '';
        $categories = $conn->fetchAllAssociative("SELECT * FROM category");

        if ($request->isMethod('POST')) {
            $name = $request->request->get('name');
            $price = $request->request->get('price');
            $expiry_date = $request->request->get('expiry_date');
            $category_id = $request->request->get('category_id');

            // Récupérer le nom de la catégorie
            $category = $conn->fetchAssociative(
                "SELECT name FROM category WHERE id = ?",
                [$category_id]
            );

            $today = new \DateTime('today');

            // LOGIQUE MÉTIER
            if ($category['name'] !== 'electronique') {

                // Date obligatoire
                if (empty($expiry_date)) {
                    $message = "❌ La date de péremption est obligatoire pour cette catégorie.";
                } else {
                    $expiryDateObj = new \DateTime($expiry_date);

                    if ($expiryDateObj < $today) {
                        $message = "❌ La date de péremption ne peut pas être antérieure à aujourd'hui.";
                    }
                }

            } else {
                // Électronique → pas de date
                $expiry_date = null;
            }

            // INSERT seulement si aucune erreur
            if ($message === '') {
                try {
                    $conn->insert('product', [
                        'name' => $name,
                        'price' => $price,
                        'expiry_date' => $expiry_date,
                        'category_id' => $category_id
                    ]);
                    $message = "✅ Produit ajouté !";
                } catch (\Exception $e) {
                    $message = "❌ Erreur SQL : " . $e->getMessage();
                }
            }
        }

        return $this->render('ex11/add.html.twig', [
            'categories' => $categories,
            'message' => $message
        ]);
    }

    #[Route('/ex11/view', name: 'ex11_view')]
    public function view(Request $request, Connection $conn): Response
    {
        $category_id = $request->query->get('category');
        $sort = $request->query->get('sort', 'name');
        $order = strtoupper($request->query->get('order', 'ASC'));
        $allowedSort = ['name', 'price', 'expiry_date'];
        
        if (!in_array($sort, $allowedSort)) {
            $sort = 'name';
        }

        $order = $order === 'DESC' ? 'DESC' : 'ASC';
        $sql = "
            SELECT p.*, c.name AS category_name
            FROM product p
            JOIN category c ON p.category_id = c.id
        ";
        $params = [];

        if ($category_id) {
            $sql .= " WHERE c.id = ?";
            $params[] = $category_id;
        }

        $sql .= " ORDER BY $sort $order";
        $products = $conn->fetchAllAssociative($sql, $params);
        $categories = $conn->fetchAllAssociative("SELECT * FROM category");

        return $this->render('ex11/view.html.twig', [
            'products' => $products,
            'categories' => $categories,
            'selectedCategory' => $category_id,
            'sort' => $sort,
            'order' => $order
        ]);
    }

}


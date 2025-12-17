<?php

namespace App\Controller;

use App\Entity\Product;
use App\Repository\CategoryRepository;
use App\Repository\ProductRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class Ex12Controller extends AbstractController
{
    #[Route('/ex12', name: 'ex12_home')]
    public function index(): Response
    {
        return $this->render('ex12/index.html.twig');
    }

    #[Route('/ex12/add', name: 'ex12_add')]
    public function add(
        Request $request,
        EntityManagerInterface $em,
        CategoryRepository $categoryRepo
    ): Response {
        $message = '';
        $categories = $categoryRepo->findAll();

        if ($request->isMethod('POST')) {
            $category = $categoryRepo->find($request->request->get('category_id'));
            $expiry = $request->request->get('expiry_date');

            if ($category->getName() !== 'electronique') {
                if (!$expiry || new \DateTime($expiry) < new \DateTime('today')) {
                    $message = "❌ Date invalide";
                }
            }

            if (!$message) {
                $product = new Product();
                $product->setName($request->request->get('name'));
                $product->setPrice((float)$request->request->get('price'));
                $product->setCategory($category);

                if ($category->getName() !== 'electronique') {
                    $product->setExpiryDate(new \DateTime($expiry));
                }

                $em->persist($product);
                $em->flush();
                $message = "✅ Produit ajouté";
            }
        }

        return $this->render('ex12/add.html.twig', [
            'categories' => $categories,
            'message' => $message
        ]);
    }

    #[Route('/ex12/view', name: 'ex12_view')]
    public function view(
        Request $request,
        EntityManagerInterface $em,
        CategoryRepository $categoryRepo
    ): Response {
        $categoryId = $request->query->get('category');
        $sort = $request->query->get('sort', 'name');
        $order = strtoupper($request->query->get('order', 'ASC'));

        $allowedSort = ['name', 'price', 'expiryDate'];
        if (!in_array($sort, $allowedSort)) {
            $sort = 'name';
        }
        $order = $order === 'DESC' ? 'DESC' : 'ASC';

        $qb = $em->createQueryBuilder()
            ->select('p', 'c')
            ->from(Product::class, 'p')
            ->join('p.category', 'c');

        if ($categoryId) {
            $qb->where('c.id = :cat')
            ->setParameter('cat', $categoryId);
        }

        if ($sort === 'expiryDate') {
            // Tri sur date avec NULL en dernier
            $qb->addOrderBy("CASE WHEN p.expiryDate IS NULL THEN 1 ELSE 0 END", 'ASC')
            ->addOrderBy('p.expiryDate', $order);
        } else {
            $qb->orderBy('p.' . $sort, $order);
        }

        $products = $qb->getQuery()->getResult();
        $categories = $categoryRepo->findAll();

        return $this->render('ex12/view.html.twig', [
            'products' => $products,
            'categories' => $categories,
            'selectedCategory' => $categoryId,
            'sort' => $sort,
            'order' => $order
        ]);
    }

}
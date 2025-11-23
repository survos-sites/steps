<?php

namespace App\Command;

use App\Entity\Product;
use App\Repository\ProductRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\Argument;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Attribute\Option;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Contracts\HttpClient\HttpClientInterface;

#[AsCommand('app:load', 'Load products from dummyjson')]
class LoadCommand
{
	public function __construct(
        private EntityManagerInterface $entityManager,
        private ProductRepository $productRepository,
        private HttpClientInterface $httpClient,
    )
	{
	}


	public function __invoke(
		SymfonyStyle $io,
		#[Argument('url of products')]
		string $url = 'https://dummyjson.com/products',
	): int
	{
        $data = $this->httpClient->request('GET', $url)->toArray();
        foreach ($data['products'] as $p) {
            $sku = $p['sku'];
            if (!$product = $this->productRepository->find($sku)) {
                $product = new Product($sku);
                $this->entityManager->persist($product);
            }
            $product->title = $p['title'];
            $product->description = $p['description'];
            $product->stock = $p['stock'];
            $product->imageUrl = $p['thumbnail'];
            $product->category = $p['category'];
        }
        $this->entityManager->flush();
		$io->success(self::class . " success.");
		return Command::SUCCESS;
	}
}

<?php

namespace App\Command;

use App\Entity\Card;
use App\Entity\Environment;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\Mailer\Transport\Smtp\Auth\CramMd5Authenticator;

#[AsCommand(name: 'app:fetch-card-images', description: 'Fetch card images from Wikipedia API')]
class FetchCardImagesCommand extends Command
{
    public function __construct(private EntityManagerInterface $entityManager)
    {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $cards = $this->entityManager->getRepository(Card::class)->findAll();

        $updated = 0;
        $skipped = 0;
        $notFound = 0;

        foreach ($cards as $card) {
            if ($card->getUrlImage()) {
                $io->text("⏭ {$card->getName()} — ya tiene imagen");
                $skipped++;
                continue;
            }

            $imageUrl = $this->fetchImageFromWikipedia($card->getName());

            if ($imageUrl) {
                $card->setUrlImage($imageUrl);
                $io->text("✓ {$card->getName()} — {$imageUrl}");
                $updated++;
            } else {
                $io->warning("{$card->getName()} — no se encontró imagen en Wikipedia");
                $notFound++;
            }

            usleep(100_000);
        }

        $this->entityManager->flush();

        $io->success("Actualizadas: {$updated} | Ya tenían: {$skipped} | Sin imagen: {$notFound}");

        return Command::SUCCESS;
    }

    private function fetchJson(string $url): ?array
    {
        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_USERAGENT => 'GachaHistoria/1.0 (card image fetcher)',
            CURLOPT_TIMEOUT => 10,
        ]);
        $response = curl_exec($ch);
        curl_close($ch);

        return $response ? json_decode($response, true) : null;
    }

    private function fetchImageFromWikipedia(string $name): ?string
    {
        $searchUrl = 'https://es.wikipedia.org/w/api.php?' . http_build_query([
            'action' => 'query',
            'list' => 'search',
            'srsearch' => $name,
            'srlimit' => 1,
            'format' => 'json',
        ]);

        $searchData = $this->fetchJson($searchUrl);
        $results = $searchData['query']['search'] ?? [];

        if (empty($results)) {
            return null;
        }

        $title = $results[0]['title'];

        $imageUrl = 'https://es.wikipedia.org/w/api.php?' . http_build_query([
            'action' => 'query',
            'titles' => $title,
            'prop' => 'pageimages',
            'pithumbsize' => 300,
            'format' => 'json',
        ]);

        $imageData = $this->fetchJson($imageUrl);
        $pages = $imageData['query']['pages'] ?? [];

        foreach ($pages as $page) {
            if (isset($page['thumbnail']['source'])) {
                return $page['thumbnail']['source'];
            }
        }

        return null;
    }
}

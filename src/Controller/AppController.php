<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Finder\Finder;

class AppController extends AbstractController
{
    #[Route('/', name: 'app_home')]
    public function index(): Response
    {
        return $this->redirectToRoute('survos_step_index');
        $castorFiles = $this->findCastorFiles();
        $slideshows = [];

        foreach ($castorFiles as $file) {
            $filepath = $file->getRealPath();
            $filename = $file->getFilenameWithoutExtension();

            $tasks = $this->parseCastorFile($filepath);

            if (!empty($tasks)) {
                $slideshows[] = [
                    'filename' => $filename,
                    'filepath' => $filepath,
                    'tasks' => $tasks,
                ];
            }
        }

        return $this->render('app/index.html.twig', [
            'slideshows' => $slideshows,
        ]);
    }

    #[Route('/slides/{code}', name: 'app_slides')]
    public function slides(string $code): Response
    {
        $castorFile = $this->findCastorFileByCode($code);

        if (!$castorFile) {
            throw $this->createNotFoundException("Slideshow '{$code}' not found");
        }

        require_once $castorFile;

        $tasks = $this->parseCastorFile($castorFile);

        return $this->render('app/slides.html.twig', [
            'code' => $code,
            'tasks' => $tasks,
        ]);
    }

    private function findCastorFiles(): Finder
    {
        $projectRoot = dirname($this->getParameter('kernel.project_dir'));

        $finder = new Finder();
        $finder->files()
            ->in($projectRoot)
            ->name('*.castor.php')
            ->depth('== 0');

        return $finder;
    }

    private function findCastorFileByCode(string $code): ?string
    {
        $projectRoot = dirname($this->getParameter('kernel.project_dir'));
        $filepath = $projectRoot . '/' . $code . '.php';
        assert(file_exists($filepath), "Missing $filepath");

        return file_exists($filepath) ? $filepath : null;
    }

    private function parseCastorFile(string $filepath): array
    {

        $content = file_get_contents($filepath);
        $tasks = [];

        $pattern = '/#\[AsTask\([^\]]*name:\s*["\']([^"\']+)["\'][^\]]*\)\]\s*#\[Slide\((.*?)\)\]\s*function\s+(\w+)/s';

        preg_match_all($pattern, $content, $matches, PREG_SET_ORDER);

        foreach ($matches as $match) {
            $taskName = $match[1];
            $slideParams = $match[2];
            $functionName = $match[3];

            $slideData = $this->parseSlideParameters($slideParams);

            $tasks[] = [
                'name' => $taskName,
                'function' => $functionName,
                'title' => $slideData['title'] ?? $functionName,
                'description' => $slideData['description'] ?? '',
                'bullets' => $slideData['bullets'] ?? [],
                'website' => $slideData['website'] ?? null,
                'bash' => $slideData['bash'] ?? [],
                'yaml' => $slideData['yaml'] ?? [],
            ];
        }

        return $tasks;
    }

    private function parseSlideParameters(string $params): array
    {
        $data = [];

        if (preg_match('/title:\s*["\']([^"\']+)["\']/', $params, $m)) {
            $data['title'] = $m[1];
        }

        if (preg_match('/description:\s*["\']([^"\']+)["\']/', $params, $m)) {
            $data['description'] = $m[1];
        }

        if (preg_match('/bullets:\s*\[(.*?)\]/s', $params, $m)) {
            $bulletsStr = $m[1];
            preg_match_all('/["\']([^"\']+)["\']/', $bulletsStr, $bulletMatches);
            $data['bullets'] = $bulletMatches[1];
        }

        if (preg_match('/website:\s*["\']([^"\']+)["\']/', $params, $m)) {
            $data['website'] = $m[1];
        }

        if (preg_match('/bash:\s*\[(.*?)\]/s', $params, $m)) {
            $bashStr = $m[1];
            preg_match_all('/\[[^\]]+\]|["\'][^"\']+["\']/', $bashStr, $cmdMatches);
            $data['bash'] = $cmdMatches[0];
        }

        if (preg_match('/yaml:\s*\[(.*?)\]/s', $params, $m)) {
            $data['yaml'] = ['present' => true];
        }

        return $data;
    }
}

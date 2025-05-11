<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class GameController extends AbstractController
{
    private $usersFile = '../var/users.txt';
    private $scoresFile = '../var/scores.txt';

    #[Route("/", name: "home")]
    public function home(): Response
    {
        $users = file_exists($this->usersFile) ? file($this->usersFile, FILE_IGNORE_NEW_LINES) : [];
        $scores = file_exists($this->scoresFile) ? file($this->scoresFile, FILE_IGNORE_NEW_LINES) : [];

        $maxScore = 0;
        foreach ($scores as $entry) {
            [$user, $score] = explode(":", $entry);
            $score = (int)$score;
            if ($score > $maxScore) {
                $maxScore = $score;
            }
        }

        return $this->render('login.html.twig', [
            'users' => $users,
            'total_players' => count($users),
            'max_score' => $maxScore
        ]);
    }

    #[Route("/start-game", name: "start_game", methods: ["POST"])]
    public function startGame(Request $request): Response
    {
        $username = $request->request->get('username');
        $newUser = $request->request->get('new_user');
        $color = $request->request->get('snake_color', 'hsl(120, 100%, 50%)');

        if ($newUser) {
            $username = trim($newUser);
            file_put_contents($this->usersFile, "$username\n", FILE_APPEND);
        }

        if (!$username) {
            return $this->redirectToRoute('home');
        }

        return $this->render('game.html.twig', [
            'username' => $username,
            'snakeColor' => $color,
            'colorHex' => $this->colorToHex($color)
        ]);
    }

    private function colorToHex(string $color): string
    {
        if (strpos($color, 'hsl') !== false) {
            preg_match('/hsl\((\d+),\s*([\d.]+)%,\s*([\d.]+)%\)/', $color, $matches);
            $h = $matches[1] / 360;
            $s = $matches[2] / 100;
            $l = $matches[3] / 100;

            if ($s == 0) {
                $r = $g = $b = $l;
            } else {
                $q = $l < 0.5 ? $l * (1 + $s) : $l + $s - $l * $s;
                $p = 2 * $l - $q;

                $r = $this->hueToRgb($p, $q, $h + 1/3);
                $g = $this->hueToRgb($p, $q, $h);
                $b = $this->hueToRgb($p, $q, $h - 1/3);
            }

            return sprintf("#%02x%02x%02x", round($r * 255), round($g * 255), round($b * 255));
        }

        return $color;
    }

    private function hueToRgb($p, $q, $t)
    {
        if ($t < 0) $t += 1;
        if ($t > 1) $t -= 1;
        if ($t < 1/6) return $p + ($q - $p) * 6 * $t;
        if ($t < 1/2) return $q;
        if ($t < 2/3) return $p + ($q - $p) * (2/3 - $t) * 6;
        return $p;
    }

    #[Route("/save-score", name: "save_score", methods: ["POST"])]
    public function saveScore(Request $request): Response
    {
        $data = json_decode($request->getContent(), true);
        $username = $data['username'] ?? 'Unknown';
        $score = $data['score'] ?? 0;

        file_put_contents($this->scoresFile, "$username:$score\n", FILE_APPEND);

        return new Response('Score saved');
    }

    #[Route("/leaderboard", name: "leaderboard")]
    public function leaderboard(): Response
    {
        $scores = file_exists($this->scoresFile) ? file($this->scoresFile, FILE_IGNORE_NEW_LINES) : [];
        $leaderboard = [];

        foreach ($scores as $entry) {
            [$user, $score] = explode(":", $entry);
            if (!isset($leaderboard[$user]) || $score > $leaderboard[$user]) {
                $leaderboard[$user] = $score;
            }
        }

        arsort($leaderboard);

        return $this->render('leaderboard.html.twig', ['leaderboard' => $leaderboard]);
    }
}
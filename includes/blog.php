<?php
declare(strict_types=1);

function blog_public_posts(int $limit = 50): array
{
    $file = __DIR__ . '/../blog-engine/dist/published.json';
    if (!is_file($file)) return [];
    $posts = json_decode((string) file_get_contents($file), true);
    if (!is_array($posts)) return [];
    return array_map(static function (array $post): array {
        return $post + [
            'category' => $post['tags'][0] ?? 'Dijital rehberler',
            'h1' => $post['title'], 'excerpt' => $post['description'],
            'published_at' => $post['publishedAt'], 'updated_at' => $post['approvedAt'],
        ];
    }, array_slice($posts, 0, $limit));
}

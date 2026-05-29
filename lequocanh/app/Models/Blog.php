<?php

declare(strict_types=1);

namespace App\Models;

use PDO;

/**
 * Blog model for blog posts.
 * Maps to 'blog_posts' table.
 *
 * @property int $id
 * @property string $title
 * @property string $slug
 * @property string $content
 * @property string $excerpt
 * @property string $featured_image
 * @property string $author
 * @property string $status
 * @property int $view_count
 * @property string $created_at
 * @property string $updated_at
 */
class Blog extends BaseModel
{
    protected static $table = 'blog_posts';
    protected static $primaryKey = 'id';
    protected static $timestamps = true;

    protected static $fillable = [
        'title',
        'slug',
        'content',
        'excerpt',
        'featured_image',
        'author',
        'status',
    ];

    /**
     * Get published posts with pagination.
     *
     * @return Blog[]
     */
    public static function getPublished(int $limit = 10, int $offset = 0): array
    {
        $db = \Database::getInstance()->getConnection();
        $stmt = $db->prepare(
            "SELECT id, title, slug, content, excerpt, featured_image, author, status, view_count, created_at, updated_at FROM " . static::$table . " WHERE status = 'published' ORDER BY created_at DESC LIMIT ? OFFSET ?"
        );
        $stmt->execute([$limit, $offset]);

        $results = [];
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $model = new static($row);
            $model->exists = true;
            $results[] = $model;
        }

        return $results;
    }

    /**
     * Find published post by slug.
     */
    public static function findBySlug(string $slug): ?self
    {
        $db = \Database::getInstance()->getConnection();
        $stmt = $db->prepare(
            "SELECT id, title, slug, content, excerpt, featured_image, author, status, view_count, created_at, updated_at FROM " . static::$table . " WHERE slug = ? AND status = 'published' LIMIT 1"
        );
        $stmt->execute([$slug]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$row) {
            return null;
        }

        // Increment view count
        $db->prepare("UPDATE " . static::$table . " SET view_count = view_count + 1 WHERE id = ?")
           ->execute([$row['id']]);

        $model = new static($row);
        $model->exists = true;
        return $model;
    }

    /**
     * Create a new blog post with auto-generated slug.
     */
    public static function createPost(string $title, string $content, ?string $excerpt = null, ?string $image = null, string $author = 'Admin'): self
    {
        $slug = self::createSlug($title);
        $excerpt = $excerpt ?: mb_substr(strip_tags($content), 0, 200) . '...';

        return self::create([
            'title' => $title,
            'slug' => $slug,
            'content' => $content,
            'excerpt' => $excerpt,
            'featured_image' => $image,
            'author' => $author,
            'status' => 'published',
        ]);
    }

    /**
     * Count published posts.
     */
    public static function countPublished(): int
    {
        $db = \Database::getInstance()->getConnection();
        $stmt = $db->prepare("SELECT COUNT(*) FROM " . static::$table . " WHERE status = 'published'");
        $stmt->execute();
        return (int) $stmt->fetchColumn();
    }

    /**
     * Generate URL-friendly slug from title.
     */
    private static function createSlug(string $title): string
    {
        $slug = mb_strtolower($title);
        $slug = preg_replace('/[^a-z0-9\s-]/', '', $slug);
        $slug = preg_replace('/[\s-]+/', '-', $slug);
        $slug = trim($slug, '-');
        return $slug . '-' . time();
    }
}

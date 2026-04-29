<?php
/**
 * ChubbyCMS - Content Management Engine
 */

namespace Core;

class Content {
    private $notesPath;

    public function __construct($notesPath) {
        $this->notesPath = rtrim($notesPath, '/');
        if (!is_dir($this->notesPath)) {
            mkdir($this->notesPath, 0755, true);
        }
    }

    /**
     * Get all articles with metadata
     */
    public function getArticles() {
        $articles = [];
        $files = $this->recursiveGlob($this->notesPath . '/*.md');

        foreach ($files as $file) {
            $articles[] = $this->readArticle($file);
        }

        // Sort by date or updated
        usort($articles, function($a, $b) {
            return ($b['updated'] ?? 0) - ($a['updated'] ?? 0);
        });

        return $articles;
    }

    /**
     * Validate that a path is within the notes directory
     */
    public function validatePath($path) {
        $realNotesPath = realpath($this->notesPath);

        // Use a more robust check by canonicalizing path without realpath for non-existent files
        $canonicalPath = str_replace(['\\', '//'], '/', $path);
        $parts = explode('/', $canonicalPath);
        $absolutes = [];
        foreach ($parts as $part) {
            if ('' == $part || '.' == $part) continue;
            if ('..' == $part) {
                array_pop($absolutes);
            } else {
                $absolutes[] = $part;
            }
        }
        $canonicalPath = '/' . implode('/', $absolutes);

        if (strpos($canonicalPath, $realNotesPath) !== 0) {
            return false;
        }

        return true;
    }

    /**
     * Read article and parse Frontmatter
     */
    public function readArticle($filepath) {
        if (!$this->validatePath($filepath)) return null;
        if (!file_exists($filepath)) return null;

        $content = file_get_contents($filepath);
        $filename = basename($filepath, '.md');
        $relativeDir = str_replace($this->notesPath, '', dirname($filepath));
        $categoryPath = trim($relativeDir, '/');

        $data = [
            'filename' => $filename,
            'path' => $categoryPath,
            'full_path' => $filepath,
            'title' => $filename,
            'blocks' => [],
            'status' => 'published',
            'updated' => filemtime($filepath)
        ];

        if (preg_match('/^---(.*?)---(.*)$/s', $content, $matches)) {
            $frontmatter = json_decode(trim($matches[1]), true);
            if ($frontmatter) {
                $data = array_merge($data, $frontmatter);
            }
            $data['raw_content'] = trim($matches[2]);
        } else {
            $data['raw_content'] = $content;
        }

        return $data;
    }

    /**
     * Save article with Frontmatter
     */
    public function saveArticle($path, $filename, $data) {
        $filename = basename($filename, '.md') . '.md';
        $dir = $this->notesPath . '/' . trim($path, '/');
        $filepath = $dir . '/' . $filename;

        if (!$this->validatePath($filepath)) return false;

        if (!is_dir($dir)) {
            mkdir($dir, 0755, true);
        }

        // Ensure blocks and metadata are in frontmatter
        $content = "---\n";
        $content .= json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
        $content .= "\n---\n";

        // Optional: add a markdown representation for fallback
        // For now we just store the blocks as JSON in frontmatter as requested for visual editor

        return file_put_contents($filepath, $content) !== false;
    }

    /**
     * Delete article
     */
    public function deleteArticle($path, $filename) {
        $filepath = $this->notesPath . '/' . trim($path, '/') . '/' . basename($filename, '.md') . '.md';

        if (!$this->validatePath($filepath)) return false;

        if (file_exists($filepath)) {
            return unlink($filepath);
        }
        return false;
    }

    /**
     * Get categories based on folder structure
     */
    public function getCategories() {
        $categories = [];
        $dirs = array_filter(glob($this->notesPath . '/*'), 'is_dir');

        foreach ($dirs as $dir) {
            $catName = basename($dir);
            $subdirs = array_filter(glob($dir . '/*'), 'is_dir');
            $subcategories = array_map('basename', $subdirs);

            $categories[$catName] = $subcategories;
        }

        return $categories;
    }

    private function recursiveGlob($pattern, $flags = 0) {
        $files = glob($pattern, $flags);
        foreach (glob(dirname($pattern) . '/*', GLOB_ONLYDIR | GLOB_NOSORT) as $dir) {
            $files = array_merge($files, $this->recursiveGlob($dir . '/' . basename($pattern), $flags));
        }
        return $files;
    }
}

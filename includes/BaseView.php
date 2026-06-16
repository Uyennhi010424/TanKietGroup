<?php
/**
 * BaseView Helper
 * Cung cấp data cho views mà không cần query DB trực tiếp trong view files.
 *
 * Cách dùng trong view:
 *   $view = new BaseView();
 *   $services = $view->services()->active();
 *   $latestPosts = $view->blog()->published(3);
 */

require_once __DIR__ . '/site.php';
require_once __DIR__ . '/db.php';
require_once __DIR__ . '/../models/Service.php';
require_once __DIR__ . '/../models/Blog.php';
require_once __DIR__ . '/../models/Project.php';
require_once __DIR__ . '/../models/Course.php';

class BaseView
{
    private ?PDO $db = null;
    private ?Service $serviceModel = null;
    private ?Blog $blogModel = null;
    private ?Project $projectModel = null;
    private ?Course $courseModel = null;

    public function __construct(?PDO $db = null)
    {
        $this->db = $db ?? site_db();
    }

    /** Service Model */
    public function services(): Service
    {
        if ($this->serviceModel === null) {
            $this->serviceModel = new Service($this->db);
        }
        return $this->serviceModel;
    }

    /** Blog Model */
    public function blog(): Blog
    {
        if ($this->blogModel === null) {
            $this->blogModel = new Blog($this->db);
        }
        return $this->blogModel;
    }

    /** Project Model */
    public function projects(): Project
    {
        if ($this->projectModel === null) {
            $this->projectModel = new Project($this->db);
        }
        return $this->projectModel;
    }

    /** Course Model */
    public function courses(): Course
    {
        if ($this->courseModel === null) {
            $this->courseModel = new Course($this->db);
        }
        return $this->courseModel;
    }

    /**
     * Helper: lấy settings website
     */
    public function settings(): array
    {
        return site_settings();
    }

    /**
     * Helper: lấy danh sách clients
     */
    public function clients(): array
    {
        return site_fetch_all(
            'SELECT * FROM clients WHERE status = 1 ORDER BY sort_order ASC, id DESC'
        );
    }

    /**
     * Helper: lấy danh sách industries
     */
    public function industries(): array
    {
        return site_fetch_all('SELECT * FROM industries ORDER BY sort_order ASC');
    }

    /**
     * Helper: lấy testimonials
     */
    public function testimonials(): array
    {
        return site_fetch_all(
            'SELECT * FROM testimonials WHERE status = 1 ORDER BY sort_order ASC'
        );
    }

    /**
     * Helper: lấy team members
     */
    public function teamMembers(): array
    {
        return site_fetch_all(
            'SELECT * FROM team_members WHERE status = 1 ORDER BY sort_order ASC'
        );
    }
}

# Blog System Example

This complete example demonstrates building a blog system with DataMapper, showcasing relationships, validation, and CRUD operations.

## Database Schema

```sql
CREATE TABLE users (
    id INT PRIMARY KEY AUTO_INCREMENT,
    username VARCHAR(50) UNIQUE NOT NULL,
    email VARCHAR(255) UNIQUE NOT NULL,
    password VARCHAR(255) NOT NULL,
    salt VARCHAR(255) NOT NULL,
    role ENUM('admin', 'author', 'subscriber') DEFAULT 'subscriber',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

CREATE TABLE posts (
    id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT NOT NULL,
    title VARCHAR(255) NOT NULL,
    slug VARCHAR(255) UNIQUE NOT NULL,
    content TEXT NOT NULL,
    excerpt TEXT,
    status ENUM('draft', 'published', 'archived') DEFAULT 'draft',
    published_at TIMESTAMP NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    INDEX idx_status (status),
    INDEX idx_published (published_at)
);

CREATE TABLE comments (
    id INT PRIMARY KEY AUTO_INCREMENT,
    post_id INT NOT NULL,
    user_id INT NULL,  -- NULL for guest comments
    author_name VARCHAR(100) NOT NULL,
    author_email VARCHAR(255) NOT NULL,
    content TEXT NOT NULL,
    status ENUM('pending', 'approved', 'spam') DEFAULT 'pending',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (post_id) REFERENCES posts(id) ON DELETE CASCADE,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL
);

CREATE TABLE tags (
    id INT PRIMARY KEY AUTO_INCREMENT,
    name VARCHAR(100) UNIQUE NOT NULL,
    slug VARCHAR(100) UNIQUE NOT NULL
);

CREATE TABLE posts_tags (
    id INT PRIMARY KEY AUTO_INCREMENT,
    post_id INT NOT NULL,
    tag_id INT NOT NULL,
    FOREIGN KEY (post_id) REFERENCES posts(id) ON DELETE CASCADE,
    FOREIGN KEY (tag_id) REFERENCES tags(id) ON DELETE CASCADE,
    UNIQUE KEY unique_post_tag (post_id, tag_id)
);
```

## Models

### User Model

```php
<?php
class User extends DataMapper {
    
    var $has_many = array('post', 'comment');
    
    var $validation = array(
        'username' => array(
            'label' => 'Username',
            'rules' => array('required', 'alpha_dash', 'min_length' => 3, 'max_length' => 50, 'unique')
        ),
        'email' => array(
            'label' => 'Email',
            'rules' => array('required', 'valid_email', 'unique')
        ),
        'password' => array(
            'label' => 'Password',
            'rules' => array('required', 'min_length' => 6, 'encrypt')
        ),
        'role' => array(
            'rules' => array('in_list' => array('admin', 'author', 'subscriber'))
        )
    );
    
    function __construct($id = NULL)
    {
        parent::__construct($id);
    }
    
    // Custom validation: Encrypt password
    function _encrypt($field)
    {
        if (!empty($this->{$field})) {
            // Generate salt if new user
            if (empty($this->salt)) {
                $this->salt = md5(uniqid(rand(), TRUE));
            }
            // Encrypt password with salt
            $this->{$field} = sha1($this->salt . $this->{$field});
        }
    }
    
    // Login method
    function login($username, $password)
    {
        // Get user by username
        $this->where('username', $username)->get();
        
        if (!$this->exists()) {
            return FALSE;
        }
        
        // Check password
        $encrypted_password = sha1($this->salt . $password);
        
        if ($this->password === $encrypted_password) {
            return TRUE;
        }
        
        return FALSE;
    }
    
    // Check if user can edit post
    function can_edit($post)
    {
        if ($this->role === 'admin') {
            return TRUE;
        }
        
        if ($this->role === 'author' && $post->user_id == $this->id) {
            return TRUE;
        }
        
        return FALSE;
    }
}
```

### Post Model

```php
<?php
class Post extends DataMapper {
    
    var $has_one = array('user');
    var $has_many = array('comment', 'tag');
    
    var $validation = array(
        'title' => array(
            'label' => 'Title',
            'rules' => array('required', 'min_length' => 3, 'max_length' => 255)
        ),
        'slug' => array(
            'label' => 'URL Slug',
            'rules' => array('required', 'alpha_dash', 'unique')
        ),
        'content' => array(
            'label' => 'Content',
            'rules' => array('required', 'min_length' => 10)
        ),
        'status' => array(
            'rules' => array('in_list' => array('draft', 'published', 'archived'))
        )
    );
    
    var $default_order_by = array('published_at' => 'desc', 'created_at' => 'desc');
    
    function __construct($id = NULL)
    {
        parent::__construct($id);
    }
    
    // Publish post
    function publish()
    {
        $this->status = 'published';
        $this->published_at = date('Y-m-d H:i:s');
        return $this->save();
    }
    
    // Get published posts
    function get_published($limit = 10, $offset = 0)
    {
        return $this->where('status', 'published')
                    ->where('published_at <=', date('Y-m-d H:i:s'))
                    ->limit($limit, $offset)
                    ->get();
    }
    
    // Generate excerpt from content
    function generate_excerpt($length = 200)
    {
        if (empty($this->excerpt) && !empty($this->content)) {
            $this->excerpt = substr(strip_tags($this->content), 0, $length) . '...';
        }
    }
    
    // Generate slug from title
    function generate_slug()
    {
        if (empty($this->slug) && !empty($this->title)) {
            $slug = strtolower(trim($this->title));
            $slug = preg_replace('/[^a-z0-9-]/', '-', $slug);
            $slug = preg_replace('/-+/', '-', $slug);
            $this->slug = trim($slug, '-');
        }
    }
}
```

### Comment Model

```php
<?php
class Comment extends DataMapper {
    
    var $has_one = array('post', 'user');
    
    var $validation = array(
        'author_name' => array(
            'label' => 'Name',
            'rules' => array('required', 'max_length' => 100)
        ),
        'author_email' => array(
            'label' => 'Email',
            'rules' => array('required', 'valid_email')
        ),
        'content' => array(
            'label' => 'Comment',
            'rules' => array('required', 'min_length' => 3)
        )
    );
    
    var $default_order_by = array('created_at' => 'asc');
    
    function __construct($id = NULL)
    {
        parent::__construct($id);
    }
    
    // Approve comment
    function approve()
    {
        $this->status = 'approved';
        return $this->save();
    }
    
    // Mark as spam
    function mark_as_spam()
    {
        $this->status = 'spam';
        return $this->save();
    }
    
    // Get approved comments for a post
    function get_approved_for_post($post_id)
    {
        return $this->where('post_id', $post_id)
                    ->where('status', 'approved')
                    ->get();
    }
}
```

### Tag Model

```php
<?php
class Tag extends DataMapper {
    
    var $has_many = array('post');
    
    var $validation = array(
        'name' => array(
            'label' => 'Tag Name',
            'rules' => array('required', 'max_length' => 100, 'unique')
        ),
        'slug' => array(
            'label' => 'Slug',
            'rules' => array('required', 'alpha_dash', 'unique')
        )
    );
    
    function __construct($id = NULL)
    {
        parent::__construct($id);
    }
    
    // Get or create tag by name
    function get_or_create($name)
    {
        $slug = strtolower(trim($name));
        $slug = preg_replace('/[^a-z0-9-]/', '-', $slug);
        $slug = preg_replace('/-+/', '-', $slug);
        $slug = trim($slug, '-');
        
        $this->where('slug', $slug)->get();
        
        if (!$this->exists()) {
            $this->name = $name;
            $this->slug = $slug;
            $this->save();
        }
        
        return $this;
    }
    
    // Get popular tags
    function get_popular($limit = 10)
    {
        return $this->select('${parent}.*, COUNT(${parent}_${child}.id) as post_count')
                    ->join_related('post')
                    ->group_by('${parent}.id')
                    ->order_by('post_count', 'desc')
                    ->limit($limit)
                    ->get();
    }
}
```

## Controllers

### Blog Controller

```php
<?php
class Blog extends CI_Controller {
    
    function index($page = 0)
    {
        $per_page = 10;
        
        $post = new Post();
        $post->get_published($per_page, $page * $per_page);
        
        $data['posts'] = $post;
        $data['total_posts'] = $post->count();
        $data['current_page'] = $page;
        $data['total_pages'] = ceil($data['total_posts'] / $per_page);
        
        $this->load->view('blog/index', $data);
    }
    
    function view($slug)
    {
        $post = new Post();
        $post->where('slug', $slug)
             ->where('status', 'published')
             ->get();
        
        if (!$post->exists()) {
            show_404();
        }
        
        // Get post author
        $post->user->get();
        
        // Get approved comments
        $comment = new Comment();
        $comment->get_approved_for_post($post->id);
        
        // Get tags
        $post->tag->get();
        
        $data['post'] = $post;
        $data['comments'] = $comment;
        
        $this->load->view('blog/post', $data);
    }
    
    function tag($slug)
    {
        $tag = new Tag();
        $tag->where('slug', $slug)->get();
        
        if (!$tag->exists()) {
            show_404();
        }
        
        // Get posts with this tag
        $tag->post->where('status', 'published')
                  ->order_by('published_at', 'desc')
                  ->get();
        
        $data['tag'] = $tag;
        $data['posts'] = $tag->post;
        
        $this->load->view('blog/tag', $data);
    }
    
    function add_comment($post_id)
    {
        $comment = new Comment();
        $comment->from_array($_POST);
        $comment->post_id = $post_id;
        $comment->status = 'pending';
        
        // If user is logged in
        if ($this->session->userdata('user_id')) {
            $comment->user_id = $this->session->userdata('user_id');
        }
        
        if ($comment->save()) {
            $this->session->set_flashdata('message', 'Comment submitted for approval');
        } else {
            $this->session->set_flashdata('error', validation_errors());
        }
        
        redirect('blog/view/' . $post_id);
    }
}
```

### Admin Controller

```php
<?php
class Admin extends CI_Controller {
    
    function __construct()
    {
        parent::__construct();
        
        // Check if user is logged in
        if (!$this->session->userdata('user_id')) {
            redirect('login');
        }
        
        // Load current user
        $this->user = new User();
        $this->user->get_by_id($this->session->userdata('user_id'));
    }
    
    function posts()
    {
        $post = new Post();
        
        // Admins see all posts, authors see only theirs
        if ($this->user->role !== 'admin') {
            $post->where('user_id', $this->user->id);
        }
        
        $post->order_by('created_at', 'desc')
             ->get();
        
        $data['posts'] = $post;
        $this->load->view('admin/posts', $data);
    }
    
    function create_post()
    {
        if ($this->input->post()) {
            $post = new Post();
            $post->from_array($_POST);
            $post->user_id = $this->user->id;
            
            // Generate slug and excerpt
            $post->generate_slug();
            $post->generate_excerpt();
            
            if ($post->save()) {
                // Handle tags
                if ($this->input->post('tags')) {
                    $tag_names = explode(',', $this->input->post('tags'));
                    
                    foreach ($tag_names as $tag_name) {
                        $tag = new Tag();
                        $tag->get_or_create(trim($tag_name));
                        $post->save($tag);
                    }
                }
                
                $this->session->set_flashdata('message', 'Post created successfully');
                redirect('admin/posts');
            } else {
                $data['errors'] = $post->error->all;
            }
        }
        
        $this->load->view('admin/post_form', $data);
    }
    
    function edit_post($id)
    {
        $post = new Post();
        $post->get_by_id($id);
        
        if (!$post->exists()) {
            show_404();
        }
        
        // Check permissions
        if (!$this->user->can_edit($post)) {
            show_error('You do not have permission to edit this post');
        }
        
        if ($this->input->post()) {
            $post->from_array($_POST);
            $post->generate_slug();
            $post->generate_excerpt();
            
            if ($post->save()) {
                // Update tags
                $post->delete($post->tag->get());
                
                if ($this->input->post('tags')) {
                    $tag_names = explode(',', $this->input->post('tags'));
                    
                    foreach ($tag_names as $tag_name) {
                        $tag = new Tag();
                        $tag->get_or_create(trim($tag_name));
                        $post->save($tag);
                    }
                }
                
                $this->session->set_flashdata('message', 'Post updated successfully');
                redirect('admin/posts');
            } else {
                $data['errors'] = $post->error->all;
            }
        }
        
        // Load tags
        $post->tag->get();
        $data['post'] = $post;
        
        $this->load->view('admin/post_form', $data);
    }
    
    function delete_post($id)
    {
        $post = new Post();
        $post->get_by_id($id);
        
        if ($post->exists() && $this->user->can_edit($post)) {
            $post->delete();
            $this->session->set_flashdata('message', 'Post deleted successfully');
        }
        
        redirect('admin/posts');
    }
    
    function moderate_comments()
    {
        $comment = new Comment();
        $comment->where('status', 'pending')
                ->order_by('created_at', 'desc')
                ->get();
        
        $data['comments'] = $comment;
        $this->load->view('admin/comments', $data);
    }
    
    function approve_comment($id)
    {
        $comment = new Comment();
        $comment->get_by_id($id);
        
        if ($comment->exists()) {
            $comment->approve();
            $this->session->set_flashdata('message', 'Comment approved');
        }
        
        redirect('admin/moderate_comments');
    }
}
```

## DataMapper 2.0 Enhancements <Badge type="tip" text="2.0" />

Using DataMapper 2.0 features for better performance:

```php
<?php
// Eager loading to prevent N+1 queries
$post = new Post();
$post->with('user')
     ->with('tag')
     ->with('comment', function($query) {
         $query->where('status', 'approved');
     })
     ->where('status', 'published')
     ->get();

foreach ($post as $p) {
    echo $p->title;
    echo $p->user->username;  // No extra query!
    
    foreach ($p->tag as $tag) {
        echo $tag->name;  // No extra query!
    }
    
    echo "Comments: " . count($p->comment->all);
}
```

## See Also

- [E-commerce Example](/examples/ecommerce) - Online store
- [User Management Example](/examples/users) - Authentication system
- [Relationships](/guide/relationships/) - Understanding relationships
- [Validation](/guide/advanced/validation) - Data validation
- [Eager Loading](/guide/datamapper-2/eager-loading) - Performance optimization

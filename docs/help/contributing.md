# Contributing to DataMapper ORM

Thank you for your interest in contributing to DataMapper ORM! This document provides guidelines for contributing to the project.

## Ways to Contribute

There are many ways to contribute to DataMapper:

- 🐛 Report bugs
- 💡 Suggest new features
- 📖 Improve documentation
- 💻 Submit code changes
- 🧪 Write tests
- 💬 Help others in discussions
- ⭐ Star the repository

## Code of Conduct

### Our Pledge

We are committed to making participation in this project a harassment-free experience for everyone.

### Our Standards

- Be respectful and inclusive
- Accept constructive criticism gracefully
- Focus on what is best for the community
- Show empathy towards other community members

## Getting Started

### 1. Fork the Repository

Visit [github.com/P2GR/datamapper](https://github.com/P2GR/datamapper) and click "Fork".

### 2. Clone Your Fork

```bash
git clone https://github.com/YOUR_USERNAME/datamapper.git
cd datamapper
```

### 3. Add Upstream Remote

```bash
git remote add upstream https://github.com/P2GR/datamapper.git
```

### 4. Create a Branch

```bash
git checkout -b feature/your-feature-name
# or
git checkout -b fix/your-bug-fix
```

## Development Setup

### Prerequisites

- PHP 7.2 or higher
- CodeIgniter 3.1.0 or higher
- Composer (for dependencies)
- MySQL, PostgreSQL, or SQLite for testing

### Install Dependencies

```bash
composer install
```

### Run Tests

```bash
vendor/bin/phpunit
```

## Making Changes

### Coding Standards

DataMapper follows **PSR-12** coding standards with some CodeIgniter-specific conventions:

#### File Structure

```php
<?php
/**
 * Class description
 *
 * @package    DataMapper
 * @subpackage Models
 * @author     Your Name
 * @license    MIT
 */

class Example_Model extends DataMapper {
    
    // Class constants
    const STATUS_ACTIVE = 'active';
    
    // Public properties
    var $table = 'examples';
    var $has_many = array('child');
    
    // Constructor
    function __construct($id = NULL)
    {
        parent::__construct($id);
    }
    
    // Public methods
    public function do_something()
    {
        // Implementation
    }
    
    // Protected methods
    protected function helper_method()
    {
        // Implementation
    }
    
    // Private methods
    private function internal_method()
    {
        // Implementation
    }
}

/* End of file example_model.php */
/* Location: ./application/models/example_model.php */
```

#### Naming Conventions

```php
// Classes: CamelCase
class UserAccount extends DataMapper {}

// Methods: snake_case
function get_user_by_email() {}

// Variables: snake_case
$user_name = 'John';

// Constants: UPPERCASE
const MAX_RETRIES = 3;

// Private/Protected properties: underscore prefix
protected $_cache = array();
private $_internal_state = null;
```

#### Code Style

```php
// ✅ Good
if ($user->exists()) {
    $user->name = 'New Name';
    $user->save();
}

// ❌ Bad
if($user->exists()){
    $user->name='New Name';
    $user->save();
}

// ✅ Good - Proper spacing
$result = $this->calculate_total($a, $b, $c);

// ❌ Bad - No spacing
$result=$this->calculate_total($a,$b,$c);

// ✅ Good - Array formatting
$config = array(
    'host'     => 'localhost',
    'database' => 'app',
    'user'     => 'root'
);

// ✅ Good - Chaining
$user = new User();
$user->where('status', 'active')
     ->where('role', 'admin')
     ->order_by('created_at', 'desc')
     ->get();
```

### Documentation

All public methods and classes must be documented:

```php
/**
 * Get users by role with optional filtering
 *
 * This method retrieves users based on their role and allows
 * additional filtering through the where parameter.
 *
 * @param  string  $role   The user role to filter by
 * @param  array   $where  Additional where clauses
 * @param  int     $limit  Maximum number of results
 * @return DataMapper      Returns $this for chaining
 *
 * @example
 * $user = new User();
 * $user->get_by_role('admin', array('status' => 'active'), 10);
 */
public function get_by_role($role, $where = array(), $limit = NULL)
{
    $this->where('role', $role);
    
    if (!empty($where)) {
        $this->where($where);
    }
    
    if ($limit !== NULL) {
        $this->limit($limit);
    }
    
    return $this->get();
}
```

### Writing Tests

All new features and bug fixes should include tests:

```php
<?php
class User_Test extends PHPUnit\Framework\TestCase {
    
    protected function setUp(): void
    {
        // Setup test database
        $this->ci = &get_instance();
        $this->ci->load->database('test');
    }
    
    public function test_user_creation()
    {
        $user = new User();
        $user->name = 'Test User';
        $user->email = 'test@example.com';
        $user->password = 'password123';
        
        $this->assertTrue($user->save());
        $this->assertNotEmpty($user->id);
    }
    
    public function test_user_validation()
    {
        $user = new User();
        $user->name = 'Test';
        // Missing required email
        
        $this->assertFalse($user->save());
        $this->assertNotEmpty($user->error->email);
    }
    
    public function test_user_relationships()
    {
        $user = new User();
        $user->get_by_id(1);
        
        $post = new Post();
        $post->title = 'Test Post';
        $post->content = 'Content here';
        
        $user->save($post);
        
        $this->assertEquals(1, $post->user_id);
    }
    
    protected function tearDown(): void
    {
        // Cleanup test database
    }
}
```

### Test Coverage

Aim for high test coverage:

```bash
# Run tests with coverage
vendor/bin/phpunit --coverage-html coverage/

# Open coverage/index.html to view report
```

Target: **95%+ code coverage** for all new code

## Submitting Changes

### 1. Commit Your Changes

Write clear, descriptive commit messages:

```bash
# Good commit messages
git commit -m "feat: Add eager loading support for nested relationships"
git commit -m "fix: Resolve cascade delete issue in many-to-many relations"
git commit -m "docs: Update installation guide for PHP 8.2"
git commit -m "test: Add tests for soft delete functionality"
git commit -m "refactor: Improve query builder performance"

# Bad commit messages
git commit -m "Fixed stuff"
git commit -m "Update"
git commit -m "Changes"
```

#### Commit Message Format

Follow [Conventional Commits](https://www.conventionalcommits.org/):

```
<type>(<scope>): <description>

[optional body]

[optional footer]
```

Types:
- `feat`: New feature
- `fix`: Bug fix
- `docs`: Documentation changes
- `test`: Test changes
- `refactor`: Code refactoring
- `perf`: Performance improvement
- `chore`: Maintenance tasks

Examples:

```bash
feat(query): Add support for JSON column queries

- Implemented whereJson() method
- Added support for JSON path queries
- Updated documentation

Closes #123

fix(validation): Unique validation now respects soft deletes

Previously, unique validation would fail for soft deleted records.
This change excludes soft deleted records from unique checks.

Fixes #456
```

### 2. Push to Your Fork

```bash
git push origin feature/your-feature-name
```

### 3. Create Pull Request

1. Go to your fork on GitHub
2. Click "Pull Request"
3. Select your branch
4. Fill in the PR template:

```markdown
## Description
Brief description of changes

## Type of Change
- [ ] Bug fix
- [ ] New feature
- [ ] Breaking change
- [ ] Documentation update

## Changes Made
- Added X feature
- Fixed Y bug
- Updated Z documentation

## Testing
- [ ] All existing tests pass
- [ ] New tests added for new features
- [ ] Manual testing completed

## Checklist
- [ ] Code follows project style guidelines
- [ ] Documentation updated
- [ ] Tests added/updated
- [ ] No breaking changes (or documented if breaking)

Closes #issue_number
```

## Pull Request Review

### What to Expect

1. **Automated Checks** - CI will run tests automatically
2. **Code Review** - Maintainers will review your code
3. **Feedback** - You may receive requests for changes
4. **Approval** - Once approved, your PR will be merged

### Review Criteria

We review for:

- ✅ Code quality and style
- ✅ Test coverage
- ✅ Documentation completeness
- ✅ Backward compatibility
- ✅ Performance impact
- ✅ Security considerations

## Reporting Bugs

### Before Reporting

1. Search existing issues
2. Try with the latest version
3. Check the documentation

### Bug Report Template

```markdown
**Describe the bug**
A clear description of what the bug is.

**To Reproduce**
Steps to reproduce:
1. Create model...
2. Run query...
3. See error

**Expected behavior**
What you expected to happen.

**Actual behavior**
What actually happened.

**Code Example**
```php
$user = new User();
$user->where('id', 1)->get();
// Error occurs here
\```

**Environment**
- DataMapper Version: 2.0.0
- CodeIgniter Version: 3.1.13
- PHP Version: 8.1.0
- Database: MySQL 8.0
- Operating System: Ubuntu 22.04

**Error Messages**
```
Full error message here
```

**Additional context**
Any other relevant information.
```

## Feature Requests

### Feature Request Template

```markdown
**Is your feature request related to a problem?**
A clear description of the problem.

**Describe the solution you'd like**
What you want to happen.

**Describe alternatives you've considered**
Other approaches you've thought about.

**Example Usage**
```php
// How you envision using this feature
$user = new User();
$user->proposed_method()->get();
\```

**Benefits**
- Why this feature would be useful
- Who would benefit from it
- How it improves DataMapper

**Additional context**
Any other relevant information.
```

## Community

### Get Help

- 💬 [GitHub Discussions](https://github.com/P2GR/datamapper/discussions)
- 🐛 [Issue Tracker](https://github.com/P2GR/datamapper/issues)
- 📧 Email: support@datamapper.org

### Recognition

Contributors will be:

- Listed in CONTRIBUTORS.md
- Mentioned in release notes
- Recognized in documentation

Top contributors may be invited to join the core team!

## License

By contributing, you agree that your contributions will be licensed under the MIT License.

## Thank You! 🎉

Every contribution, no matter how small, makes DataMapper better for everyone. We appreciate your time and effort!

---

## Quick Links

- 📖 [Documentation](/)
- 🐛 [Report Bug](https://github.com/P2GR/datamapper/issues/new?template=bug_report.md)
- 💡 [Request Feature](https://github.com/P2GR/datamapper/issues/new?template=feature_request.md)
- 💬 [Discussions](https://github.com/P2GR/datamapper/discussions)
- 🔄 [Pull Requests](https://github.com/P2GR/datamapper/pulls)

## See Also

- [Changelog](/help/changelog) - Version history
- [Roadmap](/help/roadmap) - Future plans
- [Coding Standards](https://www.php-fig.org/psr/psr-12/) - PSR-12
- [Conventional Commits](https://www.conventionalcommits.org/) - Commit format

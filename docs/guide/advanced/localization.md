# Localization

Internationalize your DataMapper models with multi-language support for validation messages, error messages, and model data.

## Validation Message Localization

### Setup Language Files

Create language files in `application/language/`:

**application/language/english/datamapper_lang.php:**
```php
<?php
$lang['required'] = 'The %s field is required.';
$lang['valid_email'] = 'The %s field must contain a valid email address.';
$lang['min_length'] = 'The %s field must be at least %s characters long.';
$lang['max_length'] = 'The %s field cannot exceed %s characters.';
$lang['unique'] = 'The %s provided is already in use.';
```

**application/language/spanish/datamapper_lang.php:**
```php
<?php
$lang['required'] = 'El campo %s es obligatorio.';
$lang['valid_email'] = 'El campo %s debe contener un correo electrónico válido.';
$lang['min_length'] = 'El campo %s debe tener al menos %s caracteres.';
$lang['max_length'] = 'El campo %s no puede exceder %s caracteres.';
$lang['unique'] = 'El %s proporcionado ya está en uso.';
```

### Use Localized Messages

```php
// Set language
$this->lang->load('datamapper', 'spanish');

class User extends DataMapper {
    var $validation = array(
        'username' => array(
            'label' => 'Nombre de usuario',
            'rules' => array('required', 'min_length' => 3, 'unique')
        ),
        'email' => array(
            'label' => 'Correo electrónico',
            'rules' => array('required', 'valid_email', 'unique')
        )
    );
}

$user = new User();
$user->username = '';
$user->email = 'invalid';

if (!$user->save()) {
    // Displays in Spanish:
    // "El campo Nombre de usuario es obligatorio."
    // "El campo Correo electrónico debe contener un correo electrónico válido."
    echo $user->error->string;
}
```

## Model Data Localization

### Translatable Fields

```php
class Product extends DataMapper {
    var $table = 'products';
    
    // Translatable fields
    var $translatable = array('name', 'description');
    
    public function getTranslation($lang = null) {
        if ($lang === null) {
            $lang = $this->getCurrentLanguage();
        }
        
        $translation = new ProductTranslation();
        $translation->where('product_id', $this->id)
                    ->where('language', $lang)
                    ->get();
        
        if ($translation->exists()) {
            $this->name = $translation->name;
            $this->description = $translation->description;
        }
        
        return $this;
    }
}

class ProductTranslation extends DataMapper {
    var $table = 'product_translations';
    var $has_one = array('product');
}
```

### Database Schema

```sql
-- Main products table
CREATE TABLE products (
    id INT PRIMARY KEY AUTO_INCREMENT,
    sku VARCHAR(50) NOT NULL,
    price DECIMAL(10,2) NOT NULL,
    created_at DATETIME,
    updated_at DATETIME
);

-- Translations table
CREATE TABLE product_translations (
    id INT PRIMARY KEY AUTO_INCREMENT,
    product_id INT NOT NULL,
    language VARCHAR(5) NOT NULL,
    name VARCHAR(255) NOT NULL,
    description TEXT,
    FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE,
    UNIQUE KEY (product_id, language)
);
```

### Usage

```php
// Save product with translations
$product = new Product();
$product->sku = 'PROD-001';
$product->price = 29.99;
$product->save();

// English translation
$translation_en = new ProductTranslation();
$translation_en->product_id = $product->id;
$translation_en->language = 'en';
$translation_en->name = 'Blue Widget';
$translation_en->description = 'A wonderful blue widget';
$translation_en->save();

// Spanish translation
$translation_es = new ProductTranslation();
$translation_es->product_id = $product->id;
$translation_es->language = 'es';
$translation_es->name = 'Widget Azul';
$translation_es->description = 'Un maravilloso widget azul';
$translation_es->save();

// Retrieve with translation
$product = new Product();
$product->get_by_id(1);
$product->getTranslation('es');
echo $product->name; // "Widget Azul"
```

## Translation Trait

Create a reusable trait:

```php
trait Translatable {
    protected $current_language = 'en';
    protected $fallback_language = 'en';
    
    public function setLanguage($lang) {
        $this->current_language = $lang;
        return $this;
    }
    
    public function translate($lang = null) {
        if ($lang === null) {
            $lang = $this->current_language;
        }
        
        if (!isset($this->translatable) || empty($this->translatable)) {
            return $this;
        }
        
        $translation_class = get_class($this) . 'Translation';
        $translation = new $translation_class();
        
        $model_field = strtolower(get_class($this)) . '_id';
        
        $translation->where($model_field, $this->id)
                    ->where('language', $lang)
                    ->get();
        
        if ($translation->exists()) {
            foreach ($this->translatable as $field) {
                if (isset($translation->$field)) {
                    $this->$field = $translation->$field;
                }
            }
        } elseif ($lang !== $this->fallback_language) {
            // Try fallback language
            $this->translate($this->fallback_language);
        }
        
        return $this;
    }
    
    public function saveTranslation($lang, $data) {
        $translation_class = get_class($this) . 'Translation';
        $translation = new $translation_class();
        
        $model_field = strtolower(get_class($this)) . '_id';
        
        $translation->where($model_field, $this->id)
                    ->where('language', $lang)
                    ->get();
        
        if (!$translation->exists()) {
            $translation->$model_field = $this->id;
            $translation->language = $lang;
        }
        
        foreach ($data as $key => $value) {
            $translation->$key = $value;
        }
        
        return $translation->save();
    }
}

// Usage
class Post extends DataMapper {
    use Translatable;
    
    var $translatable = array('title', 'content');
}

// Create post with translations
$post = new Post();
$post->author_id = 1;
$post->status = 'published';
$post->save();

$post->saveTranslation('en', array(
    'title' => 'Hello World',
    'content' => 'Welcome to my blog!'
));

$post->saveTranslation('es', array(
    'title' => 'Hola Mundo',
    'content' => '¡Bienvenido a mi blog!'
));

// Retrieve with Spanish translation
$post = new Post();
$post->get_by_id(1);
$post->translate('es');
echo $post->title; // "Hola Mundo"
```

## Dynamic Language Detection

```php
class BaseController extends CI_Controller {
    
    public function __construct() {
        parent::__construct();
        
        // Detect language from URL, session, or browser
        $language = $this->detectLanguage();
        
        // Set CodeIgniter language
        $this->config->set_item('language', $language);
        $this->lang->load('datamapper', $language);
        
        // Set for DataMapper models
        if (class_exists('DataMapper')) {
            DataMapper::setDefaultLanguage($language);
        }
    }
    
    private function detectLanguage() {
        // 1. Check URL segment
        $lang = $this->uri->segment(1);
        if (in_array($lang, array('en', 'es', 'fr', 'de'))) {
            return $lang;
        }
        
        // 2. Check session
        if ($this->session->userdata('language')) {
            return $this->session->userdata('language');
        }
        
        // 3. Check user preference
        $user_id = $this->session->userdata('user_id');
        if ($user_id) {
            $user = new User();
            $user->get_by_id($user_id);
            if ($user->exists() && $user->preferred_language) {
                return $user->preferred_language;
            }
        }
        
        // 4. Check browser language
        if (isset($_SERVER['HTTP_ACCEPT_LANGUAGE'])) {
            $lang = substr($_SERVER['HTTP_ACCEPT_LANGUAGE'], 0, 2);
            if (in_array($lang, array('en', 'es', 'fr', 'de'))) {
                return $lang;
            }
        }
        
        // 5. Default
        return 'en';
    }
}
```

## Complete Example

```php
// Product with translations
class Product extends DataMapper {
    use Translatable;
    
    var $translatable = array('name', 'description');
    var $has_many = array('translation' => array(
        'class' => 'product_translation',
        'other_field' => 'product'
    ));
}

class ProductTranslation extends DataMapper {
    var $table = 'product_translations';
    var $has_one = array('product');
}

// Controller
class Products extends BaseController {
    
    public function view($id) {
        $product = new Product();
        $product->get_by_id($id);
        
        if ($product->exists()) {
            // Translate to current language
            $product->translate($this->current_language);
            
            $data = array(
                'product' => $product
            );
            
            $this->load->view('products/view', $data);
        } else {
            show_404();
        }
    }
    
    public function admin_edit($id) {
        $product = new Product();
        $product->get_by_id($id);
        
        if ($this->input->post()) {
            // Save base product data
            $product->from_array($this->input->post(), array('sku', 'price'));
            $product->save();
            
            // Save translations
            $languages = array('en', 'es', 'fr', 'de');
            foreach ($languages as $lang) {
                $translation_data = array(
                    'name' => $this->input->post("name_$lang"),
                    'description' => $this->input->post("description_$lang")
                );
                $product->saveTranslation($lang, $translation_data);
            }
            
            redirect('admin/products');
        }
        
        // Load all translations for editing
        $translations = array();
        foreach (array('en', 'es', 'fr', 'de') as $lang) {
            $product->translate($lang);
            $translations[$lang] = array(
                'name' => $product->name,
                'description' => $product->description
            );
        }
        
        $data = array(
            'product' => $product,
            'translations' => $translations
        );
        
        $this->load->view('admin/products/edit', $data);
    }
}
```

## Related Documentation

- [Validation](/guide/advanced/validation)
- [Model Fields](../models/fields)
- [CodeIgniter Language Class](http://codeigniter.com/user_guide/libraries/language.html)

## See Also

- [Best Practices](../../help/faq#Internationalization)
- [Multi-Language Sites](../../examples/multilanguage)
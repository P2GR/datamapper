# Transactions

[**transactions** in very much the same way that CodeIgniter does (read CodeIgniter [Transactions](http://codeigniter.com/user_guide/database/transactions)), obviously because it uses the same methods! The only real difference is that you'll be calling the transaction methods directly on your DataMapper objects. For example:

```php

// Create user
$u = new User();

// Populate with form data
$u->username = $this->input->post('username');
$u->email = $this->input->post('email');
$u->password = $this->input->post('password');
$u->confirm_password = $this->input->post('confirm_password');

// Begin transaction
$u->trans_begin();

// Attempt to save user
$u->save();

// Check status of transaction
if ($u->trans_status() === FALSE)
{
    // Transaction failed, rollback
    $u->trans_rollback();

    // Add error message
    $u->error_message('transaction', 'The transaction failed to save (insert)');
}
else
{
    // Transaction successful, commit
    $u->trans_commit();
}

// Show all errors
echo $u->error->string;

// Or just show the transaction error we manually added
echo $u->error->transaction;

```

[[configuration setting](/guide/getting-started/configuration) called *auto_transaction* which, when set to TRUE, will automatically wrap your save and delete calls in transactions, even going so far as to give you an error message if the transaction was rolled back.

So, instead of the above, you can do the following and get the same result (provided you've got *auto_transaction* set to TRUE of course):

```php

// Create user
$u = new User();

// Populate with form data
$u->username = $this->input->post('username');
$u->email = $this->input->post('email');
$u->password = $this->input->post('password');
$u->confirm_password = $this->input->post('confirm_password');

// Attempt to save user
if ($u->save())
{
    // Saved successfully
}
else
{
    // Show all errors
    echo $u->error->string;

    // Or just show the transaction error
    echo $u->error->transaction;
}

```

***Important:*** You should check the result of a save() operation. Even if the transaction status indicates that everything went well, the save() could have failed, for example because of a failed validation.
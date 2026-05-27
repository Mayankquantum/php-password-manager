# PHP OOP Password Manager

A PHP + MySQL application that **generates** strong passwords and **stores** them
encrypted in a database. Built with object-oriented PHP as required by the task.

---

## How each task requirement is met

| # | Requirement | Where |
|---|-------------|-------|
| 1 | PHP, object-oriented | `src/` — six classes, an abstract base, inheritance |
| 2 | Data in MySQL | `schema.sql`, `src/Database.php` (PDO) |
| 3 | Sign-up creates user; password hashed in DB | `User::register()` — `password_hash()` (bcrypt) |
| 4 | A per-user KEY encrypts passwords; KEY is AES-wrapped with the **plain** master password and never changes | `User::register()` + `Crypto` (AES-256-GCM, PBKDF2) |
| 5 | Custom password generator class with length / upper / lower / digits / special (units **or** percent) set from the GUI | `src/PasswordGenerator.php`, `dashboard.php` |
| 6 | Save records: service name, auto date/time, generated or chosen password | `PasswordEntry::add()`, `password_entries` table |
| Note | Changing the login password re-wraps the KEY; the KEY itself never changes | `User::changeMasterPassword()` |
| 7 | DB + UML class diagrams | `diagrams/uml_classes.png`, `diagrams/db_schema.png` |

---

## Security design (the important part)

This is **envelope encryption**, the same pattern real password managers use:

1. **Login check** — the master password is hashed with bcrypt (`password_hash`).
   This hash is one-way and is used *only* to verify login.
2. **Data KEY** — at sign-up a random 256-bit KEY is generated. This KEY is what
   actually encrypts every saved password (AES-256-GCM). It is **never changed**
   for the life of the account.
3. **Wrapping the KEY** — the KEY is itself AES-encrypted with a key derived from
   the *plain* master password via PBKDF2-SHA256 (`key_salt` is stored, the plain
   password is not). Only `encrypted_key` lands in the database.
4. **Login** — the plain password is verified against the hash, then used to derive
   the wrapping key, decrypt `encrypted_key`, and recover the data KEY. The KEY is
   held in the PHP session for the duration of the session.
5. **Changing the master password** — the KEY is unwrapped with the old password
   and re-wrapped with the new one (fresh salt). Because the KEY itself is
   unchanged, every previously saved password stays decryptable. This is exactly
   what the task note demands.

The plain master password is therefore never stored anywhere.

---

## Setup

Requires PHP 8.1+ (with `pdo_mysql`, `openssl`, `mbstring`) and MySQL/MariaDB.

```bash
# 1. Create the database and tables
mysql -u root -p < schema.sql

# 2. Set your MySQL credentials
#    edit config.php  (DB_USER / DB_PASS)

# 3. Run the built-in PHP server from the project root
php -S localhost:8000

# 4. Open http://localhost:8000 in a browser
```

---

## Using the app

1. **Sign up** with a login and master password.
2. **Log in.**
3. On the **Vault** page:
   - Set the character counts (e.g. 2 lowercase, 3 uppercase, 2 digits, 2 special),
     optionally a total length, and click **Generate**.
   - Paste the generated password (or type your own) into **Save a credential**,
     give it a service name, and **Save**. The date/time is filled automatically.
   - Saved passwords are listed (decrypted on the fly) with their save time.
4. **Change password** re-wraps your KEY — saved passwords remain intact.

---

## Verifying the logic

`_test.php` checks the generator and the full encryption flow without a database:

```bash
php _test.php      # 14 assertions, all should pass
```

---

## Project structure

```
password-manager/
├── config.php              DB credentials
├── bootstrap.php           autoloader, session, helpers
├── schema.sql              MySQL tables
├── index.php               redirect
├── register.php            sign-up
├── login.php               log in
├── logout.php
├── dashboard.php           generator + save + vault list
├── change_password.php
├── _test.php               logic tests
├── assets/style.css
├── partials/               shared header / footer
├── src/
│   ├── Database.php         PDO singleton
│   ├── Crypto.php           AES-256-GCM + PBKDF2
│   ├── PasswordGenerator.php
│   ├── Model.php            abstract base (inheritance)
│   ├── User.php             extends Model
│   └── PasswordEntry.php    extends Model
└── diagrams/
    ├── uml_classes.png/.svg
    └── db_schema.png/.svg
```

---

## For the submitted report

The report needs screenshots **of your own running app and database**. Capture:

- The sign-up page, the login page.
- The Vault page after generating a password (show the parameters + result).
- The Vault page showing a few saved credentials with their timestamps.
- The MySQL tables: `SELECT * FROM users;` and `SELECT * FROM password_entries;`
  (so the grader can see `password_hash`, `encrypted_key`, and `secret_enc` are
  all encrypted/hashed, not plain text).
- The two diagrams from `diagrams/`.

> Known simplifications (worth a sentence in the report): the data KEY lives in the
> server-side session while logged in; CSRF tokens and rate-limiting are out of
> scope for this assignment.

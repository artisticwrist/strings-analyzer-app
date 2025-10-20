## 🧠 String Analysis API – Laravel Project HNG stage 1 task

This Laravel-based API analyzes strings, detects properties like palindromes, character frequency, and supports powerful natural language query filtering.

---

## 🛠️ Requirements

* PHP >= 8.1
* Composer
* Laravel >= 10
* MySQL or SQLite (or any Laravel-supported DB)
* Laravel Sail or XAMPP (optional for local dev)
* Postman or curl for testing

---

## 🚀 Setup Instructions

Follow these steps to set up the project locally.

### 1. 📦 Clone the Repository

```bash
git clone https://github.com/your-username/your-repo-name.git
cd your-repo-name
```

### 2. 🧰 Install Dependencies

```bash
composer install
```

### 3. ⚙️ Create Environment File

```bash
cp .env.example .env
```

Then edit `.env` and set your DB credentials:

```env
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=string_analysis
DB_USERNAME=root
DB_PASSWORD=
```

### 4. 🔑 Generate App Key

```bash
php artisan key:generate
```

### 5. 🧬 Run Migrations

Make sure your database exists, then:

```bash
php artisan migrate
```

### 6. ✅ Run the Local Development Server

```bash
php artisan serve
```

By default, the API will be available at:

```
http://localhost:8000
```

---

## 🧪 Testing the Endpoints

Use **Postman**, **curl**, or any HTTP client.

---

### 📥 `POST /strings`

**Analyze and store a string.**

**Request:**

```http
POST /strings
Content-Type: application/json

{
  "value": "madam"
}
```

**Response:** `201 Created`

---

### 🔍 `GET /strings/filter-by-natural-language`

**Filter strings using natural language queries.**

**Example:**

```http
GET /strings/filter-by-natural-language?query=all%20single%20word%20palindromic%20strings
```

**Response:** `200 OK`

```json
{
  "data": [...],
  "count": 1,
  "interpreted_query": {
    "original": "all single word palindromic strings",
    "parsed_filters": {
      "word_count": 1,
      "is_palindrome": true
    }
  }
}
```

---

### 🔎 `GET /strings`

**Filter with query params (non-natural language).**

**Example:**

```http
GET /strings?is_palindrome=true&min_length=5
```

**Response:** `200 OK` or `404` if no matches

---

### 🔎 `GET /strings/{string_value}`

**Check if a string exists and retrieve its analysis.**

**Example:**

```http
GET /strings/madam
```

**Response:** `200 OK` with full analysis or `404 Not Found`

---

### ❌ `DELETE /strings/{string_value}`

**Delete a string from the database.**

**Example:**

```http
DELETE /strings/madam
```

**Response:** `204 No Content` or `404 Not Found`

---

## 📚 Supported Natural Language Queries

The `/strings/filter-by-natural-language` endpoint supports intuitive queries like:

| Query Example                                    | Parsed Filters                                   |
| ------------------------------------------------ | ------------------------------------------------ |
| all single word palindromic strings              | `is_palindrome = true`, `word_count = 1`         |
| strings longer than 10 characters                | `min_length = 11`                                |
| palindromic strings that contain the first vowel | `is_palindrome = true`, `contains_character = a` |
| strings containing the letter z                  | `contains_character = z`                         |

---

## 🧪 Sample Test Data

You can insert sample data via the `POST /strings` endpoint:

```json
{ "value": "madam" }
{ "value": "racecar" }
{ "value": "apple" }
{ "value": "deed" }
```

---

## 🧼 Optional: Seed Test Strings (Developer Shortcut)

You can create a tinker session and add data manually:

```bash
php artisan tinker
```

```php
Http::post('http://localhost:8000/strings', ['value' => 'madam']);
Http::post('http://localhost:8000/strings', ['value' => 'deed']);
```

---

## 📂 Project Structure Overview

| Path                                         | Purpose                  |
| -------------------------------------------- | ------------------------ |
| `app/Http/Controllers/StringsController.php` | Core logic for endpoints |
| `routes/api.php`                             | All API routes           |
| `app/Models/Strings.php`                     | Eloquent model           |
| `database/migrations/`                       | DB schema                |

---

## 🛡 Security Notes

* Basic validation is in place.
* Duplicate strings are rejected (409).

##postman documentation
https://documenter.getpostman.com/view/29651789/2sB3QNp8Wb
---

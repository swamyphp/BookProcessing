# Interactive Book Package Processing

Minimal implementation for uploading ZIP packages, inspecting contents, renaming/replacing files, validating required files, creating a book folder, running a Python processor and inserting DB records.

Setup:

1. Create MySQL database and import `db/schema.sql`.
2. Edit `config/db.php` with DB credentials.
3. Install Python deps: `pip install -r requirements.txt`.
4. Ensure `python` is available in PATH for PHP `exec()`.

Usage:

- Open `index.php` in browser. Upload a ZIP package containing `Manuscript.docx` etc.
- Use the tree to rename/replace/delete files and validate.
- Enter a `Book Short Name` and click `Create Book` to process and insert DB records.

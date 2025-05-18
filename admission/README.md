# Admission Portal Database Updates

## Adding `full_name` column to the `personal_details` table

Follow these steps to update your database schema:

1. Log in to your MySQL server
2. Run the SQL commands in the `alter_personal_details.sql` file

```sql
-- Add full_name column to personal_details table
ALTER TABLE personal_details ADD COLUMN full_name VARCHAR(100) NULL AFTER application_id;

-- Update any existing records with name data from users table
UPDATE personal_details pd
JOIN applications a ON pd.application_id = a.id
JOIN users u ON a.user_id = u.id
SET pd.full_name = CONCAT(u.first_name, ' ', u.last_name)
WHERE pd.full_name IS NULL OR pd.full_name = '';

-- Add an index on full_name for faster searches
ALTER TABLE personal_details ADD INDEX idx_personal_details_full_name (full_name);
```

## Applying the Update

You can apply the update by running:

```bash
mysql -u [username] -p [database_name] < admission/alter_personal_details.sql
```

Or by executing the commands directly in your MySQL client.

## Changes Made

The following files have been modified to handle the new `full_name` column:

1. `save_form_step.php` - Updated to save full name to the database
2. `load_form_data.php` - Updated to retrieve full name from the database
3. `admission-form.php` - Updated to fetch full name when loading the form
4. `admission-form.html` - Updated to populate the full name field with saved data

## Testing

After applying the database changes:

1. Try submitting a new application
2. Verify that the full name is saved correctly
3. Log out and log back in to check if the form loads with the saved full name 
 
 
 
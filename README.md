# EECS Project Showcase
This repository contains source code for the Project Showcase website provided by Oregon State University EECS.

## Site Configuration
To facilitate development, staging, and deployment of the site, configuration (inluding the root `.htaccess` file) is
no longer kept in source control. Instead, please refere to this README for instructions on how to configure the
site.

### Apache `.htaccess` file
The `.htaccess` file should be placed in the root of this repository. It **MUST** have the following contents. 
**Be sure to update this README whenever additional configuration is added.**

```ini
# Deny access to files with specific extensions
<FilesMatch "\.(ini|sh|sql)$">
Order allow,deny
Deny from all
</FilesMatch>

# Deny access to filenames starting with dot(.)
<FilesMatch "^\.">
Order allow,deny
Deny from all
</FilesMatch>

RewriteEngine On

RewriteBase <URL_FROM_BASE_TO_SITE_ROOT>

# If the requested file is not a directory or a file, we need to append .php
RewriteCond %{REQUEST_FILENAME} !-d
RewriteCond %{REQUEST_FILENAME} !-f
RewriteCond %{REQUEST_FILENAME} (pages/|downloaders/|api/)
RewriteRule ^([^\.]+)$ $1.php [NC,L]

# Prepend `pages/` to the URI if it needs it
RewriteCond %{REQUEST_URI} !/(api|assets|downloaders|pages|masq)
RewriteRule ^(.*)$ pages/$1

```

Replace the following placeholders with their appropriate values:
- `URL_FROM_BASE_TO_SITE_ROOT`: for example, if my server base was at `http://example.com/` and my website lived at
  `http://example.com/foo/bar/baz/mysite/`, I would replace the placeholder with `/foo/bar/baz/mysite/`


### Sitewide `config.ini` file
Instead of having copies of the configuration INI file, there will only be one sitewide config file. This file **MUST**
be named `config.ini` and be placed at the root of the repository. It should have the following sections and contents:

```ini
private_files = ; absolute path to the directory containing private files, such as database config and logs

[server]
display_errors =                    ; whether or not to write errors to the output buffer (yes, no)
display_errors_severity =           ; indicates the level at which to display errors (notice, warn, all)
upload_profile_image_file_path =    ; the path from the `private_files` where uploaded profile images should be stored
upload_resume_file_path =           ; the path from the `private_files` where uploaded resumes should be stored
upload_artifact_file_path =         ; the path from the `private_files` where uploaded artifact files should be stored 
upload_project_image_file_path =    ; the path from the `private_files` where uploaded project images should be stored

[email]
subject_tag =                       ; [optional] tag to include in the subject header of emails sent by the server
from_address =                      ; the address to use in the 'From' header of emails sent by the server
admin_addresses[] =                 ; an array of administrator email addresses to send important notifications to

[client]
base_url =                          ; the base URL of the website

[logger]
log_file =                          ; the name of the log file in the `private_files` directory to write log messages to
level =                             ; the level at which to log messages

[database]
config_file =                       ; the name of the database configuration file in the `private_files` directory
```

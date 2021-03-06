# Changelog
All notable changes to this project will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.0.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## 1.1.1 - 2019-06-13
### Added
- Display all available and sorted keywords on input focus
- Notice that only keywords from the dropdown can be used

### Fixed
- Failure to upload non-PNG files as profile images
- Various bug fixes and enhancements

## 1.1.0 - 2019-06-06
### Added
- Support for project keywords
- Project browsing functionality for site visiters (no login required)
- Admin dashboard
- Support for hiding projects containing questionable or inappropriate content
- Autocropping of profile images

### Changed
- Configuration for site now handled via global `config.ini` in project root
- Root Apache server configuration (`.htaccess`) file removed from repository

### Fixed
- Italicized text in edit project page

## 1.0.0 - 2019-05-10
### Added
- Support for creating and editing a user's information in their profile
- Support for uploading images, artifacts, and resumes
- Support for creating and editing a project
- Support for sending collaboration invitations for projects via email
- CAS authentication
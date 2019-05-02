/**
 * Contains functionality for submitting changes to profile information in the project showcase.
 */

/**
 * Event handler to enable the 'Save Changes' button after a user makes a change to their profile information.
 */
let changesDetected = false;
function onEditProfileFormInputChange() {
    if (!changesDetected) {
        $('#btnEditProfileSubmit').attr('disabled', false);
        changesDetected = true;
    }
}
$('#formEditProfile input[type=text]').keyup(onEditProfileFormInputChange);
$('#formEditProfile textarea').keyup(onEditProfileFormInputChange);
$('#formEditProfile input[type=file]').change(onEditProfileFormInputChange);
$('#formEditProfile input[type=checkbox]').change(onEditProfileFormInputChange);

/**
 * Changes the label for the profile image input so that it displays the name of the file that was selected. This
 * will also change the preview image.
 */
function onProfileImageSelect() {
    if (this.files.length > 0) {
        // Get a preview of the selected files
        let reader = new FileReader();
        reader.onload = e => {
            let $preview = $('#profileImagePreview');
            $preview.attr('src', e.target.result);
            $preview.css({
                width: '100px',
                height: '100px'
            });
        };
        reader.readAsDataURL(this.files[0]);

        // Show the name of the file
        $('#profileImageLabel').text(this.files[0].name);
    } else {
        snackbar('Please select a single image', 'error');
    }
}
$('#profileImage').change(onProfileImageSelect);

/**
 * Changes the label for the resume image input so that it displays the name of the file that was selected.
 */
function onResumeFileSelect() {
    $('#profileResumeLabel').text(this.files[0].name);
}
$('#profileResume').change(onResumeFileSelect);

/**
 * Handler for when a user makes changes to their profile and then clicks the 'Save Changes' button.
 *
 * This function will make two asynchronous calls. The first will update information about the user's profile. The
 * second will attempt to upload any files that the user may have choosen.
 *
 * A small 'loading' or 'in-progress' icon will appear next to the 'Save Changes' button while the
 * asynchronous calls are being processed.
 */
let pendingInfoUpdateResponse = false;
let pendingFileUploadResponse = false;
function onEditProfileFormSubmit() {
    // Capture the form
    let form = new FormData(document.getElementById('formEditProfile'));

    // Serialize the form elements into JSON (except for the files). The files (if they exist) we will append to
    // a separate request that will use a urlformencoded request body.
    let bodyInfo = {
        action: 'saveProfile'
    };

    let bodyFiles = new FormData();
    let bodyFilesCount = 0;

    for (const [key, value] of form.entries()) {
        if ((key == 'profileResume' || key == 'profileImage') && value.size > 0) {
            bodyFiles.append(key, value);
            bodyFilesCount++;
        } else {
            bodyInfo[key] = value;
        }
    }

    // Make the request for updating information
    api.post('/profiles.php', bodyInfo)
        .then(res => {
            onApiResponse('info', true);
            snackbar(res.message, 'success');
        })
        .catch(err => {
            onApiResponse('info', false);
            snackbar(err.message, 'error');
        });
    pendingInfoUpdateResponse = true;

    // Make the request for uploading files if they exist
    if (bodyFilesCount > 0) {
        api.post('/upload.php', bodyFiles, true)
            .then(res => {
                onApiResponse('file', true);
                snackbar(res.message, 'success');
            })
            .catch(err => {
                onApiResponse('file', false);
                snackbar(err.message, 'error');
            });
        pendingFileUploadResponse = true;
    }

    $('#btnEditProfileSubmit').attr('disabled', true);
    $('#formEditProfileLoader').show();
    snackbar('Saving profile', 'info');
    return false;
}
$('#formEditProfile').on('submit', onEditProfileFormSubmit);

/**
 * Captures common functionality when an API response is received.
 */
function onApiResponse(type, success) {
    switch (type) {
        case 'info':
            pendingInfoUpdateResponse = false;
            break;
        case 'file':
            pendingFileUploadResponse = false;
            break;
    }
    if (!pendingFileUploadResponse && !pendingInfoUpdateResponse) {
        $('#formEditProfileLoader').hide();
        if (!success) {
            $('#btnEditProfileSubmit').attr('disabled', false);
        }
    }
}

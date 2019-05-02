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
 *
 * TODO: add cropping functionality here
 */
function onProfileImageSelect() {
    if (this.files.length > 0) {
        // Get a preview of the selected files
        let reader = new FileReader();
        reader.onload = e => {
            let $preview = $('#profileImagePreview');
            $preview.attr('src', e.target.result);
            $preview.show();
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
let pendingResumeUpload = false;
let pendingProfileImageUpload = false;
let newResumeSelected = false;
let newProfileImageSelected = false;
function onEditProfileFormSubmit() {
    // Capture the form
    let form = new FormData(document.getElementById('formEditProfile'));

    // Serialize the form elements into JSON (except for the files). The files (if they exist) we will append to
    // a separate request that will use a urlformencoded request body.
    let bodyInfo = {
        action: 'saveProfile'
    };

    let bodyResume = new FormData();

    let bodyProfileImage = new FormData();

    for (const [key, value] of form.entries()) {
        if (key == 'profileResume' && value.size > 0) {
            bodyResume.append(key, value);
            newResumeSelected = true;
        } else if (key == 'profileImage' && value.size > 0) {
            bodyProfileImage.append(key, value);
            newProfileImageSelected = true;
        } else {
            bodyInfo[key] = value;
        }
    }

    // Make the request for updating information
    api.post('/profiles.php', bodyInfo)
        .then(res => {
            onApiResponse('info', true);
        })
        .catch(err => {
            onApiResponse('info', false);
            snackbar(err.message, 'error');
        });
    pendingInfoUpdateResponse = true;

    // Request to upload the resume if there is one
    if (newResumeSelected) {
        bodyResume.append('action', 'uploadResume');
        bodyResume.append('userId', bodyInfo.userId);
        api.post('/resumes.php', bodyResume, true)
            .then(res => {
                onApiResponse('resume', true);
            })
            .catch(err => {
                onApiResponse('resume', false);
                snackbar(err.message, 'error');
            });
        pendingResumeUpload = true;
    }

    // Request to upload the profile image if there is one
    if (newProfileImageSelected) {
        bodyProfileImage.append('action', 'uploadImage');
        bodyProfileImage.append('userId', bodyInfo.userId);
        api.post('/profile-images.php', bodyProfileImage, true)
            .then(res => {
                onApiResponse('image', true);
            })
            .catch(err => {
                onApiResponse('image', false);
                snackbar(err.message, 'error');
            });
        pendingProfileImageUpload = true;
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
        case 'resume':
            pendingResumeUpload = false;
        case 'image':
            pendingProfileImageUpload = false;
            break;
    }
    if (!pendingResumeUpload && !pendingProfileImageUpload && !pendingInfoUpdateResponse) {
        $('#formEditProfileLoader').hide();
        if (!success) {
            $('#btnEditProfileSubmit').attr('disabled', false);
        } else {
            // Replace the profile image text
            if (newProfileImageSelected) {
                $('#profileImageText').text(`
                    Current Profile Image
                `);
                $('#btnProfileImageDelete').show();
                newProfileImageSelected = false;
            }

            // Replace the resume text
            if (newResumeSelected) {
                $('#resumeText').html(`
                    You previously uploaded a resume. 
                    <a href='downloaders/resumes?id=${$('#userId').val()}'>Download</a>
                `);
                newResumeSelected = false;
            }

            snackbar('Successfully saved profile', 'success');
        }
    }
}

/**
 * Deletes the current profile image after confirming with the user that this is what they want.
 */
function onDeleteProfileImage() {
    let body = new FormData();
    body.append('action', 'deleteImage');
    body.append('userId', $('#userId').val());

    api.post('/profile-images.php', body, true)
        .then(res => {
            $('#profileImageText').text(`
            No Image has been uploaded
        `);
            $('#profileImagePreview').attr('src', '');
            $('#profileImagePreview').hide();
            $('#btnProfileImageDelete').hide();
            snackbar(res.message, 'success');
        })
        .catch(err => {
            snackbar(err.message, 'error');
        });
}
$('#btnProfileImageDelete').click(onDeleteProfileImage);

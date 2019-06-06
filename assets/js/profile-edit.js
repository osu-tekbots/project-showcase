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
$('#formEditProfile input[type=email]').keyup(onEditProfileFormInputChange);
$('#formEditProfile input[type=tel]').keyup(onEditProfileFormInputChange);
$('#formEditProfile input[type=file]').change(onEditProfileFormInputChange);
$('#formEditProfile input[type=checkbox]').change(onEditProfileFormInputChange);

/**
 * Disables the ability for the form to submit on an 'Enter' key press
 */
function onFormInputKeyup(e) {
    var keyCode = e.keyCode || e.which;
    if (keyCode === 13) {
        e.preventDefault();
        return false;
    }
}
$('#formEditProfile').on('keyup', onFormInputKeyup);

let profileImageFileBlob = null;
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
            let image = new Image();
            image.src = e.target.result;
            image.onload = function() {
                smartcrop.crop(image, { width: 200, height: 200 }).then(res => {
                    let cropped = crop(image, res.topCrop.width, res.topCrop.height, res.topCrop.x, res.topCrop.y);
                    let block = cropped.split(';');
                    let contentType = block[0].split(':')[1];
                    let data = block[1].split(',')[1];
                    profileImageFileBlob = b64toBlob(data, contentType);
                    let $preview = $('#profileImagePreview');
                    $preview.attr('src', cropped);
                    $preview.show();
                });
            };
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
 * Automatically crops an image and returns the contents as a Bas64 encoded string
 * 
 * @see https://yellowpencil.com/blog/cropping-images-with-javascript/
 *
 * @param {Image} imgObj the image to crop
 * @param {number} newWidth the width of the new image
 * @param {number} newHeight the height of the cropped image
 * @param {number} startX the x position to start cropping from
 * @param {number} startY the y position to start cropping from
 * @returns {string} the base64 encoded cropped image
 */
function crop(imgObj, newWidth, newHeight, startX, startY) {
    let ratio = 1;
    //set up canvas for thumbnail
    var tnCanvas = document.createElement('canvas');
    var tnCanvasContext = tnCanvas.getContext('2d');
    tnCanvas.width = newWidth;
    tnCanvas.height = newHeight;

    /* use the sourceCanvas to duplicate the entire image. 
    This step was crucial for iOS4 and under devices. */
    var bufferCanvas = document.createElement('canvas');
    var bufferContext = bufferCanvas.getContext('2d');
    bufferCanvas.width = imgObj.width;
    bufferCanvas.height = imgObj.height;
    bufferContext.drawImage(imgObj, 0, 0);

    /* now we use the drawImage method to take the pixels from our bufferCanvas and draw them into our 
    thumbnail canvas */
    tnCanvasContext.drawImage(
        bufferCanvas,
        startX,
        startY,
        newWidth * ratio,
        newHeight * ratio,
        0,
        0,
        newWidth,
        newHeight
    );
    return tnCanvas.toDataURL();
}

/**
 * Convert a base64 string in a Blob according to the data and contentType.
 * 
 * @see http://stackoverflow.com/questions/16245767/creating-a-blob-from-a-base64-string-in-javascript
 * 
 * @param b64Data {String} Pure base64 string without contentType
 * @param contentType {String} the content type of the file i.e (image/jpeg - image/png - text/plain)
 * @param sliceSize {Int} SliceSize to process the byteCharacters
 * @return Blob
 */
function b64toBlob(b64Data, contentType, sliceSize) {
    contentType = contentType || '';
    sliceSize = sliceSize || 512;

    var byteCharacters = atob(b64Data);
    var byteArrays = [];

    for (var offset = 0; offset < byteCharacters.length; offset += sliceSize) {
        var slice = byteCharacters.slice(offset, offset + sliceSize);

        var byteNumbers = new Array(slice.length);
        for (var i = 0; i < slice.length; i++) {
            byteNumbers[i] = slice.charCodeAt(i);
        }

        var byteArray = new Uint8Array(byteNumbers);

        byteArrays.push(byteArray);
    }

    var blob = new Blob(byteArrays, { type: contentType });
    return blob;
}

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
            bodyProfileImage.append(key, profileImageFileBlob);
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
    changesDetected = false;
    $('#formEditProfileLoader').show();
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
            changesDetected = true;
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
                    You have uploaded a resume.
                `);
                $('#aResumeDownload').attr('href', $('#userId').val());
                $('#resumeActions').show();
                newResumeSelected = false;
            }

            snackbar('Successfully saved profile', 'success');
        }
    }
}

/**
 * Sends a request to delete the current profile image after confirming with the user that this is what they want.
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

/**
 * Sends a request to delete the current resume for the profile from the server
 */
function onDeleteResume() {
    let body = new FormData();
    body.append('action', 'deleteResume');
    body.append('userId', $('#userId').val());

    api.post('/resumes.php', body, true)
        .then(res => {
            $('#resumeText').text(`
                No resume has been uploaded
            `);
            $('#resumeActions').hide();
            snackbar(res.message, 'success');
        })
        .catch(err => {
            snackbar(err.message, 'error');
        });
}
$('#btnResumeDelete').click(onDeleteResume);

/**
 * Sends a request to add a new project to the user's profile.
 */
function onAddProject() {
    $title = $('#newProjectTitle');
    $description = $('#newProjectDescription');

    let body = {
        action: 'createProject',
        userId: $('#userId').val(),
        title: $title.val(),
        description: $description.val()
    };

    if (body.title == '') {
        return snackbar('Please enter a project title', 'error');
    }
    if (body.description == '') {
        return snackbar('Please enter a project description', 'error');
    }

    api.post('/showcase-projects.php', body)
        .then(res => {
            snackbar(res.message, 'success');
            addRowToTableBodyProjects(res.content.id, body.title, body.description);
            $title.val('');
            $description.val('');
        })
        .catch(err => {
            snackbar(err.message, 'error');
        });
}
$('#btnAddProject').click(onAddProject);

/**
 * Adds a new row to the projects table in response to a successful additiona (via AJAX) of a new project
 *
 * @param {string} pid the ID of the project to dynamically add to the table
 * @param {string} title the title of the project
 * @param {string} description the description for the project
 */
function addRowToTableBodyProjects(pid, title, description) {
    if (description.length > 280) {
        description = description.substr(0, 280) + '...';
    }
    $('#tableBodyProjects').append(`
        <tr>
            <td>${title}</td>
            <td>${description}</td>
            <td>
                <a href="projects/edit?id=${pid}" class="btn btn-sm btn-light">
                    <i class='fas fa-edit'></i>
                </a>
            </td>
        </tr>
    `);
    $('#tableProjects').show();
}

function onShowContactInfoChange() {
    if ($(this).prop('checked')) {
        $('#divContactInfo').show();
    } else {
        $('#divContactInfo').hide();
    }
}
$('#publishContactInfo').change(onShowContactInfoChange);

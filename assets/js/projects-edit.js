/**
 * This file contains the JavaScript for the Edit Project page, handling dynamic styling and form submissions.
 */

/**
 * Handles the form submission for editing general information about a project. Sends an AJAX request to the
 * API endpoint for showcase projects.
 */
function onEditProjectGeneralSubmit() {
    let body = {
        action: 'updateProject'
    };

    let form = new FormData(this);

    for (const [key, value] of form.entries()) {
        body[key] = value;
    }

    api.post('/showcase-projects.php', body)
        .then(res => {
            snackbar(res.message, 'success');
        })
        .catch(err => {
            snackbar(err.message, 'error');
        });

    return false;
}
$('#formEditProjectGeneral').submit(onEditProjectGeneralSubmit);

/**
 * Detects when there is a change to the image input and changes the label text to match the name of the file to
 * upload. This will also change the image preview src.
 */
function onImageFileChange() {
    // Show the name of the file
    if (this.files.length > 0) {
        // Get a preview of the selected files
        let reader = new FileReader();
        reader.onload = e => {
            let $preview = $('#projectImagePreview');
            $preview.attr('src', e.target.result);
            $preview.show();
        };
        reader.readAsDataURL(this.files[0]);
        $('#labelImageFile').text(this.files[0].name);
        $('#selectProjectImages').val('');
        $('#selectProjectImages')
            .data('picker')
            .sync_picker_with_select();
    }
}
$('#imageFile').change(onImageFileChange);

/**
 * Initiate the image picker
 */
function initializeImagePicker() {
    $('#selectProjectImages').imagepicker({
        selected: onImagePickerOptionChange,
    });
    $('#selectProjectImages')
        .data('picker')
        .sync_picker_with_select();
    
    let select = document.querySelector('#selectProjectImages');
    let selectedImage = select.options[select.selectedIndex];
    onUpdateImageButtons(selectedImage);
}
initializeImagePicker();

/**
 * Handles rendering the new image preview when the image picker option has changed.
 */
function onImagePickerOptionChange(pickerOption) {
    $('#projectImagePreview').attr('src', $(pickerOption.option[0]).data('img-src'));

    // Update movement buttons based on which is selected
    let imageId = pickerOption.option.attr('id');
    let image = document.getElementById(imageId);
    onUpdateImageButtons(image);
}

function onUpdateImageButtons(selectedImage) {
    if(!selectedImage) return;
    if(selectedImage == selectedImage.parentElement.firstElementChild) 
        document.getElementById('btnUpSelectedImage').disabled = true;
    else
        document.getElementById('btnUpSelectedImage').disabled = false;
    if(selectedImage == selectedImage.parentElement.lastElementChild) 
        document.getElementById('btnDownSelectedImage').disabled = true;
    else
        document.getElementById('btnDownSelectedImage').disabled = false;
}


/**
 * Handles a form submission for uploading a new image to associate with the project.
 */
function onAddNewImageFormSubmit() {
    let form = new FormData(this);
    form.append('action', 'addProjectImage');

    api.post('/project-images.php', form, true)
        .then(res => {
            snackbar(res.message, 'success');
            onUploadImageSuccess(res.content.id);
        })
        .catch(err => {
            snackbar(err.message, 'error');
            $('#btnUploadImage').attr('disabled', false);
            $('#formAddNewImageLoader').hide();
        });

    $('#btnUploadImage').attr('disabled', true);
    $('#formAddNewImageLoader').show();

    return false;
}
$('#formAddNewImage').submit(onAddNewImageFormSubmit);

/**
 * Handles HTML rendering DOM manipulation after a successful upload of a new project image
 * @param {string} id the ID of the newly uploaded image
 */
function onUploadImageSuccess(id) {
    $('#btnUploadImage').attr('disabled', false);
    $('#formAddNewImageLoader').hide();
    $('#btnDeleteSelectedImage').show();
    $('#btnUpSelectedImage').show();
    $('#btnDownSelectedImage').show();
    let name = $('#labelImageFile').text();
    $('#selectProjectImages').append(
        $(`
        <option
            id='${id}'
            data-img-src='downloaders/project-images?id=${id}'
            data-img-class='project-image-thumbnail'
            data-img-alt='${name}'
            value='${id}'>
            ${name}
        </option>
    `)
    );
    $('#selectProjectImages').val(id);
    initializeImagePicker();
}

/**
 * Handles deleting an image from the project by sending a request to the server for the project image to be deleted.
 */
function onDeleteSelectedImageButtonClick() {
    let res = confirm('You are about to delete the currently selected image. This action is not reversible');
    if (!res) return;

    let form = new FormData();
    let id = $('#selectProjectImages').val();
    form.append('action', 'deleteProjectImage');
    form.append('projectId', $('#projectId').val());
    form.append('imageId', id);

    api.post('/project-images.php', form, true)
        .then(res => {
            $(`option[id=${id}]`).remove();
            initializeImagePicker();
            snackbar(res.message, 'success');
            $('#labelImageFile').text('Choose a new file to upload');
            $('#projectImagePreview').attr('src', '');
            if ($('#selectProjectImages option').length == 0) {
                $('#btnDeleteSelectedImage').hide();
                $('#btnUpSelectedImage').hide();
                $('#btnDownSelectedImage').hide();
            }
        })
        .catch(err => {
            snackbar(err.message, 'error');
        });
}
$('#btnDeleteSelectedImage').click(onDeleteSelectedImageButtonClick);

/**
 * Handles moving an image forward in the project by sending a request to the server for the project image to be moved.
 */
function onMoveSelectedImageUpButtonClick() {
    $('#btnUpSelectedImage').prop('disabled', true);
    $('#btnDownSelectedImage').prop('disabled', true);
    
    let form = new FormData();
    let id = $('#selectProjectImages').val();
    form.append('action', 'moveProjectImage');
    form.append('projectId', $('#projectId').val());
    form.append('imageId', id);
    form.append('direction', 'up')

    api.post('/project-images.php', form, true)
        .then(() => {
            $(`option[id=${id}]`).insertBefore($(`option[id=${id}]`).prev());
            initializeImagePicker();
        })
        .catch(err => {
            snackbar(err.message, 'error');
        })
        .finally(() => {
            let select = document.querySelector('#selectProjectImages');
            let selectedImage = select.options[select.selectedIndex];
            onUpdateImageButtons(selectedImage);
        });
}
$('#btnUpSelectedImage').click(onMoveSelectedImageUpButtonClick);

/**
 * Handles moving an image back in the project by sending a request to the server for the project image to be moved.
 */
function onMoveSelectedImageDownButtonClick() {
    $('#btnUpSelectedImage').prop('disabled', true);
    $('#btnDownSelectedImage').prop('disabled', true);

    let form = new FormData();
    let id = $('#selectProjectImages').val();
    form.append('action', 'moveProjectImage');
    form.append('projectId', $('#projectId').val());
    form.append('imageId', id);
    form.append('direction', 'down')

    api.post('/project-images.php', form, true)
        .then(() => {
            $(`option[id=${id}]`).insertAfter($(`option[id=${id}]`).next());
            initializeImagePicker();
        })
        .catch(err => {
            snackbar(err.message, 'error');
        })
        .finally(() => {
            let select = document.querySelector('#selectProjectImages');
            let selectedImage = select.options[select.selectedIndex];
            onUpdateImageButtons(selectedImage);
        });
}
$('#btnDownSelectedImage').click(onMoveSelectedImageDownButtonClick);

/**
 * Detects a change in the radio button select for artifact types and hides/shows the appropriate input
 */
function onArtifactTypeChange() {
    let val = $(this).val();
    let $divFile = $('#divNewArtifactFile');
    let $inputFile = $('#artifactFile');
    let $divLink = $('#divNewArtifactLink');
    let $inputLink = $('#artifactLink');

    switch (val) {
        case 'file':
            $divLink.hide();
            $inputLink.attr('required', false);
            $inputFile.attr('required', true);
            $divFile.show();
            break;

        case 'link':
            $divFile.hide();
            $inputLink.attr('required', true);
            $inputFile.attr('required', false);
            $divLink.show();
            break;
    }
}
$('input[name=artifactType]').change(onArtifactTypeChange);

/**
 * Handler triggered when a new file for an artifact is selected. Changes the label to display the file name
 */
function onArtifactFileChange() {
    // Show the name of the file
    if (this.files.length > 0) {
        $('#labelArtifactFile').text(this.files[0].name);
    }
}
$('#artifactFile').change(onArtifactFileChange);

/**
 * Sends a request to the server to add a new artifact to a project. This AJAX request has content type urlformencoded.
 */
function onAddNewArtifactSubmit() {
    let form = new FormData(this);
    form.append('action', 'addArtifact');

    api.post('/artifacts.php', form, true)
        .then(res => {
            snackbar(res.message, 'success');
            onAddArtifactSuccess(
                res.content.id,
                form.get('name'),
                form.get('description'),
                form.get('artifactType'),
                form.get('artifactLink')
            );
        })
        .catch(err => {
            snackbar(err.message, 'error');
        });

    return false;
}
$('#formAddNewArtifact').submit(onAddNewArtifactSubmit);

/**
 * Called after a successful response from the server when adding an artifact to a project. Triggers dynamic HTML
 * rendering and clearing the artifact form.
 */
function onAddArtifactSuccess(id, name, description, type, link) {
    let $tbody = $('#tableBodyArtifacts');
    if (!$tbody.length) {
        // The table doesn't exist, we need to create it
        $('#pNoArtifacts').remove();
        $('#divAddNewArtifactContainer').before(`
            <table class='table table-artifacts'>
                <thead>
                    <th>Name</th>
                    <th>Description</th>
                    <th>Content</th>
                    <th></th>
                </thead>
                <tbody id='tableBodyArtifacts'>
                </tbody>
            <table>
        `);
        $tbody = $('#tableBodyArtifacts');
    }

    // Append the artifact to the bottom of the table
    contentHtml = '';
    switch (type) {
        case 'file':
            contentHtml = `
                <a href="downloaders/artifacts?id=${id}">Download Artifact File</a>
            `;
            break;

        case 'link':
            contentHtml = `
                <a href="${link}" target="_blank">Link to artifact</a>
            `;
            break;
    }
    $tbody.append(`
        <tr class="artifact-row" id="${id}">
            <td>${name}</td>
            <td>${description}</td>
            <td>${contentHtml}</td>
            <td>
                <button type='button' class='btn btn-sm btn-danger btn-delete-artifact' data-id='${id}'
                    onclick='onDeleteArtifact.call(this)'>
                    <i class='fas fa-trash'></i>
                </button>
            </td>
        </tr>
    `);

    $('#formAddNewArtifact')[0].reset();
    $('#labelArtifactFile').text('Choose artifact file');
    onArtifactTypeChange.call($('#formAddNewArtifact input[name=artifactType')[0]);
}

/**
 * Sends a request to the server to delete an artifact when the delete button for an artifact has been clicked.
 */
function onDeleteArtifact() {
    let id = $(this).data('id');

    let body = new FormData();
    body.append('action', 'deleteArtifact');
    body.append('artifactId', id);
    body.append('projectId', $('#projectId').val());

    api.post('/artifacts.php', body, true)
        .then(res => {
            snackbar(res.message, 'success');
            $(`#${id}`).remove();
        })
        .catch(err => {
            snackbar(err.message, 'error');
        });
}
$('.btn-delete-artifact').click(onDeleteArtifact);

/**
 * Sends an request to invite a user to collaborate on a project
 */
function onSendInviteFormSubmit() {
    let body = {
        action: 'inviteUser'
    };

    let form = new FormData(this);

    for (const [key, value] of form.entries()) {
        body[key] = value;
    }

    api.post('/showcase-projects.php', body)
        .then(res => {
            snackbar(res.message, 'success');
        })
        .catch(err => {
            snackbar(err.message, 'error');
        });

    return false;
}
$('#formSendInvite').submit(onSendInviteFormSubmit);

/**
 * Removes a collaborator from project
 */
function removeCollaborator(userid, projectid) {
    let body = {
        action: 'removeUser',
		userId: userid,
		projectId: projectid
    };

    api.post('/showcase-projects.php', body)
        .then(res => {
            snackbar(res.message, 'success');
			$('#collab'.userid).hide();
        })
        .catch(err => {
            snackbar(err.message, 'error');
        });

    return false;
}

/**
 * Give an award to a project
*/ 
function giveAward(projectid) {
    awardid = $('#newaward').val();
	
	let body = {
        action: 'giveAward',
		awardId: awardid,
		projectId: projectid
    };

    api.post('/showcase-projects.php', body)
        .then(res => {
            snackbar(res.message, 'success');
        })
        .catch(err => {
            snackbar(err.message, 'error');
        });

    return false;
}

/**
 * Give an award to a project
*/
function removeAward(awardid, projectid) {
    let body = {
        action: 'removeAward',
		awardId: awardid,
		projectId: projectid
    };

    api.post('/showcase-projects.php', body)
        .then(res => {
            snackbar(res.message, 'success');
        })
        .catch(err => {
            snackbar(err.message, 'error');
        });

    return false;
}

/**
 * Deletes the project entirely
 */
function onDeleteProjectClick() {
    let res = confirm('You are about to delete a project completely. This action cannot be undone.  Please confirm with your project collaborators that this is something you should do.');
    if(!res) return false;

    let projectId = $('#projectId').val();
    let body = {
        action: 'deleteProject',
        id: projectId
    };

    api.post('/showcase-projects.php', body)
        .then(res => {
            snackbar(res.message, 'success');
            setTimeout(function () { location.reload(true); }, 1000);
        })
        .catch(err => {
            snackbar(err.message, 'error');
        });

    return false;
}
$('#btnDeleteProject').click(onDeleteProjectClick);

function onHideProjectClick(id) {
    let projectId = $('#projectId').val();
    body = {
        action: 'updateVisibility',
        publish: false,
        id: projectId
    };
    api.post('/showcase-projects.php', body).then(res => {
        snackbar(res.message, 'success');
        document.getElementById('hiddenAlert').style.display = '';
    }).catch(err => {
        snackbar(err.message, 'error');
    });


}
$('#btnHideProject').click(onHideProjectClick);

/**
 * Sends a request to the server to update the visibility of the user on the project
 */
function onToggleVisibility() {
    let visible = $(this).data('visible');
    let userId = $('#userId').val();
    let projectId = $('#projectId').val();
    if (visible) {
        // Send a request to hide the user from the project
        let body = {
            action: 'hideUserFromProject',
            userId,
            projectId
        };

        api.post('/showcase-projects.php', body)
            .then(res => {
                snackbar(res.message, 'success');
                onToggleVisibilitySuccess(false);
            })
            .catch(err => {
                snackbar(err.message, 'error');
            });
    } else {
        // send a request to show the user on the project
        // Send a request to hide the user from the project
        let body = {
            action: 'showUserOnProject',
            userId,
            projectId
        };

        api.post('/showcase-projects.php', body)
            .then(res => {
                snackbar(res.message, 'success');
                onToggleVisibilitySuccess(true);
            })
            .catch(err => {
                snackbar(err.message, 'error');
            });
    }
}
$('#btnToggleVisibility').click(onToggleVisibility);

/**
 * Updates the HTML button displaying whether the user is visible on the project or not
 * @param {boolean} isVisible indicates whether the user is NOW visible or not
 */
function onToggleVisibilitySuccess(isVisible) {
    $button = $('#btnToggleVisibility');
    if (isVisible) {
        $button.attr('class', 'btn btn-sm btn-success');
        $button.data('visible', true);
        $button.html(`
            <i class='far fa-check-circle'></i>&nbsp;&nbsp;Visible
        `);
    } else {
        $button.attr('class', 'btn btn-sm btn-light');
        $button.data('visible', false);
        $button.html(`
            <i class='far fa-times-circle'></i>&nbsp;&nbsp;Not Visible
        `);
    }
}

autocomplete('keywords', keywords, item => {
    let $kInput = $('input[name=keywords]');
    let current = $kInput.val().split(',');
    if (current.includes(item.id)) {
        return false;
    }
    if ($kInput.val() === '') {
        $kInput.val(item.id);
    } else {
        $kInput.val($kInput.val() + ',' + item.id);
    }
    $('#noKeywordsText').hide();
    let $keyword = $(`
    <div class="keyword" id="${item.id}">
        ${item.name}
        <i class="fas fa-times-circle" data-id="${item.id}"></i>
    </div>
    `);
    $keyword.find('i').click(function () {
        onDeleteTag($(this).data('id'));
    });
    $('.project-keywords').append($keyword);
});

/**
 * Removes a keyword and its associated chip element from the DOM.
 * 
 * @param {number} id the ID of the keywords to remove
 */
function onDeleteTag(id) {
    let re = new RegExp(id + '(,)?', 'g');
    let $keywords = $('input[name=keywords]');
    $keywords.val($keywords.val().replace(re, ''));
    $(`#${id}`).remove();
    if ($keywords.val() == '') {
        $('#noKeywordsText').show();
    }
}
$('.keyword i').click(function () {
    onDeleteTag($(this).data('id'));
});

/**
 * Function for using autocomplete for keywords. Requires jQuery/
 *
 *
 * @param {string} inputId the input element ID
 * @param {array} values the array of possible values to display. Each value is an object of the form { id, name }
 * @param {string} valueInputId the ID of the hidden input element keeping track of the selected values
 */
function autocomplete(inputId, values, onItemSelectCb) {
    // Capture the currently 'focused' item in the autocomplete list with a variable
    let currentFocus = -1;
    let $inp = $(`#${inputId}`);

    $inp.on('input', displayKeywords);
    $inp.on('click', function(e) {
        e.preventDefault();
        e.stopImmediatePropagation();
        displayKeywords.call(this);
    });
    $inp.keydown(function (event) {
        let $items = $('.autocomplete-items div');
        switch (event.keyCode) {
            // Down
            case 40:
                currentFocus++;
                setActive($items);
                break;

            // Up
            case 38:
                currentFocus--;
                setActive($items);
                break;

            // Enter
            case 13:
                event.preventDefault();
                if (currentFocus > -1) {
                    $items[currentFocus].click();
                }
                return false;
        }
    });

    function displayKeywords() {
        console.log('entering');
        let $autocompleteItems = $('.autocomplete-items');

        // Empty any currently displayed values
        if ($autocompleteItems) {
            $autocompleteItems.remove();
        }

        currentFocus = -1;
        $autocompleteItems = $("<div class='autocomplete-items'></div>");
        $(this.parentNode).append($autocompleteItems);

        // Loop through the possible values and look for a match in names
        for (let val of values) {
            if (this.value === '' || this.value.toLowerCase() === val.name.toLowerCase().substr(0, this.value.length)) {
                // Found a substring match, add it as an option
                let $item = $(`<div id="${val.id}"><strong>${val.name}</strong></div>`);
                $item.click(() => {
                    // The item was selected. Invoke the callback with the value. If not callback is defined, set
                    // the input's value to the name of the object
                    if (onItemSelectCb) {
                        onItemSelectCb(val);
                    } else {
                        this.value = val.name;
                    }
                    // Close the autocomplete list
                    $autocompleteItems.remove();
                    this.value = '';
                });
                $autocompleteItems.append($item);
            }
        }
    }
    function setActive($items) {
        $items.removeClass('autocomplete-active');
        $($items[currentFocus]).addClass('autocomplete-active');
    }
    $(document).click(() => {
        $('.autocomplete-items').remove();
        $inp.val('');
    });
}

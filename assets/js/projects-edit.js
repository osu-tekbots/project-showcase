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
 * Sends a request to the server to add a new artifact to a project. This AJAX request has content type urlformencoded.
 */
function onAddNewArtifactSubmit() {
    let form = new FormData(this);
    form.append('action', 'addArtifact');

    api.post('/artifacts.php', form, true)
        .then(res => {
            snackbar(res.message, 'success');
            // TODO: add new row to table and clear form
        })
        .catch(err => {
            snackbar(err.message, 'error');
        });

    return false;
}
$('#formAddNewArtifact').submit(onAddNewArtifactSubmit);

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

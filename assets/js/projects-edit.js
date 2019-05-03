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

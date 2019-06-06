/**
 * Handles the form submission for creating a new user by making a request to the API server to create a new profile.
 */
function onNewUserFormSubmit() {
    let formId = 'formNewUser';
    let body = serializeFormAsJson(formId);

    api.post('/profiles.php', body)
        .then(res => {
            snackbar(res.message, 'success');
            document.getElementById(formId).reset();
        })
        .catch(err => {
            snackbar(err.message, 'error');
        });

    return false;
}
$('#formNewUser').submit(onNewUserFormSubmit);

/**
 * Handles a click on the user type button in the admin user table to promote/demote a user to/from admin status.
 */
function onUserTypeClick() {
    $btn = $(this);
    let uid = $btn.data('id');
    let isAdmin = $btn.data('admin');
    let willBeAdmin = !isAdmin;
    let body = {
        uid,
        action: 'updateUserType',
        admin: willBeAdmin
    };
    api.post('/profiles.php', body).then(res => {
        snackbar(res.message, 'success');
        $btn.data('admin', willBeAdmin);
        if(willBeAdmin) {
            $btn.removeClass('btn-light').addClass('btn-success');
            $btn.text('Admin');
            $btn.tooltip('hide').attr('data-original-title', 'Demote to Student').tooltip('show');
        } else {
            $btn.removeClass('btn-success').addClass('btn-light');
            $btn.text('Student');
            $btn.tooltip('hide').attr('data-original-title', 'Promote to Admin').tooltip('show');
        }
    }).catch(err => {
        snackbar(err.message, 'error');
    });
}
$('.btn-user-type').click(onUserTypeClick);

/**
 * Handles updating a projects visibility from published to hidden and visa-versa.
 */
function onProjectPublishedClick() {
    let published = $(this).data('published');
    let id = $(this).data('id');
    body = {
        action: 'updateVisibility',
        publish: !published,
        id
    };
    api.post('/showcase-projects.php', body).then(res => {
        snackbar(res.message, 'success');
        $(this).data('published', !published);
        if(published) {
            $(this).removeClass('btn-success').addClass('btn-danger');
            $(this).text('Hidden');
            $(this).tooltip('hide').attr('data-original-title', 'Publish').tooltip('show');
        } else {
            $(this).removeClass('btn-danger').addClass('btn-success');
            $(this).text('Published');
            $(this).tooltip('hide').attr('data-original-title', 'Hide').tooltip('show');
        }
    }).catch(err => {
        snackbar(err.message, 'error');
    });
}
$('.btn-published').click(onProjectPublishedClick);

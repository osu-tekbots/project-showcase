function onBrowseInputSubmit() {
    let data = serializeFormAsJson(this.id);
    data.action = 'browseProjects';

    api.post('/showcase-projects.php', data)
        .then(res => {
            hideSuggestions();
            showResults();
            clearResults();
            $('#results').html(res.content.html);
        })
        .catch(err => {
            snackbar(err.message, 'error');
        });

    return false;
}
$('#formBrowse').submit(onBrowseInputSubmit);

function hideSuggestions() {
    $('#suggestions').hide();
}

function clearResults() {
    $('#results').empty();
}

function showResults() {
    $('#results').show();
}

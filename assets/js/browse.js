/**
 * Sends a request for search results of browsable showcase projects based on a user-input search query. On success, it
 * will render the results on the page.
 */
function onBrowseInputSubmit() {
    $("#loading").show();
    let data = serializeFormAsJson(this.id);
    data.action = 'browseProjects';

    api.post('/showcase-projects.php', data)
        .then(res => {
            hideSuggestions();
            showResults();
            clearResults();
            $('#resultsContent').html(res.content.html);
            $("#loading").hide();
        })
        .catch(err => {
            $("#loading").hide();
            snackbar(err.message, 'error');
        });
    return false;
}

function onFilterSubmit() {
    const url = new URL(window.location.href);
    const params = new URLSearchParams(url.search);

    params.set('category', 'paramValue');

    const newUrl = url.pathname + '?' + params.toString();

    window.history.replaceState({}, '', newUrl);
}
$('#formBrowse').submit(onBrowseInputSubmit);
$('#formFilter').submit(onFilterSubmit);

function hideSuggestions() {
    $('#suggestions').hide();
}

function clearResults() {
    $('#resultsContent').empty();
}

function showResults() {
    $('#results').show();
}
